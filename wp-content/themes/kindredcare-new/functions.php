<?php
/**
 * KindredCare theme bootstrap.
 *
 * This file wires nothing itself — it only loads the modules under inc/. Each
 * module registers its own hooks, so where a behaviour lives is answerable from
 * the file list alone.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Theme version, used to bust asset caches.
 *
 * Read from style.css rather than hardcoded so a version bump happens in one
 * place. During development the enqueue module ignores this in favour of each
 * file's mtime — see inc/enqueue.php.
 */
define( 'KINDREDCARE_VERSION', wp_get_theme()->get( 'Version' ) ?: '0.1.0' );

require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/enqueue.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/social.php';
require_once get_template_directory() . '/inc/template-functions.php';
/* Loads after enqueue.php — it calls kindredcare_asset_version(). */
require_once get_template_directory() . '/inc/blog.php';
/* Builds the notification emails; both senders below use it. */
require_once get_template_directory() . '/inc/emails.php';
require_once get_template_directory() . '/inc/contact.php';
require_once get_template_directory() . '/inc/newsletter.php';
require_once get_template_directory() . '/inc/hero.php';
require_once get_template_directory() . '/inc/hero-pro.php';
require_once get_template_directory() . '/inc/trust-strip.php';
/* Calls kindredcare_tel_href() from template-functions.php, loaded above. */
require_once get_template_directory() . '/inc/our-story.php';
require_once get_template_directory() . '/inc/service-groups.php';
require_once get_template_directory() . '/inc/how-it-works.php';
require_once get_template_directory() . '/inc/taxonomy.php';
require_once get_template_directory() . '/inc/service-pages.php';
/* The one address every fetch below goes through — must load before them. */
require_once get_template_directory() . '/inc/api.php';
/* The Services page's catalogue, read from the platform API rather than an option. */
require_once get_template_directory() . '/inc/services-api.php';
/* Pulls the platform's pictures into the media library — used by the import. */
require_once get_template_directory() . '/inc/media.php';
/* The gigs post type, imported from the same platform. */
require_once get_template_directory() . '/inc/gigs.php';
require_once get_template_directory() . '/inc/benefits.php';
require_once get_template_directory() . '/inc/caregivers.php';
require_once get_template_directory() . '/inc/tiers.php';
require_once get_template_directory() . '/inc/safety.php';
require_once get_template_directory() . '/inc/for-caregivers.php';
require_once get_template_directory() . '/inc/faq.php';
require_once get_template_directory() . '/inc/final-cta.php';
/* Reads the FAQ option above — must load after it. */
require_once get_template_directory() . '/inc/schema.php';
require_once get_template_directory() . '/inc/footer-functions.php';
require_once get_template_directory() . '/inc/class-kindredcare-header-walker.php';
require_once get_template_directory() . '/inc/class-kindredcare-footer-walker.php';

// The editing screens and their Settings API registrations are admin-only.
if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin-ui.php';
	require_once get_template_directory() . '/inc/admin-hero.php';
	require_once get_template_directory() . '/inc/admin-hero-pro.php';
	require_once get_template_directory() . '/inc/admin-trust-strip.php';
	require_once get_template_directory() . '/inc/admin-our-story.php';
	require_once get_template_directory() . '/inc/admin-services.php';
	require_once get_template_directory() . '/inc/admin-how.php';
	require_once get_template_directory() . '/inc/admin-taxonomy.php';
	require_once get_template_directory() . '/inc/admin-benefits.php';
	require_once get_template_directory() . '/inc/admin-caregivers.php';
	require_once get_template_directory() . '/inc/admin-tiers.php';
	require_once get_template_directory() . '/inc/admin-safety.php';
	require_once get_template_directory() . '/inc/admin-for-caregivers.php';
	require_once get_template_directory() . '/inc/admin-faq.php';
	require_once get_template_directory() . '/inc/admin-final-cta.php';
	require_once get_template_directory() . '/inc/admin-contact.php';
	/* The Services page's fetch button and image pickers. */
	require_once get_template_directory() . '/inc/admin-services-catalogue.php';
	/* The Gigs screen, and the boxes on each gig's own editing screen. */
	require_once get_template_directory() . '/inc/admin-gigs.php';
	require_once get_template_directory() . '/inc/admin-gig-edit.php';
	/* Reads every section's option constant and sanitiser — must load last. */
	require_once get_template_directory() . '/inc/admin-transfer.php';
}
