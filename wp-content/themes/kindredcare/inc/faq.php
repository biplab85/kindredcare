<?php
/**
 * "Questions" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_faq`, edited from KindredCare -> FAQ
 * in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * Each question also carries its own structured-data pair, and inc/schema.php
 * builds the FAQPage JSON-LD from this option rather than from a second copy.
 * That coupling is the point: Google requires that a rich result's answer be
 * present on the page, so a hand-maintained schema block silently becomes a
 * violation the first time someone edits a question here and not there — and
 * nothing on the front end would show it.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_FAQ_OPTION = 'kindredcare_faq';

/**
 * Most questions the accordion will accept.
 *
 * Not a layout limit — the column scrolls. It is an attention limit: a visitor
 * scanning for the one objection blocking them stops reading a list this long,
 * and the plan's own review question is whether a section earns its scroll
 * length.
 */
const KINDREDCARE_FAQ_MAX_ITEMS = 12;

/**
 * The section's shipped defaults.
 *
 * `schema` marks a question for inclusion in the FAQPage JSON-LD. The five
 * marked here are the five the static build shipped in its schema block, in the
 * same order, with the same condensed wording — see kindredcare_faq_schema_pairs().
 *
 * @return array<string, mixed>
 */
function kindredcare_faq_defaults(): array {
	return array(
		'eyebrow'         => 'Questions',
		'title'           => 'The things families ask first.',
		'aside_title'     => 'Still not sure?',
		'aside_text'      => 'Call us and speak to a person. We would rather answer your questions than have you guess.',
		'aside_cta_label' => '+1 (437) 665-8486',
		'aside_cta_url'   => 'tel:+14376658486',
		'items'           => array(
			array(
				'question'       => 'Are caregivers background checked?',
				'answer'         => 'Yes — every caregiver on the marketplace has passed identity verification and a CPIC criminal record check through Certn before their listing goes live. Anyone offering personal care also passes a Vulnerable Sector Check, which is the same check required to work with children and vulnerable adults in Ontario. The badge on each listing tells you which tier they hold.',
				'schema'         => true,
				'schema_question' => 'Are caregivers background checked?',
				'schema_answer'  => 'Every caregiver on the marketplace has passed identity verification and a CPIC criminal record check through Certn before their listing goes live. Anyone offering personal care also passes a Vulnerable Sector Check.',
			),
			array(
				'question'       => 'Are you an agency?',
				'answer'         => 'No. KindredCare is a marketplace — we connect you directly with independent caregivers and handle verification, booking and payment. We do not employ caregivers and we do not train them. That is why the fee is 7.5% rather than an agency\'s 30–40% cut, and why you choose the specific person rather than being assigned whoever is free.',
				'schema'         => true,
				'schema_question' => 'Is KindredCare an agency?',
				'schema_answer'  => 'No. KindredCare is a marketplace connecting families directly with independent caregivers, handling verification, booking and payment. We do not employ caregivers. The platform fee is 7.5%.',
			),
			array(
				'question'       => 'How does payment work?',
				'answer'         => 'Your card is authorised when the caregiver accepts the booking, but you are only charged after the visit is completed and checked out. We deduct 7.5% and pay the caregiver the rest on a set schedule. No cash changes hands and there is nothing to invoice.',
				'schema'         => true,
				'schema_question' => 'How does payment work?',
				'schema_answer'  => 'Your card is authorised when the caregiver accepts the booking and charged after the visit is completed. KindredCare deducts 7.5% and pays the caregiver the remainder on a set schedule.',
			),
			array(
				'question'       => 'What if we don\'t get along with someone?',
				'answer'         => 'You are never locked in. Every booking is a single visit or a recurring slot you can stop at any time — there is no contract and no minimum hours. Book someone else and, if something went wrong, tell us: reviews and reports feed directly into whether a caregiver stays on the platform.',
				'schema'         => false,
				'schema_question' => '',
				'schema_answer'  => '',
			),
			array(
				'question'       => 'Can we book the same person every week?',
				'answer'         => 'Yes, and most families do. You can set a repeating weekly or biweekly booking with the same caregiver. Continuity is the thing families most often say agencies get wrong, and here you are booking a specific person rather than a slot.',
				'schema'         => true,
				'schema_question' => 'Can we book the same caregiver every week?',
				'schema_answer'  => 'Yes. You can set a repeating weekly or biweekly booking with the same caregiver, and you are booking a specific person rather than a slot.',
			),
			array(
				'question'       => 'Who is responsible if something goes wrong?',
				'answer'         => 'Caregivers on KindredCare are independent contractors, not our employees, and our terms set that out plainly. What we are responsible for is the verification behind every listing, the record of every visit, and the process for handling a complaint. Report an incident and we investigate it, with the messages and visit logs to hand.',
				'schema'         => false,
				'schema_question' => '',
				'schema_answer'  => '',
			),
			array(
				'question'       => 'Do you serve my area?',
				'answer'         => 'We are starting in Durham Region — Oshawa, Whitby, Ajax, Pickering and Clarington — and expanding across the GTA from there. If you are just outside, leave us your email and we will tell you the moment we reach you.',
				'schema'         => true,
				'schema_question' => 'Which areas does KindredCare serve?',
				'schema_answer'  => 'KindredCare is starting in Durham Region, Ontario — Oshawa, Whitby, Ajax, Pickering and Clarington — and expanding across the Greater Toronto Area.',
			),
		),
	);
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_faq(): array {
	$defaults = kindredcare_faq_defaults();
	$stored   = get_option( KINDREDCARE_FAQ_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section = array_merge( $defaults, $stored );

	$items = array();
	if ( is_array( $stored['items'] ?? null ) && $stored['items'] ) {
		foreach ( array_values( $stored['items'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$items[] = array(
				'question'        => (string) ( $row['question'] ?? '' ),
				'answer'          => (string) ( $row['answer'] ?? '' ),
				'schema'          => ! empty( $row['schema'] ),
				'schema_question' => (string) ( $row['schema_question'] ?? '' ),
				'schema_answer'   => (string) ( $row['schema_answer'] ?? '' ),
			);
		}
	}
	$section['items'] = $items ?: $defaults['items'];

	return $section;
}

/**
 * The questions that will actually render.
 *
 * A question needs both halves: a summary with no answer behind it is an
 * accordion that opens onto nothing.
 *
 * @param array<string, mixed> $section Section content.
 * @return array<int, array<string, mixed>>
 */
function kindredcare_faq_active_items( array $section ): array {
	return array_values(
		array_filter(
			$section['items'],
			static fn( array $item ): bool => '' !== trim( (string) $item['question'] ) && '' !== trim( (string) $item['answer'] )
		)
	);
}

/**
 * The question/answer pairs for the FAQPage JSON-LD, in page order.
 *
 * Only questions marked for schema are included, and each contributes its own
 * shortened wording. The two are kept separate on purpose rather than reusing
 * the visible copy verbatim: a rich-result answer has to stand alone in a
 * search listing with no surrounding page, so it drops the conversational
 * opener and leads with the fact. What it must not do is contradict the page,
 * which is why both live on the same screen, on the same row.
 *
 * A question marked for schema with either field left blank falls back to the
 * visible copy, so a new question added by an editor still produces valid,
 * on-page-truthful structured data.
 *
 * @return array<int, array{name: string, text: string}>
 */
function kindredcare_faq_schema_pairs(): array {
	$section = kindredcare_faq();
	$pairs   = array();

	foreach ( kindredcare_faq_active_items( $section ) as $item ) {
		if ( empty( $item['schema'] ) ) {
			continue;
		}

		$name = trim( (string) $item['schema_question'] );
		$text = trim( (string) $item['schema_answer'] );

		$pairs[] = array(
			'name' => '' !== $name ? $name : trim( (string) $item['question'] ),
			'text' => '' !== $text ? $text : trim( (string) $item['answer'] ),
		);
	}

	return $pairs;
}

/**
 * Render the "Questions" section.
 *
 * Markup is the static build's, unchanged. The accordion is native
 * <details>/<summary> so it works with JavaScript disabled; GSAP only animates
 * the height.
 */
function kindredcare_render_faq(): void {
	$section = kindredcare_faq();
	$items   = kindredcare_faq_active_items( $section );
	$cta_url = trim( (string) $section['aside_cta_url'] );
	?>
	<!-- ================================================================ -->
	<!-- 12 — FAQ                                                         -->
	<!-- ================================================================ -->
	<section class="l-section s-faq" id="faq" aria-labelledby="faq-title">
		<div class="l-container">
			<div class="s-faq__layout">
				<div data-reveal="left">
					<?php if ( '' !== trim( (string) $section['eyebrow'] ) ) : ?>
						<span class="u-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></span>
					<?php endif; ?>
					<h2 class="c-section-head__title" id="faq-title"><?php echo esc_html( $section['title'] ); ?></h2>

					<?php if ( '' !== trim( (string) $section['aside_title'] ) || '' !== trim( (string) $section['aside_text'] ) ) : ?>
						<div class="s-faq__aside">
							<?php if ( '' !== trim( (string) $section['aside_title'] ) ) : ?>
								<p class="s-faq__aside-title"><?php echo esc_html( $section['aside_title'] ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== trim( (string) $section['aside_text'] ) ) : ?>
								<p class="s-faq__aside-text">
									<?php echo esc_html( $section['aside_text'] ); ?>
								</p>
							<?php endif; ?>
							<?php if ( '' !== trim( (string) $section['aside_cta_label'] ) && '' !== $cta_url ) : ?>
								<a class="c-btn c-btn--outline" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $section['aside_cta_label'] ); ?></a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="s-faq__list" data-reveal="up">
					<?php foreach ( $items as $item ) : ?>
						<details class="c-accordion" data-accordion>
							<summary class="c-accordion__summary">
								<?php echo esc_html( $item['question'] ); ?>
								<span class="c-accordion__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg></span>
							</summary>
							<div class="c-accordion__body" data-accordion-body>
								<p class="c-accordion__text">
									<?php echo esc_html( $item['answer'] ); ?>
								</p>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}
