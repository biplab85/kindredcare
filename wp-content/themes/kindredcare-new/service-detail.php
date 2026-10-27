<?php
/**
 * One service's detail page — /services/{slug}/.
 *
 * Served by inc/service-pages.php for any visible "What you can book" tile. All
 * content is the tile's own, edited at KindredCare -> Service taxonomy:
 *
 *   - name, short description, rate, tier and photograph — the same fields the
 *     home page tile shows, so the tile and its page cannot disagree;
 *   - the longer description and "What's included" — detail-only fields
 *     stored on the same tile.
 *
 * The booking card's two buttons are shared by every service, from the same
 * screen's "Booking card buttons" panel.
 *
 * The masthead reuses the article header (kicker, title, deck) that the
 * Services and Contact pages use; everything below it is styled by
 * assets/css/service-detail.css.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$kc_tile = kindredcare_current_service();

if ( ! $kc_tile ) {
	// inc/service-pages.php only routes here for a found service.
	return;
}

$kc_name      = wp_strip_all_tags( html_entity_decode( (string) $kc_tile['name'], ENT_QUOTES ) );
$kc_paras     = kindredcare_service_paragraphs( (string) $kc_tile['details'] );
$kc_included  = kindredcare_service_lines( (string) $kc_tile['included'] );
$kc_image     = $kc_tile['images'][0] ?? null;

// Drawn from the Verification tiers and How it works screens, not the tile.
$kc_tier   = kindredcare_service_tier( $kc_tile );
$kc_checks = $kc_tier ? kindredcare_service_tier_checks( $kc_tier ) : array();
$kc_steps  = kindredcare_how_active_steps( kindredcare_how() );

$kc_others = array_values(
	array_filter(
		kindredcare_taxonomy_active_tiles( kindredcare_taxonomy() ),
		static fn( array $tile ): bool => kindredcare_service_slug( $tile ) !== kindredcare_service_slug( $kc_tile )
	)
);

get_header();
?>

<article class="s-article s-service">

	<header class="s-article__head s-article__head--no-media">
		<div class="s-article__head-content">
			<p class="s-article__kicker">
				<span class="s-article__kicker-rule" aria-hidden="true"></span>
				<a class="s-article__kicker-link" href="<?php echo esc_url( kindredcare_services_index_url() ); ?>"><?php esc_html_e( 'Services', 'kindredcare' ); ?></a>
			</p>

			<h1 class="s-article__title s-article__title--page"><?php echo esc_html( $kc_name ); ?></h1>

			<?php if ( '' !== trim( (string) $kc_tile['blurb'] ) ) : ?>
				<p class="s-article__deck"><?php echo esc_html( $kc_tile['blurb'] ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<div class="l-container">
		<div class="s-service__layout">

			<div class="s-service__main">
				<?php if ( $kc_image && ( (int) $kc_image['image'] > 0 || '' !== (string) $kc_image['file'] ) ) : ?>
					<figure class="s-service__media">
						<?php
						kindredcare_render_image(
							(int) $kc_image['image'],
							(string) $kc_image['file'],
							's-service__img',
							$kc_name,
							array(
								'decoding'      => 'async',
								'fetchpriority' => 'high',
							)
						);
						?>
					</figure>
				<?php endif; ?>

				<?php if ( $kc_paras ) : ?>
					<section class="s-service__block" aria-labelledby="service-about">
						<h2 class="s-service__heading" id="service-about"><?php esc_html_e( 'About this service', 'kindredcare' ); ?></h2>
						<div class="s-service__prose">
							<?php foreach ( $kc_paras as $kc_para ) : ?>
								<p><?php echo nl2br( esc_html( $kc_para ) ); ?></p>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( $kc_included ) : ?>
					<section class="s-service__block" aria-labelledby="service-included">
						<h2 class="s-service__heading" id="service-included"><?php esc_html_e( "What's included", 'kindredcare' ); ?></h2>
						<ul class="s-service__list">
							<?php foreach ( $kc_included as $kc_item ) : ?>
								<li class="s-service__item">
									<span class="s-service__tick" aria-hidden="true">
										<svg viewBox="0 0 16 16" fill="none"><path d="M3.5 8.5l3 3 6-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
									</span>
									<?php echo esc_html( $kc_item ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $kc_tier && $kc_checks ) : ?>
					<section class="s-service__block" aria-labelledby="service-checks">
						<h2 class="s-service__heading" id="service-checks"><?php esc_html_e( 'Who can take this booking', 'kindredcare' ); ?></h2>
						<div class="s-service__tier">
							<p class="s-service__tier-head">
								<span class="s-service__tier-badge"><?php echo esc_html( $kc_tier['badge'] ); ?></span>
								<?php
								/* translators: %s: verification tier name, e.g. "Enhanced". */
								echo esc_html( sprintf( __( '%s verification required', 'kindredcare' ), $kc_tier['name'] ) );
								?>
							</p>
							<?php if ( '' !== trim( (string) $kc_tier['desc'] ) ) : ?>
								<p class="s-service__tier-desc"><?php echo esc_html( $kc_tier['desc'] ); ?></p>
							<?php endif; ?>
							<ul class="s-service__checks">
								<?php foreach ( $kc_checks as $kc_check ) : ?>
									<li>
										<svg viewBox="0 0 16 16" width="16" height="16" fill="none" aria-hidden="true"><path d="M8 1.5 2.5 3.7v4.1c0 3.3 2.3 5.9 5.5 6.9 3.2-1 5.5-3.6 5.5-6.9V3.7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" /><path d="m5.6 8 1.7 1.7 3.2-3.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
										<?php echo esc_html( $kc_check ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( $kc_steps ) : ?>
					<section class="s-service__block" aria-labelledby="service-steps">
						<h2 class="s-service__heading" id="service-steps"><?php esc_html_e( 'How booking works', 'kindredcare' ); ?></h2>
						<ol class="s-service__steps">
							<?php foreach ( $kc_steps as $kc_index => $kc_step ) : ?>
								<li class="s-service__step">
									<span class="s-service__step-num" aria-hidden="true"><?php echo (int) $kc_index + 1; ?></span>
									<div>
										<h3 class="s-service__step-title"><?php echo esc_html( $kc_step['title'] ); ?></h3>
										<?php if ( '' !== trim( (string) $kc_step['text'] ) ) : ?>
											<p class="s-service__step-text"><?php echo esc_html( $kc_step['text'] ); ?></p>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>
			</div>

			<aside class="s-service__aside" aria-label="<?php esc_attr_e( 'Booking details', 'kindredcare' ); ?>">
				<div class="s-service__card">
					<dl class="s-service__facts">
						<?php if ( '' !== trim( (string) $kc_tile['rate'] ) ) : ?>
							<div class="s-service__fact">
								<dt><?php esc_html_e( 'Typical rate', 'kindredcare' ); ?></dt>
								<dd><?php echo esc_html( $kc_tile['rate'] ); ?></dd>
							</div>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $kc_tile['tier'] ) ) : ?>
							<div class="s-service__fact">
								<dt><?php esc_html_e( 'Verification required', 'kindredcare' ); ?></dt>
								<dd><?php echo esc_html( $kc_tile['tier'] ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>

					<?php
					// The pair from the Service taxonomy screen, the same on every service.
					$kc_booking = array_filter(
						kindredcare_taxonomy()['booking_buttons'] ?? array(),
						static fn( array $button ): bool => ! empty( $button['enabled'] ) && '' !== trim( (string) $button['label'] ) && '' !== trim( (string) $button['url'] )
					);
					?>
					<?php if ( $kc_booking ) : ?>
						<?php
						// One drawing per slot: a person joining, and a search.
						$kc_action_icons = array(
							'register' => 'M15 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19 8v6M22 11h-6',
							'find'     => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14ZM20 20l-4-4',
						);
						?>
						<div class="s-service__actions<?php echo 1 === count( $kc_booking ) ? ' s-service__actions--single' : ''; ?>">
							<?php foreach ( $kc_booking as $kc_button ) : ?>
								<a class="s-service__action s-service__action--<?php echo esc_attr( $kc_button['key'] ); ?>"<?php echo kindredcare_link_target( (bool) $kc_button['new_tab'] ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed attribute string. ?> href="<?php echo esc_url( $kc_button['url'] ); ?>">
									<span class="s-service__action-icon" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?php echo esc_attr( $kc_action_icons[ $kc_button['key'] ] ?? '' ); ?>" /></svg>
									</span>
									<span class="s-service__action-label"><?php echo esc_html( $kc_button['label'] ); ?><?php kindredcare_link_new_tab_hint( (bool) $kc_button['new_tab'] ); ?></span>
									<?php // ↗ for a link that leaves for a new tab, → for one that stays. ?>
									<svg class="s-service__action-arrow" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="<?php echo $kc_button['new_tab'] ? 'M5 11 11 5M6 5h5v5' : 'M3 8h10M9 4l4 4-4 4'; ?>" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</div>

	<?php if ( $kc_others ) : ?>
		<section class="s-service__others" aria-labelledby="service-others">
			<div class="l-container">
				<h2 class="s-service__heading" id="service-others"><?php esc_html_e( 'Other services', 'kindredcare' ); ?></h2>
				<ul class="s-service__grid">
					<?php foreach ( $kc_others as $kc_other ) : ?>
						<?php
						$kc_icon      = kindredcare_service_icon( $kc_other );
						$kc_tier_text = trim( wp_strip_all_tags( (string) $kc_other['tier'] ) );
						// "Enhanced — Trusted" reads as "Enhanced" on a small chip.
						$kc_tier_text = trim( (string) preg_split( '/\s+[—–-]\s+/u', $kc_tier_text )[0] );
						?>
						<li>
							<a class="s-service__other" href="<?php echo esc_url( kindredcare_service_url( $kc_other ) ); ?>" style="--tone: <?php echo esc_attr( $kc_icon['tone'] ); ?>">
								<span class="s-service__other-top">
									<span class="s-service__other-icon"><?php echo $kc_icon['svg']; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed theme SVG. ?></span>
									<span class="s-service__other-arrow" aria-hidden="true">
										<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
									</span>
								</span>
								<span class="s-service__other-name"><?php echo esc_html( wp_strip_all_tags( html_entity_decode( (string) $kc_other['name'], ENT_QUOTES ) ) ); ?></span>
								<?php if ( '' !== trim( (string) $kc_other['blurb'] ) ) : ?>
									<span class="s-service__other-text"><?php echo esc_html( $kc_other['blurb'] ); ?></span>
								<?php endif; ?>
								<span class="s-service__other-meta">
									<?php if ( '' !== trim( (string) $kc_other['rate'] ) ) : ?>
										<span class="s-service__other-rate"><?php echo esc_html( $kc_other['rate'] ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $kc_tier_text ) : ?>
										<span class="s-service__other-tier"><?php echo esc_html( $kc_tier_text ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

</article>

<?php
get_footer();
