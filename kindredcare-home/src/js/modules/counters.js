/**
 * Price count-up — section 06.
 *
 * Real figures only, sourced from our own blog research. Fires once when
 * scrolled into view. Under reduced motion the final value renders
 * immediately (plan §9).
 */
import { prefersReducedMotion, hasGsap } from './motion.js';

export function initCounters() {
  const targets = document.querySelectorAll('[data-count-to]');
  if (!targets.length) return;

  const render = (el, value) => {
    const decimals = Number(el.dataset.countDecimals || 0);
    const prefix = el.dataset.countPrefix || '';
    const suffix = el.dataset.countSuffix || '';
    el.textContent = `${prefix}${value.toFixed(decimals)}${suffix}`;
  };

  if (prefersReducedMotion() || !hasGsap() || !window.ScrollTrigger) {
    targets.forEach((el) => render(el, Number(el.dataset.countTo)));
    return;
  }

  const { gsap } = window;

  targets.forEach((el) => {
    const to = Number(el.dataset.countTo);
    const obj = { v: 0 };

    // The true value is already in the markup and is NEVER zeroed up front.
    // If the trigger somehow doesn't fire, the real figure stays on screen —
    // a stat that renders "0%" is the exact failure the live site shipped
    // ("0% Verified care-giver"), and it must not be reachable here.
    gsap.to(obj, {
      v: to,
      duration: 1.1,
      ease: 'power2.out',
      immediateRender: false,
      scrollTrigger: { trigger: el, start: 'top 92%', once: true },
      onUpdate: () => render(el, obj.v),
    });
  });

  // Sections above may still be settling (fonts, images); recalculate
  // trigger positions once layout is stable.
  window.addEventListener('load', () => window.ScrollTrigger.refresh());
}

/** Connector line for section 03, drawn as the steps come into view. */
export function initStepLine() {
  const line = document.querySelector('[data-step-line]');
  if (!line) return;
  if (prefersReducedMotion() || !hasGsap() || !window.ScrollTrigger) return;

  const { gsap } = window;
  gsap.fromTo(
    line,
    { scaleX: 0 },
    {
      scaleX: 1,
      duration: 0.9,
      ease: 'power2.out',
      scrollTrigger: { trigger: line, start: 'top 85%', once: true },
    },
  );
}
