const CACHE_NAME = 'sigdip-pwa-cache-v3';
const STATIC_ASSETS = [
  '/icon_png.png',
  'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js',
  'https://unpkg.com/html5-qrcode'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[Service Worker] Precaching static assets...');
      return Promise.allSettled(
        STATIC_ASSETS.map(url => {
          return cache.add(url).catch(err => {
            console.warn('[Service Worker] Failed to cache asset:', url, err);
          });
        })
      );
    }).then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache:', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event - Stale-While-Revalidate Strategy (Instant offline loading)
self.addEventListener('fetch', event => {
  // Skip non-GET requests (e.g. POST forms)
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip non-http schemas
  if (!event.request.url.startsWith('http')) {
    return;
  }

  // Stale-While-Revalidate
  event.respondWith(
    caches.match(event.request).then(cachedResponse => {
      // Create a promise to fetch from network and update cache
      const fetchPromise = fetch(event.request)
        .then(networkResponse => {
          // If successful (status 200), dynamically cache/update the page
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, responseToCache);
            });
          }
          return networkResponse;
        })
        .catch(err => {
          console.warn('[Service Worker] Fetch failed:', event.request.url, err);
          throw err;
        });

      // Serve instantly from cache if available, else wait for network
      if (cachedResponse) {
        return cachedResponse;
      }

      // If no cached response, wait for network, and handle failure with a fallback
      return fetchPromise.catch(() => {
        const acceptHeader = event.request.headers.get('accept') || '';
        if (acceptHeader.includes('text/html')) {
          console.log('[Service Worker] Serving offline fallback for:', event.request.url);
          return caches.match('/admin/dashboard');
        }
      });
    })
  );
});
