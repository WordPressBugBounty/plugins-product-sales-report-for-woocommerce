<?php
namespace NinjalyticsFree\Reporters;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

include_once(__DIR__.'/orders-base.php');

class EDD extends OrdersBase {
	
	const ID = 'edd';
	
	public function __construct() {
		global $wpdb;
		$this->ordersTable = $wpdb->prefix.'edd_orders';
		$this->ordersIdColumn = 'id';
		$this->ordersTypeColumn = 'type';
		$this->ordersStatusColumn = 'status';
		$this->ordersDateColumn = 'date_created';
		$this->ordersParentIdColumn = 'parent';
		$this->ordersMetaTable = $wpdb->prefix.'edd_ordermeta';
		$this->ordersMetaOrderIdColumn = 'edd_order_id';
		$this->orderItemsTable = $wpdb->prefix.'edd_order_items';
		$this->orderItemsOrderIdColumn = 'order_id';
		$this->orderItemsTypeColumn = 'type';
		$this->orderItemsMetaTable = $wpdb->prefix.'edd_order_itemmeta';
		$this->orderItemAjdustmentsTable = $wpdb->prefix.'edd_order_adjustments';
		$this->orderItemsIdColumn = 'id';
		$this->orderItemsMetaItemIdColumn = 'edd_order_item_id';
		$this->productPostType = 'download';
		$this->productCategoryTaxonomy = 'download_category';
		$this->productTagTaxonomy = 'download_tag';
		$this->orderType = 'sale';
		$this->refundOrderType = 'refund';
		$this->productOrderItemsType = 'download';
		$this->completedOrderStatus = 'complete';
		$this->defaultOrderStatuses = function_exists('edd_get_gross_order_statuses') ? edd_get_gross_order_statuses() : [];
		$this->billingStateMetaKey = '_billing_region';
	}
	
	public function getDefaultFields($exportOrders) {
		return $exportOrders ? ['builtin::product_id', 'builtin::product_name', 'builtin::quantity', 'builtin::line_total', 'builtin::order_date', 'builtin::billing_name', 'builtin::billing_email'] : array('builtin::product_id', 'builtin::product_sku', 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_sales');
	}
	
	
	public function getPlatformFeatures() {
		return [PlatformFeatures::CHILD_ITEMS, PlatformFeatures::CHILD_ITEMS_META, PlatformFeatures::META, PlatformFeatures::LINE_ITEM_ADJUSTMENTS];
	}
	
	public function getStandardFields() {
		// These must be SQL safe!
		
		return [
			'order_item_name' => ['order_item', 'product_name'],
			'quantity' => ['order_item', 'quantity'],
			'line_subtotal' => ['order_item', 'subtotal'],
			'line_total' => ['order_item', 'total'],
			'line_tax' => ['order_item', 'tax'],
			'product_id' => ['order_item', 'product_id'],
			'order_total' => ['post_data', 'total'],
			'order_date' => ['post_data', 'date_created_gmt_wpz']
		];
	}
	
	public function getReportTemplates() {
		$templates = parent::getReportTemplates();
		unset($templates['top_rated']);
		return $templates;
	}
	
	public function getOrderStatuses() {
		return edd_get_payment_statuses();
	}
	
	public function getOldestOrderYear() {
		$maxOrder = edd_get_payments([
			'number' => 1,
			'order' => 'ASC',
			'orderby' => 'date',
			'type' => $this->orderType
		]);
		$maxOrderYear = $maxOrder ? (int) strstr(current($maxOrder)->date, '-', true) : null;
		$maxRefund = edd_get_payments([
			'number' => 1,
			'order' => 'ASC',
			'orderby' => 'date',
			'type' => $this->refundOrderType
		]);
		$maxRefundYear = $maxOrder ? (int) strstr(current($maxOrder)->date, '-', true) : null;
		if ($maxOrderYear == null) {
			return $maxRefundYear;
		}
		if ($maxRefundYear == null) {
			return $maxOrderYear;
		}
		return min($maxOrderYear, $maxRefundYear);
	}
	
	public function getNewestOrderYear() {
		$maxOrder = edd_get_payments([
			'number' => 1,
			'orderby' => 'date',
			'type' => $this->orderType
		]);
		$maxOrderYear = $maxOrder ? (int) strstr(current($maxOrder)->date, '-', true) : null;
		$maxRefund = edd_get_payments([
			'number' => 1,
			'orderby' => 'date',
			'type' => $this->refundOrderType
		]);
		$maxRefundYear = $maxOrder ? (int) strstr(current($maxOrder)->date, '-', true) : null;
		if ($maxOrderYear == null) {
			return $maxRefundYear;
		}
		if ($maxRefundYear == null) {
			return $maxOrderYear;
		}
		return max($maxOrderYear, $maxRefundYear);
	}
	
	public function getVirtualOrderMeta() {
		global $wpdb;
		$virtualMeta = [];
		
		foreach (['billing', 'shipping'] as $addressType) {
			foreach (['name', 'address', 'address2', 'city', 'region', 'postal_code', 'country'] as $addressField) {
				$virtualMeta['_'.$addressType.'_'.$addressField] = [
					'field' => 'order_address_'.$addressType.'.'.$addressField,
					'joins' => [
						"order_address_$addressType" => $wpdb->prefix.'edd_order_addresses AS order_address_'.$addressType.' ON ( order_address_'.$addressType.'.order_id=posts.id AND order_address_'.$addressType.'.type="'.$addressType.'" )'
					],
					'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'edd_order_addresses WHERE '.$wpdb->prefix.'edd_order_addresses.order_id=%orderId% AND '.$wpdb->prefix.'edd_order_addresses.type="'.$addressType.'" AND '.$wpdb->prefix.'edd_order_addresses.'.$addressField.'%condition%'
				];
			}
		}
		
		$orderFieldMap = [
			'_order_email' => 'email',
			'_order_currency' => 'currency',
			'_order_total' => 'total',
			'_order_tax' => 'tax',
			'_customer_user' => 'user_id',
			'_payment_method' => 'gateway',
			'_customer_ip_address' => 'ip',
			'_order_key' => 'payment_key',
			'_date_completed' => 'date_completed',
			'_cart_discount' => 'discount'
		];
		
		foreach ($orderFieldMap as $metaField => $orderField) {
			$virtualMeta[$metaField] = [
				'field' => 'posts.'.$orderField,
				'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'wc_orders WHERE '.$wpdb->prefix.'wc_orders.id=%orderId% AND '.$wpdb->prefix.'wc_orders.'.$orderField.'%condition%'
			];
		}
		
		return $virtualMeta;
	}
	
	public function get_order_report_data( $args = array() ) {
		if (isset($args['data']['total'])) {
			$totalFieldName = $args['data']['total']['name'];
			$subtotalFieldName = $args['data']['subtotal']['name'];
			$args['data']['discount'] = $args['data']['total'];
			unset($args['data']['total']);
			
			$result = parent::get_order_report_data($args);
			
			foreach ($result as $row) {
				$row->$totalFieldName = $row->$subtotalFieldName - $row->$totalFieldName;
			}
		}
		
		return $result;
	}
	
}
