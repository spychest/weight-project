const CACHE_NAME = 'weight-project-shell-v2';
const STATIC_ASSETS = [
    '/offline.html',
    '/favicon.svg',
    '/manifest.webmanifest',
    '/css/style.css?v=20260928-1'
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(cacheNames.filter((cacheName) => cacheName !== CACHE_NAME).map((cacheName) => caches.delete(cacheName))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request).catch(() => caches.match('/offline.html')));
        return;
    }

    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin === self.location.origin && ['style', 'script', 'image', 'font'].includes(event.request.destination)) {
        event.respondWith(caches.match(event.request).then((cachedResponse) => cachedResponse || fetch(event.request)));
    }
});

self.addEventListener('push', (event) => {
    const payload = event.data ? event.data.json() : {};
    event.waitUntil(self.registration.showNotification(payload.title || 'Mon suivi bien-être', {
        body: payload.body || 'Pense à compléter ton suivi lorsque tu en as envie.',
        icon: '/favicon.svg',
        badge: '/favicon.svg',
        data: { url: payload.url || '/dashboard' },
        tag: 'tracking-reminder',
        renotify: false
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const destination = new URL(event.notification.data?.url || '/dashboard', self.location.origin).href;
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            const existingClient = clients.find((client) => client.url.startsWith(self.location.origin));
            return existingClient ? existingClient.focus().then(() => existingClient.navigate(destination)) : self.clients.openWindow(destination);
        })
    );
});
