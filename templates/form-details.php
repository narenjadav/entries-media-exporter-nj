<?php
/**
 * Form details cards template.
 *
 * @package GFME
 *
 * @var array              $selected_form  Details of the currently selected form.
 * @var array              $file_fields    File fields for the selected form.
 * @var int                $entry_count    Count of entries for the selected form.
 * @var string             $min_date       Oldest entry date.
 * @var int                $selected_id    ID of the form.
 * @var \GFME\GravityForms $this->gf       GravityForms class instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="gfme-dashboard-layout">
	<!-- Left Column: Details & Export Settings -->
	<div class="gfme-column-main">
		<div class="gfme-card">
			<h2><span class="dashicons dashicons-text-page"></span> <?php esc_html_e( 'Form Summary', 'gf-media-exporter' ); ?></h2>
			<div class="gfme-summary-grid">
				<div class="gfme-summary-item">
					<span class="gfme-summary-label"><?php esc_html_e( 'Form Name', 'gf-media-exporter' ); ?></span>
					<span class="gfme-summary-value"><?php echo esc_html( $selected_form['title'] ); ?></span>
				</div>
				<div class="gfme-summary-item">
					<span class="gfme-summary-label"><?php esc_html_e( 'Form ID', 'gf-media-exporter' ); ?></span>
					<span class="gfme-summary-value"><?php echo absint( $selected_form['id'] ); ?></span>
				</div>
				<div class="gfme-summary-item">
					<span class="gfme-summary-label"><?php esc_html_e( 'Total Submissions', 'gf-media-exporter' ); ?></span>
					<span class="gfme-summary-value gfme-badge gfme-entry-count-badge"><?php echo absint( $entry_count ); ?></span>
				</div>
				<div class="gfme-summary-item">
					<span class="gfme-summary-label"><?php esc_html_e( 'File Upload Fields', 'gf-media-exporter' ); ?></span>
					<span class="gfme-summary-value">
						<?php
						if ( empty( $file_fields ) ) {
							esc_html_e( 'None', 'gf-media-exporter' );
						} else {
							$names = array();
							foreach ( $file_fields as $gfme_f ) {
								$names[] = sprintf( '<code class="gfme-inline-code">%s (ID %d)</code>', esc_html( $gfme_f->label ), absint( $gfme_f->id ) );
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

		<div class="gfme-card">
			<h2><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Configure Export Options', 'gf-media-exporter' ); ?></h2>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="gfme-export-form" target="gfme_download_iframe">
				<input type="hidden" name="page" value="<?php echo esc_attr( GFME_SLUG ); ?>" />
				<input type="hidden" name="gfme_action" value="export" />
				<input type="hidden" name="form_id" value="<?php echo absint( $selected_id ); ?>" />
				<?php wp_nonce_field( 'gfme_export_' . $selected_id ); ?>

				<div class="gfme-form-group">
					<label class="gfme-label" for="date_start">
						<?php esc_html_e( 'Date Range (Optional)', 'gf-media-exporter' ); ?>
						<span class="gfme-tooltip-icon" title="<?php esc_attr_e( 'Only export entries submitted within this timeframe. Leave empty to export all entries.', 'gf-media-exporter' ); ?>">?</span>
					</label>
					<div class="gfme-date-range-row">
						<div class="gfme-date-input-wrap">
							<span class="gfme-date-label"><?php esc_html_e( 'From', 'gf-media-exporter' ); ?></span>
							<input type="text" id="date_start" name="date_start" class="regular-text" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
						</div>
						<div class="gfme-date-input-wrap">
							<span class="gfme-date-label"><?php esc_html_e( 'To', 'gf-media-exporter' ); ?></span>
							<input type="text" id="date_end" name="date_end" class="regular-text" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
						</div>
					</div>
				</div>

				<div class="gfme-form-group">
					<label class="gfme-checkbox-label">
						<input type="checkbox" name="files_only" value="1" <?php disabled( ! empty( $file_fields ), false ); ?> />
						<span><strong><?php esc_html_e( 'Files Only', 'gf-media-exporter' ); ?></strong> – <?php esc_html_e( 'Only package uploaded files and exclude the entries.csv file.', 'gf-media-exporter' ); ?></span>
					</label>
					<?php if ( empty( $file_fields ) ) : ?>
						<p class="description gfme-warning-text"><?php esc_html_e( 'Note: This form has no file upload fields, so files-only mode is unavailable.', 'gf-media-exporter' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="gfme-form-actions">
					<button type="submit" class="gfme-btn gfme-btn-primary gfme-submit-btn">
						<span class="dashicons dashicons-archive"></span> <?php esc_html_e( 'Generate & Download ZIP', 'gf-media-exporter' ); ?>
					</button>
				</div>
			</form>
			<!-- Hidden iframe to handle streaming file downloads without changing screen URL -->
			<iframe name="gfme_download_iframe" id="gfme_download_iframe" style="display:none;"></iframe>
		</div>
	</div>

	<!-- Right Column: Danger Zone & Dev Seeder -->
	<div class="gfme-column-side">
		<div class="gfme-card gfme-danger-card">
			<h2><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Danger Zone', 'gf-media-exporter' ); ?></h2>
			<p class="gfme-danger-desc"><?php esc_html_e( 'Delete entries and permanently remove their uploaded files from the web server. This operation is destructive and cannot be undone.', 'gf-media-exporter' ); ?></p>

			<form method="post" action="" class="gfme-remove-form">
				<input type="hidden" name="form_id" value="<?php echo absint( $selected_id ); ?>" />
				<?php wp_nonce_field( 'gfme_remove_' . $selected_id ); ?>

				<div class="gfme-form-group">
					<label class="gfme-label" for="rm_date_start"><?php esc_html_e( 'Deletion Date Filter (Optional)', 'gf-media-exporter' ); ?></label>
					<div class="gfme-date-input-wrap">
						<span class="gfme-date-label"><?php esc_html_e( 'From', 'gf-media-exporter' ); ?></span>
						<input type="text" id="rm_date_start" name="date_start" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
					</div>
					<div class="gfme-date-input-wrap" style="margin-top:8px;">
						<span class="gfme-date-label"><?php esc_html_e( 'To', 'gf-media-exporter' ); ?></span>
						<input type="text" id="rm_date_end" name="date_end" placeholder="YYYY-MM-DD" data-min-date="<?php echo esc_attr( $min_date ); ?>" />
					</div>
					<p class="description"><?php esc_html_e( 'Only entries created within this date range will be wiped. Leave empty to delete all entries for this form.', 'gf-media-exporter' ); ?></p>
				</div>

				<div class="gfme-form-group gfme-confirm-group">
					<label class="gfme-checkbox-label">
						<input type="checkbox" id="gfme_confirm_box" name="gfme_confirm" value="1" />
						<span class="gfme-danger-text"><strong><?php esc_html_e( 'I understand this will permanently delete matching database records and all associated uploaded files.', 'gf-media-exporter' ); ?></strong></span>
					</label>
				</div>

				<div class="gfme-form-actions">
					<button type="submit" class="gfme-btn gfme-btn-danger gfme-submit-btn">
						<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Permanently Delete Data', 'gf-media-exporter' ); ?>
					</button>
				</div>
			</form>
		</div>

		<?php if ( $this->gf->seeding_enabled() ) : ?>
			<div class="gfme-card gfme-dev-card">
				<h2><span class="dashicons dashicons-hammer"></span> <?php esc_html_e( 'Developer Tools', 'gf-media-exporter' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Seed real Gravity Form entries and dummy files in the uploads folder. Visible only when WP_DEBUG is enabled.', 'gf-media-exporter' ); ?></p>

				<form method="get" action="" class="gfme-seed-form">
					<input type="hidden" name="form_id" value="<?php echo absint( $selected_id ); ?>" />
					<?php wp_nonce_field( 'gfme_seed_' . $selected_id ); ?>

					<div class="gfme-seed-controls">
						<label for="seed_count" style="margin-right:8px;font-weight:600;"><?php esc_html_e( 'Entries to generate:', 'gf-media-exporter' ); ?></label>
						<input type="number" id="seed_count" name="count" value="3" min="1" max="20" style="height:40px;width:60px;border-radius:6px;border:1px solid #8c8f94;text-align:center;margin-right:12px;box-sizing:border-box;" />
						<button type="submit" class="gfme-btn gfme-btn-secondary"><?php esc_html_e( 'Seed Data', 'gf-media-exporter' ); ?></button>
					</div>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>
