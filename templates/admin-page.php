<?php
/**
 * Admin page template.
 *
 * @package EMENJ
 *
 * @var array              $forms        List of available forms.
 * @var int                $selected_id  ID of the currently selected form.
 * @var \EMENJ\GravityForms $this->gf     GravityForms class instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap emenj-admin-wrap">
	<div class="emenj-header">
		<div class="emenj-header-icon">
			<span class="dashicons dashicons-archive"></span>
		</div>
		<div class="emenj-header-title">
			<h1><?php esc_html_e( 'Entries & Media Exporter by Naren Jadav', 'entries-media-exporter-nj' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Export form entries to CSV alongside all uploaded files packaged in a single ZIP.', 'entries-media-exporter-nj' ); ?></p>
		</div>
	</div>

	<?php
	if ( ! class_exists( 'ZipArchive' ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'The PHP ZipArchive extension is not available on this server. It is required to build ZIP files.', 'entries-media-exporter-nj' ) . '</p></div>';
	}
	?>

	<div class="emenj-card emenj-form-selector-card">
		<form method="get" action="" id="emenj-selector-form">
			<label for="emenj_form_id"><strong><?php esc_html_e( 'Select Gravity Form:', 'entries-media-exporter-nj' ); ?></strong></label>
			<select name="form_id" id="emenj_form_id" class="emenj-select-large">
				<option value="0"><?php esc_html_e( '— Select a form —', 'entries-media-exporter-nj' ); ?></option>
				<?php foreach ( $forms as $emenj_form ) : ?>
					<?php
					$emenj_has_files = $this->gf->form_has_file_fields( $emenj_form );
					$emenj_label     = sprintf(
						'%1$s (ID: %2$d)%3$s',
						$emenj_form['title'],
						$emenj_form['id'],
						$emenj_has_files ? ' • ' . __( 'Uploads Enabled', 'entries-media-exporter-nj' ) : ''
					);
					?>
					<option value="<?php echo absint( $emenj_form['id'] ); ?>" <?php selected( $selected_id, $emenj_form['id'] ); ?>>
						<?php echo esc_html( $emenj_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>
	</div>

	<?php if ( empty( $forms ) ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'No Gravity Forms found on this site.', 'entries-media-exporter-nj' ); ?></p></div>
	<?php endif; ?>

	<!-- Dynamic dashboard content container loaded via AJAX -->
	<div id="emenj-dashboard-container"></div>

	<!-- Loading Modal Overlay -->
	<div id="emenj-loading-overlay" class="emenj-overlay">
		<div class="emenj-spinner-box">
			<div class="emenj-spinner"></div>
			<p id="emenj-loading-message"><?php esc_html_e( 'Processing your request... Please wait.', 'entries-media-exporter-nj' ); ?></p>
		</div>
	</div>
</div>
