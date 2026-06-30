<?php
/**
 * Entries & Media Exporter by Naren Jadav
 *
 * @package           EMENJ
 * @author            Naren Jadav
 * @copyright         2026 Naren Jadav
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Entries & Media Exporter by Naren Jadav
 * Plugin URI:        https://github.com/narenjadav/entries-media-exporter-nj
 * Description:       Refactored and modernized tool to export Gravity Forms entries to CSV with all uploaded files packaged into a downloadable ZIP, with options for automatic post-export server cleanup.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:            Naren Jadav
 * Author URI:        https://narenjadav.com
 * Text Domain:       entries-media-exporter-nj
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants.
define( 'EME_NJ_VERSION', '1.0.0' );
define( 'EME_NJ_SLUG', 'emenj-exporter' );
define( 'EME_NJ_FILE', __FILE__ );
define( 'EME_NJ_PATH', plugin_dir_path( __FILE__ ) );
define( 'EME_NJ_URL', plugin_dir_url( __FILE__ ) );

// Load Autoloader.
require_once EME_NJ_PATH . 'includes/class-loader.php';
\EMENJ\Loader::init();

// Register Activation and Deactivation Hooks.
register_activation_hook( EME_NJ_FILE, array( '\EMENJ\Plugin', 'activate' ) );
register_deactivation_hook( EME_NJ_FILE, array( '\EMENJ\Plugin', 'deactivate' ) );

// Initialize the plugin.
add_action(
	'plugins_loaded',
	function () {
		// Boot main plugin instance.
		\EMENJ\Plugin::get_instance();
	}
);
