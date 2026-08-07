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
 * With WP_DEBUG on, the file's mtime is used so an edited Sass build shows up on
 * reload without a version bump. Otherwise the theme version, which is stable
 * and lets proxies cache.
 *
 * @param string $relative_path Path relative to the theme root, no leading slash.
 * @return string Version string.
 */
function kindredcare_asset_version( string $relative_path ): string {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$file = get_theme_file_path( $relative_path );
		if ( file_exists( $file ) ) {
			return (string) filemtime( $file );
		}
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
