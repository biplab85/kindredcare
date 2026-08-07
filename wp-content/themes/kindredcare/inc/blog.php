<?php
/**
 * Blog — bylines, reading time, article processing, cards, pagination.
 *
 * The imported articles are plain Gutenberg output: headings, paragraphs, the
 * occasional list or table. Everything here exists to turn that into the
 * editorial layout the templates expect without editing a single post — the
 * source of truth stays the post content, and the presentation is derived from
 * it on render.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Words per minute used when a post carries no imported reading time.
 *
 * 200 is the figure Yoast uses, so a computed estimate and an imported one
 * cannot disagree by much.
 */
const KINDREDCARE_WPM = 200;

/**
 * A table of contents is only worth the column it sits in past this many
 * headings. Sixteen of the imported posts have no <h2> at all.
 */
const KINDREDCARE_TOC_MIN = 3;

/* -- Post meta ------------------------------------------------------------- */

/**
 * The credited author.
 *
 * Not the WordPress author: every imported article is signed "KindredCare team"
 * while the account behind it is a developer login. The meta wins where it is
 * set; anything authored locally falls back to the real display name.
 *
 * @param int|WP_Post|null $post Post to read.
 * @return string Byline, already escaped for HTML.
 */
function kindredcare_byline( $post = null ): string {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$byline = (string) get_post_meta( $post->ID, '_kc_byline', true );

	if ( '' === $byline ) {
		$byline = (string) get_the_author_meta( 'display_name', (int) $post->post_author );
	}

	return esc_html( $byline );
}

/**
 * Reading time in whole minutes, never zero.
 *
 * @param int|WP_Post|null $post Post to measure.
 * @return int Minutes.
 */
function kindredcare_reading_time( $post = null ): int {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return 1;
	}

	$stored = (int) get_post_meta( $post->ID, '_kc_reading_time', true );
	if ( $stored > 0 ) {
		return $stored;
	}

	$words = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );

	return max( 1, (int) ceil( $words / KINDREDCARE_WPM ) );
}

/**
 * The card/lead summary.
 *
 * Prefers the hand-cleaned excerpt written at import; falls back to WordPress's
 * own trimming so a locally authored post is not blank.
 *
 * @param int|WP_Post|null $post   Post to summarise.
 * @param int              $length Fallback trim length, in words.
 * @return string Plain text.
 */
function kindredcare_excerpt( $post = null, int $length = 28 ): string {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	if ( '' !== trim( $post->post_excerpt ) ) {
		return $post->post_excerpt;
	}

	return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $length, '…' );
}

/**
 * The article standfirst.
 *
 * The same source as the card excerpt, but ended on a full stop. A card can
 * carry a clause that trails into an ellipsis; directly under an <h1> the same
 * string reads as a sentence someone forgot to finish.
 *
 * Falls back to the ellipsised excerpt when there is no sentence boundary to
 * cut at — a deck that trails is better than no deck.
 *
 * @param int|WP_Post|null $post Post to summarise.
 * @return string Plain text.
 */
function kindredcare_deck( $post = null ): string {
	$text = kindredcare_excerpt( $post );

	if ( ! str_ends_with( $text, '…' ) ) {
		return $text;
	}

	$trimmed = rtrim( $text, '…' );

	// Best: a full stop. The deck then reads as a finished thought.
	$stop = max(
		(int) mb_strrpos( $trimmed, '. ' ),
		(int) mb_strrpos( $trimmed, '? ' ),
		(int) mb_strrpos( $trimmed, '! ' )
	);

	if ( $stop > 60 ) {
		return mb_substr( $trimmed, 0, $stop + 1 );
	}

	/*
	 * Failing that, a clause boundary. Long opening sentences are common in
	 * these articles and the 190-character excerpt can land inside one — which
	 * left decks trailing off mid-phrase, "…but as a…". Cutting at the last
	 * comma and closing with an ellipsis breaks in a place the reader expects.
	 */
	$clause = max(
		(int) mb_strrpos( $trimmed, ', ' ),
		(int) mb_strrpos( $trimmed, '; ' ),
		(int) mb_strrpos( $trimmed, ' — ' )
	);

	if ( $clause > 60 ) {
		return rtrim( mb_substr( $trimmed, 0, $clause ), ' ,;—' ) . '…';
	}

	return $text;
}

/**
 * The post's primary category, for the card chip and the article eyebrow.
 *
 * @param int|WP_Post|null $post Post to read.
 * @return WP_Term|null First category, or null when uncategorised.
 */
function kindredcare_primary_term( $post = null ): ?WP_Term {
	$terms = get_the_category( get_post( $post )->ID ?? 0 );

	return $terms && $terms[0] instanceof WP_Term ? $terms[0] : null;
}

/* -- Article body ---------------------------------------------------------- */

/**
 * The processed article body.
 *
 * Runs the usual `the_content` filters, then three passes the templates depend
 * on. Memoised per post because single.php renders it and the table of contents
 * reads it — parsing twice would double the work for one identical result.
 *
 * @param int|WP_Post|null $post Post to render.
 * @return string HTML, ready to echo.
 */
function kindredcare_article_html( $post = null ): string {
	static $cache = array();

	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	if ( isset( $cache[ $post->ID ] ) ) {
		return $cache[ $post->ID ];
	}

	/** This filter is documented in wp-includes/post-template.php */
	$html = (string) apply_filters( 'the_content', $post->post_content );
	$html = str_replace( ']]>', ']]&gt;', $html );

	$html = kindredcare_anchor_headings( $html );
	$html = kindredcare_wrap_tables( $html );
	$html = kindredcare_build_qa( $html );

	$cache[ $post->ID ] = $html;

	return $html;
}

/**
 * Give every h2 and h3 a stable id so the contents list can link to it.
 *
 * Ids are derived from the heading text rather than the position, so an edit
 * that inserts a section does not silently repoint every anchor below it.
 * Duplicates get a numeric suffix.
 *
 * @param string $html Article HTML.
 * @return string HTML with anchored headings.
 */
function kindredcare_anchor_headings( string $html ): string {
	$used = array();

	return (string) preg_replace_callback(
		'#<(h2|h3)([^>]*)>(.*?)</\1>#is',
		static function ( array $m ) use ( &$used ): string {
			// A heading that already carries an id keeps it.
			if ( preg_match( '/\sid=("|\')/i', $m[2] ) ) {
				return $m[0];
			}

			$slug = sanitize_title( wp_strip_all_tags( $m[3] ) );
			if ( '' === $slug ) {
				$slug = 'section';
			}

			$used[ $slug ] = isset( $used[ $slug ] ) ? $used[ $slug ] + 1 : 1;
			if ( $used[ $slug ] > 1 ) {
				$slug .= '-' . $used[ $slug ];
			}

			return sprintf(
				'<%1$s%2$s id="%3$s">%4$s</%1$s>',
				$m[1],
				$m[2],
				esc_attr( $slug ),
				$m[3]
			);
		},
		$html
	);
}

/**
 * Give tables their own horizontal scroll container.
 *
 * A five-column comparison table cannot shrink to 360px, and letting it push
 * the page wide would break every section above and below it.
 *
 * @param string $html Article HTML.
 * @return string HTML with wrapped tables.
 */
function kindredcare_wrap_tables( string $html ): string {
	if ( ! str_contains( $html, '<table' ) ) {
		return $html;
	}

	return (string) preg_replace(
		'#(<table[\s\S]*?</table>)#i',
		'<div class="c-table-scroll" tabindex="0" role="region" aria-label="Table">$1</div>',
		$html
	);
}

/**
 * Turn a run of "Q: … / A: …" paragraphs into a real disclosure list.
 *
 * Four of the imported articles end in an FAQ written as alternating
 * paragraphs, which reads as a wall of text. <details> gives it structure with
 * no JavaScript and no loss — with the element unsupported or CSS off, every
 * answer is still on the page.
 *
 * @param string $html Article HTML.
 * @return string HTML with the FAQ rebuilt.
 */
function kindredcare_build_qa( string $html ): string {
	$pair = '#<p[^>]*>\s*Q\s*[:.\x{FF1A}]\s*(.+?)</p>\s*<p[^>]*>\s*A\s*[:.\x{FF1A}]\s*(.+?)</p>#isu';

	if ( ! preg_match_all( $pair, $html, $matches, PREG_SET_ORDER ) ) {
		return $html;
	}

	$items = '';
	foreach ( $matches as $index => $m ) {
		$items .= sprintf(
			'<details class="c-qa__item"%1$s>'
				. '<summary class="c-qa__q">%2$s'
					. '<svg class="c-qa__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
					. '<path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>'
				. '</summary>'
				. '<div class="c-qa__a"><p>%3$s</p></div>'
			. '</details>',
			0 === $index ? ' open' : '',
			wp_kses_post( trim( $m[1] ) ),
			wp_kses_post( trim( $m[2] ) )
		);
	}

	// Replace the first pair with the whole block, then drop the rest in place.
	$replaced = false;

	return (string) preg_replace_callback(
		$pair,
		static function () use ( &$replaced, $items ): string {
			if ( $replaced ) {
				return '';
			}
			$replaced = true;

			return '<div class="c-qa">' . $items . '</div>';
		},
		$html
	);
}

/**
 * Headings for the table of contents.
 *
 * Reads the processed body rather than the raw post so the ids it links to are
 * the ids that actually render.
 *
 * @param int|WP_Post|null $post Post to outline.
 * @return array<int, array{id: string, text: string}> Ordered h2s.
 */
function kindredcare_article_outline( $post = null ): array {
	$html = kindredcare_article_html( $post );

	if ( ! preg_match_all( '#<h2[^>]*\sid="([^"]+)"[^>]*>(.*?)</h2>#is', $html, $matches, PREG_SET_ORDER ) ) {
		return array();
	}

	$out = array();
	foreach ( $matches as $m ) {
		$text = trim( wp_strip_all_tags( $m[2] ) );
		if ( '' === $text ) {
			continue;
		}

		/*
		 * The policy pages number their own headings — "1. Purpose of Data
		 * Collection". The contents list numbers entries itself, so the
		 * prefix is dropped here to avoid "01  1. Purpose of…". Headings that
		 * merely contain a number, like "Step 1: Build the Care Team", do not
		 * match and keep their wording.
		 */
		$text = (string) preg_replace( '/^\s*\d+\s*[.)]\s+/u', '', $text );
		$out[] = array(
			'id'   => $m[1],
			'text' => $text,
		);
	}

	return count( $out ) >= KINDREDCARE_TOC_MIN ? $out : array();
}

/* -- Rendering ------------------------------------------------------------- */

/**
 * Split a policy page's front matter out of its body.
 *
 * The imported policies open with two lines of metadata — "Last Updated: 28th
 * October 2025" and "Effective in Canada" — set as ordinary paragraphs. That is
 * masthead material, not the first thing someone should read as prose, so it is
 * lifted into the hero and removed from the body.
 *
 * Pages without that front matter (the terms page has none) come back with an
 * empty meta list and their body untouched.
 *
 * @param int|WP_Post|null $post Page to read.
 * @return array{meta: array<int, array{label: string, value: string}>, html: string}
 */
function kindredcare_policy_intro( $post = null ): array {
	static $cache = array();

	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return array(
			'meta' => array(),
			'html' => '',
		);
	}

	if ( isset( $cache[ $post->ID ] ) ) {
		return $cache[ $post->ID ];
	}

	$html = kindredcare_article_html( $post );
	$meta = array();

	// Each pattern captures its value and, matched at the very start, is
	// consumed so the next one can be tested against what remains.
	$patterns = array(
		array(
			'label'   => __( 'Last updated', 'kindredcare' ),
			'pattern' => '~^\s*<p>\s*<strong>\s*Last\s+Updated\s*:?\s*</strong>\s*(.+?)\s*</p>~is',
		),
		array(
			'label'   => __( 'Scope', 'kindredcare' ),
			'pattern' => '~^\s*<p>\s*<strong>\s*(Effective\b.*?)\s*</strong>\s*</p>~is',
		),
	);

	foreach ( $patterns as $rule ) {
		if ( preg_match( $rule['pattern'], $html, $m ) ) {
			$meta[] = array(
				'label' => $rule['label'],
				'value' => trim( wp_strip_all_tags( $m[1] ) ),
			);

			$html = (string) preg_replace( $rule['pattern'], '', $html, 1 );
		}
	}

	$cache[ $post->ID ] = array(
		'meta' => $meta,
		'html' => ltrim( $html ),
	);

	return $cache[ $post->ID ];
}

/**
 * The kicker above a page title.
 *
 * Derived from the footer's legal menu rather than a hardcoded list of slugs:
 * whatever is filed under that column is labelled with that column's heading.
 * Add a fourth policy to the menu and it is labelled without touching this.
 *
 * @param int $page_id Page being rendered.
 * @return string Kicker text, or '' when the page is not in that column.
 */
function kindredcare_page_kicker( int $page_id ): string {
	$locations = get_nav_menu_locations();
	$menu_id   = (int) ( $locations['footer-care'] ?? 0 );

	if ( ! $menu_id ) {
		return '';
	}

	foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
		if ( 'page' === $item->object && (int) $item->object_id === $page_id ) {
			return (string) kindredcare_option( 'kindredcare_footer_care_heading' );
		}
	}

	return '';
}

/**
 * The blog index URL.
 *
 * Falls back to the site root, which is where the post list lives when no
 * page has been assigned under Settings -> Reading.
 *
 * @return string Absolute URL.
 */
function kindredcare_blog_url(): string {
	$page_id = (int) get_option( 'page_for_posts' );

	return $page_id ? (string) get_permalink( $page_id ) : home_url( '/' );
}

/**
 * The small circular monogram that stands in for an author avatar.
 *
 * The articles are credited to a team rather than a person, so a Gravatar would
 * be a photograph of nobody.
 */
function kindredcare_byline_mark(): void {
	?>
	<span class="c-byline__mark" aria-hidden="true">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none">
			<path
				d="M12 20.5s-7.5-4.7-7.5-10a4.2 4.2 0 0 1 7.5-2.6 4.2 4.2 0 0 1 7.5 2.6c0 5.3-7.5 10-7.5 10Z"
				fill="currentColor"
			/>
		</svg>
	</span>
	<?php
}

/**
 * One post card.
 *
 * @param array{post?: int|WP_Post|null, lead?: bool, heading?: string} $args Card options.
 */
function kindredcare_post_card( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'post'    => null,
			'lead'    => false,
			'heading' => 'h3',
		)
	);

	$post = get_post( $args['post'] );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$lead    = (bool) $args['lead'];
	$tag     = in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h3';
	$term    = kindredcare_primary_term( $post );
	$size    = $lead ? 'kindredcare-wide' : 'kindredcare-card';
	$classes = 'c-post-card' . ( $lead ? ' c-post-card--lead' : '' );
	?>
	<article class="<?php echo esc_attr( $classes ); ?>">
		<div class="c-post-card__media">
			<?php if ( has_post_thumbnail( $post ) ) : ?>
				<?php
				echo get_the_post_thumbnail(
					$post,
					$size,
					array(
						'class'   => 'c-post-card__img',
						// The lead card is the first image on the page and is
						// above the fold; the rest are not.
						'loading' => $lead ? 'eager' : 'lazy',
						'sizes'   => $lead
							? '(min-width: 1024px) 62vw, 100vw'
							: '(min-width: 1024px) 30vw, (min-width: 768px) 45vw, 100vw',
					)
				);
				?>
			<?php else : ?>
				<span class="c-post-card__img c-post-card__img--empty" aria-hidden="true"></span>
			<?php endif; ?>

			<?php if ( $term ) : ?>
				<span class="c-post-card__cat"><?php echo esc_html( $term->name ); ?></span>
			<?php endif; ?>

			<?php /* On the image rather than in the footer: the meta row has a 16px floor, and three items at 16px is more furniture than a card can carry. */ ?>
			<span class="c-post-card__read">
				<?php
				printf(
					/* translators: %d: reading time in minutes. */
					esc_html__( '%d min read', 'kindredcare' ),
					kindredcare_reading_time( $post )
				);
				?>
			</span>
		</div>

		<div class="c-post-card__body">
			<<?php echo esc_attr( $tag ); ?> class="c-post-card__title">
				<a class="c-post-card__link" href="<?php echo esc_url( (string) get_permalink( $post ) ); ?>">
					<?php echo esc_html( get_the_title( $post ) ); ?>
				</a>
			</<?php echo esc_attr( $tag ); ?>>

			<p class="c-post-card__excerpt"><?php echo esc_html( kindredcare_excerpt( $post ) ); ?></p>

			<div class="c-post-card__foot">
				<span class="c-byline">
					<?php kindredcare_byline_mark(); ?>
					<span class="c-byline__name"><?php echo kindredcare_byline( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in kindredcare_byline(). ?></span>
				</span>

				<span class="c-post-card__dot" aria-hidden="true"></span>

				<time class="c-post-card__date" datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C, $post ) ); ?>">
					<?php echo esc_html( (string) get_the_date( 'M j, Y', $post ) ); ?>
				</time>
			</div>
		</div>
	</article>
	<?php
}

/**
 * The archive pager.
 *
 * paginate_links() supplies the anchors and the ellipsis logic; the markup
 * around them is ours so the arrows can be real buttons rather than "«".
 *
 * @param WP_Query|null $query Query to page. Defaults to the main query.
 */
function kindredcare_pagination( ?WP_Query $query = null ): void {
	$query = $query ?? $GLOBALS['wp_query'];
	$total = (int) $query->max_num_pages;

	if ( $total < 2 ) {
		return;
	}

	$current = max( 1, (int) get_query_var( 'paged' ) );

	$arrow = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
		. '<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>';

	$links = paginate_links(
		array(
			'total'     => $total,
			'current'   => $current,
			'mid_size'  => 1,
			'end_size'  => 1,
			'type'      => 'array',
			'prev_text' => $arrow . '<span class="u-sr-only">' . esc_html__( 'Previous page', 'kindredcare' ) . '</span>',
			'next_text' => $arrow . '<span class="u-sr-only">' . esc_html__( 'Next page', 'kindredcare' ) . '</span>',
		)
	);

	if ( ! is_array( $links ) ) {
		return;
	}

	/*
	 * wp_kses_post() strips <svg> outright, which would leave the prev/next
	 * controls as empty circles. The allowlist is widened for exactly the two
	 * elements the arrow is built from, and nothing else.
	 */
	$allowed = array_merge(
		wp_kses_allowed_html( 'post' ),
		array(
			'svg'  => array(
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'fill'        => true,
				'aria-hidden' => true,
			),
			'path' => array(
				'd'                => true,
				'stroke'           => true,
				'stroke-width'     => true,
				'stroke-linecap'   => true,
				'stroke-linejoin'  => true,
				'fill'             => true,
			),
		)
	);
	?>
	<nav class="c-pager" aria-label="<?php esc_attr_e( 'Article pages', 'kindredcare' ); ?>">
		<p class="c-pager__status">
			<?php
			printf(
				/* translators: 1: current page number, 2: total number of pages. */
				esc_html__( 'Page %1$d of %2$d', 'kindredcare' ),
				(int) $current,
				(int) $total
			);
			?>
		</p>

		<ul class="c-pager__list">
			<?php foreach ( $links as $link ) : ?>
				<li class="c-pager__item"><?php echo wp_kses( $link, $allowed ); ?></li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Share controls.
 *
 * Real links, not buttons: they work with JavaScript off, they can be opened in
 * a new tab deliberately, and a screen reader announces where each one goes.
 * The live site uses <button> for all three, which does none of that.
 *
 * @param string $url     Canonical URL of the article.
 * @param string $title   Article title, used as the pre-filled text.
 * @param string $variant 'rail' for the sticky column, 'inline' for the footer.
 */
function kindredcare_share( string $url, string $title, string $variant = 'inline' ): void {
	$encoded_url   = rawurlencode( $url );
	$encoded_title = rawurlencode( $title );

	$networks = array(
		'facebook' => array(
			'label' => __( 'Share on Facebook', 'kindredcare' ),
			'href'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
			'path'  => 'M14 8.5h2.5V5.6h-2.6c-2.4 0-3.9 1.5-3.9 4v1.9H7.5v3h2.5V22h3.2v-7.5h2.5l.4-3h-2.9V9.9c0-.9.3-1.4 1.3-1.4Z',
		),
		'twitter'  => array(
			'label' => __( 'Share on X', 'kindredcare' ),
			'href'  => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title,
			'path'  => 'M17.7 3h3.3l-7.2 8.2L22 21h-6.4l-5-6.5L4.8 21H1.5l7.7-8.8L1.7 3h6.6l4.5 6 5-6Zm-1.2 16.1h1.8L7.6 4.8H5.6l10.9 14.3Z',
		),
		'linkedin' => array(
			'label' => __( 'Share on LinkedIn', 'kindredcare' ),
			'href'  => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url,
			'path'  => 'M6.9 8.5H3.8V21h3.1V8.5ZM5.3 3a1.9 1.9 0 1 0 0 3.7 1.9 1.9 0 0 0 0-3.7ZM21 13.9c0-3.4-1.8-5-4.2-5a3.6 3.6 0 0 0-3.3 1.8V8.5H10.4V21h3.1v-6.6c0-1.7.9-2.6 2.2-2.6s2.2.8 2.2 2.6V21H21v-7.1Z',
		),
	);
	?>
	<div class="c-share c-share--<?php echo esc_attr( $variant ); ?>">
		<p class="c-share__label"><?php esc_html_e( 'Share', 'kindredcare' ); ?></p>

		<ul class="c-share__list">
			<?php foreach ( $networks as $key => $network ) : ?>
				<li>
					<a
						class="c-share__link c-share__link--<?php echo esc_attr( $key ); ?>"
						href="<?php echo esc_url( $network['href'] ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
							<path d="<?php echo esc_attr( $network['path'] ); ?>" />
						</svg>
						<span class="u-sr-only"><?php echo esc_html( $network['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>

			<li>
				<button
					class="c-share__link c-share__link--copy"
					type="button"
					data-copy-link="<?php echo esc_url( $url ); ?>"
				>
					<svg class="c-share__icon-link" width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M10 13.5a3.5 3.5 0 0 0 5 0l3-3a3.5 3.5 0 0 0-5-5l-1.2 1.2" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" />
						<path d="M14 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.5 3.5 0 0 0 5 5l1.2-1.2" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" />
					</svg>
					<svg class="c-share__icon-done" width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="m5 12.5 4.5 4.5L19 7.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
					<span class="u-sr-only"><?php esc_html_e( 'Copy link', 'kindredcare' ); ?></span>
				</button>
			</li>
		</ul>
	</div>
	<?php
}

/**
 * Articles to read next.
 *
 * Same category first, newest first; topped up with recent posts when a
 * category is too thin to fill the row, so the section is never half empty.
 *
 * @param int $post_id Article being read.
 * @param int $count   How many to return.
 * @return array<int, WP_Post> Posts.
 */
function kindredcare_related_posts( int $post_id, int $count = 3 ): array {
	$exclude = array( $post_id );
	$terms   = wp_get_post_categories( $post_id );

	$related = $terms
		? get_posts(
			array(
				'posts_per_page'      => $count,
				'post__not_in'        => $exclude,
				'category__in'        => $terms,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		)
		: array();

	if ( count( $related ) < $count ) {
		$fill = get_posts(
			array(
				'posts_per_page'      => $count - count( $related ),
				'post__not_in'        => array_merge( $exclude, wp_list_pluck( $related, 'ID' ) ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$related = array_merge( $related, $fill );
	}

	return $related;
}

/**
 * The masthead shared by the index, category archives and search results.
 *
 * Built to section 01's pattern (inc/hero.php, scss/sections/_hero.scss): the
 * asymmetric split — cream type panel left, photograph bleeding off the right
 * edge — with the same eyebrow, display headline, lead and single solid CTA in
 * the same order. It reuses that section's proportions rather than inventing a
 * second hero language for the blog to speak.
 *
 * Two deliberate departures from the homepage hero:
 *
 *  1. No typewriter. That headline types because it is the first thing anyone
 *     sees on the site; here it would delay a page the reader has already
 *     chosen, and the module is keyed to the hero's own markup.
 *  2. A still photograph, not the three-slide carousel. An index that already
 *     shows nine images does not need a tenth one moving.
 *
 * The entrance is the theme's reveal system (assets/js/modules/reveal.js):
 * data-reveal-group staggers its direct data-reveal children. With JavaScript
 * off, or reduced motion on, everything is simply visible.
 *
 * @param array{
 *     eyebrow?: string,
 *     title?: string,
 *     lead?: string,
 *     cta_label?: string,
 *     cta_url?: string,
 *     image?: string,
 *     image_alt?: string
 * } $args Masthead content.
 */
function kindredcare_blog_masthead( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow'   => __( 'The KindredCare journal', 'kindredcare' ),
			'title'     => __( 'Notes on caring well', 'kindredcare' ),
			'lead'      => '',
			'cta_label' => '',
			'cta_url'   => '',
			/*
			 * A theme photograph rather than the newest post's featured image:
			 * that image is already the lead card, directly below this, and the
			 * same picture twice in one screen reads as a mistake.
			 */
			'image'     => 'assets/img/hero/third-hero3.jpg',
			'image_alt' => '',
		)
	);

	$has_cta = '' !== trim( (string) $args['cta_label'] ) && '' !== trim( (string) $args['cta_url'] );
	?>
	<header class="s-blog-head" aria-labelledby="blog-head-title">
		<div class="s-blog-head__content" data-reveal-group>
			<span class="u-eyebrow s-blog-head__eyebrow" data-reveal="up">
				<?php echo esc_html( $args['eyebrow'] ); ?>
			</span>

			<h1 class="s-blog-head__title" id="blog-head-title" data-reveal="up">
				<?php echo esc_html( $args['title'] ); ?>
			</h1>

			<?php if ( $args['lead'] ) : ?>
				<p class="s-blog-head__lead" data-reveal="up"><?php echo esc_html( $args['lead'] ); ?></p>
			<?php endif; ?>

			<?php if ( $has_cta ) : ?>
				<a
					class="c-btn c-btn--primary c-btn--lg s-blog-head__cta"
					href="<?php echo esc_url( (string) $args['cta_url'] ); ?>"
					data-reveal="up"
				>
					<?php echo esc_html( $args['cta_label'] ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="s-blog-head__media">
			<div class="s-blog-head__media-inner">
				<img
					class="s-blog-head__img"
					src="<?php echo esc_url( get_theme_file_uri( (string) $args['image'] ) ); ?>"
					alt="<?php echo esc_attr( (string) $args['image_alt'] ); ?>"
					width="1460"
					height="1350"
					loading="eager"
					fetchpriority="high"
					decoding="async"
				/>
			</div>
		</div>
	</header>
	<?php
}

/* -- Wiring ---------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'kindredcare_enqueue_blog_assets', 20 );
/**
 * The single-post progress bar and contents highlighting.
 *
 * Only loaded where it has something to do. It is a classic script, not a
 * module, so it stays out of the module handle list in inc/enqueue.php.
 */
function kindredcare_enqueue_blog_assets(): void {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	wp_enqueue_script(
		'kindredcare-blog',
		get_theme_file_uri( 'assets/js/blog.js' ),
		array(),
		kindredcare_asset_version( 'assets/js/blog.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}

add_filter( 'excerpt_more', 'kindredcare_excerpt_more' );
/**
 * Replace the default "[…]" with a typographic ellipsis.
 *
 * @return string Excerpt suffix.
 */
function kindredcare_excerpt_more(): string {
	return '…';
}

add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

add_filter( 'wp_nav_menu_objects', 'kindredcare_blog_menu_current' );
/**
 * Keep the Blog item lit on articles, category archives and search results.
 *
 * WordPress marks a menu item current by matching the request against the item
 * itself. The blog link here is a custom link, and a custom link only ever
 * matches its own exact URL — so reading an article, which is where that link
 * leads, unlights it. This matches on destination instead.
 *
 * Hooked to wp_nav_menu_objects rather than nav_menu_css_class because
 * KindredCare_Header_Walker reads $item->classes off the object directly; the
 * class filter runs later and would never be seen. Items already marked
 * current are left alone, so a real Posts Page menu item keeps WordPress's
 * own answer.
 *
 * @param array<int, WP_Post> $items Menu items.
 * @return array<int, WP_Post> Filtered items.
 */
function kindredcare_blog_menu_current( array $items ): array {
	/*
	 * is_home() is in the list for paged views: the custom link matches
	 * /blog/ exactly, so WordPress lights it on page one and drops it on
	 * /blog/page/2/ onwards. is_front_page() is false here — the front page is
	 * a static page — so this cannot light Blog on the homepage.
	 */
	if (
		! is_home()
		&& ! is_singular( 'post' )
		&& ! is_category()
		&& ! is_tag()
		&& ! is_date()
		&& ! is_search()
	) {
		return $items;
	}

	$target = untrailingslashit( kindredcare_blog_url() );

	foreach ( $items as $item ) {
		if ( ! empty( $item->current ) ) {
			continue;
		}

		if ( untrailingslashit( (string) $item->url ) === $target ) {
			$item->current = true;
			$item->classes = array_merge( (array) $item->classes, array( 'current-menu-item' ) );
		}
	}

	return $items;
}
