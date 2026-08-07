<?php
/**
 * KindredCare — WordPress configuration (local WAMP development).
 *
 * The site is a straight conversion of the static homepage in kindredcare-home/.
 * The UI does not change: markup and CSS are ported as-is into the theme, so
 * anything here that would alter rendered output (a plugin, a filter, an
 * auto-inserted tag) is deliberately absent.
 */

/* -- Database ------------------------------------------------------------- */
/**
 * MySQL 8.4.7 on port 3306 — WampServer's default instance. The MariaDB 11.4.9
 * server on 3307 is left alone; picking one and naming the port here means a
 * WAMP switch between them cannot silently repoint the site.
 */
define( 'DB_NAME', 'kindredcare_wp' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '127.0.0.1:3306' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', 'utf8mb4_unicode_520_ci' );

/**
 * Table prefix.
 *
 * Asked for as `kd-`. A hyphen is not usable here: WordPress interpolates the
 * prefix into SQL unquoted in core, in dbDelta and in most plugins, so
 * `kd-posts` parses as `kd` minus `posts` and every query fails. WordPress
 * validates this itself — wp-admin/setup-config.php rejects any prefix outside
 * [a-zA-Z0-9_]. `kd_` is the same two-letter namespace, spelled the way MySQL
 * and WordPress can both read.
 */
$table_prefix = 'kd_';

/* -- Keys and salts ------------------------------------------------------- */
/* Generated from https://api.wordpress.org/secret-key/1.1/salt/ */
define('AUTH_KEY',         'o_P.Q)T}O!+)3,f.U&!I*|J?{-!=V%F&6DtVWlIT&Ub~fgC|Dq}f_i0^Me[9eZ.a');
define('SECURE_AUTH_KEY',  '$={Z,QqKM}nef/Q8OBO^9_!oKid!<o]:V!f+|jIq/]9D4>GZO)8[;V-<`mm:1* +');
define('LOGGED_IN_KEY',    ',TaB8{>=]QMh$V<;bfR.]trHSR%O-tbG*3@<j|Qwe2CTRkSk|``u?nJ1aMVom_%P');
define('NONCE_KEY',        '(krR2Y?`B[:i7UrxJuG}Dn5D[era`fz@H^gK8qsi!rb|U1l=Ze!GBucbDS/*nc+0');
define('AUTH_SALT',        'o{E=y+v)wp}z}%@=4lSU Ka>fyN`BpiPY4+K}-m;-R2BdkMG*%$s{=}/~veG35%z');
define('SECURE_AUTH_SALT', 'iM@EBImV4(6]Y}&+#[<q6/j&r.vQ=%N+7x-8&g0-7tB?ym){S7ior78&^%9{o+.t');
define('LOGGED_IN_SALT',   'iyJtvw<?G:o^-^QIM%a)w.9jP(q9X*n=Pk}><,kY5alu8tU[8f(p4!El@F[jAbH0');
define('NONCE_SALT',       ' <-cC`kC&,6&EQT/0[E:+Ok5ehpM6D~y3_!<^SB7,Hv>5JrK`~}VFdqS6[JL%$#B');

/* -- Local development ---------------------------------------------------- */
/**
 * The install lives at a nested path under the WAMP document root, so both URLs
 * are pinned. Without them a stray request through a different host or path can
 * write the wrong siteurl into the options table and lock the admin out.
 */
define( 'WP_HOME', 'http://localhost/sklentr/kindrecare-logic/kindredcare-wp' );
define( 'WP_SITEURL', 'http://localhost/sklentr/kindrecare-logic/kindredcare-wp' );

/* Errors are logged, never printed: a notice echoed into <head> would move the
   markup we are matching pixel for pixel. Read wp-content/debug.log instead. */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

/* The theme is edited on disk and versioned. Editing it through the admin would
   put the source of truth in two places. */
define( 'DISALLOW_FILE_EDIT', true );

/* No revision pile-up while pages are being rebuilt repeatedly, and a short
   trash window on a machine where nothing is precious. */
define( 'WP_POST_REVISIONS', 5 );
define( 'EMPTY_TRASH_DAYS', 7 );

/* Core auto-updates off. Version bumps are a deliberate act here, not something
   that happens between two page loads mid-conversion. */
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'AUTOMATIC_UPDATER_DISABLED', true );

/* Generous while importing media and running the odd bulk task locally. */
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

/* -- Bootstrap ------------------------------------------------------------ */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
