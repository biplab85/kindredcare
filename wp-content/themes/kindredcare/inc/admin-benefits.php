<?php
/**
 * Admin: KindredCare -> Benefits.
 *
 * Same Settings API arrangement as the Hero, Services and How it works screens,
 * and the same furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_BENEFITS_MENU_SLUG = 'kindredcare-benefits';

/**
 * Capability callback for this option group.
 */
function kindredcare_benefits_capability(): string {
	return kindredcare_admin_capability();
}

/*
 * Priority 14: after Hero (10), Services (11), How it works (12) and Taxonomy
 * (13), so the submenu reads in the order the sections appear on the page.
 */
add_action( 'admin_menu', 'kindredcare_benefits_admin_menu', 14 );
/**
 * Add the Benefits screen under the KindredCare menu.
 */
function kindredcare_benefits_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Benefits', 'kindredcare' ),
		__( 'Benefits', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_BENEFITS_MENU_SLUG,
		'kindredcare_benefits_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_benefits_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_benefits_register_setting(): void {
	register_setting(
		'kindredcare_benefits_group',
		KINDREDCARE_BENEFITS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_benefits_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_benefits_group', 'kindredcare_benefits_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_benefits_sanitize( $input ): array {
	$defaults = kindredcare_benefits_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'title'      => sanitize_text_field( $input['title'] ?? '' ),
		'portrait'   => absint( $input['portrait'] ?? 0 ),
		'card_image' => absint( $input['card_image'] ?? 0 ),
		'card_text'  => sanitize_textarea_field( $input['card_text'] ?? '' ),
		'items'      => array(),
		'counters'   => array(),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	$lines = is_array( $input['items'] ?? null ) ? array_values( $input['items'] ) : array();

	foreach ( $lines as $line ) {
		if ( count( $clean['items'] ) >= KINDREDCARE_BENEFITS_MAX_ITEMS ) {
			break;
		}
		if ( ! is_scalar( $line ) ) {
			continue;
		}

		$line = sanitize_text_field( (string) $line );

		// A blank chip renders as an empty bullet, so it is dropped rather than kept.
		if ( '' === trim( $line ) ) {
			continue;
		}

		$clean['items'][] = $line;
	}

	$rows = is_array( $input['counters'] ?? null ) ? array_values( $input['counters'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['counters'] ) >= KINDREDCARE_BENEFITS_MAX_COUNTERS ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$value = sanitize_text_field( $row['value'] ?? '' );

		// The figure is the counter; a label with no number above it is not a stat.
		if ( '' === trim( $value ) ) {
			continue;
		}

		$clean['counters'][] = array(
			'enabled' => ! empty( $row['enabled'] ),
			'value'   => $value,
			'label'   => sanitize_text_field( $row['label'] ?? '' ),
		);
	}

	/*
	 * Both lists fall back rather than saving empty. The card without its list
	 * is a photograph and a sentence, and the amber rail with no counters is a
	 * block of flat colour beside the card — neither is a state worth being one
	 * mis-save away from.
	 */
	if ( ! $clean['items'] ) {
		$clean['items'] = $defaults['items'];
	}

	if ( ! $clean['counters'] ) {
		$clean['counters'] = $defaults['counters'];
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_benefits_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_benefits_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_BENEFITS_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one list-item line.
 *
 * @param string $option Option name.
 * @param int    $index  Row position.
 * @param string $value  Current text.
 */
function kindredcare_benefits_item_row( string $option, int $index, string $value ): void {
	?>
	<div class="kc-lines__row" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $option ); ?>[items][<?php echo (int) $index; ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			aria-label="<?php esc_attr_e( 'Reason', 'kindredcare' ); ?>"
		/>
		<?php
		kindredcare_admin_icon_button(
			'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
			__( 'Remove this reason', 'kindredcare' ),
			array( 'kc-remove' => '1' ),
			true
		);
		?>
	</div>
	<?php
}

/**
 * Print one counter row, as a collapsed accordion.
 *
 * @param int                  $index    Row position.
 * @param array<string, mixed> $counter  Row data.
 * @param bool                 $expanded Whether to render it open.
 */
function kindredcare_benefits_counter_row( int $index, array $counter, bool $expanded = false ): void {
	$option  = KINDREDCARE_BENEFITS_OPTION;
	$base    = $option . '[counters][' . $index . ']';
	$body_id = 'kc-bcount-' . $index . '-body';
	$parts   = kindredcare_benefits_counter_parts( (string) ( $counter['value'] ?? '' ) );
	?>
	<div class="kc-repeat__item kc-acc <?php echo empty( $counter['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
		<div class="kc-acc__head">
			<button
				type="button"
				class="kc-acc__toggle"
				aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( $body_id ); ?>"
				data-kc-acc-toggle
			>
				<span class="kc-repeat__num"><?php echo (int) $index + 1; ?></span>
				<span
					class="kc-acc__title"
					data-kc-acc-title
					data-empty="<?php esc_attr_e( 'Unlabelled figure', 'kindredcare' ); ?>"
				><?php echo esc_html( (string) ( $counter['label'] ?? '' ) ); ?></span>
			</button>

			<div class="kc-repeat__tools">
				<?php
				kindredcare_admin_toggle(
					$base . '[enabled]',
					! empty( $counter['enabled'] ),
					__( 'Shown', 'kindredcare' ),
					array( 'kc-toggle-row' => '1' )
				);

				kindredcare_admin_icon_button(
					'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
					__( 'Remove this figure', 'kindredcare' ),
					array( 'kc-remove' => '1' ),
					true
				);
				?>
				<button
					type="button"
					class="kc-acc__chevron"
					tabindex="-1"
					aria-hidden="true"
					data-kc-acc-chevron
				>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m6 9 6 6 6-6" />
					</svg>
				</button>
			</div>
		</div>

		<div class="kc-acc__body kc-acc__body--full" id="<?php echo esc_attr( $body_id ); ?>" <?php echo $expanded ? '' : 'hidden'; ?>>
			<div class="kc-field kc-field--split" style="margin:0">
				<div>
					<label class="kc-field__label" for="kc-bcount-<?php echo (int) $index; ?>-value">
						<?php esc_html_e( 'Figure', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-bcount-<?php echo (int) $index; ?>-value"
						name="<?php echo esc_attr( $base ); ?>[value]"
						value="<?php echo esc_attr( (string) ( $counter['value'] ?? '' ) ); ?>"
					/>
					<p class="kc-field__hint">
						<?php
						if ( null !== $parts['to'] ) {
							printf(
								/* translators: 1: number counted to, 2: decimal places. */
								esc_html__( 'Counts up to %1$s with %2$s decimal place(s). Anything around the number is kept exactly as typed.', 'kindredcare' ),
								'<code>' . esc_html( (string) $parts['to'] ) . '</code>',
								'<code>' . esc_html( (string) $parts['decimals'] ) . '</code>'
							);
						} else {
							esc_html_e( 'No digits here, so this one renders as plain text with no count-up.', 'kindredcare' );
						}
						?>
					</p>
				</div>
				<div>
					<label class="kc-field__label" for="kc-bcount-<?php echo (int) $index; ?>-label">
						<?php esc_html_e( 'Label', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-bcount-<?php echo (int) $index; ?>-label"
						name="<?php echo esc_attr( $base ); ?>[label]"
						value="<?php echo esc_attr( (string) ( $counter['label'] ?? '' ) ); ?>"
						data-kc-acc-label
					/>
					<p class="kc-field__hint"><?php esc_html_e( 'One short line under the figure.', 'kindredcare' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the Benefits admin screen.
 */
function kindredcare_benefits_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section  = kindredcare_benefits();
	$option   = KINDREDCARE_BENEFITS_OPTION;
	$items    = $section['items'];
	$counters = $section['counters'];
	$active   = count( kindredcare_benefits_active_counters( $section ) );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Benefits', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The case for booking a caregiver directly rather than through an agency — heading and portrait, a bordered card of reasons, and the amber figure rail. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_benefits_group' ); ?>

			<!-- Heading and portrait ------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading and portrait', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The left-hand column. Below the tablet breakpoint the portrait moves to the foot of the section.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<label class="kc-field__label" for="kc-benefits-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-benefits-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'Portrait', 'kindredcare' ); ?></span>
						<?php
						kindredcare_admin_media_field(
							'kc-benefits-portrait',
							$option . '[portrait]',
							(int) $section['portrait'],
							kindredcare_benefits_bundled_image( 'portrait' )
						);

						kindredcare_admin_note(
							sprintf(
								/* translators: 1: recommended pixel size, 2: rendered size. */
								__( '<strong>Best size: %1$s</strong> — upright, and cut out against a transparent background if you can. It draws at %2$s, so twice that keeps it sharp on retina screens. It sits on top of the amber blob, so a photograph carrying its own white box will show that box. The blob and the small spinning shape behind it are fixed ornament and are not editable.', 'kindredcare' ),
								'<code>612 × 778</code>',
								'<code>306 × 389</code>'
							)
						);
						?>
					</div>
				</div>
			</section>

			<!-- The card -------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 5h16v14H4zM4 10h16' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Reasons card', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The bordered card in the middle column: a photograph, a sentence, then the list.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'Card photograph', 'kindredcare' ); ?></span>
						<?php
						kindredcare_admin_media_field(
							'kc-benefits-card-image',
							$option . '[card_image]',
							(int) $section['card_image'],
							kindredcare_benefits_bundled_image( 'card_image' )
						);

						kindredcare_admin_note(
							sprintf(
								/* translators: 1: recommended pixel size, 2: aspect ratio. */
								__( '<strong>Best size: %1$s</strong> — landscape, roughly %2$s. It is cropped to fill the top of the card, not letterboxed, so anything important should sit near the middle.', 'kindredcare' ),
								'<code>1125 × 601</code>',
								'<code>1.87 : 1</code>'
							)
						);
						?>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-benefits-card-text"><?php esc_html_e( 'Card sentence', 'kindredcare' ); ?></label>
						<textarea id="kc-benefits-card-text" name="<?php echo esc_attr( $option ); ?>[card_text]" rows="3"><?php echo esc_textarea( $section['card_text'] ); ?></textarea>
						<p class="kc-field__hint"><?php esc_html_e( 'One or two sentences introducing the list. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'Reasons', 'kindredcare' ); ?></span>

						<div
							class="kc-repeat"
							data-kc-repeat="items"
							data-kc-max="<?php echo (int) KINDREDCARE_BENEFITS_MAX_ITEMS; ?>"
							data-kc-min="1"
						>
							<div class="kc-lines" data-kc-list>
								<?php foreach ( $items as $index => $item ) : ?>
									<?php kindredcare_benefits_item_row( $option, (int) $index, (string) $item ); ?>
								<?php endforeach; ?>
							</div>

							<div class="kc-lines__foot">
								<?php kindredcare_admin_add_button( __( 'Add reason', 'kindredcare' ) ); ?>
								<span
									class="kc-repeat__count"
									data-kc-count
									data-template="<?php esc_attr_e( '%2$s reasons · %3$s maximum', 'kindredcare' ); ?>"
								>
									<?php
									printf(
										/* translators: 1: reasons total, 2: maximum allowed. */
										esc_html__( '%1$s reasons · %2$s maximum', 'kindredcare' ),
										count( $items ),
										(int) KINDREDCARE_BENEFITS_MAX_ITEMS
									);
									?>
								</span>
							</div>

							<template data-kc-template>
								<?php kindredcare_benefits_item_row( $option, 0, '' ); ?>
							</template>
						</div>

						<p class="kc-field__hint"><?php esc_html_e( 'Two or three words each — they are chips, not sentences. A blank line is dropped when saved.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<!-- Counters -------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M5 19V9M12 19V5M19 19v-6' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Figures', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of figures. */
								esc_html__( 'The amber rail on the right. Up to %d, stacked with a hairline between them.', 'kindredcare' ),
								(int) KINDREDCARE_BENEFITS_MAX_COUNTERS
							);
							?>
						</p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( '<strong>Every figure here has to be true and sourced.</strong> Type what should appear on screen — <code>7.5%</code>, <code>5</code>, <code>$25</code> — and the count-up works itself out from that, so what animates is always what you typed. The reference layout this follows fills these slots with <em>20k+ happy customers</em> and <em>100% home safety</em>; publishing a number we cannot stand behind is what produced <code>0% Verified care-giver</code> on the live site.', 'kindredcare' )
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="counters"
						data-kc-max="<?php echo (int) KINDREDCARE_BENEFITS_MAX_COUNTERS; ?>"
						data-kc-min="1"
					>
						<div data-kc-list>
							<?php foreach ( $counters as $index => $counter ) : ?>
								<?php kindredcare_benefits_counter_row( (int) $index, $counter ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add figure', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%1$s of %2$s figures shown · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: figures shown, 2: figures total, 3: maximum allowed. */
									esc_html__( '%1$s of %2$s figures shown · %3$s maximum', 'kindredcare' ),
									(int) $active,
									count( $counters ),
									(int) KINDREDCARE_BENEFITS_MAX_COUNTERS
								);
								?>
							</span>
						</div>

						<template data-kc-template>
							<?php
							kindredcare_benefits_counter_row(
								0,
								array(
									'enabled' => true,
									'value'   => '',
									'label'   => '',
								)
							);
							?>
						</template>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save benefits', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
