/**
 * Swiper instances — plan §8.
 *
 * Autoplay is OFF everywhere except the hero photograph. Auto-advancing content
 * is hostile to older readers and to anyone using a screen reader, so the one
 * exception is scenery rather than content: no words move, nothing is missed by
 * not watching it, and it stops the moment anyone shows interest — hover,
 * keyboard focus, or a reduced-motion preference.
 *
 * Peek of 1.15 slides so the next card is visibly cut — that signals
 * swipeability without an instruction.
 */
import { prefersReducedMotion } from './motion.js';

const speed = () => (prefersReducedMotion() ? 0 : 400);

/*
 * How long each hero photograph holds before the next fades in. Long on
 * purpose: this sits beside a headline someone is still reading, and a picture
 * changing every couple of seconds pulls the eye off the words.
 */
const HERO_DELAY = 5000;

export function initSliders() {
  if (typeof window.Swiper === 'undefined') return;
  const { Swiper } = window;

  // 01 — Hero photography. Reference: loop, 1400ms, prev/next only.
  const hero = document.querySelector('[data-slider="hero"]');
  if (hero) {
    const still = prefersReducedMotion();
    const heroPrev = document.querySelector('[data-slider-prev="hero"]');
    const heroNext = document.querySelector('[data-slider-next="hero"]');

    /*
     * The countdown ring and its counter. Counted before Swiper initialises:
     * looping clones slides, so afterwards .swiper-slide no longer answers "how
     * many photographs are there".
     */
    const total = hero.querySelectorAll('.swiper-slide').length;
    const progress = document.querySelector('[data-hero-progress]');
    const ring = document.querySelector('[data-hero-progress-bar]');
    const now = document.querySelector('[data-hero-progress-now]');

    // The ring is drawn by dashing its own outline, so the dash has to be
    // exactly as long as the circle is round.
    const circumference = ring ? ring.getTotalLength() : 0;
    if (ring) {
      ring.style.strokeDasharray = `${circumference}`;
      ring.style.strokeDashoffset = `${circumference}`;
    }

    /*
     * With autoplay off there is no countdown to draw, so the moving stroke is
     * dropped and the ring stays as a plain outline around the number.
     */
    if (progress && still) {
      progress.classList.add('is-static');
    }

    const heroSwiper = new Swiper(hero, {
      slidesPerView: 1,
      loop: true,
      speed: still ? 0 : 1400,

      /*
       * Cross-fade rather than slide. The frame stays put and the photograph
       * inside it changes, which reads as one image becoming another instead of
       * a filmstrip being dragged past — and nothing beside it appears to move,
       * which is what keeps it out of the way of the headline.
       */
      effect: 'fade',
      fadeEffect: { crossFade: true },

      autoplay: still
        ? false
        : {
            delay: HERO_DELAY,
            /*
             * Using the arrows pauses the rotation while someone is looking; it
             * does not switch it off for the rest of the visit, which is what
             * the Swiper default would do.
             */
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
          },

      a11y: { enabled: true },
      keyboard: { enabled: true },
      navigation: {
        prevEl: heroPrev,
        nextEl: heroNext,
      },

      on: {
        /*
         * Swiper counts this one down: `left` runs 1 to 0 across the delay. The
         * ring is filled by shortening its dash offset by the same fraction, so
         * a full circle means the next photograph is due.
         */
        autoplayTimeLeft(swiper, time, left) {
          if (ring) {
            ring.style.strokeDashoffset = `${circumference * left}`;
          }
        },

        // realIndex, not activeIndex: with looping the latter counts the clones.
        init: (swiper) => setCount(swiper),
        slideChange: (swiper) => setCount(swiper),
      },
    });

    function setCount(swiper) {
      if (now) {
        now.textContent = String(swiper.realIndex + 1);
      }
    }

    // `init` fires before this handler is attached when Swiper starts
    // immediately, so the first number is written here rather than relied upon.
    setCount(heroSwiper);
    if (total) {
      hero.setAttribute('data-slide-total', String(total));
    }

    /*
     * pauseOnMouseEnter is a pointer-only rule. Tabbing to the arrows would
     * otherwise leave the photograph changing under a keyboard user with no way
     * to ask it to stop, so focus pauses it as well — and because the arrows
     * sit outside the slider element, each one is listened to in its own right.
     *
     * Keyboard focus only, hence :focus-visible. Clicking an arrow also focuses
     * it, and a plain focus rule would leave the rotation stopped for the rest
     * of the visit for anyone using a mouse — the exact thing
     * disableOnInteraction: false is set to avoid.
     */
    if (!still && heroSwiper.autoplay) {
      [hero, heroPrev, heroNext].filter(Boolean).forEach((el) => {
        el.addEventListener('focusin', (event) => {
          const target = event.target;
          if (target instanceof Element && target.matches(':focus-visible')) {
            heroSwiper.autoplay.stop();
          }
        });
        el.addEventListener('focusout', () => heroSwiper.autoplay.start());
      });
    }
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
