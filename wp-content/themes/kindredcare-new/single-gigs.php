<?php
/**
 * One listing.
 *
 * The job of this page is narrow: give someone enough to decide whether to ask
 * this particular person. So it is built round the two things an agency will
 * not show you — the rate, and the paperwork.
 *
 * The hero borrows the article hero's split, type panel left and photograph
 * bleeding off the right, because a listing arrives from the same place in the
 * reader's mind as a post does and should not feel like a different site. What
 * it adds is the rate, set at heading size beside the title.
 *
 * The signature is the rail. Every other care site says "background-checked"
 * and stops; the platform actually sends the individual checks and the date
 * each one cleared, so this page prints them by name with their dates. The home
 * page asks "What's actually checked?" — this is where that question is
 * answered, about a named person, rather than in the abstract.
 *
 * Nothing here fetches. Everything on the page is post meta written by an
 * import — see inc/gigs.php.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$kc_id        = (int) get_the_ID();
	$kc_caregiver = kindredcare_gig_caregiver( $kc_id );
	$kc_rate      = kindredcare_gig_rate( absint( get_post_meta( $kc_id, KINDREDCARE_GIG_META_RATE, true ) ) );
	$kc_tasks     = kindredcare_gig_tasks( $kc_id );
	/*
	 * The listing's own photograph.
	 *
	 * kindredcare_gig_image_id() resolves it in order of who chose it: a
	 * featured image set here first, then the picture the import downloaded
	 * from the platform. Never the remote URL — the page serves this site's own
	 * copy, at this site's own sizes, which is the whole point of importing it.
	 *
	 * A note for whoever reads this next to the seed data: the platform is not
	 * yet sending photographs *of the work*. Every photo_url it returns today is
	 * a portrait, and for most caregivers it is the same file as their own
	 * avatar — so the hero and the rail show one face twice. Where the two
	 * differ, the hero shows whatever the platform called the listing's photo.
	 * That is the platform's statement about the listing, and this template
	 * renders it rather than second-guessing it. Fix it there, not here.
	 */
	$kc_image     = kindredcare_gig_image_id( $kc_id );
	$kc_face      = kindredcare_gig_portrait_id( $kc_id );
	$kc_name      = (string) $kc_caregiver['display_name'];
	$kc_years     = absint( $kc_caregiver['years_of_experience'] );
	$kc_checks    = (array) $kc_caregiver['verification_checks'];
	$kc_siblings  = kindredcare_gig_sibling_gigs( $kc_id );
	$kc_terms     = get_the_terms( $kc_id, KINDREDCARE_GIG_TAXONOMY );
	$kc_term      = is_array( $kc_terms ) && $kc_terms ? $kc_terms[0] : null;
	/*
	 * The two calls to action on this page go to two different places, because
	 * they are two different intentions.
	 *
	 * The one in the hero belongs to the listing: someone who has read the rate
	 * and wants this person. That is a booking, and booking happens on the
	 * platform — the same address the header's Login button uses.
	 *
	 * The one in the rail belongs to the caregiver card: someone with a question
	 * about the person rather than a decision about the listing. That is an
	 * enquiry, and enquiries come to us.
	 *
	 * Both absolute, and both read from inc/gigs.php rather than written out
	 * here: the archive and the category archives print the same contact button,
	 * so the address lives in one place instead of five.
	 */
	$kc_hire      = kindredcare_gig_hire_url();
	$kc_contact   = kindredcare_gig_contact_url();
	$kc_since     = kindredcare_gig_date( (string) get_post_meta( $kc_id, KINDREDCARE_GIG_META_PUBLISHED, true ) );
	?>

	<article class="s-article s-gig">

		<!-- ============================================================== -->
		<!-- Hero — the article split, with the rate carried into it.        -->
		<!-- ============================================================== -->
		<header class="s-article__head<?php echo $kc_image ? '' : ' s-article__head--no-media'; ?>">
			<div class="s-article__head-content">

				<?php if ( $kc_term instanceof WP_Term ) : ?>
					<p class="s-article__kicker">
						<span class="s-article__kicker-rule" aria-hidden="true"></span>
						<a class="s-article__kicker-link" href="<?php echo esc_url( (string) get_term_link( $kc_term ) ); ?>">
							<?php echo esc_html( $kc_term->name ); ?>
						</a>
					</p>
				<?php endif; ?>

				<h1 class="s-article__title"><?php the_title(); ?></h1>

				<?php
				/*
				 * The rate and the person, side by side, on a ruled strip. They
				 * are the two facts that decide whether anyone reads further,
				 * and pairing them says the thing the page is arguing: this
				 * price belongs to this named, checked person.
				 */
				?>
				<div class="s-gig__facts">
					<?php if ( '' !== $kc_rate ) : ?>
						<p class="s-gig__rate">
							<?php /* Abbreviated for the eye, spelled out for the ear, and joined with no whitespace — see template-parts/gig-row.php. */ ?>
							<span class="s-gig__amount"><?php echo esc_html( $kc_rate ); ?></span><span class="s-gig__unit" aria-hidden="true"><?php esc_html_e( '/hr', 'kindredcare' ); ?></span>
							<span class="u-sr-only"><?php esc_html_e( 'an hour', 'kindredcare' ); ?></span>
						</p>
					<?php endif; ?>

					<?php if ( '' !== $kc_name ) : ?>
						<?php
						/*
						 * Name and years, no portrait. The rail below carries
						 * this caregiver's face, and at the top of the page both
						 * are on screen at once — the same person, twice, at two
						 * sizes, a few hundred pixels apart. One face, in the
						 * place where a face belongs: attached to the checks
						 * that say who it is.
						 *
						 * The pairing this strip exists to make survives without
						 * it. "$25 an hour · Sarah Mitchell, five years caring
						 * for people" is the pairing, said in type.
						 */
						?>
						<p class="s-gig__by">
							<span>
								<span class="s-gig__name"><?php echo esc_html( $kc_name ); ?></span>
								<?php if ( $kc_years ) : ?>
									<span class="s-gig__years">
										<?php
										printf(
											/* translators: %d: years of experience. */
											esc_html( _n( '%d year caring for people', '%d years caring for people', $kc_years, 'kindredcare' ) ),
											(int) $kc_years
										);
										?>
									</span>
								<?php endif; ?>
							</span>
						</p>
					<?php endif; ?>
				</div>

				<?php
				/*
				 * target="_blank" because this leaves the site for the platform,
				 * and someone part-way through comparing rates should still have
				 * the listing behind them when they come back. Matches the
				 * header's Login button, which goes to the same place.
				 */
				?>
				<a class="c-btn c-btn--primary c-btn--lg s-gig__ask" href="<?php echo esc_url( $kc_hire ); ?>" target="_blank">
					<?php esc_html_e( 'Hire Me', 'kindredcare' ); ?>
				</a>
			</div>

			<?php if ( $kc_image ) : ?>
				<div class="s-article__media">
					<?php
					/*
					 * The same picture again, thrown out of focus, as the
					 * surface the sharp one sits on.
					 *
					 * WHY: what the platform sends is a 300x300 portrait. The
					 * shared article hero stretches its media across half the
					 * window — around 760px here — and a 300px square blown up
					 * two and a half times is soft enough to see, which reads
					 * as a cheap photograph rather than a small one. Blur is
					 * the one treatment that makes an upscale invisible: at
					 * this radius there is no detail left to be soft, and what
					 * is left is the picture's own colour, which is a better
					 * ground for it than any fill this stylesheet could pick.
					 *
					 * Same attachment as the sharp copy below, so it is the
					 * same URL, the same cache entry and no second request.
					 *
					 * Decorative twice over — it is a blurred repeat of the
					 * image beside it, which itself is decorative — so it is
					 * hidden from assistive technology rather than described.
					 */
					echo wp_get_attachment_image(
						$kc_image,
						'full',
						false,
						array(
							'class'       => 's-gig__backdrop',
							'alt'         => '',
							'aria-hidden' => 'true',
						)
					);
					?>

					<div class="s-article__media-inner">
						<?php
						/*
						 * Full size, not a crop: the imported pictures are
						 * small squares, and asking for a 1200x630 that was
						 * never generated only returns the original anyway.
						 * This says so plainly instead of implying a crop that
						 * does not exist.
						 *
						 * Shown at its own square proportion now — see the note
						 * in gigs.css. A square source forced into a landscape
						 * half was cropping the top of the head off, which is
						 * the one thing you should not do to a portrait.
						 */
						echo wp_get_attachment_image(
							$kc_image,
							'full',
							false,
							array(
								'class'         => 's-article__img',
								'alt'           => '',
								'fetchpriority' => 'high',
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</header>

		<!-- ============================================================== -->
		<!-- Body left, the caregiver held beside it.                        -->
		<!-- ============================================================== -->
		<div class="l-container">
			<div class="s-gig__layout">

				<div class="s-gig__body">

					<?php
					/*
					 * The caregiver's own account of themselves, moved out of
					 * the rail and put at the top of the reading column.
					 *
					 * In the card it was eight lines of prose set at 15px in a
					 * 340px column, wedged between a list of languages and a row
					 * of tags — the longest thing in the card and the only part
					 * of it that was not a checkable fact. Out here it has the
					 * width to be read, and it opens the column in the person's
					 * own voice before the page gets to the terms.
					 *
					 * Deliberately not given a heading. It is not a section; it
					 * is the standfirst the two sections below it answer to.
					 */
					?>
					<?php
					/*
					 * A wrapper around the paragraph rather than the paragraph
					 * itself: the card wants the full column and the prose
					 * inside it wants a reading measure, and one element cannot
					 * be both widths at once.
					 */
					?>
					<?php if ( '' !== (string) $kc_caregiver['bio'] ) : ?>
						<div class="s-gig__bio">
							<p><?php echo esc_html( (string) $kc_caregiver['bio'] ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( $kc_tasks ) : ?>
						<section class="s-gig__block">
							<?php
							/*
							 * The mark before each heading is decorative and
							 * hidden: the heading names the section in words,
							 * and an accessible label here would have a screen
							 * reader announce it twice. Same drawing spec as
							 * every other icon in the theme — 24px box, no
							 * fill, 2px round-capped stroke in currentColor.
							 */
							?>
							<h2 class="s-gig__h2">
								<span class="s-gig__h2-icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
										<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
										<path d="M9 2h6a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1Z" />
										<path d="M8 12h8M8 16h5" />
									</svg>
								</span>
								<?php esc_html_e( 'What’s included', 'kindredcare' ); ?>
							</h2>
							<?php
							/*
							 * A short rule against each line, not a tick. A tick
							 * would read as a feature checklist someone has
							 * signed off; these are the things this caregiver
							 * said they would do, in their own words.
							 */
							?>
							<ul class="s-gig__tasks">
								<?php foreach ( $kc_tasks as $kc_task ) : ?>
									<li class="s-gig__task"><?php echo esc_html( $kc_task ); ?></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
						<section class="s-gig__block">
							<h2 class="s-gig__h2">
								<span class="s-gig__h2-icon" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
										<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8Z" />
									</svg>
								</span>
								<?php esc_html_e( 'In their words', 'kindredcare' ); ?>
							</h2>
							<div class="c-prose s-gig__prose"><?php the_content(); ?></div>
						</section>
					<?php endif; ?>

					<?php if ( '' !== $kc_since ) : ?>
						<p class="s-gig__posted">
							<?php
							printf(
								/* translators: %s: date the caregiver published the listing. */
								esc_html__( 'Posted %s', 'kindredcare' ),
								esc_html( $kc_since )
							);
							?>
						</p>
					<?php endif; ?>
				</div>

				<aside class="s-gig__rail" aria-labelledby="gig-caregiver">
					<div class="s-gig__rail-sticky">
						<div class="c-carer">

							<?php
							/*
							 * The portrait and who it is, on one row.
							 *
							 * Two wrappers, because there are two groupings and
							 * they nest: the face sits beside the identity, and
							 * the identity is a name with a badge under it. One
							 * flat list of three siblings cannot say that — it
							 * can only stack them, which is what the card did
							 * before and why the badge read as a third
							 * unrelated line rather than as something said
							 * about the name above it.
							 */
							?>
							<div class="c-carer__head">
								<?php if ( $kc_face ) : ?>
									<div class="c-carer__portrait">
										<?php
										echo wp_get_attachment_image(
											$kc_face,
											'thumbnail',
											false,
											array(
												'class' => 'c-carer__face',
												'alt'   => '',
											)
										);
										?>

										<?php
										/*
										 * The tick on the corner of the
										 * portrait, when the platform says the
										 * checks cleared.
										 *
										 * Decorative and hidden: the pill
										 * beside it says "Verified caregiver"
										 * in words and the record below prints
										 * every check by name with its date.
										 * This is the same fact a third time,
										 * put where the eye lands first.
										 *
										 * Leaf green, which is the colour this
										 * theme already reserves for something
										 * having been confirmed — the pill, the
										 * dot on a listing row, the ticks in
										 * the record.
										 */
										?>
										<?php if ( ! empty( $kc_caregiver['is_verified'] ) ) : ?>
											<span class="c-carer__seal" aria-hidden="true">
												<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false">
													<path d="m4 12 5 5L20 6" />
												</svg>
											</span>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<div class="c-carer__identity">
									<h2 class="c-carer__name" id="gig-caregiver"><?php echo esc_html( '' !== $kc_name ? $kc_name : __( 'Your caregiver', 'kindredcare' ) ); ?></h2>

									<?php if ( ! empty( $kc_caregiver['is_verified'] ) ) : ?>
										<p class="c-carer__badge">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
												<path d="m4 12 5 5L20 6" />
											</svg>
											<?php esc_html_e( 'Verified caregiver', 'kindredcare' ); ?>
										</p>
									<?php endif; ?>
								</div>
							</div>

							<?php
							/*
							 * Languages one per line under a label, rather than
							 * comma-joined into a sentence.
							 *
							 * A <div> and a <ul>, where this was a <p>: a list
							 * of languages is a list, and it could not be marked
							 * up as one inside a paragraph. The label is its own
							 * string now instead of the "Speaks %s" the sentence
							 * needed — the same words, but no longer a sentence
							 * a translator has to keep in one piece.
							 */
							?>
							<?php if ( ! empty( $kc_caregiver['languages'] ) ) : ?>
								<div class="c-carer__line">
									<span class="c-carer__line-label"><?php esc_html_e( 'Speaks', 'kindredcare' ); ?></span>

									<?php
									/*
									 * The mark on each language is drawn in CSS
									 * rather than repeated here. It is the same
									 * glyph on every row — a list bullet, not a
									 * fact about the individual language — and
									 * one rule in the stylesheet is a better
									 * home for that than an identical SVG
									 * pasted once per item by a loop.
									 */
									?>
									<ul class="c-carer__langs">
										<?php foreach ( (array) $kc_caregiver['languages'] as $kc_lang ) : ?>
											<li><?php echo esc_html( (string) $kc_lang ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php
							/*
							 * The bio used to sit here, between the languages
							 * and the tags. It is now the first thing in the
							 * body column — see the note there. What is left in
							 * this card is only what is checkable: who they
							 * are, what they speak, what has been verified and
							 * when.
							 */
							?>

							<?php if ( ! empty( $kc_caregiver['personality_tags'] ) ) : ?>
								<ul class="c-carer__tags">
									<?php foreach ( (array) $kc_caregiver['personality_tags'] as $kc_tag ) : ?>
										<li class="c-carer__tag"><?php echo esc_html( $kc_tag ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<?php if ( $kc_checks ) : ?>
								<?php
								/*
								 * The record, in full, with dates.
								 *
								 * This is the one thing on the site that turns
								 * "background-checked" from a claim into a
								 * document. Every check the platform holds is
								 * named and dated — a family can see that the
								 * criminal record check is from June and not
								 * from four years ago, which is the actual
								 * question behind the reassurance.
								 */
								?>
								<div class="c-carer__record">
									<h3 class="c-carer__record-title"><?php esc_html_e( 'Checks on file', 'kindredcare' ); ?></h3>
									<dl class="c-carer__checks">
										<?php foreach ( $kc_checks as $kc_check ) : ?>
											<?php
											if ( ! is_array( $kc_check ) ) {
												continue;
											}

											$kc_label = kindredcare_gig_check_label( (string) ( $kc_check['check_type'] ?? '' ) );
											$kc_when  = kindredcare_gig_date( (string) ( $kc_check['reviewed_at'] ?? '' ) );
											$kc_ok    = 'cleared' === (string) ( $kc_check['status'] ?? '' );

											if ( '' === $kc_label ) {
												continue;
											}
											?>
											<div class="c-carer__check<?php echo $kc_ok ? ' is-cleared' : ''; ?>">
												<dt>
													<?php
													/*
													 * Every row carries a mark, cleared or not.
													 *
													 * A tick only where a check has cleared — it
													 * is the whole meaning of the glyph and must
													 * not be spent on anything else. But printing
													 * it on some rows and nothing on others left
													 * the labels on an unverified caregiver's card
													 * starting at a different x from the ones on a
													 * verified caregiver's, and a record that
													 * cannot be read down a column stops being a
													 * record. An open circle says the same thing
													 * the absent tick did — this one has not
													 * cleared — and says it in the same place.
													 */
													?>
													<?php if ( $kc_ok ) : ?>
														<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
															<path d="m4 12 5 5L20 6" />
														</svg>
													<?php else : ?>
														<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
															<circle cx="12" cy="12" r="8" />
														</svg>
													<?php endif; ?>
													<?php echo esc_html( $kc_label ); ?>
												</dt>
												<dd>
													<?php
													if ( ! $kc_ok ) {
														echo esc_html( kindredcare_gig_check_status( (string) ( $kc_check['status'] ?? '' ) ) );
													} else {
														echo esc_html( '' !== $kc_when ? $kc_when : __( 'Cleared', 'kindredcare' ) );
													}
													?>
												</dd>
											</div>
										<?php endforeach; ?>
									</dl>
								</div>
							<?php endif; ?>

							<?php
							/*
							 * No target: in production this is a page on this
							 * same site, and opening your own contact page in a
							 * second tab is a way of losing people.
							 */
							?>
							<a class="c-btn c-btn--primary c-btn--block c-carer__ask" href="<?php echo esc_url( $kc_contact ); ?>">
								<?php esc_html_e( 'Contact Us', 'kindredcare' ); ?>
							</a>
						</div>
					</div>
				</aside>
			</div>
		</div>

		<?php if ( $kc_siblings ) : ?>
			<div class="l-container">
				<section class="s-gig__more">
					<?php
					/*
					 * The heading and the way out on one line. Both are about
					 * leaving this listing — one to the caregiver's other work,
					 * one to everybody's — so they belong on the same rule
					 * rather than a screenful apart.
					 */
					?>
					<div class="s-gig__more-head">
						<h2 class="s-gig__h2">
							<?php
							printf(
								/* translators: %s: the caregiver's name. */
								esc_html__( 'More from %s', 'kindredcare' ),
								esc_html( '' !== $kc_name ? $kc_name : __( 'this caregiver', 'kindredcare' ) )
							);
							?>
						</h2>

						<?php get_template_part( 'template-parts/gig-viewswitch' ); ?>

						<?php get_template_part( 'template-parts/gig-back-link' ); ?>
					</div>

					<ul class="s-gigs__list s-gigs__list--tight" data-reveal-group>
						<?php foreach ( $kc_siblings as $kc_sibling ) : ?>
							<?php
							get_template_part(
								'template-parts/gig-row',
								null,
								array(
									'id'      => (int) $kc_sibling,
									'compact' => true,
								)
							);
							?>
						<?php endforeach; ?>
					</ul>
				</section>
			</div>
		<?php else : ?>
			<?php
			/*
			 * No siblings, so no "More from" heading for the back link to sit
			 * beside. It stands on its own instead — the page still has to
			 * offer a way out.
			 */
			?>
			<div class="l-container">
				<?php get_template_part( 'template-parts/gig-back-link' ); ?>
			</div>
		<?php endif; ?>
	</article>

	<?php
endwhile;

get_footer();
