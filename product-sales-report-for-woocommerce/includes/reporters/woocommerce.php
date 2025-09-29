<?php
namespace Ninjalytics\Reporters\WooCommerce;

use Ninjalytics\Reporters\PlatformFeatures;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/base.php');

abstract class Base extends \Ninjalytics\Reporters\Base {
	
	public $hiddenOrderItemFields = ['_product_id', '_variation_id'];
	
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
	
	public function getStandardFields() {
		// These must be SQL safe!
		
		return [
			'order_item_name' => ['order_item', 'order_item_name'],
			'quantity' => ['order_item_meta', '_qty'],
			'line_subtotal' => ['order_item_meta', '_line_subtotal'],
			'line_total' => ['order_item_meta', '_line_total'],
			'line_tax' => ['order_item_meta', '_line_tax'],
			'product_id' => ['order_item_meta', '_product_id'],
			'variation_id' => ['order_item_meta', '_variation_id'],
			'order_total' => ['meta', '_order_total'],
			'order_date' => ['post_data', 'date_created_gmt_wpz']
		];
	}
	
	public function getPlatformFeatures() {
		return [PlatformFeatures::VARIATIONS, PlatformFeatures::SHIPPING, PlatformFeatures::CUSTOMER_USERS];
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
			'order' => 'ASC'
		]);
		return $maxOrder ? (int) current($maxOrder)->get_date_created()->date('Y') : null;
	}
	
	public function getNewestOrderYear() {
		$maxOrder = wc_get_orders([
			'limit' => 1
		]);
		return $maxOrder ? (int) current($maxOrder)->get_date_created()->date('Y') : null;
	}
	
	public function runQuery($query, $queryParams, $fieldsMap, $debug) {
		return parent::runQuery( apply_filters( 'woocommerce_reports_get_order_report_query', $query ), $queryParams, $fieldsMap, $debug );
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
			]
		];
		
		return $virtualMeta;
	}
	
	
}
	