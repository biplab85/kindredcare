<?php
/**
 * FAQ structured data for the front page.
 *
 * Printed on wp_footer rather than from front-page.php so it lands after
 * </main>, where the static build had it. It is invisible either way, but the
 * template is a straight port and the port should stay straight.
 *
 * The cheapest durable SEO on the page — it is what produces the expandable
 * question list under the result.
 *
 * The pairs come from the FAQ option via kindredcare_faq_schema_pairs(), not
 * from a copy kept here. Google requires a rich result's answer to be present
 * on the page, so a hand-maintained block here would quietly become a
 * violation the first time somebody edited a question in the admin and not in
 * this file — and no part of the front end would show it. Each question carries
 * its own shortened schema wording on the same admin row as the visible copy.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', 'kindredcare_front_page_schema' );
/**
 * Print the FAQPage JSON-LD on the front page only.
 */
function kindredcare_front_page_schema(): void {
	if ( ! is_front_page() ) {
		return;
	}

	$pairs = kindredcare_faq_schema_pairs();

	// No marked questions means no FAQPage. An empty mainEntity is invalid.
	if ( ! $pairs ) {
		return;
	}

	$entities = array();

	foreach ( $pairs as $pair ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $pair['name'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $pair['text'],
			),
		);
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);

	/*
	 * UNESCAPED_UNICODE keeps the em dashes and the accented characters in the
	 * copy readable rather than turning them into — escapes; UNESCAPED_SLASHES
	 * leaves https:// alone. Both are cosmetic — the parsed document is identical
	 * either way — but this file is read by people checking the markup against
	 * the page.
	 */
	?>
	<script type="application/ld+json">
<?php echo wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>
	</script>
	<?php
}
