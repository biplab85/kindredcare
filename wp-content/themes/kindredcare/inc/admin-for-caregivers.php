<?php
/**
 * Admin: KindredCare -> For caregivers.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_FOR_CAREGIVERS_MENU_SLUG = 'kindredcare-for-caregivers';

/**
 * Capability callback for this option group.
 */
function kindredcare_for_caregivers_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 18: after During the visit (17), matching the page order. */
add_action( 'admin_menu', 'kindredcare_for_caregivers_admin_menu', 18 );
/**
 * Add the For caregivers screen under the KindredCare menu.
 */
function kindredcare_for_caregivers_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'For caregivers', 'kindredcare' ),
		__( 'For caregivers', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_FOR_CAREGIVERS_MENU_SLUG,
		'kindredcare_for_caregivers_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_for_caregivers_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_for_caregivers_register_setting(): void {
	register_setting(
		'kindredcare_for_caregivers_group',
		KINDREDCARE_FOR_CAREGIVERS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_for_caregivers_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_for_caregivers_group', 'kindredcare_for_caregivers_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_for_caregivers_sanitize( $input ): array {
	$defaults = kindredcare_for_caregivers_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'    => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'      => sanitize_text_field( $input['title'] ?? '' ),
		'figure'     => sanitize_text_field( $input['figure'] ?? '' ),
		'figure_sub' => sanitize_textarea_field( $input['figure_sub'] ?? '' ),
		'items'      => array(),
		'cta_label'  => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'    => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'image'      => absint( $input['image'] ?? 0 ),
		'image_alt'  => sanitize_text_field( $input['image_alt'] ?? '' ),
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
		if ( count( $clean['items'] ) >= KINDREDCARE_FOR_CAREGIVERS_MAX_ITEMS ) {
			break;
		}
		if ( ! is_scalar( $line ) ) {
			continue;
		}

		$line = sanitize_text_field( (string) $line );

		// A blank line renders as a tick with nothing beside it.
		if ( '' === trim( $line ) ) {
			continue;
		}

		$clean['items'][] = $line;
	}

	if ( ! $clean['items'] ) {
		$clean['items'] = $defaults['items'];
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_for_caregivers_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_for_caregivers_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_FOR_CAREGIVERS_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one tick line.
 *
 * @param string $option Option name.
 * @param int    $index  Row position.
 * @param string $value  Current text.
 */
function kindredcare_for_caregivers_item_row( string $option, int $index, string $value ): void {
	?>
	<div class="kc-lines__row" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $option ); ?>[items][<?php echo (int) $index; ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			aria-label="<?php esc_attr_e( 'Benefit', 'kindredcare' ); ?>"
		/>
		<?php
		kindredcare_admin_icon_button(
			'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
			__( 'Remove this line', 'kindredcare' ),
			array( 'kc-remove' => '1' ),
			true
		);
		?>
	</div>
	<?php
}

/**
 * Render the For caregivers admin screen.
 */
function kindredcare_for_caregivers_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_for_caregivers();
	$option  = KINDREDCARE_FOR_CAREGIVERS_OPTION;
	$items   = $section['items'];
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'For caregivers', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The dark band near the foot of the page — the supply side of the marketplace, in its own voice. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#for-caregivers' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_for_caregivers_group' ); ?>

			<!-- Heading and figure ---------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading and headline figure', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The top of the left column, on the dark ground.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( '<strong>This is the only dark section on the page</strong>, and that is deliberate — it is the tonal break that says there are two sides to this marketplace. The colour is set in the stylesheet and is not editable here. Write to the caregiver as "you", not about them.', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-fc-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-fc-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-fc-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-fc-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-fc-figure"><?php esc_html_e( 'Headline figure', 'kindredcare' ); ?></label>
							<input type="text" id="kc-fc-figure" name="<?php echo esc_attr( $option ); ?>[figure]" value="<?php echo esc_attr( $section['figure'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Set large. Leave blank to remove the figure and its sentence.', 'kindredcare' ); ?></p>
						</div>
						<div>
							<label class="kc-field__label" for="kc-fc-figure-sub"><?php esc_html_e( 'What it means', 'kindredcare' ); ?></label>
							<textarea id="kc-fc-figure-sub" name="<?php echo esc_attr( $option ); ?>[figure_sub]" rows="3"><?php echo esc_textarea( $section['figure_sub'] ); ?></textarea>
						</div>
					</div>

					<?php
					kindredcare_admin_note(
						__( 'The <code>92.5%</code> here and the <code>7.5%</code> fee quoted elsewhere on the page are the same number seen from two sides. <strong>Change one and you have to change the other</strong>, along with the pricing figures on the Benefits screen — a page that quotes two different fees is worse than one that quotes none.', 'kindredcare' )
					);
					?>
				</div>
			</section>

			<!-- The list -------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'm4 9.2 3.2 3.2L14 5.6' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'What they get', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of lines. */
								esc_html__( 'Up to %d ticked lines, between the figure and the button.', 'kindredcare' ),
								(int) KINDREDCARE_FOR_CAREGIVERS_MAX_ITEMS
							);
							?>
						</p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<div
							class="kc-repeat"
							data-kc-repeat="items"
							data-kc-max="<?php echo (int) KINDREDCARE_FOR_CAREGIVERS_MAX_ITEMS; ?>"
							data-kc-min="1"
						>
							<div class="kc-lines" data-kc-list>
								<?php foreach ( $items as $index => $item ) : ?>
									<?php kindredcare_for_caregivers_item_row( $option, (int) $index, (string) $item ); ?>
								<?php endforeach; ?>
							</div>

							<div class="kc-lines__foot">
								<?php kindredcare_admin_add_button( __( 'Add line', 'kindredcare' ) ); ?>
								<span
									class="kc-repeat__count"
									data-kc-count
									data-template="<?php esc_attr_e( '%2$s lines · %3$s maximum', 'kindredcare' ); ?>"
								>
									<?php
									printf(
										/* translators: 1: lines total, 2: maximum allowed. */
										esc_html__( '%1$s lines · %2$s maximum', 'kindredcare' ),
										count( $items ),
										(int) KINDREDCARE_FOR_CAREGIVERS_MAX_ITEMS
									);
									?>
								</span>
							</div>

							<template data-kc-template>
								<?php kindredcare_for_caregivers_item_row( $option, 0, '' ); ?>
							</template>
						</div>

						<p class="kc-field__hint"><?php esc_html_e( 'One line each, no full stops. These answer "why this instead of an agency", so keep them concrete — hours, control, payment, no certification barrier. A blank line is dropped when saved.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<!-- Button and photograph -------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 5h16v14H4zM4 15l4-4 4 4 3-3 5 5' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Button and photograph', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The call to action, and the image filling the right half.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-fc-cta-label"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
							<input type="text" id="kc-fc-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $section['cta_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-fc-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-fc-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $section['cta_url'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Defaults to #final-cta, the block at the foot of this page. Clearing either field removes the button.', 'kindredcare' ); ?></p>
						</div>
					</div>

					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'Photograph', 'kindredcare' ); ?></span>
						<?php
						kindredcare_admin_media_field(
							'kc-fc-image',
							$option . '[image]',
							(int) $section['image'],
							kindredcare_for_caregivers_bundled_image()
						);

						kindredcare_admin_note(
							sprintf(
								/* translators: 1: recommended pixel size, 2: aspect ratio. */
								__( '<strong>Best size: %1$s</strong> — landscape, roughly %2$s, cropped to fill rather than letterboxed. Real domestic settings and natural light. <strong>No clipboards, no scrubs, no stethoscopes, no clinical white rooms</strong> — every agency site in this market looks like that, and looking like them is the cheapest way to be mistaken for one.', 'kindredcare' ),
								'<code>1500 × 952</code>',
								'<code>1.58 : 1</code>'
							)
						);
						?>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-fc-image-alt"><?php esc_html_e( 'Image description', 'kindredcare' ); ?></label>
						<input type="text" id="kc-fc-image-alt" name="<?php echo esc_attr( $option ); ?>[image_alt]" value="<?php echo esc_attr( $section['image_alt'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'Read aloud by screen readers. Describe what is happening in the photograph, not what the section is selling.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save for caregivers', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
