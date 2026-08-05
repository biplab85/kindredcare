/**
 * Swiper instances — plan §8.
 *
 * Autoplay is OFF everywhere. Auto-advancing content is hostile to older
 * readers and to anyone using a screen reader.
 *
 * Peek of 1.15 slides so the next card is visibly cut — that signals
 * swipeability without an instruction.
 */
import { prefersReducedMotion } from './motion.js';

const speed = () => (prefersReducedMotion() ? 0 : 400);

export function initSliders() {
  if (typeof window.Swiper === 'undefined') return;
  const { Swiper } = window;

  // 01 — Hero photography. Reference: loop, 1400ms, prev/next only.
  // Autoplay stays off with everything else: the headline never moves, and
  // the images advance only when someone asks for them.
  const hero = document.querySelector('[data-slider="hero"]');
  if (hero) {
    new Swiper(hero, {
      slidesPerView: 1,
      loop: true,
      speed: prefersReducedMotion() ? 0 : 1400,
      a11y: { enabled: true },
      keyboard: { enabled: true },
      navigation: {
        prevEl: document.querySelector('[data-slider-prev="hero"]'),
        nextEl: document.querySelector('[data-slider-next="hero"]'),
      },
    });
  }

  // 03 — How It Works. Mobile only; the grid takes over at lg.
  const how = document.querySelector('[data-slider="how"]');
  if (how) {
    new Swiper(how, {
      slidesPerView: 1.15,
      spaceBetween: 16,
      speed: speed(),
      a11y: { enabled: true },
      pagination: { el: how.querySelector('.swiper-pagination'), clickable: true },
      breakpoints: {
        // Disabled at lg — CSS grid handles the four-column layout.
        1024: { enabled: false, slidesPerView: 4, spaceBetween: 32 },
      },
    });
  }

  // 05 — Caregivers / founding-caregiver placeholder.
  const gigs = document.querySelector('[data-slider="gigs"]');
  if (gigs) {
    new Swiper(gigs, {
      slidesPerView: 1.15,
      spaceBetween: 16,
      speed: speed(),
      grabCursor: true,
      a11y: { enabled: true },
      // Outside the .swiper element: the bullets share a row with the arrows.
      pagination: { el: document.querySelector('[data-pagination="gigs"]'), clickable: true },
      navigation: {
        prevEl: document.querySelector('[data-slider-prev="gigs"]'),
        nextEl: document.querySelector('[data-slider-next="gigs"]'),
      },
      breakpoints: {
        640: { slidesPerView: 2.1, spaceBetween: 20 },
        // 2.15, not 2.4: at 2.4 the card was narrow enough to break titles
        // mid-word, and the peek read as a clipped card rather than as more to come.
        1024: { slidesPerView: 2.15, spaceBetween: 28 },
      },
    });
  }
}
