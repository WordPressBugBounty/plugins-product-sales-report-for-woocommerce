<?php
/**
 * Plugin Name:          Ninjalytics Free (formerly Product Sales Report)
 * Description:          Generates a report on individual WooCommerce products sold during a specified time period.
 * Plugin URI:           https://berrypress.com/product/woocommerce/ninjalytics/
 * Version:              2.0.1
 * WC tested up to:      10.2
 * WC requires at least: 2.2
 * Author:               BerryPress
 * Author URI:           https://wpzone.co/?utm_source=product-sales-report-pro&utm_medium=link&utm_campaign=wp-plugin-author-uri
 * License:              GNU General Public License version 3 or later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.en.html
 * GitHub Plugin URI:    https://github.com/BerryPress/product-sales-report-for-woocommerce
 */

/*
    Ninjalytics
    Copyright (C) 2025 BerryPress

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

/* CREDITS:
 * This plugin contains code copied from and/or based on the following third-party products,
 * in addition to any others indicated in code comments or license files:
 *
 * WordPress, by Automattic, GPLv2+
 * WooCommerce, by Automattic, GPLv3+
 * Easy Digital Downloads, Copyright (c) Sandhills Development, LLC, GPLv2+
 *
*/

use Ninjalytics\Reporters\PlatformFeatures;

define('NINJALYTICS_VERSION', '2.0.1');

add_filter('default_option_ninjalytics_settings', 'ninjalytics_psr_import');
function ninjalytics_psr_import($default) {
	$default = get_option('hm_psr_report_settings', $default);
	if (isset($default[0])) {
		$default[0]['preset_name'] = 'Last used settings from Product Sales Report';
	} else {
		$default = [];
	}
	array_unshift($default, []);
	return $default;
}

add_action('admin_menu', 'ninjalytics_admin_menu');
function ninjalytics_admin_menu()
{
	add_menu_page('Ninjalytics', 'Ninjalytics', 'view_woocommerce_reports', 'ninjalytics', 'ninjalytics_page',
		'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iTGF5ZXJfMiIgZGF0YS1uYW1lPSJMYXllciAyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAzNC40IDI1Ij4KICA8ZyBpZD0iV2Fyc3R3YV8xIiBkYXRhLW5hbWU9IldhcnN0d2EgMSI+CiAgICA8Zz4KICAgICAgPHBhdGggZD0iTTIwLjM3LDI0LjYyYy0yLjM2LDAtNC4yNy0xLjkyLTQuMjctNC4yN3MxLjkyLTQuMjcsNC4yNy00LjI3LDQuMjcsMS45Miw0LjI3LDQuMjctMS45Miw0LjI3LTQuMjcsNC4yN1pNMjAuMzcsMTguMDdjLTEuMjUsMC0yLjI3LDEuMDItMi4yNywyLjI3czEuMDIsMi4yNywyLjI3LDIuMjcsMi4yNy0xLjAyLDIuMjctMi4yNy0xLjAyLTIuMjctMi4yNy0yLjI3WiIgc3R5bGU9ImZpbGw6ICNhN2FhYWQ7IHN0cm9rZTogI2E3YWFhZDsgc3Ryb2tlLW1pdGVybGltaXQ6IDEwOyBzdHJva2Utd2lkdGg6IC43NXB4OyIvPgogICAgICA8cGF0aCBkPSJNNC42NSwyNC42MmMtMi4zNiwwLTQuMjctMS45Mi00LjI3LTQuMjdzMS45Mi00LjI3LDQuMjctNC4yNyw0LjI3LDEuOTIsNC4yNyw0LjI3LTEuOTIsNC4yNy00LjI3LDQuMjdaTTQuNjUsMTguMDdjLTEuMjUsMC0yLjI3LDEuMDItMi4yNywyLjI3czEuMDIsMi4yNywyLjI3LDIuMjcsMi4yNy0xLjAyLDIuMjctMi4yNy0xLjAyLTIuMjctMi4yNy0yLjI3WiIgc3R5bGU9ImZpbGw6ICNhN2FhYWQ7IHN0cm9rZTogI2E3YWFhZDsgc3Ryb2tlLW1pdGVybGltaXQ6IDEwOyBzdHJva2Utd2lkdGg6IC43NXB4OyIvPgogICAgICA8cGF0aCBkPSJNMTEuNDUsMTQuMTFjLTIuMzYsMC00LjI3LTEuOTItNC4yNy00LjI3czEuOTItNC4yNyw0LjI3LTQuMjcsNC4yNywxLjkyLDQuMjcsNC4yNy0xLjkyLDQuMjctNC4yNyw0LjI3Wk0xMS40NSw3LjU2Yy0xLjI1LDAtMi4yNywxLjAyLTIuMjcsMi4yN3MxLjAyLDIuMjcsMi4yNywyLjI3LDIuMjctMS4wMiwyLjI3LTIuMjctMS4wMi0yLjI3LTIuMjctMi4yN1oiIHN0eWxlPSJmaWxsOiAjYTdhYWFkOyBzdHJva2U6ICNhN2FhYWQ7IHN0cm9rZS1taXRlcmxpbWl0OiAxMDsgc3Ryb2tlLXdpZHRoOiAuNzVweDsiLz4KICAgICAgPHBhdGggZD0iTTI4LjA2LDEyLjNjLTMuMjksMC01Ljk2LTIuNjctNS45Ni01Ljk2UzI0Ljc4LjM4LDI4LjA2LjM4czUuOTYsMi42Nyw1Ljk2LDUuOTYtMi42Nyw1Ljk2LTUuOTYsNS45NlpNMjguMDYsMi4zOGMtMi4xOCwwLTMuOTYsMS43OC0zLjk2LDMuOTZzMS43OCwzLjk2LDMuOTYsMy45NiwzLjk2LTEuNzgsMy45Ni0zLjk2LTEuNzgtMy45Ni0zLjk2LTMuOTZaIiBzdHlsZT0iZmlsbDogI2E3YWFhZDsgc3Ryb2tlOiAjYTdhYWFkOyBzdHJva2UtbWl0ZXJsaW1pdDogMTA7IHN0cm9rZS13aWR0aDogLjc1cHg7Ii8+CiAgICAgIDxwYXRoIGQ9Ik0yMS45NSwxOC4wN2MtLjE5LDAtLjM5LS4wNi0uNTYtLjE3LS40Ni0uMzEtLjU4LS45My0uMjctMS4zOWw0LjE4LTYuMTdjLjMxLS40Ni45My0uNTgsMS4zOS0uMjcuNDYuMzEuNTguOTMuMjcsMS4zOWwtNC4xOCw2LjE3Yy0uMTkuMjktLjUxLjQ0LS44My40NFoiIHN0eWxlPSJmaWxsOiAjYTdhYWFkOyBzdHJva2U6ICNhN2FhYWQ7IHN0cm9rZS1taXRlcmxpbWl0OiAxMDsgc3Ryb2tlLXdpZHRoOiAuNzVweDsiLz4KICAgICAgPHBhdGggZD0iTTUuODksMTguMzJjLS4xOSwwLS4zOS0uMDYtLjU2LS4xNy0uNDYtLjMxLS41OC0uOTMtLjI3LTEuMzlsMy4zMi00LjkxYy4zMS0uNDYuOTMtLjU4LDEuMzktLjI3LjQ2LjMxLjU4LjkzLjI3LDEuMzlsLTMuMzIsNC45MWMtLjE5LjI5LS41MS40NC0uODMuNDRaIiBzdHlsZT0iZmlsbDogI2E3YWFhZDsgc3Ryb2tlOiAjYTdhYWFkOyBzdHJva2UtbWl0ZXJsaW1pdDogMTA7IHN0cm9rZS13aWR0aDogLjc1cHg7Ii8+CiAgICAgIDxwYXRoIGQ9Ik0xNy44NCwxOC40N2MtLjI3LDAtLjUzLS4xMS0uNzMtLjMxbC00LjM3LTQuNjRjLS4zOC0uNC0uMzYtMS4wNC4wNC0xLjQxLjQtLjM4LDEuMDQtLjM2LDEuNDEuMDRsNC4zNyw0LjY0Yy4zOC40LjM2LDEuMDQtLjA0LDEuNDEtLjE5LjE4LS40NC4yNy0uNjkuMjdaIiBzdHlsZT0iZmlsbDogI2E3YWFhZDsgc3Ryb2tlOiAjYTdhYWFkOyBzdHJva2UtbWl0ZXJsaW1pdDogMTA7IHN0cm9rZS13aWR0aDogLjc1cHg7Ii8+CiAgICA8L2c+CiAgPC9nPgo8L3N2Zz4='
	);
	
    add_submenu_page('woocommerce', 'Product Sales Report', 'Product Sales Report', 'view_woocommerce_reports', 'ninjalytics', 'ninjalytics_page');
}
// Add Settings link on Plugins screen (single site)
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'ninjalytics_free_add_plugin_action_link');

function ninjalytics_free_add_plugin_action_link($links) {
	$settingsUrl = admin_url('admin.php?page=ninjalytics');
	$links[] = '<a href="'.esc_url($settingsUrl).'">'.esc_html__('Settings', 'product-sales-report-for-woocommerce').'</a>';
	return $links;
}
function ninjalytics_default_report_settings()
{
	$reporter = ninjalytics_get_active_reporter();
	return array(
		'display_mode' => 'table',
		'report_time' => '30d',
		'report_start' => '',
		'report_end' => '',
		'order_statuses' => $reporter->defaultOrderStatuses,
		'products' => 'all',
		'product_cats' => array(),
		'product_ids' => '',
		'variations' => 1,
		'groupby' => '',
		'enable_custom_segments' => -1,
		'orderby' => 'quantity',
		'orderdir' => 'desc',
		'fields' => $reporter->supports(PlatformFeatures::VARIATIONS) ? array('builtin::product_id', 'builtin::product_sku', 'builtin::variation_sku', 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_sales') : array('builtin::product_id', 'builtin::product_sku', 'builtin::product_name', 'builtin::quantity_sold', 'builtin::gross_sales'),
		'total_fields' => array('builtin::quantity_sold', 'builtin::gross_sales', 'builtin::gross_after_discount', 'builtin::taxes', 'builtin::total_with_tax'),
		'field_names' => array(),
		'chart_fields' => [],
		'chart_series_name' => 'builtin::product_name',
		'limit_on' => 0,
		'limit' => 10,
		'include_nil' => 0,
		'include_unpublished' => 1,
		'include_shipping' => 0,
		'include_header' => 1,
		'include_totals' => 0,
		'format_amounts' => 1,
		'exclude_free' => 0,
		'refunds' => 1,
		'adjustments' => 1,
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
	
	'report_time_mode' => 'basic',
	'report_time_basic_from' => '',
	'report_time_basic_from_unit' => 'max',
	'report_time_basic_from_round' => '',
	'report_time_basic_to' => '',
	'report_time_basic_to_unit' => 'max',
	'report_time_basic_to_round' => '',
	'report_time_absolute_from_date' => '',
	'report_time_absolute_from_time' => '',
	'report_time_absolute_to_date' => '',
	'report_time_absolute_to_time' => '',
	);
}

function ninjalytics_on_before_woocommerce_init()
{
	class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil') && Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__);
}
add_action('before_woocommerce_init', 'ninjalytics_on_before_woocommerce_init');

function ninjalytics_page()
{

	include_once(dirname(__FILE__).'/includes/berrypress-admin-framework/Page.php');
	include_once(dirname(__FILE__).'/admin/admin.php');
	
	(new Ninjalytics\AdminPage())->render();
}


function ninjalytics_get_default_fields()
{
	global $ninjalytics_default_fields;
	
	$reporter = ninjalytics_get_active_reporter();
	
	if (!isset($ninjalytics_default_fields)) {
		$ninjalytics_default_fields = [
			'builtin::product_id' => 'Product ID',
			'builtin::product_sku' => 'Product SKU'
		]
		+ ($reporter->supports(PlatformFeatures::VARIATIONS) ? [
			'builtin::variation_id' => 'Variation ID',
			'builtin::variation_sku' => 'Variation SKU',
			'builtin::variation_attributes' => 'Variation Attributes',
		] : [])
		+ [
			'builtin::product_name' => 'Product Name',
			'builtin::product_categories' => 'Product Categories',
			'builtin::product_price' => 'Current Product Price [Pro]',
			'builtin::product_price_with_tax' => 'Current Product Price (Incl. Tax) [Pro]',
			'builtin::product_stock' => 'Current Stock Quantity',
			'builtin::quantity_sold' => 'Quantity Sold',
			'builtin::gross_sales' => 'Gross Sales',
			'builtin::gross_after_discount' => 'Gross Sales (After Discounts)',
			'builtin::discount' => 'Total Discount Amount',
			'builtin::taxes' => 'Taxes'
		];
		
		foreach (ninjalytics_get_tax_types() as $taxTypeId => $taxType) {
			$ninjalytics_default_fields['builtin::taxes_'.$taxTypeId] = 'Taxes - '.$taxType;
		}
		
		$ninjalytics_default_fields = array_merge(
			$ninjalytics_default_fields,
			[
				'builtin::total_with_tax' => 'Total Sales Including Tax',]
			+ ($reporter->supports(PlatformFeatures::SHIPPING) ? [
				'builtin::order_shipping_methods' => 'Order Shipping Methods [Pro]'
			] : [])
			+ [
				'builtin::refund_quantity' => 'Quantity Refunded [Pro]',
				'builtin::refund_gross' => 'Gross Amount Refunded (Excl. Tax) [Pro]',
				'builtin::refund_with_tax' => 'Gross Amount Refunded (Incl. Tax) [Pro]',
				'builtin::refund_taxes' => 'Tax Refunded [Pro]',
				'builtin::publish_time' => 'Product Publish Date/Time',
				'builtin::line_item_count' => 'Line Item Count',
				'builtin::product_desc' => 'Product Description',
				'builtin::product_excerpt' => 'Product Description Excerpt',
				'builtin::product_menu_order' => 'Product Menu Order',
				'builtin::avg_order_total' => 'Average Order Total',
			]
		);
	}
	
	return $ninjalytics_default_fields;
}

function ninjalytics_get_tax_types() {
	global $wpdb, $ninjalytics_tax_types;
	if (!isset($ninjalytics_tax_types)) {
		$taxTypes = [];
		$taxTypesExclude = [];
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_col('SELECT DISTINCT tax_rate_name FROM '.$wpdb->prefix.'woocommerce_tax_rates');
		
		foreach ($result as $taxType) {
			$taxTypeKey = sanitize_key($taxType);
			if (isset($taxTypes[$taxTypeKey])) {
				// Don't allow two tax types with different names resolving to the same key
				$taxTypesExclude[$taxTypeKey] = true;
			} else {
				$taxTypes[$taxTypeKey] = $taxType;
			}
		}
		
		$ninjalytics_tax_types = array_diff_key($taxTypes, $taxTypesExclude);
	}
	
	return $ninjalytics_tax_types;
}


function ninjalytics_filter_nocache_headers($headers) {
	// Reference: https://owasp.org/www-community/OWASP_Application_Security_FAQ
	
	$cacheControl = array_map( 'trim', explode(',', $headers['Cache-Control']) );
	$cacheControl = array_unique( array_merge( [
		'no-cache',
		'no-store',
		'must-revalidate',
		'pre-check=0',
		'post-check=0',
		'max-age=0',
		's-maxage=0'
	], $cacheControl ) );
	
	$headers['Cache-Control'] = implode(', ', $cacheControl);
	$headers['Pragma'] = 'no-cache';
	
	return $headers;
}

// Hook into WordPress init; this function performs report generation when
// the admin form is submitted
add_action('init', 'ninjalytics_maybe_run_report', 9999);
function ninjalytics_maybe_run_report()
{
	global $pagenow, $ninjalytics_email_result;
	
	// Check if we are in admin and on the report page
	if (!is_admin())
		return;
	if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'ninjalytics') {
		
		add_filter('nocache_headers', 'ninjalytics_filter_nocache_headers', 9999);
		nocache_headers();
		
		if ( current_user_can('view_woocommerce_reports') && sanitize_text_field(wp_unslash($_REQUEST['ninjalytics_action'] ?? '')) == 'run' ) {
			
			if ( empty($_REQUEST['hm-psr-nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['hm-psr-nonce'])), 'hm-psr-run') ) {
				wp_die('The current request is invalid. Please go back and try again.');
			}
			
			$isChart = !empty($_REQUEST['_chart']);
			$isTimeChart = $isChart && in_array( sanitize_text_field(wp_unslash($_POST['chart_type'] ?? '')), ['line_series', 'line_totals'] );
			
			$savedReportSettings = get_option('ninjalytics_settings', array());
			
			if (empty($_POST) && isset($_GET['preset'])) {
				if ((sanitize_text_field(wp_unslash($_GET['preset'])))[0] == '_') {
					$tempateId = substr(sanitize_text_field(wp_unslash($_GET['preset'] ?? '')), 1);
					$_POST = array_merge(
						ninjalytics_default_report_settings(),
						 (ninjalytics_get_active_reporter()->getReportTemplates())[$tempateId] ?? [],
						isset((ninjalytics_get_active_reporter()->getReportTemplates())[$tempateId])
							? json_decode(get_option('ninjalytics_report_dates_'.$tempateId, '{}'), true) : []
					);
				} else if (((int) $_GET['preset']) && isset($savedReportSettings[(int) $_GET['preset']])) {
					$_POST = array_merge(
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
						$savedReportSettings[(int) ($_GET['preset'] ?? '')],
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
						((int) ($_POST['preset'] ?? '')) ? json_decode(get_option('ninjalytics_report_dates_'.((int) ($_POST['preset'] ?? '')), '{}'), true) : []
					);
				}
				
			} else {
				// Run report from $_POST
				$_POST = stripslashes_deep($_POST);
				
				if ((int) $_POST['preset']) {
					update_option(
						'ninjalytics_report_dates_'.((int) $_POST['preset']),
						wp_json_encode(array_intersect_key(
							$_POST,
							[
								'display_mode' => true,
								'report_time_mode' => true,
								'report_time_basic_from' => true,
								'report_time_basic_from_unit' => true,
								'report_time_basic_from_round' => true,
								'report_time_basic_to' => true,
								'report_time_basic_to_unit' => true,
								'report_time_basic_to_round' => true,
								'report_time_absolute_from_date' => true,
								'report_time_absolute_from_time' => true,
								'report_time_absolute_to_date' => true,
								'report_time_absolute_to_time' => true
							]
						)),
						false
					);
				}
			}
			
			if (!empty($_POST['hm_psr_debug'])) {
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Intentionally enabled for debug mode
				error_reporting(E_ALL);
// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Intentionally enabled for debug mode
				ini_set('display_errors', 1);
			}
			
			// Map new (1.6.8) product category checklist onto old field name
			if (isset($_POST['tax_input']['product_cat'])) {
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Mapping from one POST var to another, to be sanitized before use
				$_POST['product_cats'] = $_POST['tax_input']['product_cat'];
				unset($_POST['tax_input']);
			}
			
			$newSettings = array_intersect_key($_POST, ninjalytics_default_report_settings());
			
			// Also update checkbox fields in preset-save
			foreach (array(
				'limit_on', 'include_nil', 'include_shipping', 'include_unpublished', 'include_header', 'include_totals',
				'format_amounts', 'exclude_free',
				'refunds', 'adjustments', 'report_title_on', 'hm_psr_debug', 'disable_product_grouping', 'intermediate_rounding'
				) as $checkboxField) {
				
				if (!isset($newSettings[$checkboxField])) {
					$newSettings[$checkboxField] = 0;
				}
			}
			
			
			// Check if no fields are selected
			if (empty($_POST['fields']))
				return;
			
			
			if (isset($_POST['format']) && ($_POST['format'] == 'json' || $_POST['format'] == 'json-totals')) {
				list($start_date, $end_date, $dates_desc) = ninjalytics_get_report_dates(true);
				
				if (!defined('PSR_CHART_SUBSEQUENT_RUN')) {
					$format = get_option('date_format').' '.(stripos(get_option('time_format'), 'A') === false ? 'H:i:s' : 'g:i:s A');
					$meta = ['startDate' => gmdate($format, $start_date), 'endDate' => gmdate($format, $end_date), 'datesDesc' => $dates_desc];
					if (!empty($_POST['report_title_on']) && ($chartRunStart ?? 1) == 1) {
						$meta['title'] = ninjalytics_dynamic_title(sanitize_text_field(wp_unslash($_POST['report_title'] ?? '')), $titleVars);
					}
					header('X-Psr-Meta: '.wp_json_encode($meta));
				}
				
			} else {
				list($start_date, $end_date) = ninjalytics_get_report_dates();
			}
			
			$titleVars = array(
				'now' => time(),
				'preset' => (empty($_POST['preset_name']) ? 'Product Sales' : sanitize_text_field(wp_unslash($_POST['preset_name']))),
				'start' => $start_date,
				'end' => $end_date
			);
			
			if ($isChart) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
				$chartRunStart = (int) ($_SERVER['HTTP_X_PSR_CHART_RUN_START'] ?? 1);
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
				$chartRunCount = (int) ($_SERVER['HTTP_X_PSR_CHART_RUN_COUNT'] ?? 1);
				
				$hasChartStarted = defined('Ninjalytics_PSR_CHART_STARTED');
				
				if ($isTimeChart) {
					$totalInterval = $end_date - $start_date;
					if ($totalInterval <= 3 * 86400) {
						$interval = 'hour';
					} else if ($totalInterval <= 93 * 86400) {
						$interval = 'day';
					} else if ($totalInterval <= 366 * 3 * 86400) {
						$interval = 'month';
					} else {
						$interval = 'year';
					}
					
					
					if ($chartRunStart > 1) {
						$start_date = strtotime('+'.($chartRunStart - 1).' '.$interval, $start_date);
					}
					
					if (!$hasChartStarted) {
						$totalIntervalCount = 0;
						$nextIntervalStart = $start_date;
						$intervalLabels = [];
						$utc = new DateTimeZone('UTC');
						while ($nextIntervalStart < $end_date) {
							if ($totalIntervalCount < $chartRunCount) {
								switch ($interval) {
									case 'year':
										$intervalLabels[] = wp_date('Y', $nextIntervalStart, $utc);
										break;
									case 'month':
										$intervalLabels[] = wp_date('Y-m', $nextIntervalStart, $utc);
										break;
									case 'day':
										$intervalLabels[] = wp_date('Y-m-d', $nextIntervalStart, $utc);
										break;
									case 'hour':
										$intervalLabels[] = wp_date('H:00', $nextIntervalStart, $utc);
										break;
								}
							}
							$nextIntervalStart = strtotime('+1 '.$interval, $nextIntervalStart);
							++$totalIntervalCount;
						} 
						
						header('X-Psr-Chart-Run-Remaining: '.max(0, $totalIntervalCount - $chartRunCount));
						header('X-Psr-Chart-Labels: '.implode('|', $intervalLabels));
					}
					
					$end_date = strtotime('+1 '.$interval, $start_date) - 1;
				} else if ($chartRunStart != 1 || $chartRunCount != 1) {
					throw new Exception();
				}
				
				if (!$hasChartStarted) {
					echo("[\n");
					define('Ninjalytics_PSR_CHART_STARTED', true);
				}
				
			}
			
			$filepath = 'php://output';

			if ($_POST['format'] == 'json' || $_POST['format'] == 'json-totals') {
				header('Content-Type: application/json');
				
				include_once(__DIR__.'/includes/Ninjalytics_JSON_Export.php');
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- No equivalent function in WP_Filesystem
				$out = fopen($filepath, 'w');
				$dest = new Ninjalytics_JSON_Export($out, $_POST['format'] == 'json-totals');
			} else {
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="Product Sales.csv"');
				
				include_once(__DIR__.'/includes/Ninjalytics_CSV_Export.php');
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- No equivalent function in WP_Filesystem
				$out = fopen($filepath, 'w');
				$dest = new Ninjalytics_CSV_Export($out, array(
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- false positive
					'delimiter' => sanitize_text_field(wp_unslash($_POST['format_csv_delimiter'] ?? ',')),
					'surround' => sanitize_text_field(wp_unslash($_POST['format_csv_surround'] ?? '"')),
					'escape' => sanitize_text_field(wp_unslash($_POST['format_csv_escape'] ?? '\\')),
				));
			}
			
			
			if (!empty($_POST['report_title_on'])) {
				$dest->putTitle(ninjalytics_dynamic_title(sanitize_text_field(wp_unslash($_POST['report_title'] ?? '')), $titleVars));
			}
			
			if (!empty($_POST['include_header']))
				ninjalytics_export_header($dest);
			ninjalytics_export_body($dest, $start_date, $end_date);
			
			$dest->close();
			
			// Call destructor, if any
			$dest = null;
			
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- No equivalent function in WP_Filesystem
			fclose($out);
			
			if ($isChart) {
				if ($isTimeChart &&$chartRunCount > 1) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
					$_SERVER['HTTP_X_PSR_CHART_RUN_START'] = ((int) $_SERVER['HTTP_X_PSR_CHART_RUN_START'] + 1);
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
					$_SERVER['HTTP_X_PSR_CHART_RUN_COUNT'] = ((int) $_SERVER['HTTP_X_PSR_CHART_RUN_COUNT'] - 1);
					echo(',');
					if (!defined('PSR_CHART_SUBSEQUENT_RUN')) {
						define('PSR_CHART_SUBSEQUENT_RUN', true);
					}
					return ninjalytics_maybe_run_report();
				}
				echo("]\n");
			}
			
			exit;
			
		}
	}
}

function ninjalytics_get_report_dates($withDesc=false)
{
	
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
	
	// Calculate report start and end dates (timestamps)
	$dates = [];

	switch ($_POST['report_time_mode'] ?? '') {
		case 'basic':
			foreach (['from', 'to'] as $time) {
				$unit = sanitize_text_field(wp_unslash($_POST['report_time_basic_'.$time.'_unit'] ?? ''));
				if ($unit[0] == '-') {
					$invert = -1;
					$unit = substr($unit, 1);
				} else {
					$invert = 1;
				}
				switch ($unit) {
					case 'now':
						$date = current_time('timestamp');
						break;
					case 'max':
						$reporter = ninjalytics_get_active_reporter();
						$maxYear = $time == 'from' ? $reporter->getOldestOrderYear() :  $reporter->getNewestOrderYear();
						$date = strtotime(($maxYear ? $maxYear : wp_date('Y')).($time == 'from' ? '-01-01 0:00:00' : '-12-31 23:59:59'));
						break;
					case 'd':
						$date = current_time('timestamp') + (((int) $_POST['report_time_basic_'.$time] ?? 0) * $invert * 86400);
						break;
					case 'cm':
						$num = ((int) $_POST['report_time_basic_'.$time] ?? 0) * $invert;
						$date = strtotime(($num < 0 ? '-' : '+').$num.' month', current_time('timestamp'));
						break;
				}
				
				switch ($_POST['report_time_basic_'.$time.'_round'] ?? '') {
					case 'd':
						$date = $date - ($date % 86400) + ($time == 'from' ? 0 : 86399);
						break;
					case 'm':
						$date = strtotime(wp_date('Y-m', ($time == 'from' ? $date : strtotime('+1 month', $date))).'-01 00:00:00') - ($time == 'from' ? 0 : 1);
						break;
				}
				
				$dates[] = $date;
			}
			
			
			if ($withDesc) {
				$fromUnit = sanitize_text_field(wp_unslash($_POST['report_time_basic_from_unit'] ?? ''));
				$toUnit = sanitize_text_field(wp_unslash($_POST['report_time_basic_to_unit'] ?? ''));
				$startsNow = $fromUnit == 'now' || ($fromUnit != 'max' && empty($_POST['report_time_basic_from']));
				$endsNow = $toUnit == 'now' || ($toUnit != 'max' && empty($_POST['report_time_basic_to']));
				if ($fromUnit == 'max' && $toUnit == 'max') {
					$desc = 'All time';
				} else {
					if ($fromUnit == 'now') {
						switch ($_POST['report_time_basic_from_round'] ?? '') {
							case 'd':
								$startDesc = 'today';
								break;
							case 'm':
								break;
							default:
								$startDesc = 'now';
						}
					}
					
					if ($toUnit == 'now') {
						switch ($_POST['report_time_basic_to_round'] ?? '') {
							case 'd':
								$endDesc = 'today';
								break;
							case 'm':
								break;
							default:
								$endDesc = 'now';
						}
					}
					
					if (!isset($startDesc) || !isset($endDesc)) {
						if ($_POST['report_time_basic_from_round'] == 'd' && $_POST['report_time_basic_to_round'] == 'd') {
							$format = str_replace('F', 'M', get_option('date_format'));
						} else if ($_POST['report_time_basic_from_round'] == 'm' && $_POST['report_time_basic_to_round'] == 'm') {
							$format = 'M Y';
						} else {
							$format = str_replace('F', 'M', get_option('date_format')).' '.(stripos(get_option('time_format'), 'A') === false ? 'H:i:s' : 'g:i:s A');
						}
						
						if (!isset($startDesc)) {
							$startDesc = gmdate($format, $dates[0]);
						}
						if (!isset($endDesc)) {
							$endDesc = gmdate($format, $dates[1]);
						}
					}
					
					$desc = $startDesc == $endDesc ? $startDesc : $startDesc.' to '.$endDesc;
				}
				
				$dates[] = $desc;
			}
			
			
			break;
		
		case 'absolute':
			foreach (['from', 'to'] as $time) {
				$dates[] = strtotime(sanitize_text_field(wp_unslash($_POST['report_time_absolute_'.$time.'_date'] ?? '')).' '.sanitize_text_field(wp_unslash($_POST['report_time_absolute_'.$time.'_time'] ?? '')));
			}
			
			if ($withDesc) {
				$format = str_replace('F', 'M', get_option('date_format')).' '.(stripos(get_option('time_format'), 'A') === false ? 'H:i:s' : 'g:i:s A');
				$dates[] = gmdate($format, $dates[0]).' to '.gmdate($format, $dates[1]);
			}
			
			break;
			
	}
	
	
	return $dates;
	
	// Backwards compatibility with old presets
	
	switch ($_POST['report_time'] ?? '') {
		case '0d':
			$end_date = strtotime('midnight', current_time('timestamp'));
			$start_date = $end_date;
			break;
		case '1d':
			$end_date = strtotime('midnight', current_time('timestamp')) - 86400;
			$start_date = $end_date;
			break;
		case '7d':
			$end_date = strtotime('midnight', current_time('timestamp')) - 86400;
			$start_date = $end_date - (86400 * 6);
			break;
		case '1cm':
			$start_date = strtotime(gmdate('Y-m', current_time('timestamp')).'-01 midnight -1month');
			$end_date = strtotime('+1month', $start_date) - 86400;
			break;
		case '0cm':
			$start_date = strtotime(gmdate('Y-m', current_time('timestamp')).'-01 midnight');
			$end_date = strtotime('+1month', $start_date) - 86400;
			break;
		case '+1cm':
			$start_date = strtotime(gmdate('Y-m', current_time('timestamp')).'-01 midnight +1month');
			$end_date = strtotime('+1month', $start_date) - 86400;
			break;
		case '+7d':
			$start_date = strtotime('midnight', current_time('timestamp')) + 86400;
			$end_date = $start_date + (86400 * 6);
			break;
		case '+30d':
			$start_date = strtotime('midnight', current_time('timestamp')) + 86400;
			$end_date = $start_date + (86400 * 29);
			break;
		case 'custom':
			if (!empty($_POST['report_start_dynamic'])) {
				$_POST['report_start'] = gmdate('Y-m-d', strtotime(sanitize_text_field(wp_unslash($_POST['report_start_dynamic'])), current_time('timestamp')));
			}
			if (!empty($_POST['report_end_dynamic'])) {
				$_POST['report_end'] = gmdate('Y-m-d', strtotime(sanitize_text_field(wp_unslash($_POST['report_end_dynamic'])), current_time('timestamp')));
			}
			$end_date = strtotime(sanitize_text_field(wp_unslash($_POST['report_end_time'] ?? '')), strtotime(sanitize_text_field(wp_unslash($_POST['report_end'] ?? ''))));
			$start_date = strtotime(sanitize_text_field(wp_unslash($_POST['report_start_time'] ?? '')), strtotime(sanitize_text_field(wp_unslash($_POST['report_start'] ?? ''))));
			break;
		default: // 30 days is the default
			$end_date = strtotime('midnight', current_time('timestamp')) - 86400;
			$start_date = $end_date - (86400 * 29);
	}
	return array($start_date, $end_date);
	
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

// This function outputs the report header row
function ninjalytics_export_header($dest)
{
	$header = array();
	
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Individual values unslashed/sanitized below; this is a helper function, to be called after nonce is checked as needed, no persistent changes
	foreach (($_POST['fields'] ?? []) as $field) {
		$field = sanitize_text_field(wp_unslash($field));
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
		$header[] = sanitize_text_field(wp_unslash($_POST['field_names'][$field] ?? $field));
	}
	
	$dest->putRow($header, true);
}

// This function generates and outputs the report body rows
function ninjalytics_export_body($dest, $start_date, $end_date)
{
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
	global $woocommerce, $wpdb;
	
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
	$disableProductGrouping = ((int) ( $_POST['disable_product_grouping'] ?? 0 )) > 0;
	
	 if ($disableProductGrouping) {
		// Force some settings to be disabled
		unset($_POST['include_nil']);
		unset($_POST['refunds']);
	 }
	
	// Set time limit
	if (is_numeric($_POST['time_limit'] ?? '')) {
// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Required for report generation
		set_time_limit((int) $_POST['time_limit']);
	}

	/* Helper class */
	if (!class_exists('Ninjalytics_PSR_Order_Source') && trait_exists('Automattic\WooCommerce\Internal\Traits\OrderAttributionMeta')) {
		class Ninjalytics_PSR_Order_Source {
			use Automattic\WooCommerce\Internal\Traits\OrderAttributionMeta;
			
			private $type, $source;
			
			function __construct($type, $source) {
				$this->type = $type;
				$this->source = $source;
			}
			
			function get_name() {
				return $this->type ? $this->get_origin_label($this->type, $this->source ?? '') : '';
			}
		}
	}
	
	// Get base fields
	$baseFields = array_unique(array_map('sanitize_text_field', wp_unslash($_POST['fields'] ?? [])));
	
	if (!empty($_POST['enable_custom_segments']) && !empty($_POST['groupby']) && !in_array('builtin::groupby_field', $baseFields)) {
		$baseFields[] = 'builtin::groupby_field';
	}
	
	$wc_report = ninjalytics_get_active_reporter();
	
	// Check order statuses
	if (empty($_POST['order_statuses']))
		return;
	$_POST['order_statuses'] = array_intersect(array_map('sanitize_text_field', wp_unslash($_POST['order_statuses'])), array_keys($wc_report->getOrderStatuses()));
	if (empty($_POST['order_statuses']))
		return;
	
	$productsFilteringMode = sanitize_text_field(wp_unslash($_POST['products'] ?? ''));
	if ($productsFilteringMode == 'ids') {
		$product_ids = array();
		
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Individual values int cast below
		foreach (explode(',', $_POST['product_ids'] ?? []) as $productId) {
			$productId = trim($productId);
			if (is_numeric($productId))
				$product_ids[] = (int) $productId;
		}
	}
	
	$productsFiltered = ($productsFilteringMode == 'cats' || empty($_POST['include_unpublished']));
	if ($productsFiltered || !empty($_POST['include_nil'])) {
		$params = array(
			'post_type' => $wc_report->productPostType,
			'nopaging' => true,
			'fields' => 'ids',
			'ignore_sticky_posts' => true,
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array()
		);
		
		if (isset($product_ids)) {
			$params['post__in'] = $product_ids;
		}
		if ($productsFilteringMode == 'cats') {
			$cats = array();
			
// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Individual values int cast below
			foreach (($_POST['product_cats'] ?? []) as $cat)
				if (is_numeric($cat))
					$cats[] = (int) $cat;
			$params['tax_query'][] = array(
				'taxonomy' => $wc_report->productCategoryTaxonomy,
				'terms' => $cats
			);
		}
		
		if (!empty($_POST['include_unpublished'])) {
			$params['post_status'] = 'any';
		}
		
		$product_ids = get_posts($params);
	}
	if (!isset($product_ids)) {
		$product_ids = null;
	} else if ($_POST['products'] == 'ids') {
		$productsFiltered = true;
	}
	
	// Avoid max join size error
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query('SET SQL_BIG_SELECTS=1');
	
	// Filter report query
	add_filter('ninjalytics_get_order_report_query', 'ninjalytics_filter_report_query');
	
		
	$wc_report->start_date = $start_date;
	$wc_report->end_date = $end_date;
	
	// Initialize totals array
	if (empty($_POST['include_totals']) || empty($_POST['total_fields'])) {
		$totals = array();
	} else {
		$totals = array_combine(array_map('sanitize_text_field', wp_unslash($_POST['total_fields'] ?? [])), array_fill(0, count($_POST['total_fields'] ?? []), 0));
	}
	
	$rows = array();
	$orderIndex = (int) array_search($_POST['orderby'] ?? '', $_POST['fields']);
	$selectedReportFields = array_map('sanitize_text_field', wp_unslash($_POST['fields']));
	
	if ($product_ids === null || !empty($product_ids)) { // Do not run the report if product_ids is empty and not null
	
		if (method_exists($dest, 'putDebugSql')) {
			$wc_report->setDebugSqlCallback([$dest, 'putDebugSql']);
		}
		
		// Get report data
		$sold_products = ninjalytics_getReportData($wc_report, $baseFields, ($productsFiltered ? $product_ids : null), $start_date, $end_date);
		if (!empty($_POST['refunds'])) {
			$refunded_products = ninjalytics_getReportData($wc_report, $baseFields, ($productsFiltered ? $product_ids : null), $start_date, $end_date, true);
			$sold_products = ninjalytics_process_refunds($sold_products, $refunded_products, array(
				'quantity',
				'gross',
				'gross_after_discount',
				'taxes'
			), (int) $_POST['disable_product_grouping'], ((int) $_POST['disable_product_grouping']) == 2 ? 'product_category' : '');
		}
		
		
		foreach ($sold_products as $product) {
			$row = ninjalytics_get_product_row($product, $selectedReportFields, $totals);
			if (isset($rows[(string) $row[$orderIndex]])) {
				$rows[(string) $row[$orderIndex]][] = $row;
			} else {
				$rows[(string) $row[$orderIndex]] = array($row);
			}
		}
		
		if (!empty($_POST['include_nil'])) {
			foreach (ninjalytics_get_nil_products($product_ids, $sold_products, $dest, $totals) as $row) {
				if (isset($rows[(string) $row[$orderIndex]])) {
					$rows[(string) $row[$orderIndex]][] = $row;
				} else {
					$rows[(string) $row[$orderIndex]] = array($row);
				}
			}
		}
	}
	
	if (!empty($_POST['include_shipping'])) {
		$hasTaxFields = (count(array_intersect(array('builtin::taxes', 'builtin::total_with_tax', 'taxes', 'total_with_tax'), $baseFields)) > 0);
		$shippingResult = ninjalytics_getShippingReportData($wc_report, $baseFields, $start_date, $end_date, $hasTaxFields);
		
		
		// Retrieve shipping taxes (if needed) when not grouping by products, since these can't be retrieved the usual way in that case
		if ($disableProductGrouping && $shippingResult && isset(current($shippingResult)->taxes)) {
			$shippingResult = array_map('ninjalytics_fill_shipping_order_item_taxes', $shippingResult);
		}
		
		
		if (!empty($_POST['refunds'])) {
			$shippingRefundResult = ninjalytics_getShippingReportData($wc_report, $baseFields, $start_date, $end_date, $hasTaxFields, true);
			
			// Retrieve shipping taxes (if needed) when not grouping by products, since these can't be retrieved the usual way in that case
			if ($disableProductGrouping && $shippingRefundResult && isset(current($shippingRefundResult)->taxes)) {
				$shippingRefundResult = array_map('ninjalytics_fill_shipping_order_item_taxes', $shippingRefundResult);
			}
			
			$shippingResult = ninjalytics_process_refunds($shippingResult, $shippingRefundResult, array(
				'gross',
				'gross_after_discount',
				'taxes'
			), $disableProductGrouping);
		}
		foreach ($shippingResult as $shipping) {
			$row = ninjalytics_get_shipping_row($shipping, $selectedReportFields, $totals);
			if (isset($rows[(string) $row[$orderIndex]])) {
				$rows[(string) $row[$orderIndex]][] = $row;
			} else {
				$rows[(string) $row[$orderIndex]] = array($row);
			}
		}
	}
	
	if (sanitize_text_field(wp_unslash($_POST['orderdir'] ?? '')) == 'desc') {
		krsort($rows);
	} else {
		ksort($rows);
	}
	
	$rowNum = 0;
	
	if (empty($_POST['limit_on'])) {
		$limit = 0;
	} else if (!empty($_POST['limit'])) {
		$limit = (int) $_POST['limit'];
	} else {
		$limit = -1;
	}
	
	foreach ($rows as $filterValueRows) {
		foreach ($filterValueRows as $row) {
			++$rowNum;
			if ($limit && $rowNum > $limit) {
				break 2;
			}
			$dest->putRow($row);
		}
	}
	
	if (!empty($_POST['include_totals'])) {
		$dest->putRow(ninjalytics_get_totals_row($totals, $selectedReportFields), false, true);
	}
	
	// Remove report query filter
	remove_filter('ninjalytics_get_order_report_query', 'ninjalytics_filter_report_query');
	
	
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}


function ninjalytics_process_refunds($sold_products, $refunded_products, $fieldsToAdjust, $disableProductGrouping, $additionalMatchField='')
{
	foreach ($refunded_products as $refunded_product) {
		$product = false;
		
		// For refund orders with no line items, the database query returns a row with NULL product_id and NULL amounts;
		// skip this row in processing
		if (empty($refunded_product->product_id) && !$refunded_product->gross) {
			continue;
		}
		
		foreach ($sold_products as $sold_product) {
			
			if ( ($disableProductGrouping || $sold_product->product_id == $refunded_product->product_id)
				&& ($disableProductGrouping || (empty($sold_product->variation_id) && empty($refunded_product->variation_id)) || $sold_product->variation_id == $refunded_product->variation_id)
				&& ((int) $disableProductGrouping != -1 || $sold_product->product_sku == $refunded_product->product_sku)
				&& ((empty($sold_product->groupby_field) && empty($refunded_product->groupby_field)) || $sold_product->groupby_field == $refunded_product->groupby_field)
				&& (empty($additionalMatchField) || ((empty($sold_product->$additionalMatchField) && empty($refunded_product->$additionalMatchField)) || $sold_product->$additionalMatchField == $refunded_product->$additionalMatchField))
			) {
				$product = $sold_product;
				break;
			}
		}
			
		if ($product === false) {
			$product = clone $refunded_product;
			$product->is_refund_only = true;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
			if (empty($_POST['refunds'])) {
				foreach ($fieldsToAdjust as $field) {
					if (isset($product->$field)) {
						$product->$field = 0;
					}
				}
			} else {
				foreach ($fieldsToAdjust as $field) {
					if (isset($product->$field)) {
						$product->$field = abs($product->$field) * -1;
					}
				}
			}
			
			$sold_products[] = $product;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
		} else if (!empty($_POST['refunds'])) {
			foreach ($fieldsToAdjust as $field) {
				if (isset($product->$field)) {
					$product->$field += (abs($refunded_product->$field) * -1);
				}
			}
		}
	}
	
	return $sold_products;
}

function ninjalytics_is_hpos() {
	return method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
}

function ninjalytics_get_product_row($product, $fields, &$totals)
{
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
	$row = array();

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast	
	$disableProductGrouping = (int) ($_POST['disable_product_grouping'] ?? 0);
	$groupByProducts = $disableProductGrouping <= 0;
	
	// Remove duplicate product IDs and variation IDs
	
	$product->_product_ids = empty($product->product_id) ? [] : ($disableProductGrouping == -1 ? array_unique(explode(',', $product->product_id)) : [$product->product_id]);
	$product->_variation_ids = empty($product->variation_id) ? [] : ($disableProductGrouping == -1 ? array_unique(explode(',', $product->variation_id)) : [$product->variation_id]);
		
	foreach ($fields as $fieldIndex => $field) {
			$rowValue = '';
			
			if (substr($field, 0, 15) == 'builtin::taxes_') {
				$taxAmounts = explode(',', $product->order_item_ids);
				$taxId = substr($field, 15);
				$rowValue = array_sum(array_map(function($orderItemId) use ($taxId) {
					return ninjalytics_get_order_item_tax($orderItemId, $taxId, !empty($_POST['intermediate_rounding']));
				}, $taxAmounts));
			} else if ( $groupByProducts || !in_array($field, [
															'builtin::product_id', 'builtin::variation_id', 'builtin::variation_sku', 'builtin::variation_attributes', 'builtin::product_sku',
															'builtin::product_categories', 'builtin::product_menu_order', 'builtin::product_stock', 'builtin::publish_time', 'builtin::product_desc', 'builtin::product_excerpt'
														]) || ($disableProductGrouping == 2 && $field == 'builtin::product_categories'
			) ) {
				
				switch ($field) {
					case 'builtin::product_id':
						$rowValue = implode(', ', $product->_product_ids);
						break;
					case 'builtin::product_sku':
						$rowValue = isset($product->product_sku) ? $product->product_sku : implode(', ', array_unique(array_map(function($productId) {
							return get_post_meta($productId, '_sku', true);
						}, $product->_product_ids)));
						break;
					case 'builtin::product_name':
						// Following code provided by and copyright Daniel von Mitschke, released under GNU General Public License (GPL) version 2 or later, used under GPL version 3 or later (see license/LICENSE.TXT)
						// Modified by Jonathan Hall
						if ($groupByProducts) {
							$name = implode(', ', array_unique(array_map(function($productId) {
								return html_entity_decode(get_the_title($productId));
							}, $product->_product_ids)));
						} else {
							unset($name);
						}
					    // Handle deleted products
					    if(empty($name)) {
					        $name = $product->product_name;
                        }
						$rowValue = $name;
						// End code provided by Daniel von Mitschke
						break;
					case 'builtin::quantity_sold':
						$rowValue = $product->quantity;
						break;
					case 'builtin::gross_sales':
						$rowValue = $product->gross;
						break;
					case 'builtin::gross_after_discount':
						$rowValue = $product->gross_after_discount;
						break;
					case 'builtin::product_categories':
						$rowValue = ($disableProductGrouping == 2 ? $product->product_category : ninjalytics_get_custom_field_value($product->_product_ids, 'taxonomy::product_cat'));
						break;
					case 'builtin::product_menu_order':
						 $rowValueDelimiter = ', ';
						 $rowValue = array_unique(array_map(function($productId) {
							$wc_product = wc_get_product($productId);
							return empty($wc_product) ? '' : $wc_product->get_menu_order();
						}, $product->_variation_ids ? $product->_variation_ids : $product->_product_ids));
						break;
					case 'builtin::product_stock':
						$stock = '';
						if ($product->_variation_ids) {
							foreach ($product->_variation_ids as $variationId) {
								$itemStock = get_post_meta($variationId, '_stock', true); // should be NULL if _manage_stock is "no"
								if (is_numeric($itemStock)) {
									$stock = (float) $stock + (float) $itemStock;
								}
							}
						} else {
							foreach ($product->_product_ids as $productId) {
								if ( ninjalytics_is_variable_product($productId) && get_post_meta($productId, '_manage_stock', true) != 'yes' ) {
									$variationIds = get_posts([
										'post_type' => 'product_variation',
										'post_parent' => $productId,
										'fields' => 'ids',
										'nopaging' => true,
										'orderby' => 'none',
										'post_status' => 'all'
									]);
									$itemStock = 0;
									foreach ($variationIds as $stockVariationId) {
										$stock = (float) $stock + (float) get_post_meta($stockVariationId, '_stock', true);
									}
								} else {
									$itemStock = get_post_meta($productId, '_stock', true);
								}
								if (is_numeric($itemStock)) {
									$stock = (float) $stock + (float) $itemStock;
								}
							}
						}
						
						$rowValue = $stock;
						break;
					case 'builtin::taxes':
						$rowValue = $product->taxes;
						break;
					case 'builtin::discount':
						$rowValue = $product->gross - $product->gross_after_discount;
						break;
					case 'builtin::total_with_tax':
						$rowValue = $product->gross_after_discount + $product->taxes;
						break;
					case 'builtin::avg_order_total':
						$rowValue = $product->avg_order_total;
						break;
					case 'builtin::variation_id':
						$rowValueDelimiter = ', ';
						$rowValue = $product->_variation_ids;
						break;
					case 'builtin::variation_sku':
						$rowValueDelimiter = ', ';
						$rowValue = $product->_variation_ids ? array_unique(array_map(function($variationId) {
							return get_post_meta($variationId, '_sku', true);
						}, $product->_variation_ids)) : '';
						break;
					case 'builtin::variation_attributes':
						$rowValue = ninjalytics_getFormattedVariationAttributes($product);
						break;
					case 'builtin::publish_time':
						$rowValueDelimiter = ', ';
						$rowValue = array_map(function($productId) {
							get_the_time('Y-m-d H:i:s', $productId);
						}, $product->_product_ids);
						break;
					case 'builtin::line_item_count':
						$rowValue = empty($product->order_item_ids) ? 0 : substr_count($product->order_item_ids, ',') + 1;
						break;
					case 'builtin::groupby_field':
						if (!empty($_POST['enable_custom_segments'])) {
							$selectedGroupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
							if ($selectedGroupByField == 'i_builtin::item_price') {
								$rowValue = $product->gross / $product->quantity;
							} else if ($selectedGroupByField == 'o_builtin::order_source') {
								// replicated in shipping product row below
								$rowValue = class_exists('Ninjalytics_PSR_Order_Source') ? (new Ninjalytics_PSR_Order_Source( $product->groupby_field, $product->groupby_fieldb ))->get_name() : '(Unknown)';
							} else {
								$rowValue = $product->groupby_field;
							}
						} else {
							$rowValue = '';
						}
						break;
					
					// hm-export-order-items-pro\hm-export-order-items-pro.php
					case 'builtin::product_desc':
						if ($product->_product_ids) {
							$rowValue = implode("\n---\n", array_unique(array_map(function($productId) {
								$productPost = get_post($productId);
								if (empty($productPost)) {
									$rowValue = '';
								} else {
									$rowValue = html_entity_decode(wp_strip_all_tags(do_shortcode($productPost->post_content)));
								}
							}, $product->_product_ids)));
						} else {
							$rowValue = '';
						}
						break;
						
					// hm-export-order-items-pro\hm-export-order-items-pro.php
					case 'builtin::product_excerpt':
						if ($product->_product_ids) {
							$rowValue = implode("\n---\n", array_unique(array_map(function($productId) {
								$productPost = get_post($productId);
								if (empty($productPost)) {
									$rowValue = '';
								} else {
									$rowValue = html_entity_decode(wp_strip_all_tags(do_shortcode($productPost->post_excerpt)));
								}
							}, $product->_product_ids)));
						} else {
							$rowValue = '';
						}
						break;
					default:
						$rowValue = '';
				}
				
			}
			
			$formatAmount = !empty($_POST['format_amounts']) && isset($_POST['round_fields']) && in_array($field, $_POST['round_fields']);
			
			if (is_array($rowValue)) {
				$rowValue = implode(
					empty($rowValueDelimiter) ? ', ' : $rowValueDelimiter,
					$formatAmount
						? array_map(function($val) {
							return is_numeric($val) ? number_format($val, 2, '.', '') : $val;
						}, $rowValue)
						: $rowValue
				);
			} else if ($formatAmount && is_numeric($rowValue)) {
				$rowValue = number_format($rowValue, 2, '.', '');
			}
			
			$row[] = apply_filters('ninjalytics_row_value', $rowValue, $field);
		
		if (isset($totals[$field])) {
			$newValue = end($row);
			if (empty($newValue)) {
				
			} else if (is_numeric($newValue)) {
				$totals[$field] += (float) $newValue;
			} else {
				unset($totals[$field]);
			}
		}
	}
	
	return $row;
	
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

function ninjalytics_get_order_item_tax($orderItemId, $taxTypeId, $rounded=false) {
	global $ninjalytics_order_tax_rate_ids;
	
	$item = WC_Order_Factory::get_order_item($orderItemId);
	$orderId = $item->get_order_id();
	
	if (!isset($ninjalytics_order_tax_rate_ids[$orderId])) {
		$order = WC_Order_Factory::get_order($orderId);
		$orderTaxes = $order->get_items('tax');
		
		if (!isset($ninjalytics_order_tax_rate_ids)) {
			$ninjalytics_order_tax_rate_ids = [];
		}
		
		$ninjalytics_order_tax_rate_ids[$orderId] = [];
		foreach ($orderTaxes as $orderTax) {
			$ninjalytics_order_tax_rate_ids[$orderId][$orderTax->get_label()] = $orderTax->get_rate_id();
		}
	}
	
	$taxTypes = ninjalytics_get_tax_types();
	if ( empty($taxTypes[$taxTypeId]) ) {
		throw new Exception();
	}
	
	if (isset($ninjalytics_order_tax_rate_ids[$orderId][$taxTypes[$taxTypeId]])) {
		$taxes = $item->get_taxes();
		
		if (isset($taxes['total'][$ninjalytics_order_tax_rate_ids[$orderId][$taxTypes[$taxTypeId]]])) {
			$amount = $taxes['total'][$ninjalytics_order_tax_rate_ids[$orderId][$taxTypes[$taxTypeId]]];
			return $rounded ? round($amount, 2) : $amount;
		}
	}
	
	return 0;
}

function ninjalytics_get_nil_product_row($productId, $fields, $variationId = null, &$totals = null)
{
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
    $row = array();
	
    foreach ($fields as $field) {
        if (substr($field, 0, 15) == 'builtin::taxes_') {
			$row[] = empty($_POST['format_amounts']) || empty($_POST['round_fields']) || !in_array($field, $_POST['round_fields']) ? 0 : '0.00';
        } else {
            switch ($field) {
                case 'builtin::product_id':
                    $rowValue = $productId;
                    break;
                case 'builtin::product_sku':
                    $rowValue = get_post_meta($productId, '_sku', true);
                    break;
                case 'builtin::product_name':
                    $rowValue = html_entity_decode(get_the_title($productId));
                    break;
                case 'builtin::quantity_sold':
                    $rowValue = 0;
                    break;
                case 'builtin::gross_sales':
                case 'builtin::gross_after_discount':
                case 'builtin::taxes':
                case 'builtin::discount':
                case 'builtin::total_with_tax':
                    $rowValue = 0;
                    break;
                case 'builtin::groupby_field':
                    $rowValue = '';
                    break;
                case 'builtin::product_categories':
                    $rowValue = ninjalytics_get_custom_field_value([$productId], 'taxonomy::product_cat');
                    break;
				case 'builtin::product_menu_order':
					if (!isset($wc_product)) {
						$wc_product = wc_get_product(empty($variationId) ? $productId : $variationId);
					}
					$rowValue = empty($wc_product) ? '' : $wc_product->get_menu_order();
					break;
                case 'builtin::product_stock':
					if (!empty($variationId)) {
						$stock = get_post_meta($variationId, '_stock', true); // should be NULL if _manage_stock is "no"
					} else if ( ninjalytics_is_variable_product($productId) && get_post_meta($productId, '_manage_stock', true) != 'yes' ) {
						$variationIds = get_posts([
							'post_type' => 'product_variation',
							'post_parent' => $productId,
							'fields' => 'ids',
							'nopaging' => true,
							'orderby' => 'none',
							'post_status' => 'all'
						]);
						
						$stock = 0;
						foreach ($variationIds as $stockVariationId) {
							$stock += (float) get_post_meta($stockVariationId, '_stock', true);
						}
					} else {
						$stock = get_post_meta($productId, '_stock', true);
					}
					
					$rowValue = is_numeric($stock) ? (float) $stock : $stock;
					break;
                case 'builtin::variation_id':
                    $rowValue = (empty($variationId) ? '' : $variationId);
                    break;
                case 'builtin::variation_sku':
                    $rowValue = (empty($variationId) ? '' : get_post_meta($variationId, '_sku', true));
                    break;
                case 'builtin::variation_attributes':
                    $rowValue = (empty($variationId) ? '' : ninjalytics_getFormattedVariationAttributes($variationId));
                    break;
                case 'builtin::publish_time':
                    $rowValue = get_the_time('Y-m-d H:i:s', $productId);
                    break;
				
				// hm-export-order-items-pro\hm-export-order-items-pro.php
				case 'builtin::product_desc':
					$productPost = get_post($productId);
					if (empty($productPost)) {
						$rowValue = '';
					} else {
						$rowValue = html_entity_decode(wp_strip_all_tags(do_shortcode($productPost->post_content)));
					}
					break;
					
				// hm-export-order-items-pro\hm-export-order-items-pro.php
				case 'builtin::product_excerpt':
					$productPost = get_post($productId);
					if (empty($productPost)) {
						$rowValue = '';
					} else {
						$rowValue = html_entity_decode(wp_strip_all_tags(do_shortcode($productPost->post_excerpt)));
					}
					break;
				
					
                default:
                    $rowValue = '';
            }
			
			if (!empty($_POST['format_amounts']) && isset($_POST['round_fields']) && in_array($field, $_POST['round_fields']) && is_numeric($rowValue)) {
				$rowValue = number_format($rowValue, 2, '.', '');
			}
			
			$row[] = apply_filters('ninjalytics_row_value', $rowValue, $field);
        }
		
		if (isset($totals[$field])) {
			$newValue = end($row);
			if (empty($newValue)) {
				
			} else if (is_numeric($newValue)) {
				$totals[$field] += (float) $newValue;
			} else {
				unset($totals[$field]);
			}
		}
	}
	
	return $row;
	
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

function ninjalytics_get_shipping_row($shipping, $fields, &$totals)
{	
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
	
	global $woocommerce;
	
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
	$groupByProducts = (int) ($_POST['disable_product_grouping'] ?? 0) <= 0;
	
	$row = array();
	foreach ($fields as $field) {
		if ( substr($field, 0, 15) == 'builtin::taxes_' ) {
			$taxAmounts = explode(',', $shipping->order_item_ids);
			$taxId = substr($field, 15);
			$rowValue = array_sum(array_map(function($orderItemId) use ($taxId) {
				return ninjalytics_get_order_item_tax($orderItemId, $taxId, !empty($_POST['intermediate_rounding']));
			}, $taxAmounts));
			if (!empty($_POST['format_amounts']) && isset($_POST['round_fields']) && in_array($field, $_POST['round_fields'])) {
				$rowValue = number_format($rowValue, 2, '.', '');
			}
			$row[] = $rowValue;
		} else {
			switch ($field) {
				case 'builtin::product_id':
					$rowValue = $shipping->product_id;
					break;
				case 'builtin::quantity_sold':
				case 'builtin::line_item_count':
					$rowValue = empty($shipping->order_item_ids) ? 0 : substr_count($shipping->order_item_ids, ',') + 1;
					break;
				case 'builtin::gross_sales':
					$rowValue = $shipping->gross;
					break;
				case 'builtin::gross_after_discount':
					$rowValue = $shipping->gross;
					break;
				case 'builtin::product_name':
					if (isset($shipping->product_id)) {
						$woocommerce->shipping->load_shipping_methods();
						$shippingMethods = $woocommerce->shipping->get_shipping_methods();
						if (!empty($shippingMethods[$shipping->product_id]->method_title))
							$rowValue = 'Shipping - '.$shippingMethods[$shipping->product_id]->method_title;
						else
							$rowValue = 'Shipping - '.$shipping->product_id;
					} else {
						$rowValue = 'Shipping';
					}
					break;
				case 'builtin::taxes':
					$rowValue = $shipping->taxes;
					break;
				case 'builtin::total_with_tax':
					$rowValue = $shipping->gross + $shipping->taxes;
					break;
				case 'builtin::groupby_field':
					if (!empty($_POST['enable_custom_segments'])) {
						$selectedGroupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
						if ($selectedGroupByField == 'i_builtin::item_price') {
							$rowValue = $shipping->gross / $shipping->quantity;
						} else if ($selectedGroupByField == 'o_builtin::order_source') {
							// replicated in regular product row above
							$rowValue = class_exists('Ninjalytics_PSR_Order_Source') ? (new Ninjalytics_PSR_Order_Source( $product->groupby_field, $product->groupby_fieldb ))->get_name() : '(Unknown)';
						} else {
							$rowValue = $shipping->groupby_field;
							if (!empty($_POST['remove_html'])) {
								$rowValue = wp_strip_all_tags($rowValue);
							}
						}
					} else {
						$rowValue = '';
					}
					break;
				default:
					$rowValue = '';
			}
			
			if (!empty($_POST['format_amounts']) && isset($_POST['round_fields']) && in_array($field, $_POST['round_fields']) && is_numeric($rowValue)) {
				$rowValue = number_format($rowValue, 2, '.', '');
			}
			
			$row[] = apply_filters('ninjalytics_row_value', $rowValue, $field);
		}
		
		if (isset($totals[$field])) {
			$newValue = end($row);
			if (empty($newValue)) {
				
			} else if (is_numeric($newValue)) {
				$totals[$field] += (float) $newValue;
			} else {
				unset($totals[$field]);
			}
		}
	}
	return $row;
	
	
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

function ninjalytics_get_totals_row($totals, $fields)
{
	$row = array();
	
	foreach ($fields as $field) {
		if (!isset($totals[$field]) && $field != 'builtin::product_name') {
			$row[] = '';
		} else {
			switch ($field) {
				case 'builtin::product_name':
					$row[] = 'TOTALS';
					break;
				default:
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
					$row[] = !empty($_POST['format_amounts']) && !empty($_POST['round_fields']) && in_array($field, $_POST['round_fields']) ? number_format($totals[$field], 2, '.', '') : $totals[$field];
			}
		}
	}
	
	return $row;
}

function ninjalytics_fill_shipping_order_item_taxes($shipping) {
	$shipping->taxes = array_sum(array_map(function($orderItemId) {
		return WC_Order_Factory::get_order_item($orderItemId)->get_total_tax();
	}, explode(',', $shipping->order_item_ids)));
	return $shipping;
}

function ninjalytics_get_custom_field_value($productIds, $field)
{
	if ($field == 'taxonomy::product_cat') {
		$terms = [];
		foreach ($productIds as $productId) {
			$productTerms = get_the_terms($productId, 'product_cat');
			if (is_array($productTerms)) {
				$terms = array_merge($terms, array_column($productTerms, 'name', 'term_id'));
			}
		}
		return implode(', ', $terms);
	}
}


add_action('current_screen', 'ninjalytics_on_current_screen');
function ninjalytics_on_current_screen($screen) {
	if ($screen->id == 'toplevel_page_ninjalytics') {
		add_filter('admin_body_class',  'ninjalytics_admin_add_body_classes');
	    add_action('admin_enqueue_scripts', 'ninjalytics_admin_enqueue_scripts');
	}

	add_action('admin_enqueue_scripts', 'ninjalytics_admin_global_enqueue_scripts');
}
function ninjalytics_admin_add_body_classes($classes) {
	$classes .= ' berrypress-page';
	return $classes;
}

function ninjalytics_admin_global_enqueue_scripts() {
	// Enqueue BerryPress Admin Framework styles
	wp_enqueue_style('berrypress-nj-global-admin', plugins_url('includes/berrypress-admin-framework/assets/css/global-admin.css', __FILE__), null, NINJALYTICS_VERSION);

}
function ninjalytics_admin_enqueue_scripts()
{
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- just checking which page we're on for enqueues
	if ( isset( $_GET["page"] ) &&  $_GET["page"] == "ninjalytics" ) {

		// Enqueue BerryPress Admin Framework styles
		wp_enqueue_style('berrypress-nj-admin-page', plugins_url('includes/berrypress-admin-framework/assets/css/global-admin-page.css', __FILE__), ['berrypress-nj-global-admin'], NINJALYTICS_VERSION);

		wp_enqueue_style('ninjalytics_admin_style', plugins_url('css/ninjalytics.css', __FILE__), array(), NINJALYTICS_VERSION);
		wp_enqueue_style('ninjalyticsfree_admin_style', plugins_url('css/ninjalytics-free.css', __FILE__), array(), NINJALYTICS_VERSION);
		wp_enqueue_script('ags-psr-datatables', plugins_url('js/datatables/datatables.min.js', __FILE__), [], NINJALYTICS_VERSION, true);
		wp_enqueue_style('ags-psr-datatables', plugins_url('js/datatables/datatables.min.css', __FILE__), [], NINJALYTICS_VERSION);

		wp_enqueue_script('ninjalytics', plugins_url('js/ninjalytics.js', __FILE__), [], NINJALYTICS_VERSION, true);
		wp_enqueue_script('ninjalytics-chart', plugins_url('js/chartjs/chart.umd.js', __FILE__), [], NINJALYTICS_VERSION, true);

	}
}

// Schedulable email report hook
add_filter('pp_wc_get_schedulable_email_reports', 'ninjalytics_add_schedulable_email_reports');
function ninjalytics_add_schedulable_email_reports($reports)
{
	
	$myReports = array();
	$savedReportSettings = get_option('ninjalytics_settings', array());
	if (!empty($savedReportSettings)) {
		$updated = false;
		foreach ($savedReportSettings as $i => $settings) {
			if ($i == 0)
				continue;
			if (empty($settings['key'])) {
				$chars = 'abcdefghijklmnopqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$numChars = strlen($chars);
				while (true) {
					$key = '';
					for ($j = 0; $j < 32; ++$j)
						$key .= $chars[random_int(0, $numChars-1)];
					$unique = true;
					foreach ($savedReportSettings as $settings2)
						if (isset($settings2['key']) && $settings2['key'] == $key)
							$unique = false;
					if ($unique)
						break;
				}
				$savedReportSettings[$i]['key'] = $key;
				$updated = true;
			}
			$myReports[$savedReportSettings[$i]['key']] = $settings['preset_name'];
		}
		
		if ($updated)
			update_option('ninjalytics_settings', $savedReportSettings, false);
	}

	$reports['ninjalytics'] = array(
		'name' => 'Ninjalytics',
		'callback' => 'ninjalytics_run_scheduled_report',
		'fields_callback' => 'ninjalytics_get_scheduled_report_fields',
		'reports' => $myReports
	);
	return $reports;
}

function ninjalytics_run_scheduled_report($reportId, $start, $end, $args = array(), $output = false)
{
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- the calling function is responsible for doing any nonce checks
	
	$savedReportSettings = get_option('ninjalytics_settings', [ ninjalytics_default_report_settings() ] );
	if (!isset($savedReportSettings[0]))
		return false;
	
	if ($reportId == 'last') {
		$presetIndex = 0;
	} else {
		foreach ($savedReportSettings as $i => $settings) {
			if (isset($settings['key']) && $settings['key'] == $reportId) {
				$presetIndex = $i;
				break;
			}
		}
	}
	if (!isset($presetIndex))
		return false;
	
	$prevPost = $_POST;
	$_POST = array_merge(ninjalytics_default_report_settings(), $savedReportSettings[$presetIndex]);
	$_POST = array_merge($_POST, array_intersect_key($args, $_POST));
	
	if ($start === null && $end === null) {
		list($start, $end) = ninjalytics_get_report_dates();
	} else {
		// Add one day to end since we're setting the time to midnight
		$end += 86400;
		
		$_POST['report_time'] = 'custom';
		$_POST['report_start'] = gmdate('Y-m-d', $start);
		$_POST['report_start_time'] = '12:00:00 AM';
		$_POST['report_end'] = gmdate('Y-m-d', $end);
		$_POST['report_end_time'] = '12:00:00 AM';
	}
	
		$titleVars = array(
			'now' => time(),
			'preset' => (empty($_POST['preset_name']) ? 'Product Sales' : sanitize_text_field(wp_unslash($_POST['preset_name'])))
		);
		
		$reportTimeMode = sanitize_text_field(wp_unslash($_POST['report_time'] ?? ''));
		if ($reportTimeMode != 'all') {
			$titleVars['start'] = $start;
			$titleVars['end'] = $end;
			if ($reportTimeMode == 'custom') {
				$titleVars['end'] -= 1;
			} else {
				$titleVars['end'] += 86399;
			}
		}
		
		if (!$output) {
			
			if ( !function_exists('random_bytes') ) {
				return false;
			}
			
			$tempDir = ninjalytics_get_temp_dir();
			
			// Assemble the filename for the report download
			$filepath = $tempDir.'/Product Sales.csv';
				
		}
		
	if (isset($_POST['format']) && ($_POST['format'] == 'json' || $_POST['format'] == 'json-totals')) {
		include_once(__DIR__.'/includes/Ninjalytics_JSON_Export.php');
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- No equivalent function in WP_Filesystem
		$out = fopen($output ? 'php://output' : $filepath, 'w');
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
		$dest = new Ninjalytics_JSON_Export($out, $_POST['format'] == 'json-totals');
	} else {
		include_once(__DIR__.'/includes/Ninjalytics_CSV_Export.php');
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- No equivalent function in WP_Filesystem
		$out = fopen($output ? 'php://output' : $filepath, 'w');
		$dest = new Ninjalytics_CSV_Export($out);
	}
	
	if (!empty($_POST['report_title_on'])) {
		$dest->putTitle(ninjalytics_dynamic_title(sanitize_text_field(wp_unslash($_POST['report_title'] ?? '')), $titleVars));
	}
	
	if (!empty($_POST['include_header']))
		ninjalytics_export_header($dest);
	ninjalytics_export_body($dest, $start, $end);
	
	$dest->close();
	unset($dest);
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- No equivalent function in WP_Filesystem
	fclose($out);
	
	$_POST = $prevPost;
	
	if (!$output) {
		return $filepath;
	}
	
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

function ninjalytics_get_file_ext_for_format($format) {
	return $format == 'json' ? 'json' : 'csv';
}

function ninjalytics_get_temp_dir() {
	$tempDir = WP_CONTENT_DIR.'/potent-temp/'.sha1( random_bytes(256) );
	if ( !@wp_mkdir_p($tempDir) ) {
		throw new Exception('Unable to create temporary directory');
	}
	return $tempDir;
}

function ninjalytics_get_scheduled_report_fields($reportId)
{
	$savedReportSettings = get_option('ninjalytics_settings');
	if (!isset($savedReportSettings[0]))
		return false;
	
		foreach ($savedReportSettings as $i => $settings) {
			if (isset($settings['key']) && $settings['key'] == $reportId) {
				$presetIndex = $i;
				break;
			}
		}
	if (!isset($presetIndex))
		return false;
	
	return array_combine($savedReportSettings[$presetIndex]['fields'], $savedReportSettings[$presetIndex]['field_names']);
}

// Code in this function is based on get_product_class() in WooCommerce includes/class-wc-product-factory.php
function ninjalytics_is_variable_product($product_id)
{
	$product_type = get_the_terms(
		$product_id,
		'product_type'
	);
	return (!empty($product_type) && $product_type[0]->name == 'variable');
}

function ninjalytics_get_variation_ids($product_id, $includeUnpublished)
{
	return array_keys(get_children(array(
		'post_parent' => $product_id, 
		'post_type' => 'product_variation',
		'post_status' => $includeUnpublished ? 'any' : 'publish'
	), ARRAY_N));
}

function ninjalytics_get_nil_products($product_ids, $sold_products, $dest, &$totals)
{
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed, no persistent changes
	$sold_product_ids = array();
	$rows = array();
	
	$reporter = ninjalytics_get_active_reporter();
	$selectedReportFields = array_map('sanitize_text_field', wp_unslash($_POST['fields'] ?? []));
	
	if (empty($_POST['variations']) || !$reporter->supports(PlatformFeatures::VARIATIONS)) { // Variations together
		foreach ($sold_products as $product) {
			$sold_product_ids = array_merge($sold_product_ids, explode(',', $product->product_id));
		}
		foreach (array_diff($product_ids, $sold_product_ids) as $product_id) {
			$rows[] = ninjalytics_get_nil_product_row($product_id, $selectedReportFields, null, $totals);
		}
		
	} else { // Variations separately
	
		$sold_variation_ids = array();
		foreach ($sold_products as $product) {
			$sold_product_ids = array_merge($sold_product_ids, explode(',', $product->product_id));
			if (!empty($product->variation_id))
				$sold_variation_ids = array_merge($sold_variation_ids, explode(',', $product->variation_id));
		}
		
		foreach ($product_ids as $product_id) {
			if (ninjalytics_is_variable_product($product_id)) {
				$variation_ids = ninjalytics_get_variation_ids( $product_id, !empty($_POST['include_unpublished']) );
				foreach (array_diff($variation_ids, $sold_variation_ids) as $variation_id) {
					$rows[] = ninjalytics_get_nil_product_row($product_id, $selectedReportFields, $variation_id, $totals);
				}
			} else if (array_search($product_id, $sold_product_ids) === false) { // Not variable
				$rows[] = ninjalytics_get_nil_product_row($product_id, $selectedReportFields, null, $totals);
			}
		}
	
	}
	
	return $rows;
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

function ninjalytics_get_active_reporter()
{
	if (class_exists('WooCommerce')) {
		if (ninjalytics_is_hpos()) {
			include_once(__DIR__.'/includes/reporters/woocommerce-hpos.php');
			return new Ninjalytics\Reporters\WooCommerce\Hpos();
		}
		include_once(__DIR__.'/includes/reporters/woocommerce-legacy.php');
		return new Ninjalytics\Reporters\WooCommerce\Legacy();
	} else if (function_exists('EDD')) {
		include_once(__DIR__.'/includes/reporters/edd.php');
		return new Ninjalytics\Reporters\EDD();
	} else throw new Exception();
}

function ninjalytics_get_groupby_fields()
{
	global $ninjalytics_groupby_fields;
	if (!isset($ninjalytics_groupby_fields)) {
		global $wpdb;
		
		$ninjalytics_groupby_fields = [];
		$reporter = ninjalytics_get_active_reporter();
	
		foreach ($reporter->getVirtualOrderMeta() as $fieldId => $field) {
			$ninjalytics_groupby_fields['o_'.$fieldId] = $fieldId;
		}
		
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$fields = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT meta_key FROM (
									SELECT meta_key
									FROM %i ometa
									JOIN %i orders ON (ometa.%i = orders.%i)
									WHERE orders.%i=%s
									ORDER BY orders.%i DESC
									LIMIT 10000
								) fields', $reporter->ordersMetaTable, $reporter->ordersTable, $reporter->ordersMetaOrderIdColumn, $reporter->ordersIdColumn, $reporter->ordersTypeColumn, $reporter->orderType, $reporter->ordersIdColumn));
		sort($fields);
		foreach ($fields as $field) {
			$ninjalytics_groupby_fields['o_'.$field] = $field;
		}
		$ninjalytics_groupby_fields['o_builtin::order_date'] = 'Order Date';
		$ninjalytics_groupby_fields['o_builtin::order_day'] = 'Order Day';
		$ninjalytics_groupby_fields['o_builtin::order_month'] = 'Order Month';
		$ninjalytics_groupby_fields['o_builtin::order_quarter'] = 'Order Quarter';
		$ninjalytics_groupby_fields['o_builtin::order_year'] = 'Order Year';
		$ninjalytics_groupby_fields['o_builtin::order_source'] = 'Order Source';
		
		$fields = ninjalytics_get_order_item_fields();
		foreach ($fields as $field) {
			$ninjalytics_groupby_fields['i_'.$field] = $field;
		}
		
		
		$ninjalytics_groupby_fields['i_builtin::item_price'] = 'Item Price';
		
		// hm-product-sales-report-pro.php
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$productFields = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT meta_key FROM (
											SELECT meta_key
											FROM '.$wpdb->prefix.'postmeta
											JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
											WHERE post_type=%s
											ORDER BY ID DESC
											LIMIT 10000
										) fields', $reporter->productPostType));
		
		foreach ($productFields as $productField) {
			$ninjalytics_groupby_fields[ 'p_'.$productField ] = $productField;
		}
	}
	return $ninjalytics_groupby_fields;
}

function ninjalytics_get_order_item_fields($noCache=false, $lineItemOnly=false)
{
	global $wpdb;
	
	if (!$noCache) {
		$fields = get_transient('hm_psrp_fields');
		$fieldsKey = $lineItemOnly ? 'line_item_meta' : 'order_item_meta';
		if (isset($fields[$fieldsKey])) {
			return $fields[$fieldsKey];
		}
	}
		
	if (!wp_next_scheduled('ninjalytics_update_field_cache')) {
		wp_schedule_event(
			time(),
			'daily',
			'ninjalytics_update_field_cache'
		);
	}
	
	$reporter = ninjalytics_get_active_reporter();
	
	$params = [$reporter->orderItemsMetaTable];
	if ($lineItemOnly) {
		$params[] = $reporter->orderItemsTable;
		$params[] = $reporter->orderItemsIdColumn;
		$params[] = $reporter->orderItemsMetaItemIdColumn;
		$params[] = $reporter->orderItemsTypeColumn;
		$params[] = $reporter->productOrderItemsType;
	}
	if (!$noCache) {
		$params[] = $reporter->orderItemsMetaItemIdColumn;
	}
	
	$fields = array_diff(
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- passing an array of parameters is allowed
			$wpdb->prepare('SELECT DISTINCT meta_key FROM (
									SELECT meta_key
									FROM %i im
									'
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- conditional sql
									.($lineItemOnly ? 'JOIN %i i ON (i.%i=im.%i)' : '').'
									'
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- conditional sql
									.($lineItemOnly ? 'WHERE i.%i=%s' : '').'
									'
									// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- conditional sql
									.($noCache ? '' : 'ORDER BY im.%i DESC LIMIT 1000').
								') fields', $params)
						),
		$reporter->hiddenOrderItemFields
	);
								
	sort($fields);
	return $fields;
}

function ninjalytics_update_field_cache()
{
// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Required for potentially long task
	set_time_limit(3600);
	$fields = [
		'order_item_meta' => ninjalytics_get_order_item_fields(true),
		'line_item_meta' => ninjalytics_get_order_item_fields(true, true)
	];
	set_transient('hm_psrp_fields', $fields, DAY_IN_SECONDS * 2);
}
add_action('ninjalytics_update_field_cache', 'ninjalytics_update_field_cache');

function ninjalytics_get_query_field($objectType, $fieldName) {
	switch ($objectType) {
		case 'order_item':
			return 'order_items.'.$fieldName;
		case 'order_item_meta':
			return 'order_item_meta_'.$fieldName.'.meta_value';
	}
}

/**
 * Get list of shipping method filter options: instance titles and a "no shipping" sentinel.
 */
function ninjalytics_get_order_shipping_filter_options()
{
    $shippingMethods = ['-1' => '(no shipping)'];
    if (class_exists('WC_Shipping_Zones')) {
        foreach (\WC_Shipping_Zones::get_zones() as $zone) {
            foreach ($zone['shipping_methods'] as $method) {
                $methodTitle = $method->get_title();
                if ($methodTitle !== '-1') {
                    $shippingMethods[strtolower($methodTitle)] = $methodTitle;
                }
            }
        }
        
        // Also check the "Rest of the World" zone
        $restOfWorldZone = \WC_Shipping_Zones::get_zone(0);
        if ($restOfWorldZone) {
            foreach ($restOfWorldZone->get_shipping_methods() as $method) {
                $methodTitle = $method->get_title();
                if ($methodTitle !== '-1') {
                    $shippingMethods[strtolower($methodTitle)] = $methodTitle;
                }
            }
        }
		
		
    }
    return $shippingMethods;
}

function ninjalytics_filter_report_query($sql)
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
	
	$reporter = ninjalytics_get_active_reporter();
	$standardFields = $reporter->getStandardFields();
	$hasSeparateVariations = !empty($_POST['variations']) && $reporter->supports(PlatformFeatures::VARIATIONS);
	
	$sql['select'] = preg_replace('/PSRSUM\\((.+)\\)/iU', 'SUM(ROUND($1, 2))', $sql['select']);
	
	if ($hasSeparateVariations) {
		$variationIdField = ninjalytics_get_query_field($standardFields['variation_id'][0], $standardFields['variation_id'][1]);
		
		$sql['select'] = str_ireplace(
			$variationIdField.' as variation_id',
			'IF('.$variationIdField.', '.$variationIdField.', 0) as variation_id',
			$sql['select']
		);
	}
	
	$productIdField = ninjalytics_get_query_field($standardFields['product_id'][0], $standardFields['product_id'][1]);
	$hasProductIdField = strpos($sql['select'], $productIdField) !== false;
	
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

function ninjalytics_dynamic_title($title, $vars)
{
	global $ninjalytics_dt_vars;
	$ninjalytics_dt_vars = $vars;
	$title = preg_replace_callback('/\[([a-z_]+)( .+)?\]/U', 'ninjalytics_dynamic_title_cb', $title);
	unset($ninjalytics_dt_vars);
	return $title;
}

function ninjalytics_dynamic_title_cb($field)
{
	global $ninjalytics_dt_vars;
	switch ($field[1]) {
		case 'preset':
			return $ninjalytics_dt_vars['preset'];
		case 'start':
			if (!isset($ninjalytics_dt_vars['start'])) {
				return '(all time)';
			}
			$date = $ninjalytics_dt_vars['start'];
			break;
		case 'end':
			if (!isset($ninjalytics_dt_vars['end'])) {
				return '(all time)';
			}
			$date = $ninjalytics_dt_vars['end'];
			break;
		case 'created':
			$date = $ninjalytics_dt_vars['now'];
			break;
		default:
			return $field[0];
	}
	
	// Field is a date
	return date_i18n((empty($field[2]) ? get_option('date_format') : substr($field[2], 1)), $date);
}

function ninjalytics_get_wc_membership_plans()
{
	$plans = [];
	$postsArgs = [
		'post_type' => 'wc_membership_plan',
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
		'nopaging' => true
	];
	$planPosts = get_posts($postsArgs);
	if ($planPosts) {
		foreach ($planPosts as $planPost) {
			$plans[$planPost->ID] = $planPost->post_title;
		}
	}
	return $plans;
}

function ninjalytics_on_deactivate()
{
	wp_unschedule_event(
		wp_next_scheduled('ninjalytics_update_field_cache'),
		'ninjalytics_update_field_cache'
	);
}

register_deactivation_hook(__FILE__, 'ninjalytics_on_deactivate');

/*
	The following function contains code copied from WooCommerce; see license/woocommerce-license.txt for copyright and licensing information
*/
function ninjalytics_getReportData($wc_report, $baseFields, $product_ids, $startDate = null, $endDate = null, $refundOrders = false)
{
	
// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
	global $wpdb, $hm_wc_report_extra_sql;
	$hm_wc_report_extra_sql = array();

	$groupByProducts = ((int) $_POST['disable_product_grouping'] ?? 0) <= 0;
	$intermediateRounding = !empty( $_POST['intermediate_rounding'] );
	
	$standardFields = $wc_report->getStandardFields();
	$reportVariations = $wc_report->supports(PlatformFeatures::VARIATIONS) && !empty($_POST['variations']);
	
	
	// Based on woocoommerce/includes/admin/reports/class-wc-report-sales-by-product.php
	$dataParams = array(
		
		// Following code provided by and copyright Daniel von Mitschke, released under GNU General Public License (GPL) version 2 or later, used under GPL version 3 or later (see license/LICENSE.TXT)
		// Modified by Jonathan Hall
		$standardFields['order_item_name'][1] => array(
			'type' => $standardFields['order_item_name'][0],
			'function' => 'GROUP_CONCAT',
			'distinct' => true,
			'join_type' => 'LEFT',
			'name' => 'product_name'
		),
		// End code provided by Daniel von Mitschke
		$standardFields['quantity'][1] => array(
			'type' => $standardFields['quantity'][0],
			'order_item_type' => 'line_item',
			'function' => 'SUM',
			'join_type' => 'LEFT',
			'name' => 'quantity'
		),
		$standardFields['line_subtotal'][1] => array(
			'type' => $standardFields['line_subtotal'][0],
			'order_item_type' => 'line_item',
			'function' => $intermediateRounding ? 'PSRSUM' : 'SUM',
			'join_type' => 'LEFT',
			'name' => 'gross'
		),
		$standardFields['line_total'][1] => array(
			'type' => $standardFields['line_total'][0],
			'order_item_type' => 'line_item',
			'function' => $intermediateRounding ? 'PSRSUM' : 'SUM',
			'join_type' => 'LEFT',
			'name' => 'gross_after_discount'
		),
		$standardFields['line_tax'][1] => array(
			'type' => $standardFields['line_tax'][0],
			'order_item_type' => 'line_item',
			'function' => $intermediateRounding ? 'PSRSUM' : 'SUM',
			'join_type' => 'LEFT',
			'name' => 'taxes'
		)
	);
	
	if ($wc_report->supports(PlatformFeatures::LINE_ITEM_ADJUSTMENTS) && !empty($_POST['adjustments'])) {
		$dataParams['order_item_adjustment.subtotal'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => 'line_item',
			'function' => $intermediateRounding ? 'PSRSUM' : 'SUM',
			'join_type' => 'LEFT',
			'name' => 'adjustment_subtotal'
		);
		$dataParams['order_item_adjustment.total'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => 'line_item',
			'function' => $intermediateRounding ? 'PSRSUM' : 'SUM',
			'join_type' => 'LEFT',
			'name' => 'adjustment_total'
		);
		$dataParams['order_item_adjustment.tax'] = array(
			'type' => 'order_item_adjustment',
			'order_item_type' => 'line_item',
			'function' => $intermediateRounding ? 'PSRSUM' : 'SUM',
			'join_type' => 'LEFT',
			'name' => 'adjustment_tax'
		);
	}
	
	
	if ( $groupByProducts || $_POST['disable_product_grouping'] == 2 ) {
		$dataParams[$standardFields['product_id'][1]] = array(
			'type' => $standardFields['product_id'][0],
			'order_item_type' => 'line_item',
			'function' => $_POST['disable_product_grouping'] == -1 ? 'GROUP_CONCAT' : '',
			'join_type' => 'LEFT',
			'name' => 'product_id'
		);
	}
	
	if ($reportVariations && $groupByProducts) {
		$dataParams[$standardFields['variation_id'][1]] = array(
			'type' => $standardFields['variation_id'][0],
			'order_item_type' => 'line_item',
			'function' => $_POST['disable_product_grouping'] == -1 ? 'GROUP_CONCAT' : '',
			'join_type' => 'LEFT',
			'name' => 'variation_id'
		);
	}
	if ( in_array('builtin::line_item_count', $baseFields) || ninjalytics_hasTaxBreakoutField($baseFields) ) {
		$dataParams[$wc_report->orderItemsIdColumn] = array(
			'type' => 'order_item',
			'order_item_type' => 'line_item',
			'function' => 'GROUP_CONCAT',
			'join_type' => 'LEFT',
			'name' => 'order_item_ids'
		);
	}
	if ( in_array('builtin::avg_order_total', $baseFields) ) {
		$dataParams[$standardFields['order_total'][1]] = array(
			'type' => $standardFields['order_total'][0],
			'function' => 'AVG',
			'join_type' => 'LEFT',
			'name' => 'avg_order_total'
		);
	}
	foreach ($baseFields as $field) {
		if (!empty($_POST['enable_custom_segments']) && $field == 'builtin::groupby_field') {
			
			$groupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
			
			if ( !empty($groupByField) && $groupByField != 'i_builtin::item_price' ) {
				
				if (in_array($groupByField, array('o_builtin::order_month', 'o_builtin::order_quarter', 'o_builtin::order_year', 'o_builtin::order_date', 'o_builtin::order_day'))) {
					switch ($groupByField) {
						case 'o_builtin::order_month':
							$sqlFunction = 'MONTH';
							break;
						case 'o_builtin::order_quarter':
							$sqlFunction = 'QUARTER';
							break;
						case 'o_builtin::order_year':
							$sqlFunction = 'YEAR';
							break;
						case 'o_builtin::order_day':
							$sqlFunction = 'DAY';
							break;
						default:
							$sqlFunction = 'DATE';
					}
					$dataParams[$standardFields['order_date'][1]] = array(
						'type' => $standardFields['order_date'][0],
						'order_item_type' => 'line_item',
						'function' => $sqlFunction,
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
					);
				} else if ($wc_report->supports(PlatformFeatures::ORDER_SOURCE) && $groupByField == 'o_builtin::order_source') {
					// Replicated in shipping data function below
					$dataParams['_wc_order_attribution_source_type'] = [
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_field'
					];
					$dataParams['_wc_order_attribution_utm_source'] = [
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_fieldb'
					];
				} else if ($groupByField[0] != 'p') {
					$fieldName = esc_sql(substr($groupByField, 2));
					
					$dataParams[$fieldName] = array(
						'type' => ($groupByField[0] == 'i' ? 'order_item_meta' : 'meta'),
						'order_item_type' => 'line_item',
						'function' => '',
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
					);
					
				}
				
			}
		}
	}
	
	$where = array();
	$where_meta = array();
	if ($product_ids != null) {
		// If there are more than 10,000 product IDs, they should not be filtered in the SQL query
		if ( count($product_ids) > 10000 && empty($_POST['disable_product_grouping']) ) {
			$productIdsPostFilter = true;
		} else {
			$where_meta[] = array(
				'type' => $standardFields['product_id'][0],
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key' => $standardFields['product_id'][1],
				'operator' => 'IN',
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => $product_ids
			);
		}
	}
	if (!empty($_POST['exclude_free'])) {
		$where_meta[] = array(
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key' => $standardFields['line_total'][1],
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value' => 0,
			'operator' => '!=',
			'type' => $standardFields['line_total'][0]
		);
	}
	
	// Date range filtering
	$where[] = array(
		'key' => $wc_report->ordersDateColumn,
		'operator' => '>=',
		'value' => get_gmt_from_date(gmdate('Y-m-d H:i:s', $startDate))
	);
	$where[] = array(
		'key' => $wc_report->ordersDateColumn,
		'operator' => '<=',
		'value' => get_gmt_from_date(gmdate('Y-m-d H:i:s', $endDate))
	);
	
	$groupBy = [];
	
	if ( $_POST['disable_product_grouping'] == -1 ) {
		$groupBy[] = 'product_sku';
	} else if ($groupByProducts) {
		$groupBy[] = 'product_id';
		if ($reportVariations) {
			$groupBy[] = 'variation_id';
		}
	} else if ( $_POST['disable_product_grouping'] == 2 ) {
		$groupBy[] = 'product_category';
	}
	
	if (!empty($_POST['enable_custom_segments']) && !empty($_POST['groupby'])) {
		switch ($_POST['groupby']) {
			case 'i_builtin::item_price':
				$groupBy[] = 'ROUND(order_item_meta__line_subtotal.meta_value / order_item_meta__qty.meta_value, 2)';
				break;
			case 'o_builtin::order_source':
				// Replicated for shipping below
				$primaryField = 'groupby_field';
				$groupBy[] = $primaryField;
				$groupBy[] = $primaryField.'b';
				break;
			default:
				$groupBy[] = 'groupby_field';
		}
	}
	
	// Address issue with order_items JOIN with order_item_type being overridden
	foreach ($dataParams as $fieldKey => $field) {
		if ($field['type'] == 'order_item_meta' && isset($field['order_item_type'])) {
			unset($dataParams[$fieldKey]);
			$dataParams[$fieldKey] = $field; // move this key to the end of the array
			break;
		}
	}
	
	$reportOptions = array(
		'data' => $dataParams,
		'nocache' => true,
		'query_type' => 'get_results',
		'group_by' => implode(',', $groupBy),
		'filter_range' => false,
		'order_types' => array($refundOrders ? $wc_report->refundOrderType : $wc_report->orderType),
		/*'order_status' => $orderStatuses,*/ // Order status filtering is set via filter
		'where_meta' => $where_meta
	);
	
	if (!empty($_POST['hm_psr_debug'])) {
		$reportOptions['debug'] = true;
	}
	
	if (!empty($where)) {
		$reportOptions['where'] = $where;
	}
	
	// Order status filtering
	$statusesStr = '';
	

// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Individual values unslashed/sanitized below	
	foreach (($_POST['order_statuses'] ?? []) as $i => $orderStatus) {
		$statusesStr .= ($i ? ',\'' : '\'').esc_sql(sanitize_text_field(wp_unslash($orderStatus))).'\'';
	}
	
	$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND posts.'.$wc_report->ordersStatusColumn.
		($refundOrders ? '=\''.esc_sql($wc_report->completedOrderStatus).'\' AND EXISTS(SELECT 1 FROM '.$wc_report->ordersTable.' WHERE '.$wc_report->ordersIdColumn.'=posts.'.
	$wc_report->ordersParentIdColumn.' AND '.$wc_report->ordersStatusColumn.' IN('.$statusesStr.'))' :
		' IN('.$statusesStr.')');
	
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	@$wpdb->query('SET SESSION sort_buffer_size=512000');
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ($wpdb->query('SET SESSION group_concat_max_len=2000000000') === false) {
		throw new Exception();
	}
	
	add_filter('sanitize_key', 'ninjalytics_fixSanitizeKey');
	$result = $wc_report->get_order_report_data($reportOptions);
	remove_filter('sanitize_key', 'ninjalytics_fixSanitizeKey');
	
	// Do post-query product ID filtering, if necessary
	if (!empty($result) && !empty($productIdsPostFilter)) {
		foreach ($result as $key => $product) {
			if (!in_array($product->product_id, $product_ids)) {
				unset($result[$key]);
			}
		}
	}
	
	if ($wc_report->supports(PlatformFeatures::LINE_ITEM_ADJUSTMENTS) && !empty($_POST['adjustments'])) {
		foreach ($result as $row) {
			$row->gross += $row->adjustment_subtotal;
			$row->gross_after_discount += $row->adjustment_total;
			$row->taxes += $row->adjustment_tax;
		}
	}
	
	return $result;
	
// phpcs:enable WordPress.Security.NonceVerification.Missing
}

function ninjalytics_hasTaxBreakoutField($fields) {
	foreach ($fields as $fieldId) {
		if (substr($fieldId, 0, 15) == 'builtin::taxes_') {
			return true;
		}
	}
	return false;
}

function ninjalytics_fixSanitizeKey($sanitized) {
	return str_replace('-', '_', $sanitized);
}

/*
	The following function contains code copied from from WooCommerce; see license/woocommerce-license.txt for copyright and licensing information
*/
function ninjalytics_getShippingReportData($wc_report, $baseFields, $startDate, $endDate, $taxes = false, $refundOrders = false)
{
	
// phpcs:disable WordPress.Security.NonceVerification.Missing -- This is a helper function, to be called after nonce is checked as needed
	global $wpdb, $hm_wc_report_extra_sql;
	$hm_wc_report_extra_sql = array();
	
	$standardFields = $wc_report->getStandardFields();

// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- int cast
	$groupByProducts = (int) ($_POST['disable_product_grouping'] ?? 0) <= 0;
	$intermediateRounding = !empty( $_POST['intermediate_rounding'] );

	// Based on woocoommerce/includes/admin/reports/class-wc-report-sales-by-product.php
	
	$dataParams = array(
		'cost' => array(
			'type' => 'order_item_meta',
			'order_item_type' => 'shipping',
			'function' => $intermediateRounding ? 'PSRSUM' : 'SUM',
			'join_type' => 'LEFT',
			'name' => 'gross'
		)
	);
	if ($groupByProducts) {
		$dataParams['method_id'] = array(
			'type' => 'order_item_meta',
			'order_item_type' => 'shipping',
			'function' => '',
			'join_type' => 'LEFT',
			'name' => 'product_id'
		);
	}
	if ( !$refundOrders || in_array('builtin::line_item_count', $baseFields) || $taxes || ninjalytics_hasTaxBreakoutField($baseFields) ) {
		$dataParams[$wc_report->orderItemsIdColumn] = array(
			'type' => 'order_item',
			'order_item_type' => 'shipping',
			'function' => 'GROUP_CONCAT',
			'join_type' => 'LEFT',
			'name' => 'order_item_ids'
		);
	}
	
	if ( in_array('builtin::avg_order_total', $baseFields) ) {
		$dataParams['_order_total'] = array(
			'type' => 'meta',
			'function' => 'AVG',
			'join_type' => 'LEFT',
			'name' => 'avg_order_total'
		);
	}
	
	foreach ($baseFields as $field) {
		if (!empty($_POST['enable_custom_segments']) && $field == 'builtin::groupby_field') {
			
			$groupByField = sanitize_text_field(wp_unslash($_POST['groupby'] ?? ''));
			
			if (!empty($groupByField) && $groupByField != 'i_builtin::item_price') {
				if (in_array($groupByField, array('o_builtin::order_month', 'o_builtin::order_quarter', 'o_builtin::order_year', 'o_builtin::order_date', 'o_builtin::order_day'))) {
					switch ($groupByField) {
						case 'o_builtin::order_month':
							$sqlFunction = 'MONTH';
							break;
						case 'o_builtin::order_quarter':
							$sqlFunction = 'QUARTER';
							break;
						case 'o_builtin::order_year':
							$sqlFunction = 'YEAR';
							break;
						case 'o_builtin::order_day':
							$sqlFunction = 'DAY';
							break;
						default:
							$sqlFunction = 'DATE';
					}
					$dataParams[$standardFields['order_date'][1]] = array(
						'type' => $standardFields['order_date'][0],
						'order_item_type' => 'shipping',
						'function' => $sqlFunction,
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
					);
				} else if ($groupByField == 'o_builtin::order_source') {
					// Replicated in non-shipping data function above
					$dataParams['_wc_order_attribution_source_type'] = array(
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_field'
					);
					$dataParams['_wc_order_attribution_utm_source'] = array(
						'type' => 'meta',
						'join_type' => 'LEFT',
						'function' => '',
						'name' => 'groupby_fieldb'
					);
				} else if ($groupByField[0] != 'p') {
					$fieldName = esc_sql(substr($groupByField, 2));
					$dataParams[$fieldName] = array(
						'type' => ($groupByField[0] == 'i' ? 'order_item_meta' : 'meta'),
						'order_item_type' => 'shipping',
						'function' => '',
						'join_type' => 'LEFT',
						'name' => 'groupby_field'
					);
				}
			}
		}
	}
	
	$groupBy = [];
	
	if ($groupByProducts) {
		$groupBy[] = 'product_id';
	}
	
	if (!empty($_POST['enable_custom_segments']) && !empty($_POST['groupby'])) {
		switch ($_POST['groupby']) {
			case 'i_builtin::item_price':
				$groupBy[] = '(order_item_meta_cost.meta_value * 1)';
				break;
			case 'o_builtin::order_source':
				// Replicated for regular products above
				$groupBy[] = 'groupby_field';
				$groupBy[] = 'groupby_fieldb';
				break;
			default:
				$groupBy[] = 'groupby_field';
		}
	}
	
	// Address issue with order_items JOIN with order_item_type being overridden
	foreach ($dataParams as $fieldKey => $field) {
		if ($field['type'] == 'order_item_meta' && isset($field['order_item_type'])) {
			unset($dataParams[$fieldKey]);
			$dataParams[$fieldKey] = $field; // move this key to the end of the array
			break;
		}
	}
	
	$reportParams = array(
		'data' => $dataParams,
		'nocache' => true,
		'query_type' => 'get_results',
		'group_by' => implode(',', $groupBy),
		'filter_range' => false,
		'order_types' => array($refundOrders ? $wc_report->refundOrderType : $wc_report->orderType)
	);
	
	if (!empty($_POST['hm_psr_debug'])) {
		$reportParams['debug'] = true;
	}
	
	// Date range filtering
	$reportParams['where'] = array(
		array(
			'key' => $wc_report->ordersDateColumn,
			'operator' => '>=',
			'value' => get_gmt_from_date(gmdate('Y-m-d H:i:s', $startDate))
		),
		array(
			'key' => $wc_report->ordersDateColumn,
			'operator' => '<=',
			'value' => get_gmt_from_date(gmdate('Y-m-d H:i:s', $endDate))
		)
	);
	
	// Order status filtering
	$statusesStr = '';

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Individual values unslashed/sanitized below	
	foreach (($_POST['order_statuses'] ?? []) as $i => $orderStatus) {
		$statusesStr .= ($i ? ',\'' : '\'').esc_sql(sanitize_text_field(wp_unslash($orderStatus))).'\'';
	}
	
	$hm_wc_report_extra_sql['where'] = (isset($hm_wc_report_extra_sql['where']) ? $hm_wc_report_extra_sql['where'] : '').' AND posts.'.$wc_report->ordersStatusColumn.
		($refundOrders ? '=\''.esc_sql($wc_report->completedOrderStatus).'\' AND EXISTS(SELECT 1 FROM '.$wc_report->ordersTable.' WHERE '.$wc_report->ordersIdColumn.'=posts.'.
	$wc_report->ordersParentIdColumn.' AND '.$wc_report->ordersStatusColumn.' IN('.$statusesStr.'))' :
		' IN('.$statusesStr.')');
		
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	@$wpdb->query('SET SESSION sort_buffer_size=512000');
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ($wpdb->query('SET SESSION group_concat_max_len=2000000000') === false) {
		throw new Exception();
	}
	
	add_filter('sanitize_key', 'ninjalytics_fixSanitizeKey');
	$result = $wc_report->get_order_report_data($reportParams);
	remove_filter('sanitize_key', 'ninjalytics_fixSanitizeKey');
	
	if ($refundOrders) {
		foreach ($result as $shipping) {
			$shipping->quantity = 0;
		}
	}
	
	if ($taxes) {
		
		$hasShippingItemClass = class_exists('WC_Order_Item_Shipping'); // WC 3.0+
		
		$reportParams['data'] = array(
			'method_id' => array(
				'type' => 'order_item_meta',
				'order_item_type' => 'shipping',
				'function' => '',
				'name' => 'product_id'
			)
		);
		if ($hasShippingItemClass) {
			$reportParams['data'][$wc_report->orderItemsIdColumn] = array(
				'type' => 'order_item',
				'order_item_type' => 'shipping',
				'function' => '',
				'name' => 'order_item_id'
			);
		} else {
			$reportParams['data']['taxes'] = array(
				'type' => 'order_item_meta',
				'order_item_type' => 'shipping',
				'function' => '',
				'name' => 'taxes'
			);
		}
		$reportParams['group_by'] = '';
		
		add_filter('sanitize_key', 'ninjalytics_fixSanitizeKey');
		$taxResult = $wc_report->get_order_report_data($reportParams);
		remove_filter('sanitize_key', 'ninjalytics_fixSanitizeKey');
		
		foreach ($result as $shipping) {
			if ($groupByProducts) {
				$shipping->taxes = 0;
				foreach ($taxResult as $i => $taxes) {
					if ($taxes->product_id == $shipping->product_id) {
						if ($hasShippingItemClass) {
							$oi = new WC_Order_Item_Shipping($taxes->order_item_id);
							$shipping->taxes += $oi->get_total_tax();
						} else {
							$taxArray = @unserialize($taxes->taxes);
							if (!empty($taxArray)) {
								foreach ($taxArray as $taxItem) {
									$shipping->taxes += $taxItem;
								}
							}
						}
						unset($taxResult[$i]);
					}
				}
			} else {
				$shipping->taxes = '';
			}
		}
	}
	
	return $result;

// phpcs:enable WordPress.Security.NonceVerification.Missing	
}

function ninjalytics_getFormattedVariationAttributes($product)
{
	if (is_numeric($product)) {
		$varIds = [$product];
	} else if (empty($product->_variation_ids)) {
		return '';
	} else {
		$varIds = $product->_variation_ids;
	}
	
	return implode('; ', array_unique(array_map(function($varId) {
		if (function_exists('wc_get_product_variation_attributes')) {
			$attr = wc_get_product_variation_attributes($varId);
		} else {
			$product = wc_get_product($varId);
			if (empty($product))
				return '';
			$attr = $product->get_variation_attributes();
		}
		foreach ($attr as $i => $v) {
			if ($v === '')
				unset($attr[$i]);
		}
		asort($attr);
		return implode(', ', $attr);
	}, $varIds)));
}

function ninjalytics_getCustomFieldNames()
{
	global $wpdb;
	$reporter = ninjalytics_get_active_reporter();
	
	if (!isset($GLOBALS['ninjalytics_customFieldNames'])) {
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$customFields = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT meta_key FROM (
											SELECT meta_key
											FROM '.$wpdb->prefix.'postmeta
											JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
											WHERE post_type=%s
											ORDER BY ID DESC
											LIMIT 10000
										) fields', $reporter->productPostType), 0);
		
		$GLOBALS['ninjalytics_customFieldNames'] = [
			'Product' => array_combine($customFields, $customFields),
			'Product Taxonomies' => array(),
		];
		
		
		foreach (get_object_taxonomies($reporter->productPostType) as $taxonomy) {
			$GLOBALS['ninjalytics_customFieldNames']['Product Taxonomies']['taxonomy::'.$taxonomy] = $taxonomy;
		}
		
		if ( $reporter->supports(PlatformFeatures::VARIATIONS) ) {
			$GLOBALS['ninjalytics_customFieldNames']['Product Variation'] = [];
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$variationFields = $wpdb->get_col('SELECT DISTINCT meta_key FROM (
													SELECT meta_key
													FROM '.$wpdb->prefix.'postmeta
													JOIN '.$wpdb->prefix.'posts ON (post_id=ID)
													WHERE post_type="product_variation"
													ORDER BY ID DESC
													LIMIT 10000
												) fields', 0);
			foreach ($variationFields as $variationField)
				$GLOBALS['ninjalytics_customFieldNames']['Product Variation']['variation::'.$variationField] = 'Variation '.$variationField;
		}
		
		$GLOBALS['ninjalytics_customFieldNames']['Order Item'] = [];
		$skipOrderItemFields = array('_qty', '_line_subtotal', '_line_total', '_line_tax', '_line_tax_data', '_tax_class', '_refunded_item_id');
		$orderItemFields = ninjalytics_get_order_item_fields(false, true);
		foreach ($orderItemFields as $orderItemField) {
			if (!in_array($orderItemField, $skipOrderItemFields) && !empty($orderItemField)) {
				$GLOBALS['ninjalytics_customFieldNames']['Order Item']['order_item_total::'.$orderItemField] = 'Total Order Item '.$orderItemField;
			}
		}
	}
	return $GLOBALS['ninjalytics_customFieldNames'];
}

function ninjalytics_getAddonFields()
{
	if (!isset($GLOBALS['ninjalytics_addonFields'])) {
		$GLOBALS['ninjalytics_addonFields'] = array_merge(apply_filters('hm_psr_addon_fields', array()), apply_filters('ninjalytics_addon_fields', array()));
	}
	return $GLOBALS['ninjalytics_addonFields'];
}

function ninjalytics_admin_notice() {
	if ( current_user_can('view_woocommerce_reports') && !get_user_meta(get_current_user_id(), 'ninjalytics_admin_notice_hide', true) ) {
?>
    <div id="ninjalytics-admin-notice" class="berrypress-notice berrypress-notice-info berrypress-notice-headline notice is-dismissible">

        <span class="berrypress-notice-image"><img src="<?php echo(esc_url(plugin_dir_url(__FILE__).'includes/berrypress-admin-framework/assets/addons-icons/ninjalytics.png')); ?>" alt="Ninjalytics logo" width="40" height="40"></span>
        <div>
            <h3>Product Sales Report is now Ninjalytics!</h3>
            <p>
                The next generation of reporting for WooCommerce is here! Ninjalytics, by BerryPress, is the official replacement for Product Sales Report, with tons of new features (charts, segmentation, shipping, multiple presets, and more!) and backwards compatibility with your existing report configuration. <a href="admin.php?page=ninjalytics&amp;tab=about">Read more</a> or <a href="admin.php?page=ninjalytics">get started now</a>!
            </p>
        </div>
		<script>jQuery('#ninjalytics-admin-notice').on('click', '.notice-dismiss', function() { jQuery.post( location.href, {wp_screen_options: {option: 'ninjalytics_admin_notice_hide', value: 1}, screenoptionnonce: '<?php echo(esc_js(wp_create_nonce( 'screen-options-nonce'))); ?>'  } ); });</script>
	</div>
<?php
	}
}

add_action('admin_notices', 'ninjalytics_admin_notice');
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce check would be done before setting the screen option, this is just for performance to avoid adding the hook unnecessarily
if (!empty($_POST['wp_screen_options'])) {
	add_filter('set_screen_option_ninjalytics_admin_notice_hide', function() { return 1; });
}
