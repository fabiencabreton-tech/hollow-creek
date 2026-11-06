/*
 * Parcours complet d'un nouvel arrivant, dans un vrai navigateur :
 * inscription, vérification de l'adresse (lien lu dans le journal des e-mails),
 * tableau de bord, création d'un personnage en six étapes, envoi de la fiche,
 * déconnexion, reconnexion et demande de nouveau mot de passe.
 *
 * Échoue au premier problème, ou si une erreur JavaScript ou une violation de la
 * politique de sécurité (CSP) apparaît. Une capture est enregistrée à chaque étape.
 */
import { chromium } from 'playwright';
import { mkdirSync, readdirSync, readFileSync } from 'node:fs';
import { deflateSync } from 'node:zlib';

const base = process.env.PARCOURS_URL || 'http://127.0.0.1:8001';
const mailLogDir = 'storage/logs';
const out = 'storage/captures';
mkdirSync(out, { recursive: true });

const stamp = Date.now().toString(36);
const account = { pseudo: `Nouvelle${stamp}`.slice(0, 20), email: `nouvelle-${stamp}@exemple.test`, password: 'une phrase de passe assez longue' };
const problems = [];
let stepNumber = 0;

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
const page = await context.newPage();

page.on('pageerror', (error) => problems.push(`Erreur JavaScript : ${error.message}`));
page.on('console', (message) => {
    if (message.type() === 'error') {
        problems.push(`Console : ${message.text()}`);
    }
});
page.on('response', (response) => {
    const type = response.request().resourceType();
    if (response.status() >= 500 || (response.status() >= 400 && type !== 'document')) {
        problems.push(`Réponse ${response.status()} (${type}) : ${response.url()}`);
    }
});

async function step(label, action) {
    stepNumber++;
    const name = `parcours-${String(stepNumber).padStart(2, '0')}`;
    try {
        await action();
        await page.screenshot({ path: `${out}/${name}.png`, fullPage: true });
        if (problems.length > 0) {
            throw new Error(problems.join(' | '));
        }
        console.log(`✓ ${stepNumber}. ${label}`);
    } catch (error) {
        await page.screenshot({ path: `${out}/${name}-ECHEC.png`, fullPage: true }).catch(() => {});
        console.log(`✗ ${stepNumber}. ${label}\n  ${error.message}\n  Page : ${page.url()}`);
        const errors = await page.locator('.error, .alert--error').allInnerTexts().catch(() => []);
        if (errors.length) {
            console.log(`  Messages affichés : ${errors.join(' / ')}`);
        }
        await browser.close();
        process.exit(1);
    }
}

async function expectText(text) {
    await page.getByText(text, { exact: false }).first().waitFor({ timeout: 5000 });
}

function expectPath(pattern) {
    const path = new URL(page.url()).pathname;
    if (!pattern.test(path)) {
        throw new Error(`adresse inattendue : ${path}`);
    }
}

async function submit(locator) {
    await Promise.all([page.waitForLoadState('load'), page.waitForNavigation(), locator.click()]);
}

// Image PNG unie, 400 × 500, fabriquée sur place (aucun visage, aucun fichier externe).
function png(width, height) {
    const crcTable = Array.from({ length: 256 }, (_, n) => {
        let c = n;
        for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        return c >>> 0;
    });
    const crc = (buffer) => {
        let c = 0xffffffff;
        for (const byte of buffer) c = crcTable[(c ^ byte) & 0xff] ^ (c >>> 8);
        return (c ^ 0xffffffff) >>> 0;
    };
    const chunk = (type, data) => {
        const length = Buffer.alloc(4);
        length.writeUInt32BE(data.length);
        const body = Buffer.concat([Buffer.from(type), data]);
        const sum = Buffer.alloc(4);
        sum.writeUInt32BE(crc(body));
        return Buffer.concat([length, body, sum]);
    };
    const header = Buffer.alloc(13);
    header.writeUInt32BE(width, 0);
    header.writeUInt32BE(height, 4);
    header[8] = 8;
    header[9] = 2;
    const row = Buffer.concat([Buffer.from([0]), Buffer.alloc(width * 3, 0x60)]);
    const raw = Buffer.concat(Array.from({ length: height }, () => row));
    return Buffer.concat([
        Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
        chunk('IHDR', header),
        chunk('IDAT', deflateSync(raw)),
        chunk('IEND', Buffer.alloc(0)),
    ]);
}

function verificationLink() {
    const files = readdirSync(mailLogDir).filter((file) => file.startsWith('courriels')).sort();
    if (files.length === 0) {
        throw new Error('aucun journal des e-mails (storage/logs/courriels-*.log)');
    }
    const log = readFileSync(`${mailLogDir}/${files[files.length - 1]}`, 'utf8')
        .replace(/=\r?\n/g, '')
        .replace(/=3D/g, '=')
        .replace(/&amp;/g, '&');
    const links = [...log.matchAll(/https?:\/\/[^\s"'<>]+\/verifier-adresse-email\/\d+\/[a-f0-9]+\?[^\s"'<>\]]+/g)].map((match) => match[0]);
    if (links.length === 0) {
        throw new Error('aucun lien de vérification dans le journal des e-mails');
    }
    return links[links.length - 1];
}

await step('L’accueil propose de rejoindre le site', async () => {
    await page.goto(base + '/');
    await page.getByRole('link', { name: 'Rejoindre Tsukimori' }).first().click();
    await page.waitForURL(/\/inscription$/);
    await expectText('Créer mon compte');
});

await step('Un mot de passe trop court est refusé avec un message clair', async () => {
    await page.fill('#champ-pseudo', account.pseudo);
    await page.fill('#champ-email', account.email);
    await page.fill('#champ-password', 'court');
    await page.fill('#champ-password_confirmation', 'court');
    await page.check('input[name="age"]');
    await page.check('input[name="terms"]');
    await submit(page.getByRole('button', { name: 'Créer mon compte' }));
    expectPath(/\/inscription$/);
    await page.locator('#champ-password-erreur').waitFor({ timeout: 5000 });
});

await step('L’inscription crée le compte et demande de vérifier l’adresse', async () => {
    await page.fill('#champ-password', account.password);
    await page.fill('#champ-password_confirmation', account.password);
    if (!(await page.isChecked('input[name="age"]'))) await page.check('input[name="age"]');
    if (!(await page.isChecked('input[name="terms"]'))) await page.check('input[name="terms"]');
    await submit(page.getByRole('button', { name: 'Créer mon compte' }));
    await page.goto(base + '/mes-personnages');
    expectPath(/verifier-adresse-email/);
    await expectText('Vérifiez votre adresse e-mail');
});

await step('Le lien reçu par e-mail valide l’adresse', async () => {
    await page.goto(verificationLink());
    await page.goto(base + '/tableau-de-bord');
    expectPath(/tableau-de-bord/);
    await expectText(account.pseudo);
});

await step('Créer un personnage ouvre la fiche à l’étape 1', async () => {
    await page.goto(base + '/mes-personnages');
    await submit(page.getByRole('button', { name: 'Créer un personnage' }));
    expectPath(/\/mes-personnages\/[^/]+\/fiche\/1$/);
});

await step('Étape 1 : identité', async () => {
    await page.fill('#champ-first_name', 'Aiko');
    await page.fill('#champ-last_name', 'Fujimori');
    await page.fill('#champ-age', '29');
    await page.fill('#champ-profession', 'Libraire chez Kohaku Shobō');
    await submit(page.getByRole('button', { name: 'Enregistrer et continuer' }));
    expectPath(/\/fiche\/2$/);
});

await step('Étape 2 : nature (les champs suivent le choix)', async () => {
    await page.check('input[data-nature="yurei"]');
    await page.locator('[data-show-for-natures*="yurei"]').first().waitFor({ state: 'visible', timeout: 3000 });
    await page.check('input[data-nature="humains"]');
    const ghostFields = page.locator('[data-show-for-natures="yurei,onryo"], [data-show-for-natures="yurei"]');
    if ((await ghostFields.count()) > 0 && (await ghostFields.first().isVisible())) {
        throw new Error('les champs des fantômes restent affichés pour un humain');
    }
    await submit(page.getByRole('button', { name: 'Enregistrer et continuer' }));
    expectPath(/\/fiche\/3$/);
});

await step('Étape 3 : avatar (célébrité, image et crédit)', async () => {
    await page.fill('#champ-faceclaim', 'Rinko Kikuchi');
    await page.setInputFiles('#champ-avatar', { name: 'avatar.png', mimeType: 'image/png', buffer: png(400, 500) });
    await page.fill('#champ-avatar_credit', 'Photo de test, libre de droits');
    await submit(page.getByRole('button', { name: 'Enregistrer et continuer' }));
    expectPath(/\/fiche\/4$/);
});

await step('Étape 4 : histoire', async () => {
    await page.fill('#champ-history', 'Aiko a grandi à Furumachi, entre les rayonnages de la librairie familiale. '.repeat(5));
    await submit(page.getByRole('button', { name: 'Enregistrer et continuer' }));
    expectPath(/\/fiche\/5$/);
});

await step('Étape 5 : personnalité', async () => {
    await page.fill('#champ-personality', 'Curieuse, patiente et têtue, elle note tout dans des carnets. '.repeat(3));
    await submit(page.getByRole('button', { name: 'Enregistrer et continuer' }));
    expectPath(/\/fiche\/6$/);
});

await step('Étape 6 : la fiche complète s’envoie à l’équipe', async () => {
    await page.check('input[name="attestation"]');
    await submit(page.getByRole('button', { name: 'Envoyer ma fiche' }));
    await page.goto(base + '/mes-personnages');
    await expectText('Aiko Fujimori');
    await expectText('en cours de lecture');
});

await step('Notifications, messages et compte s’ouvrent', async () => {
    for (const path of ['/notifications', '/messages', '/compte', '/compte/donnees', '/bottin']) {
        const response = await page.goto(base + path);
        if (!response || response.status() !== 200) {
            throw new Error(`${path} répond ${response ? response.status() : 'sans réponse'}`);
        }
    }
});

await step('Déconnexion puis reconnexion avec le pseudo', async () => {
    await page.goto(base + '/compte');
    await page.getByRole('button', { name: 'Menu' }).click();
    await submit(page.getByRole('button', { name: 'Se déconnecter' }).first());
    await page.goto(base + '/connexion');
    await page.fill('#champ-email', account.pseudo);
    await page.fill('#champ-password', account.password);
    await submit(page.getByRole('button', { name: 'Se connecter' }));
    expectPath(/tableau-de-bord/);
});

await step('Mot de passe oublié : la demande est acceptée', async () => {
    await context.clearCookies();
    await page.goto(base + '/mot-de-passe-oublie');
    await page.fill('#champ-email', account.email);
    await submit(page.locator('form button[type="submit"]').first());
    await page.locator('.alert, [role="status"]').first().waitFor({ timeout: 5000 });
});

await browser.close();
console.log(`Parcours réussi : ${stepNumber} étapes.`);
