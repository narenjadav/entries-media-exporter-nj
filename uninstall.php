<?php
/**
 * Entries & Media Exporter by Naren Jadav Uninstall
 *
 * @package EMENJ
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Perform uninstallation cleanup.
 *
 * @return void
 */
function emenj_uninstall_cleanup() {
	$uploads = wp_get_upload_dir();
	if ( empty( $uploads['error'] ) ) {
		$dir_path = trailingslashit( $uploads['basedir'] ) . 'emenj-samples';
		if ( is_dir( $dir_path ) ) {
			global $wp_filesystem;
			if ( ! $wp_filesystem ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}
			if ( $wp_filesystem ) {
				$wp_filesystem->delete( $dir_path, true );
			}
		}
	}
}

emenj_uninstall_cleanup();
