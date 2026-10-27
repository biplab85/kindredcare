<?php
/**
 * Admin: KindredCare -> Services.
 *
 * The "Services we provide" section. Same Settings API arrangement as the Hero
 * screen, and the same furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_SERVICES_MENU_SLUG = 'kindredcare-services';

/**
 * Capability callback for this option group.
 */
function kindredcare_services_capability(): string {
	return kindredcare_admin_capability();
}

/*
 * Priority 13: after Hero (10, which creates the parent — add_submenu_page()
 * needs it to already exist), Trust strip (11) and Our story (12), so the
 * submenu reads in the order the sections appear on the page.
 */
add_action( 'admin_menu', 'kindredcare_services_admin_menu', 13 );
/**
 * Add the Services screen under the KindredCare menu.
 */
function kindredcare_services_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Services', 'kindredcare' ),
		__( 'Services', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_SERVICES_MENU_SLUG,
		'kindredcare_services_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_services_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_services_register_setting(): void {
	register_setting(
		'kindredcare_services_group',
		KINDREDCARE_SERVICES_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_services_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_services_group', 'kindredcare_services_capability' );

/**
 * Sanitise the submitted section content.
 *
 * The stored shape is the security boundary — the option is rendered straight
 * into the front page — so anything outside the schema is dropped rather than
 * carried through.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_services_sanitize( $input ): array {
	$defaults = kindredcare_service_groups_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'   => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'     => sanitize_text_field( $input['title'] ?? '' ),
		'cta_label' => sanitize_text_field( $input['cta_label'] ?? '' ),
		'cta_url'   => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'cta_new_tab' => ! empty( $input['cta_new_tab'] ),
		'cards'     => array(),
	);

	/*
	 * The intro heading is the section's accessible name — it is what
	 * aria-labelledby points at — so an empty one would leave the section
	 * unnamed. Same guard as the hero headline.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	$allowed = kindredcare_service_groups_bundled_images();
	$rows    = is_array( $input['cards'] ?? null ) ? array_values( $input['cards'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['cards'] ) >= KINDREDCARE_SERVICES_MAX_CARDS ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$title = sanitize_text_field( $row['title'] ?? '' );

		// An untitled card is a coloured block with nothing in it.
		if ( '' === trim( $title ) ) {
			continue;
		}

		$file = (string) ( $row['file'] ?? '' );

		/*
		 * Variable length now, capped, with blanks discarded rather than stored.
		 * An empty line renders nothing, so keeping it would mean the admin
		 * showed a row the front end did not — the list is re-packed instead.
		 */
		$items = array();
		$submitted = is_array( $row['items'] ?? null ) ? array_values( $row['items'] ) : array();

		foreach ( $submitted as $item ) {
			if ( count( $items ) >= KINDREDCARE_SERVICES_MAX_ITEMS ) {
				break;
			}
			$item = sanitize_text_field( $item );
			if ( '' !== trim( $item ) ) {
				$items[] = $item;
			}
		}

		/*
		 * sanitize_hex_color() returns null for anything that is not #rgb or
		 * #rrggbb, which is exactly the fallback wanted: an unparseable colour
		 * becomes "automatic" rather than reaching the style attribute.
		 */
		$ground = sanitize_hex_color( (string) ( $row['ground'] ?? '' ) );

		$clean['cards'][] = array(
			'enabled' => ! empty( $row['enabled'] ),
			'title'   => $title,
			'url'     => esc_url_raw( trim( (string) ( $row['url'] ?? '' ) ) ),
			'items'   => $items,
			'more'    => sanitize_text_field( $row['more'] ?? '' ),
			'image'   => absint( $row['image'] ?? 0 ),
			'file'    => in_array( $file, $allowed, true ) ? $file : '',
			'ground'  => $ground ?? '',
		);
	}

	if ( ! $clean['cards'] ) {
		$clean['cards'] = $defaults['cards'];
	}

	// An empty list would leave the sticky intro nothing to travel past.
	$any_enabled = false;
	foreach ( $clean['cards'] as $card ) {
		$any_enabled = $any_enabled || $card['enabled'];
	}
	if ( ! $any_enabled ) {
		$clean['cards'][0]['enabled'] = true;
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_services_admin_assets' );
/**
 * Load the shared panel assets on the Services screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_services_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_SERVICES_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one service line within a card.
 *
 * No id — these rows are cloned and renumbered, and an id would have to be kept
 * unique across every card on the page. The aria-label carries the name instead.
 *
 * @param string $base  Field name prefix for the owning card.
 * @param int    $index Line position.
 * @param string $value Current text.
 */
function kindredcare_services_item_row( string $base, int $index, string $value ): void {
	?>
	<div class="kc-lines__row" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $base ); ?>[items][<?php echo (int) $index; ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			aria-label="<?php esc_attr_e( 'Service', 'kindredcare' ); ?>"
		/>
		<?php
		kindredcare_admin_icon_button(
			'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
			__( 'Remove this service', 'kindredcare' ),
			array( 'kc-remove' => '1' ),
			true
		);
		?>
	</div>
	<?php
}

/**
 * Print one service card row, as a collapsed accordion.
 *
 * Each card carries a name, a link, four service lines, a closing line and an
 * image. Five of those open at once is a wall of inputs with no way to tell
 * which card you are in, so the row collapses to its name and the header
 * mirrors that name as it is typed.
 *
 * @param int                  $index    Row position.
 * @param array<string, mixed> $card     Row data.
 * @param bool                 $expanded Whether to render it open.
 */
function kindredcare_services_card_row( int $index, array $card, bool $expanded = false ): void {
	$option  = KINDREDCARE_SERVICES_OPTION;
	$base    = $option . '[cards][' . $index . ']';
	$body_id = 'kc-group-' . $index . '-body';
	?>
	<div class="kc-repeat__item kc-acc <?php echo empty( $card['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
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
					data-empty="<?php esc_attr_e( 'Untitled group', 'kindredcare' ); ?>"
				><?php echo esc_html( $card['title'] ); ?></span>
			</button>

			<div class="kc-repeat__tools">
				<?php
				kindredcare_admin_toggle(
					$base . '[enabled]',
					! empty( $card['enabled'] ),
					__( 'Shown', 'kindredcare' ),
					array( 'kc-toggle-row' => '1' )
				);

				kindredcare_admin_icon_button(
					'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
					__( 'Remove this service group', 'kindredcare' ),
					array( 'kc-remove' => '1' ),
					true
				);
				?>

				<?php
				/*
				 * Last control in the row. aria-hidden and out of the tab order
				 * on purpose: the title button to its left is the real expander,
				 * and announcing two controls for one panel helps nobody.
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
		kindredcare_admin_media_field(
			'kc-group-' . $index . '-image',
			$base . '[image]',
			(int) $card['image'],
			(string) $card['file']
		);
		?>

		<div class="kc-repeat__main">

			<div class="kc-field kc-field--split" style="margin:0">
				<div>
					<label class="kc-field__label" for="kc-group-<?php echo (int) $index; ?>-title">
						<?php esc_html_e( 'Group name', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-group-<?php echo (int) $index; ?>-title"
						name="<?php echo esc_attr( $base ); ?>[title]"
						value="<?php echo esc_attr( $card['title'] ); ?>"
						data-kc-acc-label
					/>
				</div>
				<div>
					<label class="kc-field__label" for="kc-group-<?php echo (int) $index; ?>-url">
						<?php esc_html_e( 'Link', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-group-<?php echo (int) $index; ?>-url"
						name="<?php echo esc_attr( $base ); ?>[url]"
						value="<?php echo esc_attr( $card['url'] ); ?>"
					/>
				</div>
			</div>

			<div class="kc-field" style="margin:0">
				<span class="kc-field__label"><?php esc_html_e( 'Services listed', 'kindredcare' ); ?></span>

				<?php
				$items = $card['items'];
				if ( ! $items ) {
					$items = array( '' );
				}
				?>
				<div
					class="kc-repeat"
					data-kc-repeat="items"
					data-kc-max="<?php echo (int) KINDREDCARE_SERVICES_MAX_ITEMS; ?>"
					data-kc-min="1"
				>
					<div class="kc-lines" data-kc-list>
						<?php foreach ( $items as $i => $item ) : ?>
							<?php kindredcare_services_item_row( $base, (int) $i, (string) $item ); ?>
						<?php endforeach; ?>
					</div>

					<div class="kc-lines__foot">
						<?php kindredcare_admin_add_button( __( 'Add service', 'kindredcare' ) ); ?>
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
								(int) KINDREDCARE_SERVICES_MAX_ITEMS
							);
							?>
						</span>
					</div>

					<?php
					/*
					 * The card is a flex row: the body on the left, a fixed
					 * 306x354 photograph on the right. Five content lines is what
					 * fills that height — past that the body outgrows the
					 * photograph and the card gets taller than its own image.
					 */
					?>
					<p class="kc-field__hint" data-kc-warn-above="5" <?php echo count( $items ) <= 5 ? 'hidden' : ''; ?>>
						<strong><?php esc_html_e( 'Past five lines (including the closing line) the card grows taller than its photograph.', 'kindredcare' ); ?></strong>
					</p>

					<template data-kc-template>
						<?php kindredcare_services_item_row( $base, 0, '' ); ?>
					</template>
				</div>

				<p class="kc-field__hint"><?php esc_html_e( 'A blank line is dropped when saved.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field" style="margin:0">
				<div class="kc-field--inline">
					<span class="kc-field__label"><?php esc_html_e( 'Background colour', 'kindredcare' ); ?></span>
					<?php
					kindredcare_admin_colour_field(
						$base . '[ground]',
						(string) ( $card['ground'] ?? '' ),
						kindredcare_service_group_auto_colour( $index ),
						__( 'Card background colour', 'kindredcare' ),
						sprintf(
							/* translators: %d: tint number in the theme palette. */
							__( 'Automatic — tint %d for this position', 'kindredcare' ),
							kindredcare_service_group_ground( $index )
						)
					);
					?>
				</div>
				<p class="kc-field__hint"><?php esc_html_e( 'Left automatic, the colour follows the card\'s position and cycles through the theme\'s five pastels. Pick one to pin this card to a colour of its own.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field" style="margin:0">
				<label class="kc-field__label" for="kc-group-<?php echo (int) $index; ?>-more">
					<?php esc_html_e( 'Closing line', 'kindredcare' ); ?>
				</label>
				<input
					type="text"
					id="kc-group-<?php echo (int) $index; ?>-more"
					name="<?php echo esc_attr( $base ); ?>[more]"
					value="<?php echo esc_attr( $card['more'] ); ?>"
				/>
				<p class="kc-field__hint"><?php esc_html_e( 'The muted fifth line, e.g. "+2 more, including meal preparation". It is written by hand — nothing counts the services for you.', 'kindredcare' ); ?></p>
			</div>

			<input type="hidden" name="<?php echo esc_attr( $base ); ?>[file]" value="<?php echo esc_attr( $card['file'] ); ?>" />
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the Services admin screen.
 */
function kindredcare_services_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_service_groups();
	$option  = KINDREDCARE_SERVICES_OPTION;
	$cards   = $section['cards'];
	$active  = count( kindredcare_service_groups_active_cards( $section ) );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Services we provide', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the front page. */
				esc_html__( 'The section where the intro pins in place while the service cards scroll past it. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#service-groups' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_services_group' ); ?>

			<!-- Intro ----------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Intro', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The column that stays pinned on the left while the cards move. It is on screen for the whole section, so it carries the section\'s promise.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<label class="kc-field__label" for="kc-svc-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-svc-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-svc-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-svc-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the whole section for screen readers, so it cannot be empty. If you change the number of cards, check it still reads true — it says "Five kinds of help" by default.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-svc-cta-label"><?php esc_html_e( 'Button label', 'kindredcare' ); ?></label>
							<input type="text" id="kc-svc-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $section['cta_label'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Leave blank to remove the button.', 'kindredcare' ); ?></p>
						</div>
						<div>
							<label class="kc-field__label" for="kc-svc-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-svc-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $section['cta_url'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'A full URL, or an on-page anchor such as #services.', 'kindredcare' ); ?></p>
							<?php kindredcare_admin_new_tab_toggle( $option . '[cta_new_tab]', kindredcare_link_new_tab( $section['cta_new_tab'] ?? null, (string) $section['cta_url'] ) ); ?>
						</div>
					</div>
				</div>
			</section>

			<!-- Cards ----------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 4h7v16H4zM13 4h7v16h-7z' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Service groups', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of cards. */
								esc_html__( 'Up to %d cards. Each card\'s background tint comes from its position and cycles through the five the stylesheet defines, so a sixth card repeats the first tint.', 'kindredcare' ),
								(int) KINDREDCARE_SERVICES_MAX_CARDS
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
							__( '<strong>Best image size: %1$s</strong> (%2$s, portrait). The card crops to fill, so a landscape photograph loses its sides. JPG, ideally under 250 KB.', 'kindredcare' ),
							'<code>612 × 708</code>',
							'306:354'
						)
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="cards"
						data-kc-max="<?php echo (int) KINDREDCARE_SERVICES_MAX_CARDS; ?>"
						data-kc-min="1"
						<?php
						/*
						 * The automatic colour depends on position, so it moves
						 * when rows are added, removed or reordered. The palette
						 * travels with the repeater so the swatches can be
						 * re-resolved client-side instead of going stale.
						 */
						?>
						data-kc-palette="<?php echo esc_attr( wp_json_encode( kindredcare_service_group_palette() ) ); ?>"
					>
						<div data-kc-list>
							<?php foreach ( $cards as $index => $card ) : ?>
								<?php kindredcare_services_card_row( (int) $index, $card ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add service group', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%1$s of %2$s groups shown · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: cards shown, 2: cards total, 3: maximum allowed. */
									esc_html__( '%1$s of %2$s groups shown · %3$s maximum', 'kindredcare' ),
									(int) $active,
									count( $cards ),
									(int) KINDREDCARE_SERVICES_MAX_CARDS
								);
								?>
							</span>
						</div>

						<p class="kc-field__hint" data-kc-warn-below="3" <?php echo $active >= 3 ? 'hidden' : ''; ?>>
							<strong><?php esc_html_e( 'With fewer than three cards there is not enough height for the intro to pin, so it simply scrolls with the page.', 'kindredcare' ); ?></strong>
						</p>

						<?php
						/*
						 * Cloned by assets/admin/panel.js. Emitted at index 0 and
						 * renumbered on insert. A new card has no bundled fallback,
						 * so it needs an image chosen before it shows a photograph.
						 */
						?>
						<template data-kc-template>
							<?php
							kindredcare_services_card_row(
								0,
								array(
									'enabled' => true,
									'title'   => '',
									'url'     => '#services',
									'items'   => array( '' ),
									'more'    => '',
									'image'   => 0,
									'file'    => '',
									'ground'  => '',
								)
							);
							?>
						</template>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save services', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
