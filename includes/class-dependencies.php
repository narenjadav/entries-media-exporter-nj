<?php
namespace GFME;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles dependency verification for the plugin.
 *
 * @package GFME
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
				__( 'GF Media Exporter requires PHP version %s or greater.', 'gf-media-exporter' ),
				'8.0.0'
			);
		}

		// Check Gravity Forms.
		if ( ! class_exists( 'GFAPI' ) ) {
			$errors[] = __( 'GF Media Exporter requires Gravity Forms to be installed and activated.', 'gf-media-exporter' );
		}

		// Check ZipArchive extension.
		if ( ! class_exists( 'ZipArchive' ) ) {
			$errors[] = __( 'GF Media Exporter requires the PHP ZipArchive extension to build the ZIP package.', 'gf-media-exporter' );
		}

		return array(
			'status' => empty( $errors ),
			'errors' => $errors,
		);
	}
}
