<?php
/**
 * "During the visit" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_safety`, edited from KindredCare ->
 * During the visit in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * Section 07 covers who we let in; this covers what happens once they are
 * inside. It is framed as family transparency and NOT as compliance: electronic
 * visit verification is not legally mandated in any Canadian province
 * (platform-workflow.md), so saying or implying otherwise would be inaccurate.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_SAFETY_OPTION = 'kindredcare_safety';

/**
 * Most features the left-hand column will list.
 *
 * The column sits beside a fixed-height timeline card. Past six the list runs
 * past the bottom of that card and the two halves of the split stop reading as
 * a pair.
 */
const KINDREDCARE_SAFETY_MAX_ITEMS = 6;

/**
 * Most rows the example timeline will show.
 */
const KINDREDCARE_SAFETY_MAX_EVENTS = 8;

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_safety_defaults(): array {
	return array(
		'eyebrow'       => 'During the visit',
		'title'         => 'You\'ll know they arrived before they\'ve taken their coat off.',
		'items'         => array(
			array(
				'enabled' => true,
				'title'   => 'GPS-verified check-in and check-out',
				'text'    => 'The app confirms they\'re at the right address. Hours are recorded from the real arrival time, not an estimate.',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'title'   => 'A message when they arrive and when they leave',
				'text'    => 'Sent to whoever in the family needs it — including if you live in another province.',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'title'   => 'A visit summary, every time',
				'text'    => 'What was done, how long it took, and any note the caregiver left. Kept on record.',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'title'   => 'Two-way ratings and an emergency button',
				'text'    => 'Caregivers rate clients too, and all in-app messages are kept in case anything is ever disputed.',
				'icon'    => 0,
			),
		),
		'visual_title'  => 'Tuesday\'s visit — Margaret',
		'visual_chip'   => 'Verified',
		'visual_events' => array(
			array(
				'time' => '9:02 AM',
				'text' => 'Anita checked in — location confirmed',
			),
			array(
				'time' => '9:05 AM',
				'text' => 'You were notified she arrived',
			),
			array(
				'time' => '9:05–11:50 AM',
				'text' => 'Companionship, a walk, lunch prepared',
			),
			array(
				'time' => '11:58 AM',
				'text' => 'Checked out · 2h 56m · “Good morning, ate well”',
			),
			array(
				'time' => '12:00 PM',
				'text' => '$72.33 paid — $5.42 platform fee',
			),
		),
	);
}

/**
 * The feature icons, in order.
 *
 * Hand-drawn per feature and not editable from a list: each illustrates that
 * specific capability — a location pin, a message, a saved record, a shield —
 * so a picker would only ever offer the wrong one. A feature past the fourth
 * reuses an earlier icon. An uploaded image overrides it per row.
 *
 * @return string[] Inline SVG markup.
 */
function kindredcare_safety_icons(): array {
	return array(
		// Location.
		'<svg width="20" height="20" viewBox="0 0 24 24" fill="none">' . "\n" .
		'<path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />' . "\n" .
		'<circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.8" />' . "\n" .
		'</svg>',

		// Message.
		'<svg width="20" height="20" viewBox="0 0 24 24" fill="none">' . "\n" .
		'<path d="M5 6h14v11H9l-4 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />' . "\n" .
		'</svg>',

		// Saved record.
		'<svg width="20" height="20" viewBox="0 0 24 24" fill="none">' . "\n" .
		'<path d="M6 4h12v16l-6-3-6 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />' . "\n" .
		'</svg>',

		// Shield.
		'<svg width="20" height="20" viewBox="0 0 24 24" fill="none">' . "\n" .
		'<path d="M12 3 4 6.5v5.2c0 4.6 3.2 8.4 8 9.3 4.8-.9 8-4.7 8-9.3V6.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />' . "\n" .
		'</svg>',
	);
}

/**
 * The icon for a feature at a given position, cycling when they run out.
 *
 * @param int $index Zero-based position.
 * @return string Inline SVG markup.
 */
function kindredcare_safety_icon( int $index ): string {
	$icons = kindredcare_safety_icons();

	return $icons[ $index % count( $icons ) ];
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_safety(): array {
	$defaults = kindredcare_safety_defaults();
	$stored   = get_option( KINDREDCARE_SAFETY_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );

	$items = array();
	if ( is_array( $stored['items'] ?? null ) && $stored['items'] ) {
		foreach ( array_values( $stored['items'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$items[] = array(
				'enabled' => ! isset( $row['enabled'] ) || (bool) $row['enabled'],
				'title'   => (string) ( $row['title'] ?? '' ),
				'text'    => (string) ( $row['text'] ?? '' ),
				'icon'    => absint( $row['icon'] ?? 0 ),
			);
		}
	}
	$section['items'] = $items ?: $defaults['items'];

	$events = array();
	if ( is_array( $stored['visual_events'] ?? null ) && $stored['visual_events'] ) {
		foreach ( array_values( $stored['visual_events'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$events[] = array(
				'time' => (string) ( $row['time'] ?? '' ),
				'text' => (string) ( $row['text'] ?? '' ),
			);
		}
	}
	$section['visual_events'] = $events ?: $defaults['visual_events'];

	return $section;
}

/**
 * The features that will actually render.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_safety_active_items( array $section ): array {
	return array_values(
		array_filter(
			$section['items'],
			static fn( array $item ): bool => ! empty( $item['enabled'] ) && '' !== trim( (string) $item['title'] )
		)
	);
}

/**
 * The timeline rows that will actually render.
 *
 * A row needs its text; a time with nothing beside it is a gap in the story the
 * card is telling.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, string>>
 */
function kindredcare_safety_active_events( array $section ): array {
	return array_values(
		array_filter(
			$section['visual_events'],
			static fn( array $row ): bool => '' !== trim( (string) $row['text'] )
		)
	);
}

/**
 * Render the "During the visit" section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_safety(): void {
	$section = kindredcare_safety();
	$items   = kindredcare_safety_active_items( $section );
	$events  = kindredcare_safety_active_events( $section );
	?>
	<!-- ================================================================ -->
	<!-- 08 — Safety during the visit                                     -->
	<!-- 07 covers who we let in; this covers what happens inside.        -->
	<!-- Framed as family transparency, NOT compliance — EVV is not       -->
	<!-- mandated in any Canadian province.                               -->
	<!-- ================================================================ -->
	<section class="l-section s-safety" id="safety" aria-labelledby="safety-title">
		<div class="l-container">
			<div class="s-safety__layout">
				<div data-reveal="left">
					<?php if ( '' !== trim( (string) $section['eyebrow'] ) ) : ?>
						<span class="u-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></span>
					<?php endif; ?>
					<h2 class="c-section-head__title" id="safety-title">
						<?php echo esc_html( $section['title'] ); ?>
					</h2>

					<?php if ( $items ) : ?>
						<div class="s-safety__list">
							<?php foreach ( $items as $index => $item ) : ?>
								<div class="s-safety__item">
									<span class="s-safety__icon" aria-hidden="true">
										<?php
										$icon_id = (int) ( $item['icon'] ?? 0 );

										if ( $icon_id > 0 && wp_attachment_is_image( $icon_id ) ) {
											/*
											 * Sized inline rather than by adding a CSS rule: the
											 * stylesheet is generated from kindredcare-home/src/scss
											 * and an edit here would be overwritten by the next Sass
											 * build.
											 */
											echo wp_get_attachment_image(
												$icon_id,
												'full',
												false,
												array(
													'alt'   => '',
													'style' => 'display:block;width:20px;height:20px;object-fit:contain',
												)
											);
										} else {
											echo kindredcare_safety_icon( (int) $index ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Theme-authored inline SVG, not user input.
										}
										?>
									</span>
									<div>
										<p class="s-safety__item-title"><?php echo esc_html( $item['title'] ); ?></p>
										<?php if ( '' !== trim( (string) $item['text'] ) ) : ?>
											<p class="s-safety__item-text">
												<?php echo esc_html( $item['text'] ); ?>
											</p>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="s-safety__visual" data-reveal="up">
					<div class="s-safety__visual-head">
						<p class="s-safety__visual-title"><?php echo esc_html( $section['visual_title'] ); ?></p>
						<?php if ( '' !== trim( (string) $section['visual_chip'] ) ) : ?>
							<span class="s-safety__visual-chip"><?php echo esc_html( $section['visual_chip'] ); ?></span>
						<?php endif; ?>
					</div>
					<div class="s-safety__timeline">
						<?php foreach ( $events as $event ) : ?>
							<div class="s-safety__event">
								<span class="s-safety__event-time"><?php echo esc_html( $event['time'] ); ?></span>
								<span class="s-safety__event-text">
									<?php echo esc_html( $event['text'] ); ?>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
}
