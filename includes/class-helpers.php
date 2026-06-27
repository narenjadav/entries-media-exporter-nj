<?php
namespace GFME;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Utility helper methods for GF Media Exporter.
 *
 * @package GFME
 */
class Helpers {

	/**
	 * Accept only YYYY-MM-DD; return empty string otherwise.
	 *
	 * @param string $value Date string.
	 * @return string Sanitized date or empty string.
	 */
	public static function sanitize_date( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	/**
	 * Safe basename for files, removing special characters.
	 *
	 * @param string $name Filename.
	 * @return string Safe basename.
	 */
	public static function safe_basename( string $name ): string {
		$name = basename( $name );
		$name = preg_replace( '/[^A-Za-z0-9._\-]/', '_', $name );
		return $name === '' ? 'file' : $name;
	}

	/**
	 * Map an uploads URL to its local filesystem path if it lives inside this site's uploads dir.
	 *
	 * @param string $url URL to map.
	 * @return string|false Absolute local path, or false if not local/invalid/traversal.
	 */
	public static function url_to_local_path( string $url ) {
		$uploads = wp_get_upload_dir();
		if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
			return false;
		}

		// Normalize scheme so http/https mismatches still match.
		$normalize = function ( string $u ): string {
			return (string) preg_replace( '#^https?://#', '', $u );
		};

		$base_url = $normalize( $uploads['baseurl'] );
		$file_url = $normalize( $url );

		if ( 0 !== strpos( $file_url, $base_url ) ) {
			return false;
		}

		$relative = ltrim( substr( $file_url, strlen( $base_url ) ), '/' );
		$relative = explode( '?', $relative )[0]; // strip query string
		$path     = trailingslashit( $uploads['basedir'] ) . $relative;

		// Guard against path traversal.
		$real_base = realpath( $uploads['basedir'] );
		$real_path = realpath( $path );
		if ( ! $real_base || ! $real_path || 0 !== strpos( $real_path, $real_base ) ) {
			return false;
		}
		return $real_path;
	}

	/**
	 * Ensure no two files collide on the same path inside the ZIP.
	 *
	 * @param \ZipArchive $zip  ZipArchive instance.
	 * @param string      $path Desired path inside zip.
	 * @return string Unique path inside zip.
	 */
	public static function dedupe_zip_path( \ZipArchive $zip, string $path ): string {
		if ( false === $zip->locateName( $path ) ) {
			return $path;
		}
		$dir  = dirname( $path );
		$ext  = pathinfo( $path, PATHINFO_EXTENSION );
		$base = pathinfo( $path, PATHINFO_FILENAME );
		$i    = 1;
		do {
			$candidate = ( '.' === $dir ? '' : $dir . '/' ) . $base . '-' . $i . ( $ext ? '.' . $ext : '' );
			$i++;
		} while ( false !== $zip->locateName( $candidate ) );
		return $candidate;
	}
}
