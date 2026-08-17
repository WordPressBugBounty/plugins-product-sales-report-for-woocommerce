<?php
namespace NinjalyticsFree\Reporters\WooCommerce;

use NinjalyticsFree\Reporters\PlatformFeatures;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/orders-base.php');

abstract class Base extends \NinjalyticsFree\Reporters\OrdersBase {
	
	public $hiddenOrderItemFields = ['_product_id', '_variation_id'], $orderItemTypes;
	
	public function __construct() {
		global $wpdb;
		$this->orderItemsTable = $wpdb->prefix.'woocommerce_order_items';
		$this->orderItemsOrderIdColumn = 'order_id';
		$this->orderItemsTypeColumn = 'order_item_type';
		$this->orderItemsMetaTable = $wpdb->prefix.'woocommerce_order_itemmeta';
		$this->orderItemsIdColumn = 'order_item_id';
		$this->orderItemsMetaItemIdColumn = 'order_item_id';
		$this->orderItemsNameColumn = 'order_item_name';
		$this->productPostType = 'product';
		$this->productCategoryTaxonomy = 'product_cat';
		$this->productTagTaxonomy = 'product_tag';
		$this->orderType = 'shop_order';
		$this->refundOrderType = 'shop_order_refund';
		$this->productOrderItemsType = 'line_item';
		$this->completedOrderStatus = 'wc-completed';
		$this->defaultOrderStatuses = array('wc-processing', 'wc-on-hold', 'wc-completed');
		$this->billingStateMetaKey = '_billing_state';
	}
	public function getDefaultFields($exportOrders) {
		return $exportOrders ? ['builtin::product_id', 'builtin::product_name', 'builtin::quantity', 'builtin::line_total', 'builtin::order_date', 'builtin::billing_name', 'builtin::billing_email'] : array('builtin::product_id', 'builtin::product_sku', 'builtin::variation_sku', 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_sales');
	}
	
	function getOrderItemTypes() {
		if (!isset($this->orderItemTypes)) {
			global $wpdb;
			$this->orderItemTypes = $wpdb->get_col('SELECT DISTINCT order_item_type FROM ' . $wpdb->prefix . 'woocommerce_order_items ORDER BY order_item_type ASC');
			if (empty($this->orderItemTypes))
				$this->orderItemTypes = array();
		}
		return $this->orderItemTypes;
	}
	
	public function getStandardFields() {
		// These must be SQL safe!
		
		return [
			'order_parent' => ['post_data', 'parent_order_id'],
			'order_item_name' => ['order_item', 'order_item_name'],
			'quantity' => ['order_item_meta', '_qty'],
			'line_subtotal' => ['order_item_meta', '_line_subtotal'],
			'line_total' => ['order_item_meta', '_line_total'],
			'line_tax' => ['order_item_meta', '_line_tax'],
			'product_id' => ['order_item_meta', '_product_id'],
			'variation_id' => ['order_item_meta', '_variation_id'],
			'order_total' => ['meta', '_order_total'],
			'order_date' => ['post_data', 'date_created_gmt_wpz'],
			'order_id' => ['order_item', 'order_id'],
			'order_item_id' => ['order_item', 'order_item_id'],
			'order_item_type' => ['order_item', 'order_item_type'],
			'billing_first_name' => ['meta', '_billing_first_name'],
			'billing_last_name' => ['meta', '_billing_last_name'],
			'billing_phone' => ['meta', '_billing_phone'],
			'billing_email' => ['meta', '_billing_email'],
			'billing_address_1' => ['meta', '_billing_address_1'],
			'billing_address_2' => ['meta', '_billing_address_2'],
			'billing_city' => ['meta', '_billing_city'],
			'billing_postcode' => ['meta', '_billing_postcode'],
			'billing_state' => ['meta', '_billing_state'],
			'billing_country' => ['meta', '_billing_country'],
			'shipping_first_name' => ['meta', '_shipping_first_name'],
			'shipping_last_name' => ['meta', '_shipping_last_name'],
			'shipping_phone' => ['meta', '_shipping_phone'],
			'shipping_email' => ['meta', '_shipping_email'],
			'shipping_address_1' => ['meta', '_shipping_address_1'],
			'shipping_address_2' => ['meta', '_shipping_address_2'],
			'shipping_city' => ['meta', '_shipping_city'],
			'shipping_postcode' => ['meta', '_shipping_postcode'],
			'shipping_state' => ['meta', '_shipping_state'],
			'shipping_country' => ['meta', '_shipping_country'],
			'status' => ['post_data', 'status'],
			'customer_id' => ['post_data', 'customer_id'],
			'customer_note' => ['post_data', 'customer_note']

		];
	}
	
	public function getPlatformFeatures() {
		return [PlatformFeatures::CHILD_ITEMS, PlatformFeatures::CHILD_ITEMS_META, PlatformFeatures::META, PlatformFeatures::VARIATIONS, PlatformFeatures::SHIPPING, PlatformFeatures::CUSTOMER_USERS, PlatformFeatures::COGS, PlatformFeatures::ORDER_SOURCE, PlatformFeatures::ALT_DATES, PlatformFeatures::COUPONS, PlatformFeatures::ITEM_STOCK, PlatformFeatures::ORDER_PARENT];
	}
	
	public function getDefaults() {
		$defaults = parent::getDefaults();
		$defaults['order_types'] = wc_get_order_types( 'reports' );
		return $defaults;
	}
	
	public function get_order_report_data( $args = array() ) {
		$args = apply_filters('woocommerce_reports_get_order_report_data_args', $args);
		$args['order_status'] = array_map(function($status) {
			return 'wc-'.$status;
		}, apply_filters('woocommerce_reports_order_statuses', $args['order_status'] ?? []));
		
		return apply_filters( 'woocommerce_reports_get_order_report_data', parent::get_order_report_data($args), $args['data']);
	}
	
	public function getOrderStatuses() {
		return wc_get_order_statuses();
	}
	
	public function getOldestOrderYear() {
		$maxOrder = wc_get_orders([
			'limit' => 1,
			'order' => 'ASC',
			'type' => [$this->orderType, $this->refundOrderType]
		]);
		return $maxOrder ? (int) current($maxOrder)->get_date_created()->date('Y') : null;
	}
	
	public function getNewestOrderYear() {
		$maxOrder = wc_get_orders([
			'limit' => 1,
			'type' => [$this->orderType, $this->refundOrderType]
		]);
		return $maxOrder ? (int) current($maxOrder)->get_date_created()->date('Y') : null;
	}
	
	public function runQuery($query, $queryParams, $fieldsMap, $debug) {
		return parent::runQuery( apply_filters( 'woocommerce_reports_get_order_report_query', $query ), $queryParams, $fieldsMap, $debug );
	}
	
	public function getDataParams($baseFields) {
		$dataParams = parent::getDataParams($baseFields);
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
		$intermediateRounding = !empty( $_POST['intermediate_rounding'] );
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
		if (empty($_POST['export_orders']) && in_array('builtin::cogs', $_POST['fields'] ?? [])) {
			$dataParams[ '_cogs_value' ] = array(
				'type' => 'order_item_meta',
				'order_item_type' => 'line_item',
				'function' => ($intermediateRounding ? 'PSRSUM' : 'SUM'),
				'name' => 'cogs',
				'join_type' => 'LEFT'
			);
		}
		return $dataParams;
	}
	
	public function getVirtualOrderMeta() {
		global $wpdb;
		$virtualMeta = [
			// Add virtual meta for shipping methods
			'_order_shipping_method' => [
				'field' => 'ninjalytics_osmi.order_item_name',
				'joins' => [
					'ninjalytics_osmi' => $wpdb->prefix.'woocommerce_order_items AS ninjalytics_osmi ON ( ninjalytics_osmi.order_id=posts.'.$this->ordersIdColumn.' AND ninjalytics_osmi.order_item_type="shipping" )',
				],
				'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'woocommerce_order_items AS ninjalytics_osmi
										WHERE ninjalytics_osmi.order_id=%orderId% AND ninjalytics_osmi.order_item_type="shipping" AND ninjalytics_osmi.order_item_name%condition%'
			],
			// Add virtual meta for coupon codes used
			'_order_coupons' => [
				'field' => 'ninjalytics_oci.order_item_name',
				'joins' => [
					'ninjalytics_oci' => $wpdb->prefix.'woocommerce_order_items AS ninjalytics_oci ON ( ninjalytics_oci.order_id=posts.'.$this->ordersIdColumn.' AND ninjalytics_oci.order_item_type="coupon" )',
				],
				'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'woocommerce_order_items AS ninjalytics_oci
										WHERE ninjalytics_oci.order_id=%orderId% AND ninjalytics_oci.order_item_type="coupon" AND ninjalytics_oci.order_item_name%condition%'
			]
		];
		
		return $virtualMeta;
	}
	
	
	/**
	 * Whether WooCommerce inventory tracking is enabled for a product or variation post.
	 */
	protected function postTracksStock( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return false;
		}
		$pt = get_post_type( $post_id );
		if ( $pt === 'product_variation' || $pt === 'product' ) {
			return get_post_meta( $post_id, '_manage_stock', true ) === 'yes';
		}
		return false;
	}

	/**
	 * Whether aggregated report row refers to at least one catalog item with managed stock (matches WooCommerce rules).
	 */
	function reportRowTracksInventory( $product ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Called during report assembly after nonce validated upstream.
		$disable_product_grouping = empty( $_POST['export_orders'] ) ? (int) ( $_POST['disable_product_grouping'] ?? 0 ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$product_ids     = empty( $product->product_id ) ? [] : ( $disable_product_grouping === -1 ? array_unique( explode( ',', (string) $product->product_id ) ) : array( $product->product_id ) );
		$variation_ids   = empty( $product->variation_id ) ? [] : ( $disable_product_grouping === -1 ? array_unique( explode( ',', (string) $product->variation_id ) ) : array( $product->variation_id ) );

		foreach ( $variation_ids as $vid ) {
			$vid = (int) trim( (string) $vid );
			if ( $vid && $this->postTracksStock( $vid ) ) {
				return true;
			}
		}
		foreach ( $product_ids as $pid ) {
			$pid = (int) trim( (string) $pid );
			if ( $pid && $this->postTracksStock( $pid ) ) {
				return true;
			}
		}
		return false;
	}
	
}
	