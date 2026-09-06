// =====================================================================
// Aria SafeOps — Service Worker (PWA حداقلی ولی واقعی)
//   ۱) پیش‌کش پوستهٔ اپ برای بازکردن آفلاین
//   ۲) fallback به‌ پوسته (index.html) برای هر ناوبری آفلاین — تا React
//      Router بتواند مسیر را کلاینت‌ساید رندر کند (وگرنه مرورگر صفحهٔ خطای
//      پیش‌فرض "شما آفلاین هستید" را نشان می‌دهد).
//   ۳) کش cache-first برای assetهای هم‌مبدأ (JS/CSS با هش Vite) — به‌محض
//      دریافت موفق هر فایل، برای بازدیدهای بعدیِ آفلاین ذخیره می‌شود.
//   ۴) برای درخواست‌های /api/ همیشه شبکه اول — نوشتن‌ها (POST/PATCH) به صف
//      آفلاین در IndexedDB سپرده می‌شود (نگاه کنید: src/lib/offlineQueue.ts)،
//      نه این Service Worker.
// =====================================================================
const CACHE = 'safeops-shell-v2';
const SHELL_URL = '/';
const SHELL = [SHELL_URL, '/manifest.webmanifest', '/icon.svg'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)))).then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);

  // ناوبری بین صفحات SPA — آفلاین یعنی fallback به shell، نه خطای مرورگر.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(SHELL_URL).then((cached) => cached ?? Response.error())),
    );
    return;
  }

  if (url.pathname.startsWith('/api/')) {
    event.respondWith(fetch(request).catch(() => caches.match(request)));
    return;
  }

  event.respondWith(
    caches.match(request).then(
      (cached) =>
        cached ||
        fetch(request).then((response) => {
          if (response.ok && url.origin === self.location.origin) {
            const copy = response.clone();
            void caches.open(CACHE).then((cache) => cache.put(request, copy));
          }
          return response;
        }),
    ),
  );
});
