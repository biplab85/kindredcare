<?php
/**
 * HTML for the notification emails.
 *
 * Written the way email has to be written rather than the way the site is:
 * tables for layout, inline styles on every element, no external stylesheet and
 * no web font. Outlook renders with Word's engine, Gmail strips <style> blocks
 * from forwarded mail, and neither respects flexbox — so the rules that hold
 * everywhere else are the wrong ones here.
 *
 * Every message carries a plain-text alternative as well. It is what a text-only
 * client shows, what a screen reader falls back to, and its absence is one of
 * the cheapest ways to look like spam.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * The palette, as hex.
 *
 * The site's own colours are oklch, which no mail client understands. These are
 * the same intent, written in the only notation that renders everywhere.
 *
 * @return array<string, string>
 */
function kindredcare_email_palette(): array {
	return array(
		'ink'    => '#101a28',
		'soft'   => '#5b6779',
		'line'   => '#e3e8ef',
		'canvas' => '#f4f6fa',
		'sand'   => '#faf7f2',
		'trust'  => '#2f80d8',
		'white'  => '#ffffff',
	);
}

/**
 * The plain-text alternative for the message currently being sent.
 *
 * wp_mail() has no argument for it, so it is parked here and attached in
 * phpmailer_init below — cleared immediately after, so it can never leak onto
 * the next message a request happens to send.
 *
 * @param string|null $set Text to remember, or null to read and clear.
 * @return string
 */
function kindredcare_email_alt( ?string $set = null ): string {
	static $text = '';

	if ( null !== $set ) {
		$text = $set;
		return $text;
	}

	$out  = $text;
	$text = '';

	return $out;
}

add_action( 'phpmailer_init', 'kindredcare_email_attach_alt' );
/**
 * Attach the plain-text part.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mail Mailer, by reference.
 */
function kindredcare_email_attach_alt( $mail ): void {
	$alt = kindredcare_email_alt();

	if ( '' !== $alt && $mail->ContentType === 'text/html' ) {
		$mail->AltBody = $alt;
	}
}

/**
 * Wrap content in the message shell.
 *
 * @param string $title     Line in the coloured bar.
 * @param string $preheader The grey line clients show beside the subject.
 * @param string $inner     Table rows for the body.
 * @return string Complete document.
 */
function kindredcare_email_shell( string $title, string $preheader, string $inner ): string {
	$c    = kindredcare_email_palette();
	$name = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );

	return '<!doctype html>
<html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="x-apple-disable-message-reformatting" />
<title>' . esc_html( $title ) . '</title>
<style>
/*
 * Everything else here is inline, because most clients strip a stylesheet.
 * These two rules cannot be: a media query has nowhere to live inline, and
 * without them the 600px card sets the outer cell\'s minimum width and a phone
 * scrolls sideways. Clients that drop this block are the desktop ones, where
 * the fixed width is right anyway.
 */
@media only screen and (max-width:620px) {
	.kc-card { width:100% !important; }
	/* Sides only — each cell keeps the vertical rhythm set inline. */
	.kc-gut { padding-left:20px !important; padding-right:20px !important; }
}
</style>
</head>
<body style="margin:0;padding:0;background-color:' . $c['canvas'] . ';">

<!-- Shown in the inbox list beside the subject, then hidden. -->
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . esc_html( $preheader ) . '</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:' . $c['canvas'] . ';">
<tr>
<td align="center" style="padding:32px 16px;">

	<table role="presentation" class="kc-card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background-color:' . $c['white'] . ';border:1px solid ' . $c['line'] . ';border-radius:14px;overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">

		<tr>
			<td class="kc-gut" style="background-color:' . $c['ink'] . ';padding:22px 28px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td style="font-size:15px;font-weight:700;color:' . $c['white'] . ';letter-spacing:-0.01em;">' . esc_html( $name ) . '</td>
					<td align="right" style="font-size:11px;font-weight:600;color:#8a94a6;text-transform:uppercase;letter-spacing:0.12em;">' . esc_html( $title ) . '</td>
				</tr>
				</table>
			</td>
		</tr>

		' . $inner . '

		<tr>
			<td class="kc-gut" style="background-color:' . $c['sand'] . ';border-top:1px solid ' . $c['line'] . ';padding:18px 28px;font-size:12px;line-height:1.6;color:' . $c['soft'] . ';">
				Sent from <a href="' . esc_url( home_url( '/' ) ) . '" style="color:' . $c['trust'] . ';text-decoration:none;">' . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ?? '' ) . '</a>
				on ' . esc_html( wp_date( 'j F Y \a\t H:i' ) ) . '.
			</td>
		</tr>

	</table>

</td>
</tr>
</table>
</body>
</html>';
}

/**
 * A label-and-value block.
 *
 * Two stacked rows rather than two columns: a narrow label column wraps badly
 * on a phone, and an email cannot be told to reflow.
 *
 * @param array<string, string> $pairs Label to value.
 * @return string Table rows.
 */
function kindredcare_email_details( array $pairs ): string {
	$c   = kindredcare_email_palette();
	$out = '';

	foreach ( $pairs as $label => $value ) {
		if ( '' === trim( (string) $value ) ) {
			continue;
		}

		// Addresses and numbers become links; the client would linkify them
		// anyway, usually less tidily.
		$display = esc_html( $value );
		if ( is_email( $value ) ) {
			$display = '<a href="mailto:' . esc_attr( $value ) . '" style="color:' . $c['trust'] . ';text-decoration:none;">' . esc_html( $value ) . '</a>';
		} elseif ( preg_match( '/^[\d\s()+.-]{7,}$/', (string) $value ) ) {
			$display = '<a href="' . esc_attr( kindredcare_tel_href( (string) $value ) ) . '" style="color:' . $c['trust'] . ';text-decoration:none;">' . esc_html( $value ) . '</a>';
		} elseif ( preg_match( '#^https?://#', (string) $value ) ) {
			/*
			 * A full URL is both unreadable and too wide for a phone. Shown as
			 * host + path, linked to the whole thing — the address bar is where
			 * the rest of it belongs.
			 */
			$parts = wp_parse_url( (string) $value );
			$short = ( $parts['host'] ?? '' ) . ( $parts['path'] ?? '' );
			$short = rtrim( $short, '/' ) ?: (string) $value;

			$display = '<a href="' . esc_url( $value ) . '" style="color:' . $c['trust'] . ';text-decoration:none;">' . esc_html( $short ) . '</a>';
		}

		$out .= '<tr>
			<td style="padding:0 0 14px 0;">
				<div style="font-size:11px;font-weight:600;color:' . $c['soft'] . ';text-transform:uppercase;letter-spacing:0.1em;padding-bottom:3px;">' . esc_html( $label ) . '</div>
				<div style="font-size:15px;color:' . $c['ink'] . ';font-weight:500;word-break:break-word;overflow-wrap:anywhere;">' . $display . '</div>
			</td>
		</tr>';
	}

	return $out;
}

/**
 * The message someone typed, set apart from the fields above it.
 *
 * @param string $message Raw text.
 * @return string Table row.
 */
function kindredcare_email_quote( string $message ): string {
	$c = kindredcare_email_palette();

	return '<tr>
		<td style="padding:4px 0 0 0;">
			<div style="font-size:11px;font-weight:600;color:' . $c['soft'] . ';text-transform:uppercase;letter-spacing:0.1em;padding-bottom:8px;">Message</div>
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td style="background-color:' . $c['sand'] . ';border-left:3px solid ' . $c['trust'] . ';border-radius:0 10px 10px 0;padding:16px 18px;font-size:15px;line-height:1.65;color:' . $c['ink'] . ';">'
				. nl2br( esc_html( $message ) ) .
				'</td>
			</tr>
			</table>
		</td>
	</tr>';
}

/**
 * A button. Table-wrapped, because a styled anchor is not clickable enough in
 * Outlook and collapses to plain text.
 *
 * @param string $url   Destination.
 * @param string $label Visible text.
 * @return string Table rows.
 */
function kindredcare_email_button( string $url, string $label ): string {
	$c = kindredcare_email_palette();

	return '<tr>
		<td style="padding:24px 0 0 0;">
			<table role="presentation" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td style="background-color:' . $c['trust'] . ';border-radius:100px;">
					<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:13px 26px;font-size:14px;font-weight:600;color:' . $c['white'] . ';text-decoration:none;">' . esc_html( $label ) . '</a>
				</td>
			</tr>
			</table>
		</td>
	</tr>';
}

/**
 * Wrap body rows in the padded cell they sit in.
 *
 * @param string $rows Table rows.
 * @return string Table row.
 */
function kindredcare_email_body( string $rows ): string {
	return '<tr>
		<td class="kc-gut" style="padding:28px;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
			. $rows .
			'</table>
		</td>
	</tr>';
}

/**
 * The enquiry notification, as HTML and as text.
 *
 * @param array<string, string> $fields Name, email, company, phone, message.
 * @param string                $source Page the form was on.
 * @return array{html: string, text: string}
 */
function kindredcare_enquiry_email( array $fields, string $source = '' ): array {
	$c      = kindredcare_email_palette();
	$name   = $fields['name'] ?? '';
	$source = kindredcare_email_source( $source );

	$intro = '<tr><td style="padding:0 0 22px 0;font-size:15px;line-height:1.6;color:' . $c['ink'] . ';">'
		. sprintf(
			/* translators: %s: sender name, already escaped. */
			esc_html__( '%s got in touch through the website.', 'kindredcare' ),
			'<strong>' . esc_html( $name ) . '</strong>'
		)
		. '</td></tr>';

	$details = kindredcare_email_details(
		array(
			__( 'Name', 'kindredcare' )    => $fields['name'] ?? '',
			__( 'Email', 'kindredcare' )   => $fields['email'] ?? '',
			__( 'Company', 'kindredcare' ) => $fields['company'] ?? '',
			__( 'Phone', 'kindredcare' )   => $fields['phone'] ?? '',
		)
	);

	$quote = kindredcare_email_quote( (string) ( $fields['message'] ?? '' ) );

	$button = '';
	if ( is_email( $fields['email'] ?? '' ) ) {
		$button = kindredcare_email_button(
			'mailto:' . $fields['email'] . '?subject=' . rawurlencode( sprintf( __( 'Re: your enquiry to %s', 'kindredcare' ), wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) ) ),
			sprintf(
				/* translators: %s: sender's first name. */
				__( 'Reply to %s', 'kindredcare' ),
				trim( explode( ' ', trim( $name ) )[0] ) ?: __( 'them', 'kindredcare' )
			)
		);
	}

	$html = kindredcare_email_shell(
		__( 'New enquiry', 'kindredcare' ),
		sprintf(
			/* translators: 1: name, 2: first line of the message. */
			__( '%1$s: %2$s', 'kindredcare' ),
			$name,
			wp_trim_words( (string) ( $fields['message'] ?? '' ), 14 )
		),
		kindredcare_email_body( $intro . $details . $quote . $button )
	);

	// The same thing, for a client that will not render the above.
	$lines = array();
	foreach ( array( 'Name' => 'name', 'Email' => 'email', 'Company' => 'company', 'Phone' => 'phone' ) as $label => $key ) {
		if ( ! empty( $fields[ $key ] ) ) {
			$lines[] = $label . ': ' . $fields[ $key ];
		}
	}
	$lines[] = '';
	$lines[] = (string) ( $fields['message'] ?? '' );
	if ( $source ) {
		$lines[] = '';
		$lines[] = 'Sent from ' . $source;
	}

	return array(
		'html' => $html,
		'text' => implode( "\n", $lines ),
	);
}

/**
 * Turn a referer into something worth putting in an email.
 *
 * Both handlers hand us wp_get_referer(), which returns a bare path when the
 * request came from this site — "/blog/" rather than "https://…/blog/". In an
 * email that is neither clickable nor meaningful once it has left the browser
 * that produced it, so it is made absolute here. Anything already absolute, or
 * empty, passes through untouched.
 *
 * @param string $source Referer as the handler received it.
 * @return string Absolute URL, or an empty string.
 */
function kindredcare_email_source( string $source ): string {
	$source = trim( $source );

	if ( '' === $source ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $source ) ) {
		return $source;
	}

	/*
	 * Joined to the scheme and host alone, not to home_url(). A referer path is
	 * relative to the server root, so on a site installed in a subdirectory
	 * home_url() would contribute that subdirectory a second time.
	 */
	$parts = wp_parse_url( home_url( '/' ) );

	if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return $source;
	}

	$root = $parts['scheme'] . '://' . $parts['host'];

	if ( ! empty( $parts['port'] ) ) {
		$root .= ':' . $parts['port'];
	}

	return $root . '/' . ltrim( $source, '/' );
}

/**
 * The new-subscriber notification, as HTML and as text.
 *
 * @param string $email  Subscriber address.
 * @param string $source Page they subscribed from.
 * @return array{html: string, text: string}
 */
function kindredcare_subscriber_email( string $email, string $source = '' ): array {
	$source = kindredcare_email_source( $source );
	$c = kindredcare_email_palette();

	$intro = '<tr><td style="padding:0 0 22px 0;font-size:15px;line-height:1.6;color:' . $c['ink'] . ';">'
		. esc_html__( 'Someone has joined the newsletter list.', 'kindredcare' )
		. '</td></tr>';

	$details = kindredcare_email_details(
		array(
			__( 'Email', 'kindredcare' ) => $email,
			__( 'From', 'kindredcare' )  => $source,
		)
	);

	$button = kindredcare_email_button(
		admin_url( 'edit.php?post_type=kc_subscriber' ),
		__( 'See every subscriber', 'kindredcare' )
	);

	return array(
		'html' => kindredcare_email_shell(
			__( 'New subscriber', 'kindredcare' ),
			sprintf(
				/* translators: %s: subscriber email. */
				__( '%s joined the newsletter list.', 'kindredcare' ),
				$email
			),
			kindredcare_email_body( $intro . $details . $button )
		),
		'text' => sprintf( "%s subscribed to the newsletter.\n\nFrom: %s", $email, $source ),
	);
}
