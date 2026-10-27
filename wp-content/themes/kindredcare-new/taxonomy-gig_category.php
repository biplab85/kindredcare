<?php
/**
 * One gig category.
 *
 * The same rate card as the full archive, scoped to a category and with a
 * masthead that says which. Written as its own file rather than folded into
 * archive-gigs.php behind an is_tax() check: the two pages differ in their
 * heading, their tally and their navigation, and a template carrying two sets
 * of those separated by conditionals is harder to read than two templates.
 *
 * The category kicker is deliberately not printed on the rows here. On the full
 * archive it tells you what kind of help a listing is; on this page every row
 * would carry the same word, which is the same sentence repeated down the page.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$kc_term    = get_queried_object();
$kc_total   = $kc_term instanceof WP_Term ? (int) $kc_term->count : 0;
$kc_terms   = kindredcare_gig_categories();
$kc_archive = (string) get_post_type_archive_link( KINDREDCARE_GIG_POST_TYPE );
$kc_blurb   = $kc_term instanceof WP_Term ? trim( (string) $kc_term->description ) : '';

/*
 * The whole catalogue, for the "Everything" chip's count.
 *
 * Every other chip in the filter carries the number of listings behind it, and
 * on the full archive that one does too. Left bare here it was the single row
 * in the sidebar with nothing in the count column — which reads as a number
 * that failed to load rather than as a chip that never had one.
 */
$kc_all = (int) wp_count_posts( KINDREDCARE_GIG_POST_TYPE )->publish;
?>

<article class="s-article s-gigs">

	<header class="s-article__head s-article__head--no-media">
		<div class="s-article__head-content">
			<p class="s-article__kicker">
				<span class="s-article__kicker-rule" aria-hidden="true"></span>
				<a class="s-article__kicker-link" href="<?php echo esc_url( $kc_archive ); ?>">
					<?php esc_html_e( 'All listings', 'kindredcare' ); ?>
				</a>
			</p>

			<h1 class="s-article__title s-article__title--page">
				<?php echo esc_html( $kc_term instanceof WP_Term ? $kc_term->name : __( 'Listings', 'kindredcare' ) ); ?>
			</h1>

			<?php if ( '' !== $kc_blurb ) : ?>
				<p class="s-article__deck"><?php echo esc_html( $kc_blurb ); ?></p>
			<?php endif; ?>

			<?php if ( $kc_total ) : ?>
				<p class="s-services__tally">
					<span class="s-services__tally-figure"><?php echo esc_html( number_format_i18n( $kc_total ) ); ?></span>
					<span><?php echo esc_html( _n( 'caregiver offering this', 'caregivers offering this', $kc_total, 'kindredcare' ) ); ?></span>
				</p>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( $kc_terms ) : ?>
		<div class="l-container">
			<nav class="s-gigs__filter" aria-label="<?php esc_attr_e( 'Listings by category', 'kindredcare' ); ?>">
				<a class="c-chip" href="<?php echo esc_url( $kc_archive ); ?>">
					<?php kindredcare_gig_category_icon(); ?>
					<?php esc_html_e( 'Everything', 'kindredcare' ); ?>
					<span class="c-chip__count"><?php echo esc_html( number_format_i18n( $kc_all ) ); ?></span>
				</a>
				<?php foreach ( $kc_terms as $kc_cat ) : ?>
					<?php $kc_here = $kc_term instanceof WP_Term && $kc_cat->term_id === $kc_term->term_id; ?>
					<a
						class="c-chip<?php echo $kc_here ? ' is-current' : ''; ?>"
						href="<?php echo esc_url( (string) get_term_link( $kc_cat ) ); ?>"
						<?php echo $kc_here ? 'aria-current="page"' : ''; ?>
					>
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
						array( 'id' => (int) get_the_ID() )
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

			<div class="s-services__empty">
				<p class="s-services__empty-text">
					<?php esc_html_e( 'Nobody is offering this at the moment. Tell us what you need and we’ll find someone.', 'kindredcare' ); ?>
				</p>
				<a class="c-btn c-btn--primary c-btn--lg" href="<?php echo esc_url( kindredcare_gig_contact_url() ); ?>">
					<?php esc_html_e( 'Contact Us', 'kindredcare' ); ?>
				</a>
			</div>

		<?php endif; ?>
	</div>
</article>

<?php
get_footer();
