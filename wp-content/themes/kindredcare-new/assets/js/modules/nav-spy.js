/**
 * Section scroll-spy for the header bar — plan section 00.
 *
 * The underline in the bar was only ever the server's answer. WordPress marks
 * the current *page*, and the front page is one page, so "Home" stayed lit from
 * the hero all the way to the footer while the reader was three sections deep.
 * The mobile bottom bar already spies its sections (bottom-bar.js section 2);
 * this gives the desktop bar the same sense of place.
 *
 * Like that one it runs under reduced motion — it reports position, it does not
 * animate.
 *
 * Off the front page the menu's fragments are rewritten to absolute URLs by
 * kindredcare_resolve_menu_anchors(), so none of them resolve to an element in
 * the current document and the spy leaves the server's answer alone.
 */

/*
 * Where "you are here" is decided: the last section whose top has passed this
 * line owns the bar. 40% down the viewport is the top edge of the bottom bar's
 * spy band, so on a tablet wide enough to show both they never disagree.
 */
const LINE = 0.4;

/*
 * Every navigation that lists the same sections. The bar and the drawer are
 * separate markup answering the same question, and a reader who opens the
 * drawer mid-page expects it to know where they are just as the bar does.
 */
const NAVS = ['.l-header__nav', '.c-offcanvas__nav'];

export function initNavSpy() {
  NAVS.forEach((selector) => {
    const nav = document.querySelector(selector);
    if (nav) spyNav(nav);
  });
}

function spyNav(nav) {
  const links = [...nav.querySelectorAll('a')];

  const targets = links
    .map((link) => {
      // Bare "#services" here, but an editor may well paste a full permalink
      // with the fragment on the end, and that should spy just the same.
      const id = (link.getAttribute('href') || '').split('#')[1];
      const el = id ? document.getElementById(id) : null;
      return el ? { link, el } : null;
    })
    .filter(Boolean)
    // Menu order is the editor's; scroll order is the document's. Sort by the
    // latter, or dragging "Services" above "How it works" in wp-admin would
    // make the bar report the wrong section.
    .sort(({ el: a }, { el: b }) =>
      a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1,
    );

  if (!targets.length) return;

  /*
   * Above the first spied section there is still a correct answer — the hero
   * belongs to "Home". Whatever the server lit is that answer, captured before
   * the first resolve overwrites it.
   */
  const fallback = links.find((link) => link.classList.contains('is-active')) || null;

  const setCurrent = (current) => {
    links.forEach((link) => {
      const on = link === current;
      link.classList.toggle('is-active', on);

      // aria-current is left to exact matches only, the same rule the header
      // walker follows — see class-kindredcare-header-walker.php.
      if (on) {
        link.setAttribute('aria-current', 'page');
      } else if ('page' === link.getAttribute('aria-current')) {
        link.removeAttribute('aria-current');
      }
    });
  };

  const resolve = () => {
    const line = window.innerHeight * LINE;
    let current = null;

    for (const target of targets) {
      if (target.el.getBoundingClientRect().top <= line) {
        current = target;
      }
    }

    setCurrent(current ? current.link : fallback);
  };

  /*
   * The observer is a trigger, not the answer. Its root is a one-percent strip
   * sitting on the line, so it fires exactly when a section boundary crosses it
   * — entering *and* leaving — and the handful of rect reads in resolve() then
   * settle which section that leaves us in. A plain isIntersecting reading
   * cannot do that on its own: scrolling back up out of the first section, no
   * observed element intersects anything, and the bar would stay stuck on it
   * instead of handing back to Home.
   */
  const spy = new IntersectionObserver(resolve, {
    rootMargin: `-${LINE * 100}% 0px -${100 - LINE * 100 - 1}% 0px`,
    threshold: 0,
  });

  targets.forEach(({ el }) => spy.observe(el));

  /*
   * The net under the observer. Crossing the strip is what the observer
   * watches for, and a jump can clear the whole strip between two frames —
   * isIntersecting reads false before and false after, no change, nothing
   * fires, and the bar keeps whatever it said before the jump. Anything that
   * moves the page without scrolling through the strip lands here: an anchor
   * with smooth scrolling turned off, a scroll restored by the browser, a
   * pinned section collapsing its spacer.
   *
   * One resolve per frame, and only while the page is actually moving — which
   * is still less work than the per-frame scroll handler the observer exists to
   * avoid, since that one would run whether or not anything had moved.
   */
  let queued = 0;
  window.addEventListener(
    'scroll',
    () => {
      if (queued) return;
      queued = requestAnimationFrame(() => {
        queued = 0;
        resolve();
      });
    },
    { passive: true },
  );

  // A reload part-way down the page, or an inbound /#services link, is already
  // somewhere before the first boundary is ever crossed.
  resolve();
}
