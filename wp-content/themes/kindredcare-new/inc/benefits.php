<?php
/**
 * "Benefits" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_benefits`, edited from KindredCare ->
 * Benefits in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_BENEFITS_OPTION = 'kindredcare_benefits';

/**
 * Most reasons the bordered card will accept.
 *
 * The card sits between the portrait and the counter rail at a fixed column
 * width, so the list grows the card downwards rather than sideways. Past six
 * the card is taller than the portrait beside it and the composition the
 * reference is built on stops reading as a row.
 */
const KINDREDCARE_BENEFITS_MAX_ITEMS = 6;

/**
 * Most counters the amber rail will accept.
 *
 * They stack vertically with a hairline between, so more is a layout question
 * rather than a breaking one — but every figure here has to be true and sourced,
 * and there are only so many of those. Four is the ceiling because a fifth
 * pushes the rail taller than the card it is aligned against.
 */
const KINDREDCARE_BENEFITS_MAX_COUNTERS = 4;

/**
 * The section's shipped defaults.
 *
 * The three counters are the ones the static build shipped: a platform fee, a
 * municipality count and a tier count, all sourced from the project docs. The
 * Sekare reference this layout follows fills the same slots with `20k+ happy
 * customers` and `100% home safety`. We have neither, and inventing them is
 * precisely what produced "0% Verified care-giver" on the live site.
 *
 * @return array<string, mixed>
 */
function kindredcare_benefits_defaults(): array {
	return array(
		'title'      => 'Choosing an independent caregiver can offer several benefits',
		'portrait'   => 0,
		'card_image' => 0,
		'card_text'  => 'Here are several reasons why families in GTA choose a caregiver directly rather than an agency.',
		/*
		 * Flat strings rather than rows with their own switch: these are
		 * four-word chips, and the same line-list the Services screen uses for
		 * "services listed". A blank one is dropped on save.
		 */
		'items'      => array(
			'Personalised matching',
			'Independence and dignity',
			'Verified, background-checked',
			'Transparent hourly pricing',
		),
		'counters'   => array(
			array(
				'enabled' => true,
				'value'   => '20%',
				'label'   => 'Platform fee, not 30–40%',
			),
			array(
				'enabled' => true,
				'value'   => '5',
				'label'   => 'Durham municipalities',
			),
			array(
				'enabled' => true,
				'value'   => '3',
				'label'   => 'Verification tiers',
			),
		),
	);
}

/**
 * The bundled image for a slot, theme-relative.
 *
 * @param string $slot Either 'portrait' or 'card_image'.
 * @return string Theme-relative path.
 */
function kindredcare_benefits_bundled_image( string $slot ): string {
	$map = array(
		'portrait'   => 'assets/img/benefits/several-service1.png',
		'card_image' => 'assets/img/benefits/several-service2.jpg',
	);

	return $map[ $slot ] ?? '';
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_benefits(): array {
	$defaults = kindredcare_benefits_defaults();
	$stored   = get_option( KINDREDCARE_BENEFITS_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );

	$items = array();
	if ( is_array( $stored['items'] ?? null ) && $stored['items'] ) {
		foreach ( array_values( $stored['items'] ) as $row ) {
			if ( is_scalar( $row ) ) {
				$items[] = (string) $row;
			}
		}
	}
	$section['items'] = $items ?: $defaults['items'];

	$counters = array();
	if ( is_array( $stored['counters'] ?? null ) && $stored['counters'] ) {
		foreach ( array_values( $stored['counters'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$counters[] = array(
				'enabled' => ! isset( $row['enabled'] ) || (bool) $row['enabled'],
				'value'   => (string) ( $row['value'] ?? '' ),
				'label'   => (string) ( $row['label'] ?? '' ),
			);
		}
	}
	$section['counters'] = $counters ?: $defaults['counters'];

	$section['portrait']   = absint( $section['portrait'] );
	$section['card_image'] = absint( $section['card_image'] );

	return $section;
}

/**
 * The list items that will actually render.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, string>
 */
function kindredcare_benefits_active_items( array $section ): array {
	return array_values(
		array_filter(
			array_map( 'trim', $section['items'] ),
			static fn( string $item ): bool => '' !== $item
		)
	);
}

/**
 * The counters that will actually render.
 *
 * A counter needs a value; the label alone is not a statistic, and an empty
 * figure above a label renders as a missing number rather than as no counter.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_benefits_active_counters( array $section ): array {
	return array_values(
		array_filter(
			$section['counters'],
			static fn( array $row ): bool => ! empty( $row['enabled'] ) && '' !== trim( (string) $row['value'] )
		)
	);
}

/**
 * Split a counter's display value into the parts the count-up needs.
 *
 * The editor types what should end up on screen — `20%`, `5`, `$25`. Asking
 * for the target, the decimal places, a prefix and a suffix as four separate
 * fields is four chances to publish a figure that animates to something other
 * than what was typed, and this is the section where a wrong number is the
 * whole risk.
 *
 * A value with no digits in it (`Free`) returns null for 'to', which renders as
 * plain text with no count-up rather than as an animation from zero.
 *
 * @param string $value Display value.
 * @return array{to: float|null, decimals: int, prefix: string, suffix: string, text: string}
 */
function kindredcare_benefits_counter_parts( string $value ): array {
	$value = trim( $value );

	$parts = array(
		'to'       => null,
		'decimals' => 0,
		'prefix'   => '',
		'suffix'   => '',
		'text'     => $value,
	);

	if ( ! preg_match( '/^(?P<prefix>[^0-9]*?)(?P<number>\d+(?:\.\d+)?)(?P<suffix>.*)$/s', $value, $match ) ) {
		return $parts;
	}

	$number   = $match['number'];
	$fraction = strstr( $number, '.' );

	$parts['to']       = (float) $number;
	$parts['decimals'] = false === $fraction ? 0 : strlen( $fraction ) - 1;
	$parts['prefix']   = $match['prefix'];
	$parts['suffix']   = $match['suffix'];

	return $parts;
}

/**
 * Print one of the section's two photographs.
 *
 * A chosen attachment renders through wp_get_attachment_image() so that its own
 * width, height and srcset come from the media library — hardcoding the bundled
 * file's dimensions onto a replacement of a different shape is a CLS bug, and
 * the performance budget puts CLS at < 0.05.
 *
 * @param int    $id       Attachment id, 0 for the bundled file.
 * @param string $slot     Either 'portrait' or 'card_image'.
 * @param string $class    Class attribute, empty for none.
 * @param int    $width    Bundled file's width.
 * @param int    $height   Bundled file's height.
 */
function kindredcare_benefits_image( int $id, string $slot, string $class, int $width, int $height ): void {
	$attr = array(
		'alt'     => '',
		'loading' => 'lazy',
	);

	if ( '' !== $class ) {
		$attr['class'] = $class;
	}

	if ( $id > 0 && wp_attachment_is_image( $id ) ) {
		echo wp_get_attachment_image( $id, 'full', false, $attr ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Core escapes its own attributes.
		return;
	}
	?>
	<img
		<?php echo '' !== $class ? 'class="' . esc_attr( $class ) . '"' : ''; ?>
		src="<?php echo esc_url( get_theme_file_uri( kindredcare_benefits_bundled_image( $slot ) ) ); ?>"
		width="<?php echo (int) $width; ?>"
		height="<?php echo (int) $height; ?>"
		alt=""
		loading="lazy"
	/>
	<?php
}

/**
 * Render the "Benefits" section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_benefits(): void {
	$section  = kindredcare_benefits();
	$items    = kindredcare_benefits_active_items( $section );
	$counters = kindredcare_benefits_active_counters( $section );
	?>
	<!-- ================================================================ -->
	<!-- 04b — Benefits                                                    -->
	<!-- Sekare demo-three .several-service-area, matched: heading + image -->
	<!-- left, bordered card centre, counter rail right on the amber       -->
	<!-- ground.                                                           -->
	<!--                                                                   -->
	<!-- The reference's counters read 24/7, 20k+ happy customers and      -->
	<!-- 100% home safety. We have none of those, and inventing them is    -->
	<!-- precisely what produced "0% Verified care-giver" on the live      -->
	<!-- site. Same three slots, three figures that are actually true and  -->
	<!-- sourced from the project docs.                                    -->
	<!-- ================================================================ -->
	<section class="l-section s-benefits" aria-labelledby="benefits-title">
		<div class="l-container">
			<!--
				The portrait moves to the foot of the layout below md — see
				js/modules/benefits-figure.js. Both attributes are inert at every
				other width; the module puts the node back on the way up.
			-->
			<div class="s-benefits__layout" data-benefits-layout>
				<div class="s-benefits__intro" data-reveal="left">
					<h2 class="s-benefits__title" id="benefits-title">
						<?php echo esc_html( $section['title'] ); ?>
					</h2>

					<div class="s-benefits__figure" data-benefits-figure>
						<?php
						kindredcare_benefits_image(
							(int) $section['portrait'],
							'portrait',
							's-benefits__portrait',
							306,
							389
						);
						?>
						<?php
						/*
						 * The blob and the spinning shape stay bundled. Both are
						 * aria-hidden ornament cut to this composition — the blob is
						 * the amber field the portrait is knocked out of, and the
						 * shape's size is what its rotation keyframe is tuned to.
						 * A picker here would only ever offer the wrong file.
						 */
						?>
						<img
							class="s-benefits__blob"
							src="<?php echo esc_url( get_theme_file_uri( 'assets/img/benefits/sevaral.svg' ) ); ?>"
							width="526"
							height="358"
							alt=""
							aria-hidden="true"
							loading="lazy"
						/>
						<img
							class="s-benefits__shape"
							src="<?php echo esc_url( get_theme_file_uri( 'assets/img/benefits/sevarel-shape1.png' ) ); ?>"
							width="101"
							height="101"
							alt=""
							aria-hidden="true"
							loading="lazy"
						/>
					</div>
				</div>

				<div class="s-benefits__panels">
					<div class="s-benefits__card" data-reveal="up">
						<div class="s-benefits__card-media">
							<?php
							kindredcare_benefits_image(
								(int) $section['card_image'],
								'card_image',
								'',
								1125,
								601
							);
							?>
						</div>
						<?php if ( '' !== trim( (string) $section['card_text'] ) ) : ?>
							<p class="s-benefits__card-text">
								<?php echo esc_html( $section['card_text'] ); ?>
							</p>
						<?php endif; ?>
						<?php if ( $items ) : ?>
							<ul class="s-benefits__list">
								<?php foreach ( $items as $item ) : ?>
									<li class="s-benefits__list-item"><?php echo esc_html( $item ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

					<?php if ( $counters ) : ?>
						<div class="s-benefits__counters" data-reveal="up">
							<?php foreach ( $counters as $counter ) : ?>
								<?php $parts = kindredcare_benefits_counter_parts( (string) $counter['value'] ); ?>
								<div class="s-benefits__counter">
									<p class="s-benefits__figure-value">
										<?php if ( null !== $parts['to'] ) : ?>
											<?php
											/*
											 * The true value is in the markup from the start and is
											 * never zeroed up front — see js/modules/counters.js. If
											 * the ScrollTrigger never fires, the real figure is what
											 * stays on screen.
											 */
											?>
											<span
												data-count-to="<?php echo esc_attr( (string) $parts['to'] ); ?>"
												<?php echo $parts['decimals'] > 0 ? 'data-count-decimals="' . esc_attr( (string) $parts['decimals'] ) . '"' : ''; ?>
												<?php echo '' !== $parts['prefix'] ? 'data-count-prefix="' . esc_attr( $parts['prefix'] ) . '"' : ''; ?>
												<?php echo '' !== $parts['suffix'] ? 'data-count-suffix="' . esc_attr( $parts['suffix'] ) . '"' : ''; ?>
											><?php echo esc_html( $parts['text'] ); ?></span>
										<?php else : ?>
											<span><?php echo esc_html( $parts['text'] ); ?></span>
										<?php endif; ?>
									</p>
									<?php if ( '' !== trim( (string) $counter['label'] ) ) : ?>
										<p class="s-benefits__counter-label"><?php echo esc_html( $counter['label'] ); ?></p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}
