<?php
/**
 * "How it works" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_how`, edited from KindredCare -> How
 * it works in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_HOW_OPTION = 'kindredcare_how';

/**
 * Most steps the section will accept.
 *
 * Above md the steps are a staggered grid and below it a swiper; both cope with
 * more, but the section's whole claim is that booking is a short sequence, and
 * a seven-step "simple process" argues against itself.
 */
const KINDREDCARE_HOW_MAX_STEPS = 6;

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_how_defaults(): array {
	return array(
		'eyebrow' => 'How it works',
		'title'   => 'Booked in four steps.',
		'lead'    => 'You see the caregiver, the service and the price before you commit. No consultation call, no contract, no minimum hours.',
		'steps'   => array(
			array(
				'enabled' => true,
				'title'   => 'Tell us who you\'re caring for',
				'text'    => 'Their address, the languages they speak, any conditions, and what they enjoy. It takes two minutes and it is what the matching runs on.',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'title'   => 'Browse gigs ranked for them',
				'text'    => 'Every listing shows what\'s included, the hourly rate, the caregiver\'s verification tier and their reviews. Filter by language, distance, budget or gender.',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'title'   => 'Book the date, time and address',
				'text'    => 'One visit or a repeating weekly slot. The caregiver accepts or declines — and if they decline, we offer you the next best match straight away.',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'title'   => 'They check in, you\'re notified',
				'text'    => 'You get a message when they arrive and a summary when they leave. Payment runs through the platform afterwards — no cash, no invoicing.',
				'icon'    => 0,
			),
		),
	);
}

/**
 * The step medallion icons, in order.
 *
 * Hand-drawn per step and not editable: each is a small illustration of that
 * specific step, so a picker would only ever offer the wrong one. A step past
 * the fourth reuses an earlier icon — see kindredcare_how_icon().
 *
 * @return string[] Inline SVG markup.
 */
function kindredcare_how_icons(): array {
	return array(
		// Step 1.
		<<<'SVG'
<!-- Intake: a profile being filled in -->
<svg viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round">
<rect x="11" y="9" width="42" height="46" rx="7" stroke="currentColor" stroke-width="2.2" />
<circle class="s-how__pop" cx="32" cy="26" r="7" />
<path d="M20 44.5c0-6.4 5.4-9.5 12-9.5s12 3.1 12 9.5" stroke="currentColor" stroke-width="2.2" />
<path d="M24.5 50.5h15" stroke="currentColor" stroke-width="2.2" />
</svg>
SVG,

		// Step 2.
		<<<'SVG'
<!-- Browse: a ranked list, searched -->
<svg viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round">
<rect x="9" y="12" width="30" height="9" rx="3" stroke="currentColor" stroke-width="2.2" />
<rect x="9" y="27" width="34" height="9" rx="3" stroke="currentColor" stroke-width="2.2" />
<rect x="9" y="42" width="24" height="9" rx="3" stroke="currentColor" stroke-width="2.2" />
<polygon
class="s-how__pop"
points="52,9 53.53,12.9 57.71,13.15 54.47,15.8 55.53,19.85 52,17.6 48.47,19.85 49.53,15.8 46.29,13.15 50.47,12.9"
/>
<circle cx="44" cy="44" r="9" stroke="currentColor" stroke-width="2.2" />
<path d="M50.4 50.4 56 56" stroke="currentColor" stroke-width="2.6" />
</svg>
SVG,

		// Step 3.
		<<<'SVG'
<!-- Book: a date picked, at a time -->
<svg viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round">
<rect x="9" y="13" width="38" height="38" rx="6" stroke="currentColor" stroke-width="2.2" />
<path d="M9 24h38" stroke="currentColor" stroke-width="2.2" />
<path d="M19 13V8M37 13V8" stroke="currentColor" stroke-width="2.2" />
<rect class="s-how__pop" x="15" y="30" width="10" height="8" rx="2.5" />
<circle cx="46" cy="46" r="11" fill="var(--c-how-surface)" stroke="currentColor" stroke-width="2.2" />
<path d="M46 40.5V46.5l4.2 2.6" stroke="currentColor" stroke-width="2.2" />
</svg>
SVG,

		// Step 4.
		<<<'SVG'
<!-- Check-in: notified, confirmed -->
<svg viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round">
<path d="M32 11V7.5" stroke="currentColor" stroke-width="2.2" />
<path
d="M21 29v-7a11 11 0 0 1 22 0v7l3.5 6.5H17.5L21 29Z"
stroke="currentColor"
stroke-width="2.2"
/>
<path d="M27.5 38.5a4.5 4.5 0 0 0 9 0" stroke="currentColor" stroke-width="2.2" />
<circle class="s-how__pop" cx="47" cy="45" r="9" />
<path d="M43 45.2l2.8 2.8 5.4-5.6" stroke="#fff" stroke-width="2.6" />
</svg>
SVG,
	);
}

/**
 * The icon for a step at a given position, cycling when they run out.
 *
 * @param int $index Zero-based step position.
 * @return string Inline SVG markup.
 */
function kindredcare_how_icon( int $index ): string {
	$icons = kindredcare_how_icons();

	return $icons[ $index % count( $icons ) ];
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_how(): array {
	$defaults = kindredcare_how_defaults();
	$stored   = get_option( KINDREDCARE_HOW_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );
	$steps   = array();

	if ( is_array( $stored['steps'] ?? null ) && $stored['steps'] ) {
		foreach ( array_values( $stored['steps'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$steps[] = array(
				'enabled' => ! isset( $row['enabled'] ) || (bool) $row['enabled'],
				'title'   => (string) ( $row['title'] ?? '' ),
				'text'    => (string) ( $row['text'] ?? '' ),
				// 0 means "use the hand-drawn illustration for this position".
				'icon'    => absint( $row['icon'] ?? 0 ),
			);
		}
	}

	$section['steps'] = $steps ?: $defaults['steps'];

	return $section;
}

/**
 * The steps that will actually render.
 *
 * A step needs to be switched on and to have a title — the title is the step,
 * and a numbered medallion with no heading beside it reads as a loading state.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_how_active_steps( array $section ): array {
	return array_values(
		array_filter(
			$section['steps'],
			static fn( array $step ): bool => ! empty( $step['enabled'] ) && '' !== trim( (string) $step['title'] )
		)
	);
}

/**
 * Render the "How it works" section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_how(): void {
	$section = kindredcare_how();
	$steps   = kindredcare_how_active_steps( $section );
	$total   = count( $steps );
	?>
	<!-- ================================================================ -->
	<!-- 03 — How It Works                                                -->
	<!-- Removes procedural uncertainty. Families will not create an       -->
	<!-- account to find out how a thing works.                           -->
	<!-- ================================================================ -->
	<section class="l-section s-how" id="how-it-works" aria-labelledby="how-title">
		<div class="l-container">
			<div class="c-section-head c-section-head--split" data-reveal="up">
				<div>
					<?php if ( '' !== trim( (string) $section['eyebrow'] ) ) : ?>
						<span class="u-eyebrow c-section-head__eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></span>
					<?php endif; ?>
					<h2 class="c-section-head__title" id="how-title"><?php echo esc_html( $section['title'] ); ?></h2>
				</div>
				<?php if ( '' !== trim( (string) $section['lead'] ) ) : ?>
					<p class="c-section-head__lead">
						<?php echo esc_html( $section['lead'] ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="s-how__track" data-how-timeline>
				<?php
				/*
				 * Staggered-capsule layout. The dashed medallion is wider than its
				 * capsule and breaks both side edges, so nothing in this subtree may
				 * clip — see _how-it-works.scss.
				 *
				 * The connecting SVG path is injected here as the first child by
				 * js/modules/how-steps.js, which also owns the scroll-driven reveal
				 * of these capsules. They carry data-how-step rather than
				 * data-reveal: the shared reveal in reveal.js writes the same
				 * transform channel and the two would fight.
				 */
				?>
				<div class="swiper" data-slider="how">
					<div class="swiper-wrapper s-how__grid" data-how-track>
						<?php foreach ( $steps as $index => $step ) : ?>
							<div class="swiper-slide s-how__step" data-how-step>
								<div class="s-how__medallion">
									<span class="s-how__dial" aria-hidden="true"></span>
									<span class="s-how__num" aria-hidden="true"><?php echo (int) $index + 1; ?></span>
									<span class="s-how__icon" aria-hidden="true">
										<?php
										$icon_id = (int) ( $step['icon'] ?? 0 );

										if ( $icon_id > 0 && wp_attachment_is_image( $icon_id ) ) {
											/*
											 * .s-how__icon sizes its child with an `svg { width:100% }`
											 * rule, so an <img> would otherwise render at its intrinsic
											 * size. Sized inline rather than by adding a CSS rule: the
											 * stylesheet is generated from kindredcare-home/src/scss and
											 * an edit here would be overwritten by the next Sass build.
											 */
											echo wp_get_attachment_image(
												$icon_id,
												'full',
												false,
												array(
													'alt'   => '',
													'style' => 'display:block;width:100%;height:100%;object-fit:contain',
												)
											);
										} else {
											echo kindredcare_how_icon( (int) $index ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Theme-authored inline SVG, not user input.
										}
										?>
									</span>
								</div>
								<h3 class="s-how__title"><?php echo esc_html( $step['title'] ); ?></h3>
								<?php if ( '' !== trim( (string) $step['text'] ) ) : ?>
									<p class="s-how__text">
										<?php echo esc_html( $step['text'] ); ?>
									</p>
								<?php endif; ?>
								<span class="u-sr-only">
									<?php
									printf(
										/* translators: 1: step number, 2: total steps. */
										esc_html__( 'Step %1$s of %2$s', 'kindredcare' ),
										(int) $index + 1,
										(int) $total
									);
									?>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="swiper-pagination"></div>
				</div>
			</div>
		</div>
	</section>
	<?php
}
