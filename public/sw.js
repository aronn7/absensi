/*
 * Service worker — Sistem Absensi Digital (PWA)
 * Strategi:
 *  - App shell (offline fallback): cache-first
 *  - Aset build (css/js/icon/font): stale-while-revalidate
 *  - Navigasi/halaman & API: network-first (data selalu segar), fallback ke offline.html
 */
const VERSION = 'v1';
const SHELL_CACHE = `shell-${VERSION}`;
const ASSET_CACHE = `assets-${VERSION}`;
const PAGE_CACHE = `pages-${VERSION}`;

const SHELL_ASSETS = [
    '/offline.html',
    '/manifest.json',
    '/favicon.svg',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL_ASSETS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => ![SHELL_CACHE, ASSET_CACHE, PAGE_CACHE].includes(key))
                    .map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

function isBuildAsset(url) {
    return url.pathname.startsWith('/build/') || /\.(css|js|woff2?|png|svg|ico|webp)$/.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Halaman / navigasi: network-first, fallback offline
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const copy = response.clone();
                    caches.open(PAGE_CACHE).then((cache) => cache.put(request, copy));
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) => cached || caches.match('/offline.html'))
                )
        );
        return;
    }

    // Aset build & ikon: stale-while-revalidate
    if (isBuildAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fresh = fetch(request)
                    .then((response) => {
                        const copy = response.clone();
                        caches.open(ASSET_CACHE).then((cache) => cache.put(request, copy));
                        return response;
                    })
                    .catch(() => cached);
                return cached || fresh;
            })
        );
        return;
    }

    // Lainnya (mis. favicon svg): hanya cache bila sukses
    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(ASSET_CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});
