/**
 * Off-canvas panel behind the circular menu button.
 *
 * The reference shows this control on every breakpoint, so it is wired for
 * real rather than left as decoration — a button that does nothing is worse
 * than no button.
 *
 * Focus is trapped while open and returned to the trigger on close, and the
 * background is inert. Plan §12.
 */
export function initOffcanvas() {
  const panel = document.querySelector('[data-offcanvas]');
  const triggers = [...document.querySelectorAll('[data-offcanvas-open]')];
  if (!panel || !triggers.length) return;

  const surface = panel.querySelector('[data-offcanvas-panel]');
  const closers = [...panel.querySelectorAll('[data-offcanvas-close]')];
  const main = document.querySelector('main');
  let lastTrigger = null;

  const focusable = () =>
    [...surface.querySelectorAll('a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])')];

  const open = (trigger) => {
    lastTrigger = trigger;
    panel.classList.add('is-open');
    document.body.classList.add('is-offcanvas-open');
    triggers.forEach((t) => t.setAttribute('aria-expanded', 'true'));
    if (main) main.setAttribute('inert', '');
    focusable()[0]?.focus();
    document.addEventListener('keydown', onKeydown);
  };

  const close = () => {
    panel.classList.remove('is-open');
    document.body.classList.remove('is-offcanvas-open');
    triggers.forEach((t) => t.setAttribute('aria-expanded', 'false'));
    if (main) main.removeAttribute('inert');
    document.removeEventListener('keydown', onKeydown);
    lastTrigger?.focus();
  };

  function onKeydown(e) {
    if (e.key === 'Escape') {
      close();
      return;
    }
    if (e.key !== 'Tab') return;

    const items = focusable();
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  triggers.forEach((t) => t.addEventListener('click', () => open(t)));
  closers.forEach((c) => c.addEventListener('click', close));

  // Closing from a nav link should also dismiss the panel.
  surface.querySelectorAll('a[href^="#"]').forEach((a) => a.addEventListener('click', close));
}
