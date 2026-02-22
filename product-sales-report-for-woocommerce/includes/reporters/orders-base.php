<?php
namespace NinjalyticsFree\Reporters;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/base.php');

abstract class OrdersBase extends Base {
	
	public $ordersTypeColumn, $ordersStatusColumn, $orderItemsTable, $orderItemsOrderIdColumn, $orderItemsTypeColumn, $orderItemsMetaTable, $orderItemsIdColumn, $orderItemsNameColumn, $orderItemsMetaItemIdColumn, $orderItemAjdustmentsTable, $billingStateMetaKey, $orderCustomerFieldIsMeta, $orderCustomerFieldKey, $productPostType, $productCategoryTaxonomy, $productTagTaxonomy, $orderType, $refundOrderType, $productOrderItemsType, $completedOrderStatus, $defaultOrderStatuses, $hiddenOrderItemFields = [], $groupByFields, $orderFieldNames, $builtinFieldsExportOrders, $builtinFields;
	
	function getGroupByFields() {
		global $wpdb;
		
		if (!isset($this->groupByFields)) {
			$this->groupByFields = [];
		
			foreach ($this->getVirtualOrderMeta() as $fieldId => $field) {
				$this->groupByFields['o_'.$fieldId] = $fieldId;
			}
			
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$fields = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT meta_key FROM (
										SELECT meta_key
										FROM %i ometa
										JOIN %i orders ON (ometa.%i = orders.%i)
										WHERE orders.%i=%s
										ORDER BY orders.%i DESC
										LIMIT 10000
									) fields', $this->ordersMetaTable, $this->ordersTable, $this->ordersMetaOrderIdColumn, $this->ordersIdColumn, $this->ordersTypeColumn, $this->orderType, $this->ordersIdColumn));
			sort($fields);
			foreach ($fields as $field) {
				$this->groupByFields['o_'.$field] = $field;
			}
			$this->groupByFields['o_builtin::order_date'] = 'Order Date';
			$this->groupByFields['o_builtin::order_day'] = 'Order Day';
			$this->groupByFields['o_builtin::order_month'] = 'Order Month';
			$this->groupByFields['o_builtin::order_quarter'] = 'Order Quarter';
			$this->groupByFields['o_builtin::order_year'] = 'Order Year';
			
			if ($this->supports(PlatformFeatures::ORDER_SOURCE)) {
				$this->groupByFields['o_builtin::order_source'] = 'Order Source';
			}
			
			$fields = \NinjalyticsFree\ninjalytics_get_order_item_fields($this);
			foreach ($fields as $field) {
				$this->groupByFields['i_'.$field] = $field;
			}
			
			
			$this->groupByFields['i_builtin::item_price'] = 'Item Price';
			
			// hm-product-sales-report-pro.php
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$productFields = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT meta_key FROM (
												SELECT meta_key
												FROM '.$wpdb->prefix.'postmeta
												JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
												WHERE post_type=%s
												ORDER BY ID DESC
												LIMIT 10000
											) fields', $this->productPostType));
			
			foreach ($productFields as $productField) {
				$this->groupByFields[ 'p_'.$productField ] = $productField;
			}
		}
		
		return $this->groupByFields;
	}
	
		
	function getOrderFieldNames()
	{
		global $wpdb;
		if (!isset($this->orderFieldNames)) {
			$this->orderFieldNames = array_merge(
				array_keys($this->getVirtualOrderMeta()),
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- using table and field name vars
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->get_col(
					$wpdb->prepare('
							SELECT DISTINCT meta_key FROM (
								SELECT meta_key
								FROM '.$this->ordersMetaTable.' ometa
								JOIN '.$this->ordersTable.' orders ON (ometa.'.$this->ordersMetaOrderIdColumn.'=orders.'.$this->ordersIdColumn.')
								WHERE orders.'.$this->ordersTypeColumn.' = %s
								ORDER BY orders.'.$this->ordersIdColumn.' DESC
								LIMIT 10000
							) fields',
							$this->orderType
					),
					0
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		}
		return $this->orderFieldNames;
	}
	
	function getCustomFields($exportOrders, $includeDisplay = false, $productFieldsOnly = false)
	{
		global $wpdb;
		$var = 'customFieldNames'.($exportOrders ? '_export' : '');
		
		if (!isset($this->$var) || $productFieldsOnly) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$customFields = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT meta_key FROM (
												SELECT meta_key
												FROM '.$wpdb->prefix.'postmeta
												JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
												WHERE post_type=%s
												ORDER BY ID DESC
												LIMIT 10000
											) fields', $this->productPostType), 0);
			if ($productFieldsOnly) {
				foreach (get_object_taxonomies($this->productPostType) as $taxonomy) {
					if ($taxonomy != 'product_cat' && $taxonomy != 'product_tag') {
						$customFields[] = 'taxonomy::'.$taxonomy;
					}
				}
				return $customFields;
			}
			
			$this->$var = [
				'Product' => array_combine($customFields, $customFields),
				'Product Taxonomies' => array(),
			];
			
			
			foreach (get_object_taxonomies($this->productPostType) as $taxonomy) {
				$this->$var['Product Taxonomies']['taxonomy::'.$taxonomy] = $taxonomy;
			}
			
			if ( $this->supports(PlatformFeatures::VARIATIONS) ) {
				$this->$var['Product Variation'] = [];
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$variationFields = $wpdb->get_col('SELECT DISTINCT meta_key FROM (
														SELECT meta_key
														FROM '.$wpdb->prefix.'postmeta
														JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
														WHERE post_type="product_variation"
														ORDER BY ID DESC
														LIMIT 10000
													) fields', 0);
				foreach ($variationFields as $variationField)
					$this->$var['Product Variation']['variation::'.$variationField] = 'Variation '.$variationField;
			}
			
			$this->$var['Order Item'] = [];
			$orderItemFields = \NinjalyticsFree\ninjalytics_get_order_item_fields($this, false, true);
			if ($exportOrders) {
				/*
				foreach ($orderItemFields as $orderItemField) {
					$this->$var['Order Item']['order_item_meta::'.$orderItemField] = $orderItemField;
				}
				*/
			} else {
				$skipOrderItemFields = array('_qty', '_line_subtotal', '_line_total', '_line_tax', '_line_tax_data', '_tax_class', '_refunded_item_id');
				foreach ($orderItemFields as $orderItemField) {
					if (!in_array($orderItemField, $skipOrderItemFields) && !empty($orderItemField)) {
						$this->$var['Order Item']['order_item_total::'.$orderItemField] = 'Total Order Item '.$orderItemField;
					}
				}
			}
			
			if ($exportOrders) {
				foreach (\NinjalyticsFree\ninjalytics_getCustomerFieldNames() as $customerField) {
					$this->$var['Customer User']['customer_user_meta::'.$customerField] = $customerField;
				}
				
			}
		}
		return $this->$var;
	}
	
	function getBuiltInFields($exportOrders) {
		$fieldsKey = $exportOrders ? 'builtinFieldsExportOrders' : 'builtinFields';
		if (!isset($this->$fieldsKey)) {
			$this->$fieldsKey = [
				'builtin::product_id' => 'Product ID',
				'builtin::product_sku' => 'Product SKU'
			]
			+ ($this->supports(PlatformFeatures::VARIATIONS) ? [
				'builtin::variation_id' => 'Variation ID',
				'builtin::variation_sku' => 'Variation SKU',
				'builtin::variation_attributes' => 'Variation Attributes',
			] : [])
			+ [
				'builtin::product_name' => 'Product Name',
				'builtin::product_categories' => 'Product Categories',
				'builtin::product_price' => 'Current Product Price [Pro]',
				'builtin::product_price_with_tax' => 'Current Product Price (Incl. Tax) [Pro]',
				'builtin::product_stock' => 'Current Stock Quantity',
				'builtin::quantity_sold' => 'Quantity Sold',
				'builtin::gross_sales' => 'Gross Sales',
				'builtin::gross_after_discount' => 'Gross Sales (After Discounts)',
				'builtin::discount' => 'Total Discount Amount',
				'builtin::taxes' => 'Taxes'
			]
			+ ($this->supports(PlatformFeatures::COGS) ? [
				'builtin::cogs' => 'Cost of Goods Sold',
				'builtin::profit' => 'Profit',
				'builtin::margin' => 'Gross Margin',
				'builtin::item_cogs' => 'Current COGS per Item',
			] : []);
			
			foreach (\NinjalyticsFree\ninjalytics_get_tax_types() as $taxTypeId => $taxType) {
				$this->$fieldsKey['builtin::taxes_'.$taxTypeId] = 'Taxes - '.$taxType;
			}
			
			$this->$fieldsKey = array_merge(
				$this->$fieldsKey,
				[
					'builtin::total_with_tax' => 'Total Sales Including Tax',]
				+ ($this->supports(PlatformFeatures::SHIPPING) ? [
					'builtin::order_shipping_methods' => 'Order Shipping Methods [Pro]'
				] : [])
				+ [
					'builtin::refund_quantity' => 'Quantity Refunded [Pro]',
					'builtin::refund_gross' => 'Gross Amount Refunded (Excl. Tax) [Pro]',
					'builtin::refund_with_tax' => 'Gross Amount Refunded (Incl. Tax) [Pro]',
					'builtin::refund_taxes' => 'Tax Refunded [Pro]',
					'builtin::publish_time' => 'Product Publish Date/Time',
					'builtin::line_item_count' => 'Line Item Count',
					'builtin::order_count' => 'Order Count',
					'builtin::product_desc' => 'Product Description',
					'builtin::product_excerpt' => 'Product Description Excerpt',
					'builtin::product_menu_order' => 'Product Menu Order',
					'builtin::avg_order_total' => 'Average Order Total',
				]
			);
		}
		return $this->$fieldsKey;
	}
	
	function getRow($product, $fields, &$totals, $fieldbuilderFields, $fieldbuilderDependencies) {
		return \NinjalyticsFree\ninjalytics_get_product_row($product, $fields, $totals, $fieldbuilderFields, $fieldbuilderDependencies);
	}
	
	public function getPrimaryItemsName() {
		return 'Orders';
	}
	
	public function getReportTemplates() {
		return apply_filters('ninjalytics_report_templates', [
			'new_sales' => [
				'preset_name' => __( 'New Sales Report', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Create a report with aggregated sales data, where each row may reflect data from multiple orders.', 'product-sales-report-for-woocommerce' ),
				'icon'        => 'icon_4'
			],
			'new_export' => [
				'preset_name' => __( 'New Order Export', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Export details of individual orders and their line items for deeper analysis.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'icon'        => 'icon_3'
			],
			'all_sales' => [
				'preset_name' => 'All Sales',
				'_description' => __( 'Comprehensive overview of sales performance across your entire catalogue.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'line_totals',
				'fields' => [ 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_after_discount' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'icon' => 'icon_4'
			],
			'top_selling' => [
				'preset_name' => 'Top Selling Products',
				'_description' => __( 'Identify top selling products with quantities and revenue in a single view.', 'product-sales-report-for-woocommerce' ),
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', 'builtin::quantity_sold', 'builtin::gross_after_discount' ],
				'chart_fields' => [ 'builtin::gross_after_discount' ],
				'orderby' => 'builtin::gross_after_discount',
				'variations' => 0,
				'limit_on' => 1,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_3',
			],
			'top_rated' => [
				'preset_name' => 'Top Rated Products',
				'_description' => __( 'See products with the highest customer ratings to highlight quality performers.', 'product-sales-report-for-woocommerce' ),
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', '_wc_average_rating' ],
				'field_names' => [ '_wc_average_rating' => 'Rating' ],
				'chart_fields' => [ '_wc_average_rating' ],
				'orderby' => '_wc_average_rating',
				'variations' => 0,
				'limit_on' => 1,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_5',
				'pro' => true
			],
			'stock' => [
				'preset_name' => 'Stock Report',
				'_description' => __( 'Monitor stock levels and surface products that need replenishment.', 'product-sales-report-for-woocommerce' ),
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', 'builtin::product_stock' ],
				'chart_fields' => [ 'builtin::product_stock' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'variations' => 0,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_6'
			],
			'state_sales' => [
				'preset_name' => 'Sales by US State',
				'_description' => __( 'Break down sales totals by US state for regional insights.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o_'.$this->billingStateMetaKey,
				'fields' => [ 'builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'field_names' => [ 'builtin::groupby_field' => 'Billing State' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'order_meta_filter_on' => 1,
				'order_meta_filter_key' => '_billing_country',
				'order_meta_filter_op' => '=',
				'order_meta_filter_value' => 'US',
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_7',
			],
			'product_sales' => [
				'preset_name' => 'Sales by Product',
				'_description' => __( 'Review sales totals grouped by product to spot trends quickly.', 'product-sales-report-for-woocommerce' ),
				'chart_type' => 'pie',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'variations' => 0,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_8'
			],
			'payment_method_sales' => [
				'preset_name' => 'Sales by Payment Method',
				'_description' => __( 'Compare revenue by payment method to understand buyer preferences.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o__payment_method',
				'fields' => ['builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold'],
				'field_names' => ['builtin::groupby_field' => 'Payment Method' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_9'
			],
			'currency_sales' => [
				'preset_name' => 'Sales by Currency',
				'_description' => __( 'Track sales totals by currency when selling internationally.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o__order_currency',
				'fields' => ['builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold'],
				'field_names' => ['builtin::groupby_field' => 'Currency' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_1'
			],
			'country_sales' => [
				'preset_name' => 'Sales by Country',
				'_description' => __( 'Analyse performance by billing country for geo insights.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o__billing_country',
				'fields' => ['builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold'],
				'field_names' => ['builtin::groupby_field' => 'Billing Country' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_2'
			]
		]);
	}
	
	public function getDataParams($baseFields) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
		
		$groupByProducts = empty($_POST['disable_product_grouping']) || (int) $_POST['disable_product_grouping'] < 0;
		$intermediateRounding = !empty( $_POST['intermediate_rounding'] );
		$exportOrders = !empty($_POST['export_orders']);
		
		$standardFields = $this->getStandardFields();
		$reportVariations = $this->supports(PlatformFeatures::VARIATIONS) && !empty($_POST['variations']);
		
		
		// Based on woocoommerce/includes/admin/reports/class-wc-report-sales-by-product.php
	$dataParams = array(
		
		// Following code provided by and copyright Daniel von Mitschke, released under GNU General Public License (GPL) version 2 or later, used under GPL version 3 or later (see license/LICENSE.TXT)
		// Modified by Jonathan Hall
		$standardFields['order_item_name'][1] => array(
			'type' => $standardFields['order_item_name'][0],
			'function' => $exportOrders ? '' : 'GROUP_CONCAT',
			'distinct' => true,
			'join_type' => 'LEFT',
			'name' => 'product_name'
		),
		// End code provided by Daniel von Mitschke
		$standardFields['quantity'][1] => array(
			'type' => $standardFields['quantity'][0],
			'order_item_type' => 'line_item',
			'function' => ($exportOrders ? '' : 'SUM'),
			'join_type' => 'LEFT',
			'name' => 'quantity'
		),
		$standardFields['line_subtotal'][1] => array(
			'type' => $standardFields['line_subtotal'][0],
			'order_item_type' => 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'gross'
		),
		$standardFields['line_total'][1] => array(
			'type' => $standardFields['line_total'][0],
			'order_item_type' => 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'gross_after_discount'
		),
		$standardFields['line_tax'][1] => array(
			'type' => $standardFields['line_tax'][0],
			'order_item_type' => 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'taxes'
		)
	);
	
	if ($exportOrders || in_array('builtin::order_count', $baseFields)) {
       $dataParams[ $standardFields['order_id'][1] ] = array(
            'type' => $standardFields['order_id'][0],
            'name' => 'order_id',
			'function' => empty($_POST['export_orders']) ?  'GROUP_CONCAT' : '',
        );
	}
	
	if ( $exportOrders ) {
		
        $dataParams[ $standardFields['order_item_id'][1] ] = array(
            'type' => $standardFields['order_item_id'][0],
            'function' => '',
            'name' => 'order_item_id',
            //'join_type' => 'LEFT'
        );
        $dataParams[ $standardFields['order_item_type'][1] ] = array(
            'type' => $standardFields['order_item_type'][0],
            'function' => '',
            'name' => 'order_item_type'
		);
		if (in_array('builtin::order_status', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['status'][1] ] = array(
				'type' => $standardFields['status'][0],
				'function' => '',
				'name' => 'order_status'
			);
		}
		if (in_array('builtin::order_total', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['order_total'][1] ] = array(
				'type' => $standardFields['order_total'][0],
				'function' => '',
				'name' => 'order_total',
				'join_type' => 'LEFT'
			);
		}
		if ( in_array('builtin::creator_roles', $_POST['fields'] ?? []) ) {
			$dataParams[ $standardFields['customer_id'][1] ] = array(
				'type' => $standardFields['customer_id'][0],
				'function' => '',
				'name' => 'order_creator_id'
			);
		}
		if (in_array('builtin::order_date', $_POST['fields'] ?? []) || in_array('builtin::order_date_only', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['order_date'][1] ] = array(
				'type' => $standardFields['order_date'][0],
				'function' => '',
				'name' => 'order_date'
			);
		}
		
        if ( in_array('order_parent', $_POST['fields'] ?? []) ) {
            $reportData[$isHpos ? 'parent_order_id' : 'post_parent'] = array(
                'type' => 'post_data',
                'function' => '',
                'name' => 'parent_order_id'
            );
        }

		if (in_array('builtin::billing_name', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['billing_first_name'][1] ] = array(
				'type' => $standardFields['billing_first_name'][0],
				'name' => 'billing_first_name',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['billing_last_name'][1] ] = array(
				'type' => $standardFields['billing_last_name'][0],
				'name' => 'billing_last_name',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::billing_phone', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['billing_phone'][1] ] = array(
				'type' => $standardFields['billing_phone'][0],
				'name' => 'billing_phone',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::billing_email', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['billing_email'][1] ] = array(
				'type' => $standardFields['billing_email'][0],
				'name' => 'billing_email',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::billing_address', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['billing_address_1'][1] ] = array(
				'type' => $standardFields['billing_address_1'][0],
				'name' => 'billing_address_1',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['billing_address_2'][1] ] = array(
				'type' => $standardFields['billing_address_2'][0],
				'name' => 'billing_address_2',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['billing_city'][1] ] = array(
				'type' => $standardFields['billing_city'][0],
				'name' => 'billing_city',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['billing_postcode'][1] ] = array(
				'type' => $standardFields['billing_postcode'][0],
				'name' => 'billing_postcode',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$hasBillingAddressField = true;
		}
		if (!empty($hasBillingAddressField) || in_array('builtin::billing_state', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['billing_state'][1] ] = array(
				'type' => $standardFields['billing_state'][0],
				'name' => 'billing_state',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['billing_country'][1] ] = array(
				'type' => $standardFields['billing_country'][0],
				'name' => 'billing_country',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::shipping_name', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['shipping_first_name'][1] ] = array(
				'type' => $standardFields['shipping_first_name'][0],
				'name' => 'shipping_first_name',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['shipping_last_name'][1] ] = array(
				'type' => $standardFields['shipping_last_name'][0],
				'name' => 'shipping_last_name',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::shipping_phone', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['shipping_phone'][1] ] = array(
				'type' => $standardFields['shipping_phone'][0],
				'name' => 'shipping_phone',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::shipping_email', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['shipping_email'][1] ] = array(
				'type' => $standardFields['shipping_email'][0],
				'name' => 'shipping_email',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::shipping_address', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['shipping_address_1'][1] ] = array(
				'type' => $standardFields['shipping_address_1'][0],
				'name' => 'shipping_address_1',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['shipping_address_2'][1] ] = array(
				'type' => $standardFields['shipping_address_2'][0],
				'name' => 'shipping_address_2',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['shipping_city'][1] ] = array(
				'type' => $standardFields['shipping_city'][0],
				'name' => 'shipping_city',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['shipping_postcode'][1] ] = array(
				'type' => $standardFields['shipping_postcode'][0],
				'name' => 'shipping_postcode',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$hasShippingAddressField = true;
		}
		if (!empty($hasShippingAddressField) || in_array('builtin::shipping_state', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['shipping_state'][1] ] = array(
				'type' => $standardFields['shipping_state'][0],
				'name' => 'shipping_state',
				'function' => '',
				'join_type' => 'LEFT'
			);
			$dataParams[ $standardFields['shipping_country'][1] ] = array(
				'type' => $standardFields['shipping_country'][0],
				'name' => 'shipping_country',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		if (in_array('builtin::customer_order_note', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['customer_note'][1] ] = array(
				'type' => $standardFields['customer_note'][0],
				'function' => 'customer_note',
				'name' => 'customer_order_note'
			);
		}
		if (in_array('builtin::order_item_name', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['order_item_name'][1] ] = array(
				'type' => $standardFields['order_item_name'][0],
				'function' => '',
				'name' => 'order_item_name'
			);
		}
	}
	
	if ($this->supports(PlatformFeatures::LINE_ITEM_ADJUSTMENTS) && !empty($_POST['adjustments'])) {
		$dataParams['order_item_adjustment.subtotal'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'adjustment_subtotal'
		);
		$dataParams['order_item_adjustment.total'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'adjustment_total'
		);
		$dataParams['order_item_adjustment.tax'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'adjustment_tax'
		);
		
	}
	
	if ( !empty($exportOrders) || $groupByProducts || $_POST['disable_product_grouping'] == 2 ) {
		$dataParams[$standardFields['product_id'][1]] = array(
			'type' => $standardFields['product_id'][0],
			'order_item_type' => 'line_item',
			'function' => empty($exportOrders) && $_POST['disable_product_grouping'] == -1 ? 'GROUP_CONCAT' : '',
			'join_type' => 'LEFT',
			'name' => 'product_id'
		);
	}
	
	if ($reportVariations && $groupByProducts) {
		$dataParams[$standardFields['variation_id'][1]] = array(
			'type' => $standardFields['variation_id'][0],
			'order_item_type' => 'line_item',
			'function' => empty($exportOrders) && $_POST['disable_product_grouping'] == -1 ? 'GROUP_CONCAT' : '',
			'join_type' => 'LEFT',
			'name' => 'variation_id'
		);
	}

	// Add shipping methods virtual meta field when needed
	if (in_array('builtin::order_shipping_methods', $baseFields)) {
		$dataParams['_order_shipping_method'] = [
			'type' => 'meta',
			'function' => empty($exportOrders) ?  'GROUP_CONCAT' : '',
			'join_type' => 'LEFT',
			'name' => 'order_shipping_methods'
		];
	}

	if (in_array('builtin::line_item_count', $baseFields) || \NinjalyticsFree\ninjalytics_hasTaxBreakoutField($baseFields)) {
		$dataParams[$this->orderItemsIdColumn] = array(
			'type' => 'order_item',
			'order_item_type' => 'line_item',
				'function' => empty($exportOrders) ?  'GROUP_CONCAT' : '',
				'join_type' => 'LEFT',
				'name' => 'order_item_ids'
		);
	}
	
	if ( in_array('builtin::avg_order_total', $baseFields) ) {
		$dataParams[$standardFields['order_total'][1]] = array(
			'type' => $standardFields['order_total'][0],
			'function' => 'AVG',
			'join_type' => 'LEFT',
			'name' => 'avg_order_total'
		);
	}
	foreach ($baseFields as $field) {
		if (substr($field, 0, 18) == 'order_item_total::') {
			$fieldNameRaw = substr($field, 18);
			$fieldName = esc_sql($fieldNameRaw);
			
			$dataParams[$fieldName] = array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => ($exportOrders ? '' :  'SUM'),
				'join_type' => 'LEFT',
				'name' => \NinjalyticsFree\ninjalytics_fixSanitizeKey(sanitize_key('order_item_total__'.$fieldNameRaw))
			);
		} else if (!empty($_POST['enable_custom_segments']) && ($field == 'builtin::groupby_field' || $field == 'builtin::groupby_field2' || $field == 'builtin::groupby_field3' || $field == 'builtin::groupby_field4' || $field == 'builtin::groupby_field5') ) {
			
			$groupbyFieldNum = $field == 'builtin::groupby_field' ? '' : $field[22];
			
			$groupByField = sanitize_text_field(wp_unslash($_POST['groupby'.$groupbyFieldNum] ?? ''));
			if ( !empty($groupByField) && $groupByField != 'i_builtin::item_price' ) {
				if (in_array($groupByField, array('o_builtin::order_month', 'o_builtin::order_quarter', 'o_builtin::order_year', 'o_builtin::order_date', 'o_builtin::order_day'))) {
					switch ($groupByField) {
						case 'o_builtin::order_month':
							$sqlFunction = 'MONTH';
							break;
						case 'o_builtin::order_quarter':
							$sqlFunction = 'QUARTER';
							break;
						case 'o_builtin::order_year':
							$sqlFunction = 'YEAR';
							break;
						case 'o_builtin::order_day':
							$sqlFunction = 'DAY';
							break;
						default:
							$sqlFunction = 'DATE';
					}
					$dataParams[$sqlFunction.'.'.$standardFields['order_date'][1]] = array(
						'type' => $standardFields['order_date'][0],
						'order_item_type' => 'line_item',
						'function' => $sqlFunction,
						'join_type' => 'LEFT',
						'name' => 'groupby_field'.$groupbyFieldNum
					);
				} else if ($this->supports(PlatformFeatures::ORDER_SOURCE) && $groupByField == 'o_builtin::order_source') {
					// Replicated in shipping data function below
					$dataParams['_wc_order_attribution_source_type'] = [
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_field'.$groupbyFieldNum
					];
					$dataParams['_wc_order_attribution_utm_source'] = [
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_field'.$groupbyFieldNum.'b'
					];
				} else if ($groupByField[0] != 'p') {
					$fieldName = esc_sql(substr($groupByField, 2));
					
					$dataParams[$fieldName] = array(
						'type' => ($groupByField[0] == 'i' ? 'order_item_meta' : 'meta'),
						'order_item_type' => 'line_item',
						'function' => '',
						'join_type' => 'LEFT',
						'name' => 'groupby_field'.$groupbyFieldNum
					);
					
				}
				
			}
		}
	}
		return $dataParams;
		
		
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
	
	public function getGroupByFieldTypes() {
		return [
			'o' => 'Order',
			'i' => 'Order Line Item',
			'p' => 'Product'
		];
	}
	
	function getSelectForField($key, $type) {
			
		switch ( $type ) {
			case 'meta':
				$virtualMeta = $this->getVirtualOrderMeta();
				if (isset($virtualMeta[$key])) {
					return $virtualMeta[$key]['field'];
				}
				break;
			case 'order_item_meta':
				return "order_item_meta_{$key}.meta_value";
			case 'order_item':
				return "order_items.{$key}";
			case 'order_item_adjustment':
				return "order_item_adjustments.{$key}";
		}
		
		return parent::getSelectForField($key, $type);
	}
	
	function addJoinForField($raw_key, $key, $value, &$joins, &$joinParams) {
		$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
		$type      = isset( $value['type'] ) ? $value['type'] : false;
		switch ( $type ) {
			case 'meta':
				$virtualMeta = $this->getVirtualOrderMeta();
				if (isset($virtualMeta[$key])) {
					if (isset($virtualMeta[$key]['joins'])) {
						foreach ($virtualMeta[$key]['joins'] as $joinId => $joinSql) {
							$joins[$joinId] = "{$join_type} JOIN {$joinSql}";
						}
					}
					return;
				}
				break;
			case 'order_item_meta':
				if ( !empty( $value['order_item_type'] )  || !isset($joins['order_items']) ) {
					$joins['order_items'] = "{$join_type} JOIN {$this->orderItemsTable} AS order_items ON (posts.{$this->ordersIdColumn} = order_items.{$this->orderItemsOrderIdColumn})";
					if ( ! empty( $value['order_item_type'] ) ) {
						$joins['order_items'] .= " AND (order_items.{$this->orderItemsTypeColumn} = %s)";
						$joinParams['order_items'] = [$value['order_item_type']];
					}
				}

				$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$this->orderItemsMetaTable} AS order_item_meta_{$key} ON " .
													"(order_items.{$this->orderItemsIdColumn} = order_item_meta_{$key}.{$this->orderItemsMetaItemIdColumn}) " .
													" AND (order_item_meta_{$key}.meta_key = %s)";
				$joinParams["order_item_meta_{$key}"] = [$raw_key];
				return;
			case 'order_item':
				if (!isset($joins['order_items'])) {
					$joins['order_items'] = "{$join_type} JOIN {$this->orderItemsTable} AS order_items ON (posts.{$this->ordersIdColumn} = order_items.{$this->orderItemsOrderIdColumn})";
				}
				return;
			case 'order_item_adjustment':
				$joins['order_item_adjustment'] = "{$join_type} JOIN {$this->orderItemAjdustmentsTable} AS order_item_adjustments ON (order_item_adjustments.object_type='order_item' AND order_item_adjustments.object_id = order_items.{$this->orderItemsIdColumn})";
				return;
		}
		
		parent::addJoinForField($raw_key, $key, $value, $joins, $joinParams);
	}
	
	
	function getWhereMetaField($key, $value) {
		if ( isset( $value['type'] ) && 'order_item_meta' === $value['type'] ) {
			return "order_item_meta_{$key}.meta_value";
		}
		$virtualMeta = $this->getVirtualOrderMeta();
		if ( isset($virtualMeta[$key]) ) {
			return $virtualMeta[$key]['field'];
		}
		return parent::getWhereMetaField($key, $value);
	}
	
}
