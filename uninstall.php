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

// Clean up any developer seeder files.
$uploads = wp_get_upload_dir();
if ( empty( $uploads['error'] ) ) {
	$dir_path = trailingslashit( $uploads['basedir'] ) . 'emenj-samples';
	if ( is_dir( $dir_path ) ) {
		$files = glob( $dir_path . '/*' );
		if ( $files ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
		// phpcs:ignore WordPress.VIP.FileSystemInputOnError.SafeDirectoryDelete
		rmdir( $dir_path );
	}
}
