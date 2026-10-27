<?php
/**
 * Admin: KindredCare -> FAQ.
 *
 * Same Settings API arrangement as the other section screens, and the same
 * furniture from inc/admin-ui.php.
 *
 * Each question also carries the pair that goes into the FAQPage structured
 * data, on the same row as the visible copy. Keeping them on one screen is the
 * whole point — see inc/faq.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_FAQ_MENU_SLUG = 'kindredcare-faq';

/**
 * Capability callback for this option group.
 */
function kindredcare_faq_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 21: after For caregivers (20), matching the page order. */
add_action( 'admin_menu', 'kindredcare_faq_admin_menu', 21 );
/**
 * Add the FAQ screen under the KindredCare menu.
 */
function kindredcare_faq_admin_menu(): void {
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'FAQ', 'kindredcare' ),
		__( 'FAQ', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_FAQ_MENU_SLUG,
		'kindredcare_faq_render_page'
	);
}

add_action( 'admin_init', 'kindredcare_faq_register_setting' );
/**
 * Register the section's option with the Settings API.
 */
function kindredcare_faq_register_setting(): void {
	register_setting(
		'kindredcare_faq_group',
		KINDREDCARE_FAQ_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kindredcare_faq_sanitize',
			'default'           => array(),
		)
	);
}

add_filter( 'option_page_capability_kindredcare_faq_group', 'kindredcare_faq_capability' );

/**
 * Sanitise the submitted section content.
 *
 * @param mixed $input Raw $_POST value for the option.
 * @return array<string, mixed> Clean option value.
 */
function kindredcare_faq_sanitize( $input ): array {
	$defaults = kindredcare_faq_defaults();

	if ( ! is_array( $input ) ) {
		return array();
	}

	$clean = array(
		'eyebrow'         => sanitize_text_field( $input['eyebrow'] ?? '' ),
		'title'           => sanitize_text_field( $input['title'] ?? '' ),
		'aside_title'     => sanitize_text_field( $input['aside_title'] ?? '' ),
		'aside_text'      => sanitize_textarea_field( $input['aside_text'] ?? '' ),
		'aside_cta_label' => sanitize_text_field( $input['aside_cta_label'] ?? '' ),
		/*
		 * esc_url_raw, which permits the tel: scheme WordPress allows by
		 * default — the button is a phone number, not a page link.
		 */
		'aside_cta_url'   => esc_url_raw( trim( (string) ( $input['aside_cta_url'] ?? '' ) ) ),
		'aside_cta_new_tab' => ! empty( $input['aside_cta_new_tab'] ),
		'items'           => array(),
	);

	/*
	 * The heading is the section's accessible name — aria-labelledby points at
	 * it — so an empty one would leave the section unnamed.
	 */
	if ( '' === trim( $clean['title'] ) ) {
		$clean['title'] = $defaults['title'];
	}

	$rows = is_array( $input['items'] ?? null ) ? array_values( $input['items'] ) : array();

	foreach ( $rows as $row ) {
		if ( count( $clean['items'] ) >= KINDREDCARE_FAQ_MAX_ITEMS ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}

		$question = sanitize_text_field( $row['question'] ?? '' );
		$answer   = sanitize_textarea_field( $row['answer'] ?? '' );

		// An accordion that opens onto nothing is worse than one fewer question.
		if ( '' === trim( $question ) || '' === trim( $answer ) ) {
			continue;
		}

		$clean['items'][] = array(
			'question'        => $question,
			'answer'          => $answer,
			'schema'          => ! empty( $row['schema'] ),
			'schema_question' => sanitize_text_field( $row['schema_question'] ?? '' ),
			'schema_answer'   => sanitize_textarea_field( $row['schema_answer'] ?? '' ),
		);
	}

	if ( ! $clean['items'] ) {
		$clean['items'] = $defaults['items'];
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_faq_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_faq_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_FAQ_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/**
 * Print one question row, as a collapsed accordion.
 *
 * @param int                  $index    Row position.
 * @param array<string, mixed> $item     Row data.
 * @param bool                 $expanded Whether to render it open.
 */
function kindredcare_faq_item_row( int $index, array $item, bool $expanded = false ): void {
	$option  = KINDREDCARE_FAQ_OPTION;
	$base    = $option . '[items][' . $index . ']';
	$body_id = 'kc-faq-' . $index . '-body';
	?>
	<div class="kc-repeat__item kc-acc" data-kc-item>
		<div class="kc-acc__head">
			<button
				type="button"
				class="kc-acc__toggle"
				aria-expanded="<?php echo $expanded ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( $body_id ); ?>"
				data-kc-acc-toggle
			>
				<span class="kc-repeat__num"><?php echo (int) $index + 1; ?></span>
				<span
					class="kc-acc__title"
					data-kc-acc-title
					data-empty="<?php esc_attr_e( 'Untitled question', 'kindredcare' ); ?>"
				><?php echo esc_html( (string) ( $item['question'] ?? '' ) ); ?></span>
			</button>

			<div class="kc-repeat__tools">
				<?php
				kindredcare_admin_icon_button(
					'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
					__( 'Remove this question', 'kindredcare' ),
					array( 'kc-remove' => '1' ),
					true
				);
				?>
				<button
					type="button"
					class="kc-acc__chevron"
					tabindex="-1"
					aria-hidden="true"
					data-kc-acc-chevron
				>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="m6 9 6 6 6-6" />
					</svg>
				</button>
			</div>
		</div>

		<div class="kc-acc__body kc-acc__body--full" id="<?php echo esc_attr( $body_id ); ?>" <?php echo $expanded ? '' : 'hidden'; ?>>
			<div class="kc-field" style="margin:0">
				<label class="kc-field__label" for="kc-faq-<?php echo (int) $index; ?>-question">
					<?php esc_html_e( 'Question', 'kindredcare' ); ?>
				</label>
				<input
					type="text"
					id="kc-faq-<?php echo (int) $index; ?>-question"
					name="<?php echo esc_attr( $base ); ?>[question]"
					value="<?php echo esc_attr( (string) ( $item['question'] ?? '' ) ); ?>"
					data-kc-acc-label
				/>
				<p class="kc-field__hint"><?php esc_html_e( 'Phrase it the way a worried family would actually say it, not the way we would.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field" style="margin-bottom:0">
				<label class="kc-field__label" for="kc-faq-<?php echo (int) $index; ?>-answer">
					<?php esc_html_e( 'Answer', 'kindredcare' ); ?>
				</label>
				<textarea
					id="kc-faq-<?php echo (int) $index; ?>-answer"
					name="<?php echo esc_attr( $base ); ?>[answer]"
					rows="5"
				><?php echo esc_textarea( (string) ( $item['answer'] ?? '' ) ); ?></textarea>
				<p class="kc-field__hint"><?php esc_html_e( 'A question with no answer is dropped when saved. Answers about liability, employment or what we are responsible for must match the terms of use — this is the page families quote back.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-subpanel">
				<div class="kc-field--inline" style="margin-bottom:12px">
					<?php
					kindredcare_admin_toggle(
						$base . '[schema]',
						! empty( $item['schema'] ),
						__( 'Offer this one to Google', 'kindredcare' ),
						array( 'kc-toggle-row' => '1' )
					);
					?>
				</div>

				<p class="kc-field__hint" style="margin-top:0">
					<?php esc_html_e( 'Adds this question to the page\'s structured data, which is what can produce an expandable answer under the search result. Google requires the answer it shows to appear on the page, so these two fields must not contradict the answer above — they only shorten it.', 'kindredcare' ); ?>
				</p>

				<div class="kc-field" style="margin:0">
					<label class="kc-field__label" for="kc-faq-<?php echo (int) $index; ?>-sq">
						<?php esc_html_e( 'Question, for search', 'kindredcare' ); ?>
					</label>
					<input
						type="text"
						id="kc-faq-<?php echo (int) $index; ?>-sq"
						name="<?php echo esc_attr( $base ); ?>[schema_question]"
						value="<?php echo esc_attr( (string) ( $item['schema_question'] ?? '' ) ); ?>"
					/>
					<p class="kc-field__hint"><?php esc_html_e( 'A search result has no surrounding page, so "Are you an agency?" becomes "Is KindredCare an agency?". Leave blank to reuse the question above.', 'kindredcare' ); ?></p>
				</div>

				<div class="kc-field" style="margin-bottom:0">
					<label class="kc-field__label" for="kc-faq-<?php echo (int) $index; ?>-sa">
						<?php esc_html_e( 'Answer, for search', 'kindredcare' ); ?>
					</label>
					<textarea
						id="kc-faq-<?php echo (int) $index; ?>-sa"
						name="<?php echo esc_attr( $base ); ?>[schema_answer]"
						rows="3"
					><?php echo esc_textarea( (string) ( $item['schema_answer'] ?? '' ) ); ?></textarea>
					<p class="kc-field__hint"><?php esc_html_e( 'Two sentences at most, leading with the fact rather than a conversational opener. Leave blank to reuse the full answer above.', 'kindredcare' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the FAQ admin screen.
 */
function kindredcare_faq_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$section = kindredcare_faq();
	$option  = KINDREDCARE_FAQ_OPTION;
	$items   = $section['items'];
	$schema  = count( kindredcare_faq_schema_pairs() );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Front page', 'kindredcare' ),
			__( 'FAQ', 'kindredcare' ),
			sprintf(
				/* translators: %s: link to the section on the front page. */
				esc_html__( 'The objections that block a booking, answered on the page rather than behind a signup. Also the cheapest durable SEO the front page has. %s', 'kindredcare' ),
				'<a href="' . esc_url( home_url( '/#faq' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it on the front page', 'kindredcare' ) . '</a>'
			)
		);
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_faq_group' ); ?>

			<!-- Heading and the call box ---------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 7V5h16v2M9 20h6M12 5v15' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Heading and the call box', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'The left column: the heading, and the box offering a phone number instead.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-field">
						<label class="kc-field__label" for="kc-faq-eyebrow"><?php esc_html_e( 'Eyebrow', 'kindredcare' ); ?></label>
						<input type="text" id="kc-faq-eyebrow" name="<?php echo esc_attr( $option ); ?>[eyebrow]" value="<?php echo esc_attr( $section['eyebrow'] ); ?>" />
						<p class="kc-field__hint"><?php esc_html_e( 'The small line above the heading. Leave blank to remove it.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-faq-title"><?php esc_html_e( 'Heading', 'kindredcare' ); ?></label>
						<input type="text" id="kc-faq-title" name="<?php echo esc_attr( $option ); ?>[title]" value="<?php echo esc_attr( $section['title'] ); ?>" required />
						<p class="kc-field__hint"><?php esc_html_e( 'This names the section for screen readers, so it cannot be empty.', 'kindredcare' ); ?></p>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-faq-aside-title"><?php esc_html_e( 'Box heading', 'kindredcare' ); ?></label>
							<input type="text" id="kc-faq-aside-title" name="<?php echo esc_attr( $option ); ?>[aside_title]" value="<?php echo esc_attr( $section['aside_title'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-faq-aside-text"><?php esc_html_e( 'Box text', 'kindredcare' ); ?></label>
							<textarea id="kc-faq-aside-text" name="<?php echo esc_attr( $option ); ?>[aside_text]" rows="3"><?php echo esc_textarea( $section['aside_text'] ); ?></textarea>
						</div>
					</div>

					<div class="kc-field kc-field--split">
						<div>
							<label class="kc-field__label" for="kc-faq-aside-cta"><?php esc_html_e( 'Button text', 'kindredcare' ); ?></label>
							<input type="text" id="kc-faq-aside-cta" name="<?php echo esc_attr( $option ); ?>[aside_cta_label]" value="<?php echo esc_attr( $section['aside_cta_label'] ); ?>" />
						</div>
						<div>
							<label class="kc-field__label" for="kc-faq-aside-url"><?php esc_html_e( 'Button link', 'kindredcare' ); ?></label>
							<input type="text" id="kc-faq-aside-url" name="<?php echo esc_attr( $option ); ?>[aside_cta_url]" value="<?php echo esc_attr( $section['aside_cta_url'] ); ?>" />
							<p class="kc-field__hint"><?php esc_html_e( 'Leave blank to link to the Contact Us page. Any other link works too — a tel: link dials on a phone.', 'kindredcare' ); ?></p>
							<?php kindredcare_admin_new_tab_toggle( $option . '[aside_cta_new_tab]', kindredcare_link_new_tab( $section['aside_cta_new_tab'] ?? null, (string) $section['aside_cta_url'] ) ); ?>
						</div>
					</div>
				</div>
			</section>

			<!-- Questions -------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M9.1 9a3 3 0 1 1 4.2 2.7c-.8.4-1.3 1.1-1.3 2M12 17h.01' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Questions', 'kindredcare' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: 1: questions offered to search, 2: maximum questions. */
								esc_html__( '%1$s currently offered to Google · up to %2$s questions', 'kindredcare' ),
								(int) $schema,
								(int) KINDREDCARE_FAQ_MAX_ITEMS
							);
							?>
						</p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'Order matters — put the objection that blocks the most bookings first. The structured data follows this same order. If a question stops being true, <strong>fix it here rather than deleting it</strong>: a question families keep asking does not go away because the answer became inconvenient.', 'kindredcare' )
					);
					?>

					<div
						class="kc-repeat"
						data-kc-repeat="items"
						data-kc-max="<?php echo (int) KINDREDCARE_FAQ_MAX_ITEMS; ?>"
						data-kc-min="1"
					>
						<div data-kc-list>
							<?php foreach ( $items as $index => $item ) : ?>
								<?php kindredcare_faq_item_row( (int) $index, (array) $item ); ?>
							<?php endforeach; ?>
						</div>

						<div class="kc-repeat__add">
							<?php kindredcare_admin_add_button( __( 'Add question', 'kindredcare' ) ); ?>
							<span
								class="kc-repeat__count"
								data-kc-count
								data-template="<?php esc_attr_e( '%2$s questions · %3$s maximum', 'kindredcare' ); ?>"
							>
								<?php
								printf(
									/* translators: 1: questions total, 2: maximum allowed. */
									esc_html__( '%1$s questions · %2$s maximum', 'kindredcare' ),
									count( $items ),
									(int) KINDREDCARE_FAQ_MAX_ITEMS
								);
								?>
							</span>
						</div>

						<template data-kc-template>
							<?php
							kindredcare_faq_item_row(
								0,
								array(
									'question'        => '',
									'answer'          => '',
									'schema'          => false,
									'schema_question' => '',
									'schema_answer'   => '',
								)
							);
							?>
						</template>
					</div>
				</div>
			</section>

			<?php kindredcare_admin_savebar( __( 'Save FAQ', 'kindredcare' ) ); ?>
		</form>
	</div>
	<?php
}
