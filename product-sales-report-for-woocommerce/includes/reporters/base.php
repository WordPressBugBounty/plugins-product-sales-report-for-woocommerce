<?php
namespace NinjalyticsFree\Reporters;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

enum PlatformFeatures {
	case VARIATIONS;
	case SHIPPING;
	case ORDER_SOURCE;
	case CUSTOMER_USERS;
	case LINE_ITEM_ADJUSTMENTS;
	case CHILD_ITEMS;
	case CHILD_ITEMS_META;
	case CHILD_ITEMS_FILTER;
	case META;
	case COGS;
}

abstract class Base {
	
	public $ordersTable, $ordersIdColumn, $ordersDateColumn, $ordersParentIdColumn, $ordersMetaTable, $ordersMetaOrderIdColumn, $debugSqlCallback, $customFieldNames, $customFieldNames_export;
	
	public $start_date, $end_date;
	
	private static $tzTransitions;

	abstract public function getPlatformFeatures();
	
	abstract public function getStandardFields();
	
	abstract public function getPrimaryItemsName();
	
	abstract public function getOrderStatuses();
	
	abstract public function getOldestOrderYear();
	
	abstract public function getNewestOrderYear();
	
	abstract function getGroupByFields();
	
	abstract function getCustomFields($exportOrders, $includeDisplay = false, $productFieldsOnly = false);
	
	abstract function getBuiltInFields($exportOrders);
	
	abstract function getRow($product, $fields, &$totals, $fieldbuilderFields, $fieldbuilderDependencies);
	
	abstract public function getGroupByFieldTypes();
	
	abstract public function getDataParams($baseFields);
	
	abstract public function getDefaultFields($exportOrders);
	
	public function supports($feature) {
		return in_array($feature, $this->getPlatformFeatures());
	}
	public function getDefaultSettings($exportOrders) {
		$today = wp_date('Y-m-d');
		return array(
			'export_orders' => $exportOrders ? 1 : 0,
			'display_mode' => 'table',
			'report_time' => '30d',
			'report_start' => '',
			'report_end' => '',
			'order_statuses' => $this->defaultOrderStatuses,
			'products' => 'all',
			'product_cats' => array(),
			'product_ids' => '',
			'variations' => 1,
			'groupby' => '',
			'enable_custom_segments' => -1,
			'orderby' => 'quantity',
			'orderdir' => 'desc',
			'fields' => $this->getDefaultFields($exportOrders),
			'total_fields' => array('builtin::quantity_sold', 'builtin::gross_sales', 'builtin::gross_after_discount', 'builtin::taxes', 'builtin::total_with_tax'),
			'field_names' => array(),
			'chart_fields' => [],
			'chart_series_name' => 'builtin::product_name',
			'limit_on' => 0,
			'limit' => 10,
			'include_nil' => 0,
			'include_unpublished' => 1,
			'include_shipping' => 0,
			'order_shipping_filter' => [],
			'include_header' => 1,
			'include_totals' => 0,
			'format_amounts' => 1,
			'exclude_free' => 0,
			'refunds' => 1,
			'adjustments' => 1,
			'report_unfiltered' => 0,
			'report_title_on' => 0,
			'report_title' => '[preset] - [start] to [end]',
			'hm_psr_debug' => 0,
			'time_limit' => 300,
			'format_csv_delimiter' => ',',
			'format_csv_surround' => '"',
			'format_csv_escape' => '\\',
			'disable_product_grouping' => 0,
			'intermediate_rounding' => 0,
			'round_fields' => ['builtin::gross_sales', 'builtin::gross_after_discount', 'builtin::taxes', 'builtin::discount', 'builtin::total_with_tax', 'builtin::avg_order_total'],
			'chart_type' => 'line_series',
			
			'report_time_mode' => 'preset',
			'report_time_preset' => 'last30',
			'report_time_basic_from' => '',
			'report_time_basic_from_unit' => 'max',
			'report_time_basic_from_round' => '',
			'report_time_basic_to' => '',
			'report_time_basic_to_unit' => 'max',
			'report_time_basic_to_round' => '',
			'report_time_absolute_from_date' => $today,
			'report_time_absolute_from_time' => '00:00:00',
			'report_time_absolute_to_date' => $today,
			'report_time_absolute_to_time' => '23:59:59'
		);
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
		return apply_filters('ninjalytics_report_templates', [
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
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_3',
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
				'chart_series_name' => 'builtin::product_name',
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
				'chart_series_name' => 'builtin::product_name',
				'icon' => 'icon_6'
			],
			'state_sales' => [
				'preset_name' => 'Sales by US State',
				'display_mode' => 'chart',
				'chart_type' => 'pie',
				'disable_product_grouping' => 1,
				'groupby' => 'o_'.$this->billingStateMetaKey ?? '',
				'fields' => [ 'builtin::groupby_field', 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'field_names' => [ 'builtin::groupby_field' => 'Billing State' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'order_meta_filter_on' => 1,
				'order_meta_filter_key' => '_billing_country',
				'order_meta_filter_op' => '=',
				'order_meta_filter_value' => 'US',
				'orderby' => 'builtin::groupby_field',
				'orderdir' => 'asc',
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_7',
			],
			'product_sales' => [
				'preset_name' => 'Sales by Product',
				'chart_type' => 'pie',
				'fields' => [ 'builtin::product_name', 'builtin::product_sku', 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'chart_fields' => [ 'builtin::gross_after_discount', 'builtin::quantity_sold' ],
				'orderby' => 'builtin::product_name',
				'orderdir' => 'asc',
				'variations' => 0,
				'chart_series_name' => 'builtin::product_name',
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
				'chart_series_name' => 'builtin::groupby_field',
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
				'chart_series_name' => 'builtin::groupby_field',
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
				'chart_series_name' => 'builtin::groupby_field',
				'icon' => 'icon_2'
			]
		]);
	}
	
	public function getGmtConversionSql($field) {
		if (!isset(self::$tzTransitions)) {
			global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$dates = $wpdb->get_row('SELECT MIN(date_created_gmt), MAX(date_created_gmt) FROM '.$wpdb->prefix.'wc_orders', ARRAY_N);
			if (!$dates || !count($dates) == 2) {
				throw new \Exception();
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
						'offset' => $tz->getOffset( new \DateTime() )
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
	
	function getSelectForField($key, $type) {
			
		if ($key == 'date_created_gmt_wpz') {
			$needsGmtConversion = true;
			$key = 'date_created_gmt';
		}
			
		switch ( $type ) {
			case 'meta':
				$get_key = "meta_{$key}.meta_value";
				break;
			case 'parent_meta':
				$get_key = "parent_meta_{$key}.meta_value";
				break;
			case 'post_data':
				$get_key = "posts.{$key}";
				break;
		}
		
		if (isset($get_key) && ($needsGmtConversion ?? false)) {
			$get_key = $this->getGmtConversionSql($get_key);
		}
		
		return $get_key;

	}
	
	function addJoinForField($raw_key, $key, $value, &$joins, &$joinParams) {
		$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
		$type      = isset( $value['type'] ) ? $value['type'] : false;
		switch ( $type ) {
			case 'meta':
				$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$this->ordersMetaTable} AS meta_{$key} ON ( posts.{$this->ordersIdColumn} = meta_{$key}.{$this->ordersMetaOrderIdColumn} AND meta_{$key}.meta_key = %s )";
				$joinParams["meta_{$key}"] = [$raw_key];
				return;
			case 'parent_meta':
				$joins[ "parent_meta_{$key}" ] = "{$join_type} JOIN {$this->ordersMetaTable} AS parent_meta_{$key} ON (posts.{$this->ordersParentIdColumn} = parent_meta_{$key}.{$this->ordersMetaOrderIdColumn}) AND (parent_meta_{$key}.meta_key = %s)";
				$joinParams["parent_meta_{$key}"] = [$raw_key];
				return;
		}
	}
	
	
	function getWhereMetaField($key, $value) {
		return "meta_{$key}.meta_value";
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
			
			$key = sanitize_key( $raw_key );
			
			$distinct = '';

			if ( isset( $value['distinct'] ) ) {
				$distinct = 'DISTINCT';
			}
			
			$get_key = $this->getSelectForField($key, $value['type']);

			if ( empty( $get_key ) ) {
				continue;
			}
			
			
			if ( $value['function'] ?? '' ) {
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
			$this->addJoinForField($raw_key, sanitize_key( $raw_key ), $value, $joins, $joinParams);
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				
				$value['type'] = isset($value['type']) && in_array($value['type'], ['order_item', 'order_item_meta']) ? $value['type'] : 'meta';
				unset($value['order_item_type']);
				
				$this->addJoinForField(
					((array)$value['meta_key'])[0],
					sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] ),
					$value,
					$joins,
					$joinParams
				);
			}
		}

		if ( ! empty( $parent_order_status ) ) {
			$joins['parent'] = "LEFT JOIN {$this->ordersTable} AS parent ON posts.{$this->ordersParentIdColumn} = parent.{$this->ordersIdColumn}";
		}
		
		$query['join'] = '';
		$queryParams['join'] = [];
		
		$query['where'] = [];
		$queryParams['where'] = [];
		
		if ($order_types) {
			$queryParams['where'] = array_merge($queryParams['where'], $order_types);
			$query['where'][] = "
				posts.{$this->ordersTypeColumn} 	IN ( " . substr(str_repeat('%s,', count($order_types)), 0, -1) . " )
				";
		}
		
		if ( ! empty( $order_status ) ) {
			$queryParams['where'] = array_merge($queryParams['where'], $order_status);
			$query['where'][] = "
				 	posts.{$this->ordersStatusColumn} 	IN (" . substr(str_repeat('%s,', count($order_status)), 0, -1) . ")
			";
		}

		if ( ! empty( $parent_order_status ) ) {
			$queryParams['where'] = array_merge($queryParams['where'], $parent_order_status);
			if ( ! empty( $order_status ) ) {
				$query['where'][] = " ( parent.{$this->ordersStatusColumn} IN (" . substr(str_repeat('%s,', count($parent_order_status)), 0, -1) . ") OR parent.id IS NULL ) ";
			} else {
				$query['where'][] = " parent.{$this->ordersStatusColumn} IN (" . substr(str_repeat('%s,', count($parent_order_status)), 0, -1) . ") ";
			}
		}

		if ( $filter_range ) {
			$query['where'][] = "
					posts.{$this->ordersDateColumn} >= %s
				AND 	posts.{$this->ordersDateColumn} < %s
			";
			$queryParams['where'][] = get_gmt_from_date( gmdate('Y-m-d H:i:s', $this->start_date) );
			$queryParams['where'][] = get_gmt_from_date( gmdate('Y-m-d H:i:s', strtotime( '+1 DAY', $this->end_date )) );
			
		}

		if ( ! empty( $where_meta ) ) {

			$relation = isset( $where_meta['relation'] ) ? preg_replace('/\\s/', '', $where_meta['relation']) : 'AND';

			$metaWhere = ' (';

			foreach ( $where_meta as $index => $value ) {

				if ( ! is_array( $value ) ) {
					continue;
				}

				if ( $index > 0 ) {
					$metaWhere .= ' ' . $relation . ' ';
				}
				
				$key = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );
				
				if ($value['type'] == 'order_item' && !$this->supports(PlatformFeatures::CHILD_ITEMS)) {
					if ($this->supports(PlatformFeatures::CHILD_ITEMS_FILTER)) {
						$metaWhere .= ' EXISTS (SELECT 1 FROM '.str_ireplace(' ON ', ' WHERE (', substr(stristr($joins['order_items'], 'join '), 5)).') AND '.$key.' '; // $key is sanitized above
						unset($joins['order_items']);
						$isChildItemFilter = true;
					} else {
						throw new \Exception('Unsupported "where" value.');
					}
				} else {
					$metaWhere .= $this->getWhereMetaField($key, $value).' ';
				}
				
				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {
					$queryParams['where']  = array_merge($queryParams['where'], (array) $value['meta_value']);
					$metaWhere .= "{$value['operator']} (". substr(str_repeat('%s,', count((array) $value['meta_value'])), 0, -1) .")";
				} else {
					$queryParams['where'][] = $value['meta_value'];
					$metaWhere .= preg_replace('/\\s/', '', $value['operator']).' %s';
				}
				
				if ($isChildItemFilter ?? false) {
					$metaWhere .= ')';
					unset($isChildItemFilter);
				}
			}

			$query['where'][] = $metaWhere.')';
		}

		// woocommerce\includes\admin\reports\class-wc-admin-report.php
		if ( ! empty( $where ) ) {
			foreach ( $where as $value ) {
				if (!in_array($value['key'], [$this->ordersDateColumn], true)) {
					throw new \Exception('Unsupported "where" value.');
				}
				
				$postsWhere = ' posts.'.sanitize_key($value['key']).' ';
				
				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {
					$queryParams['where']  = array_merge( (array) $value['value'], $queryParams['where']);
					$postsWhere .= "{$value['operator']} (". substr(str_repeat('%s,', count((array) $value['value'])), 0, -1) .")";
				} else {
					$queryParams['where'][] = $value['value'];
					$postsWhere .= preg_replace('/\\s/', '', $value['operator']).' %s';
				}
				$query['where'][] = $postsWhere;
				
			}
		}
		
		$query['where'] = ($query['where'] ? ' WHERE '.implode(' AND ', $query['where']) : '');

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

		foreach ($joins as $joinId => $joinSql) {
			$query['join'] .= ' '.$joinSql;
			$queryParams['join'] = array_merge($queryParams['join'], $joinParams[$joinId] ?? []);
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
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared above
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
	
		
	function filterReportQuery($sql)
	{
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
		// Add on any extra SQL
		global $hm_wc_report_extra_sql, $wpdb;
		if (!empty($hm_wc_report_extra_sql)) {
			foreach ($hm_wc_report_extra_sql as $key => $extraSql) {
				if (isset($sql[$key])) {
					$sql[$key] .= ' '.$extraSql;
				}
			}
		}
		
		$standardFields = $this->getStandardFields();
		$hasSeparateVariations = !empty($_POST['variations']) && $this->supports(PlatformFeatures::VARIATIONS);
		
		$sql['select'] = preg_replace('/PSRSUM\\((.+)\\)/iU', 'SUM(ROUND($1, 2))', $sql['select']);
		
		if ($hasSeparateVariations) {
			$variationIdField = \NinjalyticsFree\ninjalytics_get_query_field($standardFields['variation_id'][0], $standardFields['variation_id'][1]);
			
			$sql['select'] = str_ireplace(
				$variationIdField.' as variation_id',
				'IF('.$variationIdField.', '.$variationIdField.', 0) as variation_id',
				$sql['select']
			);
		}
		
		
		if (empty($_POST['export_orders']) && $this->supports(PlatformFeatures::CHILD_ITEMS)) {
			$productIdField = \NinjalyticsFree\ninjalytics_get_query_field($standardFields['product_id'][0], $standardFields['product_id'][1]);
			$hasProductIdField = strpos($sql['select'], $productIdField) !== false;
			
			if ($hasProductIdField) { // make sure we are not in a shipping report query
				global $wpdb;
				
				if ($hasProductIdField) { // make sure we are not in a shipping report query
					global $wpdb;
					
					switch ($_POST['disable_product_grouping'] ?? 0) {
						case -1:
							$sql['select'] .= ', IFNULL(pmeta_sku.meta_value, "") AS product_sku';
							$sql['join'] .= ' 	LEFT JOIN '.$wpdb->postmeta.' pmeta_sku ON pmeta_sku.post_id='.(
								$hasSeparateVariations
									? 'IF(IFNULL('.$variationIdField.', 0) = 0, '.$productIdField.', '.$variationIdField.')'
									: $productIdField
							).' AND pmeta_sku.meta_key="_sku"';
							break;
						case 2:
							$sql['select'] .= ', pcat_t.name AS product_category';
							$sql['join'] .= ' 	JOIN '.$wpdb->term_relationships.' pcat_tr ON pcat_tr.object_id='.$productIdField.'
												JOIN '.$wpdb->term_taxonomy.' pcat_tt ON (pcat_tt.term_taxonomy_id=pcat_tr.term_taxonomy_id AND pcat_tt.taxonomy="product_cat")
												JOIN '.$wpdb->terms.' pcat_t ON pcat_t.term_id=pcat_tt.term_id';
							break;
					}
					
					
				}
			}
		}
		
		if (!empty($_POST['enable_custom_segments'])) {
			$groupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
			if ($groupByField && $groupByField[0] == 'p') {
				$sql['select'] .= ', group_pm'.$i.'.meta_value AS groupby_field';
				$sql['join'] .= ' 	LEFT JOIN '.$wpdb->postmeta.' group_pm ON group_pm.post_id = '.(
					$standardFields['product_id'][0] == 'order_item'
						? 'order_items.'.$standardFields['product_id'][1]
						: '(SELECT meta_value FROM '.$reporter->orderItemsMetaTable.' oimeta_pid WHERE oimeta_pid.'.$reporter->orderItemsMetaItemIdColumn.' = order_items.'.$reporter->orderItemsIdColumn.' AND meta_key="'.$standardFields['product_id'][1].'")'
				)
				.' AND group_pm'.$i.'.meta_key="'.esc_sql(substr($groupByField, 2)).'"';
			}
		}

		return $sql;
		
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
	
}
