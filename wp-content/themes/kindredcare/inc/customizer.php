<?php
/**
 * Customizer settings for the site chrome.
 *
 * Every default here is the literal string the static page shipped with, so a
 * fresh activation renders byte-identical to kindredcare-home/src/index.html.
 * Nothing in the header is hardcoded any more, but nothing has moved either.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * The header's shipped defaults, keyed by setting id.
 *
 * Shared by the Customizer registration below and by the template functions
 * that read the values, so a default is written once.
 *
 * @return array<string, string|bool>
 */
function kindredcare_header_defaults(): array {
	return array(
		'kindredcare_topbar_enabled'           => true,
		'kindredcare_topbar_note'              => 'Now serving <strong>Durham Region, Ontario</strong>',
		'kindredcare_topbar_phone'             => '+1 (437) 665-8486',
		'kindredcare_topbar_cta_label'         => 'Join as a caregiver',
		'kindredcare_topbar_cta_url'           => '#for-caregivers',
		'kindredcare_header_search_enabled'    => true,
		'kindredcare_header_search_placeholder' => 'What help do you need?',
	);
}

/**
 * The footer's shipped defaults, keyed by setting id.
 *
 * The two link columns are not here — they are nav menus, registered in
 * inc/setup.php as the footer-quick and footer-care locations.
 *
 * @return array<string, string>
 */
function kindredcare_footer_defaults(): array {
	return array(
		// Subscription band.
		'kindredcare_footer_shout_label'           => "Let's talk",
		'kindredcare_footer_shout_email'           => 'info@kindredcare.ca',
		'kindredcare_footer_newsletter_title'      => 'Subscribe to our newsletter',
		'kindredcare_footer_newsletter_placeholder' => 'Enter your email',
		'kindredcare_footer_newsletter_action'     => '',

		// Column headings.
		'kindredcare_footer_serve_heading'         => 'Where we serve',
		'kindredcare_footer_quick_heading'         => 'Quick links',
		'kindredcare_footer_care_heading'          => 'Legal',
		'kindredcare_footer_contact_heading'       => 'Contact information',
		'kindredcare_footer_connect_heading'       => 'Connect with us',

		// Column one, under the mark. One line per list item.
		'kindredcare_footer_serve_lines'           => "Durham Region, Ontario\nOshawa · Whitby · Ajax · Pickering · Clarington",

		// Contact column.
		'kindredcare_footer_email'                 => 'info@kindredcare.ca',
		'kindredcare_footer_phone'                 => '+1 (437) 665-8486',
		'kindredcare_footer_location'              => 'Oshawa, Ontario',

		/*
		 * X is empty by design. The static footer carried three icons and the
		 * hero carried four; leaving this blank keeps the footer's rendered
		 * markup identical to the reference until someone opts in.
		 */
		'kindredcare_social_facebook'              => 'https://www.facebook.com/kindredcareglobal',
		'kindredcare_social_linkedin'              => 'https://linkedin.com/company/kindredcare/',
		'kindredcare_social_instagram'             => 'https://www.instagram.com/kindredcareglobal/',
		'kindredcare_social_x'                     => '',

		'kindredcare_footer_copyright'             => '© {year} KindredCare Global Inc. All rights reserved.',
	);
}

/**
 * Every shipped default, keyed by setting id.
 *
 * @return array<string, string|bool>
 */
function kindredcare_defaults(): array {
	return kindredcare_header_defaults() + kindredcare_footer_defaults();
}

/**
 * Read a theme setting, falling back to its shipped default.
 *
 * @param string $key Setting id.
 * @return string|bool Stored value or default.
 */
function kindredcare_option( string $key ) {
	$defaults = kindredcare_defaults();

	return get_theme_mod( $key, $defaults[ $key ] ?? '' );
}

/**
 * Inline tags allowed in the top-bar note.
 *
 * The shipped copy emphasises the region with <strong>; the rest is there so an
 * editor can reword without the markup being eaten.
 *
 * @return array<string, array<string, bool>>
 */
function kindredcare_note_allowed_html(): array {
	return array(
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'br'     => array(),
	);
}

add_action( 'customize_register', 'kindredcare_customize_register' );
/**
 * Register the KindredCare panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function kindredcare_customize_register( WP_Customize_Manager $wp_customize ): void {
	$defaults = kindredcare_defaults();

	$wp_customize->add_panel(
		'kindredcare',
		array(
			'title'       => __( 'KindredCare', 'kindredcare' ),
			'description' => __( 'Content for the site chrome — the strip above the header, and the header itself.', 'kindredcare' ),
			'priority'    => 20,
		)
	);

	/* -- Top bar ---------------------------------------------------------- */
	$wp_customize->add_section(
		'kindredcare_topbar',
		array(
			'title'       => __( 'Top bar', 'kindredcare' ),
			'description' => __( 'The dark utility strip above the header. Hidden below 768px by design — the phone number also lives in the off-canvas contact card, and the caregiver CTA is repeated in section 09.', 'kindredcare' ),
			'panel'       => 'kindredcare',
		)
	);

	$wp_customize->add_setting(
		'kindredcare_topbar_enabled',
		array(
			'default'           => $defaults['kindredcare_topbar_enabled'],
			'sanitize_callback' => 'kindredcare_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'kindredcare_topbar_enabled',
		array(
			'label'   => __( 'Show the top bar', 'kindredcare' ),
			'section' => 'kindredcare_topbar',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'kindredcare_topbar_note',
		array(
			'default'           => $defaults['kindredcare_topbar_note'],
			'sanitize_callback' => 'kindredcare_sanitize_note',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'kindredcare_topbar_note',
		array(
			'label'       => __( 'Note (left)', 'kindredcare' ),
			'description' => __( '<strong>, <em> and <br> are allowed.', 'kindredcare' ),
			'section'     => 'kindredcare_topbar',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'kindredcare_topbar_phone',
		array(
			'default'           => $defaults['kindredcare_topbar_phone'],
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'kindredcare_topbar_phone',
		array(
			'label'       => __( 'Phone number', 'kindredcare' ),
			'description' => __( 'Shown as typed. The tel: link is derived from the digits, so formatting is free.', 'kindredcare' ),
			'section'     => 'kindredcare_topbar',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'kindredcare_topbar_cta_label',
		array(
			'default'           => $defaults['kindredcare_topbar_cta_label'],
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'kindredcare_topbar_cta_label',
		array(
			'label'       => __( 'Button label', 'kindredcare' ),
			'description' => __( 'Keep it short — the button sits on one line beside the phone number.', 'kindredcare' ),
			'section'     => 'kindredcare_topbar',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'kindredcare_topbar_cta_url',
		array(
			'default'           => $defaults['kindredcare_topbar_cta_url'],
			'sanitize_callback' => 'kindredcare_sanitize_link',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'kindredcare_topbar_cta_url',
		array(
			'label'       => __( 'Button link', 'kindredcare' ),
			'description' => __( 'A full URL, or an on-page anchor such as #for-caregivers.', 'kindredcare' ),
			'section'     => 'kindredcare_topbar',
			'type'        => 'text',
		)
	);

	/* -- Header ----------------------------------------------------------- */
	$wp_customize->add_section(
		'kindredcare_header',
		array(
			'title'       => __( 'Header', 'kindredcare' ),
			'description' => __( 'The logo is set under Site Identity; the navigation under Menus, in the "Header — desktop bar" location.', 'kindredcare' ),
			'panel'       => 'kindredcare',
		)
	);

	$wp_customize->add_setting(
		'kindredcare_header_search_enabled',
		array(
			'default'           => $defaults['kindredcare_header_search_enabled'],
			'sanitize_callback' => 'kindredcare_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'kindredcare_header_search_enabled',
		array(
			'label'   => __( 'Show the search field', 'kindredcare' ),
			'section' => 'kindredcare_header',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'kindredcare_header_search_placeholder',
		array(
			'default'           => $defaults['kindredcare_header_search_placeholder'],
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'kindredcare_header_search_placeholder',
		array(
			'label'   => __( 'Search placeholder', 'kindredcare' ),
			'section' => 'kindredcare_header',
			'type'    => 'text',
		)
	);

	/* -- Footer ----------------------------------------------------------- */
	$wp_customize->add_section(
		'kindredcare_footer',
		array(
			'title'       => __( 'Footer', 'kindredcare' ),
			'description' => __( 'The mark is the Site Identity logo, shared with the header. The two link columns are menus — see the "Footer — quick links" and "Footer — type of care" locations under Menus.', 'kindredcare' ),
			'panel'       => 'kindredcare',
		)
	);

	$footer_controls = array(
		'kindredcare_footer_shout_label'            => array(
			'label'       => __( 'Contact line', 'kindredcare' ),
			'description' => __( 'The oversized line on the left of the subscribe band.', 'kindredcare' ),
			'type'        => 'text',
			'sanitize'    => 'sanitize_text_field',
		),
		'kindredcare_footer_shout_email'            => array(
			'label'       => __( 'Contact line email', 'kindredcare' ),
			'description' => __( 'Where that line links. Clear it to render the text without a link.', 'kindredcare' ),
			'type'        => 'email',
			'sanitize'    => 'sanitize_email',
		),
		'kindredcare_footer_newsletter_title'       => array(
			'label'       => __( 'Newsletter heading', 'kindredcare' ),
			'description' => __( 'Clear this to remove the newsletter form entirely.', 'kindredcare' ),
			'type'        => 'text',
			'sanitize'    => 'sanitize_text_field',
		),
		'kindredcare_footer_newsletter_placeholder' => array(
			'label'    => __( 'Newsletter placeholder', 'kindredcare' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'kindredcare_footer_newsletter_action'      => array(
			'label'       => __( 'Newsletter form action', 'kindredcare' ),
			'description' => __( 'The list provider endpoint the form posts to. <strong>While this is empty the form posts nowhere and the address is discarded</strong> — set it, or clear the heading above to drop the form.', 'kindredcare' ),
			'type'        => 'url',
			'sanitize'    => 'esc_url_raw',
		),
		'kindredcare_footer_serve_heading'          => array(
			'label'    => __( 'Column 1 heading', 'kindredcare' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'kindredcare_footer_serve_lines'            => array(
			'label'       => __( 'Column 1 lines', 'kindredcare' ),
			'description' => __( 'One line per row.', 'kindredcare' ),
			'type'        => 'textarea',
			'sanitize'    => 'sanitize_textarea_field',
		),
		'kindredcare_footer_quick_heading'          => array(
			'label'       => __( 'Column 2 heading', 'kindredcare' ),
			'description' => __( 'Clear this to remove the column. Its links come from the "Footer — quick links" menu.', 'kindredcare' ),
			'type'        => 'text',
			'sanitize'    => 'sanitize_text_field',
		),
		'kindredcare_footer_care_heading'           => array(
			'label'       => __( 'Column 3 heading', 'kindredcare' ),
			'description' => __( 'Clear this to remove the column. Its links come from the "Footer — type of care" menu.', 'kindredcare' ),
			'type'        => 'text',
			'sanitize'    => 'sanitize_text_field',
		),
		'kindredcare_footer_contact_heading'        => array(
			'label'    => __( 'Column 4 heading', 'kindredcare' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'kindredcare_footer_email'                  => array(
			'label'    => __( 'Contact email', 'kindredcare' ),
			'type'     => 'email',
			'sanitize' => 'sanitize_email',
		),
		'kindredcare_footer_phone'                  => array(
			'label'       => __( 'Contact phone', 'kindredcare' ),
			'description' => __( 'Shown as typed; the tel: link is derived from the digits.', 'kindredcare' ),
			'type'        => 'text',
			'sanitize'    => 'sanitize_text_field',
		),
		'kindredcare_footer_location'               => array(
			'label'    => __( 'Contact location', 'kindredcare' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'kindredcare_footer_connect_heading'        => array(
			'label'    => __( 'Social heading', 'kindredcare' ),
			'type'     => 'text',
			'sanitize' => 'sanitize_text_field',
		),
		'kindredcare_footer_copyright'              => array(
			'label'       => __( 'Copyright line', 'kindredcare' ),
			'description' => __( '<code>{year}</code> is replaced with the current year.', 'kindredcare' ),
			'type'        => 'text',
			'sanitize'    => 'kindredcare_sanitize_note',
		),
	);

	/*
	 * One control per network rather than a repeatable list — each icon is a
	 * hand-drawn path, so a network with no entry in inc/social.php has nothing
	 * to draw. An empty URL drops that icon.
	 */
	foreach ( kindredcare_social_networks() as $network ) {
		$footer_controls[ $network['setting'] ] = array(
			/* translators: %s: social network name. */
			'label'    => sprintf( __( '%s URL', 'kindredcare' ), $network['label'] ),
			'type'     => 'url',
			'sanitize' => 'esc_url_raw',
		);
	}

	foreach ( $footer_controls as $id => $control ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $defaults[ $id ] ?? '',
				'sanitize_callback' => $control['sanitize'],
				'transport'         => 'postMessage',
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'label'       => $control['label'],
				'description' => $control['description'] ?? '',
				'section'     => 'kindredcare_footer',
				'type'        => $control['type'],
			)
		);
	}

	/* -- Live preview ----------------------------------------------------- */
	/*
	 * Selective refresh re-renders just the strip instead of reloading the
	 * whole preview. That matters more here than on a typical theme: a full
	 * reload replays the hero typewriter and the GSAP entrance timelines every
	 * keystroke, which makes the field unusable.
	 */
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'kindredcare_topbar',
			array(
				'selector'            => '.l-topbar__inner',
				'container_inclusive' => false,
				'render_callback'     => 'kindredcare_render_topbar_inner',
				'settings'            => array(
					'kindredcare_topbar_note',
					'kindredcare_topbar_phone',
					'kindredcare_topbar_cta_label',
					'kindredcare_topbar_cta_url',
				),
			)
		);

		$wp_customize->selective_refresh->add_partial(
			'kindredcare_search_placeholder',
			array(
				'selector'            => '.l-header__tools .c-search',
				'container_inclusive' => true,
				'render_callback'     => 'kindredcare_render_header_search',
				'settings'            => array( 'kindredcare_header_search_placeholder' ),
			)
		);

		/*
		 * The footer re-renders whole. Every setting in the section above uses
		 * postMessage, so without this partial none of them would move the
		 * preview at all.
		 */
		$wp_customize->selective_refresh->add_partial(
			'kindredcare_footer',
			array(
				'selector'            => '.l-footer',
				'container_inclusive' => true,
				'render_callback'     => 'kindredcare_footer',
				/*
				 * custom_logo and blogname are listed because the footer draws
				 * the mark and uses the site title for its alt text and link
				 * label. Without them, changing either would update the header
				 * and leave the footer stale.
				 */
				'settings'            => array_merge(
					array_keys( kindredcare_footer_defaults() ),
					array( 'custom_logo', 'blogname' )
				),
			)
		);

		/*
		 * Repoint core's custom_logo partial at our own renderer.
		 *
		 * Its shipped selector is .custom-logo-link and its callback returns
		 * get_custom_logo() — the markup the_custom_logo() would have produced.
		 * This theme builds the brand link by hand (see
		 * kindredcare_render_brand()), so leaving the callback alone would swap
		 * our .l-header__brand for core's wrapper mid-preview and drop the
		 * header's styling. The footer's copy of the mark is covered by the
		 * partial above.
		 */
		$brand = $wp_customize->selective_refresh->get_partial( 'custom_logo' );
		if ( $brand ) {
			$brand->selector        = '.l-header__brand';
			$brand->render_callback = 'kindredcare_render_brand';
		}
	}
}

/**
 * Sanitize a checkbox setting.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function kindredcare_sanitize_checkbox( $value ): bool {
	return (bool) $value;
}

/**
 * Sanitize the top-bar note, keeping a short inline tag allowlist.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function kindredcare_sanitize_note( $value ): string {
	return wp_kses( (string) $value, kindredcare_note_allowed_html() );
}

/**
 * Sanitize a link that may be a full URL or a bare on-page fragment.
 *
 * esc_url_raw() already passes '#anchor' through untouched; this exists to make
 * that intent explicit and to normalise whitespace first.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function kindredcare_sanitize_link( $value ): string {
	return esc_url_raw( trim( (string) $value ) );
}
