<?php
/**
 * Contact page.
 *
 * WordPress picks this up by slug, so the page has no template to choose in the
 * editor and cannot be pointed at the wrong one.
 *
 * The copy and the field set come from kindredcare.ca/contact-us/ — kicker,
 * heading, standfirst, and the five fields (name, email, company, phone,
 * message). The submit label is the only change: the live button reads "Send
 * your qurries".
 *
 * The contact details beside the form are the Customizer's, the same ones the
 * footer prints, so there is one place to change a phone number.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$kc_email   = (string) kindredcare_option( 'kindredcare_footer_email' );
	$kc_phone   = (string) kindredcare_option( 'kindredcare_footer_phone' );
	$kc_address = (string) kindredcare_option( 'kindredcare_footer_location' );
	?>

	<article class="s-article s-contact">

		<header class="s-article__head s-article__head--no-media">
			<div class="s-article__head-content">
				<p class="s-article__kicker">
					<span class="s-article__kicker-rule" aria-hidden="true"></span>
					<?php esc_html_e( 'Request an appointment', 'kindredcare' ); ?>
				</p>

				<h1 class="s-article__title s-article__title--page"><?php esc_html_e( 'Let’s talk', 'kindredcare' ); ?></h1>

				<p class="s-article__deck">
					<?php esc_html_e( 'Get in touch with us easily on the enquiry form or contact us directly.', 'kindredcare' ); ?>
				</p>
			</div>
		</header>

		<div class="l-container">
			<div class="s-contact__layout">

				<!-- ========================================================= -->
				<!-- Enquiry form                                              -->
				<!-- ========================================================= -->
				<div class="s-contact__form-col" id="enquiry">
					<h2 class="s-contact__heading"><?php esc_html_e( 'Send us a message', 'kindredcare' ); ?></h2>

					<?php kindredcare_enquiry_notice(); ?>

					<form
						class="c-form"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						method="post"
					>
						<input type="hidden" name="action" value="<?php echo esc_attr( KINDREDCARE_ENQUIRY_ACTION ); ?>" />
						<?php wp_nonce_field( KINDREDCARE_ENQUIRY_ACTION, 'kc_enquiry_nonce' ); ?>

						<?php /* Honeypot. Hidden from sight and from assistive tech, but a bot fills it. */ ?>
						<p class="c-form__trap" aria-hidden="true">
							<label for="kc-website"><?php esc_html_e( 'Leave this field empty', 'kindredcare' ); ?></label>
							<input id="kc-website" type="text" name="kc_website" tabindex="-1" autocomplete="off" />
						</p>

						<div class="c-form__grid">
							<p class="c-form__field">
								<label class="c-form__label" for="kc-name">
									<?php esc_html_e( 'Full name', 'kindredcare' ); ?>
									<span class="c-form__req" aria-hidden="true">*</span>
								</label>
								<input class="c-form__input" id="kc-name" type="text" name="kc_name" autocomplete="name" required />
							</p>

							<p class="c-form__field">
								<label class="c-form__label" for="kc-email">
									<?php esc_html_e( 'Email', 'kindredcare' ); ?>
									<span class="c-form__req" aria-hidden="true">*</span>
								</label>
								<input class="c-form__input" id="kc-email" type="email" name="kc_email" autocomplete="email" required />
							</p>

							<p class="c-form__field">
								<label class="c-form__label" for="kc-company"><?php esc_html_e( 'Company name', 'kindredcare' ); ?></label>
								<input class="c-form__input" id="kc-company" type="text" name="kc_company" autocomplete="organization" />
							</p>

							<p class="c-form__field">
								<label class="c-form__label" for="kc-phone"><?php esc_html_e( 'Phone number', 'kindredcare' ); ?></label>
								<input class="c-form__input" id="kc-phone" type="tel" name="kc_phone" autocomplete="tel" />
							</p>

							<p class="c-form__field c-form__field--wide">
								<label class="c-form__label" for="kc-message">
									<?php esc_html_e( 'Message', 'kindredcare' ); ?>
									<span class="c-form__req" aria-hidden="true">*</span>
								</label>
								<textarea class="c-form__input c-form__input--area" id="kc-message" name="kc_message" rows="6" required></textarea>
							</p>
						</div>

						<button class="c-btn c-btn--primary c-btn--lg c-form__submit" type="submit">
							<?php esc_html_e( 'Send your enquiry', 'kindredcare' ); ?>
						</button>
					</form>
				</div>

				<!-- ========================================================= -->
				<!-- Details                                                   -->
				<!-- ========================================================= -->
				<aside class="s-contact__aside">
					<div class="s-contact__card">
						<h2 class="s-contact__card-title"><?php esc_html_e( 'Contact us directly', 'kindredcare' ); ?></h2>

						<ul class="s-contact__list">
							<?php if ( $kc_email ) : ?>
								<li class="s-contact__item">
									<span class="s-contact__label"><?php esc_html_e( 'Email', 'kindredcare' ); ?></span>
									<a class="s-contact__value" href="mailto:<?php echo esc_attr( $kc_email ); ?>">
										<?php echo esc_html( $kc_email ); ?>
									</a>
								</li>
							<?php endif; ?>

							<?php if ( $kc_phone ) : ?>
								<li class="s-contact__item">
									<span class="s-contact__label"><?php esc_html_e( 'Phone', 'kindredcare' ); ?></span>
									<a class="s-contact__value" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $kc_phone ) ); ?>">
										<?php echo esc_html( $kc_phone ); ?>
									</a>
								</li>
							<?php endif; ?>

							<?php if ( $kc_address ) : ?>
								<li class="s-contact__item">
									<span class="s-contact__label"><?php esc_html_e( 'Where we are', 'kindredcare' ); ?></span>
									<span class="s-contact__value"><?php echo esc_html( $kc_address ); ?></span>
								</li>
							<?php endif; ?>
						</ul>

						<p class="s-contact__note">
							<?php esc_html_e( 'We reply within one business day. For anything urgent, please call.', 'kindredcare' ); ?>
						</p>
					</div>
				</aside>
			</div>
		</div>
	</article>

	<?php
endwhile;

get_footer();
