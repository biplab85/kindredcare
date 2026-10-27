<?php
/**
 * "Founding caregivers" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_caregivers`, edited from KindredCare
 * -> Founding caregivers in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * At launch this slot does SUPPLY ACQUISITION, not liquidity proof: there are 0
 * listings and fabricating caregivers is forbidden. The cards describe the
 * caregiver's own journey, which is why none of them carries a name or a face.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_CAREGIVERS_OPTION = 'kindredcare_caregivers';

/**
 * Most tick lines the intro column will accept.
 *
 * They sit between the heading and the button in a fixed-height column beside
 * the slider. Past five the button is pushed below the fold of the composition
 * on a laptop, which is the one thing in this section that has to stay visible.
 */
const KINDREDCARE_CAREGIVERS_MAX_BENEFITS = 5;

/**
 * Most cards the slider will accept.
 *
 * The slider peeks at 1.15 / 2.1 / 2.15 per view, so more cards cost nothing in
 * layout — but each one is a step in a sequence a caregiver reads end to end,
 * and the numbering and the "Step N of M" line grow with it.
 */
const KINDREDCARE_CAREGIVERS_MAX_CARDS = 8;

/**
 * The section's shipped defaults.
 *
 * The cards are drawn from the caregiver onboarding flow in final-thoughts.md
 * (146-189). Deliberately NOT service names, rates or tiers — the taxonomy
 * section already carries those, and a slider that repeats the page is worse
 * than no slider.
 *
 * @return array<string, mixed>
 */
function kindredcare_caregivers_defaults(): array {
	return array(
		'eyebrow'      => 'We\'re just getting started',
		'show_eyebrow' => true,
		'title'        => 'Be one of our founding caregivers.',
		'benefits'     => array(
			array( 'text' => 'Top placement in GTA while we\'re small', 'enabled' => true ),
			array( 'text' => 'We cover your first background check', 'enabled' => true ),
			array( 'text' => 'Set your own rate and choose your own gigs', 'enabled' => true ),
		),
		'cta_label'    => 'Apply to join',
		'cta_url'      => '#for-caregivers',
		'show_cta'     => true,
		'cards'     => array(
			array(
				'enabled' => true,
				'name'    => 'Build your profile',
				'text'    => 'Your languages, your interests, the hours you’re free and how far you’ll travel.',
				'meta'    => 'Languages · Availability',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'name'    => 'Get verified',
				'text'    => 'Photo ID, a police check, and any certificates you upload are read automatically.',
				'meta'    => 'ID · Police check',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'name'    => 'Publish your gigs',
				'text'    => 'One listing per service you offer. Draft it, publish it, or pause it whenever you like.',
				'meta'    => 'Draft · Published · Paused',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'name'    => 'Choose your bookings',
				'text'    => 'You see the family, the date and the address before you answer — then it’s your call.',
				'meta'    => 'Accept or decline',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'name'    => 'Check in on site',
				'text'    => 'Arrival confirmed by GPS, tasks logged as you go, check out when you’re done.',
				'meta'    => 'GPS-verified arrival',
				'icon'    => 0,
			),
			array(
				'enabled' => true,
				'name'    => 'Grow your standing',
				'text'    => 'Reviews and on-time arrivals lift your Trust Score, and that ranks your listings.',
				'meta'    => 'Reviews · On-time arrival',
				'icon'    => 0,
			),
		),
	);
}

/**
 * The card icons, in order.
 *
 * Hand-drawn per card and not editable from a list: each is a small
 * illustration of that specific step, so a picker would only ever offer the
 * wrong one. A card past the sixth reuses an earlier icon — see
 * kindredcare_caregivers_icon(). An uploaded image overrides it per card.
 *
 * @return string[] Inline SVG markup.
 */
function kindredcare_caregivers_icons(): array {
	return array(
		// 01 — Build your profile.
		'<svg class="c-gig-card__icon-svg" width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="10" r="2.6" stroke="currentColor" stroke-width="1.7"/><path d="M7.6 17.2a4.8 4.8 0 0 1 8.8 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',

		// 02 — Get verified.
		'<svg class="c-gig-card__icon-svg" width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 5 6.2v5.3c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6.2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2.2 2.2L15.4 10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',

		// 03 — Publish your gigs.
		'<svg class="c-gig-card__icon-svg" width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="16" height="7" rx="2" stroke="currentColor" stroke-width="1.7"/><rect x="4" y="14" width="10" height="6" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M17 17h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',

		// 04 — Choose your bookings.
		'<svg class="c-gig-card__icon-svg" width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 13h4l1.5 2.5h5L16 13h4" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M4 13 6.5 6h11L20 13v5a1.6 1.6 0 0 1-1.6 1.6H5.6A1.6 1.6 0 0 1 4 18z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',

		// 05 — Check in on site.
		'<svg class="c-gig-card__icon-svg" width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s6.5-6 6.5-10.5a6.5 6.5 0 0 0-13 0C5.5 15 12 21 12 21Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9.4 11 1.9 1.9 3.4-3.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',

		// 06 — Grow your standing.
		'<svg class="c-gig-card__icon-svg" width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 18.5 9 13l3.5 3L20 8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M15.5 8H20v4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
	);
}

/**
 * The icon for a card at a given position, cycling when they run out.
 *
 * @param int $index Zero-based card position.
 * @return string Inline SVG markup.
 */
function kindredcare_caregivers_icon( int $index ): string {
	$icons = kindredcare_caregivers_icons();

	return $icons[ $index % count( $icons ) ];
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_caregivers(): array {
	$defaults = kindredcare_caregivers_defaults();
	$stored   = get_option( KINDREDCARE_CAREGIVERS_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );

	/*
	 * The switches. Absent from an option saved before they existed, which must
	 * read as "shown" — that is what those installs are doing today, and a
	 * missing key is not a decision to hide anything.
	 */
	foreach ( array( 'show_eyebrow', 'show_cta' ) as $flag ) {
		$section[ $flag ] = ! isset( $stored[ $flag ] ) || (bool) $stored[ $flag ];
	}

	$benefits = array();
	if ( is_array( $stored['benefits'] ?? null ) && $stored['benefits'] ) {
		foreach ( array_values( $stored['benefits'] ) as $row ) {
			$row = kindredcare_caregivers_benefit( $row );
			if ( null !== $row ) {
				$benefits[] = $row;
			}
		}
	}
	$section['benefits'] = $benefits ?: $defaults['benefits'];

	$cards = array();
	if ( is_array( $stored['cards'] ?? null ) && $stored['cards'] ) {
		foreach ( array_values( $stored['cards'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$cards[] = array(
				'enabled' => ! isset( $row['enabled'] ) || (bool) $row['enabled'],
				'name'    => (string) ( $row['name'] ?? '' ),
				'text'    => (string) ( $row['text'] ?? '' ),
				'meta'    => (string) ( $row['meta'] ?? '' ),
				// 0 means "use the hand-drawn illustration for this position".
				'icon'    => absint( $row['icon'] ?? 0 ),
			);
		}
	}
	$section['cards'] = $cards ?: $defaults['cards'];

	return $section;
}

/**
 * Normalise one stored offer into its `text` / `enabled` pair.
 *
 * The list shipped as bare strings before each line could be switched off, and
 * an install saved under that shape must not lose its content on the first
 * read. A string is therefore read as a line that is shown — which is what it
 * meant when there was nothing else it could mean.
 *
 * @param mixed $row Stored row, either a string or a text/enabled pair.
 * @return array{text: string, enabled: bool}|null Null when the row is neither.
 */
function kindredcare_caregivers_benefit( $row ): ?array {
	if ( is_scalar( $row ) ) {
		return array(
			'text'    => (string) $row,
			'enabled' => true,
		);
	}

	if ( is_array( $row ) ) {
		return array(
			'text'    => is_scalar( $row['text'] ?? null ) ? (string) $row['text'] : '',
			'enabled' => ! empty( $row['enabled'] ),
		);
	}

	return null;
}

/**
 * The tick lines that will actually render.
 *
 * A line switched off is kept in the option — the editor is hiding it, not
 * deleting it — and simply does not reach the page.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, string>
 */
function kindredcare_caregivers_active_benefits( array $section ): array {
	$active = array();

	foreach ( $section['benefits'] as $row ) {
		$row = kindredcare_caregivers_benefit( $row );

		if ( null === $row || ! $row['enabled'] ) {
			continue;
		}

		$text = trim( $row['text'] );
		if ( '' !== $text ) {
			$active[] = $text;
		}
	}

	return $active;
}

/**
 * The cards that will actually render.
 *
 * A card needs to be switched on and to have a name — the name is the step, and
 * a numbered card with no heading reads as a loading state.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_caregivers_active_cards( array $section ): array {
	return array_values(
		array_filter(
			$section['cards'],
			static fn( array $card ): bool => ! empty( $card['enabled'] ) && '' !== trim( (string) $card['name'] )
		)
	);
}

/**
 * Render the "Founding caregivers" section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_caregivers(): void {
	$section  = kindredcare_caregivers();
	$benefits = kindredcare_caregivers_active_benefits( $section );
	$cards    = kindredcare_caregivers_active_cards( $section );
	$total    = count( $cards );
	?>
	<!-- ================================================================ -->
	<!-- 05 — Founding caregivers                                         -->
	<!-- At launch this slot does SUPPLY ACQUISITION, not liquidity proof: -->
	<!-- there are 0 listings and fabricating caregivers is forbidden.     -->
	<!-- ================================================================ -->
	<section class="l-section s-caregivers" aria-labelledby="founding-title">
		<div class="l-container">
			<!--
				Ambient layer: a few line icons drifting slowly behind the content.
				Decorative only — aria-hidden, pointer-events: none, and every
				animation is switched off under prefers-reduced-motion. Fixed
				ornament, so it is not editable.
			-->
			<div class="s-caregivers__deco" aria-hidden="true">
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20.3S4.2 15.6 4.2 10.3A4.8 4.8 0 0 1 12 6.6a4.8 4.8 0 0 1 7.8 3.7c0 5.3-7.8 10-7.8 10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 5 6.2v5.3c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6.2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2.2 2.2L15.4 10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.7"/><path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20v-7M12 13c0-4 3-7 7-7 0 4-3 7-7 7Zm0 0c0-4-3-7-7-7 0 4 3 7 7 7Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v10H9l-5 4z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 4.5 2.3 4.9 5.2.6-3.8 3.5 1 5.1-4.7-2.6-4.7 2.6 1-5.1L4.5 10l5.2-.6z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 13V6.5a1.6 1.6 0 0 1 3.2 0V12m0-1.5a1.6 1.6 0 0 1 3.2 0V13m0-1a1.6 1.6 0 0 1 3.2 0v4.5A4.5 4.5 0 0 1 13 21h-1.5A5 5 0 0 1 6.6 17L5 13.4a1.6 1.6 0 0 1 2.8-1.5L8 13" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
				<span class="s-caregivers__deco-icon"><svg class="s-caregivers__deco-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="5" width="16" height="11" rx="1.6" stroke="currentColor" stroke-width="1.7"/><path d="M3 19h18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
			</div>

			<div class="s-caregivers__layout">
				<div data-reveal="left">
					<?php
					/*
					 * Each piece answers to its own switch as well as to whether it
					 * has anything in it. Two ways to remove something, on purpose:
					 * clearing the field throws the wording away, the switch keeps
					 * it for later. The heading has no switch — it is the section's
					 * accessible name through aria-labelledby.
					 */
					?>
					<?php if ( $section['show_eyebrow'] && '' !== trim( (string) $section['eyebrow'] ) ) : ?>
						<span class="u-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></span>
					<?php endif; ?>
					<h2 class="c-section-head__title" id="founding-title">
						<?php echo esc_html( $section['title'] ); ?>
					</h2>

					<?php if ( $benefits ) : ?>
						<div class="s-caregivers__benefits">
							<?php foreach ( $benefits as $benefit ) : ?>
								<p class="s-caregivers__benefit">
									<svg class="s-caregivers__benefit-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
										<path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
									</svg>
									<?php echo esc_html( $benefit ); ?>
								</p>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<a class="c-btn c-btn--primary c-btn--lg" target="_blank" rel="noopener" href="<?php echo esc_url( 'https://app.kindredcare.ca/signup' ); ?>"><?php esc_html_e( 'Registration now', 'kindredcare' ); ?></a>
				</div>

				<div class="s-caregivers__slider" data-reveal="up">
					<div class="swiper" data-slider="gigs">
						<div class="swiper-wrapper">
							<?php
							/*
							 * The caregiver's own journey, drawn from the onboarding flow in
							 * final-thoughts.md (146-189). Deliberately NOT service names,
							 * rates or tiers — the taxonomy section already carries those, and
							 * a slider that repeats the page is worse than no slider. No card
							 * carries a name or a face either, which is what the "never
							 * fabricate a caregiver" rule exists to protect.
							 */
							?>
							<?php foreach ( $cards as $index => $card ) : ?>
								<div class="swiper-slide">
									<article class="c-gig-card">
										<div class="c-gig-card__top">
											<span class="c-gig-card__icon" aria-hidden="true">
												<?php
												$icon_id = (int) ( $card['icon'] ?? 0 );

												if ( $icon_id > 0 && wp_attachment_is_image( $icon_id ) ) {
													/*
													 * .c-gig-card__icon sizes its child through an
													 * `svg` rule, so an <img> would otherwise render
													 * at its intrinsic size. Sized inline rather than
													 * by adding a CSS rule: the stylesheet is
													 * generated from kindredcare-home/src/scss and an
													 * edit here would be overwritten by the next Sass
													 * build.
													 */
													echo wp_get_attachment_image(
														$icon_id,
														'full',
														false,
														array(
															'alt'   => '',
															'style' => 'display:block;width:26px;height:26px;object-fit:contain',
														)
													);
												} else {
													echo kindredcare_caregivers_icon( (int) $index ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Theme-authored inline SVG, not user input.
												}
												?>
											</span>
											<span class="c-gig-card__step" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', (int) $index + 1 ) ); ?></span>
										</div>

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

										<h3 class="c-gig-card__name"><?php echo esc_html( $card['name'] ); ?></h3>
										<?php if ( '' !== trim( (string) $card['text'] ) ) : ?>
											<p class="c-gig-card__headline"><?php echo esc_html( $card['text'] ); ?></p>
										<?php endif; ?>

										<?php if ( '' !== trim( (string) $card['meta'] ) ) : ?>
											<div class="c-gig-card__foot">
												<span class="c-gig-card__meta"><?php echo esc_html( $card['meta'] ); ?></span>
											</div>
										<?php endif; ?>
									</article>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="s-caregivers__controls">
						<div class="swiper-pagination" data-pagination="gigs"></div>

						<div class="c-slider-nav">
							<button class="c-slider-nav__btn" type="button" data-slider-prev="gigs" aria-label="<?php esc_attr_e( 'Previous', 'kindredcare' ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</button>
							<button class="c-slider-nav__btn" type="button" data-slider-next="gigs" aria-label="<?php esc_attr_e( 'Next', 'kindredcare' ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
}
