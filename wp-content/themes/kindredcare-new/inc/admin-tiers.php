<?php
/**
 * Admin: KindredCare -> Verification tiers.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * Unlike the other screens this one has no add/remove: there are exactly three
 * tiers and the reasons are in inc/tiers.php. The repeatable parts are the
 * checks inside a card and the rows inside its modal.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_TIERS_MENU_SLUG = 'kindredcare-tiers';

/**
 * Capability callback for this option group.
 */
function kindredcare_tiers_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 18: after Founding caregivers (17), matching the page order. */
add_action( 'admin_menu', 'kindredcare_tiers_admin_menu', 18 );
/**
 * Add the Verification tiers screen under the KindredCare menu.
 */
function kindredcare_tiers_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Verification tiers', 'kindredcare' ),
		__( 'Verification tiers', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_TIERS_MENU_SLUG,
		'kindredcare_tiers_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_tiers_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_tiers_register_setting(): void {
	register_setting(
		'kindredcare_tiers_group',
		KINDREDCARE_TIERS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_tiers_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_tiers_group', 'kindredcare_tiers_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_tiers_sanitize( $input ): array {
	$defaults = kindredcare_tiers_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'    => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'      => sanitize_text_field( $input['title'] ?? '' ),
		'lead'       => sanitize_textarea_field( $input['lead'] ?? '' ),
		'more_label' => sanitize_text_field( $input['more_label'] ?? '' ),
		'tiers'      => array(),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	$rows = is_array( $input['tiers'] ?? null ) ? array_values( $input['tiers'] ) : array();

	for ( $i = 0; $i < KINDREDCARE_TIERS_COUNT; $i++ ) {
		$row      = is_array( $rows[ $i ] ?? null ) ? $rows[ $i ] : array();
		$fallback = $defaults['tiers'][ $i ];

		$name = sanitize_text_field( $row['name'] ?? '' );

		/*
		 * The name is the card's heading and the source of the modal's element
		 * id. An empty one would leave an unlabelled card wired to a button
		 * pointing at #tier- with nothing after it.
		 */
		if ( '' === trim( $name ) ) {
			$name = $fallback['name'];
		}

		$checks = array();
		foreach ( ( is_array( $row['checks'] ?? null ) ? array_values( $row['checks'] ) : array() ) as $check ) {
			if ( count( $checks ) >= KINDREDCARE_TIERS_MAX_CHECKS ) {
				break;
			}
			if ( ! is_scalar( $check ) ) {
				continue;
			}

			$check = sanitize_text_field( (string) $check );

			// A blank check renders as a tick with nothing beside it.
			if ( '' === trim( $check ) ) {
				continue;
			}

			$checks[] = $check;
		}

		$items = array();
		foreach ( ( is_array( $row['modal_items'] ?? null ) ? array_values( $row['modal_items'] ) : array() ) as $item ) {
			if ( count( $items ) >= KINDREDCARE_TIERS_MAX_MODAL_ITEMS ) {
				break;
			}
			if ( ! is_array( $item ) ) {
				continue;
			}

			$term = sanitize_text_field( $item['term'] ?? '' );

			// A definition with no term above it has nothing to define.
			if ( '' === trim( $term ) ) {
				continue;
			}

			$items[] = array(
				'term' => $term,
				'def'  => sanitize_textarea_field( $item['def'] ?? '' ),
			);
		}

		$clean['tiers'][] = array(
			'badge'       => sanitize_text_field( $row['badge'] ?? '' ),
			'name'        => $name,
			'desc'        => sanitize_textarea_field( $row['desc'] ?? '' ),
			'checks'      => $checks ?: $fallback['checks'],
			'unlocks'     => sanitize_text_field( $row['unlocks'] ?? '' ),
			'modal_title' => sanitize_text_field( $row['modal_title'] ?? '' ),
			'modal_items' => $items ?: $fallback['modal_items'],
		);
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_tiers_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_tiers_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_TIERS_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one check line.
 *
 * @param string $base  Field name prefix for this tier.
 * @param int    $index Row position.
 * @param string $value Current text.
 */
function kindredcare_tiers_check_row( string $base, int $index, string $value ): void {
	?>
	<div class="kc-lines__row" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $base ); ?>[checks][<?php echo (int) $index; ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			aria-label="<?php esc_attr_e( 'Check', 'kindredcare' ); ?>"
		/>
		<?php
		kindredcare_admin_icon_button(
			'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
			__( 'Remove this check', 'kindredcare' ),
			array( 'kc-remove' => '1' ),
			true
		);
		?>
	</div>
	<?php
}

/**
 * Print one modal row — a term and its explanation.
 *
 * @param string                $base  Field name prefix for this tier.
 * @param int                   $tier  Tier position, for unique ids.
 * @param int                   $index Row position.
 * @param array<string, string> $item  Row data.
 */
function kindredcare_tiers_modal_row( string $base, int $tier, int $index, array $item ): void {
	$id = 'kc-tier' . $tier . '-item-' . $index;
	?>
	<div class="kc-lines__row kc-lines__row--stack" data-kc-item>
		<div class="kc-field" style="margin:0;flex:1">
			<label class="kc-field__label" for="<?php echo esc_attr( $id ); ?>-term">
				<?php esc_html_e( 'Check name', 'kindredcare' ); ?>
			</label>
			<input
				type="text"
				id="<?php echo esc_attr( $id ); ?>-term"
				name="<?php echo esc_attr( $base ); ?>[modal_items][<?php echo (int) $index; ?>][term]"
				value="<?php echo esc_attr( (string) ( $item['term'] ?? '' ) ); ?>"
			/>

			<label class="kc-field__label" for="<?php echo esc_attr( $id ); ?>-def" style="margin-top:10px">
				<?php esc_html_e( 'What it means', 'kindredcare' ); ?>
			</label>
			<textarea
				id="<?php echo esc_attr( $id ); ?>-def"
				name="<?php echo esc_attr( $base ); ?>[modal_items][<?php echo (int) $index; ?>][def]"
				rows="3"
			><?php echo esc_textarea( (string) ( $item['def'] ?? '' ) ); ?></textarea>
		</div>
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
 * Print one tier panel.
 *
 * @param int                  $index Tier position.
 * @param array<string, mixed> $tier  Tier data.
 * @param string               $id    The modal element id this tier will use.
 */
function kindredcare_tiers_panel( int $index, array $tier, string $id ): void {
	$option = KINDREDCARE_TIERS_OPTION;
	$base   = $option . '[tiers][' . $index . ']';
	$checks = $tier['checks'];
	$items  = $tier['modal_items'];
	?>
	<section class="kc-panel">
		<div class="kc-panel__head">
			<?php kindredcare_admin_icon( 'M12 3.5 5 6.2v5.3c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6.2zM9 12l2.2 2.2L15.4 10' ); ?>
			<div class="kc-panel__titles">
				<h2>
					<?php
					printf(
						/* translators: 1: tier position, 2: tier name. */
						esc_html__( 'Tier %1$d — %2$s', 'kindredcare' ),
						(int) $index + 1,
						esc_html( (string) $tier['name'] )
					);
					?>
				</h2>
				<p>
					<?php
					printf(
						/* translators: %s: the modal's element id. */
						esc_html__( 'Card %1$d of 3 on the front page. Its "what\'s actually checked" panel opens at %2$s.', 'kindredcare' ),
						(int) $index + 1,
						'<code>#' . esc_html( $id ) . '</code>'
					);
					?>
				</p>
			</div>
		</div>
		<div class="kc-panel__body">
			<div class="kc-field kc-field--split">
				<div>
					<label class="kc-field__label" for="kc-tier<?php echo (int) $index; ?>-name">
						<?php esc_html_e( 'Tier name', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-tier<?php echo (int) $index; ?>-name"
						name="<?php echo esc_attr( $base ); ?>[name]"
						value="<?php echo esc_attr( (string) $tier['name'] ); ?>"
						required
					/>
					<p class="kc-field__hint"><?php esc_html_e( 'The card heading. Renaming it also changes the id its panel opens at — both sides move together, so nothing breaks.', 'kindredcare' ); ?></p>
				</div>
				<div>
					<label class="kc-field__label" for="kc-tier<?php echo (int) $index; ?>-badge">
						<?php esc_html_e( 'Badge', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-tier<?php echo (int) $index; ?>-badge"
						name="<?php echo esc_attr( $base ); ?>[badge]"
						value="<?php echo esc_attr( (string) $tier['badge'] ); ?>"
					/>
					<p class="kc-field__hint"><?php esc_html_e( 'The small ticked pill at the top of the card. One word. This is the word that appears on a caregiver\'s listing.', 'kindredcare' ); ?></p>
				</div>
			</div>

			<div class="kc-field">
				<label class="kc-field__label" for="kc-tier<?php echo (int) $index; ?>-desc">
					<?php esc_html_e( 'One-line description', 'kindredcare' ); ?>
				</label>
				<textarea
					id="kc-tier<?php echo (int) $index; ?>-desc"
					name="<?php echo esc_attr( $base ); ?>[desc]"
					rows="2"
				><?php echo esc_textarea( (string) $tier['desc'] ); ?></textarea>
			</div>

			<div class="kc-field">
				<span class="kc-field__label"><?php esc_html_e( 'Checks listed on the card', 'kindredcare' ); ?></span>

				<div
					class="kc-repeat"
					data-kc-repeat="checks"
					data-kc-max="<?php echo (int) KINDREDCARE_TIERS_MAX_CHECKS; ?>"
					data-kc-min="1"
				>
					<div class="kc-lines" data-kc-list>
						<?php foreach ( $checks as $i => $check ) : ?>
							<?php kindredcare_tiers_check_row( $base, (int) $i, (string) $check ); ?>
						<?php endforeach; ?>
					</div>

					<div class="kc-lines__foot">
						<?php kindredcare_admin_add_button( __( 'Add check', 'kindredcare' ) ); ?>
						<span
							class="kc-repeat__count"
							data-kc-count
							data-template="<?php esc_attr_e( '%2$s checks · %3$s maximum', 'kindredcare' ); ?>"
						>
							<?php
							printf(
								/* translators: 1: checks total, 2: maximum allowed. */
								esc_html__( '%1$s checks · %2$s maximum', 'kindredcare' ),
								count( $checks ),
								(int) KINDREDCARE_TIERS_MAX_CHECKS
							);
							?>
						</span>
					</div>

					<template data-kc-template>
						<?php kindredcare_tiers_check_row( $base, 0, '' ); ?>
					</template>
				</div>

				<p class="kc-field__hint"><?php esc_html_e( 'Name the actual check — "Vulnerable Sector Check", not "fully vetted". The three cards are the same height, so the longest list here sets the height of all of them.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field">
				<label class="kc-field__label" for="kc-tier<?php echo (int) $index; ?>-unlocks">
					<?php esc_html_e( 'What it unlocks', 'kindredcare' ); ?>
				</label>
				<input
					type="text"
					id="kc-tier<?php echo (int) $index; ?>-unlocks"
					name="<?php echo esc_attr( $base ); ?>[unlocks]"
					value="<?php echo esc_attr( (string) $tier['unlocks'] ); ?>"
				/>
				<p class="kc-field__hint"><?php esc_html_e( 'The tinted line at the foot of the card. Keep it consistent with the services in the taxonomy section.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field">
				<label class="kc-field__label" for="kc-tier<?php echo (int) $index; ?>-modal-title">
					<?php esc_html_e( 'Panel heading', 'kindredcare' ); ?>
				</label>
				<input
					type="text"
					id="kc-tier<?php echo (int) $index; ?>-modal-title"
					name="<?php echo esc_attr( $base ); ?>[modal_title]"
					value="<?php echo esc_attr( (string) $tier['modal_title'] ); ?>"
				/>
				<p class="kc-field__hint"><?php esc_html_e( 'Shown at the top of the panel. It repeats the tier name, so update it if you rename the tier above.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field">
				<span class="kc-field__label"><?php esc_html_e( 'What the panel explains', 'kindredcare' ); ?></span>

				<div
					class="kc-repeat"
					data-kc-repeat="modal_items"
					data-kc-max="<?php echo (int) KINDREDCARE_TIERS_MAX_MODAL_ITEMS; ?>"
					data-kc-min="1"
				>
					<div class="kc-lines" data-kc-list>
						<?php foreach ( $items as $i => $item ) : ?>
							<?php kindredcare_tiers_modal_row( $base, $index, (int) $i, (array) $item ); ?>
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
								count( $items ),
								(int) KINDREDCARE_TIERS_MAX_MODAL_ITEMS
							);
							?>
						</span>
					</div>

					<template data-kc-template>
						<?php
						kindredcare_tiers_modal_row(
							$base,
							$index,
							0,
							array(
								'term' => '',
								'def'  => '',
							)
						);
						?>
					</template>
				</div>

				<p class="kc-field__hint"><?php esc_html_e( 'This is where the claim is substantiated, so it can be longer and more technical than the card. Explain any term a family would not know — "Vulnerable Sector Check" needs a sentence saying what it reaches. A row with no name is dropped; clearing them all restores the shipped rows rather than saving an empty panel.', 'kindredcare' ); ?></p>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Render the Verification tiers admin screen.
 */
function kindredcare_tiers_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_tiers();
	$option  = KINDREDCARE_TIERS_OPTION;
	$tiers   = $section['tiers'];
	$ids     = kindredcare_tiers_ids( $tiers );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Verification tiers', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The three tiers, what each one checks, and what it lets a caregiver offer. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_tiers_group' ); ?>

			<!-- Heading --------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'Sits above the cards, with the lead paragraph to its right on wide screens.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( '<strong>Every claim on this screen has to be checkable.</strong> "We do background checks" is what Care.com says, and an undifferentiated trust claim is worth nothing to a family comparing us against an agency. Name the check — <code>CPIC via Certn</code>, <code>Vulnerable Sector Check</code> — and only list what is actually live today. Anything still planned belongs nowhere on this page.', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-tiers-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-tiers-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-tiers-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-tiers-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty. It says "Three tiers" by default — the section is built for exactly three, so if you reword it, keep the count honest.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-tiers-lead"><?php esc_html_e( 'Lead paragraph', 'kindredcare' ); ?></label>
						<textarea id="kc-tiers-lead" name="<?php echo esc_attr( $option ); ?>[lead]" rows="3"><?php echo esc_textarea( $section['lead'] ); ?></textarea>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-tiers-more"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
						<input type="text" id="kc-tiers-more" name="<?php echo esc_attr( $option ); ?>[more_label]" value="<?php echo esc_attr( $section['more_label'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The button at the foot of every card that opens its panel. One setting for all three, so they cannot end up worded differently. Leave blank to remove the buttons.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<?php
			/*
			 * No add/remove: the row is three cards. The heading says so, the
			 * positional c-tier--1|2|3 modifiers colour them, and the deal-out
			 * effect in tier-stack.js converges the row onto the middle card.
			 */
			?>
			<?php foreach ( $tiers as $index => $tier ) : ?>
				<?php kindredcare_tiers_panel( (int) $index, $tier, $ids[ $index ] ); ?>
			<?php endforeach; ?>

			<?php kindredcare_admin_savebar( __( 'Save verification tiers', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
