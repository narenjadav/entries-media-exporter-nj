<?php
namespace GFME;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles rendering admin notices.
 *
 * @package GFME
 */
class Notices {

	/**
	 * Errors to display.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Set the errors to display.
	 *
	 * @param array $errors Array of string error messages.
	 * @return void
	 */
	public function set_errors( array $errors ) {
		$this->errors = $errors;
	}

	/**
	 * Register hooks for displaying notices.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	/**
	 * Render the notices.
	 *
	 * @return void
	 */
	public function render_notices() {
		if ( empty( $this->errors ) ) {
			return;
		}

		foreach ( $this->errors as $error ) {
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong></p></div>',
				esc_html( $error )
			);
		}
	}
}
