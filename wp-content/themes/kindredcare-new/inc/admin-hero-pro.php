<?php
/**
 * Admin: KindredCare -> Hero — premium.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_HERO_PRO_MENU_SLUG = 'kindredcare-hero-pro';

/**
 * Capability callback for this option group.
 */
function kindredcare_hero_pro_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 10, required after admin-hero.php: lands directly under Hero. */
add_action( 'admin_menu', 'kindredcare_hero_pro_admin_menu', 10 );
/**
 * Add the screen under the KindredCare menu.
 */
function kindredcare_hero_pro_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Hero — premium', 'kindredcare' ),
		__( 'Hero — premium', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_HERO_PRO_MENU_SLUG,
		'kindredcare_hero_pro_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_hero_pro_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_hero_pro_register_setting(): void {
	register_setting(
		'kindredcare_hero_pro_group',
		KINDREDCARE_HERO_PRO_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_hero_pro_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_hero_pro_group', 'kindredcare_hero_pro_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_hero_pro_sanitize( $input ): array {
	$defaults = kindredcare_hero_pro_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'enabled'           => ! empty( $input['enabled'] ),
		'title'             => sanitize_text_field( $input['title'] ?? '' ),
		'title_accent'      => sanitize_text_field( $input['title_accent'] ?? '' ),
		'primary_label'     => sanitize_text_field( $input['primary_label'] ?? '' ),
		'primary_url'       => esc_url_raw( trim( (string) ( $input['primary_url'] ?? '' ) ) ),
		'primary_new_tab'   => ! empty( $input['primary_new_tab'] ),
		'secondary_label'   => sanitize_text_field( $input['secondary_label'] ?? '' ),
		'secondary_url'     => esc_url_raw( trim( (string) ( $input['secondary_url'] ?? '' ) ) ),
		'secondary_new_tab' => ! empty( $input['secondary_new_tab'] ),
		'slides'            => array(),
		'proof'             => array(),
	);

	/*
	 * Slides: a list, in the order the cards were in. A slide with neither a
	 * chosen image nor a bundled one has nothing to show, so it is not kept —
	 * that is a card added and then never given a photograph.
	 */
	$slides = is_array( $input['slides'] ?? null ) ? array_values( $input['slides'] ) : array();
	foreach ( $slides as $row ) {
		if ( ! is_array( $row ) || count( $clean['slides'] ) >= KINDREDCARE_HERO_PRO_MAX_SLIDES ) {
			continue;
		}

		$image = absint( $row['image'] ?? 0 );
		$file  = kindredcare_hero_pro_bundled_file( (string) ( $row['file'] ?? '' ) );

		if ( 0 === $image && '' === $file ) {
			continue;
		}

		$clean['slides'][] = array(
			'image'   => $image,
			'file'    => $file,
			'alt'     => sanitize_text_field( $row['alt'] ?? '' ),
			'enabled' => ! empty( $row['enabled'] ),
		);
	}

	foreach ( array_keys( $defaults['proof'] ) as $index ) {
		$row                       = is_array( $input['proof'][ $index ] ?? null ) ? $input['proof'][ $index ] : array();
		$clean['proof'][ $index ] = array(
			'value' => sanitize_text_field( $row['value'] ?? '' ),
			'label' => sanitize_text_field( $row['label'] ?? '' ),
		);
	}

	// The heading names the section for screen readers, so it cannot be empty.
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_hero_pro_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_hero_pro_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_HERO_PRO_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	wp_enqueue_media();
	kindredcare_admin_enqueue();
}

/**
 * Print one labelled text input.
 *
 * @param string $id    Field id.
 * @param string $key   Option key.
 * @param string $value Current value.
 * @param string $label Visible label.
 * @param string $hint  Hint under the field, empty for none.
 */
function kindredcare_hero_pro_text_field( string $id, string $key, string $value, string $label, string $hint = '' ): void {
	?>
	<div class="kc-field">
		<label class="kc-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( KINDREDCARE_HERO_PRO_OPTION . $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		<?php if ( '' !== $hint ) : ?>
			<p class="kc-field__hint"><?php echo esc_html( $hint ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Print one call-to-action: its text, its link and the new-tab switch.
 *
 * @param string               $key     'primary' or 'secondary'.
 * @param array<string, mixed> $section Current section content.
 * @param string               $title   Card heading.
 * @param string               $label   Label for the text input.
 * @param string               $url     Label for the link input.
 */
function kindredcare_hero_pro_link_field( string $key, array $section, string $title, string $label, string $url ): void {
	$option = KINDREDCARE_HERO_PRO_OPTION;
	$id     = 'kc-hp-' . $key;
	?>
	<div class="kc-linkcard">
		<div class="kc-linkcard__head">
			<span class="kc-linkcard__title"><?php echo esc_html( $title ); ?></span>
			<?php kindredcare_admin_toggle( $option . '[' . $key . '_new_tab]', (bool) $section[ $key . '_new_tab' ], __( 'Open in a new tab', 'kindredcare' ) ); ?>
		</div>
		<div class="kc-field--split">
			<div>
				<label class="kc-field__label" for="<?php echo esc_attr( $id . '-label' ); ?>"><?php echo esc_html( $label ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id . '-label' ); ?>" name="<?php echo esc_attr( $option . '[' . $key . '_label]' ); ?>" value="<?php echo esc_attr( (string) $section[ $key . '_label' ] ); ?>" />
			</div>
			<div>
				<label class="kc-field__label" for="<?php echo esc_attr( $id . '-url' ); ?>"><?php echo esc_html( $url ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id . '-url' ); ?>" name="<?php echo esc_attr( $option . '[' . $key . '_url]' ); ?>" value="<?php echo esc_attr( (string) $section[ $key . '_url' ] ); ?>" placeholder="#services or https://…" />
			</div>
		</div>
	</div>
	<?php
}

/**
 * Print one photograph card.
 *
 * Also emitted at index 0 inside the repeater's <template>, as the blank card
 * "Add photograph" clones; panel.js renumbers names and ids to the real slot.
 * Ids keep the index between dashes (kc-hp-slide-0-alt) because that is the
 * shape reindex() rewrites.
 *
 * @param int                  $index Position in the list.
 * @param array<string, mixed> $slide Slide content.
 */
function kindredcare_hero_pro_slide_card( int $index, array $slide ): void {
	$base    = KINDREDCARE_HERO_PRO_OPTION . '[slides][' . $index . ']';
	$enabled = ! empty( $slide['enabled'] );
	$file    = (string) ( $slide['file'] ?? '' );
	$prefix  = 'kc-hp-slide-' . $index . '-';
	$alt     = (string) ( $slide['alt'] ?? '' );
	?>
	<article class="kc-slide<?php echo $enabled ? '' : ' is-off'; ?>" data-kc-item data-kc-slide>
		<div class="kc-slide__bar">
			<span class="kc-slide__grip" data-kc-slide-grip title="<?php esc_attr_e( 'Drag to reorder', 'kindredcare' ); ?>" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6" /><circle cx="15" cy="6" r="1.6" /><circle cx="9" cy="12" r="1.6" /><circle cx="15" cy="12" r="1.6" /><circle cx="9" cy="18" r="1.6" /><circle cx="15" cy="18" r="1.6" /></svg>
			</span>
			<span class="kc-slide__num kc-repeat__num"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
			<span class="kc-slide__status">
				<span class="kc-slide__status-on"><?php esc_html_e( 'Live', 'kindredcare' ); ?></span>
				<span class="kc-slide__status-off"><?php esc_html_e( 'Hidden', 'kindredcare' ); ?></span>
			</span>
			<span class="kc-slide__tools">
				<?php
				kindredcare_admin_icon_button(
					'M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3',
					__( 'Delete this photograph', 'kindredcare' ),
					array( 'kc-remove' => '1' ),
					true
				);
				?>
			</span>
		</div>

		<div class="kc-slide__media">
			<?php
			kindredcare_admin_media_field(
				$prefix . 'image',
				$base . '[image]',
				(int) ( $slide['image'] ?? 0 ),
				$file,
				'' !== $file ? array() : array(
					'empty_note'  => __( 'No photograph yet', 'kindredcare' ),
					'reset_label' => __( 'Remove the chosen image', 'kindredcare' ),
				)
			);
			?>
			<input type="hidden" name="<?php echo esc_attr( $base . '[file]' ); ?>" value="<?php echo esc_attr( $file ); ?>" />
		</div>

		<div class="kc-slide__order">
			<?php
			kindredcare_admin_icon_button( 'M15 5 8 12l7 7', __( 'Move earlier', 'kindredcare' ), array( 'kc-slide-move' => '-1' ) );
			?>
			<label class="kc-slide__pos">
				<span><?php esc_html_e( 'Position', 'kindredcare' ); ?></span>
				<?php
				/*
				 * Options are filled and kept in step by panel.js, since adding or
				 * deleting a card changes how many positions there are. No name:
				 * the order that is saved is the order of the cards themselves.
				 */
				?>
				<select data-kc-slide-pos aria-label="<?php esc_attr_e( 'Position in the slider', 'kindredcare' ); ?>">
					<option value="<?php echo esc_attr( (string) $index ); ?>" selected><?php echo esc_html( (string) ( $index + 1 ) ); ?></option>
				</select>
			</label>
			<?php
			kindredcare_admin_icon_button( 'M9 5l7 7-7 7', __( 'Move later', 'kindredcare' ), array( 'kc-slide-move' => '1' ) );
			?>
		</div>

		<div class="kc-slide__body">
			<?php kindredcare_admin_toggle( $base . '[enabled]', $enabled, __( 'Show in slider', 'kindredcare' ), array( 'kc-toggle-row' => '1' ) ); ?>

			<label class="kc-field__label" for="<?php echo esc_attr( $prefix . 'alt' ); ?>"><?php esc_html_e( 'Description (alt text)', 'kindredcare' ); ?></label>
			<textarea id="<?php echo esc_attr( $prefix . 'alt' ); ?>" name="<?php echo esc_attr( $base . '[alt]' ); ?>" rows="2" maxlength="160" data-kc-chars placeholder="<?php esc_attr_e( 'e.g. A caregiver reading with an older woman', 'kindredcare' ); ?>"><?php echo esc_textarea( $alt ); ?></textarea>
			<p class="kc-field__hint kc-slide__hint">
				<span><?php esc_html_e( 'What a screen reader announces.', 'kindredcare' ); ?></span>
				<span class="kc-slide__count"><span data-kc-chars-now><?php echo esc_html( (string) mb_strlen( $alt ) ); ?></span>/160</span>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Describe what the slider does with a given number of photographs switched on.
 *
 * @return array<int, string> Keyed 0, 1 and 2 (two or more).
 */
function kindredcare_hero_pro_slider_modes(): array {
	return array(
		0 => __( 'No photographs — the right half stays empty', 'kindredcare' ),
		1 => __( 'Single photograph — no arrows or counter', 'kindredcare' ),
		2 => __( 'Slider — cross-fade, arrows and counter', 'kindredcare' ),
	);
}

/**
 * Render the admin screen.
 */
function kindredcare_hero_pro_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_hero_pro();
	$option  = KINDREDCARE_HERO_PRO_OPTION;
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Hero — premium', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The dark panel directly under the first hero. The first hero is unaffected by anything here. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#hero-pro' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_hero_pro_group' ); ?>

			<!-- Headline --------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Headline', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The professional case, under the personal one.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<?php kindredcare_admin_toggle( $option . '[enabled]', (bool) $section['enabled'], __( 'Show this section on the front page', 'kindredcare' ) ); ?>
					</div>

					<?php
					kindredcare_hero_pro_text_field( 'kc-hp-title', '[title]', (string) $section['title'], __( 'Heading', 'kindredcare' ), __( 'White, first line. Cannot be empty.', 'kindredcare' ) );
					kindredcare_hero_pro_text_field( 'kc-hp-accent', '[title_accent]', (string) $section['title_accent'], __( 'Heading — gold line', 'kindredcare' ), __( 'Second line, in gold. Leave blank for a one-line heading.', 'kindredcare' ) );
					?>

					<?php
					kindredcare_hero_pro_link_field(
						'primary',
						$section,
						__( 'Gold button', 'kindredcare' ),
						__( 'Button text', 'kindredcare' ),
						__( 'Button link', 'kindredcare' )
					);
					kindredcare_hero_pro_link_field(
						'secondary',
						$section,
						__( 'Text link', 'kindredcare' ),
						__( 'Link text', 'kindredcare' ),
						__( 'Link destination', 'kindredcare' )
					);
					?>
					<p class="kc-field__hint"><?php esc_html_e( 'Clearing either text or link removes that control.', 'kindredcare' ); ?></p>
				</div>
			</section>

			<!-- Proof row -------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 19h16M7 15V9M12 15V5M17 15v-3' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Proof row', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'Three short facts under the buttons.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'Only <strong>facts the business can stand behind today</strong>. No review counts, years in business or awards until they exist — leave a value blank to drop that item.', 'kindredcare' )
					);

					foreach ( $section['proof'] as $index => $row ) :
						?>
						<div class="kc-field kc-field--split">
							<div>
								<label class="kc-field__label" for="kc-hp-pv-<?php echo esc_attr( (string) $index ); ?>">
									<?php
									/* translators: %d: item number. */
									echo esc_html( sprintf( __( 'Fact %d — value', 'kindredcare' ), $index + 1 ) );
									?>
								</label>
								<input type="text" id="kc-hp-pv-<?php echo esc_attr( (string) $index ); ?>" name="<?php echo esc_attr( $option . '[proof][' . $index . '][value]' ); ?>" value="<?php echo esc_attr( $row['value'] ); ?>" />
							</div>
							<div>
								<label class="kc-field__label" for="kc-hp-plb-<?php echo esc_attr( (string) $index ); ?>"><?php esc_html_e( 'Label', 'kindredcare' ); ?></label>
								<input type="text" id="kc-hp-plb-<?php echo esc_attr( (string) $index ); ?>" name="<?php echo esc_attr( $option . '[proof][' . $index . '][label]' ); ?>" value="<?php echo esc_attr( $row['label'] ); ?>" />
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Photograph slider ------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 5h16v14H4zM4 15l4-4 5 5M14 13l2-2 4 4' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Photograph slider', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The right-hand half of the band.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<?php
				$slide_total = count( $section['slides'] );
				$slide_on    = count( array_filter( $section['slides'], static fn ( array $slide ): bool => ! empty( $slide['enabled'] ) ) );
				$modes       = kindredcare_hero_pro_slider_modes();
				?>
				<div
					class="kc-panel__body kc-slides kc-repeat"
					data-kc-slides
					data-kc-repeat="slides"
					data-kc-max="<?php echo (int) KINDREDCARE_HERO_PRO_MAX_SLIDES; ?>"
					data-kc-min="1"
				>
					<div class="kc-slides__summary">
						<div class="kc-slides__meter" aria-hidden="true" data-kc-slides-meter>
							<?php for ( $i = 0; $i < $slide_total; $i++ ) : ?>
								<span class="kc-slides__pip<?php echo $i < $slide_on ? ' is-on' : ''; ?>"></span>
							<?php endfor; ?>
						</div>
						<div class="kc-slides__stats" aria-live="polite">
							<strong>
								<?php
								printf(
									/* translators: 1: photographs switched on, 2: photographs saved. */
									esc_html__( '%1$s of %2$s photographs showing', 'kindredcare' ),
									'<span data-kc-slides-on>' . (int) $slide_on . '</span>',
									'<span data-kc-slides-total>' . (int) $slide_total . '</span>'
								);
								?>
							</strong>
							<span
								class="kc-slides__mode"
								data-kc-slides-mode
								data-state="<?php echo (int) min( $slide_on, 2 ); ?>"
								data-mode-0="<?php echo esc_attr( $modes[0] ); ?>"
								data-mode-1="<?php echo esc_attr( $modes[1] ); ?>"
								data-mode-2="<?php echo esc_attr( $modes[2] ); ?>"
							><?php echo esc_html( $modes[ min( $slide_on, 2 ) ] ); ?></span>
						</div>
						<ul class="kc-slides__specs">
							<li><?php esc_html_e( 'Cross-fade · 5.5 s', 'kindredcare' ); ?></li>
							<li><?php esc_html_e( 'Ideal 1458 × 1350 px', 'kindredcare' ); ?></li>
							<li><?php esc_html_e( 'Faces near centre', 'kindredcare' ); ?></li>
						</ul>
					</div>

					<div class="kc-slides__grid" data-kc-list>
						<?php
						foreach ( array_values( $section['slides'] ) as $index => $slide ) {
							kindredcare_hero_pro_slide_card( (int) $index, (array) $slide );
						}
						?>
					</div>

					<div class="kc-slides__foot">
						<button type="button" class="kc-slides__add" data-kc-add>
							<span class="kc-slides__add-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14" /></svg>
							</span>
							<span class="kc-slides__add-text">
								<strong><?php esc_html_e( 'Add photograph', 'kindredcare' ); ?></strong>
								<span>
									<?php
									/* translators: %d: maximum number of photographs. */
									echo esc_html( sprintf( __( 'Up to %d in the slider. New photographs go at the end.', 'kindredcare' ), KINDREDCARE_HERO_PRO_MAX_SLIDES ) );
									?>
								</span>
							</span>
						</button>
						<p class="kc-field__hint">
							<?php esc_html_e( 'Deleting a card removes that photograph when you save. A new card that is saved without an image is dropped. Switch a photograph off to hide it but keep it here.', 'kindredcare' ); ?>
						</p>
					</div>

					<template data-kc-template>
						<?php
						kindredcare_hero_pro_slide_card(
							0,
							array(
								'image'   => 0,
								'file'    => '',
								'alt'     => '',
								'enabled' => true,
							)
						);
						?>
					</template>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save premium hero', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
