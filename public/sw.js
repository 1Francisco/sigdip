const CACHE_NAME = 'sigdip-pwa-cache-v2';
const STATIC_ASSETS = [
  '/icon_png.png',
  'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js',
  'https://unpkg.com/html5-qrcode'
];

// Install Event - Cache only public static assets (avoids redirect/login issues)
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[Service Worker] Precaching public static assets...');
      return Promise.allSettled(
        STATIC_ASSETS.map(url => {
          return cache.add(url).catch(err => {
            console.warn('[Service Worker] Failed to cache static asset:', url, err);
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

// Fetch Event (Network First, with Fallback to Cache)
self.addEventListener('fetch', event => {
  // Skip non-GET requests (e.g. POST for forms)
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip chrome-extension or other non-http schemas
  if (!event.request.url.startsWith('http')) {
    return;
  }

  // Strategy: Network First, fallback to Cache
  event.respondWith(
    fetch(event.request)
      .then(networkResponse => {
        // If successful and response is OK, dynamically cache/update the page or asset
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // Offline: try to serve from Cache
        return caches.match(event.request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          // Fallback if navigating offline and page is not cached
          if (event.request.mode === 'navigate') {
            console.log('[Service Worker] Navigating offline, trying fallback...');
            // Match main entrypoint if specific page isn't in cache
            return caches.match('/admin/dashboard');
          }
        });
      })
  );
});
