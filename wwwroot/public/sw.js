const CACHE_NAME = 'marachain-v1.0.0';
const STATIC_ASSETS = [
  '/',
  '/login',
  '/register',
  '/inbox',
  '/outbox',
  '/transfers/new',
  '/profile',
  '/web/contacts',
  '/assets/css/main.css',
  '/assets/js/marachain-crypto.js',
  '/assets/js/marachain-validation.js',
  '/assets/js/dropzone-init.js',
  '/assets/images/logo.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      return cachedResponse || fetch(event.request).then((response) => {
        if (event.request.method === 'GET' && response.status === 200) {
          const clonedResponse = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, clonedResponse);
          });
        }
        return response;
      });
    })
  );
});
