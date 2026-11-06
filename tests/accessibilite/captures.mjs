/*
 * Captures d'écran des pages principales (nuit et parchemin, ordinateur et téléphone),
 * pour relire l'apparence du site dans l'intégration continue. N'échoue jamais.
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const base = process.env.AUDIT_URL || 'http://127.0.0.1:8000';
const out = 'storage/captures';
mkdirSync(out, { recursive: true });

const pages = (process.env.CAPTURE_PAGES || '/,/ville,/univers,/univers/especes/onryo,/scenes,/scenes/1,/personnages/audit-1,/gazette,/annonces,/enquetes,/connexion,/univers/familles,/univers/familles/minase,/application').split(',');
const memberPages = (process.env.CAPTURE_MEMBER_PAGES || '/tableau-de-bord,/mes-personnages,/?vitrine=1').split(',');

const browser = await chromium.launch();

for (const [label, viewport, theme] of [
    ['nuit-ordinateur', { width: 1280, height: 900 }, null],
    ['parchemin-telephone', { width: 390, height: 844 }, 'parchemin'],
    ['nuit-telephone', { width: 390, height: 844 }, null],
]) {
    const context = await browser.newContext({ viewport });
    if (theme) {
        await context.addInitScript((value) => window.localStorage.setItem('hollow-creek-theme', value), theme);
    }

    const shoot = async (path, prefix) => {
        const page = await context.newPage();
        try {
            await page.goto(base + path, { waitUntil: 'networkidle' });
            const name = (prefix + path.replace(/[^a-z0-9]+/gi, '_')).replace(/_+$/, '') || prefix + 'accueil';
            await page.screenshot({ path: `${out}/${label}__${name}.png`, fullPage: true });
        } catch (error) {
            console.log(`capture impossible ${path} : ${error.message}`);
        }
        await page.close();
    };

    for (const path of pages) {
        await shoot(path, 'p');
    }

    const login = await context.newPage();
    await login.goto(base + '/connexion');
    await login.fill('#champ-email', 'auditeur@audit.test');
    await login.fill('#champ-password', 'audit-accessibilite');
    await Promise.all([login.waitForNavigation(), login.click('form button[type="submit"]')]);
    await login.close();

    for (const path of memberPages) {
        await shoot(path, 'm');
    }

    await context.close();
}

await browser.close();
