<?php
namespace EMENJ;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles error and debug logging.
 *
 * @package EMENJ
 */
class Logger {

	/**
	 * Log a message to debug.log if WP_DEBUG is enabled.
	 *
	 * @param string $message The log message.
	 * @param string $level   The log level (info, warning, error, debug).
	 * @return void
	 */
	public static function log( string $message, string $level = 'info' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$formatted = sprintf(
			'[%1$s] [EMENJ %2$s] %3$s',
			gmdate( 'Y-m-d H:i:s' ),
			strtoupper( $level ),
			$message
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $formatted );
	}
}
