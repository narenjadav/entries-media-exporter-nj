<?php
namespace GFME;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the WordPress administration screens.
 *
 * @package GFME
 */
class Admin {

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
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Register AJAX actions.
		add_action( 'wp_ajax_gfme_get_form_details', array( $this, 'ajax_get_form_details' ) );
		add_action( 'wp_ajax_gfme_seed_entries', array( $this, 'ajax_seed_entries' ) );
		add_action( 'wp_ajax_gfme_remove_entries', array( $this, 'ajax_remove_entries' ) );
	}

	/**
	 * Register menu under Gravity Forms menu, falling back to Tools.
	 *
	 * @return void
	 */
	public function register_menu() {
		$parent = class_exists( 'GFAPI' ) ? 'gf_edit_forms' : 'tools.php';

		add_submenu_page(
			$parent,
			__( 'GF Media Exporter', 'gf-media-exporter' ),
			__( 'Media Exporter', 'gf-media-exporter' ),
			'manage_options',
			GFME_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets (CSS and JS).
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ) {
		if ( strpos( $hook, GFME_SLUG ) === false ) {
			return;
		}

		// Enqueue SweetAlert2 from local assets.
		wp_enqueue_style(
			'sweetalert2',
			GFME_URL . 'assets/css/sweetalert2.min.css',
			array(),
			'11.10.0'
		);

		wp_enqueue_script(
			'sweetalert2',
			GFME_URL . 'assets/js/sweetalert2.all.min.js',
			array(),
			'11.10.0',
			true
		);

		// Enqueue Flatpickr (Datepicker) from local assets.
		wp_enqueue_style(
			'flatpickr',
			GFME_URL . 'assets/css/flatpickr.min.css',
			array(),
			'4.6.13'
		);
		wp_enqueue_script(
			'flatpickr',
			GFME_URL . 'assets/js/flatpickr.min.js',
			array(),
			'4.6.13',
			true
		);

		// Enqueue Select2 (Searchable Dropdown) from local assets.
		wp_enqueue_style(
			'select2',
			GFME_URL . 'assets/css/select2.min.css',
			array(),
			'4.1.0'
		);
		wp_enqueue_script(
			'select2',
			GFME_URL . 'assets/js/select2.min.js',
			array( 'jquery' ),
			'4.1.0',
			true
		);

		wp_enqueue_style(
			'gfme-admin-css',
			GFME_URL . 'assets/css/admin.css',
			array( 'sweetalert2', 'flatpickr', 'select2' ),
			GFME_VERSION
		);

		wp_enqueue_script(
			'gfme-admin-js',
			GFME_URL . 'assets/js/admin.js',
			array( 'jquery', 'sweetalert2', 'flatpickr', 'select2' ),
			GFME_VERSION,
			true
		);

		wp_localize_script(
			'gfme-admin-js',
			'gfme_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gfme_admin_nonce' ),
				'slug'     => GFME_SLUG
			)
		);
	}

	/**
	 * Render the administration settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'gf-media-exporter' ) );
		}

		$forms = $this->gf->get_forms();
		if ( is_wp_error( $forms ) ) {
			$forms = array();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_id   = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$selected_form = null;
		$file_fields   = array();
		$entry_count   = 0;
		$min_date      = '';

		if ( $selected_id ) {
			$selected_form = $this->gf->get_form( $selected_id );
			if ( $selected_form ) {
				$file_fields = $this->gf->get_file_fields( $selected_form );
				$entry_count = $this->gf->count_entries( $selected_id );
				$min_date    = $this->gf->get_oldest_entry_date( $selected_id );
			}
		}

		// Load template file.
		include GFME_PATH . 'templates/admin-page.php';
	}

	/**
	 * AJAX handler to get form details fragment.
	 */
	public function ajax_get_form_details() {
		check_ajax_referer( 'gfme_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gf-media-exporter' ) ) );
		}

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Form ID.', 'gf-media-exporter' ) ) );
		}

		$selected_form = $this->gf->get_form( $form_id );
		if ( ! $selected_form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'gf-media-exporter' ) ) );
		}

		$file_fields = $this->gf->get_file_fields( $selected_form );
		$entry_count = $this->gf->count_entries( $form_id );
		$min_date    = $this->gf->get_oldest_entry_date( $form_id );
		$selected_id = $form_id;

		ob_start();
		include GFME_PATH . 'templates/form-details.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX handler to seed entries.
	 */
	public function ajax_seed_entries() {
		check_ajax_referer( 'gfme_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gf-media-exporter' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$count   = isset( $_POST['count'] ) ? max( 1, min( 20, absint( $_POST['count'] ) ) ) : 3;

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Form ID.', 'gf-media-exporter' ) ) );
		}

		$result = $this->gf->seed_sample_entries( $form_id, $count );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$entry_count = $this->gf->count_entries( $form_id );
		$min_date    = $this->gf->get_oldest_entry_date( $form_id );

		wp_send_json_success( array(
			'message'     => sprintf(
				/* translators: %d: number of seeded entries */
				_n( 'Created %d sample entry with files.', 'Created %d sample entries with files.', $result, 'gf-media-exporter' ),
				$result
			),
			'entry_count' => $entry_count,
			'min_date'    => $min_date
		) );
	}

	/**
	 * AJAX handler to remove entries.
	 */
	public function ajax_remove_entries() {
		check_ajax_referer( 'gfme_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gf-media-exporter' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Form ID.', 'gf-media-exporter' ) ) );
		}

		if ( empty( $_POST['gfme_confirm'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please check the confirmation box before deleting entries.', 'gf-media-exporter' ) ) );
		}

		$date_start = Helpers::sanitize_date( sanitize_text_field( wp_unslash( $_POST['date_start'] ?? '' ) ) );
		$date_end   = Helpers::sanitize_date( sanitize_text_field( wp_unslash( $_POST['date_end'] ?? '' ) ) );

		if ( $date_start && $date_end && $date_start > $date_end ) {
			wp_send_json_error( array( 'message' => __( 'The start date must be before the end date.', 'gf-media-exporter' ) ) );
		}

		$export = Plugin::get_instance()->get_export();
		if ( ! $export ) {
			wp_send_json_error( array( 'message' => __( 'Exporter not loaded.', 'gf-media-exporter' ) ) );
		}

		$result = $export->run_remove( $form_id, array(
			'date_start' => $date_start,
			'date_end'   => $date_end,
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$entry_count = $this->gf->count_entries( $form_id );
		$min_date    = $this->gf->get_oldest_entry_date( $form_id );

		$message = sprintf(
			/* translators: 1: entries deleted count, 2: files deleted count */
			__( 'Removed %1$d entries and deleted %2$d files from the server.', 'gf-media-exporter' ),
			$result['entries_deleted'],
			$result['files_deleted']
		);

		if ( $result['files_failed'] > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: files failed count */
				_n( '%d file could not be deleted (check permissions).', '%d files could not be deleted (check permissions).', $result['files_failed'], 'gf-media-exporter' ),
				$result['files_failed']
			);
		}

		wp_send_json_success( array(
			'message'     => $message,
			'entry_count' => $entry_count,
			'min_date'    => $min_date
		) );
	}
}
