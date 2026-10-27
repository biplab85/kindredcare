<?php
/**
 * Nav walker for the desktop header bar.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Emits a menu as bare anchors instead of a nested list.
 *
 * .l-header__nav is `display:flex` with the links as its direct children
 * (scss/layout/_header.scss:40-49). WordPress's default output wraps every item
 * in <li> inside a <ul>, which would insert two non-flex levels between the
 * container and the links and collapse the row. Rather than restyle the ported
 * CSS to accommodate WordPress, the walker is bent to match the CSS — the
 * static markup is the reference, and it stays the reference.
 *
 * Submenus are dropped, not flattened: the bar has no dropdown design. Menu
 * depth is also capped at 1 by the wp_nav_menu() call, so this only guards
 * against a caller that forgets.
 */
class KindredCare_Header_Walker extends Walker_Nav_Menu {

	/**
	 * Open a submenu level. Intentionally silent.
	 *
	 * @param string   $output Accumulated markup, by reference.
	 * @param int      $depth  Current depth.
	 * @param stdClass $args   wp_nav_menu arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {}

	/**
	 * Close a submenu level. Intentionally silent.
	 *
	 * @param string   $output Accumulated markup, by reference.
	 * @param int      $depth  Current depth.
	 * @param stdClass $args   wp_nav_menu arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	/**
	 * Render one menu item as an anchor.
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

		$item    = $data_object;
		$classes = array( 'l-header__link' );

		/*
		 * WordPress flags the exact match and its ancestors separately. Both get
		 * the underline; only the exact match gets aria-current, because a
		 * parent of the current page is not the current page.
		 */
		$item_classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$is_current   = ! empty( $item->current )
			|| in_array( 'current-menu-item', $item_classes, true )
			|| in_array( 'current_page_item', $item_classes, true );

		/*
		 * `current_page_parent` is the one WordPress puts on the posts-page item
		 * while a single post is open — the article is not the blog index, but
		 * the blog is where it lives, so the bar should say so. Without it the
		 * header goes blank the moment a reader opens an article.
		 */
		$is_ancestor = in_array( 'current-menu-ancestor', $item_classes, true )
			|| in_array( 'current-menu-parent', $item_classes, true )
			|| in_array( 'current_page_parent', $item_classes, true )
			|| in_array( 'current_page_ancestor', $item_classes, true );

		if ( $is_current || $is_ancestor ) {
			$classes[] = 'is-active';
		}

		// Anything typed into the menu item's own "CSS Classes" field.
		foreach ( $item_classes as $custom ) {
			$custom = trim( (string) $custom );
			if ( '' !== $custom && ! str_starts_with( $custom, 'menu-item' ) && ! str_starts_with( $custom, 'current' ) && ! str_starts_with( $custom, 'page-item' ) ) {
				$classes[] = $custom;
			}
		}

		$attributes = array(
			'class' => implode( ' ', array_unique( array_filter( $classes ) ) ),
			'href'  => ! empty( $item->url ) ? $item->url : '',
		);

		if ( ! empty( $item->target ) ) {
			$attributes['target'] = $item->target;
		}

		/*
		 * A new tab plus an attacker-controllable destination is the classic
		 * reverse-tabnabbing shape, so noopener is added rather than left to the
		 * browser default.
		 */
		$rel = trim( (string) ( $item->xfn ?? '' ) );
		if ( '_blank' === ( $attributes['target'] ?? '' ) ) {
			$rel = trim( $rel . ' noopener' );
		}
		if ( '' !== $rel ) {
			$attributes['rel'] = $rel;
		}

		if ( ! empty( $item->attr_title ) ) {
			$attributes['title'] = $item->attr_title;
		}

		if ( $is_current ) {
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

		/*
		 * The title runs through the same filters core applies, so plugins and
		 * the menu admin's own entity encoding keep working, then is escaped.
		 */
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$output .= '<a' . $markup . '>' . kindredcare_header_nav_icon( (string) $title, (string) $item->url ) . esc_html( $title ) . '</a>';
	}

	/**
	 * Close a menu item.
	 *
	 * The anchor is already closed in start_el(); there is no wrapper element to
	 * balance, so this is a no-op.
	 *
	 * @param string   $output      Accumulated markup, by reference.
	 * @param WP_Post  $data_object Menu item post object.
	 * @param int      $depth       Current depth.
	 * @param stdClass $args        wp_nav_menu arguments.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {}
}
