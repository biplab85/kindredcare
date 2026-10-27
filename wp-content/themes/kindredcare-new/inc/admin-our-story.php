<?php
/**
 * Admin: KindredCare -> Our story.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_OUR_STORY_MENU_SLUG = 'kindredcare-our-story';

/**
 * Capability callback for this option group.
 */
function kindredcare_our_story_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 12: after Trust strip (11), matching the page order. */
add_action( 'admin_menu', 'kindredcare_our_story_admin_menu', 12 );
/**
 * Add the Our story screen under the KindredCare menu.
 */
function kindredcare_our_story_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Our story', 'kindredcare' ),
		__( 'Our story', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_OUR_STORY_MENU_SLUG,
		'kindredcare_our_story_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_our_story_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_our_story_register_setting(): void {
	register_setting(
		'kindredcare_our_story_group',
		KINDREDCARE_OUR_STORY_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_our_story_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_our_story_group', 'kindredcare_our_story_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_our_story_sanitize( $input ): array {
	$defaults = kindredcare_our_story_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'image'       => absint( $input['image'] ?? 0 ),
		'image_alt'   => sanitize_text_field( $input['image_alt'] ?? '' ),
		'vertical'    => sanitize_text_field( $input['vertical'] ?? '' ),
		'title'       => sanitize_textarea_field( $input['title'] ?? '' ),
		'brand'       => sanitize_text_field( $input['brand'] ?? '' ),
		'text'        => sanitize_textarea_field( $input['text'] ?? '' ),
		'phone_label' => sanitize_text_field( $input['phone_label'] ?? '' ),
		'phone'       => sanitize_text_field( $input['phone'] ?? '' ),
		'email_label' => sanitize_text_field( $input['email_label'] ?? '' ),
		'email'       => sanitize_email( trim( (string) ( $input['email'] ?? '' ) ) ),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	// A label with nothing under it is a dangling word, so the pair travels
	// together: clearing the value clears its label too.
	if ( '' === trim( $clean['phone'] ) ) {
		$clean['phone_label'] = '';
	} elseif ( '' === trim( $clean['phone_label'] ) ) {
		$clean['phone_label'] = $defaults['phone_label'];
	}

	if ( '' === $clean['email'] ) {
		$clean['email_label'] = '';
	} elseif ( '' === trim( $clean['email_label'] ) ) {
		$clean['email_label'] = $defaults['email_label'];
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_our_story_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_our_story_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_OUR_STORY_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Render the Our story admin screen.
 */
function kindredcare_our_story_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_our_story();
	$option  = KINDREDCARE_OUR_STORY_OPTION;
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'Our story', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The band under the hero — who built KindredCare, why, and how to reach them. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#our-story' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_our_story_group' ); ?>

			<!-- The story ------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading and story', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The right-hand column, beside the portrait.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'The site this section is modelled on opens with <em>"Founded in 2000 — a trusted name for 24 years"</em>. KindredCare has neither, and <strong>inventing a founding date or a years-in-business figure here is the one thing this section must never do</strong>. Keep the shape of the sentence and keep the claims true: who built it, and where it starts.', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-os-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<textarea id="kc-os-title" name="<?php echo esc_attr( $option ); ?>[title]" rows="3" required><?php echo esc_textarea( $section['title'] ); ?></textarea>
						<p class="kc-field__hint"><?php esc_html_e( 'One sentence, set large. This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-os-brand"><?php esc_html_e( 'Word to highlight', 'kindredcare' ); ?></label>
						<input type="text" id="kc-os-brand" name="<?php echo esc_attr( $option ); ?>[brand]" value="<?php echo esc_attr( $section['brand'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'Picked out in the brand colour where it appears in the heading above. It must match the heading exactly, letter for letter. Leave blank — or rewrite the heading without it — and the heading is simply set in one colour.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-os-text"><?php esc_html_e( 'Story', 'kindredcare' ); ?></label>
						<textarea id="kc-os-text" name="<?php echo esc_attr( $option ); ?>[text]" rows="6"><?php echo esc_textarea( $section['text'] ); ?></textarea>
						<p class="kc-field__hint"><?php esc_html_e( 'The paragraph under the heading. Written as "we", to a family who has already been let down once. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>
				</div>
			</section>

			<!-- Portrait --------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 5h16v14H4zM4 15l4-4 4 4 3-3 5 5' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Portrait', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The image filling the left half, and the label turned on its side beside it.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'Photograph', 'kindredcare' ); ?></span>
						<?php
						kindredcare_admin_media_field(
							'kc-os-image',
							$option . '[image]',
							(int) $section['image'],
							kindredcare_our_story_bundled_image()
						);

						kindredcare_admin_note(
							sprintf(
								/* translators: 1: recommended pixel size, 2: aspect ratio. */
								__( '<strong>Best size: %1$s</strong> — portrait, roughly %2$s, cropped to fill rather than letterboxed. Two people, at home, mid-conversation. <strong>No clipboards, no scrubs, no stethoscopes, no clinical white rooms</strong> — every agency site in this market looks like that, and looking like them is the cheapest way to be mistaken for one.', 'kindredcare' ),
								'<code>941 × 1101</code>',
								'<code>0.85 : 1</code>'
							)
						);
						?>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-os-image-alt"><?php esc_html_e( 'Image description', 'kindredcare' ); ?></label>
						<input type="text" id="kc-os-image-alt" name="<?php echo esc_attr( $option ); ?>[image_alt]" value="<?php echo esc_attr( $section['image_alt'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'Read aloud by screen readers. Describe what is happening in the photograph, not what the section is selling.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-os-vertical"><?php esc_html_e( 'Rotated label', 'kindredcare' ); ?></label>
						<input type="text" id="kc-os-vertical" name="<?php echo esc_attr( $option ); ?>[vertical]" value="<?php echo esc_attr( $section['vertical'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'Set sideways down the edge of the photograph. A few words at most — it is as tall as the image and a sentence will not fit. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<?php
					kindredcare_admin_note(
						__( 'The heart badge on the corner of the frame is brand furniture rather than content — it is what ties this frame to the logo — so it is set in the theme and is not editable here.', 'kindredcare' )
					);
					?>
				</div>
			</section>

			<!-- Contact ---------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 6h16v12H4zM4 7l8 6 8-6' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'How to reach us', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The pair at the foot of the story.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-os-phone-label"><?php esc_html_e( 'Phone label', 'kindredcare' ); ?></label>
							<input type="text" id="kc-os-phone-label" name="<?php echo esc_attr( $option ); ?>[phone_label]" value="<?php echo esc_attr( $section['phone_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-os-phone"><?php esc_html_e( 'Phone number', 'kindredcare' ); ?></label>
							<input type="text" id="kc-os-phone" name="<?php echo esc_attr( $option ); ?>[phone]" value="<?php echo esc_attr( $section['phone'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Write it the way it should be read. The dialling link is built from the digits. Leave blank to remove the pair.', 'kindredcare' ); ?></p>
						</div>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-os-email-label"><?php esc_html_e( 'Email label', 'kindredcare' ); ?></label>
							<input type="text" id="kc-os-email-label" name="<?php echo esc_attr( $option ); ?>[email_label]" value="<?php echo esc_attr( $section['email_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-os-email"><?php esc_html_e( 'Email address', 'kindredcare' ); ?></label>
							<input type="email" id="kc-os-email" name="<?php echo esc_attr( $option ); ?>[email]" value="<?php echo esc_attr( $section['email'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Leave blank to remove the pair.', 'kindredcare' ); ?></p>
						</div>
					</div>

					<?php
					kindredcare_admin_note(
						__( 'These two are <strong>not the only place the number and address appear</strong>. The top bar and the footer read theirs from <em>Appearance → Customise → KindredCare</em>, and the slide-out menu and the FAQ aside carry their own. Change one and check the rest — a page quoting two different numbers is worse than one quoting none.', 'kindredcare' )
					);
					?>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save our story', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
