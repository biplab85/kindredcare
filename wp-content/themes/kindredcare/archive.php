<?php
/**
 * Category, tag and date archives.
 *
 * Same grid as the index — only the masthead copy changes, so the two never
 * drift apart visually.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$kc_description = wp_strip_all_tags( (string) get_the_archive_description() );

kindredcare_blog_masthead(
	array(
		'eyebrow' => __( 'Journal', 'kindredcare' ),
		'title'   => wp_strip_all_tags( (string) get_the_archive_title() ),
		'lead'    => $kc_description,
	)
);

get_template_part( 'template-parts/post-list' );

get_footer();
