<?php
/**
 * Where the platform lives, and how this theme talks to it.
 *
 * Every request to the Laravel side goes through here. The reason is a single
 * address: the platform is on localhost today and on its own domain the moment
 * this is deployed, and an install that hardcodes the host in three files is an
 * install where moving it means finding all three.
 *
 * So one option holds the origin — scheme, host, port, nothing else — and each
 * caller supplies only the path it wants. Changing the address on a live site
 * is then one field on one screen, and the service catalogue and the gigs both
 * follow it without being told.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * The origin used when nobody has set one.
 *
 * Only a starting value, and only ever right on a development machine.
 */
const KINDREDCARE_API_BASE_DEFAULT = 'http://localhost:8001';

/** Site option holding the origin an administrator has set. */
const KINDREDCARE_API_BASE_OPTION = 'kindredcare_api_base';

/**
 * How long a request behind an admin button is given to answer.
 *
 * Far longer than would be acceptable on the front end, because nothing here
 * runs on a page render — it runs behind a button someone has just pressed and
 * is watching. Waiting fifteen seconds for an answer beats being told "could
 * not connect" by a timeout that gave up too early.
 */
const KINDREDCARE_API_TIMEOUT = 15;

/**
 * Whether wp-config.php has pinned the origin.
 *
 * A constant beats the admin field on purpose. It is how a production install
 * says "this address is part of the deployment, not something to be changed
 * from a browser" — and when it is set the admin field shows the value but
 * refuses to edit it, rather than silently accepting a change that would have
 * no effect.
 */
function kindredcare_api_base_is_locked(): bool {
	return defined( 'KINDREDCARE_API_BASE' );
}

/**
 * The platform's origin, with no trailing slash.
 *
 * Four sources, most specific first:
 *
 *   1. A KINDREDCARE_API_BASE constant in wp-config.php.
 *   2. The address set at KindredCare -> Gigs. The normal way.
 *   3. The origin of a pre-existing services endpoint, for installs upgrading
 *      from when that screen stored a whole URL of its own. Read, never
 *      written back: saving the field on the Gigs screen is what retires it.
 *   4. The bundled default.
 */
function kindredcare_api_base(): string {
	if ( kindredcare_api_base_is_locked() ) {
		$base = (string) constant( 'KINDREDCARE_API_BASE' );
	} else {
		$base = (string) get_option( KINDREDCARE_API_BASE_OPTION, '' );

		if ( '' === $base ) {
			$base = kindredcare_api_base_legacy();
		}

		if ( '' === $base ) {
			$base = KINDREDCARE_API_BASE_DEFAULT;
		}
	}

	/**
	 * Filters the platform origin.
	 *
	 * @param string $base Scheme and host, without a trailing slash.
	 */
	return untrailingslashit( (string) apply_filters( 'kindredcare_api_base', $base ) );
}

/**
 * The origin of the endpoint the Services screen used to store on its own.
 *
 * Before this file existed, the Services page held a complete URL. Anyone who
 * had already pointed that at a staging or production host should not have to
 * type the address a second time, so it is read once as a fallback here.
 *
 * @return string Origin, or an empty string if there is nothing to recover.
 */
function kindredcare_api_base_legacy(): string {
	$legacy = (string) get_option( 'kindredcare_services_endpoint', '' );

	return '' === $legacy ? '' : kindredcare_api_sanitize_base( $legacy );
}

/**
 * Clean an address someone has typed in, reducing it to an origin.
 *
 * Whatever arrives — a bare host, a trailing slash, a whole endpoint pasted out
 * of a browser — comes back as scheme://host[:port]. Keeping a path would mean
 * two callers appending their own path to someone else's, which produces a URL
 * that looks plausible and 404s.
 *
 * Scheme-checked rather than run through wp_http_validate_url(), which rejects
 * loopback and private addresses — and the platform runs on localhost during
 * development, so that check would reject the one address this is shipped
 * pointing at. The field is therefore only as trusted as the people who can
 * reach it, which is why it is bound to the same capability as editing the
 * theme's other screens and why wp-config can take it away entirely.
 *
 * @param string $value Raw submitted value.
 * @return string A usable origin, or an empty string to mean "unset".
 */
function kindredcare_api_sanitize_base( string $value ): string {
	$value = trim( $value );

	if ( '' === $value ) {
		return '';
	}

	/*
	 * "kindredcare.ca" is what people type; without a scheme wp_parse_url()
	 * reads the whole thing as a path and there is no host to check.
	 */
	if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $value ) ) {
		$value = 'https://' . $value;
	}

	$value = esc_url_raw( $value, array( 'http', 'https' ) );

	if ( ! $value ) {
		return '';
	}

	$parts  = wp_parse_url( $value );
	$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
	$host   = (string) ( $parts['host'] ?? '' );

	/*
	 * esc_url_raw() is a cleaner, not a validator: given "not a url" it prepends
	 * a scheme and percent-encodes the spaces, handing back something that looks
	 * like a URL and can never resolve. Storing that turns a typo into a fetch
	 * failure reported as "could not reach the platform", which sends whoever
	 * made it looking at the network instead of at what they typed.
	 */
	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return '';
	}

	if ( '' === $host || preg_match( '/[\s%]/', $host ) ) {
		return '';
	}

	$port = isset( $parts['port'] ) ? ':' . absint( $parts['port'] ) : '';

	return $scheme . '://' . $host . $port;
}

/**
 * Build an absolute URL for a path on the platform.
 *
 * @param string $path Leading-slashed path, e.g. '/api/public/gigs'.
 */
function kindredcare_api_url( string $path ): string {
	return kindredcare_api_base() . '/' . ltrim( $path, '/' );
}

/**
 * Ask the platform for something and hand back the decoded payload.
 *
 * The only place this theme performs an HTTP request. Called from the admin, by
 * a person pressing a button, never from a page render — see the note at the
 * top of inc/services-api.php for why that is the whole design.
 *
 * Laravel resources wrap their payload in `data`. That wrapper is unwrapped
 * here so no caller has to know about it, and a bare payload is accepted too so
 * the imports keep working if it is ever dropped.
 *
 * @param string $url Absolute URL to fetch.
 * @return array<int|string, mixed>|WP_Error Decoded payload, or the reason there is none.
 */
function kindredcare_api_get( string $url ) {
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => KINDREDCARE_API_TIMEOUT,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'kindredcare_api_unreachable',
			sprintf(
				/* translators: %s: underlying transport error. */
				__( 'Could not reach the platform: %s', 'kindredcare' ),
				$response->get_error_message()
			)
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code ) {
		return new WP_Error(
			'kindredcare_api_http',
			sprintf(
				/* translators: %d: HTTP status code. */
				__( 'The platform answered with HTTP %d.', 'kindredcare' ),
				$code
			),
			array( 'status' => $code )
		);
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $payload ) ) {
		return new WP_Error(
			'kindredcare_api_unreadable',
			__( 'The platform’s answer was not readable JSON.', 'kindredcare' )
		);
	}

	$data = $payload['data'] ?? $payload;

	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'kindredcare_api_shape',
			__( 'The platform’s answer was not in the shape this theme expects.', 'kindredcare' )
		);
	}

	return $data;
}

/**
 * Sanitise an arbitrarily nested payload fragment.
 *
 * Used for the parts of a gig whose shape belongs to the platform rather than
 * to this theme — a caregiver's verification checks, their reviews — where
 * enumerating the fields would mean guessing at them and silently dropping
 * whatever was guessed wrong. Keys and scalars are cleaned, the structure is
 * kept, and the depth is bounded so a hostile or looping document cannot turn
 * an import into a stack overflow.
 *
 * @param mixed $value Anything decoded from JSON.
 * @param int   $depth Remaining levels to descend.
 * @return mixed Cleaned value of the same shape.
 */
function kindredcare_api_sanitize_deep( $value, int $depth = 6 ) {
	if ( is_array( $value ) ) {
		if ( $depth <= 0 ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $key => $item ) {
			$key = is_int( $key ) ? $key : sanitize_key( (string) $key );

			$clean[ $key ] = kindredcare_api_sanitize_deep( $item, $depth - 1 );
		}

		return $clean;
	}

	if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
		return $value;
	}

	if ( null === $value ) {
		return '';
	}

	return sanitize_textarea_field( (string) $value );
}
