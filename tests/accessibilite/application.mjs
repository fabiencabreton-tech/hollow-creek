/*
 * Application installable (PWA), vérifiée dans un vrai Chromium :
 *   1. le manifeste est lu sans erreur et Chrome juge le site installable ;
 *   2. le script d'arrière-plan s'enregistre et prend la main sur les pages ;
 *   3. après une visite connectée (tableau de bord, scène), rien d'autre que les fichiers
 *      statiques et la page hors connexion n'est gardé sur l'appareil ;
 *   4. serveur coupé, une page du site affiche « hors connexion ».
 *
 * Le script lance son propre serveur PHP (port 8002) pour pouvoir le couper ensuite ;
 * il utilise la base préparée pour l'audit (AuditSeeder).
 */
import { chromium } from 'playwright';
import { spawn } from 'node:child_process';
import { mkdirSync } from 'node:fs';

const port = 8002;
const base = `http://127.0.0.1:${port}`;
const out = 'storage/captures';
mkdirSync(out, { recursive: true });

// Lancé depuis public/, comme le fait « php artisan serve ».
const server = spawn('php', ['-S', `127.0.0.1:${port}`, '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], {
    cwd: 'public',
    env: { ...process.env, APP_URL: base },
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
        try {
            const response = await fetch(base + '/');
            if (response.ok) {
                return;
            }
        } catch {
            // pas encore prêt
        }
        await new Promise((resolve) => setTimeout(resolve, 200));
    }
    fail('Le serveur PHP de test ne répond pas.');
}

await waitForServer();

browser = await chromium.launch();
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
await Promise.all([page.waitForNavigation(), page.click('form button[type="submit"]')]);
for (const path of ['/tableau-de-bord', '/scenes/1', '/messages', '/application']) {
    await page.goto(base + path, { waitUntil: 'networkidle' });
}
await page.screenshot({ path: `${out}/application-installer.png`, fullPage: true });

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

// 4. Serveur coupé : la page hors connexion prend le relais.
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
