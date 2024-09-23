<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

include_once($GLOBALS['woocommerce']->plugin_path().'/includes/admin/reports/class-wc-admin-report.php');

class WC_Admin_Report_HPOS_WPZ extends WC_Admin_Report {
	
	public static function getVirtualOrderMeta() {
		global $wpdb;
		$virtualMeta = [];
		
		foreach (['billing', 'shipping'] as $addressType) {
			foreach (['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone'] as $addressField) {
				$virtualMeta['_'.$addressType.'_'.$addressField] = [
					'field' => 'order_address_'.$addressType.'.'.$addressField,
					'joins' => [
						"order_address_$addressType" => $wpdb->prefix.'wc_order_addresses AS order_address_'.$addressType.' ON ( order_address_'.$addressType.'.order_id=posts.id AND address_type="'.$addressType.'" )'
					],
					'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'wc_order_addresses WHERE order_id=%orderId% AND '.$addressField.'%condition%'
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
				'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'wc_orders WHERE order_id=%orderId% AND '.$orderField.'%condition%'
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
				'filterSubquery' => 'SELECT 1 FROM '.$wpdb->prefix.'wc_order_operational_data WHERE order_id=%orderId% AND '.$opField.'%condition%'
			];
		}
		
		return $virtualMeta;
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
		
		$virtualMeta = self::getVirtualOrderMeta();

		$default_args = array(
			'data'                => array(),
			'where'               => array(),
			'where_meta'          => array(),
			'query_type'          => 'get_row',
			'group_by'            => '',
			'order_by'            => '',
			'limit'               => '',
			'filter_range'        => false,
			'nocache'             => false,
			'debug'               => false,
			'order_types'         => wc_get_order_types( 'reports' ),
			'order_status'        => array( 'completed', 'processing', 'on-hold' ),
			'parent_order_status' => false,
		);
		$args         = apply_filters( 'woocommerce_reports_get_order_report_data_args', $args );
		$args         = wp_parse_args( $args, $default_args );

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args );

		if ( empty( $data ) ) {
			return '';
		}

		$order_status = apply_filters( 'woocommerce_reports_order_statuses', $order_status );

		$query  = array();
		$select = array();

		foreach ( $data as $raw_key => $value ) {
			
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
			}

			if ( empty( $get_key ) ) {
				// Skip to the next foreach iteration else the query will be invalid.
				continue;
			}
			
			if ($raw_key == 'date_created_gmt_wpz') {
				$gmtOffset = get_option('gmt_offset');
				$get_key = ($gmtOffset < 0 ? 'DATE_SUB' : 'DATE_ADD').'('.$get_key.',INTERVAL '.abs($gmtOffset).' HOUR)';
			}
			
			if ( $value['function'] ) {
				$get = "{$value['function']}({$distinct} {$get_key})";
			} else {
				$get = "{$distinct} {$get_key}";
			}

			$select[] = "{$get} as {$value['name']}";
		}

		$query['select'] = 'SELECT ' . implode( ',', $select );
		$query['from']   = "FROM {$wpdb->prefix}wc_orders AS posts";

		// Joins.
		$joins = array();

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
						$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}wc_orders_meta AS meta_{$key} ON ( posts.id = meta_{$key}.order_id AND meta_{$key}.meta_key = '{$raw_key}' )";
					}
					break;
				case 'parent_meta':
					$joins[ "parent_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}wc_orders_meta AS parent_meta_{$key} ON (posts.parent_order_id = parent_meta_{$key}.order_id) AND (parent_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'order_item_meta':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON (posts.id = order_items.order_id)";

					if ( ! empty( $value['order_item_type'] ) ) {
						$joins['order_items'] .= " AND (order_items.order_item_type = '{$value['order_item_type']}')";
					}

					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON " .
														"(order_items.order_item_id = order_item_meta_{$key}.order_item_id) " .
														" AND (order_item_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'order_item':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.id = order_items.order_id";
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

					$joins['order_items']              = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.id = order_items.order_id";
					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items.order_item_id = order_item_meta_{$key}.order_item_id";

				} else {
					if (isset($virtualMeta[$key])) {
						if (isset($virtualMeta[$key]['joins'])) {
							foreach ($virtualMeta[$key]['joins'] as $joinId => $joinSql) {
								$joins[$joinId] = "{$join_type} JOIN {$joinSql}";
							}
						}
					} else {
						
						// If we have a where clause for meta, join the postmeta table.
						$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}wc_orders_meta AS meta_{$key} ON posts.id = meta_{$key}.order_id";
						
					}
				}
			}
		}

		if ( ! empty( $parent_order_status ) ) {
			$joins['parent'] = "LEFT JOIN {$wpdb->prefix}wc_orders AS parent ON posts.parent_order_id = parent.id";
		}

		$query['join'] = implode( ' ', $joins );

		$query['where'] = "
			WHERE 	posts.type 	IN ( '" . implode( "','", $order_types ) . "' )
			";

		if ( ! empty( $order_status ) ) {
			$query['where'] .= "
				AND 	posts.status 	IN ( 'wc-" . implode( "','wc-", $order_status ) . "')
			";
		}

		if ( ! empty( $parent_order_status ) ) {
			if ( ! empty( $order_status ) ) {
				$query['where'] .= " AND ( parent.status IN ( 'wc-" . implode( "','wc-", $parent_order_status ) . "') OR parent.id IS NULL ) ";
			} else {
				$query['where'] .= " AND parent.status IN ( 'wc-" . implode( "','wc-", $parent_order_status ) . "') ";
			}
		}

		if ( $filter_range ) {
			$query['where'] .= "
				AND 	posts.date_created_gmt >= '" . esc_sql(get_gmt_from_date( date('Y-m-d H:i:s', $this->start_date) )) . "'
				AND 	posts.date_created_gmt < '" . esc_sql(get_gmt_from_date( date('Y-m-d H:i:s', strtotime( '+1 DAY', $this->end_date )) )) . "'
			";
		}

		if ( ! empty( $where_meta ) ) {

			$relation = isset( $where_meta['relation'] ) ? $where_meta['relation'] : 'AND';

			$query['where'] .= ' AND (';

			foreach ( $where_meta as $index => $value ) {

				if ( ! is_array( $value ) ) {
					continue;
				}

				$key = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {

					if ( is_array( $value['meta_value'] ) ) {
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						$value['meta_value'] = implode( "','", $value['meta_value'] );
					}

					if ( ! empty( $value['meta_value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['meta_value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['meta_value']}'";
				}

				if ( ! empty( $where_value ) ) {
					if ( $index > 0 ) {
						$query['where'] .= ' ' . $relation;
					}

					if ( isset( $value['type'] ) && 'order_item_meta' === $value['type'] ) {

						if ( is_array( $value['meta_key'] ) ) {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
						} else {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   = '{$value['meta_key']}'";
						}

						$query['where'] .= " AND order_item_meta_{$key}.meta_value {$where_value} )";
					} else {
						
						if ( isset($virtualMeta[$key]) ) {
							
							$query['where'] .= $virtualMeta[$key]['field']." {$where_value} )";
							
						} else {

							if ( is_array( $value['meta_key'] ) ) {
								$query['where'] .= " ( meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
							} else {
								$query['where'] .= " ( meta_{$key}.meta_key   = '{$value['meta_key']}'";
							}

							$query['where'] .= " AND meta_{$key}.meta_value {$where_value} )";
						
						}
					}
				}
			}

			$query['where'] .= ')';
		}

		if ( ! empty( $where ) ) {

			foreach ( $where as $value ) {

				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {

					if ( is_array( $value['value'] ) ) {
						$value['value'] = implode( "','", $value['value'] );
					}

					if ( ! empty( $value['value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['value']}'";
				}

				if ( ! empty( $where_value ) ) {
					$query['where'] .= " AND {$value['key']} {$where_value}";
				}
			}
		}

		if ( $group_by ) {
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$query['order_by'] = "ORDER BY {$order_by}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		$query = apply_filters( 'woocommerce_reports_get_order_report_query', $query );
		$query = implode( ' ', $query );

		if ( $debug ) {
			echo '<pre>';
			wc_print_r( $query );
			echo '</pre>';
		}

		if ( $debug || $nocache ) {
			self::enable_big_selects();

			$result = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
		} else {
			$query_hash = md5( $query_type . $query );
			$result     = $this->get_cached_query( $query_hash );
			if ( null === $result ) {
				self::enable_big_selects();

				$result = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
			}
			$this->set_cached_query( $query_hash, $result );
		}

		return $result;
	}
}
