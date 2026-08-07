<?php
/**
 * Social profile registry.
 *
 * A fixed set of networks rather than a repeatable list. Each icon is a
 * hand-drawn path sized to this design; a free-form "add a link" control would
 * have no icon to draw and would fall back to a generic glyph, which is exactly
 * the kind of drift the port is trying to avoid. Adding a network is a code
 * change here plus a Customizer control — deliberately.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * The supported networks, in render order.
 *
 * Paths are lifted verbatim from the static build so the marks are identical to
 * the ones the hero already draws.
 *
 * @return array<string, array{label: string, setting: string, path: string}>
 */
function kindredcare_social_networks(): array {
	return array(
		'facebook'  => array(
			'label'   => 'Facebook',
			'setting' => 'kindredcare_social_facebook',
			'path'    => 'M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5H16.7V3.6c-.3-.04-1.3-.13-2.47-.13-2.45 0-4.13 1.5-4.13 4.24V9.9H7.4V13h2.7v8z',
		),
		'linkedin'  => array(
			'label'   => 'LinkedIn',
			'setting' => 'kindredcare_social_linkedin',
			'path'    => 'M4.98 3.5A2.5 2.5 0 1 0 5 8.5a2.5 2.5 0 0 0-.02-5ZM3 9h4v12H3zM9 9h3.8v1.7h.06c.53-1 1.83-2.06 3.76-2.06C20.5 8.64 21 11.1 21 14.3V21h-4v-5.9c0-1.4-.03-3.2-1.95-3.2-1.96 0-2.26 1.53-2.26 3.1V21H9z',
		),
		'instagram' => array(
			'label'   => 'Instagram',
			'setting' => 'kindredcare_social_instagram',
			'path'    => 'M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16Zm0 6.01a3.83 3.83 0 1 0 0 7.66 3.83 3.83 0 0 0 0-7.66Zm0 6.32a2.49 2.49 0 1 1 0-4.98 2.49 2.49 0 0 1 0 4.98Zm4.88-6.47a.9.9 0 1 1-1.79 0 .9.9 0 0 1 1.79 0Z',
		),
		'x'         => array(
			'label'   => 'X',
			'setting' => 'kindredcare_social_x',
			'path'    => 'M17.53 3h3.02l-6.6 7.54L21.7 21h-6.06l-4.75-6.2L5.46 21H2.44l7.06-8.07L2.3 3h6.21l4.29 5.67L17.53 3Zm-1.06 16.17h1.67L7.6 4.73H5.81l10.66 14.44Z',
		),
	);
}

/**
 * The networks that currently have a URL set, in render order.
 *
 * @return array<string, array{label: string, setting: string, path: string, url: string}>
 */
function kindredcare_active_social_networks(): array {
	$active = array();

	foreach ( kindredcare_social_networks() as $key => $network ) {
		$url = trim( (string) kindredcare_option( $network['setting'] ) );

		if ( '' === $url ) {
			continue;
		}

		$network['url']  = $url;
		$active[ $key ]  = $network;
	}

	return $active;
}

/**
 * Print one social icon as an inline SVG.
 *
 * @param array{path: string} $network Network definition.
 * @param int                 $size    Square size in pixels.
 */
function kindredcare_social_icon( array $network, int $size = 18 ): void {
	printf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="%2$s" /></svg>',
		$size,
		esc_attr( $network['path'] )
	);
}
