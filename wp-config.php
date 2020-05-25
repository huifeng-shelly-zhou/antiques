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
define( 'WPCACHEHOME', '/home/shezho5/antiques-fun.club/wp-content/plugins/wp-super-cache/' );
define('DB_NAME', 'antiques_fun_club');

/** MySQL database username */
define('DB_USER', 'antiquesfunclub');

/** MySQL database password */
define('DB_PASSWORD', 'sG8cVdzP');

/** MySQL hostname */
define('DB_HOST', 'mysql.antiques-fun.club');

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
define('AUTH_KEY',         'RvSzz_Dt|Qws%x?5KBa*K:LTNnG2wJLhWi)8bH*Nm?LvSsqo^/svE6#"Sw(~b?ZJ');
define('SECURE_AUTH_KEY',  '2X"uqDEl&kdoArXg`4*fnh2kYS9se)7+0?`Dz^V9;$+epkMd$Yi!:T!9hJoT_Ao+');
define('LOGGED_IN_KEY',    '/sW!+&eIJmO_P&#2#i4V4H8omrvoV~0@4ON_00D)^1D3J5tRz0o^Nm`6gn;YRnr|');
define('NONCE_KEY',        '$m75E$isl~Fi8:$YHf1s`^EUE4fQ$g/dt;z(pZ+`N1KwaSTWR@p"%L/hI?"S5W*&');
define('AUTH_SALT',        'yr(;N:z9o#6lNu+F|jyEUR)zR*3fg3^PI4%7_um6dFB#Y$5%_+sPBqApv2oc(5+B');
define('SECURE_AUTH_SALT', 'q+E"Ta^h?22"p)*SHaMGEp78/fUzHqAy3K0om69DP&LPye0jDp;mAe3KYpwOOIqH');
define('LOGGED_IN_SALT',   'xcE6rw/wNa@pob)Ft$(JS9YX?)pHwpa2DnTyDS"a1zysM|wY_"r^ZlmTZR?;*T"L');
define('NONCE_SALT',       'iVe#Y|tcLq$Pmn+fy1M+cwj6D%EYuvfKDXWkZ?+wqb_hI"ffN?)5VNYqsOhyZlil');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_4frue7_';

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
define('WP_DEBUG', true);

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

