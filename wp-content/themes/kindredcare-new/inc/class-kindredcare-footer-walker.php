<?php
/**
 * Nav walker for the footer's link columns.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Emits <li><a class="l-footer__link"> and nothing else.
 *
 * The footer columns are real lists, so unlike the header this keeps the <li> —
 * but core's default walker also stamps every item with `menu-item`,
 * `menu-item-type-custom`, `menu-item-object-custom`, an id, and a
 * `current-menu-item` class on the live page. None of that is styled, and the
 * ported markup carries a bare <li>. Emitting the extra attributes would make
 * the theme's output diverge from kindredcare-home/src/index.html for no gain.
 *
 * The accordion module (js/modules/footer-accordion.js) takes each heading's
 * next element sibling as the panel, so the <ul> this produces must remain the
 * heading's immediate sibling — see kindredcare_render_footer_menu_column().
 */
class KindredCare_Footer_Walker extends Walker_Nav_Menu {

	/**
	 * Open a submenu level. The columns are flat by design.
	 *
	 * @param string   $output Accumulated markup, by reference.
	 * @param int      $depth  Current depth.
	 * @param stdClass $args   wp_nav_menu arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {}

	/**
	 * Close a submenu level.
	 *
	 * @param string   $output Accumulated markup, by reference.
	 * @param int      $depth  Current depth.
	 * @param stdClass $args   wp_nav_menu arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	/**
	 * Render one menu item as a list item.
	 *
	 * @param string   $output            Accumulated markup, by reference.
	 * @param WP_Post  $data_object       Menu item post object.
	 * @param int      $depth             Current depth.
	 * @param stdClass $args              wp_nav_menu arguments.
	 * @param int      $current_object_id Current object id.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		if ( $depth > 0 ) {
			return;
		}

		$item = $data_object;

		$attributes = array(
			'class' => 'l-footer__link',
			'href'  => ! empty( $item->url ) ? $item->url : '',
		);

		if ( ! empty( $item->target ) ) {
			$attributes['target'] = $item->target;
		}

		$rel = trim( (string) ( $item->xfn ?? '' ) );
		if ( '_blank' === ( $attributes['target'] ?? '' ) ) {
			$rel = trim( $rel . ' noopener' );
		}
		if ( '' !== $rel ) {
			$attributes['rel'] = $rel;
		}

		if ( ! empty( $item->current ) ) {
			$attributes['aria-current'] = 'page';
		}

		$markup = '';
		foreach ( $attributes as $name => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			$value   = ( 'href' === $name ) ? esc_url( $value ) : esc_attr( $value );
			$markup .= sprintf( ' %s="%s"', esc_attr( $name ), $value );
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$output .= '<li><a' . $markup . '>' . esc_html( $title ) . '</a></li>';
	}

	/**
	 * Close a menu item. start_el() emits the whole <li>, so nothing to balance.
	 *
	 * @param string   $output      Accumulated markup, by reference.
	 * @param WP_Post  $data_object Menu item post object.
	 * @param int      $depth       Current depth.
	 * @param stdClass $args        wp_nav_menu arguments.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {}
}
