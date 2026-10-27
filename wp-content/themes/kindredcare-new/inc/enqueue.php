<?php
/**
 * Front-end assets.
 *
 * Load order here is not cosmetic — it reproduces the static page's <head>
 * exactly. See kindredcare-home/src/index.html lines 21-76 for the original and
 * the reasoning behind it.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Handles that must be printed as <script type="module">.
 *
 * main.js and hero-type.js use bare `import` statements; served as classic
 * scripts they throw a SyntaxError on the first line.
 */
const KINDREDCARE_MODULE_HANDLES = array( 'kindredcare-main', 'kindredcare-hero-type' );

/**
 * Cache-busting version for a theme-relative asset.
 *
 * The theme version plus the file's mtime, always — not only under WP_DEBUG.
 * The theme's CSS and JS are edited in place without bumping the theme
 * version, so a version-only string let browsers keep serving a stale sheet
 * after an edit (new markup arriving unstyled). The value only changes when the
 * file does, so proxies and browsers still cache it between edits.
 *
 * @param string $relative_path Path relative to the theme root, no leading slash.
 * @return string Version string.
 */
function kindredcare_asset_version( string $relative_path ): string {
	$file = get_theme_file_path( $relative_path );

	if ( file_exists( $file ) ) {
		return KINDREDCARE_VERSION . '.' . filemtime( $file );
	}

	return KINDREDCARE_VERSION;
}

add_action( 'wp_enqueue_scripts', 'kindredcare_enqueue_assets' );
/**
 * Register and enqueue the front-end bundle.
 */
function kindredcare_enqueue_assets(): void {
	/*
	 * Work Sans (display) + Inter (body), matching kindredcare.ca. Before
	 * launch these should be self-hosted as subset WOFF2 and the @font-face
	 * blocks in base/_fonts.scss uncommented (redesign plan section 13).
	 */
	wp_enqueue_style(
		'kindredcare-fonts',
		'https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap',
		array(),
		null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves its own versioned URLs; a ?ver= query busts their cache for no gain.
	);

	// The compiled Sass bundle. style.css is metadata only and is never enqueued.
	wp_enqueue_style(
		'kindredcare',
		get_theme_file_uri( 'assets/css/main.css' ),
		array( 'kindredcare-fonts' ),
		kindredcare_asset_version( 'assets/css/main.css' )
	);

	/*
	 * Registration + Login in the header, and the 4-item mobile bottom bar
	 * (its search button was removed). Inline because main.css
	 * is compiled from Sass outside this theme.
	 */
	wp_add_inline_style(
		'kindredcare',
		'.l-header__account{display:flex;gap:8px;align-items:center}.l-header__account .c-btn{white-space:nowrap}@media (max-width:479px){.l-header__inner{gap:12px}.l-header__account{gap:6px}.l-header__account .c-btn--sm{--btn-pad-x:12px;--btn-zone:0px}.l-header__account .c-btn--sm::after{display:none}}@media (max-width:389px){.l-header__account .c-btn--sm{--btn-pad-x:9px;font-size:13px}}.l-bottom-bar__list{grid-template-columns:repeat(4,1fr)}@media (min-width:1280px){.l-header__inner{display:grid;grid-template-columns:1fr auto 1fr}.l-header__nav{margin-inline:0}.l-header__tools{justify-self:end}}.l-header__link{display:inline-flex;gap:7px;align-items:center}.l-header__icon{flex-shrink:0;transition:transform 200ms cubic-bezier(0.25,0.46,0.45,0.94)}.l-header__icon-fill{fill:transparent;transition:fill 200ms cubic-bezier(0.25,0.46,0.45,0.94)}.l-header__link.is-active .l-header__icon-fill,.l-header__link[aria-current=page] .l-header__icon-fill{fill:color-mix(in oklab,var(--c-trust) 20%,transparent)}@media (hover:hover) and (pointer:fine){.l-header__link:hover .l-header__icon{transform:translateY(-1px)}}@media (min-width:1024px){.s-faq__intro{position:sticky;top:104px;align-self:start}.s-faq__intro .s-faq__aside{position:static}}.c-tier__list{margin-block-end:24px}.c-tier__link{color:inherit;text-decoration:none}.c-tier__link::after{position:absolute;inset:0;z-index:2;content:""}.c-tier__link:focus-visible{outline:none}.c-tier:has(.c-tier__link:focus-visible){outline:3px solid var(--c-trust);outline-offset:2px}'
	);

	/*
	 * The Services page's own sheet, on that page only.
	 *
	 * Separate from the bundle because the bundle is generated — see the note
	 * at the top of assets/css/services.css. Declared as depending on
	 * `kindredcare` so it always prints after it and can rely on the tokens
	 * defined there, whatever order WordPress would otherwise choose.
	 */
	// The premium hero's sheet, on the front page only. See assets/css/hero-pro.css.
	if ( is_front_page() ) {
		/*
		 * The premium hero's serif headline. Front page only, and only the two
		 * cuts it uses: upright 500 for the statement, italic 400 for the accent.
		 */
		wp_enqueue_style(
			'kindredcare-hero-pro-font',
			'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves its own versioned URLs.
		);

		wp_enqueue_style(
			'kindredcare-hero-pro',
			get_theme_file_uri( 'assets/css/hero-pro.css' ),
			array( 'kindredcare', 'kindredcare-hero-pro-font' ),
			kindredcare_asset_version( 'assets/css/hero-pro.css' )
		);
	}

	if ( is_page_template( 'template-services.php' ) ) {
		wp_enqueue_style(
			'kindredcare-services',
			get_theme_file_uri( 'assets/css/services.css' ),
			array( 'kindredcare' ),
			kindredcare_asset_version( 'assets/css/services.css' )
		);
	}

	/*
	 * The gigs sheet, on the three screens that show a listing: the post type
	 * archive, a category archive, and one gig. Separate from the bundle for
	 * the same reason the Services sheet is — see the note at the top of
	 * assets/css/gigs.css.
	 *
	 * It borrows a handful of selectors from the Services sheet — the tally in
	 * the masthead, the closing call to action, the empty state — because those
	 * are the same objects doing the same job, and a second copy of them under
	 * new names would be two things to keep in agreement. So that file loads
	 * here too, before this one.
	 */
	if (
		is_post_type_archive( KINDREDCARE_GIG_POST_TYPE )
		|| is_tax( KINDREDCARE_GIG_TAXONOMY )
		|| is_singular( KINDREDCARE_GIG_POST_TYPE )
	) {
		wp_enqueue_style(
			'kindredcare-services',
			get_theme_file_uri( 'assets/css/services.css' ),
			array( 'kindredcare' ),
			kindredcare_asset_version( 'assets/css/services.css' )
		);

		wp_enqueue_style(
			'kindredcare-gigs',
			get_theme_file_uri( 'assets/css/gigs.css' ),
			array( 'kindredcare-services' ),
			kindredcare_asset_version( 'assets/css/gigs.css' )
		);
	}

	/*
	 * Vendor libraries. Deferred, not async: main.js reads GSAP, ScrollTrigger,
	 * Swiper and Fancybox off the global scope at boot, so they must have run
	 * first and in this order (ScrollTrigger registers itself into GSAP).
	 */
	$vendor = array(
		'kindredcare-gsap'          => 'assets/vendor/gsap/gsap.min.js',
		'kindredcare-scrolltrigger' => 'assets/vendor/gsap/ScrollTrigger.min.js',
		'kindredcare-swiper'        => 'assets/vendor/swiper/swiper-bundle.min.js',
		'kindredcare-fancybox'      => 'assets/vendor/fancybox/fancybox.umd.js',
	);

	$previous = array();
	foreach ( $vendor as $handle => $path ) {
		wp_enqueue_script(
			$handle,
			get_theme_file_uri( $path ),
			$previous,
			kindredcare_asset_version( $path ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$previous = array( $handle );
	}

	// The boot sequence. A module, so it defers by nature.
	wp_enqueue_script(
		'kindredcare-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		array( 'kindredcare-fancybox' ),
		kindredcare_asset_version( 'assets/js/main.js' ),
		array( 'in_footer' => true )
	);

	/*
	 * The hero headline types on its own entry point, in <head>, ahead of
	 * everything above. It cannot wait for main.js: the vendor scripts land
	 * hundreds of milliseconds later and the headline has to be readable inside
	 * 200ms. in_footer is false for exactly that reason.
	 */
	wp_enqueue_script(
		'kindredcare-hero-type',
		get_theme_file_uri( 'assets/js/hero-type.js' ),
		array(),
		kindredcare_asset_version( 'assets/js/hero-type.js' ),
		array( 'in_footer' => false )
	);

	/*
	 * Arms the typewriter before the first paint.
	 *
	 * The headline text lives in the markup so it survives with JS off, which
	 * also means it is painted in full and then wiped when the typewriter runs
	 * — a flash of the finished line. This hides the typed copy up front so
	 * typing starts from empty.
	 *
	 * Attached 'before' hero-type.js so it prints inline, in <head>, ahead of
	 * the module it arms — a fetched file would land after the paint it exists
	 * to precede.
	 *
	 * The 8s timer is only a backstop for a request that hangs without ever
	 * resolving or erroring. Real recovery is the onerror handler added in
	 * kindredcare_script_loader_tag(): a module that fails to load says so, and
	 * a slow one is not a failed one. An earlier 3s timer proved that the hard
	 * way — on a cold edge the module arrived at 3.9s, so the timer fired first
	 * and put the finished headline on screen for the 900ms before typing
	 * began, which is the exact flash this all exists to prevent.
	 */
	wp_add_inline_script(
		'kindredcare-hero-type',
		'(function () {'
			. 'if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;'
			. 'var root = document.documentElement;'
			. 'root.setAttribute("data-typing-ready", "");'
			. 'window.setTimeout(function () {'
				. 'if (!root.hasAttribute("data-typing-started")) {'
					. 'root.removeAttribute("data-typing-ready");'
				. '}'
			. '}, 8000);'
		. '})();',
		'before'
	);
}

add_filter( 'script_loader_tag', 'kindredcare_script_loader_tag', 10, 3 );
/**
 * Mark the ES modules as such, and give hero-type.js its failure path.
 *
 * @param string $tag    The full <script> tag.
 * @param string $handle Registered handle.
 * @param string $src    Script source URL.
 * @return string Filtered tag.
 */
function kindredcare_script_loader_tag( string $tag, string $handle, string $src ): string {
	if ( ! in_array( $handle, KINDREDCARE_MODULE_HANDLES, true ) ) {
		return $tag;
	}

	$attributes = 'type="module"';

	/*
	 * If hero-type.js never loads, show the headline immediately rather than
	 * waiting out the 8s backstop above.
	 */
	if ( 'kindredcare-hero-type' === $handle ) {
		$attributes .= ' onerror="document.documentElement.removeAttribute(\'data-typing-ready\')"';
	}

	// WordPress emits type="text/javascript" only when the theme omits html5
	// 'script' support; with it declared the tag has no type at all. Handle both.
	if ( str_contains( $tag, " type='text/javascript'" ) ) {
		return str_replace( " type='text/javascript'", ' ' . $attributes, $tag );
	}

	if ( str_contains( $tag, ' type="text/javascript"' ) ) {
		return str_replace( ' type="text/javascript"', ' ' . $attributes, $tag );
	}

	return str_replace( '<script ', '<script ' . $attributes . ' ', $tag );
}

add_action( 'wp_head', 'kindredcare_head_meta', 2 );
/**
 * Meta and icons the static page carried in <head>.
 *
 * Only the favicons are conditional: if a Site Icon is set in Settings ->
 * General, WordPress prints its own set and these would be duplicates.
 */
function kindredcare_head_meta(): void {
	printf(
		'<meta name="theme-color" content="%s" />' . "\n",
		esc_attr( '#faf7f2' )
	);

	if ( has_site_icon() ) {
		return;
	}

	/*
	 * The live site's own mark, taken from kindredcare.ca and self-hosted
	 * rather than hotlinked. Same three files it serves: 32px for the tab,
	 * 192px for Android's home screen, 180px for iOS.
	 */
	$icons = array(
		array( 'icon', 'assets/img/favicon-32x32.png', '32x32' ),
		array( 'icon', 'assets/img/favicon-192x192.png', '192x192' ),
		array( 'apple-touch-icon', 'assets/img/apple-touch-icon.png', '' ),
	);

	foreach ( $icons as list( $rel, $path, $sizes ) ) {
		printf(
			'<link rel="%1$s" href="%2$s"%3$s type="image/png" />' . "\n",
			esc_attr( $rel ),
			esc_url( get_theme_file_uri( $path ) ),
			$sizes ? ' sizes="' . esc_attr( $sizes ) . '"' : ''
		);
	}
}
