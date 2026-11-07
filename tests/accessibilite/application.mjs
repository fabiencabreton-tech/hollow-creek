/*
 * Application installable (PWA), vérifiée dans un vrai Chromium :
 *   1. le manifeste est lu sans erreur et Chrome juge le site installable ;
 *   2. le script d'arrière-plan s'enregistre et prend la main sur les pages ;
 *   3. après une visite connectée (tableau de bord, scène), rien d'autre que les fichiers
 *      statiques et la page hors connexion n'est gardé sur l'appareil ;
 *   4. notifications push : un appareil s'abonne, le site lui envoie une alerte chiffrée et
 *      signée (reçue par un service push simulé, déchiffrée ici avec un code indépendant),
 *      puis le script d'arrière-plan l'affiche ;
 *   5. serveur coupé, une page du site affiche « hors connexion ».
 *
 * Le script lance son propre serveur PHP (port 8002) pour pouvoir le couper ensuite ;
 * il utilise la base préparée pour l'audit (AuditSeeder).
 */
import { chromium } from 'playwright';
import { spawn } from 'node:child_process';
import { createServer, get } from 'node:http';
import { createSubscriptionKeys, decrypt, verifyVapid } from './webpush-outils.mjs';
import { mkdirSync } from 'node:fs';

const port = 8002;
const base = `http://127.0.0.1:${port}`;
const out = 'storage/captures';
mkdirSync(out, { recursive: true });

// Lancé depuis public/, comme le fait « php artisan serve ».
const server = spawn('php', ['-S', `127.0.0.1:${port}`, '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], {
    cwd: 'public',
    env: { ...process.env, APP_URL: base, WEBPUSH_HOTES_SUPPLEMENTAIRES: '127.0.0.1:8003' },
    stdio: 'ignore',
});

const problems = [];
let browser;
let serverStopped = false;

function fail(message) {
    console.log(`✗ ${message}`);
    if (problems.length) {
        console.log(`  ${problems.join('\n  ')}`);
    }
    server.kill();
    browser?.close();
    process.exit(1);
}

async function waitForServer() {
    for (let i = 0; i < 50; i++) {
        // node:http plutôt que fetch : plus tolérant avec le serveur intégré de PHP.
        const status = await new Promise((resolve) => {
            get(base + '/', (response) => {
                response.resume();
                resolve(response.statusCode);
            }).on('error', () => resolve(0));
        });
        if (status === 200) {
            return;
        }
        await new Promise((resolve) => setTimeout(resolve, 200));
    }
    fail('Le serveur PHP de test ne répond pas.');
}

await waitForServer();

// Chromium complet (nouveau mode sans écran) : l'ancien « headless shell » refuse les notifications.
browser = await chromium.launch({ channel: 'chromium' });
const context = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
const page = await context.newPage();
page.on('pageerror', (error) => serverStopped || problems.push(`Erreur JavaScript : ${error.message}`));
page.on('console', (message) => {
    if (!serverStopped && message.type() === 'error') {
        problems.push(`Console : ${message.text()}`);
    }
});

// 1. Manifeste et installabilité, selon Chrome lui-même.
await page.goto(base + '/', { waitUntil: 'load' });
const cdp = await context.newCDPSession(page);
const manifest = await cdp.send('Page.getAppManifest');
if (!manifest.url || !manifest.url.endsWith('/manifest.webmanifest')) {
    fail(`Manifeste introuvable (${manifest.url || 'aucun'}).`);
}
if (manifest.errors.length) {
    fail(`Manifeste invalide : ${manifest.errors.map((e) => e.message).join(' ; ')}`);
}
console.log('✓ Manifeste lu sans erreur');

// 2. Script d'arrière-plan actif, page contrôlée.
const ready = await page.evaluate(async () => {
    const registration = await Promise.race([navigator.serviceWorker.ready, new Promise((resolve) => setTimeout(() => resolve(null), 10000))]);
    return registration ? registration.active?.scriptURL : null;
});
if (!ready || !ready.endsWith('/sw.js')) {
    fail('Le script d’arrière-plan ne s’est pas activé.');
}
await page.reload({ waitUntil: 'load' });
if (!(await page.evaluate(() => Boolean(navigator.serviceWorker.controller)))) {
    fail('Le script d’arrière-plan ne contrôle pas la page.');
}
console.log('✓ Script d’arrière-plan actif');

const installability = await cdp.send('Page.getInstallabilityErrors');
// Les contextes Playwright sont des sessions privées : Chrome le signale, ce n'est pas un défaut du site.
const blocking = installability.installabilityErrors.filter((error) => error.errorId !== 'in-incognito');
if (blocking.length) {
    fail(`Chrome ne juge pas le site installable : ${blocking.map((e) => e.errorId).join(', ')}`);
}
console.log('✓ Installable selon Chrome');

// 3. Visite connectée, puis inventaire de ce qui est gardé sur l'appareil.
await page.goto(base + '/connexion');
await page.fill('#champ-email', 'auditeur@audit.test');
await page.fill('#champ-password', 'audit-accessibilite');
await Promise.all([page.waitForNavigation(), page.click('main form button[type="submit"]')]);
for (const path of ['/tableau-de-bord', '/scenes/1', '/messages', '/application']) {
    await page.goto(base + path, { waitUntil: 'networkidle' });
}
await page.screenshot({ path: `${out}/application-installer.png`, fullPage: true });
if (await page.locator('[data-install-status]').isVisible()) {
    fail('Dans le navigateur, la page affiche à tort « l’application est installée ».');
}

const stored = await page.evaluate(async () => {
    const urls = [];
    for (const name of await caches.keys()) {
        const cache = await caches.open(name);
        for (const request of await cache.keys()) {
            urls.push(new URL(request.url).pathname);
        }
    }
    return urls;
});
const allowed = /^(\/build\/assets\/|\/icones\/|\/favicon\.svg$|\/hors-ligne\.html$)/;
const unexpected = stored.filter((path) => !allowed.test(path));
if (unexpected.length) {
    fail(`Des pages du site sont gardées sur l’appareil : ${unexpected.join(', ')}`);
}
if (!stored.includes('/hors-ligne.html') || !stored.some((path) => path.startsWith('/build/assets/'))) {
    fail(`Fichiers attendus absents de l’appareil : ${stored.join(', ')}`);
}
console.log(`✓ Seuls ${stored.length} fichiers statiques sont gardés, aucune page`);

// 4. Notifications push.
const received = [];
const pushService = createServer((request, response) => {
    const chunks = [];
    request.on('data', (chunk) => chunks.push(chunk));
    request.on('end', () => {
        received.push({ url: request.url, headers: request.headers, body: Buffer.concat(chunks) });
        response.writeHead(201).end();
    });
});
await new Promise((resolve) => pushService.listen(8003, '127.0.0.1', resolve));
await context.grantPermissions(['notifications'], { origin: base });

await page.goto(base + '/compte#notifications-appareils', { waitUntil: 'networkidle' });
await page.waitForFunction(() => !document.querySelector('[data-push-status]')?.textContent.includes('Vérification'), null, { timeout: 15000 });
const pushStatus = (await page.locator('[data-push-status]').textContent()).trim();
if (pushStatus !== 'Désactivées sur cet appareil.' || !(await page.locator('[data-push-enable]').isVisible())) {
    const permission = await page.evaluate(async () => `${Notification.permission} / ${(await navigator.permissions.query({ name: 'notifications' })).state}`);
    fail(`Le bouton d’activation n’est pas proposé (état : ${pushStatus} ; autorisation : ${permission}).`);
}
await page.locator('#notifications-appareils').screenshot({ path: `${out}/application-notifications.png` });
const siteKey = await page.locator('[data-push]').getAttribute('data-push-key');

// Abonnement tel que l'enverrait le navigateur, vers le service push simulé.
const keys = createSubscriptionKeys();
const subscribed = await page.evaluate(async ({ keys }) => {
    const token = document.querySelector('[data-push] input[name="_token"]').value;
    const response = await fetch('/notifications/appareils', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ endpoint: 'http://127.0.0.1:8003/push/appareil-audit', keys }),
    });
    return { status: response.status, data: await response.json() };
}, { keys: { p256dh: keys.p256dh, auth: keys.authText } });
if (subscribed.status !== 200 || !subscribed.data.delivered) {
    fail(`Abonnement refusé ou alerte non partie : ${JSON.stringify(subscribed)}`);
}

function readPush(index) {
    const push = received[index];
    if (!push) {
        fail('Le service push simulé n’a rien reçu.');
    }
    if (push.headers['content-encoding'] !== 'aes128gcm' || !push.headers.ttl) {
        fail(`En-têtes push inattendus : ${JSON.stringify(push.headers)}`);
    }
    const claims = verifyVapid(push.headers.authorization, siteKey);
    if (claims.aud !== 'http://127.0.0.1:8003' || !String(claims.sub).startsWith('mailto:') || claims.exp < Date.now() / 1000) {
        fail(`Jeton VAPID inattendu : ${JSON.stringify(claims)}`);
    }
    return JSON.parse(decrypt(push.body, keys));
}

const welcome = readPush(0);
if (welcome.title !== 'Notifications activées') {
    fail(`Alerte de bienvenue inattendue : ${JSON.stringify(welcome)}`);
}

await page.reload({ waitUntil: 'networkidle' });
await Promise.all([page.waitForNavigation(), page.click('form[action$="/notifications/appareils/essai"] button')]);
const trial = readPush(1);
if (trial.title !== 'Une lanterne s’allume sur le lac' || !trial.url.startsWith('/compte')) {
    fail(`Alerte d’essai inattendue : ${JSON.stringify(trial)}`);
}
if (!(await page.getByText('Notification d’essai envoyée à 1 appareil.').isVisible())) {
    fail('Le message de confirmation de l’essai ne s’affiche pas.');
}
console.log('✓ Alertes push chiffrées, signées et reçues (bienvenue, essai)');

// Le script d'arrière-plan affiche l'alerte déchiffrée.
const scopeURL = base + '/';
const registrationFound = new Promise((resolve) => {
    cdp.on('ServiceWorker.workerRegistrationUpdated', ({ registrations }) => {
        const found = registrations.find((registration) => registration.scopeURL === scopeURL && !registration.isDeleted);
        if (found) {
            resolve(found.registrationId);
        }
    });
});
await cdp.send('ServiceWorker.enable');
const registrationId = await registrationFound;
await cdp.send('ServiceWorker.deliverPushMessage', { origin: base, registrationId, data: JSON.stringify(trial) });
const shown = await page.evaluate(async () => {
    const registration = await navigator.serviceWorker.ready;
    for (let i = 0; i < 30; i++) {
        const notifications = await registration.getNotifications();
        if (notifications.length) {
            return notifications.map((notification) => ({ title: notification.title, url: notification.data?.url }));
        }
        await new Promise((resolve) => setTimeout(resolve, 200));
    }
    return [];
});
if (!shown.some((notification) => notification.title === trial.title && notification.url === trial.url)) {
    fail(`Le script d’arrière-plan n’a pas affiché l’alerte : ${JSON.stringify(shown)}`);
}
console.log('✓ Alerte affichée par l’application');

// Désabonnement depuis « Mon compte ».
await page.goto(base + '/compte#notifications-appareils', { waitUntil: 'networkidle' });
await Promise.all([page.waitForNavigation(), page.click('form[action*="/notifications/appareils/"] button:has-text("Retirer")')]);
if (await page.locator('form[action$="/notifications/appareils/essai"]').count()) {
    fail('L’appareil retiré apparaît encore.');
}
pushService.close();
console.log('✓ Appareil retiré');

// 5. Serveur coupé : la page hors connexion prend le relais.
serverStopped = true;
server.kill();
await new Promise((resolve) => setTimeout(resolve, 500));
await page.goto(base + '/tableau-de-bord', { waitUntil: 'load' }).catch(() => null);
const offlineTitle = await page.locator('h1').textContent().catch(() => null);
await page.screenshot({ path: `${out}/application-hors-ligne.png`, fullPage: true });
if (offlineTitle?.trim() !== 'La brume a coupé la route') {
    fail(`Sans réseau, la page affichée n’est pas la page hors connexion (titre : ${offlineTitle}).`);
}
console.log('✓ Sans réseau, la page hors connexion s’affiche');

if (problems.length) {
    fail('Erreurs relevées pendant le parcours.');
}

await browser.close();
console.log('Application installable : tout fonctionne.');
