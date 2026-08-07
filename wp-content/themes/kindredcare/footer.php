<?php
/**
 * Site footer, bottom app bar, modal content, and the off-canvas menu.
 *
 * The footer itself is editable — it renders from inc/footer-functions.php,
 * driven by the Customizer's KindredCare -> Footer section and the two
 * footer menu locations.
 *
 * Everything below it is still the ported static markup: the mobile bottom app
 * bar, the Fancybox modal content, and the off-canvas menu. Those carry their
 * own copies of the phone number and the nav links, so editing the header or
 * footer will not move them yet.
 *
 * wp_footer() stands in for the four vendor <script> tags and js/main.js the
 * static page hardcoded; those are registered in inc/enqueue.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
		</main>


		<?php kindredcare_footer(); ?>

    <!-- ================================================================== -->
    <!-- 15 — Mobile bottom app bar (below 768px)                           -->
    <!-- Present at rest; hides on scroll down, returns on scroll up.       -->
    <!-- ================================================================== -->
    <nav class="l-bottom-bar" aria-label="Primary" data-bottom-bar>
      <ul class="l-bottom-bar__list">
        <li class="l-bottom-bar__item">
          <a class="l-bottom-bar__link" href="<?php echo esc_url( kindredcare_anchor_url( '#services' ) ); ?>" data-spy-target="services" aria-label="Services">
            <svg class="l-bottom-bar__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <rect x="3.5" y="3.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.9" />
              <rect x="13.5" y="3.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.9" />
              <rect x="3.5" y="13.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.9" />
              <rect x="13.5" y="13.5" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.9" />
            </svg>
            Services
          </a>
        </li>

        <li class="l-bottom-bar__item">
          <a class="l-bottom-bar__link" href="<?php echo esc_url( kindredcare_anchor_url( '#how-it-works' ) ); ?>" data-spy-target="how-it-works" aria-label="How it works">
            <svg class="l-bottom-bar__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <rect x="3" y="14" width="5" height="6" rx="1.4" stroke="currentColor" stroke-width="1.9" />
              <rect x="9.5" y="9" width="5" height="11" rx="1.4" stroke="currentColor" stroke-width="1.9" />
              <rect x="16" y="4" width="5" height="16" rx="1.4" stroke="currentColor" stroke-width="1.9" />
            </svg>
            Steps
          </a>
        </li>

        <li class="l-bottom-bar__item l-bottom-bar__fab-slot">
          <a class="l-bottom-bar__fab" href="<?php echo esc_url( kindredcare_anchor_url( '#services' ) ); ?>" aria-label="Find care">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2" />
              <path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
          </a>
          <span class="l-bottom-bar__fab-label" aria-hidden="true">Find care</span>
        </li>

        <li class="l-bottom-bar__item">
          <a class="l-bottom-bar__link" href="<?php echo esc_url( kindredcare_anchor_url( '#safety' ) ); ?>" data-spy-target="safety" aria-label="Trust and safety">
            <svg class="l-bottom-bar__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 3.5 5 6.2v5.3c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6.2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
            </svg>
            Safety
          </a>
        </li>

        <li class="l-bottom-bar__item">
          <!--
            Opens the same off-canvas drawer as the header button. It carried a
            `data-sheet-trigger="menu"` placeholder for a separate bottom sheet
            that was never built, so the control did nothing when tapped. Now
            that the drawer slides in from the right at every width, a second
            mobile-only menu surface has no reason to exist.
          -->
          <button class="l-bottom-bar__link" type="button" aria-label="Open menu" aria-expanded="false" data-offcanvas-open>
            <svg class="l-bottom-bar__icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            Menu
          </button>
        </li>
      </ul>
    </nav>

    <!-- ================================================================== -->
    <!-- Inline modal content (Fancybox). Hidden until opened.              -->
    <!-- Category modals are generated from tile data attributes instead —  -->
    <!-- twelve blocks of hidden markup would be unmaintainable.            -->
    <!-- ================================================================== -->
    <div style="display: none">
<?php
/*
 * The three "what's actually checked" panels. Markup and content come from
 * inc/tiers.php, the same place the cards that open them are rendered from —
 * the trigger's data-src and the panel's id are generated by one helper, so a
 * renamed tier moves both and cannot leave a dead button behind.
 */
kindredcare_render_tier_modals();
?>

    </div>

    <!-- ================================================================== -->
    <!-- Off-canvas panel — behind the circular menu button.                -->
    <!-- Slides from the right on desktop, rises as a sheet below md.       -->
    <!-- ================================================================== -->
    <div class="c-offcanvas" id="site-menu" data-offcanvas>
      <div class="c-offcanvas__backdrop" data-offcanvas-close></div>
      <div
        class="c-offcanvas__panel"
        role="dialog"
        aria-modal="true"
        aria-label="Site menu"
        data-offcanvas-panel
      >
        <span class="c-offcanvas__handle" aria-hidden="true"></span>

        <div class="c-offcanvas__head">
          <span class="c-offcanvas__title">Menu</span>
          <button class="c-offcanvas__close" type="button" aria-label="Close menu" data-offcanvas-close>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
          </button>
        </div>

        <nav class="c-offcanvas__nav" aria-label="Menu">
          <a class="c-offcanvas__link" href="<?php echo esc_url( kindredcare_anchor_url( '#how-it-works' ) ); ?>">
            <span>How it works</span>
            <svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
          <a class="c-offcanvas__link" href="<?php echo esc_url( kindredcare_anchor_url( '#services' ) ); ?>">
            <span>Services</span>
            <svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
          <a class="c-offcanvas__link" href="<?php echo esc_url( kindredcare_anchor_url( '#safety' ) ); ?>">
            <span>Trust &amp; safety</span>
            <svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
          <a class="c-offcanvas__link" href="<?php echo esc_url( kindredcare_anchor_url( '#for-caregivers' ) ); ?>">
            <span>Join as a caregiver</span>
            <svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
          <a class="c-offcanvas__link" href="<?php echo esc_url( kindredcare_blog_url() ); ?>">
            <span>Blog</span>
            <svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
          <a class="c-offcanvas__link" href="<?php echo esc_url( kindredcare_anchor_url( '#our-story' ) ); ?>">
            <span>About us</span>
            <svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
          <a class="c-offcanvas__link" href="<?php echo esc_url( kindredcare_page_url( 'contact-us', home_url( '/' ) ) ); ?>">
            <span>Contact us</span>
            <svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
          </a>
        </nav>

        <div class="c-offcanvas__contact">
          <span class="c-offcanvas__contact-label">Talk to a person</span>
          <a class="c-offcanvas__contact-link" href="tel:+14376658486">+1 (437) 665-8486</a>
          <a class="c-offcanvas__contact-link" href="mailto:info@kindredcare.ca">info@kindredcare.ca</a>
          <span class="c-offcanvas__contact-place">Oshawa, Ontario</span>
        </div>
      </div>
    </div>

		<?php wp_footer(); ?>
	</body>
</html>
