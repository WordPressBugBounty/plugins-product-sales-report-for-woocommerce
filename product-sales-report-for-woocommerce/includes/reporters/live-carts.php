<?php
namespace NinjalyticsFree\Reporters;

use NinjalyticsFree\Reporters\PlatformFeatures;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/base.php');

class LiveCarts extends \NinjalyticsFree\Reporters\Base {
	
	const ID = 'livecarts';
	
	public $ordersStatusColumn, $defaultOrderStatuses, $orderItemsTable, $orderItemsOrderIdColumn, $orderItemsIdColumn, $productPostType, $productCategoryTaxonomy, $productTagTaxonomy;
	private static $cartStatuses, $tsFormat;
	
	public function __construct() {
		global $wpdb;
		$this->ordersTable = $wpdb->prefix.'phplugins_carts';
		$this->ordersIdColumn = 'cart_id';
		$this->ordersStatusColumn = 'status';
		$this->ordersDateColumn = 'created';
		$this->defaultOrderStatuses = array_keys($this->getOrderStatuses());
		$this->orderItemsTable = $wpdb->prefix.'phplugins_cart_contents_items';
		$this->orderItemsOrderIdColumn = 'contents_id';
		$this->orderItemsIdColumn = 'item_id';
		$this->productPostType = 'product';
		$this->productCategoryTaxonomy = 'product_cat';
		$this->productTagTaxonomy = 'product_tag';
	}
	
	public function getReportTemplates() {
		return [
			'live_carts_report' => [
				'preset_name' => __( 'Live Carts Report', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Default aggregate statistics report for carts captured by the Live Carts plugin.', 'product-sales-report-for-woocommerce' ),
				'icon'        => 'icon_3',
				'fields' => ['builtin::cart_value', 'builtin::cart_count'],
				'chart_series_name' => 'builtin::cart_count',
			],
			'live_carts_export' => [
				'preset_name' => __( 'Live Carts Export', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Default individual cart data export for carts captured by the Live Carts plugin.', 'product-sales-report-for-woocommerce' ),
				'export_orders' => 1,
				'icon'        => 'icon_3',
				'fields' => ['builtin::cart_id', 'builtin::user_email', 'builtin::status', 'builtin::last_seen', 'builtin::cart_value'],
				'chart_series_name' => 'builtin::cart_id',
				'display_mode' => 'table',
			],
			'live_carts_status' => [
				'preset_name' => __( 'Carts by Status', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'See total cart value segmented by status.', 'product-sales-report-for-woocommerce' ),
				'icon'        => 'icon_3',
				'fields' => ['builtin::groupby_field', 'builtin::cart_value'],
				'field_names' => ['builtin::groupby_field' => 'Status'],
				'chart_type' => 'line_series',
				'chart_series_name' => 'builtin::groupby_field',
				'enable_custom_segments' => 1,
				'groupby' => 'c_builtin::status',
			],
			'live_carts_avg_value' => [
				'preset_name' => __( 'Average Cart Value', 'product-sales-report-for-woocommerce' ),
				'_description' => __( 'Monitor changes in average total value per cart.', 'product-sales-report-for-woocommerce' ),
				'icon'        => 'icon_3',
				'fields' => ['builtin::avg_cart_value', 'builtin::cart_count'],
				'chart_fields' => ['builtin::avg_cart_value'],
				'chart_series_name' => 'builtin::cart_count',
			],
		];
	}
	
	public function getPrimaryItemsName() {
		return 'Carts';
	}
	
	public function getStandardFields() {
		// These must be SQL safe!
		
		return [
			'quantity' => ['order_item', 'quantity'],
			'line_subtotal' => ['order_item', 'line_subtotal'],
			'line_total' => ['order_item', 'line_total'],
			'line_tax' => ['order_item', 'line_tax'],
			'product_id' => ['order_item', 'product_id'],
			'variation_id' => ['order_item', 'variation_id'],
			'order_total' => ['post_data', 'value'],
			'order_date' => ['post_data', 'created'],
			'order_id' => ['post_data', 'cart_id'],
			'order_item_id' => ['order_item', 'item_id'],
			'status' => ['post_data', 'status'],
			'customer_id' => ['post_data', 'user_id']
		];
	}
	
	public function getDefaultFields($exportOrders) {
		return array('builtin::cart_value');
	}
	
	public function getDefaultSettings($exportOrders) {
		return array_merge(
			parent::getDefaultSettings($exportOrders),
			[
				'display_mode' => 'chart',
				'chart_fields' => ['builtin::cart_value'],
				'chart_type' => 'line_totals',
				'refunds' => 0,
				'adjustments' => 0,
				'total_fields' => ['builtin::cart_value'],
				'round_fields' => $exportOrders ? ['builtin::cart_value'] : ['builtin::cart_value', 'builtin::avg_cart_value'],
			]
		);
	}
	
	
	function addJoinForField($raw_key, $key, $value, &$joins, &$joinParams) {
		global $wpdb;
		$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
		$type      = isset( $value['type'] ) ? $value['type'] : false;
		switch ( $type ) {
			case 'order_item':
				if (!isset($joins['order_items'])) {
					$joins['order_items'] = "{$join_type} JOIN {$this->orderItemsTable} AS order_items ON (order_items.{$this->orderItemsOrderIdColumn}=(SELECT MAX({$wpdb->prefix}phplugins_cart_contents.contents_id) FROM {$wpdb->prefix}phplugins_cart_contents WHERE {$wpdb->prefix}phplugins_cart_contents.cart_id=posts.{$this->ordersIdColumn}))";
				}
				return;
		}
		
		parent::addJoinForField($raw_key, $key, $value, $joins, $joinParams);
	}
	
	function getGroupByFields() {
		return [
			'c_builtin::user_id' => 'User ID',
			'c_builtin::creation_date' => 'Creation Date',
			'c_builtin::creation_day' => 'Creation Day',
			'c_builtin::creation_month' => 'Creation Month',
			'c_builtin::creation_quarter' => 'Creation Quarter',
			'c_builtin::creation_year' => 'Creation Year',
			'c_builtin::status' => 'Status',
			'c_builtin::ip_address' => 'IP Address',
			'c_builtin::archived' => 'Archived'
		];
	}
	
	public function getGroupByFieldTypes() {
		return ['c' => 'Cart'];
	}
	
	public function getDataParams($baseFields) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
		$intermediateRounding = !empty( $_POST['intermediate_rounding'] );
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
		$exportOrders = !empty($_POST['export_orders']);
		$standardFields = $this->getStandardFields();
		
		$dataParams = [];
			
		if (in_array('builtin::cart_value', $baseFields) || in_array('builtin::avg_cart_value', $baseFields)) {
			$dataParams[ $standardFields['order_total'][1] ] = array(
				'type' => $standardFields['order_total'][0],
				'function' => $exportOrders ? '' : ($intermediateRounding ? 'PSRSUM' : 'SUM'),
				'join_type' => 'LEFT',
				'name' => 'cart_value'
			);
		}
		
		if ($exportOrders) {
			if (array('builtin::cart_id', $baseFields) || in_array('builtin::contents', $baseFields)) {
				$dataParams[ $standardFields['order_id'][1] ] = array(
					'type' => $standardFields['order_id'][0],
					'join_type' => 'LEFT',
					'name' => 'cart_id'
				);
			}
			if (in_array('builtin::user_id', $baseFields) || in_array('builtin::user_email', $baseFields)) {
				$dataParams[ $standardFields['customer_id'][1] ] = array(
					'type' => $standardFields['customer_id'][0],
					'join_type' => 'LEFT',
					'name' => 'user_id'
				);
			}
			if (in_array('builtin::ip_address', $baseFields)) {
				$dataParams['ip_address'] = array(
					'type' => 'post_data',
					'name' => 'ip_address'
				);
			}
			if (in_array('builtin::status', $baseFields)) {
				$dataParams[ $standardFields['status'][1] ] = array(
					'type' => $standardFields['status'][0],
					'join_type' => 'LEFT',
					'name' => 'status'
				);
			}
			if (in_array('builtin::created_at', $baseFields)) {
				$dataParams[ $standardFields['order_date'][1] ] = array(
					'type' => $standardFields['order_date'][0],
					'name' => 'created_at'
				);
			}
			if (in_array('builtin::last_seen', $baseFields)) {
				$dataParams[ 'last_seen' ] = array(
					'type' => 'post_data',
					'name' => 'last_seen'
				);
			}
			if (in_array('builtin::last_url', $baseFields)) {
				$dataParams[ 'last_url' ] = array(
					'type' => 'post_data',
					'name' => 'last_url'
				);
			}
			if (in_array('builtin::coupon', $baseFields)) {
				$dataParams[ 'coupon' ] = array(
					'type' => 'post_data',
					'name' => 'coupon'
				);
			}
			if (in_array('builtin::order_id', $baseFields)) {
				$dataParams[ 'order_id' ] = array(
					'type' => 'post_data',
					'name' => 'order_id'
				);
			}
			if (in_array('builtin::archived', $baseFields)) {
				$dataParams[ 'archived' ] = array(
					'type' => 'post_data',
					'name' => 'archived'
				);
			}
		} else {
			if (in_array('builtin::cart_count', $baseFields) || in_array('builtin::avg_cart_value', $baseFields)) {
				$dataParams[ $standardFields['order_id'][1] ] = array(
					'type' => $standardFields['order_id'][0],
					'function' => 'COUNT',
					'join_type' => 'LEFT',
					'name' => 'cart_count'
				);
			}
		}
		
		foreach ($baseFields as $field) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
			if (!empty($_POST['enable_custom_segments']) && $field == 'builtin::groupby_field' ) {
				
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
				$groupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
				if ( !empty($groupByField) ) {
					switch ($groupByField) {
						case 'c_builtin::creation_month':
						case 'c_builtin::creation_quarter':
						case 'c_builtin::creation_year':
						case 'c_builtin::creation_date':
						case 'c_builtin::creation_day':
							switch ($groupByField) {
								case 'c_builtin::creation_month':
									$sqlFunction = 'MONTH';
									break;
								case 'c_builtin::creation_quarter':
									$sqlFunction = 'QUARTER';
									break;
								case 'c_builtin::creation_year':
									$sqlFunction = 'YEAR';
									break;
								case 'c_builtin::creation_day':
									$sqlFunction = 'DAY';
									break;
								default:
									$sqlFunction = 'DATE';
							}
							$dataParams['created'] = array(
								'type' => 'post_data',
								'function' => $sqlFunction,
								'join_type' => 'LEFT',
								'name' => 'groupby_field'
							);
							break;
						case 'c_builtin::user':
						case 'c_builtin::user_id':
							$dataParams['user_id'] = array(
								'type' => 'post_data',
								'function' => '',
								'join_type' => 'LEFT',
								'name' => 'groupby_field'
							);
							break;
						case 'c_builtin::status':
						case 'c_builtin::ip_address':
						case 'c_builtin::archived':
							$fieldName = substr($groupByField, 11);
							
							$dataParams[$fieldName] = array(
								'type' => 'post_data',
								'function' => '',
								'join_type' => 'LEFT',
								'name' => 'groupby_field'
							);
							break;
						
					}
					
				}
			}
		}
		
		return $dataParams;
	}
	
	static function getCartStatusName($status) {
		if (!isset(self::$cartStatuses)) {
			self::$cartStatuses = \BerryPress\LiveCarts\LiveCarts::instance()->getCartStatuses();
		}
		return isset( self::$cartStatuses[ $status ] ) ? self::$cartStatuses[ $status ] : $status;
	}
	
	static function formatTimestamp($ts) {
		if (!isset(self::$tsFormat)) {
			self::$tsFormat = \BerryPress\LiveCarts\LiveCarts::instance()->getTimestampFormat();
		}
		return get_date_from_gmt($ts, self::$tsFormat);
	}
	
	function getCustomFields($exportOrders, $includeDisplay = false, $productFieldsOnly = false) {
		return [];
	}
	
	function getBuiltInFields($exportOrders) {
		$fields = $exportOrders
					? [
						'builtin::cart_id' => 'Cart ID',
						'builtin::user_id' => 'User ID',
						'builtin::user_email' => 'User Email',
						'builtin::ip_address' => 'IP Address',
						'builtin::status' => 'Status',
						'builtin::created_at' => 'Created At',
						'builtin::last_seen' => 'Last Seen',
						'builtin::last_url' => 'Last URL',
						'builtin::contents' => 'Contents',
						'builtin::coupon' => 'Coupon',
						'builtin::order_id' => 'Converted Order ID',
						'builtin::archived' => 'Is Archived',
					]
					: [
						'builtin::cart_count' => 'Cart Count',
						'builtin::avg_cart_value' => 'Average Cart Value'
					];
		
		$fields['builtin::cart_value'] = 'Cart Value';
		return $fields;
	}
	
	function getRow($product, $fields, &$totals, $fieldbuilderFields, $fieldbuilderDependencies) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
		global $wpdb;
		$row = array();

		foreach ($fields as $fieldIndex => $field) {
					switch ($field) {
						case 'builtin::cart_value':
							$rowValue = $product->cart_value;
							break;
						case 'builtin::avg_cart_value':
							$rowValue = $product->cart_count ? $product->cart_value / $product->cart_count : 0;
							break;
						case 'builtin::cart_count':
							$rowValue = $product->cart_count;
							break;
						case 'builtin::cart_id':
							$rowValue = \BerryPress\LiveCarts\LiveCarts::formatCartId($product->cart_id);
							break;
						case 'builtin::user_id':
							$rowValue = $product->user_id;
							break;
						case 'builtin::user_email':
							$cartUser = get_userdata($product->user_id);
							$rowValue = $cartUser ? $cartUser->user_email : '';
							break;
						case 'builtin::status':
							$rowValue = self::getCartStatusName($product->status);
							break;
						case 'builtin::created_at':
							$rowValue = self::formatTimestamp($product->created_at);
							break;
						case 'builtin::last_seen':
							$rowValue = self::formatTimestamp($product->last_seen);
							break;
						case 'builtin::last_url':
							$rowValue = $product->last_url;
							break;
						case 'builtin::coupon':
							$rowValue = $product->coupon;
							break;
						case 'builtin::order_id':
							$rowValue = $product->order_id;
							break;
						case 'builtin::ip_address':
							$rowValue = $product->ip_address;
							break;
						case 'builtin::archived':
							$rowValue = $product->archived ? 'Yes' : 'No';
							break;
						case 'builtin::contents':
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$contents = $wpdb->get_results(
								$wpdb->prepare(
									'SELECT product_id, variation_id, quantity
									FROM '.$wpdb->prefix.'phplugins_cart_contents_items 
									WHERE contents_id=(SELECT MAX(contents_id) FROM '.$wpdb->prefix.'phplugins_cart_contents WHERE cart_id=%d)
									ORDER BY item_id ASC',
									$product->cart_id
								),
								ARRAY_N
							);
							$rowValue = implode('; ', array_map(function($item) {
								$product = wc_get_product( empty( $item[1] ) ? $item[0] : $item[1] );
								return ($product ? $product->get_name() : (empty( $item[1] ) ? sprintf('Product #%d', $item[0]) : sprintf('Variation #%d', $item[1]))).', '.$item[2];
							}, $contents ));
							break;
						case 'builtin::groupby_field':
							if (!empty($_POST['enable_custom_segments'])) {
								$selectedGroupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
								
								switch ($selectedGroupByField) {
									
									case 'c_builtin::status':
										$rowValue = self::getCartStatusName($product->groupby_field);
										break;
									case 'c_builtin::user':
										$user = get_userdata($product->groupby_field);
										$rowValue = $user ? $user->display_name : '';
										break;
									default:
										$rowValue = $product->groupby_field;
								}
							} else {
								$rowValue = '';
							}
							break;
						default:
							$rowValue = '';
					}
				
				$formatAmount = !empty($_POST['format_amounts']) && isset($_POST['round_fields']) && in_array($field, $_POST['round_fields']);
				
				if (is_array($rowValue)) {
					$rowValue = implode(
						empty($rowValueDelimiter) ? ', ' : $rowValueDelimiter,
						$formatAmount
							? array_map(function($val) {
								return is_numeric($val) ? number_format($val, 2, '.', '') : $val;
							}, $rowValue)
							: $rowValue
					);
				} else if ($formatAmount && is_numeric($rowValue)) {
					$rowValue = number_format($rowValue, 2, '.', '');
				}
				
				$row[] = apply_filters('ninjalytics_row_value', $rowValue, $field);
				
				
			}
			
			if (isset($totals[$field])) {
				$newValue = end($row);
				if (empty($newValue)) {
					
				} else if (is_numeric($newValue)) {
					$totals[$field] += (float) $newValue;
				} else {
					unset($totals[$field]);
				}
			}
		
		return $row;
		
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
	
	public function getPlatformFeatures() {
		return [PlatformFeatures::CHILD_ITEMS_FILTER];
	}
	
	public function getOrderStatuses() {
		return class_exists('\BerryPress\LiveCarts\LiveCarts') ? \BerryPress\LiveCarts\LiveCarts::instance()->getCartStatuses() : [];
	}
	
	public function getOldestOrderYear() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$maxOrder = $wpdb->get_var('SELECT MIN(created) FROM '.$wpdb->prefix.'phplugins_carts');
		return $maxOrder ? get_date_from_gmt($maxOrder, 'Y') : null;
	}
	
	public function getNewestOrderYear() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$maxOrder = $wpdb->get_var('SELECT MAX(created) FROM '.$wpdb->prefix.'phplugins_carts');
		return $maxOrder ? get_date_from_gmt($maxOrder, 'Y') : null;
	}
	
}
	