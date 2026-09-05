const TOKEN_KEY = 'safeops.token';
const OFFLINE_PREFIX = 'safeops.offline.';

export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const setToken = (t: string) => localStorage.setItem(TOKEN_KEY, t);
export const clearToken = () => localStorage.removeItem(TOKEN_KEY);

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers);
  const token = getToken();
  headers.set('Accept', 'application/json');
  if (token) headers.set('Authorization', `Bearer ${token}`);
  if (init.body && !headers.has('Content-Type')) headers.set('Content-Type', 'application/json');
  try {
    const response = await fetch(`/api${path}`, { ...init, headers });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const message = data.message || data.detail || data['hydra:description'] || 'خطای سرور';
      throw new Error(typeof message === 'string' ? message : 'خطای سرور');
    }
    const method = (init.method ?? 'GET').toUpperCase();
    if (method === 'GET') {
      localStorage.setItem(OFFLINE_PREFIX + path, JSON.stringify(data));
    }
    return data as T;
  } catch (err) {
    const method = (init.method ?? 'GET').toUpperCase();
    if (method === 'GET') {
      const cached = localStorage.getItem(OFFLINE_PREFIX + path);
      if (cached) return JSON.parse(cached) as T;
    }
    throw err;
  }
}

export function patch<T>(path: string, body: unknown): Promise<T> {
  return api<T>(path, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/merge-patch+json' },
    body: JSON.stringify(body),
  });
}

export function collection<T>(data: { member?: T[]; 'hydra:member'?: T[] } | T[]): T[] {
  if (Array.isArray(data)) return data;
  return data.member ?? data['hydra:member'] ?? [];
}

export function iriId(value: string | { id?: string } | null | undefined): string {
  if (!value) return '';
  if (typeof value === 'string') return value.split('/').pop() ?? value;
  return value.id ?? '';
}
