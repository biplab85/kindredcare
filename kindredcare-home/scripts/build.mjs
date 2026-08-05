/**
 * Copies src/ to dist/ for deployment, minus the SCSS sources.
 * CSS is already compiled into src/css by build:css before this runs.
 */
import { cp, rm, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const src = resolve(root, 'src');
const dist = resolve(root, 'dist');

await rm(dist, { recursive: true, force: true });
await mkdir(dist, { recursive: true });

await cp(src, dist, {
  recursive: true,
  filter: (path) => !path.includes(`${'scss'}`),
});

console.log('build: src/ -> dist/ (scss excluded)');
