<?php
namespace NinjalyticsFree;

if ( ! defined( 'ABSPATH' ) ) exit;

function ninjalytics_process_psr_settings($psrSettings) {
	if (isset($psrSettings[0])) {
		$psrSettings[0]['preset_name'] = 'Last used settings from Product Sales Report';
		if (in_array($psrSettings[0]['orderby'] ?? '', ['product_id', 'quantity', 'gross', 'gross_after_discount'])) {
			$psrSettings[0]['orderby'] = 'builtin::'.$psrSettings[0]['orderby'];
		}
	}
	return array_map('NinjalyticsFree\\ninjalytics_import_report_dates', $psrSettings);
}

function ninjalytics_process_xoi_settings($xoiSources) {
	$settings = [];
	
	$xoiFieldKeys = array('fields', 'total_fields', 'field_names', 'orderby');

	 $xoiFields = array(
		'product_id',
		'product_sku',
		'product_name',
		'product_desc',
		'variation_id',
		'variation_sku',
		'variation_attributes',
		'item_sku',
		'product_categories',
		'order_id',
		'order_status',
		'order_total',
		'order_date',
		'order_date_only',
		'order_parent',
		'order_item_type',
		'order_item_name',
		'quantity',
		'line_subtotal',
		'line_total',
		'line_tax',
		'line_total_with_tax',
		'billing_name',
		'billing_phone',
		'billing_email',
		'billing_address',
		'billing_state',
		'shipping_name',
		'shipping_phone',
		'shipping_email',
		'shipping_address',
		'shipping_state',
		'order_shipping_methods',
		'order_total_qty',
		'order_item_id',
		'order_product_total'
	);
	
	// EOIP Pro custom-meta field IDs use `__<type>__<key>`; map onto Ninjalytics conventions.
	$xoiCustomFieldPrefixMap = array(
		'__shop_order__'        => 'order_meta::',
		'__order_item__'        => 'order_item_meta::',
		'__customer_user__'     => 'customer_user_meta::',
		'__product__'           => '', // Ninjalytics uses raw meta keys for product meta
		'__product_variation__' => 'variation::',
	);
	
	$mapXoiField = function($fieldId) use ($xoiFields, $xoiCustomFieldPrefixMap) {
		if (in_array($fieldId, $xoiFields, true)) {
			return 'builtin::'.$fieldId;
		}
		foreach ($xoiCustomFieldPrefixMap as $eoipPrefix => $ninjalyticsPrefix) {
			if (strpos($fieldId, $eoipPrefix) === 0) {
				return $ninjalyticsPrefix.substr($fieldId, strlen($eoipPrefix));
			}
		}
		return $fieldId;
	};
	
	foreach ($xoiSources as $sourceName => $sourcePresets) {
		if (!is_array($sourcePresets) || empty($sourcePresets)) {
			continue;
		}
		foreach ($sourcePresets as $i => $preset) {
			if (!$i) {
				$preset['preset_name'] = 'Last used settings from '.$sourceName;
			}
			$preset['export_orders'] = 1;

			foreach ($xoiFieldKeys as $key) {
				if (isset($preset[$key])) {
					if (is_array($preset[$key])) {
						$newArr = [];
						foreach ($preset[$key] as $k => $v) {
							// `fields` / `total_fields` are indexed arrays (field IDs as values);
							// `field_names` is an associative array (field IDs as keys, labels as values).
							if (is_string($k)) {
								$k = $mapXoiField($k);
							}
							if (is_string($v) && is_int($k)) {
								// Only map values for indexed arrays (fields/total_fields), not for field_names labels
								$v = $mapXoiField($v);
							}
							$newArr[$k] = $v;
						}
						$preset[$key] = $newArr;
					} else if (is_string($preset[$key]) && $preset[$key] !== '') {
						$preset[$key] = $mapXoiField($preset[$key]);
					}
				}
			}
			
			$settings[] = ninjalytics_import_report_dates($preset);
		}
	}
	
	return $settings;
}

function ninjalytics_import_report_dates($preset) {
	// Map PSRP/EOIP `report_time` (single string) onto Ninjalytics's date range schema
	// (`report_time_mode` + `report_time_preset` / `report_time_absolute_*`).
	if (isset($preset['report_time'])) {
		$xoiReportTimePresetMap = array(
			'0d'  => 'today',
			'1d'  => 'yesterday',
			'7d'  => 'last7',
			'30d' => 'last30',
			'0cm' => 'month',
			'1cm' => 'lastmonth',
			'all' => 'forever',
		);
		if (isset($xoiReportTimePresetMap[$preset['report_time']])) {
			$preset['report_time_mode']   = 'preset';
			$preset['report_time_preset'] = $xoiReportTimePresetMap[$preset['report_time']];
		} else if (in_array($preset['report_time'], ['+7d', '+30d', '+1cm'])) {
			$preset['report_time_mode'] = 'basic';
			
			if ($preset['report_time'] == '+1cm') {
				$preset['report_time_basic_from'] = 1;
				$preset['report_time_basic_from_unit'] = 'cm';
				$preset['report_time_basic_from_round'] = 'm';
				$preset['report_time_basic_to'] = 1;
				$preset['report_time_basic_to_unit'] = 'cm';
				$preset['report_time_basic_to_round'] = 'm';
			} else {
				$preset['report_time_basic_from'] = 1;
				$preset['report_time_basic_from_unit'] = 'd';
				$preset['report_time_basic_from_round'] = 'd';
				$preset['report_time_basic_to'] = $preset['report_time'] == '+7d' ? 7 : 30;
				$preset['report_time_basic_to_unit'] = 'd';
				$preset['report_time_basic_to_round'] = 'd';
			}
			
		} else if ($preset['report_time'] === 'custom') {
			$preset['report_time_mode'] = 'absolute';
			if (!empty($preset['report_start'])) {
				$preset['report_time_absolute_from_date'] = $preset['report_start'];
			}
			if (!empty($preset['report_end'])) {
				$preset['report_time_absolute_to_date'] = $preset['report_end'];
			}
			// EOIP Pro stores times like "12:00:00 AM" / "01:30:00 PM"; Ninjalytics's
			// <input type="time" step="1"> expects 24-hour HH:MM:SS.
			foreach (array(
				'report_start_time' => 'report_time_absolute_from_time',
				'report_end_time'   => 'report_time_absolute_to_time',
			) as $xoiKey => $ninjalyticsKey) {
				if (!empty($preset[$xoiKey])) {
					$timestamp = strtotime($preset[$xoiKey]);
					if ($timestamp !== false) {
						$preset[$ninjalyticsKey] = date('H:i:s', $timestamp);
					}
				}
			}
		}
	}
	
	return $preset;
}
