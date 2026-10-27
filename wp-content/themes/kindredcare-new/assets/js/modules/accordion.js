/**
 * FAQ accordion — section 12.
 *
 * Built on native <details>/<summary>, so it works with JS disabled. This
 * module only adds the height transition; it never gates the content.
 */
import { prefersReducedMotion } from './motion.js';

const DURATION = 280;

export function initAccordion() {
  const items = document.querySelectorAll('[data-accordion]');
  if (!items.length) return;

  if (prefersReducedMotion()) return; // native open/close is fine

  items.forEach((details) => {
    const summary = details.querySelector('summary');
    const body = details.querySelector('[data-accordion-body]');
    if (!summary || !body) return;

    let animation = null;

    const animate = (from, to, opening) => {
      animation?.cancel();
      animation = body.animate(
        { height: [`${from}px`, `${to}px`], opacity: opening ? [0, 1] : [1, 0] },
        { duration: DURATION, easing: 'cubic-bezier(0.25,0.46,0.45,0.94)' },
      );
      animation.onfinish = () => {
        animation = null;
        body.style.height = '';
        if (!opening) details.open = false;
      };
    };

    summary.addEventListener('click', (e) => {
      e.preventDefault();

      if (details.open) {
        const start = body.offsetHeight;
        animate(start, 0, false);
      } else {
        details.open = true;
        const end = body.offsetHeight;
        animate(0, end, true);
      }
    });
  });
}
