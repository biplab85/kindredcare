/**
 * Motion gate — plan §9.
 *
 * Single source of truth for whether animation runs at all. Everything else
 * imports `prefersReducedMotion` rather than testing the media query itself,
 * so there is one place to change if the policy changes.
 */
const query = window.matchMedia('(prefers-reduced-motion: reduce)');

export const prefersReducedMotion = () => query.matches;

export const hasGsap = () => typeof window.gsap !== 'undefined';

/** Registers ScrollTrigger once, if both it and GSAP are present. */
export function initGsap() {
  if (!hasGsap()) return false;
  if (window.ScrollTrigger) {
    window.gsap.registerPlugin(window.ScrollTrigger);
  }
  return true;
}
