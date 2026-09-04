<?php
namespace NinjalyticsFree;

/**
 * "Export Report Settings" modal markup for the report editor page.
 *
 * Expects (set by the including scope before require):
 * - $exportPresetId (int) ID of the report currently being edited.
 * - $exportPresetName (string) Display name of that report.
 * - $exportReporterId (string) Reporter/integration key for the current report.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$exportNonce = wp_create_nonce( 'hm-psr-run' );
$exportUrl   = add_query_arg(
	[
		'ninjalytics_action_free' => 'export',
		'preset'                  => (int) $exportPresetId,
		'_reporter'               => $exportReporterId,
		'hm-psr-nonce'            => $exportNonce,
	],
	admin_url( 'admin.php?page=ninjalytics-free' )
);
?>
<div class="berrypress-modal ninjalytics-modal-export" id="ninjalytics-export-modal" role="dialog" aria-modal="true" aria-labelledby="ninjalytics-export-modal-title">
	<button type="button" class="berrypress-modal-close berrypress-modal-close-top" aria-label="<?php esc_attr_e( 'Close Dialog', 'product-sales-report-for-woocommerce' ); ?>"><i class="berrypress-icon-close" aria-hidden="true"></i></button>
	<div class="berrypress-card berrypress-card-100">
		<div class="berrypress-card-content">
			<h2 id="ninjalytics-export-modal-title"><?php esc_html_e( 'Export Report Settings', 'product-sales-report-for-woocommerce' ); ?></h2>
			<p class="berrypress-text-secondary">
				<?php
				echo esc_html( sprintf(
					/* translators: %s: report name */
					__( 'Download a settings file for "%s" that you can import into this or another report later.', 'product-sales-report-for-woocommerce' ),
					$exportPresetName
				) );
				?>
			</p>

			<div class="ninjalytics-modal-footer">
				<button type="button" class="berrypress-btn berrypress-btn-secondary berrypress-modal-close"><?php esc_html_e( 'Cancel', 'product-sales-report-for-woocommerce' ); ?></button>
				<a href="<?php echo esc_url( $exportUrl ); ?>" target="_blank" class="berrypress-btn berrypress-btn-primary">
					<i class="berrypress-icon-download" aria-hidden="true"></i>
					<?php esc_html_e( 'Download', 'product-sales-report-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
