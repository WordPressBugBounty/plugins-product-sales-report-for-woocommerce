<?php
/**
 * BerryPress Admin Framework - Addons Page Template
 * 
 * This file contains the HTML structure for the addons page.
 * It can be included in any BerryPress admin page to display addons content.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin base path for icons - use same method as logo in header
$plugin_url = plugin_dir_url(dirname(dirname(dirname(__FILE__))) . '/hm-product-sales-report.php');
?>

<div class="berrypress-addons-page">
    <div class="addons-header">
        <h1><?php esc_html_e('BerryPress Plugins', 'product-sales-report-for-woocommerce'); ?></h1>
        <h2 class="about-description berrypress-fs-16 berrypress-fw-medium berrypress-mb-3">
            <?php esc_html_e('Discover our collection of powerful WordPress plugins designed to enhance your website functionality!', 'product-sales-report-for-woocommerce'); ?>
        </h2>
    </div>

    
    <div class="berrypress-addons-grid">
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge berrypress-product-badge-highlight">NEW!</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo $plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/mobile-app.png'; ?>"
                     srcset="<?php echo $plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/mobile-app.png'; ?> 1x, <?php echo $plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/mobile-app-2x.png'; ?> 2x"
                     alt="<?php esc_attr_e('Mobile App Icon', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Ninjalytics App', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Access your reports on the go with the Ninjalytics app (beta)! Works with Ninjalytics Pro.', 'product-sales-report-for-woocommerce'); ?></p>
            <div class="berrypress-addon-item-buttons">
                <a href="https://play.google.com/store/apps/details?id=com.berrypress.ninjalytics" class="berrypress-btn berrypress-btn-primary berrypress-mb-2" target="_blank"><?php esc_html_e('Install on Android', 'product-sales-report-for-woocommerce'); ?></a>
                <a href="https://apps.apple.com/se/app/ninjalytics/id6757487864?l=en-GB" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('Install on iOS', 'product-sales-report-for-woocommerce'); ?></a>
            </div>
        </div>

        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free & Pro</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/live-carts-for-woocommerce.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/live-carts-for-woocommerce.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/live-carts-for-woocommerce-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Live Carts', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Live Carts', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Track active, abandoned, and converted carts live.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://berrypress.com/product/woocommerce/live-carts/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free & Pro</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/automatic-product-categories.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/automatic-product-categories.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/automatic-product-categories-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Automatic Product Categories', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Automatic Product Categories', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Automatically assign WooCommerce categories and tags with powerful rules, conditions, and triggers.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://berrypress.com/product/woocommerce/automatic-product-categories/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free & Pro</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/ninjalytics.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/ninjalytics.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/ninjalytics-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Ninjalytics', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Ninjalytics', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Report, analyze, and visualize your store\'s data to gain actionable insights for growth, support operations, and satisfy customers!', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://berrypress.com/product/woocommerce/ninjalytics/?utm_campaign=upsell&source=ninjalytics-free-plugin" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Pro</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/image-upload-for-bbpress.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/image-upload-for-bbpress.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/image-upload-for-bbpress-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Image Upload for bbPress Pro', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Image Upload for bbPress Pro', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Upload inline images to bbPress forum topics and replies.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://berrypress.com/product/bbpress/image-upload-for-bbpress-pro/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/s3-image-storage-for-bbpress.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/s3-image-storage-for-bbpress.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/s3-image-storage-for-bbpress-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('S3 Image Storage for bbPress', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('S3 Image Storage for bbPress', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Works with the Image Upload for bbPress Pro plugin to store images on Amazon S3, reducing storage and bandwidth usage.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://berrypress.com/product/bbpress/s3-image-storage-for-bbpress/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/photoberry-studio.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/photoberry-studio.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/photoberry-studio-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Photoberry Studio', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Photoberry Studio', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Image selection, proofing, watermarking, and client management.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://wordpress.org/plugins/photoberry-studio/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/product-sales-report.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/product-sales-report.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/product-sales-report-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Product Sales Report for WooCommerce', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Product Sales Report for WooCommerce', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('View and download sales data for individual products.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://wordpress.org/plugins/product-sales-report-for-woocommerce/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/export-order-items.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/export-order-items.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/export-order-items-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Export Order Items for WooCommerce', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Export Order Items for WooCommerce', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Export details of order product line items.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://wordpress.org/plugins/export-order-items-for-woocommerce/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Pro</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/export-order-items.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/export-order-items.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/export-order-items-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Export Order Items Pro', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Export Order Items Pro', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Advanced export functionality for order items with enhanced features and customization options.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://wpzone.co/product/export-order-items-pro-for-woocommerce/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/customer-address-change-notification.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/customer-address-change-notification.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/customer-address-change-notification-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('Customer Address Change Notification', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('Customer Address Change Notification', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Notify admins instantly whenever a customer address is added or edited.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://wordpress.org/plugins/customer-address-change-notification-for-woocommerce/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/rest-api-explorer.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/rest-api-explorer.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/rest-api-explorer-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('REST API Explorer', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('REST API Explorer', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Browse and test WordPress REST API endpoints directly from your dashboard.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://wordpress.org/plugins/rest-api-explorer/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
        
        <div class="berrypress-addon-item">
            <span class="berrypress-product-badge">Free</span>
            <div class="berrypress-addon-icon">
                <img src="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/loginberry.png'); ?>" 
                     srcset="<?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/loginberry.png'); ?> 1x, <?php echo esc_url($plugin_url . 'includes/berrypress-admin-framework/assets/addons-icons/loginberry-2x.png'); ?> 2x"
                     alt="<?php esc_attr_e('LoginBerry', 'product-sales-report-for-woocommerce'); ?>" />
            </div>
            <h3><?php esc_html_e('LoginBerry', 'product-sales-report-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Automatic email verification for WordPress accounts. Stops fake signups with a 6-digit activation code.', 'product-sales-report-for-woocommerce'); ?></p>
            <a href="https://wordpress.org/plugins/loginberry/" class="berrypress-btn berrypress-btn-primary" target="_blank"><?php esc_html_e('View Product', 'product-sales-report-for-woocommerce'); ?></a>
        </div>
    </div>
    
    <div class="berrypress-box-cta">
        <h3><?php esc_html_e('Find our complete collection on Berrypress', 'product-sales-report-for-woocommerce'); ?></h3>
        <p><?php esc_html_e('See everything we’ve built for WordPress on Berrypress and unlock even more ways to power up your website.', 'product-sales-report-for-woocommerce'); ?></p>
        <a href="https://berrypress.com/shop" target="_blank" class="berrypress-btn berrypress-btn-primary">
            <?php esc_html_e('View All Plugins', 'product-sales-report-for-woocommerce'); ?>
        </a>
    </div>
</div>
