// Service Worker for PWA offline capability
const CACHE_NAME = 'qr-scanner-v1';
const urlsToCache = [
    '/poc/',
    '/poc/index.html',
    '/poc/app.js',
    '/poc/style.css',
    '/poc/manifest.json'
];

// Install event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
            .then(() => self.skipWaiting())
    );
});

// Activate event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - Network first, fallback to cache
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Network first strategy for API calls
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (!response || response.status !== 200 || response.type === 'error') {
                        return response;
                    }
                    return caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, response.clone());
                        return response;
                    });
                })
                .catch(() => caches.match(event.request))
        );
    } else {
        // Cache first for static assets
        event.respondWith(
            caches.match(event.request)
                .then(response => response || fetch(event.request))
                .catch(() => new Response('Offline', { status: 503 }))
        );
    }
});
