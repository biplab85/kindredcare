<?php
/**
 * Template Name: Services
 * Template Post Type: page
 *
 * The service catalogue.
 *
 * A named template rather than page-services.php, for the same reason the
 * contact page uses one: matching by slug means renaming the page silently
 * drops it back to the generic template. Chosen under Page Attributes instead,
 * so the page can be called "Services", "What we offer" or anything else and
 * still render this.
 *
 * The masthead follows the contact template — same kicker, same title and deck
 * classes — because the two are siblings in the reader's mind. Below it, a
 * two-up grid of photograph-led cards. See inc/services-api.php and
 * assets/css/services.css for the reasoning behind the card itself.
 *
 * The page's own editor content, if any, prints between the masthead and the
 * list. That is where an intro paragraph or a note about coverage areas goes.
 *
 * Nothing here talks to the platform. The catalogue is read from this page's
 * own post meta, imported by an editor pressing "Fetch" on the editing screen —
 * so this page renders at the speed of a database read and stays up whether the
 * platform is reachable or not.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$kc_services = kindredcare_services( (int) get_the_ID() );
$kc_asks     = kindredcare_services_task_count( $kc_services );

while ( have_posts() ) :
	the_post();
	?>

	<article class="s-article s-services">

		<header class="s-article__head s-article__head--no-media">
			<div class="s-article__head-content">
				<p class="s-article__kicker">
					<span class="s-article__kicker-rule" aria-hidden="true"></span>
					<?php esc_html_e( 'What you can book', 'kindredcare' ); ?>
				</p>

				<?php
				/*
				 * The page's own title. Renaming the page in the editor is the
				 * obvious way to change this heading, and a second copy of it in
				 * an option would only ever drift.
				 */
				?>
				<h1 class="s-article__title s-article__title--page"><?php the_title(); ?></h1>

				<?php if ( has_excerpt() ) : ?>
					<p class="s-article__deck"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<?php if ( $kc_services ) : ?>
					<?php
					/*
					 * Counted, not written. "Eight services" is a filing system;
					 * "32 things you can ask for" is the answer to what someone
					 * actually arrived wondering, and it stays true on its own
					 * as the platform adds categories.
					 */
					?>
					<?php
					/*
					 * Two counts, two strings. Folding them into one sentence
					 * would make a single plural form answer for both numbers,
					 * and they are not the same number — one service with four
					 * tasks would read "kinds of help".
					 */
					$kc_kinds = count( $kc_services );
					?>
					<p class="s-services__tally">
						<span class="s-services__tally-figure"><?php echo esc_html( number_format_i18n( $kc_kinds ) ); ?></span>
						<span>
							<?php echo esc_html( _n( 'kind of help', 'kinds of help', $kc_kinds, 'kindredcare' ) ); ?>
							<span aria-hidden="true">·</span>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: number of individual example tasks. */
									_n( '%s thing you can ask for', '%s things you can ask for', $kc_asks, 'kindredcare' ),
									number_format_i18n( $kc_asks )
								)
							);
							?>
						</span>
					</p>

					<?php
					/*
					 * Said once, here, instead of on every row. Every caregiver
					 * on the platform is verified, so a badge repeated against
					 * all eight services is the same sentence eight times. When
					 * the tiers actually differ the rows print their own — see
					 * kindredcare_services_tiers_vary().
					 */
					?>
					<?php if ( ! kindredcare_services_tiers_vary( $kc_services ) ) : ?>
						<p class="s-services__assurance">
							<span class="s-services__assurance-dot" aria-hidden="true"></span>
							<?php esc_html_e( 'Every caregiver is background-checked and verified before their first visit.', 'kindredcare' ); ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</header>

		<?php
		/*
		 * Optional editor copy. Wrapped in c-prose so a paragraph typed in the
		 * block editor picks up the same measure and rhythm as the body of a
		 * blog post rather than the full container width.
		 */
		$kc_content = trim( (string) get_the_content() );

		if ( '' !== $kc_content ) :
			?>
			<div class="l-container">
				<div class="s-services__intro c-prose">
					<?php the_content(); ?>
				</div>
			</div>
			<?php
		endif;
		?>

		<?php if ( $kc_services ) : ?>

			<div class="l-container">
				<?php kindredcare_render_services_index( $kc_services ); ?>
			</div>

			<div class="l-container">
				<?php
				/*
				 * The closing action. Points at the contact page when one exists
				 * — booking runs through the platform, and until that hand-off
				 * is wired up an enquiry is the real next step. Falls back to
				 * the home page's final CTA anchor so the button is never dead.
				 */
				$kc_cta = kindredcare_contact_url() ?: kindredcare_anchor_url( 'final-cta' );
				?>
				<div class="s-services__cta">
					<p class="s-services__cta-text">
						<?php esc_html_e( 'Not sure what to ask for? Tell us about the situation and we’ll help you work it out.', 'kindredcare' ); ?>
					</p>
					<a class="c-btn c-btn--primary c-btn--lg" href="<?php echo esc_url( $kc_cta ); ?>">
						<?php esc_html_e( 'Talk to us', 'kindredcare' ); ?>
					</a>
				</div>
			</div>

		<?php else : ?>

			<div class="l-container">
				<?php
				/*
				 * Reached only when nothing has been imported yet — a page
				 * published before anyone pressed Fetch. A visitor sees a way
				 * forward rather than an empty page; an editor sees the reason
				 * and where to fix it, because being told which button to press
				 * beats wondering why the page is blank.
				 */
				?>
				<div class="s-services__empty">
					<p class="s-services__empty-text">
						<?php esc_html_e( 'Our service list is not available right now. Please get in touch and we will talk you through what we offer.', 'kindredcare' ); ?>
					</p>

					<?php if ( current_user_can( 'edit_post', (int) get_the_ID() ) ) : ?>
						<p class="s-services__empty-admin">
							<?php esc_html_e( 'Only you can see this: the catalogue has not been imported yet.', 'kindredcare' ); ?>
							<a href="<?php echo esc_url( (string) get_edit_post_link() ); ?>">
								<?php esc_html_e( 'Fetch it on the editing screen.', 'kindredcare' ); ?>
							</a>
						</p>
					<?php endif; ?>

					<a class="c-btn c-btn--primary c-btn--lg" href="<?php echo esc_url( kindredcare_contact_url() ?: kindredcare_anchor_url( 'final-cta' ) ); ?>">
						<?php esc_html_e( 'Talk to us', 'kindredcare' ); ?>
					</a>
				</div>
			</div>

		<?php endif; ?>
	</article>

	<?php
endwhile;

get_footer();
