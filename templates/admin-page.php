<?php
/**
 * Admin page template.
 *
 * @package GFME
 *
 * @var array              $forms        List of available forms.
 * @var int                $selected_id  ID of the currently selected form.
 * @var \GFME\GravityForms $this->gf     GravityForms class instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap gfme-admin-wrap">
	<div class="gfme-header">
		<div class="gfme-header-icon">
			<span class="dashicons dashicons-archive"></span>
		</div>
		<div class="gfme-header-title">
			<h1><?php esc_html_e( 'GF Media Exporter', 'gf-media-exporter' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Export form entries to CSV alongside all uploaded files packaged in a single ZIP.', 'gf-media-exporter' ); ?></p>
		</div>
	</div>

	<?php
	if ( ! class_exists( 'ZipArchive' ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'The PHP ZipArchive extension is not available on this server. It is required to build ZIP files.', 'gf-media-exporter' ) . '</p></div>';
	}
	?>

	<div class="gfme-card gfme-form-selector-card">
		<form method="get" action="" id="gfme-selector-form">
			<label for="gfme_form_id"><strong><?php esc_html_e( 'Select Gravity Form:', 'gf-media-exporter' ); ?></strong></label>
			<select name="form_id" id="gfme_form_id" class="gfme-select-large">
				<option value="0"><?php esc_html_e( '— Select a form —', 'gf-media-exporter' ); ?></option>
				<?php foreach ( $forms as $gfme_form ) : ?>
					<?php
					$gfme_has_files = $this->gf->form_has_file_fields( $gfme_form );
					$gfme_label     = sprintf(
						'%1$s (ID: %2$d)%3$s',
						$gfme_form['title'],
						$gfme_form['id'],
						$gfme_has_files ? ' • ' . __( 'Uploads Enabled', 'gf-media-exporter' ) : ''
					);
					?>
					<option value="<?php echo absint( $gfme_form['id'] ); ?>" <?php selected( $selected_id, $gfme_form['id'] ); ?>>
						<?php echo esc_html( $gfme_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>
	</div>

	<?php if ( empty( $forms ) ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'No Gravity Forms found on this site.', 'gf-media-exporter' ); ?></p></div>
	<?php endif; ?>

	<!-- Dynamic dashboard content container loaded via AJAX -->
	<div id="gfme-dashboard-container"></div>

	<!-- Loading Modal Overlay -->
	<div id="gfme-loading-overlay" class="gfme-overlay">
		<div class="gfme-spinner-box">
			<div class="gfme-spinner"></div>
			<p id="gfme-loading-message"><?php esc_html_e( 'Processing your request... Please wait.', 'gf-media-exporter' ); ?></p>
		</div>
	</div>
</div>
