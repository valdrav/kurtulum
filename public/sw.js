const CACHE_NAME = 'exportflow-v10';
const STATIC_ASSETS = [
    '/css/theme.css',
    '/vendor/tabler-icons/tabler-icons.min.css',
    '/vendor/tabler-icons/fonts/tabler-icons.woff2',
    '/vendor/tabler-icons/fonts/tabler-icons.woff',
    '/js/documents-upload.js',
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;

    const url = new URL(e.request.url);

    if (url.origin !== self.location.origin) {
        e.respondWith(
            caches.match(e.request).then((cached) => cached || fetch(e.request))
        );
        return;
    }

    const isStatic = url.pathname.startsWith('/css/')
        || url.pathname.startsWith('/js/')
        || url.pathname.startsWith('/vendor/')
        || url.pathname.startsWith('/build/')
        || url.pathname.match(/\.(css|js|png|jpg|jpeg|webp|svg|woff2?|ttf)$/i);

    if (isStatic) {
        e.respondWith(
            caches.match(e.request).then((cached) => cached || fetch(e.request).then((response) => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(e.request, clone));
                }
                return response;
            }))
        );
        return;
    }

    e.respondWith(
        fetch(e.request).catch(() => caches.match('/login'))
    );
});
