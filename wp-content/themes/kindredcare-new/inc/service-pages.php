<?php
/**
 * Service detail pages — one page per "What you can book" tile.
 *
 * Each tile in the `kindredcare_taxonomy` option is also a page at
 * /services/{slug}/. There is no post type behind them: the tile already holds
 * the service's name, rate, tier, description and photograph, and a second copy
 * of those in a post would only ever drift from the tile. The detail-only
 * fields (the longer description, what's included, the button) are stored on
 * the same tile and edited on the same screen, KindredCare -> Service taxonomy.
 *
 * The route is a rewrite rule plus a query var. A slug that matches no visible
 * tile is a real 404, not an empty page.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/** Query var carrying the requested service slug. */
const KINDREDCARE_SERVICE_QUERY_VAR = 'kc_service';

/** Query var set on the /services/ listing. */
const KINDREDCARE_SERVICES_INDEX_VAR = 'kc_services';

/** URL base the detail pages hang off: /services/{slug}/. */
const KINDREDCARE_SERVICE_BASE = 'services';

/**
 * Bumped whenever the rewrite rules below change, so they are flushed once on
 * the next request instead of needing Settings -> Permalinks to be re-saved.
 */
const KINDREDCARE_SERVICE_REWRITE_VERSION = '2';

/**
 * Whether the current request is the /services/ listing.
 */
function kindredcare_is_services_index(): bool {
	return '1' === (string) get_query_var( KINDREDCARE_SERVICES_INDEX_VAR );
}

/**
 * The /services/ listing address.
 */
function kindredcare_services_index_url(): string {
	return home_url( user_trailingslashit( KINDREDCARE_SERVICE_BASE ) );
}

/**
 * The slug a tile is served at.
 *
 * The stored slug if an editor set one, otherwise one derived from the visible
 * name — "Dementia & <span>Alzheimer's care</span>" becomes
 * "dementia-alzheimers-care".
 *
 * @param array<string, mixed> $tile Tile data.
 */
function kindredcare_service_slug( array $tile ): string {
	$slug = sanitize_title( (string) ( $tile['slug'] ?? '' ) );

	if ( '' !== $slug ) {
		return $slug;
	}

	return sanitize_title( wp_strip_all_tags( html_entity_decode( (string) ( $tile['name'] ?? '' ), ENT_QUOTES ) ) );
}

/**
 * The detail page address for a tile.
 *
 * @param array<string, mixed> $tile Tile data.
 */
function kindredcare_service_url( array $tile ): string {
	return home_url( user_trailingslashit( KINDREDCARE_SERVICE_BASE . '/' . kindredcare_service_slug( $tile ) ) );
}

/**
 * The visible tile served at a slug, or null.
 *
 * Only tiles that render on the home page have a page — a tile switched off
 * there is switched off here too.
 *
 * @param string $slug Requested slug.
 * @return array<string, mixed>|null
 */
function kindredcare_service_find( string $slug ): ?array {
	$slug = sanitize_title( $slug );

	if ( '' === $slug ) {
		return null;
	}

	foreach ( kindredcare_taxonomy_active_tiles( kindredcare_taxonomy() ) as $tile ) {
		if ( kindredcare_service_slug( $tile ) === $slug ) {
			return $tile;
		}
	}

	return null;
}

/**
 * The service being viewed on the current request, or null.
 *
 * @return array<string, mixed>|null
 */
function kindredcare_current_service(): ?array {
	static $cache = array();

	$slug = (string) get_query_var( KINDREDCARE_SERVICE_QUERY_VAR );

	if ( '' === $slug ) {
		return null;
	}

	if ( ! array_key_exists( $slug, $cache ) ) {
		$cache[ $slug ] = kindredcare_service_find( $slug );
	}

	return $cache[ $slug ];
}

/**
 * Split stored multi-line text into trimmed, non-empty lines.
 *
 * @param string $text Raw text.
 * @return string[]
 */
function kindredcare_service_lines( string $text ): array {
	return array_values(
		array_filter(
			array_map( 'trim', preg_split( '/\R/', $text ) ?: array() ),
			static fn( string $line ): bool => '' !== $line
		)
	);
}

/**
 * Split stored text into paragraphs on blank lines.
 *
 * @param string $text Raw text.
 * @return string[]
 */
function kindredcare_service_paragraphs( string $text ): array {
	return array_values(
		array_filter(
			array_map( 'trim', preg_split( '/\R\s*\R/', trim( $text ) ) ?: array() ),
			static fn( string $para ): bool => '' !== $para
		)
	);
}

/**
 * The verification tier a service requires, from KindredCare -> Verification tiers.
 *
 * Matched on the tile's tier text, which starts with the tier's name
 * ("Enhanced — Trusted"), so the detail page lists the same checks the home
 * page's tier cards do and the two cannot disagree.
 *
 * @param array<string, mixed> $tile Tile data.
 * @return array<string, mixed>|null The tier, or null when none matches.
 */
function kindredcare_service_tier( array $tile ): ?array {
	$label = strtolower( trim( wp_strip_all_tags( (string) ( $tile['tier'] ?? '' ) ) ) );

	if ( '' === $label ) {
		return null;
	}

	foreach ( kindredcare_tiers()['tiers'] as $tier ) {
		$name = strtolower( trim( (string) $tier['name'] ) );

		if ( '' !== $name && str_starts_with( $label, $name ) ) {
			return $tier;
		}
	}

	return null;
}

/**
 * Every check behind a tier, spelled out.
 *
 * The home page cards say "Everything in Basic" because the Basic card sits
 * beside them; a service page shows one tier on its own, so the lower tiers'
 * checks are listed in full instead of being referred to.
 *
 * @param array<string, mixed> $target The tier from kindredcare_service_tier().
 * @return string[]
 */
function kindredcare_service_tier_checks( array $target ): array {
	$checks = array();

	foreach ( kindredcare_tiers()['tiers'] as $tier ) {
		foreach ( kindredcare_tiers_active_checks( $tier ) as $check ) {
			if ( ! preg_match( '/^everything in\b/i', trim( (string) $check ) ) ) {
				$checks[] = (string) $check;
			}
		}

		if ( $tier['name'] === $target['name'] ) {
			break;
		}
	}

	return array_values( array_unique( $checks ) );
}

/**
 * Icon and accent colour for a service's card in "Other services".
 *
 * Matched on keywords in the slug, so a renamed or newly added service still
 * gets a sensible icon; anything unmatched gets the heart in the brand blue.
 *
 * @param array<string, mixed> $tile Tile data.
 * @return array{svg: string, tone: string}
 */
function kindredcare_service_icon( array $tile ): array {
	$slug = kindredcare_service_slug( $tile );

	$icons = array(
		// Two speech bubbles.
		'companion' => array( '#db4a2b', '<path d="M4 5.5h10.5a1.5 1.5 0 0 1 1.5 1.5v5.5a1.5 1.5 0 0 1-1.5 1.5H9l-3.5 3v-3H4a1.5 1.5 0 0 1-1.5-1.5V7A1.5 1.5 0 0 1 4 5.5Z"/><path d="M16 9.5h3.5A1.5 1.5 0 0 1 21 11v5.5a1.5 1.5 0 0 1-1.5 1.5H18v2.5L15 18h-3.5"/><path d="M6.5 10h.01M9.25 10h.01M12 10h.01" stroke-width="2.4"/>' ),
		// A head with a heart where the mind is.
		'dementia'  => array( '#7a5cc7', '<path d="M9 21v-3.2a6.8 6.8 0 1 1 8.6-6.6c0 .9.9 1.6 1.5 2.4l.9 1.4h-2v2.5a1.5 1.5 0 0 1-1.5 1.5H15V21"/><path d="M12.4 13.3s-2.9-1.7-2.9-3.6A1.55 1.55 0 0 1 12.4 9a1.55 1.55 0 0 1 2.9.7c0 1.9-2.9 3.6-2.9 3.6Z"/>' ),
		// A tablet.
		'tech'      => array( '#4293cf', '<rect x="5" y="2.5" width="14" height="19" rx="2.5"/><path d="M10.5 18.5h3"/><path d="M8.5 7.5h7M8.5 11h5"/>' ),
		// A car.
		'transport' => array( '#2e6db4', '<path d="M5 16.5H3.5v-4.2l1.8-4.6A2 2 0 0 1 7.2 6.5h9.6a2 2 0 0 1 1.9 1.2l1.8 4.6v4.2H19"/><path d="M3.5 12.5h17"/><circle cx="7.5" cy="16.5" r="2"/><circle cx="16.5" cy="16.5" r="2"/><path d="M9.5 16.5h5"/>' ),
		// A pot with steam.
		'meal'      => array( '#d98c1f', '<path d="M4.5 11.5h15v4.5a4 4 0 0 1-4 4h-7a4 4 0 0 1-4-4z"/><path d="M2.5 11.5h19"/><path d="M9 3.5c-.8.8-.8 1.7 0 2.5s.8 1.7 0 2.5M13 3.5c-.8.8-.8 1.7 0 2.5s.8 1.7 0 2.5M17 3.5c-.8.8-.8 1.7 0 2.5"/>' ),
		// A hand holding a heart.
		'personal'  => array( '#c2477d', '<path d="M3 14.5h3l3.6 1.5h3.4a1.5 1.5 0 0 1 0 3H9.5"/><path d="M13 19l5.3-2.4a1.6 1.6 0 0 1 1.9 2.4L16.5 21.5H6L3 20"/><path d="M14 11.5S9.5 9 9.5 6a2.25 2.25 0 0 1 4.5-1 2.25 2.25 0 0 1 4.5 1c0 3-4.5 5.5-4.5 5.5Z"/>' ),
		// A sprout.
		'garden'    => array( '#2e9465', '<path d="M12 21v-8"/><path d="M12 13c0-4 3-7 8-7 0 4.5-3.2 7-8 7Z"/><path d="M12 15.5c0-3.4-2.6-6-7-6 0 3.8 2.8 6 7 6Z"/><path d="M7 21h10"/>' ),
	);

	foreach ( $icons as $needle => list( $tone, $paths ) ) {
		if ( str_contains( $slug, $needle ) ) {
			return array(
				'tone' => $tone,
				'svg'  => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>',
			);
		}
	}

	return array(
		'tone' => '#2e6db4',
		'svg'  => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20.3S4.2 15.6 4.2 10.3A4.8 4.8 0 0 1 12 6.6a4.8 4.8 0 0 1 7.8 3.7c0 5.3-7.8 10-7.8 10Z"/></svg>',
	);
}

add_action( 'init', 'kindredcare_service_rewrite' );
/**
 * Register /services/ and /services/{slug}/, and flush once when the rules are
 * new or changed.
 */
function kindredcare_service_rewrite(): void {
	add_rewrite_rule(
		'^' . KINDREDCARE_SERVICE_BASE . '/?$',
		'index.php?' . KINDREDCARE_SERVICES_INDEX_VAR . '=1',
		'top'
	);
	add_rewrite_rule(
		'^' . KINDREDCARE_SERVICE_BASE . '/([^/]+)/?$',
		'index.php?' . KINDREDCARE_SERVICE_QUERY_VAR . '=$matches[1]',
		'top'
	);

	if ( get_option( 'kindredcare_service_rewrite_version' ) !== KINDREDCARE_SERVICE_REWRITE_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'kindredcare_service_rewrite_version', KINDREDCARE_SERVICE_REWRITE_VERSION, true );
	}
}

add_filter( 'query_vars', 'kindredcare_service_query_vars' );
/**
 * Let WordPress read the service slug from the rewritten request.
 *
 * @param string[] $vars Public query vars.
 * @return string[]
 */
function kindredcare_service_query_vars( array $vars ): array {
	$vars[] = KINDREDCARE_SERVICE_QUERY_VAR;
	$vars[] = KINDREDCARE_SERVICES_INDEX_VAR;

	return $vars;
}

add_action( 'parse_query', 'kindredcare_service_parse_query' );
/**
 * Stop a service request reading as the blog home.
 *
 * With only the query var set, WordPress would otherwise treat the request as
 * the posts page — wrong body classes, wrong title, the latest posts queried.
 *
 * @param WP_Query $query The query being parsed.
 */
function kindredcare_service_parse_query( WP_Query $query ): void {
	if ( ! $query->is_main_query() ) {
		return;
	}

	if ( '' === (string) $query->get( KINDREDCARE_SERVICE_QUERY_VAR ) && '1' !== (string) $query->get( KINDREDCARE_SERVICES_INDEX_VAR ) ) {
		return;
	}

	$query->is_home       = false;
	$query->is_front_page = false;
	$query->is_archive    = false;
}

add_action( 'template_redirect', 'kindredcare_service_404' );
/**
 * Answer an unknown or hidden service with a proper 404.
 */
function kindredcare_service_404(): void {
	if ( '' === (string) get_query_var( KINDREDCARE_SERVICE_QUERY_VAR ) || kindredcare_current_service() ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}

add_filter( 'template_include', 'kindredcare_service_template' );
/**
 * Serve the listing with services-index.php and a found service with
 * service-detail.php.
 *
 * @param string $template Template WordPress chose.
 */
function kindredcare_service_template( string $template ): string {
	if ( kindredcare_is_services_index() ) {
		$index = locate_template( 'services-index.php' );

		return '' !== $index ? $index : $template;
	}

	if ( ! kindredcare_current_service() ) {
		return $template;
	}

	$detail = locate_template( 'service-detail.php' );

	return '' !== $detail ? $detail : $template;
}

add_filter( 'document_title_parts', 'kindredcare_service_title' );
/**
 * Title the browser tab after the service, or "Services" on the listing.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function kindredcare_service_title( array $parts ): array {
	$tile = kindredcare_current_service();

	if ( kindredcare_is_services_index() ) {
		$parts['title'] = __( 'Services', 'kindredcare' );
	} elseif ( $tile ) {
		$parts['title'] = wp_strip_all_tags( html_entity_decode( (string) $tile['name'], ENT_QUOTES ) );
	}

	return $parts;
}

add_action( 'wp_head', 'kindredcare_service_meta', 1 );
/**
 * Description and canonical for the listing and for a service page.
 *
 * WordPress prints neither for a route it does not own, so without these the
 * page would go to search engines with no summary and no canonical address.
 */
function kindredcare_service_meta(): void {
	if ( kindredcare_is_services_index() ) {
		$lead = (string) kindredcare_taxonomy()['lead'];
		?>
		<?php if ( '' !== trim( $lead ) ) : ?>
			<meta name="description" content="<?php echo esc_attr( wp_trim_words( wp_strip_all_tags( $lead ), 30, '…' ) ); ?>" />
		<?php endif; ?>
		<link rel="canonical" href="<?php echo esc_url( kindredcare_services_index_url() ); ?>" />
		<?php
		return;
	}

	$tile = kindredcare_current_service();

	if ( ! $tile ) {
		return;
	}

	$summary = '' !== trim( (string) $tile['blurb'] ) ? (string) $tile['blurb'] : (string) ( $tile['details'] ?? '' );
	?>
	<meta name="description" content="<?php echo esc_attr( wp_trim_words( wp_strip_all_tags( $summary ), 30, '…' ) ); ?>" />
	<link rel="canonical" href="<?php echo esc_url( kindredcare_service_url( $tile ) ); ?>" />
	<?php
}

add_action( 'wp_enqueue_scripts', 'kindredcare_service_assets', 20 );
/**
 * The service pages' own sheet, on the listing and the detail pages only.
 *
 * Separate from main.css for the same reason services.css is — see the note at
 * the top of assets/css/service-detail.css.
 */
function kindredcare_service_assets(): void {
	if ( ! kindredcare_is_services_index() && ! kindredcare_current_service() ) {
		return;
	}

	wp_enqueue_style(
		'kindredcare-service-detail',
		get_theme_file_uri( 'assets/css/service-detail.css' ),
		array( 'kindredcare' ),
		kindredcare_asset_version( 'assets/css/service-detail.css' )
	);
}

add_filter( 'wp_nav_menu_objects', 'kindredcare_service_menu_current' );
/**
 * Light the Services menu item on a service's detail page.
 *
 * WordPress only matches a custom link against its exact URL, so the item
 * pointing at /services/ is current on the listing and dark on
 * /services/gardening/. On a detail page it is marked as an ancestor instead:
 * KindredCare_Header_Walker underlines it, but does not add aria-current,
 * because the listing is not the page being read.
 *
 * @param array<int, WP_Post> $items Menu items.
 * @return array<int, WP_Post> Filtered items.
 */
function kindredcare_service_menu_current( array $items ): array {
	if ( ! kindredcare_current_service() ) {
		return $items;
	}

	$target = untrailingslashit( kindredcare_services_index_url() );

	foreach ( $items as $item ) {
		if ( untrailingslashit( (string) $item->url ) === $target ) {
			$item->classes = array_merge( (array) $item->classes, array( 'current-menu-ancestor' ) );
		}
	}

	return $items;
}
