<?php
/**
 * Single article.
 *
 * Two parts: the hero — section 01's asymmetric split, type panel left and the
 * featured image bleeding off the right edge — and then the article at a fixed
 * reading measure with a sticky contents rail beside it.
 *
 * Everything sits in the site's ordinary .l-container, so the kicker, the body
 * and the related row below all start on the same left edge as every other
 * section on the site. The reading measure is set on the text column itself
 * rather than by narrowing the container around it.
 *
 * The body is not printed with the_content(). kindredcare_article_html() runs
 * the same filters and then anchors the headings, wraps tables and rebuilds the
 * FAQ — and the contents rail reads back out of that same processed string, so
 * the links and the ids can never disagree. See inc/blog.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$kc_id      = get_the_ID();
	$kc_term    = kindredcare_primary_term();
	$kc_outline = kindredcare_article_outline();
	$kc_deck    = kindredcare_deck();
	$kc_url     = (string) get_permalink();
	$kc_title   = get_the_title();
	$kc_media   = has_post_thumbnail();
	?>

	<div class="c-progress" aria-hidden="true">
		<span class="c-progress__bar" data-progress-bar></span>
	</div>

	<article class="s-article" data-article>

		<!-- ============================================================== -->
		<!-- Hero — section 01's split: type panel left, photograph right.   -->
		<!-- ============================================================== -->
		<header class="s-article__head<?php echo $kc_media ? '' : ' s-article__head--no-media'; ?>">
			<div class="s-article__head-content">

				<?php if ( $kc_term ) : ?>
					<p class="s-article__kicker">
						<span class="s-article__kicker-rule" aria-hidden="true"></span>
						<a class="s-article__kicker-link" href="<?php echo esc_url( (string) get_category_link( $kc_term ) ); ?>">
							<?php echo esc_html( $kc_term->name ); ?>
						</a>
					</p>
				<?php endif; ?>

				<h1 class="s-article__title"><?php echo esc_html( $kc_title ); ?></h1>

				<?php if ( $kc_deck ) : ?>
					<p class="s-article__deck"><?php echo esc_html( $kc_deck ); ?></p>
				<?php endif; ?>

				<?php
				/*
				 * A ruled strip rather than a run of dot-separated words: byline
				 * held left, publication facts right. It reads as a masthead
				 * credit line and gives the panel a defined bottom edge.
				 */
				?>
				<div class="s-article__meta">
					<span class="c-byline c-byline--lg">
						<?php kindredcare_byline_mark(); ?>
						<span class="c-byline__name"><?php echo kindredcare_byline(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in kindredcare_byline(). ?></span>
					</span>

					<span class="s-article__facts">
						<time class="s-article__date" datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C ) ); ?>">
							<?php echo esc_html( (string) get_the_date( 'F j, Y' ) ); ?>
						</time>

						<span class="s-article__meta-sep" aria-hidden="true"></span>

						<span class="s-article__read">
							<?php
							printf(
								/* translators: %d: reading time in minutes. */
								esc_html__( '%d min read', 'kindredcare' ),
								kindredcare_reading_time()
							);
							?>
						</span>
					</span>
				</div>
			</div>

			<?php if ( $kc_media ) : ?>
				<div class="s-article__media">
					<div class="s-article__media-inner">
						<?php
						the_post_thumbnail(
							'kindredcare-wide',
							array(
								'class'         => 's-article__img',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'sizes'         => '(min-width: 1024px) 56vw, 100vw',
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</header>

		<!-- ============================================================== -->
		<!-- Body + contents rail                                            -->
		<!-- ============================================================== -->
		<div class="l-container">
			<div class="s-article__layout">

				<?php
				/*
				 * The rail is always rendered, not only when there are enough
				 * headings for a contents list. It also carries the share
				 * controls, and those are the only ones on the page at this
				 * width — the copy in the article footer is for mobile, where
				 * this column is hidden.
				 */
				?>
				<aside class="s-article__rail">
					<div class="s-article__rail-sticky">
						<?php if ( $kc_outline ) : ?>
							<nav class="c-toc" aria-labelledby="toc-label" data-toc>
								<p class="c-toc__label" id="toc-label"><?php esc_html_e( 'In this article', 'kindredcare' ); ?></p>
								<ol class="c-toc__list">
									<?php foreach ( $kc_outline as $kc_item ) : ?>
										<li class="c-toc__item">
											<a class="c-toc__link" href="#<?php echo esc_attr( $kc_item['id'] ); ?>">
												<?php echo esc_html( $kc_item['text'] ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ol>
							</nav>
						<?php endif; ?>

						<?php kindredcare_share( $kc_url, $kc_title, 'rail' ); ?>
					</div>
				</aside>

				<div class="s-article__body">
					<div class="c-prose">
						<?php
						echo kindredcare_article_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- post content, already run through the_content filters.
						?>
					</div>

					<footer class="s-article__foot">
						<?php
						/*
						 * Hidden from lg up in CSS — above that width the rail
						 * beside this column already carries it, and two share
						 * rows on one screen is one too many.
						 */
						kindredcare_share( $kc_url, $kc_title, 'inline' );
						?>

						<a class="s-article__back" href="<?php echo esc_url( kindredcare_blog_url() ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<?php esc_html_e( 'All articles', 'kindredcare' ); ?>
						</a>
					</footer>
				</div>
			</div>
		</div>
	</article>

	<?php
	/* -- Related ---------------------------------------------------------- */
	$kc_related = kindredcare_related_posts( $kc_id, 3 );

	if ( $kc_related ) :
		?>
		<section class="l-section l-section--sand s-related">
			<div class="l-container">
				<div class="c-section-head c-section-head--split s-related__head">
					<h2 class="c-section-head__title"><?php esc_html_e( 'Keep reading', 'kindredcare' ); ?></h2>
					<p class="c-section-head__lead">
						<?php esc_html_e( 'More from the journal on choosing, funding and living with care at home.', 'kindredcare' ); ?>
					</p>
				</div>

				<div class="s-related__grid">
					<?php
					foreach ( $kc_related as $kc_post ) {
						kindredcare_post_card( array( 'post' => $kc_post ) );
					}
					?>
				</div>

				<div class="s-related__more">
					<a class="c-btn c-btn--outline" href="<?php echo esc_url( kindredcare_blog_url() ); ?>">
						<?php esc_html_e( 'View all articles', 'kindredcare' ); ?>
					</a>
				</div>
			</div>
		</section>
		<?php
	endif;

endwhile;

get_footer();
