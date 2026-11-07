// Régénère resources/css/fonts.css : node scripts/generer-polices.mjs "$PWD"
import fs from 'node:fs';
import path from 'node:path';

const root = process.argv[2];

// Caractères japonais réellement affichés : relevés dans les gabarits, les contenus de départ
// et la configuration, plus quelques mots du décor. Le navigateur ne télécharge ensuite que
// les fragments de police qui les contiennent (unicode-range).
const sources = ['resources/views', 'database/seeders', 'config', 'app'];
const extra = '月森町月森新聞ツキモリ町幽霊怨霊水瀬家藤沢橘葛城故霧精神病院';
const japanese = /[぀-ヿ㐀-鿿豈-﫿]/u;
const found = new Set([...extra]);
const walk = (dir) => {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walk(full);
    } else if (/\.(php|md|blade\.php)$/.test(entry.name)) {
      for (const character of fs.readFileSync(full, 'utf8')) {
        if (japanese.test(character)) {
          found.add(character);
        }
      }
    }
  }
};
sources.forEach((dir) => walk(path.join(root, dir)));
const kanji = [...found].map((c) => c.codePointAt(0));

const wanted = {
  // Japonais : la marque, les familles, les kanji du décor.
  'shippori-mincho': { variants: ['400', '500', '700'], japanese: true },
  // Titres, navigation, libellés : la serif de la maquette.
  'cormorant-garamond': { variants: ['500', '600', '700', '500-italic'], japanese: false },
  // Lecture et interface.
  'literata': { variants: ['400', '400-italic', '700', '700-italic'], japanese: false },
};
const inRange = (cp, range) => range.split(',').some(part => {
  const m = part.trim().replace(/^U\+/i, '').split('-');
  const a = parseInt(m[0], 16), b = m[1] ? parseInt(m[1], 16) : a;
  return cp >= a && cp <= b;
});
let out = `/*\n * Polices auto-hébergées (licence SIL Open Font License), fournies par Fontsource.\n * Cormorant Garamond (titres, navigation, libellés), Literata (lecture et interface),\n * Shippori Mincho (japonais : 月森町, les familles, le décor).\n * Jeux latin et latin étendu (ū, ō de Yūrei, Onryō), plus les seuls fragments japonais qui\n * contiennent un caractère affiché par le site : le navigateur ne télécharge que ce dont la\n * page a besoin (unicode-range).\n * Fichier généré : ne pas modifier à la main.\n */\n`;
for (const [pkg, conf] of Object.entries(wanted)) {
  for (const v of conf.variants) {
    const css = fs.readFileSync(path.join(root, 'node_modules/@fontsource', pkg, `${v}.css`), 'utf8');
    const blocks = css.split('/*').slice(1).map(b => '/*' + b);
    const keep = blocks.filter(b => {
      const head = b.match(/^\/\* ([^*]+) \*\//)[1];
      if (/-(latin|latin-ext)-\d+-(normal|italic)$/.test(head)) return true;
      if (conf.japanese && /-\[\d+\]-/.test(head)) {
        const range = b.match(/unicode-range: ([^;]+);/)[1];
        return kanji.some(cp => inRange(cp, range));
      }
      return false;
    });
    if (keep.length < 2) throw new Error(`subsets manquants ${pkg} ${v}`);
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
console.log('ok', (out.match(/@font-face/g) || []).length, 'faces', kanji.length, 'caractères japonais');
