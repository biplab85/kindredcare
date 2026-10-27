<?php
/**
 * Document head and site header.
 *
 * Skip link, then logo · nav · Registration + Login buttons. The dark top
 * utility strip and the header search box were removed; the strip's buttons
 * moved into the header. See inc/customizer.php (Top bar section) for the
 * button labels/links and inc/template-functions.php for each renderer.
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

		<!-- ============================================================== -->
		<!-- 00 — Header                                                    -->
		<!-- Structure: logo · nav · Registration + Login                  -->
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
					<?php
					// Registration + Login, moved here from the removed top strip.
					kindredcare_render_header_account();
					?>
				</div>
			</div>
		</header>

		<main id="main">
