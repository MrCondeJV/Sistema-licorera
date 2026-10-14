const CACHE_NAME = 'licorera-pos-v3';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Always let non-GET and mutations pass straight to server
  if (event.request.method !== 'GET') {
    return;
  }

  // HTML page navigations: ALWAYS fetch from network to guarantee fresh products and tenant data
  if (event.request.mode === 'navigate' || event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          return caches.match('/pos') || caches.match('/');
        })
    );
    return;
  }

  // Static assets (CSS, JS, Fonts): Network first with fallback to cache
  event.respondWith(
    fetch(event.request)
      .catch(() => {
        return caches.match(event.request);
      })
  );
});
