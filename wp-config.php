<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('WP_CACHE', true);
define( 'WPCACHEHOME', '/home/shezho5/collectionfun.club/wp-content/plugins/wp-super-cache/' );
define('DB_NAME', 'collectionfun_club');

/** MySQL database username */
define('DB_USER', 'collectionfunclu');

/** MySQL database password */
define('DB_PASSWORD', 'VGWjNdhM');

/** MySQL hostname */
define('DB_HOST', 'mysql.collectionfun.club');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '3?`AD%_Cce0Ldw!9YNUevKXaX~Eq$bEB!V2E9IBAYHW!6J`5"jAEXnUG7y:ZgabD');
define('SECURE_AUTH_KEY',  'Q8^D`O4T+?XG2+i+q)81FV$ZSD!??;(6xG#Rbf9M(HvXdMzHS@a8"Aez#z"gUHpU');
define('LOGGED_IN_KEY',    'J)pClUrXFI:Z|ZWJC``s%rN!r@"~gn#i/P*I)xpZ_z$xO*vnn*S&|5krPqsJ%:z8');
define('NONCE_KEY',        'accD`$YX^ZE8qkZ02SpS*RLh/NzSin692AYi2?h?gVDUSG!zxz(o0t:3|GOdPM"P');
define('AUTH_SALT',        ':O2kCr`p:ll&UcIqb~qVih~Z`2v214vhn+XSz!zluh4k:7|XEwMegfAp)Ao5RH9z');
define('SECURE_AUTH_SALT', 'oVII/;m"AgcdD!w;@Ccj09er1IK$P"&DxPMBkdXC#qodYBig/vf$WGn(vb0N_pV5');
define('LOGGED_IN_SALT',   '5g#K!G/l^o($^NND)d/f*3C!q4BUhO"L#D0;TQIS;o$+&vseal?/m;GyB_EEy&yT');
define('NONCE_SALT',       'Ol$77^HkdTt63"pIWfG(Gd~l03D*&iJn3?FA`4kO5kA7n/K6dRmyd)u5y%cGEI!$');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_vq59wb_';

/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_DEBUG_LOG', true );

/**
 * Removing this could cause issues with your experience in the DreamHost panel
 */

if (preg_match("/^(.*)\.dream\.website$/", $_SERVER['HTTP_HOST'])) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        define('WP_SITEURL', $proto . '://' . $_SERVER['HTTP_HOST']);
        define('WP_HOME',    $proto . '://' . $_SERVER['HTTP_HOST']);
}

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

