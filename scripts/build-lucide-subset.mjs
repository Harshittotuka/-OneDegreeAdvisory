/**
 * Builds public/assets/vendor/lucide-subset.min.js — the Lucide icons this site
 * actually uses, bundled with Lucide's own runtime.
 *
 * The full vendored library is 414 KB on disk (91 KB brotli) and ships ~1500
 * icons on every page; the site uses a couple of hundred. This tree-shakes it.
 *
 * The catch this build has to respect: CMS editors can type ANY Lucide name
 * into a brief-page block's icon field, so the used set is not knowable from
 * the templates alone. Two things cover that:
 *   1. the scan below also reads the icon names already stored in storage/app,
 *   2. the generated bundle falls back to loading the full library at runtime
 *      the moment it meets a name it does not carry (see the wrapper emitted
 *      in entrySource()), so an icon picked tomorrow still renders.
 *
 * Run: node scripts/build-lucide-subset.mjs
 */
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, readdirSync, statSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { toPascalCase } from 'lucide/dist/esm/shared/src/utils/toPascalCase.mjs';
import * as lucide from 'lucide';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

const OUT = 'public/assets/vendor/lucide-subset.min.js';
const FULL = 'assets/vendor/lucide.min.js';

/**
 * Icon names are written four different ways in this codebase, and a miss is
 * only a performance cost (the runtime fallback still renders the icon), so
 * these patterns err towards over-collecting:
 *   <i data-lucide="route">          markup
 *   'icon' => 'handshake'            PHP block/card arrays
 *   $factIcons = ['banknote', …]     bare lists named "…icon(s)"
 *   "btn_icon": "arrow-right"        CMS JSON
 */
const ICON_KEY = String.raw`(?:icon|icons|btn_icon|icon_name|iconName)`;

const MARKUP = /data-lucide=\\?["']([a-z0-9-]+)\\?["']/g;
const KEYED = new RegExp(String.raw`["']?${ICON_KEY}["']?\s*(?:=>|:)\s*["']([a-z0-9][a-z0-9-]{1,})["']`, 'gi');
const SET_ATTR = /setAttribute\(\s*["']data-lucide["']\s*,\s*["']([a-z0-9-]+)["']/g;

/** `$somethingIcons = [ 'a', 'b' ]` / `'icons' => ['a','b']` — grab the list, then its strings. */
function bareIconLists(text) {
    const found = [];
    const listRe = new RegExp(String.raw`(?:\$\w*[Ii]cons?|["']${ICON_KEY}["'])\s*(?:=>|=|:)\s*\[([^\]]{0,2000})\]`, 'gi');
    for (const match of text.matchAll(listRe)) {
        for (const str of match[1].matchAll(/["']([a-z0-9][a-z0-9-]{1,})["']/g)) found.push(str[1]);
    }
    return found;
}

const SOURCES = [
    { dir: 'resources/views', exts: ['.php'], patterns: [MARKUP, KEYED] },
    { dir: 'app', exts: ['.php'], patterns: [MARKUP, KEYED] },
    { dir: 'public', exts: ['.js'], recurse: false, patterns: [MARKUP, SET_ATTR, KEYED] },
    // CMS content: icons an editor has already chosen.
    { dir: 'storage/app', exts: ['.json'], recurse: false, patterns: [MARKUP, KEYED] },
];

/**
 * The patterns above assume an icon name is introduced by something that says
 * "icon". Not all of them are: mbbs/country.blade.php picks one with a match
 * expression whose arms are bare strings —
 *
 *   str_contains($h, 'food') => 'utensils',
 *
 * which no key-based pattern can see, and which cost that page a fallback
 * request for five icons. So in any file that renders icons at all (it mentions
 * data-lucide), every quoted lowercase string that happens to BE a real Lucide
 * name is collected too. That over-collects the odd word — "home", "check" —
 * and a few unused icons cost a few hundred bytes, where a miss costs a 91 KB
 * fallback fetch on a page that did not need one.
 */
function lucideNamesInIconFile(text, isLucideName) {
    if (! text.includes('data-lucide')) return [];

    const found = [];
    for (const match of text.matchAll(/["']([a-z][a-z0-9]*(?:-[a-z0-9]+)*)["']/g)) {
        if (isLucideName(match[1])) found.push(match[1]);
    }
    return found;
}

function walk(dir, exts, recurse = true) {
    const out = [];
    let entries;
    try {
        entries = readdirSync(path.join(root, dir));
    } catch {
        return out;
    }
    for (const entry of entries) {
        const rel = path.join(dir, entry);
        const full = path.join(root, rel);
        let st;
        try {
            st = statSync(full);
        } catch {
            continue;
        }
        if (st.isDirectory()) {
            if (recurse) out.push(...walk(rel, exts, recurse));
        } else if (exts.some((e) => entry.endsWith(e))) {
            // Skip the giant scraped content dumps: they hold no icon fields.
            if (st.size > 5 * 1024 * 1024) continue;
            out.push(full);
        }
    }
    return out;
}

const isLucideName = (name) =>
    Object.prototype.hasOwnProperty.call(lucide, toPascalCase(name)) && toPascalCase(name) !== 'icons';

const kebab = new Set();
for (const source of SOURCES) {
    for (const file of walk(source.dir, source.exts, source.recurse !== false)) {
        const text = readFileSync(file, 'utf8');
        for (const pattern of source.patterns) {
            for (const match of text.matchAll(pattern)) kebab.add(match[1].toLowerCase());
        }
        for (const name of bareIconLists(text)) kebab.add(name.toLowerCase());
        for (const name of lucideNamesInIconFile(text, isLucideName)) kebab.add(name);
    }
}

// Icons the runtime injects that a scan cannot see, plus Lucide's own default.
for (const extra of ['menu', 'x', 'chevron-down', 'chevron-up', 'sparkles', 'arrow-right', 'check', 'zap']) {
    kebab.add(extra);
}

const resolved = [];
const missing = [];
for (const name of [...kebab].sort()) {
    const pascal = toPascalCase(name);
    if (Object.prototype.hasOwnProperty.call(lucide, pascal) && pascal !== 'icons') resolved.push({ name, pascal });
    else missing.push(name);
}

function entrySource(icons) {
    const imports = icons.map(({ pascal }) => pascal).join(',\n  ');
    const entries = icons.map(({ pascal }) => `  ${pascal},`).join('\n');

    return `import { createIcons, createElement,
  ${imports}
} from 'lucide';

const icons = {
${entries}
};

// Where to find the complete library if we meet an icon we did not bundle.
const fallbackUrl = (document.currentScript && document.currentScript.dataset.lucideFallback) || '/${FULL}';

let requested = false;
function loadFullLibrary() {
  if (requested) return;
  requested = true;
  const script = document.createElement('script');
  script.src = fallbackUrl;
  // Loading it replaces window.lucide with the complete build, whose own
  // createIcons defaults to every icon — so re-running it finishes the page.
  script.onload = () => {
    try {
      if (window.lucide && window.lucide.createIcons !== run) window.lucide.createIcons();
    } catch (e) {
      /* the page keeps the icons it already has */
    }
  };
  document.head.appendChild(script);
}

function run(options) {
  // Lucide leaves data-lucide on the <svg> it creates, so anything still
  // matching this selector afterwards is an icon it could not resolve.
  createIcons({ icons, ...(options || {}) });
  if (document.querySelector('[data-lucide]:not(svg)')) loadFullLibrary();
}

window.lucide = { icons, createElement, createIcons: run };
`;
}

if (resolved.length === 0) {
    console.error('No icons resolved — refusing to write an empty bundle.');
    process.exit(1);
}

// The entry has to sit inside the project: esbuild resolves `lucide` by
// walking up for node_modules, which a path in the OS temp dir never finds.
const tmp = mkdtempSync(path.join(root, 'node_modules', '.lucide-subset-'));
const entry = path.join(tmp, 'entry.js');
writeFileSync(entry, entrySource(resolved), 'utf8');

// Call esbuild's binary directly: `npx` is not reliably on PATH here.
const esbuildBin = path.join(
    root,
    'node_modules',
    process.platform === 'win32' ? '@esbuild/win32-x64/esbuild.exe' : '.bin/esbuild'
);

execFileSync(
    esbuildBin,
    [entry, '--bundle', '--minify', '--format=iife', `--outfile=${path.join(root, OUT)}`],
    { cwd: root, stdio: 'inherit' }
);
rmSync(tmp, { recursive: true, force: true });

const bytes = statSync(path.join(root, OUT)).size;
const fullBytes = statSync(path.join(root, 'public', FULL)).size;
console.log(`\n${resolved.length} icons bundled -> ${OUT}`);
console.log(`${(bytes / 1024).toFixed(1)} KB (was ${(fullBytes / 1024).toFixed(1)} KB, ${(100 - (bytes / fullBytes) * 100).toFixed(1)}% smaller)`);
if (missing.length) console.log(`\nNot Lucide names, ignored: ${missing.join(', ')}`);
