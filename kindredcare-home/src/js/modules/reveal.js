/**
 * Scroll reveal — plan §9.
 *
 * Two cues only, reproducing the reference's restraint (it uses exactly two
 * across 36 elements) without adding scrollCue.js alongside GSAP:
 *
 *   data-reveal="up"    24px vertical  — the default
 *   data-reveal="left"  28px horizontal — hero type block and split sections
 *
 * data-reveal-group on a parent staggers its direct [data-reveal] children.
 *
 * Content is never hidden behind animation: the hidden start state is applied
 * by CSS only under [data-reveal-ready], which this module sets. No JS, or
 * reduced motion, means everything is simply visible.
 */
import { prefersReducedMotion, hasGsap } from './motion.js';

const DURATION = 0.6;
const STAGGER = 0.07;
const STAGGER_CAP = 6;
const START = 'top 85%';

export function initReveal() {
  const targets = document.querySelectorAll('[data-reveal]');
  if (!targets.length) return;

  // Bail to the visible final state.
  if (prefersReducedMotion() || !hasGsap() || !window.ScrollTrigger) return;

  document.documentElement.setAttribute('data-reveal-ready', '');

  const { gsap } = window;

  // Grouped children first, so they are not also caught individually.
  const grouped = new Set();

  document.querySelectorAll('[data-reveal-group]').forEach((group) => {
    const children = [...group.querySelectorAll(':scope > [data-reveal]')];
    if (!children.length) return;

    children.forEach((child) => grouped.add(child));

    gsap.to(children.slice(0, STAGGER_CAP * 2), {
      opacity: 1,
      x: 0,
      y: 0,
      duration: DURATION,
      ease: 'power2.out',
      stagger: { each: STAGGER, amount: Math.min(children.length, STAGGER_CAP) * STAGGER },
      scrollTrigger: { trigger: group, start: START, once: true },
      onComplete: () => children.forEach((c) => c.classList.add('is-revealed')),
    });
  });

  targets.forEach((el) => {
    if (grouped.has(el)) return;

    gsap.to(el, {
      opacity: 1,
      x: 0,
      y: 0,
      duration: DURATION,
      ease: 'power2.out',
      scrollTrigger: { trigger: el, start: START, once: true },
      onComplete: () => el.classList.add('is-revealed'),
    });
  });
}

/**
 * Hero load timeline. Runs on load rather than on scroll — the hero is already
 * in view, and the headline must be readable within 200ms, so the type block
 * animates from a very short offset and nothing gates its legibility.
 */
export function initHeroIntro() {
  const hero = document.querySelector('[data-hero]');
  if (!hero) return;

  if (prefersReducedMotion() || !hasGsap()) return;

  const { gsap } = window;
  const items = hero.querySelectorAll('[data-hero-item]');
  if (!items.length) return;

  gsap.fromTo(
    items,
    { opacity: 0, x: -20 },
    {
      opacity: 1,
      x: 0,
      duration: 0.7,
      ease: 'power2.out',
      stagger: 0.08,
      clearProps: 'transform',
    },
  );
}
