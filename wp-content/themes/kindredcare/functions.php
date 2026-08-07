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
require_once get_template_directory() . '/inc/contact.php';
require_once get_template_directory() . '/inc/hero.php';
require_once get_template_directory() . '/inc/service-groups.php';
require_once get_template_directory() . '/inc/how-it-works.php';
require_once get_template_directory() . '/inc/taxonomy.php';
require_once get_template_directory() . '/inc/benefits.php';
require_once get_template_directory() . '/inc/caregivers.php';
require_once get_template_directory() . '/inc/tiers.php';
require_once get_template_directory() . '/inc/safety.php';
require_once get_template_directory() . '/inc/for-caregivers.php';
require_once get_template_directory() . '/inc/faq.php';
/* Reads the FAQ option above — must load after it. */
require_once get_template_directory() . '/inc/schema.php';
require_once get_template_directory() . '/inc/footer-functions.php';
require_once get_template_directory() . '/inc/class-kindredcare-header-walker.php';
require_once get_template_directory() . '/inc/class-kindredcare-footer-walker.php';

// The editing screens and their Settings API registrations are admin-only.
if ( is_admin() ) {
	require_once get_template_directory() . '/inc/admin-ui.php';
	require_once get_template_directory() . '/inc/admin-hero.php';
	require_once get_template_directory() . '/inc/admin-services.php';
	require_once get_template_directory() . '/inc/admin-how.php';
	require_once get_template_directory() . '/inc/admin-taxonomy.php';
	require_once get_template_directory() . '/inc/admin-benefits.php';
	require_once get_template_directory() . '/inc/admin-caregivers.php';
	require_once get_template_directory() . '/inc/admin-tiers.php';
	require_once get_template_directory() . '/inc/admin-safety.php';
	require_once get_template_directory() . '/inc/admin-for-caregivers.php';
	require_once get_template_directory() . '/inc/admin-faq.php';
}
