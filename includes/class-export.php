<?php
namespace GFME;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use ZipArchive;
use WP_Error;

/**
 * Handles processing export, removal, and seeding requests.
 *
 * @package GFME
 */
class Export {

	/**
	 * Temp files pending deletion after ZIP is built.
	 *
	 * @var array
	 */
	private $temp_to_clean = array();

	/**
	 * Gravity Forms API wrapper.
	 *
	 * @var GravityForms
	 */
	private $gf;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->gf = new GravityForms();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init() {
		// Stream/handle requests early before headers are sent.
		add_action( 'admin_init', array( $this, 'maybe_handle_requests' ) );
	}

	/**
	 * Handle incoming administrative requests.
	 *
	 * @return void
	 */
	public function maybe_handle_requests() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page   = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_REQUEST['gfme_action'] ) ? sanitize_key( wp_unslash( $_REQUEST['gfme_action'] ) ) : '';

		if ( GFME_SLUG !== $page || 'export' !== $action ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'gf-media-exporter' ) );
		}

		$this->handle_export();
	}

	/**
	 * Run removal business logic.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $options Removal filters.
	 * @return array|WP_Error Array of delete results, or WP_Error.
	 */
	public function run_remove( int $form_id, array $options ) {
		$form = $this->gf->get_form( $form_id );
		if ( ! $form ) {
			return new WP_Error( 'no_form', __( 'Form not found.', 'gf-media-exporter' ) );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 0 );
		}

		$file_fields     = $this->gf->get_file_fields( $form );
		$search_criteria = $this->build_search_criteria( $options );
		$entries         = $this->gf->get_matching_entries( $form_id, $search_criteria );

		$entries_deleted = 0;
		$files_deleted   = 0;
		$files_failed    = 0;

		foreach ( $entries as $entry ) {
			// Delete local files.
			if ( ! empty( $file_fields ) ) {
				$urls = $this->gf->collect_entry_file_urls( $entry, $file_fields );
				foreach ( $urls as $url ) {
					$local = Helpers::url_to_local_path( $url );
					if ( ! $local ) {
						continue;
					}
					if ( ! file_exists( $local ) ) {
						continue;
					}
					if ( wp_delete_file( $local ) ) {
						$files_deleted++;
						$this->maybe_remove_empty_dir( dirname( $local ) );
					} else {
						$files_failed++;
					}
				}
			}

			// Delete entry record.
			$deleted = $this->gf->delete_entry( absint( rgar( $entry, 'id' ) ) );
			if ( ! is_wp_error( $deleted ) ) {
				$entries_deleted++;
			}
		}

		return array(
			'entries_deleted' => $entries_deleted,
			'files_deleted'   => $files_deleted,
			'files_failed'    => $files_failed,
		);
	}

	/**
	 * Remove a directory if it sits inside uploads and is now empty.
	 *
	 * @param string $dir Path to folder.
	 * @return void
	 */
	private function maybe_remove_empty_dir( string $dir ) {
		$uploads = wp_get_upload_dir();
		$base    = realpath( $uploads['basedir'] ?? '' );
		$real    = realpath( $dir );

		if ( ! $base || ! $real || $real === $base || 0 !== strpos( $real, $base ) ) {
			return;
		}

		$items = @scandir( $real );
		if ( is_array( $items ) && 0 === count( array_diff( $items, array( '.', '..' ) ) ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			@rmdir( $real );
		}
	}

	/**
	 * Handle main ZIP/CSV export action.
	 *
	 * @return void
	 */
	private function handle_export() {
		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! $form_id || ! wp_verify_nonce( $nonce, 'gfme_export_' . $form_id ) ) {
			$this->redirect_with_error( $form_id, __( 'Security check failed. Please try again.', 'gf-media-exporter' ) );
		}

		$form = $this->gf->get_form( $form_id );
		if ( ! $form ) {
			$this->redirect_with_error( $form_id, __( 'Form not found.', 'gf-media-exporter' ) );
		}

		$raw_start  = isset( $_GET['date_start'] ) ? sanitize_text_field( wp_unslash( $_GET['date_start'] ) ) : '';
		$raw_end    = isset( $_GET['date_end'] ) ? sanitize_text_field( wp_unslash( $_GET['date_end'] ) ) : '';
		$date_start = Helpers::sanitize_date( $raw_start );
		$date_end   = Helpers::sanitize_date( $raw_end );
		$files_only = ! empty( $_GET['files_only'] );

		if ( $date_start && $date_end && $date_start > $date_end ) {
			$this->redirect_with_error( $form_id, __( 'The start date must be before the end date.', 'gf-media-exporter' ) );
		}

		$this->run_export(
			$form_id,
			array(
				'date_start' => $date_start,
				'date_end'   => $date_end,
				'files_only' => $files_only,
			)
		);
	}

	/**
	 * Build ZIP and stream to browser.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $options Export settings.
	 * @return void
	 */
	private function run_export( int $form_id, array $options ) {
		$form = $this->gf->get_form( $form_id );
		if ( ! $form ) {
			$this->redirect_with_error( $form_id, __( 'Form not found.', 'gf-media-exporter' ) );
		}

		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 0 );
		}

		$file_fields = $this->gf->get_file_fields( $form );

		if ( $options['files_only'] && empty( $file_fields ) ) {
			$this->redirect_with_error( $form_id, __( 'Files-only export was requested, but this form has no file fields.', 'gf-media-exporter' ) );
		}

		$search_criteria = $this->build_search_criteria( $options );
		$entries         = $this->gf->get_matching_entries( $form_id, $search_criteria );

		// Prepare a temp working zip.
		$tmp_zip = wp_tempnam( 'gfme-export' );
		$zip     = new ZipArchive();
		if ( true !== $zip->open( $tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			$this->redirect_with_error( $form_id, __( 'Could not create the ZIP archive.', 'gf-media-exporter' ) );
		}

		$added           = 0;
		$failed          = array();
		$entry_zip_paths = array();

		if ( ! empty( $file_fields ) ) {
			foreach ( $entries as $entry ) {
				$entry_id = absint( rgar( $entry, 'id' ) );
				$urls     = $this->gf->collect_entry_file_urls( $entry, $file_fields );
				foreach ( $urls as $url ) {
					$local = $this->fetch_file( $url );
					if ( false === $local || ! is_readable( $local['path'] ) ) {
						$failed[] = $url;
						continue;
					}

					$zip_path = 'files/' . Helpers::safe_basename( $local['name'] );
					$zip_path = Helpers::dedupe_zip_path( $zip, $zip_path );
					$zip->addFile( $local['path'], $zip_path );

					if ( ! empty( $local['is_temp'] ) ) {
						$this->temp_to_clean[] = $local['path'];
					}

					$entry_zip_paths[ $entry_id ][] = $zip_path;
					$added++;
				}
			}
		}

		if ( ! $options['files_only'] ) {
			$zip->addFromString( 'entries.csv', $this->build_csv( $form, $entries, $file_fields, $entry_zip_paths ) );
		}

		$zip->close();

		// Cleanup temp files.
		if ( ! empty( $this->temp_to_clean ) ) {
			foreach ( $this->temp_to_clean as $t ) {
				wp_delete_file( $t );
			}
		}

		// Stream file.
		$form_slug = sanitize_title_with_dashes( $form['title'] );
		$filename  = sprintf(
			'%s-%d-%s.zip',
			$form_slug,
			$form_id,
			gmdate( 'Ymd-His' )
		);

		// Set download token cookie if present.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = isset( $_GET['gfme_download_token'] ) ? sanitize_key( wp_unslash( $_GET['gfme_download_token'] ) ) : '';
		if ( $token ) {
			setcookie( 'gfme_download_token', $token, time() + 600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl() );
		}

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $tmp_zip ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $tmp_zip );
		wp_delete_file( $tmp_zip );
		exit;
	}

	/**
	 * Build CSV data.
	 *
	 * @param array $form            Form details.
	 * @param array $entries         Entries matching.
	 * @param array $file_fields     Fields of type file/image.
	 * @param array $entry_zip_paths ZIP archive references mapping.
	 * @return string CSV content with UTF-8 BOM.
	 */
	private function build_csv( array $form, array $entries, array $file_fields, array $entry_zip_paths = array() ): string {
		$headers = array( 'entry_id', 'date_created', 'created_by', 'ip', 'source_url' );
		$columns = array();
		foreach ( $form['fields'] as $field ) {
			if ( in_array( $field->type, array( 'html', 'section', 'page', 'captcha' ), true ) ) {
				continue;
			}
			$columns[ (string) $field->id ] = $field->label;
			$headers[]                       = $field->label;
		}
		$headers[] = 'files_in_zip';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$fh = fopen( 'php://temp', 'r+' );
		if ( false === $fh ) {
			return '';
		}

		fputcsv( $fh, $headers );

		foreach ( $entries as $entry ) {
			$row = array(
				rgar( $entry, 'id' ),
				rgar( $entry, 'date_created' ),
				rgar( $entry, 'created_by' ),
				rgar( $entry, 'ip' ),
				rgar( $entry, 'source_url' ),
			);
			foreach ( $columns as $field_id => $label ) {
				$row[] = $this->get_entry_field_value( $entry, $field_id );
			}
			$entry_id = absint( rgar( $entry, 'id' ) );
			$paths    = isset( $entry_zip_paths[ $entry_id ] ) ? $entry_zip_paths[ $entry_id ] : array();
			$row[]    = implode( ' | ', $paths );
			fputcsv( $fh, $row );
		}

		rewind( $fh );
		$csv = stream_get_contents( $fh );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $fh );

		return "\xEF\xBB\xBF" . ( $csv ? $csv : '' );
	}

	/**
	 * Read field value.
	 *
	 * @param array  $entry    Entry details.
	 * @param string $field_id Field identifier.
	 * @return string Stringified and joined values.
	 */
	private function get_entry_field_value( array $entry, string $field_id ): string {
		if ( isset( $entry[ $field_id ] ) && '' !== $entry[ $field_id ] ) {
			return $this->stringify_value( $entry[ $field_id ] );
		}

		$parts = array();
		foreach ( $entry as $key => $val ) {
			if ( '' === $val ) {
				continue;
			}
			if ( 0 === strpos( (string) $key, $field_id . '.' ) ) {
				$parts[] = $this->stringify_value( $val );
			}
		}
		return implode( ' | ', $parts );
	}

	/**
	 * Stringify value.
	 *
	 * @param mixed $val Value to cast.
	 * @return string String representation.
	 */
	private function stringify_value( $val ): string {
		if ( is_array( $val ) ) {
			return implode( ', ', array_map( 'strval', $val ) );
		}
		return (string) $val;
	}

	/**
	 * Build GF search criteria from options.
	 *
	 * @param array $options Options array.
	 * @return array Criteria formatting.
	 */
	private function build_search_criteria( array $options ): array {
		$search_criteria = array();
		if ( ! empty( $options['date_start'] ) ) {
			$search_criteria['start_date'] = $options['date_start'] . ' 00:00:00';
		}
		if ( ! empty( $options['date_end'] ) ) {
			$search_criteria['end_date'] = $options['date_end'] . ' 23:59:59';
		}
		return $search_criteria;
	}

	/**
	 * Fetch a file from URL.
	 *
	 * @param string $url URL path.
	 * @return array|false Resolved temp file info, or false.
	 */
	private function fetch_file( string $url ) {
		$local_path = Helpers::url_to_local_path( $url );
		if ( $local_path && is_readable( $local_path ) ) {
			return array(
				'path'    => $local_path,
				'name'    => basename( wp_parse_url( $url, PHP_URL_PATH ) ),
				'is_temp' => false,
			);
		}

		// Fallback to remote HTTP download.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'  => 30,
				'stream'   => true,
				'filename' => wp_tempnam( 'gfme-dl' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$tmp  = $response['filename'] ?? '';
		if ( 200 !== (int) $code || ! $tmp || ! is_readable( $tmp ) ) {
			if ( $tmp ) {
				wp_delete_file( $tmp );
			}
			return false;
		}

		return array(
			'path'    => $tmp,
			'name'    => basename( wp_parse_url( $url, PHP_URL_PATH ) ),
			'is_temp' => true,
		);
	}

	/**
	 * Redirect with error.
	 *
	 * @param int    $form_id Form ID.
	 * @param string $message Error details.
	 * @return void
	 */
	private function redirect_with_error( int $form_id, string $message ) {
		$url = add_query_arg(
			array(
				'page'       => GFME_SLUG,
				'form_id'    => absint( $form_id ),
				'gfme_error' => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirect with notice.
	 *
	 * @param int    $form_id Form ID.
	 * @param string $message Notice details.
	 * @return void
	 */
	private function redirect_with_notice( int $form_id, string $message ) {
		$url = add_query_arg(
			array(
				'page'        => GFME_SLUG,
				'form_id'     => absint( $form_id ),
				'gfme_notice' => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
