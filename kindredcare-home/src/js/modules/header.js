/**
 * Sticky header — plan section 00.
 * Transparent over the hero; condenses after 80px of scroll.
 *
 * Uses an IntersectionObserver sentinel rather than a scroll listener so there
 * is no per-frame work on the main thread.
 */
const CONDENSE_AT = 80;

export function initHeader() {
  const header = document.querySelector('[data-header]');
  if (!header) return;

  const sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  sentinel.style.cssText = `position:absolute;top:0;left:0;width:1px;height:${CONDENSE_AT}px;pointer-events:none;`;
  document.body.prepend(sentinel);

  const observer = new IntersectionObserver(
    ([entry]) => header.classList.toggle('is-condensed', !entry.isIntersecting),
    { threshold: 0 },
  );

  observer.observe(sentinel);
}
