<?php
/**
 * Admin: KindredCare -> Final CTA.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_FINAL_CTA_MENU_SLUG = 'kindredcare-final-cta';

/**
 * Capability callback for this option group.
 */
function kindredcare_final_cta_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 22: after FAQ (21), and last, matching the page order. */
add_action( 'admin_menu', 'kindredcare_final_cta_admin_menu', 22 );
/**
 * Add the Final CTA screen under the KindredCare menu.
 */
function kindredcare_final_cta_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Final CTA', 'kindredcare' ),
		__( 'Final CTA', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_FINAL_CTA_MENU_SLUG,
		'kindredcare_final_cta_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_final_cta_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_final_cta_register_setting(): void {
	register_setting(
		'kindredcare_final_cta_group',
		KINDREDCARE_FINAL_CTA_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_final_cta_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_final_cta_group', 'kindredcare_final_cta_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_final_cta_sanitize( $input ): array {
	$defaults = kindredcare_final_cta_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'         => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'           => sanitize_text_field( $input['title'] ?? '' ),
		'lead'            => sanitize_textarea_field( $input['lead'] ?? '' ),
		'primary_label'   => sanitize_text_field( $input['primary_label'] ?? '' ),
		'primary_url'     => esc_url_raw( trim( (string) ( $input['primary_url'] ?? '' ) ) ),
		'secondary_label' => sanitize_text_field( $input['secondary_label'] ?? '' ),
		'secondary_url'   => esc_url_raw( trim( (string) ( $input['secondary_url'] ?? '' ) ) ),
		'primary_new_tab'   => ! empty( $input['primary_new_tab'] ),
		'secondary_new_tab' => ! empty( $input['secondary_new_tab'] ),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_final_cta_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_final_cta_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_FINAL_CTA_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Render the Final CTA admin screen.
 */
function kindredcare_final_cta_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_final_cta();
	$option  = KINDREDCARE_FINAL_CTA_OPTION;
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Final CTA', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The panel that closes the page, above the footer. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#final-cta' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_final_cta_group' ); ?>

			<!-- The ask ---------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'The ask', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The last thing anyone reads on this page.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'Whoever is still here has read the whole page. They do not need persuading again — they need <strong>the next step, and what it costs them to take it</strong>. Keep the promise small and true: browsing is free, and nothing is committed by looking.', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-fcta-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-fcta-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-fcta-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-fcta-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-fcta-lead"><?php esc_html_e( 'Supporting line', 'kindredcare' ); ?></label>
						<textarea id="kc-fcta-lead" name="<?php echo esc_attr( $option ); ?>[lead]" rows="3"><?php echo esc_textarea( $section['lead'] ); ?></textarea>
						<p class="kc-field__hint"><?php esc_html_e( 'One or two sentences under the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<!-- Buttons ---------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 8h16v8H4zM8 12h8' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Buttons', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'One for each side of the marketplace.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'This is the one place on the page where <strong>both sides get a button of the same size</strong>. A two-sided marketplace that only ever asks families to act does not fill its side of the exchange — if you drop the caregiver button, drop it knowing that.', 'kindredcare' )
					);
					?>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-fcta-primary-label"><?php esc_html_e( 'First button text', 'kindredcare' ); ?></label>
							<input type="text" id="kc-fcta-primary-label" name="<?php echo esc_attr( $option ); ?>[primary_label]" value="<?php echo esc_attr( $section['primary_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-fcta-primary-url"><?php esc_html_e( 'First button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-fcta-primary-url" name="<?php echo esc_attr( $option ); ?>[primary_url]" value="<?php echo esc_attr( $section['primary_url'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Defaults to #services, further up this page. Clearing either field removes the button.', 'kindredcare' ); ?></p>
							<?php kindredcare_admin_new_tab_toggle( $option . '[primary_new_tab]', kindredcare_link_new_tab( $section['primary_new_tab'] ?? null, (string) $section['primary_url'], 'external' ) ); ?>
						</div>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-fcta-secondary-label"><?php esc_html_e( 'Second button text', 'kindredcare' ); ?></label>
							<input type="text" id="kc-fcta-secondary-label" name="<?php echo esc_attr( $option ); ?>[secondary_label]" value="<?php echo esc_attr( $section['secondary_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-fcta-secondary-url"><?php esc_html_e( 'Second button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-fcta-secondary-url" name="<?php echo esc_attr( $option ); ?>[secondary_url]" value="<?php echo esc_attr( $section['secondary_url'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Defaults to #for-caregivers, the dark band above. Clearing either field removes the button.', 'kindredcare' ); ?></p>
							<?php kindredcare_admin_new_tab_toggle( $option . '[secondary_new_tab]', kindredcare_link_new_tab( $section['secondary_new_tab'] ?? null, (string) $section['secondary_url'] ) ); ?>
						</div>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save final CTA', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
