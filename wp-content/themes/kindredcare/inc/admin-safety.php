<?php
/**
 * Admin: KindredCare -> During the visit.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_SAFETY_MENU_SLUG = 'kindredcare-safety';

/**
 * Capability callback for this option group.
 */
function kindredcare_safety_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 17: after Verification tiers (16), matching the page order. */
add_action( 'admin_menu', 'kindredcare_safety_admin_menu', 17 );
/**
 * Add the During the visit screen under the KindredCare menu.
 */
function kindredcare_safety_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'During the visit', 'kindredcare' ),
		__( 'During the visit', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_SAFETY_MENU_SLUG,
		'kindredcare_safety_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_safety_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_safety_register_setting(): void {
	register_setting(
		'kindredcare_safety_group',
		KINDREDCARE_SAFETY_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_safety_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_safety_group', 'kindredcare_safety_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_safety_sanitize( $input ): array {
	$defaults = kindredcare_safety_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'       => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'         => sanitize_text_field( $input['title'] ?? '' ),
		'items'         => array(),
		'visual_title'  => sanitize_text_field( $input['visual_title'] ?? '' ),
		'visual_chip'   => sanitize_text_field( $input['visual_chip'] ?? '' ),
		'visual_events' => array(),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	$rows = is_array( $input['items'] ?? null ) ? array_values( $input['items'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['items'] ) >= KINDREDCARE_SAFETY_MAX_ITEMS ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$title = sanitize_text_field( $row['title'] ?? '' );

		// An icon with no heading beside it reads as a loading state.
		if ( '' === trim( $title ) ) {
			continue;
		}

		$clean['items'][] = array(
			'enabled' => ! empty( $row['enabled'] ),
			'title'   => $title,
			'text'    => sanitize_textarea_field( $row['text'] ?? '' ),
			// 0 falls back to the hand-drawn illustration for that position.
			'icon'    => absint( $row['icon'] ?? 0 ),
		);
	}

	$events = is_array( $input['visual_events'] ?? null ) ? array_values( $input['visual_events'] ) : array();

	foreach ( $events as $row ) {
		if ( count( $clean['visual_events'] ) >= KINDREDCARE_SAFETY_MAX_EVENTS ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$text = sanitize_text_field( $row['text'] ?? '' );

		// A time with nothing beside it is a gap in the story the card tells.
		if ( '' === trim( $text ) ) {
			continue;
		}

		$clean['visual_events'][] = array(
			'time' => sanitize_text_field( $row['time'] ?? '' ),
			'text' => $text,
		);
	}

	if ( ! $clean['items'] ) {
		$clean['items'] = $defaults['items'];
	}

	if ( ! $clean['visual_events'] ) {
		$clean['visual_events'] = $defaults['visual_events'];
	}

	// An empty list would leave the column as a heading beside a timeline card.
	$any_enabled = false;
	foreach ( $clean['items'] as $item ) {
		$any_enabled = $any_enabled || $item['enabled'];
	}
	if ( ! $any_enabled ) {
		$clean['items'][0]['enabled'] = true;
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_safety_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_safety_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_SAFETY_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one feature row, as a collapsed accordion.
 *
 * @param int                  $index    Row position.
 * @param array<string, mixed> $item     Row data.
 * @param bool                 $expanded Whether to render it open.
 */
function kindredcare_safety_item_row( int $index, array $item, bool $expanded = false ): void {
	$option  = KINDREDCARE_SAFETY_OPTION;
	$base    = $option . '[items][' . $index . ']';
	$body_id = 'kc-safety-' . $index . '-body';
	?>
	<div class="kc-repeat__item kc-acc <?php echo empty( $item['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
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
					data-empty="<?php esc_attr_e( 'Untitled feature', 'kindredcare' ); ?>"
				><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></span>
			</button>

			<div class="kc-repeat__tools">
				<?php
				kindredcare_admin_toggle(
					$base . '[enabled]',
					! empty( $item['enabled'] ),
					__( 'Shown', 'kindredcare' ),
					array( 'kc-toggle-row' => '1' )
				);

				kindredcare_admin_icon_button(
					'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
					__( 'Remove this feature', 'kindredcare' ),
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

		<div class="kc-acc__body" id="<?php echo esc_attr( $body_id ); ?>" <?php echo $expanded ? '' : 'hidden'; ?>>
			<?php
			kindredcare_admin_media_field(
				'kc-safety-' . $index . '-icon',
				$base . '[icon]',
				(int) ( $item['icon'] ?? 0 ),
				'',
				array(
					'empty_note'  => __( 'Using the built-in drawing', 'kindredcare' ),
					'reset_label' => __( 'Clear — go back to the built-in drawing', 'kindredcare' ),
				)
			);
			?>

			<div class="kc-repeat__main">
				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-safety-<?php echo (int) $index; ?>-title">
						<?php esc_html_e( 'Feature', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-safety-<?php echo (int) $index; ?>-title"
						name="<?php echo esc_attr( $base ); ?>[title]"
						value="<?php echo esc_attr( (string) ( $item['title'] ?? '' ) ); ?>"
						data-kc-acc-label
					/>
				</div>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-safety-<?php echo (int) $index; ?>-text">
						<?php esc_html_e( 'Description', 'kindredcare' ); ?>
					</label>
					<textarea
						id="kc-safety-<?php echo (int) $index; ?>-text"
						name="<?php echo esc_attr( $base ); ?>[text]"
						rows="3"
					><?php echo esc_textarea( (string) ( $item['text'] ?? '' ) ); ?></textarea>
					<p class="kc-field__hint"><?php esc_html_e( 'One or two sentences. Describe what the family sees, not what the system does.', 'kindredcare' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Print one timeline row.
 *
 * @param int                   $index Row position.
 * @param array<string, string> $event Row data.
 */
function kindredcare_safety_event_row( int $index, array $event ): void {
	$option = KINDREDCARE_SAFETY_OPTION;
	$base   = $option . '[visual_events][' . $index . ']';
	?>
	<div class="kc-lines__row" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $base ); ?>[time]"
			value="<?php echo esc_attr( (string) ( $event['time'] ?? '' ) ); ?>"
			aria-label="<?php esc_attr_e( 'Time', 'kindredcare' ); ?>"
			style="flex:0 0 130px"
		/>
		<input
			type="text"
			name="<?php echo esc_attr( $base ); ?>[text]"
			value="<?php echo esc_attr( (string) ( $event['text'] ?? '' ) ); ?>"
			aria-label="<?php esc_attr_e( 'What happened', 'kindredcare' ); ?>"
		/>
		<?php
		kindredcare_admin_icon_button(
			'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
			__( 'Remove this row', 'kindredcare' ),
			array( 'kc-remove' => '1' ),
			true
		);
		?>
	</div>
	<?php
}

/**
 * Render the During the visit admin screen.
 */
function kindredcare_safety_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_safety();
	$option  = KINDREDCARE_SAFETY_OPTION;
	$items   = $section['items'];
	$events  = $section['visual_events'];
	$active  = count( kindredcare_safety_active_items( $section ) );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'During the visit', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'What a family sees while the visit is happening. Section 07 covers who we let in; this covers what happens once they are inside. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#safety' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_safety_group' ); ?>

			<!-- Heading --------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'Sits above the feature list, on the left half of the split.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( '<strong>This is a transparency feature, not a compliance one.</strong> Electronic visit verification is not legally mandated in any Canadian province, so nothing here may say or imply that GPS check-in is required by law or by a regulator. It is here because families want to know their parent is not sitting alone waiting.', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-safety-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-safety-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-safety-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-safety-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<!-- Features -------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M5 6h14M5 12h14M5 18h14' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'What happens', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of features. */
								esc_html__( 'Up to %d, listed down the left column beside the example card.', 'kindredcare' ),
								(int) KINDREDCARE_SAFETY_MAX_ITEMS
							);
							?>
						</p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						sprintf(
							/* translators: 1: recommended pixel size, 2: rendered size, 3: number of built-in icons. */
							__( '<strong>Best icon size: %1$s</strong> — square, transparent PNG. It draws at %2$s, so twice that keeps it sharp on retina screens. Leave the picker empty to keep the built-in drawing for that position; there are %3$d of them and a later row reuses an earlier one. WordPress blocks SVG uploads unless a plugin allows them.', 'kindredcare' ),
							'<code>40 × 40</code>',
							'<code>20 × 20</code>',
							count( kindredcare_safety_icons() )
						)
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="items"
						data-kc-max="<?php echo (int) KINDREDCARE_SAFETY_MAX_ITEMS; ?>"
						data-kc-min="1"
					>
						<div data-kc-list>
							<?php foreach ( $items as $index => $item ) : ?>
								<?php kindredcare_safety_item_row( (int) $index, $item ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add feature', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%1$s of %2$s features shown · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: features shown, 2: features total, 3: maximum allowed. */
									esc_html__( '%1$s of %2$s features shown · %3$s maximum', 'kindredcare' ),
									(int) $active,
									count( $items ),
									(int) KINDREDCARE_SAFETY_MAX_ITEMS
								);
								?>
							</span>
						</div>

						<template data-kc-template>
							<?php
							kindredcare_safety_item_row(
								0,
								array(
									'enabled' => true,
									'title'   => '',
									'text'    => '',
									'icon'    => 0,
								)
							);
							?>
						</template>
					</div>
				</div>
			</section>

			<!-- Example card ---------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 5h16v14H4zM8 10h8M8 14h5' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Example visit card', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The card on the right, showing one visit from check-in to payment.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( '<strong>This card is an illustration, not a real visit.</strong> Keep it obviously generic — a first name and a weekday, never a real client, a real caregiver or a real address. If the figures change, keep the arithmetic honest: the platform fee shown should be 7.5% of the amount paid, because someone will check.', 'kindredcare' )
					);
					?>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-safety-visual-title"><?php esc_html_e( 'Card heading', 'kindredcare' ); ?></label>
							<input type="text" id="kc-safety-visual-title" name="<?php echo esc_attr( $option ); ?>[visual_title]" value="<?php echo esc_attr( $section['visual_title'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-safety-visual-chip"><?php esc_html_e( 'Card badge', 'kindredcare' ); ?></label>
							<input type="text" id="kc-safety-visual-chip" name="<?php echo esc_attr( $option ); ?>[visual_chip]" value="<?php echo esc_attr( $section['visual_chip'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'One word. Leave blank to remove it.', 'kindredcare' ); ?></p>
						</div>
					</div>

					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'Timeline', 'kindredcare' ); ?></span>

						<div
							class="kc-repeat"
							data-kc-repeat="visual_events"
							data-kc-max="<?php echo (int) KINDREDCARE_SAFETY_MAX_EVENTS; ?>"
							data-kc-min="1"
						>
							<div class="kc-lines" data-kc-list>
								<?php foreach ( $events as $index => $event ) : ?>
									<?php kindredcare_safety_event_row( (int) $index, (array) $event ); ?>
								<?php endforeach; ?>
							</div>

							<div class="kc-lines__foot">
								<?php kindredcare_admin_add_button( __( 'Add row', 'kindredcare' ) ); ?>
								<span
									class="kc-repeat__count"
									data-kc-count
									data-template="<?php esc_attr_e( '%2$s rows · %3$s maximum', 'kindredcare' ); ?>"
								>
									<?php
									printf(
										/* translators: 1: rows total, 2: maximum allowed. */
										esc_html__( '%1$s rows · %2$s maximum', 'kindredcare' ),
										count( $events ),
										(int) KINDREDCARE_SAFETY_MAX_EVENTS
									);
									?>
								</span>
							</div>

							<template data-kc-template>
								<?php
								kindredcare_safety_event_row(
									0,
									array(
										'time' => '',
										'text' => '',
									)
								);
								?>
							</template>
						</div>

						<p class="kc-field__hint"><?php esc_html_e( 'Time on the left, what happened on the right. A row with no description is dropped when saved.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save during the visit', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
