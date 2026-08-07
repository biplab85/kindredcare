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
 * Render the contents of the top bar's inner container.
 *
 * Selective-refresh partial callback; also called directly by kindredcare_topbar().
 */
function kindredcare_render_topbar_inner(): void {
	$note      = (string) kindredcare_option( 'kindredcare_topbar_note' );
	$phone     = (string) kindredcare_option( 'kindredcare_topbar_phone' );
	$cta_label = (string) kindredcare_option( 'kindredcare_topbar_cta_label' );
	$cta_url = (string) kindredcare_option( 'kindredcare_topbar_cta_url' );

	/*
	 * The shipped value is "#for-caregivers", a section that only exists on the
	 * front page. The bar is on every page, so the anchor is resolved the same
	 * way the menus are.
	 */
	if ( str_starts_with( $cta_url, '#' ) ) {
		$cta_url = kindredcare_anchor_url( $cta_url );
	}
	$tel       = kindredcare_tel_href( $phone );
	?>
	<?php if ( '' !== trim( wp_strip_all_tags( $note ) ) ) : ?>
		<p class="l-topbar__note"><?php echo wp_kses( $note, kindredcare_note_allowed_html() ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== trim( $phone ) || '' !== trim( $cta_label ) ) : ?>
		<div class="l-topbar__actions">
			<?php if ( '' !== trim( $phone ) ) : ?>
				<?php if ( '' !== $tel ) : ?>
					<a class="l-topbar__phone" href="<?php echo esc_url( $tel, array( 'tel' ) ); ?>"><?php echo esc_html( $phone ); ?></a>
				<?php else : ?>
					<span class="l-topbar__phone"><?php echo esc_html( $phone ); ?></span>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( '' !== trim( $cta_label ) ) : ?>
				<a class="c-btn c-btn--primary c-btn--sm" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( $cta_label ); ?>
				</a>
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
	// Blog is current anywhere in the post hierarchy; Home everywhere else.
	$active = ( is_home() || is_singular( 'post' ) || is_archive() || is_search() ) ? 4 : 0;
	?>
	<nav class="l-header__nav" aria-label="<?php esc_attr_e( 'Site', 'kindredcare' ); ?>">
		<?php foreach ( $links as $index => list( $href, $label ) ) : ?>
			<a
				class="l-header__link<?php echo $active === $index ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $href ); ?>"
				<?php echo $active === $index ? 'aria-current="page"' : ''; ?>
			><?php echo esc_html( $label ); ?></a>
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
