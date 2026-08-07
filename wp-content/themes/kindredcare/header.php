<?php
/**
 * Document head, top bar, and site header.
 *
 * Structure is unchanged from kindredcare-home/src/index.html lines 79-164:
 * skip link, dark utility strip, then logo · nav · search · circular menu
 * button. What changed is where the content comes from — see inc/customizer.php
 * for the strip and inc/template-functions.php for each renderer.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
		<?php wp_head(); ?>
	</head>

	<body <?php body_class(); ?>>
		<?php wp_body_open(); ?>

		<a class="u-skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'kindredcare' ); ?></a>

		<?php
		/*
		 * Top utility strip — dark bar, note left, phone + CTA right. Carries
		 * the caregiver side so the bottom app bar can own the family side;
		 * neither audience is buried. Hidden below md in CSS, and switchable
		 * off entirely under Customizer -> KindredCare -> Top bar.
		 */
		kindredcare_topbar();
		?>

		<!-- ============================================================== -->
		<!-- 00 — Header                                                    -->
		<!-- Reference structure: logo · nav · search · circular menu button -->
		<!-- ============================================================== -->
		<header class="l-header" data-header>
			<div class="l-container l-header__inner">
				<?php
				// Site Identity -> Logo. Falls back to the bundled mark.
				kindredcare_render_brand();

				// Menus -> "Header — desktop bar". Falls back to the five ported links.
				kindredcare_render_header_nav();
				?>

				<div class="l-header__tools">
					<?php kindredcare_render_header_search(); ?>

					<button
						class="c-menu-btn"
						type="button"
						aria-label="<?php esc_attr_e( 'Open menu', 'kindredcare' ); ?>"
						aria-expanded="false"
						aria-controls="site-menu"
						data-offcanvas-open
					>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
						</svg>
					</button>
				</div>
			</div>
		</header>

		<main id="main">
