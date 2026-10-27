<?php
/**
 * The service catalogue — imported from the platform API, then owned here.
 *
 * The list of bookable services is the platform's data, so it is imported
 * rather than typed in. But it is imported *on demand*, not on render: an
 * editor presses "Fetch from the platform" on the Services page and the result
 * is written to that page's post meta. Every visitor after that is served from
 * the database like any other page content.
 *
 * That is the whole point of the design. A page that fetches while it renders
 * is a page whose uptime is the API's uptime, whose speed is the API's speed,
 * and whose content changes without anyone deciding it should. Importing once
 * gives up automatic freshness — someone has to press the button after the
 * platform changes — and gets back a page that cannot break, cannot slow down,
 * and cannot surprise anyone.
 *
 * It also makes the catalogue editable, which fetching every time cannot. Each
 * service carries one field the API does not have and never will: a photograph,
 * chosen from the media library. A re-fetch deliberately preserves it.
 *
 * The admin side of this — the button, the list, the image pickers — lives in
 * inc/admin-services-catalogue.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * The path the catalogue is read from.
 *
 * Only the path. The address it hangs off is the site-wide one in inc/api.php,
 * set once at KindredCare -> Gigs, so moving the platform moves the catalogue
 * and the gigs together instead of one at a time.
 */
const KINDREDCARE_SERVICES_PATH = '/api/service-categories';

/**
 * The template file that marks a page as the Services page.
 *
 * Identified by template rather than by slug, for the same reason the contact
 * page is: the page can then be renamed without the catalogue box vanishing
 * from its editing screen.
 */
const KINDREDCARE_SERVICES_TEMPLATE = 'template-services.php';

/** Post meta holding the imported catalogue. */
const KINDREDCARE_SERVICES_META = '_kindredcare_services';

/** Post meta holding the Unix time of the last successful import. */
const KINDREDCARE_SERVICES_META_TIME = '_kindredcare_services_fetched';

/** Most tasks stored against one service. */
const KINDREDCARE_SERVICES_MAX_TASKS = 4;

/**
 * The picture a service shows until someone chooses one for it.
 *
 * Every card carries a photograph, so every card needs one from the moment the
 * catalogue is first imported — a grid where some cards have an image and some
 * have a grey box does not look like a page waiting to be filled in, it looks
 * like a broken page. This is the bundled fallback: on-brand, generic enough to
 * sit under any of the eight, and replaced service by service from the editing
 * screen whenever there is a better one.
 */
const KINDREDCARE_SERVICES_DEFAULT_IMAGE = 'assets/img/home-care.webp';

/** Intrinsic size of the bundled default, so it reserves its space on load. */
const KINDREDCARE_SERVICES_DEFAULT_IMAGE_W = 1500;
const KINDREDCARE_SERVICES_DEFAULT_IMAGE_H = 952;

/**
 * Whether the address is fixed by wp-config.php.
 *
 * Two constants can do it. KINDREDCARE_SERVICES_ENDPOINT pins this one endpoint
 * outright and is honoured first, for installs that set it before the address
 * became a site-wide setting; KINDREDCARE_API_BASE pins the platform for
 * everything at once and is the one to reach for now.
 */
function kindredcare_services_endpoint_is_locked(): bool {
	return defined( 'KINDREDCARE_SERVICES_ENDPOINT' ) || kindredcare_api_base_is_locked();
}

/**
 * Where the catalogue is imported from.
 *
 * The platform address plus this section's path. The address itself has its own
 * order of precedence — see kindredcare_api_base() — and the one exception is a
 * KINDREDCARE_SERVICES_ENDPOINT constant, which names a complete URL and so
 * overrides the assembly entirely.
 */
function kindredcare_services_endpoint(): string {
	$endpoint = defined( 'KINDREDCARE_SERVICES_ENDPOINT' )
		? (string) constant( 'KINDREDCARE_SERVICES_ENDPOINT' )
		: kindredcare_api_url( KINDREDCARE_SERVICES_PATH );

	/**
	 * Filters the service catalogue endpoint.
	 *
	 * @param string $endpoint Absolute URL returning the categories payload.
	 */
	return (string) apply_filters( 'kindredcare_services_endpoint', $endpoint );
}

/**
 * The catalogue stored against a page.
 *
 * @param int $post_id Page holding the catalogue.
 * @return array<int, array<string, mixed>> Stored services, in display order.
 */
function kindredcare_services( int $post_id ): array {
	$stored = get_post_meta( $post_id, KINDREDCARE_SERVICES_META, true );

	return is_array( $stored ) ? array_values( $stored ) : array();
}

/**
 * When the catalogue on a page was last imported.
 *
 * @param int $post_id Page holding the catalogue.
 * @return int Unix timestamp, or 0 if it has never been imported.
 */
function kindredcare_services_fetched_at( int $post_id ): int {
	return absint( get_post_meta( $post_id, KINDREDCARE_SERVICES_META_TIME, true ) );
}

/**
 * Ask the API for the catalogue.
 *
 * Called from the admin, by a person, never from a page render. The request
 * itself — timeout, decoding, unwrapping Laravel's `data` — is inc/api.php's
 * job, shared with the gigs import so both fail the same way and say so in the
 * same words.
 *
 * @return array<int, array<string, mixed>>|WP_Error Services, or the reason the
 *                                                   import could not be done.
 */
function kindredcare_services_fetch() {
	$rows = kindredcare_api_get( kindredcare_services_endpoint() );

	if ( is_wp_error( $rows ) ) {
		return $rows;
	}

	$services = kindredcare_services_normalise( $rows );

	if ( ! $services ) {
		return new WP_Error(
			'kindredcare_services_empty',
			__( 'The platform returned no services.', 'kindredcare' )
		);
	}

	return $services;
}

/**
 * Reduce the API payload to the fields this page renders, sanitised.
 *
 * Nothing from the endpoint is stored unfiltered. It is our own API, but it is
 * still a remote document arriving over plain HTTP, and post meta written from
 * one is post meta a template will later print.
 *
 * @param array<int|string, mixed> $rows Raw rows.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_services_normalise( array $rows ): array {
	$services = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$name = sanitize_text_field( (string) ( $row['name'] ?? '' ) );

		// A service with no name has nothing to label its card with.
		if ( '' === $name ) {
			continue;
		}

		$tasks = array();
		foreach ( (array) ( $row['example_tasks'] ?? array() ) as $task ) {
			if ( count( $tasks ) >= KINDREDCARE_SERVICES_MAX_TASKS ) {
				break;
			}

			$task = sanitize_text_field( (string) $task );
			if ( '' !== $task ) {
				$tasks[] = $task;
			}
		}

		$services[] = array(
			'id'          => absint( $row['id'] ?? 0 ),
			'name'        => $name,
			'slug'        => sanitize_title( (string) ( $row['slug'] ?? $name ) ),
			'description' => sanitize_text_field( (string) ( $row['description'] ?? '' ) ),
			'icon'        => sanitize_text_field( (string) ( $row['icon'] ?? '' ) ),
			'tier'        => sanitize_key( (string) ( $row['tier_required'] ?? '' ) ),
			'tasks'       => $tasks,
			'order'       => absint( $row['sort_order'] ?? 0 ),

			/*
			 * The one field the API does not supply. Set here so every stored
			 * row has the same shape whether it came from an import or from a
			 * merge, and filled in by hand on the Services page.
			 */
			'image'       => 0,
		);
	}

	/*
	 * The API already sorts, but the order is the platform's editorial decision
	 * and worth honouring explicitly rather than depending on JSON key order
	 * surviving a decode. Ties fall back to name so the sequence is stable.
	 */
	usort(
		$services,
		static function ( array $a, array $b ): int {
			return $a['order'] === $b['order']
				? strcasecmp( $a['name'], $b['name'] )
				: $a['order'] <=> $b['order'];
		}
	);

	return $services;
}

/**
 * Carry hand-picked images across a re-import.
 *
 * The photographs are the one thing on this page that a person chose, and a
 * fetch is not a request to throw them away — it is a request to pick up the
 * platform's latest wording. So the incoming list wins on every field the API
 * owns, and the existing list wins on the one field it does not.
 *
 * Matched on slug rather than id: a service re-created on the platform comes
 * back with a new id but the same slug, and losing a photograph to that would
 * be baffling from the outside.
 *
 * @param array<int, array<string, mixed>> $incoming Freshly fetched services.
 * @param array<int, array<string, mixed>> $existing Services already stored.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_services_merge_images( array $incoming, array $existing ): array {
	$images = array();

	foreach ( $existing as $service ) {
		$slug = (string) ( $service['slug'] ?? '' );
		if ( '' !== $slug && ! empty( $service['image'] ) ) {
			$images[ $slug ] = absint( $service['image'] );
		}
	}

	if ( ! $images ) {
		return $incoming;
	}

	foreach ( $incoming as $index => $service ) {
		$slug = (string) ( $service['slug'] ?? '' );
		if ( isset( $images[ $slug ] ) ) {
			$incoming[ $index ]['image'] = $images[ $slug ];
		}
	}

	return $incoming;
}

/**
 * Write a catalogue to a page.
 *
 * @param int                              $post_id  Page to store against.
 * @param array<int, array<string, mixed>> $services Services to store.
 */
function kindredcare_services_store( int $post_id, array $services ): void {
	update_post_meta( $post_id, KINDREDCARE_SERVICES_META, $services );
	update_post_meta( $post_id, KINDREDCARE_SERVICES_META_TIME, time() );
}

/**
 * The human label for a verification tier.
 *
 * The API sends a key. Printing "basic" on a card aimed at a family choosing
 * care reads as a downgrade; "Verified caregiver" reads as the assurance it
 * actually is. Unknown keys fall through to a title-cased version of the key
 * rather than being dropped, so a tier added on the platform still shows.
 *
 * @param string $tier Tier key from the API.
 */
function kindredcare_services_tier_label( string $tier ): string {
	$labels = array(
		'basic'        => __( 'Verified caregiver', 'kindredcare' ),
		'enhanced'     => __( 'Trusted caregiver', 'kindredcare' ),
		'professional' => __( 'Certified caregiver', 'kindredcare' ),
	);

	if ( isset( $labels[ $tier ] ) ) {
		return $labels[ $tier ];
	}

	return '' === $tier ? '' : ucwords( str_replace( array( '-', '_' ), ' ', $tier ) );
}

/**
 * Icon geometry, keyed by the icon name the API sends.
 *
 * The platform names icons from the Lucide set, which the React front end
 * resolves at runtime. There is no such resolver here and pulling a 1,500-icon
 * library in to draw eight of them would be absurd, so the eight in use are
 * inlined — the exact node data from lucide-react v1.8.0, ISC licensed.
 *
 * An icon the map does not know renders as the fallback rather than as nothing:
 * a card with an empty badge looks like a loading failure, and the platform can
 * add a category faster than this map can be updated.
 *
 * @return array<string, array<int, array{0: string, 1: array<string, string>}>>
 */
function kindredcare_services_icons(): array {
	return array(
		'Heart'       => array(
			array( 'path', array( 'd' => 'M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5' ) ),
		),
		'Smartphone'  => array(
			array( 'rect', array( 'width' => '14', 'height' => '20', 'x' => '5', 'y' => '2', 'rx' => '2', 'ry' => '2' ) ),
			array( 'path', array( 'd' => 'M12 18h.01' ) ),
		),
		'ShoppingBag' => array(
			array( 'path', array( 'd' => 'M16 10a4 4 0 0 1-8 0' ) ),
			array( 'path', array( 'd' => 'M3.103 6.034h17.794' ) ),
			array( 'path', array( 'd' => 'M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z' ) ),
		),
		'Footprints'  => array(
			array( 'path', array( 'd' => 'M4 16v-2.38C4 11.5 2.97 10.5 3 8c.03-2.72 1.49-6 4.5-6C9.37 2 10 3.8 10 5.5c0 3.11-2 5.66-2 8.68V16a2 2 0 1 1-4 0Z' ) ),
			array( 'path', array( 'd' => 'M20 20v-2.38c0-2.12 1.03-3.12 1-5.62-.03-2.72-1.49-6-4.5-6C14.63 6 14 7.8 14 9.5c0 3.11 2 5.66 2 8.68V20a2 2 0 1 0 4 0Z' ) ),
			array( 'path', array( 'd' => 'M16 17h4' ) ),
			array( 'path', array( 'd' => 'M4 13h4' ) ),
		),
		'Flower2'     => array(
			array( 'path', array( 'd' => 'M12 5a3 3 0 1 1 3 3m-3-3a3 3 0 1 0-3 3m3-3v1M9 8a3 3 0 1 0 3 3M9 8h1m5 0a3 3 0 1 1-3 3m3-3h-1m-2 3v-1' ) ),
			array( 'circle', array( 'cx' => '12', 'cy' => '8', 'r' => '2' ) ),
			array( 'path', array( 'd' => 'M12 10v12' ) ),
			array( 'path', array( 'd' => 'M12 22c4.2 0 7-1.667 7-5-4.2 0-7 1.667-7 5Z' ) ),
			array( 'path', array( 'd' => 'M12 22c-4.2 0-7-1.667-7-5 4.2 0 7 1.667 7 5Z' ) ),
		),
		'ChefHat'     => array(
			array( 'path', array( 'd' => 'M17 21a1 1 0 0 0 1-1v-5.35c0-.457.316-.844.727-1.041a4 4 0 0 0-2.134-7.589 5 5 0 0 0-9.186 0 4 4 0 0 0-2.134 7.588c.411.198.727.585.727 1.041V20a1 1 0 0 0 1 1Z' ) ),
			array( 'path', array( 'd' => 'M6 17h12' ) ),
		),
		'Car'         => array(
			array( 'path', array( 'd' => 'M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2' ) ),
			array( 'circle', array( 'cx' => '7', 'cy' => '17', 'r' => '2' ) ),
			array( 'path', array( 'd' => 'M9 17h6' ) ),
			array( 'circle', array( 'cx' => '17', 'cy' => '17', 'r' => '2' ) ),
		),
		'SprayCan'    => array(
			array( 'path', array( 'd' => 'M3 3h.01' ) ),
			array( 'path', array( 'd' => 'M7 5h.01' ) ),
			array( 'path', array( 'd' => 'M11 7h.01' ) ),
			array( 'path', array( 'd' => 'M3 7h.01' ) ),
			array( 'path', array( 'd' => 'M7 9h.01' ) ),
			array( 'path', array( 'd' => 'M3 11h.01' ) ),
			array( 'rect', array( 'width' => '4', 'height' => '4', 'x' => '15', 'y' => '5' ) ),
			array( 'path', array( 'd' => 'm19 9 2 2v10c0 .6-.4 1-1 1h-6c-.6 0-1-.4-1-1V11l2-2' ) ),
			array( 'path', array( 'd' => 'm13 14 8-2' ) ),
			array( 'path', array( 'd' => 'm13 19 8-2' ) ),
		),
		// The fallback. A plain sparkle, used for any icon name not listed above.
		'_default'    => array(
			array( 'path', array( 'd' => 'M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z' ) ),
		),
	);
}

/**
 * Print one icon.
 *
 * Decorative: the service name sits beside it in text, so announcing the icon
 * as well would read the same thing twice.
 *
 * @param string $name Icon name from the API.
 */
function kindredcare_services_icon( string $name ): void {
	$icons = kindredcare_services_icons();
	$nodes = $icons[ $name ] ?? $icons['_default'];

	echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';

	foreach ( $nodes as list( $tag, $attributes ) ) {
		// Only the three shapes the icon data actually uses are drawable.
		if ( ! in_array( $tag, array( 'path', 'circle', 'rect' ), true ) ) {
			continue;
		}

		$markup = '<' . $tag;
		foreach ( $attributes as $attribute => $value ) {
			$markup .= sprintf( ' %s="%s"', esc_attr( $attribute ), esc_attr( $value ) );
		}

		echo $markup . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted above, every attribute escaped.
	}

	echo '</svg>';
}

/**
 * How many individual requests the catalogue adds up to.
 *
 * Printed in the masthead. It is the honest measure of what this page offers —
 * "8 services" is a filing system, "32 things you can ask for" is the actual
 * answer to what someone came here wondering.
 *
 * @param array<int, array<string, mixed>> $services Stored services.
 */
function kindredcare_services_task_count( array $services ): int {
	$total = 0;

	foreach ( $services as $service ) {
		$total += count( (array) ( $service['tasks'] ?? array() ) );
	}

	return $total;
}

/**
 * Whether the catalogue actually contains more than one verification tier.
 *
 * The tier is worth stating on a service only when it distinguishes that
 * service from the others. Today every category on the platform is 'basic', so
 * printing "Verified caregiver" against all eight says nothing eight times —
 * it is repetition dressed as information. When the platform starts returning
 * enhanced and professional tiers the label becomes a real distinction and
 * turns itself back on.
 *
 * @param array<int, array<string, mixed>> $services Stored services.
 */
function kindredcare_services_tiers_vary( array $services ): bool {
	$tiers = array();

	foreach ( $services as $service ) {
		$tier = (string) ( $service['tier'] ?? '' );
		if ( '' !== $tier ) {
			$tiers[ $tier ] = true;
		}
	}

	return count( $tiers ) > 1;
}

/**
 * Print a service's photograph.
 *
 * The chosen attachment when there is one, the bundled default when there is
 * not. Both are drawn into the same fixed 16:10 frame by the stylesheet, so a
 * service that has been given its own picture and one still on the default are
 * the same shape and the grid stays even.
 *
 * Alt is empty in both cases: the card's heading names the service directly
 * beside the image, so describing the photograph as well only repeats it.
 *
 * @param int $attachment Chosen attachment id, 0 for none.
 */
function kindredcare_services_image( int $attachment ): void {
	if ( $attachment && wp_attachment_is_image( $attachment ) ) {
		echo wp_get_attachment_image(
			$attachment,
			'kindredcare-card',
			false,
			array(
				'alt'      => '',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		return;
	}

	printf(
		'<img src="%1$s" alt="" width="%2$d" height="%3$d" loading="lazy" decoding="async" />',
		esc_url( get_theme_file_uri( KINDREDCARE_SERVICES_DEFAULT_IMAGE ) ),
		(int) KINDREDCARE_SERVICES_DEFAULT_IMAGE_W,
		(int) KINDREDCARE_SERVICES_DEFAULT_IMAGE_H
	);
}

/**
 * Render the catalogue as a two-up grid of cards.
 *
 * Two per row, every card led by a photograph — the brief for this page. Within
 * that the choices are about restraint: one surface colour, one hairline, one
 * radius, and the four example tasks kept as the body of the card rather than
 * hidden behind a link, because those specifics are the reason someone is
 * reading. "Chat over tea or coffee" answers a question that "Companionship"
 * only names.
 *
 * The icon is drawn as a chip on the photograph. That is not decoration: until
 * an editor picks a picture for each service they all show the same bundled
 * default, and the chip is then the only thing distinguishing one card from the
 * next at a glance. It keeps earning its place afterwards as a fast visual key.
 *
 * @param array<int, array<string, mixed>> $services Stored services.
 */
function kindredcare_render_services_index( array $services ): void {
	if ( ! $services ) {
		return;
	}

	$show_tier = kindredcare_services_tiers_vary( $services );
	?>
	<ul class="s-services__grid" data-reveal-group>
		<?php foreach ( array_values( $services ) as $service ) : ?>
			<?php
			$slug = (string) $service['slug'];
			$tier = $show_tier ? kindredcare_services_tier_label( (string) ( $service['tier'] ?? '' ) ) : '';
			?>
			<li class="c-card" id="service-<?php echo esc_attr( $slug ); ?>" data-reveal="up">

				<div class="c-card__media">
					<?php kindredcare_services_image( absint( $service['image'] ?? 0 ) ); ?>
					<span class="c-card__icon" aria-hidden="true">
						<?php kindredcare_services_icon( (string) ( $service['icon'] ?? '' ) ); ?>
					</span>
				</div>

				<div class="c-card__body">
					<h2 class="c-card__title"><?php echo esc_html( (string) $service['name'] ); ?></h2>

					<?php if ( '' !== (string) ( $service['description'] ?? '' ) ) : ?>
						<p class="c-card__blurb"><?php echo esc_html( (string) $service['description'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $service['tasks'] ) ) : ?>
						<ul class="c-card__tasks">
							<?php foreach ( (array) $service['tasks'] as $task ) : ?>
								<li class="c-card__task"><?php echo esc_html( (string) $task ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( '' !== $tier ) : ?>
						<p class="c-card__tier">
							<span class="c-card__tier-dot" aria-hidden="true"></span>
							<?php echo esc_html( $tier ); ?>
						</p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
