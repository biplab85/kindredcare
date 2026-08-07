<?php
/**
 * Contact form handling.
 *
 * The live site's form is a page-builder widget backed by that builder's own
 * submission service. Nothing of that comes across, so the form here is a plain
 * POST to admin-post.php with the usual protections: a nonce, a honeypot,
 * per-field sanitising, and a redirect back to the page carrying a status.
 *
 * Submissions are emailed to the address in Settings -> General and also stored
 * as a private post, so nothing is lost if mail delivery fails — which it will
 * on a local install with no mail transport configured.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_ENQUIRY_ACTION = 'kindredcare_enquiry';

add_action( 'init', 'kindredcare_register_enquiry_type' );
/**
 * Where submissions are kept.
 *
 * Not public and not in search: this is a record, not content.
 */
function kindredcare_register_enquiry_type(): void {
	register_post_type(
		'kc_enquiry',
		array(
			'labels'          => array(
				'name'          => __( 'Enquiries', 'kindredcare' ),
				'singular_name' => __( 'Enquiry', 'kindredcare' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email-alt',
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor' ),
			'has_archive'     => false,
			'rewrite'         => false,
		)
	);
}

add_action( 'admin_post_nopriv_' . KINDREDCARE_ENQUIRY_ACTION, 'kindredcare_handle_enquiry' );
add_action( 'admin_post_' . KINDREDCARE_ENQUIRY_ACTION, 'kindredcare_handle_enquiry' );
/**
 * Validate, store and forward a submission.
 */
function kindredcare_handle_enquiry(): void {
	$redirect = wp_get_referer() ?: home_url( '/' );

	// Nonce first: everything below trusts the request came from our form.
	if (
		! isset( $_POST['kc_enquiry_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kc_enquiry_nonce'] ) ), KINDREDCARE_ENQUIRY_ACTION )
	) {
		wp_safe_redirect( add_query_arg( 'enquiry', 'expired', $redirect ) . '#enquiry' );
		exit;
	}

	// Honeypot. A real person cannot fill a field that is not on screen.
	if ( ! empty( $_POST['kc_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'enquiry', 'sent', $redirect ) . '#enquiry' );
		exit;
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['kc_name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['kc_email'] ?? '' ) );
	$company = sanitize_text_field( wp_unslash( $_POST['kc_company'] ?? '' ) );
	$phone   = sanitize_text_field( wp_unslash( $_POST['kc_phone'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['kc_message'] ?? '' ) );

	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'enquiry', 'invalid', $redirect ) . '#enquiry' );
		exit;
	}

	$lines = array(
		sprintf( 'Name: %s', $name ),
		sprintf( 'Email: %s', $email ),
		$company ? sprintf( 'Company: %s', $company ) : '',
		$phone ? sprintf( 'Phone: %s', $phone ) : '',
		'',
		$message,
	);
	$body  = implode( "\n", array_filter( $lines, static fn( $l ) => '' !== $l ) );

	// Stored before sending, so a mail failure cannot lose the enquiry.
	wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'kc_enquiry',
				'post_status'  => 'private',
				/* translators: %s: sender name. */
				'post_title'   => sprintf( __( 'Enquiry from %s', 'kindredcare' ), $name ),
				'post_content' => $body,
			)
		)
	);

	$sent = wp_mail(
		(string) get_option( 'admin_email' ),
		/* translators: %s: sender name. */
		sprintf( __( 'Website enquiry from %s', 'kindredcare' ), $name ),
		$body,
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	/*
	 * 'stored' rather than 'sent' when delivery failed: the message is safe in
	 * the admin, and telling someone their enquiry was sent when it was not is
	 * the one outcome worth avoiding.
	 */
	wp_safe_redirect( add_query_arg( 'enquiry', $sent ? 'sent' : 'stored', $redirect ) . '#enquiry' );
	exit;
}

/**
 * The status banner shown after a redirect back from the handler.
 */
function kindredcare_enquiry_notice(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of our own redirect flag.
	$state = isset( $_GET['enquiry'] ) ? sanitize_key( wp_unslash( $_GET['enquiry'] ) ) : '';

	if ( '' === $state ) {
		return;
	}

	$messages = array(
		'sent'    => array( 'ok', __( 'Thank you — your message is on its way. We reply within one business day.', 'kindredcare' ) ),
		'stored'  => array( 'ok', __( 'Thank you — your message has been received. We reply within one business day.', 'kindredcare' ) ),
		'invalid' => array( 'error', __( 'Please add your name, a valid email address and a message.', 'kindredcare' ) ),
		'expired' => array( 'error', __( 'That form had been open a while and expired. Please send it again.', 'kindredcare' ) ),
	);

	if ( ! isset( $messages[ $state ] ) ) {
		return;
	}

	list( $kind, $text ) = $messages[ $state ];
	?>
	<p class="c-notice c-notice--<?php echo esc_attr( $kind ); ?>" role="status">
		<?php echo esc_html( $text ); ?>
	</p>
	<?php
}
