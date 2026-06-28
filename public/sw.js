/**
 * Mazin Shoes ERP - service worker (offline shell).
 *
 * Strategy:
 *  - Precache a tiny static shell + offline fallback on install.
 *  - Network-first for navigations (always prefer fresh, authenticated HTML);
 *    fall back to the cached offline page when the network is unavailable.
 *  - Stale-while-revalidate for built static assets (Vite output under /build).
 *  - Never cache POST/PUT/PATCH/DELETE or non-GET requests, and never cache
 *    Livewire update calls so live data stays correct.
 */
const CACHE_VERSION = 'mazin-shell-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || url.pathname.startsWith('/livewire')) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.open(CACHE_VERSION).then(async (cache) => {
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        if (response && response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(() => cached);

                return cached || network;
            })
        );
    }
});
