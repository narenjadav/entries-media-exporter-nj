<?php
namespace GFME;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use GFAPI;
use WP_Error;

/**
 * Data layer interface to Gravity Forms.
 *
 * @package GFME
 */
class GravityForms {

	/**
	 * File upload field types we know how to extract URLs from.
	 */
	const FILE_FIELD_TYPES = array( 'fileupload', 'post_image' );

	/**
	 * Fetch all active forms.
	 *
	 * @return array|WP_Error Array of forms or WP_Error on error.
	 */
	public function get_forms() {
		return GFAPI::get_forms();
	}

	/**
	 * Fetch form details.
	 *
	 * @param int $form_id Form ID.
	 * @return array|false Form array or false if not found.
	 */
	public function get_form( int $form_id ) {
		$form = GFAPI::get_form( $form_id );
		return $form ? $form : false;
	}

	/**
	 * Count entries.
	 *
	 * @param int   $form_id         Form ID.
	 * @param array $search_criteria Search filters.
	 * @return int
	 */
	public function count_entries( int $form_id, array $search_criteria = array() ): int {
		return GFAPI::count_entries( $form_id, $search_criteria );
	}

	/**
	 * Check if form has file-upload fields.
	 *
	 * @param array $form Form array.
	 * @return bool
	 */
	public function form_has_file_fields( array $form ): bool {
		return ! empty( $this->get_file_fields( $form ) );
	}

	/**
	 * Get file-upload field objects from form.
	 *
	 * @param array $form Form array.
	 * @return array Array of GF_Field objects.
	 */
	public function get_file_fields( array $form ): array {
		$fields = array();
		if ( empty( $form['fields'] ) ) {
			return $fields;
		}
		foreach ( $form['fields'] as $field ) {
			if ( in_array( $field->type, self::FILE_FIELD_TYPES, true ) ) {
				$fields[] = $field;
			}
		}
		return $fields;
	}

	/**
	 * Fetch all matching entries, paging so we don't blow up memory on large forms.
	 *
	 * @param int   $form_id         Form ID.
	 * @param array $search_criteria Search criteria.
	 * @return array Array of entry arrays.
	 */
	public function get_matching_entries( int $form_id, array $search_criteria ): array {
		$entries   = array();
		$page_size = 200;
		$offset    = 0;
		do {
			$batch = GFAPI::get_entries(
				$form_id,
				$search_criteria,
				null,
				array(
					'offset'    => $offset,
					'page_size' => $page_size,
				)
			);
			if ( is_wp_error( $batch ) || empty( $batch ) ) {
				break;
			}
			$entries = array_merge( $entries, $batch );
			$offset += $page_size;
		} while ( count( $batch ) === $page_size );
		return $entries;
	}

	/**
	 * Collect all file URLs stored in an entry for the given file fields.
	 *
	 * Single-file fileupload stores a plain URL string. Multi-file fileupload
	 * stores a JSON-encoded array of URLs. post_image stores a pipe-delimited
	 * "url|title|caption|description|alt" string.
	 *
	 * @param array $entry       Entry array.
	 * @param array $file_fields File fields.
	 * @return array URLs.
	 */
	public function collect_entry_file_urls( array $entry, array $file_fields ): array {
		$urls = array();
		foreach ( $file_fields as $field ) {
			$raw = rgar( $entry, (string) $field->id );
			if ( '' === $raw || null === $raw ) {
				continue;
			}

			if ( 'post_image' === $field->type ) {
				$first = explode( '|', $raw );
				if ( ! empty( $first[0] ) ) {
					$urls[] = trim( $first[0] );
				}
				continue;
			}

			// fileupload: could be JSON array (multi-file) or a single URL.
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $u ) {
					if ( ! empty( $u ) ) {
						$urls[] = trim( $u );
					}
				}
			} else {
				$urls[] = trim( $raw );
			}
		}
		return array_values( array_filter( $urls ) );
	}

	/**
	 * Check if seeding developer sample data is enabled.
	 *
	 * @return bool
	 */
	public function seeding_enabled(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Seed sample entries with real files.
	 *
	 * @param int $form_id Form ID.
	 * @param int $count   Number of entries to seed.
	 * @return int|WP_Error Number of entries created, or WP_Error.
	 */
	public function seed_sample_entries( int $form_id, int $count ) {
		$form = $this->get_form( $form_id );
		if ( ! $form ) {
			return new WP_Error( 'no_form', __( 'Form not found.', 'gf-media-exporter' ) );
		}

		$uploads = wp_get_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'uploads', __( 'Uploads directory is not writable: ', 'gf-media-exporter' ) . $uploads['error'] );
		}

		$dir_path = trailingslashit( $uploads['basedir'] ) . 'gfme-samples';
		$dir_url  = trailingslashit( $uploads['baseurl'] ) . 'gfme-samples';
		if ( ! wp_mkdir_p( $dir_path ) ) {
			return new WP_Error( 'mkdir', __( 'Could not create the sample files directory.', 'gf-media-exporter' ) );
		}

		$created = 0;

		for ( $i = 1; $i <= $count; $i++ ) {
			$entry = array( 'form_id' => $form_id );

			foreach ( $form['fields'] as $field ) {
				$fid = (string) $field->id;

				if ( in_array( $field->type, self::FILE_FIELD_TYPES, true ) ) {
					$urls = $this->generate_sample_files( $dir_path, $dir_url, $i, $field );
					if ( empty( $urls ) ) {
						continue;
					}
					$is_multi = ( 'fileupload' === $field->type && ! empty( $field->multipleFiles ) );
					$entry[ $fid ] = $is_multi ? wp_json_encode( $urls ) : $urls[0];
					continue;
				}

				switch ( $field->type ) {
					case 'text':
					case 'name':
						$entry[ $fid ] = 'Sample ' . $field->label . ' ' . $i;
						break;
					case 'email':
						$entry[ $fid ] = 'sample' . $i . '@example.com';
						break;
					case 'textarea':
						$entry[ $fid ] = 'This is sample entry #' . $i . '.';
						break;
				}
			}

			$result = GFAPI::add_entry( $entry );
			if ( ! is_wp_error( $result ) ) {
				$created++;
			}
		}

		if ( 0 === $created ) {
			return new WP_Error( 'none', __( 'No sample entries could be created. Check the debug log.', 'gf-media-exporter' ) );
		}
		return $created;
	}

	/**
	 * Write small dummy files for seeding.
	 *
	 * @param string   $dir_path    Target base directory path.
	 * @param string   $dir_url     Target base URL path.
	 * @param int      $entry_index Seed index.
	 * @param \GF_Field $field       GF Field instance.
	 * @return array List of URL paths created.
	 */
	private function generate_sample_files( string $dir_path, string $dir_url, int $entry_index, $field ): array {
		$urls     = array();
		$is_multi = ( 'fileupload' === $field->type && ! empty( $field->multipleFiles ) );
		$num      = $is_multi ? 2 : 1;

		$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPgPAAEEAQB9ssjfAAAAAElFTkSuQmCC' );

		for ( $n = 1; $n <= $num; $n++ ) {
			if ( 1 === $n ) {
				$name    = sprintf( 'sample-entry%d-field%d.txt', $entry_index, $field->id );
				$content = "Sample upload for entry {$entry_index}, field {$field->id} ({$field->label}).\nGenerated by GF Media Exporter (dev seeder).\n";
			} else {
				$name    = sprintf( 'sample-entry%d-field%d.png', $entry_index, $field->id );
				$content = $png;
			}

			$path  = trailingslashit( $dir_path ) . $name;
			$bytes = file_put_contents( $path, $content );
			if ( false !== $bytes ) {
				$urls[] = trailingslashit( $dir_url ) . $name;
			}
		}
		return $urls;
	}

	/**
	 * Get the date of the oldest entry for a form.
	 *
	 * @param int $form_id Form ID.
	 * @return string Oldest entry date (YYYY-MM-DD) or empty string.
	 */
	public function get_oldest_entry_date( int $form_id ): string {
		$oldest = GFAPI::get_entries(
			$form_id,
			array(),
			array( 'key' => 'date_created', 'direction' => 'ASC' ),
			array( 'offset' => 0, 'page_size' => 1 )
		);
		if ( ! empty( $oldest ) && ! is_wp_error( $oldest ) ) {
			return gmdate( 'Y-m-d', strtotime( $oldest[0]['date_created'] ) );
		}
		return '';
	}

	/**
	 * Deletes an entry.
	 *
	 * @param int $entry_id Entry ID.
	 * @return bool|WP_Error True if deleted, or WP_Error on failure.
	 */
	public function delete_entry( int $entry_id ) {
		return GFAPI::delete_entry( $entry_id );
	}
}
