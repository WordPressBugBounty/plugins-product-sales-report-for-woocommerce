<?php
namespace NinjalyticsFree\Reporters\WooCommerce;

use NinjalyticsFree\Reporters\PlatformFeatures;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/woocommerce.php');

class Legacy extends Base {
	
	const ID = 'woocommerce-legacy';
	
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
		$fields['status'] = ['post_data', 'post_status'];
		$fields['customer_id'] = ['meta', '_customer_user'];
		$fields['customer_note'] = ['post_data', 'post_excerpt'];
		$fields['order_parent'] = ['post_data', 'post_parent'];
		$fields['order_creator_id'] = ['post_data', 'post_author'];
		return $fields;
	}
	
	public function getPlatformFeatures() {
		return array_merge(parent::getPlatformFeatures(), [PlatformFeatures::ORDER_CREATOR]);
	}
}