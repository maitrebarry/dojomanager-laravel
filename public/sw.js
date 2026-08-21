/* DojoManager - Service Worker PWA
 * Pages dynamiques : réseau d'abord, repli hors-ligne.
 * Assets statiques : cache d'abord, mise à jour en arrière-plan.
 */
const CACHE = 'dojomanager-static-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [
    '/offline.html',
    '/images/icons/dojo-192.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.map((key) => (key === CACHE ? null : caches.delete(key)))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') {
        return;
    }

    let url;
    try {
        url = new URL(req.url);
    } catch (e) {
        return;
    }

    if (url.origin !== self.location.origin) {
        return;
    }

    // Les pages de l'app sont dynamiques (session, données) : jamais servies depuis
    // le cache, seulement une page hors-ligne de repli si le réseau est indisponible.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (/\.(?:css|js|mjs|png|jpg|jpeg|gif|webp|avif|svg|ico|woff2?|ttf|eot)$/i.test(url.pathname)) {
        event.respondWith(
            caches.match(req).then((cached) => {
                const fromNetwork = fetch(req)
                    .then((res) => {
                        if (res && res.status === 200 && res.type === 'basic') {
                            const copy = res.clone();
                            caches.open(CACHE).then((cache) => cache.put(req, copy));
                        }
                        return res;
                    })
                    .catch(() => cached);
                return cached || fromNetwork;
            })
        );
    }
});
