import { initGsap } from './modules/motion.js';
import { initHeader } from './modules/header.js';
import { initOffcanvas } from './modules/offcanvas.js';
import { initBottomBar } from './modules/bottom-bar.js';
import { initFooterAccordion } from './modules/footer-accordion.js';
import { initBenefitsFigure } from './modules/benefits-figure.js';
import { initReveal, initHeroIntro } from './modules/reveal.js';
import { initSliders } from './modules/sliders.js';
import { initModals } from './modules/modals.js';
import { initCounters, initStepLine } from './modules/counters.js';
import { initAccordion } from './modules/accordion.js';
import { initChips } from './modules/chips.js';
import { initTypewriter } from './modules/typewriter.js';
import { initHowSteps } from './modules/how-steps.js';
import { initTierStack } from './modules/tier-stack.js';

function boot() {
  initGsap();

  // Chrome
  initHeader();
  initOffcanvas();
  initBottomBar();
  initFooterAccordion();
  // Before the reveals, which measure final layout.
  initBenefitsFigure();

  // Content
  initSliders();
  initModals();
  initChips();
  initAccordion();

  // Motion — last, so it measures final layout
  initHeroIntro();
  initTypewriter();
  initStepLine();
  initHowSteps();
  initTierStack();
  initCounters();
  initReveal();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
