<?php
/**
 * Form details cards template.
 *
 * @package EMENJ
 *
 * @var array              $selected_form  Details of the currently selected form.
 * @var array              $file_fields    File fields for the selected form.
 * @var int                $entry_count    Count of entries for the selected form.
 * @var string             $min_date       Oldest entry date.
 * @var int                $selected_id    ID of the form.
 * @var \EMENJ\GravityForms $this->gf       GravityForms class instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="emenj-dashboard-layout">
	<!-- Left Column: Details & Export Settings -->
	<div class="emenj-column-main">
		<div class="emenj-card">
			<h2><span class="dashicons dashicons-text-page"></span> <?php esc_html_e( 'Form Summary', 'entries-media-exporter-nj' ); ?></h2>
			<div class="emenj-summary-grid">
				<div class="emenj-summary-item">
					<span class="emenj-summary-label"><?php esc_html_e( 'Form Name', 'entries-media-exporter-nj' ); ?></span>
					<span class="emenj-summary-value"><?php echo esc_html( $selected_form['title'] ); ?></span>
				</div>
				<div class="emenj-summary-item">
					<span class="emenj-summary-label"><?php esc_html_e( 'Form ID', 'entries-media-exporter-nj' ); ?></span>
					<span class="emenj-summary-value"><?php echo absint( $selected_form['id'] ); ?></span>
				</div>
				<div class="emenj-summary-item">
					<span class="emenj-summary-label"><?php esc_html_e( 'Total Submissions', 'entries-media-exporter-nj' ); ?></span>
					<span class="emenj-summary-value emenj-badge emenj-entry-count-badge"><?php echo absint( $entry_count ); ?></span>
				</div>
				<div class="emenj-summary-item">
					<span class="emenj-summary-label"><?php esc_html_e( 'File Upload Fields', 'entries-media-exporter-nj' ); ?></span>
					<span class="emenj-summary-value">
						<?php
						if ( empty( $file_fields ) ) {
							esc_html_e( 'None', 'entries-media-exporter-nj' );
						} else {
							$names = array();
							foreach ( $file_fields as $emenj_f ) {
								$names[] = sprintf( '<code class="emenj-inline-code">%s (ID %d)</code>', esc_html( $emenj_f->label ), absint( $emenj_f->id ) );
							}
							echo wp_kses(
								implode( ', ', $names ),
								array(
									'code' => array(
										'class' => array(),
									),
								)
							);
						}
						?>
					</span>
				</div>
			</div>
		</div>

		<div class="emenj-card">
			<h2><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Configure Export Options', 'entries-media-exporter-nj' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="emenj-export-form" target="emenj_download_iframe">
				<input type="hidden" name="page" value="<?php echo esc_attr( EME_NJ_SLUG ); ?>" />
				<input type="hidden" name="emenj_action" value="export" />
				<input type="hidden" name="form_id" value="<?php echo absint( $selected_id ); ?>" />
				<?php wp_nonce_field( 'emenj_export_' . $selected_id ); ?>

				<div class="emenj-form-group">
					<label class="emenj-label" for="date_start">
						<?php esc_html_e( 'Date Range (Optional)', 'entries-media-exporter-nj' ); ?>
						<span class="emenj-tooltip-icon" title="<?php esc_attr_e( 'Only export entries submitted within this timeframe. Leave empty to export all entries.', 'entries-media-exporter-nj' ); ?>">?</span>
					</label>
					<div class="emenj-date-range-row">
						<div class="emenj-date-input-wrap">
							<span class="emenj-date-label"><?php esc_html_e( 'From', 'entries-media-exporter-nj' ); ?></span>
							<input type="text" id="date_start" name="date_start" class="regular-text" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
						</div>
						<div class="emenj-date-input-wrap">
							<span class="emenj-date-label"><?php esc_html_e( 'To', 'entries-media-exporter-nj' ); ?></span>
							<input type="text" id="date_end" name="date_end" class="regular-text" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
						</div>
					</div>
				</div>

				<div class="emenj-form-group">
					<label class="emenj-checkbox-label">
						<input type="checkbox" name="files_only" value="1" <?php disabled( ! empty( $file_fields ), false ); ?> />
						<span><strong><?php esc_html_e( 'Files Only', 'entries-media-exporter-nj' ); ?></strong> – <?php esc_html_e( 'Only package uploaded files and exclude the entries.csv file.', 'entries-media-exporter-nj' ); ?></span>
					</label>
					<?php if ( empty( $file_fields ) ) : ?>
						<p class="description emenj-warning-text"><?php esc_html_e( 'Note: This form has no file upload fields, so files-only mode is unavailable.', 'entries-media-exporter-nj' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="emenj-form-actions">
					<button type="submit" class="emenj-btn emenj-btn-primary emenj-submit-btn">
						<span class="dashicons dashicons-archive"></span> <?php esc_html_e( 'Generate & Download ZIP', 'entries-media-exporter-nj' ); ?>
					</button>
				</div>
			</form>
			<!-- Hidden iframe to handle streaming file downloads without changing screen URL -->
			<iframe name="emenj_download_iframe" id="emenj_download_iframe" style="display:none;"></iframe>
		</div>
	</div>

	<!-- Right Column: Danger Zone & Dev Seeder -->
	<div class="emenj-column-side">
		<div class="emenj-card emenj-danger-card">
			<h2><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Danger Zone', 'entries-media-exporter-nj' ); ?></h2>
			<p class="emenj-danger-desc"><?php esc_html_e( 'Delete entries and permanently remove their uploaded files from the web server. This operation is destructive and cannot be undone.', 'entries-media-exporter-nj' ); ?></p>

			<form method="post" action="" class="emenj-remove-form">
				<input type="hidden" name="form_id" value="<?php echo absint( $selected_id ); ?>" />
				<?php wp_nonce_field( 'emenj_remove_' . $selected_id ); ?>

				<div class="emenj-form-group">
					<label class="emenj-label" for="rm_date_start"><?php esc_html_e( 'Deletion Date Filter (Optional)', 'entries-media-exporter-nj' ); ?></label>
					<div class="emenj-date-input-wrap">
						<span class="emenj-date-label"><?php esc_html_e( 'From', 'entries-media-exporter-nj' ); ?></span>
						<input type="text" id="rm_date_start" name="date_start" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
					</div>
					<div class="emenj-date-input-wrap" style="margin-top:8px;">
						<span class="emenj-date-label"><?php esc_html_e( 'To', 'entries-media-exporter-nj' ); ?></span>
						<input type="text" id="rm_date_end" name="date_end" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
					</div>
					<p class="description"><?php esc_html_e( 'Only entries created within this date range will be wiped. Leave empty to delete all entries for this form.', 'entries-media-exporter-nj' ); ?></p>
				</div>

				<div class="emenj-form-group emenj-confirm-group">
					<label class="emenj-checkbox-label">
						<input type="checkbox" id="emenj_confirm_box" name="emenj_confirm" value="1" />
						<span class="emenj-danger-text"><strong><?php esc_html_e( 'I understand this will permanently delete matching database records and all associated uploaded files.', 'entries-media-exporter-nj' ); ?></strong></span>
					</label>
				</div>

				<div class="emenj-form-actions">
					<button type="submit" class="emenj-btn emenj-btn-danger emenj-submit-btn">
						<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Permanently Delete Data', 'entries-media-exporter-nj' ); ?>
					</button>
				</div>
			</form>
		</div>

		<?php if ( $this->gf->seeding_enabled() ) : ?>
			<div class="emenj-card emenj-dev-card">
				<h2><span class="dashicons dashicons-hammer"></span> <?php esc_html_e( 'Developer Tools', 'entries-media-exporter-nj' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Seed real Gravity Form entries and dummy files in the uploads folder. Visible only when WP_DEBUG is enabled.', 'entries-media-exporter-nj' ); ?></p>

				<form method="get" action="" class="emenj-seed-form">
					<input type="hidden" name="form_id" value="<?php echo absint( $selected_id ); ?>" />
					<?php wp_nonce_field( 'emenj_seed_' . $selected_id ); ?>

					<div class="emenj-seed-controls">
						<label for="seed_count" style="margin-right:8px;font-weight:600;"><?php esc_html_e( 'Entries to generate:', 'entries-media-exporter-nj' ); ?></label>
						<input type="number" id="seed_count" name="count" value="3" min="1" max="20" style="height:40px;width:60px;border-radius:6px;border:1px solid #8c8f94;text-align:center;margin-right:12px;box-sizing:border-box;" />
						<button type="submit" class="emenj-btn emenj-btn-secondary"><?php esc_html_e( 'Seed Data', 'entries-media-exporter-nj' ); ?></button>
					</div>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>
