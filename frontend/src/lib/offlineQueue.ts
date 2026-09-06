import { useEffect, useState } from 'react';
import { api, getToken } from './api';

/**
 * صف آفلاین واقعی (نه فقط کش خواندنی) — برای فرم‌های میدانی حساس مثل صدور
 * مجوز کار و ثبت گاز‌تست که ممکن است در نقاطی از سایت بدون اتصال اینترنت
 * (مثلاً داخل واحد فرآیندی) پر شوند. درخواست‌های نوشتنی (POST/PATCH) که با
 * خطای شبکه (نه خطای اعتبارسنجی سرور) مواجه شوند، در IndexedDB ذخیره و به
 * محض بازگشت اتصال (رویداد online، بارگذاری صفحه، یا هر ۳۰ ثانیه) به‌ترتیب
 * ثبت‌شان به سرور ارسال می‌شوند.
 */

const DB_NAME = 'safeops-offline';
const DB_VERSION = 1;
const STORE = 'mutation-queue';

export interface QueuedMutation {
  id: string;
  path: string;
  method: 'POST' | 'PATCH' | 'PUT' | 'DELETE';
  body: unknown;
  description: string;
  createdAt: string;
  attempts: number;
  lastError?: string;
}

type Listener = (queue: QueuedMutation[]) => void;
const listeners = new Set<Listener>();

function hasIndexedDb(): boolean {
  return typeof indexedDB !== 'undefined';
}

function openDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains(STORE)) {
        db.createObjectStore(STORE, { keyPath: 'id' });
      }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

/** یک تراکنش IndexedDB باز می‌کند، دقیقاً یک IDBRequest از store می‌سازد و نتیجه‌اش را برمی‌گرداند. */
async function withStore<T>(mode: IDBTransactionMode, fn: (store: IDBObjectStore) => IDBRequest<T>): Promise<T> {
  const db = await openDb();
  const tx = db.transaction(STORE, mode);
  const request = fn(tx.objectStore(STORE));
  return new Promise<T>((resolve, reject) => {
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
    tx.onerror = () => reject(tx.error);
    tx.onabort = () => reject(tx.error ?? new Error('IndexedDB transaction aborted'));
  });
}

export async function listQueue(): Promise<QueuedMutation[]> {
  if (!hasIndexedDb()) return [];
  try {
    const items = await withStore<QueuedMutation[]>('readonly', (store) => store.getAll());
    return items.sort((a, b) => a.createdAt.localeCompare(b.createdAt));
  } catch {
    return [];
  }
}

function notify() {
  void listQueue().then((queue) => listeners.forEach((l) => l(queue)));
}

/** برای UI: ثبت‌نام برای دریافت صف به‌روز؛ خروجی تابعِ لغو ثبت‌نام است. */
export function subscribeQueue(listener: Listener): () => void {
  listeners.add(listener);
  void listQueue().then(listener);
  return () => listeners.delete(listener);
}

function generateId(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) return crypto.randomUUID();
  return `q-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export interface EnqueueInput {
  path: string;
  method: QueuedMutation['method'];
  body: unknown;
  description: string;
}

export async function enqueueMutation(input: EnqueueInput): Promise<QueuedMutation> {
  const item: QueuedMutation = { id: generateId(), createdAt: new Date().toISOString(), attempts: 0, ...input };
  if (hasIndexedDb()) {
    await withStore('readwrite', (store) => store.put(item));
  }
  notify();
  scheduleFlush();
  return item;
}

async function removeFromQueue(id: string): Promise<void> {
  if (!hasIndexedDb()) return;
  await withStore('readwrite', (store) => store.delete(id));
  notify();
}

async function bumpAttempt(id: string, error: string): Promise<void> {
  if (!hasIndexedDb()) return;
  const existing = await withStore<QueuedMutation | undefined>('readonly', (store) => store.get(id));
  if (!existing) return;
  existing.attempts += 1;
  existing.lastError = error;
  await withStore('readwrite', (store) => store.put(existing));
  notify();
}

let flushing = false;

/** بازپخش صف به ترتیب ثبت؛ با اولین خطای شبکه متوقف می‌شود (احتمالاً هنوز آفلاین‌ایم). */
export async function flushQueue(): Promise<void> {
  if (flushing || !hasIndexedDb() || (typeof navigator !== 'undefined' && !navigator.onLine)) return;
  flushing = true;
  try {
    const queue = await listQueue();
    for (const item of queue) {
      try {
        const headers = new Headers({ Accept: 'application/json', 'Content-Type': 'application/json' });
        const token = getToken();
        if (token) headers.set('Authorization', `Bearer ${token}`);
        const response = await fetch(`/api${item.path}`, {
          method: item.method,
          headers,
          body: JSON.stringify(item.body),
        });
        if (!response.ok && response.status >= 400 && response.status < 500) {
          // خطای اعتبارسنجی/دسترسی دائمی است؛ تلاش دوباره کمکی نمی‌کند — از صف خارج می‌شود.
          await removeFromQueue(item.id);
          continue;
        }
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        await removeFromQueue(item.id);
      } catch (err) {
        await bumpAttempt(item.id, err instanceof Error ? err.message : 'خطای ناشناخته');
        break;
      }
    }
  } finally {
    flushing = false;
  }
}

let scheduled = false;
function scheduleFlush(delayMs = 300) {
  if (scheduled || typeof window === 'undefined') return;
  scheduled = true;
  window.setTimeout(() => {
    scheduled = false;
    void flushQueue();
  }, delayMs);
}

if (typeof window !== 'undefined') {
  window.addEventListener('online', () => void flushQueue());
  window.addEventListener('load', () => void flushQueue());
  window.setInterval(() => void flushQueue(), 30_000);
}

/**
 * یک درخواست نوشتنی را اجرا می‌کند؛ اگر با خطای شبکه (آفلاین) مواجه شود —
 * نه خطای اعتبارسنجی/مجوز از سرور — آن را در صف آفلاین ذخیره می‌کند تا
 * کاربر میدانی بدون از دست رفتن داده به کار خود ادامه دهد.
 */
export async function mutateOrQueue<T>(opts: EnqueueInput): Promise<{ queued: boolean; data?: T }> {
  try {
    const data = await api<T>(opts.path, { method: opts.method, body: JSON.stringify(opts.body) });
    return { queued: false, data };
  } catch (err) {
    const looksOffline = err instanceof TypeError || (typeof navigator !== 'undefined' && !navigator.onLine);
    if (!looksOffline) throw err;
    await enqueueMutation(opts);
    return { queued: true };
  }
}

/** برای نمایش نشانگر آفلاین/صف در پوستهٔ برنامه (Shell) و صفحات فرم. */
export function useOfflineQueue() {
  const [queue, setQueue] = useState<QueuedMutation[]>([]);
  const [online, setOnline] = useState(typeof navigator === 'undefined' ? true : navigator.onLine);

  useEffect(() => {
    const unsubscribe = subscribeQueue(setQueue);
    const onOnline = () => setOnline(true);
    const onOffline = () => setOnline(false);
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
    return () => {
      unsubscribe();
      window.removeEventListener('online', onOnline);
      window.removeEventListener('offline', onOffline);
    };
  }, []);

  return { queue, online, pendingCount: queue.length };
}
