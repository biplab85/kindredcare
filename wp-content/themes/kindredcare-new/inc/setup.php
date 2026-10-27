<?php
/**
 * Theme supports and menu locations.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'kindredcare_setup' );
/**
 * Register theme supports and navigation locations.
 */
function kindredcare_setup(): void {
	load_theme_textdomain( 'kindredcare', get_template_directory() . '/languages' );

	// WordPress owns <title>; header.php no longer prints one.
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );

	/*
	 * Two crops, both hard, because the blog grid only reads as a grid if every
	 * card is the same shape — the source images are a mix of 1.91:1 and 16:9.
	 *
	 * 'card' is 1.6:1 at 800px, which covers the widest a card ever gets (about
	 * 400px in the three-column grid) on a 2x screen. 'wide' is 1200x630, the
	 * native size of nearly every uploaded image, so the single-post hero is a
	 * pass-through rather than an upscale.
	 */
	add_image_size( 'kindredcare-card', 800, 500, true );
	add_image_size( 'kindredcare-wide', 1200, 630, true );

	/*
	 * The ported markup is hand-written HTML5, not the div soup the default
	 * gallery/caption output assumes.
	 */
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	/*
	 * Logo dimensions match the static header's <img width="162" height="36">.
	 * flex-* is true because the mark is only ever constrained by height in CSS
	 * (.l-header__logo is height:28px/34px, width:auto) — cropping an upload to
	 * exactly 162x36 would distort any mark with a different aspect ratio.
	 */
	add_theme_support(
		'custom-logo',
		array(
			'width'                => 162,
			'height'               => 36,
			'flex-width'           => true,
			'flex-height'          => true,
			'unlink-homepage-logo' => false,
		)
	);

	/*
	 * The footer's two link columns are menus rather than Customizer text so
	 * they can point at real posts and pages and pick up permalink changes. The
	 * first and fourth footer columns are not menus — they are a mark plus a
	 * service area, and a contact block, both of which live in the Customizer.
	 */
	register_nav_menus(
		array(
			'primary'      => __( 'Header — desktop bar', 'kindredcare' ),
			'footer-quick' => __( 'Footer — quick links', 'kindredcare' ),
			'footer-care'  => __( 'Footer — legal', 'kindredcare' ),
		)
	);
}

add_action( 'after_setup_theme', 'kindredcare_content_width', 0 );
/**
 * Set the editor/oEmbed content width.
 *
 * Matches $container-max (scss/abstracts/_variables.scss:58), so an embed
 * dropped into a post cannot outgrow the grid the rest of the page sits on.
 */
function kindredcare_content_width(): void {
	$GLOBALS['content_width'] = 1440;
}
