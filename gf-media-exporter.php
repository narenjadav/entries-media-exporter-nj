<?php
/**
 * Plugin Name: GF Media Exporter
 * Description: Refactored and modernized tool to export Gravity Forms entries to CSV with all uploaded files packaged into a downloadable ZIP, with options for automatic post-export server cleanup.
 * Version:     1.0.0
 * Author:      Naren Jadav
 * Author URI:  https://narenjadav.com
 * License:     GPL-2.0-or-later
 * Text Domain: gf-media-exporter
 * Domain Path: /languages
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants.
define( 'GFME_VERSION', '1.0.0' );
define( 'GFME_SLUG', 'gfme-exporter' );
define( 'GFME_FILE', __FILE__ );
define( 'GFME_PATH', plugin_dir_path( __FILE__ ) );
define( 'GFME_URL', plugin_dir_url( __FILE__ ) );

// Load Autoloader.
require_once GFME_PATH . 'includes/class-loader.php';

// Initialize the plugin.
add_action(
	'plugins_loaded',
	function () {
		// Initialize autoloader.
		\GFME\Loader::init();

		// Boot main plugin instance.
		\GFME\Plugin::get_instance();
	}
);
