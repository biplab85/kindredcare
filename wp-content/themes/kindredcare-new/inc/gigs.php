<?php
/**
 * Gigs — imported from the platform, then owned here.
 *
 * A gig is a caregiver's own listing: what they will do, for how much, in their
 * own words. It lives on the platform, and this theme imports it rather than
 * rendering it live, for the reasons set out at the top of inc/services-api.php
 * — a page that fetches while it renders is a page whose uptime is the API's.
 *
 * Where the catalogue is one blob of meta on one page, a gig is a post. That is
 * the difference worth having: each one then gets its own editing screen, its
 * own URL, its own revisions, its own featured image, and it can be searched,
 * ordered and filtered by the same machinery as everything else in WordPress.
 *
 * Two endpoints back it, and each is used where it belongs:
 *
 *   /api/public/gigs       fills the whole post type, from the Gigs screen.
 *   /api/public/gigs/{id}  refreshes exactly one, from that gig's own screen.
 *
 * The admin side of this is in inc/admin-gigs.php and inc/admin-gig-edit.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/** The post type. */
const KINDREDCARE_GIG_POST_TYPE = 'gigs';

/** The taxonomy carrying the platform's service category. */
const KINDREDCARE_GIG_TAXONOMY = 'gig_category';

/** Path of the endpoint listing every published gig. */
const KINDREDCARE_GIGS_PATH = '/api/public/gigs';

/** Path of the endpoint for one gig, with %d standing in for its id. */
const KINDREDCARE_GIG_PATH = '/api/public/gigs/%d';

/** Site option holding the Unix time of the last whole-list import. */
const KINDREDCARE_GIGS_IMPORTED_OPTION = 'kindredcare_gigs_imported';

/* -- Meta keys ------------------------------------------------------------- *
 *
 * Underscore-prefixed, so WordPress treats them as protected and they do not
 * appear in the generic Custom Fields box — every one of them has a proper
 * field on the gig's own screen, and two ways to edit the same value that
 * disagree about validation is one way too many.
 */

/** The gig's id on the platform. The link between a post and its endpoint. */
const KINDREDCARE_GIG_META_REMOTE_ID = '_kc_gig_remote_id';

/** What the caregiver will do, as a list of short lines. */
const KINDREDCARE_GIG_META_TASKS = '_kc_gig_tasks';

/** The hourly rate, in cents, exactly as the platform states it. */
const KINDREDCARE_GIG_META_RATE = '_kc_gig_rate_cents';

/** The photograph the platform holds, as an absolute URL. */
const KINDREDCARE_GIG_META_PHOTO = '_kc_gig_photo_url';

/** This site's own copy of that photograph, downloaded at import. */
const KINDREDCARE_GIG_META_PHOTO_ID = '_kc_gig_photo_id';

/** When the caregiver published the gig, as the platform's own timestamp. */
const KINDREDCARE_GIG_META_PUBLISHED = '_kc_gig_published_at';

/** The caregiver behind the gig, as one nested array. */
const KINDREDCARE_GIG_META_CAREGIVER = '_kc_gig_caregiver';

/** Unix time this particular gig last came back from the platform. */
const KINDREDCARE_GIG_META_SYNCED = '_kc_gig_synced_at';

/**
 * The caregiver's id on the platform, kept as a key of its own.
 *
 * It is already inside the caregiver block, but that block is one serialised
 * array and nothing can query into it. Lifting the id out is what makes "the
 * other gigs by this person" a single query rather than a walk over every gig
 * on the site — and a caregiver posting four listings is the normal case here,
 * not the exception.
 */
const KINDREDCARE_GIG_META_CAREGIVER_ID = '_kc_gig_caregiver_id';

/**
 * Set when a sync is what put a gig into draft.
 *
 * The flag is what separates "the platform stopped listing this" from "someone
 * here unpublished it deliberately". Without it, a gig an editor had drafted on
 * purpose would be republished the moment it reappeared in the API, and a gig
 * the platform had withdrawn would be indistinguishable from one being worked
 * on. See kindredcare_gigs_apply().
 */
const KINDREDCARE_GIG_META_WITHDRAWN = '_kc_gig_withdrawn';

/** Most tasks stored against one gig. */
const KINDREDCARE_GIG_MAX_TASKS = 12;

/* -- Registration ---------------------------------------------------------- */

add_action( 'init', 'kindredcare_gigs_register' );
/**
 * Register the post type, its taxonomy and its meta.
 */
function kindredcare_gigs_register(): void {
	register_taxonomy(
		KINDREDCARE_GIG_TAXONOMY,
		KINDREDCARE_GIG_POST_TYPE,
		array(
			'labels'            => array(
				'name'          => __( 'Gig categories', 'kindredcare' ),
				'singular_name' => __( 'Gig category', 'kindredcare' ),
				'menu_name'     => __( 'Categories', 'kindredcare' ),
				'all_items'     => __( 'All categories', 'kindredcare' ),
				'edit_item'     => __( 'Edit category', 'kindredcare' ),
				'view_item'     => __( 'View category', 'kindredcare' ),
				'search_items'  => __( 'Search categories', 'kindredcare' ),
				'not_found'     => __( 'No categories found.', 'kindredcare' ),
			),
			'public'            => true,
			/*
			 * Flat, because the platform's categories are. Rendering them as a
			 * hierarchy would invite someone to nest them here, and the next
			 * import would flatten the nesting back out without saying so.
			 */
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'gig-category' ),
		)
	);

	register_post_type(
		KINDREDCARE_GIG_POST_TYPE,
		array(
			'labels'          => array(
				'name'               => __( 'Gigs', 'kindredcare' ),
				'singular_name'      => __( 'Gig', 'kindredcare' ),
				'menu_name'          => __( 'All gigs', 'kindredcare' ),
				'add_new'            => __( 'Add gig', 'kindredcare' ),
				'add_new_item'       => __( 'Add gig', 'kindredcare' ),
				'edit_item'          => __( 'Edit gig', 'kindredcare' ),
				'new_item'           => __( 'New gig', 'kindredcare' ),
				'view_item'          => __( 'View gig', 'kindredcare' ),
				'view_items'         => __( 'View gigs', 'kindredcare' ),
				'search_items'       => __( 'Search gigs', 'kindredcare' ),
				'not_found'          => __( 'No gigs yet. Import them from the platform.', 'kindredcare' ),
				'not_found_in_trash' => __( 'No gigs in the bin.', 'kindredcare' ),
				'all_items'          => __( 'All gigs', 'kindredcare' ),
			),
			'public'          => true,
			'has_archive'     => true,
			'rewrite'         => array( 'slug' => 'gigs' ),
			'menu_icon'       => 'dashicons-clipboard',
			/*
			 * No menu entry of its own. It belongs under the theme's menu — a
			 * gig is not a different kind of errand from everything else an
			 * editor does here — but naming that menu as the parent is not the
			 * way to get it there: core adds such a post type at whatever point
			 * in the run its own hook fires, which lands "All gigs" second in a
			 * list of fifteen, between Hero and Trust strip.
			 *
			 * inc/admin-gigs.php registers the entries by hand instead, in the
			 * order they should be read in.
			 */
			'show_in_menu'    => false,
			'show_ui'         => true,
			'supports'        => array( 'title', 'editor', 'thumbnail', 'revisions', 'excerpt' ),
			'show_in_rest'    => true,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'taxonomies'      => array( KINDREDCARE_GIG_TAXONOMY ),
		)
	);

	kindredcare_gigs_register_meta();
}

/**
 * Register every gig field with WordPress.
 *
 * Registering rather than just calling update_post_meta() buys the one thing
 * that matters here: the sanitiser is attached to the key, not to the form. A
 * gig written by the importer and a gig written by hand on the editing screen
 * are cleaned by the same function, so there is no path into the database that
 * skips it.
 */
function kindredcare_gigs_register_meta(): void {
	$fields = array(
		KINDREDCARE_GIG_META_REMOTE_ID => array( 'integer', 'absint' ),
		KINDREDCARE_GIG_META_RATE      => array( 'integer', 'absint' ),
		KINDREDCARE_GIG_META_PHOTO_ID  => array( 'integer', 'absint' ),
		KINDREDCARE_GIG_META_CAREGIVER_ID => array( 'integer', 'absint' ),
		KINDREDCARE_GIG_META_SYNCED    => array( 'integer', 'absint' ),
		KINDREDCARE_GIG_META_WITHDRAWN => array( 'boolean', 'kindredcare_gig_sanitize_flag' ),
		KINDREDCARE_GIG_META_PHOTO     => array( 'string', 'esc_url_raw' ),
		KINDREDCARE_GIG_META_PUBLISHED => array( 'string', 'sanitize_text_field' ),
		KINDREDCARE_GIG_META_TASKS     => array( 'array', 'kindredcare_gig_sanitize_tasks' ),
		KINDREDCARE_GIG_META_CAREGIVER => array( 'array', 'kindredcare_gig_sanitize_caregiver' ),
	);

	foreach ( $fields as $key => list( $type, $sanitiser ) ) {
		register_post_meta(
			KINDREDCARE_GIG_POST_TYPE,
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'sanitize_callback' => $sanitiser,
				/*
				 * Not exposed over REST: these are protected keys with a
				 * hand-built screen of their own, and putting them in the API
				 * as well would mean maintaining a second schema that has to
				 * agree with the sanitisers above.
				 */
				'show_in_rest'      => false,
				/*
				 * Untyped on purpose. This file declares strict types and the
				 * filter behind auth_callback is called from several places in
				 * core with a post id that is sometimes a numeric string — a
				 * typed signature would turn that into a fatal error rather
				 * than a permission check.
				 */
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
					return current_user_can( 'edit_post', absint( $post_id ) );
				},
			)
		);
	}
}

/**
 * Keep gigs on the classic editor.
 *
 * Almost nothing about a gig is prose. It is a rate, a list of tasks and a
 * caregiver — structured fields living in a meta box — and the block editor
 * would put a full-height canvas above them for the one paragraph of
 * description, then push everything that matters below the fold.
 *
 * @param bool   $use_block Whether the block editor would be used.
 * @param string $post_type Post type being edited.
 */
function kindredcare_gigs_classic_editor( bool $use_block, string $post_type ): bool {
	return KINDREDCARE_GIG_POST_TYPE === $post_type ? false : $use_block;
}
add_filter( 'use_block_editor_for_post_type', 'kindredcare_gigs_classic_editor', 10, 2 );

/* -- Sanitisers ------------------------------------------------------------ */

/**
 * Reduce anything to a stored boolean.
 *
 * @param mixed $value Raw value.
 */
function kindredcare_gig_sanitize_flag( $value ): bool {
	return (bool) $value;
}

/**
 * Clean the list of tasks.
 *
 * Empty lines are dropped rather than stored: clearing a field is how a line is
 * deleted on the editing screen, and a blank bullet on the front end is a
 * rendering fault nobody can explain from the admin.
 *
 * @param mixed $value Raw value.
 * @return array<int, string>
 */
function kindredcare_gig_sanitize_tasks( $value ): array {
	$tasks = array();

	foreach ( (array) $value as $task ) {
		if ( count( $tasks ) >= KINDREDCARE_GIG_MAX_TASKS ) {
			break;
		}

		if ( is_array( $task ) ) {
			continue;
		}

		$task = sanitize_text_field( (string) $task );

		if ( '' !== $task ) {
			$tasks[] = $task;
		}
	}

	return $tasks;
}

/**
 * Clean the caregiver block.
 *
 * The named fields are the ones this theme renders and are cleaned by type. The
 * two unnamed ones — the verification checks and the reviews — are the
 * platform's own documents whose shape is its business, so they are kept
 * whatever shape they arrive in and cleaned scalar by scalar.
 *
 * @param mixed $value Raw value.
 * @return array<string, mixed>
 */
function kindredcare_gig_sanitize_caregiver( $value ): array {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$lists = array();
	foreach ( array( 'languages', 'interests', 'personality_tags' ) as $key ) {
		$lists[ $key ] = array();

		foreach ( (array) ( $value[ $key ] ?? array() ) as $item ) {
			if ( is_array( $item ) ) {
				continue;
			}

			$item = sanitize_text_field( (string) $item );

			if ( '' !== $item ) {
				$lists[ $key ][] = $item;
			}
		}
	}

	return array(
		'user_id'             => absint( $value['user_id'] ?? 0 ),
		'display_name'        => sanitize_text_field( (string) ( $value['display_name'] ?? '' ) ),
		'photo_url'           => esc_url_raw( (string) ( $value['photo_url'] ?? '' ) ),
		// This site's own copy of the portrait. See inc/media.php.
		'photo_id'            => absint( $value['photo_id'] ?? 0 ),
		'bio'                 => sanitize_textarea_field( (string) ( $value['bio'] ?? '' ) ),
		'years_of_experience' => absint( $value['years_of_experience'] ?? 0 ),
		'languages'           => $lists['languages'],
		'interests'           => $lists['interests'],
		'personality_tags'    => $lists['personality_tags'],
		'is_verified'         => ! empty( $value['is_verified'] ),
		'verification_checks' => (array) kindredcare_api_sanitize_deep( (array) ( $value['verification_checks'] ?? array() ) ),
		/*
		 * Stored as the platform sends them: a rating with no count is a number
		 * with no weight behind it, and null is a different statement from 0.
		 */
		'rating_average'      => null === ( $value['rating_average'] ?? null ) ? null : round( (float) $value['rating_average'], 2 ),
		'rating_count'        => absint( $value['rating_count'] ?? 0 ),
		'reviews'             => (array) kindredcare_api_sanitize_deep( (array) ( $value['reviews'] ?? array() ) ),
	);
}

/* -- Reading --------------------------------------------------------------- */

/**
 * The caregiver stored against a gig, with every key present.
 *
 * Filled out from defaults so a template can read a field without testing for
 * it first — a gig imported before a field existed simply reads as empty.
 *
 * @param int $post_id Gig.
 * @return array<string, mixed>
 */
function kindredcare_gig_caregiver( int $post_id ): array {
	$stored = get_post_meta( $post_id, KINDREDCARE_GIG_META_CAREGIVER, true );

	return kindredcare_gig_sanitize_caregiver( is_array( $stored ) ? $stored : array() );
}

/**
 * The tasks stored against a gig.
 *
 * @param int $post_id Gig.
 * @return array<int, string>
 */
function kindredcare_gig_tasks( int $post_id ): array {
	$stored = get_post_meta( $post_id, KINDREDCARE_GIG_META_TASKS, true );

	return is_array( $stored ) ? array_values( $stored ) : array();
}

/**
 * A gig's id on the platform, or 0 for one created by hand here.
 *
 * @param int $post_id Gig.
 */
function kindredcare_gig_remote_id( int $post_id ): int {
	return absint( get_post_meta( $post_id, KINDREDCARE_GIG_META_REMOTE_ID, true ) );
}

/**
 * The URL that refreshes one gig.
 *
 * @param int $remote_id The gig's id on the platform.
 */
function kindredcare_gig_endpoint( int $remote_id ): string {
	return kindredcare_api_url( sprintf( KINDREDCARE_GIG_PATH, $remote_id ) );
}

/**
 * An hourly rate, written the way a price is written.
 *
 * Whole dollars lose the ".00": "$22 an hour" is how the rate would be said out
 * loud, and "$22.00" reads like a form field rather than a price. A rate that
 * is not whole keeps both decimals.
 *
 * @param int $cents Rate in cents.
 */
function kindredcare_gig_rate( int $cents ): string {
	if ( $cents <= 0 ) {
		return '';
	}

	$amount = $cents % 100 === 0
		? number_format_i18n( intdiv( $cents, 100 ) )
		: number_format_i18n( $cents / 100, 2 );

	/* translators: %s: formatted amount, e.g. 22 or 22.50. */
	return sprintf( __( '$%s', 'kindredcare' ), $amount );
}

/**
 * The post holding a given platform gig, if there is one.
 *
 * Matched on the remote id, which is what makes a second import an update
 * rather than a duplicate. Drafts and gigs in the bin are included on purpose:
 * a withdrawn gig that comes back should reclaim its own post, its own URL and
 * its own history rather than arriving as a second copy.
 *
 * @param int $remote_id The gig's id on the platform.
 * @return int Post id, or 0 if this gig has never been imported.
 */
function kindredcare_gigs_find( int $remote_id ): int {
	if ( ! $remote_id ) {
		return 0;
	}

	$found = get_posts(
		array(
			'post_type'        => KINDREDCARE_GIG_POST_TYPE,
			'post_status'      => 'any',
			'numberposts'      => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- indexed lookup by a key this theme owns; there is no other way to find a post by its remote id.
				array(
					'key'   => KINDREDCARE_GIG_META_REMOTE_ID,
					'value' => $remote_id,
				),
			),
		)
	);

	return $found ? absint( $found[0] ) : 0;
}

/**
 * Every imported gig, whatever its status.
 *
 * @return array<int, int> Post ids, newest first.
 */
function kindredcare_gigs_all(): array {
	return array_map(
		'absint',
		get_posts(
			array(
				'post_type'        => KINDREDCARE_GIG_POST_TYPE,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => -1,
				'fields'           => 'ids',
				'orderby'          => 'title',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		)
	);
}

/* -- Pictures -------------------------------------------------------------- */

/**
 * The attachment a gig should show, in order of who chose it.
 *
 *   1. The featured image. Someone here picked it deliberately, and nothing an
 *      import does may override a decision made on this site.
 *   2. The copy downloaded from the platform at import.
 *   3. Nothing — the caller decides what an absent picture looks like.
 *
 * The remote URL is not in that list. It is kept in meta as a record of where
 * the local copy came from, not as a thing to print: printing it is exactly the
 * hotlink this import exists to avoid.
 *
 * @param int $post_id Gig.
 * @return int Attachment id, or 0.
 */
function kindredcare_gig_image_id( int $post_id ): int {
	$featured = absint( get_post_thumbnail_id( $post_id ) );

	if ( $featured && wp_attachment_is_image( $featured ) ) {
		return $featured;
	}

	$imported = absint( get_post_meta( $post_id, KINDREDCARE_GIG_META_PHOTO_ID, true ) );

	return $imported && wp_attachment_is_image( $imported ) ? $imported : 0;
}

/**
 * The attachment holding the caregiver's portrait.
 *
 * No featured-image equivalent: the portrait belongs to the person, not to the
 * listing, and every gig of theirs shows the same one.
 *
 * @param int $post_id Gig.
 * @return int Attachment id, or 0.
 */
function kindredcare_gig_portrait_id( int $post_id ): int {
	$portrait = absint( kindredcare_gig_caregiver( $post_id )['photo_id'] ?? 0 );

	return $portrait && wp_attachment_is_image( $portrait ) ? $portrait : 0;
}

/**
 * Whether every picture this gig names has actually been downloaded.
 *
 * Used on the editing screen to say so plainly. A gig whose photograph is still
 * only a URL on the platform is a gig that will show nothing once the platform
 * is unreachable, and that is worth being told before it happens rather than
 * after.
 *
 * @param int $post_id Gig.
 */
function kindredcare_gig_images_pending( int $post_id ): bool {
	$photo    = (string) get_post_meta( $post_id, KINDREDCARE_GIG_META_PHOTO, true );
	$portrait = (string) ( kindredcare_gig_caregiver( $post_id )['photo_url'] ?? '' );

	if ( '' !== $photo && ! absint( get_post_meta( $post_id, KINDREDCARE_GIG_META_PHOTO_ID, true ) ) ) {
		return true;
	}

	return '' !== $portrait && ! kindredcare_gig_portrait_id( $post_id );
}

/* -- Fetching -------------------------------------------------------------- */

/**
 * Ask the platform for every gig.
 *
 * @return array<int, array<string, mixed>>|WP_Error Normalised gigs, or why not.
 */
function kindredcare_gigs_fetch_all() {
	$rows = kindredcare_api_get( kindredcare_api_url( KINDREDCARE_GIGS_PATH ) );

	if ( is_wp_error( $rows ) ) {
		return $rows;
	}

	$gigs = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$gig = kindredcare_gigs_normalise( $row );

		if ( null !== $gig ) {
			$gigs[] = $gig;
		}
	}

	if ( ! $gigs ) {
		return new WP_Error(
			'kindredcare_gigs_empty',
			__( 'The platform returned no gigs.', 'kindredcare' )
		);
	}

	return $gigs;
}

/**
 * Ask the platform for one gig.
 *
 * @param int $remote_id The gig's id on the platform.
 * @return array<string, mixed>|WP_Error Normalised gig, or why not.
 */
function kindredcare_gigs_fetch_one( int $remote_id ) {
	if ( ! $remote_id ) {
		return new WP_Error(
			'kindredcare_gigs_no_id',
			__( 'This gig has no id on the platform, so there is nothing to refresh it from.', 'kindredcare' )
		);
	}

	$row = kindredcare_api_get( kindredcare_gig_endpoint( $remote_id ) );

	if ( is_wp_error( $row ) ) {
		return $row;
	}

	$gig = kindredcare_gigs_normalise( $row );

	if ( null === $gig ) {
		return new WP_Error(
			'kindredcare_gigs_shape',
			__( 'The platform’s answer did not describe a gig.', 'kindredcare' )
		);
	}

	return $gig;
}

/**
 * Reduce one API row to the fields this theme stores, sanitised.
 *
 * Nothing from the endpoint is stored unfiltered. It is our own API, but it is
 * still a remote document arriving over plain HTTP, and post meta written from
 * one is post meta a template will later print.
 *
 * @param array<string, mixed> $row Raw row.
 * @return array<string, mixed>|null Null when the row cannot be a gig.
 */
function kindredcare_gigs_normalise( array $row ): ?array {
	$remote_id = absint( $row['id'] ?? 0 );
	$title     = sanitize_text_field( (string) ( $row['title'] ?? '' ) );

	/*
	 * Both are required, and for different reasons: without an id there is no
	 * way to match this row to a post on the next import, and without a title
	 * there is nothing to put in the list. Either way the row is skipped rather
	 * than half-imported into something an editor would have to clean up.
	 */
	if ( ! $remote_id || '' === $title ) {
		return null;
	}

	$category = is_array( $row['service_category'] ?? null ) ? $row['service_category'] : array();

	$caregiver             = is_array( $row['caregiver'] ?? null ) ? $row['caregiver'] : array();
	$rating                = is_array( $caregiver['rating'] ?? null ) ? $caregiver['rating'] : array();
	$caregiver['rating_average'] = $rating['average'] ?? null;
	$caregiver['rating_count']   = $rating['count'] ?? 0;

	return array(
		'remote_id'    => $remote_id,
		'title'        => $title,
		'description'  => sanitize_textarea_field( (string) ( $row['description'] ?? '' ) ),
		'tasks'        => kindredcare_gig_sanitize_tasks( $row['tasks_included'] ?? array() ),
		/*
		 * Cents only. The endpoint also sends hourly_rate_dollars, which is the
		 * same number said twice — and two stored copies of one price is two
		 * numbers that can disagree. The dollars are worked out at render.
		 */
		'rate_cents'   => absint( $row['hourly_rate_cents'] ?? 0 ),
		'photo_url'    => esc_url_raw( (string) ( $row['photo_url'] ?? '' ) ),
		'published_at' => sanitize_text_field( (string) ( $row['published_at'] ?? '' ) ),
		'category'     => array(
			'remote_id'     => absint( $category['id'] ?? 0 ),
			'name'          => sanitize_text_field( (string) ( $category['name'] ?? '' ) ),
			'slug'          => sanitize_title( (string) ( $category['slug'] ?? ( $category['name'] ?? '' ) ) ),
			'description'   => sanitize_text_field( (string) ( $category['description'] ?? '' ) ),
			'icon'          => sanitize_text_field( (string) ( $category['icon'] ?? '' ) ),
			'tier'          => sanitize_key( (string) ( $category['tier_required'] ?? '' ) ),
			'example_tasks' => kindredcare_gig_sanitize_tasks( $category['example_tasks'] ?? array() ),
			'default_tasks' => kindredcare_gig_sanitize_tasks( $category['default_tasks'] ?? array() ),
			'order'         => absint( $category['sort_order'] ?? 0 ),
		),
		'caregiver'    => kindredcare_gig_sanitize_caregiver( $caregiver ),
	);
}

/* -- Writing --------------------------------------------------------------- */

/**
 * Write one normalised gig to the database, creating the post if need be.
 *
 * The rule, decided once so both callers behave the same way: everything the
 * platform owns is overwritten, everything it does not is left alone. The
 * platform owns the wording, the rate, the tasks, the caregiver and the
 * category. It does not own the featured image, the slug once WordPress has
 * assigned one, or the decision that an editor made to unpublish something.
 *
 * @param array<string, mixed> $gig Normalised gig from kindredcare_gigs_normalise().
 * @return array{post_id: int, created: bool, downloaded: int, pending: int}|WP_Error
 */
function kindredcare_gigs_apply( array $gig ) {
	$remote_id = absint( $gig['remote_id'] ?? 0 );
	$post_id   = kindredcare_gigs_find( $remote_id );
	$created   = 0 === $post_id;

	$postarr = array(
		'post_type'    => KINDREDCARE_GIG_POST_TYPE,
		'post_title'   => (string) $gig['title'],
		'post_content' => (string) $gig['description'],
	);

	if ( $created ) {
		$postarr['post_status'] = 'publish';

		$post_id = wp_insert_post( $postarr, true );
	} else {
		$postarr['ID'] = $post_id;

		/*
		 * A gig this theme drafted because the platform had stopped listing it
		 * is published again the moment it comes back. A gig someone here
		 * unpublished by hand carries no such flag and keeps whatever status
		 * they chose — an import is not an argument about editorial decisions.
		 */
		if ( get_post_meta( $post_id, KINDREDCARE_GIG_META_WITHDRAWN, true ) ) {
			$postarr['post_status'] = 'publish';
		}

		/*
		 * Reviving something from the bin has to be explicit: wp_update_post()
		 * on a trashed post would otherwise leave it trashed with fresh content
		 * nobody can see.
		 */
		if ( 'trash' === get_post_status( $post_id ) ) {
			$postarr['post_status'] = 'publish';
		}

		$post_id = wp_update_post( $postarr, true );
	}

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$post_id = absint( $post_id );

	$caregiver = (array) $gig['caregiver'];

	/*
	 * Both pictures are pulled into the media library rather than linked to.
	 * The reasoning is in inc/media.php; the short version is that a remote
	 * <img> makes every visitor fetch from the platform on every view, which is
	 * the one thing this whole import exists to avoid.
	 *
	 * The gig's photograph and the caregiver's portrait are usually the same
	 * file — the platform does not yet hold a separate picture per listing — so
	 * the second call almost always finds what the first one downloaded.
	 */
	$photo_url    = (string) $gig['photo_url'];
	$portrait_url = (string) ( $caregiver['photo_url'] ?? '' );
	$name         = (string) ( $caregiver['display_name'] ?? '' );

	/*
	 * A picture is described by what it depicts, and when the two URLs are the
	 * same the gig's "photograph" is the caregiver's face. Naming it after the
	 * listing would put a filename and an alt text on it that describe an
	 * errand — and since one file is shared by every gig that caregiver posts,
	 * it would describe whichever of those errands happened to import first.
	 */
	$photo = kindredcare_media_import(
		$photo_url,
		$photo_url === $portrait_url && '' !== $name ? $name : (string) $gig['title']
	);

	$portrait = kindredcare_media_import( $portrait_url, $name );

	/*
	 * A picture that could not be fetched leaves whatever is already stored
	 * alone rather than clearing it. Losing a good local copy to one failed
	 * request would mean an import made the page worse.
	 */
	if ( $photo['id'] ) {
		update_post_meta( $post_id, KINDREDCARE_GIG_META_PHOTO_ID, $photo['id'] );
	}

	$caregiver['photo_id'] = $portrait['id']
		? $portrait['id']
		: absint( kindredcare_gig_caregiver( $post_id )['photo_id'] ?? 0 );

	update_post_meta( $post_id, KINDREDCARE_GIG_META_REMOTE_ID, $remote_id );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_TASKS, $gig['tasks'] );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_RATE, absint( $gig['rate_cents'] ) );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_PHOTO, $gig['photo_url'] );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_PUBLISHED, $gig['published_at'] );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_CAREGIVER, $caregiver );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_CAREGIVER_ID, absint( $caregiver['user_id'] ?? 0 ) );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_SYNCED, time() );

	delete_post_meta( $post_id, KINDREDCARE_GIG_META_WITHDRAWN );

	kindredcare_gigs_apply_category( $post_id, (array) $gig['category'] );

	return array(
		'post_id'    => $post_id,
		'created'    => $created,
		'downloaded' => (int) $photo['downloaded'] + (int) $portrait['downloaded'],
		'pending'    => (int) $photo['pending'] + (int) $portrait['pending'],
	);
}

/**
 * Put a gig in its category, creating the term the first time it is seen.
 *
 * The category's own fields are kept on the term rather than repeated on every
 * gig that belongs to it. Fifteen gigs in five categories would otherwise mean
 * fifteen copies of five descriptions, and a category renamed on the platform
 * would be renamed in some of them and not others depending on which gigs got
 * re-fetched.
 *
 * @param int                  $post_id  Gig.
 * @param array<string, mixed> $category Normalised category block.
 */
function kindredcare_gigs_apply_category( int $post_id, array $category ): void {
	$slug = (string) ( $category['slug'] ?? '' );
	$name = (string) ( $category['name'] ?? '' );

	if ( '' === $slug || '' === $name ) {
		wp_set_object_terms( $post_id, array(), KINDREDCARE_GIG_TAXONOMY );

		return;
	}

	$term = get_term_by( 'slug', $slug, KINDREDCARE_GIG_TAXONOMY );

	if ( $term instanceof WP_Term ) {
		$term_id = (int) $term->term_id;

		if ( $term->name !== $name || $term->description !== (string) $category['description'] ) {
			wp_update_term(
				$term_id,
				KINDREDCARE_GIG_TAXONOMY,
				array(
					'name'        => $name,
					'description' => (string) $category['description'],
				)
			);
		}
	} else {
		$created = wp_insert_term(
			$name,
			KINDREDCARE_GIG_TAXONOMY,
			array(
				'slug'        => $slug,
				'description' => (string) $category['description'],
			)
		);

		if ( is_wp_error( $created ) ) {
			return;
		}

		$term_id = absint( $created['term_id'] );
	}

	update_term_meta( $term_id, '_kc_category_remote_id', absint( $category['remote_id'] ?? 0 ) );
	update_term_meta( $term_id, '_kc_category_icon', (string) ( $category['icon'] ?? '' ) );
	update_term_meta( $term_id, '_kc_category_tier', (string) ( $category['tier'] ?? '' ) );
	update_term_meta( $term_id, '_kc_category_example_tasks', (array) ( $category['example_tasks'] ?? array() ) );
	update_term_meta( $term_id, '_kc_category_default_tasks', (array) ( $category['default_tasks'] ?? array() ) );
	update_term_meta( $term_id, '_kc_category_order', absint( $category['order'] ?? 0 ) );

	wp_set_object_terms( $post_id, array( $term_id ), KINDREDCARE_GIG_TAXONOMY );
}

/**
 * Import every gig the platform is listing.
 *
 * Two halves. The first walks what came back and writes it, creating what is
 * new and updating what is not. The second deals with the gigs that did not
 * come back at all — those are set to draft, never deleted, because a caregiver
 * pausing a listing for a fortnight should not cost this site the post, its
 * URL, its featured image or its history.
 *
 * @return array{created: int, updated: int, withdrawn: int, failed: int, downloaded: int, pending: int}|WP_Error
 */
function kindredcare_gigs_import() {
	$gigs = kindredcare_gigs_fetch_all();

	if ( is_wp_error( $gigs ) ) {
		return $gigs;
	}

	$result = array(
		'created'    => 0,
		'updated'    => 0,
		'withdrawn'  => 0,
		'failed'     => 0,
		'downloaded' => 0,
		'pending'    => 0,
	);

	$seen = array();

	foreach ( $gigs as $gig ) {
		$applied = kindredcare_gigs_apply( $gig );

		if ( is_wp_error( $applied ) ) {
			++$result['failed'];

			continue;
		}

		$seen[] = $applied['post_id'];

		$result['downloaded'] += $applied['downloaded'];
		$result['pending']    += $applied['pending'];

		if ( $applied['created'] ) {
			++$result['created'];
		} else {
			++$result['updated'];
		}
	}

	$result['withdrawn'] = kindredcare_gigs_withdraw_missing( $seen );

	update_option( KINDREDCARE_GIGS_IMPORTED_OPTION, time(), false );

	return $result;
}

/**
 * Draft the published gigs the platform no longer lists.
 *
 * Only published ones are touched. A gig already in draft is either one this
 * has drafted before — nothing to do — or one an editor is working on, and
 * re-drafting a draft would move its flag and later republish something they
 * had unpublished on purpose.
 *
 * @param array<int, int> $seen Post ids that came back in this import.
 * @return int How many were drafted.
 */
function kindredcare_gigs_withdraw_missing( array $seen ): int {
	$stale = get_posts(
		array(
			'post_type'        => KINDREDCARE_GIG_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'post__not_in'     => $seen,
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- runs once per manual import, not on any render.
				array(
					'key'     => KINDREDCARE_GIG_META_REMOTE_ID,
					'compare' => 'EXISTS',
				),
			),
		)
	);

	foreach ( $stale as $post_id ) {
		wp_update_post(
			array(
				'ID'          => absint( $post_id ),
				'post_status' => 'draft',
			)
		);

		update_post_meta( absint( $post_id ), KINDREDCARE_GIG_META_WITHDRAWN, true );
	}

	return count( $stale );
}

/**
 * When the whole list was last imported.
 *
 * @return int Unix timestamp, or 0 if it never has been.
 */
function kindredcare_gigs_imported_at(): int {
	return absint( get_option( KINDREDCARE_GIGS_IMPORTED_OPTION, 0 ) );
}

/* -- Reading, for the templates -------------------------------------------- */

/**
 * The lowest, or highest, hourly rate anyone is currently asking.
 *
 * Printed as a range at the top of the archive, which is a fact about the whole
 * list that no single row can state — and the frame a reader needs before the
 * ladder of prices below means anything. "Can I afford this at all" comes
 * before "which of these do I want".
 *
 * @param string $end 'low' or 'high'.
 * @return int Rate in cents, or 0 when there is nothing published.
 */
function kindredcare_gigs_rate_edge( string $end = 'low' ): int {
	$cheapest = get_posts(
		array(
			'post_type'        => KINDREDCARE_GIG_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => 1,
			'fields'           => 'ids',
			'meta_key'         => KINDREDCARE_GIG_META_RATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- ordering by a numeric key this theme owns; the alternative is loading every gig to find one number.
			'orderby'          => 'meta_value_num',
			'order'            => 'high' === $end ? 'DESC' : 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- excludes gigs with no rate, which would otherwise sort to the front as zero.
				array(
					'key'     => KINDREDCARE_GIG_META_RATE,
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	return $cheapest ? absint( get_post_meta( absint( $cheapest[0] ), KINDREDCARE_GIG_META_RATE, true ) ) : 0;
}

/**
 * The other gigs this caregiver has posted.
 *
 * Shown at the foot of a single gig. Three of the four caregivers in the
 * current data have posted several listings, so someone who likes the person
 * but not this particular errand has somewhere to go — and that is a more
 * useful "related" list than gigs in the same category by strangers.
 *
 * @param int $post_id Gig being read.
 * @param int $limit   Most to return.
 * @return array<int, int> Post ids.
 */
function kindredcare_gig_sibling_gigs( int $post_id, int $limit = 3 ): array {
	$caregiver = absint( get_post_meta( $post_id, KINDREDCARE_GIG_META_CAREGIVER_ID, true ) );

	if ( ! $caregiver ) {
		return array();
	}

	return array_map(
		'absint',
		get_posts(
			array(
				'post_type'        => KINDREDCARE_GIG_POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => $limit,
				'fields'           => 'ids',
				'post__not_in'     => array( $post_id ),
				'orderby'          => 'title',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the reason KINDREDCARE_GIG_META_CAREGIVER_ID exists as a key of its own.
					array(
						'key'   => KINDREDCARE_GIG_META_CAREGIVER_ID,
						'value' => $caregiver,
					),
				),
			)
		)
	);
}

/**
 * Where "Hire Me" goes.
 *
 * The platform, not this site. Booking a caregiver happens there, and the
 * header's Login button already points at the same address.
 *
 * A function rather than a constant so a child theme or a filter can move it
 * without editing four templates, and so the address is written down once.
 */
function kindredcare_gig_hire_url(): string {
	return (string) apply_filters( 'kindredcare_gig_hire_url', 'https://app.kindredcare.ca/login' );
}

/**
 * Where the contact buttons on the gig screens go.
 *
 * Deliberately not kindredcare_contact_url(), which resolves to whichever page
 * this install has set as its contact page. These buttons go to the live site's
 * contact page, which is a statement about the business rather than about this
 * WordPress install's page tree.
 */
function kindredcare_gig_contact_url(): string {
	return (string) apply_filters( 'kindredcare_gig_contact_url', 'https://kindredcare.ca/contact-us/' );
}

/**
 * The human name for a verification check.
 *
 * The platform sends keys — "cpic", "aml". Printed raw against a caregiver's
 * name they read as jargon at best and as an accusation at worst; "aml" means
 * nothing to a family and "Criminal record check" means everything. An unknown
 * key falls through to a title-cased version rather than being dropped, so a
 * check added on the platform still appears.
 *
 * @param string $type Check type key.
 */
function kindredcare_gig_check_label( string $type ): string {
	$labels = array(
		'identity'  => __( 'Identity confirmed', 'kindredcare' ),
		'cpic'      => __( 'Criminal record check', 'kindredcare' ),
		'aml'       => __( 'Financial screening', 'kindredcare' ),
		'reference' => __( 'References contacted', 'kindredcare' ),
		'vsc'       => __( 'Vulnerable sector check', 'kindredcare' ),
	);

	if ( isset( $labels[ $type ] ) ) {
		return $labels[ $type ];
	}

	return '' === $type ? '' : ucfirst( str_replace( array( '-', '_' ), ' ', $type ) );
}

/**
 * Where a check has got to, in words a visitor can read.
 *
 * The template used to print ucfirst() of the platform's own status key, which
 * put "Not_started" on the page of every caregiver who has not been through
 * verification yet — a database value with its underscore still in it, sitting
 * in the one part of this card that is supposed to inspire confidence.
 *
 * Same shape as kindredcare_gig_check_label() above, and for the same reason:
 * a known key gets a translated phrase, an unknown one falls through to a
 * humanised version rather than being dropped, so a status the platform adds
 * later still reads as English.
 *
 * "cleared" is absent deliberately — a cleared check prints the date it
 * cleared, which is the more useful fact and the one the record exists to show.
 *
 * @param string $status Status key as the platform sends it.
 * @return string Human-readable status, or an empty string for an empty key.
 */
function kindredcare_gig_check_status( string $status ): string {
	$labels = array(
		'not_started' => __( 'Not started', 'kindredcare' ),
		'pending'     => __( 'Pending', 'kindredcare' ),
		'in_progress' => __( 'In progress', 'kindredcare' ),
		'submitted'   => __( 'Submitted', 'kindredcare' ),
		'expired'     => __( 'Expired', 'kindredcare' ),
		'failed'      => __( 'Not cleared', 'kindredcare' ),
	);

	if ( isset( $labels[ $status ] ) ) {
		return $labels[ $status ];
	}

	return '' === $status ? '' : ucfirst( str_replace( array( '-', '_' ), ' ', $status ) );
}

/**
 * A date from the platform, written the way this site writes dates.
 *
 * @param string $iso Timestamp as the platform sends it.
 * @return string Formatted date, or an empty string if it could not be read.
 */
function kindredcare_gig_date( string $iso ): string {
	$time = strtotime( $iso );

	return $time ? (string) wp_date( (string) get_option( 'date_format' ), $time ) : '';
}

/**
 * Every category that currently has a published gig in it.
 *
 * @return array<int, WP_Term>
 */
function kindredcare_gig_categories(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => KINDREDCARE_GIG_TAXONOMY,
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);

	return is_array( $terms ) ? $terms : array();
}

/**
 * The mark beside a category in the filter.
 *
 * Keyed on the slug rather than the name, because the name is editable in
 * wp-admin and the slug is what the platform imports against — renaming
 * "Tech Help" to "Technology Help" should not silently drop its icon.
 *
 * Every category the platform currently sends is here; anything it adds later
 * falls back to the tag, which is honest about being a category rather than
 * guessing at a picture for a word this theme has not seen. That fallback is
 * the reason this is a map with a default and not a match the caller has to
 * get right.
 *
 * Drawn from the same set as the rest of the theme — 24px box, no fill, 2px
 * round-capped stroke in currentColor — so an icon here sits at the same weight
 * as the arrow on a listing row.
 *
 * @param string $slug Term slug. An empty string is the "everything" link,
 *                     which is not a term and so has no slug of its own.
 * @return array<int, string> SVG path `d` values, drawn in order.
 */
function kindredcare_gig_category_icon_paths( string $slug = '' ): array {
	$icons = array(
		// The archive link: layers, for the whole set rather than one of them.
		''                  => array(
			'M12 2 2 7l10 5 10-5-10-5Z',
			'm2 17 10 5 10-5',
			'm2 12 10 5 10-5',
		),
		'companionship'     => array(
			'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2',
			'M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
			'M22 21v-2a4 4 0 0 0-3-3.87',
			'M16 3.13a4 4 0 0 1 0 7.75',
		),
		'errands-shopping'  => array(
			'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z',
			'M3 6h18',
			'M16 10a4 4 0 0 1-8 0',
		),
		'gardening'         => array(
			'M7 20h10',
			'M10 20c5.5-2.5.8-6.4 3-10',
			'M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8Z',
			'M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2Z',
		),
		'meal-preparation'  => array(
			'M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2',
			'M7 2v20',
			'M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7',
		),
		'tech-help'         => array(
			'M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2Z',
			'M12 18h.01',
		),
		'walking-companion' => array(
			'M4 16v-2.38C4 11.5 2.97 10.5 3 8c.03-2.72 1.49-6 4.5-6C9.37 2 10 3.8 10 5.5c0 3.11-2 5.66-2 8.68V16a2 2 0 1 1-4 0Z',
			'M20 20v-2.38c0-1.12 1.03-2.12 1-4.62-.03-2.72-1.49-6-4.5-6C14.63 7 14 8.8 14 10.5c0 3.11 2 5.66 2 8.68V20a2 2 0 1 0 4 0Z',
			'M16 17h4',
			'M4 13h4',
		),
	);

	if ( isset( $icons[ $slug ] ) ) {
		return $icons[ $slug ];
	}

	// A tag: this is a category, and the theme has not been told which one.
	return array(
		'M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z',
		'M7.5 7.5h.01',
	);
}

/**
 * Print a category's icon.
 *
 * Always decorative and always aria-hidden. Wherever this is used the category
 * is either already named in text beside it, or — on a listing row, where the
 * rate column carries the mark on its own — it is a repeat of the kicker above
 * the title. Neither case wants a screen reader to say it a second time.
 *
 * The wrapper class is a parameter because the icon appears in two unrelated
 * objects: a filter chip and the rate column of a listing row. They size and
 * colour it differently and neither should have to inherit the other's rules,
 * so each names its own element rather than sharing one class that then needs
 * unpicking in context.
 *
 * @param string $slug  Term slug, or an empty string for the "everything" link.
 * @param string $class Wrapper class. Defaults to the filter chip's.
 */
function kindredcare_gig_category_icon( string $slug = '', string $class = 'c-chip__icon' ): void {
	?>
	<span class="<?php echo esc_attr( $class ); ?>" aria-hidden="true">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
			<?php foreach ( kindredcare_gig_category_icon_paths( $slug ) as $kc_path ) : ?>
				<path d="<?php echo esc_attr( $kc_path ); ?>" />
			<?php endforeach; ?>
		</svg>
	</span>
	<?php
}

add_action( 'pre_get_posts', 'kindredcare_gigs_archive_order' );
/**
 * Order the listing archives by rate, cheapest first.
 *
 * Not the newest first, which is the default and which here would be noise —
 * the date a caregiver happened to post is not a fact anyone is choosing on.
 * Ordering by rate is what makes the archive's whole layout mean something: the
 * column of prices down the left edge becomes a ladder that can be read, rather
 * than fifteen numbers in the order they arrived.
 *
 * It also breaks up the page. Each caregiver on the platform charges one rate
 * across everything they post, so date order clusters all four of a person's
 * listings together and the reader meets the same name, price and badge four
 * times running.
 *
 * Cheapest first rather than dearest: the question someone brings to this page
 * is what it costs to start, and the answer to that is at the top.
 *
 * @param WP_Query $query The query about to run.
 */
function kindredcare_gigs_archive_order( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if (
		! $query->is_post_type_archive( KINDREDCARE_GIG_POST_TYPE )
		&& ! $query->is_tax( KINDREDCARE_GIG_TAXONOMY )
	) {
		return;
	}

	$query->set( 'meta_key', KINDREDCARE_GIG_META_RATE );
	$query->set( 'orderby', array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ) );

	/*
	 * Enough that the current catalogue is one page. A rate card that paginates
	 * at ten cannot be compared, which is the only thing it is for.
	 */
	$query->set( 'posts_per_page', 24 );
}

add_filter( 'wp_nav_menu_objects', 'kindredcare_gigs_menu_current' );
/**
 * Light the Gigs menu item on one gig and on a gig category.
 *
 * The menu item is a custom link to the archive, which WordPress only matches
 * on the archive itself. Elsewhere in the gigs section it is marked as an
 * ancestor: KindredCare_Header_Walker underlines it without adding
 * aria-current, because the archive is not the page being read.
 *
 * @param array<int, WP_Post> $items Menu items.
 * @return array<int, WP_Post> Filtered items.
 */
function kindredcare_gigs_menu_current( array $items ): array {
	if ( ! is_singular( KINDREDCARE_GIG_POST_TYPE ) && ! is_tax( KINDREDCARE_GIG_TAXONOMY ) ) {
		return $items;
	}

	$archive = get_post_type_archive_link( KINDREDCARE_GIG_POST_TYPE );

	if ( ! $archive ) {
		return $items;
	}

	$target = untrailingslashit( $archive );

	foreach ( $items as $item ) {
		if ( untrailingslashit( (string) $item->url ) === $target ) {
			$item->classes = array_merge( (array) $item->classes, array( 'current-menu-ancestor' ) );
		}
	}

	return $items;
}
