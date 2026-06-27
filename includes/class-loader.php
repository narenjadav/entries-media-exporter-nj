<?php
namespace GFME;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Autoloader class for GF Media Exporter.
 *
 * @package GFME
 */
class Loader {

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function init() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload classes based on namespace and WordPress standards.
	 *
	 * Maps namespace \GFME\My_Class to includes/class-my-class.php (lowercase, hyphenated).
	 *
	 * @param string $class Class name to load.
	 * @return void
	 */
	public static function autoload( $class ) {
		// Only autoload classes in our namespace.
		if ( 0 !== strpos( $class, 'GFME\\' ) ) {
			return;
		}

		// Remove the namespace prefix.
		$relative_class = substr( $class, 5 );

		// Convert class name to WordPress standard filename format.
		// Class Name: My_Class_Name -> class-my-class-name.php
		// Namespace: Admin\Settings_Page -> includes/admin/class-settings-page.php
		$parts = explode( '\\', $relative_class );
		$file  = 'class-' . strtolower( str_replace( '_', '-', array_pop( $parts ) ) ) . '.php';

		// Rebuild path.
		$sub_path = '';
		if ( ! empty( $parts ) ) {
			$sub_path = strtolower( implode( '/', $parts ) ) . '/';
		}

		$filepath = GFME_PATH . 'includes/' . $sub_path . $file;

		if ( file_exists( $filepath ) ) {
			require_once $filepath;
		}
	}
}
