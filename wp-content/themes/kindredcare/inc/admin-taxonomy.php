<?php
/**
 * Admin: KindredCare -> Service taxonomy.
 *
 * The "What you can book" mosaic. Same Settings API arrangement as the other
 * screens, and the same furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_TAXONOMY_MENU_SLUG = 'kindredcare-taxonomy';

/**
 * Capability callback for this option group.
 */
function kindredcare_taxonomy_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 13: after Hero (10), Services (11) and How it works (12). */
add_action( 'admin_menu', 'kindredcare_taxonomy_admin_menu', 13 );
/**
 * Add the Service taxonomy screen under the KindredCare menu.
 */
function kindredcare_taxonomy_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Service taxonomy', 'kindredcare' ),
		__( 'Service taxonomy', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_TAXONOMY_MENU_SLUG,
		'kindredcare_taxonomy_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_taxonomy_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_taxonomy_register_setting(): void {
	register_setting(
		'kindredcare_taxonomy_group',
		KINDREDCARE_TAXONOMY_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_taxonomy_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_taxonomy_group', 'kindredcare_taxonomy_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_taxonomy_sanitize( $input ): array {
	$defaults = kindredcare_taxonomy_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'   => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'     => sanitize_text_field( $input['title'] ?? '' ),
		'lead'      => sanitize_textarea_field( $input['lead'] ?? '' ),
		'cta_label' => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'   => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'chips'     => array(),
		'tiles'     => array(),
	);

	// The heading names the section through aria-labelledby.
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	/*
	 * Chip keys are structural — a tile is filed under one, and the filter
	 * matches on it — so only the labels come from the form.
	 */
	foreach ( $defaults['chips'] as $index => $chip ) {
		$label = sanitize_text_field( $input['chips'][ $index ]['label'] ?? '' );

		$clean['chips'][] = array(
			'key'   => $chip['key'],
			'label' => '' !== trim( $label ) ? $label : $chip['label'],
		);
	}

	$allowed = kindredcare_taxonomy_bundled_images();
	$groups  = kindredcare_taxonomy_groups();
	$rows    = is_array( $input['tiles'] ?? null ) ? array_values( $input['tiles'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['tiles'] ) >= KINDREDCARE_TAXONOMY_MAX_TILES ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		// The name is both the tile label and the modal heading.
		$name = wp_kses( (string) ( $row['name'] ?? '' ), array( 'span' => array() ) );

		if ( '' === trim( wp_strip_all_tags( $name ) ) ) {
			continue;
		}

		$images = array();
		foreach ( array_values( is_array( $row['images'] ?? null ) ? $row['images'] : array() ) as $image ) {
			if ( ! is_array( $image ) || count( $images ) >= KINDREDCARE_TAXONOMY_MAX_IMAGES ) {
				continue;
			}

			$file  = (string) ( $image['file'] ?? '' );
			$id    = absint( $image['image'] ?? 0 );
			$file  = in_array( $file, $allowed, true ) ? $file : '';

			// A slot with neither an upload nor a bundled file shows nothing.
			if ( 0 === $id && '' === $file ) {
				continue;
			}

			$images[] = array(
				'image' => $id,
				'file'  => $file,
			);
		}

		$group = (string) ( $row['group'] ?? '' );

		$clean['tiles'][] = array(
			'enabled' => ! empty( $row['enabled'] ),
			'name'    => $name,
			'group'   => in_array( $group, $groups, true ) ? $group : ( $groups[0] ?? '' ),
			'rate'    => sanitize_text_field( $row['rate'] ?? '' ),
			'tier'    => sanitize_text_field( $row['tier'] ?? '' ),
			'blurb'   => sanitize_textarea_field( $row['blurb'] ?? '' ),
			// Layout, not content — allowlisted and never edited by hand.
			'mods'    => kindredcare_taxonomy_clean_mods( (string) ( $row['mods'] ?? '' ) ),
			'images'  => $images,
		);
	}

	if ( ! $clean['tiles'] ) {
		$clean['tiles'] = $defaults['tiles'];
	}

	// An empty mosaic would leave the heading tile sitting alone in the grid.
	$any_enabled = false;
	foreach ( $clean['tiles'] as $tile ) {
		$any_enabled = $any_enabled || $tile['enabled'];
	}
	if ( ! $any_enabled ) {
		$clean['tiles'][0]['enabled'] = true;
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_taxonomy_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_taxonomy_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_TAXONOMY_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one tile row, as a collapsed accordion.
 *
 * @param int                  $index    Row position.
 * @param array<string, mixed> $tile     Row data.
 * @param array<string,string> $chips    Chip key => label, for the group select.
 * @param bool                 $expanded Whether to render it open.
 */
function kindredcare_taxonomy_tile_row( int $index, array $tile, array $chips, bool $expanded = false ): void {
	$option  = KINDREDCARE_TAXONOMY_OPTION;
	$base    = $option . '[tiles][' . $index . ']';
	$body_id = 'kc-tile-' . $index . '-body';
	$images  = $tile['images'];

	// Always offer the full complement of slots, so a photograph can be added.
	while ( count( $images ) < KINDREDCARE_TAXONOMY_MAX_IMAGES ) {
		$images[] = array( 'image' => 0, 'file' => '' );
	}
	?>
	<div class="kc-repeat__item kc-acc <?php echo empty( $tile['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
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
					data-empty="<?php esc_attr_e( 'Untitled service', 'kindredcare' ); ?>"
				><?php echo esc_html( wp_strip_all_tags( (string) $tile['name'] ) ); ?></span>
			</button>

			<div class="kc-repeat__tools">
				<?php
				kindredcare_admin_toggle(
					$base . '[enabled]',
					! empty( $tile['enabled'] ),
					__( 'Shown', 'kindredcare' ),
					array( 'kc-toggle-row' => '1' )
				);

				kindredcare_admin_icon_button(
					'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
					__( 'Remove this service', 'kindredcare' ),
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
			<div class="kc-repeat__main">
				<div class="kc-field kc-field--split" style="margin:0">
					<div>
						<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-name">
							<?php esc_html_e( 'Service name', 'kindredcare' ); ?>
						</label>
						<input
							type="text"
							id="kc-tile-<?php echo (int) $index; ?>-name"
							name="<?php echo esc_attr( $base ); ?>[name]"
							value="<?php echo esc_attr( $tile['name'] ); ?>"
							data-kc-acc-label
						/>
						<p class="kc-field__hint"><?php esc_html_e( 'Wrap part of it in <span> to choose where the line breaks on the tile — the modal and screen readers get the plain text either way.', 'kindredcare' ); ?></p>
					</div>
					<div>
						<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-group">
							<?php esc_html_e( 'Filter group', 'kindredcare' ); ?>
						</label>
						<select
							id="kc-tile-<?php echo (int) $index; ?>-group"
							name="<?php echo esc_attr( $base ); ?>[group]"
							class="kc-select"
						>
							<?php foreach ( $chips as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $tile['group'], $key ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="kc-field__hint"><?php esc_html_e( 'Which chip shows this tile.', 'kindredcare' ); ?></p>
					</div>
				</div>

				<div class="kc-field kc-field--split" style="margin:0">
					<div>
						<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-rate">
							<?php esc_html_e( 'Rate', 'kindredcare' ); ?>
						</label>
						<input type="text" id="kc-tile-<?php echo (int) $index; ?>-rate" name="<?php echo esc_attr( $base ); ?>[rate]" value="<?php echo esc_attr( $tile['rate'] ); ?>" />
					</div>
					<div>
						<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-tier">
							<?php esc_html_e( 'Verification tier', 'kindredcare' ); ?>
						</label>
						<input type="text" id="kc-tile-<?php echo (int) $index; ?>-tier" name="<?php echo esc_attr( $base ); ?>[tier]" value="<?php echo esc_attr( $tile['tier'] ); ?>" />
					</div>
				</div>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-blurb">
						<?php esc_html_e( 'Description', 'kindredcare' ); ?>
					</label>
					<textarea id="kc-tile-<?php echo (int) $index; ?>-blurb" name="<?php echo esc_attr( $base ); ?>[blurb]" rows="3"><?php echo esc_textarea( $tile['blurb'] ); ?></textarea>
					<p class="kc-field__hint"><?php esc_html_e( 'Shown in the pop-up when the tile is clicked, with the rate and tier above it.', 'kindredcare' ); ?></p>
				</div>

				<div class="kc-field" style="margin:0">
					<span class="kc-field__label"><?php esc_html_e( 'Photographs', 'kindredcare' ); ?></span>
					<div class="kc-photos">
						<?php foreach ( $images as $slot => $image ) : ?>
							<?php
							kindredcare_admin_media_field(
								'kc-tile-' . $index . '-img-' . $slot,
								$base . '[images][' . $slot . '][image]',
								(int) $image['image'],
								(string) $image['file'],
								array(
									'empty_note'  => '' !== $image['file'] ? __( 'Bundled image', 'kindredcare' ) : __( 'Empty slot', 'kindredcare' ),
									'reset_label' => '' !== $image['file'] ? __( 'Reset to the bundled image', 'kindredcare' ) : __( 'Clear this slot', 'kindredcare' ),
								)
							);
							?>
							<input type="hidden" name="<?php echo esc_attr( $base ); ?>[images][<?php echo (int) $slot; ?>][file]" value="<?php echo esc_attr( $image['file'] ); ?>" />
						<?php endforeach; ?>
					</div>
					<p class="kc-field__hint"><?php esc_html_e( 'Only the double-height Companionship tile shows two; every other layout uses the first and ignores the second.', 'kindredcare' ); ?></p>
				</div>

				<input type="hidden" name="<?php echo esc_attr( $base ); ?>[mods]" value="<?php echo esc_attr( $tile['mods'] ); ?>" />
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the Service taxonomy admin screen.
 */
function kindredcare_taxonomy_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_taxonomy();
	$option  = KINDREDCARE_TAXONOMY_OPTION;
	$tiles   = $section['tiles'];
	$active  = count( kindredcare_taxonomy_active_tiles( $section ) );

	// Group select options: every chip except the "all" reset.
	$chips = array();
	foreach ( $section['chips'] as $chip ) {
		if ( 'all' !== $chip['key'] ) {
			$chips[ $chip['key'] ] = $chip['label'];
		}
	}
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Service taxonomy', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The pastel mosaic that proves this is not an agency — every service families can book, filtered by the chips above it. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#services' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_taxonomy_group' ); ?>

			<!-- Heading tile ---------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading tile', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The heading is one of the tiles rather than a band above them, so it sits inside the mosaic and shares its grid.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<label class="kc-field__label" for="kc-tax-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-tax-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
					</div>
					<div class="kc-field">
						<label class="kc-field__label" for="kc-tax-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-tax-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'Names the section for screen readers, so it cannot be empty. It counts the services out loud — check it still matches if you add or remove tiles.', 'kindredcare' ); ?></p>
					</div>
					<div class="kc-field">
						<label class="kc-field__label" for="kc-tax-lead"><?php esc_html_e( 'Lead paragraph', 'kindredcare' ); ?></label>
						<textarea id="kc-tax-lead" name="<?php echo esc_attr( $option ); ?>[lead]" rows="4"><?php echo esc_textarea( $section['lead'] ); ?></textarea>
					</div>
					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-tax-cta-label"><?php esc_html_e( 'Button label', 'kindredcare' ); ?></label>
							<input type="text" id="kc-tax-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $section['cta_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-tax-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-tax-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $section['cta_url'] ); ?>" />
						</div>
					</div>
				</div>
			</section>

			<!-- Chips ----------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 8h16M4 16h10' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Filter chips', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'Shown below 768px, where the mosaic stacks and the grid alone stops being a way to scan. The labels are yours; the groups behind them are fixed, because every tile is filed under one.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php foreach ( $section['chips'] as $index => $chip ) : ?>
						<div class="kc-row">
							<span class="kc-row__brand"><code><?php echo esc_html( $chip['key'] ); ?></code></span>
							<input
								type="text"
								name="<?php echo esc_attr( $option ); ?>[chips][<?php echo (int) $index; ?>][label]"
								value="<?php echo esc_attr( $chip['label'] ); ?>"
								aria-label="<?php
									/* translators: %s: chip key. */
									echo esc_attr( sprintf( __( 'Label for the %s chip', 'kindredcare' ), $chip['key'] ) );
								?>"
							/>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Tiles ----------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 4h7v7H4zM13 4h7v11h-7zM4 13h7v7H4zM13 17h7v3h-7z' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Services', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of tiles. */
								esc_html__( 'Up to %d. Clicking a tile opens a pop-up built from its rate, tier and description — there is no separate page to write.', 'kindredcare' ),
								(int) KINDREDCARE_TAXONOMY_MAX_TILES
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
							__( '<strong>Best image size: %1$s</strong> (%2$s, portrait) — the tiles crop to fill and the tall ones are the deepest. Each tile\'s ground colour, corner rounding and height are <strong>fixed by its place in the mosaic</strong> and are not editable: the grid is hand-fitted, and one wrong value drops a tile out of it. A new tile joins with no styling of its own.', 'kindredcare' ),
							'<code>600 × 800</code>',
							'3:4'
						)
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="tiles"
						data-kc-max="<?php echo (int) KINDREDCARE_TAXONOMY_MAX_TILES; ?>"
						data-kc-min="1"
					>
						<div data-kc-list>
							<?php foreach ( $tiles as $index => $tile ) : ?>
								<?php kindredcare_taxonomy_tile_row( (int) $index, $tile, $chips ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add service', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%1$s of %2$s services shown · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: tiles shown, 2: tiles total, 3: maximum allowed. */
									esc_html__( '%1$s of %2$s services shown · %3$s maximum', 'kindredcare' ),
									(int) $active,
									count( $tiles ),
									(int) KINDREDCARE_TAXONOMY_MAX_TILES
								);
								?>
							</span>
						</div>

						<template data-kc-template>
							<?php
							kindredcare_taxonomy_tile_row(
								0,
								array(
									'enabled' => true,
									'name'    => '',
									'group'   => array_key_first( $chips ) ?? '',
									'rate'    => '',
									'tier'    => '',
									'blurb'   => '',
									'mods'    => '',
									'images'  => array(),
								),
								$chips
							);
							?>
						</template>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save taxonomy', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
