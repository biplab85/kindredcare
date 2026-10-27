<?php
/**
 * Admin: KindredCare -> Gigs.
 *
 * One screen doing three separable things, kept visibly separate because they
 * fail separately: saying where the platform is, pulling the whole list down,
 * and showing what is currently here.
 *
 * The address is a setting, so it saves through the Settings API like every
 * other screen in this theme. Importing and testing are actions — they happen
 * at once, whether or not anything on the screen is being saved — so they are
 * links to admin-post.php rather than second submit buttons, which is also the
 * only way to have them at all without nesting a form inside a form.
 *
 * @package KindredCare
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const KINDREDCARE_GIGS_MENU_SLUG = 'kindredcare-gigs';

/** The admin-post action behind "Import gigs". */
const KINDREDCARE_GIGS_IMPORT_ACTION = 'kindredcare_import_gigs';

/** The admin-post action behind "Test the connection". */
const KINDREDCARE_GIGS_TEST_ACTION = 'kindredcare_test_platform';

/**
 * Capability callback for this option group.
 */
function kindredcare_gigs_capability(): string {
	return kindredcare_admin_capability();
}

/* Priority 24: last, after the front-page sections. Gigs are not one of them. */
add_action( 'admin_menu', 'kindredcare_gigs_admin_menu', 24 );
/**
 * Add the Gigs screen under the KindredCare menu.
 *
 * The post type registers its own "All gigs" entry into the same menu, so this
 * screen is the one that owns the connection and the import while the list of
 * posts stays where WordPress puts it.
 */
function kindredcare_gigs_admin_menu(): void {
	$post_type = get_post_type_object( KINDREDCARE_GIG_POST_TYPE );

	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		__( 'Gigs', 'kindredcare' ),
		__( 'Gigs', 'kindredcare' ),
		kindredcare_admin_capability(),
		KINDREDCARE_GIGS_MENU_SLUG,
		'kindredcare_gigs_render_page'
	);

	if ( ! $post_type instanceof WP_Post_Type ) {
		return;
	}

	/*
	 * The post type's own screens, placed by hand and directly after the one
	 * above — the connection, then the posts it produces, then the categories
	 * those posts fall into. Their capabilities are the post type's own, not
	 * this theme's: whoever may edit gigs may reach the list, whether or not
	 * they may change the platform address.
	 */
	add_submenu_page(
		KINDREDCARE_ADMIN_MENU_SLUG,
		$post_type->labels->name,
		__( 'All gigs', 'kindredcare' ),
		$post_type->cap->edit_posts,
		'edit.php?post_type=' . KINDREDCARE_GIG_POST_TYPE
	);

	$taxonomy = get_taxonomy( KINDREDCARE_GIG_TAXONOMY );

	if ( $taxonomy instanceof WP_Taxonomy ) {
		add_submenu_page(
			KINDREDCARE_ADMIN_MENU_SLUG,
			$taxonomy->labels->name,
			__( 'Gig categories', 'kindredcare' ),
			$taxonomy->cap->manage_terms,
			'edit-tags.php?taxonomy=' . KINDREDCARE_GIG_TAXONOMY . '&post_type=' . KINDREDCARE_GIG_POST_TYPE
		);
	}
}

add_filter( 'parent_file', 'kindredcare_gigs_menu_parent' );
/**
 * Keep the KindredCare menu open and marked while a gig screen is showing.
 *
 * Registering the post type's screens by hand means WordPress no longer knows
 * which menu they belong to: without this, editing a gig collapses the menu and
 * highlights nothing, which reads as having navigated out of the section.
 *
 * @param string $parent_file The menu item WordPress was going to mark.
 */
function kindredcare_gigs_menu_parent( string $parent_file ): string {
	global $submenu_file;

	$screen = get_current_screen();

	if ( ! $screen || KINDREDCARE_GIG_POST_TYPE !== $screen->post_type ) {
		return $parent_file;
	}

	// Which of the two entries to mark depends on which screen this is.
	$submenu_file = in_array( $screen->base, array( 'edit-tags', 'term' ), true )
		? 'edit-tags.php?taxonomy=' . KINDREDCARE_GIG_TAXONOMY . '&post_type=' . KINDREDCARE_GIG_POST_TYPE
		: 'edit.php?post_type=' . KINDREDCARE_GIG_POST_TYPE;

	return KINDREDCARE_ADMIN_MENU_SLUG;
}

add_action( 'admin_init', 'kindredcare_gigs_register_setting' );
/**
 * Register the platform address with the Settings API.
 */
function kindredcare_gigs_register_setting(): void {
	register_setting(
		'kindredcare_api_group',
		KINDREDCARE_API_BASE_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'kindredcare_gigs_sanitize_base',
			'default'           => '',
		)
	);
}

add_filter( 'option_page_capability_kindredcare_api_group', 'kindredcare_gigs_capability' );

/**
 * Clean the submitted address, refusing rather than erasing.
 *
 * An address that cannot be parsed is a typo, and the worst possible answer to
 * a typo is to store an empty string — the site would silently fall back to
 * localhost and every fetch from then on would fail on a live server for a
 * reason nobody could see. So an unusable value keeps whatever was there and
 * says what happened.
 *
 * Emptying the field on purpose is different, and does mean "go back to the
 * bundled default".
 *
 * @param mixed $value Raw submitted value.
 */
function kindredcare_gigs_sanitize_base( $value ): string {
	$raw   = is_string( $value ) ? trim( $value ) : '';
	$clean = kindredcare_api_sanitize_base( $raw );

	if ( '' === $clean && '' !== $raw ) {
		add_settings_error(
			KINDREDCARE_API_BASE_OPTION,
			'kindredcare_api_base_invalid',
			__( 'That does not look like a web address, so it has not been saved. It needs to be something like https://app.kindredcare.ca — scheme and host, nothing after them.', 'kindredcare' )
		);

		return (string) get_option( KINDREDCARE_API_BASE_OPTION, '' );
	}

	return $clean;
}

add_action( 'admin_enqueue_scripts', 'kindredcare_gigs_admin_assets' );
/**
 * Load the shared panel assets on this screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function kindredcare_gigs_admin_assets( string $hook_suffix ): void {
	if ( 'kindredcare_page_' . KINDREDCARE_GIGS_MENU_SLUG !== $hook_suffix ) {
		return;
	}

	kindredcare_admin_enqueue();
}

/* -- Notices --------------------------------------------------------------- */

/**
 * Park a message for the screen the browser is about to be sent to.
 *
 * Kept in a transient rather than passed as a query argument, so it survives
 * the redirect without becoming a URL anyone can forge or bookmark. Keyed by
 * user, because two editors importing at once should each see their own result.
 *
 * @param string $kind 'success', 'error' or 'warning'.
 * @param string $text What to say.
 */
function kindredcare_gigs_notify( string $kind, string $text ): void {
	set_transient(
		'kindredcare_gigs_notice_' . get_current_user_id(),
		array(
			'kind' => $kind,
			'text' => $text,
		),
		MINUTE_IN_SECONDS
	);
}

add_action( 'admin_notices', 'kindredcare_gigs_admin_notice' );
/**
 * Show the result of an import, a test or a single refresh.
 */
function kindredcare_gigs_admin_notice(): void {
	$key    = 'kindredcare_gigs_notice_' . get_current_user_id();
	$notice = get_transient( $key );

	if ( ! is_array( $notice ) ) {
		return;
	}

	delete_transient( $key );

	$kind = (string) ( $notice['kind'] ?? 'success' );

	printf(
		'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( in_array( $kind, array( 'error', 'warning', 'info' ), true ) ? $kind : 'success' ),
		esc_html( (string) ( $notice['text'] ?? '' ) )
	);
}

/* -- Actions --------------------------------------------------------------- */

/**
 * Where an action sends the browser back to.
 */
function kindredcare_gigs_screen_url(): string {
	return admin_url( 'admin.php?page=' . KINDREDCARE_GIGS_MENU_SLUG );
}

/**
 * Build a nonced link to one of this screen's actions.
 *
 * @param string $action admin-post action name.
 */
function kindredcare_gigs_action_url( string $action ): string {
	return wp_nonce_url(
		add_query_arg( 'action', $action, admin_url( 'admin-post.php' ) ),
		$action
	);
}

add_action( 'admin_post_' . KINDREDCARE_GIGS_IMPORT_ACTION, 'kindredcare_gigs_handle_import' );
/**
 * Pull the whole list down and write it.
 */
function kindredcare_gigs_handle_import(): void {
	check_admin_referer( KINDREDCARE_GIGS_IMPORT_ACTION );

	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to import gigs.', 'kindredcare' ) );
	}

	$result = kindredcare_gigs_import();

	if ( is_wp_error( $result ) ) {
		kindredcare_gigs_notify( 'error', $result->get_error_message() );

		wp_safe_redirect( kindredcare_gigs_screen_url() );
		exit;
	}

	$parts = array(
		sprintf(
			/* translators: %d: number of gigs. */
			_n( '%d new gig.', '%d new gigs.', $result['created'], 'kindredcare' ),
			$result['created']
		),
		sprintf(
			/* translators: %d: number of gigs. */
			_n( '%d updated.', '%d updated.', $result['updated'], 'kindredcare' ),
			$result['updated']
		),
	);

	if ( $result['downloaded'] ) {
		$parts[] = sprintf(
			/* translators: %d: number of pictures downloaded. */
			_n(
				'%d picture brought onto this site.',
				'%d pictures brought onto this site.',
				$result['downloaded'],
				'kindredcare'
			),
			$result['downloaded']
		);
	}

	/*
	 * Said out loud rather than left to be discovered. An import that ran out of
	 * time part-way through the pictures has done nothing wrong and needs no
	 * repair — it needs the button pressing again — but silence here would read
	 * as "finished" and the missing pictures would surface much later as blank
	 * frames on the front end.
	 */
	if ( $result['pending'] ) {
		$parts[] = sprintf(
			/* translators: %d: number of pictures not yet fetched. */
			_n(
				'%d picture could not be fetched yet — import again to pick it up.',
				'%d pictures could not be fetched yet — import again to pick them up.',
				$result['pending'],
				'kindredcare'
			),
			$result['pending']
		);
	}

	if ( $result['withdrawn'] ) {
		$parts[] = sprintf(
			/* translators: %d: number of gigs set to draft. */
			_n(
				'%d is no longer listed on the platform and has been set to draft.',
				'%d are no longer listed on the platform and have been set to draft.',
				$result['withdrawn'],
				'kindredcare'
			),
			$result['withdrawn']
		);
	}

	if ( $result['failed'] ) {
		$parts[] = sprintf(
			/* translators: %d: number of gigs that could not be saved. */
			_n( '%d could not be saved.', '%d could not be saved.', $result['failed'], 'kindredcare' ),
			$result['failed']
		);
	}

	kindredcare_gigs_notify(
		$result['failed'] || $result['pending'] ? 'warning' : 'success',
		implode( ' ', $parts )
	);

	wp_safe_redirect( kindredcare_gigs_screen_url() );
	exit;
}

add_action( 'admin_post_' . KINDREDCARE_GIGS_TEST_ACTION, 'kindredcare_gigs_handle_test' );
/**
 * Ask the platform whether it is there, and change nothing.
 *
 * Worth its own button because the two ways an import fails look identical from
 * the outside — a wrong address and a platform that is down both report "could
 * not reach" — and this one can be pressed before there is anything to lose.
 */
function kindredcare_gigs_handle_test(): void {
	check_admin_referer( KINDREDCARE_GIGS_TEST_ACTION );

	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'kindredcare' ) );
	}

	$rows = kindredcare_api_get( kindredcare_api_url( KINDREDCARE_GIGS_PATH ) );

	if ( is_wp_error( $rows ) ) {
		kindredcare_gigs_notify( 'error', $rows->get_error_message() );
	} else {
		kindredcare_gigs_notify(
			'success',
			sprintf(
				/* translators: 1: number of gigs, 2: platform address. */
				_n(
					'Connected. %1$s is listing %2$d gig.',
					'Connected. %1$s is listing %2$d gigs.',
					count( $rows ),
					'kindredcare'
				),
				kindredcare_api_base(),
				count( $rows )
			)
		);
	}

	wp_safe_redirect( kindredcare_gigs_screen_url() );
	exit;
}

/* -- The screen ------------------------------------------------------------ */

/**
 * Print one row of the imported-gigs table.
 *
 * @param int $post_id Gig.
 */
function kindredcare_gigs_table_row( int $post_id ): void {
	$remote_id = kindredcare_gig_remote_id( $post_id );
	$caregiver = kindredcare_gig_caregiver( $post_id );
	$synced    = absint( get_post_meta( $post_id, KINDREDCARE_GIG_META_SYNCED, true ) );
	$status    = (string) get_post_status( $post_id );
	$terms     = get_the_terms( $post_id, KINDREDCARE_GIG_TAXONOMY );
	$edit      = (string) get_edit_post_link( $post_id, 'raw' );
	?>
	<tr class="kc-gigs__row<?php echo 'publish' === $status ? '' : ' is-off'; ?>">
		<td class="kc-gigs__title">
			<a href="<?php echo esc_url( $edit ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
			<?php if ( 'publish' !== $status ) : ?>
				<span class="kc-gigs__badge">
					<?php
					echo get_post_meta( $post_id, KINDREDCARE_GIG_META_WITHDRAWN, true )
						? esc_html__( 'Withdrawn on the platform', 'kindredcare' )
						: esc_html( ucfirst( $status ) );
					?>
				</span>
			<?php endif; ?>
		</td>

		<td>
			<?php
			echo is_array( $terms ) && $terms
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '<span class="kc-gigs__none">' . esc_html__( 'None', 'kindredcare' ) . '</span>';
			?>
		</td>

		<td><?php echo esc_html( kindredcare_gig_rate( absint( get_post_meta( $post_id, KINDREDCARE_GIG_META_RATE, true ) ) ) ); ?></td>

		<td><?php echo esc_html( (string) $caregiver['display_name'] ); ?></td>

		<td><code><?php echo $remote_id ? esc_html( (string) $remote_id ) : '—'; ?></code></td>

		<td>
			<?php
			echo $synced
				? esc_html(
					sprintf(
						/* translators: %s: human-readable time difference, e.g. "2 hours". */
						__( '%s ago', 'kindredcare' ),
						human_time_diff( $synced )
					)
				)
				: esc_html__( 'Never', 'kindredcare' );
			?>
		</td>

		<td class="kc-gigs__actions">
			<a href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'Edit', 'kindredcare' ); ?></a>
			<?php if ( $remote_id ) : ?>
				<a href="<?php echo esc_url( kindredcare_gig_refresh_url( $post_id ) ); ?>">
					<?php esc_html_e( 'Refresh', 'kindredcare' ); ?>
				</a>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render the Gigs admin screen.
 */
function kindredcare_gigs_render_page(): void {
	if ( ! current_user_can( kindredcare_admin_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this section.', 'kindredcare' ) );
	}

	$locked   = kindredcare_api_base_is_locked();
	$base     = kindredcare_api_base();
	$gigs     = kindredcare_gigs_all();
	$imported = kindredcare_gigs_imported_at();
	$counts   = wp_count_posts( KINDREDCARE_GIG_POST_TYPE );
	?>
	<div class="wrap kc-admin" data-kc-admin>
		<?php
		kindredcare_admin_masthead(
			__( 'Platform', 'kindredcare' ),
			__( 'Gigs', 'kindredcare' ),
			esc_html__( 'Caregivers write their listings on the platform. This screen brings them across as posts you can edit, and holds the one address everything else on this site fetches from.', 'kindredcare' )
		);

		settings_errors( KINDREDCARE_API_BASE_OPTION );
		?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kindredcare_api_group' ); ?>

			<!-- Where the platform is ------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M3.5 9h17M3.5 15h17M12 3c2.5 2.6 2.5 15.4 0 18M12 3c-2.5 2.6-2.5 15.4 0 18' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Where the platform is', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'One address, used by every fetch this site makes. Change it here when the platform moves and nothing else needs touching.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php
					kindredcare_admin_note(
						__( 'Give the <strong>address only</strong> — scheme and host, like <code>https://app.kindredcare.ca</code> or <code>http://localhost:8001</code>. Everything after it is added by the theme, so pasting a whole endpoint here is safe: the path is trimmed off.', 'kindredcare' )
					);
					?>

					<div class="kc-field">
						<label class="kc-field__label" for="kc-api-base"><?php esc_html_e( 'Platform address', 'kindredcare' ); ?></label>
						<input
							type="text"
							id="kc-api-base"
							class="kc-catalogue__endpoint-input"
							name="<?php echo esc_attr( KINDREDCARE_API_BASE_OPTION ); ?>"
							value="<?php echo esc_attr( $locked ? $base : (string) get_option( KINDREDCARE_API_BASE_OPTION, '' ) ); ?>"
							placeholder="<?php echo esc_attr( KINDREDCARE_API_BASE_DEFAULT ); ?>"
							inputmode="url"
							spellcheck="false"
							<?php disabled( $locked ); ?>
						/>
						<p class="kc-field__hint">
							<?php if ( $locked ) : ?>
								<?php esc_html_e( 'Set in wp-config.php as KINDREDCARE_API_BASE, so it cannot be changed here.', 'kindredcare' ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Leave it empty to fall back to the bundled development address. Save the address first, then import — the import reads it back at the moment it runs.', 'kindredcare' ); ?>
							<?php endif; ?>
						</p>
					</div>

					<div class="kc-field">
						<span class="kc-field__label"><?php esc_html_e( 'What that resolves to', 'kindredcare' ); ?></span>
						<ul class="kc-endpoints">
							<li>
								<span><?php esc_html_e( 'Every gig', 'kindredcare' ); ?></span>
								<code><?php echo esc_html( kindredcare_api_url( KINDREDCARE_GIGS_PATH ) ); ?></code>
							</li>
							<li>
								<span><?php esc_html_e( 'One gig', 'kindredcare' ); ?></span>
								<code><?php echo esc_html( kindredcare_api_url( KINDREDCARE_GIGS_PATH ) . '/{id}' ); ?></code>
							</li>
							<li>
								<span><?php esc_html_e( 'Service catalogue', 'kindredcare' ); ?></span>
								<code><?php echo esc_html( kindredcare_services_endpoint() ); ?></code>
							</li>
						</ul>
						<p class="kc-field__hint"><?php esc_html_e( 'These update as soon as the address above is saved. The catalogue is fetched from the Services page; the gigs are fetched here.', 'kindredcare' ); ?></p>
					</div>

					<p class="kc-actions">
						<a class="kc-btn kc-btn--ghost" href="<?php echo esc_url( kindredcare_gigs_action_url( KINDREDCARE_GIGS_TEST_ACTION ) ); ?>">
							<?php esc_html_e( 'Test the connection', 'kindredcare' ); ?>
						</a>
						<span class="kc-field__hint"><?php esc_html_e( 'Asks the platform for the list and reports what came back. Changes nothing.', 'kindredcare' ); ?></span>
					</p>
				</div>
			</section>

			<!-- Importing -------------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M12 4v11m0 0 4-4m-4 4-4-4M4 18h16' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Import', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'Brings every gig the platform is listing across as a post. Run it whenever the platform has changed — nothing here fetches on its own.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<div class="kc-catalogue__bar">
						<div>
							<?php if ( $imported ) : ?>
								<p class="kc-catalogue__count">
									<?php
									printf(
										/* translators: 1: published gigs, 2: drafted gigs. */
										esc_html__( '%1$d published · %2$d in draft', 'kindredcare' ),
										absint( $counts->publish ?? 0 ),
										absint( $counts->draft ?? 0 )
									);
									?>
								</p>
								<p class="kc-catalogue__when">
									<?php
									printf(
										/* translators: %s: human-readable time difference, e.g. "2 hours". */
										esc_html__( 'Last imported %s ago', 'kindredcare' ),
										esc_html( human_time_diff( $imported ) )
									);
									?>
								</p>
							<?php else : ?>
								<p class="kc-catalogue__count"><?php esc_html_e( 'Nothing imported yet', 'kindredcare' ); ?></p>
								<p class="kc-catalogue__when"><?php esc_html_e( 'Import the gigs to fill this post type.', 'kindredcare' ); ?></p>
							<?php endif; ?>
						</div>

						<a class="button button-primary" href="<?php echo esc_url( kindredcare_gigs_action_url( KINDREDCARE_GIGS_IMPORT_ACTION ) ); ?>">
							<?php
							echo $imported
								? esc_html__( 'Import again', 'kindredcare' )
								: esc_html__( 'Import from the platform', 'kindredcare' );
							?>
						</a>
					</div>

					<?php
					kindredcare_admin_note(
						__( 'Importing <strong>updates a gig that is already here and creates one that is not</strong>, matching on the id the platform gave it. A gig the platform has stopped listing is <strong>set to draft, never deleted</strong> — its post, its link and its featured image are kept, and it publishes itself again if the caregiver relists it.', 'kindredcare' )
					);

					kindredcare_admin_note(
						__( 'The wording, rate, tasks, caregiver and category all come from the platform and are <strong>replaced on every import</strong>. The featured image is yours and is never touched — set one to override the photograph the platform sends.', 'kindredcare' )
					);

					kindredcare_admin_note(
						__( 'Photographs and portraits are <strong>downloaded into this site’s media library</strong>, not linked to. Pages then serve their own copies, at their own sizes, and keep working when the platform does not. A picture already here is never fetched twice, so importing again is quick.', 'kindredcare' )
					);
					?>
				</div>
			</section>

			<!-- What is here ---------------------------------------------- -->
			<section class="kc-panel">
				<div class="kc-panel__head">
					<?php kindredcare_admin_icon( 'M4 6h16M4 12h16M4 18h16' ); ?>
					<div class="kc-panel__titles">
						<h2><?php esc_html_e( 'Imported gigs', 'kindredcare' ); ?></h2>
						<p><?php esc_html_e( 'What is in the database now. Each one has its own screen, wired to its own endpoint.', 'kindredcare' ); ?></p>
					</div>
				</div>
				<div class="kc-panel__body">
					<?php if ( ! $gigs ) : ?>
						<p class="kc-catalogue__empty">
							<?php esc_html_e( 'No gigs yet. Check the address above, test the connection, then import.', 'kindredcare' ); ?>
						</p>
					<?php else : ?>
						<div class="kc-gigs">
							<table class="kc-gigs__table">
								<thead>
									<tr>
										<th scope="col"><?php esc_html_e( 'Gig', 'kindredcare' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Category', 'kindredcare' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Rate', 'kindredcare' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Caregiver', 'kindredcare' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Platform id', 'kindredcare' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Last synced', 'kindredcare' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Actions', 'kindredcare' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $gigs as $post_id ) : ?>
										<?php kindredcare_gigs_table_row( (int) $post_id ); ?>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<p class="kc-field__hint">
							<?php
							printf(
								/* translators: %s: link to the gigs list screen. */
								esc_html__( 'Ordering, searching and bulk actions live on the ordinary list screen: %s', 'kindredcare' ),
								'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . KINDREDCARE_GIG_POST_TYPE ) ) . '">' . esc_html__( 'All gigs', 'kindredcare' ) . '</a>'
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			</section>

			<?php
			kindredcare_admin_savebar(
				__( 'Save address', 'kindredcare' ),
				__( 'Saving stores the address. Importing is a separate step — press the button above once the address is right.', 'kindredcare' )
			);
			?>
		</form>
	</div>
	<?php
}
