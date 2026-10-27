<?php
/**
 * Admin: KindredCare -> How it works.
 *
 * Same Settings API arrangement as the Hero and Services screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_HOW_MENU_SLUG = 'kindredcare-how';

/**
 * Capability callback for this option group.
 */
function kindredcare_how_capability(): string {
	return kindredcare_admin_capability();
}

/*
 * Priority 14: after Hero (10, which creates the parent), Trust strip (11), Our
 * story (12) and Services (13), so the submenu reads in the order the sections
 * appear on the page.
 */
add_action( 'admin_menu', 'kindredcare_how_admin_menu', 14 );
/**
 * Add the How it works screen under the KindredCare menu.
 */
function kindredcare_how_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'How it works', 'kindredcare' ),
		__( 'How it works', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_HOW_MENU_SLUG,
		'kindredcare_how_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_how_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_how_register_setting(): void {
	register_setting(
		'kindredcare_how_group',
		KINDREDCARE_HOW_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_how_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_how_group', 'kindredcare_how_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_how_sanitize( $input ): array {
	$defaults = kindredcare_how_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'   => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'     => sanitize_text_field( $input['title'] ?? '' ),
		'lead'      => sanitize_textarea_field( $input['lead'] ?? '' ),
		'steps'     => array(),
		'cta_label' => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'   => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'show_cta'  => ! empty( $input['show_cta'] ),
		'cta_new_tab' => ! empty( $input['cta_new_tab'] ),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	$rows = is_array( $input['steps'] ?? null ) ? array_values( $input['steps'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['steps'] ) >= KINDREDCARE_HOW_MAX_STEPS ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$title = sanitize_text_field( $row['title'] ?? '' );

		// A numbered medallion with no heading beside it reads as a loading state.
		if ( '' === trim( $title ) ) {
			continue;
		}

		$clean['steps'][] = array(
			'enabled' => ! empty( $row['enabled'] ),
			'title'   => $title,
			'text'    => sanitize_textarea_field( $row['text'] ?? '' ),
			// 0 falls back to the hand-drawn illustration for that position.
			'icon'    => absint( $row['icon'] ?? 0 ),
		);
	}

	if ( ! $clean['steps'] ) {
		$clean['steps'] = $defaults['steps'];
	}

	// An empty sequence would leave the timeline with nothing to draw between.
	$any_enabled = false;
	foreach ( $clean['steps'] as $step ) {
		$any_enabled = $any_enabled || $step['enabled'];
	}
	if ( ! $any_enabled ) {
		$clean['steps'][0]['enabled'] = true;
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_how_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_how_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_HOW_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one step row, as a collapsed accordion.
 *
 * @param int                  $index    Row position.
 * @param array<string, mixed> $step     Row data.
 * @param bool                 $expanded Whether to render it open.
 */
function kindredcare_how_step_row( int $index, array $step, bool $expanded = false ): void {
	$option  = KINDREDCARE_HOW_OPTION;
	$base    = $option . '[steps][' . $index . ']';
	$body_id = 'kc-step-' . $index . '-body';
	?>
	<div class="kc-repeat__item kc-acc <?php echo empty( $step['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
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
					data-empty="<?php esc_attr_e( 'Untitled step', 'kindredcare' ); ?>"
				><?php echo esc_html( $step['title'] ); ?></span>
			</button>

			<div class="kc-repeat__tools">
				<?php
				kindredcare_admin_toggle(
					$base . '[enabled]',
					! empty( $step['enabled'] ),
					__( 'Shown', 'kindredcare' ),
					array( 'kc-toggle-row' => '1' )
				);

				kindredcare_admin_icon_button(
					'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
					__( 'Remove this step', 'kindredcare' ),
					array( 'kc-remove' => '1' ),
					true
				);

				/*
				 * Last control in the row. aria-hidden and out of the tab order:
				 * the title button to its left is the real expander.
				 */
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
			/*
			 * No bundled-file fallback is passed: the default is not an image at
			 * all, it is the hand-drawn SVG for this position, which the picker
			 * has no way to preview. An empty frame therefore means "using the
			 * built-in drawing".
			 */
			kindredcare_admin_media_field(
				'kc-step-' . $index . '-icon',
				$base . '[icon]',
				(int) ( $step['icon'] ?? 0 ),
				'',
				array(
					'empty_note'  => __( 'Using the built-in drawing', 'kindredcare' ),
					'reset_label' => __( 'Clear — go back to the built-in drawing', 'kindredcare' ),
				)
			);
			?>

			<div class="kc-repeat__main">
				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-step-<?php echo (int) $index; ?>-title">
						<?php esc_html_e( 'Step title', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-step-<?php echo (int) $index; ?>-title"
						name="<?php echo esc_attr( $base ); ?>[title]"
						value="<?php echo esc_attr( $step['title'] ); ?>"
						data-kc-acc-label
					/>
				</div>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-step-<?php echo (int) $index; ?>-text">
						<?php esc_html_e( 'Description', 'kindredcare' ); ?>
					</label>
					<textarea
						id="kc-step-<?php echo (int) $index; ?>-text"
						name="<?php echo esc_attr( $base ); ?>[text]"
						rows="3"
					><?php echo esc_textarea( $step['text'] ); ?></textarea>
					<p class="kc-field__hint"><?php esc_html_e( 'Two or three sentences. The capsules sit side by side, so a much longer one here stretches the row it is in.', 'kindredcare' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the How it works admin screen.
 */
function kindredcare_how_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_how();
	$option  = KINDREDCARE_HOW_OPTION;
	$steps   = $section['steps'];
	$active  = count( kindredcare_how_active_steps( $section ) );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'How it works', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The numbered sequence that removes procedural uncertainty — families will not create an account to find out how a thing works. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#how-it-works' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_how_group' ); ?>

			<!-- Intro ----------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'Sits above the steps, with the lead paragraph to its right on wide screens.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<label class="kc-field__label" for="kc-how-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-how-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-how-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-how-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty. It says "Booked in four steps." by default — if you change how many steps there are, check it still reads true.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-how-lead"><?php esc_html_e( 'Lead paragraph', 'kindredcare' ); ?></label>
						<textarea id="kc-how-lead" name="<?php echo esc_attr( $option ); ?>[lead]" rows="3"><?php echo esc_textarea( $section['lead'] ); ?></textarea>
					</div>
				</div>
			</section>

			<!-- Steps ----------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M5 6h14M5 12h14M5 18h14' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Steps', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of steps. */
								esc_html__( 'Up to %d. Numbering and the "Step N of M" line read by screen readers are both worked out from position, so reordering or hiding a step renumbers the rest.', 'kindredcare' ),
								(int) KINDREDCARE_HOW_MAX_STEPS
							);
							?>
						</p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						sprintf(
							/* translators: 1: recommended pixel size, 2: desktop size, 3: mobile size, 4: number of built-in icons. */
							__( '<strong>Best icon size: %1$s</strong> — square, transparent PNG. It draws at %2$s on desktop and %3$s on mobile, so twice that keeps it sharp on retina screens. Leave the picker empty to keep the built-in drawing for that position; there are %4$d of them and a later step reuses an earlier one. The built-in drawings are line art that takes the section\'s blue automatically — an uploaded image keeps its own colours. WordPress blocks SVG uploads unless a plugin allows them.', 'kindredcare' ),
							'<code>192 × 192</code>',
							'<code>96 × 96</code>',
							'<code>76 × 76</code>',
							count( kindredcare_how_icons() )
						)
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="steps"
						data-kc-max="<?php echo (int) KINDREDCARE_HOW_MAX_STEPS; ?>"
						data-kc-min="1"
					>
						<div data-kc-list>
							<?php foreach ( $steps as $index => $step ) : ?>
								<?php kindredcare_how_step_row( (int) $index, $step ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add step', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%1$s of %2$s steps shown · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: steps shown, 2: steps total, 3: maximum allowed. */
									esc_html__( '%1$s of %2$s steps shown · %3$s maximum', 'kindredcare' ),
									(int) $active,
									count( $steps ),
									(int) KINDREDCARE_HOW_MAX_STEPS
								);
								?>
							</span>
						</div>

						<p class="kc-field__hint" data-kc-warn-below="2" <?php echo $active > 1 ? 'hidden' : ''; ?>>
							<strong><?php esc_html_e( 'With one step shown there is no sequence left to draw the connecting line between.', 'kindredcare' ); ?></strong>
						</p>

						<template data-kc-template>
							<?php
							kindredcare_how_step_row(
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

			<!-- Button -------------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M5 12h14M13 6l6 6-6 6' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Button', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The call to action centred under the steps.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field <?php echo empty( $section['show_cta'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<span class="kc-field__label"><?php esc_html_e( 'Button', 'kindredcare' ); ?></span>
							<?php
							kindredcare_admin_toggle(
								$option . '[show_cta]',
								! empty( $section['show_cta'] ),
								__( 'Shown', 'kindredcare' ),
								array( 'kc-toggle-row' => '1' )
							);
							?>
						</div>
						<div class="kc-field--split" style="margin:0">
							<div>
								<label class="kc-field__label" for="kc-how-cta-label"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
								<input type="text" id="kc-how-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $section['cta_label'] ); ?>" />
							</div>
							<div>
								<label class="kc-field__label" for="kc-how-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
								<input type="text" id="kc-how-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $section['cta_url'] ); ?>" />
								<p class="kc-field__hint"><?php esc_html_e( 'A full address, or an on-page anchor such as #final-cta. Switch the pair off to hide the button, or clear either field to remove it.', 'kindredcare' ); ?></p>
								<?php kindredcare_admin_new_tab_toggle( $option . '[cta_new_tab]', kindredcare_link_new_tab( $section['cta_new_tab'] ?? null, (string) $section['cta_url'], 'external' ) ); ?>
							</div>
						</div>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save how it works', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
