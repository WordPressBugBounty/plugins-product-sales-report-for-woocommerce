<?php
namespace NinjalyticsFree;

/**
 * Shared template modal markup for Ninjalytics admin pages.
 * Expects $templateLibrary (array) to be defined in the calling scope.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$iconBaseUrl = plugin_dir_url( dirname( __DIR__, 2 ) . '/ninjalytics.php' ) . 'images/templates/';
$reporters = ninjalytics_get_reporters_info();

// Group templates by reporter, then by type (aggregated reports vs raw exports).
// `export_orders => 1` is the canonical flag that switches the underlying data
// model (one row per record) so it is the right axis to group on.
$groupedTemplates = [];
foreach ( $templateLibrary as $tpl ) {
	$type = ! empty( $tpl['export_orders'] ) ? 'export' : 'report';
	$groupedTemplates[ $tpl['reporter'] ][ $type ][] = $tpl;
}

$sectionMeta = [
	'report' => [
		'label_suffix' => __( 'Product Reports', 'product-sales-report-for-woocommerce' ),
		'description'  => __( 'Summarised metrics by product, time, geography and more. Each row aggregates many underlying records. Ideal for charts.', 'product-sales-report-for-woocommerce' ),
	],
	'export' => [
		'label_suffix' => __( 'Order Reports', 'product-sales-report-for-woocommerce' ),
		'description'  => __( 'One row per order or line item for spreadsheets and day-to-day operations.', 'product-sales-report-for-woocommerce' ),
	],
];

?>
<div class="berrypress-modal berrypress-modal-xl ninjalytics-modal-templates">
	<button type="button" class="berrypress-modal-close berrypress-modal-close-top" aria-label="<?php esc_html_e( 'Close Dialog', 'product-sales-report-for-woocommerce' ); ?>"><i class="berrypress-icon-close"></i></button>
	<div class="berrypress-card berrypress-card-100 ninjalytics-template-modal-card">
		<div class="berrypress-card-content">
			<h2 class="ninjalytics-template-modal-title"><?php esc_html_e( 'Choose Report Template', 'product-sales-report-for-woocommerce' ); ?></h2>
			<p class="ninjalytics-template-modal-subtitle"><?php esc_html_e( 'Select a ready-made template or start from a blank report. Available fields and settings depend on the template you choose.', 'product-sales-report-for-woocommerce' ); ?></p>

            <div class="ninjalytics-template-modal-search">
                <label for="ninjalytics-template-search" class="berrypress-visually-hidden">
					<?php esc_html_e( 'Search templates', 'product-sales-report-for-woocommerce' ); ?>
                </label>
                <input type="search" id="ninjalytics-template-search" placeholder="<?php esc_attr_e( 'Search templates…', 'product-sales-report-for-woocommerce' ); ?>" />
            </div>
			<div class="ninjalytics-template-content-wrapper">
			    <div class="ninjalytics-template-modal-filters">
				<?php foreach ($reporters as $reporterId => $reporter) { ?>
					<button type="button" class="berrypress-btn berrypress-btn-secondary js-ninjalytics-template-filter<?php echo ( ! empty( $hideInactiveTemplates ) && empty( $reporter['active'] ) ) ? ' berrypress-hidden' : ''; ?>" data-ninjalytics-template-filter="<?php echo(esc_html( $reporterId )); ?>"><?php echo(esc_html( $reporter['label'] )); ?></button>
				<?php } ?>
			</div>
			    <div class="berrypress-cards-wrapper ninjalytics-template-modal-grid" data-ninjalytics-template-count="<?php echo esc_attr( count( $templateLibrary ) ); ?>">
				<?php
				foreach ( $reporters as $reporterId => $reporterMeta ) {
					if ( empty( $groupedTemplates[ $reporterId ] ) ) {
						continue;
					}

					$reporterInactive = empty( $reporterMeta['active'] );
					$hideReporter     = $reporterInactive && ! empty( $hideInactiveTemplates );

					foreach ( [ 'report', 'export' ] as $type ) {
						if ( empty( $groupedTemplates[ $reporterId ][ $type ] ) ) {
							continue;
						}

						$sectionHeaderClasses = [ 'ninjalytics-template-modal-section-header' ];
						if ( $hideReporter ) {
							$sectionHeaderClasses[] = 'berrypress-hidden';
						}
						?>
						<div class="<?php echo esc_attr( implode( ' ', $sectionHeaderClasses ) ); ?> berrypress-mb-2"
							data-ninjalytics-template-section="<?php echo esc_attr( $reporterId . '|' . $type ); ?>"
							data-ninjalytics-template-integrations="<?php echo esc_attr( $reporterId ); ?>">
							<h3 class="ninjalytics-template-modal-section-title berrypress-mt-0 berrypress-mb-0">
								<span class="ninjalytics-template-modal-section-reporter"
									data-ninjalytics-template-section-reporter="<?php echo esc_attr( $reporterId ); ?>"><?php echo esc_html( $reporterMeta['label'] ); ?> - </span><?php echo esc_html( $sectionMeta[ $type ]['label_suffix'] ); ?>
							</h3>
							<p class="ninjalytics-template-modal-section-description">
								<?php echo esc_html( $sectionMeta[ $type ]['description'] ); ?>
							</p>
						</div>
						<?php
						foreach ( $groupedTemplates[ $reporterId ][ $type ] as $template ) {
							$iconFilename = $template['icon'];
							if ( strpos( $iconFilename, '.' ) === false ) {
								$iconFilename .= '.svg';
							}
							$iconUrl             = $iconBaseUrl . $iconFilename;

							$cardClasses         = [ 'berrypress-card', 'ninjalytics-template-card' ];
							if ( $reporterInactive ) {
								$cardClasses[] = 'ninjalytics-template-card-disabled';
								if ( ! empty( $hideInactiveTemplates ) ) {
									$cardClasses[] = 'berrypress-hidden';
								}
							}
							if ( $template['pro'] ?? false ) {
								$cardClasses[] = 'ninjalytics-template-card-pro';
							} elseif ( $template['blank'] ?? false ) {
								$cardClasses[] = 'ninjalytics-template-card-blank';
							}

							$templateUrl = AdminPage::getUrl(
								[
									'preset'               => '_' . $template['id'],
									'_reporter' => $template['reporter'],
								]
							);
							?>
							<div class="<?php echo esc_attr( implode( ' ', $cardClasses ) ); ?>"
								data-ninjalytics-template-card="true"
								data-ninjalytics-template-id="<?php echo esc_attr( $template['id'] ); ?>"
								data-ninjalytics-template-integrations="<?php echo esc_attr( $template['reporter'] ); ?>"
								data-ninjalytics-template-type="<?php echo esc_attr( $type ); ?>"
								data-ninjalytics-template-active="<?php echo esc_attr( $reporters[$template['reporter']]['active'] ? '1' : '0' ); ?>"
								data-ninjalytics-template-pro="<?php echo esc_attr( empty($template['pro']) ? '0' : '1' ); ?>"
								data-ninjalytics-template-search="<?php echo esc_attr( strtolower( $template['preset_name'] . ' ' . $template['_description'] . ' ' ) ); ?>">
								<div class="ninjalytics-template-card-content">
									<div class="ninjalytics-template-card-header">
										<span class="ninjalytics-template-icon-wrapper">
											<img class="ninjalytics-template-icon" src="<?php echo esc_url( $iconUrl ); ?>" alt="" role="presentation" />
										</span>
										<h3 class="ninjalytics-template-card-title">
											<?php echo esc_html( $template['preset_name'] ); ?>
											<?php if ( $template['pro'] ?? false ) { ?>
												<span class="ninjalytics-template-card-badge">
													<?php esc_html_e( 'Pro', 'product-sales-report-for-woocommerce' ); ?>
												</span>
											<?php } ?>
										</h3>
									</div>
									<p class="ninjalytics-template-card-description">
										<?php echo esc_html( $template['_description'] ); ?>
									</p>
									<div class="ninjalytics-template-card-actions">
										<?php if (empty($template['pro'])) { ?>
											<?php if ( !current_user_can($template['capability']) ) { ?>
												<button type="button" class="berrypress-btn berrypress-btn-secondary ninjalytics-template-card-action" disabled>
													<?php esc_html_e( 'Permission required', 'product-sales-report-for-woocommerce' ); ?>
												</button>
											<?php } else if ($reporters[$template['reporter']]['active']) { ?>
												<a href="<?php echo esc_url( $templateUrl ); ?>" class="berrypress-btn berrypress-btn-primary ninjalytics-template-card-action" data-ninjalytics-template-action="use">
													<?php
													if ( $template['blank'] ?? false ) {
														esc_html_e( 'Create Blank Report', 'product-sales-report-for-woocommerce' );
													} else {
														esc_html_e( 'Use Template', 'product-sales-report-for-woocommerce' );
													}
													?>
												</a>
											<?php } else { ?>
												<a href="<?php echo esc_url( 'https://wordpress.org/plugins/'.$reporters[$template['reporter']]['plugin_slug'] ); ?>" class="berrypress-btn berrypress-btn-secondary ninjalytics-template-card-action ninjalytics-template-card-action-install" target="_blank" rel="noopener">
													<?php esc_html_e( 'Install from WordPress.org', 'product-sales-report-for-woocommerce' ); ?>
												</a>
											<?php } ?>
										<?php } else { ?>
											<span class="ninjalytics-template-card-note">
												<?php esc_html_e( 'Available in the Pro version.', 'product-sales-report-for-woocommerce' ); ?>
											</span>
										<?php } ?>
									</div>
								</div>
							</div>
						<?php } /* end foreach template */ ?>
					<?php } /* end foreach type */ ?>
				<?php } /* end foreach reporter */ ?>
			</div>
            </div>
			<div class="ninjalytics-template-modal-footer">
				<button type="button" class="berrypress-btn berrypress-btn-secondary berrypress-modal-close" aria-label="<?php esc_html_e( 'Close Dialog', 'product-sales-report-for-woocommerce' ); ?>"><?php esc_html_e( 'Close', 'product-sales-report-for-woocommerce' ); ?></button>
			</div>
		</div>
	</div>
</div>
