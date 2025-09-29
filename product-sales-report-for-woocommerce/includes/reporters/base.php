<?php
namespace Ninjalytics\Reporters;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

enum PlatformFeatures {
	case VARIATIONS;
	case SHIPPING;
	case ORDER_SOURCE;
	case CUSTOMER_USERS;
	case LINE_ITEM_ADJUSTMENTS;
}

abstract class Base {
	
	public $ordersTable, $ordersIdColumn, $ordersTypeColumn, $ordersStatusColumn, $ordersDateColumn, $ordersParentIdColumn, $ordersMetaTable, $ordersMetaOrderIdColumn, $orderItemsTable, $orderItemsOrderIdColumn, $orderItemsTypeColumn, $orderItemsMetaTable, $orderItemsIdColumn, $orderItemsNameColumn, $orderItemsMetaItemIdColumn, $orderItemAjdustmentsTable, $billingStateMetaKey, $orderCustomerFieldIsMeta, $orderCustomerFieldKey, $productPostType, $productCategoryTaxonomy, $productTagTaxonomy, $orderType, $refundOrderType, $productOrderItemsType, $completedOrderStatus, $defaultOrderStatuses, $hiddenOrderItemFields = [], $debugSqlCallback;
	
	public $start_date, $end_date;
	
	private static $tzTransitions;

	abstract public function getPlatformFeatures();
	
	abstract public function getStandardFields();
	
	abstract public function getOrderStatuses();
	
	abstract public function getOldestOrderYear();
	
	abstract public function getNewestOrderYear();
	
	public function supports($feature) {
		return in_array($feature, $this->getPlatformFeatures());
	}
	
	public function getVirtualOrderMeta() {
		return [];
	}
	
	public function setDebugSqlCallback($cb) {
		$this->debugSqlCallback = $cb;
	}
	
	public function getDefaults() {
		return array(
			'data'                => array(),
			'where'               => array(),
			'where_meta'          => array(),
			'group_by'            => '',
			'order_by'            => '',
			'limit'               => '',
			'filter_range'        => false,
			'nocache'             => false,
			'debug'               => false,
			'order_types'         => [],
			'order_status'        => [],
			'parent_order_status' => false,
		);
	}
	
		
	public function getReportTemplates() {
		return [
			'all_sales' => [
				'preset_name' => 'All Sales',
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
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', 'builtin::quantity_sold', 'builtin::gross_after_discount' ],
				'chart_fields' => [ 'builtin::gross_after_discount' ],
				'orderby' => 'builtin::gross_after_discount',
				'variations' => 0,
				'limit_on' => 1,
				'icon' => 'icon_3'
			],
			'top_rated' => [
				'preset_name' => 'Top Rated Products',
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', '_wc_average_rating' ],
				'field_names' => [ '_wc_average_rating' => 'Rating' ],
				'chart_fields' => [ '_wc_average_rating' ],
				'orderby' => '_wc_average_rating',
				'variations' => 0,
				'limit_on' => 1,
				'icon' => 'icon_5',
				'pro' => true
			],
			'stock' => [
				'preset_name' => 'Stock Report',
				'chart_type' => 'bar',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', 'builtin::product_stock' ],
				'chart_fields' => [ 'builtin::product_stock' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'variations' => 0,
				'icon' => 'icon_6'
			],
			'state_sales' => [
				'preset_name' => 'Sales by US State',
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
				'icon' => 'icon_7'
			],
			'product_sales' => [
				'preset_name' => 'Sales by Product',
				'chart_type' => 'pie',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'variations' => 0,
				'icon' => 'icon_8'
			],
			'payment_method_sales' => [
				'preset_name' => 'Sales by Payment Method',
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o__payment_method',
				'fields' => ['builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold'],
				'field_names' => ['builtin::groupby_field' => 'Payment Method' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'icon' => 'icon_9'
			],
			'currency_sales' => [
				'preset_name' => 'Sales by Currency',
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o__order_currency',
				'fields' => ['builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold'],
				'field_names' => ['builtin::groupby_field' => 'Currency' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'icon' => 'icon_1'
			],
			'country_sales' => [
				'preset_name' => 'Sales by Country',
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o__billing_country',
				'fields' => ['builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold'],
				'field_names' => ['builtin::groupby_field' => 'Billing Country' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'icon' => 'icon_2'
			]
		];
	}
	
	public function getGmtConversionSql($field) {
		if (!isset(self::$tzTransitions)) {
			global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$dates = $wpdb->get_row('SELECT MIN(date_created_gmt), MAX(date_created_gmt) FROM '.$wpdb->prefix.'wc_orders', ARRAY_N);
			if (!$dates || !count($dates) == 2) {
				throw new Exception();
			}
			$min = strtotime($dates[0]);
			$max = strtotime($dates[1]);
			
			// Add a week on either end just as a precaution
			$min -= 86400 * 7;
			$max += 86400 * 7;
			
			$tz = wp_timezone();
			self::$tzTransitions = $tz->getTransitions($min, $max);
			
			if (self::$tzTransitions === false) {
				self::$tzTransitions = [
					[
						'offset' => $tz->getOffset( new DateTime() )
					]
				];
			}
		}
		
		$sql = '';
		$sqlEnd = '';
		for ($i = count(self::$tzTransitions) - 1; $i > 0; --$i) {
			$sql .= 'IF('.$field.'>="'.gmdate('Y-m-d H:i:s', self::$tzTransitions[$i]['ts']).'",DATE_ADD('.$field.', INTERVAL '.((int) self::$tzTransitions[$i]['offset']).' SECOND),';
		}
		
		$sql .= 'DATE_ADD('.$field.', INTERVAL '.((int) self::$tzTransitions[0]['offset']).' SECOND'.str_repeat(')', count(self::$tzTransitions));
		
		return $sql;
	}
	

	/**
	 * Get report totals such as order totals and discount amounts.
	 *
	 * Data example:
	 *
	 * '_order_total' => array(
	 *     'type'     => 'meta',
	 *     'function' => 'SUM',
	 *     'name'     => 'total_sales'
	 * )
	 *
	 * @param  array $args arguments for the report.
	 * @return mixed depending on query_type
	 */
	public function get_order_report_data( $args = array() ) {
		global $wpdb;
		
		$virtualMeta = $this->getVirtualOrderMeta();

		$args         = wp_parse_args( $args, $this->getDefaults() );

		extract( $args );

		if ( empty( $data ) ) {
			return '';
		}

		$query  = array();
		$queryParams = [];
		$select = array();
		
		$fieldsMap = [];

		foreach ( $data as $raw_key => $value ) {
			
			if (strstr($raw_key, '.', true) === $value['type']) {
				$raw_key = substr(strstr($raw_key, '.'), 1);
			}
			
			if ($raw_key == 'date_created_gmt_wpz') {
				$key = 'date_created_gmt';
			} else {
				$key      = sanitize_key( $raw_key );
			}
			$distinct = '';

			if ( isset( $value['distinct'] ) ) {
				$distinct = 'DISTINCT';
			}

			switch ( $value['type'] ) {
				case 'meta':
					$get_key = isset($virtualMeta[$key]) ? $virtualMeta[$key]['field'] : "meta_{$key}.meta_value";
					break;
				case 'parent_meta':
					$get_key = "parent_meta_{$key}.meta_value";
					break;
				case 'post_data':
					$get_key = "posts.{$key}";
					break;
				case 'order_item_meta':
					$get_key = "order_item_meta_{$key}.meta_value";
					break;
				case 'order_item':
					$get_key = "order_items.{$key}";
					break;
				case 'order_item_adjustment':
					$get_key = "order_item_adjustments.{$key}";
					break;
			}

			if ( empty( $get_key ) ) {
				continue;
			}
			
			if ($raw_key == 'date_created_gmt_wpz') {
				$get_key = $this->getGmtConversionSql($get_key);
			}
			
			if ( $value['function'] ) {
				$get = preg_replace('/\\s/', '', $value['function'])."({$distinct} {$get_key})";
			} else {
				$get = "{$distinct} {$get_key}";
			}

			$select[] = "{$get} as field" . count($fieldsMap);
			$fieldsMap[$value['name']] = 'field' . count($fieldsMap);
		}

		$query['select'] = 'SELECT ' . implode( ', ', $select );
		$query['from']   = " FROM {$this->ordersTable} AS posts";

		$joins = array();
		$joinParams = array();

		foreach ( ( $data + $where ) as $raw_key => $value ) {
			$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
			$type      = isset( $value['type'] ) ? $value['type'] : false;
			$key       = sanitize_key( $raw_key );

			switch ( $type ) {
				case 'meta':
					if (isset($virtualMeta[$key])) {
						if (isset($virtualMeta[$key]['joins'])) {
							foreach ($virtualMeta[$key]['joins'] as $joinId => $joinSql) {
								$joins[$joinId] = "{$join_type} JOIN {$joinSql}";
							}
						}
					} else {
						$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$this->ordersMetaTable} AS meta_{$key} ON ( posts.{$this->ordersIdColumn} = meta_{$key}.{$this->ordersMetaOrderIdColumn} AND meta_{$key}.meta_key = %s )";
						$joinParams["meta_{$key}"] = [$raw_key];
					}
					break;
				case 'parent_meta':
					$joins[ "parent_meta_{$key}" ] = "{$join_type} JOIN {$this->ordersMetaTable} AS parent_meta_{$key} ON (posts.{$this->ordersParentIdColumn} = parent_meta_{$key}.{$this->ordersMetaOrderIdColumn}) AND (parent_meta_{$key}.meta_key = %s)";
					$joinParams["parent_meta_{$key}"] = [$raw_key];
					break;
				case 'order_item_meta':
					$joins['order_items'] = "{$join_type} JOIN {$this->orderItemsTable} AS order_items ON (posts.id = order_items.{$this->orderItemsOrderIdColumn})";

					if ( ! empty( $value['order_item_type'] ) ) {
						$joins['order_items'] .= " AND (order_items.{$this->orderItemsTypeColumn} = %s)";
						$joinParams['order_items'] = [$value['order_item_type']];
					}

					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$this->orderItemsMetaTable} AS order_item_meta_{$key} ON " .
														"(order_items.{$this->orderItemsIdColumn} = order_item_meta_{$key}.{$this->orderItemsMetaItemIdColumn}) " .
														" AND (order_item_meta_{$key}.meta_key = %s)";
					$joinParams["order_item_meta_{$key}"] = [$raw_key];
					break;
				case 'order_item':
					if (!isset($joins['order_items'])) {
						$joins['order_items'] = "{$join_type} JOIN {$this->orderItemsTable} AS order_items ON (posts.id = order_items.{$this->orderItemsOrderIdColumn})";
					}
					break;
				case 'order_item_adjustment':
					$joins['order_item_adjustment'] = "{$join_type} JOIN {$this->orderItemAjdustmentsTable} AS order_item_adjustments ON (order_item_adjustments.object_type='order_item' AND order_item_adjustments.object_id = order_items.{$this->orderItemsIdColumn})";
					break;
			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
				$type      = isset( $value['type'] ) ? $value['type'] : false;
				$key       = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( 'order_item_meta' === $type ) {
					if (!isset($joins['order_items'])) {
						$joins['order_items'] = "{$join_type} JOIN {$this->orderItemsTable} AS order_items ON (posts.id = order_items.{$this->orderItemsOrderIdColumn})";
					}
					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$this->orderItemsMetaTable} AS order_item_meta_{$key} ON " .
														"(order_items.{$this->orderItemsIdColumn} = order_item_meta_{$key}.{$this->orderItemsMetaItemIdColumn}) " .
														" AND (order_item_meta_{$key}.meta_key = %s)";
					$joinParams["order_item_meta_{$key}"] = [ ((array)$value['meta_key'])[0] ];

				} else {
					if (isset($virtualMeta[$key])) {
						if (isset($virtualMeta[$key]['joins'])) {
							foreach ($virtualMeta[$key]['joins'] as $joinId => $joinSql) {
								$joins[$joinId] = "{$join_type} JOIN {$joinSql}";
							}
						}
					} else {
						$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$this->ordersMetaTable} AS meta_{$key} ON ( posts.{$this->ordersIdColumn} = meta_{$key}.{$this->ordersMetaOrderIdColumn} AND meta_{$key}.meta_key = %s )";
						$joinParams["meta_{$key}"] = [ ((array)$value['meta_key'])[0] ];
					}
				}
			}
		}

		if ( ! empty( $parent_order_status ) ) {
			$joins['parent'] = "LEFT JOIN {$this->ordersTable} AS parent ON posts.{$this->ordersParentIdColumn} = parent.{$this->ordersIdColumn}";
		}
		
		$query['join'] = '';
		$queryParams['join'] = [];
		foreach ($joins as $joinId => $joinSql) {
			$query['join'] .= ' '.$joinSql;
			$queryParams['join'] = array_merge($queryParams['join'], $joinParams[$joinId] ?? []);
		}
		
		$queryParams['where'] = $order_types;
		$query['where'] = "
			WHERE 	posts.{$this->ordersTypeColumn} 	IN ( " . substr(str_repeat('%s,', count($order_types)), 0, -1) . " )
			";

		if ( ! empty( $order_status ) ) {
			$queryParams['where'] = array_merge($queryParams['where'], $order_status);
			$query['where'] .= "
				AND 	posts.{$this->ordersStatusColumn} 	IN (" . substr(str_repeat('%s,', count($order_status)), 0, -1) . ")
			";
		}

		if ( ! empty( $parent_order_status ) ) {
			$queryParams['where'] = array_merge($queryParams['where'], $parent_order_status);
			if ( ! empty( $order_status ) ) {
				$query['where'] .= " AND ( parent.{$this->ordersStatusColumn} IN (" . substr(str_repeat('%s,', count($parent_order_status)), 0, -1) . ") OR parent.id IS NULL ) ";
			} else {
				$query['where'] .= " AND parent.{$this->ordersStatusColumn} IN (" . substr(str_repeat('%s,', count($parent_order_status)), 0, -1) . ") ";
			}
		}

		if ( $filter_range ) {
			$query['where'] .= "
				AND 	posts.{$this->ordersDateColumn} >= %s
				AND 	posts.{$this->ordersDateColumn} < %s
			";
			$queryParams['where'][] = get_gmt_from_date( gmdate('Y-m-d H:i:s', $this->start_date) );
			$queryParams['where'][] = get_gmt_from_date( gmdate('Y-m-d H:i:s', strtotime( '+1 DAY', $this->end_date )) );
			
		}

		if ( ! empty( $where_meta ) ) {

			$relation = isset( $where_meta['relation'] ) ? preg_replace('/\\s/', '', $where_meta['relation']) : 'AND';

			$query['where'] .= ' AND (';

			foreach ( $where_meta as $index => $value ) {

				if ( ! is_array( $value ) ) {
					continue;
				}

				if ( $index > 0 ) {
					$query['where'] .= ' ' . $relation . ' ';
				}
				
				$key = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( isset( $value['type'] ) && 'order_item_meta' === $value['type'] ) {
					$query['where'] .= "order_item_meta_{$key}.meta_value ";
				} else {
					if ( isset($virtualMeta[$key]) ) {
						$query['where'] .= $virtualMeta[$key]['field']." ";
					} else {
						$query['where'] .= "meta_{$key}.meta_value ";
					
					}
				}
				
				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {
					$queryParams['where']  = array_merge($queryParams['where'], (array) $value['meta_value']);
					$query['where'] .= "{$value['operator']} (". substr(str_repeat('%s,', count((array) $value['meta_value'])), 0, -1) .")";
				} else {
					$queryParams['where'][] = $value['meta_value'];
					$query['where'] .= preg_replace('/\\s/', '', $value['operator']).' %s';
				}
			}

			$query['where'] .= ')';
		}

		// woocommerce\includes\admin\reports\class-wc-admin-report.php
		if ( ! empty( $where ) ) {
			foreach ( $where as $value ) {
				if (!in_array($value['key'], [$this->ordersDateColumn], true)) {
					throw new \Exception('Unsupported "where" value.');
				}

				$query['where'] .= ' AND posts.'.$value['key'].' ';
				
				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {
					$queryParams['where']  = array_merge( (array) $value['value'], $queryParams['where']);
					$query['where'] .= "{$value['operator']} (". substr(str_repeat('%s,', count((array) $value['value'])), 0, -1) .")";
				} else {
					$queryParams['where'][] = $value['value'];
					$query['where'] .= preg_replace('/\\s/', '', $value['operator']).' %s';
				}

			}
		}

		if ( $group_by ) {
			$group_by = explode(',', $group_by);
			foreach ($group_by as &$item) {
				if ( $item != 'product_category' && $item != 'product_sku' ) {
					$item = trim($item);
					if (!isset($fieldsMap[$item])) {
						throw new \Exception('Invalid "group by" value: '.esc_html($item));
					}
					$item = $fieldsMap[$item];
				}
			}
			$group_by = implode(',', $group_by);
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$order_by = explode(' ', trim($order_by));
			if (count($order_by) > 2 || !isset($fieldsMap[$order_by[0]]) || !in_array(strtolower($order_by[1]), ['asc', 'desc'], true)) {
				throw new \Exception('Invalid "order by" value.');
			}
			$query['order_by'] = 'ORDER BY '.$fieldsMap[$order_by[0]].' '.$order_by[1];
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT %d";
			$queryParams['limit'] = [ $limit ];
		}

		return $this->runQuery( apply_filters('ninjalytics_get_order_report_query', $query), $queryParams, $fieldsMap, $debug);
		
	}
	
	public function runQuery($query, $queryParams, $fieldsMap, $debug) {
		global $wpdb;
		
		$querySql = '';
		$allParams = [];
		foreach ($query as $queryPartId => $sql) {
			$querySql .= $sql;
			$allParams = array_merge( $allParams, ($queryParams[$queryPartId] ?? []) );
		}
		
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- False positive
		$querySql = $wpdb->prepare($querySql, $allParams);
		
		if ( $debug && $this->debugSqlCallback ) {
			call_user_func($this->debugSqlCallback, $querySql);
		}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Prepared above
		$result = $wpdb->get_results($querySql, ARRAY_A );
		
		$fieldsMap = array_flip($fieldsMap);
		$result = array_map(
			function($row) use ($fieldsMap) {
				return (object) array_combine(
					array_map(
						function($field) use ($fieldsMap) {
							return $fieldsMap[$field] ?? $field;
						},
						array_keys($row)
					),
					array_values($row)
				);
			},
			$result
		);

		return $result;
	}
	
	
}
