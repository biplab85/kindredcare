<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'kindredweb' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'F!t{G3oNI;AY29VcY)w>wO`%A:Q9h6kePz1S1{zX<<}di]-Q@9&9M:Ni3&!tz[)x' );
define( 'SECURE_AUTH_KEY',   '[[0e#<[mid3;)KLL~}8R8E8AvJXK{Zl1~]FzyN86J{TW>186FFLXdjWggP)m75js' );
define( 'LOGGED_IN_KEY',     '3`HeNB-|P_%.4D^}&*xWhj!AD;a-Z1@1K%Xga9QMX&G0KtYoJpE8m,^;YG8CSy5(' );
define( 'NONCE_KEY',         '@XIa;tb@0[wlkibe?nd3W4}w$OCGUH&WwQBix6Xuq=p)q)tKY7GUr/=6@f)9Qm]m' );
define( 'AUTH_SALT',         'mu7w3.]NG!V8$Q9^-/Rti`xa+}Rrynee%A+5Bw5e>;0Xm-%?`l;4KKb5v$/WO>j3' );
define( 'SECURE_AUTH_SALT',  ',~knGmRRR~V[NdQ%Y#/*jRn%@N`HpO`)y*W70%71a4 Q5@eT#q]^b}@GO(0Nx(QA' );
define( 'LOGGED_IN_SALT',    ';%{O!FAYG2,u]{> *TW<1Fn}03xf|O+8 /|{g.: @_M-cE%oZ@^@]-*LtAxqtyj;' );
define( 'NONCE_SALT',        ';720m(^nxT77^i;`:s0.Os^c3~ygjRSe8XLJaXbe*SbKp2Bf.[U<Xpk$2qi u]1b' );
define( 'WP_CACHE_KEY_SALT', 's[stRuq)M9kGeTc*Fq=&|UwkZ/~4m(7_RO[vel-HK^$1lo+a3(<u82j);zote$)]' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'krc_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
