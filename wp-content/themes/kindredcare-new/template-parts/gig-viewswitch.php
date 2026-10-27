<?php
/**
 * How the listings below are drawn.
 *
 * Shared by the gigs archive and the category archives. Identical on both, and
 * a part rather than a copy in each template for exactly that reason: the two
 * files already differ in their heading, their tally and their navigation, and
 * a third thing that is supposed to be the same in both is the thing that
 * quietly stops being the same.
 *
 * The categories to the left change which listings are shown. This changes
 * nothing about the set, only how it is drawn — which is why the two sit on
 * opposite sides of the page and look nothing alike. One is navigation between
 * real archives; this is a preference about the archive you are already on.
 *
 * Two radios and no JavaScript. The switch is a choice between two mutually
 * exclusive states, which is what a radio group is, and built that way the
 * browser supplies the whole of it: arrow keys move between the options, the
 * checked state is real state, and it works before — and without — the vendor
 * bundle in the footer.
 *
 * The list is the default because the rates only line up into a column to be
 * read against each other in list form, which is the argument the rate card is
 * built on. The grid is for browsing by name.
 *
 * Nothing here is remembered between page loads. Doing that means either a
 * cookie read on the server or storage read in script, and a two-state view
 * preference is not worth either. The ids are fixed rather than generated
 * because only one of these appears on a page.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>

<div class="s-gigs__toolbar">
	<div class="c-viewswitch" role="radiogroup" aria-label="<?php esc_attr_e( 'Listing view', 'kindredcare' ); ?>">
		<input class="c-viewswitch__input" type="radio" name="kc-gig-view" id="kc-gig-view-list" checked />
		<label class="c-viewswitch__label" for="kc-gig-view-list">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
				<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
			</svg>
			<?php esc_html_e( 'List', 'kindredcare' ); ?>
		</label>

		<input class="c-viewswitch__input" type="radio" name="kc-gig-view" id="kc-gig-view-grid" />
		<label class="c-viewswitch__label" for="kc-gig-view-grid">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
				<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z" />
			</svg>
			<?php esc_html_e( 'Grid', 'kindredcare' ); ?>
		</label>
	</div>
</div>
