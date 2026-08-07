<?php
/**
 * "Verification tiers" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_tiers`, edited from KindredCare ->
 * Verification tiers in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * This section is the answer to Care.com: "we do background checks" is what
 * every competitor says, and an undifferentiated trust claim is worth nothing.
 * The whole point is that each check is nameable, so the copy here stays
 * specific — Certn, CPIC, Vulnerable Sector Check — and never softens into
 * "fully vetted".
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_TIERS_OPTION = 'kindredcare_tiers';

/**
 * How many tiers the section has.
 *
 * Fixed at three, and deliberately not a repeater. Three things depend on it:
 * the heading says "Three tiers"; the cards carry positional `c-tier--1|2|3`
 * modifiers that colour the badge and fill the meter; and js/modules/
 * tier-stack.js converges the row onto `cards[1]` and treats first and last as
 * the outer pair, so a fourth card would have no resting place and the deal-out
 * would drop it in the middle of the pile. Adding a tier is a design change to
 * all three, not a content edit.
 */
const KINDREDCARE_TIERS_COUNT = 3;

/**
 * Most checks one card will list.
 *
 * The three cards are grid siblings of equal height, so the longest list sets
 * the height of all of them. Past five the row is taller than a laptop viewport
 * and the "unlocks" line — the part that says what the tier is actually for —
 * falls below the fold.
 */
const KINDREDCARE_TIERS_MAX_CHECKS = 5;

/**
 * Most rows one "what's actually checked" modal will list.
 */
const KINDREDCARE_TIERS_MAX_MODAL_ITEMS = 6;

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_tiers_defaults(): array {
	return array(
		'eyebrow'    => 'Verification',
		'title'      => 'Three tiers. Each one unlocks more.',
		'lead'       => 'Nobody offers a service they aren\'t cleared for. A caregiver offering companionship passes different checks from one offering personal care, and the badge on every listing tells you which.',
		'more_label' => 'What\'s actually checked?',
		'tiers'      => array(
			array(
				'badge'       => 'Verified',
				'name'        => 'Basic',
				'desc'        => 'The floor. Nobody appears on the marketplace below this.',
				'checks'      => array(
					'Identity verified (eKYC, document + face)',
					'Criminal record check (CPIC, via Certn)',
				),
				'unlocks'     => 'Unlocks companionship, errands, tech help, gardening',
				'modal_title' => 'Basic — what\'s actually checked',
				'modal_items' => array(
					array(
						'term' => 'Identity verification (eKYC)',
						'def'  => 'Government photo ID is uploaded and machine-read, then matched against a live selfie. Confirms the person listing is the person in the document.',
					),
					array(
						'term' => 'CPIC criminal record check',
						'def'  => 'Run through Certn against the Canadian Police Information Centre database. A listing cannot go live until this clears.',
					),
					array(
						'term' => 'Address verification',
						'def'  => 'Confirms the caregiver lives where they say, so the service-area distance shown on their listing is real.',
					),
				),
			),
			array(
				'badge'       => 'Trusted',
				'name'        => 'Enhanced',
				'desc'        => 'Required before anyone enters a home to give hands-on help.',
				'checks'      => array(
					'Everything in Basic',
					'Vulnerable Sector Check',
					'Two professional references, contacted by us',
				),
				'unlocks'     => 'Adds personal care, meal prep, transportation',
				'modal_title' => 'Enhanced — what\'s actually checked',
				'modal_items' => array(
					array(
						'term' => 'Everything in Basic',
						'def'  => 'Identity, CPIC and address, all cleared.',
					),
					array(
						'term' => 'Vulnerable Sector Check',
						'def'  => 'A deeper police check used in Canada for anyone working with children or vulnerable adults. It searches records a standard criminal record check does not reach, including pardoned sexual offences.',
					),
					array(
						'term' => 'Two professional references',
						'def'  => 'Contacted by us, not self-reported. We ask about reliability, judgement and how they were with the person in their care.',
					),
				),
			),
			array(
				'badge'       => 'Certified',
				'name'        => 'Professional',
				'desc'        => 'For chronic conditions, where training is the whole point.',
				'checks'      => array(
					'Everything in Enhanced',
					'Certifications verified (PSW, First Aid, CPR)',
					'Live video identity check',
				),
				'unlocks'     => 'Adds dementia care, diabetes support, post-surgery recovery',
				'modal_title' => 'Professional — what\'s actually checked',
				'modal_items' => array(
					array(
						'term' => 'Everything in Enhanced',
						'def'  => 'Including the Vulnerable Sector Check and both references.',
					),
					array(
						'term' => 'Certification verification',
						'def'  => 'PSW, nursing, First Aid and CPR certificates are uploaded, read automatically, and checked for validity and expiry. Expired certificates suspend the services they unlock.',
					),
					array(
						'term' => 'Live video identity check',
						'def'  => 'A short recorded video call confirming the person holding the certifications is the person on the listing.',
					),
					array(
						'term' => 'Condition-specific experience',
						'def'  => 'Years of experience per condition — dementia stage, diabetes type, post-surgery recovery — recorded so matching can use it.',
					),
				),
			),
		),
	);
}

/**
 * One tier row, with every key present.
 *
 * @param mixed                $row      Stored row, any shape.
 * @param array<string, mixed> $fallback Default row for this position.
 * @return array<string, mixed>
 */
function kindredcare_tiers_row( $row, array $fallback ): array {
	if ( ! is_array( $row ) ) {
		return $fallback;
	}

	$checks = array();
	if ( is_array( $row['checks'] ?? null ) ) {
		foreach ( array_values( $row['checks'] ) as $check ) {
			if ( is_scalar( $check ) ) {
				$checks[] = (string) $check;
			}
		}
	}

	$items = array();
	if ( is_array( $row['modal_items'] ?? null ) ) {
		foreach ( array_values( $row['modal_items'] ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$items[] = array(
				'term' => (string) ( $item['term'] ?? '' ),
				'def'  => (string) ( $item['def'] ?? '' ),
			);
		}
	}

	return array(
		'badge'       => (string) ( $row['badge'] ?? $fallback['badge'] ),
		'name'        => (string) ( $row['name'] ?? $fallback['name'] ),
		'desc'        => (string) ( $row['desc'] ?? $fallback['desc'] ),
		'checks'      => $checks ?: $fallback['checks'],
		'unlocks'     => (string) ( $row['unlocks'] ?? $fallback['unlocks'] ),
		'modal_title' => (string) ( $row['modal_title'] ?? $fallback['modal_title'] ),
		'modal_items' => $items ?: $fallback['modal_items'],
	);
}

/**
 * The stored section content, merged over the defaults.
 *
 * Always returns exactly KINDREDCARE_TIERS_COUNT tiers: a stored array that is
 * short, long or malformed is padded and trimmed against the defaults rather
 * than rendered as-is, because the layout and the scroll effect both assume
 * three.
 *
 * @return array<string, mixed>
 */
function kindredcare_tiers(): array {
	$defaults = kindredcare_tiers_defaults();
	$stored   = get_option( KINDREDCARE_TIERS_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );
	$rows    = is_array( $stored['tiers'] ?? null ) ? array_values( $stored['tiers'] ) : array();
	$tiers   = array();

	for ( $i = 0; $i < KINDREDCARE_TIERS_COUNT; $i++ ) {
		$tiers[] = kindredcare_tiers_row( $rows[ $i ] ?? null, $defaults['tiers'][ $i ] );
	}

	$section['tiers'] = $tiers;

	return $section;
}

/**
 * The modal element id for each tier, in order.
 *
 * Derived from the tier name so the card's button and the hidden modal are
 * generated from one source and cannot drift apart. Duplicate or empty names
 * fall back to position, because two elements sharing an id would make the
 * second modal unreachable.
 *
 * @param array<int, array<string, mixed>> $tiers Tier rows.
 * @return array<int, string>
 */
function kindredcare_tiers_ids( array $tiers ): array {
	$ids  = array();
	$used = array();

	foreach ( array_values( $tiers ) as $index => $tier ) {
		$slug = sanitize_title( (string) ( $tier['name'] ?? '' ) );

		if ( '' === $slug ) {
			$slug = (string) ( $index + 1 );
		}

		$id   = 'tier-' . $slug;
		$base = $id;
		$n    = 2;

		while ( in_array( $id, $used, true ) ) {
			$id = $base . '-' . $n;
			++$n;
		}

		$used[] = $id;
		$ids[]  = $id;
	}

	return $ids;
}

/**
 * The checks on a card that will actually render.
 *
 * @param array<string, mixed> $tier Tier row.
 * @return array<int, string>
 */
function kindredcare_tiers_active_checks( array $tier ): array {
	return array_values(
		array_filter(
			array_map( 'trim', $tier['checks'] ),
			static fn( string $check ): bool => '' !== $check
		)
	);
}

/**
 * The modal rows for a tier that will actually render.
 *
 * A row needs a term; a definition with no term above it has nothing to define.
 *
 * @param array<string, mixed> $tier Tier row.
 * @return array<int, array<string, string>>
 */
function kindredcare_tiers_active_modal_items( array $tier ): array {
	return array_values(
		array_filter(
			$tier['modal_items'],
			static fn( array $item ): bool => '' !== trim( (string) $item['term'] )
		)
	);
}

/**
 * Render the "Verification tiers" section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_tiers(): void {
	$section = kindredcare_tiers();
	$tiers   = $section['tiers'];
	$ids     = kindredcare_tiers_ids( $tiers );
	?>
	<!-- ================================================================ -->
	<!-- 07 — Verification tiers                                          -->
	<!-- "We do background checks" is what Care.com says. Be specific.     -->
	<!-- ================================================================ -->
	<section class="l-section s-tiers" aria-labelledby="tiers-title">
		<div class="l-container">
			<div class="c-section-head c-section-head--split" data-reveal="up">
				<div>
					<?php if ( '' !== trim( (string) $section['eyebrow'] ) ) : ?>
						<span class="u-eyebrow c-section-head__eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></span>
					<?php endif; ?>
					<h2 class="c-section-head__title" id="tiers-title">
						<?php echo esc_html( $section['title'] ); ?>
					</h2>
				</div>
				<?php if ( '' !== trim( (string) $section['lead'] ) ) : ?>
					<p class="c-section-head__lead">
						<?php echo esc_html( $section['lead'] ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="s-tiers__grid" data-tier-stack>
				<?php foreach ( $tiers as $index => $tier ) : ?>
					<?php $checks = kindredcare_tiers_active_checks( $tier ); ?>
					<article class="c-tier c-tier--<?php echo (int) $index + 1; ?>" data-tier-card>
						<div class="c-tier__top">
							<span class="c-tier__badge">
							<svg width="14" height="14" viewBox="0 0 18 18" fill="none" aria-hidden="true">
								<path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<?php echo esc_html( $tier['badge'] ); ?>
						</span>
							<?php
							/*
							 * Three pips, filled by the positional c-tier--N modifier. Not
							 * driven by the number of checks: it reads as "tier 2 of 3", and
							 * a card listing four checks is not four-thirds verified.
							 */
							?>
							<span class="c-tier__meter" aria-hidden="true"><i></i><i></i><i></i></span>
						</div>
						<h3 class="c-tier__name"><?php echo esc_html( $tier['name'] ); ?></h3>
						<?php if ( '' !== trim( (string) $tier['desc'] ) ) : ?>
							<p class="c-tier__desc"><?php echo esc_html( $tier['desc'] ); ?></p>
						<?php endif; ?>

						<?php if ( $checks ) : ?>
							<div class="c-tier__list">
								<?php foreach ( $checks as $check ) : ?>
									<p class="c-tier__item">
										<svg class="c-tier__check" width="16" height="16" viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" /></svg>
										<?php echo esc_html( $check ); ?>
									</p>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( '' !== trim( (string) $tier['unlocks'] ) ) : ?>
							<p class="c-tier__unlocks">
								<?php echo esc_html( $tier['unlocks'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( '' !== trim( (string) $section['more_label'] ) && kindredcare_tiers_active_modal_items( $tier ) ) : ?>
							<button class="c-tier__more" type="button" data-fancybox-inline data-src="#<?php echo esc_attr( $ids[ $index ] ); ?>">
								<?php echo esc_html( $section['more_label'] ); ?>
								<svg class="c-tier__more-icon" width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
									<path d="M4 12 12 4M12 4H5.5M12 4v6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</button>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Render the hidden "what's actually checked" modals.
 *
 * Called from footer.php inside the shared display:none wrapper. Kept in this
 * file rather than there because the ids have to be generated by the same
 * helper the buttons use — a modal whose id no longer matches its trigger is a
 * dead button, and that is not a failure the front page shows.
 */
function kindredcare_render_tier_modals(): void {
	$section = kindredcare_tiers();
	$tiers   = $section['tiers'];
	$ids     = kindredcare_tiers_ids( $tiers );

	foreach ( $tiers as $index => $tier ) {
		$items = kindredcare_tiers_active_modal_items( $tier );

		if ( ! $items ) {
			continue;
		}
		?>
		<div class="c-tier-modal" id="<?php echo esc_attr( $ids[ $index ] ); ?>">
			<h2 class="c-tier-modal__title"><?php echo esc_html( $tier['modal_title'] ); ?></h2>
			<dl class="c-tier-modal__list">
				<?php foreach ( $items as $item ) : ?>
					<div class="c-tier-modal__item">
						<dt class="c-tier-modal__term"><?php echo esc_html( $item['term'] ); ?></dt>
						<dd class="c-tier-modal__def">
							<?php echo esc_html( $item['def'] ); ?>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</div>
		<?php
	}
}
