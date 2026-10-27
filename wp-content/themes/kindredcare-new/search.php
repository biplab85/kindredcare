<?php
/**
 * Search results.
 *
 * The header search box has pointed at this since the port; before now it
 * landed on index.php's plain fallback.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$kc_found = (int) $GLOBALS['wp_query']->found_posts;

kindredcare_blog_masthead(
	array(
		'eyebrow' => __( 'Search', 'kindredcare' ),
		/* translators: %s: search query. */
		'title'   => sprintf( __( '“%s”', 'kindredcare' ), get_search_query() ),
		/*
		 * The masthead no longer carries a separate count. It is folded into
		 * the standfirst here because on a results page the number is the
		 * answer, not decoration.
		 */
		'lead'    => $kc_found
			? sprintf(
				/* translators: %s: number of matching articles. */
				esc_html( _n( '%s article matches your search, newest first.', '%s articles match your search, newest first.', $kc_found, 'kindredcare' ) ),
				number_format_i18n( $kc_found )
			)
			: __( 'No article matched that search. Try a broader term, or browse the full journal.', 'kindredcare' ),
	)
);

get_template_part( 'template-parts/post-list' );

get_footer();
