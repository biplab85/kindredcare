<?php
/**
 * Blog index — the page assigned under Settings -> Reading.
 *
 * front-page.php outranks this file for the front page itself, so the ported
 * marketing homepage is untouched; this renders at /blog/ only.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$kc_page = (int) get_option( 'page_for_posts' );
$kc_lead = $kc_page ? get_post_field( 'post_excerpt', $kc_page ) : '';

/*
 * The hero CTA points at the newest article. "Browse the journal" would be a
 * button that leads to the page it is already on; the one thing a reader
 * arriving here cannot yet do in a click is start reading.
 */
$kc_newest = get_posts(
	array(
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

kindredcare_blog_masthead(
	array(
		'title'     => $kc_page ? get_the_title( $kc_page ) : __( 'Journal', 'kindredcare' ),
		'lead'      => $kc_lead
			? $kc_lead
			: __( 'Practical writing on senior care in Canada — costs, care options, legal footing, and how to find someone you can trust with the people you love.', 'kindredcare' ),
		'cta_label' => $kc_newest ? __( 'Read the latest', 'kindredcare' ) : '',
		'cta_url'   => $kc_newest ? (string) get_permalink( $kc_newest[0] ) : '',
	)
);

get_template_part( 'template-parts/post-list' );

get_footer();
