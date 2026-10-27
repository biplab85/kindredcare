<?php
/**
 * The service listing — /services/.
 *
 * Served by inc/service-pages.php. Lists every visible "What you can book" tile
 * as a card linking to its detail page, in the order set at KindredCare ->
 * Service taxonomy. The deck under the title is that section's lead paragraph,
 * so the two read the same way.
 *
 * Masthead is the shared article header; the cards are styled by
 * assets/css/service-detail.css.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$kc_section = kindredcare_taxonomy();
$kc_tiles   = kindredcare_taxonomy_active_tiles( $kc_section );

get_header();
?>

<article class="s-article s-service">

	<header class="s-article__head s-article__head--no-media">
		<div class="s-article__head-content">
			<?php if ( '' !== trim( (string) $kc_section['eyebrow'] ) ) : ?>
				<p class="s-article__kicker">
					<span class="s-article__kicker-rule" aria-hidden="true"></span>
					<?php echo esc_html( $kc_section['eyebrow'] ); ?>
				</p>
			<?php endif; ?>

			<h1 class="s-article__title s-article__title--page"><?php esc_html_e( 'Services', 'kindredcare' ); ?></h1>

			<?php if ( '' !== trim( (string) $kc_section['lead'] ) ) : ?>
				<p class="s-article__deck"><?php echo esc_html( $kc_section['lead'] ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<div class="l-container">
		<?php if ( $kc_tiles ) : ?>
			<ul class="s-service__cards">
				<?php foreach ( $kc_tiles as $kc_tile ) : ?>
					<?php
					$kc_name  = wp_strip_all_tags( html_entity_decode( (string) $kc_tile['name'], ENT_QUOTES ) );
					$kc_image = $kc_tile['images'][0] ?? null;
					?>
					<li>
						<a class="s-service__tile" href="<?php echo esc_url( kindredcare_service_url( $kc_tile ) ); ?>">
							<?php if ( $kc_image && ( (int) $kc_image['image'] > 0 || '' !== (string) $kc_image['file'] ) ) : ?>
								<span class="s-service__tile-media" aria-hidden="true">
									<?php
									kindredcare_render_image(
										(int) $kc_image['image'],
										(string) $kc_image['file'],
										's-service__tile-img',
										'',
										array(
											'loading'  => 'lazy',
											'decoding' => 'async',
										)
									);
									?>
								</span>
							<?php endif; ?>

							<span class="s-service__tile-body">
								<span class="s-service__tile-name"><?php echo esc_html( $kc_name ); ?></span>

								<?php if ( '' !== trim( (string) $kc_tile['blurb'] ) ) : ?>
									<span class="s-service__tile-text"><?php echo esc_html( $kc_tile['blurb'] ); ?></span>
								<?php endif; ?>

								<?php if ( '' !== trim( (string) $kc_tile['rate'] ) || '' !== trim( (string) $kc_tile['tier'] ) ) : ?>
									<span class="s-service__tile-meta">
										<?php if ( '' !== trim( (string) $kc_tile['rate'] ) ) : ?>
											<span class="s-service__tile-rate"><?php echo esc_html( $kc_tile['rate'] ); ?></span>
										<?php endif; ?>
										<?php if ( '' !== trim( (string) $kc_tile['tier'] ) ) : ?>
											<span class="s-service__tile-tier"><?php echo esc_html( $kc_tile['tier'] ); ?></span>
										<?php endif; ?>
									</span>
								<?php endif; ?>

								<span class="s-service__tile-more">
									<?php esc_html_e( 'View service', 'kindredcare' ); ?>
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
								</span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="s-service__empty"><?php esc_html_e( 'No services are listed right now.', 'kindredcare' ); ?></p>
		<?php endif; ?>
	</div>

</article>

<?php
get_footer();
