<?php
/**
 * The Services page's catalogue box.
 *
 * One meta box on the Services page, doing two things that are deliberately
 * kept apart:
 *
 *   Fetching is an action. It is a link to admin-post.php, not a submit button,
 *   because it happens immediately and independently of whether the page itself
 *   is saved. Wiring it as a second submit inside the editor's own form would
 *   also mean nesting a form inside a form, which browsers silently discard.
 *
 *   Choosing images is editing. Those are ordinary fields, saved by the ordinary
 *   post save, so they behave like every other field on the screen — including
 *   being discarded if the editor navigates away without saving.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/** The admin-post action behind the fetch button. */
const KINDREDCARE_SERVICES_FETCH_ACTION = 'kindredcare_fetch_services';

/** Nonce action for saving the image fields. */
const KINDREDCARE_SERVICES_SAVE_ACTION = 'kindredcare_save_services';

add_action( 'add_meta_boxes', 'kindredcare_services_meta_box' );
/**
 * Register the box, on the Services template only.
 *
 * @param string $post_type Post type of the screen being built.
 */
function kindredcare_services_meta_box( string $post_type ): void {
	if ( 'page' !== $post_type ) {
		return;
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( KINDREDCARE_SERVICES_TEMPLATE !== get_page_template_slug( $post ) ) {
		return;
	}

	add_meta_box(
		'kindredcare-services',
		__( 'Service catalogue', 'kindredcare' ),
		'kindredcare_services_meta_box_render',
		'page',
		'normal',
		'high'
	);
}

add_action( 'admin_enqueue_scripts', 'kindredcare_services_meta_box_assets' );
/**
 * Load the media modal and the shared panel styles on the editor screen.
 *
 * @param string $hook Current admin page.
 */
function kindredcare_services_meta_box_assets( string $hook ): void {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post || KINDREDCARE_SERVICES_TEMPLATE !== get_page_template_slug( $post ) ) {
		return;
	}

	/*
	 * The same assets the KindredCare screens use. panel.js is delegated from
	 * [data-kc-admin] and bails when there is none, so the box below opting in
	 * with that attribute is all the wiring the image pickers need.
	 */
	kindredcare_admin_enqueue();
}

/**
 * Render the box.
 *
 * @param WP_Post $post The page being edited.
 */
function kindredcare_services_meta_box_render( WP_Post $post ): void {
	$services = kindredcare_services( $post->ID );
	$fetched  = kindredcare_services_fetched_at( $post->ID );

	wp_nonce_field( KINDREDCARE_SERVICES_SAVE_ACTION, 'kindredcare_services_nonce' );
	?>
	<div class="kc-catalogue" data-kc-admin>

		<div class="kc-catalogue__bar">
			<div class="kc-catalogue__status">
				<?php if ( $fetched ) : ?>
					<p class="kc-catalogue__count">
						<?php
						printf(
							/* translators: %d: number of services. */
							esc_html( _n( '%d service imported', '%d services imported', count( $services ), 'kindredcare' ) ),
							count( $services )
						);
						?>
					</p>
					<p class="kc-catalogue__when">
						<?php
						printf(
							/* translators: %s: human-readable time difference, e.g. "2 hours". */
							esc_html__( 'Last fetched %s ago', 'kindredcare' ),
							esc_html( human_time_diff( $fetched ) )
						);
						?>
					</p>
				<?php else : ?>
					<p class="kc-catalogue__count"><?php esc_html_e( 'Nothing imported yet', 'kindredcare' ); ?></p>
					<p class="kc-catalogue__when">
						<?php esc_html_e( 'Fetch the catalogue to fill this page.', 'kindredcare' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<?php
			/*
			 * A link, not a button: it leaves the screen, does its work and
			 * comes back. Styled as a button because that is what it behaves
			 * like to the person pressing it.
			 */
			$fetch_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => KINDREDCARE_SERVICES_FETCH_ACTION,
						'post'   => $post->ID,
					),
					admin_url( 'admin-post.php' )
				),
				KINDREDCARE_SERVICES_FETCH_ACTION . '_' . $post->ID
			);
			?>
			<a class="button button-primary" href="<?php echo esc_url( $fetch_url ); ?>">
				<?php
				echo $fetched
					? esc_html__( 'Fetch again', 'kindredcare' )
					: esc_html__( 'Fetch from the platform', 'kindredcare' );
				?>
			</a>
		</div>

		<?php
		/*
		 * Shown, not edited. The address used to be typed in here, which meant
		 * this page and the gigs each held their own copy of the same host and
		 * moving the platform meant remembering both. There is one setting for
		 * it now, and this says what that setting currently resolves to so the
		 * fetch button is never a black box.
		 */
		?>
		<p class="kc-catalogue__endpoint">
			<span class="kc-catalogue__endpoint-label">
				<?php esc_html_e( 'Fetched from', 'kindredcare' ); ?>
			</span>
			<code class="kc-catalogue__endpoint-value"><?php echo esc_html( kindredcare_services_endpoint() ); ?></code>
			<span class="kc-catalogue__endpoint-note">
				<?php if ( kindredcare_services_endpoint_is_locked() ) : ?>
					<?php esc_html_e( 'Fixed in wp-config.php, so it cannot be changed from the admin.', 'kindredcare' ); ?>
				<?php else : ?>
					<?php
					printf(
						/* translators: %s: link to the Gigs screen. */
						esc_html__( 'The platform address is set once for the whole site, at %s. Change it there and this follows.', 'kindredcare' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=' . KINDREDCARE_GIGS_MENU_SLUG ) ) . '">' . esc_html__( 'KindredCare → Gigs', 'kindredcare' ) . '</a>'
					);
					?>
				<?php endif; ?>
			</span>
		</p>

		<?php if ( ! $services ) : ?>
			<p class="kc-catalogue__empty">
				<?php esc_html_e( 'The list of services, and a picture for each one, will appear here once you fetch.', 'kindredcare' ); ?>
			</p>
			<?php
			return;
		endif;
		?>

		<p class="kc-catalogue__hint">
			<?php esc_html_e( 'Wording comes from the platform and is replaced on every fetch. Pictures are yours — they are kept when you fetch again. Every service starts on the same default picture; choose one here to replace it.', 'kindredcare' ); ?>
		</p>

		<ul class="kc-catalogue__list">
			<?php foreach ( $services as $index => $service ) : ?>
				<?php
				$slug     = (string) ( $service['slug'] ?? '' );
				$field_id = 'kc-service-image-' . $slug;
				$tier     = kindredcare_services_tier_label( (string) ( $service['tier'] ?? '' ) );
				?>
				<li class="kc-catalogue__item">
					<div class="kc-catalogue__media">
						<?php
						/*
						 * Keyed by slug rather than by position. The order of
						 * the list is the platform's and can change between
						 * fetches; the slug is what identifies a service, and
						 * keying on it means a reordered catalogue keeps every
						 * picture on the right card.
						 */
						kindredcare_admin_media_field(
							$field_id,
							'kc_service_image[' . esc_attr( $slug ) . ']',
							absint( $service['image'] ?? 0 ),
							/*
							 * The bundled default, so the picker previews what the card
							 * is actually showing rather than an empty frame — and so
							 * the reset button has something to put back.
							 */
							KINDREDCARE_SERVICES_DEFAULT_IMAGE,
							array(
								'empty_note'  => __( 'Using the default picture', 'kindredcare' ),
								'reset_label' => __( 'Go back to the default picture', 'kindredcare' ),
							)
						);
						?>
					</div>

					<div class="kc-catalogue__text">
						<h3 class="kc-catalogue__name"><?php echo esc_html( (string) $service['name'] ); ?></h3>

						<?php if ( '' !== (string) ( $service['description'] ?? '' ) ) : ?>
							<p class="kc-catalogue__blurb"><?php echo esc_html( (string) $service['description'] ); ?></p>
						<?php endif; ?>

						<p class="kc-catalogue__meta">
							<code><?php echo esc_html( $slug ); ?></code>
							<?php if ( '' !== $tier ) : ?>
								<span class="kc-catalogue__tier"><?php echo esc_html( $tier ); ?></span>
							<?php endif; ?>
						</p>

						<?php if ( ! empty( $service['tasks'] ) ) : ?>
							<ul class="kc-catalogue__tasks">
								<?php foreach ( (array) $service['tasks'] as $task ) : ?>
									<li><?php echo esc_html( (string) $task ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

add_action( 'admin_post_' . KINDREDCARE_SERVICES_FETCH_ACTION, 'kindredcare_services_handle_fetch' );
/**
 * Fetch the catalogue and store it against the page.
 */
function kindredcare_services_handle_fetch(): void {
	$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified immediately below.

	check_admin_referer( KINDREDCARE_SERVICES_FETCH_ACTION . '_' . $post_id );

	/*
	 * Checked against this page, not against the theme in general: the right to
	 * import a catalogue onto a page is the right to edit that page.
	 */
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this page.', 'kindredcare' ) );
	}

	$back = get_edit_post_link( $post_id, 'raw' ) ?: admin_url( 'edit.php?post_type=page' );

	$fetched = kindredcare_services_fetch();

	if ( is_wp_error( $fetched ) ) {
		set_transient(
			'kindredcare_services_notice_' . $post_id,
			array(
				'kind' => 'error',
				'text' => $fetched->get_error_message(),
			),
			MINUTE_IN_SECONDS
		);

		wp_safe_redirect( $back );
		exit;
	}

	$existing = kindredcare_services( $post_id );
	$services = kindredcare_services_merge_images( $fetched, $existing );

	kindredcare_services_store( $post_id, $services );

	$kept = count(
		array_filter( $services, static fn( array $service ): bool => ! empty( $service['image'] ) )
	);

	set_transient(
		'kindredcare_services_notice_' . $post_id,
		array(
			'kind' => 'success',
			'text' => $kept
				? sprintf(
					/* translators: 1: number of services, 2: number of pictures kept. */
					esc_html( _n( 'Imported %1$d service. %2$d picture kept.', 'Imported %1$d services. %2$d pictures kept.', count( $services ), 'kindredcare' ) ),
					count( $services ),
					$kept
				)
				: sprintf(
					/* translators: %d: number of services. */
					esc_html( _n( 'Imported %d service.', 'Imported %d services.', count( $services ), 'kindredcare' ) ),
					count( $services )
				),
		),
		MINUTE_IN_SECONDS
	);

	wp_safe_redirect( $back );
	exit;
}

add_action( 'admin_notices', 'kindredcare_services_admin_notice' );
/**
 * Show the result of a fetch.
 *
 * Parked in a transient by the handler rather than passed as a query argument,
 * so the message survives the redirect without becoming a URL anyone can forge
 * or bookmark.
 */
function kindredcare_services_admin_notice(): void {
	$screen = get_current_screen();

	if ( ! $screen || 'page' !== $screen->id ) {
		return;
	}

	$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, and the notice is scoped to a page the user is already viewing.

	if ( ! $post_id ) {
		return;
	}

	$notice = get_transient( 'kindredcare_services_notice_' . $post_id );

	if ( ! is_array( $notice ) ) {
		return;
	}

	delete_transient( 'kindredcare_services_notice_' . $post_id );

	printf(
		'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
		'error' === ( $notice['kind'] ?? '' ) ? 'error' : 'success',
		esc_html( (string) ( $notice['text'] ?? '' ) )
	);
}

add_action( 'save_post_page', 'kindredcare_services_save_images', 10, 2 );
/**
 * Store the chosen pictures.
 *
 * The images are the only thing writable here. The wording is the platform's
 * and is only ever replaced by a fetch, so a save walks the stored list and
 * updates exactly one field per row — an editor cannot accidentally rewrite the
 * catalogue by saving the page.
 *
 * @param int     $post_id Page being saved.
 * @param WP_Post $post    The page.
 */
function kindredcare_services_save_images( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['kindredcare_services_nonce'] ) ) {
		return;
	}

	if (
		! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['kindredcare_services_nonce'] ) ),
			KINDREDCARE_SERVICES_SAVE_ACTION
		)
	) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( KINDREDCARE_SERVICES_TEMPLATE !== get_page_template_slug( $post ) ) {
		return;
	}

	$services = kindredcare_services( $post_id );

	if ( ! $services ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value is cast with absint below.
	$submitted = isset( $_POST['kc_service_image'] ) ? (array) wp_unslash( $_POST['kc_service_image'] ) : array();

	foreach ( $services as $index => $service ) {
		$slug = (string) ( $service['slug'] ?? '' );

		/*
		 * A slug missing from the post is left alone rather than cleared. The
		 * only way it can be missing is that the field was not on the screen —
		 * a fetch that added a service while this form was open, say — and
		 * "not submitted" is not the same statement as "remove the picture".
		 * Clearing is done by the field's own reset button, which submits a 0.
		 */
		if ( ! array_key_exists( $slug, $submitted ) ) {
			continue;
		}

		$image = absint( $submitted[ $slug ] );

		// Only a real image in the library. Anything else stores as "none".
		$services[ $index ]['image'] = wp_attachment_is_image( $image ) ? $image : 0;
	}

	update_post_meta( $post_id, KINDREDCARE_SERVICES_META, $services );
}
