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

const KINDREDCARE_CONTACT_OPTION = 'kindredcare_contact';

/**
 * The template file that marks a page as the contact page.
 *
 * Identifying it by template rather than by slug is what lets the page be
 * renamed. "Get in touch", "Contact", "Reach us" — the slug changes, the
 * template does not, so every link to it keeps working.
 */
const KINDREDCARE_CONTACT_TEMPLATE = 'template-contact.php';

/**
 * The contact page's shipped copy.
 *
 * The address, phone and email are deliberately absent: those come from the
 * Customizer, the same values the footer prints, so there is one place to
 * change a phone number rather than two that can disagree.
 *
 * @return array<string, mixed>
 */
function kindredcare_contact_defaults(): array {
	return array(
		'kicker'        => 'Request an appointment',
		'show_kicker'   => true,
		'title'         => 'Let’s talk',
		'lead'          => 'Get in touch with us easily on the enquiry form or contact us directly.',
		'show_lead'     => true,
		'form_heading'  => 'Send us a message',
		'submit_label'  => 'Send your enquiry',
		'card_title'    => 'Contact us directly',
		'show_card'     => true,
		'email_label'   => 'Email',
		'phone_label'   => 'Phone',
		'address_label' => 'Where we are',
		'note'          => 'We reply within one business day. For anything urgent, please call.',
		'show_note'     => true,
	);
}

/**
 * The stored contact page copy, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_contact_content(): array {
	$defaults = kindredcare_contact_defaults();
	$stored   = get_option( KINDREDCARE_CONTACT_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$content = array_merge( $defaults, $stored );

	// A switch missing from an option saved before it existed reads as "shown".
	foreach ( array( 'show_kicker', 'show_lead', 'show_card', 'show_note' ) as $flag ) {
		$content[ $flag ] = ! isset( $stored[ $flag ] ) || (bool) $stored[ $flag ];
	}

	return $content;
}

/**
 * The page using the contact template.
 *
 * Falls back to the `contact-us` slug for installs set up before the template
 * existed, so an upgrade does not break every link to the page at once.
 *
 * @return WP_Post|null
 */
function kindredcare_contact_page(): ?WP_Post {
	/*
	 * Ordered oldest first, deliberately. Nothing stops two pages carrying this
	 * template — a duplicate made while reorganising, a draft copied from the
	 * original — and without an order the winner would be whichever the
	 * database happened to return, so links could move between them from one
	 * request to the next. Oldest wins: the page that has been the contact page
	 * all along keeps being it, and a new copy has to be deliberate.
	 */
	$found = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'meta_key'               => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one indexed lookup.
			'meta_value'             => KINDREDCARE_CONTACT_TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above.
		)
	);

	if ( $found ) {
		return $found[0];
	}

	$legacy = get_page_by_path( 'contact-us' );

	return $legacy instanceof WP_Post ? $legacy : null;
}

/**
 * Where an enquiry should land.
 *
 * The address the site publishes, not WordPress's admin address. Those are
 * different things: the admin address is where WordPress sends password resets
 * and update notices, while the contact email is the one printed in the footer
 * and on the contact page — the address a visitor believes they are writing to.
 * Changing the published address in the Customizer therefore moves the post as
 * well, which is what anyone changing it would expect.
 *
 * Falls back to the admin address, so a site with the contact email cleared
 * still delivers rather than silently dropping enquiries.
 */
function kindredcare_enquiry_recipient(): string {
	$published = (string) kindredcare_option( 'kindredcare_footer_email' );

	if ( is_email( $published ) ) {
		return $published;
	}

	return (string) get_option( 'admin_email' );
}

/**
 * Where the contact page lives, or an empty string when there is not one.
 */
function kindredcare_contact_url(): string {
	$page = kindredcare_contact_page();

	return $page ? (string) get_permalink( $page ) : '';
}

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

	/*
	 * The notification comes from the site's own published address rather than
	 * WordPress's default wordpress@<host>, which is not a mailbox anyone reads
	 * and is the first thing a spam filter marks down. It is the Customizer's
	 * value — the same address the footer and the contact page print — so there
	 * is one place to change it.
	 *
	 * From is the site; Reply-To is the person who wrote in. Hitting reply in a
	 * mail client therefore answers the enquirer, not the website.
	 */
	$from_email = (string) kindredcare_option( 'kindredcare_footer_email' );
	if ( ! is_email( $from_email ) ) {
		$from_email = (string) get_option( 'admin_email' );
	}

	$from_name = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );

	$mail = kindredcare_enquiry_email(
		array(
			'name'    => $name,
			'email'   => $email,
			'company' => $company,
			'phone'   => $phone,
			'message' => $message,
		),
		$redirect
	);

	// Parked for phpmailer_init, which is the only place AltBody can be set.
	kindredcare_email_alt( $mail['text'] );

	$sent = wp_mail(
		kindredcare_enquiry_recipient(),
		/* translators: %s: sender name. */
		sprintf( __( 'Website enquiry from %s', 'kindredcare' ), $name ),
		$mail['html'],
		array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
			sprintf( 'Reply-To: %s <%s>', $name, $email ),
		)
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
