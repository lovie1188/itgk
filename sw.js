const CACHE_NAME = 'itgk-portal-v1';
const ASSETS_TO_CACHE = [
  '/certificate/dashboard',
  '/certificate/assets/css/style.css',
  '/certificate/assets/vendor/bootstrap-5.3.3/css/bootstrap.min.css',
  '/certificate/assets/vendor/fontawesome-6.0.0/css/all.min.css',
  '/certificate/assets/vendor/bootstrap-5.3.3/js/bootstrap.bundle.min.css'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE).catch(() => {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  
  // Do not intercept non-http(s) requests or API POSTs
  if (!event.request.url.startsWith('http')) return;

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
          return networkResponse;
        }
        // Optionally update cache in background
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
        return networkResponse;
      })
      .catch(async () => {
        const cachedResponse = await caches.match(event.request);
        if (cachedResponse) {
          return cachedResponse;
        }
        // Fallback for HTML pages when completely offline
        if (event.request.headers.get('accept')?.includes('text/html')) {
          const dashboardCache = await caches.match('/certificate/dashboard');
          if (dashboardCache) return dashboardCache;
        }
        return new Response('Network error occurred', {
          status: 503,
          statusText: 'Service Unavailable',
          headers: new Headers({ 'Content-Type': 'text/plain' })
        });
      })
  );
});
