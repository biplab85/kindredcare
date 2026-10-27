<?php
/**
 * Admin: one gig's own editing screen.
 *
 * Three boxes. The side box is the connection — which gig on the platform this
 * post is, the exact URL it will be refreshed from, and the button that does
 * it. The other two are the gig itself, every field of it, editable.
 *
 * The split is the same one the Services catalogue makes: refreshing is an
 * action that happens at once, through admin-post.php, and is not part of
 * saving the post. Editing is editing, saved by Update like anything else.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/** The admin-post action behind a single gig's refresh button. */
const KINDREDCARE_GIG_REFRESH_ACTION = 'kindredcare_refresh_gig';

/** Nonce action for saving the fields below. */
const KINDREDCARE_GIG_SAVE_ACTION = 'kindredcare_save_gig';

/**
 * The nonced link that refreshes one gig from its own endpoint.
 *
 * @param int $post_id Gig.
 */
function kindredcare_gig_refresh_url( int $post_id ): string {
	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => KINDREDCARE_GIG_REFRESH_ACTION,
				'post'   => $post_id,
			),
			admin_url( 'admin-post.php' )
		),
		KINDREDCARE_GIG_REFRESH_ACTION . '_' . $post_id
	);
}

add_action( 'add_meta_boxes_' . KINDREDCARE_GIG_POST_TYPE, 'kindredcare_gig_meta_boxes' );
/**
 * Register the boxes on the gig screen.
 */
function kindredcare_gig_meta_boxes(): void {
	add_meta_box(
		'kindredcare-gig-sync',
		__( 'Platform', 'kindredcare' ),
		'kindredcare_gig_sync_box',
		KINDREDCARE_GIG_POST_TYPE,
		'side',
		'high'
	);

	add_meta_box(
		'kindredcare-gig-details',
		__( 'The gig', 'kindredcare' ),
		'kindredcare_gig_details_box',
		KINDREDCARE_GIG_POST_TYPE,
		'normal',
		'high'
	);

	add_meta_box(
		'kindredcare-gig-caregiver',
		__( 'The caregiver', 'kindredcare' ),
		'kindredcare_gig_caregiver_box',
		KINDREDCARE_GIG_POST_TYPE,
		'normal',
		'default'
	);
}

add_action( 'admin_enqueue_scripts', 'kindredcare_gig_edit_assets' );
/**
 * Load the shared panel assets on the gig editing screen only.
 *
 * @param string $hook Current admin page.
 */
function kindredcare_gig_edit_assets( string $hook ): void {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || KINDREDCARE_GIG_POST_TYPE !== $screen->post_type ) {
		return;
	}

	/*
	 * panel.js is delegated from [data-kc-admin] and binds to the first one it
	 * finds, so exactly one box carries that attribute — the details box, which
	 * is the only one with a repeater in it. The side box needs no script.
	 */
	kindredcare_admin_enqueue();
}

/* -- The side box ---------------------------------------------------------- */

/**
 * Print the platform connection for this gig.
 *
 * @param WP_Post $post The gig.
 */
function kindredcare_gig_sync_box( WP_Post $post ): void {
	$remote_id = kindredcare_gig_remote_id( $post->ID );
	$synced    = absint( get_post_meta( $post->ID, KINDREDCARE_GIG_META_SYNCED, true ) );
	?>
	<div class="kc-sync">
		<?php if ( $remote_id ) : ?>
			<p class="kc-sync__state">
				<?php if ( $synced ) : ?>
					<?php
					printf(
						/* translators: %s: human-readable time difference, e.g. "2 hours". */
						esc_html__( 'Last refreshed %s ago', 'kindredcare' ),
						esc_html( human_time_diff( $synced ) )
					);
					?>
				<?php else : ?>
					<?php esc_html_e( 'Linked, but never refreshed.', 'kindredcare' ); ?>
				<?php endif; ?>
			</p>

			<p class="kc-sync__url"><code><?php echo esc_html( kindredcare_gig_endpoint( $remote_id ) ); ?></code></p>

			<?php if ( get_post_meta( $post->ID, KINDREDCARE_GIG_META_WITHDRAWN, true ) ) : ?>
				<p class="kc-sync__warn">
					<?php esc_html_e( 'The platform stopped listing this gig, so it was set to draft. Refreshing will publish it again if it is back.', 'kindredcare' ); ?>
				</p>
			<?php endif; ?>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( kindredcare_gig_refresh_url( $post->ID ) ); ?>">
					<?php esc_html_e( 'Refresh from the platform', 'kindredcare' ); ?>
				</a>
			</p>

			<p class="kc-sync__note">
				<?php esc_html_e( 'Refreshing replaces the title, description, rate, tasks, category and caregiver with the platform’s current version, downloads any picture this site does not already hold, and discards anything unsaved on this screen. The featured image is left alone.', 'kindredcare' ); ?>
			</p>
		<?php else : ?>
			<p class="kc-sync__state"><?php esc_html_e( 'Not linked to the platform.', 'kindredcare' ); ?></p>
			<p class="kc-sync__note">
				<?php esc_html_e( 'This gig was written here rather than imported, so there is nothing to refresh it from. Give it a platform id below to attach it to a real listing.', 'kindredcare' ); ?>
			</p>
		<?php endif; ?>

		<p class="kc-sync__field">
			<label for="kc-gig-remote-id"><?php esc_html_e( 'Platform id', 'kindredcare' ); ?></label>
			<input
				type="number"
				id="kc-gig-remote-id"
				name="kc_gig[remote_id]"
				value="<?php echo esc_attr( (string) $remote_id ); ?>"
				min="0"
				step="1"
			/>
		</p>
		<p class="kc-sync__note">
			<?php esc_html_e( 'Which listing this post follows. Changing it points this post at a different gig; setting it to 0 detaches it from the platform for good.', 'kindredcare' ); ?>
		</p>

		<p class="kc-sync__note">
			<?php
			printf(
				/* translators: %s: link to the Gigs screen. */
				esc_html__( 'The platform address is set once for the whole site, at %s.', 'kindredcare' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=' . KINDREDCARE_GIGS_MENU_SLUG ) ) . '">' . esc_html__( 'KindredCare → Gigs', 'kindredcare' ) . '</a>'
			);
			?>
		</p>
	</div>
	<?php
}

/* -- The details box ------------------------------------------------------- */

/**
 * Say whether a picture has actually been brought onto this site.
 *
 * The URL field above it names an address on the platform, which is not what
 * the page renders — the page renders the downloaded copy. Without this line
 * there is no way to tell a gig whose picture is safely here from one whose
 * picture is still only a promise, and the difference only becomes visible when
 * the platform goes down, which is the worst moment to discover it.
 *
 * @param int    $attachment Local copy, 0 if there is none.
 * @param string $url        The address it was to come from.
 */
function kindredcare_gig_copy_state( int $attachment, string $url ): void {
	if ( '' === trim( $url ) ) {
		return;
	}

	if ( $attachment && wp_attachment_is_image( $attachment ) ) {
		printf(
			'<p class="kc-copy is-here"><img src="%1$s" alt="" width="40" height="40" /><span>%2$s</span></p>',
			esc_url( (string) wp_get_attachment_image_url( $attachment, 'thumbnail' ) ),
			sprintf(
				/* translators: %s: link to the attachment in the media library. */
				esc_html__( 'Stored on this site. %s', 'kindredcare' ),
				'<a href="' . esc_url( (string) get_edit_post_link( $attachment, 'raw' ) ) . '">' . esc_html__( 'View in the media library', 'kindredcare' ) . '</a>'
			)
		);

		return;
	}

	printf(
		'<p class="kc-copy"><span>%s</span></p>',
		esc_html__( 'Not downloaded yet — refresh this gig to bring it onto this site.', 'kindredcare' )
	);
}

/**
 * Print one task line.
 *
 * @param int    $index Row position.
 * @param string $task  Current value.
 */
function kindredcare_gig_task_row( int $index, string $task ): void {
	?>
	<div class="kc-lines__row" data-kc-item>
		<input
			type="text"
			name="kc_gig[tasks][<?php echo (int) $index; ?>]"
			value="<?php echo esc_attr( $task ); ?>"
			aria-label="<?php esc_attr_e( 'Task', 'kindredcare' ); ?>"
		/>
		<?php
		kindredcare_admin_icon_button(
			'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
			__( 'Remove this task', 'kindredcare' ),
			array( 'kc-remove' => '1' ),
			true
		);
		?>
	</div>
	<?php
}

/**
 * Print the gig's own fields.
 *
 * @param WP_Post $post The gig.
 */
function kindredcare_gig_details_box( WP_Post $post ): void {
	wp_nonce_field( KINDREDCARE_GIG_SAVE_ACTION, 'kindredcare_gig_nonce' );

	$tasks     = kindredcare_gig_tasks( $post->ID );
	$cents     = absint( get_post_meta( $post->ID, KINDREDCARE_GIG_META_RATE, true ) );
	$photo     = (string) get_post_meta( $post->ID, KINDREDCARE_GIG_META_PHOTO, true );
	$published = (string) get_post_meta( $post->ID, KINDREDCARE_GIG_META_PUBLISHED, true );

	// A gig with no tasks still needs one empty line to type into.
	if ( ! $tasks ) {
		$tasks = array( '' );
	}
	?>
	<div class="kc-admin kc-gigbox" data-kc-admin>
		<?php
		kindredcare_admin_note(
			__( 'Everything here comes from the platform and is <strong>replaced the next time this gig is refreshed or imported</strong>. Edit it freely between refreshes — but if a change is meant to last, it belongs on the platform.', 'kindredcare' )
		);
		?>

		<div class="kc-field--split">
			<div class="kc-field">
				<label class="kc-field__label" for="kc-gig-rate"><?php esc_html_e( 'Hourly rate', 'kindredcare' ); ?></label>
				<input
					type="number"
					id="kc-gig-rate"
					name="kc_gig[rate]"
					value="<?php echo esc_attr( $cents ? (string) ( $cents / 100 ) : '' ); ?>"
					min="0"
					step="0.01"
					inputmode="decimal"
				/>
				<p class="kc-field__hint">
					<?php
					printf(
						/* translators: %s: the rate as the platform stores it, in cents. */
						esc_html__( 'In dollars. The platform stores this in cents — %s here right now — and the two are converted on save.', 'kindredcare' ),
						'<code>' . esc_html( (string) $cents ) . '</code>'
					);
					?>
				</p>
			</div>

			<div class="kc-field">
				<label class="kc-field__label" for="kc-gig-published"><?php esc_html_e( 'Published on the platform', 'kindredcare' ); ?></label>
				<input
					type="text"
					id="kc-gig-published"
					name="kc_gig[published_at]"
					value="<?php echo esc_attr( $published ); ?>"
					spellcheck="false"
				/>
				<p class="kc-field__hint"><?php esc_html_e( 'The caregiver’s own publish date, exactly as the platform states it. This post has its own date in the Publish box beside it; the two are separate on purpose.', 'kindredcare' ); ?></p>
			</div>
		</div>

		<div class="kc-field">
			<label class="kc-field__label" for="kc-gig-photo"><?php esc_html_e( 'Photograph on the platform', 'kindredcare' ); ?></label>
			<input
				type="url"
				id="kc-gig-photo"
				name="kc_gig[photo_url]"
				value="<?php echo esc_attr( $photo ); ?>"
				spellcheck="false"
			/>
			<?php kindredcare_gig_copy_state( absint( get_post_meta( $post->ID, KINDREDCARE_GIG_META_PHOTO_ID, true ) ), $photo ); ?>
			<p class="kc-field__hint"><?php esc_html_e( 'Where the picture came from. The page shows this site’s own copy of it, never this address — set a featured image to override that copy with one you choose.', 'kindredcare' ); ?></p>
		</div>

		<div class="kc-field">
			<span class="kc-field__label"><?php esc_html_e( 'What’s included', 'kindredcare' ); ?></span>

			<div
				class="kc-repeat"
				data-kc-repeat="tasks"
				data-kc-max="<?php echo (int) KINDREDCARE_GIG_MAX_TASKS; ?>"
				data-kc-min="1"
			>
				<div class="kc-lines" data-kc-list>
					<?php foreach ( $tasks as $index => $task ) : ?>
						<?php kindredcare_gig_task_row( (int) $index, (string) $task ); ?>
					<?php endforeach; ?>
				</div>

				<div class="kc-lines__foot">
					<?php kindredcare_admin_add_button( __( 'Add task', 'kindredcare' ) ); ?>
					<span
						class="kc-repeat__count"
						data-kc-count
						data-template="<?php esc_attr_e( '%2$s saved · %3$s maximum', 'kindredcare' ); ?>"
					>
						<?php
						printf(
							/* translators: 1: tasks saved, 2: maximum allowed. */
							esc_html__( '%1$s saved · %2$s maximum', 'kindredcare' ),
							count( $tasks ),
							(int) KINDREDCARE_GIG_MAX_TASKS
						);
						?>
					</span>
				</div>

				<template data-kc-template>
					<?php kindredcare_gig_task_row( 0, '' ); ?>
				</template>
			</div>

			<p class="kc-field__hint"><?php esc_html_e( 'One line each, in the caregiver’s own words. Clearing a line deletes it.', 'kindredcare' ); ?></p>
		</div>
	</div>
	<?php
}

/* -- The caregiver box ----------------------------------------------------- */

/**
 * Print a read-only summary of something the platform owns outright.
 *
 * Verification checks and reviews are records, not copy. Editing a review here
 * would not change anything on the platform and would put words in someone
 * else's mouth on this site, so they are shown and not offered as fields.
 *
 * @param string                   $label Row label.
 * @param array<int|string, mixed> $items Stored records.
 */
function kindredcare_gig_records( string $label, array $items ): void {
	?>
	<div class="kc-field">
		<span class="kc-field__label"><?php echo esc_html( $label ); ?></span>
		<?php if ( ! $items ) : ?>
			<p class="kc-field__hint"><?php esc_html_e( 'None on the platform.', 'kindredcare' ); ?></p>
		<?php else : ?>
			<ul class="kc-records">
				<?php foreach ( $items as $item ) : ?>
					<li>
						<?php
						echo esc_html(
							is_array( $item )
								? implode( ' · ', array_map( 'strval', array_filter( $item, 'is_scalar' ) ) )
								: (string) $item
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<p class="kc-field__hint"><?php esc_html_e( 'Held on the platform and shown here for reference. Not editable — changing it here would not change it there.', 'kindredcare' ); ?></p>
	</div>
	<?php
}

/**
 * Print the caregiver's fields.
 *
 * @param WP_Post $post The gig.
 */
function kindredcare_gig_caregiver_box( WP_Post $post ): void {
	$caregiver = kindredcare_gig_caregiver( $post->ID );
	?>
	<div class="kc-admin kc-gigbox">
		<div class="kc-field--split">
			<div class="kc-field">
				<label class="kc-field__label" for="kc-gig-cg-name"><?php esc_html_e( 'Name', 'kindredcare' ); ?></label>
				<input type="text" id="kc-gig-cg-name" name="kc_gig[caregiver][display_name]" value="<?php echo esc_attr( (string) $caregiver['display_name'] ); ?>" />
			</div>

			<div class="kc-field">
				<label class="kc-field__label" for="kc-gig-cg-years"><?php esc_html_e( 'Years of experience', 'kindredcare' ); ?></label>
				<input type="number" id="kc-gig-cg-years" name="kc_gig[caregiver][years_of_experience]" value="<?php echo esc_attr( (string) $caregiver['years_of_experience'] ); ?>" min="0" step="1" />
			</div>
		</div>

		<div class="kc-field">
			<label class="kc-field__label" for="kc-gig-cg-bio"><?php esc_html_e( 'Bio', 'kindredcare' ); ?></label>
			<textarea id="kc-gig-cg-bio" name="kc_gig[caregiver][bio]" rows="3"><?php echo esc_textarea( (string) $caregiver['bio'] ); ?></textarea>
		</div>

		<div class="kc-field">
			<label class="kc-field__label" for="kc-gig-cg-photo"><?php esc_html_e( 'Portrait', 'kindredcare' ); ?></label>
			<input type="url" id="kc-gig-cg-photo" name="kc_gig[caregiver][photo_url]" value="<?php echo esc_attr( (string) $caregiver['photo_url'] ); ?>" spellcheck="false" />
			<?php kindredcare_gig_copy_state( absint( $caregiver['photo_id'] ), (string) $caregiver['photo_url'] ); ?>
			<p class="kc-field__hint"><?php esc_html_e( 'One portrait serves every gig this caregiver has posted, so it is stored once and shared.', 'kindredcare' ); ?></p>
		</div>

		<div class="kc-field--split">
			<div class="kc-field">
				<label class="kc-field__label" for="kc-gig-cg-languages"><?php esc_html_e( 'Languages', 'kindredcare' ); ?></label>
				<input type="text" id="kc-gig-cg-languages" name="kc_gig[caregiver][languages]" value="<?php echo esc_attr( implode( ', ', $caregiver['languages'] ) ); ?>" />
				<p class="kc-field__hint"><?php esc_html_e( 'Separated by commas.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field">
				<label class="kc-field__label" for="kc-gig-cg-tags"><?php esc_html_e( 'Personality tags', 'kindredcare' ); ?></label>
				<input type="text" id="kc-gig-cg-tags" name="kc_gig[caregiver][personality_tags]" value="<?php echo esc_attr( implode( ', ', $caregiver['personality_tags'] ) ); ?>" />
				<p class="kc-field__hint"><?php esc_html_e( 'Separated by commas.', 'kindredcare' ); ?></p>
			</div>
		</div>

		<div class="kc-field--split">
			<div class="kc-field">
				<label class="kc-field__label" for="kc-gig-cg-interests"><?php esc_html_e( 'Interests', 'kindredcare' ); ?></label>
				<input type="text" id="kc-gig-cg-interests" name="kc_gig[caregiver][interests]" value="<?php echo esc_attr( implode( ', ', $caregiver['interests'] ) ); ?>" />
				<p class="kc-field__hint"><?php esc_html_e( 'Separated by commas.', 'kindredcare' ); ?></p>
			</div>

			<div class="kc-field">
				<span class="kc-field__label"><?php esc_html_e( 'Verified', 'kindredcare' ); ?></span>
				<?php
				kindredcare_admin_toggle(
					'kc_gig[caregiver][is_verified]',
					! empty( $caregiver['is_verified'] ),
					__( 'Verified by the platform', 'kindredcare' )
				);
				?>
				<p class="kc-field__hint">
					<?php
					$average = $caregiver['rating_average'];
					$count   = absint( $caregiver['rating_count'] );

					if ( null === $average || ! $count ) {
						esc_html_e( 'No ratings on the platform yet.', 'kindredcare' );
					} else {
						printf(
							/* translators: 1: average rating, 2: number of ratings. */
							esc_html( _n( 'Rated %1$s from %2$d review.', 'Rated %1$s from %2$d reviews.', $count, 'kindredcare' ) ),
							esc_html( number_format_i18n( (float) $average, 1 ) ),
							(int) $count
						);
					}
					?>
				</p>
			</div>
		</div>

		<?php
		kindredcare_gig_records( __( 'Verification checks', 'kindredcare' ), (array) $caregiver['verification_checks'] );
		kindredcare_gig_records( __( 'Reviews', 'kindredcare' ), (array) $caregiver['reviews'] );
		?>

		<p class="kc-field__hint">
			<?php
			printf(
				/* translators: %s: the caregiver's user id on the platform. */
				esc_html__( 'Platform user id: %s', 'kindredcare' ),
				'<code>' . esc_html( (string) $caregiver['user_id'] ) . '</code>'
			);
			?>
		</p>
	</div>
	<?php
}

/* -- Saving ---------------------------------------------------------------- */

add_action( 'save_post_' . KINDREDCARE_GIG_POST_TYPE, 'kindredcare_gig_save', 10, 2 );
/**
 * Store what was typed on the gig screen.
 *
 * Only the fields the boxes above actually print are written. The caregiver's
 * verification checks and reviews are read back off the existing meta and put
 * back untouched, so a save cannot quietly drop the parts of the record that
 * have no field on the screen.
 *
 * @param int     $post_id Gig being saved.
 * @param WP_Post $post    The gig.
 */
function kindredcare_gig_save( int $post_id, WP_Post $post ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['kindredcare_gig_nonce'] ) ) {
		return;
	}

	if (
		! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['kindredcare_gig_nonce'] ) ),
			KINDREDCARE_GIG_SAVE_ACTION
		)
	) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every field is cleaned individually below and again by the registered meta sanitisers.
	$input = isset( $_POST['kc_gig'] ) ? (array) wp_unslash( $_POST['kc_gig'] ) : array();

	if ( ! $input ) {
		return;
	}

	update_post_meta( $post_id, KINDREDCARE_GIG_META_REMOTE_ID, absint( $input['remote_id'] ?? 0 ) );

	/*
	 * Dollars in, cents out. Rounded rather than truncated: 22.99 typed into a
	 * float field can arrive as 22.989999999999998, and (int) would store $22.98.
	 */
	update_post_meta(
		$post_id,
		KINDREDCARE_GIG_META_RATE,
		(int) round( (float) str_replace( ',', '', (string) ( $input['rate'] ?? '0' ) ) * 100 )
	);

	update_post_meta( $post_id, KINDREDCARE_GIG_META_PHOTO, esc_url_raw( (string) ( $input['photo_url'] ?? '' ) ) );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_PUBLISHED, sanitize_text_field( (string) ( $input['published_at'] ?? '' ) ) );
	update_post_meta( $post_id, KINDREDCARE_GIG_META_TASKS, kindredcare_gig_sanitize_tasks( $input['tasks'] ?? array() ) );

	$submitted = is_array( $input['caregiver'] ?? null ) ? $input['caregiver'] : array();

	if ( $submitted ) {
		$existing = kindredcare_gig_caregiver( $post_id );

		update_post_meta(
			$post_id,
			KINDREDCARE_GIG_META_CAREGIVER,
			kindredcare_gig_sanitize_caregiver(
				array_merge(
					$submitted,
					array(
						'languages'           => kindredcare_gig_split_list( (string) ( $submitted['languages'] ?? '' ) ),
						'interests'           => kindredcare_gig_split_list( (string) ( $submitted['interests'] ?? '' ) ),
						'personality_tags'    => kindredcare_gig_split_list( (string) ( $submitted['personality_tags'] ?? '' ) ),
						// Not on the screen, so carried over rather than lost.
						'user_id'             => $existing['user_id'],
						'photo_id'            => $existing['photo_id'],
						'verification_checks' => $existing['verification_checks'],
						'rating_average'      => $existing['rating_average'],
						'rating_count'        => $existing['rating_count'],
						'reviews'             => $existing['reviews'],
					)
				)
			)
		);
	}
}

/**
 * Split a comma-separated field into a list.
 *
 * @param string $value Raw field value.
 * @return array<int, string>
 */
function kindredcare_gig_split_list( string $value ): array {
	$items = array_map( 'trim', explode( ',', $value ) );

	return array_values( array_filter( $items, static fn( string $item ): bool => '' !== $item ) );
}

/* -- Refreshing ------------------------------------------------------------ */

add_action( 'admin_post_' . KINDREDCARE_GIG_REFRESH_ACTION, 'kindredcare_gig_handle_refresh' );
/**
 * Pull one gig from its own endpoint and write it.
 */
function kindredcare_gig_handle_refresh(): void {
	$post_id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified immediately below.

	check_admin_referer( KINDREDCARE_GIG_REFRESH_ACTION . '_' . $post_id );

	/*
	 * Checked against this post, not against the theme in general: the right to
	 * refresh a gig is the right to edit it.
	 */
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this gig.', 'kindredcare' ) );
	}

	$back = get_edit_post_link( $post_id, 'raw' ) ?: admin_url( 'edit.php?post_type=' . KINDREDCARE_GIG_POST_TYPE );

	$gig = kindredcare_gigs_fetch_one( kindredcare_gig_remote_id( $post_id ) );

	if ( is_wp_error( $gig ) ) {
		/*
		 * A 404 is not a failure, it is an answer: the platform is up and is
		 * saying this gig is gone. Drafting it here matches what a whole-list
		 * import would have done, so the two routes cannot leave the site in
		 * different states.
		 */
		if ( 404 === (int) ( $gig->get_error_data()['status'] ?? 0 ) ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				)
			);

			update_post_meta( $post_id, KINDREDCARE_GIG_META_WITHDRAWN, true );

			kindredcare_gigs_notify(
				'warning',
				__( 'The platform no longer has this gig, so it has been set to draft. Nothing has been deleted.', 'kindredcare' )
			);
		} else {
			kindredcare_gigs_notify( 'error', $gig->get_error_message() );
		}

		wp_safe_redirect( $back );
		exit;
	}

	$applied = kindredcare_gigs_apply( $gig );

	if ( is_wp_error( $applied ) ) {
		kindredcare_gigs_notify( 'error', $applied->get_error_message() );

		wp_safe_redirect( $back );
		exit;
	}

	kindredcare_gigs_notify(
		'success',
		sprintf(
			/* translators: %s: gig title. */
			__( '“%s” has been refreshed from the platform.', 'kindredcare' ),
			get_the_title( $applied['post_id'] )
		)
	);

	/*
	 * Redirected to the post that actually took the update. Normally the one we
	 * started from — but if its platform id had been pointed at a gig another
	 * post already holds, that other post is where the content went, and
	 * landing back on an unchanged screen would be baffling.
	 */
	wp_safe_redirect( get_edit_post_link( $applied['post_id'], 'raw' ) ?: $back );
	exit;
}
