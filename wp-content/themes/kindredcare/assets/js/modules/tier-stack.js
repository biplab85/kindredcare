/**
 * Section 07 motion — the tier cards arrive stacked, then deal out.
 *
 * At rest the three cards sit converged on the middle one, fanned a few degrees
 * and scaled back, so the row reads at first glance like an overlap bug. When the
 * section comes into view they slide to their real grid positions. Scroll back up
 * above the section and they collapse again — the effect toggles rather than
 * playing once.
 *
 * The cards are grid items and stay in their cells throughout: only `x`, `rotate`
 * and `scale` are animated, so nothing reflows and the reverse is exact. The
 * distance each one travels is measured from the live layout rather than
 * hardcoded, which is what keeps it correct at every container width.
 *
 * These cards deliberately no longer carry `data-reveal`. The shared reveal in
 * reveal.js animates the same elements' transform, and transform is a single
 * property — two modules writing it means whichever runs last wins.
 */
import { prefersReducedMotion, hasGsap } from './motion.js';

// Below this the grid is a single column, so there is no horizontal room to
// converge into and the effect is switched off.
const LG = 1024;

// How far each outer card stays clear of the middle one at rest. Zero would hide
// them completely; this keeps the stack legible as a stack.
const PEEK = 16;

const FAN = 3; // degrees
const SCALE = 0.95;

export function initTierStack() {
  const grid = document.querySelector('[data-tier-stack]');
  if (!grid) return;

  const cards = [...grid.querySelectorAll('[data-tier-card]')];
  if (cards.length < 3) return;

  // Nothing to undo: the cards are in their final positions in the markup, so
  // bailing out here leaves the row correct and fully readable.
  if (prefersReducedMotion() || !hasGsap() || !window.ScrollTrigger) return;

  const { gsap } = window;

  let st = null;
  let lastWidth = 0;

  function teardown() {
    if (!st) return;
    st.kill();
    st = null;
    gsap.killTweensOf(cards);
    gsap.set(cards, { clearProps: 'transform' });
  }

  function build() {
    const width = grid.offsetWidth;
    if (width === lastWidth) return;
    lastWidth = width;

    teardown();
    if (window.innerWidth < LG) return;

    // offsetLeft, not a client rect: the cards may already be transformed from a
    // previous pass, and a client rect would measure the transformed position.
    const middle = cards[1].offsetLeft;

    const stacked = cards.map((card, i) => {
      const dx = middle - card.offsetLeft;
      if (i === 0) return { x: dx - PEEK, rotate: -FAN, scale: SCALE };
      if (i === cards.length - 1) return { x: dx + PEEK, rotate: FAN, scale: SCALE };
      return { x: 0, rotate: 0, scale: 1 };
    });

    // Two plain tween sets driven by explicit callbacks, rather than a timeline
    // on `toggleActions`. A timeline of nested `fromTo`s strands itself partway
    // when the trigger toggles rapidly — scrolling quickly through the boundary
    // left it parked at progress 0.53 with the cards half-dealt. `overwrite`
    // makes each direction cancel the other outright, so any sequence of
    // enter/leave events still resolves to one of the two end states.
    const deal = () => {
      cards.forEach((card, i) => {
        gsap.to(card, {
          x: 0,
          rotate: 0,
          scale: 1,
          duration: 0.75,
          ease: 'power3.out',
          // The middle card is already home, so the stagger reads as the outer
          // two being drawn off the pile.
          delay: i * 0.09,
          overwrite: 'auto',
        });
      });
    };

    const pile = () => {
      cards.forEach((card, i) => {
        gsap.to(card, {
          ...stacked[i],
          duration: 0.5,
          ease: 'power2.inOut',
          overwrite: 'auto',
        });
      });
    };

    cards.forEach((card, i) => gsap.set(card, stacked[i]));

    st = window.ScrollTrigger.create({
      trigger: grid,
      // Deliberately late. At `top 82%` the pile only cleared the bottom edge
      // before it dealt out, so the overlap never registered — which is the whole
      // first beat. At 58% the stack is well inside the viewport first.
      start: 'top 58%',
      invalidateOnRefresh: true,
      onEnter: deal,
      onLeaveBack: pile,
    });

    // Already scrolled past on load: skip straight to the dealt state rather than
    // animating something the visitor never saw stacked.
    if (st.progress > 0) {
      cards.forEach((card) => gsap.set(card, { x: 0, rotate: 0, scale: 1 }));
    }
  }

  build();

  let frame = 0;
  const schedule = () => {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(build);
  };

  window.addEventListener('resize', schedule);
  // Card heights depend on how the copy wraps, so a late webfont moves them.
  document.fonts?.ready.then(schedule);
}
