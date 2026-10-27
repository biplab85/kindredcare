/**
 * Taxonomy filter chips — section 04, app mode only.
 *
 * Filters the category grid client-side. Purely additive: with JS disabled
 * every tile is visible, which is the correct default.
 */
export function initChips() {
  const bar = document.querySelector('[data-chips]');
  const grid = document.querySelector('[data-cat-grid]');
  if (!bar || !grid) return;

  const chips = [...bar.querySelectorAll('[data-chip]')];
  const tiles = [...grid.querySelectorAll('[data-group]')];

  function apply(group) {
    chips.forEach((c) => c.setAttribute('aria-pressed', String(c.dataset.chip === group)));

    tiles.forEach((tile) => {
      const match = group === 'all' || tile.dataset.group === group;
      tile.hidden = !match;
    });
  }

  chips.forEach((chip) => chip.addEventListener('click', () => apply(chip.dataset.chip)));

  // The chip bar is hidden from lg up, where the grid is also explicitly placed
  // into named cells. A filter left active on a narrow viewport would therefore
  // strand itself: the control is gone, and each hidden tile leaves an empty cell
  // in the composition. Clear it on the way up.
  const wide = window.matchMedia('(min-width: 1024px)');
  const sync = () => {
    if (wide.matches) apply('all');
  };

  wide.addEventListener('change', sync);
  sync();
}
