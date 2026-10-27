<?php
/**
 * The gigs archive — a rate card.
 *
 * Every other index on this site answers "what kinds of thing exist". This one
 * answers a different question: who is actually available, and what do they
 * charge. That is a comparison, and comparisons are made down a column, so the
 * listings are a single stack with the rates aligned on one edge rather than a
 * grid that scatters them.
 *
 * The masthead follows the Services page's — same kicker, title and tally
 * classes — because a visitor arriving from one should not feel they have
 * changed sites. What differs is the tally itself: services counts kinds of
 * help, this counts listings and states the floor price, which is the fact
 * people arrive holding.
 *
 * Nothing here talks to the platform. Gigs are posts, imported by an editor
 * pressing a button — see inc/gigs.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$kc_total   = (int) wp_count_posts( KINDREDCARE_GIG_POST_TYPE )->publish;
$kc_lowest  = kindredcare_gigs_rate_edge( 'low' );
$kc_highest = kindredcare_gigs_rate_edge( 'high' );
$kc_terms   = kindredcare_gig_categories();
$kc_archive = (string) get_post_type_archive_link( KINDREDCARE_GIG_POST_TYPE );
?>

<article class="s-article s-gigs">

	<header class="s-article__head s-article__head--no-media">
		<div class="s-article__head-content">
			<p class="s-article__kicker">
				<span class="s-article__kicker-rule" aria-hidden="true"></span>
				<?php esc_html_e( 'Open listings', 'kindredcare' ); ?>
			</p>

			<h1 class="s-article__title s-article__title--page">
				<?php esc_html_e( 'Who’s available, and what they charge.', 'kindredcare' ); ?>
			</h1>

			<p class="s-article__deck">
				<?php esc_html_e( 'Each listing below is one caregiver’s own offer — their words, their hours, their rate. Ask about any of them and we’ll make the introduction.', 'kindredcare' ); ?>
			</p>

			<?php if ( $kc_total ) : ?>
				<p class="s-services__tally">
					<span class="s-services__tally-figure"><?php echo esc_html( number_format_i18n( $kc_total ) ); ?></span>
					<span>
						<?php echo esc_html( _n( 'listing', 'listings', $kc_total, 'kindredcare' ) ); ?>
						<?php if ( $kc_lowest ) : ?>
							<span aria-hidden="true">·</span>
							<?php
							/*
							 * The range, not just the floor. "From $20" answers
							 * half the question and leaves the other half —
							 * how high does this go — to be discovered by
							 * scrolling, which is the half people worry about.
							 */
							if ( $kc_highest > $kc_lowest ) {
								printf(
									/* translators: 1: lowest hourly rate, 2: highest, both formatted, e.g. $20. */
									esc_html__( '%1$s to %2$s an hour', 'kindredcare' ),
									esc_html( kindredcare_gig_rate( $kc_lowest ) ),
									esc_html( kindredcare_gig_rate( $kc_highest ) )
								);
							} else {
								printf(
									/* translators: %s: the hourly rate, already formatted, e.g. $20. */
									esc_html__( '%s an hour', 'kindredcare' ),
									esc_html( kindredcare_gig_rate( $kc_lowest ) )
								);
							}
							?>
						<?php endif; ?>
					</span>
				</p>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( $kc_terms ) : ?>
		<div class="l-container">
			<?php
			/*
			 * Real links to real archives, each carrying its count. Not a filter
			 * widget: nothing here is hidden and re-shown, so a count that says
			 * four means four pages down that road, and the browser back button
			 * does what it always does.
			 */
			?>
			<nav class="s-gigs__filter" aria-label="<?php esc_attr_e( 'Listings by category', 'kindredcare' ); ?>">
				<a class="c-chip is-current" href="<?php echo esc_url( $kc_archive ); ?>" aria-current="page">
					<?php kindredcare_gig_category_icon(); ?>
					<?php esc_html_e( 'Everything', 'kindredcare' ); ?>
					<span class="c-chip__count"><?php echo esc_html( number_format_i18n( $kc_total ) ); ?></span>
				</a>
				<?php foreach ( $kc_terms as $kc_cat ) : ?>
					<a class="c-chip" href="<?php echo esc_url( (string) get_term_link( $kc_cat ) ); ?>">
						<?php kindredcare_gig_category_icon( (string) $kc_cat->slug ); ?>
						<?php echo esc_html( $kc_cat->name ); ?>
						<span class="c-chip__count"><?php echo esc_html( number_format_i18n( (int) $kc_cat->count ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
	<?php endif; ?>

	<div class="l-container">
		<?php if ( have_posts() ) : ?>

			<?php get_template_part( 'template-parts/gig-viewswitch' ); ?>

			<ul class="s-gigs__list" data-reveal-group>
				<?php
				while ( have_posts() ) :
					the_post();

					get_template_part(
						'template-parts/gig-row',
						null,
						array(
							'id'            => (int) get_the_ID(),
							'show_category' => true,
						)
					);
				endwhile;
				?>
			</ul>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => esc_html__( 'Previous', 'kindredcare' ),
					'next_text' => esc_html__( 'Next', 'kindredcare' ),
				)
			);
			?>

			<div class="s-services__cta">
				<p class="s-services__cta-text">
					<?php esc_html_e( 'Not sure who to ask? Tell us about the situation and we’ll point you at the right person.', 'kindredcare' ); ?>
				</p>
				<a class="c-btn c-btn--primary c-btn--lg" href="<?php echo esc_url( kindredcare_gig_contact_url() ); ?>">
					<?php esc_html_e( 'Contact Us', 'kindredcare' ); ?>
				</a>
			</div>

		<?php else : ?>

			<?php
			/*
			 * Reached before anything has been imported. A visitor gets a way
			 * forward; an editor is told why the page is empty and where the
			 * button is, because being sent to the right screen beats guessing.
			 */
			?>
			<div class="s-services__empty">
				<p class="s-services__empty-text">
					<?php esc_html_e( 'No listings are showing right now. Tell us what you need and we’ll find someone for you.', 'kindredcare' ); ?>
				</p>

				<?php
				/*
				 * The capability spelled out rather than read from
				 * kindredcare_admin_capability(), which lives in
				 * inc/admin-ui.php and is only loaded for admin requests.
				 */
				?>
				<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
					<p class="s-services__empty-admin">
						<?php esc_html_e( 'Only you can see this: no gigs have been imported yet.', 'kindredcare' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=kindredcare-gigs' ) ); ?>">
							<?php esc_html_e( 'Import them from the platform.', 'kindredcare' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<a class="c-btn c-btn--primary c-btn--lg" href="<?php echo esc_url( kindredcare_gig_contact_url() ); ?>">
					<?php esc_html_e( 'Contact Us', 'kindredcare' ); ?>
				</a>
			</div>

		<?php endif; ?>
	</div>
</article>

<?php
get_footer();
