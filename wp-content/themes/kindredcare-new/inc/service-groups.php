<?php
/**
 * "Services we provide" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_service_groups`, edited from
 * KindredCare -> Services in the admin.
 *
 * Every default is the exact string or file the static build shipped, so a
 * fresh install renders identically to kindredcare-home/src/index.html.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_SERVICES_OPTION = 'kindredcare_service_groups';

/**
 * Most cards the section will accept.
 *
 * The intro column pins with `position: sticky` while the cards scroll past it,
 * so the card list is the runway that pin travels along. More cards simply make
 * a longer runway; the cap is here to stop the section becoming the page.
 */
const KINDREDCARE_SERVICES_MAX_CARDS = 8;

/**
 * How many background tints the stylesheet defines.
 *
 * `.c-group-card--ground-1` to `--ground-5` in sections/_service-groups.scss.
 * A sixth card reuses the first tint rather than falling through to the
 * unstyled default — see kindredcare_service_group_ground().
 */
const KINDREDCARE_SERVICES_GROUNDS = 5;

/**
 * Listed services per card in the shipped defaults.
 *
 * The reference card holds five content lines — four services and the "+N more"
 * line — which is what fills the 306x354 photograph beside it. The count is
 * editable, but past five the body outgrows the photograph and the card becomes
 * taller than its own image, so the admin warns rather than forbids.
 */
const KINDREDCARE_SERVICES_ITEMS = 4;

/**
 * Most service lines a card will accept.
 */
const KINDREDCARE_SERVICES_MAX_ITEMS = 8;

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_service_groups_defaults(): array {
	return array(
		'eyebrow'   => 'Services we provide',
		'title'     => 'Five kinds of help, from a cup of tea to certified dementia care.',
		'cta_label' => 'View all services',
		'cta_url'   => kindredcare_services_index_url(),
		'cta_new_tab' => null,
		'cards'     => array(
			array(
				'enabled' => true,
				'title'   => 'Traditional care',
				'url'     => home_url( '/services/personal-care/' ),
				'items'   => array(
					'Personal care — bathing, dressing, grooming',
					'Medication reminders',
					'Mobility & transfer assistance',
					'Wound care',
				),
				'more'    => '+2 more, including meal preparation',
				'image'   => 0,
				'file'    => 'assets/img/services/service-group1.jpg',
				'ground'  => '',
			),
			array(
				'enabled' => true,
				'title'   => 'Companionship & social',
				'url'     => home_url( '/services/companionship/' ),
				'items'   => array(
					'Storytelling & reading aloud',
					'Conversation in a first language',
					'Holiday & event company',
					'Social outings',
				),
				'more'    => '+3 more, including hobby partners',
				'image'   => 0,
				'file'    => 'assets/img/services/service-group2.jpg',
				'ground'  => '',
			),
			array(
				'enabled' => true,
				'title'   => 'Practical help',
				'url'     => home_url( '/services/technology-help/' ),
				'items'   => array(
					'Technology assistance',
					'Groceries & errands',
					'Transportation to appointments',
					'Paperwork & admin',
				),
				'more'    => '+3 more, including seasonal help',
				'image'   => 0,
				'file'    => 'assets/img/services/service-group3.jpg',
				'ground'  => '',
			),
			array(
				'enabled' => true,
				'title'   => 'Wellness & lifestyle',
				'url'     => home_url( '/services/gardening/' ),
				'items'   => array(
					'Walking companion',
					'Gardening assistance',
					'Meal prep for dietary needs',
					'Meditation & relaxation',
				),
				'more'    => '+1 more, gentle yoga & stretching',
				'image'   => 0,
				'file'    => 'assets/img/services/service-group4.jpg',
				'ground'  => '',
			),
			array(
				'enabled' => true,
				'title'   => 'Specialised care — certified',
				'url'     => home_url( '/services/dementia-alzheimers-care/' ),
				'items'   => array(
					'Dementia & Alzheimer\'s care',
					'Diabetes management',
					'Palliative companionship',
					'Post-surgery recovery',
				),
				'more'    => '+2 more, including rehab exercise support',
				'image'   => 0,
				'file'    => 'assets/img/services/service-group5.jpg',
				'ground'  => '',
			),
		),
	);
}

/**
 * Theme-relative image paths a stored card is allowed to name.
 *
 * Cards are repeatable, so a card's bundled fallback travels in the option and
 * is therefore untrusted. This allowlist is what stops `file` becoming an
 * arbitrary path read.
 *
 * @return string[]
 */
function kindredcare_service_groups_bundled_images(): array {
	$paths = array();

	foreach ( kindredcare_service_groups_defaults()['cards'] as $card ) {
		if ( ! empty( $card['file'] ) ) {
			$paths[] = $card['file'];
		}
	}

	return $paths;
}

/**
 * The background-tint modifier for a card at a given position.
 *
 * Cycles rather than running out: the stylesheet defines five tints and the
 * section accepts more cards than that, and a card with no tint falls back to
 * the component's unstyled default, which reads as a bug.
 *
 * @param int $index Zero-based card position.
 * @return int Ground number, 1-based.
 */
function kindredcare_service_group_ground( int $index ): int {
	return ( $index % KINDREDCARE_SERVICES_GROUNDS ) + 1;
}

/**
 * The five pastel grounds, in the order the stylesheet defines them.
 *
 * Duplicated from sections/_service-groups.scss:380-400 because PHP cannot read
 * a Sass file. They are only ever used to *show* the automatic colour in the
 * admin — the front end still takes its default from the stylesheet, so if the
 * two ever disagree the rendered page is unaffected.
 *
 * @return string[] Hex colours, indexed 0-4 for grounds 1-5.
 */
function kindredcare_service_group_palette(): array {
	return array( '#eff0fe', '#faf6e7', '#f5f6f9', '#ede3eb', '#ebf3fa' );
}

/**
 * The colour a card would use with no override, given its position.
 *
 * @param int $index Zero-based card position.
 * @return string Hex colour.
 */
function kindredcare_service_group_auto_colour( int $index ): string {
	$palette = kindredcare_service_group_palette();

	return $palette[ kindredcare_service_group_ground( $index ) - 1 ];
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_service_groups(): array {
	$defaults = kindredcare_service_groups_defaults();
	$stored   = get_option( KINDREDCARE_SERVICES_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );
	$cards   = array();

	if ( is_array( $stored['cards'] ?? null ) && $stored['cards'] ) {
		$allowed = kindredcare_service_groups_bundled_images();

		foreach ( array_values( $stored['cards'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$file  = (string) ( $row['file'] ?? '' );
			$items = is_array( $row['items'] ?? null ) ? array_values( $row['items'] ) : array();

			$cards[] = array(
				'enabled' => ! isset( $row['enabled'] ) || (bool) $row['enabled'],
				'title'   => (string) ( $row['title'] ?? '' ),
				'url'     => (string) ( $row['url'] ?? '' ),
				'items'   => array_slice( array_map( 'strval', $items ), 0, KINDREDCARE_SERVICES_MAX_ITEMS ),
				'more'    => (string) ( $row['more'] ?? '' ),
				'image'   => absint( $row['image'] ?? 0 ),
				'file'    => in_array( $file, $allowed, true ) ? $file : '',
				// Empty means "follow the position", which is the shipped behaviour.
				'ground'  => (string) ( sanitize_hex_color( (string) ( $row['ground'] ?? '' ) ) ?? '' ),
			);
		}
	}

	$section['cards'] = $cards ?: $defaults['cards'];

	return $section;
}

/**
 * The cards that will actually render.
 *
 * A card needs to be switched on and to have a title — an untitled card is a
 * block of colour with no meaning, and the heading is what the link wraps.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_service_groups_active_cards( array $section ): array {
	return array_values(
		array_filter(
			$section['cards'],
			static fn( array $card ): bool => ! empty( $card['enabled'] ) && '' !== trim( (string) $card['title'] )
		)
	);
}

/**
 * Render the "Services we provide" section.
 *
 * Markup is the static build's, unchanged. See kindredcare-home/src/index.html
 * for the original and the reasoning preserved in the comments.
 */
function kindredcare_render_service_groups(): void {
	$section = kindredcare_service_groups();
	$cards   = kindredcare_service_groups_active_cards( $section );
	?>
	<!-- ================================================================ -->
	<!-- 01c — Service groups (sticky intro / scrolling cards)             -->
	<!-- Sekare demo-four .demo-four-service-area, matched. The intro pins -->
	<!-- while the cards pass, then releases into section 03. Pure         -->
	<!-- CSS position:sticky — see sections/_service-groups.scss.          -->
	<!--                                                                   -->
	<!-- The groups are the taxonomy from final-thoughts.md §"The          -->
	<!-- Expanded Service Taxonomy" — 31 services in total. Each card      -->
	<!-- lists four and counts the rest, so every card holds the           -->
	<!-- reference's five content lines and its 354px height.              -->
	<!-- ================================================================ -->
	<section
		class="l-section s-service-groups"
		id="service-groups"
		aria-labelledby="service-groups-title"
	>
		<?php
		/*
		 * Decorative layer. A SIBLING of .l-container, never an ancestor of it:
		 * the clipping lives on this div, because `overflow: hidden` anywhere
		 * above the sticky intro column would make that box the sticky scroll
		 * container and the pin would stop working.
		 *
		 * Left static deliberately — these are six abstract motes, not content,
		 * and nothing about them is worth an editor's attention.
		 */
		?>
		<div class="s-service-groups__deco" aria-hidden="true">
			<!-- Heart -->
			<svg class="s-service-groups__mote s-service-groups__mote--1" viewBox="0 0 24 24" fill="none">
				<path d="M12 20.5S4 15.6 4 10.2A4.9 4.9 0 0 1 12 6.5a4.9 4.9 0 0 1 8 3.7c0 5.4-8 10.3-8 10.3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
			</svg>

			<!-- Leaf / gardening -->
			<svg class="s-service-groups__mote s-service-groups__mote--2" viewBox="0 0 24 24" fill="none">
				<path d="M12 21v-7M12 14c0-3.5 2.5-6 6-6 0 3.5-2.5 6-6 6ZM12 14c0-3.5-2.5-6-6-6 0 3.5 2.5 6 6 6Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
			</svg>

			<!-- Speech bubble / companionship -->
			<svg class="s-service-groups__mote s-service-groups__mote--3" viewBox="0 0 24 24" fill="none">
				<path d="M4 5h16v10H9l-5 4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
			</svg>

			<!-- Tablet / technology help -->
			<svg class="s-service-groups__mote s-service-groups__mote--4" viewBox="0 0 24 24" fill="none">
				<rect x="4" y="4" width="16" height="12" rx="1.5" stroke="currentColor" stroke-width="1.5" />
				<path d="M9 20h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
			</svg>

			<!-- Cup / a visit -->
			<svg class="s-service-groups__mote s-service-groups__mote--5" viewBox="0 0 24 24" fill="none">
				<path d="M4 8h12v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4zM16 10h2.5a2.5 2.5 0 0 1 0 5H16" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
			</svg>

			<!-- Shield / verified -->
			<svg class="s-service-groups__mote s-service-groups__mote--6" viewBox="0 0 24 24" fill="none">
				<path d="M12 3.5 19 6v5.5c0 4.2-2.9 7.4-7 8.9-4.1-1.5-7-4.7-7-8.9V6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
			</svg>
		</div>

		<div class="l-container">
			<div class="s-service-groups__layout">
				<div class="s-service-groups__intro">
					<div class="s-service-groups__intro-inner" data-reveal="left">
						<?php if ( '' !== trim( (string) $section['eyebrow'] ) ) : ?>
							<span class="u-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></span>
						<?php endif; ?>
						<h2 class="s-service-groups__title" id="service-groups-title">
							<?php echo esc_html( $section['title'] ); ?>
						</h2>
						<?php if ( '' !== trim( (string) $section['cta_label'] ) ) : ?>
							<?php $kc_cta_new_tab = kindredcare_link_new_tab( $section['cta_new_tab'] ?? null, (string) $section['cta_url'] ); ?>
							<a class="c-btn c-btn--primary c-btn--lg"<?php echo kindredcare_link_target( $kc_cta_new_tab ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- Fixed attribute string. ?> href="<?php echo esc_url( $section['cta_url'] ); ?>">
								<?php echo esc_html( $section['cta_label'] ); ?>
								<?php kindredcare_link_new_tab_hint( $kc_cta_new_tab ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<ul class="s-service-groups__list" data-reveal-group>
					<?php
					foreach ( $cards as $index => $card ) :
						/*
						 * The ground class always stays. A custom colour is an
						 * inline --group-ground on top of it, which the class's
						 * own declaration then loses to — so a card left on
						 * "automatic" emits exactly the markup the static build
						 * shipped, with no style attribute at all.
						 */
						$ground = trim( (string) ( $card['ground'] ?? '' ) );
						?>
						<li data-reveal="up">
							<article
								class="c-group-card c-group-card--ground-<?php echo (int) kindredcare_service_group_ground( (int) $index ); ?>"
								<?php echo '' !== $ground ? 'style="--group-ground:' . esc_attr( $ground ) . '"' : ''; ?>
							>
								<div class="c-group-card__body">
									<h3 class="c-group-card__title">
										<a class="c-group-card__link" href="<?php echo esc_url( $card['url'] ); ?>">
											<?php echo esc_html( $card['title'] ); ?>
										</a>
									</h3>
									<ul class="c-group-card__list">
										<?php
										foreach ( $card['items'] as $item ) :
											if ( '' === trim( (string) $item ) ) {
												continue;
											}
											?>
											<li class="c-group-card__item"><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
										<?php if ( '' !== trim( (string) $card['more'] ) ) : ?>
											<li class="c-group-card__more"><?php echo esc_html( $card['more'] ); ?></li>
										<?php endif; ?>
									</ul>
									<span class="c-group-card__icon" aria-hidden="true">
										<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
											<path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
										</svg>
									</span>
								</div>
								<div class="c-group-card__media">
									<?php
									kindredcare_render_image(
										(int) $card['image'],
										(string) $card['file'],
										'c-group-card__img',
										'',
										array(
											'width'   => '306',
											'height'  => '354',
											'loading' => 'lazy',
										)
									);
									?>
								</div>
							</article>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>
	<?php
}
