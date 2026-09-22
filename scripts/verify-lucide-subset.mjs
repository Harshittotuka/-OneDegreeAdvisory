/**
 * Proves the Lucide subset renders every icon the real pages ask for, and that
 * an icon it does not carry still reaches the full library.
 *
 * The subset exists to cut 404 KB down to 44 KB; this is the check that the
 * saving costs no icons. It runs the built bundle against real rendered HTML
 * in a DOM, exactly as a browser would.
 *
 * Usage: node scripts/verify-lucide-subset.mjs <dir-of-html-files>
 */
import { readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { JSDOM } from 'jsdom';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const bundle = readFileSync(path.join(root, 'public/assets/vendor/lucide-subset.min.js'), 'utf8');

const pagesDir = process.argv[2];
if (!pagesDir) {
    console.error('Usage: node scripts/verify-lucide-subset.mjs <dir-of-html-files>');
    process.exit(1);
}

/** Runs the subset over one document and reports what happened. */
function render(html) {
    const dom = new JSDOM(html, { runScripts: 'outside-only' });
    const { window } = dom;

    const before = window.document.querySelectorAll('[data-lucide]').length;
    const names = new Set(
        [...window.document.querySelectorAll('[data-lucide]')].map((el) => el.getAttribute('data-lucide'))
    );

    // Record any attempt to pull in the full library.
    const appended = [];
    const realAppend = window.document.head.appendChild.bind(window.document.head);
    window.document.head.appendChild = (node) => {
        if (node.tagName === 'SCRIPT') appended.push(node.src);
        return realAppend(node);
    };

    window.eval(bundle);
    window.lucide.createIcons();

    const unresolved = [...window.document.querySelectorAll('[data-lucide]:not(svg)')].map((el) =>
        el.getAttribute('data-lucide')
    );
    const svgs = window.document.querySelectorAll('svg[data-lucide]').length;

    return { before, names, svgs, unresolved, appended };
}

let failures = 0;
const allNames = new Set();

for (const file of readdirSync(pagesDir).filter((f) => f.endsWith('.html'))) {
    const { before, names, svgs, unresolved, appended } = render(
        readFileSync(path.join(pagesDir, file), 'utf8')
    );
    names.forEach((n) => allNames.add(n));

    const ok = unresolved.length === 0 && appended.length === 0 && svgs === before;
    if (!ok) failures++;

    console.log(
        `${ok ? 'PASS' : 'FAIL'}  ${file.padEnd(26)} ${String(before).padStart(4)} placeholders -> ${String(svgs).padStart(4)} svg` +
            (unresolved.length ? `  UNRESOLVED: ${[...new Set(unresolved)].join(', ')}` : '') +
            (appended.length ? `  FELL BACK TO: ${appended.join(', ')}` : '')
    );
}

// The fallback itself must work, or a CMS-chosen icon would silently vanish.
const probe = render(
    '<!doctype html><html><head></head><body><i data-lucide="check"></i><i data-lucide="axe"></i></body></html>'
);
const fallbackFired = probe.appended.length === 1 && probe.unresolved.includes('axe');
console.log(
    `${fallbackFired ? 'PASS' : 'FAIL'}  ${'(unknown icon -> fallback)'.padEnd(26)} requested: ${probe.appended.join(', ') || 'nothing'}`
);
if (!fallbackFired) failures++;

console.log(`\n${allNames.size} distinct icon names seen across the sampled pages.`);
console.log(failures === 0 ? 'All checks passed.' : `${failures} check(s) FAILED.`);
process.exit(failures === 0 ? 0 : 1);
