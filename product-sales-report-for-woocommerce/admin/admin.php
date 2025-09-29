<?php
namespace Ninjalytics;

use Ninjalytics\Admin\Page as BerryPressPage;
use Ninjalytics\Reporters\PlatformFeatures;

defined( 'ABSPATH' ) || exit;

class AdminPage extends BerryPressPage {

	protected $table, $cartData, $docsUrl = 'https://berrypress.com/docs/ninjalytics/', $supportUrl = 'https://wordpress.org/support/plugin/product-sales-report-for-woocommerce/', $reviewUrl = 'https://wordpress.org/support/plugin/product-sales-report-for-woocommerce/reviews/#new-post';

	function __construct() {
		/*
		add_action( get_plugin_page_hookname( 'ninjalytics', 'woocommerce' ), function () {
			$this->render();
		} );
		*/
		
		add_filter( 'berrypress_admin_page_top_nav', [ $this, 'filterNavItems' ] );
		add_filter( 'berrypress_admin_page_nav', [ $this, 'filterNavItems' ] );
		add_filter( 'berrypress_admin_page_display_sidebar', function() {
			return false;
		});

		add_filter(
			'berrypress_admin_page_header_url',
			function ( $url ) {
				return 'https://berrypress.com/product/woocommerce/ninjalytics/';
			}
		);

		add_filter(
			'berrypress_admin_page_header_text',
			function ( $text ) {
				return __( 'Ninjalytics', 'product-sales-report-for-woocommerce' );
			}
		);

		add_filter(
			'berrypress_admin_page_logo',
			function ( $logo ) {
				return plugin_dir_url( dirname( __DIR__ ) . '/ninjalytics.php' ) .
				       'images/ninjalytics.png';
			}
		);
	}

	public static function getUrl( array $args = [] ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=ninjalytics' ) );
	}
	
	public static function
    docsLink( $page, $anchor='', $important=false ) {
		return '<a href="'.esc_url('https://berrypress.com/docs/ninjalytics/'.$page.($anchor ? '#'.$anchor : '')).'" target="_blank" class="berrypress-link ninjalytics-docs-link'.($important ? ' ninjalytics-docs-link-important' : '').'">
					<span>'.($important ? esc_html__('Read documentation for important details', 'product-sales-report-for-woocommerce' ) : esc_html__('Read documentation', 'product-sales-report-for-woocommerce' )).'</span>
				</a>';
	}

	public function filterNavItems( $nav ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- just checking the page we're on
		$current_page = isset($_GET['tab']) ? sanitize_text_field(wp_unslash( $_GET['tab']) ) : 'reports';
		
		$nav = [
			[
				'link'   => self::getUrl(),
				'icon'   => 'berrypress-icon-bar-chart',
				'title'  => __( 'Reports', 'product-sales-report-for-woocommerce' ),
				'active' => ($current_page === 'reports' || $current_page === '')
			],
            [
				'link'   => self::getUrl(['tab' => 'about']),
				'icon'   => 'berrypress-icon-info',
				'title'  => __( 'About', 'product-sales-report-for-woocommerce' ),
				'active' => ($current_page === 'about')
			],
            [
				'link'   => self::getUrl(['tab' => 'addons']),
				'icon'   => 'berrypress-icon-addons',
				'title'  => __( 'Addons', 'product-sales-report-for-woocommerce' ),
				'active' => ($current_page === 'addons')
			],
            [
				'link'   => self::getUrl(['tab' => 'about-pro']),
				'icon'   => 'berrypress-icon-pro',
				'title'  => __( 'About Pro', 'product-sales-report-for-woocommerce' ),
				'active' => ($current_page === 'about-pro')
			]
		];

		return $nav;
	}

	public function body() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- just checking the page we're on
		$current_page = isset($_GET['tab']) ? sanitize_text_field(wp_unslash( $_GET['tab']) ) : 'reports';
		
		// Route to appropriate page content
		switch ($current_page) {
			case 'about':
				$this->renderAboutPage();
				break;
			case 'addons':
				$this->renderAddonsPage();
				break;
			case 'about-pro':
				$this->renderAboutProPage();
				break;
			default:
				$this->renderReportsPage();
				break;
		}
	}
	
	private function renderReportsPage() {
		global $wp_roles;
		
		try {
			$reporter = ninjalytics_get_active_reporter();
		} catch (\Exception $ex) {
			echo('<div class="ags-psr-notification ags-psr-notification-error"><p>This plugin requires that WooCommerce or Easy Digital Downloads is installed and activated.</p></div></div>');
			return;
		}
			

	$savedReportSettings = get_option('ninjalytics_settings');
	if (empty($savedReportSettings)) {
		$savedReportSettings = array(
			ninjalytics_default_report_settings()
		);
	}
	
	if (isset($_REQUEST['preset']) && isset($_REQUEST['ninjalytics_action'])) {
		
		if ($_REQUEST['ninjalytics_action'] == 'update-fields') {
				check_admin_referer('hm-psrp-update-fields');
				ninjalytics_update_field_cache();
				echo('<script type="text/javascript">location.href = \'?page=ninjalytics\' + window.location.hash;</script>');
				return;
			} else if ($_REQUEST['ninjalytics_action'] == 'preset-save') {
				check_admin_referer('hm-psr-run', 'hm-psr-nonce');
			
				$isNew = empty((int) $_REQUEST['preset']);
				
				if ($isNew || isset($savedReportSettings[(int) $_REQUEST['preset']])) {
					$_POST = stripslashes_deep($_POST);
					
					// Map new (1.6.8) product category checklist onto old field name
					if (isset($_POST['tax_input']['product_cat'])) {
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Mapping from one POST var to another, to be sanitized before use
						$_POST['product_cats'] = $_POST['tax_input']['product_cat'];
						unset($_POST['tax_input']);
					}
					
					// Also update checkbox fields in hm_sbp_on_init
					foreach (array(
						'limit_on', 'include_nil', 'include_shipping', 'include_unpublished', 'include_header', 'include_totals',
						'format_amounts', 'exclude_free', 'order_meta_filter_on', 'order_meta_filter_2_on', 'customer_meta_filter_on', 'product_tag_filter_on',
						'product_meta_filter_on', 'refunds', 'adjustments', 'report_title_on', 'report_unfiltered', 'hm_psr_debug', 'object_caching_disable',
						'use_wp_date', 'disable_product_grouping', 'intermediate_rounding', 'order_item_meta_filter_1_on', 'order_item_meta_filter_2_on',
						'remove_html'
						) as $checkboxField) {
						
						if (!isset($_POST[$checkboxField])) {
							$_POST[$checkboxField] = 0;
						}
					}
					
					if (isset($savedReportSettings[$_REQUEST['preset']]['key'])) {
						$_POST['key'] = $savedReportSettings[(int) $_REQUEST['preset']]['key'];
					}
					
					if ($isNew) {
						$savedReportSettings[] = stripslashes_deep($_POST);
					} else {
						$savedReportSettings[(int) $_REQUEST['preset']] = $_POST;
					}
					update_option('ninjalytics_settings', $savedReportSettings, false);
					
					if ($isNew) {
						echo('<script type="text/javascript">location.href = \'?page=ninjalytics&preset='.(count($savedReportSettings) - 1).'\';</script>');
					}
					
				}
			} else if ($_REQUEST['ninjalytics_action'] == 'preset-del' && !empty((int) $_GET['preset']) && isset($savedReportSettings[(int) $_GET['preset']])) {
				check_admin_referer('hm-psr-run');
				
				unset($savedReportSettings[(int) $_GET['preset']]);
				update_option('ninjalytics_settings', $savedReportSettings, false);
				delete_option('ninjalytics_report_dates_'.((int) $_GET['preset']));
				unset($_GET['preset']);
				echo('<script type="text/javascript">location.href = \'?page=ninjalytics\';</script>');
				return;
			}
		}
		
		if (!empty($_GET['preset'])) {
			$openPreset = sanitize_text_field(wp_unslash($_GET['preset']));
			
			if ($openPreset == 'new') {
				$reportSettings = ninjalytics_default_report_settings();
			} else {
				$reportSettings = array_merge(
					ninjalytics_default_report_settings(),
					$openPreset == '_' ? ((ninjalytics_get_active_reporter()->getReportTemplates())[substr($openPreset, 1)] ?? []) : ($savedReportSettings[ $openPreset ] ?? []),
					((int) $openPreset) ? json_decode(get_option('ninjalytics_report_dates_'.((int) $openPreset), '{}'), true) : []
				);
				
				// For backwards compatibility with pre-1.5 versions
				if (!empty($reportSettings['cat'])) {
					$reportSettings['products'] = 'cats';
					$reportSettings['product_cats'] = array($reportSettings['cat']);
				}
			}
			
			$fieldOptions = ninjalytics_get_default_fields();
				
			// Print form
			//ninjalytics_loadPresetField($savedReportSettings);

			$orderBy = (in_array($reportSettings['orderby'], array('product_id', 'quantity', 'gross', 'gross_after_discount')) ? 'builtin::'.$reportSettings['orderby'] : $reportSettings['orderby']);
			
			
			
			?>
			<ol id="ags-psr-breadcrumbs">
				<li>
					<a href="?page=ninjalytics">Reports</a>
				</li>
				<li>
					<a href="?page=ninjalytics&preset=<?php echo((int) $openPreset); ?>"><?php echo($openPreset == 'new' ? 'New Report' : esc_html($reportSettings['preset_name'] ?? 'Untitled Report')); ?></a>
				</li>
			</ol>
			<form action="" method="post" id="ninjalytics-form">
				<input type="hidden" name="preset" value="<?php echo((int) $openPreset); ?>">


                <div id="ags-psr-display-toolbar">

                    <div class="ags-psr-display-toolbar-left">

                        <div id="ags-psr-display-mode" role="radiogroup" aria-labelledby="ags-psr-display-label">

                            <span class="ags-psr-label">Display:</span>

                            <div class="ags-psr-display-options">

                                <input type="radio" id="display_mode_table" name="display_mode" value="table" <?php checked('table', $reportSettings['display_mode']); ?>>
                                <label for="display_mode_table" class="ags-psr-button-icon ags-psr-button-icon-border ags-psr-button-icon-table" data-tooltip="Display Table">
                                    <img alt="Table Icon" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../images/table.svg' ); ?>">
                                    <span class="berrypress-visually-hidden">Display Table</span>
                                </label>

                                <input type="radio" id="display_mode_chart" name="display_mode" value="chart" <?php checked('chart', $reportSettings['display_mode']); ?>>
                                <label for="display_mode_chart" class="ags-psr-button-icon ags-psr-button-icon-border ags-psr-button-icon-chart"  data-tooltip="Display Chart">
                                    <img alt="Table Icon" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../images/chart.svg' ); ?>">
                                    <span class="berrypress-visually-hidden">Display Chart</span>
                                </label>

                            </div>
                        </div>
                        <div id="ags-psr-date-range">
                            <span class="ags-psr-label">Date range:</span>
                            <input type="text" id="ags-psr-dates-desc" readonly>

                            <div id="ags-psr-date-range-dropdown" class="hm-psr-hidden">

                                <div class="ags-psr-date-range-tabs">
                                    <?php foreach (['basic' => 'Quick', 'absolute' => 'Absolute', 'dynamic' => 'Expression'] as $mode => $label) { ?>
                                        <input type="radio" id="report_time_mode_<?php echo(esc_attr($mode)); ?>" name="report_time_mode" value="<?php echo(esc_attr($mode)); ?>"<?php checked($mode, $reportSettings['report_time_mode']); ?>>
                                        <label for="report_time_mode_<?php echo(esc_attr($mode)); ?>"><?php echo(esc_html($label).($mode == 'dynamic' ? ' <span class="ninjalytics-pro-badge">Pro</span>' : '')); ?></label>
                                    <?php } ?>
                                </div>

                                <div id="ags-psr-date-range-dropdown-body">
                                    <div class="ags-psr-date-range-dropdown-tab-content" id="ags-psr-date-range-basic">
                                        <div>
                                            <label for="ags-psr-date-range-basic-from">From:</label>
                                            <div>
                                                <input type="number" id="ags-psr-date-range-basic-from" name="report_time_basic_from">
                                                <select name="report_time_basic_from_unit">
                                                    <option value="max"<?php checked('max', $reportSettings['report_time_basic_from_unit']); ?>>Forever</option>
                                                    <option value="now"<?php checked('now', $reportSettings['report_time_basic_from_unit']); ?>>Now</option>
                                                    <option value="-d"<?php checked('-d', $reportSettings['report_time_basic_from_unit']); ?>>Day(s) ago</option>
                                                    <option value="-cm"<?php checked('-cm', $reportSettings['report_time_basic_from_unit']); ?>>Month(s) ago</option>
                                                </select>
                                                <select name="report_time_basic_from_round">
                                                    <option value="d"<?php checked('d', $reportSettings['report_time_basic_from_round']); ?>>rounded to start of day</option>
                                                    <option value="m"<?php checked('m', $reportSettings['report_time_basic_from_round']); ?>>rounded to start of month</option>
                                                    <option value=""<?php checked(!$reportSettings['report_time_basic_from_round']); ?>>without rounding</option>
                                                </select>
                                            </div>
                                            <p></p>
                                        </div>
                                        <div>
                                            <label for="ags-psr-date-range-basic-to">To:</label>
                                            <div>
                                                <input type="number" id="ags-psr-date-range-basic-to" name="report_time_basic_to">
                                                <select name="report_time_basic_to_unit">
                                                    <option value="max"<?php checked('max', $reportSettings['report_time_basic_to_unit']); ?>>Forever</option>
                                                    <option value="now"<?php checked('now', $reportSettings['report_time_basic_to_unit']); ?>>Now</option>
                                                    <option value="-d"<?php checked('-d', $reportSettings['report_time_basic_to_unit']); ?>>Day(s) ago</option>
                                                    <option value="-cm"<?php checked('-cm', $reportSettings['report_time_basic_to_unit']); ?>>Month(s) ago</option>
                                                    <option value="d"<?php checked('d', $reportSettings['report_time_basic_to_unit']); ?>>Day(s) in future</option>
                                                    <option value="cm"<?php checked('cm', $reportSettings['report_time_basic_to_unit']); ?>>Month(s) in future</option>
                                                </select>
                                                <select name="report_time_basic_to_round">
                                                    <option value="d"<?php checked('d', $reportSettings['report_time_basic_to_round']); ?>>rounded to end of day</option>
                                                    <option value="m"<?php checked('m', $reportSettings['report_time_basic_to_round']); ?>>rounded to end of month</option>
                                                    <option value=""<?php checked(!$reportSettings['report_time_basic_to_round']); ?>>without rounding</option>
                                                </select>
                                            </div>
                                            <p></p>
                                        </div>
                                    </div>

                                    <div class="ags-psr-date-range-dropdown-tab-content" id="ags-psr-date-range-absolute">
                                        <div>
                                            <label for="ags-psr-date-range-absolute-from">From:</label>
                                            <div>
                                                <input type="date" id="ags-psr-date-range-absolute-from" name="report_time_absolute_from_date" value="<?php echo(esc_attr($reportSettings['report_time_absolute_from_date'])); ?>">
                                                <input type="time" step="1" name="report_time_absolute_from_time" value="<?php echo(esc_attr($reportSettings['report_time_absolute_from_time'])); ?>">
                                            </div>
                                        </div>
                                        <div>
                                            <label for="ags-psr-date-range-absolute-to">To:</label>
                                            <div>
                                                <input type="date" id="ags-psr-date-range-absolute-to" name="report_time_absolute_to_date" value="<?php echo(esc_attr($reportSettings['report_time_absolute_to_date'])); ?>">
                                                <input type="time" step="1" name="report_time_absolute_to_time" value="<?php echo(esc_attr($reportSettings['report_time_absolute_to_time'])); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ags-psr-date-range-dropdown-tab-content ninjalytics-pro-feature" id="ags-psr-date-range-dynamic">
                                        <div>
                                            <label for="ags-psr-date-range-dynamic-from">From:</label>
                                            <div>
                                                <input disabled type="text" id="ags-psr-date-range-dynamic-from" name="report_time_dynamic_from" placeholder="example: -1 month">
                                                <p></p>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="ags-psr-date-range-dynamic-to">To:</label>
                                            <div>
                                                <input disabled type="text" id="ags-psr-date-range-dynamic-to" name="report_time_dynamic_to" placeholder="example: yesterday midnight">
                                                <p></p>
                                            </div>
                                        </div>
                                    </div>

                                </div>


                            </div>
                        </div>

                    </div>

                    <div id="ags-psr-download-email">
                        <button id="ags-psr-download-button" class="berrypress-btn berrypress-btn-icon" type="submit" name="ninjalytics_action" value="run" data-tooltip="Download Report" aria-label="Download">
                            <i class="berrypress-icon-download" aria-hidden="true"></i>
                        </button>
                        <label for="email_to" class="hm-psr-hidden">Send to email</label>
                        <input type="email" name="email_to" id="email_to" placeholder="Email address" value="<?php echo(esc_attr(get_option('ninjalytics_last_email_to', get_bloginfo('admin_email')))); ?>" class="hidden" disabled>

                        <button id="ags-psr-email-button" class="ninjalytics-pro-feature berrypress-btn berrypress-btn-icon" data-tooltip="Email Report"  aria-label="Email Report" type="button">
                            <i class="berrypress-icon-email" aria-hidden="true"></i>
							<span class="ninjalytics-pro-badge">Pro</span>
                        </button>
                        <button class="berrypress-btn berrypress-btn-primary" name="ninjalytics_action" value="preset-save" onclick="jQuery(this).closest('form').attr('target', ''); return true;">Save</button>
                    </div>
                </div>
                <div id="ninjalytics-settings-settings" class="ninjalytics-settings-active">

				<div>
					<div id="hm_psr_output_container">
						<div class="hm_psr_output_loading">
							<label>Loading...<progress min="0" max="100"></label>
						</div>
						<canvas id="hm_psr_chart"></canvas>
						<p id="hm-psr-chart-no-fields" class="hm-psr-hidden">No fields are selected for the chart. Enable the Chart option for one or more fields in the Report Fields tab.</p>
					</div>
				</div>
				<div>
					<div id="ags-psr-report-meta">
						<label>
							<span>Report Name:</span>
							<input type="text" name="preset_name" value="<?php  echo(esc_attr($reportSettings['preset_name'] ?? '')); ?>"  />
						</label>
					</div>
					
					<div id="ninjalytics-settings">
							<div class="ninjalytics-settings-toggle">
								<div class="ags-psr-section-title">
									<h3>Products</h3>
									<label>
										<input type="checkbox" class="ags-psr-no-update">
										<span>Advanced</span>
									</label>
                                    <button class="berrypress-btn-icon">
                                        <i class="berrypress-icon-expand_more"></i>
                                    </button>
								</div>
			<?php
			echo('
			<div id="hm_psr_tab_products_panel" class="ags-psr-section-body">
			
			  <div class="ninjalytics-settings-box">
                    <div class="ninjalytics-settings-cb-list ninjalytics-settings-cb-list-column">
                        <div class="ninjalytics-settings-content">
                            <label class="ninjalytics-settings-cb-list-item">
                                <input type="radio" name="products" value="all"' . ($reportSettings['products'] == 'all' ? ' checked="checked"' : '') . ' /> All products
                            </label>
						    <label class="ninjalytics-settings-cb-list-item">
						        <input type="radio" name="products" value="cats"' . ($reportSettings['products'] == 'cats' ? ' checked="checked"' : '') . ' /> Products in categories:
						    </label>
						    <label class="ninjalytics-settings-cb-list-item ninjalytics-settings-cb-list-item-child">
						<ul id="hm-psr-product-cats">
					');
			wp_terms_checklist(0, array('selected_cats' => $reportSettings['product_cats'], 'taxonomy' => $reporter->productCategoryTaxonomy, 'checked_ontop' => false));
			echo('
						</ul>
						</label>
                            <label class="ninjalytics-settings-cb-list-item">
                                <input type="radio" name="products" value="ids"' . ($reportSettings['products'] == 'ids' ? ' checked="checked"' : '') . ' /> Product ID(s):
                            </label> 
                            <label class="ninjalytics-settings-cb-list-item ninjalytics-settings-cb-list-item-child">
                                <input type="text" name="product_ids" placeholder="Use commas to separate multiple product IDs" value="' . esc_attr($reportSettings['product_ids']) . '" />
                            </label>
                        </div>
                    </div>
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products' ).'
                </div>
                
                  <div class="ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
                        <label class="ninjalytics-settings-title">
                           <span class="label">Only Products Tagged: <span class="ninjalytics-pro-badge">Pro</span></span>
                         </label>
                        <div id="hm_psr_product_tag_filter_settings" class="ninjalytics-settings-content">
                            <input type="checkbox" name="product_tag_filter_on" value="1"' . (empty($reportSettings['product_tag_filter_on']) ? '' : ' checked="checked"') . ' disabled />
                            <input type="text" id="hm_psr_product_tag_filter" name="product_tag_filter" disabled />
			
							<select disabled id="hm_psr_product_tag_filter_select">
								<option value="">Select tag...</option>
							</select>
						</div>
                    </div>
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'only-products-tagged' ).'
                </div>
				
                  <div class="ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
                        <label class="ninjalytics-settings-title">
                           <span class="label">Only Products With Field: <span class="ninjalytics-pro-badge">Pro</span></span>
						</label>
                        <div id="hm_psr_product_meta_filter_settings" class="ninjalytics-settings-content">
                            <input type="checkbox" disabled />
						<select disabled name="product_meta_filter_key">
						</select>
						<select disabled class="ags-psr-order-meta-filter-cast">
							<option>as text/number</option>
							<option>as date/time with format:</option>
						</select>
						<input disabled type="text">
						<select disabled id="hm_psr_product_meta_filter_op">
							<option>equal to</option>
							<option>not equal to</option>
							<option>less than</option>
							<option>less than or equal to</option>
							<option>greater than</option>
							<option>greater than or equal to</option>
							<option>between</option>
						</select>
						<select disabled class="hm-psr-value-preset" data-hm-psr-for="product_meta_filter_value">
							<option>value:</option>
							<option>current user ID</option>
						</select>
						<input type="text" name="product_meta_filter_value">
						<span id="hm_psr_product_meta_filter_value_2" style="display: none;"> and
							<input type="text">
						</span>
                        </div>
						
                    </div>
					
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'only-products-with-field', true ).'
                </div>');
				$hasVariationSupport = $reporter->supports(PlatformFeatures::VARIATIONS);
                if ($hasVariationSupport) {
					echo('<div class="ninjalytics-settings-box">
						<div class="ninjalytics-settings-cb-list  ninjalytics-settings-cb-list-column">
							<label class="ninjalytics-settings-title">
							   <span class="label">Product Variations:</span>
							 </label>
							<div class="ninjalytics-settings-content">
								<label class="ninjalytics-settings-cb-list-item">
								<input type="radio" name="variations" value="0"'.(empty($reportSettings['variations']) ? ' checked="checked"' : '').' class="hm_psr_variations_fld" />
								Group product variations together
								</label>
								<label class="ninjalytics-settings-cb-list-item">
								<input type="radio" name="variations" value="1"'.(empty($reportSettings['variations']) ? '' : ' checked="checked"').' class="hm_psr_variations_fld" />
								Report on each variation separately
							</label>
							</div>
						</div>
					
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'product-variations' ).'
					</div>');
				}
				
				echo('
                <div class="ninjalytics-settings-box">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="include_nil" value="1"'.(empty($reportSettings['include_nil']) ? '' : ' checked="checked"').' />
                       <span class="label">Include products with no sales matching the filtering criteria</span>
						</label>
					
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'products-no-sales' ).'
                </div>   
				
                <div class="ninjalytics-settings-box">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="include_unpublished" value="1"'.(empty($reportSettings['include_unpublished']) ? '' : ' checked="checked"').' />
                            <span class="label">Include unpublished products</span>
						</label>
					
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'products-unpublished' ).'
                </div>   
                
                <div class="ninjalytics-settings-box">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="exclude_free" value="1"'.(empty($reportSettings['exclude_free']) ? '' : ' checked="checked"').' />
                            <span class="label">Exclude free products</span>
						</label>
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'exclude-free', true ).'
                </div>');
				
				if ( $reporter->supports(PlatformFeatures::SHIPPING) ) {
					echo('
						<div class="ninjalytics-settings-box">
								<label class="ninjalytics-settings-label-column">
									<input type="checkbox" name="include_shipping" value="1"'.(empty($reportSettings['include_shipping']) ? '' : ' checked="checked"').' />
									<span class="label">Include shipping</span>
								</label>
					
							'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'shipping' ).'
						</div>
					');
				}
				
				if ( $reporter->supports(PlatformFeatures::LINE_ITEM_ADJUSTMENTS) ) {
					echo('<div class="ninjalytics-settings-box">
							<label class="ninjalytics-settings-label-column">
								<input type="checkbox" name="adjustments" value="1"'.(empty($reportSettings['adjustments']) ? '' : ' checked="checked"').' />
								<span class="label">Include line-item adjustments</span>
							</label>
					
							'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'adjustments', true ).'
						</div>');
				}
				
                echo('
                <div class="ninjalytics-settings-box">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="refunds" value="1"'.(empty($reportSettings['refunds']) ? '' : ' checked="checked"').' />
                            <span class="label">Include line-item refunds</span>
						</label>
                    
					
					'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/products', 'refunds', true ).'
                </div> 
			
            </div> <!-- hm_psr_tab_products_panel -->
			
			');
			?>
			</div>
			<div class="ninjalytics-settings-toggle">
				<div class="ags-psr-section-title">
					<h3>Orders</h3>
					<label>
						<input type="checkbox" class="ags-psr-no-update">
						<span>Advanced</span>
					</label>

                    <button class="berrypress-btn-icon">
                        <i class="berrypress-icon-expand_more"></i>
                    </button>
				</div>
				<div id="hm_psr_tab_orders_panel" class="ags-psr-section-body">
					<?php echo('
						 <div class="ninjalytics-settings-box">
							<div class="ninjalytics-settings-cb-list  ninjalytics-settings-cb-list-column">
								<label class="ninjalytics-settings-title">
								   <span class="label">Status:</span>
								 </label>
								<div class="ninjalytics-settings-content">');
					foreach ($reporter->getOrderStatuses() as $status => $statusName) {
						echo('<label class="ninjalytics-settings-cb-list-item"><input type="checkbox" name="order_statuses[]"' . (in_array($status, $reportSettings['order_statuses']) ? ' checked="checked"' : '') . ' value="' . esc_attr($status) . '" /> ' . esc_html($statusName) . '</label>');
					}
					echo('</div>
							</div>
					
							'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/orders', 'order-status' ).'
						</div>
						
						<div class="ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
							<div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
								<label class="ninjalytics-settings-title">
								   <span class="label">Only Orders With Field: <span class="ninjalytics-pro-badge">Pro</span></span>
								</label>
								<div id="hm_psr_order_meta_filter_settings" class="ninjalytics-settings-content">
									<input disabled type="checkbox" />
								<select disabled>
								</select>
								<select disabled id="hm_psr_order_meta_filter_op">
									<option>equal to</option>
									<option>not equal to</option>
									<option>less than</option>
									<option>less than or equal to</option>
									<option>greater than</option>
									<option>greater than or equal to</option>
									<option>between</option>
									<option>does not exist</option>
								</select>
								<span class="hm_psr_filter_value_container">
									<input disabled type="text" id="hm_psr_order_meta_filter_value" />
									<input disabled type="text" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
									<span class="hm-psr-date-dynamic-toggle">dynamic date</span>
								</span>
								<span id="hm_psr_order_meta_filter_value_2" style="display: none;">
									and
									<span class="hm_psr_order_meta_filter_value_container">
										<input disabled type="text">
										<input disabled type="text" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
										<span class="hm-psr-date-dynamic-toggle">dynamic date</span>
									</span>
								</span>
								</div>
							</div>
							<div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
								<label class="ninjalytics-settings-title">
								   <span class="label" style="display: none;">Only Orders With Field 2: <span class="ninjalytics-pro-badge">Pro</span></span>
								</label>
								<div id="hm_psr_order_meta_filter_2_settings" class="ninjalytics-settings-content">
									<input disabled type="checkbox" />
									
								<select disabled style="width: auto;" name="order_meta_filter_2_logic">
									<option>and</option>
									<option>or</option>
								</select>
								<select disabled>
								</select>
								<select disabled class="ags-psr-order-meta-filter-cast">
									<option>as text/number</option>
									<option>as date/time with format:</option>
								</select>
								<input disabled type="text">
								<select disabled id="hm_psr_order_meta_filter_2_op">
									<option>equal to</option>
									<option>not equal to</option>
									<option>less than</option>
									<option>less than or equal to</option>
									<option>greater than</option>
									<option>greater than or equal to</option>
									<option>between</option>
									<option>does not exist</option>
								</select>
								<span class="hm_psr_filter_value_container">
									<input disabled type="text" id="hm_psr_order_meta_filter_2_value" />
									<input disabled type="text" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
									<span href="javascript:void(0);" class="hm-psr-date-dynamic-toggle">dynamic date</span>
								</span>
								<span id="hm_psr_order_meta_filter_2_value_2" style="display: none;">
									and
									<span class="hm_psr_order_meta_filter_2_value_container">
										<input type="text" />
										<input type="text" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
										<a href="javascript:void(0);" class="hm-psr-date-dynamic-toggle">dynamic date</a>
									</span>
								</span>
								</div>
								
								'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/orders', 'only-orders-with-field', true ).'

							</div>
						</div>
						
						<div class="ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
							<div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
								<label class="ninjalytics-settings-title">
								   <span class="label">Only Order Items With Field: <span class="ninjalytics-pro-badge">Pro</span></span>
								</label>
								<div id="hm_psr_order_item_meta_filter_settings_1" class="ninjalytics-settings-content">
									<input disabled type="checkbox" />
								<select disabled style="width: auto;">
								</select>
								<select disabled style="width: auto;" id="hm_psr_order_item_meta_filter_1_op">
									<option>equal to</option>
									<option>not equal to</option>
									<option>less than</option>
									<option>less than or equal to</option>
									<option>greater than</option>
									<option>greater than or equal to</option>
									<option>between</option>
									<option>does not exist</option>
								</select>
								<span class="hm_psr_filter_value_container">
									<input disabled type="text" style="width: auto;" id="hm_psr_order_item_meta_filter_1_value" />
									<input disabled type="text" style="width: auto;" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
									<span class="hm-psr-date-dynamic-toggle">dynamic date</span>
								</span>
								<span id="hm_psr_order_item_meta_filter_1_value_2" style="display: none;">
									and
									<span class="hm_psr_order_item_meta_filter_1_value_container">
										<input disabled type="text" style="width: auto;" />
										<input disabled type="text" style="width: auto;" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
										<span class="hm-psr-date-dynamic-toggle">dynamic date</span>
									</span>
								</span>
								</div>
								</div>
								
								
								 <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
								<label class="ninjalytics-settings-title">
								   <span class="label" style="display: none;">Only Order Items With Field 2: <span class="ninjalytics-pro-badge">Pro</span></span>
								</label>
								<div id="hm_psr_order_item_meta_filter_settings_2" class="ninjalytics-settings-content">
								<select disabled style="width: auto;">
									<option>and</option>
									<option>or</option>
								</select>
								<input disabled type="checkbox" />
								
								<select disabled style="width: auto;">
								</select>
								<select disabled style="width: auto;" id="hm_psr_order_item_meta_filter_2_op" name="order_item_meta_filter_2_op">
									<option>equal to</option>
									<option>not equal to</option>
									<option>less than</option>
									<option>less than or equal to</option>
									<option>greater than</option>
									<option>greater than or equal to</option>
									<option>between</option>
									<option>does not exist</option>
								</select>
								<span class="hm_psr_filter_value_container">
									<input disabled style="width: auto;" type="text" id="hm_psr_order_item_meta_filter_2_value" />
									<input disabled style="width: auto;" type="text" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
									<span class="hm-psr-date-dynamic-toggle">dynamic date</span>
								</span>
								<span id="hm_psr_order_item_meta_filter_2_value_2" style="display: none;">
									and
									<span class="hm_psr_order_item_meta_filter_2_value_container">
										<input disabled style="width: auto;" type="text" />
										<input disabled style="width: auto;" type="text" class="hm-psr-date-dynamic-field hm-psr-date-dynamic-field-with-format hidden" placeholder="e.g. -7 days" />
										<span class="hm-psr-date-dynamic-toggle">dynamic date</span>
									</span>
								</span>
								</div>
							</div>
							
							'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/orders', 'only-order-items-with-field', true ).'
						</div>

					');
					
					if ($reporter->supports(PlatformFeatures::SHIPPING)) {
						echo('
							<div class="ninjalytics-settings-box ninjalytics-pro-feature">
								<div class="ninjalytics-settings-cb-list  ninjalytics-settings-cb-list-column">
									<label class="ninjalytics-settings-title">
									   <span class="label">Include Orders by Shipping Method: <span class="ninjalytics-pro-badge">Pro</span></span>
									 </label>
									<div class="ninjalytics-settings-content">');
							foreach (\ninjalytics_get_order_shipping_filter_options() as $shippingMethodId => $shippingMethod) {
								echo('<label class="ninjalytics-settings-cb-list-item"><input type="checkbox" disabled> ' . esc_html($shippingMethod) . '</label>');
							}
							echo('</div>
								</div>
								
								'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/orders', 'include-orders-by-shipping-method', true ).'
							</div>
						');
					}
					
					if ($reporter->supports(PlatformFeatures::CUSTOMER_USERS)) {
					echo('
						<div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
							<label class="ninjalytics-settings-title">
							<span class="label">Filter Orders by Customer Role: <span class="ninjalytics-pro-badge">Pro</span></span></label>
				   ');
				   
				    $customerRoles = ['-1' => '(Guest Customers)'];
					foreach ($wp_roles->roles as $roleId => $role) {
						$customerRoles[$roleId] = $role['name'];
					}

				   ?>
				   <?php foreach ( ['customer_role' => 'Include Only', 'customer_role_exclude' => 'Exclude'] as $settingKey => $label) { ?>
				   <div>
						<span><?php echo(esc_html($label)); ?>:<span>
						<ul>
							<?php foreach ($customerRoles as $roleId => $roleName) { ?>
								<li>
									<label class="ninjalytics-settings-cb-list-item">
										<input disabled type="checkbox">
										<?php echo(esc_html($roleName)); ?>
									</label>
								</li>
							<?php } ?>
						</ul>
					</div>
				   <?php } ?>
				<?php
                echo(/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/orders', 'filter-orders-by-customer-role' ).'</div>');


					$wcMemberships = ninjalytics_get_wc_membership_plans();
					if ($wcMemberships) {

						echo('
						<div class="ninjalytics-settings-box ags-psr-advanced">
							<label>
							   <span class="label">Include Orders by Customer Membership: <span class="ninjalytics-pro-badge">Pro</span></span>
								<select disabled name="wc_membership">
									<option>(All Customers)</option>
									<option disabled>(Customers Without Membership)</option>
									<option disabled>(Customers With Any Membership)</option>');
						foreach ($wcMemberships as $membershipId => $membershipName) {
							echo('<option disabled>'.esc_html($membershipName).'</option>');
						}
						echo('						</select>
								</label>
								
							'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/orders', 'include-orders-by-customer-membership' ).'
						</div>
						
						');

					}
					echo('
						
						  <div class="ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
							<div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
								<label class="ninjalytics-settings-title">
								   <span class="label">Only Orders from Customers With Field: <span class="ninjalytics-pro-badge">Pro</span></span>
								 </label>
								<div id="hm_psr_customer_meta_filter_settings" class="ninjalytics-settings-content">
									<input disabled type="checkbox"/>
								<select name="customer_meta_filter_key" disabled>
								</select>
								<select disabled id="hm_psr_customer_meta_filter_op">
									<option>equal to</option>
									<option>not equal to</option>
									<option>less than</option>
									<option>less than or equal to</option>
									<option>greater than</option>
									<option>greater than or equal to</option>
									<option>between</option>
									<option>does not exist</option>
								</select>
								<input disabled type="text" id="hm_psr_customer_meta_filter_value" />
								<span id="hm_psr_customer_meta_filter_value_2" style="display: none;">
									and
									<input type="text" />
								</span>
								</div>
							</div>
							
							'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/orders', 'only-orders-from-customers-with-field' ).'
						</div>');
					}
				echo('
					</div> <!-- hm_psr_tab_orders_panel -->'); ?>
				</div>
			<div class="ninjalytics-settings-toggle">
                <div class="ags-psr-section-title">
                    <h3>Segmentation</h3>
                    <button class="berrypress-btn-icon">
                        <i class="berrypress-icon-expand_more"></i>
                    </button>
			</div>
			<?php
			$groupByFields = ninjalytics_get_groupby_fields();
			?>
			
			<div id="hm_psr_tab_groupsort_panel" class="ags-psr-section-body">
			
			
                
                <div class="ninjalytics-settings-box">
                    <div class="ninjalytics-settings-cb-list  ninjalytics-settings-cb-list-column">
                        <div class="ninjalytics-settings-content">
                            <label class="ninjalytics-settings-cb-list-item">
								<input type="radio" class="ags-psr-disable-product-grouping" name="disable_product_grouping" value="0"<?php checked(empty($reportSettings['disable_product_grouping']), true); ?>>
								Segment sales by products<?php if ($hasVariationSupport) { ?> or variations<?php } ?> (based on ID)
                            </label>
                            <label class="ninjalytics-settings-cb-list-item">
								<input type="radio" class="ags-psr-disable-product-grouping" name="disable_product_grouping" value="-1"<?php checked($reportSettings['disable_product_grouping'], -1); ?>>
								Segment sales by products<?php if ($hasVariationSupport) { ?> or variations<?php } ?> (based on SKU)
							</label>
                            <label class="ninjalytics-settings-cb-list-item">
								<input type="radio" class="ags-psr-disable-product-grouping" name="disable_product_grouping" value="2"<?php checked($reportSettings['disable_product_grouping'], 2); ?>>
								Segment sales by product category
							</label>
                            <label class="ninjalytics-settings-cb-list-item">
								<input type="radio" class="ags-psr-disable-product-grouping" name="disable_product_grouping" value="1"<?php checked($reportSettings['disable_product_grouping'], 1); ?>>
								None (custom segments only)
							</label>
                        </div>
                    </div>

					<?php echo(/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/segmentation', 'main-segment', true )); ?>
                </div>
					
			<?php
			  // Custom segments section
			echo('
				<div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
					<label class="ninjalytics-settings-title hm-psr-hidden">
						<span class="label">Custom Segments:</span>
					</label>
					<div class="ninjalytics-settings-content">
						<label class="ninjalytics-settings-cb-list-item">
							<input type="checkbox" name="enable_custom_segments" id="hm_psr_enable_custom_segments" value="1"'.(isset($reportSettings['enable_custom_segments']) && ($reportSettings['enable_custom_segments'] == 1 || ($reportSettings['enable_custom_segments'] == -1 && $reportSettings['groupby'])) ? ' checked="checked"' : '').' />
							<span class="hm-psr-cb-list-item-label">Enable custom segments</span>
						</label>
					</div>
				</div>
				
			');
		  
			for ($i = 1; $i < 6; ++$i) {
				$fieldName = 'groupby'.($i == 1 ? '' : $i);
				echo('
						<div class="ninjalytics-settings-multirow  ninjalytics-custom-segment">
							<label class="ninjalytics-settings-title" for="hm_psr_field_'.esc_attr($fieldName).'">
							   <span class="label">Segment '.((int) $i + 1).':'.($i == 1 ? '' : ' <span class="ninjalytics-pro-badge">Pro</span>').'</span>
							 </label>  
								<select name="'.esc_attr($fieldName).'" id="hm_psr_field_'.esc_attr($fieldName).'"'.disabled($i > 1, true, false).'>
								<option value="">(None)</option>');
								if ($i == 1) {
									echo('<optgroup label="Order" class="hm-psr-select-other" data-hm-psr-other-field-prefix="o_">');
					$foundGroupByField = false;
					$fieldType = 'o';
					foreach ($groupByFields as $optionId => $optionName) {
						if ($optionId[0] != $fieldType) {
							if (!$foundGroupByField && !empty($reportSettings[$fieldName]) && $reportSettings[$fieldName][0] == $fieldType) {
								$foundGroupByField = true;
								echo('<option value="'.esc_attr($reportSettings[$fieldName]).'" selected>'.esc_html(substr($reportSettings[$fieldName], 2)).'</option>');
							}
							$fieldType = $optionId[0];
							echo('</optgroup><optgroup label="'.($fieldType == 'i' ? 'Order Line Item' : 'Product').'" class="hm-psr-select-other" data-hm-psr-other-field-prefix="'.esc_attr($fieldType).'_">');
							$isOrderItemField = true;
						}
						$foundGroupByField = $foundGroupByField || $reportSettings[$fieldName] == $optionId;
						echo('<option value="'.esc_attr($optionId).'"'.($reportSettings[$fieldName] == $optionId ? ' selected="selected"' : '').'>'.esc_html($optionName).'</option>');
					}
					if (!$foundGroupByField && !empty($reportSettings[$fieldName])) {
						echo('<option value="'.esc_attr($reportSettings[$fieldName]).'" selected>'.esc_html(substr($reportSettings[$fieldName], 2)).'</option>');
					}
					echo('			</optgroup>');
								}
				echo('
							</select>');
				if ($i == 1) {
				echo('
							<button type="button" class="berrypress-btn-icon ninjalytics-segment-reset" aria-label="Clear to none" title="Clear to none" data-field="'.esc_attr($fieldName).'"><i class="berrypress-icon-close"></i><span class="berrypress-visually-hidden">Clear to none</span></button>');
				}
				echo('
						</div>
				');
			}

            ?>

			<?php echo(/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/segmentation', 'custom-segments', true )); ?>
                </div> <!-- hm_psr_tab_groupsort_panel -->
			</div>
			<div class="ninjalytics-settings-toggle">
				<div class="ags-psr-section-title">
					<h3>Fields</h3>
                    <button class="berrypress-btn-icon">
                        <i class="berrypress-icon-expand_more"></i>
                    </button>
				</div>
			<?php echo('
			
			<div id="hm_psr_tab_fields_panel" class="ags-psr-section-body">
			
			  <div class="ninjalytics-settings-box">
                    <div class="ninjalytics-settings-cb-list ninjalytics-settings-cb-list-column ninjalytics-settings-fields">
						'./* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/fields' ).'
                        <div class="ninjalytics-settings-content">
                            <div id="hm_psr_report_field_selection">
                                <div id="hm_psr_report_fields">');
			$customFields = ninjalytics_getCustomFieldNames();
			$addonFields = ninjalytics_getAddonFields();
			$noTotalFields = array('builtin::product_id', 'builtin::product_sku', 'builtin::product_name', 'builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes',
			                       'builtin::product_categories', 'order_id', 'order_status', 'order_date', 'billing_name', 'billing_phone',
			                       'builtin::publish_time', 'builtin::product_desc', 'builtin::product_excerpt', 'builtin::product_menu_order');
			foreach ($reportSettings['fields'] as $fieldId) {
				$isGroupingField =  substr($fieldId, 0, 22) == 'builtin::groupby_field';
				if (!isset($fieldOptions[$fieldId]) && !isset($customFields[$fieldId]) && !isset($addonFields[$fieldId]) && !$isGroupingField) {

					// Compatibility with pre-1.6.9 versions that didn't have the builtin:: prefix
					if (isset($fieldOptions['builtin::'.$fieldId])) {
						$fieldId = 'builtin::'.$fieldId;
					}

				}
				echo('<div'.(in_array($fieldId, array('builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes')) || substr($fieldId, 0, 11) == 'variation::' ? ' class="hm_psr_variation_field"' : ($isGroupingField ? ' class="hm_psr_'.esc_attr(substr($fieldId, 9)).'"' : '')).'>
			<input type="hidden" name="fields[]" value="'.esc_attr($fieldId).'" />
			<button type="button" onclick="ninjalytics_remove_field(this);"><span class="dashicons dashicons-no"></span></button>
			<input type="text" class="hm_psr_field_name" name="field_names['.esc_attr($fieldId).']" value="'.esc_attr( $fieldId == 'builtin::groupby_field' ? substr($reportSettings['groupby'], 2) : (isset($fieldOptions[$fieldId]) ? $fieldOptions[$fieldId] : $fieldId) ).'" readonly />
			<label class="hm_psr_total_field'.(in_array($fieldId, $noTotalFields) ? ' no-total' : '').'"><span>Total</span><input type="checkbox" name="total_fields[]" value="'.esc_attr($fieldId).'"'.(in_array($fieldId, $reportSettings['total_fields']) ? ' checked="checked"' : '').' /></label>
			<label class="hm_psr_chart_field'.(in_array($fieldId, $noTotalFields) ? ' no-chart' : '').'"><span>Chart</span><input type="checkbox" name="chart_fields[]" value="'.esc_attr($fieldId).'"'.(in_array($fieldId, $reportSettings['chart_fields']) ? ' checked="checked"' : '').' /></label>
			<label class="hm_psr_round_field'.(in_array($fieldId, $noTotalFields) ? ' no-round' : '').'"><span>Round</span><input type="checkbox" name="round_fields[]" value="'.esc_attr($fieldId).'"'.checked(in_array($fieldId, $reportSettings['round_fields']), true, false).'></label>'
				     .'</div>');
			}

			echo('</div>
			                <p class="ninjalytics-pro-feature instructions">Click and drag to the right of the field name text box to re-order fields. <span class="ninjalytics-pro-badge">Pro</span></p>
            
            <div id="hm_psr_report_add_custom_field">
                <strong>Add Field:</strong> <select id="hm_psr_custom_field" class="ags-psr-no-update">');
			foreach (array_merge(array('Built-in Fields' => $fieldOptions), $customFields) as $fieldGroupName => $fields) {
				switch ($fieldGroupName) {
					case 'Product Variation':
						$fieldGroupPrefix = 'variation::';
						break;
					case 'Order Item':
						$fieldGroupPrefix = 'order_item_total::';
						break;
				}

				$optgroupClasses = [];

				if ( $fieldGroupName == 'Product' || $fieldGroupName == 'Product Taxonomies' || $fieldGroupName == 'Product Variation' ) {
					$optgroupClasses[] = 'hm-psr-product-fields';
				}

				echo('<optgroup label="'.esc_attr($fieldGroupName.( $fieldGroupName == 'Built-in Fields' ? '' : ' [Pro]' )).'"'.($optgroupClasses ? ' class="'.esc_attr(implode(' ', $optgroupClasses)).'"' : '').(isset($fieldGroupPrefix) ? ' data-hm-psr-other-field-prefix="'.esc_attr($fieldGroupPrefix).'"' : '').'>');
				foreach ($fields as $fieldId => $fieldDisplay) {
					$fieldClasses = '';
					if (in_array($fieldId, array('builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes')) || substr($fieldId, 0, 11) == 'variation::') {
						$fieldClasses = 'hm_psr_variation_field';
					}
					if (in_array($fieldId, [
							'builtin::product_id', 'builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes', 'builtin::product_sku',
							'builtin::product_categories', 'builtin::product_price', 'builtin::product_price_with_tax',
							'builtin::product_menu_order', 'builtin::product_stock', 'builtin::publish_time', 'builtin::product_desc', 'builtin::product_excerpt'
						]
					)) {
						$fieldClasses .= (empty($fieldClasses) ? '' : ' ').'hm-psr-product-field';
					}
					if (in_array($fieldId, $noTotalFields)) {
						$fieldClasses .= (empty($fieldClasses) ? '' : ' ').'no-total-field no-round-field';
					}
					echo('<option value="'.esc_attr($fieldId).'"'.(empty($fieldClasses) ? '' : ' class="'.esc_attr($fieldClasses).'"').disabled($fieldGroupName != 'Built-in Fields' || substr($fieldDisplay, -6) == ' [Pro]', true, false).'>'.esc_html($fieldDisplay).'</option>');
				}
				echo('</optgroup>');
			}

			$addonFields = array_diff_key($addonFields, $fieldOptions, $customFields);
			if (!empty($addonFields)) {
				echo('<optgroup label="Addon Fields [Pro]">');
				foreach ($addonFields as $fieldId => $fieldData) {
					echo('<option value="'.esc_attr($fieldId).'" disabled>'.esc_html($fieldData['label']).'</option>');
				}
				echo('</optgroup>');
			}
			echo('</select>

                                    <button type="button" class="berrypress-btn berrypress-btn-secondary" id="hm-psr-button-add-field">Add</button>
                                    <button type="button" class="berrypress-btn berrypress-btn-secondary ninjalytics-pro-feature" id="ags-psr-button-add-fieldbuilder">New Calculated Field <span class="ninjalytics-pro-badge">Pro</span></button>
                                    
                                 </div>'); ?>
									
					<?php echo(/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/fields', 'add-field', true )); ?>
                                 
                            </div>
                        </div>
                    </div>
                    
                </div>
				
			  <div class="ninjalytics-settings-box ags-psr-advanced">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
                        <label class="ninjalytics-settings-title">
                           <span class="label">Refresh Fields:</span>
                         </label>
                        <div class="ninjalytics-settings-content">
							<a class="button-secondary ags-psr-button-secondary ags-psr-button-refresh" href="<?php echo(esc_url(wp_nonce_url(add_query_arg('ninjalytics_action', 'update-fields'), 'hm-psrp-update-fields').'#orders')); ?>">Refresh Fields</a>
                        </div>
                    </div>
					<?php echo(/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/fields', 'refresh-fields')); ?>
                </div>
<?php echo('
            </div> <!-- hm_psr_tab_fields_panel -->'); ?>
			</div>
			
			<div class="ninjalytics-settings-toggle">
				<div class="ags-psr-section-title">
					<h3>Table &amp; Downloads</h3>
					<label>
						<input type="checkbox" class="ags-psr-no-update">
						<span>Advanced</span>
					</label>
                    <button class="berrypress-btn-icon">
                        <i class="berrypress-icon-expand_more"></i>
                    </button>
				</div>
			<?php echo('
			<div id="hm_psr_tab_display_panel" class="ags-psr-section-body">
                
              
              <div class="ninjalytics-settings-box">
                     <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
                        <label class="ninjalytics-settings-title">
                           <span class="label">Title:</span>
						</label>
                        <div class="ninjalytics-settings-content">
                            <label>
								<input type="checkbox" name="report_title_on" value="1"'.(empty($reportSettings['report_title_on']) ? '' : ' checked="checked"').' />
								Show title in output
							</label>
                            <input type="text" name="report_title" value="' . esc_attr($reportSettings['report_title']) . '" class="hm-psr-input-fullwidth"/>

                        </div>
                    </div>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'report-title' ).'
                </div>
				
				
                <div class="ninjalytics-settings-box">
					    <label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="include_header" value="1"'.(empty($reportSettings['include_header']) ? '' : ' checked="checked"').' />
                            <span class="label">Show column names</span>
						</label>
                    
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'column-names' ).'
                </div>
                
                <div class="ninjalytics-settings-box">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" id="hm_psr_field_include_totals" name="include_totals" value="1"'.(empty($reportSettings['include_totals']) ? '' : ' checked="checked"').' />
                            <span class="label">Show column totals</span>
						</label>
                    
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'totals' ).'
                </div>
                
                
                  <div class="ninjalytics-settings-box">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
                        <label class="ninjalytics-settings-title" for="hm_sbp_field_orderby">
                           <span class="label">Sort By:</span>
                         </label>
                        <div class="ninjalytics-settings-content">
						<select name="orderby" id="hm_sbp_field_orderby">
							<option value="'.esc_attr($orderBy).'">'.esc_html($orderBy).'</option>
						</select>
						<select name="orderdir" class="hm-psr-input-fullwidth">
							<option value="asc"'.($reportSettings['orderdir'] == 'asc' ? ' selected="selected"' : '').'>ascending</option>
							<option value="desc"'.($reportSettings['orderdir'] == 'desc' ? ' selected="selected"' : '').'>descending</option>
						</select>
                        </div>
                    </div>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'sort' ).'
                </div>
				
				
                  <div class="ninjalytics-settings-box">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
                        <label class="ninjalytics-settings-title" for="hm_psr_field_format">
                           <span class="label">Download Format:</span>
                        </label>
                        <div class="ninjalytics-settings-cb-list">
                            <div id="hm_psr_field_format_settings" >
                            <select name="format" id="hm_psr_field_format">
                                <option value="csv" selected>CSV</option>
                                <option disabled>XLSX [Pro]</option>
                                <option disabled>HTML [Pro]</option>
                                <option disabled>HTML (enhanced) [Pro]</option>
                            </select>
                            <div id="ninjalytics-format_options_csv" class="ninjalytics-format_options">
                                <label class="ninjalytics-settings-title berrypress-mb-2">
                                    Separate fields with:
                                    <input type="text" name="format_csv_delimiter" maxlength="1"'.(empty($reportSettings['format_csv_delimiter']) ? '' : ' value="'.esc_attr($reportSettings['format_csv_delimiter']).'"').'>
                                </label> 
                                <label class="ninjalytics-settings-title berrypress-mb-2">
                                    Surround fields with:
                                    <input type="text" name="format_csv_surround" maxlength="1"'.(empty($reportSettings['format_csv_surround']) ? '' : ' value="'.esc_attr($reportSettings['format_csv_surround']).'"').'>
                                </label>
                                <label class="ninjalytics-settings-title berrypress-mb-2">
                                    Escape surround character with:
                                    <input type="text" name="format_csv_escape" maxlength="1"'.(empty($reportSettings['format_csv_escape']) ? '' : ' value="'.esc_attr($reportSettings['format_csv_escape']).'"').'>
                                </label>
                            </div>
                            </div>
                        </div>
                    </div>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'download-format' ).'
                </div>
                
              <div class="ninjalytics-settings-box ninjalytics-pro-feature">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
                        <label class="ninjalytics-settings-title"  for="hm_psr_field_filename">
                           <span class="label">Download Filename: <span class="ninjalytics-pro-badge">Pro</span></span>
                        </label>
                        
                        <input type="text" placeholder="Filename" name="filename" id="hm_psr_field_filename" class="hm-psr-field-300" disabled />
                        <div class="ninjalytics-settings-tooltip tooltip-hover">
                            <div class="ninjalytics-settings-tooltiptext">
                                <span>Help</span>
                                An extension (e.g. &quot;.csv&quot;) will be added automatically. See the &quot;Include title&quot; setting for dynamic field examples and other information.
                            </div>
                        </div>
                    </div>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'download-filename' ).'
                </div>  
			
			  <div class="ninjalytics-settings-box ags-psr-advanced">
                <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column has-checkbox">
                    <label class="ninjalytics-settings-title">
                       <span class="label">Row count</span>
                     </label>
                    <div class="ninjalytics-settings-content">
							<input type="checkbox" name="limit_on" value="1"'.(empty($reportSettings['limit_on']) ? '' : ' checked="checked"').' />
							Show only the first
                        <input  id="hm_psr_limit_number" type="number" name="limit" value="' . esc_attr($reportSettings['limit']) . '" min="0" step="1" class="small-text" />
							rows
                    </div>
                </div>
				 
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'row-count', true ).'
              </div>
                
                <div class="ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
                    <label for="hm_psr_field_report_css">
                       <span>Report CSS: <span class="ninjalytics-pro-badge">Pro</span></span>
                    </label>
                     <textarea id="hm_psr_field_report_css" disabled rows="11"></textarea>
                    
                     
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/table-and-downloads', 'report-css', true ).'
                </div>
			
            </div> <!-- hm_psr_tab_display_panel -->'); ?>
			</div>
			
			<div class="ninjalytics-settings-toggle">
				<div class="ags-psr-section-title">
					<h3>Chart</h3>
                    <button class="berrypress-btn-icon">
                        <i class="berrypress-icon-expand_more"></i>
                    </button>
				</div>
					<div id="hm_psr_tab_chart_panel" class="ags-psr-section-body">
						
						<div class="ninjalytics-settings-box">
							<div class="ninjalytics-settings-cb-list  ninjalytics-settings-cb-list-column">
								<label class="ninjalytics-settings-title">
								   <span class="label">Chart Type:</span>
								 </label>
								<div id="hm_psr_chart_type" class="ninjalytics-settings-content">
									<label class="ninjalytics-settings-cb-list-item">
										<input type="radio" name="chart_type" value="line_series"<?php checked($reportSettings['chart_type'] == 'line_series'); ?>>
										Line chart - series over time
									</label>
									<label class="ninjalytics-settings-cb-list-item">
										<input type="radio" name="chart_type" value="line_totals"<?php checked($reportSettings['chart_type'] == 'line_totals'); ?>>
										Line chart - totals over time
									</label>
									<label class="ninjalytics-settings-cb-list-item">
										<input type="radio" name="chart_type" value="line_rows"<?php checked($reportSettings['chart_type'] == 'line_rows'); ?>>
										Line chart - from rows
									</label>
									<label class="ninjalytics-settings-cb-list-item">
										<input type="radio" name="chart_type" value="bar"<?php checked($reportSettings['chart_type'] == 'bar'); ?>>
										Bar chart
									</label>
									<label class="ninjalytics-settings-cb-list-item">
										<input type="radio" name="chart_type" value="pie_chart" disabled>
										Pie chart
										<span class="ninjalytics-pro-badge">Pro</span>
									</label>
								</div>
							</div>
							<?php echo(/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/chart', 'chart-type' )); ?>
						</div>
						
						
					  <div class="ninjalytics-settings-box">
                          <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
							<label for="hm_sbp_field_chart_series_name" class="ninjalytics-settings-title">
							   <span class="label">Series/label field:</span>
							</label>

                          <select name="chart_series_name" id="hm_sbp_field_chart_series_name" class="hm-psr-input-fullwidth">
                              <option value="<?php echo(esc_attr($reportSettings['chart_series_name'])); ?>"><?php echo(esc_html($reportSettings['chart_series_name'])); ?></option>
                          </select>
							<div class="ninjalytics-settings-tooltip ags-psr-tooltip-modal">
								<a href="#">Important Note</a>
								<div class="ninjalytics-settings-tooltiptext ags-psr-modal">
									<button type="button" class="ags-psr-modal-close ags-psr-modal-close-top">Close</button>
                                    <h3>Important Note</h3>
									Each value of the series/label field should only occur once in the report; if there are duplicates, the chart may only reflect one of the duplicated instances.
								</div>
							 </div>
                         </div>
						<?php echo(/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/chart', 'series-field' )); ?>
						</div>
						
					</div>
			</div>
			
			<div class="ninjalytics-settings-toggle">
				<div class="ags-psr-section-title">
					<h3>Data &amp; Display</h3>
					<label>
						<input type="checkbox" class="ags-psr-no-update">
						<span>Advanced</span>
					</label>
                    <button class="berrypress-btn-icon">
                        <i class="berrypress-icon-expand_more"></i>
                    </button>
				</div>
			
			<?php echo('
			
			<div id="hm_psr_tab_advanced_panel" class="ags-psr-section-body">
                
                <div class="ninjalytics-settings-box">
						<label class="ninjalytics-settings-label-column">
						    <input id="hm_psr_field_format_amounts" type="checkbox" name="format_amounts" value="1"'.(empty($reportSettings['format_amounts']) ? '' : ' checked="checked"').' />
                            <span class="label">Display amounts with two decimal places</span>
                    </label>
                    
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'final-rounding', true ).'
                </div>
			
			  <div class="ninjalytics-settings-box ags-psr-advanced">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
                        <label class="ninjalytics-settings-title" for="hm_psr_field_time_limit">
                           <span class="label">Time Limit:</span>
                         </label>
                        <div class="ninjalytics-settings-content">
							Allow report to run for up to
							<input type="number" id="hm_psr_field_time_limit" name="time_limit" class="small-text" min="0" step="1" value="'.esc_attr($reportSettings['time_limit']).'" />
							seconds
                        </div>
                    </div>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'time-limit' ).'
                </div>
				
               
               <div class="ninjalytics-settings-box ninjalytics-pro-feature ags-psr-advanced">
                    <div class="ninjalytics-settings-multirow ninjalytics-settings-multirow-column">
                        <label class="ninjalytics-settings-title" for="hm_psr_field_time_limit2">
                           <span class="label berrypress-mb-2">Sort Buffer Size: <span class="ninjalytics-pro-badge">Pro</span></span>
						</label>
                        <div class="ninjalytics-settings-content">
							Attempt to set MySQL sort buffer size to
							<input type="number" id="hm_psr_field_time_limit2" class="small-text" disabled />
                        </div>
                    </div>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'sort-buffer-size' ).'
                </div>
                
                <div class="ninjalytics-settings-box ags-psr-advanced">
						<label class="ninjalytics-settings-label-column">
							<input disabled type="checkbox" name="report_unfiltered"'.(empty($reportSettings['report_unfiltered']) ? '' : ' checked="checked"').'>
							<span class="label">Attempt to prevent other plugins or code from changing the export query or output <span class="ninjalytics-pro-badge">Pro</span></span>
							
						</label>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'report-unfiltered' ).'
                </div>
                
                <div class="ninjalytics-settings-box ags-psr-advanced">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="remove_html"'.(empty($reportSettings['remove_html']) ? '' : ' checked="checked"').' disabled />
						    <span class="label">Remove HTML tags in the content of non-built-in fields (also applies to Group By fields; doesn\'t apply to addon fields) <span class="ninjalytics-pro-badge">Pro</span></span>
						</label>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'remove-html' ).'
                </div>
                
                <div class="ninjalytics-settings-box ags-psr-advanced">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="object_caching_disable" value="1"'.(empty($reportSettings['object_caching_disable']) ? '' : ' checked="checked"').' disabled />
                            <span class="label">Disable WordPress object caching <span class="ninjalytics-pro-badge">Pro</span></span>
						</label>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'object-caching-disable' ).'
                </div>
				
				 <div class="ninjalytics-settings-box ags-psr-advanced">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" id="hm_psr_use_wp_date" name="use_wp_date" value="1"'.(empty($reportSettings['use_wp_date']) ? '' : ' checked="checked"').' disabled />
                            <span class="label">Use WordPress date formatting functionality for dynamic date values <span class="ninjalytics-pro-badge">Pro</span></span>
						</label>
                    
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'use-wp-date' ).'
                </div>
                
                <div class="ninjalytics-settings-box ags-psr-advanced">
						<label  class="ninjalytics-settings-label-column">
							<input type="checkbox" name="intermediate_rounding" value="2"'.(empty($reportSettings['intermediate_rounding']) ? '' : ' checked="checked"').' />
                        <span class="label">Intermediate rounding</span>
                    </label>
                    
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'intermediate-rounding', true ).'
                </div>
                
                <div class="ninjalytics-settings-box ags-psr-advanced">
						<label class="ninjalytics-settings-label-column">
							<input type="checkbox" name="hm_psr_debug" value="1"'.(empty($reportSettings['hm_psr_debug']) ? '' : ' checked="checked"').' />
                            <span class="label">Enable debug mode</span>
                    </label>
                    './* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ self::docsLink( 'report-configuration/data-and-display', 'debug' ).'
                </div>
               
            </div> <!-- hm_psr_tab_advanced_panel -->'); ?>
			
			</div>
			</div>
			
			</div>

			<?php
			//ninjalytics_savePresetField();

			wp_nonce_field('hm-psr-run', 'hm-psr-nonce');

			/*echo('<div class="hm_psr_submit_wrapper">
                    <button type="submit" class="berrypress-btn berrypress-btn-primary ags-psr-button-download" name="ninjalytics_action" value="run" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'_blank\'); return true;">Download Report</button>

                    <div class="hm_psr_email_report">
				
                        <button type="submit" class="ags-psr-button-secondary" name="ninjalytics_action" value="email" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'\'); return true;">Email Report</button>
                    </div>
                </div>');*/
		?>
		
        </div>
        </form>
			<?php } else { ?>

                <div class="berrypress-card berrypress-card-nj-reports berrypress-card-100">
                    <div class="berrypress-card-header">
					<h2>Reports</h2>
                    </div>
                    <div class="berrypress-card-content">

					<table id="hm_psr_tab_presets_panel">
						<tbody>
						<?php
							$runNonce = wp_create_nonce('hm-psr-run');
							if (is_array($savedReportSettings) && count($savedReportSettings) > 1) {
								uasort($savedReportSettings, function($preset1, $preset2) {
									return strcasecmp(
										isset($preset1['preset_name']) ? $preset1['preset_name'] : '',
										isset($preset2['preset_name']) ? $preset2['preset_name'] : ''
									);
								});
								foreach ($savedReportSettings as $presetId => $preset) {
									if (!$presetId) {
										continue;
									}
							?>
								<tr>
									<td>
										<a href="?page=ninjalytics&amp;preset=<?php echo( (int) $presetId ); ?>">
											<?php echo(esc_html($preset['preset_name'])); ?>
										</a>
									</td>
									<td>
										<a href="?page=ninjalytics&amp;ninjalytics_action=run&amp;preset=<?php echo( (int) $presetId ); ?>&amp;hm-psr-nonce=<?php echo( esc_attr($runNonce) ); ?>" aria-label="Download" target="_blank" class="berrypress-btn berrypress-btn-icon">
                                            <i class="berrypress-icon-download" aria-hidden="true"></i>
                                        </a>
										<a href="?page=ninjalytics&amp;preset=<?php echo( (int) $presetId ); ?>" class="berrypress-btn berrypress-btn-icon" aria-label="Edit">
                                            <i class="berrypress-icon-edit" aria-hidden="true"></i>
                                        </a>
										<a href="?page=ninjalytics&amp;ninjalytics_action=preset-del&amp;preset=<?php echo( (int) $presetId ); ?>&amp;_wpnonce=<?php echo( esc_attr($runNonce) ); ?>" class="berrypress-btn berrypress-btn-icon" onclick="return confirm('Are you sure that you want to delete this report?');" aria-label="Remove">
                                            <i class="berrypress-icon-delete" aria-hidden="true"></i>
                                        </a>
									</td>
								</tr>
								<?php } ?>
							<?php } else { ?>
								<tr class="ninjalytics-empty">
									<td style="text-align: left; font-weight: normal;">
										<div class="nj-welcome">
											You don't have any saved reports yet. Click one of the buttons below to get started!
										</div>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>

                    </div>
                    <div id="hm_psr-buttons-wrapper" class="berrypress-card-footer">
						<a href="?page=ninjalytics&amp;preset=new" class="berrypress-btn berrypress-btn-primary">Create New Report</a>
                        <a href="#" id="ags-psr-template-modal" class="berrypress-btn berrypress-btn-primary ags-psr-ml-10">New Report From Template</a>
					</div>
				</div>


                <div class="ags-psr-modal ags-psr-modal-templates">
                    <button type="button" class="ags-psr-modal-close ags-psr-modal-close-top">Close</button>
                    <h2>Templates</h2>
                    <ul id="hm-psr-templates">
                        <?php foreach (ninjalytics_get_active_reporter()->getReportTemplates() as $templateId => $template) { ?>
                                <li class="ags-psr-template-<?php echo(esc_attr($templateId)); ?>">
                                    <a href="<?php echo( ($template['pro'] ?? false) ? '#' : esc_url('?page=ninjalytics&preset=_'.$templateId) ); ?>">
                                        <img src="<?php echo ( esc_url( plugin_dir_url( __FILE__ ) . '../images/templates/' . $template['icon'])) . '.svg'; ?>" alt="<?php echo(esc_html($template['preset_name'])); ?>">
                                        <?php echo(esc_html($template['preset_name'])); ?>
										<?php if ($template['pro'] ?? false) { ?>
										<span class="ninjalytics-pro-badge">Pro</span>
										<?php } ?>
                                    </a>
                                </li>
                        <?php } ?>
                    </ul>
                    <div class="berrypress-text-center">
                        <button type="button" class="berrypress-btn berrypress-btn-secondary ags-psr-modal-close">Cancel</button>
                    </div>

                </div>
			<?php } ?>
<script>window.ninjalytics_fieldbuilder = JSON.parse(atob("<?php echo(base64_encode(get_option('ninjalytics_fieldbuilder', '[]'))); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>"));</script>

<?php
	}
	
	private function renderAboutPage() {
		?>
		<div class="ninjalytics-about-page">
			<div class="about-header">
				<h1><?php esc_html_e('About Ninjalytics', 'product-sales-report-for-woocommerce'); ?></h1>
                <h2 class="about-description berrypress-fs-16 berrypress-fw-medium berrypress-mb-3">
					<?php esc_html_e('The new, enhanced version of', 'product-sales-report-for-woocommerce'); ?> <strong><?php esc_html_e('Product Sales Report for WooCommerce', 'product-sales-report-for-woocommerce'); ?></strong>
				</h2>
			</div>
			
			<div class="about-section">
				<h3 class="berrypress-fs-18"><?php esc_html_e('Welcome to Ninjalytics', 'product-sales-report-for-woocommerce'); ?></h3>
				<p>
					<?php esc_html_e('Ninjalytics is the new, enhanced version of the Product Sales Report for WooCommerce plugin. We\'ve rebranded and expanded the functionality to give you more powerful sales reporting and help you make smarter business decisions.', 'product-sales-report-for-woocommerce'); ?>
				</p>
			</div>
			
			<div class="about-section">
				<h3><?php esc_html_e('What\'s New in Ninjalytics?', 'product-sales-report-for-woocommerce'); ?></h3>
				<ul class="ninjalytics-feature-list">
					<li><?php esc_html_e('Live report table and chart previews', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Expanded report fields and data options', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Quick report creation from pre-built templates', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Detailed product sales reports including variations and shipping data', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Modern, intuitive reporting interface', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Interactive line and bar charts for data visualization', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Save and reuse multiple custom report configurations', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Flexible date range selection with relative and absolute time ranges', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Custom data segmentation and grouping options', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Row count limits - show only the top X results', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Customizable CSV export settings (delimiters, quotes, escape characters)', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Support for both WooCommerce and Easy Digital Downloads (beta)', 'product-sales-report-for-woocommerce'); ?></li>
				</ul>
			</div>
			
			<div class="about-section">
				<h3><?php esc_html_e('Rolling Back if You Encounter Issues', 'product-sales-report-for-woocommerce'); ?></h3>
				<p><?php esc_html_e('If you run into problems after updating, you can easily roll back to a previous version:', 'product-sales-report-for-woocommerce'); ?></p>
				<ol>
					<li><?php esc_html_e('Install and activate the free', 'product-sales-report-for-woocommerce'); ?> <a href="https://pl.wordpress.org/plugins/wp-rollback/" class="berrypress-link" target="_blank" rel="noopener"><?php esc_html_e('WP Rollback plugin', 'product-sales-report-for-woocommerce'); ?></a></li>
					<li><?php esc_html_e('Go to Plugins → find Ninjalytics and click "Rollback"', 'product-sales-report-for-woocommerce'); ?></li>
					<li><?php esc_html_e('Select a stable earlier version and confirm', 'product-sales-report-for-woocommerce'); ?></li>
				</ol>
				<p><?php esc_html_e('You can also download previous versions directly from', 'product-sales-report-for-woocommerce'); ?> <a href="https://wordpress.org/plugins/product-sales-report-for-woocommerce/#developers" class="berrypress-link" target="_blank" rel="noopener"><?php esc_html_e('WordPress.org', 'product-sales-report-for-woocommerce'); ?></a> <?php esc_html_e('or check the', 'product-sales-report-for-woocommerce'); ?> <a href="https://wordpress.org/plugins/product-sales-report-for-woocommerce/#developers" class="berrypress-link" target="_blank" rel="noopener"><?php esc_html_e('changelog', 'product-sales-report-for-woocommerce'); ?></a> <?php esc_html_e('for version history.', 'product-sales-report-for-woocommerce'); ?></p>
			</div>
			
			<div class="about-section">
				<h3><?php esc_html_e('Your Feedback Matters', 'product-sales-report-for-woocommerce'); ?></h3>
				<p class="berrypress-mb-4"><?php esc_html_e('We\'d love to hear your feedback and see your reviews. If you experience any issues, please let us know:', 'product-sales-report-for-woocommerce'); ?></p>
				<div class="ninjalytics-support-resources berrypress-mb-3">
					<div class="ninjalytics-support-links">
						<a href="https://wordpress.org/support/plugin/product-sales-report-for-woocommerce/" target="_blank" class="berrypress-btn berrypress-btn-primary">
							<?php esc_html_e('Support Forum', 'product-sales-report-for-woocommerce'); ?>
						</a>
						<a href="https://berrypress.com/docs/ninjalytics/" target="_blank" class="berrypress-btn berrypress-btn-secondary">
							<?php esc_html_e('Documentation', 'product-sales-report-for-woocommerce'); ?>
						</a>
					</div>
				</div>
				<p><?php esc_html_e('Your input helps us improve Ninjalytics and continue delivering better solutions for your store.', 'product-sales-report-for-woocommerce'); ?></p>
			</div>

		</div>
		<?php
	}
	
	private function renderAddonsPage() {
		include_once(plugin_dir_path(__FILE__) . '../includes/berrypress-admin-framework/addons-page.php');
	}
	
	private function renderAboutProPage() {
		?>
		<div class="ninjalytics-pro-page">
            <div class="about-header">
                <h1><?php esc_html_e('Ninjalytics Pro', 'product-sales-report-for-woocommerce'); ?></h1>
                <h2 class="about-description berrypress-fs-16 berrypress-fw-medium berrypress-mb-3">
                    <?php esc_html_e('Take your product sales reporting to the next level with advanced features and premium support.', 'product-sales-report-for-woocommerce'); ?>
                </h2>
            </div>

            <div class="about-section">
                <h3><?php esc_html_e('Featuring:', 'product-sales-report-for-woocommerce'); ?></h3>
                <ul class="ninjalytics-feature-list">
                    <li><?php esc_html_e('More built-in fields', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Product and variation meta fields, product taxonomy terms, and total order item meta fields', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Custom calculated fields', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Additional order and product filtering options, including meta field filtering', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Download reports in Excel (XLSX) and HTML formats', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Customer filtering based on user role or meta field', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('More custom segments', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Create pie charts', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('...and more!', 'product-sales-report-for-woocommerce'); ?></li>
                </ul>
            </div>
            <div class="berrypress-box-cta">
                <h2 class="berrypress-fs-18">
	                <?php
	                printf(
	                /* translators: 1: opening span tag, 2: highlighted offer, 3: closing span tag */
		                esc_html__(
			                'We’ve got a special offer for Ninjalytics users – enjoy %1$s%2$s%3$s your Pro plan!',
			                'product-sales-report-for-woocommerce'
		                ),
		                '<span class="berrypress-primary-color">',
		                esc_html__( '15% OFF', 'product-sales-report-for-woocommerce' ),
		                '</span>'
	                );
	                ?>
                </h2>
                <p class="berrypress-mb-3"><?php esc_html_e('	Unlock advanced reporting features and take your store analytics to the next level with Ninjalytics Pro.', 'product-sales-report-for-woocommerce'); ?></p>

                <div class="berrypress-coupon berrypress-mb-2">
		            <?php esc_html_e('Coupon Code', 'product-sales-report-for-woocommerce') ?>: <strong id="couponCode">NINJALYTICS15</strong>
                </div>

                <p>
                    <a href="https://berrypress.com/product/woocommerce/ninjalytics/" target="_blank" class="berrypress-btn berrypress-btn-primary">
                    <?php esc_html_e('Purchase Pro', 'product-sales-report-for-woocommerce'); ?>
                    </a>
                </p>
            </div>
        </div>
		<?php
	}
}
