/*
 * Tsukimori — script d'arrière-plan de l'application installable (service worker).
 *
 * Ce qu'il fait :
 *   - garde sur l'appareil les styles, scripts, polices et icônes (fichiers dont le nom
 *     change à chaque version) pour que l'application s'ouvre vite ;
 *   - affiche une page « hors connexion » quand le réseau manque ;
 *   - affiche les notifications push (titre et adresse seulement) et ouvre la bonne page
 *     quand on les touche.
 *
 * Ce qu'il ne fait volontairement pas : garder des pages du site. Les scènes privées, les
 * messages et les fiches ne sont jamais copiés sur l'appareil par ce script ; chaque page
 * est demandée au serveur, qui vérifie qui y a droit.
 *
 * Changer VERSION à chaque modification de ce fichier ou de hors-ligne.html.
 */
const VERSION = 'v5';
const SHELL = `tsukimori-coquille-${VERSION}`;
// Les fichiers gardés suivent aussi la version : une nouvelle palette ou de nouvelles
// icônes remplacent les anciennes au lieu de cohabiter avec elles.
const ASSETS = `tsukimori-fichiers-${VERSION}`;
const OFFLINE_URL = '/hors-ligne.html';
const MAX_ASSETS = 120;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL)
            .then((cache) => cache.addAll([OFFLINE_URL, '/favicon.svg', '/icones/icone-192.png', '/icones/badge-96.png']))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('tsukimori-') && key !== SHELL && key !== ASSETS)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

// Styles, scripts et polices (noms versionnés) ; les illustrations restent au cache du navigateur.
function isAsset(url) {
    return (url.pathname.startsWith('/build/assets/') && /\.(css|js|woff2)$/.test(url.pathname))
        || url.pathname.startsWith('/icones/')
        || url.pathname === '/favicon.svg';
}

async function trimAssets() {
    const cache = await caches.open(ASSETS);
    const keys = await cache.keys();
    // Les plus anciens d'abord : les fichiers d'une version précédente partent en premier.
    await Promise.all(keys.slice(0, Math.max(0, keys.length - MAX_ASSETS)).map((key) => cache.delete(key)));
}

async function fromCacheThenNetwork(request) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    const response = await fetch(request);
    if (response.ok && response.type === 'basic') {
        const cache = await caches.open(ASSETS);
        await cache.put(request, response.clone());
        trimAssets();
    }
    return response;
}

async function pageOrOffline(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const offline = await caches.match(OFFLINE_URL);
        return offline || Response.error();
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }
    if (request.mode === 'navigate') {
        event.respondWith(pageOrOffline(request));
        return;
    }
    if (isAsset(url)) {
        event.respondWith(fromCacheThenNetwork(request));
    }
});

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (error) {
        data = {};
    }

    event.waitUntil(self.registration.showNotification(data.title || 'Tsukimori', {
        body: data.body || undefined,
        icon: '/icones/icone-192.png',
        badge: '/icones/badge-96.png',
        tag: data.tag || undefined,
        lang: 'fr',
        data: { url: data.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/', self.location.origin);
    if (target.origin !== self.location.origin) {
        return;
    }

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windows) {
            if (new URL(client.url).origin === self.location.origin && 'focus' in client) {
                await client.focus();
                return client.navigate ? client.navigate(target.href) : undefined;
            }
        }
        return self.clients.openWindow(target.href);
    })());
});
