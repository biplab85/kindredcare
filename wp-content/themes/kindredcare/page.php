<?php
/**
 * Static pages — the legal set, and anything else authored in the editor.
 *
 * Deliberately built from the article primitives rather than a second layout:
 * the same hero panel, the same contents rail, the same prose styles. A privacy
 * policy is long-form reading with headings, which is exactly what single.php
 * already solves — and kindredcare_article_html() anchors those headings, so
 * the rail works here for free.
 *
 * What is left out is the article furniture that a page has no business
 * carrying: no byline, no reading time, no related posts.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$kc_id      = (int) get_the_ID();
	$kc_outline = kindredcare_article_outline();
	$kc_kicker  = kindredcare_page_kicker( $kc_id );
	/*
	 * Only a hand-written excerpt. get_the_excerpt() falls back to trimming the
	 * body, which on a policy produced a standfirst reading "Last Updated: 28th
	 * October 2025 Effective in Canada Welcome to KindredCare. This Privacy
	 * Policy explains…" — the front matter and the opening paragraphs run
	 * together, directly above the strip that states the same two facts.
	 */
	$kc_deck    = has_excerpt() ? get_the_excerpt() : '';
	$kc_intro   = kindredcare_policy_intro();
	?>

	<article class="s-article" data-article>

		<header class="s-article__head s-article__head--no-media">
			<div class="s-article__head-content">

				<?php if ( $kc_kicker ) : ?>
					<p class="s-article__kicker">
						<span class="s-article__kicker-rule" aria-hidden="true"></span>
						<?php echo esc_html( $kc_kicker ); ?>
					</p>
				<?php endif; ?>

				<h1 class="s-article__title s-article__title--page"><?php the_title(); ?></h1>

				<?php if ( $kc_deck ) : ?>
					<p class="s-article__deck"><?php echo esc_html( $kc_deck ); ?></p>
				<?php endif; ?>

				<?php if ( $kc_intro['meta'] ) : ?>
					<dl class="s-page-meta">
						<?php foreach ( $kc_intro['meta'] as $kc_fact ) : ?>
							<div class="s-page-meta__item">
								<dt class="s-page-meta__label"><?php echo esc_html( $kc_fact['label'] ); ?></dt>
								<dd class="s-page-meta__value"><?php echo esc_html( $kc_fact['value'] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>
		</header>

		<div class="l-container">
			<div class="s-article__layout">

				<aside class="s-article__rail">
					<div class="s-article__rail-sticky">
						<?php if ( $kc_outline ) : ?>
							<nav class="c-toc" aria-labelledby="toc-label" data-toc>
								<p class="c-toc__label" id="toc-label"><?php esc_html_e( 'On this page', 'kindredcare' ); ?></p>
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

						<?php kindredcare_share( (string) get_permalink(), get_the_title(), 'rail' ); ?>
					</div>
				</aside>

				<div class="s-article__body">
					<div class="c-prose">
						<?php
						// Front matter already shown in the masthead is not repeated here.
						echo $kc_intro['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- page content, already run through the_content filters.
						?>
					</div>

					<footer class="s-article__foot">
						<?php kindredcare_share( (string) get_permalink(), get_the_title(), 'inline' ); ?>

						<a class="s-article__back" href="<?php echo esc_url( home_url( '/' ) ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<?php esc_html_e( 'Back to home', 'kindredcare' ); ?>
						</a>
					</footer>
				</div>
			</div>
		</div>
	</article>

	<?php
endwhile;

get_footer();
