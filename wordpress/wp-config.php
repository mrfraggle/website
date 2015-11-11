<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link https://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'test_wp');

/** MySQL database username */
define('DB_USER', 'rtd_dk');

/** MySQL database password */
define('DB_PASSWORD', 'ryed3VB2VfRdeLpK');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

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
define('AUTH_KEY',         '%=Z44PyjntE=)ayy?S;n5wTzIsGz8./+9O,{e|6Zw,5ScNeuB2.fb[rJq5^@F^gC');
define('SECURE_AUTH_KEY',  'rh9q-^T]-ySeJDQ4%>afmspz#i[l|dP8tn2ug/U~+-ll(/:#89{.LUu.IRB2}v}-');
define('LOGGED_IN_KEY',    '%2=OD),n^N9xY.!-+J/[Edl!j3Pj|eLG|qZ^&9|HZoL9W[G84.4RPGC{+7lVK89X');
define('NONCE_KEY',        ']&-*TUFzLqY4shQjk+olcI0LwZj;@w=d{8?,=JnJ!`nzP$YMOgB-@sNd]LeKHd9Y');
define('AUTH_SALT',        '8AoFdG6Vko+t9)-l(!qoF{!YFFdq+6l!nEp9k/A>zEUVWJ$ZCh>{jl!rW*%7|+O]');
define('SECURE_AUTH_SALT', 'W{?|)lI@XEv-s#!Q:JzN[_$oro1UhL.-H=e)_K%C=(epEu/7eE0EI7)oh(YK$YUC');
define('LOGGED_IN_SALT',   '|R3%-HjNIw0+94SgFHO4d+IV.y+@4`a>GQjXfd-JfQ-zVsz%@w,LS9,^2j51y&pe');
define('NONCE_SALT',       'TDO#i&G%K)t/rA*fOV]9&1||v!]j>qr-iF?gk[)?L)+`erxtTyJ/9|*%Xy&b@x8V');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_rtd';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

define( 'COOKIEPATH', '/' );
define( 'SITECOOKIEPATH', '/' );
define( 'ADMIN_COOKIE_PATH', SITECOOKIEPATH . 'wordpress/wp-admin' );
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/*define('COOKIE_DOMAIN', 'http://dev.rtd.dk/wordpress');
define( 'COOKIEPATH', preg_replace( '|https?://[^/]+|i', '', get_option( 'home' ) . '/' ) );
define( 'SITECOOKIEPATH', preg_replace( '|https?://[^/]+|i', '', get_option( 'siteurl' ) . '/' ) );
define( 'ADMIN_COOKIE_PATH', SITECOOKIEPATH . 'wp-admin' );
define( 'PLUGINS_COOKIE_PATH', preg_replace( '|https?://[^/]+|i', '', WP_PLUGIN_URL ) );
define( 'TEMPLATEPATH', get_template_directory() );
define( 'STYLESHEETPATH', get_stylesheet_directory() );*/

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

