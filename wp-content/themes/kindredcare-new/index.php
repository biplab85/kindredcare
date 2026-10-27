<?php
/**
 * Fallback template.
 *
 * WordPress requires this file; every request that is not the front page lands
 * here until the rest of the templates are built (single.php, archive.php,
 * search.php, 404.php). It exists so the header and footer can be seen in
 * context on a non-home URL — the post styling is deliberately plain, because
 * the redesign plan has no blog design yet.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="l-section">
	<div class="l-container">
		<?php if ( have_posts() ) : ?>

			<?php if ( ! is_front_page() && ( is_home() || is_archive() || is_search() ) ) : ?>
				<header class="u-stack">
					<h1>
						<?php
						if ( is_search() ) {
							/* translators: %s: search query. */
							printf( esc_html__( 'Results for “%s”', 'kindredcare' ), esc_html( get_search_query() ) );
						} elseif ( is_archive() ) {
							the_archive_title();
						} else {
							echo esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'kindredcare' ) );
						}
						?>
					</h1>
					<?php the_archive_description(); ?>
				</header>
			<?php endif; ?>

			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'u-stack' ); ?>>
					<?php if ( is_singular() ) : ?>
						<h1><?php the_title(); ?></h1>
						<?php the_content(); ?>
					<?php else : ?>
						<h2>
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<?php the_excerpt(); ?>
					<?php endif; ?>
				</article>
				<?php
			endwhile;

			the_posts_pagination();

		else :
			?>
			<p><?php esc_html_e( 'Nothing found.', 'kindredcare' ); ?></p>
			<?php
			get_search_form();
		endif;
		?>
	</div>
</section>

<?php
get_footer();
