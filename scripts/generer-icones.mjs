/*
 * Icônes de l'application installable (PWA) : le sceau vermillon 月森町 sur le crépuscule prune.
 *
 * Usage : node scripts/generer-icones.mjs "$PWD"   (après npm run build : la police
 * Shippori Mincho est prise dans public/build, déjà découpée pour les kanji du site).
 *
 * Produit public/icones/ :
 *   icone-192.png, icone-512.png        coins arrondis, pour Android et les navigateurs ;
 *   icone-masquable-512.png             fond plein, sceau dans la zone sûre (80 %) ;
 *   apple-touch-icon.png (180)          fond plein, iOS arrondit lui-même ;
 *   badge-96.png                        lanterne blanche, pour la barre des notifications.
 */
import { chromium } from 'playwright';
import { mkdirSync, readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { extname, join, normalize } from 'node:path';

const root = process.argv[2] || process.cwd();
const manifest = JSON.parse(readFileSync(join(root, 'public/build/manifest.json'), 'utf8'));
const css = manifest['resources/css/app.css'].file;
const out = join(root, 'public/icones');
mkdirSync(out, { recursive: true });

const icons = [
    { file: 'icone-192.png', size: 192, rounded: true, seal: 0.62 },
    { file: 'icone-512.png', size: 512, rounded: true, seal: 0.62 },
    { file: 'icone-masquable-512.png', size: 512, rounded: false, seal: 0.5 },
    { file: 'apple-touch-icon.png', size: 180, rounded: false, seal: 0.6 },
];

// Petit serveur local : la page d'icône et les fichiers compilés (CSS, polices).
const types = { '.css': 'text/css', '.woff2': 'font/woff2', '.html': 'text/html; charset=utf-8' };
let current = '';
const server = createServer((request, response) => {
    if (request.url === '/icone.html') {
        response.writeHead(200, { 'Content-Type': types['.html'] });
        return response.end(current);
    }
    const file = join(root, 'public', normalize(decodeURIComponent(request.url.split('?')[0])).replace(/^(\.\.[/\\])+/, ''));
    try {
        const body = readFileSync(file);
        response.writeHead(200, { 'Content-Type': types[extname(file)] || 'application/octet-stream' });
        response.end(body);
    } catch {
        response.writeHead(404).end();
    }
});
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const browser = await chromium.launch();

for (const icon of icons) {
    const page = await browser.newPage({ viewport: { width: icon.size, height: icon.size } });
    const s = icon.size;
    const sealH = s * icon.seal;
    const sealW = sealH * 0.68;
    const html = `<!doctype html><html><head><meta charset="utf-8">
<link rel="stylesheet" href="/build/${css}">
<style>
html, body { margin: 0; width: ${s}px; height: ${s}px; background: transparent; }
.icon {
    position: relative; width: ${s}px; height: ${s}px; overflow: hidden;
    border-radius: ${icon.rounded ? s * 0.22 : 0}px;
    background:
        radial-gradient(ellipse at 50% 112%, rgba(239, 198, 132, .30), transparent 58%),
        radial-gradient(ellipse at 50% -10%, #46384f, transparent 70%),
        #211a26;
    display: flex; align-items: center; justify-content: center;
}
.seal {
    width: ${sealW}px; height: ${sealH}px; border-radius: ${sealW * 0.14}px;
    background: #c4543f; color: #fbf6ec;
    box-shadow: inset 0 0 0 ${Math.max(2, s * 0.012)}px rgba(251, 246, 236, .24), 0 ${s * 0.02}px ${s * 0.06}px rgba(0, 0, 0, .45);
    transform: rotate(-3deg);
    display: flex; align-items: center; justify-content: center;
    writing-mode: vertical-rl;
    font-family: 'Shippori Mincho', serif; font-weight: 700;
    font-size: ${sealH * 0.27}px; letter-spacing: ${sealH * 0.02}px; line-height: 1;
}
</style></head><body><div class="icon"><div class="seal" lang="ja">月森町</div></div></body></html>`;
    current = html;
    await page.goto(`${origin}/icone.html`, { waitUntil: 'load' });
    const loaded = await page.evaluate(async () => (await document.fonts.load('700 40px "Shippori Mincho"', '月森町')).length > 0);
    if (!loaded) {
        throw new Error('La police Shippori Mincho n’a pas été chargée : lancez npm run build avant.');
    }
    await page.locator('.icon').screenshot({ path: join(out, icon.file), omitBackground: true });
    await page.close();
    console.log(`✓ ${icon.file}`);
}

// Petite icône monochrome des notifications (barre d'état Android) : la lanterne en blanc.
const badge = await browser.newPage({ viewport: { width: 96, height: 96 } });
current = `<!doctype html><html><head><style>html, body { margin: 0; background: transparent; }</style></head><body>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="8 8 48 48" width="96" height="96"><path d="M24 14h16M32 14v4" stroke="#fff" stroke-width="3.4" stroke-linecap="round"/><path d="M21 22h22l3 5v18l-3 5H21l-3-5V27z" fill="none" stroke="#fff" stroke-width="3.4" stroke-linejoin="round"/><path d="M32 29c3.5 4 3.5 8.6 0 12.2c-3.5-3.6-3.5-8.2 0-12.2z" fill="#fff"/></svg>
</body></html>`;
await badge.goto(`${origin}/icone.html`, { waitUntil: 'load' });
await badge.locator('svg').screenshot({ path: join(out, 'badge-96.png'), omitBackground: true });
console.log('✓ badge-96.png');

await browser.close();
server.close();
