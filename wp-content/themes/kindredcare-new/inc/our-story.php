<?php
/**
 * "Our story" section — stored content and renderer.
 *
 * Stored in a single option, `kindredcare_our_story`, edited from
 * KindredCare -> Our story in the admin.
 *
 * Every default is the exact string the static build shipped, so a fresh
 * install renders identically to kindredcare-home/src/index.html.
 *
 * Plan section 01b. Sekare's .about-founded-area, matched: portrait image left
 * with a rotated label and a heart badge straddling its corner, copy right
 * ending in a phone + email row.
 *
 * The reference heading reads "Founded in 2000 … a trusted name for 24 years".
 * KindredCare has neither a 2000 founding nor 24 years, and inventing them is
 * the exact failure the plan calls out. Same sentence shape, true claims: who
 * built it and where it starts. That constraint is the reason the heading is a
 * plain sentence here rather than a number the editor is invited to inflate.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_OUR_STORY_OPTION = 'kindredcare_our_story';

/**
 * The section's shipped defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_our_story_defaults(): array {
	return array(
		'image'       => 0,
		'image_alt'   => 'A caregiver sitting and talking with an older woman at home',
		'vertical'    => 'Chosen by you, every visit',
		'title'       => 'Starting in GTA, KindredCare is built by four founders who have spent their careers in care, medicine and finance.',
		'brand'       => 'KindredCare',
		'text'        => 'We each went looking for care for someone we love, and found the same thing every time — agencies that cost a fortune and send a different stranger each week. So we built the alternative we wanted: verified local caregivers, booked by the hour, and the person who shows up is the person you chose.',
		'phone_label' => 'Phone',
		'phone'       => '+1 (437) 665-8486',
		'email_label' => 'Email',
		'email'       => 'info@kindredcare.ca',
	);
}

/**
 * The bundled portrait, theme-relative.
 */
function kindredcare_our_story_bundled_image(): string {
	return 'assets/img/about/third-about.jpg';
}

/**
 * The stored section content, merged over the defaults.
 *
 * @return array<string, mixed>
 */
function kindredcare_our_story(): array {
	$defaults = kindredcare_our_story_defaults();
	$stored   = get_option( KINDREDCARE_OUR_STORY_OPTION, array() );

	if ( ! is_array( $stored ) || ! $stored ) {
		return $defaults;
	}

	$section          = array_merge( $defaults, $stored );
	$section['image'] = absint( $section['image'] );

	return $section;
}

/**
 * The heading, with the brand word wrapped for its own styling.
 *
 * The static markup carried `<span class="s-founded__brand">KindredCare</span>`
 * inside the sentence. Splitting the heading into three stored fields to keep
 * that would make the editor assemble a sentence out of parts; instead the whole
 * sentence is one field and the word to lift is another, matched in place.
 *
 * The title is escaped *before* the span is injected and the needle is escaped
 * the same way, so the only markup that can reach the page is the span this
 * function writes itself — the stored values cannot contribute any.
 *
 * @param string $title Heading sentence.
 * @param string $brand Word or phrase within it to lift, may be empty.
 * @return string Escaped heading HTML.
 */
function kindredcare_our_story_title_html( string $title, string $brand ): string {
	$title = esc_html( $title );
	$brand = trim( $brand );

	if ( '' === $brand ) {
		return $title;
	}

	$needle   = esc_html( $brand );
	$position = strpos( $title, $needle );

	// A brand word the editor has since rewritten out of the sentence simply
	// leaves the heading unstyled rather than appending a stray span.
	if ( false === $position ) {
		return $title;
	}

	return substr_replace(
		$title,
		'<span class="s-founded__brand">' . $needle . '</span>',
		$position,
		strlen( $needle )
	);
}

/**
 * Render the "Our story" section.
 *
 * Markup is the static build's, unchanged.
 */
function kindredcare_render_our_story(): void {
	$section  = kindredcare_our_story();
	$image    = (int) $section['image'];
	$vertical = trim( (string) $section['vertical'] );
	$text     = trim( (string) $section['text'] );
	$phone    = trim( (string) $section['phone'] );
	$email    = trim( (string) $section['email'] );
	$tel      = kindredcare_tel_href( $phone );
	?>
	<!-- ================================================================ -->
	<!-- 01b — Our story / how to reach us                                -->
	<!-- Portrait left with a rotated label and a heart badge straddling   -->
	<!-- its corner, copy right ending in a phone + email row.             -->
	<!-- ================================================================ -->
	<section class="l-section s-founded" id="our-story" aria-labelledby="founded-title">
		<div class="l-container">
			<div class="s-founded__layout">
				<div class="s-founded__media" data-reveal="left">
					<div class="s-founded__frame">
						<?php
						if ( $image > 0 && wp_attachment_is_image( $image ) ) {
							/*
							 * Dimensions come from the media library rather than being
							 * hardcoded, so a replacement of a different shape does not
							 * reserve the wrong box and cost CLS.
							 */
							echo wp_get_attachment_image(
								$image,
								'full',
								false,
								array(
									'class'    => 's-founded__img',
									'alt'      => $section['image_alt'],
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							);
						} else {
							?>
							<img
								class="s-founded__img"
								src="<?php echo esc_url( get_theme_file_uri( kindredcare_our_story_bundled_image() ) ); ?>"
								width="941"
								height="1101"
								alt="<?php echo esc_attr( $section['image_alt'] ); ?>"
								loading="lazy"
								decoding="async"
							/>
							<?php
						}
						?>

						<?php if ( '' !== $vertical ) : ?>
							<p class="s-founded__vertical"><?php echo esc_html( $vertical ); ?></p>
						<?php endif; ?>

						<?php
						/*
						 * The heart is brand furniture rather than content — it is the
						 * device that ties this frame to the logo — so it stays bundled
						 * and is not offered as a picker.
						 */
						?>
						<div class="s-founded__badge">
							<img
								src="<?php echo esc_url( get_theme_file_uri( 'assets/img/about/founded-heart.svg' ) ); ?>"
								width="102"
								height="104"
								alt=""
								loading="lazy"
							/>
						</div>
					</div>
				</div>

				<div class="s-founded__content" data-reveal="up">
					<h2 class="s-founded__title" id="founded-title">
						<?php
						/*
						 * Escaped inside the helper, which then injects the one span it
						 * writes itself — see kindredcare_our_story_title_html().
						 */
						echo wp_kses(
							kindredcare_our_story_title_html( (string) $section['title'], (string) $section['brand'] ),
							array( 'span' => array( 'class' => array() ) )
						);
						?>
					</h2>

					<?php if ( '' !== $text ) : ?>
						<p class="s-founded__text"><?php echo esc_html( $text ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $phone || '' !== $email ) : ?>
						<div class="s-founded__contact">
							<?php if ( '' !== $phone ) : ?>
								<div class="s-founded__contact-item">
									<span class="s-founded__contact-label"><?php echo esc_html( $section['phone_label'] ); ?></span>
									<?php if ( '' !== $tel ) : ?>
										<a class="s-founded__contact-link" href="<?php echo esc_url( $tel ); ?>">
											<?php echo esc_html( $phone ); ?>
										</a>
									<?php else : ?>
										<span class="s-founded__contact-link"><?php echo esc_html( $phone ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<?php if ( '' !== $email ) : ?>
								<div class="s-founded__contact-item">
									<span class="s-founded__contact-label"><?php echo esc_html( $section['email_label'] ); ?></span>
									<a class="s-founded__contact-link" href="<?php echo esc_url( 'mailto:' . $email ); ?>">
										<?php echo esc_html( $email ); ?>
									</a>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}
