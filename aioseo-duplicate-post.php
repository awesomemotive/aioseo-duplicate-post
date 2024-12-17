<?php
/**
 * Plugin Name: Duplicate Post by AIOSEO
 * Plugin URI:  https://aioseo.com/
 * Description: Clone any of your posts with just one click and schedule revisions to be automatically merged & published.
 * Author:      All in One SEO Team
 * Author URI:  https://aioseo.com
 * Version:     1.0.0
 * Text Domain: duplicate-post-page-aioseo
 * Domain Path: /languages
 * License:     GPL-3.0+
 *
 * Duplicate Post by AIOSEO is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Duplicate Post by AIOSEO is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Duplicate Post by AIOSEO. If not, see <https://www.gnu.org/licenses/>.
 *
 * @since     1.0.0
 * @author    All in One SEO
 * @license   GPL-2.0+
 * @copyright Copyright (c) 2023, All in One SEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AIOSEO_DUPLICATE_POST_PHP_VERSION_DIR' ) ) {
	define( 'AIOSEO_DUPLICATE_POST_PHP_VERSION_DIR', basename( dirname( __FILE__ ) ) );
}

require_once dirname( __FILE__ ) . '/app/init/init.php';

// Check if this plugin should be disabled.
if ( aioseo_duplicate_post_is_plugin_disabled() ) {
	return;
}

require_once dirname( __FILE__ ) . '/app/init/notices.php';

// We require PHP 7.0 or higher for the whole plugin to work.
if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
	add_action( 'admin_notices', 'aioseo_plugin_php_notice' );

	// Do not process the plugin code further.
	return;
}

// We require WP 5.3+ for the whole plugin to work.
global $wp_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
if ( version_compare( $wp_version, '5.3', '<' ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
	add_action( 'admin_notices', 'aioseo_plugin_wordpress_notice' );

	// Do not process the plugin code further.
	return;
}

// Plugin constants.
if ( ! defined( 'AIOSEO_DUPLICATE_POST_DIR' ) ) {
	define( 'AIOSEO_DUPLICATE_POST_DIR', __DIR__ );
}
if ( ! defined( 'AIOSEO_DUPLICATE_POST_FILE' ) ) {
	define( 'AIOSEO_DUPLICATE_POST_FILE', __FILE__ );
}

// Define the class and the function.
require_once dirname( __FILE__ ) . '/app/DuplicatePost.php';

aioseoDuplicatePost();