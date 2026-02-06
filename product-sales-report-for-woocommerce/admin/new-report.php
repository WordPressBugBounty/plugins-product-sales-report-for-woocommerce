<?php
namespace NinjalyticsFree;

use NinjalyticsFree\AdminPage;
use NinjalyticsFree\Reporters\EDD;
use NinjalyticsFree\Reporters\LiveCarts;

defined( 'ABSPATH' ) || exit;

trait NewReportPageTrait {
	protected function renderNewReportPage() {
		$templateLibrary = $this->buildTemplateLibrary();
		
		$blankQuery            = [ 'preset' => 'new' ];
		$blankReportUrl = AdminPage::getUrl( $blankQuery );
		
		$hideInactiveTemplates = $this->getHideInactiveTemplatesSetting();
		?>

		<div class="berrypress-card berrypress-card-100 ninjalytics-new-report-hero">
			<div class="berrypress-card-content">
				<span class="ninjalytics-new-report-logo" aria-hidden="true">
					<img src="<?php echo esc_url( plugin_dir_url( dirname( __DIR__ ) . '/ninjalytics.php' ) . 'images/ninjalytics.png' ); ?>" alt="" />
				</span>
				<h2 class="ninjalytics-new-report-title"><?php esc_html_e( 'Create Report', 'product-sales-report-for-woocommerce' ); ?></h2>
				<p class="ninjalytics-new-report-subtitle"><?php esc_html_e( 'Choose a template or start from a blank configuration to build the report that fits your workflow.', 'product-sales-report-for-woocommerce' ); ?></p>
				<div class="ninjalytics-new-report-actions">
					<button type="button" class="berrypress-btn berrypress-btn-primary js-ninjalytics-modal-trigger" data-ninjalytics-template-filter="all">
						<?php esc_html_e( 'Use Template', 'product-sales-report-for-woocommerce' ); ?>
					</button>
					<a class="berrypress-btn berrypress-btn-secondary" href="<?php echo esc_url( $blankReportUrl ); ?>">
						<?php esc_html_e( 'Create Blank', 'product-sales-report-for-woocommerce' ); ?>
					</a>
				</div>
			</div>
		</div>

		<div class="ninjalytics-new-report-section">
            <h2><?php esc_html_e( 'Available Plugin Integrations', 'product-sales-report-for-woocommerce' ); ?></h2>

			<div class="ninjalytics-new-report-integration-list">
			<?php foreach ( ninjalytics_get_reporters_info() as $integrationKey => $integration ) {
				$cardClasses = [  'ninjalytics-new-report-integration-card' ];
				if ( $integration['active'] && current_user_can($integration['capability']) ) {
					$templateCount = count(ninjalytics_get_reporter_by_id($integrationKey)->getReportTemplates());
				} else {
					$cardClasses[] = 'ninjalytics-new-report-integration-card-disabled';
					if ( $hideInactiveTemplates ) {
						$cardClasses[] = 'berrypress-hidden';
					}
					$templateCount = 0;
				}
				?>
				<div class="<?php echo esc_attr( implode( ' ', $cardClasses ) ); ?>">
					<div class="ninjalytics-new-report-integration-top">
						<?php if ( ! empty( $integration['icon'] ) ) { ?>
							<div class="ninjalytics-new-report-integration-icon">
								<img src="<?php echo esc_url( $integration['icon'] ); ?>" alt="" role="presentation" loading="lazy" />
							</div>
						<?php } ?>
						<div class="ninjalytics-new-report-integration-details">
							<h3 class="ninjalytics-new-report-integration-title">
								<?php echo esc_html( $integration['label'] ); ?>
								<span class="ninjalytics-new-report-integration-status">
									<?php echo esc_html( $integration['active'] ? __( 'Active', 'product-sales-report-for-woocommerce' ) : __( 'Inactive', 'product-sales-report-for-woocommerce' ) ); ?>
								</span>
							</h3>
							<p class="ninjalytics-new-report-integration-description">
								<?php echo esc_html( $integration['description'] ); ?>
							</p>
							<p class="ninjalytics-new-report-integration-templates">
								<?php
								if ( $templateCount ) {
									echo esc_html(sprintf(
										/* translators: %d: number of templates */
										_n( '%d template available', '%d templates available', $templateCount, 'product-sales-report-for-woocommerce' ),
										$templateCount
									));
								} else {
									esc_html_e( 'Templates become available once the integration is active.', 'product-sales-report-for-woocommerce' );
								}
								?>
							</p>
						</div>
						<div class="ninjalytics-new-report-integration-actions">
							<?php if ( !current_user_can($integration['capability']) ) { ?>
								<button type="button" class="berrypress-btn berrypress-btn-secondary" disabled>
									<?php esc_html_e( 'Permission required', 'product-sales-report-for-woocommerce' ); ?>
								</button>
							<?php } else if ( $integration['active'] ) { ?>
								<button type="button" class="berrypress-btn berrypress-btn-primary js-ninjalytics-modal-trigger" data-ninjalytics-template-filter="<?php echo esc_attr( $integrationKey ); ?>">
									<?php esc_html_e( 'Use Template', 'product-sales-report-for-woocommerce' ); ?>
								</button>
							<?php } elseif ( ! empty( $integration['install_url'] ) ) { ?>
								<a class="berrypress-btn berrypress-btn-primary" href="<?php echo esc_url( $integration['install_url'] ); ?>" target="_blank" rel="noopener">
									<?php esc_html_e( 'Install from WordPress.org', 'product-sales-report-for-woocommerce' ); ?>
								</a>
							<?php } else { ?>
								<button type="button" class="berrypress-btn berrypress-btn-secondary" disabled>
									<?php esc_html_e( 'Activation required', 'product-sales-report-for-woocommerce' ); ?>
								</button>
							<?php } ?>
						</div>
					</div>
				</div>
			<?php } ?>
				</div>
		</div>


		<?php $this->renderTemplateModal(); ?>
		<?php
	}

	protected function renderTemplateModal() {
		$templateLibrary = $this->buildTemplateLibrary();
		$hideInactiveTemplates = $this->getHideInactiveTemplatesSetting();
		
		include __DIR__ . '/views/template-modal.php';
	}

	protected function getHideInactiveTemplatesSetting() {
		static $cache = null;
		if ( null === $cache ) {
			$cache = get_option( 'ninjalytics_hide_inactive_templates', false );
		}
		return $cache;
	}

	protected function buildTemplateLibrary() {
		static $library = null;
		if ( null !== $library ) {
			return $library;
		}
		
		$library = [];

		foreach ( ninjalytics_get_reporters_info() as $reporterKey => $reporterMeta ) {
			
			$reporter = ninjalytics_get_reporter_by_id($reporterKey);
			$templates = $reporter->getReportTemplates();
			
			foreach ( $templates as $templateId => $template ) {
				$library[] = array_merge(
					[
						'icon' => 'icon_4',
						'_description' => ''
					],
					$template,
					[
						'id' => $templateId,
						'reporter' => $reporterKey,
						'capability' => $reporterMeta['capability']
					]
				);
			}
		}

		return $library;
	}

	protected function getPluginIconUrl( string $pluginSlug ) {
		$transient_key = 'ninjalytics_icon_' . sanitize_key( $pluginSlug );
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return $cached;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$iconUrl = '';

		$response = plugins_api(
			'plugin_information',
			[
				'slug'   => $pluginSlug,
				'fields' => [
					'icons' => true,
				],
			]
		);

		if ( ! is_wp_error( $response ) && ! empty( $response->icons ) && is_array( $response->icons ) ) {
			foreach ( [ 'svg', '2x', '1x', 'default' ] as $key ) {
				if ( ! empty( $response->icons[ $key ] ) ) {
					$iconUrl = $response->icons[ $key ];
					break;
				}
			}
		}

		set_transient( $transient_key, $iconUrl, DAY_IN_SECONDS );

		return $iconUrl;
	}
}


