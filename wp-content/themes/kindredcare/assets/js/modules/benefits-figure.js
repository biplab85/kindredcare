/**
 * Benefits portrait — moved to the foot of the section below md.
 *
 * At lg the section is a 5/7 split: heading and portrait in the left column,
 * card and counter rail in the right. Collapsed to one column the portrait
 * lands between the heading and the argument it introduces, so the reader meets
 * a 389px photograph before a single benefit. At the foot it closes the section
 * instead of interrupting it.
 *
 * BELOW md ONLY, and put back on the way up — the desktop DOM is the desktop
 * DOM. Same contract as the footer accordion and the bottom bar.
 *
 * Done in the DOM rather than with `order`, because the only pure-CSS route is
 * `display: contents` on the intro column, and that would silently kill its
 * `data-reveal` (an element with no box has no opacity to animate). Moving the
 * node keeps the heading's reveal intact and puts the reading order where the
 * visual order is.
 */
const MOBILE = '(max-width: 767.98px)';

export function initBenefitsFigure() {
  const figure = document.querySelector('[data-benefits-figure]');
  const layout = document.querySelector('[data-benefits-layout]');
  if (!figure || !layout) return;

  // Where it lives at every other width: last child of the intro column.
  const home = figure.parentElement;
  const mq = window.matchMedia(MOBILE);

  const sync = () => {
    const parent = mq.matches ? layout : home;
    if (figure.parentElement !== parent) parent.appendChild(figure);
  };

  mq.addEventListener('change', sync);
  sync();
}
