<?php
/**
 * The way back to the archive, from a single listing.
 *
 * A part rather than markup in the template because it now prints in two
 * places: beside the "More from …" heading when the caregiver has other
 * listings, and on its own when they do not. It is the only route back out of
 * this page that is not the site header, so it cannot be allowed to live
 * inside the siblings conditional and quietly disappear on a caregiver with a
 * single listing.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>

<div class="s-article__foot">
	<a class="s-article__back" href="<?php echo esc_url( (string) get_post_type_archive_link( KINDREDCARE_GIG_POST_TYPE ) ); ?>">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
			<path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
		</svg>
		<?php esc_html_e( 'All listings', 'kindredcare' ); ?>
	</a>
</div>
