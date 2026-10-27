<?php
/**
 * "For caregivers" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_for_caregivers`, edited from
 * KindredCare -> For caregivers in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * This is THE one dark section on the page, and it is the tonal break that
 * says "this is a marketplace with two sides". A two-sided marketplace that
 * markets to one side does not start, and cold-start supply is the highest
 * severity risk in the strategy's own risk table. More than one dark section
 * and the paper concept collapses, so the colour is not editable.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_FOR_CAREGIVERS_OPTION = 'kindredcare_for_caregivers';

/**
 * Most tick lines the column will accept.
 *
 * The list sits between the big percentage and the button, opposite a
 * fixed-ratio photograph. Past eight the column outgrows the image beside it
 * and the button drops below the fold of the split.
 */
const KINDREDCARE_FOR_CAREGIVERS_MAX_ITEMS = 8;

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_for_caregivers_defaults(): array {
	return array(
		'eyebrow'      => 'For caregivers',
		'show_eyebrow' => true,
		'title'        => 'Set your rate. Choose your gigs. Keep 80%.',
		'figure'       => '80%',
		'figure_sub'   => 'of what the family pays goes to you. An agency keeps 30–40% of the same visit.',
		'show_figure'  => true,
		'items'        => array(
			array( 'text' => 'Work the hours you actually want', 'enabled' => true ),
			array( 'text' => 'Accept or decline every booking', 'enabled' => true ),
			array( 'text' => 'Paid automatically after each visit', 'enabled' => true ),
			array( 'text' => 'Build a Trust Score that ranks your listings', 'enabled' => true ),
			array( 'text' => 'No certification needed for companionship work', 'enabled' => true ),
			array( 'text' => 'Annual earnings statement for your taxes', 'enabled' => true ),
		),
		'cta_label'    => 'Join as a caregiver',
		'cta_url'      => '#final-cta',
		'show_cta'     => true,
		'image'        => 0,
		'image_alt'    => 'A caregiver visiting a client at home.',
	);
}

/**
 * The bundled photograph, theme-relative.
 */
function kindredcare_for_caregivers_bundled_image(): string {
	return 'assets/img/home-care.webp';
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_for_caregivers(): array {
	$defaults = kindredcare_for_caregivers_defaults();
	$stored   = get_option( KINDREDCARE_FOR_CAREGIVERS_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );

	/*
	 * The switches. Absent from an option saved before they existed, which must
	 * read as "shown" — that is what those installs are doing today, and a
	 * missing key is not a decision to hide anything.
	 */
	foreach ( array( 'show_eyebrow', 'show_figure', 'show_cta' ) as $flag ) {
		$section[ $flag ] = ! isset( $stored[ $flag ] ) || (bool) $stored[ $flag ];
	}

	$items = array();
	if ( is_array( $stored['items'] ?? null ) && $stored['items'] ) {
		foreach ( array_values( $stored['items'] ) as $row ) {
			$row = kindredcare_for_caregivers_item( $row );
			if ( null !== $row ) {
				$items[] = $row;
			}
		}
	}
	$section['items'] = $items ?: $defaults['items'];
	$section['image'] = absint( $section['image'] );

	return $section;
}

/**
 * Normalise one stored line into its `text` / `enabled` pair.
 *
 * The list shipped as bare strings before each line could be switched off, and
 * an install saved under that shape must not lose its content on the first
 * read. A string is therefore read as a line that is shown — which is what it
 * meant when there was nothing else it could mean.
 *
 * @param mixed $row Stored row, either a string or a text/enabled pair.
 * @return array{text: string, enabled: bool}|null Null when the row is neither.
 */
function kindredcare_for_caregivers_item( $row ): ?array {
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
 * @param array<string, mixed> $section Section content.
 * @return array<int, string>
 */
function kindredcare_for_caregivers_active_items( array $section ): array {
	$active = array();

	foreach ( $section['items'] as $row ) {
		$row = kindredcare_for_caregivers_item( $row );

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
 * Render the "For caregivers" section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_for_caregivers(): void {
	$section = kindredcare_for_caregivers();
	$items   = kindredcare_for_caregivers_active_items( $section );
	$image   = (int) $section['image'];
	?>
	<!-- ================================================================ -->
	<!-- 09 — For caregivers. THE one dark section.                       -->
	<!-- A two-sided marketplace that markets to one side does not start.  -->
	<!-- ================================================================ -->
	<section class="l-section s-for-caregivers" id="for-caregivers" aria-labelledby="cg-title">
		<div class="l-container">
			<div class="s-for-caregivers__layout">
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
					<h2 class="c-section-head__title c-section-head--inverse" id="cg-title" style="color: var(--c-ink-inverse)">
						<?php echo esc_html( $section['title'] ); ?>
					</h2>

					<?php if ( $section['show_figure'] && '' !== trim( (string) $section['figure'] ) ) : ?>
						<p class="s-for-caregivers__figure">
							<?php echo esc_html( $section['figure'] ); ?>
							<?php if ( '' !== trim( (string) $section['figure_sub'] ) ) : ?>
								<span class="s-for-caregivers__figure-sub">
									<?php echo esc_html( $section['figure_sub'] ); ?>
								</span>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<?php if ( $items ) : ?>
						<div class="s-for-caregivers__list">
							<?php foreach ( $items as $item ) : ?>
								<p class="s-for-caregivers__item">
									<svg class="s-for-caregivers__icon" width="16" height="16" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" /></svg>
									<?php echo esc_html( $item ); ?>
								</p>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="s-for-caregivers__media" data-reveal="up">
					<?php
					if ( $image > 0 && wp_attachment_is_image( $image ) ) {
						/*
						 * Dimensions come from the media library rather than being
						 * hardcoded, so a replacement of a different shape does not
						 * reserve the wrong box and cost CLS.
						 */
						echo wp_get_attachment_image(
							$image,
							'full',
							false,
							array(
								'class'    => 's-for-caregivers__img',
								'alt'      => $section['image_alt'],
								'loading'  => 'lazy',
								'decoding' => 'async',
							)
						);
					} else {
						?>
						<img
							class="s-for-caregivers__img"
							src="<?php echo esc_url( get_theme_file_uri( kindredcare_for_caregivers_bundled_image() ) ); ?>"
							alt="<?php echo esc_attr( $section['image_alt'] ); ?>"
							width="1500"
							height="952"
							loading="lazy"
							decoding="async"
						/>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</section>
	<?php
}
