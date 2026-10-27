<?php
/**
 * Trust strip — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_trust_strip`, edited from
 * KindredCare -> Trust strip in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * This strip does the job of the cut section 02. It carries specifics, never
 * numbers: the live site's counters all read zero, and a marketplace at zero
 * that advertises its totals is arguing against itself. Four verifiable claims
 * say the same thing without a figure that has to be true.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_TRUST_STRIP_OPTION = 'kindredcare_trust_strip';

/**
 * Most claims the strip will accept.
 *
 * Not a layout limit any more — past the marquee threshold the row holds as
 * many as it is given. This is a limit on the reader: a band that takes a
 * minute to come back round is a band nobody watches to the end.
 */
const KINDREDCARE_TRUST_STRIP_MAX_ITEMS = 12;

/**
 * Claim count above which the row travels instead of standing still.
 *
 * The still list is a four-column grid at lg and two at md
 * (scss/sections/_hero.scss:262-279), so four fills both cleanly and five
 * leaves an orphan. Past five there is no arrangement that tiles, and a moving
 * band is a better answer than a second row sitting three-quarters empty.
 */
const KINDREDCARE_TRUST_STRIP_MARQUEE_AT = 5;

/**
 * Seconds of travel per claim.
 *
 * The loop is timed from the number of claims rather than fixed, so the band
 * moves at one speed however long it is — a fixed duration would make every
 * claim fly past the moment a few more were added.
 */
const KINDREDCARE_TRUST_STRIP_SECONDS_PER_ITEM = 4;

/**
 * The strip's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_trust_strip_defaults(): array {
	return array(
		'items' => array(
			array(
				'text'    => 'Certn background checks',
				'enabled' => true,
			),
			array(
				'text'    => 'Identity verified',
				'enabled' => true,
			),
			array(
				'text'    => 'GPS-verified visits',
				'enabled' => true,
			),
			array(
				'text'    => 'Payment held by the platform',
				'enabled' => true,
			),
		),
	);
}

/**
 * Normalise one stored claim into its `text` / `enabled` pair.
 *
 * The strip shipped as a list of bare strings before each claim could be
 * switched off, and an install saved under that shape must not lose its content
 * on the first read. A string is therefore read as a claim that is shown —
 * which is what it meant when there was nothing else it could mean.
 *
 * @param mixed $row Stored row, either a string or a text/enabled pair.
 * @return array{text: string, enabled: bool}|null Null when the row is neither.
 */
function kindredcare_trust_strip_row( $row ): ?array {
	if ( is_scalar( $row ) ) {
		return array(
			'text'    => (string) $row,
			'enabled' => true,
		);
	}

	if ( is_array( $row ) ) {
		return array(
			'text'    => is_scalar( $row['text'] ?? null ) ? (string) $row['text'] : '',
			'enabled' => ! empty( $row['enabled'] ),
		);
	}

	return null;
}

/**
 * The stored strip content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_trust_strip(): array {
	$defaults = kindredcare_trust_strip_defaults();
	$stored   = get_option( KINDREDCARE_TRUST_STRIP_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$strip = array_merge( $defaults, $stored );

	$items = array();
	if ( is_array( $stored['items'] ?? null ) && $stored['items'] ) {
		foreach ( array_values( $stored['items'] ) as $row ) {
			$row = kindredcare_trust_strip_row( $row );
			if ( null !== $row ) {
				$items[] = $row;
			}
		}
	}
	$strip['items'] = $items ?: $defaults['items'];

	return $strip;
}

/**
 * The claims that will actually render.
 *
 * A claim switched off is kept in the option — the editor is hiding it, not
 * deleting it — and simply does not reach the page.
 *
 * @param array<string, mixed> $strip Strip content.
 * @return array<int, string>
 */
function kindredcare_trust_strip_active_items( array $strip ): array {
	$active = array();

	foreach ( $strip['items'] as $row ) {
		$row = kindredcare_trust_strip_row( $row );

		if ( null === $row || ! $row['enabled'] ) {
			continue;
		}

		$text = trim( $row['text'] );
		if ( '' !== $text ) {
			$active[] = $text;
		}
	}

	return $active;
}

/**
 * Print one claim.
 *
 * @param string $item     Claim text.
 * @param bool   $repeated Whether this is the marquee's second copy, which is
 *                         decoration and must not be read out twice.
 */
function kindredcare_trust_strip_item( string $item, bool $repeated = false ): void {
	?>
	<li class="s-trust-strip__item"<?php echo $repeated ? ' aria-hidden="true"' : ''; ?>>
		<svg class="s-trust-strip__icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
			<path d="m4 9.2 3.2 3.2L14 5.6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
		</svg>
		<?php echo esc_html( $item ); ?>
	</li>
	<?php
}

/**
 * Render the trust strip.
 *
 * Markup is the static build's, unchanged, until there are enough claims to
 * need the marquee — which adds a class, a duration, and a second copy of the
 * run for the loop to hand over to.
 */
function kindredcare_render_trust_strip(): void {
	$items = kindredcare_trust_strip_active_items( kindredcare_trust_strip() );

	// Every claim switched off is a deliberate state, not an empty one: the
	// white band goes with them rather than being left as a blank rule under
	// the hero.
	if ( ! $items ) {
		return;
	}

	$marquee  = count( $items ) > KINDREDCARE_TRUST_STRIP_MARQUEE_AT;
	$duration = count( $items ) * KINDREDCARE_TRUST_STRIP_SECONDS_PER_ITEM;
	?>
	<!-- Trust strip — the job of the cut section 02. Specificity, never
		 numbers: it exists because the live site's counters all read zero. -->
	<div
		class="s-trust-strip<?php echo $marquee ? ' s-trust-strip--marquee' : ''; ?>"
		<?php
		/*
		 * The duration is content, not design: it is the claim count times a
		 * fixed seconds-per-claim, so it cannot live in the stylesheet.
		 */
		if ( $marquee ) {
			printf( 'style="--trust-marquee-duration:%ds"', (int) $duration );
		}
		?>
	>
		<div class="l-container">
			<ul class="s-trust-strip__list">
				<?php foreach ( $items as $item ) : ?>
					<?php kindredcare_trust_strip_item( $item ); ?>
				<?php endforeach; ?>

				<?php
				/*
				 * The second run is what the loop scrolls into. It is the same
				 * claims again, so it is hidden from assistive technology — the
				 * list is read once and animates twice.
				 */
				if ( $marquee ) :
					?>
					<?php foreach ( $items as $item ) : ?>
						<?php kindredcare_trust_strip_item( $item, true ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>
	</div>
	<?php
}
