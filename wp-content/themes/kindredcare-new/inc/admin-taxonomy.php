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

/* Priority 15: after How it works (14), matching the page order. */
add_action( 'admin_menu', 'kindredcare_taxonomy_admin_menu', 15 );
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
		'eyebrow'      => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'show_eyebrow' => ! empty( $input['show_eyebrow'] ),
		'title'        => sanitize_text_field( $input['title'] ?? '' ),
		'lead'         => sanitize_textarea_field( $input['lead'] ?? '' ),
		'show_lead'    => ! empty( $input['show_lead'] ),
		'cta_label'    => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'      => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'show_cta'     => ! empty( $input['show_cta'] ),
		'booking_buttons' => array(),
		'chips'        => array(),
		'tiles'        => array(),
	);

	foreach ( $defaults['booking_buttons'] as $index => $button ) {
		$row = is_array( $input['booking_buttons'][ $index ] ?? null ) ? $input['booking_buttons'][ $index ] : array();

		$clean['booking_buttons'][] = array(
			'key'     => $button['key'],
			'enabled' => ! empty( $row['enabled'] ),
			'label'   => sanitize_text_field( $row['label'] ?? '' ),
			'url'     => esc_url_raw( trim( (string) ( $row['url'] ?? '' ) ) ),
			'new_tab' => ! empty( $row['new_tab'] ),
		);
	}

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
			'key'     => $chip['key'],
			'label'   => '' !== trim( $label ) ? $label : $chip['label'],
			'enabled' => ! empty( $input['chips'][ $index ]['enabled'] ),
		);
	}

	$allowed = kindredcare_taxonomy_bundled_images();
	$groups  = kindredcare_taxonomy_groups();
	$rows    = is_array( $input['tiles'] ?? null ) ? array_values( $input['tiles'] ) : array();
	$slugs   = array();

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

		/*
		 * The detail page address. Blank means "derive it from the name"; either
		 * way it must be unique, or two services would answer the same URL and
		 * the second could never be reached.
		 */
		$slug = sanitize_title( (string) ( $row['slug'] ?? '' ) );
		if ( '' === $slug ) {
			$slug = kindredcare_service_slug( array( 'name' => $name ) );
		}
		$base_slug = $slug;
		$suffix    = 2;
		while ( in_array( $slug, $slugs, true ) ) {
			$slug = $base_slug . '-' . $suffix++;
		}
		$slugs[] = $slug;

		$clean['tiles'][] = array(
			'enabled'   => ! empty( $row['enabled'] ),
			'name'      => $name,
			'group'     => in_array( $group, $groups, true ) ? $group : ( $groups[0] ?? '' ),
			'rate'      => sanitize_text_field( $row['rate'] ?? '' ),
			'tier'      => sanitize_text_field( $row['tier'] ?? '' ),
			'blurb'     => sanitize_textarea_field( $row['blurb'] ?? '' ),
			// Layout, not content — allowlisted and never edited by hand.
			'mods'      => kindredcare_taxonomy_clean_mods( (string) ( $row['mods'] ?? '' ) ),
			'arrow'     => ! empty( $row['arrow'] ),
			'slug'      => $slug,
			'details'   => sanitize_textarea_field( $row['details'] ?? '' ),
			'included'  => sanitize_textarea_field( $row['included'] ?? '' ),
			'images'    => $images,
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
					<p class="kc-field__hint"><?php esc_html_e( 'The short summary under the title at the top of the service page.', 'kindredcare' ); ?></p>
				</div>

				<?php
				/*
				 * Detail page fields. The tile links to /services/{slug}/, which is
				 * built from this row — see inc/service-pages.php.
				 */
				$slug = '' !== trim( wp_strip_all_tags( (string) $tile['name'] ) ) ? kindredcare_service_slug( $tile ) : '';
				?>
				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-slug">
						<?php esc_html_e( 'Page address', 'kindredcare' ); ?>
					</label>
					<input type="text" id="kc-tile-<?php echo (int) $index; ?>-slug" name="<?php echo esc_attr( $base ); ?>[slug]" value="<?php echo esc_attr( (string) ( $tile['slug'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $slug ); ?>" />
					<p class="kc-field__hint">
						<?php
						if ( '' !== $slug ) {
							printf(
								/* translators: %s: link to the service's detail page. */
								esc_html__( 'The last part of the page\'s address. Leave blank to build it from the name. Currently: %s', 'kindredcare' ),
								'<a href="' . esc_url( kindredcare_service_url( $tile ) ) . '" target="_blank" rel="noopener">' . esc_html( kindredcare_service_url( $tile ) ) . '</a>'
							);
						} else {
							esc_html_e( 'The last part of the page\'s address. Leave blank to build it from the name.', 'kindredcare' );
						}
						?>
					</p>
				</div>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-details">
						<?php esc_html_e( 'About this service', 'kindredcare' ); ?>
					</label>
					<textarea id="kc-tile-<?php echo (int) $index; ?>-details" name="<?php echo esc_attr( $base ); ?>[details]" rows="6"><?php echo esc_textarea( (string) ( $tile['details'] ?? '' ) ); ?></textarea>
					<p class="kc-field__hint"><?php esc_html_e( 'The longer description on the service page. Leave a blank line between paragraphs. Leave empty to hide the section.', 'kindredcare' ); ?></p>
				</div>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-tile-<?php echo (int) $index; ?>-included">
						<?php esc_html_e( "What's included", 'kindredcare' ); ?>
					</label>
					<textarea id="kc-tile-<?php echo (int) $index; ?>-included" name="<?php echo esc_attr( $base ); ?>[included]" rows="5"><?php echo esc_textarea( (string) ( $tile['included'] ?? '' ) ); ?></textarea>
					<p class="kc-field__hint"><?php esc_html_e( 'One item per line, each shown with a tick. Leave empty to hide the section.', 'kindredcare' ); ?></p>
				</div>

				<?php
				/*
				 * No per-service button: the booking card's buttons are the
				 * shared pair under "Booking card buttons" further down.
				 */
				?>

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
				<?php // Layout like mods: carried through the save so the tile keeps its arrow. ?>
				<input type="hidden" name="<?php echo esc_attr( $base ); ?>[arrow]" value="<?php echo ! empty( $tile['arrow'] ) ? '1' : ''; ?>" />
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
					<?php
					kindredcare_admin_note(
						__( 'Each part of this tile has its own switch. <strong>Switching one off hides it from the page but keeps what you wrote</strong>, so it is there to turn back on — clearing the field is what throws the wording away. The heading has no switch: it names the section for screen readers and something has to.', 'kindredcare' )
					);
					?>

					<div class="kc-field <?php echo empty( $section['show_eyebrow'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<label class="kc-field__label" for="kc-tax-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
							<?php
							kindredcare_admin_toggle(
								$option . '[show_eyebrow]',
								! empty( $section['show_eyebrow'] ),
								__( 'Shown', 'kindredcare' ),
								array( 'kc-toggle-row' => '1' )
							);
							?>
						</div>
						<input type="text" id="kc-tax-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-tax-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-tax-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'Names the section for screen readers, so it cannot be empty. It counts the services out loud — check it still matches if you add or remove tiles.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field <?php echo empty( $section['show_lead'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<label class="kc-field__label" for="kc-tax-lead"><?php esc_html_e( 'Lead paragraph', 'kindredcare' ); ?></label>
							<?php
							kindredcare_admin_toggle(
								$option . '[show_lead]',
								! empty( $section['show_lead'] ),
								__( 'Shown', 'kindredcare' ),
								array( 'kc-toggle-row' => '1' )
							);
							?>
						</div>
						<textarea id="kc-tax-lead" name="<?php echo esc_attr( $option ); ?>[lead]" rows="4"><?php echo esc_textarea( $section['lead'] ); ?></textarea>
					</div>

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
								<label class="kc-field__label" for="kc-tax-cta-label"><?php esc_html_e( 'Button label', 'kindredcare' ); ?></label>
								<input type="text" id="kc-tax-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $section['cta_label'] ); ?>" />
							</div>
							<div>
								<label class="kc-field__label" for="kc-tax-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
								<input type="text" id="kc-tax-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $section['cta_url'] ); ?>" />
							</div>
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
					<?php
					kindredcare_admin_note(
						__( 'A chip switched off leaves the row but keeps its group — every tile filed under it still shows, and the group is still offered when you edit a tile. <strong>The first chip left showing is the one the row opens on</strong>, so if you hide the reset chip, make sure whatever leads instead is a sensible place to start.', 'kindredcare' )
					);
					?>

					<?php foreach ( $section['chips'] as $index => $chip ) : ?>
						<div class="kc-row <?php echo empty( $chip['enabled'] ) ? 'is-off' : ''; ?>">
							<span class="kc-row__brand"><code><?php echo esc_html( $chip['key'] ); ?></code></span>
							<div class="kc-row__control">
								<input
									type="text"
									name="<?php echo esc_attr( $option ); ?>[chips][<?php echo (int) $index; ?>][label]"
									value="<?php echo esc_attr( $chip['label'] ); ?>"
									aria-label="<?php
										/* translators: %s: chip key. */
										echo esc_attr( sprintf( __( 'Label for the %s chip', 'kindredcare' ), $chip['key'] ) );
									?>"
								/>
								<?php
								kindredcare_admin_toggle(
									$option . '[chips][' . (int) $index . '][enabled]',
									! empty( $chip['enabled'] ),
									__( 'Shown', 'kindredcare' ),
									array( 'kc-toggle-row' => '1' )
								);
								?>
							</div>
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
									'enabled'   => true,
									'name'      => '',
									'group'     => array_key_first( $chips ) ?? '',
									'rate'      => '',
									'tier'      => '',
									'blurb'     => '',
									'mods'      => '',
									'slug'      => '',
									'details'   => '',
									'included'  => '',
									'cta_label' => 'Book now',
									'cta_url'   => 'https://app.kindredcare.ca/signup',
									'images'    => array(),
								),
								$chips
							);
							?>
						</template>
					</div>
				</div>
			</section>

			<!-- Booking card buttons -------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7h16v10H4zM8 12h8' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Booking card buttons', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The only buttons in the booking card, under "Typical rate" on every service detail page.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					$kc_button_titles = array(
						'register' => __( 'First button', 'kindredcare' ),
						'find'     => __( 'Second button', 'kindredcare' ),
					);

					foreach ( $section['booking_buttons'] as $index => $button ) :
						$base = $option . '[booking_buttons][' . $index . ']';
						$id   = 'kc-tax-booking-' . $button['key'];
						?>
						<div class="kc-linkcard<?php echo $button['enabled'] ? '' : ' is-off'; ?>" data-kc-item>
							<div class="kc-linkcard__head">
								<span class="kc-linkcard__title"><?php echo esc_html( $kc_button_titles[ $button['key'] ] ?? '' ); ?></span>
								<?php kindredcare_admin_toggle( $base . '[enabled]', (bool) $button['enabled'], __( 'Show this button', 'kindredcare' ), array( 'kc-toggle-row' => '1' ) ); ?>
							</div>
							<div class="kc-field--split">
								<div>
									<label class="kc-field__label" for="<?php echo esc_attr( $id . '-label' ); ?>"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
									<input type="text" id="<?php echo esc_attr( $id . '-label' ); ?>" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( (string) $button['label'] ); ?>" />
								</div>
								<div>
									<label class="kc-field__label" for="<?php echo esc_attr( $id . '-url' ); ?>"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
									<input type="text" id="<?php echo esc_attr( $id . '-url' ); ?>" name="<?php echo esc_attr( $base . '[url]' ); ?>" value="<?php echo esc_attr( (string) $button['url'] ); ?>" />
									<?php kindredcare_admin_new_tab_toggle( $base . '[new_tab]', (bool) $button['new_tab'] ); ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
					<p class="kc-field__hint">
						<?php
						printf(
							/* translators: %s: the gig list address. */
							esc_html__( 'Switch a button off to hide it, or clear its text or link. The gig list is at %s.', 'kindredcare' ),
							esc_html( home_url( '/gigs/' ) )
						);
						?>
					</p>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save taxonomy', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
