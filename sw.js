const CACHE_NAME = 'igs-v1.0.0';
const STATIC_ASSETS = [
  '/',
  '/app/auth/login.php',
  '/app/auth/register.php',
  '/manifest.json',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
];

// Install: cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS).catch(() => {}))
  );
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Fetch: network first, fall back to cache
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  // Always fetch PHP pages from network (they're dynamic)
  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match('/app/auth/login.php'))
    );
    return;
  }
  event.respondWith(
    caches.match(event.request).then(cached =>
      cached || fetch(event.request).then(response => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        return response;
      })
    )
  );
});
