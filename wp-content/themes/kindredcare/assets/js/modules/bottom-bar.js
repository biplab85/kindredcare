/**
 * Mobile bottom app bar — plan section 15. Below md only.
 *
 * Two behaviours:
 *   1. Present from the start, then follows scroll DIRECTION: it drops away
 *      while you read downward and comes straight back the moment you scroll
 *      up. Reading gets the full screen; the controls are one upward flick
 *      away, which is the gesture a phone user already makes when they want to
 *      navigate. It is also pinned open at the very top and at the very bottom
 *      of the page, so the two places where nobody is mid-read never hide it.
 *   2. Scroll-spy: mark the slot for the section currently in view.
 *
 * Scroll-spy still runs under reduced motion — it conveys location, not
 * decoration. Only the slide is suppressed.
 */
import { prefersReducedMotion } from './motion.js';

const MOBILE = '(max-width: 767.98px)';

// Movement below this is noise — a fingertip resting on a scrolling page, or
// momentum settling — and must not flip the bar.
const DELTA = 10;

// Inside this much of either end of the document the bar stays open regardless
// of direction.
const EDGE = 60;

export function initBottomBar() {
  const bar = document.querySelector('[data-bottom-bar]');
  if (!bar) return;

  const mq = window.matchMedia(MOBILE);

  /* ---- 1. Present at rest, hidden while scrolling down -------------------- */
  const show = () => bar.classList.add('is-visible');
  const hide = () => bar.classList.remove('is-visible');

  // Reduced motion: no slide, just presence.
  if (prefersReducedMotion()) {
    bar.style.transition = 'none';
  }

  show();

  let last = window.scrollY;
  let queued = false;

  const update = () => {
    queued = false;
    if (!mq.matches) return;

    const y = window.scrollY;

    // Overscroll on iOS reports positions outside the document, and the
    // rubber-band settling back produces a burst of alternating deltas. Clamp
    // rather than react.
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const clamped = Math.min(Math.max(y, 0), Math.max(max, 0));

    if (clamped <= EDGE || clamped >= max - EDGE) {
      show();
      last = clamped;
      return;
    }

    const delta = clamped - last;
    if (Math.abs(delta) < DELTA) return;

    if (delta > 0) hide();
    else show();

    // Only moved on a decision, so a slow drag accumulates towards the
    // threshold instead of being discarded frame by frame.
    last = clamped;
  };

  window.addEventListener(
    'scroll',
    () => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(update);
    },
    { passive: true },
  );

  // Crossing into mobile mid-session must not leave the bar parked off-screen.
  mq.addEventListener('change', () => {
    if (mq.matches) {
      last = window.scrollY;
      show();
    }
  });

  /* ---- 2. Scroll-spy ------------------------------------------------------ */
  const links = [...bar.querySelectorAll('[data-spy-target]')];
  if (!links.length) return;

  const sections = links
    .map((link) => {
      const id = link.getAttribute('data-spy-target');
      const el = document.getElementById(id);
      return el ? { link, el } : null;
    })
    .filter(Boolean);

  if (!sections.length) return;

  const setActive = (activeLink) => {
    links.forEach((link) => {
      if (link === activeLink) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
  };

  const spy = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((e) => e.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (!visible) return;
      const match = sections.find((s) => s.el === visible.target);
      if (match) setActive(match.link);
    },
    { rootMargin: '-40% 0px -50% 0px', threshold: [0, 0.25, 0.5, 1] },
  );

  sections.forEach(({ el }) => spy.observe(el));
}
