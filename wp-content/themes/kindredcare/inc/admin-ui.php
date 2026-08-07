<?php
/**
 * Shared building blocks for the KindredCare admin screens.
 *
 * The Hero and Services screens are the same screen with different content:
 * a masthead, panels, toggles, media pickers and repeatable rows. Everything
 * common lives here so the two cannot drift into looking like two products.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_ADMIN_MENU_SLUG = 'kindredcare-hero';

/**
 * The capability required to see and save the KindredCare screens.
 *
 * edit_theme_options rather than manage_options: this is site content of the
 * same order as menus and the Customizer, not server configuration. Editors
 * with theme rights can maintain it without being full administrators.
 */
function kindredcare_admin_capability(): string {
	return 'edit_theme_options';
}

/**
 * Load the media modal and the shared panel assets.
 *
 * Called from each screen's own admin_enqueue_scripts check, so nothing is
 * queued anywhere else in wp-admin.
 */
function kindredcare_admin_enqueue(): void {
	wp_enqueue_media();

	wp_enqueue_style(
		'kindredcare-admin-panel',
		get_theme_file_uri( 'assets/admin/panel.css' ),
		array(),
		kindredcare_asset_version( 'assets/admin/panel.css' )
	);

	wp_enqueue_script(
		'kindredcare-admin-panel',
		get_theme_file_uri( 'assets/admin/panel.js' ),
		array( 'media-editor' ),
		kindredcare_asset_version( 'assets/admin/panel.js' ),
		true
	);

	wp_localize_script(
		'kindredcare-admin-panel',
		'kindredcarePanel',
		array(
			'strings' => array(
				'pickTitle'  => __( 'Choose an image', 'kindredcare' ),
				'pickButton' => __( 'Use this image', 'kindredcare' ),
			),
		)
	);
}

/**
 * Print the screen masthead.
 *
 * @param string $eyebrow Small label above the title.
 * @param string $title   Screen title.
 * @param string $lead    Supporting sentence. Escaped markup allowed.
 */
function kindredcare_admin_masthead( string $eyebrow, string $title, string $lead ): void {
	?>
	<div class="kc-admin__masthead">
		<span class="kc-admin__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
		<h1><?php echo esc_html( $title ); ?></h1>
		<p><?php echo wp_kses( $lead, array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ), 'strong' => array(), 'code' => array() ) ); ?></p>
	</div>
	<?php
}

/**
 * Print a panel icon.
 *
 * @param string $path SVG path data.
 */
function kindredcare_admin_icon( string $path ): void {
	printf(
		'<span class="kc-panel__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="%s" /></svg></span>',
		esc_attr( $path )
	);
}

/**
 * Print an informational note.
 *
 * Used for image specifications. The numbers in those are not the bundled
 * files' dimensions — they are what the layout asks for. Both image slots use
 * `object-fit: cover`, so an image of the wrong shape is not letterboxed, it is
 * cropped, and the guidance has to describe the shape the CSS will crop to.
 *
 * @param string $html Note body. Escaped markup expected.
 */
function kindredcare_admin_note( string $html ): void {
	?>
	<p class="kc-note">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<circle cx="12" cy="12" r="9" />
			<path d="M12 11v5M12 8h.01" />
		</svg>
		<span><?php echo wp_kses( $html, array( 'strong' => array(), 'code' => array(), 'br' => array(), 'em' => array() ) ); ?></span>
	</p>
	<?php
}

/**
 * Print a toggle switch.
 *
 * The hidden input before the checkbox is what makes "off" survive the post —
 * an unchecked checkbox sends nothing at all.
 *
 * @param string               $name    Input name.
 * @param bool                 $checked Current state.
 * @param string               $label   Visible text.
 * @param array<string,string> $data    Extra data-* attributes.
 */
function kindredcare_admin_toggle( string $name, bool $checked, string $label, array $data = array() ): void {
	$attributes = '';
	foreach ( $data as $key => $value ) {
		$attributes .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}
	?>
	<label class="kc-toggle">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0" />
		<input
			type="checkbox"
			name="<?php echo esc_attr( $name ); ?>"
			value="1"
			<?php checked( $checked ); ?>
			<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Built from escaped pairs above. ?>
		/>
		<span class="kc-toggle__track" aria-hidden="true"></span>
		<span class="kc-toggle__text"><?php echo esc_html( $label ); ?></span>
	</label>
	<?php
}

/**
 * Print an icon-only button.
 *
 * @param string               $path     SVG path data.
 * @param string               $label    Accessible name, also the tooltip.
 * @param array<string,string> $data     Extra data-* attributes.
 * @param bool                 $danger   Whether the action destroys something.
 * @param bool                 $disabled Whether there is nothing for it to do yet.
 */
function kindredcare_admin_icon_button( string $path, string $label, array $data = array(), bool $danger = false, bool $disabled = false ): void {
	$attributes = '';
	foreach ( $data as $key => $value ) {
		$attributes .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}
	?>
	<button
		type="button"
		class="kc-iconbtn<?php echo $danger ? ' kc-iconbtn--danger' : ''; ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
		title="<?php echo esc_attr( $label ); ?>"
		<?php disabled( $disabled, true ); ?>
		<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Built from escaped pairs above. ?>
	>
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="<?php echo esc_attr( $path ); ?>" />
		</svg>
	</button>
	<?php
}

/**
 * Print a media picker.
 *
 * @param string               $id       Field id, unique on the page.
 * @param string               $name     Input name attribute.
 * @param int                  $value    Current attachment id.
 * @param string               $fallback Theme-relative bundled image, empty when
 *                                       there is no image to fall back to.
 * @param array<string,string> $args     Optional 'empty_note' and 'reset_label'.
 */
function kindredcare_admin_media_field( string $id, string $name, int $value, string $fallback, array $args = array() ): void {
	$is_set       = $value > 0 && wp_attachment_is_image( $value );
	$fallback_url = '' !== $fallback ? get_theme_file_uri( $fallback ) : '';
	$preview      = $is_set ? (string) wp_get_attachment_image_url( $value, 'medium' ) : $fallback_url;

	/*
	 * Not every picker falls back to an image. The step icons fall back to an
	 * inline SVG the media library cannot preview, so the frame is empty and
	 * this note is what says why.
	 */
	$empty_note  = $args['empty_note'] ?? __( 'Bundled image', 'kindredcare' );
	$reset_label = $args['reset_label'] ?? __( 'Reset to the bundled image', 'kindredcare' );
	?>
	<div class="kc-media <?php echo $is_set ? 'is-set' : ''; ?>">
		<button
			type="button"
			class="kc-media__frame"
			data-kc-media-open
			data-label="<?php echo '' !== $preview ? esc_attr__( 'Change image', 'kindredcare' ) : esc_attr__( 'Choose an image', 'kindredcare' ); ?>"
		>
			<?php
			/*
			 * A new row has no bundled fallback, so there is nothing to preview
			 * until an image is chosen. src="" would make the browser re-request
			 * the current page, so the attribute is omitted rather than emptied.
			 */
			?>
			<img<?php echo '' !== $preview ? ' src="' . esc_url( $preview ) . '"' : ''; ?> alt="" />
		</button>

		<input
			type="hidden"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			data-kc-media-field
			data-kc-media-fallback="<?php echo esc_url( $fallback_url ); ?>"
		/>

		<div class="kc-media__actions">
			<button type="button" class="kc-btn kc-btn--sm" data-kc-media-open>
				<?php esc_html_e( 'Choose', 'kindredcare' ); ?>
			</button>
			<?php
			/*
			 * Always present, disabled when there is no chosen image to clear —
			 * the same arrangement as the colour field, so the row does not
			 * reflow as the control changes state.
			 */
			kindredcare_admin_icon_button(
				'M3 12a9 9 0 1 0 3-6.7L3 8M3 4v4h4',
				$reset_label,
				array( 'kc-media-reset' => '1' ),
				false,
				! $is_set
			);
			?>
		</div>

		<?php if ( '' !== $empty_note ) : ?>
			<span class="kc-media__origin"><?php echo esc_html( $empty_note ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Print a colour field that can fall back to an automatic value.
 *
 * A native <input type="color"> has no empty state — it always reports some
 * colour — so the value that actually gets saved lives in the hidden input
 * beside it. Empty there means "automatic", and the swatch is only showing what
 * automatic currently resolves to.
 *
 * @param string $name  Input name for the stored value.
 * @param string $value Current stored colour, empty for automatic.
 * @param string $auto  Colour automatic resolves to right now.
 * @param string $label Accessible name for the swatch.
 * @param string $note  Short description of what automatic means here.
 */
function kindredcare_admin_colour_field( string $name, string $value, string $auto, string $label, string $note ): void {
	$is_custom = '' !== trim( $value );
	?>
	<div class="kc-colour <?php echo $is_custom ? 'is-custom' : ''; ?>" data-kc-colour>
		<input
			type="color"
			class="kc-colour__swatch"
			value="<?php echo esc_attr( $is_custom ? $value : $auto ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
			data-kc-colour-input
		/>
		<input
			type="hidden"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			data-kc-colour-value
			data-kc-colour-auto="<?php echo esc_attr( $auto ); ?>"
		/>
		<span class="kc-colour__state">
			<span class="kc-colour__auto"><?php echo esc_html( $note ); ?></span>
			<span class="kc-colour__custom"><?php esc_html_e( 'Custom colour', 'kindredcare' ); ?></span>
		</span>
		<?php
		kindredcare_admin_icon_button(
			'M3 12a9 9 0 1 0 3-6.7L3 8M3 4v4h4',
			__( 'Back to the automatic colour', 'kindredcare' ),
			array( 'kc-colour-reset' => '1' ),
			false,
			// Nothing to undo while the card is already following its position.
			! $is_custom
		);
		?>
	</div>
	<?php
}

/**
 * Print the sticky save bar.
 *
 * Deliberately not submit_button(): that helper emits an <input> carrying
 * wp-admin's own .button-primary, which cannot hold an icon and drags the
 * admin's default styling into a screen built to look like the product.
 *
 * @param string $label Submit button label.
 */
function kindredcare_admin_savebar( string $label ): void {
	?>
	<div class="kc-savebar">
		<span class="kc-savebar__note"><?php esc_html_e( 'Changes go live on the front page as soon as they are saved.', 'kindredcare' ); ?></span>
		<?php
		/*
		 * The label swaps to "Saving…" while the form posts, so the two strings
		 * both ship and neither is built in JS. aria-live announces the change;
		 * the spinner itself is decorative.
		 */
		?>
		<button type="submit" name="submit" class="kc-btn kc-btn--primary" data-kc-save>
			<span class="kc-btn__spinner" aria-hidden="true"></span>
			<svg class="kc-btn__tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M20 6 9 17l-5-5" />
			</svg>
			<span class="kc-btn__idle"><?php echo esc_html( $label ); ?></span>
			<span class="kc-btn__busy"><?php esc_html_e( 'Saving…', 'kindredcare' ); ?></span>
		</button>
	</div>
	<?php
}

/**
 * Print an "add another row" button.
 *
 * @param string $label Button text.
 */
function kindredcare_admin_add_button( string $label ): void {
	?>
	<button type="button" class="kc-btn kc-btn--ghost" data-kc-add>
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M12 5v14M5 12h14" />
		</svg>
		<?php echo esc_html( $label ); ?>
	</button>
	<?php
}
