<?php
namespace NinjalyticsFree\Reporters;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/base.php');

abstract class OrdersBase extends Base {
	
	public $ordersTypeColumn, $ordersStatusColumn, $orderItemsTable, $orderItemsOrderIdColumn, $orderItemsTypeColumn, $orderItemsMetaTable, $orderItemsIdColumn, $orderItemsNameColumn, $orderItemsMetaItemIdColumn, $orderItemAjdustmentsTable, $billingStateMetaKey, $orderCustomerFieldIsMeta, $orderCustomerFieldKey, $productPostType, $productCategoryTaxonomy, $productTagTaxonomy, $orderType, $refundOrderType, $productOrderItemsType, $completedOrderStatus, $defaultOrderStatuses, $hiddenOrderItemFields = [], $groupByFields, $orderFieldNames, $builtinFieldsExportOrders, $builtinFields;
	
	function getAllowedWhereColumns() {
		$whereColumns = parent::getAllowedWhereColumns();
		
		// Must be sanitized!
		$whereColumns[] = 'order_items.'.sanitize_key($this->orderItemsTypeColumn);
		
		return $whereColumns;
	}
	
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
			$this->groupByFields['o_builtin::order_week'] = 'Order Week';
			
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
	
		
	function getOrderFieldNames($includeVirtual=true)
	{
		global $wpdb;
		if (!isset($this->orderFieldNames)) {
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- using table and field name vars
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->orderFieldNames = $wpdb->get_col(
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
				);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		}
		return $includeVirtual ? array_merge(array_keys($this->getVirtualOrderMeta()), $this->orderFieldNames) : $this->orderFieldNames;
	}
	
	function getExportGroupingField()
	{
		return empty($_POST['one_line_per_order']) ? 'order_items.'.$this->orderItemsIdColumn : 'order_items.'.$this->orderItemsOrderIdColumn;
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
			
			$orderItemFields = \NinjalyticsFree\ninjalytics_get_order_item_fields($this, false, !$exportOrders);
			if ($exportOrders) {
				$this->$var['Order'] = [];
				foreach ($this->getOrderFieldNames() as $orderField) {
					$this->$var['Order']['order_meta::'.$orderField] = $orderField;
				}
				$this->$var['Order Item'] = [];
				foreach ($orderItemFields as $orderItemField) {
					$this->$var['Order Item']['order_item_meta::'.$orderItemField] = $orderItemField;
				}
				$this->$var['Customer User'] = [];
				foreach (\NinjalyticsFree\ninjalytics_getCustomerFieldNames() as $customerField) {
					$this->$var['Customer User']['customer_user_meta::'.$customerField] = $customerField;
				}
			} else {
				$this->$var['Order Item'] = [];
				$skipOrderItemFields = array('_qty', '_line_subtotal', '_line_total', '_line_tax', '_line_tax_data', '_tax_class', '_refunded_item_id');
				foreach ($orderItemFields as $orderItemField) {
					if (!in_array($orderItemField, $skipOrderItemFields) && !empty($orderItemField)) {
						$this->$var['Order Item']['order_item_total::'.$orderItemField] = 'Total Order Item '.$orderItemField;
					}
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
				'builtin::product_image' => 'Product Image',
				'builtin::product_sku' => 'Product SKU',
				'builtin::item_sku' => 'Item SKU',
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
				'builtin::product_stock' => 'Current Stock Quantity'
			]
			+ ($exportOrders
				? [
					'builtin::quantity' => 'Line Item Quantity',
					'builtin::line_subtotal' => 'Line Item Gross',
					'builtin::line_total' => 'Line Item Gross After Discounts',
					'builtin::line_tax' => 'Line Item Tax',
				]
				: [
					'builtin::quantity_sold' => 'Quantity Sold',
					'builtin::gross_sales' => 'Gross Sales',
					'builtin::gross_after_discount' => 'Gross Sales (After Discounts)',
					'builtin::discount' => 'Total Discount Amount [Pro]',
					'builtin::taxes' => 'Taxes'
				]
			)
			+ ($this->supports(PlatformFeatures::COGS) ? [
				'builtin::cogs' => 'Cost of Goods Sold',
				'builtin::profit' => 'Profit',
				'builtin::margin' => 'Gross Margin',
				'builtin::refund_cogs' => 'Refund Cost of Goods Sold [Pro]',
				'builtin::item_cogs' => 'Current COGS per Item',
			] : []);
			
			foreach (\NinjalyticsFree\ninjalytics_get_tax_types() as $taxTypeId => $taxType) {
				$this->$fieldsKey['builtin::taxes_'.$taxTypeId] = 'Taxes - '.$taxType;
			}
			
			$this->$fieldsKey = array_merge(
				$this->$fieldsKey,
				($exportOrders
				? [
					'builtin::line_total_with_tax' => 'Line Item Total With Tax',
				]
				: [
					'builtin::total_with_tax' => 'Total Sales Including Tax',
				])
				+ ($this->supports(PlatformFeatures::SHIPPING) ? [
					'builtin::order_shipping_methods' => 'Order Shipping Methods'
				] : [])
				+ ($this->supports(PlatformFeatures::COUPONS) ? [
					'builtin::order_coupons' => 'Order Coupons',
				] : [])
				+ ($exportOrders
					? []
					: [
						'builtin::refund_quantity' => 'Quantity Refunded [Pro]',
						'builtin::refund_gross' => 'Gross Amount Refunded (Excl. Tax) [Pro]',
						'builtin::refund_with_tax' => 'Gross Amount Refunded (Incl. Tax) [Pro]',
						'builtin::refund_taxes' => 'Tax Refunded [Pro]',
						'builtin::order_count' => 'Order Count',
						'builtin::line_item_count' => 'Line Item Count',
						'builtin::unique_item_count' => 'Unique Item Count [Pro]',
						'builtin::avg_order_total' => 'Average Order Total',
					])
				+ [
					'builtin::publish_time' => 'Product Publish Date/Time',
					'builtin::product_desc' => 'Product Description',
					'builtin::product_excerpt' => 'Product Description Excerpt',
					'builtin::product_menu_order' => 'Product Menu Order',
				]
				+ (
					$exportOrders
						? ([
							'builtin::order_id' => 'Order ID',
							'builtin::order_status' => 'Order Status',
							'builtin::order_total' => 'Order Total',
							'builtin::order_date' => 'Order Date/Time',
							'builtin::order_date_only' => 'Order Date',
							'builtin::order_parent' => 'Parent Order',
							'builtin::order_item_type' => 'Order Item Type',
							'builtin::order_item_name' => 'Line Item Name',
							'builtin::billing_name' => 'Billing Name',
							'builtin::billing_phone' => 'Billing Phone',
							'builtin::billing_email' => 'Billing Email',
							'builtin::billing_address' => 'Billing Address',
							'builtin::billing_state' => 'Billing State',
							'builtin::billing_country' => 'Billing Country',
							'builtin::shipping_name' => 'Shipping Name',
							'builtin::shipping_phone' => 'Shipping Phone',
							'builtin::shipping_email' => 'Shipping Email',
							'builtin::shipping_address' => 'Shipping Address',
							'builtin::shipping_state' => 'Shipping State',
							'builtin::shipping_country' => 'Shipping Country',
							'builtin::customer_order_note' => 'Customer Order Note [Pro]',
							'builtin::order_note_most_recent' => 'Order Note - Most Recent [Pro]',
							'builtin::order_notes_user' => 'User Order Notes [Pro]',
							'builtin::order_notes_all' => 'Order Notes - All [Pro]',
							'builtin::order_shipping_methods' => 'Order Shipping Methods',
							'builtin::order_shipping_cost' => 'Order Shipping Cost [Pro]',
							'builtin::order_shipping_tax' => 'Order Shipping Tax [Pro]',
							'builtin::order_shipping_cost_with_tax' => 'Order Shipping Cost With Tax [Pro]',
							'builtin::order_total_qty' => 'Order Total Item Quantity',
							'builtin::order_total_fees' => 'Total Order Fees [Pro]',
							'builtin::order_total_fees_with_tax' => 'Total Order Fees With Tax [Pro]',
							'builtin::customer_roles' => 'Customer User Roles [Pro]',
						]
						+ ($this->supports(PlatformFeatures::ORDER_CREATOR) ? [
							'builtin::creator_roles' => 'Order Creator User Roles [Pro]'
						] : [])
						+ [
							'builtin::order_item_id' => 'Order Item ID',
							'builtin::order_product_total' => 'Order Product Total'
						])
						: []
				)
			);
		}
		return $this->$fieldsKey;
	}
	
	function getBuiltInOrderRelatedExportFields() {
		return ['builtin::order_shipping_methods', 'builtin::order_coupons', 'builtin::order_id', 'builtin::order_status', 'builtin::order_total', 'builtin::order_date', 'builtin::order_date_only', 'builtin::order_parent', 'builtin::billing_name', 'builtin::billing_phone', 'builtin::billing_email', 'builtin::billing_address', 'builtin::billing_state', 'builtin::billing_country', 'builtin::shipping_name', 'builtin::shipping_phone', 'builtin::shipping_email', 'builtin::shipping_address', 'builtin::shipping_state', 'builtin::shipping_country', 'builtin::order_total_qty', 'builtin::order_product_total'];
	}
	
	function getRow($product, $fields) {
		return \NinjalyticsFree\ninjalytics_get_product_row($product, $fields);
	}
	
	public function getPrimaryItemsName() {
		return 'Orders';
	}
	
	public function getReportTemplates() {
		$templates = [
			'new_sales' => [
				'preset_name' => __( 'Blank Product Report', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'A minimal starting point for a product report - pick your own fields and settings from scratch.', 'product-sales-report-for-woocommerce' ),
				'fields' => [ 'builtin::product_image', 'builtin::product_id', 'builtin::product_sku', 'builtin::variation_sku', 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_sales' ],
				'chart_fields' => [ 'builtin::gross_sales', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'blank' => true,
				'icon'        => 'icon_4'
			],
			'new_export' => [
				'preset_name' => __( 'Blank Order Export', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'A minimal starting point for an order export - choose your own fields, one row per order or per line item.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'blank' => true,
				'icon'        => 'icon_3'
			],
			'order_export_accounting' => [
				'preset_name' => __( 'Accounting', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'One row per completed order with totals, tax, and billing country for bookkeeping.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'format_amounts' => 1,
				'include_totals' => 1,
				'filename' => '[preset] - [created Y-m-d]',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'lastmonth',
				'use_wp_date' => 1,
				'alt_order_date_local' => 1,
				'one_line_per_order' => 1,
				'order_fields_once' => 1,
				'order_total_once' => 1,
				'order_statuses' => [ 'wc-completed' ],
				'fields' => [
					'builtin::order_id',
					'builtin::order_date_only',
					'builtin::billing_name',
					'builtin::billing_country',
					'builtin::order_total',
					'builtin::order_product_total',
					'builtin::line_tax'
				],
				'total_fields' => [
					'builtin::order_total',
					'builtin::order_product_total',
					'builtin::line_tax'
				],
				'round_fields' => [
					'builtin::order_total',
					'builtin::order_product_total',
					'builtin::line_tax'
				],
				'orderby' => 'builtin::order_date',
				'orderdir' => 'asc',
				'icon' => 'icon_4'
			],
			'order_export_fulfillment_shipping' => [
				'preset_name' => __( 'Fulfillment & Shipping', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'One row per order with shipping contact, address, method and quantities for picking, packing and labels.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'format_amounts' => 1,
				'include_totals' => 1,
				'filename' => '[preset] - [created Y-m-d]',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'month',
				'one_line_per_order' => 1,
				'order_fields_once' => 1,
				'order_statuses' => [ 'wc-processing' ],
				'fields' => [
					'builtin::order_id',
					'builtin::order_date_only',
					'builtin::shipping_name',
					'builtin::shipping_phone',
					'builtin::shipping_email',
					'builtin::shipping_address',
					'builtin::shipping_state',
					'builtin::shipping_country',
					'builtin::order_shipping_methods',
					'builtin::order_total_qty',
					'builtin::order_item_name',
				],
				'total_fields' => [
					'builtin::order_total_qty',
				],
				'round_fields' => [
					'builtin::order_total_qty',
				],
				'orderby' => 'builtin::order_date',
				'orderdir' => 'asc',
				'icon' => 'icon_6'
			],
			'order_export_refunds_returns' => [
				'preset_name' => __( 'Refunds & Returns', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'One row per refunded order with status, and amounts.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'format_amounts' => 1,
				'include_totals' => 1,
				'filename' => '[preset] - [created Y-m-d]',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'lastmonth',
				'one_line_per_order' => 1,
				'order_fields_once' => 1,
				'order_total_once' => 1,
				'order_statuses' => [ 'wc-refunded' ],
				'fields' => [
					'builtin::order_id',
					'builtin::order_parent',
					'builtin::order_date_only',
					'builtin::order_status',
					'builtin::billing_name',
					'builtin::order_total',
					'builtin::order_product_total',
				],
				'total_fields' => [
					'builtin::order_total',
					'builtin::order_product_total',
				],
				'round_fields' => [
					'builtin::order_total',
					'builtin::order_product_total',
				],
				'orderby' => 'builtin::order_date',
				'orderdir' => 'desc',
				'icon' => 'icon_5'
			],
			'order_export_line_items' => [
				'preset_name' => __( 'Line Items (One Row Per Item)', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'One row per line item for supplier files, picking lists and SKU-level analysis.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'format_amounts' => 1,
				'include_totals' => 1,
				'filename' => '[preset] - [created Y-m-d]',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'month',
				'one_line_per_order' => 0,
				'order_fields_once' => 0,
				'order_statuses' => [ 'wc-processing', 'wc-on-hold', 'wc-completed' ],
				'fields' => [
					'builtin::order_id',
					'builtin::order_date_only',
					'builtin::item_sku',
					'builtin::order_item_name',
					'builtin::variation_attributes',
					'builtin::quantity',
					'builtin::line_subtotal',
					'builtin::line_tax',
					'builtin::line_total',
					'builtin::billing_email',
				],
				'total_fields' => [
					'builtin::quantity',
					'builtin::line_subtotal',
					'builtin::line_tax',
					'builtin::line_total',
				],
				'round_fields' => [
					'builtin::quantity',
					'builtin::line_subtotal',
					'builtin::line_tax',
					'builtin::line_total',
				],
				'orderby' => 'builtin::order_date',
				'orderdir' => 'asc',
				'icon' => 'icon_8'
			],
			'order_export_margin_summary' => [
				'preset_name' => __( 'Order Margin Summary', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'One row per completed order with revenue and tax amounts. COGS columns appear when cost tracking is enabled.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'format_amounts' => 1,
				'include_totals' => 1,
				'filename' => '[preset] - [created Y-m-d]',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'lastmonth',
				'use_wp_date' => 1,
				'alt_order_date_local' => 1,
				'one_line_per_order' => 1,
				'order_fields_once' => 1,
				'order_total_once' => 1,
				'order_statuses' => [ 'wc-completed' ],
				'fields' => array_merge(
					[
						'builtin::order_id',
						'builtin::order_date_only',
						'builtin::order_paid_date_only',
						'builtin::billing_name',
						'builtin::billing_country',
						'builtin::order_product_total',
						'builtin::line_tax',
						'builtin::order_total'
					],
					$this->supports( PlatformFeatures::COGS )
						? [
							'builtin::cogs',
							'builtin::profit',
							'builtin::margin',
						]
						: []
				),
				'total_fields' => array_merge(
					[
						'builtin::order_product_total',
						'builtin::line_tax',
						'builtin::order_total'
					],
					$this->supports( PlatformFeatures::COGS )
						? [
							'builtin::cogs',
							'builtin::profit',
						]
						: []
				),
				'round_fields' => array_merge(
					[
						'builtin::order_product_total',
						'builtin::line_tax',
						'builtin::order_total'
					],
					$this->supports( PlatformFeatures::COGS )
						? [
							'builtin::cogs',
							'builtin::profit',
							'builtin::margin',
						]
						: []
				),
				'field_names' => [
					'builtin::cogs' => __( 'Cost of Goods Sold', 'product-sales-report-for-woocommerce' ),
					'builtin::profit' => __( 'Profit', 'product-sales-report-for-woocommerce' ),
					'builtin::margin' => __( 'Gross Margin', 'product-sales-report-for-woocommerce' ),
				],
				'orderby' => 'builtin::order_date',
				'orderdir' => 'asc',
				'icon' => 'icon_4',
			],
			'all_sales' => [
				'preset_name' => __( 'All Sales', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Sales trend chart with quantity sold and revenue after discounts, shown per product.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'line_totals',
				'fields' => [ 'builtin::product_image', 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_after_discount' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'icon' => 'icon_4'
			],
			'top_selling' => [
				'preset_name' => __( 'Top Selling Products', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Identify top selling products with quantities and revenue in a single view.', 'product-sales-report-for-woocommerce' ),
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_image', 'builtin::product_id', 'builtin::product_name', 'builtin::product_sku', 'builtin::quantity_sold', 'builtin::gross_after_discount' ],
				'chart_fields' => [ 'builtin::gross_after_discount' ],
				'orderby' => 'builtin::gross_after_discount',
				'orderdir' => 'desc',
				'variations' => 0,
				'limit_on' => 1,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_3',
			],
			'refund_analytics_products' => [
				'preset_name' => __( 'Refunds By Product', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Top products by refunded amount in the period. Compare with sales columns; add a calculated field for refund rate if you need it.', 'product-sales-report-for-woocommerce' ),
				'icon' => 'icon_5',
				'pro' => true
			],
			'top_rated' => [
				'preset_name' => __( 'Top Rated Products', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'See products with the highest customer ratings to highlight quality performers.', 'product-sales-report-for-woocommerce' ),
				'icon' => 'icon_5',
				'pro' => true
			],
			'stock' => [
				'preset_name' => __( 'Stock Report', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Monitor stock levels and surface products that need replenishment.', 'product-sales-report-for-woocommerce' ),
				'order_statuses' => function_exists( 'wc_get_order_statuses' ) ? array_keys( wc_get_order_statuses() ) : [ 'wc-processing', 'wc-on-hold', 'wc-completed' ],
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_image', 'builtin::product_id', 'builtin::product_name', 'builtin::product_sku', 'builtin::product_stock' ],
				'chart_fields' => [ 'builtin::product_stock' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'variations' => 0,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_6'
			],
			'stock_watchlist' => [
				'preset_name' => __( 'Low & Out Of Stock', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Lowest on-hand quantities first so shortages stand out; sold units follow the report dates and statuses.', 'product-sales-report-for-woocommerce' ),
				'exclude_unmanaged_stock' => 1,
				'order_statuses' => function_exists( 'wc_get_order_statuses' ) ? array_keys( wc_get_order_statuses() ) : [ 'wc-processing', 'wc-on-hold', 'wc-completed' ],
				'chart_type' => 'bar',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'lastyear',
				'fields' => [
					'builtin::product_image',
					'builtin::product_id',
					'builtin::product_name',
					'builtin::product_sku',
					'builtin::product_stock',
					'builtin::quantity_sold',
				],
				'chart_fields' => [ 'builtin::product_stock' ],
				'orderby' => 'builtin::product_stock',
				'orderdir' => 'asc',
				'variations' => 0,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_6',
			],
			'state_sales' => [
				'preset_name' => __( 'Sales By US State', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Break down sales totals by US state for regional insights.', 'product-sales-report-for-woocommerce' ),
				'icon' => 'icon_7',
				'pro' => true
			],
			'product_sales' => [
				'preset_name' => __( 'Sales By Product', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Review sales totals grouped by product to spot trends quickly.', 'product-sales-report-for-woocommerce' ),
				'chart_type' => 'pie',
				'fields' => [ 'builtin::product_image', 'builtin::product_id', 'builtin::product_name', 'builtin::product_sku', 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'variations' => 0,
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_8'
			],
			'payment_method_sales' => [
				'preset_name' => __( 'Sales By Payment Method', 'product-sales-report-for-woocommerce' ),
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
			'sales_trend_monthly' => [
				'preset_name' => __( 'Sales Trend (Monthly)', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Revenue and units sold summed by calendar month across the dates you choose.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'line_totals',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'last1y',
				'disable_product_grouping' => 1,
				'groupby' => 'o_builtin::order_month',
				'fields' => [
					'builtin::groupby_field',
					'builtin::gross_after_discount',
					'builtin::quantity_sold',
				],
				'field_names' => [
					'builtin::groupby_field' => __( 'Month', 'product-sales-report-for-woocommerce' ),
				],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_4',
			],
			'sales_trend_weekly' => [
				'preset_name' => __( 'Sales Trend (Weekly)', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Revenue and units sold summed by ISO week across the dates you choose.', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'line_totals',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'last1y',
				'disable_product_grouping' => 1,
				'groupby' => 'o_builtin::order_week',
				'fields' => [
					'builtin::groupby_field',
					'builtin::gross_after_discount',
					'builtin::quantity_sold',
				],
				'field_names' => [
					'builtin::groupby_field' => __( 'Week', 'product-sales-report-for-woocommerce' ),
				],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_3',
			],
			'refund_trend_monthly' => [
				'preset_name' => __( 'Refund Trend (Monthly)', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Refunded amounts and units by calendar month across the dates you choose.', 'product-sales-report-for-woocommerce' ),
				'icon' => 'icon_5',
				'pro' => true
			],
			'currency_sales' => [
				'preset_name' => __( 'Sales By Currency', 'product-sales-report-for-woocommerce' ),
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
				'preset_name' => __( 'Sales By Country', 'product-sales-report-for-woocommerce' ),
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
			],
			'ltv_by_billing_email' => [
				'preset_name' => __( 'Top Revenue By Billing Email', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Ranking of billing emails by revenue in your date range, with units, orders and average order total (top 100 rows).', 'product-sales-report-for-woocommerce' ),
				'display_mode' => 'chart',
				'chart_type' => 'bar',
				'disable_product_grouping' => 1,
				'groupby' => 'o__billing_email',
				'fields' => [
					'builtin::groupby_field',
					'builtin::gross_after_discount',
					'builtin::quantity_sold',
					'builtin::order_count',
					'builtin::avg_order_total',
				],
				'field_names' => [
					'builtin::groupby_field' => __( 'Billing email', 'product-sales-report-for-woocommerce' ),
				],
				'chart_fields' => [ 'builtin::gross_after_discount' ],
				'orderby' => 'builtin::gross_after_discount',
				'orderdir' => 'desc',
				'limit_on' => 1,
				'limit' => 100,
				'chart_series_name' => 'builtin::groupby_field',
				'report_time_mode' => 'preset',
				'report_time_preset' => 'last1y',
				'icon' => 'icon_4',
			],
		];

		return apply_filters( 'ninjalytics_report_templates', $templates );
	}
	
	public function getDataParams($baseFields) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
		
		$intermediateRounding = !empty( $_POST['intermediate_rounding'] );
		$exportOrders = !empty($_POST['export_orders']);
		
		$standardFields = $this->getStandardFields();
		
		if (empty($_POST['export_orders'])) {
			$groupByProducts = (int) ($_POST['disable_product_grouping'] ?? 0) <= 0;
			$reportVariations = $this->supports(PlatformFeatures::VARIATIONS) && !empty($_POST['variations']);
		}
		
		
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
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => ($exportOrders ? '' : 'SUM'),
			'join_type' => 'LEFT',
			'name' => 'quantity'
		),
		$standardFields['line_subtotal'][1] => array(
			'type' => $standardFields['line_subtotal'][0],
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'gross'
		),
		$standardFields['line_total'][1] => array(
			'type' => $standardFields['line_total'][0],
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'gross_after_discount'
		),
		$standardFields['line_tax'][1] => array(
			'type' => $standardFields['line_tax'][0],
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'taxes'
		)
	);

	// Add coupons virtual meta field when needed
	if (in_array('builtin::order_coupons', $baseFields) && $this->supports(PlatformFeatures::COUPONS)) {
		$dataParams['_order_coupons'] = [
			'type' => 'meta',
			'function' => 'GROUP_CONCAT',
			'join_type' => 'LEFT',
			'name' => 'order_coupons'
		];
	}
	
	
	if ( !$exportOrders ) {
		// Add shipping methods virtual meta field when needed
		if (in_array('builtin::order_shipping_methods', $baseFields)) {
			$dataParams['_order_shipping_method'] = [
				'type' => 'meta',
				'function' => 'GROUP_CONCAT',
				'join_type' => 'LEFT',
				'name' => 'order_shipping_methods'
			];
		}
		if (in_array('builtin::order_count', $baseFields)) {
		   $dataParams[ $standardFields['order_id'][1] ] = array(
				'type' => $standardFields['order_id'][0],
				'name' => 'order_id',
				'function' => 'GROUP_CONCAT'
			);
		}
	} else {
       $dataParams[ $standardFields['order_id'][1] ] = array(
            'type' => $standardFields['order_id'][0],
            'function' => '',
            'name' => 'order_id',
            //'join_type' => 'LEFT'
        );
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
		if (in_array('builtin::order_date', $_POST['fields'] ?? []) || in_array('builtin::order_date_only', $_POST['fields'] ?? [])) {
			$dataParams[ $standardFields['order_date'][1] ] = array(
				'type' => $standardFields['order_date'][0],
				'function' => '',
				'name' => 'order_date',
				'join_type' => 'LEFT'
			);
		}
		
		if ( in_array( 'builtin::order_parent', $_POST['fields'], true ) ) {
			$dataParams[ $standardFields['order_parent'][1] ] = array(
				'type' => $standardFields['order_parent'][0],
				'function' => '',
				'name' => 'parent_order_id',
				'join_type' => 'LEFT',
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
		}
		if (!empty($hasBillingAddressField) || in_array('builtin::billing_country', $_POST['fields'])) {
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
		}
		if (!empty($hasShippingAddressField) || in_array('builtin::shipping_country', $_POST['fields'])) {
			$dataParams[ $standardFields['shipping_country'][1] ] = array(
				'type' => $standardFields['shipping_country'][0],
				'name' => 'shipping_country',
				'function' => '',
				'join_type' => 'LEFT'
			);
		}
		
		// Shipping line item fields
		if (!empty($_POST['include_shipping'])) {
			if (in_array('builtin::product_id', $_POST['fields']) || strpos($_POST['shipping_product_name'] ?? '', '[method_name]') !== false) {
				$dataParams['method_id'] = array(
					'type' => 'order_item_meta',
					//'order_item_type' => 'shipping',
					'function' => '',
					'name' => 'shipping_method_id',
					'join_type' => 'LEFT'
				);
			}
			if (in_array('builtin::line_subtotal', $_POST['fields']) || in_array('builtin::line_total', $_POST['fields']) || in_array('builtin::line_total_with_tax', $_POST['fields'])) {
				$dataParams['cost'] = array(
					'type' => 'order_item_meta',
					//'order_item_type' => 'shipping',
					'function' => '',
					'name' => 'shipping_cost',
					'join_type' => 'LEFT'
				);
			}
			if (in_array('builtin::line_total_with_tax', $_POST['fields']) || in_array('builtin::line_tax', $_POST['fields'])) {
				$dataParams['taxes'] = array(
					'type' => 'order_item_meta',
					//'order_item_type' => 'shipping',
					'function' => '',
					'name' => 'shipping_taxes',
					'join_type' => 'LEFT'
				);
			}
		}
	}
	
	if ($this->supports(PlatformFeatures::LINE_ITEM_ADJUSTMENTS) && !empty($_POST['adjustments'])) {
		$dataParams['order_item_adjustment.subtotal'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'adjustment_subtotal'
		);
		$dataParams['order_item_adjustment.total'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'adjustment_total'
		);
		$dataParams['order_item_adjustment.tax'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => ($exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM')),
			'join_type' => 'LEFT',
			'name' => 'adjustment_tax'
		);
		
	}
	
	if ( !empty($exportOrders) || $groupByProducts || $_POST['disable_product_grouping'] == 2 ) {
		$dataParams[$standardFields['product_id'][1]] = array(
			'type' => $standardFields['product_id'][0],
			'order_item_type' => $exportOrders ? null :  'line_item',
			'function' => !$exportOrders && $_POST['disable_product_grouping'] == -1 ? 'GROUP_CONCAT' : '',
			'join_type' => 'LEFT',
			'name' => 'product_id'
		);
	}
	
	if (!empty($_POST['export_orders']) || ($reportVariations && $groupByProducts)) {
		$dataParams[$standardFields['variation_id'][1]] = array(
			'type' => $standardFields['variation_id'][0],
			'order_item_type' => $exportOrders ? null : 'line_item',
			'function' => !$exportOrders && $_POST['disable_product_grouping'] == -1 ? 'GROUP_CONCAT' : '',
			'join_type' => 'LEFT',
			'name' => 'variation_id'
		);
	}


	if (!$exportOrders && (in_array('builtin::line_item_count', $baseFields) || \NinjalyticsFree\ninjalytics_hasTaxBreakoutField($baseFields))) {
		$dataParams[$this->orderItemsIdColumn] = array(
			'type' => 'order_item',
			'order_item_type' =>  'line_item',
				'function' => 'GROUP_CONCAT',
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
		if (!empty($_POST['enable_custom_segments']) && $field == 'builtin::groupby_field' ) {
			
			$groupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
			if ( !empty($groupByField) && $groupByField != 'i_builtin::item_price' ) {
				if (in_array($groupByField, array('o_builtin::order_month', 'o_builtin::order_quarter', 'o_builtin::order_year', 'o_builtin::order_date', 'o_builtin::order_day', 'o_builtin::order_week'), true)) {
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
						case 'o_builtin::order_week':
							$sqlFunction = 'YEARWEEK';
							break;
						default:
							$sqlFunction = 'DATE';
					}
					$period_field_spec = [
						'type' => $standardFields['order_date'][0],
						'order_item_type' => $exportOrders ? null :  'line_item',
						'function' => $sqlFunction,
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
					];
					if ('YEARWEEK' === $sqlFunction ) {
						/**
						 * MySQL YEARWEEK( date, mode ) mode 3: ISO-8601 week (Monday … Sunday), year/week consistent with WEEK(..., 3).
						 */
						$period_field_spec['function_args_after'] = [3 => '%d'];
					}
					$dataParams[$sqlFunction.'.'.$standardFields['order_date'][1]] = $period_field_spec;
				} else if ($this->supports(PlatformFeatures::ORDER_SOURCE) && $groupByField == 'o_builtin::order_source') {
					// Replicated in shipping data function below
					$dataParams['_wc_order_attribution_source_type'] = [
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_field'
					];
					$dataParams['_wc_order_attribution_utm_source'] = [
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_fieldb'
					];
				} else if ($groupByField[0] != 'p') {
					$fieldName = esc_sql(substr($groupByField, 2));
					
					$dataParams[$fieldName] = array(
						'type' => ($groupByField[0] == 'i' ? 'order_item_meta' : 'meta'),
						'order_item_type' => $exportOrders ? null :  'line_item',
						'function' => '',
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
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
