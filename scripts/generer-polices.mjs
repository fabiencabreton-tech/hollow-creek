// Régénère resources/css/fonts.css : node scripts/generer-polices.mjs "$PWD"
import fs from 'node:fs';
import path from 'node:path';

const root = process.argv[2];
// Caractères japonais réellement affichés par le site (marque, en-têtes).
const kanji = [...'月森町水瀬家藤沢橘葛城故霧精神病院'].map(c => c.codePointAt(0));
const wanted = {
  'shippori-mincho': { variants: ['500', '700'], japanese: true },
  'zen-kaku-gothic-new': { variants: ['400', '500', '700'], japanese: false },
  'literata': { variants: ['400', '400-italic', '700', '700-italic'], japanese: false },
  // Seulement pour les voyelles longues (ū, ō) absentes des polices japonaises.
  'ibm-plex-sans': { variants: ['400', '500', '700'], japanese: false, only: 'latin-ext' },
};
const inRange = (cp, range) => range.split(',').some(part => {
  const m = part.trim().replace(/^U\+/i, '').split('-');
  const a = parseInt(m[0], 16), b = m[1] ? parseInt(m[1], 16) : a;
  return cp >= a && cp <= b;
});
let out = `/*\n * Polices auto-hébergées (licence SIL Open Font License), fournies par Fontsource.\n * Shippori Mincho (titres), Zen Kaku Gothic New (interface), Literata (lecture),\n * IBM Plex Sans (latin étendu seulement : ū, ō de Yūrei, Onryō).\n * Jeux latin et latin étendu, plus les fragments japonais qui contiennent « 月森町 » et les noms des grandes familles :\n * le navigateur ne télécharge que ce dont la page a besoin (unicode-range).\n * Fichier généré : ne pas modifier à la main.\n */\n`;
for (const [pkg, conf] of Object.entries(wanted)) {
  for (const v of conf.variants) {
    const css = fs.readFileSync(path.join(root, 'node_modules/@fontsource', pkg, `${v}.css`), 'utf8');
    const blocks = css.split('/*').slice(1).map(b => '/*' + b);
    const keep = blocks.filter(b => {
      const head = b.match(/^\/\* ([^*]+) \*\//)[1];
      if (conf.only) return new RegExp(`-${conf.only}-\\d+-(normal|italic)$`).test(head);
      if (/-(latin|latin-ext)-\d+-(normal|italic)$/.test(head)) return true;
      if (conf.japanese && /-\[\d+\]-/.test(head)) {
        const range = b.match(/unicode-range: ([^;]+);/)[1];
        return kanji.some(cp => inRange(cp, range));
      }
      return false;
    });
    if (keep.length < (conf.only ? 1 : 2)) throw new Error(`subsets manquants ${pkg} ${v}`);
    for (const block of keep) {
      const family = block.match(/font-family: '([^']+)'/)[1];
      const style = block.match(/font-style: (\w+)/)[1];
      const weight = block.match(/font-weight: (\d+)/)[1];
      const range = block.match(/unicode-range: ([^;]+);/)[1];
      const file = block.match(/url\(\.\/files\/([^)]+\.woff2)\)/)[1];
      out += `\n@font-face {\n    font-family: '${family}';\n    font-style: ${style};\n    font-weight: ${weight};\n    font-display: swap;\n    src: url('../../node_modules/@fontsource/${pkg}/files/${file}') format('woff2');\n    unicode-range: ${range};\n}\n`;
    }
  }
}
fs.writeFileSync(path.join(root, 'resources/css/fonts.css'), out);
console.log('ok', (out.match(/@font-face/g) || []).length, 'faces');
