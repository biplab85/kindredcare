<?php
/**
 * Second hero ("Hero — premium") — stored content and renderer.
 *
 * Sits directly under the original hero, which is untouched. Where the first
 * hero is warm and personal, this one makes the professional case: a dark,
 * full-width band with type on the left and a cross-fading photograph slider
 * bleeding off the right edge.
 *
 * Stored in a single option, `kindredcare_hero_pro`, edited from
 * KindredCare -> Hero — premium in the admin (inc/admin-hero-pro.php). Styles
 * live in assets/css/hero-pro.css and the slider in
 * assets/js/modules/hero-pro.js, outside the generated bundle.
 *
 * Every claim in the defaults is one the page already makes elsewhere — the fee
 * split from the first hero, the checks from the verification tiers. No
 * invented numbers; see the note on the hero's stat cards.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_HERO_PRO_OPTION = 'kindredcare_hero_pro';

/** How many photographs the slider can hold. */
const KINDREDCARE_HERO_PRO_MAX_SLIDES = 8;

/**
 * The section's shipped defaults.
 *
 * Slides are a list the editor can add to and delete from. `file` is the
 * bundled fallback; it is stored with each slide so that deleting one of the
 * shipped slides does not shift the others onto the wrong photograph, and it
 * is only ever accepted if it is one of the files in this list — see
 * kindredcare_hero_pro_bundled_file().
 *
 * @return array<string, mixed>
 */
function kindredcare_hero_pro_defaults(): array {
	return array(
		'enabled'           => true,
		'title'             => 'Professional care,',
		'title_accent'      => 'verified at every step.',
		'primary_label'     => 'Find a caregiver',
		'primary_url'       => '#services',
		'primary_new_tab'   => false,
		'secondary_label'   => 'See how it works',
		'secondary_url'     => '#how-it-works',
		'secondary_new_tab' => false,
		'slides'            => array(
			array(
				'image'   => 0,
				'file'    => 'assets/img/hero/third-hero2.jpg',
				'alt'     => 'A caregiver helping an older man in his living room',
				'enabled' => true,
			),
			array(
				'image'   => 0,
				'file'    => 'assets/img/hero/third-hero3.jpg',
				'alt'     => 'An older couple sharing a moment with their companion',
				'enabled' => true,
			),
			array(
				'image'   => 0,
				'file'    => 'assets/img/about/third-about.jpg',
				'alt'     => 'A caregiver spending time with an older woman at home',
				'enabled' => true,
			),
		),
		'proof'           => array(
			array(
				'value' => '20%',
				'label' => 'Platform fee, against the typical 30–40% agency cut',
			),
			array(
				'value' => '3 tiers',
				'label' => 'Of verification, from ID to certifications',
			),
			array(
				'value' => 'Hourly',
				'label' => 'Book only the time you actually need',
			),
		),
	);
}

/**
 * Accept a bundled fallback only if the theme actually ships it.
 *
 * The value round-trips through a hidden form field, so it is treated as
 * untrusted: anything that is not one of the default slides' files is dropped.
 *
 * @param string $file Theme-relative path from the option or the form.
 * @return string The same path, or '' when it is not a bundled slide.
 */
function kindredcare_hero_pro_bundled_file( string $file ): string {
	$allowed = array_column( kindredcare_hero_pro_defaults()['slides'], 'file' );

	return in_array( $file, $allowed, true ) ? $file : '';
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_hero_pro(): array {
	$defaults = kindredcare_hero_pro_defaults();
	$stored   = get_option( KINDREDCARE_HERO_PRO_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section            = array_merge( $defaults, $stored );
	$section['enabled'] = isset( $stored['enabled'] ) ? (bool) $stored['enabled'] : $defaults['enabled'];

	foreach ( array( 'primary_new_tab', 'secondary_new_tab' ) as $key ) {
		$section[ $key ] = isset( $stored[ $key ] ) ? (bool) $stored[ $key ] : $defaults[ $key ];
	}

	if ( isset( $stored['slides'] ) && is_array( $stored['slides'] ) ) {
		$slides = array();
		foreach ( array_values( $stored['slides'] ) as $index => $saved ) {
			if ( ! is_array( $saved ) ) {
				continue;
			}

			$default = $defaults['slides'][ $index ] ?? array(
				'file' => '',
				'alt'  => '',
			);

			/*
			 * Saved before slides could be added or deleted, a slide carried no
			 * `file` and took the bundled photograph of its slot. Those saves
			 * still read the same way; anything newer says which file it uses.
			 */
			$file = array_key_exists( 'file', $saved )
				? kindredcare_hero_pro_bundled_file( (string) $saved['file'] )
				: $default['file'];

			$slides[] = array(
				'image'   => absint( $saved['image'] ?? 0 ),
				'file'    => $file,
				'alt'     => isset( $saved['alt'] ) ? (string) $saved['alt'] : $default['alt'],
				'enabled' => isset( $saved['enabled'] ) ? (bool) $saved['enabled'] : true,
			);
		}
		$section['slides'] = array_slice( $slides, 0, KINDREDCARE_HERO_PRO_MAX_SLIDES );
	} else {
		$section['slides'] = $defaults['slides'];
	}

	$proof = array();
	foreach ( $defaults['proof'] as $index => $row ) {
		$saved           = is_array( $stored['proof'][ $index ] ?? null ) ? $stored['proof'][ $index ] : array();
		$proof[ $index ] = array(
			'value' => isset( $saved['value'] ) ? (string) $saved['value'] : $row['value'],
			'label' => isset( $saved['label'] ) ? (string) $saved['label'] : $row['label'],
		);
	}
	$section['proof'] = $proof;

	return $section;
}

/**
 * Render the premium hero.
 */
function kindredcare_render_hero_pro(): void {
	$section = kindredcare_hero_pro();

	if ( empty( $section['enabled'] ) ) {
		return;
	}

	$accent = trim( (string) $section['title_accent'] );

	$primary_label   = trim( (string) $section['primary_label'] );
	$primary_url     = trim( (string) $section['primary_url'] );
	$secondary_label = trim( (string) $section['secondary_label'] );
	$secondary_url   = trim( (string) $section['secondary_url'] );

	$has_primary   = '' !== $primary_label && '' !== $primary_url;
	$has_secondary = '' !== $secondary_label && '' !== $secondary_url;

	// Whether a link opens a new tab is the editor's call, per link.
	$new_tab          = ' target="_blank" rel="noopener"';
	$primary_target   = ! empty( $section['primary_new_tab'] ) ? $new_tab : '';
	$secondary_target = ! empty( $section['secondary_new_tab'] ) ? $new_tab : '';

	// A slide needs a picture to show: a chosen one or its bundled fallback.
	$slides = array_values(
		array_filter(
			$section['slides'],
			static fn ( array $slide ): bool => ! empty( $slide['enabled'] ) && ( (int) $slide['image'] > 0 || '' !== (string) $slide['file'] )
		)
	);
	$total  = count( $slides );

	$proof = array_filter(
		$section['proof'],
		static fn ( array $row ): bool => '' !== trim( $row['value'] )
	);

	?>
	<!-- ================================================================ -->
	<!-- 01b — Hero, premium                                              -->
	<!-- Full-width dark band: type left, photograph slider bleeding right -->
	<!-- ================================================================ -->
	<section class="s-hero-pro" id="hero-pro" aria-labelledby="hero-pro-title">
		<div class="s-hero-pro__content">
			<h2 class="s-hero-pro__title" id="hero-pro-title">
				<?php echo esc_html( $section['title'] ); ?>
				<?php if ( '' !== $accent ) : ?>
					<span class="s-hero-pro__accent"><?php echo esc_html( $accent ); ?></span>
				<?php endif; ?>
			</h2>

			<?php if ( $has_primary || $has_secondary ) : ?>
				<div class="s-hero-pro__actions">
					<?php if ( $has_primary ) : ?>
						<a class="c-btn c-btn--primary c-btn--lg"<?php echo $primary_target; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed string. ?> href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $primary_label ); ?><?php if ( '' !== $primary_target ) : ?><span class="u-sr-only"> <?php esc_html_e( '(opens in a new tab)', 'kindredcare' ); ?></span><?php endif; ?></a>
					<?php endif; ?>

					<?php if ( $has_secondary ) : ?>
						<a class="s-hero-pro__link"<?php echo $secondary_target; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed string. ?> href="<?php echo esc_url( $secondary_url ); ?>">
							<span><?php echo esc_html( $secondary_label ); ?></span>
							<?php if ( '' !== $secondary_target ) : ?>
								<span class="u-sr-only"><?php esc_html_e( '(opens in a new tab)', 'kindredcare' ); ?></span>
							<?php endif; ?>
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
								<path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $proof ) : ?>
				<dl class="s-hero-pro__proof">
					<?php foreach ( $proof as $row ) : ?>
						<div class="s-hero-pro__proof-item">
							<dt><?php echo esc_html( $row['value'] ); ?></dt>
							<dd><?php echo esc_html( $row['label'] ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>

		<div class="s-hero-pro__media">
			<div class="s-hero-pro__frame">
			<?php if ( $slides ) : ?>
				<div class="swiper s-hero-pro__slider" data-slider="hero-pro">
					<div class="swiper-wrapper">
						<?php foreach ( $slides as $slide ) : ?>
							<div class="swiper-slide">
								<?php
								kindredcare_render_image(
									(int) $slide['image'],
									(string) $slide['file'],
									's-hero-pro__img',
									(string) $slide['alt'],
									array(
										'width'    => '1458',
										'height'   => '1350',
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								);
								?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $total > 1 ) : ?>
				<?php
				/*
				 * Counter, progress line and arrows. aria-hidden on the counter:
				 * Swiper's a11y module already announces "1 / 3".
				 */
				?>
				<div class="s-hero-pro__nav">
					<span class="s-hero-pro__count" aria-hidden="true">
						<span data-hero-pro-now>01</span>
						<span class="s-hero-pro__count-bar"><span data-hero-pro-bar></span></span>
						<span><?php echo esc_html( str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) ); ?></span>
					</span>
					<button class="s-hero-pro__nav-btn" type="button" data-slider-prev="hero-pro" aria-label="<?php esc_attr_e( 'Previous photograph', 'kindredcare' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</button>
					<button class="s-hero-pro__nav-btn" type="button" data-slider-next="hero-pro" aria-label="<?php esc_attr_e( 'Next photograph', 'kindredcare' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</button>
				</div>
			<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}
