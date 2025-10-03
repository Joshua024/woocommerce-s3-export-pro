<?php
/**
 * Plugin Name: WooCommerce S3 Export Pro
 * Plugin URI: https://github.com/Joshua024/woocommerce-s3-export-pro
 * Description: Professional WooCommerce CSV export automation with S3 upload capabilities. Perfect for businesses needing automated data exports to Amazon S3 with zero technical knowledge required.
 * Version: 2.1.0
 * Author: Joshua C. Adumchimma
 * Author URI: https://dev-joshua-web-developer.pantheonsite.io/
 * Text Domain: wc-s3-export-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 10.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @package WC_S3_Export_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_S3_EXPORT_PRO_VERSION', '2.1.0');
define('WC_S3_EXPORT_PRO_PLUGIN_FILE', __FILE__);
define('WC_S3_EXPORT_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_S3_EXPORT_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_S3_EXPORT_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load Composer autoloader for AWS SDK
if (file_exists(WC_S3_EXPORT_PRO_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WC_S3_EXPORT_PRO_PLUGIN_DIR . 'vendor/autoload.php';
}

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'WC_S3_Export_Pro\\';
    $base_dir = WC_S3_EXPORT_PRO_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Admin notice functions
function wc_s3_export_pro_woocommerce_missing_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>WooCommerce S3 Export Pro</strong> requires WooCommerce to be installed and activated.';
    echo '</p></div>';
}

function wc_s3_export_pro_csv_export_missing_notice() {
    echo '<div class="notice notice-error"><p>';
    echo '<strong>WooCommerce S3 Export Pro</strong> requires WooCommerce Customer/Order CSV Export plugin to be installed and activated.';
    echo '</p></div>';
}

// Initialize the plugin
function wc_s3_export_pro_init() {
    // Initialize the main plugin class regardless of WooCommerce dependencies
    // The plugin will work independently and handle missing dependencies gracefully
    new \WC_S3_Export_Pro\Export_Manager();
}
add_action('plugins_loaded', 'wc_s3_export_pro_init');

// Activation function
function wc_s3_export_pro_activate() {
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $export_dir = $upload_dir['basedir'] . '/wc-s3-exports/';
    
    if (!file_exists($export_dir)) {
        wp_mkdir_p($export_dir);
    }
    
    // Create .htaccess to protect exports
    $htaccess_file = $export_dir . '.htaccess';
    if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, "Order deny,allow\nDeny from all");
    }
    
    // Schedule health check cron
    if (!wp_next_scheduled('wc_s3_export_health_check')) {
        wp_schedule_event(time(), 'daily', 'wc_s3_export_health_check');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation function
function wc_s3_export_pro_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('wc_s3_export_health_check');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Activation hook
register_activation_hook(__FILE__, 'wc_s3_export_pro_activate');

// Deactivation hook
register_deactivation_hook(__FILE__, 'wc_s3_export_pro_deactivate');

// Uninstall function
function wc_s3_export_pro_uninstall() {
    // Clean up options
    delete_option('wc_s3_export_pro_settings');
    delete_option('wc_s3_export_pro_s3_config');
    
    // Clear scheduled events
    wp_clear_scheduled_hook('wc_s3_export_health_check');
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'wc_s3_export_pro_uninstall');