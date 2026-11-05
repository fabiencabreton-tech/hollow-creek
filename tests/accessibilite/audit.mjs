/*
 * Audit d'accessibilité automatisé (axe-core, règles WCAG 2.1 A et AA), en mode nuit
 * et en mode parchemin, sur ordinateur et sur téléphone. Lancé par la CI sur le site
 * servi localement avec les données de AuditSeeder.
 *
 * Échec si une violation « grave » ou « critique » est trouvée, ou si une page déborde
 * horizontalement sur téléphone.
 */
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';

const base = process.env.AUDIT_URL || 'http://127.0.0.1:8000';

const publicPages = [
    '/', '/univers', '/univers/especes', '/univers/familles', '/ville', '/gazette', '/scenes',
    '/personnages', '/bottin', '/predefinis', '/murmures', '/evenements', '/recherche?q=test',
    '/contact', '/connexion', '/inscription', '/mot-de-passe-oublie', '/faq', '/reglement',
];

const memberPages = [
    '/tableau-de-bord', '/mes-scenes', '/mes-personnages', '/messages', '/messages/nouveau',
    '/notifications', '/relations', '/compte', '/compte/donnees', '/scenes/nouvelle', '/mes-demandes',
];

const failures = [];

async function audit(context, path, label) {
    const page = await context.newPage();
    const response = await page.goto(base + path, { waitUntil: 'networkidle' });

    if (!response || response.status() >= 400) {
        failures.push(`${label} ${path} : réponse ${response ? response.status() : 'absente'}`);
        await page.close();
        return;
    }

    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
    const serious = results.violations.filter((violation) => ['serious', 'critical'].includes(violation.impact));

    for (const violation of serious) {
        const targets = violation.nodes.slice(0, 3).map((node) => node.target.join(' ')).join(' | ');
        failures.push(`${label} ${path} : [${violation.impact}] ${violation.id} — ${violation.help} — ${targets}`);
    }

    const minor = results.violations.length - serious.length;
    if (minor > 0) {
        console.log(`  ${label} ${path} : ${minor} remarque(s) mineure(s)`);
    }

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    if (overflow) {
        failures.push(`${label} ${path} : débordement horizontal`);
    }

    await page.close();
}

async function login(context) {
    const page = await context.newPage();
    await page.goto(base + '/connexion');
    await page.fill('#champ-email', 'auditeur@audit.test');
    await page.fill('#champ-password', 'audit-accessibilite');
    await Promise.all([page.waitForNavigation(), page.click('form button[type="submit"]')]);
    await page.close();
}

const browser = await chromium.launch();

for (const [label, viewport, theme] of [
    ['[nuit, ordinateur]', { width: 1280, height: 900 }, null],
    ['[parchemin, téléphone]', { width: 390, height: 844 }, 'parchemin'],
]) {
    const context = await browser.newContext({ viewport, bypassCSP: true });

    if (theme) {
        await context.addInitScript((value) => window.localStorage.setItem('hollow-creek-theme', value), theme);
    }

    for (const path of publicPages) {
        await audit(context, path, label);
    }

    await login(context);

    const sceneLink = await (async () => {
        const page = await context.newPage();
        await page.goto(base + '/scenes');
        const href = await page.getAttribute('.scene-card__title a', 'href');
        await page.close();
        return href ? new URL(href, base).pathname : null;
    })();

    for (const path of [...memberPages, ...(sceneLink ? [sceneLink] : [])]) {
        await audit(context, path, label);
    }

    await context.close();
}

await browser.close();

if (failures.length > 0) {
    console.error(`\n${failures.length} problème(s) d'accessibilité :\n- ` + failures.join('\n- '));
    process.exit(1);
}

console.log('\nAudit d’accessibilité : aucune violation grave.');
