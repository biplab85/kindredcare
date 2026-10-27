<?php
/**
 * Admin: KindredCare -> Trust strip.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_TRUST_STRIP_MENU_SLUG = 'kindredcare-trust-strip';

/**
 * Capability callback for this option group.
 */
function kindredcare_trust_strip_capability(): string {
	return kindredcare_admin_capability();
}

/*
 * Priority 11: the parent menu is created by inc/admin-hero.php at the default
 * 10, and add_submenu_page() needs it to already exist. The strip sits directly
 * under the hero on the page, so it sits directly under Hero here.
 */
add_action( 'admin_menu', 'kindredcare_trust_strip_admin_menu', 11 );
/**
 * Add the Trust strip screen under the KindredCare menu.
 */
function kindredcare_trust_strip_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Trust strip', 'kindredcare' ),
		__( 'Trust strip', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_TRUST_STRIP_MENU_SLUG,
		'kindredcare_trust_strip_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_trust_strip_register_setting' );
/**
 * Register the strip's option with the Settings API.
 */
function kindredcare_trust_strip_register_setting(): void {
	register_setting(
		'kindredcare_trust_strip_group',
		KINDREDCARE_TRUST_STRIP_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_trust_strip_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_trust_strip_group', 'kindredcare_trust_strip_capability' );

/**
 * Sanitise the submitted strip content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_trust_strip_sanitize( $input ): array {
	$defaults = kindredcare_trust_strip_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array( 'items' => array() );
	$lines = is_array( $input['items'] ?? null ) ? array_values( $input['items'] ) : array();

	foreach ( $lines as $line ) {
		if ( count( $clean['items'] ) >= KINDREDCARE_TRUST_STRIP_MAX_ITEMS ) {
			break;
		}

		$row = kindredcare_trust_strip_row( $line );
		if ( null === $row ) {
			continue;
		}

		$text = sanitize_text_field( $row['text'] );

		/*
		 * Emptying the field is how a claim is deleted; the switch is how one is
		 * hidden. A claim that still has words is therefore kept whichever way
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

add_action( 'admin_enqueue_scripts', 'kindredcare_trust_strip_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_trust_strip_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_TRUST_STRIP_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one claim row.
 *
 * The wording comes first and the two controls that act on it follow: the
 * switch, then the delete button. Both are about the claim to their left, so
 * they read in the order of how far they go — hide it, then remove it.
 *
 * The whole row dims when the switch is off; panel.js keys that off
 * `data-kc-toggle-row`.
 *
 * @param string                        $option Option name.
 * @param int                           $index  Row position.
 * @param array{text: string, enabled: bool} $item Current row.
 */
function kindredcare_trust_strip_item_row( string $option, int $index, array $item ): void {
	$base = $option . '[items][' . $index . ']';
	?>
	<div class="kc-lines__row <?php echo empty( $item['enabled'] ) ? 'is-off' : ''; ?>" data-kc-item>
		<input
			type="text"
			name="<?php echo esc_attr( $base ); ?>[text]"
			value="<?php echo esc_attr( (string) $item['text'] ); ?>"
			aria-label="<?php esc_attr_e( 'Claim', 'kindredcare' ); ?>"
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
			__( 'Remove this claim', 'kindredcare' ),
			array( 'kc-remove' => '1' ),
			true
		);
		?>
	</div>
	<?php
}

/**
 * Render the Trust strip admin screen.
 */
function kindredcare_trust_strip_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$strip  = kindredcare_trust_strip();
	$option = KINDREDCARE_TRUST_STRIP_OPTION;
	$items  = $strip['items'];
	$active = count( kindredcare_trust_strip_active_items( $strip ) );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Trust strip', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The ticked row on the white band directly under the hero. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_trust_strip_group' ); ?>

			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'm4 9.2 3.2 3.2L14 5.6' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'The claims', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: 1: count above which the row scrolls, 2: maximum number of claims. */
								esc_html__( 'Ticked, in one row across the page. Past %1$d the row scrolls; %2$d at most.', 'kindredcare' ),
								(int) KINDREDCARE_TRUST_STRIP_MARQUEE_AT,
								(int) KINDREDCARE_TRUST_STRIP_MAX_ITEMS
							);
							?>
						</p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'This strip exists to do the job of a counter row <strong>without quoting a number</strong>. A new marketplace\'s totals all read zero, and "0 caregivers" argues against the page it sits on. Name a thing that is checked instead — something specific enough that a family could ask about it. <strong>Never put a count, a rating or a years-in-business figure here.</strong>', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<div
							class="kc-repeat"
							data-kc-repeat="items"
							data-kc-max="<?php echo (int) KINDREDCARE_TRUST_STRIP_MAX_ITEMS; ?>"
							data-kc-min="1"
						>
							<div class="kc-lines" data-kc-list>
								<?php foreach ( $items as $index => $item ) : ?>
									<?php kindredcare_trust_strip_item_row( $option, (int) $index, (array) $item ); ?>
								<?php endforeach; ?>
							</div>

							<div class="kc-lines__foot">
								<?php kindredcare_admin_add_button( __( 'Add claim', 'kindredcare' ) ); ?>
								<span
									class="kc-repeat__count"
									data-kc-count
									data-template="<?php esc_attr_e( '%1$s shown · %2$s saved · %3$s maximum', 'kindredcare' ); ?>"
								>
									<?php
									printf(
										/* translators: 1: claims shown, 2: claims saved, 3: maximum allowed. */
										esc_html__( '%1$s shown · %2$s saved · %3$s maximum', 'kindredcare' ),
										(int) $active,
										count( $items ),
										(int) KINDREDCARE_TRUST_STRIP_MAX_ITEMS
									);
									?>
								</span>
							</div>

							<?php
							/*
							 * Shown only while nothing is shown. panel.js keeps it in
							 * step with the switches; the attribute here is just the
							 * state the page loads in.
							 */
							?>
							<p
								class="kc-note kc-note--warn"
								data-kc-warn-below="1"
								<?php echo $active > 0 ? 'hidden' : ''; ?>
							>
								<?php esc_html_e( 'Every claim is switched off, so the strip will not appear on the page at all — the white band under the hero goes with it.', 'kindredcare' ); ?>
							</p>

							<template data-kc-template>
								<?php
								kindredcare_trust_strip_item_row(
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

						<p class="kc-field__hint">
							<?php
							printf(
								/* translators: %d: count above which the row starts scrolling. */
								esc_html__( 'A few words each, no full stops. Up to four sit still in a row — that is four columns on a desktop and two on a tablet, so four fills both evenly and five leaves one on a line of its own. Past %d the row scrolls instead, looping continuously and stopping while the pointer is over it. Switching a claim off hides it from the page but keeps the wording here; clearing the field is what deletes it.', 'kindredcare' ),
								(int) KINDREDCARE_TRUST_STRIP_MARQUEE_AT
							);
							?>
						</p>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save trust strip', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
