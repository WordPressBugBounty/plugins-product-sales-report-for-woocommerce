<?php

namespace NinjalyticsFree;

require_once __DIR__ . '/new-report.php';
require_once __DIR__ . '/settings.php';

use NinjalyticsFree\NewReportPageTrait;
use NinjalyticsFree\SettingsPageTrait;
use NinjalyticsFree\Admin\Page as BerryPressPage;
use NinjalyticsFree\Reporters\PlatformFeatures;

defined( 'ABSPATH' ) || exit;

class AdminPage extends BerryPressPage {
	use NewReportPageTrait;
	use SettingsPageTrait;

	protected $table, $cartData, $docsUrl = 'https://berrypress.com/docs/ninjalytics/', $supportUrl = 'https://wordpress.org/support/plugin/product-sales-report-for-woocommerce/', $reviewUrl = 'https://wordpress.org/support/plugin/product-sales-report-for-woocommerce/reviews/#new-post';

	function __construct() {
		/*
		add_action( get_plugin_page_hookname( 'ninjalytics', 'woocommerce' ), function () {
			$this->render();
		} );
		*/

		add_filter( 'berrypress_admin_page_display_sidebar', function () {
			return false;
		} );

	}

	public static function getUrl( array $args = [] ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=ninjalytics-free' ) );
	}

    public static function proBadge() {
        echo '<span class="ninjalytics-pro-badge">' . esc_html__( 'Pro', 'product-sales-report-for-woocommerce' ) . '</span>';
    }

	public static function
	docsLink(
		$page, $anchor = '', $important = false
	) {
		echo('<a href="' . esc_url( 'https://berrypress.com/docs/ninjalytics/' . $page . ( $anchor ? '#' . $anchor : '' ) ) . '"
        target="_blank"
        data-bp-tooltip-position="top"
        data-bp-tooltip="' . ( $important
				? esc_html__( 'Read documentation for important details', 'product-sales-report-for-woocommerce' )
				: esc_html__( 'Read documentation', 'product-sales-report-for-woocommerce' )
			) . '"
        class="berrypress-doc-note ninjalytics-doc-note' . ( $important ? ' ninjalytics-docs-link-important' : '' ) . '">
            <span class="berrypress-visually-hidden">Note</span>
            <i class="berrypress-icon-external-link"></i>
        </a>');
	}

	public function getNav() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- just checking the page we're on
		$current_page = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'reports';

		$nav = [
			[
				'link'   => self::getUrl(),
				'icon'   => 'berrypress-icon-bar-chart',
				'title'  => __( 'Reports', 'product-sales-report-for-woocommerce' ),
				'active' => ( $current_page === 'reports' || $current_page === '' )
			],
			[
				'link'   => self::getUrl( [ 'tab' => 'new-report' ] ),
				'icon'   => 'berrypress-icon-add',
				'title'  => __( 'New Report', 'product-sales-report-for-woocommerce' ),
				'active' => ( $current_page === 'new-report' )
			],
			[
				'link'   => self::getUrl( [ 'tab' => 'settings' ] ),
				'icon'   => 'berrypress-icon-settings',
				'title'  => __( 'Settings', 'product-sales-report-for-woocommerce' ),
				'active' => ( $current_page === 'settings' )
			],
			[
				'link'   => self::getUrl( [ 'tab' => 'addons' ] ),
				'icon'   => 'berrypress-icon-addons',
				'title'  => __( 'Addons', 'product-sales-report-for-woocommerce' ),
				'active' => ( $current_page === 'addons' )
			],
			[
				'link'   => self::getUrl( [ 'tab' => 'about' ] ),
				'icon'   => 'berrypress-icon-about',
				'title'  => __( 'About', 'product-sales-report-for-woocommerce' ),
				'active' => ( $current_page === 'about' )
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

	public function getTopNav() {
		return self::getNav();
	}

    public function getAboveHeaderHtml() {
	    $product_url = self::getProductUrl();

	    // Check for Black Friday promotion
	    $black_friday_notice = \NinjalyticsFree\Admin\Page::get_black_friday_notice( 'https://berrypress.com/shop/', 'Ninjalytics Pro' );
	    if ( !empty( $black_friday_notice ) ) {
		    return $black_friday_notice;
	    }
	    // Default upgrade notice
	    return sprintf(
		    '<div class="berrypress-top-bar"><h2>%s <a class="berrypress-link" href="%s">%s<i class="berrypress-icon-filled berrypress-icon-keyboard_double_arrow_right"></i></a></h2></div>',
		    sprintf(
				// translators: %1$s, %2$s, %3$s, %4$s = <a> tags
				esc_html__( 'The free version of Ninjalytics gives you the essentials. Go Pro for next-level reports, custom fields, a mobile app for %1$sAndroid%2$s and %3$siOS%4$s, and premium features.', 'product-sales-report-for-woocommerce' ),
				'<a class="berrypress-link berrypress-text-color" href="https://play.google.com/store/apps/details?id=com.berrypress.ninjalytics" target="_blank">',
				'</a>',
                '<a class="berrypress-link berrypress-text-color" href="https://apps.apple.com/se/app/ninjalytics/id6757487864?l=en-GB" target="_blank">',
				'</a>'
			),
		    esc_url( $product_url ),
		    esc_html__( 'Upgrade', 'product-sales-report-for-woocommerce' )
	    );
    }

    public function getLogoUrl() {
	    return plugin_dir_url( dirname( __DIR__ ) . '/ninjalytics.php' ) . 'images/ninjalytics.png';
	}
	public function getProductUrl() {
		return 'https://berrypress.com/product/woocommerce/ninjalytics/?utm_campaign=upsell&source=ninjalytics-free-plugin';
	}
	public function getHeaderText() {
		return __( 'Ninjalytics', 'product-sales-report-for-woocommerce' );
	}

	public function body() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- just checking the page we're on
		$current_page = isset($_GET['tab']) ? sanitize_text_field(wp_unslash( $_GET['tab']) ) : 'reports';
		
		// Route to appropriate page content
		switch ( $current_page ) {
			case 'new-report':
				$this->renderNewReportPage();
				break;
			case 'settings':
				$this->renderSettingsPage();
				break;
			case 'about':
				$this->renderAboutPage();
				break;
			case 'addons':
				include __DIR__ . '/../includes/berrypress-admin-framework/addons-page.php';
				break;
			case 'about-pro':
				$this->renderAboutProPage();
				break;
			default:
				$this->renderReportsPage();
				break;
		}
	}

	/**
	 * Renders comparison operator dropdown (=, !=, <, <=, >, >=, BETWEEN, NOTEXISTS)
	 *
	 * @param string $selectedOperator Currently selected operator
	 * @param string $fieldName Name attribute for the select element
	 * @param string $fieldId Optional ID attribute for the select element
	 * @param string $additionalClasses Optional additional CSS classes
	 * @param string $style Optional inline style attribute
	 */
    private function renderComparisonOperators() {
        ?>
        <select disabled>
            <option><?php esc_html_e( 'equal to', 'product-sales-report-for-woocommerce' ); ?></option>
            <option><?php esc_html_e( 'not equal to', 'product-sales-report-for-woocommerce' ); ?></option>
            <option><?php esc_html_e( 'less than', 'product-sales-report-for-woocommerce' ); ?></option>
            <option><?php esc_html_e( 'less than or equal to', 'product-sales-report-for-woocommerce' ); ?></option>
            <option><?php esc_html_e( 'greater than', 'product-sales-report-for-woocommerce' ); ?></option>
            <option><?php esc_html_e( 'greater than or equal to', 'product-sales-report-for-woocommerce' ); ?></option>
            <option><?php esc_html_e( 'between', 'product-sales-report-for-woocommerce' ); ?></option>
            <option><?php esc_html_e( 'does not exist', 'product-sales-report-for-woocommerce' ); ?></option>
        </select>
        <?php
    }

	/**
	 * Renders section header with title, optional advanced checkbox, and expand button
	 *
	 * @param string $title Section title
	 * @param bool   $hasAdvanced Whether to show "Advanced" checkbox
	 * @param string $advancedName Optional form field name for saving advanced state (e.g. 'advanced_table_downloads')
	 * @param array  $reportSettings Current report settings (used when $advancedName is set)
	 */
	private function renderSectionHeader( $title, $hasAdvanced = true, $advancedName = '', $reportSettings = array() ) {
		?>
		<div class="ninjalytics-section-title">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( $hasAdvanced ) { ?>
				<label>
					<input type="checkbox" class="ninjalytics-no-update"<?php echo $advancedName ? ' name="' . esc_attr( $advancedName ) . '" value="1"' . checked( ! empty( $reportSettings[ $advancedName ] ), true, false ) : ''; ?>>
					<span><?php esc_html_e( 'Advanced', 'product-sales-report-for-woocommerce' ); ?></span>
				</label>
			<?php } ?>
			<button class="berrypress-btn-icon" type="button">
				<i class="berrypress-icon-expand_more"></i>
			</button>
		</div>
		<?php
	}
	
	private function renderPrimaryProductsFilter($reporter, $reportSettings) {
		?>
		<div class="ninjalytics-switch-conditional-group">
			<div class="berrypress-field">
				<input type="radio" name="products" id="ninjalytics-all-products"
					   value="all" <?php echo $reportSettings['products'] == 'all' ? ' checked="checked"' : ''; ?> />
				<label for="ninjalytics-all-products"><?php esc_html_e( 'All products', 'product-sales-report-for-woocommerce' ) ?></label>
			</div>

			<div class="ninjalytics-field-switch-conditional">
				<div class="berrypress-field">
					<input type="radio" name="products" id="ninjalytics-cat-products"
						   value="cats" <?php echo $reportSettings['products'] == 'cats' ? ' checked="checked"' : ''; ?> data-toggle-key="products_in_categories" />
					<label for="ninjalytics-cat-products"><?php esc_html_e( 'Products in categories', 'product-sales-report-for-woocommerce' ) ?></label>
				</div>
				<div class="ninjalytics-field-child"  data-toggle-panel="products_in_categories">
					<!--  Product Categories -->
					<ul class="ninjalytics-terms-checklist">
						<?php
						wp_terms_checklist( 0, array(
							'selected_cats' => $reportSettings['product_cats'],
							'taxonomy'      => $reporter->productCategoryTaxonomy,
							'checked_ontop' => false
						) );
						?>
					</ul>
				</div>
			</div>

			<div class="ninjalytics-field-switch-conditional">
				<div class="berrypress-field">
					<input type="radio" name="products" id="ninjalytics-products-ids"
						   value="ids" <?php echo $reportSettings['products'] == 'ids' ? ' checked="checked"' : ''; ?> data-toggle-key="specific_products" />
					<label for="ninjalytics-products-ids"> <?php esc_html_e( 'Specific products', 'product-sales-report-for-woocommerce' );
						self::docsLink( 'report-configuration/products' ); ?></label>
				</div>

				<div class="ninjalytics-field-child" data-toggle-panel="specific_products">
					<label class="berrypress-multiple-dropdown-container ninjalytics-product-select-container">
						<select id="ninjalytics-product-ids"
								class="ninjalytics-product-select" multiple="multiple"
								data-allow-clear="true">
							<?php
							$productIdsValue      = '';
							$sanitizedProductIds  = empty( $reportSettings['product_ids'] ) ? [] : array_map( 'intval', array_map( 'trim', explode( ',', $reportSettings['product_ids'] ) ) );
							if ( $sanitizedProductIds ) {
								$productIdsValue = implode( ',', $sanitizedProductIds );
								foreach ( $sanitizedProductIds as $productId ) {
									$product = wc_get_product( $productId );

									$productLabel = $product
										? $product->get_formatted_name()
										// translators: %d: Product ID
										: sprintf( __( 'Product #%d (not found)', 'product-sales-report-for-woocommerce' ), $productId );

									echo '<option value="' . ( (int) $productId ) . '" selected="selected">' . esc_html( $productLabel ) . '</option>';
								}
							}

							?>
						</select>
						<input type="hidden" name="product_ids"
							   id="ninjalytics-product-ids-input"
							   value="<?php echo esc_attr( $productIdsValue ); ?>"/>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the Display/Table & Downloads section
	 *
	 * @param array  $reportSettings Current report settings
	 * @param string $orderBy Order by field value
	 * @param bool   $canEditReportCSS Whether user can edit report CSS
	 */
	private function renderDisplaySection( $reportSettings, $orderBy ) {
		?>
		<div class="ninjalytics-settings-toggle">
			<?php $this->renderSectionHeader( __( 'Table & Downloads', 'product-sales-report-for-woocommerce' ), true, 'advanced_table_downloads', $reportSettings ); ?>
			
			<div class="ninjalytics-section-body">
                <div class="ninjalytics-group-title">
                    <?php esc_html_e( 'Display', 'product-sales-report-for-woocommerce' ); ?>
                </div>


                <div class="berrypress-field berrypress-field-flex berrypress-field-align-center">
                    <label for="hm_sbp_field_orderby"><?php esc_html_e( 'Sort by field', 'product-sales-report-for-woocommerce' ); ?></label>
                    <select name="orderby" id="hm_sbp_field_orderby">
                        <option value="<?php echo esc_attr( $orderBy ); ?>"><?php echo esc_html( $orderBy ); ?></option>
                    </select>
					<?php self::docsLink( 'report-configuration/table-and-downloads', 'sort' ); ?>
                </div>

                <div class="berrypress-field berrypress-field-flex berrypress-field-align-center">
                    <label for="ninjalytics-orderdir"><?php esc_html_e( 'Order by', 'product-sales-report-for-woocommerce' ); ?></label>
                    <select id="ninjalytics-orderdir" name="orderdir">
                        <option value="asc"<?php selected( $reportSettings['orderdir'], 'asc' ); ?>><?php esc_html_e( 'ascending', 'product-sales-report-for-woocommerce' ); ?></option>
                        <option value="desc"<?php selected( $reportSettings['orderdir'], 'desc' ); ?>><?php esc_html_e( 'descending', 'product-sales-report-for-woocommerce' ); ?></option>
                    </select>
                </div>


                <div class="ninjalytics-field-switch-conditional berrypress-mt-3">
                    <div class="berrypress-field">
                        <input type="checkbox" id="ninjalytcs-table-report-title-on" name="report_title_on"
                               value="1"<?php checked( ! empty( $reportSettings['report_title_on'] ) ); ?> data-toggle-key="show_title_in_output" />
                        <label for="ninjalytcs-table-report-title-on"><?php esc_html_e( 'Show title in output', 'product-sales-report-for-woocommerce' ); ?>
                            <?php self::docsLink( 'report-configuration/table-and-downloads', 'report-title' ); ?>
                        </label>

                    </div>
                    <div class="ninjalytics-field-child" data-toggle-panel="show_title_in_output">
                        <label class="berrypress-field">
                            <span class="label berrypress-visually-hidden"><?php esc_html_e( 'Title', 'product-sales-report-for-woocommerce' ); ?> </span>
                            <input type="text" name="report_title"
                                   value="<?php echo esc_attr( $reportSettings['report_title'] ); ?>"
                                   class="hm-psr-input-fullwidth"/>
                        </label>
                    </div>
                </div>

                <div class="berrypress-field">
                    <input type="checkbox" id="ninjalytics-table-include-header" name="include_header"
                           value="1"<?php checked( ! empty( $reportSettings['include_header'] ) ); ?> />
                     <label for="ninjalytics-table-include-header"><?php esc_html_e( 'Show column names', 'product-sales-report-for-woocommerce' ); ?>
					<?php self::docsLink( 'report-configuration/table-and-downloads', 'column-names' ); ?> </label>
                </div>

                <div class="berrypress-field">
                    <input type="checkbox" id="hm_psr_field_include_totals"
                           name="include_totals"
                           value="1"<?php checked( ! empty( $reportSettings['include_totals'] ) ); ?> />
                     <label for="hm_psr_field_include_totals"><?php esc_html_e( 'Show column totals', 'product-sales-report-for-woocommerce' ); ?>
					 <?php self::docsLink( 'report-configuration/table-and-downloads', 'totals' ); ?></label>
                </div>


                <div class="ninjalytics-field-switch-conditional">
                    <div class="berrypress-field">
                        <input type="checkbox" id="ninjalytics-rows-limit-on" name="limit_on"
                               value="1"<?php checked( ! empty( $reportSettings['limit_on'] ) ); ?> data-toggle-key="limit_number_of_rows" />
                        <label for="ninjalytics-rows-limit-on"><?php esc_html_e( 'Limit number of rows', 'product-sales-report-for-woocommerce' ); ?>
						<?php self::docsLink( 'report-configuration/table-and-downloads', 'row-count', true ); ?></label>
                    </div>
                    <div class="ninjalytics-field-child" data-toggle-panel="limit_number_of_rows">
                        <div class="berrypress-field berrypress-field-align-center">
                            <label for="hm_psr_limit_number"><?php esc_html_e( 'Maximum rows to show', 'product-sales-report-for-woocommerce' ); ?> </label>
                            <input id="hm_psr_limit_number" type="number" name="limit"
                                    value="<?php echo esc_attr( $reportSettings['limit'] ); ?>" min="0"
                                    step="1"
                                    class="small-text"/>
                        </div>
                    </div>

                </div>


                <div class="ninjalytics-group-title berrypress-mt-4">
					<?php esc_html_e( 'Download settings', 'product-sales-report-for-woocommerce' ); ?>
                </div>

                <div class="berrypress-field berrypress-field-flex berrypress-field-align-center ninjalytics-pro-feature">
                    <label for="hm_psr_field_filename"><?php esc_html_e( 'Download filename', 'product-sales-report-for-woocommerce' ); ?>
                        <?php self::proBadge() ?></label>
                    <input type="text" name="filename" id="hm_psr_field_filename"
                           class="ninjalytics-select-fw"
                           disabled/>
	                <?php self::docsLink( 'report-configuration/table-and-downloads', 'download-filename' ); ?>
                </div>

                <div class="ninjalytics-field-formats berrypress-field berrypress-field-flex berrypress-field-align-center">
                    <label for="hm_psr_field_format"><?php esc_html_e( 'Download format', 'product-sales-report-for-woocommerce' ); ?></label>
                    <select name="format" id="hm_psr_field_format">
                        <option value="csv" selected>CSV</option>
                        <option disabled><?php esc_html_e( 'XLSX', 'product-sales-report-for-woocommerce' ); ?> <?php self::proBadge() ?></option>
                        <option disabled><?php esc_html_e( 'HTML', 'product-sales-report-for-woocommerce' ); ?> <?php self::proBadge() ?></option>
                        <option disabled><?php esc_html_e( 'HTML (enhanced)', 'product-sales-report-for-woocommerce' ); ?> <?php self::proBadge() ?></option>
                    </select>
	                <?php self::docsLink( 'report-configuration/table-and-downloads', 'download-format' ); ?>
                    <div id="ninjalytics-format_options_csv" class="ninjalytics-format_options">
                        <label>
                            <span><?php esc_html_e( 'Separate fields with:', 'product-sales-report-for-woocommerce' ); ?></span>
                            <input type="text" name="format_csv_delimiter"
                                   maxlength="1"<?php echo ! empty( $reportSettings['format_csv_delimiter'] ) ? ' value="' . esc_attr( $reportSettings['format_csv_delimiter'] ) . '"' : ''; ?>>
                        </label>
                        <label>
			                <span><?php esc_html_e( 'Surround fields with:', 'product-sales-report-for-woocommerce' ); ?></span>
                            <input type="text" name="format_csv_surround"
                                   maxlength="1"<?php echo ! empty( $reportSettings['format_csv_surround'] ) ? ' value="' . esc_attr( $reportSettings['format_csv_surround'] ) . '"' : ''; ?>>
                        </label>
                        <label>
			                <span><?php esc_html_e( 'Escape surround character with:', 'product-sales-report-for-woocommerce' ); ?></span>
                            <input type="text" name="format_csv_escape"
                                   maxlength="1"<?php echo ! empty( $reportSettings['format_csv_escape'] ) ? ' value="' . esc_attr( $reportSettings['format_csv_escape'] ) . '"' : ''; ?>>
                        </label>
                    </div>
                </div>

				<div class="ninjalytics-setting-advanced">
                    <!--TODO show only when HTML format selected? -->
                    <div class="ninjalytics-group-title ninjalytics-pro-feature berrypress-mt-4">
                        <?php esc_html_e( 'Report CSS', 'product-sales-report-for-woocommerce' ); ?>
                        <?php self::proBadge() ?>
                        <?php self::docsLink( 'report-configuration/table-and-downloads', 'report-css', true ); ?>
                    </div>

                    <div class="berrypress-field ninjalytics-pro-feature berrypress-field-textarea">
                        <label for="hm_psr_field_report_css" class="berrypress-visually-hidden"><?php esc_html_e( 'Report CSS', 'product-sales-report-for-woocommerce' ); ?></label>
                        <textarea id="hm_psr_field_report_css" name="report_css"
                                  rows="11" disabled></textarea>
                    </div>

				</div>

			</div> <!-- /ninjalytics-section-body -->
		</div> <!-- /ninjalytics-settings-toggle (Table & Downloads) -->
		<?php
	}

	/**
	 * Renders the Chart section
	 *
	 * @param array $reportSettings Current report settings
	 */
	private function renderChartSection( $reportSettings ) {
		?>

		<div class="ninjalytics-settings-toggle">
			<?php $this->renderSectionHeader( __( 'Chart', 'product-sales-report-for-woocommerce' ), false ); ?>
			<div class="ninjalytics-section-body">

                <div class="ninjalytics-group-title">
	                <?php esc_html_e( 'Chart Type:', 'product-sales-report-for-woocommerce' ); ?>
	                <?php self::docsLink( 'report-configuration/chart', 'chart-type' ); ?>
                </div>

                <fieldset id="hm_psr_chart_type">

                    <legend class="berrypress-visually-hidden"><?php esc_html_e( 'Chart type', 'product-sales-report-for-woocommerce' ); ?></legend>

                    <label class="berrypress-field">
                        <input type="radio" name="chart_type"
                               value="line_series"<?php checked( $reportSettings['chart_type'] == 'line_series' ); ?>>
                        <span class="label"><?php esc_html_e( 'Line chart - series over time', 'product-sales-report-for-woocommerce' ); ?></span>
                    </label>
                    <label class="berrypress-field">
                        <input type="radio" name="chart_type"
                               value="line_totals"<?php checked( $reportSettings['chart_type'] == 'line_totals' ); ?>>
                        <span class="label"><?php esc_html_e( 'Line chart - totals over time', 'product-sales-report-for-woocommerce' ); ?></span>
                    </label>
                    <label class="berrypress-field">
                        <input type="radio" name="chart_type"
                               value="line_rows"<?php checked( $reportSettings['chart_type'] == 'line_rows' ); ?>>
                        <span class="label"><?php esc_html_e( 'Line chart - from rows', 'product-sales-report-for-woocommerce' ); ?></span>
                    </label>
                    <label class="berrypress-field">
                        <input type="radio" name="chart_type"
                               value="bar"<?php checked( $reportSettings['chart_type'] == 'bar' ); ?>>
                        <span class="label"><?php esc_html_e( 'Bar chart', 'product-sales-report-for-woocommerce' ); ?></span>
                    </label>
                    <label class="berrypress-field ninjalytics-pro-feature">
                        <input type="radio" name="chart_type" disabled>
                        <span class="label"><?php esc_html_e( 'Pie chart', 'product-sales-report-for-woocommerce' ); ?>
                            <?php self::proBadge() ?></span>
                    </label>
                </fieldset>

                <div class="berrypress-field berrypress-field-flex berrypress-field-align-center berrypress-mt-3">
                    <label for="hm_sbp_field_chart_series_name">
                        <?php esc_html_e( 'Series/label field:', 'product-sales-report-for-woocommerce' ); ?>
                    </label>
                    <select name="chart_series_name" id="hm_sbp_field_chart_series_name"
                            class="hm-psr-input-fullwidth">
                        <option value="<?php echo esc_attr( $reportSettings['chart_series_name'] ); ?>"
                                selected><?php echo esc_html( $reportSettings['chart_series_name'] ); ?></option>
                    </select>
	                <?php self::docsLink( 'report-configuration/chart', 'series-field' ); ?>
                </div>


			</div> <!-- /ninjalytics-section-body -->
		</div> <!-- /ninjalytics-settings-toggle (Chart) -->
		<?php
	}

	/**
	 * Renders the Data & Display (Advanced) section
	 *
	 * @param array $reportSettings Current report settings
	 */
	private function renderAdvancedSection( $reportSettings ) {
		?>
		<div class="ninjalytics-settings-toggle">
			<?php $this->renderSectionHeader( __( 'Data & Display', 'product-sales-report-for-woocommerce' ), true, 'advanced_data_display', $reportSettings ); ?>

			<div class="ninjalytics-section-body">

                <div class="ninjalytics-group-title">
                    <?php esc_html_e( 'Display', 'product-sales-report-for-woocommerce' ); ?>
                </div>

                <div class="berrypress-field">
                    <input id="hm_psr_field_format_amounts" type="checkbox"
                           name="format_amounts"
                           value="1"<?php checked( ! empty( $reportSettings['format_amounts'] ) ); ?> />
                    <label for="hm_psr_field_format_amounts" >
							<?php esc_html_e( 'Display amounts with two decimal places', 'product-sales-report-for-woocommerce' ); ?>
							<?php self::docsLink( 'report-configuration/data-and-display', 'final-rounding', true ); ?>
						</label>
                </div>


                <div class="ninjalytics-group-title berrypress-mt-4 ninjalytics-setting-advanced">
					<?php esc_html_e( 'Time limit', 'product-sales-report-for-woocommerce' ); ?>
					<?php self::docsLink( 'report-configuration/data-and-display', 'time-limit' ); ?>
                </div>

                <div class="berrypress-field berrypress-field-align-center ninjalytics-setting-advanced">
                    <label for="hm_psr_field_time_limit" class="berrypress-visually-hidden"><?php esc_html_e( 'Time limit', 'product-sales-report-for-woocommerce' ); ?></label>
					<?php esc_html_e( 'Allow report to run for up to', 'product-sales-report-for-woocommerce' ); ?>
                    <span><input type="number" id="hm_psr_field_time_limit" name="time_limit"
                           class="small-text" min="0" step="1"
                           value="<?php echo esc_attr( $reportSettings['time_limit'] ); ?>"/>
					<?php esc_html_e( 'seconds', 'product-sales-report-for-woocommerce' ); ?></span>
                </div>


                <div class="ninjalytics-group-title berrypress-mt-4 ninjalytics-setting-advanced ninjalytics-pro-feature">
	                <?php esc_html_e( 'Sort buffer size', 'product-sales-report-for-woocommerce' ); ?>
                    <?php self::proBadge() ?>
                </div>

                <div class="berrypress-field berrypress-field-flex berrypress-field-align-center ninjalytics-setting-advanced ninjalytics-pro-feature">
                    <label for="hm_psr_field_time_limit2" ><?php esc_html_e( 'Attempt to set MySQL sort buffer size to', 'product-sales-report-for-woocommerce' ); ?></label>
                    <span><input type="number" id="hm_psr_field_time_limit2"
                           name="db_sort_buffer_size" class="small-text" min="0" step="1"  disabled/>
	                <?php self::docsLink( 'report-configuration/data-and-display', 'sort-buffer-size' ); ?></span>
                </div>


                <div class="ninjalytics-group-title berrypress-mt-4 ninjalytics-setting-advanced">
					<?php esc_html_e( 'Advanced', 'product-sales-report-for-woocommerce' ); ?>
                </div>

                <div class="berrypress-field ninjalytics-setting-advanced ninjalytics-pro-feature">
                    <input type="checkbox" id="ninjalytics-report-unfiltered"
                           name="report_unfiltered"  disabled>
                    <label for="ninjalytics-report-unfiltered">
							<?php esc_html_e( 'Attempt to prevent other plugins or code from changing the export query or output', 'product-sales-report-for-woocommerce' ); ?>
                            <?php self::proBadge() ?>
							<?php self::docsLink( 'report-configuration/data-and-display', 'report-unfiltered' ); ?>
						</label>
                </div>

                <div class="berrypress-field ninjalytics-setting-advanced ninjalytics-pro-feature">
                    <input type="checkbox" id="ninjalytics-remove-html"
                           name="remove_html" disabled>
                    <label for="ninjalytics-remove-html">
							<?php
							if ( $reportSettings['export_orders'] ) {
								esc_html_e( "Remove HTML tags in the content of non-built-in fields (doesn't apply to addon fields)", 'product-sales-report-for-woocommerce' );
							} else {
								esc_html_e( "Remove HTML tags in the content of non-built-in fields (also applies to Group By fields; doesn't apply to addon fields)", 'product-sales-report-for-woocommerce' );
							}
							?>
                            <?php self::proBadge() ?>
							<?php self::docsLink( 'report-configuration/data-and-display', 'remove-html' ); ?>
						</label>
                </div>


                <div class="berrypress-field ninjalytics-setting-advanced ninjalytics-pro-feature">
                    <input type="checkbox" id="ninjalytics-object-caching-disable" name="object_caching_disable"
                             disabled />
                    <label for="ninjalytics-object-caching-disable">
                        <?php esc_html_e( 'Disable WordPress object caching', 'product-sales-report-for-woocommerce' ); ?>
                        <?php self::proBadge() ?>
                        <?php self::docsLink( 'report-configuration/data-and-display', 'object-caching-disable' ); ?>
                    </label>
                </div>

                <div class="berrypress-field ninjalytics-setting-advanced ninjalytics-pro-feature">
                    <input type="checkbox" id="hm_psr_use_wp_date" name="use_wp_date"  disabled/>
                    <label for="hm_psr_use_wp_date">
                        <?php esc_html_e( 'Use WordPress date formatting functionality for dynamic date values', 'product-sales-report-for-woocommerce' ); ?>
                        <?php self::proBadge() ?>
                        <?php self::docsLink( 'report-configuration/data-and-display', 'use-wp-date' ); ?>
                    </label>
                </div>

				<?php if ( ! $reportSettings['export_orders'] ) { ?>
                    <div class="berrypress-field ninjalytics-setting-advanced">
                        <input type="checkbox" id="ninjalytics-intermediate-rounding" name="intermediate_rounding"
                               value="2"<?php checked( ! empty( $reportSettings['intermediate_rounding'] ) ); ?> />
                        <label for="ninjalytics-intermediate-rounding">
                            <?php esc_html_e( 'Intermediate rounding', 'product-sales-report-for-woocommerce' ); ?>
                            <?php self::docsLink( 'report-configuration/data-and-display', 'intermediate-rounding', true ); ?>
                        </label>
					</div>
				<?php } ?>

                <div class="berrypress-field ninjalytics-setting-advanced">
                    <input type="checkbox" id="ninjalytics-enable-debug" name="hm_psr_debug"
                           value="1"<?php checked( ! empty( $reportSettings['hm_psr_debug'] ) ); ?> />
                    <label for="ninjalytics-enable-debug">
                        <?php esc_html_e( 'Enable debug mode', 'product-sales-report-for-woocommerce' ); ?>
                        <?php self::docsLink( 'report-configuration/data-and-display', 'debug' ); ?>
                    </label>
				</div>

				<div class="berrypress-field ninjalytics-setting-advanced ninjalytics-debug-sql-box berrypress-hidden" id="ninjalytics-debug-sql-box"  aria-hidden="true">
					<label for="ninjalytics-debug-sql-content"><?php esc_html_e( 'Debug: MySQL queries', 'product-sales-report-for-woocommerce' ); ?></label>
					<pre id="ninjalytics-debug-sql-content" class="ninjalytics-debug-sql-content"></pre>
				</div>

			</div> <!-- /ninjalytics-section-body -->
		</div> <!-- /ninjalytics-settings-toggle (Data & Display) -->
		<?php
	}

	private function renderReportsPage() {

		global $wp_roles;
		
		try {
			$reporterId = ninjalytics_get_active_reporter_id();
		} catch ( \Exception $ex ) {
			?>
            <div class="ags-psr-notification ags-psr-notification-error">
                <p><?php esc_html_e( 'This plugin requires that WooCommerce or Easy Digital Downloads is installed and activated.', 'product-sales-report-for-woocommerce' ); ?></p>
            </div>
			<?php
			return;
		}
		
		$reportersInfo = ninjalytics_get_reporters_info();
		
		if ( !current_user_can($reportersInfo[$reporterId]['capability']) ) {
			?>
            <div class="ags-psr-notification ags-psr-notification-error">
                <p><?php esc_html_e( 'You do not have permission to access this report.', 'product-sales-report-for-woocommerce' ); ?></p>
            </div>
			<?php
			return;
		}
		
		$reporter = ninjalytics_get_reporter_by_id($reporterId);

		$savedReportSettings = get_option( 'ninjalytics_settings' );
		if ( empty( $savedReportSettings ) ) {
			$savedReportSettings = array(
				$reporter->getDefaultSettings( false )
			);
		}

		if ( isset( $_REQUEST['preset'] ) ) {

			if ( isset( $_REQUEST['ninjalytics_action_free'] ) ) {
				if ( $_REQUEST['ninjalytics_action_free'] == 'preset-save' ) {
					check_admin_referer( 'hm-psr-run', 'hm-psr-nonce' );

					$isNew = empty( (int) $_REQUEST['preset'] );

					if ( $isNew || isset( $savedReportSettings[ (int) $_REQUEST['preset'] ] ) ) {
						$_POST = stripslashes_deep( $_POST );

						// Map new (1.6.8) product category checklist onto old field name
						if ( isset( $_POST['tax_input']['product_cat'] ) ) {
							// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Mapping from one POST var to another, to be sanitized before use
							$_POST['product_cats'] = $_POST['tax_input']['product_cat'];
							unset( $_POST['tax_input'] );
						}

						// Also update checkbox fields in hm_sbp_on_init
						foreach (
							array(
								'limit_on',
								'include_nil',
								'include_shipping',
								'include_unpublished',
								'include_header',
								'include_totals',
								'format_amounts',
								'exclude_free',
								'order_meta_filter_on',
								'order_meta_filter_2_on',
								'customer_meta_filter_on',
								'product_tag_filter_on',
								'product_meta_filter_on',
								'refunds',
								'adjustments',
								'report_title_on',
								'report_unfiltered',
								'hm_psr_debug',
								'object_caching_disable',
								'use_wp_date',
								'disable_product_grouping',
								'intermediate_rounding',
								'order_item_meta_filter_1_on',
								'order_item_meta_filter_2_on',
								'remove_html',
								'enable_custom_segments',
								'advanced_table_downloads',
								'advanced_data_display',
								'advanced_products',
								'advanced_orders'
							) as $checkboxField
						) {
							if ( ! isset( $_POST[ $checkboxField ] ) ) {
								$_POST[ $checkboxField ] = 0;
							}
						}

						if (isset($savedReportSettings[$_REQUEST['preset']]['key'])) {
							$_POST['key'] = $savedReportSettings[(int) $_REQUEST['preset']]['key'];
						}

						if ($isNew) {
							$savedReportSettings[] = $_POST;
						} else {
							$savedReportSettings[(int) $_REQUEST['preset']] = $_POST;
						}
						update_option('ninjalytics_settings', array_values($savedReportSettings), false);

							
						if ($isNew) {
							echo('<script type="text/javascript">location.href = atob(\''.esc_html(base64_encode(add_query_arg('preset', count($savedReportSettings) - 1, remove_query_arg('preset')))).'\');</script>');
						}
					}
				} else if ($_REQUEST['ninjalytics_action_free'] == 'preset-del' && !empty((int) $_GET['preset']) && isset($savedReportSettings[(int) $_GET['preset']])) {
					check_admin_referer('hm-psr-run');
					
					unset($savedReportSettings[(int) $_GET['preset']]);
					update_option('ninjalytics_settings', array_values($savedReportSettings), false);
					delete_option('ninjalytics_report_dates_'.((int) $_GET['preset']));
					unset($_GET['preset']);
					echo('<script type="text/javascript">location.href = \'?page=ninjalytics-free\';</script>');
					return;
				}
			}
			
			$openPreset = sanitize_text_field(wp_unslash($_GET['preset'] ?? '0'));
			$savedSettings = ($openPreset && $openPreset[0] == '_') ? (($reporter->getReportTemplates())[substr($openPreset, 1)] ?? []) : ($savedReportSettings[ $openPreset ] ?? []);
			
			$reportSettings = array_merge(
				$reporter->getDefaultSettings($savedSettings['export_orders'] ?? false),
				$savedSettings,
				((int) $openPreset) ? json_decode(get_option('ninjalytics_report_dates_'.((int) $openPreset), '{}'), true) : []
			);

			// For backwards compatibility with pre-1.5 versions
			if (!empty($reportSettings['cat'])) {
				$reportSettings['products'] = 'cats';
				$reportSettings['product_cats'] = array($reportSettings['cat']);
			}

			$fieldOptions = $reporter->getBuiltInFields($reportSettings['export_orders']);
		}

		if ( isset( $reportSettings ) ) {

			$orderBy          = ( in_array( $reportSettings['orderby'], array(
				'product_id',
				'quantity',
				'gross',
				'gross_after_discount'
			) ) ? 'builtin::' . $reportSettings['orderby'] : $reportSettings['orderby'] );


			?>
            <ol id="ninjalytics-breadcrumbs">
                <li>
                    <a href="?page=ninjalytics-free"><?php esc_html_e( 'Reports', 'product-sales-report-for-woocommerce' ); ?></a>
                </li>
                <li>
                    <a href="?page=ninjalytics-free&preset=<?php echo (int) $openPreset; ?>"><?php echo esc_html( $reportSettings['preset_name'] ?? __( 'Untitled Report', 'product-sales-report-for-woocommerce' ) ); ?></a>
                </li>
            </ol>
            <form action="" method="post" id="ninjalytics-form">
                <input type="hidden" name="preset" value="<?php echo (int) $openPreset; ?>">
				<?php if ( isset( $_REQUEST['ninjalytics_reporter'] ) ) { ?>
                    <input type="hidden" name="_reporter"
                           value="<?php echo esc_attr( sanitize_text_field(wp_unslash($_REQUEST['ninjalytics_reporter'])) ); ?>">
				<?php } ?>


                <div id="ninjalytics-display-toolbar">

                    <div id="ninjalytyics-display-mode">

                        <span><?php esc_html_e( 'View:', 'product-sales-report-for-woocommerce' ); ?></span>

                        <div class="ninjalytics-display-options ninjalytics-buttons-switch"
                             role="group"
                             aria-label="<?php esc_attr_e( 'Display mode', 'product-sales-report-for-woocommerce' ); ?>">

                            <button type="button"
                                    class="berrypress-btn berrypress-btn-icon"
                                    aria-pressed="<?php echo $reportSettings['display_mode'] === 'table' ? 'true' : 'false'; ?>"
                                    data-display-mode="table"
                                    data-bp-tooltip="<?php esc_attr_e( 'Display Table', 'product-sales-report-for-woocommerce' ); ?>">
                                <i class="berrypress-icon-table" aria-hidden="true"></i>
                                <span class="berrypress-visually-hidden"><?php esc_html_e( 'Display Table', 'product-sales-report-for-woocommerce' ); ?></span>
                            </button>

							<?php if ( ! $reportSettings['export_orders'] ) { ?>
                                <button type="button"
                                        class="berrypress-btn berrypress-btn-icon"
                                        aria-pressed="<?php echo $reportSettings['display_mode'] === 'chart' ? 'true' : 'false'; ?>"
                                        data-display-mode="chart"
                                        data-bp-tooltip="<?php esc_attr_e( 'Display Chart', 'product-sales-report-for-woocommerce' ); ?>">
                                    <i class="berrypress-icon-chart" aria-hidden="true"></i>
                                    <span class="berrypress-visually-hidden"><?php esc_html_e( 'Display Chart', 'product-sales-report-for-woocommerce' ); ?></span>
                                </button>
							<?php } ?>

                        </div> <!-- /ninjalytics-display-options -->
                    </div> <!-- /ninjalytyics-display-mode -->

                    <div id="ninjalytics-date-range">
                        <div class="ninjalytics-date-field">
                            <i class="berrypress-icon-access_time" aria-hidden="true"></i>
                            <!--                        <input type="text" id="ninjalytics-dates-desc" readonly>-->
                            <output id="ninjalytics-dates-desc" tabindex="0"></output>
                        </div>

                        <div id="ninjalytics-date-range-dropdown" class="berrypress-hidden">
                            <div class="ninjalytics-date-range-tabs">
								<?php foreach (
									[
										'preset'    => 'Quick',
										'basic'    => 'Relative',
										'absolute' => 'Absolute',
										'dynamic'  => 'Expression'
									] as $mode => $label
								) { ?>
                                    <input type="radio" id="report_time_mode_<?php echo( esc_attr( $mode ) ); ?>"
                                           name="report_time_mode"
                                           value="<?php echo( esc_attr( $mode ) ); ?>"<?php checked( $mode, $reportSettings['report_time_mode'] ); ?>>
                                    <label for="report_time_mode_<?php echo(esc_attr($mode)); ?>"><?php echo(esc_html($label).($mode == 'dynamic' ? ' <span class="ninjalytics-pro-badge">Pro</span>' : '')); ?></label>
								<?php } ?>
                            </div>

                            <div id="ninjalytics-date-range-dropdown-body">
                                <div class="ninjalytics-date-range-dropdown-tab-content"
                                     id="ninjalytics-date-range-preset">
                                    <div>
                                        <label for="ninjalytics-date-range-preset-select"><?php esc_html_e( 'Report Dates', 'product-sales-report-for-woocommerce' ) ?>:</label>
                                        <div>
                                            <select id="ninjalytics-date-range-preset-select" name="report_time_preset">
												<?php foreach( ninjalytics_get_report_dates_presets() as $datePresetId => $datePreset) { ?>
                                                <option value="<?php echo(esc_attr($datePresetId)); ?>"<?php selected( $datePresetId, $reportSettings['report_time_preset'] ); ?>>
													<?php echo(esc_html( $datePreset['label'] )); ?>
                                                </option>
												<?php } ?>
                                            </select>
                                        </div>
                                        <p></p>
                                    </div>
                                </div>
                                <div class="ninjalytics-date-range-dropdown-tab-content"
                                     id="ninjalytics-date-range-basic">
                                    <div>
                                        <label for="ninjalytics-date-range-basic-from"><?php esc_html_e( 'From', 'product-sales-report-for-woocommerce' ) ?>
                                            :</label>
                                        <div>
                                            <input type="number" id="ninjalytics-date-range-basic-from"
                                                   name="report_time_basic_from">
                                            <select name="report_time_basic_from_unit">
                                                <option value="max"<?php checked( 'max', $reportSettings['report_time_basic_from_unit'] ); ?>>
													<?php esc_html_e( 'Forever', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="now"<?php checked( 'now', $reportSettings['report_time_basic_from_unit'] ); ?>>
													<?php esc_html_e( 'Now', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="-d"<?php checked( '-d', $reportSettings['report_time_basic_from_unit'] ); ?>>
													<?php esc_html_e( 'Day(s) ago', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="-cm"<?php checked( '-cm', $reportSettings['report_time_basic_from_unit'] ); ?>>
													<?php esc_html_e( 'Month(s) ago', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                            </select>
                                            <select name="report_time_basic_from_round">
                                                <option value="d"<?php checked( 'd', $reportSettings['report_time_basic_from_round'] ); ?>>
													<?php esc_html_e( 'rounded to start of day', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="m"<?php checked( 'm', $reportSettings['report_time_basic_from_round'] ); ?>>
													<?php esc_html_e( 'rounded to start of month', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value=""<?php checked( ! $reportSettings['report_time_basic_from_round'] ); ?>>
													<?php esc_html_e( 'without rounding', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                            </select>
                                        </div>
                                        <p></p>
                                    </div>
                                    <div>
                                        <label for="ninjalytics-date-range-basic-to"><?php esc_html_e( 'To', 'product-sales-report-for-woocommerce' ) ?>
                                            :</label>
                                        <div>
                                            <input type="number" id="ninjalytics-date-range-basic-to"
                                                   name="report_time_basic_to">
                                            <select name="report_time_basic_to_unit">
                                                <option value="max"<?php checked( 'max', $reportSettings['report_time_basic_to_unit'] ); ?>>
													<?php esc_html_e( 'Forever', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="now"<?php checked( 'now', $reportSettings['report_time_basic_to_unit'] ); ?>>
													<?php esc_html_e( 'Now', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="-d"<?php checked( '-d', $reportSettings['report_time_basic_to_unit'] ); ?>>
													<?php esc_html_e( 'Day(s) ago', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="-cm"<?php checked( '-cm', $reportSettings['report_time_basic_to_unit'] ); ?>>
													<?php esc_html_e( 'Month(s) ago', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="d"<?php checked( 'd', $reportSettings['report_time_basic_to_unit'] ); ?>>
													<?php esc_html_e( 'Day(s) in future', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="cm"<?php checked( 'cm', $reportSettings['report_time_basic_to_unit'] ); ?>>
													<?php esc_html_e( 'Month(s) in future', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                            </select>
                                            <select name="report_time_basic_to_round">
                                                <option value="d"<?php checked( 'd', $reportSettings['report_time_basic_to_round'] ); ?>>
													<?php esc_html_e( 'rounded to end of day', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value="m"<?php checked( 'm', $reportSettings['report_time_basic_to_round'] ); ?>>
													<?php esc_html_e( 'rounded to end of month', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                                <option value=""<?php checked( ! $reportSettings['report_time_basic_to_round'] ); ?>>
													<?php esc_html_e( 'without rounding', 'product-sales-report-for-woocommerce' ) ?>
                                                </option>
                                            </select>
                                        </div>
                                        <p></p>
                                    </div>
                                </div>

                                <div class="ninjalytics-date-range-dropdown-tab-content"
                                     id="ninjalytics-date-range-absolute">
                                    <div>
                                        <label for="ninjalytics-date-range-absolute-from"><?php esc_html_e( 'From', 'product-sales-report-for-woocommerce' ) ?>
                                            :</label>
                                        <div>
                                            <input type="date" id="ninjalytics-date-range-absolute-from"
                                                   name="report_time_absolute_from_date"
                                                   value="<?php echo( esc_attr( $reportSettings['report_time_absolute_from_date'] ) ); ?>">
                                            <input type="time" step="1" name="report_time_absolute_from_time"
                                                   value="<?php echo( esc_attr( $reportSettings['report_time_absolute_from_time'] ) ); ?>">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="ninjalytics-date-range-absolute-to"><?php esc_html_e( 'To', 'product-sales-report-for-woocommerce' ) ?>
                                            :</label>
                                        <div>
                                            <input type="date" id="ninjalytics-date-range-absolute-to"
                                                   name="report_time_absolute_to_date"
                                                   value="<?php echo( esc_attr( $reportSettings['report_time_absolute_to_date'] ) ); ?>">
                                            <input type="time" step="1" name="report_time_absolute_to_time"
                                                   value="<?php echo( esc_attr( $reportSettings['report_time_absolute_to_time'] ) ); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="ninjalytics-date-range-dropdown-tab-content ninjalytics-pro-feature"
                                     id="ninjalytics-date-range-dynamic">
                                    <div>
                                        <label for="ninjalytics-date-range-dynamic-from"><?php esc_html_e( 'From', 'product-sales-report-for-woocommerce' ) ?>
                                            :</label>
                                        <div>
                                            <input disabled type="text" id="ninjalytics-date-range-dynamic-from"
                                                   name="report_time_dynamic_from" placeholder="example: -1 month">
                                            <p></p>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="ninjalytics-date-range-dynamic-to"><?php esc_html_e( 'To', 'product-sales-report-for-woocommerce' ) ?>
                                            :</label>
                                        <div>
                                            <input disabled type="text" id="ninjalytics-date-range-dynamic-to"
                                                   name="report_time_dynamic_to"
                                                   placeholder="example: yesterday midnight">
                                            <p></p>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- /ninjalytics-date-range-dropdown-body -->
							<?php if ( $reporter->supports( PlatformFeatures::ALT_DATES ) ) { ?>
							<div class="ninjalytics-date-range-dropdown-alt-dates-wrapper">
								<div class="ninjalytics-date-range-dropdown-alt-dates-toggle">
									<span class="ninjalytics-alt-dates-label"><?php esc_html_e( 'Based on:', 'product-sales-report-for-woocommerce' ); ?> <strong><?php esc_html_e( 'Order Date', 'product-sales-report-for-woocommerce' ); ?></strong></span>
									<button type="button" class="berrypress-link ninjalytics-alt-dates-toggle-btn"><?php esc_html_e( 'Change', 'product-sales-report-for-woocommerce' ); ?></button>
								</div>
								<div class="ninjalytics-date-range-dropdown-alt-dates berrypress-hidden">
								<div class="berrypress-field">
									<label for="ninjalytics_alt_order_date"><?php esc_html_e( 'Report date range based on:', 'product-sales-report-for-woocommerce' ); ?></label>
									<select name="alt_order_date" id="ninjalytics_alt_order_date">
										<option value="" selected><?php esc_attr_e( 'Order Date', 'product-sales-report-for-woocommerce' ); ?></option>
										<option value="order_meta::_date_paid" disabled><?php printf( /* translators: %s = option name */ esc_html__( '%s [Pro]', 'product-sales-report-for-woocommerce'), esc_html__( 'Date Paid', 'product-sales-report-for-woocommerce' ) ); ?></option>
										<option value="order_meta::_date_completed" disabled><?php printf( /* translators: %s = option name */ esc_html__( '%s [Pro]', 'product-sales-report-for-woocommerce'), esc_html__( 'Date Completed', 'product-sales-report-for-woocommerce' ) ); ?></option>
										<optgroup label="<?php printf( /* translators: %s = option name */ esc_attr__( '%s [Pro]', 'product-sales-report-for-woocommerce'), esc_attr__( 'Order Meta', 'product-sales-report-for-woocommerce' ) ); ?>" class="hm-psr-select-other" data-hm-psr-other-field-prefix="order_meta::">
											<?php
											foreach ( $reporter->getOrderFieldNames(false) as $orderField ) {
												if (str_starts_with($orderField, '_wc_order_attribution_') || str_starts_with($orderField, 'is_') || str_ends_with($orderField, '_hash') || str_ends_with($orderField, '_index') || str_ends_with($orderField, '_lock') || str_ends_with($orderField, '_value')) {
													continue;
												}
												?>
												<option value="order_meta::<?php echo esc_attr( $orderField ); ?>" disabled><?php echo esc_html( $orderField ); ?></option>
												<?php
											}
											?>
										</optgroup>
									</select>
								</div>
								</div> <!-- /ninjalytics-date-range-dropdown-alt-dates -->
							</div> <!-- /ninjalytics-date-range-dropdown-alt-dates-wrapper -->
							<?php } ?>
                        </div> <!-- /ninjalytics-date-range-dropdown -->
                    </div> <!-- /ninjalytics-date-range -->

                    <div class="ninjalytics-toolbar-buttons">
                        <button id="ninjalytics-download-button" class="berrypress-btn berrypress-btn-secondary"
                                type="submit"
                                name="ninjalytics_action_free" value="run"
                                data-bp-tooltip="<?php esc_html_e( 'Download Report', 'product-sales-report-for-woocommerce' ) ?>"
                                aria-label="<?php esc_attr_e( 'Download', 'product-sales-report-for-woocommerce' ) ?>">
                            <i class="berrypress-icon-download" aria-hidden="true"></i>
							<?php esc_html_e( 'Download', 'product-sales-report-for-woocommerce' ) ?>
                        </button>
                        <label for="email_to"
                               class="berrypress-hidden"><?php esc_html_e( 'Send to email', 'product-sales-report-for-woocommerce' ) ?></label>
                        <input type="email" name="email_to" id="email_to"
                               placeholder="<?php esc_attr_e( 'Email address', 'product-sales-report-for-woocommerce' ); ?>"
                               class="hidden" disabled>

                        <button id="ags-psr-email-button" class="ninjalytics-pro-feature berrypress-btn berrypress-btn-secondary"
                                data-bp-tooltip="<?php esc_html_e( 'Email Report', 'product-sales-report-for-woocommerce' ) ?>"
                                aria-label="<?php esc_attr_e( 'Email Report', 'product-sales-report-for-woocommerce' ) ?>" type="button">
                        <i class="berrypress-icon-email" aria-hidden="true"></i>
						<span class="ninjalytics-pro-badge">Pro</span>
						<?php esc_html_e( 'Email Report', 'product-sales-report-for-woocommerce' ); ?>
                        </button>
                        <button class="berrypress-btn berrypress-btn-primary" name="ninjalytics_action_free"
                                value="preset-save"
                                aria-label="<?php esc_html_e( 'Save', 'product-sales-report-for-woocommerce' ) ?>"
                                onclick="jQuery(this).closest('form').attr('target', ''); return true;">
                            <i class="berrypress-icon-save" aria-hidden="true"></i>
							<?php esc_html_e( 'Save', 'product-sales-report-for-woocommerce' ) ?>
                        </button>
                    </div> <!-- /ninjalytics-toolbar-buttons -->
                </div> <!-- /ninjalytics-display-toolbar -->
                <div id="ninjalytics-settings-settings" class="ninjalytics-settings-active">
                    <div class="ninjalytics-settings-data">
                        <div id="ninjalytics_output_container">
                            <div class="ninjalytics-loading">
                                <label><?php esc_html_e( 'Loading', 'product-sales-report-for-woocommerce' ) ?>...
                                    <progress min="0" max="100"></progress>
                                </label>
                            </div>
                            <div id="ninjalytics-chart-duplicate-series" class="berrypress-notice berrypress-notice-info berrypress-mb-3 berrypress-hidden">
                                <i class="berrypress-icon-info"></i>
                                <?php esc_html_e( 'Duplicate series field values were detected. Only one series field value is used at a time, so the chart may be missing data.', 'product-sales-report-for-woocommerce' ); ?>
                            </div>
                            <canvas id="hm_psr_chart"></canvas>
                            <p id="ninjalytics-chart-no-fields" class="berrypress-hidden">
								<?php esc_html_e( 'No fields are selected for the chart.', 'product-sales-report-for-woocommerce' ); ?>
								<?php esc_html_e( 'Enable the Chart option for one or more fields in the Report Fields tab.', 'product-sales-report-for-woocommerce' ); ?>
                            </p>
                        </div> <!-- /ninjalytics_output_container -->
                    </div> <!-- /ninjalytics-settings-data -->
                    <div class="ninjalytics-settings-panel">
                        <div id="ags-psr-report-meta">
                            <label>
                                <span><?php esc_html_e( 'Report Name', 'product-sales-report-for-woocommerce' ) ?>:</span>
                                <input type="text" name="preset_name"
                                       value="<?php echo( esc_attr( $reportSettings['preset_name'] ?? '' ) ); ?>"/>
                            </label>
                            <input type="hidden" name="export_orders"
                                   value="<?php echo( ( $reportSettings['export_orders'] ?? 0 ) ? 1 : 0 ); ?>"/>
                        </div>

                        <div id="ninjalytics-settings">

							<?php if ( $reporter->supports( PlatformFeatures::CHILD_ITEMS ) ) { ?>
                                <div class="ninjalytics-settings-toggle">
                                    <div class="ninjalytics-section-title">
                                        <h3><?php esc_html_e( 'Products', 'product-sales-report-for-woocommerce' ) ?></h3>
                                        <label>
                                            <input type="checkbox" class="ninjalytics-no-update" name="advanced_products" value="1"<?php checked( ! empty( $reportSettings['advanced_products'] ) ); ?>>
                                            <span><?php esc_html_e( 'Advanced', 'product-sales-report-for-woocommerce' ) ?></span>
                                        </label>
                                        <button class="berrypress-btn-icon" type="button">
                                            <i class="berrypress-icon-expand_more"></i>
                                        </button>
                                    </div>

                                    <div id="hm_psr_tab_products_panel" class="ninjalytics-section-body">

                                        <div class="ninjalytics-group-title"><?php esc_html_e( 'Products to include', 'product-sales-report-for-woocommerce' ) ?>
                                        </div>
                                        <?php $this->renderPrimaryProductsFilter($reporter, $reportSettings); ?>

										<?php if ( ! $reportSettings['export_orders'] ) { ?>
                                        <div class="ninjalytics-group-title berrypress-mt-4"><?php esc_html_e( 'Product filtering', 'product-sales-report-for-woocommerce' ) ?>
                                        </div>

                                            <div class="berrypress-field">
                                                <input type="checkbox" id="ninjalytics-product-include-nil"
                                                       name="include_nil"
                                                       value="1"<?php checked( ! empty( $reportSettings['include_nil'] ) ); ?> />
                                                <label for="ninjalytics-product-include-nil">
													<?php esc_html_e( 'Include products with no sales matching the filtering criteria', 'product-sales-report-for-woocommerce' ); ?>
													<?php self::docsLink( 'report-configuration/products', 'products-no-sales' ); ?>
                                                </label>
                                            </div>

                                            <div class="berrypress-field">
                                                <input type="checkbox" id="ninjalytics-include-unpublished"
                                                       name="include_unpublished"
                                                       value="1"<?php checked( ! empty( $reportSettings['include_unpublished'] ) ); ?> />
                                                <label for="ninjalytics-include-unpublished">
													<?php esc_html_e( 'Include unpublished products', 'product-sales-report-for-woocommerce' ); ?>
													<?php self::docsLink( 'report-configuration/products', 'products-unpublished' ); ?>
                                                </label>
                                            </div>

                                            <div class="berrypress-field">
                                                <input type="checkbox" id="ninjalytics-product-exclude-free"
                                                       name="exclude_free"
                                                       value="1"<?php checked( ! empty( $reportSettings['exclude_free'] ) ); ?> />
                                                <label for="ninjalytics-product-exclude-free">
													<?php esc_html_e( 'Exclude free products', 'product-sales-report-for-woocommerce' ); ?>
													<?php self::docsLink( 'report-configuration/products', 'exclude-free', true ); ?>
                                                </label>
                                            </div>
										<?php } ?>

                                        <div class="ninjalytics-group-title berrypress-mt-4 ninjalytics-setting-advanced"><?php esc_html_e( 'Advanced filtering', 'product-sales-report-for-woocommerce' ) ?>
                                        </div>

                                        <div class="ninjalytics-field-switch-conditional ninjalytics-setting-advanced">
                                            <div class="berrypress-field berrypress-switch ninjalytics-pro-feature">
                                                <input
                                                        type="checkbox"
                                                        name="product_tag_filter_on"
                                                        id="ninjalytics-product-tag-filter-on"
                                                        disabled
                                                />
                                                <label for="ninjalytics-product-tag-filter-on"><?php esc_html_e( 'Only products tagged', 'product-sales-report-for-woocommerce' ) ?>
                                                    <?php self::proBadge() ?>
													<?php self::docsLink( 'report-configuration/products', 'only-products-tagged' ); ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="ninjalytics-field-switch-conditional ninjalytics-setting-advanced">
                                            <div id="hm_psr_product_meta_filter_settings"
                                                 class="berrypress-field berrypress-switch ninjalytics-pro-feature">
                                                <input id="ninjalytics-product-meta-filter-on" type="checkbox"
                                                       name="product_meta_filter_on"
                                                       disabled/>
                                                <label for="ninjalytics-product-meta-filter-on"><?php esc_html_e( 'Only products with field', 'product-sales-report-for-woocommerce' ) ?>
                                                    <?php self::proBadge() ?>
                                                    <?php self::docsLink( 'report-configuration/products', 'only-products-with-field', true ) ?></label>
                                            </div>
                                        </div>


										<?php

										if ( ! $reportSettings['export_orders'] ) {

											$hasVariationSupport = $reporter->supports( PlatformFeatures::VARIATIONS );
											if ( $hasVariationSupport ) {
												?>
                                                <div class="ninjalytics-group-title berrypress-mt-4">
													<?php esc_html_e( 'Product variations', 'product-sales-report-for-woocommerce' ); ?>
													<?php self::docsLink( 'report-configuration/products', 'product-variations' ); ?>
                                                </div>

                                                <div class="berrypress-field">
                                                    <input type="radio" id="ninjalytics-variations-together"
                                                           name="variations"
                                                           value="0"<?php checked( empty( $reportSettings['variations'] ) ); ?>
                                                           class="hm_psr_variations_fld"/>
                                                    <label for="ninjalytics-variations-together"><?php esc_html_e( 'Group product variations together', 'product-sales-report-for-woocommerce' ); ?></label>
                                                </div>

                                                <div class="berrypress-field">
                                                    <input type="radio" id="ninjalytics-variations-seperately"
                                                           name="variations"
                                                           value="1"<?php checked( ! empty( $reportSettings['variations'] ) ); ?>
                                                           class="hm_psr_variations_fld"/>
                                                    <label for="ninjalytics-variations-seperately"><?php esc_html_e( 'Report on each variation separately', 'product-sales-report-for-woocommerce' ); ?></label>
                                                </div>
												<?php
											}


											?>
                                            <div class="ninjalytics-group-title berrypress-mt-4"><?php esc_html_e( 'Additional report items', 'product-sales-report-for-woocommerce' ) ?> </div>

											<?php if ( $reporter->supports( PlatformFeatures::SHIPPING ) ) { ?>
                                                <div class="berrypress-field">
                                                    <input type="checkbox" id="ninjalytics-product-include-shipping"
                                                           name="include_shipping"
                                                           value="1"<?php checked( ! empty( $reportSettings['include_shipping'] ) ); ?> />
                                                    <label for="ninjalytics-product-include-shipping">
														<?php esc_html_e( 'Display shipping as report items', 'product-sales-report-for-woocommerce' ); ?>
														<?php self::docsLink( 'report-configuration/products', 'shipping' ); ?>
                                                    </label>
                                                </div>
											<?php } ?>


                                            <div class="ninjalytics-group-title berrypress-mt-4"><?php esc_html_e( 'Sales Adjustments', 'product-sales-report-for-woocommerce' ) ?> </div>

											<?php
											if ( $reporter->supports( PlatformFeatures::LINE_ITEM_ADJUSTMENTS ) ) { ?>
                                                <div class="berrypress-field">
                                                    <input type="checkbox" id="ninjalytics-product-adjustments"
                                                           name="adjustments"
                                                           value="1" <?php echo( empty( $reportSettings['adjustments'] ) ? '' : ' checked="checked"' ) ?> />
                                                    <label for="ninjalytics-product-adjustments"><?php esc_html_e( 'Include line-item adjustments', 'product-sales-report-for-woocommerce' );
														self::docsLink( 'report-configuration/products', 'adjustments', true ) ?> </label>
                                                </div>
											<?php } ?>

                                            <div class="berrypress-field">
                                                <input type="checkbox" id="ninjalytics-product-refunds" name="refunds"
                                                       value="1" <?php echo( empty( $reportSettings['refunds'] ) ? '' : ' checked="checked"' ) ?> />
                                                <label for="ninjalytics-product-refunds"><?php esc_html_e( 'Include line-item refunds', 'product-sales-report-for-woocommerce' );
													self::docsLink( 'report-configuration/products', 'refunds', true ) ?> </label>
                                            </div>
										<?php } ?>

                                    </div> <!-- /hm_psr_tab_products_panel -->
                                </div> <!-- /ninjalytics-settings-toggle (Products) -->
							<?php } ?>

                            <div class="ninjalytics-settings-toggle">
                                <div class="ninjalytics-section-title">
                                    <h3><?php echo( esc_html( $reporter->getPrimaryItemsName() ) ); ?></h3>
                                    <label>
                                        <input type="checkbox" class="ninjalytics-no-update" name="advanced_orders" value="1"<?php checked( ! empty( $reportSettings['advanced_orders'] ) ); ?>>
                                        <span><?php esc_html_e( 'Advanced', 'product-sales-report-for-woocommerce' ) ?> </span>
                                    </label>

                                    <button class="berrypress-btn-icon" type="button">
                                        <i class="berrypress-icon-expand_more"></i>
                                    </button>
                                </div>
                                <div class="ninjalytics-section-body">
                                    <div class="ninjalytics-group-title">
										<?php esc_html_e( 'Status', 'product-sales-report-for-woocommerce' ); ?>:
										<?php self::docsLink( 'report-configuration/orders', 'order-status' ); ?>
                                    </div>
                                    <div class="berrypress-mb-3">
									<?php foreach ( $reporter->getOrderStatuses() as $status => $statusName ) { ?>
                                        <label class="berrypress-field">
                                            <input type="checkbox"
                                                   name="order_statuses[]"<?php checked( in_array( $status, $reportSettings['order_statuses'] ) ); ?>
                                                   value="<?php echo esc_attr( $status ); ?>"/>
                                            <span class="label"><?php echo esc_html( $statusName ); ?></span>
                                        </label>
									<?php } ?>
                                    </div>
									
									
									<?php if ( $reporter->supports( PlatformFeatures::CHILD_ITEMS_FILTER ) ) { ?>
										<div class="ninjalytics-group-title"><?php esc_html_e( 'Containing products', 'product-sales-report-for-woocommerce' ) ?>
										</div>
                                        <?php $this->renderPrimaryProductsFilter($reporter, $reportSettings); ?>
									<?php }

									if ( $reporter->supports( PlatformFeatures::META ) ) { ?>
                                        <div class="ninjalytics-field-switch-conditional ninjalytics-setting-advanced berrypress-mt-4">

                                            <div class="ninjalytics-group-title ninjalytics-pro-feature">
												<?php esc_html_e( 'Order filtering', 'product-sales-report-for-woocommerce' ); ?>:
                                                <?php self::proBadge() ?>
                                            </div>

                                            <div class="berrypress-field ninjalytics-pro-feature">
                                                <input type="checkbox" id="ninjalytics-order-field-1"
                                                       name="order_meta_filter_on"
                                                       data-toggle-key="order_meta_filter_on"
                                                       disabled/>
                                                <label for="ninjalytics-order-field-1">
													<?php esc_html_e( 'Only orders with field', 'product-sales-report-for-woocommerce' ); ?>:
                                                    <?php self::proBadge() ?>
													<?php self::docsLink( 'report-configuration/orders', 'only-orders-with-field', true ); ?>
                                                </label>
                                            </div>

                                        </div>
										<?php
									}
									if ( $reporter->supports( PlatformFeatures::CHILD_ITEMS_META ) ) {
										?>
                                        <div class="ninjalytics-field-switch-conditional ninjalytics-setting-advanced berrypress-mt-2">

                                            <div class="berrypress-field ninjalytics-pro-feature">
                                                <input type="checkbox" id="ninjalytics-order-field-2"
                                                       name="order_item_meta_filter_1_on"
                                                       data-toggle-key="order_item_meta_filter_1_on"
                                                       disabled/>
                                                <label for="ninjalytics-order-field-2">
													<?php esc_html_e( 'Only order items with field', 'product-sales-report-for-woocommerce' ); ?>:
                                                    <?php self::proBadge() ?>
													<?php self::docsLink( 'report-configuration/orders', 'only-order-items-with-field', true ); ?>
                                                </label>
                                            </div>

                                        </div>

										<?php

									}

									if ( $reporter->supports( PlatformFeatures::SHIPPING ) ) {
										?>
                                        <div class="ninjalytics-group-title ninjalytics-pro-feature berrypress-mt-4">
											<?php esc_html_e( 'Include orders by shipping method', 'product-sales-report-for-woocommerce' ); ?>:
                                            <?php self::proBadge() ?>
											<?php self::docsLink( 'report-configuration/orders', 'include-orders-by-shipping-method', true ); ?>
                                        </div>
                                    <div class="ninjalytics-checkboxes-container berrypress-mb-3">
										<?php
										foreach ( \NinjalyticsFree\ninjalytics_get_order_shipping_filter_options() as $shippingMethodId => $shippingMethod ) {
											?>
                                            <label class="berrypress-field ninjalytics-pro-feature">
                                                <input type="checkbox"
                                                       name="order_shipping_filter[]"<?php checked( in_array( $shippingMethodId, $reportSettings['order_shipping_filter'] ?? [] ) ); ?>
                                                       disabled>
                                                <span class="label"><?php echo esc_html( $shippingMethod ); ?></span>
                                            </label>
											<?php } ?>
                                    </div> <!-- /ninjalytics-checkboxes-container-->
                                        <?php
									}

									if ( $reporter->supports( PlatformFeatures::CUSTOMER_USERS ) ) {
										?>
                                        <div class="ninjalytics-group-title ninjalytics-pro-feature berrypress-mt-4 ninjalytics-setting-advanced">
											<?php esc_html_e( 'Filter Orders by Customer Role', 'product-sales-report-for-woocommerce' ); ?>:
                                            <?php self::proBadge() ?>
											<?php self::docsLink( 'report-configuration/orders', 'filter-orders-by-customer-role' ); ?>
                                        </div>
										<?php

										$customerRoles = [ '-1' => __( '(Guest Customers)', 'product-sales-report-for-woocommerce' ) ];
										foreach ( $wp_roles->roles as $roleId => $role ) {
											$customerRoles[ $roleId ] = $role['name'];
										}

										?>
										<?php foreach (
											[
												'customer_role'         => __( 'Include Only', 'product-sales-report-for-woocommerce' ),
												'customer_role_exclude' => __( 'Exclude', 'product-sales-report-for-woocommerce' )
											] as $settingKey => $label
										) { ?>
                                            <div class="berrypress-mb-3">
                                                <div class="ninjalytics-group-subtitle ninjalytics-pro-feature berrypress-mb-2 berrypress-fw-medium"><?php echo esc_html( $label ); ?>:
                                                </div>

                                                <div class="ninjalytics-checkboxes-container">
												<?php foreach ( $customerRoles as $roleId => $roleName ) { ?>
                                                    <label class="berrypress-field ninjalytics-pro-feature">
                                                        <input type="checkbox"
                                                               name="<?php echo esc_attr( $settingKey ); ?>[]" disabled >
                                                        <span class="label"><?php echo esc_html( $roleName ); ?></span>
                                                    </label>
												<?php } ?>
                                                </div>

                                            </div>

										<?php } ?>
										<?php

										$wcMemberships = ninjalytics_get_wc_membership_plans();
										if ( $wcMemberships ) {
											?>
                                            <div class="berrypress-field berrypress-field-flex berrypress-field-align-center ninjalytics-setting-advanced">
                                                <label for="ninjalytics-wc-membership">
                                                       <?php esc_html_e( 'Include Orders by Customer Membership:', 'product-sales-report-for-woocommerce' ); ?>
                                                </label>

	                                            <?php self::docsLink( 'report-configuration/orders', 'include-orders-by-customer-membership' ); ?>
                                                <select id="ninjalytics-wc-membership" name="wc_membership">
                                                    <option value="0"><?php esc_html_e( '(All Customers)', 'product-sales-report-for-woocommerce' ); ?></option>
                                                    <option value="-1"><?php esc_html_e( '(Customers Without Membership)', 'product-sales-report-for-woocommerce' ); ?></option>
                                                    <option value="-2"><?php esc_html_e( '(Customers With Any Membership)', 'product-sales-report-for-woocommerce' ); ?></option>
                                                    <?php foreach ( $wcMemberships as $membershipId => $membershipName ) { ?>
                                                        <option value="<?php echo (int) $membershipId; ?>"><?php echo esc_html( $membershipName ); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>

											<?php

										}
										?>
                                        <div class="ninjalytics-field-switch-conditional berrypress-mt-4 ninjalytics-setting-advanced">
                                            <div class="ninjalytics-group-title ninjalytics-pro-feature"><?php esc_html_e( 'Advanced Filtering', 'product-sales-report-for-woocommerce' ); ?>
                                            <?php self::proBadge() ?>
                                            </div>
                                            <div class="berrypress-field ninjalytics-pro-feature">
                                                <input type="checkbox" id="ninjalytics-order-customer-meta-filter"
                                                       name="customer_meta_filter_on"
                                                       disabled/>
                                                <label for="ninjalytics-order-customer-meta-filter">
													<?php esc_html_e( 'Only Orders from Customers With Field:', 'product-sales-report-for-woocommerce' ); ?>
													<?php self::docsLink( 'report-configuration/orders', 'only-orders-from-customers-with-field' ); ?>
                                                </label>
                                            </div>
                                        </div>
										<?php
									}
									?>
                                </div> <!-- /ninjalytics-section-body -->
                            </div> <!-- /ninjalytics-settings-toggle (Orders) -->

							<?php if ( ! $reportSettings['export_orders'] ) { ?>
                                <div class="ninjalytics-settings-toggle">
                                    <div class="ninjalytics-section-title">
                                        <h3><?php esc_html_e( 'Segmentation', 'product-sales-report-for-woocommerce' ); ?></h3>
                                        <button class="berrypress-btn-icon" type="button">
                                            <i class="berrypress-icon-expand_more"></i>
                                        </button>
                                    </div>
									<?php
									$groupByFields = $reporter->getGroupByFields();
									?>

                                    <div class="ninjalytics-section-body">
										<?php if ( $reporter->supports( PlatformFeatures::CHILD_ITEMS ) ) { ?>
                                            <div class="ninjalytics-group-title">
												<?php esc_html_e( 'Main Segment', 'product-sales-report-for-woocommerce' ); ?>:
												<?php self::docsLink( 'report-configuration/segmentation', 'main-segment', true ); ?>
                                            </div>

                                            <label class="berrypress-field">
                                                <input type="radio" class="ags-psr-disable-product-grouping"
                                                       name="disable_product_grouping"
                                                       value="0"<?php checked( empty( $reportSettings['disable_product_grouping'] ), true ); ?>>
                                                <span class="label">
													<?php
													if ( $hasVariationSupport ) {
														esc_html_e( 'By products or variations (based on ID)', 'product-sales-report-for-woocommerce' );
													} else {
														esc_html_e( 'By products (based on ID)', 'product-sales-report-for-woocommerce' );
													}
													?>
												</span>
                                            </label>

                                            <label class="berrypress-field">
                                                <input type="radio" class="ags-psr-disable-product-grouping"
                                                       name="disable_product_grouping"
                                                       value="-1"<?php checked( $reportSettings['disable_product_grouping'], - 1 ); ?>>
                                                <span class="label">
													<?php
													if ( $hasVariationSupport ) {
														esc_html_e( 'By products or variations (based on SKU)', 'product-sales-report-for-woocommerce' );
													} else {
														esc_html_e( 'By products (based on SKU)', 'product-sales-report-for-woocommerce' );
													}
													?>
												</span>
                                            </label>

                                            <label class="berrypress-field">
                                                <input type="radio" class="ags-psr-disable-product-grouping"
                                                       name="disable_product_grouping"
                                                       value="2"<?php checked( $reportSettings['disable_product_grouping'], 2 ); ?> >
                                                <span class="label"><?php esc_html_e( 'By product category', 'product-sales-report-for-woocommerce' ); ?></span>
                                            </label>

                                            <label class="berrypress-field">
                                                <input type="radio" class="ags-psr-disable-product-grouping"
                                                       name="disable_product_grouping"
                                                       value="1"<?php checked( $reportSettings['disable_product_grouping'], 1 ); ?>>
                                                <span class="label"><?php esc_html_e( 'None', 'product-sales-report-for-woocommerce' ); ?></span>
                                            </label>

										<?php } ?>
										<?php
										// Custom segments section
										?>

                                        <div class="ninjalytics-field-switch-conditional berrypress-mt-4">
                                            <div class="berrypress-field">
                                                <input type="checkbox" name="enable_custom_segments"
                                                       id="hm_psr_enable_custom_segments"
                                                       data-toggle-key="enable_custom_segments"
                                                       value="1"<?php checked( isset( $reportSettings['enable_custom_segments'] ) && ( $reportSettings['enable_custom_segments'] == 1 || ( $reportSettings['enable_custom_segments'] == - 1 ) &&  $reportSettings['groupby'] ) ); ?> />
                                                <label for="hm_psr_enable_custom_segments"
                                                       class="berrypress-fw-medium"><?php esc_html_e( 'Enable custom segments', 'product-sales-report-for-woocommerce' ); ?><?php
														self::docsLink( 'report-configuration/segmentation', 'custom-segments', true );
													?></label>

                                            </div>
                                            <div class="ninjalytics-field-child" data-toggle-panel="enable_custom_segments">

										<?php

										for ( $i = 1; $i < 6; ++ $i ) {
											$fieldName = 'groupby' . ( $i == 1 ? '' : $i );
											?>
                                            <div class="ninjalytics-custom-segment">
                                                <label class="ninjalytics-settings-title"
                                                       for="hm_psr_field_<?php echo esc_attr( $fieldName ); ?>">
                                                    <span class="label"><?php /* translators: %d: segment number */ echo esc_html( sprintf( __( 'Segment %d:', 'product-sales-report-for-woocommerce' ), $i + 1 ) ); ?></span>
                                                    <?php
                                                    if ($i != 1) self::proBadge();
                                                    ?>
                                                </label>
                                                <select name="<?php echo esc_attr( $fieldName ); ?>"
                                                        id="hm_psr_field_<?php echo esc_attr( $fieldName ); ?>"
                                                        <?php
                                                        echo $i == 1 ? '' : 'disabled' ;
                                                        ?>
                                                >
                                                    <option value=""><?php esc_html_e( '(None)', 'product-sales-report-for-woocommerce' ); ?></option>
													<?php
                                                    if ( $i == 1 ) {
                                                        $foundGroupByField = false;
                                                        $fieldType         = '';
                                                        $fieldTypes        = $reporter->getGroupByFieldTypes();
                                                        foreach ( $groupByFields as $optionId => $optionName ) {
                                                            if ( $optionId[0] != $fieldType ) {
                                                                if ( ! $foundGroupByField && ! empty( $reportSettings[ $fieldName ] ) && $reportSettings[ $fieldName ][0] == $fieldType ) {
                                                                    $foundGroupByField = true;
                                                                    echo '<option value="' . esc_attr( $reportSettings[ $fieldName ] ) . '" selected>' . esc_html( substr( $reportSettings[ $fieldName ], 2 ) ) . '</option>';
                                                                }
                                                                $fieldType = $optionId[0];
                                                                if ( $fieldType ) {
                                                                    echo '</optgroup>';
                                                                }
                                                                echo '<optgroup label="' . esc_attr( $fieldTypes[ $fieldType ] ) . '" class="hm-psr-select-other" data-hm-psr-other-field-prefix="' . esc_attr( $fieldType ) . '_">';
                                                                $isOrderItemField = true;
                                                            }
                                                            $foundGroupByField = $foundGroupByField || $reportSettings[ $fieldName ] == $optionId;
                                                            echo '<option value="' . esc_attr( $optionId ) . '"' . ( $reportSettings[ $fieldName ] == $optionId ? ' selected="selected"' : '' ) . '>' . esc_html( $optionName ) . '</option>';
                                                        }
                                                        if ( ! $foundGroupByField && ! empty( $reportSettings[ $fieldName ] ) ) {
                                                            echo '<option value="' . esc_attr( $reportSettings[ $fieldName ] ) . '" selected>' . esc_html( substr( $reportSettings[ $fieldName ], 2 ) ) . '</option>';
                                                        }
                                                        if ( $fieldType ) {
                                                            echo '</optgroup>';
                                                        }
                                                    }
													?>
                                                    </optgroup>
                                                </select>
                                                 <?php if ($i == 1)  { ?>
                                                    <button type="button"
                                                            class="berrypress-btn-icon ninjalytics-segment-reset"
                                                            aria-label="<?php esc_attr_e( 'Clear to none', 'product-sales-report-for-woocommerce' ); ?>"
                                                            title="<?php esc_attr_e( 'Clear to none', 'product-sales-report-for-woocommerce' ); ?>"
                                                            data-field="<?php echo esc_attr( $fieldName ); ?>"><i
                                                                class="berrypress-icon-close"></i><span
                                                                class="berrypress-visually-hidden"><?php esc_html_e( 'Clear to none', 'product-sales-report-for-woocommerce' ); ?></span>
                                                    </button>
                                                <?php } ?>
                                            </div>
											<?php
										}
										?>


                                            </div> <!-- ninjalytics-field-child -->
                                        </div> <!-- ninjalytics-field-switch-conditional-->
                                    </div> <!-- /ninjalytics-section-body -->
                                </div> <!-- /ninjalytics-settings-toggle (Segmentation) -->
							<?php } ?>

                            <div class="ninjalytics-settings-toggle">
                                <div class="ninjalytics-section-title">
                                    <h3><?php esc_html_e( 'Report Fields', 'product-sales-report-for-woocommerce' ); ?></h3>
                                    <button class="berrypress-btn-icon" type="button">
                                        <i class="berrypress-icon-expand_more"></i>
                                    </button>
                                </div>

                                <div class="ninjalytics-section-body">

                                    <div class="ninjalytics-group-title">
	                                    <?php esc_html_e( 'Report Fields', 'product-sales-report-for-woocommerce' ); ?>
	                                    <?php self::docsLink( 'report-configuration/fields' ); ?>
                                    </div>


                                    <div id="hm_psr_report_fields">
                                        <?php
                                        $customFields     = $reporter->getCustomFields( $reportSettings['export_orders'], true );
                                        $customFieldsFlat = array_merge( ...array_values( $customFields ) );
                                        $addonFields      = ninjalytics_getAddonFields();
                                        $noTotalFields    = array(
                                            'builtin::product_id',
                                            'builtin::product_sku',
                                            'builtin::product_name',
                                            'builtin::variation_id',
                                            'builtin::variation_sku',
                                            'builtin::variation_attributes',
                                            'builtin::product_categories',
                                            'order_id',
                                            'order_status',
                                            'order_date',
                                            'billing_name',
                                            'billing_phone',
                                            'builtin::publish_time',
                                            'builtin::product_desc',
                                            'builtin::product_excerpt',
                                            'builtin::product_menu_order'
                                        );
                                        foreach ( $reportSettings['fields'] as $fieldId ) {
                                            $isGroupingField = substr( $fieldId, 0, 22 ) == 'builtin::groupby_field';
                                            if ( ! isset( $fieldOptions[ $fieldId ] ) && ! isset( $customFieldsFlat[ $fieldId ] ) && ! isset( $addonFields[ $fieldId ] ) && ! $isGroupingField ) {

                                                // Compatibility with pre-1.6.9 versions that didn't have the builtin:: prefix
                                                if ( isset( $fieldOptions[ 'builtin::' . $fieldId ] ) ) {
                                                    if ( isset( $reportSettings['field_names'][ $fieldId ] ) ) {
                                                        $reportSettings['field_names'][ 'builtin::' . $fieldId ] = $reportSettings['field_names'][ $fieldId ];
                                                    }
                                                    $fieldId = 'builtin::' . $fieldId;
                                                }

                                            }
                                            $divClass = 'ninjalytics-report-field ';
                                            if ( in_array( $fieldId, array(
                                                    'builtin::variation_id',
                                                    'builtin::variation_sku',
                                                    'builtin::variation_attributes'
                                                ) ) || substr( $fieldId, 0, 11 ) == 'variation::' ) {
                                                $divClass .= ' hm_psr_variation_field';
                                            } elseif ( $isGroupingField ) {
                                                $divClass .= ' hm_psr_' . substr( $fieldId, 9 ) ;
                                            }
                                            $fieldValue = isset( $reportSettings['field_names'][ $fieldId ] ) ? $reportSettings['field_names'][ $fieldId ] : ( isset( $fieldOptions[ $fieldId ] ) ? $fieldOptions[ $fieldId ] : $fieldId );
                                            ?>
                                            <div class="<?php echo esc_attr($divClass); ?>">
                                                <input type="hidden" name="fields[]"
                                                       value="<?php echo esc_attr( $fieldId ); ?>"/>
                                                <label for="field_name_<?php echo esc_attr( $fieldId ); ?>" class="berrypress-visually-hidden">
                                                    <?php /* translators: %s: field name */ echo esc_html( sprintf( __( 'Field label for %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>
                                                </label>
<!--                                                <i class="berrypress-icon-drag-indicator"></i>-->
                                                <input type="text"
                                                       id="field_name_<?php echo esc_attr( $fieldId ); ?>"
                                                       class="hm_psr_field_name"
                                                       name="field_names[<?php echo esc_attr( $fieldId ); ?>]"
                                                       value="<?php echo esc_attr( $fieldValue ); ?>"
                                                       aria-describedby="field_desc_<?php echo esc_attr( $fieldId ); ?>"/>

                                                <span id="field_desc_<?php echo esc_attr( $fieldId ); ?>" class="berrypress-visually-hidden">
                                                    <?php /* translators: %s: field name */ echo esc_html( sprintf( __( 'Options for field: %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>
                                                </span>
                                                <div role="group" aria-label="<?php /* translators: %s: field name */ echo esc_attr( sprintf( __( 'Display options for %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>" class="ninjalytics-field-options">
                                                    <label class="hm_psr_total_field<?php echo in_array( $fieldId, $noTotalFields ) ? ' no-total' : ''; ?>">
                                                        <input type="checkbox"
                                                               id="total_field_<?php echo esc_attr( $fieldId ); ?>"
                                                               name="total_fields[]"
                                                               value="<?php echo esc_attr( $fieldId ); ?>"<?php checked( in_array( $fieldId, $reportSettings['total_fields'] ) ); ?>
                                                               aria-label="<?php /* translators: %s: field name */ echo esc_attr( sprintf( __( 'Include %s in totals row', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>" />
                                                        <span aria-hidden="true"><?php esc_html_e( 'Total', 'product-sales-report-for-woocommerce' ); ?></span>
                                                    </label>
                                                    <label class="hm_psr_chart_field <?php //echo in_array( $fieldId, $noTotalFields ) ? ' no-chart' : ''; ?>">
                                                        <input type="checkbox"
                                                               id="chart_field_<?php echo esc_attr( $fieldId ); ?>"
                                                               name="chart_fields[]"
                                                               value="<?php echo esc_attr( $fieldId ); ?>"<?php checked( in_array( $fieldId, $reportSettings['chart_fields'] ) ); ?>
                                                               aria-label="<?php /* translators: %s: field name */ echo esc_attr( sprintf( __( 'Include %s in chart', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>" />
                                                        <span aria-hidden="true"><?php esc_html_e( 'Chart', 'product-sales-report-for-woocommerce' ); ?></span>
                                                    </label>
                                                    <label class="hm_psr_round_field<?php echo in_array( $fieldId, $noTotalFields ) ? ' no-round' : ''; ?>">
                                                        <input type="checkbox"
                                                               id="round_field_<?php echo esc_attr( $fieldId ); ?>"
                                                               name="round_fields[]"
                                                               value="<?php echo esc_attr( $fieldId ); ?>"<?php checked( in_array( $fieldId, $reportSettings['round_fields'] ) ); ?>
                                                               aria-label="<?php /* translators: %s: field name */ echo esc_attr( sprintf( __( 'Round values for %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>" />
                                                        <span aria-hidden="true"><?php esc_html_e( 'Round', 'product-sales-report-for-woocommerce' ); ?></span>
                                                    </label>
                                                </div>
                                                <div class="ninjalytics-field-actions-wrapper">
                                                    <button type="button"
                                                            class="berrypress-btn berrypress-btn-icon ninjalytics-btn-field-edit"
                                                            aria-label="<?php /* translators: %s: field name */ echo esc_attr( sprintf( __( 'Edit field: %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>">
                                                        <i class="berrypress-icon-edit"></i>
                                                        <span class="berrypress-visually-hidden"><?php /* translators: %s: field name */ echo esc_html( sprintf( __( 'Edit field: %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?></span>
                                                    </button>
                                                    <button class="berrypress-btn berrypress-btn-icon" type="button"
                                                            onclick="ninjalytics_remove_field(this.parentElement);"
                                                            aria-label="<?php /* translators: %s: field name */ echo esc_attr( sprintf( __( 'Remove field: %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?>">
                                                        <i class="berrypress-icon-delete" aria-hidden="true"></i>
                                                        <span class="berrypress-visually-hidden"><?php /* translators: %s: field name */ echo esc_html( sprintf( __( 'Remove field: %s', 'product-sales-report-for-woocommerce' ), $fieldValue ) ); ?></span>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php
                                        }

                                        ?>
                                    </div>
                                    <div role="group" class="ninjalytics-add-field-group" aria-labelledby="ninjalytics-add-field-label">

                                        <label id="ninjalytics-add-field-label" class="berrypress-visually-hidden" for="hm_psr_custom_field">
                                            <strong><?php esc_html_e( 'Add Field', 'product-sales-report-for-woocommerce' ); ?></strong>
                                        </label>

                                        <div class="ninjalytics-add-report-field">
                                            <select id="hm_psr_custom_field"
                                                    class="ninjalytics-no-update"
                                                    aria-label="<?php esc_attr_e( 'Select field to add to report', 'product-sales-report-for-woocommerce' ); ?>">
                                                <?php
                                                foreach ( array_merge( array( 'Built-in Fields' => $fieldOptions ), $customFields ) as $fieldGroupName => $fields ) {
                                                    $fieldGroupPrefix = ninjalytics_get_field_group_prefix($fieldGroupName, $reportSettings);

                                                    $optgroupClasses = [];
                                                    if ( $fieldGroupName != 'Built-in Fields' && $fieldGroupName != 'Product Taxonomies' ) {
                                                        $optgroupClasses[] = 'hm-psr-select-other';
                                                    }

                                                    if ( $fieldGroupName == 'Product' || $fieldGroupName == 'Product Taxonomies' || $fieldGroupName == 'Product Variation' ) {
                                                        $optgroupClasses[] = 'hm-psr-product-fields';
                                                    }

                                                    echo '<optgroup label="' . esc_attr( $fieldGroupName == 'Built-in Fields' ? 'Built-in Fields' : sprintf( /* translators: %s = option name */ __( '%s [Pro]', 'product-sales-report-for-woocommerce'), __( $fieldGroupName, 'product-sales-report-for-woocommerce' ) ) ) . '"' . ( $optgroupClasses ? ' class="' . esc_attr(implode( ' ', $optgroupClasses )) . '"' : '' ) . ( isset( $fieldGroupPrefix ) ? ' data-hm-psr-other-field-prefix="' . esc_attr( $fieldGroupPrefix ) . '"' : '' ) . '>';
                                                    foreach ( $fields as $fieldId => $fieldDisplay ) {
                                                        $fieldClasses = '';
                                                        if ( in_array( $fieldId, array(
                                                                'builtin::variation_id',
                                                                'builtin::variation_sku',
                                                                'builtin::variation_attributes'
                                                            ) ) || substr( $fieldId, 0, 11 ) == 'variation::' ) {
                                                            $fieldClasses = 'hm_psr_variation_field';
                                                        }
                                                        if ( in_array( $fieldId, [
                                                                'builtin::product_id',
                                                                'builtin::variation_id',
                                                                'builtin::variation_sku',
                                                                'builtin::variation_attributes',
                                                                'builtin::product_sku',
                                                                'builtin::product_categories',
                                                                'builtin::product_price',
                                                                'builtin::product_price_with_tax',
                                                                'builtin::product_menu_order',
                                                                'builtin::product_stock',
                                                                'builtin::publish_time',
                                                                'builtin::product_desc',
                                                                'builtin::product_excerpt'
                                                            ]
                                                        ) ) {
                                                            $fieldClasses .= ( empty( $fieldClasses ) ? '' : ' ' ) . 'hm-psr-product-field';
                                                        }
                                                        if ( in_array( $fieldId, $noTotalFields ) ) {
                                                            $fieldClasses .= ( empty( $fieldClasses ) ? '' : ' ' ) . 'no-total-field no-round-field';
                                                        }

                                                        echo('<option value="'.esc_attr($fieldId).'"'.(empty($fieldClasses) ? '' : ' class="'.esc_attr($fieldClasses).'"').disabled($fieldGroupName != 'Built-in Fields' || substr($fieldDisplay, -6) == ' [Pro]', true, false).'>'.esc_html($fieldDisplay).'</option>');
                                                    }
                                                    echo '</optgroup>';
                                                }

                                                $addonFields = array_diff_key( $addonFields, $fieldOptions, $customFieldsFlat );
                                                if ( ! empty( $addonFields ) ) {
                                                    ?>
                                                    <optgroup
                                                            label="<?php esc_attr_e( 'Addon Fields', 'product-sales-report-for-woocommerce' ); ?>">
                                                        <?php
                                                        foreach ( $addonFields as $fieldId => $fieldData ) {
                                                            echo '<option value="' . esc_attr( $fieldId ) . '">' . esc_html( $fieldData['label'] ) . '</option>';
                                                        }
                                                        ?>
                                                    </optgroup>
                                                    <?php
                                                }
                                                ?>
                                            </select>

                                            <button type="button"
                                                    class="berrypress-btn berrypress-btn-primary"
                                                    id="hm-psr-button-add-field"
                                                    aria-label="<?php esc_attr_e( 'Add selected field to report', 'product-sales-report-for-woocommerce' ); ?>">
                                                <i class="berrypress-icon-add"></i>
                                                <?php esc_html_e( 'Add', 'product-sales-report-for-woocommerce' ); ?>
                                            </button>

                                        </div>

                                        <button type="button"
                                                class="berrypress-btn berrypress-btn-secondary ninjalytics-pro-feature"
                                                id="ags-psr-button-add-fieldbuilder"
                                                data-bp-tooltip="<?php esc_attr_e( 'Upgrade to Pro to define your own fields based on formulas, other fields, and functions.', 'product-sales-report-for-woocommerce' ); ?>"
                                                aria-label="<?php esc_attr_e( 'Create new calculated field', 'product-sales-report-for-woocommerce' ); ?>">
                                            <i class="berrypress-icon-calculate"></i>
                                            <?php self::proBadge() ?>
                                            <?php esc_html_e( 'Add Calculated Field', 'product-sales-report-for-woocommerce' ); ?>
                                        </button>
                                    </div>

                                    <p class="berrypress-text-secondary berrypress-color-disabled berrypress-fs-12 berrypress-mb-3"><?php esc_html_e( 'Click and drag to the left of the field name text box to re-order fields.', 'product-sales-report-for-woocommerce' ); ?> <?php self::proBadge() ?></p>

                                    <div class="ninjalytics-group-title ninjalytics-fields-refresh">
                                        <a class="berrypress-btn berrypress-btn-icon"
                                           href="<?php echo( esc_url( wp_nonce_url( add_query_arg( 'ninjalytics_action_free', 'update-fields' ), 'hm-psrp-update-fields' ) . '#orders' ) ); ?>">
                                            <i class="berrypress-icon-reset"></i>
                                            <span class="berrypress-visually-hidden"><?php esc_html_e('Refresh Fields', 'product-sales-report-for-woocommerce') ?></span>
                                        </a>
	                                    <?php esc_html_e('Refresh Fields', 'product-sales-report-for-woocommerce') ?>:
	                                    <?php self::docsLink( 'report-configuration/fields', 'refresh-fields' ); ?>
                                    </div>
                                 </div> <!-- /ninjalytics-section-body -->
                            </div> <!-- /ninjalytics-settings-toggle (Report Fields) -->

                            <?php $this->renderDisplaySection( $reportSettings, $orderBy ); ?>

							<?php
                                if ( ! $reportSettings['export_orders'] ) {
                                    $this->renderChartSection( $reportSettings );
                                }
                            ?>

                            <?php $this->renderAdvancedSection( $reportSettings ); ?>
                        </div> <!-- /ninjalytics-settings -->

                    </div> <!-- /ninjalytics-settings-panel -->

					<?php
					//ninjalytics_savePresetField();

					wp_nonce_field( 'hm-psr-run', 'hm-psr-nonce' );

					/*echo('<div class="hm_psr_submit_wrapper">
								<button type="submit" class="berrypress-btn berrypress-btn-primary ags-psr-button-download" name="ninjalytics_action_free" value="run" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'_blank\"); return true;">Download Report</button>

								<div class="hm_psr_email_report">

									<button type="submit" class="ags-psr-button-secondary" name="ninjalytics_action_free" value="email" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'\'); return true;">Email Report</button>
								</div>
							</div>');*/
					?>

                </div> <!-- /ninjalytics-settings-settings -->
            </form> <!-- /ninjalytics-form -->
		<?php } else { ?>
                <div class="ninjalytics-nj-reports-container">
                    <div class="ninjalytics-card-reports ninjalytics-col-1">

                        <div class="berrypress-card berrypress-card-100">
                            <div class="berrypress-card-header">
                                <h2><?php esc_html_e( 'Reports', 'product-sales-report-for-woocommerce' ); ?></h2>
                            </div>
                            <div class="berrypress-card-content">
                                <table id="ninjalytics_presets_panel">
                                    <tbody>
                                    <?php
                                    $runNonce = wp_create_nonce( 'hm-psr-run' );
                                    if ( is_array( $savedReportSettings ) && count( $savedReportSettings ) > 1 ) {
                                        uasort( $savedReportSettings, function ( $preset1, $preset2 ) {
                                            return strcasecmp(
                                                isset( $preset1['preset_name'] ) ? $preset1['preset_name'] : '',
                                                isset( $preset2['preset_name'] ) ? $preset2['preset_name'] : ''
                                            );
                                        } );
                                        foreach ( $savedReportSettings as $presetId => $preset ) {
                                            if ( ! $presetId ) {
                                                continue;
                                            }
                                            ?>
                                            <tr>
                                                <td class="ninjalytics-report-row-name">
                                                    <a class="ninjalytics-report-name"
                                                       href="?page=ninjalytics-free&amp;preset=<?php echo (int) $presetId; ?><?php if ( isset( $preset['_reporter'] ) ) { ?>&amp;ninjalytics_reporter=<?php echo esc_attr( $preset['_reporter'] ); } ?>"
                                                       aria-label="<?php esc_attr_e( 'Edit Report', 'product-sales-report-for-woocommerce' ); ?>">
                                                        <?php echo esc_html( $preset['preset_name'] ); ?>
                                                    </a>
                                                </td>
                                                <td class="ninjalytics-report-row-actions">
                                                    <a href="?page=ninjalytics-free&amp;ninjalytics_action_free=run&amp;preset=<?php echo (int) $presetId; ?><?php if ( isset( $preset['_reporter'] ) ) { ?>&amp;ninjalytics_reporter=<?php echo esc_attr( $preset['_reporter'] );
                                                    } ?>&amp;hm-psr-nonce=<?php echo esc_attr( $runNonce ); ?>"
                                                       aria-label="<?php esc_attr_e( 'Download', 'product-sales-report-for-woocommerce' ); ?>"
                                                       target="_blank" class="berrypress-btn berrypress-btn-icon">
                                                        <i class="berrypress-icon-download" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="?page=ninjalytics-free&amp;preset=<?php echo (int) $presetId; ?><?php if ( isset( $preset['_reporter'] ) ) { ?>&amp;ninjalytics_reporter=<?php echo esc_attr( $preset['_reporter'] );
                                                    } ?>" class="berrypress-btn berrypress-btn-icon"
                                                       aria-label="<?php esc_attr_e( 'Edit', 'product-sales-report-for-woocommerce' ); ?>">
                                                        <i class="berrypress-icon-edit" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="?page=ninjalytics-free&amp;ninjalytics_action_free=preset-del&amp;preset=<?php echo (int) $presetId; ?>&amp;_wpnonce=<?php echo esc_attr( $runNonce ); ?>"
                                                       class="berrypress-btn berrypress-btn-icon"
                                                       onclick="return confirm('<?php echo esc_js( __( 'Are you sure that you want to delete this report?', 'product-sales-report-for-woocommerce' ) ); ?>');"
                                                       aria-label="<?php esc_attr_e( 'Remove', 'product-sales-report-for-woocommerce' ); ?>">
                                                        <i class="berrypress-icon-delete" aria-hidden="true"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr class="ninjalytics-empty">
                                            <td style="text-align: left; font-weight: normal;">
                                                <div class="ninjalytics-welcome">
                                                    <?php esc_html_e( "You don't have any saved reports yet. Click the button below to get started!", 'product-sales-report-for-woocommerce' ); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="hm_psr-buttons-wrapper" class="berrypress-mt-3">
                            <a href="#" id="ags-psr-template-modal"
                               class="berrypress-btn berrypress-btn-primary ags-psr-ml-10"><?php esc_html_e( 'New Report', 'product-sales-report-for-woocommerce' ); ?></a>
                        </div>
                    </div>

                    <div class="berrypress-upgrade-box ninjalytics-col-2">
                            <div>
                                <div>
                                    <i class="berrypress-upgrade-box-icon berrypress-icon-filled berrypress-icon-lock"></i>

                                    <h4>
                                        <?php
                                        printf(
												// translators: %s: pro product name
                                                esc_html__( 'Upgrade to %s', 'product-sales-report-for-woocommerce' ),
                                                '<span class="brand">Ninjalytics Pro</span>'
                                        );
                                        ?>
                                    </h4>

                                    <p>
                                        <?php esc_html_e(
                                                'Grow smarter with advanced analytics made for WooCommerce store owners. Get full control over your data and make decisions with confidence.',
                                                'product-sales-report-for-woocommerce'
                                        ); ?>
                                    </p>
                                </div>

                                <div class="berrypress-upgrade-box-content berrypress-mb-3">
                                    <h5 class="berrypress-fw-bold berrypress-fs-14">
                                        <?php esc_html_e( "What's inside Pro:", 'product-sales-report-for-woocommerce' ); ?>
                                    </h5>

                                    <ul class="berrypress-upgrade-box-list">
                                        <li><i class="berrypress-icon-filled berrypress-icon-check"></i>
                                            <?php esc_html_e( 'Excel & HTML Exports', 'product-sales-report-for-woocommerce' ); ?>
                                        </li>
                                        <li><i class="berrypress-icon-filled berrypress-icon-check"></i>
                                            <?php esc_html_e( 'Custom & Calculated Fields', 'product-sales-report-for-woocommerce' ); ?>
                                        </li>
                                        <li><i class="berrypress-icon-filled berrypress-icon-check"></i>
                                            <?php esc_html_e( 'Send Email Reports', 'product-sales-report-for-woocommerce' ); ?>
                                        </li>
                                        <li><i class="berrypress-icon-filled berrypress-icon-check"></i>
                                            <?php esc_html_e( 'Rename, Reorder Report Columns', 'product-sales-report-for-woocommerce' ); ?>
                                        </li>
                                        <li><i class="berrypress-icon-filled berrypress-icon-check"></i>
                                            <?php esc_html_e( 'Filter by Custom Meta (e.g. delivery date)', 'product-sales-report-for-woocommerce' ); ?>
                                        </li>
                                        <li><i class="berrypress-icon-filled berrypress-icon-check"></i>
                                            <?php esc_html_e( 'More Custom Segments', 'product-sales-report-for-woocommerce' ); ?>
                                        </li>
                                        <li>
                                            <i class="berrypress-icon-filled berrypress-icon-check"></i>

                                            <?php
                                            printf(
                                            /* translators: 1: Android link, 2: iOS link */
                                                    esc_html__( 'Access your reports on the go with the Ninjalytics app for %1$s or %2$s (beta)', 'ninjalytics' ),
                                                    '<a class="berrypress-link" href="' . esc_url( 'https://play.google.com/store/apps/details?id=com.berrypress.ninjalytics' ) . '" target="_blank">' . esc_html__( 'Android', 'ninjalytics' ) . '</a>',
                                                    '<a class="berrypress-link" href="' . esc_url( 'https://apps.apple.com/se/app/ninjalytics/id6757487864?l=en-GB' ) . '" target="_blank">' . esc_html__( 'iOS', 'ninjalytics' ) . '</a>'
                                            );
                                            ?>
                                        </li>
                                        <li><i class="berrypress-icon-filled berrypress-icon-check"></i>
                                            <?php esc_html_e( 'And more!', 'product-sales-report-for-woocommerce' ); ?>
                                        </li>
                                    </ul>

                                    <p>
                                        <em><?php esc_html_e( 'Plus new features added regularly!', 'product-sales-report-for-woocommerce' ); ?></em>
                                    </p>
                                </div>

                                <div class="berrypress-upgrade-box-footer">
                                    <div class="berrypress-coupon berrypress-fs-14 berrypress-mb-2">
                                        <?php esc_html_e( 'Coupon code:', 'product-sales-report-for-woocommerce' ); ?>
                                        <strong id="couponCode">NINJALYTICS15</strong>
                                    </div>

                                    <p>
                                        <strong><?php esc_html_e( 'From $59 / year', 'product-sales-report-for-woocommerce' ); ?></strong>
                                    </p>

                                    <a
                                            href="https://berrypress.com/product/woocommerce/ninjalytics/?utm_campaign=upsell&source=ninjalytics-free-plugin"
                                            target="_blank"
                                            class="berrypress-btn berrypress-btn-primary"
                                    >
                                        <?php esc_html_e( 'View plans', 'product-sales-report-for-woocommerce' ); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                </div>

        </div>
    </div>


			<?php $this->renderTemplateModal(); ?>
		<?php } ?>

		<?php
	}
	
	private function renderAboutPage() {
		?>
		<div class="berrypress-about-page">
			<div class="about-header">
				<h1><?php esc_html_e('About Ninjalytics', 'product-sales-report-for-woocommerce'); ?></h1>
                <h2 class="about-description berrypress-fs-16 berrypress-fw-medium berrypress-mb-3">
					<?php esc_html_e('The new, enhanced version of', 'product-sales-report-for-woocommerce'); ?> <strong><?php esc_html_e('Product Sales Report for WooCommerce', 'product-sales-report-for-woocommerce'); ?></strong>
				</h2>
			</div>
			
			<div class="about-section berrypress-mb-4">
				<h3 class="berrypress-fs-18"><?php esc_html_e('Welcome to Ninjalytics', 'product-sales-report-for-woocommerce'); ?></h3>
				<p>
					<?php esc_html_e('Ninjalytics is the new, enhanced version of the Product Sales Report for WooCommerce plugin. We\'ve rebranded and expanded the functionality to give you more powerful sales reporting and help you make smarter business decisions.', 'product-sales-report-for-woocommerce'); ?>
				</p>
			</div>
			
			<div class="about-section berrypress-mb-4">
				<h3><?php esc_html_e('What\'s New in Ninjalytics?', 'product-sales-report-for-woocommerce'); ?></h3>
				<ul class="berrypress-feature-list">
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
			
			<div class="about-section berrypress-mb-4">
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
				<div class="berrypress-support-resources berrypress-mb-3">
					<div class="berrypress-support-links">
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
		<div class="berrypress-pro-page">
            <div class="about-header">
                <h1><?php esc_html_e('Ninjalytics Pro', 'product-sales-report-for-woocommerce'); ?></h1>
                <h2 class="about-description berrypress-fs-16 berrypress-fw-medium berrypress-mb-3">
                    <?php esc_html_e('Take your product sales reporting to the next level with advanced features and premium support.', 'product-sales-report-for-woocommerce'); ?>
                </h2>
            </div>

            <div class="about-section">
                <h3><?php esc_html_e('Featuring:', 'product-sales-report-for-woocommerce'); ?></h3>
                <ul class="berrypress-feature-list">
                    <li><?php esc_html_e('More built-in fields', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Product and variation meta fields, product taxonomy terms, and total order item meta fields', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Custom calculated fields', 'product-sales-report-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Access your reports on the go with the Ninjalytics app for Android (beta)', 'product-sales-report-for-woocommerce'); ?></li>
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
                <p class="berrypress-mb-3"><?php esc_html_e('Unlock advanced reporting features and take your store analytics to the next level with Ninjalytics Pro.', 'product-sales-report-for-woocommerce'); ?></p>

                <div class="berrypress-coupon berrypress-mb-2">
		            <?php esc_html_e('Coupon Code', 'product-sales-report-for-woocommerce') ?>: <strong id="couponCode">NINJALYTICS15</strong>
                </div>

                <p>
                    <a href="https://berrypress.com/product/woocommerce/ninjalytics/?utm_campaign=upsell&source=ninjalytics-free-plugin" target="_blank" class="berrypress-btn berrypress-btn-primary">
                    <?php esc_html_e('Purchase Pro', 'product-sales-report-for-woocommerce'); ?>
                    </a>
                </p>
            </div>
        </div>
		<?php
	}
}
