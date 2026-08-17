<?php
/**
 * Report sidebar — Debug tab
 *
 * @package Ninjalytics
 *
 * @var array $reportSettings Current report settings (from renderDebugSection).
 */

namespace NinjalyticsFree;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ninjalytics-debug">
	<header class="ninjalytics-sidebar-header">
		<div class="ninjalytics-sidebar-header-text">
			<h2 class="ninjalytics-sidebar-title"><?php esc_html_e( 'Debug', 'product-sales-report-for-woocommerce' ); ?></h2>
			<p class="ninjalytics-sidebar-subtitle"><?php esc_html_e( 'Enable debug mode and inspect report SQL after each run.', 'product-sales-report-for-woocommerce' ); ?></p>
		</div>
	</header>

	<div class="ninjalytics-sidebar-body ninjalytics-debug-body">
		<div class="berrypress-field">
			<input type="checkbox" id="ninjalytics-enable-debug" name="hm_psr_debug"
				   value="1"<?php checked( ! empty( $reportSettings['hm_psr_debug'] ) ); ?> />
			<label for="ninjalytics-enable-debug">
				<?php esc_html_e( 'Enable debug mode', 'product-sales-report-for-woocommerce' ); ?>
				<?php AdminPage::docsLink( 'report-configuration/data-and-display', 'debug' ); ?>
			</label>
		</div>

		<div class="ninjalytics-debug-sql-box berrypress-hidden"
		     id="ninjalytics-debug-sql-box"
		     aria-hidden="true"
		     data-query-label-template="<?php echo esc_attr__( 'Query %d', 'product-sales-report-for-woocommerce' ); ?>">
			<div class="ninjalytics-debug-sql-heading">
				<span class="ninjalytics-debug-sql-heading-text"><?php esc_html_e( 'MySQL queries', 'product-sales-report-for-woocommerce' ); ?></span>
				<span class="ninjalytics-debug-sql-count" id="ninjalytics-debug-sql-count" hidden></span>
			</div>
			<div id="ninjalytics-debug-sql-list" class="ninjalytics-debug-sql-list" role="log" aria-live="polite"></div>
		</div>
	</div>
</div>
