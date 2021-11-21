<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'lformcom_a2wp179' );

/** MySQL database username */
define( 'DB_USER', 'lformcom_a2wp179' );

/** MySQL database password */
define( 'DB_PASSWORD', 'Q4S!1wpA-7' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'noz1eiy2clskhqqlsesk4x0nvcxsnzcchqytjyj3paus1b0airfo8xcpkdxgkhn6' );
define( 'SECURE_AUTH_KEY',  'tp2geor55rq6ydb0w9zz0urjghyia17n3dvkyfmfcvma1llrlblqebwu6iqy7rfl' );
define( 'LOGGED_IN_KEY',    'xlqfvrh9rlsvisye3o2we2j4wslzyxcf7xwhcaof4fwlakyulbroi0qev4j0a0hp' );
define( 'NONCE_KEY',        'btyewsoa4giangwfcoy9smwfcaihks1iug9qvqagru7uzorj01xaxe0kvssjgkyw' );
define( 'AUTH_SALT',        'eoylnlqgbrjdkzvvfpa2iv5fknq0blgbyibh6fhksfgwtsyzwpqlhin4wz0sfcng' );
define( 'SECURE_AUTH_SALT', 'b0gflm4mzqmrw1tgvfhlv743xamq1qsbkixgq7xqtn8h7sceyzry7nisihbfly2r' );
define( 'LOGGED_IN_SALT',   'kw9riijhmpdkcw2prte8xitthtiiyefwc45zdzlpnhg57jcdyki1uddgbo3lapn4' );
define( 'NONCE_SALT',       't3prpvdgb1qlbz96lnvou0vp9g3ircwajb9u7dk9qghs1aau2cu54h1vlwav6btd' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
