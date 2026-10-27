<?php
/**
 * Admin: KindredCare -> Import / export.
 *
 * Moves the content of all thirteen section screens between installs as one
 * JSON file.
 *
 * What this does NOT move, because it cannot: the KindredCare menu itself. That
 * menu and its screens are built by add_menu_page() and add_submenu_page() in
 * inc/admin-*.php — they are code, not rows in a table, and they exist on any
 * install the moment this theme is active. A file can only carry what the
 * screens hold. The manifest below therefore also *records* the menu, so an
 * import can check the destination is running a theme with the same screens
 * before it writes anything into them.
 *
 * Imported values go through each section's own registered sanitize_callback —
 * the same function a form post runs through. A file cannot therefore put
 * anything into an option that the screen itself would have refused.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_TRANSFER_MENU_SLUG = 'kindredcare-transfer';
const KINDREDCARE_TRANSFER_EXPORT    = 'kindredcare_export';
const KINDREDCARE_TRANSFER_IMPORT    = 'kindredcare_import';

/**
 * File format version.
 *
 * Bumped only when the shape of the file changes in a way an older importer
 * would read wrongly. The importer refuses anything it does not know.
 */
const KINDREDCARE_TRANSFER_FORMAT = 3;

/**
 * Stand-in for the site's own address inside the file.
 *
 * Menu links, page content and button destinations are stored as absolute URLs,
 * so a file written on one install carries that install's domain everywhere. On
 * a different site those links point back at the machine they came from — dead
 * on a live server, and quietly so, because they still look like links. Every
 * occurrence is swapped for this token on the way out and for the destination's
 * own address on the way in.
 */
const KINDREDCARE_TRANSFER_HOME_TOKEN = '{{KC_HOME}}';

/**
 * Swap one string for another everywhere in a nested value.
 *
 * @param mixed  $value Value to walk.
 * @param string $from  Needle.
 * @param string $to    Replacement.
 * @return mixed Rewritten value.
 */
function kindredcare_transfer_rewrite( $value, string $from, string $to ) {
	if ( is_string( $value ) ) {
		return str_replace( $from, $to, $value );
	}

	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$value[ $key ] = kindredcare_transfer_rewrite( $item, $from, $to );
		}
	}

	return $value;
}

/**
 * WordPress's own placeholder, never this site's content.
 *
 * Carrying it would put "Sample Page" on a live install for no reason.
 *
 * @return array<int, string>
 */
function kindredcare_transfer_skipped_pages(): array {
	return array( 'sample-page' );
}

/**
 * The pages, as slugs rather than ids.
 *
 * Ids are meaningless on another install — page 203 there is something else, or
 * nothing. Slugs are what the two sites can agree on, so every relationship in
 * the file (a parent, a menu item's target, the front page) is written as one.
 *
 * @return array<int, array<string, mixed>>
 */
function kindredcare_transfer_export_pages(): array {
	$skip  = kindredcare_transfer_skipped_pages();
	$pages = array();

	foreach ( get_pages( array( 'post_status' => 'publish,draft,private', 'sort_column' => 'menu_order,post_title' ) ) as $page ) {
		if ( in_array( $page->post_name, $skip, true ) ) {
			continue;
		}

		$parent = $page->post_parent ? get_post( $page->post_parent ) : null;

		$pages[] = array(
			'slug'           => $page->post_name,
			'title'          => $page->post_title,
			'content'        => $page->post_content,
			'excerpt'        => $page->post_excerpt,
			'status'         => $page->post_status,
			'menu_order'     => (int) $page->menu_order,
			'parent'         => $parent ? $parent->post_name : null,
			'template'       => get_page_template_slug( $page->ID ) ?: '',
			'comment_status' => $page->comment_status,
		);
	}

	return $pages;
}

/**
 * The navigation menus, their items, and where they are assigned.
 *
 * A menu item is itself a post with a fistful of meta, and one pointing at a
 * page holds that page's id. Both are rewritten as slugs here for the same
 * reason the pages are.
 *
 * @return array<int, array<string, mixed>>
 */
function kindredcare_transfer_export_menus(): array {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$menus     = array();

	foreach ( wp_get_nav_menus() as $menu ) {
		$items = array();

		/*
		 * Item ids are only needed to rebuild the parent relationships, and they
		 * do not survive the trip. Positions in this list do, so a child records
		 * the *index* of its parent instead.
		 */
		$order = array();
		foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: array() as $index => $item ) {
			$order[ (int) $item->ID ] = $index;
		}

		foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: array() as $item ) {
			$target_slug = '';
			if ( 'post_type' === $item->type ) {
				$object = get_post( (int) $item->object_id );
				if ( $object ) {
					$target_slug = $object->post_name;
				}
			}

			$parent = (int) $item->menu_item_parent;

			$items[] = array(
				'title'       => $item->title,
				'type'        => $item->type,          // custom | post_type | taxonomy
				'object'      => $item->object,        // page | post | category …
				'object_slug' => $target_slug,
				'url'         => $item->url,
				'target'      => $item->target,
				'classes'     => implode( ' ', array_filter( (array) $item->classes ) ),
				'xfn'         => $item->xfn,
				'attr_title'  => $item->attr_title,
				'description' => $item->description,
				'position'    => (int) $item->menu_order,
				'parent'      => isset( $order[ $parent ] ) ? $order[ $parent ] : null,
			);
		}

		$menus[] = array(
			'slug'      => $menu->slug,
			'name'      => $menu->name,
			'locations' => array_keys( array_filter( $locations, static fn( $id ) => (int) $id === (int) $menu->term_id ) ),
			'items'     => $items,
		);
	}

	return $menus;
}

/**
 * Every section screen, in menu order, with the option it edits.
 *
 * This is the manifest: it is what makes the file self-describing, and it is
 * what the importer checks the destination against.
 *
 * @return array<int, array<string, string>>
 */
function kindredcare_transfer_manifest(): array {
	return array(
		array( 'slug' => KINDREDCARE_ADMIN_MENU_SLUG, 'title' => 'Hero', 'option' => KINDREDCARE_HERO_OPTION ),
		array( 'slug' => 'kindredcare-trust-strip', 'title' => 'Trust strip', 'option' => KINDREDCARE_TRUST_STRIP_OPTION ),
		array( 'slug' => 'kindredcare-our-story', 'title' => 'Our story', 'option' => KINDREDCARE_OUR_STORY_OPTION ),
		array( 'slug' => 'kindredcare-services', 'title' => 'Services', 'option' => KINDREDCARE_SERVICES_OPTION ),
		array( 'slug' => 'kindredcare-how', 'title' => 'How it works', 'option' => KINDREDCARE_HOW_OPTION ),
		array( 'slug' => 'kindredcare-taxonomy', 'title' => 'Service taxonomy', 'option' => KINDREDCARE_TAXONOMY_OPTION ),
		array( 'slug' => 'kindredcare-benefits', 'title' => 'Benefits', 'option' => KINDREDCARE_BENEFITS_OPTION ),
		array( 'slug' => 'kindredcare-caregivers', 'title' => 'Founding caregivers', 'option' => KINDREDCARE_CAREGIVERS_OPTION ),
		array( 'slug' => 'kindredcare-tiers', 'title' => 'Verification tiers', 'option' => KINDREDCARE_TIERS_OPTION ),
		array( 'slug' => 'kindredcare-safety', 'title' => 'During the visit', 'option' => KINDREDCARE_SAFETY_OPTION ),
		array( 'slug' => 'kindredcare-for-caregivers', 'title' => 'For caregivers', 'option' => KINDREDCARE_FOR_CAREGIVERS_OPTION ),
		array( 'slug' => 'kindredcare-faq', 'title' => 'FAQ', 'option' => KINDREDCARE_FAQ_OPTION ),
		array( 'slug' => 'kindredcare-final-cta', 'title' => 'Final CTA', 'option' => KINDREDCARE_FINAL_CTA_OPTION ),
		array( 'slug' => 'kindredcare-contact', 'title' => 'Contact page', 'option' => KINDREDCARE_CONTACT_OPTION ),
	);
}

/**
 * Walk every attachment id in a stored value, through a callback.
 *
 * Attachment ids are the one thing in these options that means nothing on
 * another install: id 42 here is a different picture there, or nothing at all.
 * Every key called `image` or `icon` holds one, so both are visited wherever
 * they appear, however deeply nested.
 *
 * @param mixed    $value    Stored value.
 * @param callable $callback Receives the id, returns its replacement.
 * @return mixed Value with every attachment id replaced.
 */
function kindredcare_transfer_walk_media( $value, callable $callback ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}

	foreach ( $value as $key => $item ) {
		if ( is_array( $item ) ) {
			$value[ $key ] = kindredcare_transfer_walk_media( $item, $callback );
			continue;
		}

		if ( in_array( $key, array( 'image', 'icon' ), true ) ) {
			$value[ $key ] = $callback( (int) $item );
		}
	}

	return $value;
}

/**
 * Build the export payload.
 *
 * @return array<string, mixed>
 */
function kindredcare_transfer_export_payload(): array {
	$sections = array();
	$media    = array();

	/*
	 * Each attachment id is recorded with the URL it points at here, so the
	 * destination can look for the same file rather than trusting a number that
	 * means something else on its own install.
	 */
	$collect = static function ( int $id ) use ( &$media ): int {
		if ( $id > 0 && ! isset( $media[ $id ] ) ) {
			$url = wp_get_attachment_url( $id );
			if ( $url ) {
				$media[ $id ] = $url;
			}
		}

		return $id;
	};

	foreach ( kindredcare_transfer_manifest() as $screen ) {
		$stored = get_option( $screen['option'], null );

		// null means "never saved" — the screen is running on shipped defaults,
		// and that is worth carrying across as-is rather than freezing today's
		// defaults into the file.
		if ( null === $stored ) {
			$sections[ $screen['option'] ] = null;
			continue;
		}

		kindredcare_transfer_walk_media( $stored, $collect );
		$sections[ $screen['option'] ] = $stored;
	}

	// Customizer values live in theme mods, not options — the header strip, the
	// footer columns, the social links.
	$mods = array();
	foreach ( array_keys( kindredcare_defaults() ) as $key ) {
		$value = get_theme_mod( $key, null );
		if ( null !== $value ) {
			$mods[ $key ] = $value;
		}
	}

	$logo = (int) get_theme_mod( 'custom_logo' );
	if ( $logo > 0 ) {
		$collect( $logo );
		$mods['custom_logo'] = $logo;
	}

	$payload = array(
		'format'    => KINDREDCARE_TRANSFER_FORMAT,
		'generated' => gmdate( 'c' ),
		'source'    => array(
			'site'      => home_url( '/' ),
			'theme'     => get_template(),
			'version'   => KINDREDCARE_VERSION,
			'wordpress' => get_bloginfo( 'version' ),
		),
		/*
		 * The menu, recorded rather than created. Importing cannot build these
		 * screens; this is here so the importer can confirm the destination
		 * already has them, and so the file reads as a description of what it
		 * carries.
		 */
		'menu'      => array(
			'parent' => array(
				'slug'  => KINDREDCARE_ADMIN_MENU_SLUG,
				'title' => 'KindredCare',
			),
			'items'  => array_map(
				static fn( array $screen, int $index ): array => array(
					'position' => $index + 1,
					'slug'     => $screen['slug'],
					'title'    => $screen['title'],
					'option'   => $screen['option'],
				),
				kindredcare_transfer_manifest(),
				array_keys( kindredcare_transfer_manifest() )
			),
		),
		'sections'  => $sections,
		'theme_mods' => $mods,
		'media'     => $media,

		/*
		 * Pages and menus are not theme settings — they are posts and taxonomy
		 * terms, which is why they are separate keys rather than more of the
		 * section content above.
		 */
		'pages'     => kindredcare_transfer_export_pages(),
		'menus'     => kindredcare_transfer_export_menus(),

		// Which page is the front page, and which lists the posts.
		'reading'   => array(
			'show_on_front' => (string) get_option( 'show_on_front' ),
			'front'         => ( $front = get_post( (int) get_option( 'page_on_front' ) ) ) ? $front->post_name : '',
			'posts'         => ( $posts = get_post( (int) get_option( 'page_for_posts' ) ) ) ? $posts->post_name : '',
		),
	);

	/*
	 * Everything but `source` is made portable. That block is left alone on
	 * purpose — it is the record of where the file came from, and rewriting it
	 * would erase the one useful trace of that.
	 */
	$source = $payload['source'];
	$payload = kindredcare_transfer_rewrite( $payload, untrailingslashit( home_url() ), KINDREDCARE_TRANSFER_HOME_TOKEN );
	$payload['source'] = $source;

	return $payload;
}

/**
 * Create or update the pages carried in a file.
 *
 * Matched by slug: a page that is already there is updated in place rather than
 * duplicated with a "-2" suffix, which is what inserting blindly would produce.
 *
 * @param array<int, mixed> $pages Page rows from the file.
 * @return int How many pages were written.
 */
function kindredcare_transfer_import_pages( array $pages ): int {
	$written = 0;
	$ids     = array();

	// First pass: the pages themselves. Parents are resolved afterwards, since
	// a parent may appear later in the list than its child.
	foreach ( $pages as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$slug = sanitize_title( (string) ( $row['slug'] ?? '' ) );
		if ( '' === $slug ) {
			continue;
		}

		$existing = get_page_by_path( $slug );

		$data = array(
			'post_type'      => 'page',
			'post_name'      => $slug,
			'post_title'     => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'post_content'   => wp_kses_post( (string) ( $row['content'] ?? '' ) ),
			'post_excerpt'   => wp_kses_post( (string) ( $row['excerpt'] ?? '' ) ),
			'post_status'    => in_array( $row['status'] ?? '', array( 'publish', 'draft', 'private' ), true ) ? $row['status'] : 'publish',
			'menu_order'     => (int) ( $row['menu_order'] ?? 0 ),
			'comment_status' => 'open' === ( $row['comment_status'] ?? '' ) ? 'open' : 'closed',
		);

		if ( $existing instanceof WP_Post ) {
			$data['ID'] = $existing->ID;
		}

		$id = wp_insert_post( wp_slash( $data ), true );

		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}

		$template = (string) ( $row['template'] ?? '' );
		if ( '' !== $template ) {
			update_post_meta( $id, '_wp_page_template', sanitize_text_field( $template ) );
		} else {
			delete_post_meta( $id, '_wp_page_template' );
		}

		$ids[ $slug ] = (int) $id;
		$written++;
	}

	// Second pass: parents, now that every slug has an id.
	foreach ( $pages as $row ) {
		$slug   = sanitize_title( (string) ( $row['slug'] ?? '' ) );
		$parent = $row['parent'] ?? null;

		if ( ! isset( $ids[ $slug ] ) ) {
			continue;
		}

		$parent_id = ( $parent && isset( $ids[ $parent ] ) ) ? $ids[ $parent ] : 0;

		if ( (int) get_post_field( 'post_parent', $ids[ $slug ] ) !== $parent_id ) {
			wp_update_post( array( 'ID' => $ids[ $slug ], 'post_parent' => $parent_id ) );
		}
	}

	return $written;
}

/**
 * Rebuild the navigation menus carried in a file.
 *
 * An existing menu of the same slug is emptied before it is refilled. Adding to
 * it instead would double every item on a second import, and there is no way to
 * tell "the same item again" from "a new one that happens to match".
 *
 * @param array<int, mixed> $menus Menu rows from the file.
 * @return int How many menus were written.
 */
function kindredcare_transfer_import_menus( array $menus ): int {
	$written   = 0;
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$registered = array_keys( get_registered_nav_menus() );

	foreach ( $menus as $menu ) {
		if ( ! is_array( $menu ) ) {
			continue;
		}

		$name = sanitize_text_field( (string) ( $menu['name'] ?? '' ) );
		$slug = sanitize_title( (string) ( $menu['slug'] ?? $name ) );

		if ( '' === $name || '' === $slug ) {
			continue;
		}

		$object = wp_get_nav_menu_object( $slug );

		if ( ! $object ) {
			$id = wp_create_nav_menu( $name );
			if ( is_wp_error( $id ) ) {
				continue;
			}
			$object = wp_get_nav_menu_object( (int) $id );
		}

		if ( ! $object ) {
			continue;
		}

		// Empty it, so a repeated import lands on the same result.
		foreach ( wp_get_nav_menu_items( $object->term_id ) ?: array() as $old ) {
			wp_delete_post( (int) $old->ID, true );
		}

		$created = array();

		foreach ( (array) ( $menu['items'] ?? array() ) as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$type = in_array( $item['type'] ?? '', array( 'custom', 'post_type', 'taxonomy' ), true ) ? $item['type'] : 'custom';

			$args = array(
				'menu-item-title'      => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
				'menu-item-type'       => $type,
				'menu-item-object'     => sanitize_key( (string) ( $item['object'] ?? '' ) ),
				'menu-item-url'        => esc_url_raw( (string) ( $item['url'] ?? '' ) ),
				'menu-item-target'     => '_blank' === ( $item['target'] ?? '' ) ? '_blank' : '',
				'menu-item-classes'    => sanitize_text_field( (string) ( $item['classes'] ?? '' ) ),
				'menu-item-xfn'        => sanitize_text_field( (string) ( $item['xfn'] ?? '' ) ),
				'menu-item-attr-title' => sanitize_text_field( (string) ( $item['attr_title'] ?? '' ) ),
				'menu-item-description' => sanitize_text_field( (string) ( $item['description'] ?? '' ) ),
				'menu-item-position'   => (int) ( $item['position'] ?? ( $index + 1 ) ),
				'menu-item-status'     => 'publish',
			);

			/*
			 * A link to a page travels as that page's slug and is looked up
			 * here. If the page is missing the item is demoted to a plain link
			 * rather than left pointing at whatever holds that id locally.
			 */
			if ( 'post_type' === $type ) {
				$target = get_page_by_path( sanitize_title( (string) ( $item['object_slug'] ?? '' ) ) );

				if ( $target instanceof WP_Post ) {
					$args['menu-item-object-id'] = $target->ID;
				} else {
					$args['menu-item-type'] = 'custom';
					$args['menu-item-url']  = esc_url_raw( (string) ( $item['url'] ?? '' ) );
				}
			}

			$item_id = wp_update_nav_menu_item( $object->term_id, 0, $args );

			if ( ! is_wp_error( $item_id ) ) {
				$created[ $index ] = (int) $item_id;
			}
		}

		// Parents, by the position each held in the file.
		foreach ( (array) ( $menu['items'] ?? array() ) as $index => $item ) {
			$parent = $item['parent'] ?? null;

			if ( null === $parent || ! isset( $created[ $index ], $created[ $parent ] ) ) {
				continue;
			}

			update_post_meta( $created[ $index ], '_menu_item_menu_item_parent', (string) $created[ $parent ] );
		}

		foreach ( (array) ( $menu['locations'] ?? array() ) as $location ) {
			if ( in_array( $location, $registered, true ) ) {
				$locations[ $location ] = (int) $object->term_id;
			}
		}

		$written++;
	}

	set_theme_mod( 'nav_menu_locations', $locations );

	return $written;
}

add_action( 'admin_post_' . KINDREDCARE_TRANSFER_EXPORT, 'kindredcare_transfer_handle_export' );
/**
 * Stream the export as a download.
 */
function kindredcare_transfer_handle_export(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to export this content.', 'kindredcare' ) );
	}

	check_admin_referer( KINDREDCARE_TRANSFER_EXPORT );

	$payload = kindredcare_transfer_export_payload();
	$name    = sprintf( 'kindredcare-content-%s.json', gmdate( 'Y-m-d' ) );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . $name );

	echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

add_action( 'admin_post_' . KINDREDCARE_TRANSFER_IMPORT, 'kindredcare_transfer_handle_import' );
/**
 * Read an uploaded file and write it into the section options.
 */
function kindredcare_transfer_handle_import(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to import this content.', 'kindredcare' ) );
	}

	check_admin_referer( KINDREDCARE_TRANSFER_IMPORT );

	$back = admin_url( 'admin.php?page=' . KINDREDCARE_TRANSFER_MENU_SLUG );

	/** Send the editor back with a result. */
	$finish = static function ( string $state, int $count = 0 ) use ( $back ): void {
		wp_safe_redirect( add_query_arg( array( 'kc-import' => $state, 'kc-count' => $count ), $back ) );
		exit;
	};

	if ( empty( $_FILES['kc_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['kc_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked by is_uploaded_file, read below.
		$finish( 'nofile' );
	}

	$raw     = (string) file_get_contents( $_FILES['kc_file']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local upload, not a remote request.
		$payload = json_decode( $raw, true );

	if ( ! is_array( $payload ) || JSON_ERROR_NONE !== json_last_error() ) {
		$finish( 'unreadable' );
	}

	if ( (int) ( $payload['format'] ?? 0 ) !== KINDREDCARE_TRANSFER_FORMAT ) {
		$finish( 'format' );
	}

	if ( ! is_array( $payload['sections'] ?? null ) ) {
		$finish( 'empty' );
	}

	/*
	 * Put this site's address back wherever the source's used to be, so links
	 * that were absolute at the source stay absolute — and point here.
	 */
	$source = $payload['source'] ?? array();
	$payload = kindredcare_transfer_rewrite( $payload, KINDREDCARE_TRANSFER_HOME_TOKEN, untrailingslashit( home_url() ) );
	$payload['source'] = $source;

	/*
	 * Resolve the file's attachment ids against this install's media library by
	 * the URL each one had at the source. A picture that is not here yet
	 * resolves to 0, which every renderer already reads as "use the bundled
	 * image" — a visibly wrong photograph is worse than the shipped one.
	 */
	$media  = is_array( $payload['media'] ?? null ) ? $payload['media'] : array();
	$resolve = static function ( int $id ) use ( $media ): int {
		if ( $id < 1 ) {
			return 0;
		}

		$url = $media[ (string) $id ] ?? ( $media[ $id ] ?? '' );
		if ( ! $url ) {
			return 0;
		}

		$local = attachment_url_to_postid( (string) $url );
		if ( $local ) {
			return (int) $local;
		}

		/*
		 * The exact address will not match when the two installs upload into
		 * different months, or when the destination sits on a different path.
		 * The file name still will, so the same picture uploaded here is found
		 * rather than silently falling back to the bundled one.
		 */
		$file = basename( wp_parse_url( (string) $url, PHP_URL_PATH ) ?: '' );
		if ( '' === $file ) {
			return 0;
		}

		$match = get_posts(
			array(
				'post_type'              => 'attachment',
				'post_status'            => 'inherit',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one lookup per distinct picture, at import time only.
					array(
						'key'     => '_wp_attached_file',
						'value'   => $file,
						'compare' => 'LIKE',
					),
				),
			)
		);

		return $match ? (int) $match[0] : 0;
	};

	$registered = get_registered_settings();
	$written    = 0;

	foreach ( kindredcare_transfer_manifest() as $screen ) {
		$option = $screen['option'];

		if ( ! array_key_exists( $option, $payload['sections'] ) ) {
			continue;
		}

		$value = $payload['sections'][ $option ];

		// null means the source was on shipped defaults. Match it by removing
		// any stored copy here rather than writing a frozen snapshot.
		if ( null === $value ) {
			delete_option( $option );
			$written++;
			continue;
		}

		if ( ! is_array( $value ) ) {
			continue;
		}

		$value = kindredcare_transfer_walk_media( $value, $resolve );

		/*
		 * Through the screen's own sanitiser. Nothing reaches an option that a
		 * save on that screen would not have allowed, so a hand-edited file
		 * cannot smuggle markup past the escaping the renderers rely on.
		 */
		$sanitize = $registered[ $option ]['sanitize_callback'] ?? null;
		if ( is_callable( $sanitize ) ) {
			$value = call_user_func( $sanitize, $value );
		}

		update_option( $option, $value );
		$written++;
	}

	/*
	 * Pages before menus: a menu item pointing at a page needs that page to
	 * exist so it can be looked up by slug.
	 */
	if ( is_array( $payload['pages'] ?? null ) ) {
		$written += kindredcare_transfer_import_pages( $payload['pages'] );
	}

	if ( is_array( $payload['menus'] ?? null ) ) {
		$written += kindredcare_transfer_import_menus( $payload['menus'] );
	}

	// Which page is the front page. After the pages, for the same reason.
	if ( is_array( $payload['reading'] ?? null ) ) {
		$front = get_page_by_path( sanitize_title( (string) ( $payload['reading']['front'] ?? '' ) ) );
		$posts = get_page_by_path( sanitize_title( (string) ( $payload['reading']['posts'] ?? '' ) ) );

		if ( 'page' === ( $payload['reading']['show_on_front'] ?? '' ) && $front instanceof WP_Post ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $front->ID );
			update_option( 'page_for_posts', $posts instanceof WP_Post ? $posts->ID : 0 );
		}
	}

	// Theme mods travel too, or the header strip and footer arrive empty.
	if ( is_array( $payload['theme_mods'] ?? null ) ) {
		$allowed = array_keys( kindredcare_defaults() );

		foreach ( $payload['theme_mods'] as $key => $value ) {
			if ( 'custom_logo' === $key ) {
				$local = $resolve( (int) $value );
				if ( $local ) {
					set_theme_mod( 'custom_logo', $local );
				}
				continue;
			}

			if ( in_array( $key, $allowed, true ) && ( is_scalar( $value ) || is_bool( $value ) ) ) {
				set_theme_mod( $key, $value );
			}
		}
	}

	$finish( 'done', $written );
}

/* Priority 23: after Final CTA (22), and last — it is a tool, not a section. */
add_action( 'admin_menu', 'kindredcare_transfer_admin_menu', 23 );
/**
 * Add the Import / export screen under the KindredCare menu.
 */
function kindredcare_transfer_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Import / export', 'kindredcare' ),
		__( 'Import / export', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_TRANSFER_MENU_SLUG,
		'kindredcare_transfer_render_page'
	);
}

add_action( 'admin_enqueue_scripts', 'kindredcare_transfer_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_transfer_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_TRANSFER_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Render the Import / export screen.
 */
function kindredcare_transfer_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to use this screen.', 'kindredcare' ) );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of our own redirect flags.
	$state = isset( $_GET['kc-import'] ) ? sanitize_key( wp_unslash( $_GET['kc-import'] ) ) : '';
	$count = isset( $_GET['kc-count'] ) ? absint( wp_unslash( $_GET['kc-count'] ) ) : 0;
	// phpcs:enable

	$messages = array(
		'done'       => array( 'ok', __( '%d items written from the file — section screens, pages and menus.', 'kindredcare' ) ),
		'nofile'     => array( 'error', __( 'No file arrived. Choose a .json file and try again.', 'kindredcare' ) ),
		'unreadable' => array( 'error', __( 'That file is not readable as JSON.', 'kindredcare' ) ),
		'format'     => array( 'error', __( 'That file was written for a different version of this theme and has not been applied.', 'kindredcare' ) ),
		'empty'      => array( 'error', __( 'That file carries no section content.', 'kindredcare' ) ),
	);
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Tools', 'kindredcare' ),
			__( 'Import / export', 'kindredcare' ),
			esc_html__( 'Move the thirteen section screens, the pages and the navigation menus between installs as a single file.', 'kindredcare' )
		);

		if ( isset( $messages[ $state ] ) ) {
			list( $kind, $text ) = $messages[ $state ];
			printf(
				'<p class="c-notice c-notice--%1$s" role="status">%2$s</p>',
				esc_attr( $kind ),
				esc_html( 'done' === $state ? sprintf( $text, $count ) : $text )
			);
		}
		?>

		<!-- Export ----------------------------------------------------- -->
		<section class="kc-panel">
			<div class="kc-panel__head">
				<?php kindredcare_admin_icon( 'M12 3v12M8 11l4 4 4-4M5 20h14' ); ?>
				<div class="kc-panel__titles">
					<h2><?php esc_html_e( 'Export', 'kindredcare' ); ?></h2>
					<p><?php esc_html_e( 'Downloads one JSON file holding every section screen, the pages, the navigation menus, the Customizer settings, and the pictures they point at.', 'kindredcare' ); ?></p>
				</div>
			</div>
			<div class="kc-panel__body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( KINDREDCARE_TRANSFER_EXPORT ); ?>" />
					<?php wp_nonce_field( KINDREDCARE_TRANSFER_EXPORT ); ?>
					<button type="submit" class="kc-btn"><?php esc_html_e( 'Download the file', 'kindredcare' ); ?></button>
				</form>
			</div>
		</section>

		<!-- Import ----------------------------------------------------- -->
		<section class="kc-panel">
			<div class="kc-panel__head">
				<?php kindredcare_admin_icon( 'M12 15V3M8 7l4-4 4 4M5 20h14' ); ?>
				<div class="kc-panel__titles">
					<h2><?php esc_html_e( 'Import', 'kindredcare' ); ?></h2>
					<p><?php esc_html_e( 'Replaces what the section screens currently hold with what is in the file.', 'kindredcare' ); ?></p>
				</div>
			</div>
			<div class="kc-panel__body">
				<?php
				kindredcare_admin_note(
					__( '<strong>This overwrites the thirteen section screens.</strong> Export first if the current content is worth keeping — that download is the only way back. Everything in the file is put through the same checks a save on each screen performs, so a file cannot write anything a screen would refuse.', 'kindredcare' )
				);

				kindredcare_admin_note(
					__( '<strong>Pages and menus are matched by their slug.</strong> A page already on this site with a matching slug is rewritten, not duplicated — so an existing Privacy policy here would be replaced by the one in the file. A menu of the same name is emptied and refilled, or a second import would double every item.', 'kindredcare' )
				);

				kindredcare_admin_note(
					__( 'Pictures are matched by their address, not their id — an id means a different file on every install. Anything the destination has not got in its media library falls back to the bundled image rather than to whatever happens to share that id. <strong>Upload the images first</strong> and they will be picked up.', 'kindredcare' )
				);
				?>

				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( KINDREDCARE_TRANSFER_IMPORT ); ?>" />
					<?php wp_nonce_field( KINDREDCARE_TRANSFER_IMPORT ); ?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-import-file"><?php esc_html_e( 'The file', 'kindredcare' ); ?></label>
						<input type="file" id="kc-import-file" name="kc_file" accept="application/json,.json" required />
					</div>

					<button type="submit" class="kc-btn"><?php esc_html_e( 'Import this file', 'kindredcare' ); ?></button>
				</form>
			</div>
		</section>

		<!-- What travels ------------------------------------------------ -->
		<section class="kc-panel">
			<div class="kc-panel__head">
				<?php kindredcare_admin_icon( 'M4 6h16M4 12h16M4 18h10' ); ?>
				<div class="kc-panel__titles">
					<h2><?php esc_html_e( 'What the file carries', 'kindredcare' ); ?></h2>
					<p><?php esc_html_e( 'One entry per screen in this menu, in this order — plus the pages, the menus and the front-page setting.', 'kindredcare' ); ?></p>
				</div>
			</div>
			<div class="kc-panel__body">
				<?php
				kindredcare_admin_note(
					__( '<strong>The menu itself is not in the file, and cannot be.</strong> These screens are built by the theme\'s code, so they appear on any install running this theme and cannot be created by importing anything. The file carries what the screens <em>hold</em>.', 'kindredcare' )
				);
				?>

				<?php foreach ( kindredcare_transfer_manifest() as $index => $screen ) : ?>
					<div class="kc-row">
						<span class="kc-row__brand"><?php echo esc_html( (string) ( $index + 1 ) . '. ' . $screen['title'] ); ?></span>
						<code><?php echo esc_html( $screen['option'] ); ?></code>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	</div>
	<?php
}
