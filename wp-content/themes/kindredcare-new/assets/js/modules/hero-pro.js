/**
 * Premium hero slider (inc/hero-pro.php).
 *
 * Same rules as the first hero's photograph: cross-fade so nothing beside it
 * appears to move, and rotation stops for hover, keyboard
 * focus or a reduced-motion preference.
 */
import { prefersReducedMotion } from './motion.js';

const DELAY = 5500;

export function initHeroPro() {
  if (typeof window.Swiper === 'undefined') return;

  const el = document.querySelector('[data-slider="hero-pro"]');
  if (!el) return;

  const still = prefersReducedMotion();
  const prev = document.querySelector('[data-slider-prev="hero-pro"]');
  const next = document.querySelector('[data-slider-next="hero-pro"]');
  const now = document.querySelector('[data-hero-pro-now]');
  const bar = document.querySelector('[data-hero-pro-bar]');

  // Counted before Swiper initialises: looping clones slides.
  const total = el.querySelectorAll('.swiper-slide').length;
  if (total < 2) return;

  const setCount = (swiper) => {
    if (now) now.textContent = String(swiper.realIndex + 1).padStart(2, '0');
  };

  const swiper = new window.Swiper(el, {
    slidesPerView: 1,
    loop: true,
    speed: still ? 0 : 1400,
    effect: 'fade',
    fadeEffect: { crossFade: true },
    autoplay: still
      ? false
      : { delay: DELAY, disableOnInteraction: false, pauseOnMouseEnter: true },
    a11y: { enabled: true },
    navigation: { prevEl: prev, nextEl: next },
    on: {
      // `left` runs 1 to 0 across the delay; the bar fills as it falls.
      autoplayTimeLeft(s, time, left) {
        if (bar) bar.style.transform = `scaleX(${1 - left})`;
      },
      slideChange: setCount,
    },
  });

  setCount(swiper);
  if (still && bar) bar.style.transform = 'scaleX(1)';

  // Keyboard focus pauses rotation too — pauseOnMouseEnter is pointer-only.
  if (!still && swiper.autoplay) {
    [el, prev, next].filter(Boolean).forEach((node) => {
      node.addEventListener('focusin', (event) => {
        if (event.target instanceof Element && event.target.matches(':focus-visible')) {
          swiper.autoplay.stop();
        }
      });
      node.addEventListener('focusout', () => swiper.autoplay.start());
    });
  }
}
