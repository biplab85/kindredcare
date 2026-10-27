<?php
/**
 * One listing, as a row on a rate card.
 *
 * Shared by the gigs archive, the category archives, and the "more from this
 * caregiver" strip at the foot of a single gig.
 *
 * The rate leads. That is the one decision this row is built around, and it is
 * not a stylistic preference — it is the site's own argument. The home page
 * sells KindredCare on taking 20% where an agency takes 30–40%, and a marketplace
 * that argues about price and then buries it behind "enquire for rates" is
 * making its case in one place and abandoning it in another.
 *
 * It also happens to be what the pictures cannot do. A caregiver's photograph
 * is a portrait, and the platform reuses it across every listing that person
 * posts — so a photograph-led grid would show the same face four times running
 * and distinguish nothing. The words and the number are what differ.
 *
 * Laid out as a single column rather than a grid so the rates line up down one
 * edge and can be read against each other. A grid scatters them, which is
 * precisely the comparison this page exists to make possible.
 *
 * @package KindredCare
 *
 * @var array{id: int, compact?: bool, show_category?: bool} $args
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$kc_id = absint( $args['id'] ?? 0 );

if ( ! $kc_id ) {
	return;
}

$kc_compact  = ! empty( $args['compact'] );
$kc_show_cat = ! empty( $args['show_category'] );

$kc_caregiver = kindredcare_gig_caregiver( $kc_id );
$kc_rate      = kindredcare_gig_rate( absint( get_post_meta( $kc_id, KINDREDCARE_GIG_META_RATE, true ) ) );
$kc_tasks     = kindredcare_gig_tasks( $kc_id );
$kc_face      = kindredcare_gig_portrait_id( $kc_id );
$kc_name      = (string) $kc_caregiver['display_name'];
$kc_years     = absint( $kc_caregiver['years_of_experience'] );
/*
 * Read whatever the show_category flag says, because the flag governs the
 * kicker and the kicker is no longer the only thing the category is used for —
 * the rate column carries its icon on every row, including the category
 * archive where naming it in words down the page would be the same sentence
 * fifteen times. get_the_terms() is cached per post, so asking on every row
 * costs nothing.
 */
$kc_terms     = get_the_terms( $kc_id, KINDREDCARE_GIG_TAXONOMY );
$kc_term      = is_array( $kc_terms ) && $kc_terms ? $kc_terms[0] : null;
?>

<li class="c-gig<?php echo $kc_compact ? ' c-gig--compact' : ''; ?>" data-reveal="up">

	<?php
	/*
	 * A paragraph, not a heading. It is a fact about the listing beside it, and
	 * marking it up as a heading would put a price into the document outline
	 * ahead of the thing being priced.
	 */
	?>
	<p class="c-gig__rate">
		<?php if ( '' !== $kc_rate ) : ?>
			<?php
			/*
			 * "/hr" to read, "an hour" to hear.
			 *
			 * The abbreviation is for the eye — it keeps the rate to one short
			 * run so the column of prices stays scannable. It is not for the
			 * ear: a screen reader says "slash h r", and the people this site
			 * is built for are disproportionately the ones using one. The
			 * visible mark is hidden from the accessibility tree and the words
			 * it stands for are supplied beside it, so the page looks like
			 * "$25/hr" and sounds like "$25 an hour".
			 *
			 * The amount and the unit are on one line here with nothing between
			 * them, and that is load-bearing rather than untidy: above 700px the
			 * rate is a block and these two are inline, so a newline between the
			 * tags collapses to a real space and prints "$25 /hr".
			 */
			?>
			<span class="c-gig__amount"><?php echo esc_html( $kc_rate ); ?></span><span class="c-gig__unit" aria-hidden="true"><?php esc_html_e( '/hr', 'kindredcare' ); ?></span>
			<span class="u-sr-only"><?php esc_html_e( 'an hour', 'kindredcare' ); ?></span>
		<?php else : ?>
			<span class="c-gig__unit"><?php esc_html_e( 'Rate on request', 'kindredcare' ); ?></span>
		<?php endif; ?>

		<?php
		/*
		 * The kind of help, under the price it is being offered at.
		 *
		 * Printed in both branches, including "Rate on request" — a listing
		 * with no number still has a category, and the column would otherwise
		 * be the one row in the ladder missing its mark.
		 *
		 * Decorative: the kicker above the title already names the category on
		 * the full archive, and on a category archive the page's own heading
		 * does. Nothing here is the only place a fact appears.
		 */
		?>
		<?php if ( $kc_term instanceof WP_Term ) : ?>
			<?php kindredcare_gig_category_icon( (string) $kc_term->slug, 'c-gig__icon' ); ?>
		<?php endif; ?>
	</p>

	<div class="c-gig__main">

		<?php
		/*
		 * The person first, then what they are offering.
		 *
		 * Reordered in the markup rather than with CSS order, which would put
		 * the only link in the row in a different place for the eye than for
		 * the tab key. The heading no longer opens the block, which is allowed
		 * — a heading labels the section it is in, it does not have to be the
		 * first thing in it — and the row still exposes exactly one link with
		 * one accessible name.
		 */
		?>
		<p class="c-gig__by">
			<?php if ( $kc_face ) : ?>
				<?php
				echo wp_get_attachment_image(
					$kc_face,
					'thumbnail',
					false,
					array(
						'class'   => 'c-gig__face',
						'alt'     => '',
						'loading' => 'lazy',
					)
				);
				?>
			<?php elseif ( '' !== $kc_name ) : ?>
				<?php
				/*
				 * An initial rather than a grey silhouette. A listing whose
				 * portrait has not been downloaded yet should look like a
				 * person without a photograph, not like a broken image.
				 */
				?>
				<span class="c-gig__face c-gig__face--letter" aria-hidden="true"><?php echo esc_html( mb_substr( $kc_name, 0, 1 ) ); ?></span>
			<?php endif; ?>

			<?php if ( '' !== $kc_name ) : ?>
				<span class="c-gig__name"><?php echo esc_html( $kc_name ); ?></span>
			<?php endif; ?>

			<?php if ( $kc_years ) : ?>
				<span class="c-gig__sep" aria-hidden="true">·</span>
				<span>
					<?php
					printf(
						/* translators: %d: years of experience. */
						esc_html( _n( '%d year', '%d years', $kc_years, 'kindredcare' ) ),
						(int) $kc_years
					);
					?>
				</span>
			<?php endif; ?>

			<?php
			/*
			 * The category, in the byline rather than above the title.
			 *
			 * A <span>, where this was a <p> when it stood on its own — .c-gig__by
			 * is itself a paragraph, and a <p> inside a <p> is not something the
			 * parser tolerates: it closes the outer one at the opening tag and the
			 * rest of the byline falls out of the flex row. The tag has to change
			 * for the element to move.
			 *
			 * Joined with the same · the rest of the line uses. Everything here is
			 * a fact about who is offering this and what kind of thing it is, and
			 * the line already had a way of saying "and also".
			 *
			 * Still only printed where show_category asks for it — a category
			 * archive would otherwise carry its own name down every row.
			 */
			?>
			<?php if ( $kc_show_cat && $kc_term instanceof WP_Term ) : ?>
				<span class="c-gig__sep" aria-hidden="true">·</span>
				<span class="c-gig__kicker"><?php echo esc_html( $kc_term->name ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $kc_caregiver['is_verified'] ) ) : ?>
				<span class="c-gig__sep" aria-hidden="true">·</span>
				<span class="c-gig__verified">
					<span class="c-gig__dot" aria-hidden="true"></span>
					<?php esc_html_e( 'Verified', 'kindredcare' ); ?>
				</span>
			<?php endif; ?>
		</p>

		<h2 class="c-gig__title">
			<?php
			/*
			 * The link covers the whole row through its ::after. The title is
			 * still the only real link, so it is what a screen reader announces
			 * and what the keyboard lands on — the overlay is an affordance for
			 * the mouse, not a second control.
			 */
			?>
			<a class="c-gig__link" href="<?php echo esc_url( (string) get_permalink( $kc_id ) ); ?>">
				<?php echo esc_html( get_the_title( $kc_id ) ); ?>
			</a>
		</h2>

		<?php if ( ! $kc_compact && $kc_tasks ) : ?>
			<?php
			/*
			 * Run together on one line rather than set as a list. The full list
			 * is the substance of the gig's own page; here it is a sample, and
			 * four bulleted lines per row would turn a rate card back into a
			 * stack of cards and bury the number this row is built around.
			 */
			?>
			<p class="c-gig__tasks"><?php echo esc_html( implode( ' · ', array_slice( $kc_tasks, 0, 4 ) ) ); ?></p>
		<?php endif; ?>
	</div>

	<span class="c-gig__go" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
			<path d="M7 17 17 7M9 7h8v8" />
		</svg>
	</span>
</li>
