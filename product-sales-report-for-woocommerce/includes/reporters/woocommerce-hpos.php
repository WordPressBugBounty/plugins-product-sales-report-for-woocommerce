<?php
namespace Ninjalytics\Reporters\WooCommerce;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/woocommerce.php');

class Hpos extends Base {
	
	public function __construct() {
		parent::__construct();
		
		global $wpdb;
		$this->ordersTable = $wpdb->prefix.'wc_orders';
		$this->ordersIdColumn = 'id';
		$this->ordersTypeColumn = 'type';
		$this->ordersStatusColumn = 'status';
		$this->ordersDateColumn = 'date_created_gmt';
		$this->ordersParentIdColumn = 'parent_order_id';
		$this->ordersMetaTable = $wpdb->prefix.'wc_orders_meta';
		$this->ordersMetaOrderIdColumn = 'order_id';
		$this->orderCustomerFieldIsMeta = false;
		$this->orderCustomerFieldKey = 'customer_id';
	}
	
	public function getVirtualOrderMeta() {
		global $wpdb;
		$virtualMeta = parent::getVirtualOrderMeta();
		
		foreach (['billing', 'shipping'] as $addressType) {
			foreach (['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone'] as $addressField) {
				$virtualMeta['_'.$addressType.'_'.$addressField] = [
					'field' => 'order_address_'.$addressType.'.'.$addressField,
					'joins' => [
						"order_address_$addressType" => $wpdb->prefix.'wc_order_addresses AS order_address_'.$addressType.' ON ( order_address_'.$addressType.'.order_id=posts.id AND order_address_'.$addressType.'.address_type="'.$addressType.'" )'
					],
					'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'wc_order_addresses WHERE '.$wpdb->prefix.'wc_order_addresses.order_id=%orderId% AND '.$wpdb->prefix.'wc_order_addresses.address_type="'.$addressType.'" AND '.$wpdb->prefix.'wc_order_addresses.'.$addressField.'%condition%'
				];
			}
		}
		
		$orderFieldMap = [
			'_order_currency' => 'currency',
			'_order_total' => 'total_amount',
			'_order_tax' => 'tax_amount',
			'_customer_user' => 'customer_id',
			'_payment_method' => 'payment_method',
			'_payment_method_title' => 'payment_method_title',
			'_transaction_id' => 'transaction_id',
			'_customer_ip_address' => 'ip_address',
			'_customer_user_agent' => 'user_agent'
		];
		
		foreach ($orderFieldMap as $metaField => $orderField) {
			$virtualMeta[$metaField] = [
				'field' => 'posts.'.$orderField,
				'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'wc_orders WHERE '.$wpdb->prefix.'wc_orders.id=%orderId% AND '.$wpdb->prefix.'wc_orders.'.$orderField.'%condition%'
			];
		}
		
		$opDataMap = [
			'_created_via' => 'created_via',
			'_prices_include_tax' => 'prices_include_tax',
			'_recorded_coupon_usage_counts' => 'coupon_usages_are_counted',
			'_download_permission_granted' => 'download_permission_granted',
			'_cart_hash' => 'cart_hash',
			'_new_order_email_sent' => 'new_order_email_sent',
			'_order_key' => 'order_key',
			'_order_stock_reduced' => 'order_stock_reduced',
			'_date_paid' => 'date_paid_gmt',
			'_date_completed' => 'date_completed_gmt',
			'_order_shipping_tax' => 'shipping_tax_amount',
			'_order_shipping' => 'shipping_total_amount',
			'_cart_discount_tax' => 'discount_tax_amount',
			'_cart_discount' => 'discount_total_amount',
			'_recorded_sales' => 'recorded_sales'
		];
		
		foreach ($opDataMap as $metaField => $opField) {
			$virtualMeta[$metaField] = [
				'field' => 'order_op_data.'.$opField,
				'joins' => [
					'order_op_data' => $wpdb->prefix.'wc_order_operational_data AS order_op_data ON ( order_op_data.order_id=posts.id )'
				],
				'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'wc_order_operational_data WHERE '.$wpdb->prefix.'wc_order_operational_data.order_id=%orderId% AND '.$wpdb->prefix.'wc_order_operational_data.'.$opField.'%condition%'
			];
		}
		
		return $virtualMeta;
	}
}
