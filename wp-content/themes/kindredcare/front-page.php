<?php
/**
 * Front page — the ported marketing homepage.
 *
 * Carried over from kindredcare-home/src/index.html. Image paths were rewritten
 * to resolve from the theme directory, and the hero was lifted out into
 * inc/hero.php — everything below the hero is still the static markup and is
 * not yet editable.
 *
 * Because of that extraction this file is no longer purely generated: a
 * re-run of the splitter would restore the static hero and undo the wiring.
 *
 * The opening <main id="main"> lives in header.php and the closing tag in
 * footer.php, which is why neither appears below.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * The hero is the one section on this page that is editable. Its markup lives
 * in inc/hero.php and its content in the `kindredcare_hero` option, edited at
 * KindredCare -> Hero in the admin.
 */
kindredcare_render_hero();

?>

      <!-- Trust strip — the job of the cut section 02. Specificity, never
           numbers: it exists because the live site's counters all read zero. -->
      <div class="s-trust-strip">
        <div class="l-container">
          <ul class="s-trust-strip__list">
            <li class="s-trust-strip__item">
              <svg class="s-trust-strip__icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                <path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              Certn background checks
            </li>
            <li class="s-trust-strip__item">
              <svg class="s-trust-strip__icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                <path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              Identity verified
            </li>
            <li class="s-trust-strip__item">
              <svg class="s-trust-strip__icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                <path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              GPS-verified visits
            </li>
            <li class="s-trust-strip__item">
              <svg class="s-trust-strip__icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                <path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              Payment held by the platform
            </li>
          </ul>
        </div>
      </div>

      <!-- ================================================================ -->
      <!-- 01b — Founded / how to reach us                                   -->
      <!-- Sekare's .about-founded-area, matched: portrait image left with a -->
      <!-- rotated label and a heart badge straddling its corner, copy right -->
      <!-- ending in a phone + email row.                                    -->
      <!--                                                                   -->
      <!-- The reference heading reads "Founded in 2000 … a trusted name for -->
      <!-- 24 years". KindredCare has neither a 2000 founding nor 24 years,  -->
      <!-- and inventing them is the exact failure the plan calls out. Same  -->
      <!-- sentence shape, true claims: who built it and where it starts.    -->
      <!-- ================================================================ -->
      <section class="l-section s-founded" id="our-story" aria-labelledby="founded-title">
        <div class="l-container">
          <div class="s-founded__layout">
            <div class="s-founded__media" data-reveal="left">
              <div class="s-founded__frame">
                <img
                  class="s-founded__img"
                  src="<?php echo esc_url( get_theme_file_uri( 'assets/img/about/third-about.jpg' ) ); ?>"
                  width="941"
                  height="1101"
                  alt="A caregiver sitting and talking with an older woman at home"
                  loading="lazy"
                />
                <p class="s-founded__vertical">Chosen by you, every visit</p>
                <div class="s-founded__badge">
                  <img
                    src="<?php echo esc_url( get_theme_file_uri( 'assets/img/about/founded-heart.svg' ) ); ?>"
                    width="102"
                    height="104"
                    alt=""
                    loading="lazy"
                  />
                </div>
              </div>
            </div>

            <div class="s-founded__content" data-reveal="up">
              <h2 class="s-founded__title" id="founded-title">
                Starting in Durham Region,
                <span class="s-founded__brand">KindredCare</span>
                is built by four founders who have spent their careers in care,
                medicine and finance.
              </h2>

              <p class="s-founded__text">
                We each went looking for care for someone we love, and found the
                same thing every time — agencies that cost a fortune and send a
                different stranger each week. So we built the alternative we
                wanted: verified local caregivers, booked by the hour, and the
                person who shows up is the person you chose.
              </p>

              <div class="s-founded__contact">
                <div class="s-founded__contact-item">
                  <span class="s-founded__contact-label">Phone</span>
                  <a class="s-founded__contact-link" href="tel:+14376658486">
                    +1 (437) 665-8486
                  </a>
                </div>

                <div class="s-founded__contact-item">
                  <span class="s-founded__contact-label">Email</span>
                  <a class="s-founded__contact-link" href="mailto:info@kindredcare.ca">
                    info@kindredcare.ca
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

<?php
/*
 * "Services we provide". Markup in inc/service-groups.php, content in the
 * `kindredcare_service_groups` option, edited at KindredCare -> Services.
 */
kindredcare_render_service_groups();
?>

<?php
/*
 * "How it works". Markup in inc/how-it-works.php, content in the
 * `kindredcare_how` option, edited at KindredCare -> How it works.
 */
kindredcare_render_how();
?>

<?php
/*
 * "What you can book" — the service taxonomy mosaic. Markup in
 * inc/taxonomy.php, content in the `kindredcare_taxonomy` option.
 */
kindredcare_render_taxonomy();
?>

<?php
/*
 * "Benefits" — the case for booking a caregiver directly rather than through an
 * agency. Markup in inc/benefits.php, content in the `kindredcare_benefits`
 * option, edited at KindredCare -> Benefits.
 */
kindredcare_render_benefits();
?>

<?php
/*
 * "Founding caregivers" — supply acquisition, not liquidity proof. Markup in
 * inc/caregivers.php, content in the `kindredcare_caregivers` option, edited at
 * KindredCare -> Founding caregivers.
 */
kindredcare_render_caregivers();
?>

<?php
/*
 * "Verification tiers". Markup in inc/tiers.php, content in the
 * `kindredcare_tiers` option, edited at KindredCare -> Verification tiers.
 * The hidden "what's actually checked" panels are rendered from the same
 * option in footer.php.
 */
kindredcare_render_tiers();
?>

<?php
/*
 * "During the visit". Markup in inc/safety.php, content in the
 * `kindredcare_safety` option, edited at KindredCare -> During the visit.
 */
kindredcare_render_safety();
?>

<?php
/*
 * "For caregivers" — the one dark section. Markup in inc/for-caregivers.php,
 * content in the `kindredcare_for_caregivers` option, edited at KindredCare ->
 * For caregivers.
 */
kindredcare_render_for_caregivers();
?>

      <!-- ================================================================ -->
      <!-- 10 — Founders + why we're building this                          -->
      <!-- Absorbs the story that the cut section 11 placeholder carried.    -->

      <!--
        11 — Testimonials. BUILT BUT NOT RENDERED.
        Zero real reviews exist and fabricating them is forbidden (plan §3).
        Its draft placeholder duplicated section 10, so an empty slot is more
        honest than a padded one. Renders here, between 10 and 12, when real
        reviews arrive — no rebuild needed.
      -->

<?php
/*
 * "Questions". Markup in inc/faq.php, content in the `kindredcare_faq` option,
 * edited at KindredCare -> FAQ. The FAQPage JSON-LD in inc/schema.php is built
 * from that same option, so the structured data cannot drift from the copy.
 */
kindredcare_render_faq();
?>

      <!-- ================================================================ -->
      <!-- 13 — Final CTA                                                   -->
      <!-- ================================================================ -->
      <section class="l-section s-final" id="final-cta" aria-labelledby="final-title">
        <div class="l-container">
          <div class="s-final__panel" data-reveal="up">
            <span class="u-eyebrow s-final__eyebrow">Ready when you are</span>
            <h2 class="s-final__title" id="final-title">
              Find someone kind, this week.
            </h2>
            <p class="s-final__lead">
              Browsing is free and takes a few minutes. You will see real people,
              real rates and real verification before you decide anything.
            </p>

            <div class="s-final__actions">
              <a class="c-btn c-btn--primary c-btn--lg" href="#services">Find a caregiver</a>
              <a class="c-btn c-btn--ink c-btn--lg" href="#for-caregivers">Join as a caregiver</a>
            </div>
          </div>
        </div>
      </section>


<?php
get_footer();
