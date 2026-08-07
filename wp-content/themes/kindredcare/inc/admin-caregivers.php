<?php
/**
 * Admin: KindredCare -> Founding caregivers.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_CAREGIVERS_MENU_SLUG = 'kindredcare-caregivers';

/**
 * Capability callback for this option group.
 */
function kindredcare_caregivers_capability(): string {
	return kindredcare_admin_capability();
}

/*
 * Priority 15: after Hero (10), Services (11), How it works (12), Taxonomy (13)
 * and Benefits (14), so the submenu reads in the order the sections appear on
 * the page.
 */
add_action( 'admin_menu', 'kindredcare_caregivers_admin_menu', 15 );
/**
 * Add the Founding caregivers screen under the KindredCare menu.
 */
function kindredcare_caregivers_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Founding caregivers', 'kindredcare' ),
		__( 'Founding caregivers', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_CAREGIVERS_MENU_SLUG,
		'kindredcare_caregivers_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_caregivers_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_caregivers_register_setting(): void {
	register_setting(
		'kindredcare_caregivers_group',
		KINDREDCARE_CAREGIVERS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_caregivers_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_caregivers_group', 'kindredcare_caregivers_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_caregivers_sanitize( $input ): array {
	$defaults = kindredcare_caregivers_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'   => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'     => sanitize_text_field( $input['title'] ?? '' ),
		'benefits'  => array(),
		'cta_label' => sanitize_text_field( $input['cta_label'] ?? '' ),
		/*
		 * esc_url_raw, not sanitize_text_field: the default is the in-page
		 * anchor #for-caregivers, and this field is also where the Laravel
		 * signup URL goes once it exists. Both survive esc_url_raw.
		 */
		'cta_url'   => esc_url_raw( trim( (string) ( $input['cta_url'] ?? '' ) ) ),
		'cards'     => array(),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	$lines = is_array( $input['benefits'] ?? null ) ? array_values( $input['benefits'] ) : array();

	foreach ( $lines as $line ) {
		if ( count( $clean['benefits'] ) >= KINDREDCARE_CAREGIVERS_MAX_BENEFITS ) {
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

		$clean['benefits'][] = $line;
	}

	$rows = is_array( $input['cards'] ?? null ) ? array_values( $input['cards'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['cards'] ) >= KINDREDCARE_CAREGIVERS_MAX_CARDS ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$name = sanitize_text_field( $row['name'] ?? '' );

		// A numbered card with no heading reads as a loading state.
		if ( '' === trim( $name ) ) {
			continue;
		}

		$clean['cards'][] = array(
			'enabled' => ! empty( $row['enabled'] ),
			'name'    => $name,
			'text'    => sanitize_textarea_field( $row['text'] ?? '' ),
			'meta'    => sanitize_text_field( $row['meta'] ?? '' ),
			// 0 falls back to the hand-drawn illustration for that position.
			'icon'    => absint( $row['icon'] ?? 0 ),
		);
	}

	if ( ! $clean['benefits'] ) {
		$clean['benefits'] = $defaults['benefits'];
	}

	if ( ! $clean['cards'] ) {
		$clean['cards'] = $defaults['cards'];
	}

	// An empty slider would leave the right-hand column blank beside the pitch.
	$any_enabled = false;
	foreach ( $clean['cards'] as $card ) {
		$any_enabled = $any_enabled || $card['enabled'];
	}
	if ( ! $any_enabled ) {
		$clean['cards'][0]['enabled'] = true;
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_caregivers_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_caregivers_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_CAREGIVERS_MENU_SLUG !== $hook_suffix ) {
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
function kindredcare_caregivers_benefit_row( string $option, int $index, string $value ): void {
	?>
	<div class="kc-lines__row" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $option ); ?>[benefits][<?php echo (int) $index; ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			aria-label="<?php esc_attr_e( 'Offer', 'kindredcare' ); ?>"
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
 * Print one journey card row, as a collapsed accordion.
 *
 * @param int                  $index    Row position.
 * @param array<string, mixed> $card     Row data.
 * @param bool                 $expanded Whether to render it open.
 */
function kindredcare_caregivers_card_row( int $index, array $card, bool $expanded = false ): void {
	$option  = KINDREDCARE_CAREGIVERS_OPTION;
	$base    = $option . '[cards][' . $index . ']';
	$body_id = 'kc-gig-' . $index . '-body';
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
					data-empty="<?php esc_attr_e( 'Untitled card', 'kindredcare' ); ?>"
				><?php echo esc_html( (string) ( $card['name'] ?? '' ) ); ?></span>
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
					__( 'Remove this card', 'kindredcare' ),
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
			/*
			 * No bundled-file fallback is passed: the default is not an image at
			 * all, it is the hand-drawn SVG for this position, which the picker
			 * has no way to preview. An empty frame therefore means "using the
			 * built-in drawing".
			 */
			kindredcare_admin_media_field(
				'kc-gig-' . $index . '-icon',
				$base . '[icon]',
				(int) ( $card['icon'] ?? 0 ),
				'',
				array(
					'empty_note'  => __( 'Using the built-in drawing', 'kindredcare' ),
					'reset_label' => __( 'Clear — go back to the built-in drawing', 'kindredcare' ),
				)
			);
			?>

			<div class="kc-repeat__main">
				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-gig-<?php echo (int) $index; ?>-name">
						<?php esc_html_e( 'Card title', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-gig-<?php echo (int) $index; ?>-name"
						name="<?php echo esc_attr( $base ); ?>[name]"
						value="<?php echo esc_attr( (string) ( $card['name'] ?? '' ) ); ?>"
						data-kc-acc-label
					/>
				</div>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-gig-<?php echo (int) $index; ?>-text">
						<?php esc_html_e( 'Description', 'kindredcare' ); ?>
					</label>
					<textarea
						id="kc-gig-<?php echo (int) $index; ?>-text"
						name="<?php echo esc_attr( $base ); ?>[text]"
						rows="3"
					><?php echo esc_textarea( (string) ( $card['text'] ?? '' ) ); ?></textarea>
					<p class="kc-field__hint"><?php esc_html_e( 'One sentence, written to the caregiver as "you". The cards sit side by side at a fixed height, so a much longer one here sets the height for all of them.', 'kindredcare' ); ?></p>
				</div>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-gig-<?php echo (int) $index; ?>-meta">
						<?php esc_html_e( 'Footer line', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-gig-<?php echo (int) $index; ?>-meta"
						name="<?php echo esc_attr( $base ); ?>[meta]"
						value="<?php echo esc_attr( (string) ( $card['meta'] ?? '' ) ); ?>"
					/>
					<p class="kc-field__hint"><?php esc_html_e( 'The small line at the foot of the card. Two or three words, separated by · . Leave blank to remove it.', 'kindredcare' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the Founding caregivers admin screen.
 */
function kindredcare_caregivers_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section  = kindredcare_caregivers();
	$option   = KINDREDCARE_CAREGIVERS_OPTION;
	$benefits = $section['benefits'];
	$cards    = $section['cards'];
	$active   = count( kindredcare_caregivers_active_cards( $section ) );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Founding caregivers', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The supply side of the marketplace: the pitch to caregivers on the left, and the slider walking through what joining actually involves. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_caregivers_group' ); ?>

			<!-- The pitch ------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'The pitch', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The left-hand column: heading, the tick list, and the button.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'This section exists to <strong>recruit caregivers</strong>, not to show them off. There are no listings yet, and nothing here may imply there are — no invented names, faces, counts or ratings. When real gigs exist this same slot switches to showing them.', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-cg-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-cg-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-cg-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-cg-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'What they get', 'kindredcare' ); ?></span>

						<div
							class="kc-repeat"
							data-kc-repeat="benefits"
							data-kc-max="<?php echo (int) KINDREDCARE_CAREGIVERS_MAX_BENEFITS; ?>"
							data-kc-min="1"
						>
							<div class="kc-lines" data-kc-list>
								<?php foreach ( $benefits as $index => $benefit ) : ?>
									<?php kindredcare_caregivers_benefit_row( $option, (int) $index, (string) $benefit ); ?>
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
										count( $benefits ),
										(int) KINDREDCARE_CAREGIVERS_MAX_BENEFITS
									);
									?>
								</span>
							</div>

							<template data-kc-template>
								<?php kindredcare_caregivers_benefit_row( $option, 0, '' ); ?>
							</template>
						</div>

						<p class="kc-field__hint"><?php esc_html_e( 'Each gets a tick. Keep them to one line — they wrap awkwardly against the slider beside them. A blank line is dropped when saved.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-cg-cta-label"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
							<input type="text" id="kc-cg-cta-label" name="<?php echo esc_attr( $option ); ?>[cta_label]" value="<?php echo esc_attr( $section['cta_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-cg-cta-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-cg-cta-url" name="<?php echo esc_attr( $option ); ?>[cta_url]" value="<?php echo esc_attr( $section['cta_url'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Defaults to #for-caregivers, the section further down this page. Point it at the caregiver signup once that flow can produce a bookable listing. Clearing either field removes the button.', 'kindredcare' ); ?></p>
						</div>
					</div>
				</div>
			</section>

			<!-- The slider ------------------------------------------------ -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 6h10v12H4zM17 8h3v8h-3' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Journey cards', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: maximum number of cards. */
								esc_html__( 'Up to %d. Numbering and the "Step N of M" line read by screen readers are both worked out from position, so reordering or hiding a card renumbers the rest.', 'kindredcare' ),
								(int) KINDREDCARE_CAREGIVERS_MAX_CARDS
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
							__( '<strong>Best icon size: %1$s</strong> — square, transparent PNG. It draws at %2$s, so twice that keeps it sharp on retina screens. Leave the picker empty to keep the built-in drawing for that position; there are %3$d of them and a later card reuses an earlier one. The built-in drawings are line art that takes the section\'s colour automatically — an uploaded image keeps its own colours. WordPress blocks SVG uploads unless a plugin allows them.', 'kindredcare' ),
							'<code>52 × 52</code>',
							'<code>26 × 26</code>',
							count( kindredcare_caregivers_icons() )
						)
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="cards"
						data-kc-max="<?php echo (int) KINDREDCARE_CAREGIVERS_MAX_CARDS; ?>"
						data-kc-min="1"
					>
						<div data-kc-list>
							<?php foreach ( $cards as $index => $card ) : ?>
								<?php kindredcare_caregivers_card_row( (int) $index, $card ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add card', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%1$s of %2$s cards shown · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: cards shown, 2: cards total, 3: maximum allowed. */
									esc_html__( '%1$s of %2$s cards shown · %3$s maximum', 'kindredcare' ),
									(int) $active,
									count( $cards ),
									(int) KINDREDCARE_CAREGIVERS_MAX_CARDS
								);
								?>
							</span>
						</div>

						<p class="kc-field__hint" data-kc-warn-below="2" <?php echo $active > 1 ? 'hidden' : ''; ?>>
							<strong><?php esc_html_e( 'With one card shown there is nothing to swipe to, and the arrows and dots have no second slide to move between.', 'kindredcare' ); ?></strong>
						</p>

						<template data-kc-template>
							<?php
							kindredcare_caregivers_card_row(
								0,
								array(
									'enabled' => true,
									'name'    => '',
									'text'    => '',
									'meta'    => '',
									'icon'    => 0,
								)
							);
							?>
						</template>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save founding caregivers', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
