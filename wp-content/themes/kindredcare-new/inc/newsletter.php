<?php
/**
 * Newsletter subscriptions.
 *
 * The footer's subscribe box shipped with no destination: it posted to whatever
 * page it sat on, WordPress had no route for it, and the address was dropped
 * while the visitor saw a cleared field and read that as success. This gives it
 * somewhere to go.
 *
 * Same shape as the enquiry form in inc/contact.php — a plain POST to
 * admin-post.php with a nonce, a honeypot, per-field sanitising and a redirect
 * carrying a status. Addresses are stored as private posts so the list is owned
 * here rather than at a third party, and can be exported into one later.
 *
 * A provider endpoint set under Customizer -> KindredCare -> Footer still wins:
 * if the site is already wired to Mailchimp or similar, the form posts there and
 * none of this runs. See kindredcare_render_footer_subscribe().
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_SUBSCRIBE_ACTION = 'kindredcare_subscribe';

/**
 * Meta key holding the lowercased address.
 *
 * The post title carries the address for the admin list, but titles pass
 * through filters and are not a safe thing to match on. Duplicates are checked
 * against this instead.
 */
const KINDREDCARE_SUBSCRIBER_EMAIL_KEY = '_kc_subscriber_email';

add_action( 'init', 'kindredcare_register_subscriber_type' );
/**
 * Where subscriptions are kept.
 *
 * Not public and not in search: this is a list, not content. Nobody adds one by
 * hand either — an address in here means someone typed it into the footer.
 */
function kindredcare_register_subscriber_type(): void {
	register_post_type(
		'kc_subscriber',
		array(
			'labels'          => array(
				'name'          => __( 'Subscribers', 'kindredcare' ),
				'singular_name' => __( 'Subscriber', 'kindredcare' ),
				'search_items'  => __( 'Search subscribers', 'kindredcare' ),
				'not_found'     => __( 'Nobody has subscribed yet.', 'kindredcare' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-megaphone',
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'supports'        => array( 'title' ),
			'has_archive'     => false,
			'rewrite'         => false,
		)
	);
}

/**
 * Whether an address is already on the list.
 *
 * @param string $email Lowercased address.
 * @return bool
 */
function kindredcare_is_subscribed( string $email ): bool {
	$found = get_posts(
		array(
			'post_type'              => 'kc_subscriber',
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'meta_key'               => KINDREDCARE_SUBSCRIBER_EMAIL_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- an indexed exact match on a small table.
			'meta_value'             => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above.
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return ! empty( $found );
}

add_action( 'admin_post_nopriv_' . KINDREDCARE_SUBSCRIBE_ACTION, 'kindredcare_handle_subscribe' );
add_action( 'admin_post_' . KINDREDCARE_SUBSCRIBE_ACTION, 'kindredcare_handle_subscribe' );
/**
 * Validate and store a subscription.
 */
function kindredcare_handle_subscribe(): void {
	$redirect = wp_get_referer() ?: home_url( '/' );

	/** Send the visitor back to the box they typed into. */
	$back = static function ( string $state ) use ( $redirect ): void {
		wp_safe_redirect( add_query_arg( 'subscribe', $state, $redirect ) . '#newsletter' );
		exit;
	};

	// Nonce first: everything below trusts the request came from our form.
	if (
		! isset( $_POST['kc_subscribe_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kc_subscribe_nonce'] ) ), KINDREDCARE_SUBSCRIBE_ACTION )
	) {
		$back( 'expired' );
	}

	// Honeypot. A real person cannot fill a field that is not on screen.
	if ( ! empty( $_POST['kc_hp'] ) ) {
		$back( 'ok' );
	}

	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

	if ( ! is_email( $email ) ) {
		$back( 'invalid' );
	}

	$email = strtolower( $email );

	/*
	 * Saying "you are already on the list" rather than "subscribed" is the
	 * honest answer and costs nothing — this list is not secret, and the
	 * alternative is someone subscribing three times and wondering why.
	 */
	if ( kindredcare_is_subscribed( $email ) ) {
		$back( 'already' );
	}

	$id = wp_insert_post(
		wp_slash(
			array(
				'post_type'   => 'kc_subscriber',
				'post_status' => 'private',
				'post_title'  => $email,
			)
		)
	);

	if ( ! $id || is_wp_error( $id ) ) {
		$back( 'error' );
	}

	update_post_meta( $id, KINDREDCARE_SUBSCRIBER_EMAIL_KEY, $email );

	/*
	 * Which page the box was on. Useful later for telling a footer signup from
	 * one on a campaign page, and it costs one row.
	 */
	update_post_meta( $id, '_kc_subscriber_source', esc_url_raw( $redirect ) );

	/*
	 * A heads-up to the site's own address. The subscription is already saved,
	 * so whether this sends changes nothing the visitor is told — unlike the
	 * enquiry form, where the message *is* the mail.
	 */
	$from_email = (string) kindredcare_option( 'kindredcare_footer_email' );
	if ( ! is_email( $from_email ) ) {
		$from_email = (string) get_option( 'admin_email' );
	}

	$mail = kindredcare_subscriber_email( $email, $redirect );

	kindredcare_email_alt( $mail['text'] );

	wp_mail(
		kindredcare_enquiry_recipient(),
		__( 'New newsletter subscriber', 'kindredcare' ),
		$mail['html'],
		array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ), $from_email ),
		)
	);

	$back( 'ok' );
}

/**
 * The status banner shown after a redirect back from the handler.
 *
 * Printed inside the footer's newsletter block, which sits on the dark ground —
 * hence its own class rather than the .c-notice the contact page uses.
 */
function kindredcare_subscribe_notice(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of our own redirect flag.
	$state = isset( $_GET['subscribe'] ) ? sanitize_key( wp_unslash( $_GET['subscribe'] ) ) : '';

	if ( '' === $state ) {
		return;
	}

	$messages = array(
		'ok'      => array( 'ok', __( 'Thank you — you are on the list.', 'kindredcare' ) ),
		'already' => array( 'ok', __( 'You are already on the list — nothing more to do.', 'kindredcare' ) ),
		'invalid' => array( 'error', __( 'That does not look like an email address. Please check it and try again.', 'kindredcare' ) ),
		'expired' => array( 'error', __( 'That form had been open a while and expired. Please try again.', 'kindredcare' ) ),
		'error'   => array( 'error', __( 'Something went wrong saving that. Please try again.', 'kindredcare' ) ),
	);

	if ( ! isset( $messages[ $state ] ) ) {
		return;
	}

	list( $kind, $text ) = $messages[ $state ];
	?>
	<p class="l-footer__notice l-footer__notice--<?php echo esc_attr( $kind ); ?>" role="status">
		<?php echo esc_html( $text ); ?>
	</p>
	<?php
}
