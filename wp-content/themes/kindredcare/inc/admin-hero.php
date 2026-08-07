<?php
/**
 * Admin: KindredCare -> Hero.
 *
 * Registers the KindredCare menu and the Hero screen. Saving goes through the
 * Settings API (options.php), so the nonce, the referer check and the
 * capability gate are WordPress's rather than hand-rolled; the only thing this
 * file owns is the sanitiser and the form.
 *
 * Shared furniture — masthead, panels, toggles, media pickers — is in
 * inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_HERO_MENU_SLUG = KINDREDCARE_ADMIN_MENU_SLUG;

/**
 * Back-compat alias for the shared capability.
 *
 * Named separately because it is also the callback for this option group's
 * `option_page_capability_` filter.
 */
function kindredcare_hero_capability(): string {
	return kindredcare_admin_capability();
}

add_action( 'admin_menu', 'kindredcare_hero_admin_menu' );
/**
 * Register the KindredCare menu and its Hero submenu.
 *
 * This is also where the parent menu itself is created — the Services screen
 * hooks admin_menu later and only adds its own submenu.
 */
function kindredcare_hero_admin_menu(): void {
	$capability = kindredcare_admin_capability();

	add_menu_page(
		__( 'KindredCare', 'kindredcare' ),
		__( 'KindredCare', 'kindredcare' ),
		$capability,
		KINDREDCARE_ADMIN_MENU_SLUG,
		'kindredcare_hero_render_page',
		'dashicons-heart',
		59 // Directly above Appearance, where the rest of the theme's controls live.
	);

	/*
	 * add_menu_page() auto-creates a first submenu item repeating the parent's
	 * title. Re-registering the same slug renames that entry to "Hero" instead
	 * of leaving a duplicate "KindredCare" above it.
	 */
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Hero', 'kindredcare' ),
		__( 'Hero', 'kindredcare' ),
		$capability,
		KINDREDCARE_ADMIN_MENU_SLUG,
		'kindredcare_hero_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_hero_register_setting' );
/**
 * Register the hero option with the Settings API.
 */
function kindredcare_hero_register_setting(): void {
	register_setting(
		'kindredcare_hero_group',
		KINDREDCARE_HERO_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_hero_sanitize',
			'default'           => array(),
		)
	);
}

/*
 * options.php demands manage_options unless the option group says otherwise.
 * Without this the Hero screen would render for an editor and then refuse the
 * save, which is a worse failure than not showing it at all.
 */
add_filter( 'option_page_capability_kindredcare_hero_group', 'kindredcare_hero_capability' );

/**
 * Sanitise the submitted hero content.
 *
 * Anything not in the schema is dropped rather than passed through: the option
 * is rendered straight into the front page, so the stored shape is the security
 * boundary.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_hero_sanitize( $input ): array {
	$defaults = kindredcare_hero_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'        => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'          => sanitize_text_field( $input['title'] ?? '' ),
		'lead'           => sanitize_textarea_field( $input['lead'] ?? '' ),
		'cta_label'      => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'        => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'social_enabled' => ! empty( $input['social_enabled'] ),
		'social_label'   => sanitize_text_field( $input['social_label'] ?? '' ),
		'social'         => array(),
		'cards'          => array(),
		'slides'         => array(),
	);

	/*
	 * An empty title would collapse the headline box the ghost span reserves and
	 * leave the typewriter with nothing to type, so it falls back rather than
	 * saving blank. The other strings are allowed to be empty — each has a
	 * guard in the renderer that drops its element cleanly.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	foreach ( $defaults['social'] as $key => $row ) {
		$clean['social'][ $key ] = array(
			'url'     => esc_url_raw( trim( (string) ( $input['social'][ $key ]['url'] ?? '' ) ) ),
			'enabled' => ! empty( $input['social'][ $key ]['enabled'] ),
		);
	}

	foreach ( $defaults['cards'] as $index => $card ) {
		$clean['cards'][ $index ] = array(
			'image' => absint( $input['cards'][ $index ]['image'] ?? 0 ),
			'label' => sanitize_text_field( $input['cards'][ $index ]['label'] ?? '' ),
			'file'  => $card['file'],
			'tint'  => $card['tint'],
		);
	}

	/*
	 * Slides are repeatable, so `file` now arrives from the form instead of
	 * being looked up by position. It is checked against the bundled set rather
	 * than sanitised as a path — an allowlist cannot be talked into resolving
	 * somewhere unintended, and a cleaned path still can.
	 */
	$allowed = kindredcare_hero_bundled_images();
	$rows    = is_array( $input['slides'] ?? null ) ? array_values( $input['slides'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['slides'] ) >= KINDREDCARE_HERO_MAX_SLIDES ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$file  = (string) ( $row['file'] ?? '' );
		$image = absint( $row['image'] ?? 0 );
		$file  = in_array( $file, $allowed, true ) ? $file : '';

		// A row with neither an attachment nor a bundled file has nothing to show.
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

	if ( ! $clean['slides'] ) {
		$clean['slides'] = $defaults['slides'];
	}

	/*
	 * The slider is the hero's whole right-hand column. Switching every slide
	 * off would leave it blank, so the first one is switched back on — the same
	 * shape of guard as the empty headline above.
	 */
	$any_enabled = false;
	foreach ( $clean['slides'] as $slide ) {
		$any_enabled = $any_enabled || $slide['enabled'];
	}
	if ( ! $any_enabled ) {
		$clean['slides'][0]['enabled'] = true;
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_hero_admin_assets' );
/**
 * Load the shared panel assets on the Hero screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_hero_admin_assets( string $hook_suffix ): void {
	if ( 'toplevel_page_' . KINDREDCARE_ADMIN_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one slide row.
 *
 * @param int                  $index Row position.
 * @param array<string, mixed> $slide Row data.
 */
function kindredcare_hero_slide_row( int $index, array $slide ): void {
	$option = KINDREDCARE_HERO_OPTION;
	$base   = $option . '[slides][' . $index . ']';
	?>
	<div class="kc-repeat__item <?php echo empty( $slide['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
		<?php
		kindredcare_admin_media_field(
			'kc-slide-' . $index . '-image',
			$base . '[image]',
			(int) $slide['image'],
			(string) $slide['file']
		);
		?>

		<div class="kc-repeat__main">
			<div class="kc-repeat__bar">
				<span class="kc-repeat__index">
					<span class="kc-repeat__num"><?php echo (int) $index + 1; ?></span>
					<?php esc_html_e( 'Photograph', 'kindredcare' ); ?>
				</span>

				<div class="kc-repeat__tools">
					<?php
					kindredcare_admin_toggle(
						$base . '[enabled]',
						! empty( $slide['enabled'] ),
						__( 'Shown', 'kindredcare' ),
						array( 'kc-toggle-row' => '1' )
					);

					kindredcare_admin_icon_button(
						'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
						__( 'Remove this photograph', 'kindredcare' ),
						array( 'kc-remove' => '1' ),
						true
					);
					?>
				</div>
			</div>

			<div class="kc-field" style="margin:0">
				<label class="kc-field__label" for="kc-slide-<?php echo (int) $index; ?>-alt">
					<?php esc_html_e( 'Alt text', 'kindredcare' ); ?>
				</label>
				<input
					type="text"
					id="kc-slide-<?php echo (int) $index; ?>-alt"
					name="<?php echo esc_attr( $base ); ?>[alt]"
					value="<?php echo esc_attr( $slide['alt'] ); ?>"
				/>
				<p class="kc-field__hint"><?php esc_html_e( 'Describe what is happening in the photograph, for screen readers and for when the image fails to load.', 'kindredcare' ); ?></p>
			</div>

			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[file]" value="<?php echo esc_attr( $slide['file'] ); ?>" />
		</div>
	</div>
	<?php
}

/**
 * Render the Hero admin screen.
 */
function kindredcare_hero_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the hero.', 'kindredcare' ) );
	}

	$hero     = kindredcare_hero();
	$networks = kindredcare_social_networks();
	$option   = KINDREDCARE_HERO_OPTION;
	$slides   = $hero['slides'];
	$active   = count( kindredcare_hero_active_slides( $hero ) );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Hero', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the front page. */
				esc_html__( 'The first thing anyone sees — headline, lead, call to action, social row, cards and the photograph slider. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_hero_group' ); ?>

			<!-- Copy ------------------------------------------------------ -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Copy', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The headline types itself on load, so keep it short enough to finish before a reader looks away.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<label class="kc-field__label" for="kc-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $hero['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the headline. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-title"><?php esc_html_e( 'Headline', 'kindredcare' ); ?></label>
						<input type="text" id="kc-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $hero['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'Also the heading assistive technology reads, so write it as a full sentence. It cannot be empty — it reserves the space the rest of the hero is laid out against.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-lead"><?php esc_html_e( 'Lead paragraph', 'kindredcare' ); ?></label>
						<textarea id="kc-lead" name="<?php echo esc_attr( $option ); ?>[lead]" rows="3"><?php echo esc_textarea( $hero['lead'] ); ?></textarea>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-cta-label"><?php esc_html_e( 'Button label', 'kindredcare' ); ?></label>
							<input type="text" id="kc-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $hero['cta_label'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Leave blank to remove the button.', 'kindredcare' ); ?></p>
						</div>
						<div>
							<label class="kc-field__label" for="kc-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $hero['cta_url'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'A full URL, or an on-page anchor such as #services.', 'kindredcare' ); ?></p>
						</div>
					</div>
				</div>
			</section>

			<!-- Social row ------------------------------------------------ -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M18 8a3 3 0 1 0-2.83-4M6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM18 22a3 3 0 1 0-2.83-4M8.6 10.7l6.8-3.4M8.6 13.3l6.8 3.4' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Social row', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The hero keeps its own links, separate from the footer\'s — the hero shows four icons and the footer three. Switch off a network, or the whole row, without losing the URL.', 'kindredcare' ); ?></p>
					</div>
					<?php
					kindredcare_admin_toggle(
						$option . '[social_enabled]',
						! empty( $hero['social_enabled'] ),
						__( 'Show row', 'kindredcare' ),
						array( 'kc-toggle-section' => 'kc-social-body' )
					);
					?>
				</div>
				<div class="kc-panel__body <?php echo empty( $hero['social_enabled'] ) ? 'is-off' : ''; ?>" id="kc-social-body">
					<div class="kc-field">
						<label class="kc-field__label" for="kc-social-label"><?php esc_html_e( 'Label', 'kindredcare' ); ?></label>
						<input type="text" id="kc-social-label" name="<?php echo esc_attr( $option ); ?>[social_label]" value="<?php echo esc_attr( $hero['social_label'] ); ?>" />
					</div>

					<?php foreach ( $networks as $key => $network ) : ?>
						<?php $row = $hero['social'][ $key ] ?? array( 'url' => '', 'enabled' => false ); ?>
						<div class="kc-row <?php echo empty( $row['enabled'] ) ? 'is-off' : ''; ?>">
							<span class="kc-row__brand">
								<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?php echo esc_attr( $network['path'] ); ?>" /></svg>
								<?php echo esc_html( $network['label'] ); ?>
							</span>
							<div class="kc-field--split" style="align-items:center">
								<input
									type="text"
									name="<?php echo esc_attr( $option ); ?>[social][<?php echo esc_attr( $key ); ?>][url]"
									value="<?php echo esc_attr( $row['url'] ); ?>"
									aria-label="<?php
										/* translators: %s: social network name. */
										echo esc_attr( sprintf( __( '%s URL', 'kindredcare' ), $network['label'] ) );
									?>"
								/>
								<?php
								kindredcare_admin_toggle(
									$option . '[social][' . $key . '][enabled]',
									! empty( $row['enabled'] ),
									__( 'Shown', 'kindredcare' ),
									array( 'kc-toggle-row' => '1' )
								);
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Cards ----------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M3 5h8v6H3zM13 5h8v6h-8zM3 13h8v6H3zM13 13h8v6h-8z' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Cards', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The two small tiles beside the social row.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						sprintf(
							/* translators: 1: recommended pixel size, 2: aspect ratio. */
							__( '<strong>Best image size: %1$s</strong> (%2$s). The tile crops to fill, so anything taller is trimmed top and bottom — keep the subject centred. The bundled photographs are 528 × 384 and are cropped this way themselves. Landscape JPG, ideally under 250 KB.', 'kindredcare' ),
							'<code>800 × 500</code>',
							'16:10'
						)
					);
					?>

					<?php foreach ( $hero['cards'] as $index => $card ) : ?>
						<div class="kc-repeat__item">
							<?php
							kindredcare_admin_media_field(
								'kc-card-' . $index . '-image',
								$option . '[cards][' . $index . '][image]',
								(int) $card['image'],
								(string) $card['file']
							);
							?>
							<div class="kc-repeat__main">
								<div class="kc-repeat__bar">
									<span class="kc-repeat__index">
										<span class="kc-repeat__num"><?php echo (int) $index + 1; ?></span>
										<?php esc_html_e( 'Card', 'kindredcare' ); ?>
									</span>
								</div>
								<div class="kc-field" style="margin:0">
									<label class="kc-field__label" for="kc-card-<?php echo (int) $index; ?>-label"><?php esc_html_e( 'Label', 'kindredcare' ); ?></label>
									<input
										type="text"
										id="kc-card-<?php echo (int) $index; ?>-label"
										name="<?php echo esc_attr( $option ); ?>[cards][<?php echo (int) $index; ?>][label]"
										value="<?php echo esc_attr( $card['label'] ); ?>"
									/>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Slider ---------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M3 6h18v12H3zM8 10l3 3 2-2 3 3' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Photograph slider', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of slides. */
								esc_html__( 'Between one and %d photographs, advanced by the arrows. Autoplay is deliberately off so the headline never moves under the reader. The first shown photograph loads eagerly — it is the largest image on the page.', 'kindredcare' ),
								(int) KINDREDCARE_HERO_MAX_SLIDES
							);
							?>
						</p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						sprintf(
							/* translators: 1: recommended pixel size, 2: aspect ratio. */
							__( '<strong>Best image size: %1$s</strong> (roughly square, %2$s) — the same as the bundled photographs. This panel changes shape: about 4:3 on a phone, nearly a tall square on a wide screen, and the image crops to fill both. Keep faces near the centre and leave room at the edges. Portrait-orientation photographs lose their top and bottom here. JPG, ideally under 250 KB — this is the heaviest image on the page.', 'kindredcare' ),
							'<code>1458 × 1350</code>',
							'27:25'
						)
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="slides"
						data-kc-max="<?php echo (int) KINDREDCARE_HERO_MAX_SLIDES; ?>"
						data-kc-min="1"
					>
						<div data-kc-list>
							<?php foreach ( $slides as $index => $slide ) : ?>
								<?php kindredcare_hero_slide_row( (int) $index, $slide ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add photograph', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%1$s of %2$s slides shown · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: slides shown, 2: slides total, 3: maximum allowed. */
									esc_html__( '%1$s of %2$s slides shown · %3$s maximum', 'kindredcare' ),
									(int) $active,
									count( $slides ),
									(int) KINDREDCARE_HERO_MAX_SLIDES
								);
								?>
							</span>
						</div>

						<p class="kc-field__hint" data-kc-warn-below="2" <?php echo $active > 1 ? 'hidden' : ''; ?>>
							<strong><?php esc_html_e( 'With one photograph shown the slider stops looping and the arrows are hidden.', 'kindredcare' ); ?></strong>
						</p>

						<?php
						/*
						 * Cloned by assets/admin/panel.js for a new row. It is
						 * emitted at index 0 and renumbered on insert by the same
						 * reindex pass that runs after a removal, so the template
						 * needs no placeholder of its own.
						 *
						 * A new row has no bundled fallback — only the three the
						 * theme ships have one — so it must be given an image
						 * before it will render.
						 */
						?>
						<template data-kc-template>
							<?php
							kindredcare_hero_slide_row(
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
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save hero', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
