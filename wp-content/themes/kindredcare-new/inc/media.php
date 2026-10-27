<?php
/**
 * Bringing the platform's pictures into this site's media library.
 *
 * The rest of this theme refuses to fetch from the platform while a page is
 * rendering — see the note at the top of inc/services-api.php. Storing a remote
 * URL and printing it into an <img> quietly breaks that rule: the markup is
 * local but the picture is not, and every visitor's browser goes to the
 * platform for it on every view.
 *
 * That is not a theoretical objection. A portrait on the platform is whatever
 * the caregiver uploaded — three-quarters of a megabyte of PNG in one case
 * here — and hotlinked it is served at that size to every visitor, at whatever
 * dimensions it happens to have, from a host that is `localhost` during
 * development and plain http after that. An https page will not load an http
 * image at all.
 *
 * So the pictures are downloaded, once, at import. After that they are ordinary
 * attachments: resized into the theme's own sizes, given a srcset, served from
 * this site, and still there when the platform is not.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Attachment meta recording where a downloaded picture came from.
 *
 * This is what makes the whole thing cheap. Four gigs by the same caregiver
 * name the same portrait, and a re-import names every picture again — without a
 * record of what has already been fetched, that is four copies the first time
 * and fifteen more downloads every time after.
 */
const KINDREDCARE_MEDIA_SOURCE_META = '_kc_source_url';

/** How long one file is given to arrive. */
const KINDREDCARE_MEDIA_TIMEOUT = 30;

/**
 * How long an import may spend downloading before it stops and says so.
 *
 * Fifteen gigs sharing four portraits is four downloads and this never comes
 * near. Two hundred gigs with two hundred photographs is not, and a shared host
 * will kill the request at thirty or sixty seconds — leaving an import that
 * died half-way with no message, which is the worst of all outcomes. Past the
 * budget the text still imports and the pictures are reported as outstanding,
 * so pressing the button again picks up exactly where this left off.
 */
const KINDREDCARE_MEDIA_BUDGET = 45;

/** Largest file worth taking. Past this something is wrong at the other end. */
const KINDREDCARE_MEDIA_MAX_BYTES = 12582912; // 12 MB.

/**
 * The key a downloaded picture is remembered by.
 *
 * Anything on the platform is keyed by its path alone. That is deliberate: the
 * platform's address is a setting and is expected to change, and keying on the
 * whole URL would mean every picture on the site downloading itself again the
 * first time it is imported from the new address — the same bytes, under a new
 * name, for no reason. Anything elsewhere is keyed by its full URL, where the
 * host is genuinely part of what identifies the file.
 *
 * @param string $url Absolute URL of the picture.
 */
function kindredcare_media_key( string $url ): string {
	$base = kindredcare_api_base();

	if ( '' !== $base && str_starts_with( $url, $base . '/' ) ) {
		return substr( $url, strlen( $base ) );
	}

	return $url;
}

/**
 * The attachment already holding a given picture, if there is one.
 *
 * @param string $url Absolute URL of the picture.
 * @return int Attachment id, or 0.
 */
function kindredcare_media_find( string $url ): int {
	$found = get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'inherit',
			'numberposts'      => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- an indexed lookup on a key this theme owns; it is the whole point of the key.
				array(
					'key'   => KINDREDCARE_MEDIA_SOURCE_META,
					'value' => kindredcare_media_key( $url ),
				),
			),
		)
	);

	return $found ? absint( $found[0] ) : 0;
}

/**
 * Whether this request has spent long enough downloading.
 *
 * The clock starts at the first picture rather than at the start of the
 * request, so the budget measures downloading and not the whole import.
 */
function kindredcare_media_over_budget(): bool {
	static $started = null;

	if ( null === $started ) {
		$started = microtime( true );

		return false;
	}

	return ( microtime( true ) - $started ) > KINDREDCARE_MEDIA_BUDGET;
}

/**
 * Fetch a picture to a temporary file.
 *
 * Two routes, and which one is taken is the whole of the security thinking here.
 *
 * WordPress's own download_url() refuses two things this theme has to cope with:
 * hosts that resolve to a private or loopback address, and ports outside 80,
 * 443 and 8080. Both refusals are correct in general — they are what stops a
 * remote document talking this server into fetching from its own network — and
 * both describe the platform exactly as it runs during development, on
 * localhost, on port 8001.
 *
 * Loosening those rules globally would turn off that protection for every
 * request WordPress makes, so instead the URL decides:
 *
 *   On the platform we are configured to talk to, the request is made directly.
 *   That address was typed in by someone with rights over the whole theme and
 *   can be pinned in wp-config.php, and this theme already fetches JSON from it
 *   the same way. A picture is not a greater risk than the document naming it.
 *
 *   Anywhere else — and the seed data points at an outside avatar service, so
 *   "anywhere else" is the common case — goes through download_url() with every
 *   one of WordPress's checks intact. That matters: these URLs arrive inside a
 *   payload, and a payload that could name any address on the local network and
 *   have this server fetch it is the definition of the hole those checks exist
 *   to close.
 *
 * @param string $url Absolute URL of the picture.
 * @return string|WP_Error Path to a temporary file, or why there is none.
 */
function kindredcare_media_download( string $url ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$base = kindredcare_api_base();

	if ( '' === $base || ! str_starts_with( $url, $base . '/' ) ) {
		return download_url( $url, KINDREDCARE_MEDIA_TIMEOUT );
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => KINDREDCARE_MEDIA_TIMEOUT,
			'redirection' => 3,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code ) {
		return new WP_Error(
			'kindredcare_media_http',
			sprintf(
				/* translators: %d: HTTP status code. */
				__( 'The platform answered with HTTP %d for that picture.', 'kindredcare' ),
				$code
			)
		);
	}

	$body = wp_remote_retrieve_body( $response );

	if ( '' === $body ) {
		return new WP_Error(
			'kindredcare_media_empty',
			__( 'The platform sent an empty file.', 'kindredcare' )
		);
	}

	$temp = wp_tempnam( $url );

	if ( ! $temp ) {
		return new WP_Error(
			'kindredcare_media_no_temp',
			__( 'This site could not make a temporary file to download into.', 'kindredcare' )
		);
	}

	if ( false === file_put_contents( $temp, $body ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- writing a temp file for media_handle_sideload(), which is what core's own download_url() does here.
		wp_delete_file( $temp );

		return new WP_Error(
			'kindredcare_media_unwritable',
			__( 'This site could not write the downloaded file to disk.', 'kindredcare' )
		);
	}

	return $temp;
}

/**
 * Fetch a picture into the media library and remember where it came from.
 *
 * @param string $url         Absolute URL of the picture.
 * @param string $description What the picture is, used for its title and alt text.
 * @return int|WP_Error Attachment id, or why there is none.
 */
function kindredcare_media_sideload( string $url, string $description ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$temp = kindredcare_media_download( $url );

	if ( is_wp_error( $temp ) ) {
		return $temp;
	}

	if ( filesize( $temp ) > KINDREDCARE_MEDIA_MAX_BYTES ) {
		wp_delete_file( $temp );

		return new WP_Error(
			'kindredcare_media_too_large',
			__( 'The file was larger than this theme is willing to store.', 'kindredcare' )
		);
	}

	/*
	 * The filename has to be worked out from the bytes, not from the URL. The
	 * placeholder avatars in the seed data live at addresses like
	 * "i.pravatar.cc/300?img=20" — no filename and no extension anywhere in it —
	 * and WordPress will not accept a sideload it cannot name a type for.
	 */
	$size = wp_getimagesize( $temp );
	$mime = is_array( $size ) ? (string) ( $size['mime'] ?? '' ) : '';

	if ( ! $mime || ! str_starts_with( $mime, 'image/' ) ) {
		wp_delete_file( $temp );

		return new WP_Error(
			'kindredcare_media_not_an_image',
			__( 'What came back was not an image.', 'kindredcare' )
		);
	}

	$extension = wp_get_default_extension_for_mime_type( $mime );

	if ( ! $extension ) {
		wp_delete_file( $temp );

		return new WP_Error(
			'kindredcare_media_unsupported',
			sprintf(
				/* translators: %s: MIME type, e.g. image/avif. */
				__( 'This site does not accept %s files.', 'kindredcare' ),
				$mime
			)
		);
	}

	$slug = sanitize_title( $description );

	if ( '' === $slug ) {
		$slug = 'kindredcare-' . substr( md5( $url ), 0, 8 );
	}

	$attachment = media_handle_sideload(
		array(
			'name'     => sanitize_file_name( $slug . '.' . $extension ),
			'tmp_name' => $temp,
		),
		/*
		 * Deliberately unparented. One portrait belongs to every gig that
		 * caregiver has posted, so hanging it off whichever one happened to be
		 * imported first would be a lie the media library then repeats.
		 */
		0,
		$description
	);

	if ( is_wp_error( $attachment ) ) {
		// media_handle_sideload() cleans up after itself only on success.
		if ( file_exists( $temp ) ) {
			wp_delete_file( $temp );
		}

		return $attachment;
	}

	$attachment = absint( $attachment );

	update_post_meta( $attachment, KINDREDCARE_MEDIA_SOURCE_META, kindredcare_media_key( $url ) );

	/*
	 * Alt text matters more here than on a picture someone chose by hand: these
	 * arrive without anyone looking at them, and an empty alt on a portrait
	 * beside a caregiver's name leaves a screen reader with a name and no
	 * indication there is a face next to it.
	 */
	update_post_meta( $attachment, '_wp_attachment_image_alt', $description );

	return $attachment;
}

/**
 * Get a picture into the library, downloading it only if it is not already there.
 *
 * The one function the importers call. It never throws and never fails an
 * import: a picture that cannot be fetched is reported and the gig is stored
 * without it, because a listing with no photograph is worth having and a failed
 * import is not.
 *
 * @param string $url         Absolute URL of the picture.
 * @param string $description What the picture is.
 * @return array{id: int, downloaded: bool, pending: bool}
 */
function kindredcare_media_import( string $url, string $description ): array {
	$none = array(
		'id'         => 0,
		'downloaded' => false,
		'pending'    => false,
	);

	$url = esc_url_raw( trim( $url ), array( 'http', 'https' ) );

	if ( '' === $url ) {
		return $none;
	}

	$existing = kindredcare_media_find( $url );

	if ( $existing ) {
		return array(
			'id'         => $existing,
			'downloaded' => false,
			'pending'    => false,
		);
	}

	if ( kindredcare_media_over_budget() ) {
		return array(
			'id'         => 0,
			'downloaded' => false,
			// Not a failure — simply not reached yet. Say so, and it is picked
			// up by the next run rather than being quietly given up on.
			'pending'    => true,
		);
	}

	$attachment = kindredcare_media_sideload( $url, $description );

	if ( is_wp_error( $attachment ) ) {
		return array(
			'id'         => 0,
			'downloaded' => false,
			'pending'    => true,
		);
	}

	return array(
		'id'         => absint( $attachment ),
		'downloaded' => true,
		'pending'    => false,
	);
}
