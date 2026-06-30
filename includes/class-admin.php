<?php
namespace EMENJ;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the WordPress administration screens.
 *
 * @package EMENJ
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

		add_filter( 'plugin_action_links_' . plugin_basename( EME_NJ_FILE ), array( $this, 'add_plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );

		// Register AJAX actions.
		add_action( 'wp_ajax_emenj_get_form_details', array( $this, 'ajax_get_form_details' ) );
		add_action( 'wp_ajax_emenj_seed_entries', array( $this, 'ajax_seed_entries' ) );
		add_action( 'wp_ajax_emenj_remove_entries', array( $this, 'ajax_remove_entries' ) );
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
			__( 'Entries & Media Exporter by Naren Jadav', 'entries-media-exporter-nj' ),
			__( 'Entries & Media Exporter', 'entries-media-exporter-nj' ),
			'manage_options',
			EME_NJ_SLUG,
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
		if ( strpos( $hook, EME_NJ_SLUG ) === false ) {
			return;
		}

		// Enqueue SweetAlert2 from local assets.
		wp_enqueue_style(
			'sweetalert2',
			EME_NJ_URL . 'assets/css/sweetalert2.min.css',
			array(),
			'11.10.0'
		);

		wp_enqueue_script(
			'sweetalert2',
			EME_NJ_URL . 'assets/js/sweetalert2.all.min.js',
			array(),
			'11.10.0',
			true
		);

		// Enqueue Flatpickr (Datepicker) from local assets.
		wp_enqueue_style(
			'flatpickr',
			EME_NJ_URL . 'assets/css/flatpickr.min.css',
			array(),
			'4.6.13'
		);
		wp_enqueue_script(
			'flatpickr',
			EME_NJ_URL . 'assets/js/flatpickr.min.js',
			array(),
			'4.6.13',
			true
		);

		// Enqueue Select2 (Searchable Dropdown) from local assets.
		wp_enqueue_style(
			'select2',
			EME_NJ_URL . 'assets/css/select2.min.css',
			array(),
			'4.1.0'
		);
		wp_enqueue_script(
			'select2',
			EME_NJ_URL . 'assets/js/select2.min.js',
			array( 'jquery' ),
			'4.1.0',
			true
		);

		wp_enqueue_style(
			'emenj-admin-css',
			EME_NJ_URL . 'assets/css/admin.css',
			array( 'sweetalert2', 'flatpickr', 'select2' ),
			EME_NJ_VERSION
		);

		wp_enqueue_script(
			'emenj-admin-js',
			EME_NJ_URL . 'assets/js/admin.js',
			array( 'jquery', 'sweetalert2', 'flatpickr', 'select2' ),
			EME_NJ_VERSION,
			true
		);

		wp_localize_script(
			'emenj-admin-js',
			'emenj_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'emenj_admin_nonce' ),
				'slug'     => EME_NJ_SLUG
			)
		);
	}

	/**
	 * Add custom action links on the plugins listing page.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'admin.php?page=' . EME_NJ_SLUG );
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url($settings_url),
			esc_html__( 'Settings', 'entries-media-exporter-nj' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add custom row meta links on the plugins listing page.
	 *
	 * @param array  $plugin_meta Existing row meta.
	 * @param string $plugin_file Plugin file path relative to plugins folder.
	 * @return array Modified row meta.
	 */
	public function add_plugin_row_meta( array $plugin_meta, string $plugin_file ): array {
		if ( plugin_basename( EME_NJ_FILE ) === $plugin_file ) {
			$repo_url = 'https://github.com/narenjadav/entries-media-exporter-nj';
			$plugin_meta[] = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $repo_url ),
				esc_html__( 'View Details', 'entries-media-exporter-nj' )
			);
		}
		return $plugin_meta;
	}

	/**
	 * Render the administration settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'entries-media-exporter-nj' ) );
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
		include EME_NJ_PATH . 'templates/admin-page.php';
	}

	/**
	 * AJAX handler to get form details fragment.
	 */
	public function ajax_get_form_details() {
		check_ajax_referer( 'emenj_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'entries-media-exporter-nj' ) ) );
		}

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Form ID.', 'entries-media-exporter-nj' ) ) );
		}

		$selected_form = $this->gf->get_form( $form_id );
		if ( ! $selected_form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'entries-media-exporter-nj' ) ) );
		}

		$file_fields = $this->gf->get_file_fields( $selected_form );
		$entry_count = $this->gf->count_entries( $form_id );
		$min_date    = $this->gf->get_oldest_entry_date( $form_id );
		$selected_id = $form_id;

		ob_start();
		include EME_NJ_PATH . 'templates/form-details.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX handler to seed entries.
	 */
	public function ajax_seed_entries() {
		check_ajax_referer( 'emenj_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'entries-media-exporter-nj' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$count   = isset( $_POST['count'] ) ? max( 1, min( 20, absint( $_POST['count'] ) ) ) : 3;

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Form ID.', 'entries-media-exporter-nj' ) ) );
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
				_n( 'Created %d sample entry with files.', 'Created %d sample entries with files.', $result, 'entries-media-exporter-nj' ),
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
		check_ajax_referer( 'emenj_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'entries-media-exporter-nj' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Form ID.', 'entries-media-exporter-nj' ) ) );
		}

		if ( empty( $_POST['emenj_confirm'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please check the confirmation box before deleting entries.', 'entries-media-exporter-nj' ) ) );
		}

		$date_start = Helpers::sanitize_date( sanitize_text_field( wp_unslash( $_POST['date_start'] ?? '' ) ) );
		$date_end   = Helpers::sanitize_date( sanitize_text_field( wp_unslash( $_POST['date_end'] ?? '' ) ) );

		if ( $date_start && $date_end && $date_start > $date_end ) {
			wp_send_json_error( array( 'message' => __( 'The start date must be before the end date.', 'entries-media-exporter-nj' ) ) );
		}

		$export = Plugin::get_instance()->get_export();
		if ( ! $export ) {
			wp_send_json_error( array( 'message' => __( 'Exporter not loaded.', 'entries-media-exporter-nj' ) ) );
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
			__( 'Removed %1$d entries and deleted %2$d files from the server.', 'entries-media-exporter-nj' ),
			$result['entries_deleted'],
			$result['files_deleted']
		);

		if ( $result['files_failed'] > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: files failed count */
				_n( '%d file could not be deleted (check permissions).', '%d files could not be deleted (check permissions).', $result['files_failed'], 'entries-media-exporter-nj' ),
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
