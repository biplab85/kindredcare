/**
 * Fancybox bindings — plan §8.
 *
 * Category modals are generated from data attributes on the tiles rather than
 * twelve blocks of hidden markup. Tier and founder modals use inline content
 * because their bodies are prose and lists.
 *
 * Below md every instance presents as a bottom sheet (see _fancybox.scss);
 * dragToClose is enabled there because dragging a sheet down to dismiss is
 * expected app behaviour, and disabled for the centred desktop dialog.
 */
const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;

const esc = (s) =>
  String(s).replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
  );

function categoryHtml(el) {
  const { name, rate, tier, blurb } = el.dataset;
  return `
    <div class="c-cat-modal">
      <span class="u-eyebrow c-cat-modal__eyebrow">Service category</span>
      <h2 class="c-cat-modal__title">${esc(name)}</h2>
      <p class="c-cat-modal__text">${esc(blurb)}</p>
      <div class="c-cat-modal__meta">
        <div>
          <span class="c-cat-modal__meta-label">Typical rate</span>
          <span class="c-cat-modal__meta-value">${esc(rate)}</span>
        </div>
        <div>
          <span class="c-cat-modal__meta-label">Verification required</span>
          <span class="c-cat-modal__meta-value">${esc(tier)}</span>
        </div>
      </div>
      <div class="c-cat-modal__actions">
        <a class="c-btn c-btn--primary" href="#final-cta" data-fancybox-close>
          Find a caregiver
        </a>
      </div>
    </div>`;
}

export function initModals() {
  const fb = window.Fancybox;
  if (!fb) return;

  fb.defaults.dragToClose = isMobile();
  fb.defaults.Hash = false;
  fb.defaults.autoFocus = true;
  fb.defaults.trapFocus = true;
  fb.defaults.placeFocusBack = true;

  // Category tiles → generated modal
  document.querySelectorAll('[data-category]').forEach((tile) => {
    tile.addEventListener('click', (e) => {
      e.preventDefault();
      fb.show([{ src: categoryHtml(tile), type: 'html' }], { dragToClose: isMobile() });
    });
  });

  // Inline modals — tiers and founders
  fb.bind('[data-fancybox-inline]', {
    dragToClose: isMobile(),
    groupAll: false,
  });

  // Close buttons inside generated content
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-fancybox-close]')) fb.close();
  });
}
