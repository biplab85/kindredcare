<?php
/**
 * Final CTA — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_final_cta`, edited from
 * KindredCare -> Final CTA in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * Plan section 13. The last thing on the page, and the only place both sides of
 * the marketplace are offered the same weight of button — a family that has read
 * this far and a caregiver that has read this far both have somewhere to go.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_FINAL_CTA_OPTION = 'kindredcare_final_cta';

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_final_cta_defaults(): array {
	return array(
		'eyebrow'         => 'Ready when you are',
		'title'           => 'Find someone kind, this week.',
		'lead'            => 'Browsing is free and takes a few minutes. You will see real people, real rates and real verification before you decide anything.',
		'primary_label'   => 'Registration now',
		'primary_url'     => 'https://app.kindredcare.ca/signup',
		'secondary_label' => '',
		'secondary_url'   => '',
		// Null until saved with the switches: see kindredcare_link_new_tab().
		'primary_new_tab'   => null,
		'secondary_new_tab' => null,
	);
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_final_cta(): array {
	$defaults = kindredcare_final_cta_defaults();
	$stored   = get_option( KINDREDCARE_FINAL_CTA_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	return array_merge( $defaults, $stored );
}

/**
 * Render the final CTA.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_final_cta(): void {
	$section = kindredcare_final_cta();

	$eyebrow = trim( (string) $section['eyebrow'] );
	$lead    = trim( (string) $section['lead'] );

	/*
	 * A button needs both halves to be worth printing: a label with no
	 * destination is a dead control, and a destination with no label is an
	 * invisible one.
	 */
	$primary_label   = trim( (string) $section['primary_label'] );
	$primary_url     = trim( (string) $section['primary_url'] );
	$secondary_label = trim( (string) $section['secondary_label'] );
	$secondary_url   = trim( (string) $section['secondary_url'] );

	$has_primary   = '' !== $primary_label && '' !== $primary_url;
	$has_secondary = '' !== $secondary_label && '' !== $secondary_url;
	?>
	<!-- ================================================================ -->
	<!-- 13 — Final CTA                                                   -->
	<!-- ================================================================ -->
	<section class="l-section s-final" id="final-cta" aria-labelledby="final-title">
		<div class="l-container">
			<div class="s-final__panel" data-reveal="up">
				<?php if ( '' !== $eyebrow ) : ?>
					<span class="u-eyebrow s-final__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>

				<h2 class="s-final__title" id="final-title">
					<?php echo esc_html( $section['title'] ); ?>
				</h2>

				<?php if ( '' !== $lead ) : ?>
					<p class="s-final__lead">
						<?php echo esc_html( $lead ); ?>
					</p>
				<?php endif; ?>

				<?php if ( $has_primary || $has_secondary ) : ?>
					<div class="s-final__actions">
						<?php if ( $has_primary ) : ?>
							<?php
							// The editor's switch; until it is first saved, an anchor stays in the
							// tab and a full URL opens a new one, as elsewhere on the page.
							$kc_primary_new_tab = kindredcare_link_new_tab( $section['primary_new_tab'] ?? null, $primary_url, 'external' );
							?>
							<a class="c-btn c-btn--primary c-btn--lg"<?php echo kindredcare_link_target( $kc_primary_new_tab ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed attribute string. ?> href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $primary_label ); ?><?php kindredcare_link_new_tab_hint( $kc_primary_new_tab ); ?></a>
						<?php endif; ?>

						<?php if ( $has_secondary ) : ?>
							<?php $kc_secondary_new_tab = kindredcare_link_new_tab( $section['secondary_new_tab'] ?? null, $secondary_url ); ?>
							<a class="c-btn c-btn--ink c-btn--lg"<?php echo kindredcare_link_target( $kc_secondary_new_tab ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed attribute string. ?> href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?><?php kindredcare_link_new_tab_hint( $kc_secondary_new_tab ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}
