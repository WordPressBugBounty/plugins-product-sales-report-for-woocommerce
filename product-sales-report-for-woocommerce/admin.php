<?php if (!defined('ABSPATH')) exit; ?>

<div id="ags-psrf-settings-container">
    <div id="ags-psrf-settings">
        <div id="ags-psrf-settings-header">
            <div class="ags-psrf-settings-logo">
                <h3><?php echo HM_PSRF_ITEM_NAME; ?></h3>
            </div>
            <div id="ags-psrf-settings-header-links">
                <a id="ags-psrf-settings-header-link-review" href="https://wordpress.org/plugins/product-sales-report-for-woocommerce/#reviews"
                   target="_blank"><?php echo esc_html__('Leave Us A Review', 'product-sales-report-for-woocommerce') ?></a>
                <a id="ags-psrf-settings-header-link-upgrade" href="https://wpzone.co/product/product-sales-report-pro-for-woocommerce/?utm_source=export-order-items-for-woocommerce&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link"
                   target="_blank"><?php echo esc_html__('Upgrade To Pro', 'product-sales-report-for-woocommerce') ?></a>
            </div>
        </div>

        <ul id="ags-psrf-settings-tabs">
            <li class="ags-psrf-settings-active"><a href="#settings"><?php esc_html_e('Report Settings', 'product-sales-report-for-woocommerce'); ?></a></li>
            <li><a href="#addons"><?php esc_html_e('Addons', 'product-sales-report-for-woocommerce'); ?></a></li>
        </ul>

	    <?php
	    // Check for WooCommerce
	    if (!class_exists('WooCommerce')) {
		    echo('<div id="ags-psrf-settings-tabs-content"><p class="ags-psrf-notification ags-psrf-notification-warning">' . esc_html__('This plugin requires that WooCommerce is installed and activated.', 'product-sales-report-for-woocommerce') . '</p></div></div></div>');
		    return;
	    } else if (!function_exists('wc_get_order_types')) {
		    echo('<div id="ags-psrf-settings-tabs-content"><p class="ags-psrf-notification ags-psrf-notification-warning">' . esc_html__('The Product Sales Report plugin requires WooCommerce 2.2 or higher. Please update your WooCommerce install.', 'product-sales-report-for-woocommerce') . '</p></div></div></div>');
		    return;
	    }

	    if (hm_psrf_is_hpos()) {
		    echo('<div id="ags-psrf-settings-tabs-content"><p class="ags-psrf-notification ags-psrf-notification-warning">');
		    esc_html_e('You are using High Performance Order Storage (HPOS) in WooCommerce. This plugin\'s HPOS support is currently in beta, so please validate the output as necessary.', 'product-sales-report-for-woocommerce');
		    echo('</p></div>');
	    }
	    ?>


        <div id="ags-psrf-settings-tabs-content">
            <?php
            // Print form
            echo('
            <div id="ags-psrf-settings-settings" class="ags-psrf-settings-active">
                <div class="ags-psrf-settings-left-area">
                    <form action="#hm_sbp_table" method="post">
                        <input type="hidden" name="hm_sbp_do_export" value="1" />
                ');
            wp_nonce_field('hm_sbpf_do_export');
            echo('
    
                 <div class="ags-psrf-settings-box">
                    <label for="hm_sbp_field_report_time">
                       <span class="label">' . esc_html__('Report Period', 'product-sales-report-for-woocommerce') . ':</span>
                        <select name="report_time" id="hm_sbp_field_report_time">
                            <option value="0d" ' . ($reportSettings['report_time'] == '0d' ? ' selected="selected"' : '') . '>' . esc_html__('Today', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="1d" ' . ($reportSettings['report_time'] == '1d' ? ' selected="selected"' : '') . '> ' . esc_html__('Yesterday', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="7d" ' . ($reportSettings['report_time'] == '7d' ? ' selected="selected"' : '') . '>' . esc_html__('Previous 7 days (excluding today)', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="30d" ' . ($reportSettings['report_time'] == '30d' ? ' selected="selected"' : '') . '>' . esc_html__('Previous 30 days (excluding today)', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="0cm" ' . ($reportSettings['report_time'] == '0cm' ? ' selected="selected"' : '') . '>' . esc_html__('Current calendar month', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="1cm" ' . ($reportSettings['report_time'] == '1cm' ? ' selected="selected"' : '') . '>' . esc_html__('Previous calendar month', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="+7d" ' . ($reportSettings['report_time'] == '+7d' ? ' selected="selected"' : '') . '>' . esc_html__('Next 7 days (future dated orders)', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="+30d" ' . ($reportSettings['report_time'] == '+30d' ? ' selected="selected"' : '') . '>' . esc_html__('Next 30 days (future dated orders)', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="+1cm" ' . ($reportSettings['report_time'] == '+1cm' ? ' selected="selected"' : '') . '>' . esc_html__('Next calendar month (future dated orders)', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="all" ' . ($reportSettings['report_time'] == 'all' ? ' selected="selected"' : '') . '>' . esc_html__('All time', 'product-sales-report-for-woocommerce') . '</option>
                            <option value="custom" ' . ($reportSettings['report_time'] == 'custom' ? ' selected="selected"' : '') . '>' . esc_html__('Custom date range', 'product-sales-report-for-woocommerce') . '</option>
                        </select>
                    </label>
                 </div>
                    
                <div class="ags-psrf-settings-box hm_sbp_custom_time">
                    <div class="ags-psrf-settings-multirow">
                        <label for="hm_sbp_field_report_start" class="ags-psrf-settings-title">
                           <span class="label">' . esc_html__('Start Date', 'product-sales-report-for-woocommerce') . ':</span>
                         </label>
                        <div class="ags-psrf-settings-content">
                            <input type="date" name="report_start" id="hm_sbp_field_report_start" value="' . $reportSettings['report_start'] . '" />
                        </div>
                    </div>
                </div>
                    
                <div class="ags-psrf-settings-box hm_sbp_custom_time">
                    <div class="ags-psrf-settings-multirow">
                        <label for="hm_sbp_field_report_end" class="ags-psrf-settings-title">
                           <span class="label">' . esc_html__('End Date', 'product-sales-report-for-woocommerce') . ':</span>
                         </label>
                        <div class="ags-psrf-settings-content">
                            <input type="date" name="report_end" id="hm_sbp_field_report_end" value="' . $reportSettings['report_end'] . '" />
                        </div>
                    </div>
                </div>
                    
                <div class="ags-psrf-settings-box">
                    <div class="ags-psrf-settings-cb-list">
                        <label class="ags-psrf-settings-title">
                           <span class="label">' . esc_html__('Include Orders With Status', 'product-sales-report-for-woocommerce') . ':</span>
                         </label>
                        <div class="ags-psrf-settings-content">');
            foreach (wc_get_order_statuses() as $status => $statusName) {
                echo('<label class="ags-psrf-settings-cb-list-item"><input type="checkbox" name="order_statuses[]" ' . (in_array($status, $reportSettings['order_statuses']) ? ' checked="checked"' : '') . ' value="' . $status . '" /> ' . $statusName . '</label>');
            }
            echo('</div>
                    </div>
                </div>
         
                <div class="ags-psrf-settings-box">
                    <div class="ags-psrf-settings-cb-list">
                        <label class="ags-psrf-settings-title">
                           <span class="label">' . esc_html__('Include Products', 'product-sales-report-for-woocommerce') . ':</span>
                         </label>
                        <div class="ags-psrf-settings-content">
                            <label class="ags-psrf-settings-cb-list-item">
                                <input type="radio" name="products" value="all" ' . ($reportSettings['products'] == 'all' ? ' checked="checked"' : '') . ' />' . esc_html__('All products', 'product-sales-report-for-woocommerce') . '
                            </label>
                            <label class="ags-psrf-settings-cb-list-item">
                               <input type="radio" name="products" value="cats" ' . ($reportSettings['products'] == 'cats' ? ' checked="checked"' : '') . ' />' . esc_html__('Products in categories', 'product-sales-report-for-woocommerce') . ':
                            </label>
                            <label class="ags-psrf-settings-cb-list-item ags-psrf-settings-cb-list-item-child">
                                <ul id="hm-psr-product-cats">');
            foreach (get_terms('product_cat', array('hierarchical' => false)) as $term) {
                echo('<li><label><input type="checkbox" name="product_cats[]" ' . (in_array($term->term_id, $reportSettings['product_cats']) ? ' checked="checked"' : '') . ' value="' . $term->term_id . '" /> ' . htmlspecialchars($term->name) . '</label></li>');
            }
            echo('</ul>
                            </label>
                            <label class="ags-psrf-settings-cb-list-item">
                                <input type="radio" name="products" value="ids" ' . ($reportSettings['products'] == 'ids' ? ' checked="checked"' : '') . ' />' . esc_html__('Product ID(s)', 'product-sales-report-for-woocommerce') . ':
                            </label> 
                            <label class="ags-psrf-settings-cb-list-item ags-psrf-settings-cb-list-item-child">
                                <input type="text" name="product_ids" placeholder="' . esc_html__('Use commas to separate multiple product IDs', 'product-sales-report-for-woocommerce') . '" value="' . htmlspecialchars($reportSettings['product_ids']) . '" />
                            </label>
                        </div>
                    </div>
                </div>
                    
                <div class="ags-psrf-settings-box">
                    <div class="ags-psrf-settings-cb-list">
                        <label class="ags-psrf-settings-title">
                           <span class="label">' . esc_html__('Product Variations', 'product-sales-report-for-woocommerce') . ':</span>
                         </label>
                        <div class="ags-psrf-settings-content">
                            <label class="ags-psrf-settings-cb-list-item">
                                <input type="radio" name="variations" value="0" ' . (empty($reportSettings['variations']) ? ' checked="checked"' : '') . ' class="variations-fld" />
                                ' . esc_html__('Group product variations together', 'product-sales-report-for-woocommerce') . '
                            </label>
                            <label class="ags-psrf-settings-cb-list-item ags-psrf-pro-feature">
                                <input type="radio" name="variations" value="1" disabled="disabled" class="variations-fld" />
                                ' . esc_html__('Report on each variation separately', 'product-sales-report-for-woocommerce') . ' <sup>PRO</sup> 
                            </label>
                        </div>
                    </div>
                </div>	
                    
                <div class="ags-psrf-settings-box">
                    <div class="ags-psrf-settings-multirow">
                        <label for="hm_sbp_field_orderby" class="ags-psrf-settings-title">
                           <span class="label">' . esc_html__('Sort By', 'product-sales-report-for-woocommerce') . ':</span>
                         </label>
                        <div class="ags-psrf-settings-content">
                            <select name="orderby" id="hm_sbp_field_orderby">
                                <option value="product_id" ' . ($reportSettings['orderby'] == 'product_id' ? ' selected="selected"' : '') . '>' . esc_html__('Product ID', 'product-sales-report-for-woocommerce') . '</option>
                                <option value="quantity" ' . ($reportSettings['orderby'] == 'quantity' ? ' selected="selected"' : '') . '>' . esc_html__('Quantity Sold', 'product-sales-report-for-woocommerce') . '</option>
                                <option value="gross" ' . ($reportSettings['orderby'] == 'gross' ? ' selected="selected"' : '') . '>' . esc_html__('Gross Sales', 'product-sales-report-for-woocommerce') . '</option>
                                <option value="gross_after_discount" ' . ($reportSettings['orderby'] == 'gross_after_discount' ? ' selected="selected"' : '') . '>' . esc_html__('Gross Sales (After Discounts)', 'product-sales-report-for-woocommerce') . '</option>
                            </select>
                            <select name="orderdir" id="hm_sbp_field_orderdir">
                                <option value="asc" ' . ($reportSettings['orderdir'] == 'asc' ? ' selected="selected"' : '') . '>' . esc_html__('Ascending', 'product-sales-report-for-woocommerce') . '</option>
                                <option value="desc" ' . ($reportSettings['orderdir'] == 'desc' ? ' selected="selected"' : '') . '>' . esc_html__('Descending', 'product-sales-report-for-woocommerce') . '</option>
                            </select>
                        </div>
                    </div>
                </div>
                    
               <div class="ags-psrf-settings-box">
                    <div class="ags-psrf-settings-cb-list">
                        <label class="ags-psrf-settings-title">
                            <span class="label">' . esc_html__('Report Fields', 'product-sales-report-for-woocommerce') . ':</span>
                        </label>
                        <div id="hm_psr_report_field_selection" class="ags-psrf-settings-content">');
            $fieldOptions2 = $fieldOptions;
            foreach ($reportSettings['fields'] as $fieldId) {
                if (!isset($fieldOptions2[$fieldId]))
                    continue;
                echo('<label class="ags-psrf-settings-cb-list-item"><input type="checkbox" name="fields[]" checked="checked" value="' . $fieldId . '" ' . (in_array($fieldId, array('variation_id', 'variation_attributes')) ? ' class="variation-field"' : '') . '/> ' . $fieldOptions2[$fieldId] . '</label>');
                unset($fieldOptions2[$fieldId]);
            }
            foreach ($fieldOptions2 as $fieldId => $fieldDisplay) {
                echo('<label class="ags-psrf-settings-cb-list-item"><input type="checkbox" name="fields[]" value="' . $fieldId . '" ' . (in_array($fieldId, array('variation_id', 'variation_attributes')) ? ' class="variation-field"' : '') . '/> ' . $fieldDisplay . '</label>');
            }
            unset($fieldOptions2);
            echo('</div>
                    </div>
               </div>
                    
                <div class="ags-psrf-settings-box">
                    <label>
                        <span class="label">' . esc_html__('Exclude free products', 'product-sales-report-for-woocommerce') . '</span>
                        <input type="checkbox" name="exclude_free" ' . (empty($reportSettings['exclude_free']) ? '' : ' checked="checked"') . ' />
                    </label>
                    <div class="ags-psrf-settings-tooltip">
                        <div class="ags-psrf-settings-tooltiptext">
                            <span>' . esc_html__('Help', 'product-sales-report-for-woocommerce') . '</span>
                            ' . esc_html__('If checked, order line items with a total amount of zero (after discounts) will be excluded from the report calculations.', 'product-sales-report-for-woocommerce') . '
                        </div>
                    </div>
                </div>
                        
                <div class="ags-psrf-settings-box">
                    <div class="ags-psrf-settings-multirow has-checkbox">
                        <label class="ags-psrf-settings-title">
                           <span class="label">' . esc_html__('Products number', 'product-sales-report-for-woocommerce') . '</span>
                         </label>
                        <div class="ags-psrf-settings-content">
                            <input type="checkbox" name="limit_on" ' . (empty($reportSettings['limit_on']) ? '' : ' checked="checked"') . ' />
                            ' . sprintf(esc_html__('Show only the first %s products', 'product-sales-report-for-woocommerce'), '<input id="hm_psr_limit_number" type="number" name="limit" value="' . $reportSettings['limit'] . '" min="0" step="1" class="small-text" />') . '
                        </div>
                    </div>
                </div>
				
				<div class="ags-psrf-settings-box">
					<label>
                       <span class="label">' . esc_html__('Intermediate rounding', 'product-sales-report-for-woocommerce') . '</span>
						<input type="checkbox" name="intermediate_rounding" value="2"'.(empty($reportSettings['intermediate_rounding']) ? '' : ' checked="checked"').' />
                    </label>
                     <div class="ags-psrf-settings-tooltip">
                        <div class="ags-psrf-settings-tooltiptext">
                            <span>' . esc_html__('Help', 'product-sales-report-for-woocommerce') . '</span>
							' . esc_html__('Enabling this option will round certain order line item amounts to two decimal places before they are added to the total amount on any given row. This only applies to the Gross Sales and Gross Sales (After Discounts) fields.', 'product-sales-report-for-woocommerce') . '
                        </div>
                    </div>
                </div>
                  
                <div class="ags-psrf-settings-box">
                    <label>
                        <span class="label">' . esc_html__('Include header row', 'product-sales-report-for-woocommerce') . '</span>
                        <input type="checkbox" name="include_header" ' . (empty($reportSettings['include_header']) ? '' : ' checked="checked"') . ' />
                    </label>
                    <div class="ags-psrf-settings-tooltip">
                        <div class="ags-psrf-settings-tooltiptext">
                            <span>' . esc_html__('Help', 'product-sales-report-for-woocommerce') . '</span>
                            ' . esc_html__('If checked, the first row of the report will contain the field names.', 'product-sales-report-for-woocommerce') . '
                        </div>
                    </div>
                </div>
				
				
				<div class="ags-psrf-settings-box">
					<label>
						<span class="label">' . esc_html__('Enable debug mode', 'product-sales-report-for-woocommerce') . '</span>
						<input type="checkbox" name="hm_psr_debug" value="1"'.(empty($reportSettings['hm_psr_debug']) ? '' : ' checked="checked"').' />
                    </label>
                </div>
				
               <p class="ags-psrf-notification">
               ' . sprintf(esc_html__('%sNote:%s Line item refunds created during the reporting period (regardless of the original order date) will be deducted from sales quantities and amounts if the status of the line item refund matches one of the selected order statuses (e.g. Complete), independent of the status of the original order. If you would like to disable this behavior, please check out our %sPro plugin%s, which also applies status filtering differently for line item refunds.', 'product-sales-report-for-woocommerce'), '<strong>', '</strong>', '<a href="https://wpzone.co/product/product-sales-report-pro-for-woocommerce/?utm_source=product-sales-report&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank">', '</a>') . '
                </p>
    
               <p class="submit">
                    <button type="submit" class="ags-psrf-button-primary" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'\'); return true;">' . esc_html__('View Report', 'product-sales-report-for-woocommerce') . '</button>
                    <button type="submit" class="ags-psrf-button-dark" name="hm_sbp_download" value="1" onclick="jQuery(this).closest(\'form\').attr(\'target\', \'_blank\'); return true;">' . esc_html__('Download Report as CSV', 'product-sales-report-for-woocommerce') . '</button>
                </p>
            </form>
            ');
            if (!empty($_POST['hm_sbp_do_export']) && !empty($_POST['fields'])) {
                echo('<table id="hm_sbp_table">');
                if (!empty($_POST['include_header'])) {
                    echo('<thead><tr>');
                    foreach (hm_sbpf_export_header(null, true) as $rowItem)
                        echo('<th>' . htmlspecialchars($rowItem) . '</th>');
                    echo('</tr></thead>');
                }
                echo('<tbody>');
                foreach (hm_sbpf_export_body(null, true) as $row) {
                    echo('<tr>');
                    foreach ($row as $rowItem) {
                        echo('<td>' . htmlspecialchars($rowItem) . '</td>');
                    }
                    echo('</tr>');
                }
                echo('</tbody></table>');

            }
            echo('
        </div> <!-- ags-psrf-settings-left-area -->

        <div class="ags-psrf-settings-sidebar">
            <div class="ags-psrf-widget">
                <img src=" ' . plugins_url('images/product_sales_report_pro.png', __FILE__) . ' " alt="Product Sales Report Pro for WooCommerce" class="widget-thumb"/>
                <div class="inside">
                    <h2>' . esc_html__('Upgrade to Pro', 'product-sales-report-for-woocommerce') . '</h2>
                    <p><strong>' . sprintf(esc_html__('Upgrade to %sProduct Sales Report Pro%s for the following additional features:', 'product-sales-report-for-woocommerce'), '<a href="https://wpzone.co/product/product-sales-report-pro-for-woocommerce/?utm_source=product-sales-report&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank">', '</a>') . '</strong></p>
                    <ul style="list-style-type: disc; padding-left: 1.5em;">
                        <li>' . esc_html__('Report on product variations individually', 'product-sales-report-for-woocommerce') . '</li>
                        <li>' . esc_html__('Optionally include products with no sales.', 'product-sales-report-for-woocommerce') . '</li>
                        <li>' . esc_html__('Report on shipping methods used.', 'product-sales-report-for-woocommerce') . '</li>
                        <li>' . esc_html__('Limit the report to orders with a matching custom meta field (e.g. delivery date).', 'product-sales-report-for-woocommerce') . '</li>
                        <li>' . esc_html__('Change the names and order of fields in the report.', 'product-sales-report-for-woocommerce') . '</li>
                        <li>' . sprintf(esc_html__('Include %scustom fields%s defined by WooCommerce or another plugin on a product or product variation.', 'product-sales-report-for-woocommerce'), '<strong style="color: #CC4A49;">', '</strong>') . '</li>
                        <li>' . esc_html__('Save multiple report presets to save time when generating different reports.', 'product-sales-report-for-woocommerce') . '</li>
                        <li>' . esc_html__('Export in Excel (XLSX or XLS) format.', 'product-sales-report-for-woocommerce') . '</li>
                        <li>' . esc_html__('Send the report as an email attachment.', 'product-sales-report-for-woocommerce') . '</li>
                    </ul>
                    <p>' . sprintf(esc_html__('%sReceive a %s discount with the coupon code %sWCREPORT10%s!%s (Not valid with any other discount)', 'product-sales-report-for-woocommerce'), '<strong>', '10%', '<span style="color: #CC4A49;">', '</span>', '</strong>') . '</p>
                    <a href="https://wpzone.co/product/product-sales-report-pro-for-woocommerce/?utm_source=product-sales-report&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank" class="ags-psrf-button-secondary">' . esc_html__('Buy Now', 'product-sales-report-for-woocommerce') . '</a>
                </div>
            </div>

            <div class="ags-psrf-widget">
                <img src=" ' . plugins_url('images/scheduled-email-reports.png', __FILE__) . ' " alt="Scheduled Email Reports for WooCommerce" class="widget-thumb"/>
                <div class="inside">
                    <h2>' . esc_html__('Schedule Email Reports', 'product-sales-report-for-woocommerce') . '</h2>
                    <p>' . esc_html__('Automatically send reports as email attachments on a recurring schedule.', 'product-sales-report-for-woocommerce') . '</p>
                    <a href="https://wpzone.co/product/scheduled-email-reports-for-woocommerce/?utm_source=product-sales-report&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank" class="ags-psrf-button-secondary">' . esc_html__('Get the add-on plugin', 'product-sales-report-for-woocommerce') . '</a>
                </div>
            </div>

            <div class="ags-psrf-widget">
                <img src=" ' . plugins_url('images/frontend-report-for-woocommerce.png', __FILE__) . ' " alt="Frontend Reports for WooCommerce" class="widget-thumb"/>
                <div class="inside">
                    <h2>' . esc_html__('Embed Report in Frontend Pages', 'product-sales-report-for-woocommerce') . '</h2>
                    <p>' . esc_html__('Display the report or a download link in posts and pages using a shortcode.', 'product-sales-report-for-woocommerce') . '</p>
                    <a href="https://wpzone.co/product/frontend-reports-for-woocommerce/?utm_source=product-sales-report&amp;utm_medium=link&amp;utm_campaign=wp-plugin-upgrade-link" target="_blank" class="ags-psrf-button-secondary">' . esc_html__('Get the add-on plugin', 'product-sales-report-for-woocommerce') . '</a>
                </div>
            </div>
        </div> <!-- /ags-psrf-settings-sidebar -->
    </div> <!-- #ags-psrf-settings-settings -->

    <div id="ags-psrf-settings-addons"> ');
            define('AGS_PSRF_ADDONS_URL', 'https://wpzone.co/wp-content/uploads/product-addons/product-sales-report-free.json');
            require_once(dirname(__FILE__) . '/addons/addons.php');
            AGS_PSRF_Addons::outputList();
            echo('
    </div>  <!-- #ags-psrf-settings-addons -->

        </div> <!-- ags-psrf-settings-tabs-content -->
    </div> <!-- ags-psrf-settings -->
</div> <!-- ags-psrf-settings-container --> '); ?>

            <script type="text/javascript" src="<?php echo plugins_url('/js/hm-product-sales-report.js?v=' . HM_PSRF_VERSION, __FILE__); ?>"></script>
            <script>
                var ags_psrf_tabs_navigate = function () {
                    var tabs = [
                            {
                                tabsContainerId: 'ags-psrf-settings-tabs',
                                contentIdPrefix: 'ags-psrf-settings-'
                            }
                        ],
                        activeClass = 'ags-psrf-settings-active';
                    for (var i = 0; i < tabs.length; ++i) {
                        var $tabContent = jQuery('#' + tabs[i].contentIdPrefix + location.hash.substr(1));
                        if ($tabContent.length) {
                            var $tabs = jQuery('#' + tabs[i].tabsContainerId + ' > li');
                            $tabContent
                                .siblings()
                                .add($tabs)
                                .removeClass(activeClass);
                            $tabContent.addClass(activeClass);
                            $tabs
                                .filter(':has(a[href="' + location.hash + '"])')
                                .addClass(activeClass);
                            break;
                        }
                    }
                };
                if (location.hash) {
                    ags_psrf_tabs_navigate();
                }

                jQuery(window).on('hashchange', ags_psrf_tabs_navigate);
            </script>