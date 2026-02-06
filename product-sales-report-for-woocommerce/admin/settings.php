<?php
namespace NinjalyticsFree;

defined( 'ABSPATH' ) || exit;

trait SettingsPageTrait {
	protected function renderSettingsPage() {
		// Handle form submission
		$settings_saved = false;
		if ( isset( $_POST['ninjalytics_save_settings'] ) ) {
			// Verify nonce
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- nonce verification
			if ( ! isset( $_POST['ninjalytics_settings_nonce'] ) || ! wp_verify_nonce( $_POST['ninjalytics_settings_nonce'], 'ninjalytics_settings' ) ) {
				wp_die( esc_html__( 'Security check failed', 'product-sales-report-for-woocommerce' ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'product-sales-report-for-woocommerce' ) );
			}

			// Save settings
			$hide_inactive_templates = isset( $_POST['ninjalytics_hide_inactive_templates'] ) && $_POST['ninjalytics_hide_inactive_templates'] === '1';
			update_option( 'ninjalytics_hide_inactive_templates', $hide_inactive_templates );

			$settings_saved = true;
		}

		// Get current settings
		$hide_inactive_templates = get_option( 'ninjalytics_hide_inactive_templates', false );
		?>
		<div id="ninjalytics-settings-page">
			<h1 class="berrypress-mb-4 ninjalytics-settings-header"><?php esc_html_e( 'Ninjalytics Settings', 'product-sales-report-for-woocommerce' ); ?></h1>
			
			<?php if ( $settings_saved ) : ?>
				<div class="berrypress-notice berrypress-notice-success notice is-dismissible">
                    <i class="berrypress-icon-check_circle"></i> <?php esc_html_e( 'Settings saved successfully.', 'product-sales-report-for-woocommerce' ); ?>
				</div>
			<?php endif; ?>
			
			<form action="" method="post">
				<?php wp_nonce_field( 'ninjalytics_settings', 'ninjalytics_settings_nonce' ); ?>
				
				<h3 class="berrypress-fs-16"><?php esc_html_e( 'Template Library', 'product-sales-report-for-woocommerce' ); ?></h3>
				
				<div class="berrypress-field">
					<input 
						type="checkbox" 
						name="ninjalytics_hide_inactive_templates" 
						id="ninjalytics_hide_inactive_templates" 
						value="1"
						<?php checked( $hide_inactive_templates, true ); ?>
					/>
					<div>
						<label for="ninjalytics_hide_inactive_templates">
							<?php esc_html_e( 'Hide templates for inactive plugins', 'product-sales-report-for-woocommerce' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When enabled, templates for inactive e-commerce plugins will be hidden from the template library.', 'product-sales-report-for-woocommerce' ); ?>
						</p>
					</div>
				</div>
                <div class="berrypress-mt-4">
                    <button type="submit" name="ninjalytics_save_settings" class="berrypress-btn berrypress-btn-primary">
                        <?php esc_html_e( 'Save Settings', 'product-sales-report-for-woocommerce' ); ?>
                    </button>
                </div>
			</form>
		</div>
		<?php
	}
}

