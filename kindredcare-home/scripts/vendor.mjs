/**
 * Copies the browser-ready files we actually use out of node_modules into
 * src/vendor/, so index.html can load them with plain <script>/<link> tags.
 *
 * No bundler. This keeps the build portable into the WordPress theme later,
 * where a bundled ES-module graph would be a liability.
 */
import { cp, mkdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');

// Only what is used. Swiper and Fancybox arrive in build steps 6+ but are
// wired now so the vendor step never has to change again.
const files = [
  ['node_modules/gsap/dist/gsap.min.js', 'src/vendor/gsap/gsap.min.js'],
  ['node_modules/gsap/dist/ScrollTrigger.min.js', 'src/vendor/gsap/ScrollTrigger.min.js'],
  ['node_modules/swiper/swiper-bundle.min.js', 'src/vendor/swiper/swiper-bundle.min.js'],
  ['node_modules/swiper/swiper-bundle.min.css', 'src/vendor/swiper/swiper-bundle.min.css'],
  ['node_modules/@fancyapps/ui/dist/fancybox/fancybox.umd.js', 'src/vendor/fancybox/fancybox.umd.js'],
  ['node_modules/@fancyapps/ui/dist/fancybox/fancybox.css', 'src/vendor/fancybox/fancybox.css'],
];

let copied = 0;
const missing = [];

for (const [from, to] of files) {
  const src = resolve(root, from);
  const dest = resolve(root, to);
  if (!existsSync(src)) {
    missing.push(from);
    continue;
  }
  await mkdir(dirname(dest), { recursive: true });
  await cp(src, dest);
  copied++;
}

console.log(`vendor: copied ${copied}/${files.length} file(s) to src/vendor/`);
if (missing.length) {
  console.warn('vendor: missing (run `npm install` first):');
  missing.forEach((m) => console.warn(`  - ${m}`));
}
