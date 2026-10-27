<?php
/**
 * Renderers for the footer.
 *
 * Same arrangement as the header: one function per fragment, so footer.php and
 * the Customizer's selective-refresh preview run identical code.
 *
 * A structural constraint runs through the whole file. The accordion module
 * (js/modules/footer-accordion.js) takes `heading.nextElementSibling` as the
 * panel it collapses below 768px. Every heading must therefore be immediately
 * followed by its own list — no wrapper, no conditional element in between —
 * and a column with nothing in it must be omitted whole rather than rendered as
 * a heading with no panel.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Render the whole footer.
 *
 * Also the Customizer's selective-refresh callback. The footer is re-rendered
 * as one unit rather than fragment by fragment because a full preview reload
 * would replay the hero typewriter and every GSAP entrance timeline on each
 * keystroke — the same reason the top bar has its own partial.
 *
 * One consequence worth knowing: replacing the element detaches the accordion's
 * listeners, so in the preview at mobile width the columns stop collapsing
 * until the next real reload. That only affects the Customizer preview.
 */
function kindredcare_footer(): void {
	?>
	<!-- ================================================================== -->
	<!-- 14 — Footer                                                        -->
	<!-- ================================================================== -->
	<footer class="l-footer">
		<div class="l-container">
			<?php
			kindredcare_render_footer_subscribe();
			?>

			<!-- Four widgets, split by hairline rules. -->
			<div class="l-footer__widgets">
				<?php
				kindredcare_render_footer_brand_column();

				/*
				 * The three list columns collapse below md — see
				 * js/modules/footer-accordion.js. The data attribute is inert at
				 * every other width; the module builds and unbuilds around its
				 * own media query, so the desktop footer never carries accordion
				 * markup.
				 */
				kindredcare_render_footer_menu_column(
					'footer-quick',
					'kindredcare_footer_quick_heading',
					array(
						array( '#how-it-works', __( 'How it works', 'kindredcare' ) ),
						array( '#our-story', __( 'About us', 'kindredcare' ) ),
						array( '#faq', __( 'FAQ', 'kindredcare' ) ),
						array( '/terms-of-use/', __( 'Terms of use', 'kindredcare' ) ),
						array( '/privacy-policy/', __( 'Privacy policy', 'kindredcare' ) ),
					)
				);

				kindredcare_render_footer_menu_column(
					'footer-care',
					'kindredcare_footer_care_heading',
					array(
						array( '#services', __( 'Traditional care', 'kindredcare' ) ),
						array( '#services', __( 'Companionship & social', 'kindredcare' ) ),
						array( '#services', __( 'Practical help', 'kindredcare' ) ),
						array( '#services', __( 'Wellness & lifestyle', 'kindredcare' ) ),
						array( '#services', __( 'Specialised care', 'kindredcare' ) ),
						array( '#safety', __( 'Trust & safety', 'kindredcare' ) ),
					)
				);

				kindredcare_render_footer_contact_column();
				?>
			</div>
		</div>

		<?php kindredcare_render_footer_copyright(); ?>
	</footer>
	<?php
}

/**
 * Render the subscription band — oversized contact line left, newsletter right.
 */
function kindredcare_render_footer_subscribe(): void {
	$label       = (string) kindredcare_option( 'kindredcare_footer_shout_label' );
	$email       = (string) kindredcare_option( 'kindredcare_footer_shout_email' );
	$title       = (string) kindredcare_option( 'kindredcare_footer_newsletter_title' );
	$placeholder = (string) kindredcare_option( 'kindredcare_footer_newsletter_placeholder' );
	$action      = (string) kindredcare_option( 'kindredcare_footer_newsletter_action' );
	?>
	<div class="l-footer__subscribe">
		<?php if ( '' !== trim( $label ) ) : ?>
			<div class="l-footer__contact-lead">
				<h2 class="l-footer__shout">
					<?php if ( is_email( $email ) ) : ?>
						<a class="l-footer__shout-link" href="<?php echo esc_url( 'mailto:' . $email, array( 'mailto' ) ); ?>">
							<?php echo esc_html( $label ); ?>
							<svg class="l-footer__shout-icon" width="42" height="42" viewBox="0 0 16 16" fill="none" aria-hidden="true">
								<path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
					<?php else : ?>
						<?php echo esc_html( $label ); ?>
					<?php endif; ?>
				</h2>
			</div>
		<?php endif; ?>

		<?php
		if ( '' !== trim( $title ) ) :
			/*
			 * Two destinations. With "Newsletter form action" set the form posts
			 * straight to that provider, exactly as before — a site already wired
			 * to Mailchimp must not be quietly rerouted. With it blank the address
			 * comes here instead, to inc/newsletter.php, and is kept as a
			 * subscriber record. What it must never do again is post to the
			 * current page and drop the address on the floor.
			 */
			$external = '' !== trim( $action );
			?>
			<div class="l-footer__newsletter" id="newsletter">
				<h3 class="l-footer__newsletter-title"><?php echo esc_html( $title ); ?></h3>

				<?php
				if ( ! $external ) {
					kindredcare_subscribe_notice();
				}
				?>

				<form
					class="l-footer__form"
					method="post"
					action="<?php echo esc_url( $external ? $action : admin_url( 'admin-post.php' ) ); ?>"
					data-newsletter
				>
					<?php if ( ! $external ) : ?>
						<input type="hidden" name="action" value="<?php echo esc_attr( KINDREDCARE_SUBSCRIBE_ACTION ); ?>" />
						<?php wp_nonce_field( KINDREDCARE_SUBSCRIBE_ACTION, 'kc_subscribe_nonce' ); ?>

						<?php /* Honeypot. Hidden from sight and from assistive tech, but a bot fills it. */ ?>
						<span class="c-form__trap" aria-hidden="true">
							<label for="kc-hp"><?php esc_html_e( 'Leave this field empty', 'kindredcare' ); ?></label>
							<input id="kc-hp" type="text" name="kc_hp" tabindex="-1" autocomplete="off" />
						</span>
					<?php endif; ?>

					<label class="u-sr-only" for="newsletter-email"><?php esc_html_e( 'Email address', 'kindredcare' ); ?></label>
					<input
						class="l-footer__input"
						id="newsletter-email"
						name="email"
						type="email"
						autocomplete="email"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						required
					/>
					<button class="l-footer__submit" type="submit" aria-label="<?php esc_attr_e( 'Subscribe', 'kindredcare' ); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</button>
				</form>

				<?php
				/*
				 * Consent has to be asked for where the address is typed, not
				 * buried in a policy. It is also the only place the reader is
				 * told what they are agreeing to receive.
				 */
				?>
				<p class="l-footer__consent">
					<?php esc_html_e( 'Occasional updates about KindredCare. No sharing your address, and you can ask us to remove it at any time.', 'kindredcare' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render the first footer column — the mark, then the service area.
 *
 * Uses the same Site Identity logo as the header, so the two cannot disagree.
 */
function kindredcare_render_footer_brand_column(): void {
	$heading = (string) kindredcare_option( 'kindredcare_footer_serve_heading' );
	$lines   = kindredcare_footer_serve_lines();
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$name    = get_bloginfo( 'name', 'display' );
	?>
	<div class="l-footer__widget">
		<a
			class="l-footer__brand"
			href="<?php echo esc_url( home_url( '/' ) ); ?>"
			aria-label="<?php
				/* translators: %s: site title. */
				echo esc_attr( sprintf( __( '%s — home', 'kindredcare' ), $name ) );
			?>"
		>
			<?php
			if ( $logo_id && wp_attachment_is_image( $logo_id ) ) {
				echo wp_get_attachment_image(
					$logo_id,
					'full',
					false,
					array(
						'class'   => 'l-footer__logo',
						'alt'     => $name,
						'loading' => 'lazy',
					)
				);
			} else {
				printf(
					'<img class="l-footer__logo" src="%1$s" width="162" height="36" alt="%2$s" loading="lazy" />',
					esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ),
					esc_attr( $name )
				);
			}
			?>
		</a>

		<?php if ( '' !== trim( $heading ) && $lines ) : ?>
			<h3 class="l-footer__heading"><?php echo esc_html( $heading ); ?></h3>
			<ul class="l-footer__plain">
				<?php foreach ( $lines as $line ) : ?>
					<li><?php echo esc_html( $line ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The service-area lines, one per row of the Customizer textarea.
 *
 * @return string[] Non-empty trimmed lines.
 */
function kindredcare_footer_serve_lines(): array {
	$raw = (string) kindredcare_option( 'kindredcare_footer_serve_lines' );

	return array_values(
		array_filter(
			array_map( 'trim', preg_split( '/\R/', $raw ) ?: array() ),
			static fn( string $line ): bool => '' !== $line
		)
	);
}

/**
 * Render one of the two menu-backed footer columns.
 *
 * The heading is printed here rather than inside wp_nav_menu() so it stays the
 * list's immediate previous sibling, which the accordion depends on.
 *
 * @param string                 $location    Registered nav menu location.
 * @param string                 $heading_key Customizer setting id for the column heading.
 * @param array<array{0:string,1:string}> $fallback Links used when no menu is
 *                                            assigned, as [ url, label ] pairs.
 *                                            A list, not a map: several of the
 *                                            care links share one target.
 */
function kindredcare_render_footer_menu_column( string $location, string $heading_key, array $fallback ): void {
	$heading = (string) kindredcare_option( $heading_key );

	// Nothing to collapse and nothing to show — omit the column entirely.
	if ( '' === trim( $heading ) ) {
		return;
	}
	?>
	<div class="l-footer__widget" data-footer-acc>
		<h3 class="l-footer__heading"><?php echo esc_html( $heading ); ?></h3>
		<?php
		if ( has_nav_menu( $location ) ) {
			wp_nav_menu(
				array(
					'theme_location' => $location,
					'container'      => false,
					'items_wrap'     => '<ul class="l-footer__list">%3$s</ul>',
					'depth'          => 1,
					'walker'         => new KindredCare_Footer_Walker(),
					'fallback_cb'    => false,
				)
			);
		} else {
			?>
			<ul class="l-footer__list">
				<?php foreach ( $fallback as list( $url, $label ) ) : ?>
					<li><a class="l-footer__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Render the fourth column — contact details, then the social row.
 *
 * "Connect with us" sits after the contact list on purpose: the accordion
 * collapses only the heading's next sibling, so everything past the list stays
 * visible on mobile. That is the behaviour the static build shipped.
 */
function kindredcare_render_footer_contact_column(): void {
	$heading  = (string) kindredcare_option( 'kindredcare_footer_contact_heading' );
	$connect  = (string) kindredcare_option( 'kindredcare_footer_connect_heading' );
	$email    = (string) kindredcare_option( 'kindredcare_footer_email' );
	$phone    = (string) kindredcare_option( 'kindredcare_footer_phone' );
	$location = (string) kindredcare_option( 'kindredcare_footer_location' );
	$tel      = kindredcare_tel_href( $phone );
	$social   = kindredcare_active_social_networks();

	$has_contact = is_email( $email ) || '' !== trim( $phone ) || '' !== trim( $location );

	if ( ! $has_contact && ! $social ) {
		return;
	}
	?>
	<div class="l-footer__widget l-footer__widget--last" data-footer-acc>
		<?php if ( '' !== trim( $heading ) && $has_contact ) : ?>
			<h3 class="l-footer__heading"><?php echo esc_html( $heading ); ?></h3>
			<ul class="l-footer__contact">
				<?php if ( is_email( $email ) ) : ?>
					<li>
						<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M3 6.5h18v11H3zM3 7l9 6 9-6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
						</svg>
						<a class="l-footer__link" href="<?php echo esc_url( 'mailto:' . $email, array( 'mailto' ) ); ?>"><?php echo esc_html( $email ); ?></a>
					</li>
				<?php endif; ?>

				<?php if ( '' !== trim( $phone ) ) : ?>
					<li>
						<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M5 4h4l2 5-2.5 1.5a12 12 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A15 15 0 0 1 4 5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
						</svg>
						<?php if ( '' !== $tel ) : ?>
							<a class="l-footer__link" href="<?php echo esc_url( $tel, array( 'tel' ) ); ?>"><?php echo esc_html( $phone ); ?></a>
						<?php else : ?>
							<span><?php echo esc_html( $phone ); ?></span>
						<?php endif; ?>
					</li>
				<?php endif; ?>

				<?php if ( '' !== trim( $location ) ) : ?>
					<li>
						<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
							<circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.7" />
						</svg>
						<span><?php echo esc_html( $location ); ?></span>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $social && '' !== trim( $connect ) ) : ?>
			<div class="l-footer__connect">
				<h3 class="l-footer__heading"><?php echo esc_html( $connect ); ?></h3>
				<ul class="l-footer__social">
					<?php foreach ( $social as $network ) : ?>
						<li>
							<a
								class="l-footer__social-link"
								href="<?php echo esc_url( $network['url'] ); ?>"
								aria-label="<?php
									/* translators: 1: site title, 2: social network name. */
									echo esc_attr( sprintf( __( '%1$s on %2$s', 'kindredcare' ), get_bloginfo( 'name', 'display' ), $network['label'] ) );
								?>"
							>
								<?php kindredcare_social_icon( $network ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render the copyright strip.
 *
 * {year} is substituted with the current year in the site's timezone, so the
 * line does not quietly go stale every January.
 */
function kindredcare_render_footer_copyright(): void {
	$text = (string) kindredcare_option( 'kindredcare_footer_copyright' );

	if ( '' === trim( $text ) ) {
		return;
	}

	$text = str_replace( '{year}', wp_date( 'Y' ), $text );
	?>
	<div class="l-footer__copyright">
		<div class="l-container">
			<p><?php echo wp_kses( $text, kindredcare_note_allowed_html() ); ?></p>
		</div>
	</div>
	<?php
}

/* -- Off-canvas menu ------------------------------------------------------- */

/**
 * The leading icon for each off-canvas destination.
 *
 * One glyph per row, so the panel can be read down its left edge rather than
 * word by word — which is the whole reason a drawer gets icons and a text link
 * in a paragraph does not.
 *
 * @return array<string, string> Icon name to SVG path markup.
 */
function kindredcare_offcanvas_icons(): array {
	return array(
		'steps'    => '<rect x="3" y="14" width="5" height="6" rx="1.4" /><rect x="9.5" y="9" width="5" height="11" rx="1.4" /><rect x="16" y="4" width="5" height="16" rx="1.4" />',
		'grid'     => '<rect x="3.5" y="3.5" width="7" height="7" rx="2" /><rect x="13.5" y="3.5" width="7" height="7" rx="2" /><rect x="3.5" y="13.5" width="7" height="7" rx="2" /><rect x="13.5" y="13.5" width="7" height="7" rx="2" />',
		'shield'   => '<path d="M12 3 5 6v5c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6z" /><path d="m9 12 2 2 4-4" />',
		'caregiver' => '<circle cx="9" cy="8" r="3.4" /><path d="M3 20a6 6 0 0 1 12 0" /><path d="M18 8h4M20 6v4" />',
		'article'  => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6" /><path d="M8 13h8M8 17h5" />',
		'heart'    => '<path d="M12 20s-7-4.4-7-9.2A3.8 3.8 0 0 1 12 8a3.8 3.8 0 0 1 7 2.8C19 15.6 12 20 12 20z" />',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3.5 7 8.5 6 8.5-6" />',
	);
}

/**
 * Print one row of the off-canvas menu.
 *
 * The label sits in its own span beside the icon rather than as a bare text
 * node: the row is a flex container that pushes the trailing arrow to the far
 * edge, and an unwrapped string would be a third flex child fighting for that
 * space.
 *
 * @param string $url    Destination.
 * @param string $label  Visible text.
 * @param string $icon   Key into kindredcare_offcanvas_icons().
 * @param bool   $active Whether this row is the page or section being viewed.
 */
function kindredcare_offcanvas_link( string $url, string $label, string $icon, bool $active = false ): void {
	$icons = kindredcare_offcanvas_icons();
	$path  = $icons[ $icon ] ?? '';
	?>
	<a
		class="c-offcanvas__link<?php echo $active ? ' is-active' : ''; ?>"
		href="<?php echo esc_url( $url ); ?>"
		<?php echo $active ? ' aria-current="page"' : ''; ?>
	>
		<span class="c-offcanvas__link-main">
			<span class="c-offcanvas__link-lead" aria-hidden="true">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
					<?php echo wp_kses( $path, kindredcare_offcanvas_icon_allowed_html() ); ?>
				</svg>
			</span>
			<span class="c-offcanvas__link-text"><?php echo esc_html( $label ); ?></span>
		</span>
		<svg class="c-offcanvas__link-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
	</a>
	<?php
}

/**
 * The shapes an icon may be drawn from.
 *
 * The paths are the theme's own, but they still go through wp_kses so this
 * function stays the only thing deciding what markup an icon can contain.
 *
 * @return array<string, array<string, bool>>
 */
function kindredcare_offcanvas_icon_allowed_html(): array {
	$geometry = array(
		'd'      => true,
		'x'      => true,
		'y'      => true,
		'cx'     => true,
		'cy'     => true,
		'r'      => true,
		'rx'     => true,
		'ry'     => true,
		'width'  => true,
		'height' => true,
	);

	return array(
		'path'   => $geometry,
		'rect'   => $geometry,
		'circle' => $geometry,
	);
}

/**
 * Whether an off-canvas row points at what is currently on screen.
 *
 * Only answers for whole pages. The rows pointing at front-page sections are
 * decided by scroll position, which is a client-side question — assets/js/
 * modules/nav-spy.js marks those.
 *
 * @param string $target 'blog' or 'contact'.
 * @return bool
 */
function kindredcare_offcanvas_is_current( string $target ): bool {
	if ( 'blog' === $target ) {
		return is_home() || is_singular( 'post' ) || is_category() || is_tag();
	}

	if ( 'contact' === $target ) {
		// By template, not by slug, so renaming the page does not lose the mark.
		$contact = kindredcare_contact_page();

		return $contact instanceof WP_Post && is_page( $contact->ID );
	}

	return false;
}
