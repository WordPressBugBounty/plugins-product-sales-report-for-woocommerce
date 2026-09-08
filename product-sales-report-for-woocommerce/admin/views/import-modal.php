<?php
namespace NinjalyticsFree;

/**
 * Shared "Import Report Settings" modal markup.
 *
 * Expects (optional, set by the including scope before require):
 * - $importOverwritePresetId (int) ID of the report currently being edited, if this
 *   modal is opened from the report editor toolbar. When set, importing overwrites
 *   that report instead of creating a new one.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$importOverwritePresetId = isset( $importOverwritePresetId ) ? (int) $importOverwritePresetId : 0;
$importNonce             = wp_create_nonce( 'hm-psr-run' );
$importActionUrl         = add_query_arg(
	[ 'ninjalytics_action_free' => 'import', 'hm-psr-nonce' => $importNonce ],
	admin_url( 'admin.php?page=ninjalytics-free' )
);
?>
<div class="berrypress-modal ninjalytics-modal-import" id="ninjalytics-import-modal" role="dialog" aria-modal="true" aria-labelledby="ninjalytics-import-modal-title">
	<button type="button" class="berrypress-modal-close berrypress-modal-close-top" aria-label="<?php esc_attr_e( 'Close Dialog', 'product-sales-report-for-woocommerce' ); ?>"><i class="berrypress-icon-close" aria-hidden="true"></i></button>
	<div class="berrypress-card berrypress-card-100">
		<div class="berrypress-card-content">
			<h2 id="ninjalytics-import-modal-title"><?php esc_html_e( 'Import Report Settings', 'product-sales-report-for-woocommerce' ); ?></h2>
			<p class="berrypress-text-secondary"><?php esc_html_e( 'Choose a settings file exported from Ninjalytics (.json).', 'product-sales-report-for-woocommerce' ); ?></p>

			<form method="post" action="<?php echo esc_url( $importActionUrl ); ?>" enctype="multipart/form-data">
				<div class="berrypress-field">
					<label class="berrypress-visually-hidden" for="ninjalytics-import-file"><?php esc_html_e( 'Settings file', 'product-sales-report-for-woocommerce' ); ?></label>
					<input type="file" name="import_file" id="ninjalytics-import-file" accept=".json" class="berrypress-file-upload-input"
						   <?php echo $importOverwritePresetId ? 'aria-describedby="ninjalytics-import-overwrite-note"' : ''; ?>
						   required>
				</div>

                <p id="ninjalytics-import-overwrite-note" class="berrypress-text-secondary ninjalytics-import-overwrite-note<?php echo $importOverwritePresetId ? '' : ' berrypress-hidden'; ?>">
                    <?php esc_html_e( "This will overwrite the current report's settings.", 'product-sales-report-for-woocommerce' ); ?>
                </p>

				<input type="hidden" name="preset" class="ninjalytics-import-preset-input" value="<?php echo esc_attr( $importOverwritePresetId ); ?>">

				<div class="ninjalytics-modal-footer">
					<button type="button" class="berrypress-btn berrypress-btn-secondary berrypress-modal-close"><?php esc_html_e( 'Cancel', 'product-sales-report-for-woocommerce' ); ?></button>
					<button type="submit" class="berrypress-btn berrypress-btn-primary"><?php esc_html_e( 'Import', 'product-sales-report-for-woocommerce' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
