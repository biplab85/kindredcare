<?php
/**
 * Renderers for the header chrome.
 *
 * Each piece lives in a function rather than inline in header.php because the
 * Customizer's selective refresh needs to call the same code to re-render a
 * fragment. header.php and the preview therefore cannot drift apart.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Build a tel: href from a human-formatted phone number.
 *
 * Editors type "+1 (437) 665-8486"; dialers want "+14376658486". Everything but
 * digits is dropped, and a single leading + is preserved.
 *
 * @param string $phone Display number.
 * @return string Value for an href, empty if there were no digits.
 */
function kindredcare_tel_href( string $phone ): string {
	$digits = preg_replace( '/[^0-9]/', '', $phone );

	if ( '' === $digits || null === $digits ) {
		return '';
	}

	$plus = str_starts_with( trim( $phone ), '+' ) ? '+' : '';

	return 'tel:' . $plus . $digits;
}

/**
 * Whether a button's link opens in a new tab.
 *
 * Each section stores its own `*_new_tab` flag, set by an "Open in a new tab"
 * switch on its admin screen. Until that screen is saved once the flag is
 * absent (null), and the button keeps the behaviour it shipped with: some
 * sections always stayed in the tab, others sent every full URL to a new one
 * and kept #anchors in place. $legacy names which.
 *
 * @param mixed  $stored The stored flag; null when never saved.
 * @param string $url    The button's link.
 * @param string $legacy 'same' (stay in the tab) or 'external' (new tab for
 *                       anything that is not a #anchor).
 */
function kindredcare_link_new_tab( $stored, string $url, string $legacy = 'same' ): bool {
	if ( null !== $stored ) {
		return (bool) $stored;
	}

	$url = trim( $url );

	return 'external' === $legacy && '' !== $url && ! str_starts_with( $url, '#' );
}

/**
 * The attributes that open a link in a new tab, or nothing.
 *
 * @param bool $new_tab Whether the link opens in a new tab.
 * @return string A fixed, already-safe attribute string with a leading space.
 */
function kindredcare_link_target( bool $new_tab ): string {
	return $new_tab ? ' target="_blank" rel="noopener"' : '';
}

/**
 * Print the screen-reader hint for a link that opens in a new tab.
 *
 * @param bool $new_tab Whether the link opens in a new tab.
 */
function kindredcare_link_new_tab_hint( bool $new_tab ): void {
	if ( $new_tab ) {
		echo '<span class="u-sr-only"> ' . esc_html__( '(opens in a new tab)', 'kindredcare' ) . '</span>';
	}
}

/**
 * Collect the Registration and Login buttons.
 *
 * Still edited under Customizer -> KindredCare -> Top bar, but rendered in the
 * main header beside the search box by kindredcare_render_header_account().
 *
 * @return array<int, array{label: string, url: string, target: string}>
 */
function kindredcare_account_buttons(): array {
	// Registration, then Login. A button needs both a label and a link to show.
	$buttons = array();
	foreach ( array( 'register', 'login' ) as $key ) {
		$label = trim( (string) kindredcare_option( "kindredcare_topbar_{$key}_label" ) );
		$url   = trim( (string) kindredcare_option( "kindredcare_topbar_{$key}_url" ) );

		if ( '' === $label || '' === $url ) {
			continue;
		}

		/*
		 * The bar is on every page, but an anchor like "#for-caregivers" only
		 * exists on the front page, so it is resolved the same way the menus are.
		 * An anchor stays in the tab; a full URL opens a new one.
		 */
		$is_anchor = str_starts_with( $url, '#' );

		$buttons[] = array(
			'label'  => $label,
			'url'    => $is_anchor ? kindredcare_anchor_url( $url ) : $url,
			'target' => $is_anchor ? '' : '_blank',
		);
	}

	return $buttons;
}

/**
 * Render the Registration and Login buttons in the main header.
 *
 * Registration uses the outline variant and Login the solid one, so the two
 * read as a pair on the light header.
 */
function kindredcare_render_header_account(): void {
	$buttons = kindredcare_account_buttons();

	if ( ! $buttons ) {
		return;
	}
	?>
	<div class="l-header__account">
		<?php foreach ( $buttons as $i => $button ) : ?>
			<a class="c-btn <?php echo 0 === $i && count( $buttons ) > 1 ? 'c-btn--outline' : 'c-btn--primary'; ?> c-btn--sm"<?php echo $button['target'] ? ' target="' . esc_attr( $button['target'] ) . '" rel="noopener"' : ''; ?> href="<?php echo esc_url( $button['url'] ); ?>">
				<?php echo esc_html( $button['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Render the contents of the top bar's inner container.
 *
 * Selective-refresh partial callback; also called directly by kindredcare_topbar().
 * The Registration and Login buttons now live in the main header instead.
 */
function kindredcare_render_topbar_inner(): void {
	$note  = (string) kindredcare_option( 'kindredcare_topbar_note' );
	$phone = (string) kindredcare_option( 'kindredcare_topbar_phone' );
	$tel   = kindredcare_tel_href( $phone );
	?>
	<?php if ( '' !== trim( wp_strip_all_tags( $note ) ) ) : ?>
		<p class="l-topbar__note"><?php echo wp_kses( $note, kindredcare_note_allowed_html() ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== trim( $phone ) ) : ?>
		<div class="l-topbar__actions">
			<?php if ( '' !== $tel ) : ?>
				<a class="l-topbar__phone" href="<?php echo esc_url( $tel, array( 'tel' ) ); ?>"><?php echo esc_html( $phone ); ?></a>
			<?php else : ?>
				<span class="l-topbar__phone"><?php echo esc_html( $phone ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php
}

/**
 * Render the top utility strip.
 *
 * Carries the caregiver side so the bottom app bar can own the family side;
 * neither audience is buried.
 */
function kindredcare_topbar(): void {
	if ( ! kindredcare_option( 'kindredcare_topbar_enabled' ) ) {
		return;
	}
	?>
	<div class="l-topbar">
		<div class="l-container l-topbar__inner">
			<?php kindredcare_render_topbar_inner(); ?>
		</div>
	</div>
	<?php
}

/**
 * Render the header brand link.
 *
 * Deliberately not the_custom_logo(): that helper hardcodes its own
 * .custom-logo-link wrapper, and the ported CSS keys off .l-header__brand and
 * .l-header__logo. The Site Identity control still drives it — only the markup
 * around the image is ours.
 */
function kindredcare_render_brand(): void {
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$name    = get_bloginfo( 'name', 'display' );
	?>
	<a
		class="l-header__brand"
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
					'class' => 'l-header__logo',
					'alt'   => $name,
				)
			);
		} else {
			/*
			 * No logo uploaded yet — fall back to the mark the static build
			 * shipped, at its natural 162x36, so a fresh activation looks
			 * finished rather than showing a text placeholder.
			 */
			printf(
				'<img class="l-header__logo" src="%1$s" width="162" height="36" alt="%2$s" />',
				esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ),
				esc_attr( $name )
			);
		}
		?>
	</a>
	<?php
}

/**
 * Render the desktop navigation bar.
 *
 * items_wrap drops the <ul> entirely: .l-header__nav is a flex row whose direct
 * children are the links (scss/layout/_header.scss:40), so a list between them
 * would break the layout. The walker supplies bare anchors to match.
 */
function kindredcare_render_header_nav(): void {
	wp_nav_menu(
		array(
			'theme_location'       => 'primary',
			'container'            => 'nav',
			'container_class'      => 'l-header__nav',
			'container_aria_label' => __( 'Site', 'kindredcare' ),
			'items_wrap'           => '%3$s',
			'depth'                => 1,
			'walker'               => new KindredCare_Header_Walker(),
			'fallback_cb'          => 'kindredcare_header_nav_fallback',
		)
	);
}

/**
 * Resolve an in-page anchor so it works from anywhere on the site.
 *
 * The marketing sections — how it works, services, safety — only exist on the
 * front page. A bare "#services" href is fine there and useless everywhere
 * else: on a policy page or an article the browser looks for that id in the
 * current document, finds nothing, and does nothing at all. Prefixing with the
 * home URL turns it into a real navigation.
 *
 * @param string $fragment Anchor including its leading hash.
 * @return string URL to use in an href.
 */
function kindredcare_anchor_url( string $fragment ): string {
	$fragment = '#' . ltrim( $fragment, '#' );

	return is_front_page() ? $fragment : home_url( '/' ) . $fragment;
}

/**
 * The permalink of a page, by slug.
 *
 * The off-canvas menu is still ported static markup and links by path. A
 * root-relative "/contact-us/" resolves against the server root, which on this
 * subdirectory install is not the site — so the page is looked up instead.
 *
 * @param string $slug     Page slug.
 * @param string $fallback URL to use when no such page exists.
 * @return string URL.
 */
function kindredcare_page_url( string $slug, string $fallback = '' ): string {
	$page = get_page_by_path( $slug );

	if ( $page instanceof WP_Post ) {
		return (string) get_permalink( $page );
	}

	return '' !== $fallback ? $fallback : home_url( '/' );
}

add_filter( 'wp_nav_menu_objects', 'kindredcare_resolve_menu_anchors' );
/**
 * Apply the same resolution to menu items pointing at a front-page section.
 *
 * The header and footer menus store these as custom links with a bare fragment,
 * which is what an editor naturally types. Rewriting them here means the menus
 * keep working from every template without anyone having to know the rule.
 *
 * @param array<int, WP_Post> $items Menu items.
 * @return array<int, WP_Post> Filtered items.
 */
function kindredcare_resolve_menu_anchors( array $items ): array {
	if ( is_front_page() ) {
		return $items;
	}

	foreach ( $items as $item ) {
		if ( isset( $item->url ) && str_starts_with( (string) $item->url, '#' ) ) {
			$item->url = kindredcare_anchor_url( (string) $item->url );
		}
	}

	return $items;
}

/**
 * The icon shown before a header menu link.
 *
 * Picked by matching the item's label (then its URL) against a few keywords,
 * so a menu edited in Appearance -> Menus keeps its icons without any extra
 * fields. An item that matches nothing gets no icon. Strokes use currentColor
 * so the icon follows the link's hover and active colour; shapes marked
 * .l-header__icon-fill also take a soft tint while the link is active.
 *
 * @param string $label Menu item label.
 * @param string $url   Menu item URL.
 * @return string Inline SVG markup, or an empty string.
 */
function kindredcare_header_nav_icon( string $label, string $url = '' ): string {
	$icons = array(
		'home'     => '<path class="l-header__icon-fill" d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5.5h-5V20H5a1 1 0 0 1-1-1z" stroke-linejoin="round"/>',
		'how'      => '<rect class="l-header__icon-fill" x="3.5" y="14" width="4.5" height="6" rx="1.2"/><rect class="l-header__icon-fill" x="9.75" y="9" width="4.5" height="11" rx="1.2"/><rect class="l-header__icon-fill" x="16" y="4" width="4.5" height="16" rx="1.2"/>',
		'service'  => '<rect class="l-header__icon-fill" x="3.5" y="3.5" width="7" height="7" rx="2"/><rect class="l-header__icon-fill" x="13.5" y="3.5" width="7" height="7" rx="2"/><rect class="l-header__icon-fill" x="3.5" y="13.5" width="7" height="7" rx="2"/><rect class="l-header__icon-fill" x="13.5" y="13.5" width="7" height="7" rx="2"/>',
		'gig'      => '<rect class="l-header__icon-fill" x="3.5" y="7.5" width="17" height="12" rx="2"/><path d="M9 7.5V6a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 6v1.5M3.5 12.5h17" stroke-linecap="round"/>',
		'safety'   => '<path class="l-header__icon-fill" d="M12 3.5 5 6.2v5.3c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6.2z" stroke-linejoin="round"/><path d="m9 12 2.2 2.2L15.4 10" stroke-linecap="round" stroke-linejoin="round"/>',
		'blog'     => '<path class="l-header__icon-fill" d="M6 3.5h8.5L19 8v12.5H6z" stroke-linejoin="round"/><path d="M14.5 3.5V8H19M9 12.5h7M9 16h5" stroke-linecap="round" stroke-linejoin="round"/>',
		'about'    => '<path class="l-header__icon-fill" d="M12 20S4.5 15.5 4.5 10.2A4.6 4.6 0 0 1 12 6.6a4.6 4.6 0 0 1 7.5 3.6C19.5 15.5 12 20 12 20Z" stroke-linejoin="round"/>',
		'faq'      => '<circle class="l-header__icon-fill" cx="12" cy="12" r="8.5"/><path d="M9.6 9.6a2.5 2.5 0 1 1 3.4 2.3c-.6.3-1 .8-1 1.5v.4M12 16.8h.01" stroke-linecap="round"/>',
		'contact'  => '<path class="l-header__icon-fill" d="M4 5.5h16v10.5H9.5L4 20z" stroke-linejoin="round"/>',
	);

	// Checked in this order, so "How it works" wins over anything else in it.
	$keywords = array(
		'home'    => array( 'home' ),
		'how'     => array( 'how it works', 'how-it-works', 'steps' ),
		'service' => array( 'service' ),
		'gig'     => array( 'gig' ),
		'safety'  => array( 'trust', 'safety' ),
		'blog'    => array( 'blog', 'news', 'article' ),
		'about'   => array( 'about', 'our story', 'our-story' ),
		'faq'     => array( 'faq', 'question' ),
		'contact' => array( 'contact' ),
	);

	foreach ( array( strtolower( wp_strip_all_tags( $label ) ), strtolower( $url ) ) as $haystack ) {
		foreach ( $keywords as $key => $needles ) {
			foreach ( $needles as $needle ) {
				if ( str_contains( $haystack, $needle ) ) {
					return '<svg class="l-header__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false">' . $icons[ $key ] . '</svg>';
				}
			}
		}
	}

	return '';
}

/**
 * Navigation shown when no menu is assigned to the "primary" location.
 *
 * Reproduces the static page's five links so the header is never empty on a
 * fresh activation. Assigning a menu in Appearance -> Menus replaces this.
 */
function kindredcare_header_nav_fallback(): void {
	/*
	 * The first four are in-page anchors and only resolve on the front page, so
	 * they are prefixed with it. Blog is a real destination: it reads the page
	 * assigned under Settings -> Reading rather than assuming /blog/, which on
	 * this subdirectory install would resolve against the server root.
	 */
	$home = is_front_page() ? '' : home_url( '/' );

	$links = array(
		array( $home . '#hero-title', __( 'Home', 'kindredcare' ) ),
		array( $home . '#how-it-works', __( 'How it works', 'kindredcare' ) ),
		array( $home . '#services', __( 'Services', 'kindredcare' ) ),
		array( $home . '#safety', __( 'Trust &amp; safety', 'kindredcare' ) ),
		array( kindredcare_blog_url(), __( 'Blog', 'kindredcare' ) ),
	);

	/*
	 * Contact, when there is a page to point at. Found by its template rather
	 * than a slug, so it survives being renamed — and left out entirely when no
	 * such page exists, because a menu item leading to the home page is worse
	 * than one that is not there.
	 */
	$contact = kindredcare_contact_page();
	if ( $contact ) {
		/*
		 * The page's own title, not a label written in here. Rename the page to
		 * "Contact" and this says Contact — which is what renaming it is for.
		 */
		$links[] = array( (string) get_permalink( $contact ), get_the_title( $contact ) );
	}

	// Blog is current anywhere in the post hierarchy; Home everywhere else.
	$active = ( is_home() || is_singular( 'post' ) || is_archive() || is_search() ) ? 4 : 0;

	if ( $contact && is_page( $contact->ID ) ) {
		$active = count( $links ) - 1;
	}
	?>
	<nav class="l-header__nav" aria-label="<?php esc_attr_e( 'Site', 'kindredcare' ); ?>">
		<?php foreach ( $links as $index => list( $href, $label ) ) : ?>
			<a
				class="l-header__link<?php echo $active === $index ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $href ); ?>"
				<?php echo $active === $index ? 'aria-current="page"' : ''; ?>
			><?php echo kindredcare_header_nav_icon( $label, $href ) . esc_html( $label ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed inline SVG. ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Render the header search form.
 *
 * The static markup pointed at #services and was never wired to any JS — no
 * module in kindredcare-home/src/js touches .c-search. It is a real WordPress
 * search now; the classes and the icon are unchanged.
 */
function kindredcare_render_header_search(): void {
	if ( ! kindredcare_option( 'kindredcare_header_search_enabled' ) ) {
		return;
	}

	$placeholder = (string) kindredcare_option( 'kindredcare_header_search_placeholder' );
	?>
	<form class="c-search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" role="search">
		<svg
			class="c-search__icon"
			width="18"
			height="18"
			viewBox="0 0 24 24"
			fill="none"
			aria-hidden="true"
		>
			<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
			<path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
		</svg>
		<label class="u-sr-only" for="site-search"><?php echo esc_html( $placeholder ); ?></label>
		<input
			class="c-search__input"
			id="site-search"
			type="search"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
		/>
	</form>
	<?php
}
