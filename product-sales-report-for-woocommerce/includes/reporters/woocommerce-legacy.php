<?php
namespace Ninjalytics\Reporters\WooCommerce;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/woocommerce.php');

class Legacy extends Base {
	
	public function __construct() {
		parent::__construct();
		
		global $wpdb;
		$this->ordersTable = $wpdb->posts;
		$this->ordersIdColumn = 'ID';
		$this->ordersTypeColumn = 'post_type';
		$this->ordersStatusColumn = 'post_status';
		$this->ordersDateColumn = 'post_date_gmt';
		$this->ordersParentIdColumn = 'post_parent';
		$this->ordersMetaTable = $wpdb->postmeta;
		$this->ordersMetaOrderIdColumn = 'post_id';
		$this->orderCustomerFieldIsMeta = true;
		$this->orderCustomerFieldKey = '_customer_user';
	}
	
	public function getStandardFields() {
		$fields = parent::getStandardFields();
		$fields['order_date'] = ['post_data', 'post_date'];
		return $fields;
	}
	
}