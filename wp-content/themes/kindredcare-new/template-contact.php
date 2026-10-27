<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * The contact page.
 *
 * A named template rather than page-contact-us.php, which WordPress matched by
 * slug: renaming the page to anything but "contact-us" silently dropped it back
 * to the generic page template, taking the form with it. Chosen under Page
 * Attributes instead, so the page can be called whatever suits and every link
 * to it — the header, the drawer, the footer — still finds it, because they all
 * look it up by template.
 *
 * The copy is editable at KindredCare -> Contact page. The address, phone and
 * email beside the form are the Customizer's, the same ones the footer prints,
 * so there is one place to change a phone number.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$kc = kindredcare_contact_content();

$kc_email   = (string) kindredcare_option( 'kindredcare_footer_email' );
$kc_phone   = (string) kindredcare_option( 'kindredcare_footer_phone' );
$kc_address = (string) kindredcare_option( 'kindredcare_footer_location' );

while ( have_posts() ) :
	the_post();
	?>

	<article class="s-article s-contact">

		<header class="s-article__head s-article__head--no-media">
			<div class="s-article__head-content">
				<?php if ( $kc['show_kicker'] && '' !== trim( (string) $kc['kicker'] ) ) : ?>
					<p class="s-article__kicker">
						<span class="s-article__kicker-rule" aria-hidden="true"></span>
						<?php echo esc_html( $kc['kicker'] ); ?>
					</p>
				<?php endif; ?>

				<?php
				/*
				 * The page's own title, not a stored one. Renaming the page in the
				 * editor is the obvious way to change this heading, and a second
				 * copy in an option would only ever drift from it.
				 */
				?>
				<h1 class="s-article__title s-article__title--page"><?php the_title(); ?></h1>

				<?php if ( $kc['show_lead'] && '' !== trim( (string) $kc['lead'] ) ) : ?>
					<p class="s-article__deck">
						<?php echo esc_html( $kc['lead'] ); ?>
					</p>
				<?php endif; ?>
			</div>
		</header>

		<div class="l-container">
			<div class="s-contact__layout">

				<!-- ========================================================= -->
				<!-- Enquiry form                                              -->
				<!-- ========================================================= -->
				<div class="s-contact__form-col" id="enquiry">
					<?php if ( '' !== trim( (string) $kc['form_heading'] ) ) : ?>
						<h2 class="s-contact__heading"><?php echo esc_html( $kc['form_heading'] ); ?></h2>
					<?php endif; ?>

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
							<?php echo esc_html( $kc['submit_label'] ); ?>
						</button>
					</form>
				</div>

				<!-- ========================================================= -->
				<!-- Details                                                   -->
				<!-- ========================================================= -->
				<?php if ( $kc['show_card'] ) : ?>
					<aside class="s-contact__aside">
						<div class="s-contact__card">
							<?php if ( '' !== trim( (string) $kc['card_title'] ) ) : ?>
								<h2 class="s-contact__card-title"><?php echo esc_html( $kc['card_title'] ); ?></h2>
							<?php endif; ?>

							<ul class="s-contact__list">
								<?php if ( $kc_email ) : ?>
									<li class="s-contact__item">
										<span class="s-contact__label"><?php echo esc_html( $kc['email_label'] ); ?></span>
										<a class="s-contact__value" href="<?php echo esc_attr( 'mailto:' . $kc_email ); ?>">
											<?php echo esc_html( $kc_email ); ?>
										</a>
									</li>
								<?php endif; ?>

								<?php if ( $kc_phone ) : ?>
									<li class="s-contact__item">
										<span class="s-contact__label"><?php echo esc_html( $kc['phone_label'] ); ?></span>
										<a class="s-contact__value" href="<?php echo esc_attr( kindredcare_tel_href( $kc_phone ) ); ?>">
											<?php echo esc_html( $kc_phone ); ?>
										</a>
									</li>
								<?php endif; ?>

								<?php if ( $kc_address ) : ?>
									<li class="s-contact__item">
										<span class="s-contact__label"><?php echo esc_html( $kc['address_label'] ); ?></span>
										<span class="s-contact__value"><?php echo esc_html( $kc_address ); ?></span>
									</li>
								<?php endif; ?>
							</ul>

							<?php if ( $kc['show_note'] && '' !== trim( (string) $kc['note'] ) ) : ?>
								<p class="s-contact__note">
									<?php echo esc_html( $kc['note'] ); ?>
								</p>
							<?php endif; ?>
						</div>
					</aside>
				<?php endif; ?>
			</div>
		</div>
	</article>

	<?php
endwhile;

get_footer();
