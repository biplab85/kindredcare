<?php
/**
 * The article grid, shared by the index, category archives and search.
 *
 * Nine posts to a page, in three columns. On the first page the newest article
 * is promoted to a lead card spanning two columns and two rows — which leaves
 * exactly eight cells across the remaining four rows, so the grid still packs
 * without a gap. Later pages are a plain three by three.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$kc_paged = max( 1, (int) get_query_var( 'paged' ) );
$kc_lead  = 1 === $kc_paged && ! is_search() && ! is_paged();
$kc_index = 0;
?>

<section class="l-section s-blog">
	<div class="l-container">
		<?php if ( have_posts() ) : ?>

			<div class="s-blog__grid<?php echo $kc_lead ? ' s-blog__grid--has-lead' : ''; ?>">
				<?php
				while ( have_posts() ) :
					the_post();

					kindredcare_post_card(
						array(
							'lead'    => $kc_lead && 0 === $kc_index,
							'heading' => ( $kc_lead && 0 === $kc_index ) ? 'h2' : 'h3',
						)
					);

					++$kc_index;
				endwhile;
				?>
			</div>

			<?php kindredcare_pagination(); ?>

		<?php else : ?>

			<div class="s-blog__empty">
				<h2 class="s-blog__empty-title"><?php esc_html_e( 'Nothing here yet', 'kindredcare' ); ?></h2>
				<p class="s-blog__empty-text">
					<?php esc_html_e( 'We could not find an article matching that. Try a different search, or read the latest from the journal.', 'kindredcare' ); ?>
				</p>
				<a class="c-btn c-btn--primary" href="<?php echo esc_url( kindredcare_blog_url() ); ?>">
					<?php esc_html_e( 'All articles', 'kindredcare' ); ?>
				</a>
			</div>

		<?php endif; ?>
	</div>
</section>
