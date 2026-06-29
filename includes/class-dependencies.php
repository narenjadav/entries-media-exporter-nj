<?php
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
		$errors = array();

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
