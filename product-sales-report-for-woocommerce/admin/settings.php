<?php
namespace NinjalyticsFree;

defined( 'ABSPATH' ) || exit;

trait SettingsPageTrait {

	protected function renderSettingsPage() {
		$this->renderSettingsGeneralTab();
	}

	protected function renderSettingsGeneralTab() {
		$settings_saved = false;
		if ( isset( $_POST['ninjalytics_save_settings'] ) ) {
			if ( ! isset( $_POST['ninjalytics_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ninjalytics_settings_nonce'] ) ), 'ninjalytics_settings' ) ) {
				wp_die( esc_html__( 'Security check failed', 'product-sales-report-for-woocommerce' ) );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'product-sales-report-for-woocommerce' ) );
			}
			$hide_inactive_templates = isset( $_POST['ninjalytics_hide_inactive_templates'] ) && $_POST['ninjalytics_hide_inactive_templates'] === '1';
			$link_order_id_in_preview = isset( $_POST['ninjalytics_link_order_id_in_preview'] ) && $_POST['ninjalytics_link_order_id_in_preview'] === '1';
			$link_product_id_in_preview = isset( $_POST['ninjalytics_link_product_id_in_preview'] ) && $_POST['ninjalytics_link_product_id_in_preview'] === '1';
			$link_product_name_in_preview = isset( $_POST['ninjalytics_link_product_name_in_preview'] ) && $_POST['ninjalytics_link_product_name_in_preview'] === '1';
			$style_order_status_in_preview = isset( $_POST['ninjalytics_style_order_status_in_preview'] ) && $_POST['ninjalytics_style_order_status_in_preview'] === '1';
			$preview_negative_numbers_red = isset( $_POST['ninjalytics_preview_negative_numbers_red'] ) && $_POST['ninjalytics_preview_negative_numbers_red'] === '1';
			update_option( 'ninjalytics_hide_inactive_templates', (int) $hide_inactive_templates, false );
			update_option( 'ninjalytics_link_order_id_in_preview', (int) $link_order_id_in_preview, false );
			update_option( 'ninjalytics_link_product_id_in_preview', (int) $link_product_id_in_preview, false );
			update_option( 'ninjalytics_link_product_name_in_preview', (int) $link_product_name_in_preview, false );
			update_option( 'ninjalytics_style_order_status_in_preview', (int) $style_order_status_in_preview, false );
			update_option( 'ninjalytics_preview_negative_numbers_red', (int) $preview_negative_numbers_red, false );

			$settings_saved = true;
		}
		$hide_inactive_templates = get_option( 'ninjalytics_hide_inactive_templates', false );
		$link_order_id_in_preview = get_option( 'ninjalytics_link_order_id_in_preview', true );
		$link_product_id_in_preview = get_option( 'ninjalytics_link_product_id_in_preview', true );
		$link_product_name_in_preview = get_option( 'ninjalytics_link_product_name_in_preview', true );
		$style_order_status_in_preview = get_option( 'ninjalytics_style_order_status_in_preview', true );
		$preview_negative_numbers_red = get_option( 'ninjalytics_preview_negative_numbers_red', true );
		?>
		<div id="ninjalytics-settings-page">
			<h1 class="berrypress-mb-2 ninjalytics-settings-header"><?php esc_html_e( 'Ninjalytics Settings', 'product-sales-report-for-woocommerce' ); ?></h1>
			<?php if ( $settings_saved ) : ?>
				<div class="berrypress-notice berrypress-notice-success notice is-dismissible">
					<i class="berrypress-icon-check_circle"></i> <?php esc_html_e( 'Settings saved successfully.', 'product-sales-report-for-woocommerce' ); ?>
				</div>
			<?php endif; ?>
			<form action="" method="post">
				<?php wp_nonce_field( 'ninjalytics_settings', 'ninjalytics_settings_nonce' ); ?>
				<h2 class="berrypress-fs-16 berrypress-mb-4"><?php esc_html_e( 'General', 'product-sales-report-for-woocommerce' ); ?></h2>
				<div class="berrypress-field">
					<input type="checkbox" name="ninjalytics_hide_inactive_templates" id="ninjalytics_hide_inactive_templates" value="1" <?php checked( $hide_inactive_templates, true ); ?> />
					<div>
						<label for="ninjalytics_hide_inactive_templates"><?php esc_html_e( 'Hide templates for inactive plugins', 'product-sales-report-for-woocommerce' ); ?></label>
						<p class="description"><?php esc_html_e( 'When enabled, templates for inactive e-commerce plugins will be hidden from the template library.', 'product-sales-report-for-woocommerce' ); ?></p>
					</div>
				</div>
				<h3 class="berrypress-fs-16 berrypress-mt-4"><?php esc_html_e( 'Report Preview', 'product-sales-report-for-woocommerce' ); ?></h3>
				<h4 class="berrypress-fs-14 berrypress-mt-3 berrypress-mb-2"><?php esc_html_e( 'Quick Links', 'product-sales-report-for-woocommerce' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Enable links for fields in report preview. Product links open the WordPress admin edit screen for the product or variation (not the storefront).', 'product-sales-report-for-woocommerce' ); ?></p>

				<div class="berrypress-field berrypress-mt-2">
					<input
						type="checkbox"
						name="ninjalytics_link_order_id_in_preview"
						id="ninjalytics_link_order_id_in_preview"
						value="1"
						<?php checked( $link_order_id_in_preview, true ); ?>
					/>
					<div>
						<label for="ninjalytics_link_order_id_in_preview">
							<?php esc_html_e( 'Order ID', 'product-sales-report-for-woocommerce' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Also links Parent order when that field is present and the value is non-zero (e.g. refund rows).', 'product-sales-report-for-woocommerce' ); ?>
						</p>
					</div>
				</div>

				<div class="berrypress-field">
					<input
						type="checkbox"
						name="ninjalytics_link_product_id_in_preview"
						id="ninjalytics_link_product_id_in_preview"
						value="1"
						<?php checked( $link_product_id_in_preview, true ); ?>
					/>
					<div>
						<label for="ninjalytics_link_product_id_in_preview">
							<?php esc_html_e( 'Product ID', 'product-sales-report-for-woocommerce' ); ?>
						</label>
					</div>
				</div>

				<div class="berrypress-field">
					<input
						type="checkbox"
						name="ninjalytics_link_product_name_in_preview"
						id="ninjalytics_link_product_name_in_preview"
						value="1"
						<?php checked( $link_product_name_in_preview, true ); ?>
					/>
					<div>
						<label for="ninjalytics_link_product_name_in_preview">
							<?php esc_html_e( 'Product name and order line item name', 'product-sales-report-for-woocommerce' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Opens the catalog product/variation edit screen when Product ID or Variation ID is available on the same row (add those columns when you rely on links).', 'product-sales-report-for-woocommerce' ); ?>
						</p>
					</div>
				</div>

				<h4 class="berrypress-fs-14 berrypress-mt-3 berrypress-mb-2"><?php esc_html_e( 'Visual Styling', 'product-sales-report-for-woocommerce' ); ?></h4>
				<p class="description berrypress-mb-2"><?php esc_html_e( 'Enable visual styling in report preview:', 'product-sales-report-for-woocommerce' ); ?></p>
				<div class="berrypress-field">
					<input
						type="checkbox"
						name="ninjalytics_style_order_status_in_preview"
						id="ninjalytics_style_order_status_in_preview"
						value="1"
						<?php checked( $style_order_status_in_preview, true ); ?>
					/>
					<div>
						<label for="ninjalytics_style_order_status_in_preview">
							<?php esc_html_e( 'Order Status badges', 'product-sales-report-for-woocommerce' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'WooCommerce-style status colors and badge formatting.', 'product-sales-report-for-woocommerce' ); ?></p>
					</div>
				</div>
				<div class="berrypress-field">
					<input
						type="checkbox"
						name="ninjalytics_preview_negative_numbers_red"
						id="ninjalytics_preview_negative_numbers_red"
						value="1"
						<?php checked( $preview_negative_numbers_red, true ); ?>
					/>
					<div>
						<label for="ninjalytics_preview_negative_numbers_red">
							<?php esc_html_e( 'Show negative numeric values in red', 'product-sales-report-for-woocommerce' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'In the interactive table preview, cells in amount/quantity columns (DataTables numeric type) show below zero in red. Does not change exported CSV, XLSX, or HTML downloads.', 'product-sales-report-for-woocommerce' ); ?></p>
					</div>
				</div>
                <div class="berrypress-mt-4">
                    <button type="submit" name="ninjalytics_save_settings" class="berrypress-btn berrypress-btn-primary" value="1">
                        <?php esc_html_e( 'Save Settings', 'product-sales-report-for-woocommerce' ); ?>
                    </button>
                </div>
			</form>
		</div>
		<?php
	}
}
