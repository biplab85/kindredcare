<?php
/**
 * "What you can book" — the service taxonomy mosaic.
 *
 * Stored in `kindredcare_taxonomy`, edited from KindredCare -> Service taxonomy.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_TAXONOMY_OPTION = 'kindredcare_taxonomy';

/** Most tiles the mosaic will accept. */
const KINDREDCARE_TAXONOMY_MAX_TILES = 12;

/** Most photographs one tile will accept. */
const KINDREDCARE_TAXONOMY_MAX_IMAGES = 2;

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_taxonomy_defaults(): array {
	return array(
		'eyebrow'      => 'What you can book',
		'show_eyebrow' => true,
		'title'        => 'Agencies offer six services. We offer thirty.',
		'lead'         => 'An agency\'s menu is built around what its insurance and training allow. A marketplace isn\'t. Start with something small — someone to garden with, help with the tablet — and move to personal care when you\'re ready.',
		'show_lead'    => true,
		'cta_label'    => 'Browse all services',
		'cta_url'      => '#final-cta',
		'show_cta'     => true,
		// The pair under "Typical rate" on every service detail page. Fixed
		// slots: `key` is structural and never read from the form.
		'booking_buttons' => array(
			array(
				'key'     => 'register',
				'enabled' => true,
				'label'   => 'Register a caregiver',
				'url'     => 'https://app.kindredcare.ca/signup',
				'new_tab' => true,
			),
			array(
				'key'     => 'find',
				'enabled' => true,
				'label'   => 'Find a caregiver',
				// The gigs archive; see register_post_type() in inc/gigs.php.
				'url'     => home_url( '/gigs/' ),
				'new_tab' => false,
			),
		),
		'chips'        => array(
			array( 'key' => 'all', 'label' => 'All', 'enabled' => true ),
			array( 'key' => 'company', 'label' => 'Companionship', 'enabled' => true ),
			array( 'key' => 'practical', 'label' => 'Practical help', 'enabled' => true ),
			array( 'key' => 'wellness', 'label' => 'Wellness', 'enabled' => true ),
			array( 'key' => 'care', 'label' => 'Personal care', 'enabled' => true ),
		),
		'tiles'     => array(
			array(
				'enabled'   => true,
				'name'      => 'Companionship',
				'group'     => 'company',
				'rate'      => '$22–28 / hour',
				'tier'      => 'Basic — Verified',
				'blurb'     => 'Someone to sit with, talk to, and share an afternoon. Tea and conversation, a walk to the shops, company on a long day. The most-requested service on the platform, and the easiest place to start.',
				'mods'      => 'c-cat--cream c-cat--tall c-cat--rr c-cat--duo c-cat--duo-stack c-cat--arrow',
				'arrow'     => true,
				'slug'      => '',
				'details'   => '',
				'included'  => '',
				'cta_label' => 'Book now',
				'cta_url'   => 'https://app.kindredcare.ca/signup',
				'images'    => array(
					array( 'image' => 0, 'file' => 'assets/img/services/companionship-1.jpg' ),
					array( 'image' => 0, 'file' => 'assets/img/services/companionship-2.jpg' ),
				),
			),
			array(
				'enabled'   => true,
				'name'      => 'Dementia & <span>Alzheimer\'s care</span>',
				'group'     => 'care',
				'rate'      => '$32–45 / hour',
				'tier'      => 'Professional — Certified',
				'blurb'     => 'Matched by stage — early, moderate or advanced — and by the caregiver\'s specific dementia training, not just general experience. No other platform in Canada matches at this level of detail.',
				'mods'      => 'c-cat--sage c-cat--bm c-cat--frame c-cat--arrow',
				'arrow'     => true,
				'slug'      => '',
				'details'   => '',
				'included'  => '',
				'cta_label' => 'Book now',
				'cta_url'   => 'https://app.kindredcare.ca/signup',
				'images'    => array(
					array( 'image' => 0, 'file' => 'assets/img/services/dementia.jpg' ),
				),
			),
			array(
				'enabled'   => true,
				'name'      => 'Technology help',
				'group'     => 'practical',
				'rate'      => '$25–32 / hour',
				'tier'      => 'Basic — Verified',
				'blurb'     => 'Setting up a tablet, teaching video calls with the grandchildren, sorting out the TV, making the phone\'s text bigger. Patient, unhurried, as many times as it takes.',
				'mods'      => 'c-cat--grey c-cat--tr',
				'arrow'     => false,
				'slug'      => '',
				'details'   => '',
				'included'  => '',
				'cta_label' => 'Book now',
				'cta_url'   => 'https://app.kindredcare.ca/signup',
				'images'    => array(
					array( 'image' => 0, 'file' => 'assets/img/services/technology.jpg' ),
				),
			),
			array(
				'enabled'   => true,
				'name'      => 'Transportation',
				'group'     => 'practical',
				'rate'      => '$26–34 / hour',
				'tier'      => 'Enhanced — Trusted',
				'blurb'     => 'Driving to appointments and waiting through them, then driving home. Includes helping in and out of the car and carrying what needs carrying.',
				'mods'      => 'c-cat--rose c-cat--bl',
				'arrow'     => false,
				'slug'      => '',
				'details'   => '',
				'included'  => '',
				'cta_label' => 'Book now',
				'cta_url'   => 'https://app.kindredcare.ca/signup',
				'images'    => array(
					array( 'image' => 0, 'file' => 'assets/img/services/transportation.jpg' ),
				),
			),
			array(
				'enabled'   => true,
				'name'      => 'Meal preparation',
				'group'     => 'wellness',
				'rate'      => '$24–30 / hour',
				'tier'      => 'Enhanced — Trusted',
				'blurb'     => 'Cooking for specific needs — diabetic, low-sodium, soft foods, or the dishes they grew up with. Batch cooking for the week is the most common booking.',
				'mods'      => 'c-cat--blue c-cat--tm c-cat--frame',
				'arrow'     => false,
				'slug'      => '',
				'details'   => '',
				'included'  => '',
				'cta_label' => 'Book now',
				'cta_url'   => 'https://app.kindredcare.ca/signup',
				'images'    => array(
					array( 'image' => 0, 'file' => 'assets/img/services/meal-prep.jpg' ),
				),
			),
			array(
				'enabled'   => true,
				'name'      => 'Personal care',
				'group'     => 'care',
				'rate'      => '$28–38 / hour',
				'tier'      => 'Enhanced — Trusted',
				'blurb'     => 'Bathing, dressing, grooming and mobility support, delivered with dignity. Requires a Vulnerable Sector Check on top of the standard background check.',
				'mods'      => 'c-cat--sage c-cat--tall c-cat--tl c-cat--arrow',
				'arrow'     => false,
				'slug'      => '',
				'details'   => '',
				'included'  => '',
				'cta_label' => 'Book now',
				'cta_url'   => 'https://app.kindredcare.ca/signup',
				'images'    => array(
					array( 'image' => 0, 'file' => 'assets/img/services/personal-care.jpg' ),
				),
			),
			array(
				'enabled'   => true,
				'name'      => 'Gardening',
				'group'     => 'wellness',
				'rate'      => '$24–30 / hour',
				'tier'      => 'Basic — Verified',
				'blurb'     => 'Keeping the garden they built going, together. Weeding, planting, pots on the balcony, or just sitting outside while the work gets done.',
				'mods'      => 'c-cat--cream c-cat--bn',
				'arrow'     => false,
				'slug'      => '',
				'details'   => '',
				'included'  => '',
				'cta_label' => 'Book now',
				'cta_url'   => 'https://app.kindredcare.ca/signup',
				'images'    => array(
					array( 'image' => 0, 'file' => 'assets/img/services/gardening.jpg' ),
				),
			),
		),
	);
}

/**
 * The modifier classes a tile is allowed to carry.
 *
 * These are the mosaic — a pastel ground, which corners round, whether the tile
 * is double height, carries a frame, an arrow or a stacked photo pair. They are
 * layout, not content, so they are stored but never editable: a free-text field
 * here would let one wrong word drop a tile out of the grid, and there is no
 * sensible admin control for "which corners are round".
 *
 * @return string[]
 */
function kindredcare_taxonomy_modifiers(): array {
	return array(
		// Grounds.
		'c-cat--cream', 'c-cat--sage', 'c-cat--grey', 'c-cat--rose', 'c-cat--blue',
		// Span and corner rounding.
		'c-cat--tall', 'c-cat--tl', 'c-cat--tm', 'c-cat--tr',
		'c-cat--bl', 'c-cat--bm', 'c-cat--bn', 'c-cat--rr',
		// Decoration.
		'c-cat--frame', 'c-cat--arrow', 'c-cat--duo', 'c-cat--duo-stack',
	);
}

/**
 * The chip keys a tile may be filed under.
 *
 * Derived from the chips themselves so the two cannot drift: a tile in a group
 * with no chip could never be filtered back into view. "all" is the reset chip
 * and is not a group a tile can belong to.
 *
 * @return string[]
 */
function kindredcare_taxonomy_groups(): array {
	$keys = array();

	foreach ( kindredcare_taxonomy_defaults()['chips'] as $chip ) {
		if ( 'all' !== $chip['key'] ) {
			$keys[] = $chip['key'];
		}
	}

	return $keys;
}

/**
 * Theme-relative image paths a stored tile is allowed to name.
 *
 * @return string[]
 */
function kindredcare_taxonomy_bundled_images(): array {
	$paths = array();

	foreach ( kindredcare_taxonomy_defaults()['tiles'] as $tile ) {
		foreach ( $tile['images'] as $image ) {
			if ( ! empty( $image['file'] ) ) {
				$paths[] = $image['file'];
			}
		}
	}

	return $paths;
}

/**
 * Keep only the modifier classes on the allowlist.
 *
 * @param string $mods Space-separated class list.
 * @return string Clean class list.
 */
function kindredcare_taxonomy_clean_mods( string $mods ): string {
	$allowed = kindredcare_taxonomy_modifiers();
	$keep    = array();

	foreach ( preg_split( '/\s+/', trim( $mods ) ) ?: array() as $class ) {
		if ( in_array( $class, $allowed, true ) && ! in_array( $class, $keep, true ) ) {
			$keep[] = $class;
		}
	}

	return implode( ' ', $keep );
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_taxonomy(): array {
	$defaults = kindredcare_taxonomy_defaults();
	$stored   = get_option( KINDREDCARE_TAXONOMY_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );

	/*
	 * The switches. Absent from an option saved before they existed, which must
	 * read as "shown" — that is what those installs are doing today, and a
	 * missing key is not a decision to hide anything.
	 */
	foreach ( array( 'show_eyebrow', 'show_lead', 'show_cta' ) as $flag ) {
		$section[ $flag ] = ! isset( $stored[ $flag ] ) || (bool) $stored[ $flag ];
	}

	// Booking card buttons: each slot over its default, so an option saved
	// before they existed gets both buttons as shipped.
	$buttons = array();
	foreach ( $defaults['booking_buttons'] as $index => $row ) {
		$saved             = is_array( $stored['booking_buttons'][ $index ] ?? null ) ? $stored['booking_buttons'][ $index ] : array();
		$buttons[ $index ] = array(
			'key'     => $row['key'],
			'enabled' => isset( $saved['enabled'] ) ? (bool) $saved['enabled'] : $row['enabled'],
			'label'   => isset( $saved['label'] ) ? (string) $saved['label'] : $row['label'],
			'url'     => isset( $saved['url'] ) ? (string) $saved['url'] : $row['url'],
			'new_tab' => isset( $saved['new_tab'] ) ? (bool) $saved['new_tab'] : $row['new_tab'],
		);
	}
	$section['booking_buttons'] = $buttons;

	// Chips: fixed keys, editable labels, and each one can be switched off.
	$chips = array();
	foreach ( $defaults['chips'] as $index => $chip ) {
		$saved   = is_array( $stored['chips'][ $index ] ?? null ) ? $stored['chips'][ $index ] : array();
		$chips[] = array(
			'key'     => $chip['key'],
			'label'   => isset( $saved['label'] ) ? (string) $saved['label'] : $chip['label'],
			'enabled' => ! isset( $saved['enabled'] ) || (bool) $saved['enabled'],
		);
	}
	$section['chips'] = $chips;

	// Tiles: repeatable.
	$tiles   = array();
	$allowed = kindredcare_taxonomy_bundled_images();
	$groups  = kindredcare_taxonomy_groups();

	if ( is_array( $stored['tiles'] ?? null ) && $stored['tiles'] ) {
		foreach ( array_values( $stored['tiles'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$images = array();
			foreach ( array_values( is_array( $row['images'] ?? null ) ? $row['images'] : array() ) as $image ) {
				if ( ! is_array( $image ) || count( $images ) >= KINDREDCARE_TAXONOMY_MAX_IMAGES ) {
					continue;
				}
				$file = (string) ( $image['file'] ?? '' );
				$images[] = array(
					'image' => absint( $image['image'] ?? 0 ),
					'file'  => in_array( $file, $allowed, true ) ? $file : '',
				);
			}

			$group = (string) ( $row['group'] ?? '' );

			$tiles[] = array(
				'enabled'   => ! isset( $row['enabled'] ) || (bool) $row['enabled'],
				'name'      => (string) ( $row['name'] ?? '' ),
				'group'     => in_array( $group, $groups, true ) ? $group : ( $groups[0] ?? '' ),
				'rate'      => (string) ( $row['rate'] ?? '' ),
				'tier'      => (string) ( $row['tier'] ?? '' ),
				'blurb'     => (string) ( $row['blurb'] ?? '' ),
				'mods'      => kindredcare_taxonomy_clean_mods( (string) ( $row['mods'] ?? '' ) ),
				'arrow'     => ! empty( $row['arrow'] ),
				// Detail page fields. A tile saved before they existed has none of
				// these keys, and must still get a working page and button.
				'slug'      => (string) ( $row['slug'] ?? '' ),
				'details'   => (string) ( $row['details'] ?? '' ),
				'included'  => (string) ( $row['included'] ?? '' ),
				'cta_label' => isset( $row['cta_label'] ) ? (string) $row['cta_label'] : 'Book now',
				'cta_url'   => isset( $row['cta_url'] ) ? (string) $row['cta_url'] : 'https://app.kindredcare.ca/signup',
				'images'    => $images,
			);
		}
	}

	$section['tiles'] = $tiles ?: $defaults['tiles'];

	return $section;
}

/**
 * The tiles that will actually render.
 *
 * A tile needs to be switched on and named — the name is both the visible label
 * and the modal's heading, so an unnamed tile opens a dialog with no title.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_taxonomy_active_tiles( array $section ): array {
	return array_values(
		array_filter(
			$section['tiles'],
			static fn( array $tile ): bool => ! empty( $tile['enabled'] ) && '' !== trim( wp_strip_all_tags( (string) $tile['name'] ) )
		)
	);
}

/**
 * The filter chips that will actually render.
 *
 * Switching a chip off only takes it out of the row. It does not touch the
 * groups themselves — a tile stays filed under its group either way, and the
 * group is still offered when editing a tile, or hiding a chip would orphan
 * every tile beneath it.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_taxonomy_active_chips( array $section ): array {
	return array_values(
		array_filter(
			$section['chips'],
			static fn( array $chip ): bool => ! empty( $chip['enabled'] ) && '' !== trim( (string) $chip['label'] )
		)
	);
}

/**
 * Render the taxonomy section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_taxonomy(): void {
	$section = kindredcare_taxonomy();
	$tiles   = kindredcare_taxonomy_active_tiles( $section );
	$chips   = kindredcare_taxonomy_active_chips( $section );

	// The visible name may wrap part of itself in a <span> to force the break.
	$name_tags = array( 'span' => array() );
	?>
	<!-- ================================================================ -->
	<!-- 04 — Expanded service taxonomy ⭐ THE CENTREPIECE                -->
	<!-- Proves we are not an agency. Laid out on the Sekare "Service We   -->
	<!-- Provide" pattern: a pastel mosaic whose heading is one of the      -->
	<!-- tiles rather than a band above it. See _taxonomy.scss.            -->
	<!-- ================================================================ -->
	<section class="l-section s-taxonomy" id="services" aria-labelledby="services-title" style="display:none;visibility:hidden">
		<div class="l-container">
			<?php if ( $chips ) : ?>
				<!-- App-mode filter chips -->
				<div class="s-taxonomy__chips" data-chips role="group" aria-label="<?php esc_attr_e( 'Filter services', 'kindredcare' ); ?>">
					<?php foreach ( $chips as $index => $chip ) : ?>
						<?php
						/*
						 * Pressed on the first chip that renders, not on the first
						 * chip that exists. Switching off whichever one used to lead
						 * would otherwise leave the row with nothing selected and the
						 * grid filtered by a chip nobody can see.
						 */
						?>
						<button class="s-taxonomy__chip" type="button" data-chip="<?php echo esc_attr( $chip['key'] ); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"><?php echo esc_html( $chip['label'] ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="s-taxonomy__grid" data-cat-grid data-reveal-group>
				<?php
				/*
				 * The heading tile. First in the DOM so the stacked mobile order still
				 * reads heading-then-services; from md up it spans half the row and two
				 * rows deep, with the two tall feature tiles closing the band beside it.
				 */
				?>
				<div class="c-cat-head" data-reveal="up">
					<?php
					/*
					 * Each piece answers to its own switch as well as to whether it
					 * has anything in it. Two ways to remove something, on purpose:
					 * clearing the field throws the wording away, the switch keeps
					 * it for later. The heading has no switch — it is the section's
					 * accessible name through aria-labelledby.
					 */
					?>
					<?php if ( $section['show_eyebrow'] && '' !== trim( (string) $section['eyebrow'] ) ) : ?>
						<span class="u-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></span>
					<?php endif; ?>
					<h2 class="c-cat-head__title" id="services-title">
						<?php echo esc_html( $section['title'] ); ?>
					</h2>
					<?php if ( $section['show_lead'] && '' !== trim( (string) $section['lead'] ) ) : ?>
						<p class="c-cat-head__lead">
							<?php echo esc_html( $section['lead'] ); ?>
						</p>
					<?php endif; ?>
					<?php if ( $section['show_cta'] && '' !== trim( (string) $section['cta_label'] ) ) : ?>
						<a class="c-btn c-btn--primary c-cat-head__action" style="display:none;visibility:hidden" href="<?php echo esc_url( $section['cta_url'] ); ?>"><?php echo esc_html( $section['cta_label'] ); ?></a>
					<?php endif; ?>
				</div>

				<?php
				foreach ( $tiles as $tile ) :
					$mods = kindredcare_taxonomy_clean_mods( (string) $tile['mods'] );
					?>
					<?php
					/*
					 * A link to the service's own page (inc/service-pages.php) rather
					 * than a button opening a pop-up. Inline colour and underline reset
					 * because .c-cat was styled for a <button>, and main.css is
					 * compiled from Sass outside this theme.
					 */
					?>
					<a
						class="c-cat<?php echo '' !== $mods ? ' ' . esc_attr( $mods ) : ''; ?>"
						href="<?php echo esc_url( kindredcare_service_url( $tile ) ); ?>"
						style="color:inherit;text-decoration:none"
						data-reveal="up"
						data-group="<?php echo esc_attr( $tile['group'] ); ?>"
					>
						<span class="c-cat__name"><?php echo wp_kses( (string) $tile['name'], $name_tags ); ?></span>
						<?php if ( ! empty( $tile['arrow'] ) ) : ?>
							<span class="c-cat__arrow" aria-hidden="true">
								<svg width="18" height="18" viewBox="0 0 16 16" fill="none">
									<path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</span>
						<?php endif; ?>
						<?php if ( $tile['images'] ) : ?>
							<span class="c-cat__photo" aria-hidden="true">
								<?php
								foreach ( $tile['images'] as $image ) {
									kindredcare_render_image(
										(int) $image['image'],
										(string) $image['file'],
										'',
										'',
										array(
											'loading'  => 'lazy',
											'decoding' => 'async',
										)
									);
								}
								?>
							</span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}
