/**
 * Hero headline typewriter.
 *
 * The full string already sits in the DOM, so this is a pure enhancement: with
 * JS off, or under prefers-reduced-motion, the headline is simply there. The
 * ghost copy beside it reserves the final line box, so typing never reflows
 * the CTA or the cards beneath.
 *
 * Each character is scheduled against elapsed wall-clock time rather than
 * advanced one-per-frame. The hero decodes three large photographs on load and
 * frames there run as slow as 235ms — a per-frame counter stretches the whole
 * effect out under that contention. This way a slow frame catches up instead,
 * and the headline always finishes in the same ~1.45s.
 */
import { prefersReducedMotion } from './motion.js';

const CHAR_MS = 34;
const START_DELAY = 200;

// Punctuation is where a real typist pauses; without this it reads mechanical.
const PAUSE_AFTER = { ',': 220, '.': 180 };

/** Wall-clock offset at which each character becomes visible. */
function buildSchedule(text) {
  let t = 0;
  return [...text].map((char) => {
    t += CHAR_MS;
    const at = t;
    t += PAUSE_AFTER[char] ?? 0;
    return at;
  });
}

export function initTypewriter() {
  const el = document.querySelector('[data-typewriter]');
  if (!el) return;

  const full = el.textContent.replace(/\s+/g, ' ').trim();
  if (!full) return;

  // Reduced motion: leave the headline exactly as authored.
  if (prefersReducedMotion()) return;

  const schedule = buildSchedule(full);

  el.textContent = '';
  el.classList.add('is-typing');

  let start = null;
  let index = 0;

  const step = (now) => {
    if (start === null) start = now;
    const elapsed = now - start - START_DELAY;

    if (elapsed >= 0) {
      const before = index;
      while (index < full.length && schedule[index] <= elapsed) index += 1;
      if (index !== before) el.textContent = full.slice(0, index);
    }

    if (index < full.length) {
      requestAnimationFrame(step);
    } else {
      el.classList.remove('is-typing');
    }
  };

  requestAnimationFrame(step);
}
