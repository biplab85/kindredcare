<?php
/**
 * Front page — the ported marketing homepage.
 *
 * Carried over from kindredcare-home/src/index.html, then emptied out section by
 * section: each one now lives in its own inc/ module behind an option and an
 * admin screen, and this file is the running order that calls them.
 *
 * Nothing here is static markup any more — every block on the page is now behind
 * an option and a screen. What is left between the calls are the two notes about
 * sections 10 and 11, which record why a slot on the page is deliberately empty.
 *
 * Because of those extractions this file is no longer purely generated: a
 * re-run of the splitter would restore the static markup and undo the wiring.
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
 * The hero. Markup in inc/hero.php, content in the `kindredcare_hero` option,
 * edited at KindredCare -> Hero in the admin.
 */
kindredcare_render_hero();

/*
 * The second, premium hero — the professional case under the personal one.
 * Markup in inc/hero-pro.php, content in the `kindredcare_hero_pro` option,
 * edited at KindredCare -> Hero — premium. The first hero above is untouched.
 */
kindredcare_render_hero_pro();

/*
 * The trust strip. Markup in inc/trust-strip.php, content in the
 * `kindredcare_trust_strip` option, edited at KindredCare -> Trust strip.
 */
kindredcare_render_trust_strip();

/*
 * "How it works". Markup in inc/how-it-works.php, content in the
 * `kindredcare_how` option, edited at KindredCare -> How it works.
 */
kindredcare_render_how();
?>

<?php
/*
 * "Services we provide". Markup in inc/service-groups.php, content in the
 * `kindredcare_service_groups` option, edited at KindredCare -> Services.
 */
kindredcare_render_service_groups();
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
 * "Our story" — the founders. Markup in inc/our-story.php, content in the
 * `kindredcare_our_story` option, edited at KindredCare -> Our story. Placed
 * after "During the visit".
 */
kindredcare_render_our_story();
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

<?php
/*
 * The closing panel. Markup in inc/final-cta.php, content in the
 * `kindredcare_final_cta` option, edited at KindredCare -> Final CTA.
 */
kindredcare_render_final_cta();

get_footer();
