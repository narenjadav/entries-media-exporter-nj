<?php
/**
 * Dependencies verification class file for Entries & Media Exporter by Naren Jadav.
 *
 * @package EMENJ
 */

namespace EMENJ;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dependency verification for the plugin.
 *
 * @package EMENJ
 */
class Dependencies {

	/**
	 * Verify that all required dependencies are met.
	 *
	 * @return array Array containing 'status' => bool and 'errors' => array of string messages.
	 */
	public function verify(): array {
		global $wp_version;

		$errors = array();

		// Check WordPress Version (5.8+ required).
		if ( version_compare( $wp_version, '5.8', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required WordPress version */
				__( 'Entries & Media Exporter by Naren Jadav requires WordPress version %s or greater.', 'entries-media-exporter-nj' ),
				'5.8'
			);
		}

		// Check PHP Version (PHP 8.0+ required).
		if ( version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required PHP version */
				__( 'Entries & Media Exporter by Naren Jadav requires PHP version %s or greater.', 'entries-media-exporter-nj' ),
				'8.0.0'
			);
		}

		// Check Gravity Forms.
		if ( ! class_exists( 'GFAPI' ) ) {
			$errors[] = __( 'Entries & Media Exporter by Naren Jadav requires Gravity Forms to be installed and activated.', 'entries-media-exporter-nj' );
		}

		// Check ZipArchive extension.
		if ( ! class_exists( 'ZipArchive' ) ) {
			$errors[] = __( 'Entries & Media Exporter by Naren Jadav requires the PHP ZipArchive extension to build the ZIP package.', 'entries-media-exporter-nj' );
		}

		return array(
			'status' => empty( $errors ),
			'errors' => $errors,
		);
	}
}
