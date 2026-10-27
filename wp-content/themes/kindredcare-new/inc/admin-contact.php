<?php
/**
 * Admin: KindredCare -> Contact page.
 *
 * The copy around the enquiry form. Same Settings API arrangement as the
 * section screens, and the same furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_CONTACT_MENU_SLUG = 'kindredcare-contact';

/**
 * Capability callback for this option group.
 */
function kindredcare_contact_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 23: after the front-page sections, before the tools screen. */
add_action( 'admin_menu', 'kindredcare_contact_admin_menu', 23 );
/**
 * Add the Contact page screen under the KindredCare menu.
 */
function kindredcare_contact_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Contact page', 'kindredcare' ),
		__( 'Contact page', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_CONTACT_MENU_SLUG,
		'kindredcare_contact_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_contact_register_setting' );
/**
 * Register the option with the Settings API.
 */
function kindredcare_contact_register_setting(): void {
	register_setting(
		'kindredcare_contact_group',
		KINDREDCARE_CONTACT_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_contact_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_contact_group', 'kindredcare_contact_capability' );

/**
 * Sanitise the submitted copy.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_contact_sanitize( $input ): array {
	$defaults = kindredcare_contact_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'kicker'        => sanitize_text_field( $input['kicker'] ?? '' ),
		'show_kicker'   => ! empty( $input['show_kicker'] ),
		'lead'          => sanitize_textarea_field( $input['lead'] ?? '' ),
		'show_lead'     => ! empty( $input['show_lead'] ),
		'form_heading'  => sanitize_text_field( $input['form_heading'] ?? '' ),
		'submit_label'  => sanitize_text_field( $input['submit_label'] ?? '' ),
		'card_title'    => sanitize_text_field( $input['card_title'] ?? '' ),
		'show_card'     => ! empty( $input['show_card'] ),
		'email_label'   => sanitize_text_field( $input['email_label'] ?? '' ),
		'phone_label'   => sanitize_text_field( $input['phone_label'] ?? '' ),
		'address_label' => sanitize_text_field( $input['address_label'] ?? '' ),
		'note'          => sanitize_textarea_field( $input['note'] ?? '' ),
		'show_note'     => ! empty( $input['show_note'] ),
	);

	// A button with no words on it cannot be pressed with any confidence.
	if ( '' === trim( $clean['submit_label'] ) ) {
		$clean['submit_label'] = $defaults['submit_label'];
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_contact_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_contact_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_CONTACT_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Render the Contact page screen.
 */
function kindredcare_contact_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this page.', 'kindredcare' ) );
	}

	$kc     = kindredcare_contact_content();
	$option = KINDREDCARE_CONTACT_OPTION;
	$page   = kindredcare_contact_page();
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Pages', 'kindredcare' ),
			__( 'Contact page', 'kindredcare' ),
			$page
				? sprintf(
					/* translators: 1: page title, 2: link to view it. */
					esc_html__( 'The copy around the enquiry form on “%1$s”. %2$s', 'kindredcare' ),
					esc_html( get_the_title( $page ) ),
					'<a href="' . esc_url( (string) get_permalink( $page ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View the page', 'kindredcare' ) . '</a>'
				)
				: esc_html__( 'The copy around the enquiry form.', 'kindredcare' )
		);

		if ( ! $page ) {
			kindredcare_admin_note(
				sprintf(
					/* translators: %s: template name. */
					__( '<strong>No page is using the contact template yet.</strong> Create a page, then under Page Attributes choose the “%s” template. The page can be called anything — every link to it is found by its template, not its name.', 'kindredcare' ),
					'<code>Contact</code>'
				)
			);
		}
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_contact_group' ); ?>

			<!-- The head ---------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Top of the page', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'What sits above the form.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( '<strong>The big heading is the page’s own title</strong> — rename the page to change it. Keeping a second copy here would only ever drift from the name in the page list.', 'kindredcare' )
					);
					?>

					<div class="kc-field <?php echo empty( $kc['show_kicker'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<label class="kc-field__label" for="kc-ct-kicker"><?php esc_html_e( 'Kicker', 'kindredcare' ); ?></label>
							<?php kindredcare_admin_toggle( $option . '[show_kicker]', ! empty( $kc['show_kicker'] ), __( 'Shown', 'kindredcare' ), array( 'kc-toggle-row' => '1' ) ); ?>
						</div>
						<input type="text" id="kc-ct-kicker" name="<?php echo esc_attr( $option ); ?>[kicker]" value="<?php echo esc_attr( $kc['kicker'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small ruled line above the title.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field <?php echo empty( $kc['show_lead'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<label class="kc-field__label" for="kc-ct-lead"><?php esc_html_e( 'Standfirst', 'kindredcare' ); ?></label>
							<?php kindredcare_admin_toggle( $option . '[show_lead]', ! empty( $kc['show_lead'] ), __( 'Shown', 'kindredcare' ), array( 'kc-toggle-row' => '1' ) ); ?>
						</div>
						<textarea id="kc-ct-lead" name="<?php echo esc_attr( $option ); ?>[lead]" rows="3"><?php echo esc_textarea( $kc['lead'] ); ?></textarea>
					</div>
				</div>
			</section>

			<!-- The form ---------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 6h16v12H4zM4 7l8 6 8-6' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'The form', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'Its heading and the button under it.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-ct-form-heading"><?php esc_html_e( 'Form heading', 'kindredcare' ); ?></label>
							<input type="text" id="kc-ct-form-heading" name="<?php echo esc_attr( $option ); ?>[form_heading]" value="<?php echo esc_attr( $kc['form_heading'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Leave blank to remove it.', 'kindredcare' ); ?></p>
						</div>
						<div>
							<label class="kc-field__label" for="kc-ct-submit"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
							<input type="text" id="kc-ct-submit" name="<?php echo esc_attr( $option ); ?>[submit_label]" value="<?php echo esc_attr( $kc['submit_label'] ); ?>" required />
							<p class="kc-field__hint"><?php esc_html_e( 'Cannot be empty — a button with no words on it cannot be pressed with any confidence.', 'kindredcare' ); ?></p>
						</div>
					</div>

					<?php
					kindredcare_admin_note(
						__( 'The field labels and the messages after sending are not editable here. They are the wording the form’s own checks refer to, and copy that disagrees with an error message is worse than plain wording.', 'kindredcare' )
					);
					?>
				</div>
			</section>

			<!-- The card ---------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 5h16v14H4zM8 9h8M8 13h5' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Contact us directly', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The card beside the form.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						sprintf(
							/* translators: %s: link to the Customizer footer section. */
							__( '<strong>The address, phone number and location themselves are not here.</strong> They come from %s — the same values the footer prints, so there is one place to change a phone number rather than two that can disagree. What you set below is what each one is <em>called</em>.', 'kindredcare' ),
							'<a href="' . esc_url( admin_url( 'customize.php' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Customise → KindredCare → Footer', 'kindredcare' ) . '</a>'
						)
					);
					?>

					<div class="kc-field <?php echo empty( $kc['show_card'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<label class="kc-field__label" for="kc-ct-card-title"><?php esc_html_e( 'Card heading', 'kindredcare' ); ?></label>
							<?php kindredcare_admin_toggle( $option . '[show_card]', ! empty( $kc['show_card'] ), __( 'Card shown', 'kindredcare' ), array( 'kc-toggle-row' => '1' ) ); ?>
						</div>
						<input type="text" id="kc-ct-card-title" name="<?php echo esc_attr( $option ); ?>[card_title]" value="<?php echo esc_attr( $kc['card_title'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'Switching this off removes the whole card and the form takes the full width.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-ct-email-label"><?php esc_html_e( 'Email label', 'kindredcare' ); ?></label>
							<input type="text" id="kc-ct-email-label" name="<?php echo esc_attr( $option ); ?>[email_label]" value="<?php echo esc_attr( $kc['email_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-ct-phone-label"><?php esc_html_e( 'Phone label', 'kindredcare' ); ?></label>
							<input type="text" id="kc-ct-phone-label" name="<?php echo esc_attr( $option ); ?>[phone_label]" value="<?php echo esc_attr( $kc['phone_label'] ); ?>" />
						</div>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-ct-address-label"><?php esc_html_e( 'Location label', 'kindredcare' ); ?></label>
						<input type="text" id="kc-ct-address-label" name="<?php echo esc_attr( $option ); ?>[address_label]" value="<?php echo esc_attr( $kc['address_label'] ); ?>" />
					</div>

					<div class="kc-field <?php echo empty( $kc['show_note'] ) ? 'is-off' : ''; ?>">
						<div class="kc-field__head">
							<label class="kc-field__label" for="kc-ct-note"><?php esc_html_e( 'Closing line', 'kindredcare' ); ?></label>
							<?php kindredcare_admin_toggle( $option . '[show_note]', ! empty( $kc['show_note'] ), __( 'Shown', 'kindredcare' ), array( 'kc-toggle-row' => '1' ) ); ?>
						</div>
						<textarea id="kc-ct-note" name="<?php echo esc_attr( $option ); ?>[note]" rows="2"><?php echo esc_textarea( $kc['note'] ); ?></textarea>
						<p class="kc-field__hint"><?php esc_html_e( 'A reply-time promise sets an expectation you have to keep — say what is true.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save contact page', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
