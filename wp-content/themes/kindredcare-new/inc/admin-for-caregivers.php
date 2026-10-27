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

/* Priority 20: after During the visit (19), matching the page order. */
add_action( 'admin_menu', 'kindredcare_for_caregivers_admin_menu', 20 );
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
		'eyebrow'      => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'show_eyebrow' => ! empty( $input['show_eyebrow'] ),
		'title'        => sanitize_text_field( $input['title'] ?? '' ),
		'figure'       => sanitize_text_field( $input['figure'] ?? '' ),
		'figure_sub'   => sanitize_textarea_field( $input['figure_sub'] ?? '' ),
		'show_figure'  => ! empty( $input['show_figure'] ),
		'items'        => array(),
		'cta_label'    => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'      => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'show_cta'     => ! empty( $input['show_cta'] ),
		'image'        => absint( $input['image'] ?? 0 ),
		'image_alt'    => sanitize_text_field( $input['image_alt'] ?? '' ),
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

		$row = kindredcare_for_caregivers_item( $line );
		if ( null === $row ) {
			continue;
		}

		$text = sanitize_text_field( $row['text'] );

		/*
		 * Emptying the field is how a line is deleted; the switch is how one is
		 * hidden. A line that still has words is therefore kept whichever way
		 * its switch is set — otherwise switching one off would quietly throw
		 * the wording away and there would be nothing to switch back on.
		 */
		if ( '' === trim( $text ) ) {
			continue;
		}

		$clean['items'][] = array(
			'text'    => $text,
			'enabled' => $row['enabled'],
		);
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
 * The wording comes first and the two controls that act on it follow: the
 * switch, then the delete button — hide it, then remove it.
 *
 * @param string                             $option Option name.
 * @param int                                $index  Row position.
 * @param array{text: string, enabled: bool} $item   Current row.
 */
function kindredcare_for_caregivers_item_row( string $option, int $index, array $item ): void {
	$base = $option . '[items][' . $index . ']';
	?>
	<div class="kc-lines__row <?php echo empty( $item['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $base ); ?>[text]"
			value="<?php echo esc_attr( (string) $item['text'] ); ?>"
			aria-label="<?php esc_attr_e( 'Benefit', 'kindredcare' ); ?>"
		/>
		<?php
		kindredcare_admin_toggle(
			$base . '[enabled]',
			! empty( $item['enabled'] ),
			__( 'Shown', 'kindredcare' ),
			array( 'kc-toggle-row' => '1' )
		);

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

					<?php
					kindredcare_admin_note(
						__( 'Each part of this column has its own switch. <strong>Switching one off hides it from the page but keeps what you wrote</strong>, so it is there to turn back on — clearing the field is what throws the wording away. The heading has no switch: it names the section for screen readers and something has to.', 'kindredcare' )
					);
					?>

					<div class="kc-field <?php echo empty( $section['show_eyebrow'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<label class="kc-field__label" for="kc-fc-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
							<?php
							kindredcare_admin_toggle(
								$option . '[show_eyebrow]',
								! empty( $section['show_eyebrow'] ),
								__( 'Shown', 'kindredcare' ),
								array( 'kc-toggle-row' => '1' )
							);
							?>
						</div>
						<input type="text" id="kc-fc-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Switch it off to hide it, or clear it to remove it for good.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-fc-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-fc-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field <?php echo empty( $section['show_figure'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<span class="kc-field__label"><?php esc_html_e( 'Headline figure', 'kindredcare' ); ?></span>
							<?php
							/*
							 * One switch for the pair. The sentence exists to explain
							 * the number and reads as a fragment without it, so the
							 * two are never usefully hidden apart.
							 */
							kindredcare_admin_toggle(
								$option . '[show_figure]',
								! empty( $section['show_figure'] ),
								__( 'Shown', 'kindredcare' ),
								array( 'kc-toggle-row' => '1' )
							);
							?>
						</div>
						<div class="kc-field--split" style="margin:0">
							<div>
								<label class="kc-field__label" for="kc-fc-figure"><?php esc_html_e( 'The number', 'kindredcare' ); ?></label>
								<input type="text" id="kc-fc-figure" name="<?php echo esc_attr( $option ); ?>[figure]" value="<?php echo esc_attr( $section['figure'] ); ?>" />
								<p class="kc-field__hint"><?php esc_html_e( 'Set large. Switch the pair off to hide it, or clear this to remove the figure and its sentence for good.', 'kindredcare' ); ?></p>
							</div>
							<div>
								<label class="kc-field__label" for="kc-fc-figure-sub"><?php esc_html_e( 'What it means', 'kindredcare' ); ?></label>
								<textarea id="kc-fc-figure-sub" name="<?php echo esc_attr( $option ); ?>[figure_sub]" rows="3"><?php echo esc_textarea( $section['figure_sub'] ); ?></textarea>
							</div>
						</div>
					</div>

					<?php
					kindredcare_admin_note(
						__( 'The <code>80%</code> here and the <code>20%</code> fee quoted elsewhere on the page are the same number seen from two sides. <strong>Change one and you have to change the other</strong>, along with the pricing figures on the Benefits screen — a page that quotes two different fees is worse than one that quotes none.', 'kindredcare' )
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
									<?php kindredcare_for_caregivers_item_row( $option, (int) $index, (array) $item ); ?>
								<?php endforeach; ?>
							</div>

							<div class="kc-lines__foot">
								<?php kindredcare_admin_add_button( __( 'Add line', 'kindredcare' ) ); ?>
								<span
									class="kc-repeat__count"
									data-kc-count
									data-template="<?php esc_attr_e( '%1$s shown · %2$s saved · %3$s maximum', 'kindredcare' ); ?>"
								>
									<?php
									printf(
										/* translators: 1: lines shown, 2: lines saved, 3: maximum allowed. */
										esc_html__( '%1$s shown · %2$s saved · %3$s maximum', 'kindredcare' ),
										count( kindredcare_for_caregivers_active_items( $section ) ),
										count( $items ),
										(int) KINDREDCARE_FOR_CAREGIVERS_MAX_ITEMS
									);
									?>
								</span>
							</div>

							<template data-kc-template>
								<?php
								kindredcare_for_caregivers_item_row(
									$option,
									0,
									array(
										'text'    => '',
										'enabled' => true,
									)
								);
								?>
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
					<div class="kc-field <?php echo empty( $section['show_cta'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<span class="kc-field__label"><?php esc_html_e( 'Button', 'kindredcare' ); ?></span>
							<?php
							/*
							 * One switch for the pair. A label with no link is a dead
							 * control and a link with no label is an invisible one, so
							 * they are never usefully hidden apart.
							 */
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
								<label class="kc-field__label" for="kc-fc-cta-label"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
								<input type="text" id="kc-fc-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $section['cta_label'] ); ?>" />
							</div>
							<div>
								<label class="kc-field__label" for="kc-fc-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
								<input type="text" id="kc-fc-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $section['cta_url'] ); ?>" />
								<p class="kc-field__hint"><?php esc_html_e( 'Defaults to #final-cta, the block at the foot of this page. Switch the pair off to hide the button, or clear either field to remove it for good.', 'kindredcare' ); ?></p>
							</div>
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
