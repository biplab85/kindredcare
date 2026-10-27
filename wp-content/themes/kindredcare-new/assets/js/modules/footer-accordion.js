/**
 * Footer columns as accordions — app mode only.
 *
 * Below md the four footer widgets stack, and three of them are long lists of
 * links. Stacked open they push the copyright line about a screen and a half
 * down the page, so the last thing a phone user meets is scrolling rather than
 * the contact details they came for. Collapsed, the footer is a short index.
 *
 * BELOW md ONLY, and torn down completely above it. The desktop footer is four
 * columns side by side — there is nothing to collapse there, and a residual
 * button or `aria-expanded` in the markup at that width would be a lie. So this
 * module builds on the way down and unbuilds on the way up, the same contract
 * the bottom bar keeps with its own media query.
 *
 * Structure it expects, per `[data-footer-acc]` widget:
 *
 *   <div class="l-footer__widget" data-footer-acc>
 *     <h3 class="l-footer__heading">Quick links</h3>   <- becomes the trigger
 *     <ul>…</ul>                                       <- becomes the panel
 *     …anything after the list is left alone           <- e.g. "Connect with us"
 *
 * The heading keeps its <h3>; the button goes inside it. Replacing the heading
 * with a bare button would cost the footer its outline.
 */
import { prefersReducedMotion } from './motion.js';

const MOBILE = '(max-width: 767.98px)';
const DURATION = 280;

const CHEVRON = `<svg class="l-footer__acc-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" /></svg>`;

export function initFooterAccordion() {
  const widgets = [...document.querySelectorAll('[data-footer-acc]')];
  if (!widgets.length) return;

  const mq = window.matchMedia(MOBILE);
  let built = [];

  const build = () => {
    widgets.forEach((widget, i) => {
      const heading = widget.querySelector('.l-footer__heading');
      const panel = heading?.nextElementSibling;
      if (!heading || !panel) return;

      const label = heading.textContent.trim();
      const id = `footer-acc-${i + 1}`;

      panel.id = id;
      panel.classList.add('l-footer__acc-panel');
      panel.hidden = true;

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'l-footer__acc-btn';
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-controls', id);
      btn.innerHTML = `<span>${label}</span>${CHEVRON}`;

      heading.textContent = '';
      heading.appendChild(btn);

      let animation = null;

      const animate = (from, to, opening) => {
        animation?.cancel();
        panel.style.overflow = 'hidden';
        animation = panel.animate(
          { height: [`${from}px`, `${to}px`] },
          { duration: DURATION, easing: 'cubic-bezier(0.25,0.46,0.45,0.94)' },
        );
        animation.onfinish = () => {
          animation = null;
          panel.style.overflow = '';
          if (!opening) panel.hidden = true;
        };
      };

      const toggle = () => {
        const open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!open));

        if (prefersReducedMotion()) {
          panel.hidden = open;
          return;
        }

        if (open) {
          animate(panel.offsetHeight, 0, false);
        } else {
          panel.hidden = false;
          animate(0, panel.scrollHeight, true);
        }
      };

      btn.addEventListener('click', toggle);

      built.push({ widget, heading, panel, label, btn });
    });
  };

  const teardown = () => {
    built.forEach(({ heading, panel, label }) => {
      heading.textContent = label;
      panel.hidden = false;
      panel.removeAttribute('id');
      panel.classList.remove('l-footer__acc-panel');
      // Not `style.overflow = ''` — that clears the value but leaves an empty
      // `style` attribute on the element. Nothing renders differently, but the
      // desktop footer should carry no trace that this module ran at all.
      panel.removeAttribute('style');
    });
    built = [];
  };

  const sync = () => {
    if (mq.matches && !built.length) build();
    else if (!mq.matches && built.length) teardown();
  };

  mq.addEventListener('change', sync);
  sync();
}
