/**
 * Fancybox bindings — plan §8.
 *
 * Tier and founder modals use inline content because their bodies are prose and
 * lists. Service tiles no longer open a modal: each links to its own detail page
 * (inc/service-pages.php).
 *
 * Below md every instance presents as a bottom sheet (see _fancybox.scss);
 * dragToClose is enabled there because dragging a sheet down to dismiss is
 * expected app behaviour, and disabled for the centred desktop dialog.
 */
const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;

export function initModals() {
  const fb = window.Fancybox;
  if (!fb) return;

  fb.defaults.dragToClose = isMobile();
  fb.defaults.Hash = false;
  fb.defaults.autoFocus = true;
  fb.defaults.trapFocus = true;
  fb.defaults.placeFocusBack = true;

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
