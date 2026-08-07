<?php
/**
 * Hero section — stored content and renderer.
 *
 * Everything here is stored in a single option, `kindredcare_hero`, edited from
 * KindredCare -> Hero in the admin (inc/admin-hero.php).
 *
 * Every default is the exact string or file the static build shipped, and an
 * image left unset renders the original <img> tag verbatim rather than routing
 * through wp_get_attachment_image(). A fresh install is therefore byte-identical
 * to kindredcare-home/src/index.html — nothing about the rendered page changes
 * until someone deliberately edits it.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_HERO_OPTION = 'kindredcare_hero';

/**
 * Most photographs the slider will accept.
 *
 * Six is a product decision, not a technical limit: past that the strip stops
 * reading as a considered set and the payload grows for images most visitors
 * never advance to.
 */
const KINDREDCARE_HERO_MAX_SLIDES = 6;

/**
 * The hero's shipped defaults.
 *
 * Image entries are attachment ids, 0 meaning "use the bundled file named in
 * `file`". The bundled path is never taken from user input — see
 * kindredcare_hero_bundled_images().
 *
 * @return array<string, mixed>
 */
function kindredcare_hero_defaults(): array {
	return array(
		'eyebrow'        => 'Senior care in Durham Region',
		'title'          => 'Kind care, close to home.',
		'lead'           => "From gardening and tech help to personal care — book verified local caregivers by the hour, for 7.5% instead of an agency's 15%.",
		'cta_label'      => 'Explore services',
		'cta_url'        => '#services',

		'social_enabled' => true,
		'social_label'   => 'Connect with us',

		/*
		 * The hero keeps its own social URLs rather than sharing the footer's.
		 * The footer ships three icons and the hero four; pointing both at one
		 * set would either add X to the footer or drop it from the hero, and
		 * both are changes to a page that must not change. The icon paths are
		 * still shared — see kindredcare_social_networks().
		 */
		'social'         => array(
			'facebook'  => array( 'url' => 'https://www.facebook.com/kindredcareglobal', 'enabled' => true ),
			'linkedin'  => array( 'url' => 'https://linkedin.com/company/kindredcare/', 'enabled' => true ),
			'instagram' => array( 'url' => 'https://www.instagram.com/kindredcareglobal/', 'enabled' => true ),
			'x'         => array( 'url' => 'https://x.com/kindredcare', 'enabled' => true ),
		),

		'cards'          => array(
			array(
				'image' => 0,
				'file'  => 'assets/img/hero/third-hero-small1.jpg',
				'label' => 'Companionship',
				'tint'  => 'a',
			),
			array(
				'image' => 0,
				'file'  => 'assets/img/hero/third-hero-small2.jpg',
				'label' => 'Technology help',
				'tint'  => 'b',
			),
		),

		'slides'         => array(
			array(
				'image'   => 0,
				'file'    => 'assets/img/hero/third-hero1.jpg',
				'alt'     => 'A caregiver and an older woman sitting together at home',
				'enabled' => true,
			),
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
		),
	);
}

/**
 * Theme-relative image paths a stored row is allowed to name.
 *
 * The slider is repeatable now, so a row's bundled fallback can no longer be
 * looked up by position — it has to travel in the option. That makes `file` an
 * untrusted value, and this allowlist is what stops it becoming an arbitrary
 * path read. Anything not on the list is dropped to an empty string.
 *
 * @return string[]
 */
function kindredcare_hero_bundled_images(): array {
	$paths    = array();
	$defaults = kindredcare_hero_defaults();

	foreach ( array( 'cards', 'slides' ) as $group ) {
		foreach ( $defaults[ $group ] as $row ) {
			if ( ! empty( $row['file'] ) ) {
				$paths[] = $row['file'];
			}
		}
	}

	return $paths;
}

/**
 * The stored hero content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_hero(): array {
	$defaults = kindredcare_hero_defaults();
	$stored   = get_option( KINDREDCARE_HERO_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$hero = array_merge( $defaults, $stored );

	// Social: fixed set of networks, each a url plus an on/off flag.
	$social = array();
	foreach ( $defaults['social'] as $key => $row ) {
		$saved         = is_array( $stored['social'][ $key ] ?? null ) ? $stored['social'][ $key ] : array();
		$social[ $key ] = array(
			'url'     => isset( $saved['url'] ) ? (string) $saved['url'] : $row['url'],
			'enabled' => isset( $saved['enabled'] ) ? (bool) $saved['enabled'] : $row['enabled'],
		);
	}
	$hero['social']         = $social;
	$hero['social_enabled'] = isset( $stored['social_enabled'] ) ? (bool) $stored['social_enabled'] : $defaults['social_enabled'];

	// Cards: fixed pair; `file` and `tint` are structural and stay with the theme.
	$cards = array();
	foreach ( $defaults['cards'] as $index => $row ) {
		$saved           = is_array( $stored['cards'][ $index ] ?? null ) ? $stored['cards'][ $index ] : array();
		$cards[ $index ] = array_merge( $row, $saved );
		$cards[ $index ]['file'] = $row['file'];
		$cards[ $index ]['tint'] = $row['tint'];
	}
	$hero['cards'] = $cards;

	// Slides: repeatable, so the stored list wins outright when there is one.
	$slides = array();
	if ( is_array( $stored['slides'] ?? null ) && $stored['slides'] ) {
		$allowed = kindredcare_hero_bundled_images();
		foreach ( array_values( $stored['slides'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$file = (string) ( $row['file'] ?? '' );
			$slides[] = array(
				'image'   => absint( $row['image'] ?? 0 ),
				'file'    => in_array( $file, $allowed, true ) ? $file : '',
				'alt'     => (string) ( $row['alt'] ?? '' ),
				'enabled' => ! isset( $row['enabled'] ) || (bool) $row['enabled'],
			);
		}
	}
	$hero['slides'] = $slides ?: $defaults['slides'];

	return $hero;
}

/**
 * The slides that will actually render.
 *
 * A row needs to be switched on and to have something to show — an attachment,
 * or one of the bundled files. A row added in the admin and saved before an
 * image was chosen has neither, and is skipped rather than rendered as a broken
 * image.
 *
 * @param array<string, mixed> $hero Hero content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_hero_active_slides( array $hero ): array {
	return array_values(
		array_filter(
			$hero['slides'],
			static function ( array $slide ): bool {
				$has_source = ( $slide['image'] > 0 && wp_attachment_is_image( (int) $slide['image'] ) )
					|| '' !== (string) $slide['file'];

				return ! empty( $slide['enabled'] ) && $has_source;
			}
		)
	);
}

/**
 * The social networks that will actually render, in theme order.
 *
 * @param array<string, mixed> $hero Hero content.
 * @return array<string, array<string, mixed>>
 */
function kindredcare_hero_active_social( array $hero ): array {
	if ( empty( $hero['social_enabled'] ) ) {
		return array();
	}

	$active = array();
	foreach ( kindredcare_social_networks() as $key => $network ) {
		$row = $hero['social'][ $key ] ?? array();
		$url = trim( (string) ( $row['url'] ?? '' ) );

		if ( empty( $row['enabled'] ) || '' === $url ) {
			continue;
		}

		$network['url']  = $url;
		$active[ $key ]  = $network;
	}

	return $active;
}

/**
 * Print a section image, from the media library or the bundled fallback.
 *
 * With no attachment chosen the original tag is reproduced exactly, attribute
 * for attribute. That is what keeps an untouched install pixel-identical.
 *
 * Shared: the service-groups section calls this too. It lives here rather than
 * in template-functions.php only because the hero was the first caller — move
 * it if a third section needs it and this file stops being the obvious home.
 *
 * @param int                  $attachment_id Chosen attachment, 0 for the bundled file.
 * @param string               $file          Theme-relative fallback path.
 * @param string               $class         CSS class for the <img>.
 * @param string               $alt           Alt text.
 * @param array<string,string> $attributes    Extra attributes (width, height, loading, ...).
 */
function kindredcare_render_image( int $attachment_id, string $file, string $class, string $alt, array $attributes ): void {
	if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
		echo wp_get_attachment_image(
			$attachment_id,
			'full',
			false,
			array_merge(
				$attributes,
				array(
					'class' => $class,
					'alt'   => $alt,
				)
			)
		);
		return;
	}

	if ( '' === $file ) {
		return;
	}

	$markup = '';

	/*
	 * Omitted rather than emitted empty: the taxonomy tiles' photographs carry
	 * no class in the reference markup, and a class="" would be a difference
	 * from it for nothing.
	 */
	if ( '' !== $class ) {
		$markup .= sprintf( ' class="%s"', esc_attr( $class ) );
	}

	$markup .= sprintf( ' src="%s" alt="%s"', esc_url( get_theme_file_uri( $file ) ), esc_attr( $alt ) );

	foreach ( $attributes as $name => $value ) {
		$markup .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
	}

	printf(
		'<img%s />',
		$markup // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Names and values are escaped individually above.
	);
}

/**
 * Render the hero section.
 *
 * Markup is the static build's, unchanged. See kindredcare-home/src/index.html
 * lines 165-224 for the original and the reasoning preserved in the comments.
 */
function kindredcare_render_hero(): void {
	$hero   = kindredcare_hero();
	$social = kindredcare_hero_active_social( $hero );
	$slides = kindredcare_hero_active_slides( $hero );
	$title  = (string) $hero['title'];
	?>
	<!-- ================================================================ -->
	<!-- 01 — Hero                                                        -->
	<!-- Asymmetric split: type panel left, photograph bleeding right.    -->
	<!-- No carousel. The headline must be readable within 200ms.         -->
	<!-- ================================================================ -->
	<section class="s-hero" data-hero aria-labelledby="hero-title">
		<div class="s-hero__content">
			<?php if ( '' !== trim( (string) $hero['eyebrow'] ) ) : ?>
				<span class="u-eyebrow s-hero__eyebrow" data-hero-item>
					<?php echo esc_html( $hero['eyebrow'] ); ?>
				</span>
			<?php endif; ?>

			<?php
			/*
			 * Typewriter headline. The ghost copy is in normal flow and reserves
			 * the final two-line box, so nothing below it moves while the visible
			 * copy types over the top. Both are aria-hidden and the h1 carries the
			 * full string as its accessible name — assistive tech never sees a
			 * half-typed heading. With JS off, the typed span still holds the
			 * complete text.
			 *
			 * No data-hero-item here: the headline's entrance is the typing. It
			 * types before the vendor scripts land, so letting the hero timeline
			 * also fade it would blank a half-typed line and start it over.
			 *
			 * All three copies of the string must stay identical — the ghost sizes
			 * the box the typed span writes into.
			 */
			?>
			<h1
				class="s-hero__title"
				id="hero-title"
				aria-label="<?php echo esc_attr( $title ); ?>"
			>
				<span class="s-hero__title-ghost" aria-hidden="true"><?php echo esc_html( $title ); ?></span>
				<span class="s-hero__typed" aria-hidden="true" data-typewriter><?php echo esc_html( $title ); ?></span>
			</h1>

			<?php if ( '' !== trim( (string) $hero['lead'] ) ) : ?>
				<p class="s-hero__lead" data-hero-item>
					<?php echo esc_html( $hero['lead'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( '' !== trim( (string) $hero['cta_label'] ) ) : ?>
				<!-- Arrow comes from .c-btn::after now, on every button. -->
				<a class="c-btn c-btn--primary c-btn--lg s-hero__cta" href="<?php echo esc_url( $hero['cta_url'] ); ?>" data-hero-item>
					<?php echo esc_html( $hero['cta_label'] ); ?>
				</a>
			<?php endif; ?>

			<?php
			/*
			 * The reference's stat-card pair, form matched exactly. Its content
			 * is not: it shows "10+ Winning awards" / "20+ Years of services" and
			 * we have neither. Inventing them is how the live site ended up
			 * publishing "0% Verified care-giver". Real starting rates instead.
			 *
			 * Reference bottom-left cluster: .share-social sits to the LEFT of
			 * .third-hero-widget, both riding the hero's bottom edge.
			 */
			?>
			<div class="s-hero__widgets" data-hero-item>
				<?php if ( $social ) : ?>
					<div class="s-hero__social">
						<?php if ( '' !== trim( (string) $hero['social_label'] ) ) : ?>
							<p class="s-hero__social-label"><?php echo esc_html( $hero['social_label'] ); ?></p>
						<?php endif; ?>
						<ul class="s-hero__social-list">
							<?php foreach ( $social as $network ) : ?>
								<li>
									<a
										class="s-hero__social-link"
										href="<?php echo esc_url( $network['url'] ); ?>"
										aria-label="<?php
											/* translators: 1: site title, 2: social network name. */
											echo esc_attr( sprintf( __( '%1$s on %2$s', 'kindredcare' ), get_bloginfo( 'name', 'display' ), $network['label'] ) );
										?>"
									>
										<?php kindredcare_social_icon( $network, 16 ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<div class="s-hero__stats">
					<?php foreach ( $hero['cards'] as $card ) : ?>
						<article class="c-stat-card c-stat-card--tint-<?php echo esc_attr( $card['tint'] ); ?>">
							<div class="c-stat-card__media">
								<?php
								kindredcare_render_image(
									(int) $card['image'],
									(string) $card['file'],
									'c-stat-card__img',
									'',
									array(
										'width'   => '528',
										'height'  => '384',
										'loading' => 'lazy',
									)
								);
								?>
							</div>
							<span class="c-stat-card__label"><?php echo esc_html( $card['label'] ); ?></span>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php
		/*
		 * Reference: .third-hero-slider — three photographs, loop, 1400ms,
		 * prev/next only. Autoplay stays OFF (plan §12): the images rotate on
		 * demand, the headline never moves.
		 *
		 * The count is editable now. Swiper's loop needs at least two slides;
		 * with one it quietly disables looping, which is why the admin says so
		 * rather than letting it be discovered on the front end.
		 */
		?>
		<div class="s-hero__media">
			<div class="s-hero__media-inner">
				<div class="swiper s-hero__slider" data-slider="hero">
					<div class="swiper-wrapper">
						<?php foreach ( $slides as $index => $slide ) : ?>
							<div class="swiper-slide">
								<?php
								/*
								 * Only the first slide is eager with a high fetch
								 * priority — it is the largest contentful paint.
								 */
								kindredcare_render_image(
									(int) $slide['image'],
									(string) $slide['file'],
									's-hero__img',
									(string) $slide['alt'],
									array_merge(
										array(
											'width'  => '1458',
											'height' => '1350',
										),
										0 === $index
											? array( 'fetchpriority' => 'high' )
											: array( 'loading' => 'lazy' )
									)
								);
								?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( count( $slides ) > 1 ) : ?>
					<div class="s-hero__nav">
						<button
							class="s-hero__nav-btn"
							type="button"
							data-slider-prev="hero"
							aria-label="<?php esc_attr_e( 'Previous photograph', 'kindredcare' ); ?>"
						>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</button>
						<button
							class="s-hero__nav-btn"
							type="button"
							data-slider-next="hero"
							aria-label="<?php esc_attr_e( 'Next photograph', 'kindredcare' ); ?>"
						>
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
