/*
 * Captures d'écran des pages principales (nuit et parchemin, ordinateur et téléphone),
 * pour relire l'apparence du site dans l'intégration continue. N'échoue jamais.
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';

const base = process.env.AUDIT_URL || 'http://127.0.0.1:8000';
const out = 'storage/captures';
mkdirSync(out, { recursive: true });
mkdirSync(`${out}/html`, { recursive: true });

const pages = (process.env.CAPTURE_PAGES || '/,/ville,/univers,/univers/especes,/univers/especes/onryo,/univers/familles,/univers/familles/minase,/registre-de-kirisawa,/gazette,/gazette/audit-une,/gazette?rubrique=faits-divers,/scenes,/scenes/1,/personnages,/personnages/audit-1,/predefinis,/murmures,/evenements,/enquetes,/archives,/annonces,/connexion,/inscription,/application').split(',');
const memberPages = (process.env.CAPTURE_MEMBER_PAGES || '/tableau-de-bord,/mes-personnages,/mes-scenes,/messages,/notifications,/compte,/?vitrine=1').split(',');

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
            // Descendre la page pour charger les images différées, puis remonter.
            await page.evaluate(async () => {
                for (let y = 0; y < document.body.scrollHeight; y += 700) {
                    window.scrollTo(0, y);
                    await new Promise((resolve) => setTimeout(resolve, 40));
                }
                window.scrollTo(0, 0);
            });
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(150);
            const name = (prefix + path.replace(/[^a-z0-9]+/gi, '_')).replace(/_+$/, '') || prefix + 'accueil';
            await page.screenshot({ path: `${out}/${label}__${name}.png`, fullPage: true });
            if (label === 'nuit-ordinateur') {
                // Le HTML rendu, pour retoucher la feuille de style hors intégration continue.
                writeFileSync(`${out}/html/${name}.html`, await page.content());
            }
            if (path === '/' && prefix === 'p') {
                // Menus de l'en-tête ouverts : « RPG » sur ordinateur, le tiroir sur téléphone.
                const opener = label === 'nuit-ordinateur' ? page.locator('.nav-menu__summary') : page.getByRole('button', { name: 'Menu' });
                if (await opener.isVisible()) {
                    await opener.click();
                    await page.waitForTimeout(300);
                    await page.screenshot({ path: `${out}/${label}__menu.png` });
                }
            }
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
    await Promise.all([login.waitForNavigation(), login.click('main form button[type="submit"]')]);
    await login.close();

    for (const path of memberPages) {
        await shoot(path, 'm');
    }

    await context.close();
}

await browser.close();
