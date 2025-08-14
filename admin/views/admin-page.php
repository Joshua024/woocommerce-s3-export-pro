<?php
/**
 * WooCommerce S3 Export Pro - Admin Dashboard
 * 
 * @package WC_S3_Export_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin instance
$plugin = \WC_S3_Export_Pro\Export_Manager::get_instance();
$settings = new \WC_S3_Export_Pro\Settings();
$monitoring = new \WC_S3_Export_Pro\Monitoring();

// Get system status
$system_status = $monitoring->get_system_status();
$s3_status = $plugin->get_s3_uploader()->test_connection();
$export_status = $monitoring->get_export_status();

// Get current settings
$current_settings = $settings->get_settings();
$current_s3_config = $settings->get_s3_config();

/**
 * Get data source options for field mapping
 */
function get_data_source_options($export_type, $selected_value = '') {
    $options = array();
    
    switch ($export_type) {
        case 'orders':
            $options = array(
                'order_id' => 'Order ID',
                'order_number' => 'Order Number',
                'order_number_formatted' => 'Order Number (Formatted)',
                'order_date' => 'Order Date',
                'status' => 'Order Status',
                'shipping_total' => 'Shipping Total',
                'shipping_tax_total' => 'Shipping Tax Total',
                'fee_total' => 'Fee Total',
                'fee_tax_total' => 'Fee Tax Total',
                'tax_total' => 'Tax Total',
                'discount_total' => 'Discount Total',
                'order_total' => 'Order Total',
                'refunded_total' => 'Refunded Total',
                'order_currency' => 'Order Currency',
                'payment_method' => 'Payment Method',
                'shipping_method' => 'Shipping Method',
                'customer_id' => 'Customer ID',
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_full_name' => 'Billing Full Name',
                'billing_company' => 'Billing Company',
                'vat_number' => 'VAT Number',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',
                'billing_address_1' => 'Billing Address 1',
                'billing_address_2' => 'Billing Address 2',
                'billing_postcode' => 'Billing Postcode',
                'billing_city' => 'Billing City',
                'billing_state' => 'Billing State',
                'billing_state_code' => 'Billing State Code',
                'billing_country' => 'Billing Country',
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_full_name' => 'Shipping Full Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_address_2' => 'Shipping Address 2',
                'shipping_postcode' => 'Shipping Postcode',
                'shipping_city' => 'Shipping City',
                'shipping_state' => 'Shipping State',
                'shipping_state_code' => 'Shipping State Code',
                'shipping_country' => 'Shipping Country',
                'shipping_company' => 'Shipping Company',
                'customer_note' => 'Customer Note',
                'item_id' => 'Item ID',
                'item_product_id' => 'Item Product ID',
                'item_name' => 'Item Name',
                'item_sku' => 'Item SKU',
                'item_quantity' => 'Item Quantity',
                'item_subtotal' => 'Item Subtotal',
                'item_subtotal_tax' => 'Item Subtotal Tax',
                'item_total' => 'Item Total',
                'item_total_tax' => 'Item Total Tax',
                'item_refunded' => 'Item Refunded',
                'item_refunded_qty' => 'Item Refunded Quantity',
                'item_meta' => 'Item Meta',
                'item_price' => 'Item Price',
                'line_items' => 'Line Items',
                'shipping_items' => 'Shipping Items',
                'fee_items' => 'Fee Items',
                'tax_items' => 'Tax Items',
                'coupon_items' => 'Coupon Items',
                'refunds' => 'Refunds',
                'order_notes' => 'Order Notes',
                'download_permissions' => 'Download Permissions',
                'order_meta' => 'Order Meta'
            );
            break;
            
        case 'order_items':
            $options = array(
                'order_id' => 'Order ID',
                'order_item_id' => 'Order Item ID',
                'product_id' => 'Product ID',
                'product_name' => 'Product Name',
                'product_sku' => 'Product SKU',
                'product_variation_id' => 'Product Variation ID',
                'product_variation_sku' => 'Product Variation SKU',
                'product_variation_attributes' => 'Product Variation Attributes',
                'quantity' => 'Quantity',
                'line_total' => 'Line Total',
                'line_subtotal' => 'Line Subtotal',
                'line_tax' => 'Line Tax',
                'line_subtotal_tax' => 'Line Subtotal Tax',
                'product_meta' => 'Product Meta',
                'order_date' => 'Order Date',
                'order_status' => 'Order Status',
                'order_number' => 'Order Number',
                'order_number_formatted' => 'Order Number (Formatted)',
                'customer_id' => 'Customer ID',
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',
                'billing_address_1' => 'Billing Address 1',
                'billing_address_2' => 'Billing Address 2',
                'billing_city' => 'Billing City',
                'billing_state' => 'Billing State',
                'billing_postcode' => 'Billing Postcode',
                'billing_country' => 'Billing Country',
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_address_2' => 'Shipping Address 2',
                'shipping_city' => 'Shipping City',
                'shipping_state' => 'Shipping State',
                'shipping_postcode' => 'Shipping Postcode',
                'shipping_country' => 'Shipping Country',
                'payment_method' => 'Payment Method',
                'payment_method_title' => 'Payment Method Title',
                'order_total' => 'Order Total',
                'order_subtotal' => 'Order Subtotal',
                'order_tax' => 'Order Tax',
                'order_shipping' => 'Order Shipping',
                'order_discount' => 'Order Discount',
                'order_currency' => 'Order Currency',
                'customer_note' => 'Customer Note',
                'shipping_total' => 'Shipping Total',
                'shipping_tax_total' => 'Shipping Tax Total',
                'fee_total' => 'Fee Total',
                'tax_total' => 'Tax Total',
                'discount_total' => 'Discount Total',
                'refunded_total' => 'Refunded Total',
                'shipping_method' => 'Shipping Method',
                'order_meta' => 'Order Meta',
                'product_type' => 'Product Type',
                'product_status' => 'Product Status',
                'product_price' => 'Product Price',
                'product_regular_price' => 'Product Regular Price',
                'product_sale_price' => 'Product Sale Price',
                'product_description' => 'Product Description',
                'product_short_description' => 'Product Short Description',
                'product_categories' => 'Product Categories',
                'product_tags' => 'Product Tags',
                'product_stock_quantity' => 'Product Stock Quantity',
                'product_stock_status' => 'Product Stock Status',
                'product_weight' => 'Product Weight',
                'product_dimensions' => 'Product Dimensions'
            );
            break;
            
        case 'customers':
            $options = array(
                'customer_id' => 'Customer ID',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'user_login' => 'Username',
                'email' => 'Email',
                'user_pass' => 'User Password',
                'date_registered' => 'Date Registered',
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_full_name' => 'Billing Full Name',
                'billing_company' => 'Billing Company',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',
                'billing_address_1' => 'Billing Address 1',
                'billing_address_2' => 'Billing Address 2',
                'billing_postcode' => 'Billing Postcode',
                'billing_city' => 'Billing City',
                'billing_state' => 'Billing State',
                'billing_state_code' => 'Billing State Code',
                'billing_country' => 'Billing Country',
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_full_name' => 'Shipping Full Name',
                'shipping_company' => 'Shipping Company',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_address_2' => 'Shipping Address 2',
                'shipping_postcode' => 'Shipping Postcode',
                'shipping_city' => 'Shipping City',
                'shipping_state' => 'Shipping State',
                'shipping_state_code' => 'Shipping State Code',
                'shipping_country' => 'Shipping Country',
                'total_spent' => 'Total Spent',
                'order_count' => 'Order Count',
                'customer_meta' => 'Customer Meta'
            );
            break;
            
        case 'products':
            $options = array(
                'product_id' => 'Product ID',
                'product_name' => 'Product Name',
                'product_sku' => 'Product SKU',
                'product_type' => 'Product Type',
                'product_status' => 'Product Status',
                'product_price' => 'Product Price',
                'product_regular_price' => 'Product Regular Price',
                'product_sale_price' => 'Product Sale Price',
                'product_description' => 'Product Description',
                'product_short_description' => 'Product Short Description',
                'product_categories' => 'Product Categories',
                'product_tags' => 'Product Tags',
                'product_stock_quantity' => 'Product Stock Quantity',
                'product_stock_status' => 'Product Stock Status',
                'product_weight' => 'Product Weight',
                'product_dimensions' => 'Product Dimensions',
                'product_meta' => 'Product Meta'
            );
            break;
            
        case 'coupons':
            $options = array(
                'code' => 'Coupon Code',
                'type' => 'Coupon Type',
                'description' => 'Coupon Description',
                'amount' => 'Coupon Amount',
                'expiry_date' => 'Coupon Expiry Date',
                'enable_free_shipping' => 'Enable Free Shipping',
                'minimum_amount' => 'Minimum Amount',
                'maximum_amount' => 'Maximum Amount',
                'individual_use' => 'Individual Use',
                'exclude_sale_items' => 'Exclude Sale Items',
                'products' => 'Products',
                'exclude_products' => 'Exclude Products',
                'product_categories' => 'Product Categories',
                'exclude_product_categories' => 'Exclude Product Categories',
                'customer_emails' => 'Customer Emails',
                'usage_limit' => 'Usage Limit',
                'limit_usage_to_x_items' => 'Limit Usage To X Items',
                'usage_limit_per_user' => 'Usage Limit Per User',
                'usage_count' => 'Usage Count',
                'product_ids' => 'Product IDs',
                'exclude_product_ids' => 'Exclude Product IDs',
                'used_by' => 'Used By',
                'coupon_meta' => 'Coupon Meta'
            );
            break;
    }
    
    $html = '';
    foreach ($options as $value => $label) {
        $selected = ($value === $selected_value) ? 'selected' : '';
        $html .= '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
    
    return $html;
}
?>

<div class="wrap wc-s3-export-pro-wrap">
    <!-- Header -->
    <div class="wc-s3-header">
        <h1>🚀 WooCommerce S3 Export Pro</h1>
        <p>Professional WooCommerce CSV export automation with S3 upload capabilities</p>
    </div>

    <!-- Status Overview -->
    <div class="wc-s3-status-grid">
        <!-- System Status -->
        <div class="wc-s3-status-card <?php echo $system_status['overall'] === 'healthy' ? 'success' : 'error'; ?>">
            <h3>🖥️ System Status</h3>
            <div class="status-indicator <?php echo $system_status['overall'] === 'healthy' ? 'success' : 'error'; ?>">
                <?php echo $system_status['overall'] === 'healthy' ? 'Healthy' : 'Issues Detected'; ?>
            </div>
            <p><strong>WooCommerce:</strong> <?php echo $system_status['woocommerce'] ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>CSV Export Plugin:</strong> <?php echo $system_status['csv_export'] ? '✅ Active' : '❌ Inactive'; ?></p>
            <p><strong>Action Scheduler:</strong> <?php echo $system_status['action_scheduler'] ? '✅ Working' : '❌ Issues'; ?></p>
            <?php if (!empty($system_status['issues'])): ?>
                <div class="status-issues">
                    <strong>Issues Found:</strong>
                    <ul>
                        <?php foreach ($system_status['issues'] as $issue): ?>
                            <li>⚠️ <?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- S3 Connection -->
        <div class="wc-s3-status-card <?php echo $s3_status['success'] ? 'success' : 'warning'; ?>">
            <h3>☁️ S3 Connection</h3>
            <div class="status-indicator <?php echo $s3_status['success'] ? 'success' : 'warning'; ?>">
                <?php echo $s3_status['success'] ? 'Connected' : 'Not Configured'; ?>
            </div>
            <?php if ($s3_status['success']): ?>
                <p><strong>Status:</strong> ✅ Connected</p>
                <p><strong>Buckets:</strong> <?php echo $s3_status['buckets']; ?> available</p>
                <p><strong>Region:</strong> <?php echo esc_html($current_s3_config['region'] ?? 'us-east-1'); ?></p>
            <?php else: ?>
                <p><strong>Status:</strong> ⚠️ <?php echo esc_html($s3_status['message'] ?? 'Not configured'); ?></p>
                <p><strong>Action:</strong> Configure S3 credentials below</p>
            <?php endif; ?>
        </div>

        <!-- Export Status -->
        <div class="wc-s3-status-card <?php echo $export_status['status'] === 'active' ? 'success' : 'warning'; ?>">
            <h3>📊 Export Status</h3>
            <div class="status-indicator <?php echo $export_status['status'] === 'active' ? 'success' : 'warning'; ?>">
                <?php echo $export_status['status'] === 'active' ? 'Active' : 'Inactive'; ?>
            </div>
            <p><strong>Last Export:</strong> <?php echo $export_status['last_export'] ?: 'Never'; ?></p>
            <p><strong>Next Export:</strong> <?php echo $export_status['next_export'] ?: 'Not Scheduled'; ?></p>
            <p><strong>Total Exports:</strong> <?php echo $export_status['total_exports']; ?></p>
            <p><strong>Pending Jobs:</strong> <?php echo $export_status['pending_jobs']; ?></p>
            <?php if ($export_status['status'] !== 'active'): ?>
                <p><strong>Action:</strong> <a href="#" onclick="setupAutomation(); return false;">Setup Automation</a></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="wc-s3-actions">
        <button class="wc-s3-btn primary" onclick="testS3Connection()">
            <span class="wc-s3-btn-loading primary" id="s3-loading" style="display: none;"></span>
            🔗 Test S3 Connection
        </button>
        
        <button class="wc-s3-btn secondary" onclick="showManualExportModal()">
            <span class="wc-s3-btn-loading secondary" id="export-loading" style="display: none;"></span>
            📤 Run Manual Export
        </button>
        
        <button class="wc-s3-btn success" onclick="setupAutomation()">
            ⚙️ Setup Automation
        </button>
        
        <button class="wc-s3-btn warning" onclick="viewLogs()">
            📋 View Logs
        </button>
        
        <button class="wc-s3-btn info" onclick="showExportHistory()">
            <span class="wc-s3-btn-icon">📊</span>
            Export History
        </button>
    </div>

    <!-- Manual Export Modal -->
    <div id="manual-export-modal" class="wc-s3-modal" style="display: none;">
        <div class="wc-s3-modal-content">
            <div class="wc-s3-modal-header">
                <h3>📤 Manual Export</h3>
                <button type="button" class="wc-s3-modal-close-btn" onclick="closeManualExportModal()" aria-label="Close modal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="wc-s3-modal-body">
                <form id="manual-export-form">
                    <div class="wc-s3-form-group">
                        <label for="export_start_date">Start Date</label>
                        <input type="date" id="export_start_date" name="export_start_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="wc-s3-form-group">
                        <label for="export_end_date">End Date (Optional - Leave empty for single date)</label>
                        <input type="date" id="export_end_date" name="export_end_date" 
                               value="" placeholder="Leave empty for single date">
                    </div>
                    
                    <div class="wc-s3-form-group">
                        <label for="export_types">Export Types</label>
                        <div class="wc-s3-checkbox-group">
                            <?php
                            $export_types_config = $settings->get_export_types_config();
                            foreach ($export_types_config as $export_type) {
                                // Show all export types in manual export, not just enabled ones
                                $checked = $export_type['enabled'] ? 'checked' : '';
                                $disabled_class = !$export_type['enabled'] ? 'disabled' : '';
                                echo '<label class="wc-s3-checkbox ' . $disabled_class . '">';
                                echo '<input type="checkbox" name="export_types[]" value="' . esc_attr($export_type['id']) . '" ' . $checked . '>';
                                echo '<span class="checkmark"></span>';
                                echo esc_html($export_type['name']);
                                if (!$export_type['enabled']) {
                                    echo ' <span class="wc-s3-disabled-note">(Disabled)</span>';
                                }
                                echo '</label>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="wc-s3-form-group">
                        <label class="wc-s3-checkbox-label">
                            <input type="checkbox" id="force_export" name="force_export" value="1">
                            <span class="checkmark"></span>
                            Force Export (Skip duplicate check)
                        </label>
                    </div>
                    
                    <div class="wc-s3-modal-actions">
                        <button type="button" class="wc-s3-btn secondary" onclick="closeManualExportModal()">Cancel</button>
                        <button type="submit" class="wc-s3-btn primary">
                            <span class="wc-s3-btn-loading primary" id="manual-export-loading" style="display: none;"></span>
                            📤 Run Export
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export History Modal -->
    <div id="export-history-modal" class="wc-s3-modal" style="display: none;">
        <div class="wc-s3-modal-content wc-s3-modal-large">
            <div class="wc-s3-modal-header">
                <h3>📊 Export History</h3>
                <button type="button" class="wc-s3-modal-close-btn" onclick="closeExportHistoryModal()" aria-label="Close modal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="wc-s3-modal-body">
                <div class="wc-s3-history-filters">
                    <div class="wc-s3-filter-row">
                        <div class="wc-s3-form-group">
                            <label for="history_start_date">Start Date</label>
                            <input type="date" id="history_start_date" name="history_start_date" 
                                   value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        </div>
                        
                        <div class="wc-s3-form-group">
                            <label for="history_end_date">End Date</label>
                            <input type="date" id="history_end_date" name="history_end_date" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="wc-s3-form-group">
                            <label for="history_export_type">Export Type</label>
                            <select id="history_export_type" name="history_export_type">
                                <option value="">All Types</option>
                                <?php
                                foreach ($export_types_config as $export_type) {
                                    echo '<option value="' . esc_attr($export_type['id']) . '">' . esc_html($export_type['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="wc-s3-form-group">
                            <label for="history_trigger_type">Trigger Type</label>
                            <select id="history_trigger_type" name="history_trigger_type">
                                <option value="">All Triggers</option>
                                <option value="manual">Manual</option>
                                <option value="automatic">Automatic</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="wc-s3-history-filter-actions">
                        <button type="button" class="wc-s3-btn primary small" onclick="loadExportHistory()">🔍 Filter</button>
                        <button type="button" id="delete-selected-btn" class="wc-s3-btn error small" onclick="deleteSelectedExportRecords()" disabled>🗑️ Delete Selected</button>
                    </div>
                </div>
                
                <div id="export-history-table" class="wc-s3-table-container">
                    <table class="wc-s3-table">
                        <thead>
                            <tr>
                                <th style="width:36px;"><input type="checkbox" id="history_select_all"></th>
                                <th>Date</th>
                                <th>Export Type</th>
                                <th>Trigger Type</th>
                                <th>File Name</th>
                                <th>Status</th>
                                <th>File Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="export-history-tbody">
                            <!-- History data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Modal -->
    <div id="logs-modal" class="wc-s3-modal" style="display: none;">
        <div class="wc-s3-modal-content wc-s3-modal-large">
            <div class="wc-s3-modal-header">
                <h3>📋 System Logs</h3>
                <button type="button" class="wc-s3-modal-close-btn" onclick="closeLogsModal()" aria-label="Close modal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="wc-s3-modal-body">
                <div class="wc-s3-logs-container">
                    <div class="wc-s3-logs-header">
                        <h4>Recent System Logs</h4>
                        <button type="button" class="wc-s3-btn secondary small" onclick="loadLogs()">🔄 Refresh</button>
                    </div>
                    <div id="logs-content" class="wc-s3-logs-content">
                        <!-- Logs will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- S3 Configuration -->
    <div class="wc-s3-form">
        <h3>☁️ S3 Configuration</h3>
        <form id="s3-config-form">
            <div class="wc-s3-form-group">
                <label for="s3_access_key">AWS Access Key ID</label>
                <input type="text" id="s3_access_key" name="s3_access_key" 
                       value="<?php echo esc_attr($current_s3_config['access_key'] ?? ''); ?>" 
                       placeholder="AKIAIOSFODNN7EXAMPLE" required>
            </div>
            
            <div class="wc-s3-form-group">
                <label for="s3_secret_key">AWS Secret Access Key</label>
                <input type="password" id="s3_secret_key" name="s3_secret_key" 
                       value="<?php echo esc_attr($current_s3_config['secret_key'] ?? ''); ?>" 
                       placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" required>
            </div>
            
            <div class="wc-s3-form-group">
                <label for="s3_region">AWS Region</label>
                <select id="s3_region" name="s3_region">
                    <option value="us-east-1" <?php selected($current_s3_config['region'] ?? '', 'us-east-1'); ?>>US East (N. Virginia) - us-east-1</option>
                    <option value="us-west-2" <?php selected($current_s3_config['region'] ?? '', 'us-west-2'); ?>>US West (Oregon) - us-west-2</option>
                    <option value="eu-west-1" <?php selected($current_s3_config['region'] ?? '', 'eu-west-1'); ?>>Europe (Ireland) - eu-west-1</option>
                    <option value="eu-west-2" <?php selected($current_s3_config['region'] ?? '', 'eu-west-2'); ?>>Europe (London) - eu-west-2</option>
                    <option value="af-south-1" <?php selected($current_s3_config['region'] ?? '', 'af-south-1'); ?>>Africa (Cape Town) - af-south-1</option>
                    <option value="ap-southeast-1" <?php selected($current_s3_config['region'] ?? '', 'ap-southeast-1'); ?>>Asia Pacific (Singapore) - ap-southeast-1</option>
                </select>
            </div>
            
            <div class="wc-s3-form-group">
                <label for="s3_bucket">S3 Bucket Name</label>
                <input type="text" id="s3_bucket" name="s3_bucket" 
                       value="<?php echo esc_attr($current_s3_config['bucket'] ?? ''); ?>" 
                       placeholder="my-woocommerce-exports" required>
            </div>
            
            <button type="submit" class="wc-s3-btn primary">💾 Save S3 Configuration</button>
        </form>
    </div>

    <!-- Export Settings -->
    <div class="wc-s3-form">
        <h3>⚙️ Export Settings</h3>
        <form id="export-settings-form">
            <div class="wc-s3-form-group">
                <label for="export_frequency">Default Export Frequency</label>
                <select id="export_frequency" name="export_frequency">
                    <option value="daily" <?php selected($current_settings['export_frequency'] ?? 'daily', 'daily'); ?>>Daily</option>
                    <option value="weekly" <?php selected($current_settings['export_frequency'] ?? 'daily', 'weekly'); ?>>Weekly</option>
                    <option value="monthly" <?php selected($current_settings['export_frequency'] ?? 'daily', 'monthly'); ?>>Monthly</option>
                </select>
            </div>
            
            <div class="wc-s3-form-group">
                <label for="export_time">Default Export Time</label>
                <input type="time" id="export_time" name="export_time" 
                       value="<?php echo esc_attr($current_settings['export_time'] ?? '01:00'); ?>">
            </div>
            
            <button type="submit" class="wc-s3-btn primary">💾 Save Default Settings</button>
        </form>
    </div>

    <!-- Export Types Configuration -->
    <div class="wc-s3-form">
        <h3>📋 Export Types Configuration</h3>
        <p>Configure individual settings for each export type. You can add, remove, and configure as many export types as you need.</p>
        
        <form id="export-types-form">
            <div id="export-types-container">
                <?php
                $current_export_types_config = $settings->get_export_types_config();
                if (empty($current_export_types_config)) {
                    $current_export_types_config = array();
                }
                
                foreach ($current_export_types_config as $index => $export_type): 
                ?>
                    <div class="wc-s3-export-type-section" data-index="<?php echo $index; ?>">
                        <div class="wc-s3-export-type-header">
                            <h4><?php echo esc_html($export_type['name'] ?: 'New Export Type'); ?></h4>
                            <button type="button" class="wc-s3-btn error small remove-export-type" onclick="removeExportType(<?php echo $index; ?>)">
                                🗑️ Remove
                            </button>
                        </div>
                        
                        <div class="wc-s3-form-row">
            <div class="wc-s3-form-group">
                                <label for="export_type_<?php echo $index; ?>_name">Export Name</label>
                                <input type="text" id="export_type_<?php echo $index; ?>_name" 
                                       name="export_types[<?php echo $index; ?>][name]" 
                                       value="<?php echo esc_attr($export_type['name'] ?? ''); ?>" 
                                       placeholder="e.g., Web Sales, Customer Data" required>
                                <p class="description">A descriptive name for this export</p>
                            </div>
                            
                            <div class="wc-s3-form-group">
                                <label for="export_type_<?php echo $index; ?>_type">Export Type</label>
                                <select id="export_type_<?php echo $index; ?>_type" 
                                        name="export_types[<?php echo $index; ?>][type]">
                                    <option value="orders" <?php selected($export_type['type'] ?? 'orders', 'orders'); ?>>Orders</option>
                                    <option value="customers" <?php selected($export_type['type'] ?? 'orders', 'customers'); ?>>Customers</option>
                                    <option value="products" <?php selected($export_type['type'] ?? 'orders', 'products'); ?>>Products</option>
                                    <option value="coupons" <?php selected($export_type['type'] ?? 'orders', 'coupons'); ?>>Coupons</option>
                                </select>
                                <p class="description">The type of data to export</p>
                            </div>
                        </div>
                        
                        <div class="wc-s3-form-row">
                            <div class="wc-s3-form-group order-statuses-field" id="order_statuses_<?php echo $index; ?>_container" style="<?php echo ($export_type['type'] ?? 'orders') === 'orders' ? '' : 'display: none;'; ?>">
                                <label>Order Statuses</label>
                                <div class="wc-s3-status-checkboxes">
                                    <div class="wc-s3-status-header">
                                        <label class="wc-s3-checkbox">
                                            <input type="checkbox" class="wc-s3-select-all-statuses" data-export-index="<?php echo $index; ?>">
                                            <span class="checkmark"></span>
                                            <strong>Select All Statuses</strong>
                    </label>
                                        <small>Or select specific statuses below:</small>
                                    </div>
                                    <div class="wc-s3-status-grid">
                                        <?php
                                        $order_statuses = wc_get_order_statuses();
                                        $selected_statuses = $export_type['statuses'] ?? array();
                                        foreach ($order_statuses as $status_key => $status_label) {
                                            $checked = in_array($status_key, $selected_statuses) ? 'checked' : '';
                                            echo '<label class="wc-s3-checkbox">';
                                            echo '<input type="checkbox" name="export_types[' . $index . '][statuses][]" value="' . esc_attr($status_key) . '" ' . $checked . ' class="wc-s3-status-checkbox" data-export-index="' . $index . '">';
                                            echo '<span class="checkmark"></span>';
                                            echo esc_html($status_label);
                                            echo '</label>';
                                        }
                                        ?>
                                    </div>
                                    <p class="description">Orders with these statuses will be included. Leave all unchecked to include all statuses.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wc-s3-form-row">
                            <div class="wc-s3-form-group">
                                <label for="export_type_<?php echo $index; ?>_file_prefix">File Prefix</label>
                                <input type="text" id="export_type_<?php echo $index; ?>_file_prefix" 
                                       name="export_types[<?php echo $index; ?>][file_prefix]" 
                                       value="<?php echo esc_attr($export_type['file_prefix'] ?? ''); ?>" 
                                       placeholder="e.g., FundsOnlineWebsiteSales">
                                <p class="description">Prefix for the exported file name</p>
                            </div>
                            
                            <div class="wc-s3-form-group">
                                <label for="export_type_<?php echo $index; ?>_s3_folder">S3 Folder</label>
                                <input type="text" id="export_type_<?php echo $index; ?>_s3_folder" 
                                       name="export_types[<?php echo $index; ?>][s3_folder]" 
                                       value="<?php echo esc_attr($export_type['s3_folder'] ?? ''); ?>" 
                                       placeholder="e.g., web-sales">
                                <p class="description">Folder name within the S3 bucket</p>
                            </div>
                            
                            <div class="wc-s3-form-group">
                                <label for="export_type_<?php echo $index; ?>_local_uploads_folder">Local Uploads Folder</label>
                                <input type="text" id="export_type_<?php echo $index; ?>_local_uploads_folder" 
                                       name="export_types[<?php echo $index; ?>][local_uploads_folder]" 
                                       value="<?php echo esc_attr($export_type['local_uploads_folder'] ?? ''); ?>" 
                                       placeholder="e.g., web-sales">
                                <p class="description">Folder name in wp-content/uploads/wc-s3-exports/</p>
                            </div>
                        </div>
                        
                        <div class="wc-s3-form-row">
                            <div class="wc-s3-form-group">
                                <label for="export_type_<?php echo $index; ?>_frequency">Export Frequency</label>
                                <select id="export_type_<?php echo $index; ?>_frequency" 
                                        name="export_types[<?php echo $index; ?>][frequency]">
                                    <option value="hourly" <?php selected($export_type['frequency'] ?? 'daily', 'hourly'); ?>>Hourly</option>
                                    <option value="daily" <?php selected($export_type['frequency'] ?? 'daily', 'daily'); ?>>Daily</option>
                                    <option value="weekly" <?php selected($export_type['frequency'] ?? 'daily', 'weekly'); ?>>Weekly</option>
                                    <option value="monthly" <?php selected($export_type['frequency'] ?? 'daily', 'monthly'); ?>>Monthly</option>
                                </select>
                            </div>
                            
                            <div class="wc-s3-form-group">
                                <label for="export_type_<?php echo $index; ?>_time">Export Time</label>
                                <input type="time" id="export_type_<?php echo $index; ?>_time" 
                                       name="export_types[<?php echo $index; ?>][time]" 
                                       value="<?php echo esc_attr($export_type['time'] ?? '01:00'); ?>">
            </div>
            
            <div class="wc-s3-form-group">
                    <label style="display: flex; align-items: center; margin: 0;">
                                    <input type="checkbox" name="export_types[<?php echo $index; ?>][enabled]" value="1" 
                                           <?php checked($export_type['enabled'] ?? true); ?> 
                               style="margin-right: 0.5rem;">
                                    Enable this export
                    </label>
                </div>
            </div>
            
                        <div class="wc-s3-form-group">
                            <label for="export_type_<?php echo $index; ?>_description">Description</label>
                            <textarea id="export_type_<?php echo $index; ?>_description" 
                                      name="export_types[<?php echo $index; ?>][description]" 
                                      rows="2" placeholder="Optional description of what this export contains"><?php echo esc_textarea($export_type['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Field Mapping Configuration -->
                        <div class="wc-s3-field-mapping-section">
                            <h4>📋 Field Mapping Configuration</h4>
                            <p class="description">Configure which fields to include in this export. Drag to reorder, add custom fields, or remove unwanted columns.</p>
                            
                            <div class="wc-s3-field-mapping-table-container">
                                <table class="wc-s3-field-mapping-table" id="field-mapping-table-<?php echo $index; ?>">
                                    <thead>
                                        <tr>
                                            <th style="width: 30px;">
                                                <input type="checkbox" id="select-all-<?php echo $index; ?>" onchange="toggleAllFields(<?php echo $index; ?>, this.checked)">
                                            </th>
                                            <th style="width: 30px;">⋮⋮</th>
                                            <th style="width: 35%;">Column Name</th>
                                            <th style="width: 35%;">Data Source</th>
                                            <th style="width: 60px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="wc-s3-field-mapping-tbody" id="field-mapping-tbody-<?php echo $index; ?>">
                                        <?php
                                        $current_field_mappings = $export_type['field_mappings'] ?? array();
                                        // Only load default fields for truly new export types (those without a proper ID)
                                        $has_proper_id = !empty($export_type['id']) && strpos($export_type['id'], '_') !== false;
                                        if (empty($current_field_mappings) && !$has_proper_id) {
                                            // Load default fields for this export type
                                            $default_fields = $settings->get_available_fields($export_type['type'] ?? 'orders');
                                            $current_field_mappings = array();
                                            foreach ($default_fields as $field_key => $field_label) {
                                                $current_field_mappings[] = array(
                                                    'enabled' => true,
                                                    'column_name' => $field_label,
                                                    'data_source' => $field_key
                                                );
                                            }
                                        }
                                        
                                        foreach ($current_field_mappings as $field_index => $field_data):
                                        ?>
                                            <tr class="wc-s3-field-row" data-field-index="<?php echo (int)$field_index; ?>">
                                                <td>
                                                    <input type="checkbox" name="export_types[<?php echo $index; ?>][field_mappings][<?php echo $field_index; ?>][enabled]" 
                                                           value="1" <?php checked($field_data['enabled'] ?? true); ?>>
                                                </td>
                                                <td class="drag-handle">⋮⋮</td>
                                                <td>
                                                    <input type="text" name="export_types[<?php echo $index; ?>][field_mappings][<?php echo $field_index; ?>][column_name]" 
                                                           value="<?php echo esc_attr($field_data['column_name'] ?? ''); ?>" 
                                                           placeholder="e.g., Order ID" class="regular-text">
                                                </td>
                                                <td>
                                                    <select name="export_types[<?php echo $index; ?>][field_mappings][<?php echo $field_index; ?>][data_source]" class="data-source-select">
                                                        <?php echo get_data_source_options($export_type['type'] ?? 'orders', $field_data['data_source'] ?? ''); ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="wc-s3-btn error small remove-field-btn" 
                                                            data-action="remove-field" 
                                                            data-export-type="<?php echo (int)$index; ?>" 
                                                            data-field-index="<?php echo (int)$field_index; ?>">
                                                        🗑️
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="wc-s3-field-mapping-actions">
                                <button type="button" class="wc-s3-btn secondary" onclick="addFieldRow(<?php echo $index; ?>)">➕ Add Column</button>
                                <button type="button" class="wc-s3-btn secondary" onclick="removeSelectedFields(<?php echo $index; ?>)">🗑️ Remove Selected</button>
                                <button type="button" class="wc-s3-btn secondary" onclick="loadDefaultFields(<?php echo $index; ?>, '<?php echo $export_type['type'] ?? 'orders'; ?>')">📋 Load Default Fields</button>
                            </div>
                        </div>
                        
                        <input type="hidden" name="export_types[<?php echo $index; ?>][id]" 
                               value="<?php echo esc_attr($export_type['id'] ?? ''); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="wc-s3-export-type-actions">
                <button type="button" class="wc-s3-btn secondary" onclick="addExportType()">
                    ➕ Add New Export Type
                </button>
                
                <button type="submit" class="wc-s3-btn primary">
                    💾 Save Export Types Configuration
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Activity -->
    <div class="wc-s3-form">
        <h3>📈 Recent Activity</h3>
        <div class="wc-s3-logs" id="recent-logs">
            <?php
            $recent_logs = $monitoring->get_recent_logs(10);
            if (!empty($recent_logs)):
                foreach ($recent_logs as $log):
                    $log_class = 'info';
                    if (strpos($log, 'ERROR') !== false) $log_class = 'error';
                    elseif (strpos($log, 'WARNING') !== false) $log_class = 'warning';
                    elseif (strpos($log, 'SUCCESS') !== false) $log_class = 'success';
            ?>
                <div class="log-entry <?php echo $log_class; ?>"><?php echo esc_html($log); ?></div>
            <?php 
                endforeach;
            else:
            ?>
                <div class="log-entry info">No recent activity. Start by configuring S3 and running your first export.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications -->
    <div id="notifications"></div>
</div>

<script>
// S3 Connection Test
function testS3Connection() {
    const loading = document.getElementById('s3-loading');
    const button = loading.parentElement;
    const originalText = button.innerHTML;
    
    // Show loading state
    loading.style.display = 'inline-flex';
    button.disabled = true;
    button.style.opacity = '0.7';
    
    // Optional: Change button text during loading
    const textNode = button.childNodes[button.childNodes.length - 1];
    if (textNode && textNode.nodeType === Node.TEXT_NODE) {
        textNode.textContent = ' Testing Connection...';
    }
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=wc_s3_test_s3_connection&nonce=' + wcS3ExportPro.nonce
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            setTimeout(() => location.reload(), 1500); // Small delay to show success message
        }
    })
    .catch(error => {
        showNotification('error', 'Connection test failed: ' + error.message);
    })
    .finally(() => {
        // Reset button state
        loading.style.display = 'none';
        button.disabled = false;
        button.style.opacity = '1';
        
        // Restore original text
        if (textNode && textNode.nodeType === Node.TEXT_NODE) {
            textNode.textContent = ' 🔗 Test S3 Connection';
        }
    });
}

// Manual Export
function showManualExportModal() {
    document.getElementById('manual-export-modal').style.display = 'block';
}

function closeManualExportModal() {
    document.getElementById('manual-export-modal').style.display = 'none';
    document.getElementById('manual-export-form').reset();
}

function testManualExport() {
    console.log('Testing AJAX call...');
    
    const formData = new FormData();
    formData.append('action', 'wc_s3_test_manual_export');
    formData.append('nonce', wcS3ExportPro.nonce);
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        showNotification(data.success ? 'success' : 'error', data.message);
    })
    .catch(error => {
        console.error('AJAX error:', error);
        showNotification('error', 'Test failed: ' + error.message);
    });
}

function runManualExport() {
    const form = document.getElementById('manual-export-form');
    const formData = new FormData(form);
    const loading = document.getElementById('manual-export-loading');
    const button = loading.parentElement;
    
    // Validate form
    const startDate = formData.get('export_start_date');
    const endDate = formData.get('export_end_date');
    
    if (!startDate) {
        showNotification('error', 'Please select a start date.');
        return;
    }
    
    if (endDate && new Date(endDate) < new Date(startDate)) {
        showNotification('error', 'End date cannot be before start date.');
        return;
    }
    
    // Show loading state
    loading.style.display = 'inline-flex';
    button.disabled = true;
    button.style.opacity = '0.7';
    
    // Add action and nonce
    formData.append('action', 'wc_s3_run_manual_export');
    formData.append('nonce', wcS3ExportPro.nonce);
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            closeManualExportModal();
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(error => {
        showNotification('error', 'Export failed: ' + error.message);
    })
    .finally(() => {
        loading.style.display = 'none';
        button.disabled = false;
    });
}

// Manual Export Form
document.getElementById('manual-export-form').addEventListener('submit', function(e) {
    e.preventDefault();
    runManualExport();
});

// Setup Automation
function setupAutomation() {
    showNotification('info', 'Setting up automation... This may take a moment.');
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=wc_s3_setup_automation&nonce=' + wcS3ExportPro.nonce
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(error => {
        showNotification('error', 'Setup failed: ' + error.message);
    });
}

// View Logs
function viewLogs() {
    document.getElementById('logs-modal').style.display = 'block';
    loadLogs();
}

function closeLogsModal() {
    document.getElementById('logs-modal').style.display = 'none';
}

function loadLogs() {
    const logsContainer = document.getElementById('logs-content');
    logsContainer.innerHTML = '<div class="wc-s3-loading"><div class="wc-s3-loading-spinner"></div><p class="wc-s3-loading-text">Loading logs...</p></div>';

    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=wc_s3_get_log_content&nonce=' + wcS3ExportPro.nonce
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data && data.data.content) {
            const logs = data.data.content;
            if (logs && logs.trim() !== '') {
                // Format logs for display
                const formattedLogs = logs.split('\n').map(line => {
                    if (line.trim() === '') return '';
                    return '<div class="log-entry">' + esc_html(line) + '</div>';
                }).join('');
                logsContainer.innerHTML = formattedLogs;
            } else {
                logsContainer.innerHTML = '<div class="wc-s3-no-data">No log entries found.</div>';
            }
        } else {
            logsContainer.innerHTML = '<div class="wc-s3-error">Error loading logs: ' + (data.data ? data.data : 'Unknown error') + '</div>';
        }
    })
    .catch(error => {
        logsContainer.innerHTML = '<div class="wc-s3-error">Error loading logs: ' + error.message + '</div>';
    });
}

function esc_html(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export History
function showExportHistory() {
    document.getElementById('export-history-modal').style.display = 'block';
    loadExportHistory(); // Load initial history on modal open
}

function closeExportHistoryModal() {
    document.getElementById('export-history-modal').style.display = 'none';
    document.getElementById('history_start_date').value = '<?php echo date('Y-m-d', strtotime('-30 days')); ?>';
    document.getElementById('history_end_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('history_export_type').value = '';
    document.getElementById('export-history-tbody').innerHTML = ''; // Clear history table
}

function loadExportHistory() {
    const startDate = document.getElementById('history_start_date').value;
    const endDate = document.getElementById('history_end_date').value;
    const exportType = document.getElementById('history_export_type').value;
    const triggerType = document.getElementById('history_trigger_type').value;

    const tbody = document.getElementById('export-history-tbody');
    tbody.innerHTML = '<tr><td colspan="8"><div class="wc-s3-table-loading"><div class="wc-s3-loading-spinner"></div><p class="wc-s3-loading-text">Loading export history...</p></div></td></tr>';

    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=wc_s3_get_export_history&nonce=' + wcS3ExportPro.nonce + 
              '&start_date=' + startDate + 
              '&end_date=' + endDate + 
              '&export_type=' + exportType +
              '&trigger_type=' + triggerType
    })
    .then(response => response.json())
    .then(data => {
        tbody.innerHTML = '';
        if (data.success && data.data && data.data.length > 0) {
            data.data.forEach(item => {
                const row = document.createElement('tr');
                const statusClass = item.status === 'completed' ? 'success' : item.status === 'failed' ? 'error' : 'warning';
                const triggerClass = item.trigger_type === 'automatic' ? 'info' : 'warning';
                const fileSize = item.file_size ? formatFileSize(item.file_size) : 'N/A';
                
                row.innerHTML = `
                    <td><input type=\"checkbox\" class=\"history-select\" value=\"${item.id}\"></td>
                    <td>${item.date}</td>
                    <td>${item.export_type_name || item.export_type}</td>
                    <td><span class="wc-s3-status ${triggerClass}">${item.trigger_type || 'manual'}</span></td>
                    <td>${item.file_name}</td>
                    <td><span class="wc-s3-status ${statusClass}">${item.status}</span></td>
                    <td>${fileSize}</td>
                    <td>
                        ${item.file_exists ? `<button type="button" class="wc-s3-btn info small" onclick="downloadExportFile('${item.file_path}')">Download</button>` : '<span class="wc-s3-status error">File not found</span>'}
                        <button type="button" class="wc-s3-btn warning small" onclick="deleteExportRecord('${item.id}')">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="8"><div class="wc-s3-table-loading"><div class="wc-s3-loading-text" style="color: #6b7280;">No export history found for the selected date range.</div></div></td></tr>';
        }
    })
    .catch(error => {
        tbody.innerHTML = '<tr><td colspan="8"><div class="wc-s3-table-loading"><div class="wc-s3-loading-text" style="color: #dc2626;">Error loading export history: ' + error.message + '</div></div></td></tr>';
        showNotification('error', 'Error loading export history: ' + error.message);
    });
}

function downloadExportFile(filePath) {
    const link = document.createElement('a');
    link.href = wcS3ExportPro.ajaxUrl + '?action=wc_s3_download_export_file&nonce=' + wcS3ExportPro.nonce + '&file_path=' + encodeURIComponent(filePath);
    link.download = filePath.split('/').pop(); // Suggest a filename
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function deleteExportRecord(recordId) {
    if (!confirm('Are you sure you want to delete this export record? This will also delete the associated file if it exists.')) {
        return;
    }
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=wc_s3_delete_export_record&nonce=' + wcS3ExportPro.nonce + '&record_id=' + recordId
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            loadExportHistory(); // Reload the history
        }
    })
    .catch(error => {
        showNotification('error', 'Error deleting export record: ' + error.message);
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// S3 Configuration Form
document.getElementById('s3-config-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'wc_s3_save_s3_config');
    formData.append('nonce', wcS3ExportPro.nonce);
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(error => {
        showNotification('error', 'Save failed: ' + error.message);
    });
});

// Export Settings Form
document.getElementById('export-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'wc_s3_save_export_settings');
    formData.append('nonce', wcS3ExportPro.nonce);
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.success ? 'success' : 'error', data.message);
        if (data.success) {
            setTimeout(() => location.reload(), 2000);
        }
    })
    .catch(error => {
        showNotification('error', 'Save failed: ' + error.message);
    });
});



// Add Export Type
function addExportType() {
    const container = document.getElementById('export-types-container');
    const newIndex = container.children.length;
    
    const newExportType = `
        <div class="wc-s3-export-type-section" data-index="${newIndex}">
            <div class="wc-s3-export-type-header">
                <h4>New Export Type</h4>
                <button type="button" class="wc-s3-btn error small remove-export-type" onclick="removeExportType(${newIndex})">
                    🗑️ Remove
                </button>
            </div>
            
            <div class="wc-s3-form-row">
                <div class="wc-s3-form-group">
                    <label for="export_type_${newIndex}_name">Export Name</label>
                    <input type="text" id="export_type_${newIndex}_name" 
                           name="export_types[${newIndex}][name]" 
                           placeholder="e.g., Web Sales, Customer Data" required>
                    <p class="description">A descriptive name for this export</p>
                </div>
                
                <div class="wc-s3-form-group">
                    <label for="export_type_${newIndex}_type">Export Type</label>
                    <select id="export_type_${newIndex}_type" 
                            name="export_types[${newIndex}][type]">
                        <option value="orders">Orders</option>
                        <option value="customers">Customers</option>
                        <option value="products">Products</option>
                        <option value="coupons">Coupons</option>
                    </select>
                    <p class="description">The type of data to export</p>
                </div>
            </div>
            
            <div class="wc-s3-form-row">
                <div class="wc-s3-form-group order-statuses-field" id="order_statuses_${newIndex}_container">
                    <label>Order Statuses</label>
                    <div class="wc-s3-status-checkboxes">
                        <div class="wc-s3-status-header">
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" class="wc-s3-select-all-statuses" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                <strong>Select All Statuses</strong>
                            </label>
                            <small>Or select specific statuses below:</small>
                        </div>
                        <div class="wc-s3-status-grid">
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" name="export_types[${newIndex}][statuses][]" value="wc-pending" class="wc-s3-status-checkbox" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                Pending payment
                            </label>
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" name="export_types[${newIndex}][statuses][]" value="wc-processing" class="wc-s3-status-checkbox" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                Processing
                            </label>
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" name="export_types[${newIndex}][statuses][]" value="wc-on-hold" class="wc-s3-status-checkbox" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                On hold
                            </label>
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" name="export_types[${newIndex}][statuses][]" value="wc-completed" class="wc-s3-status-checkbox" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                Completed
                            </label>
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" name="export_types[${newIndex}][statuses][]" value="wc-cancelled" class="wc-s3-status-checkbox" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                Cancelled
                            </label>
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" name="export_types[${newIndex}][statuses][]" value="wc-refunded" class="wc-s3-status-checkbox" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                Refunded
                            </label>
                            <label class="wc-s3-checkbox">
                                <input type="checkbox" name="export_types[${newIndex}][statuses][]" value="wc-failed" class="wc-s3-status-checkbox" data-export-index="${newIndex}">
                                <span class="checkmark"></span>
                                Failed
                            </label>
                        </div>
                        <p class="description">Orders with these statuses will be included. Leave all unchecked to include all statuses.</p>
                    </div>
                </div>
            </div>
            
            <div class="wc-s3-form-row">
                <div class="wc-s3-form-group">
                    <label for="export_type_${newIndex}_file_prefix">File Prefix</label>
                    <input type="text" id="export_type_${newIndex}_file_prefix" 
                           name="export_types[${newIndex}][file_prefix]" 
                           placeholder="e.g., FundsOnlineWebsiteSales">
                    <p class="description">Prefix for the exported file name</p>
                </div>
                
                <div class="wc-s3-form-group">
                    <label for="export_type_${newIndex}_s3_folder">S3 Folder</label>
                    <input type="text" id="export_type_${newIndex}_s3_folder" 
                           name="export_types[${newIndex}][s3_folder]" 
                           placeholder="e.g., web-sales">
                    <p class="description">Folder name within the S3 bucket</p>
                </div>
                
                <div class="wc-s3-form-group">
                    <label for="export_type_${newIndex}_local_uploads_folder">Local Uploads Folder</label>
                    <input type="text" id="export_type_${newIndex}_local_uploads_folder" 
                           name="export_types[${newIndex}][local_uploads_folder]" 
                           placeholder="e.g., web-sales">
                    <p class="description">Folder name in wp-content/uploads/wc-s3-exports/</p>
                </div>
            </div>
            
            <div class="wc-s3-form-row">
                <div class="wc-s3-form-group">
                    <label for="export_type_${newIndex}_frequency">Export Frequency</label>
                    <select id="export_type_${newIndex}_frequency" 
                            name="export_types[${newIndex}][frequency]">
                        <option value="hourly">Hourly</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                
                <div class="wc-s3-form-group">
                    <label for="export_type_${newIndex}_time">Export Time</label>
                    <input type="time" id="export_type_${newIndex}_time" 
                           name="export_types[${newIndex}][time]">
                </div>
                
                <div class="wc-s3-form-group">
                    <label style="display: flex; align-items: center; margin: 0;">
                        <input type="checkbox" name="export_types[${newIndex}][enabled]" value="1">
                        Enable this export
                    </label>
                </div>
            </div>
            
            <div class="wc-s3-form-group">
                <label for="export_type_${newIndex}_description">Description</label>
                <textarea id="export_type_${newIndex}_description" 
                          name="export_types[${newIndex}][description]" 
                          rows="2" placeholder="Optional description of what this export contains"></textarea>
            </div>
            
            <!-- Field Mapping Configuration -->
            <div class="wc-s3-field-mapping-section">
                <h4>📋 Field Mapping Configuration</h4>
                <p class="description">Configure which fields to include in this export. Drag to reorder, add custom fields, or remove unwanted columns.</p>
                
                <div class="wc-s3-field-mapping-table-container">
                    <table class="wc-s3-field-mapping-table" id="field-mapping-table-${newIndex}">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="select-all-${newIndex}" onchange="toggleAllFields(${newIndex}, this.checked)">
                                </th>
                                                        <th style="width: 30px;">⋮⋮</th>
                        <th style="width: 35%;">Column Name</th>
                        <th style="width: 35%;">Data Source</th>
                        <th style="width: 60px;">Action</th>
                    </tr>
                </thead>
                <tbody class="wc-s3-field-mapping-tbody" id="field-mapping-tbody-${newIndex}">
                    <!-- New export type starts with no fields -->
                </tbody>
                    </table>
                </div>
                
                <div class="wc-s3-field-mapping-actions">
                    <button type="button" class="wc-s3-btn secondary" onclick="addFieldRow(${newIndex})">➕ Add Column</button>
                    <button type="button" class="wc-s3-btn secondary" onclick="removeSelectedFields(${newIndex})">🗑️ Remove Selected</button>
                    <button type="button" class="wc-s3-btn secondary" onclick="loadDefaultFields(${newIndex}, 'orders')">📋 Load Default Fields</button>
                </div>
            </div>
            
            <input type="hidden" name="export_types[${newIndex}][id]" 
                   value="">
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', newExportType);
    
    // Initialize drag and drop for the new export type
    initializeDragAndDrop(newIndex);
}

// Remove Export Type
function removeExportType(index) {
    const container = document.getElementById('export-types-container');
    const exportTypeSection = container.children[index];
    
    if (confirm('Are you sure you want to remove this export type?')) {
        exportTypeSection.remove();
        // Re-index the remaining sections
        for (let i = 0; i < container.children.length; i++) {
            container.children[i].dataset.index = i;
        }
    }
}

// Field Mapping Functions
function addFieldRow(exportTypeIndex) {
    const tbody = document.getElementById(`field-mapping-tbody-${exportTypeIndex}`);
    const newFieldIndex = tbody.children.length;
    const exportType = document.getElementById(`export_type_${exportTypeIndex}_type`).value;
    
    const newRow = document.createElement('tr');
    newRow.className = 'wc-s3-field-row';
    newRow.dataset.fieldIndex = newFieldIndex;
    newRow.draggable = true;
    
    newRow.innerHTML = `
        <td>
            <input type="checkbox" name="export_types[${exportTypeIndex}][field_mappings][${newFieldIndex}][enabled]" value="1" checked>
        </td>
        <td class="drag-handle">⋮⋮</td>
        <td>
            <input type="text" name="export_types[${exportTypeIndex}][field_mappings][${newFieldIndex}][column_name]" 
                   placeholder="e.g., Custom Field" class="regular-text">
        </td>
        <td>
            <select name="export_types[${exportTypeIndex}][field_mappings][${newFieldIndex}][data_source]" class="data-source-select">
                ${getDataSourceOptionsHTML(exportType)}
            </select>
        </td>
        <td>
            <button type="button" class="wc-s3-btn error small remove-field-btn" 
                    data-action="remove-field" 
                    data-export-type="${exportTypeIndex}" 
                    data-field-index="${newFieldIndex}">
                🗑️
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    initializeDragAndDrop(exportTypeIndex);
}

function removeFieldRow(exportTypeIndex, fieldIndex) {
    const tbody = document.getElementById(`field-mapping-tbody-${exportTypeIndex}`);
    if (!tbody) {
        console.error('Table body not found for export type:', exportTypeIndex);
        return;
    }
    
    // Find the row by data-field-index attribute
    const row = tbody.querySelector(`tr[data-field-index="${fieldIndex}"]`);
    if (!row) {
        console.error('Row not found with field index:', fieldIndex);
        return;
    }
    
    if (confirm('Are you sure you want to remove this field?')) {
        console.log('Removing row with field index:', fieldIndex);
        row.remove();
        
        // Re-index remaining rows
        const remainingRows = tbody.querySelectorAll('tr');
        console.log('Remaining rows after removal:', remainingRows.length);
        
        remainingRows.forEach((row, index) => {
            row.dataset.fieldIndex = index;
            
            // Update all form elements
            const inputs = row.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.name;
                if (name) {
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    input.name = newName;
                }
            });
            
            // Update the remove button
            const removeButton = row.querySelector('button[data-action="remove-field"]');
            if (removeButton) {
                removeButton.setAttribute('data-field-index', index);
            }
        });
        
        console.log('Field removal completed successfully');
    }
}

function removeSelectedFields(exportTypeIndex) {
    const tbody = document.getElementById(`field-mapping-tbody-${exportTypeIndex}`);
    const selectedRows = tbody.querySelectorAll('input[type="checkbox"]:checked');
    
    if (selectedRows.length === 0) {
        alert('Please select fields to remove.');
        return;
    }
    
    if (confirm(`Are you sure you want to remove ${selectedRows.length} selected field(s)?`)) {
        selectedRows.forEach(checkbox => {
            checkbox.closest('tr').remove();
        });
        
        // Re-index remaining rows
        const remainingRows = tbody.querySelectorAll('tr');
        remainingRows.forEach((row, index) => {
            row.dataset.fieldIndex = index;
            const inputs = row.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.name;
                if (name) {
                    input.name = name.replace(/\[\d+\]/, `[${index}]`);
                }
            });
            // Update the remove button data attributes
            const removeButton = row.querySelector('button[data-action="remove-field"]');
            if (removeButton) {
                removeButton.setAttribute('data-field-index', index);
            }
        });
    }
}

function loadDefaultFields(exportTypeIndex, exportType) {
    if (confirm('This will replace all current field mappings with default fields. Continue?')) {
        const tbody = document.getElementById(`field-mapping-tbody-${exportTypeIndex}`);
        tbody.innerHTML = '';
        
        const defaultFields = getDefaultFields(exportType);
        defaultFields.forEach((field, index) => {
            const row = document.createElement('tr');
            row.className = 'wc-s3-field-row';
            row.dataset.fieldIndex = index;
            row.draggable = true;
            
            row.innerHTML = `
                <td>
                    <input type="checkbox" name="export_types[${exportTypeIndex}][field_mappings][${index}][enabled]" value="1" checked>
                </td>
                <td class="drag-handle">⋮⋮</td>
                <td>
                    <input type="text" name="export_types[${exportTypeIndex}][field_mappings][${index}][column_name]" 
                           value="${field.label}" class="regular-text">
                </td>
                <td>
                    <select name="export_types[${exportTypeIndex}][field_mappings][${index}][data_source]" class="data-source-select">
                        ${getDataSourceOptionsHTML(exportType, field.key)}
                    </select>
                </td>
                <td>
                    <button type="button" class="wc-s3-btn error small remove-field-btn" 
                            data-action="remove-field" 
                            data-export-type="${exportTypeIndex}" 
                            data-field-index="${index}">
                        🗑️
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        initializeDragAndDrop(exportTypeIndex);
    }
}

function toggleAllFields(exportTypeIndex, checked) {
    const tbody = document.getElementById(`field-mapping-tbody-${exportTypeIndex}`);
    const checkboxes = tbody.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
}

function initializeDragAndDrop(exportTypeIndex) {
    const tbody = document.getElementById(`field-mapping-tbody-${exportTypeIndex}`);
    if (!tbody) return;
    
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((row, rowIndex) => {
        row.draggable = true;
        row.dataset.rowIndex = rowIndex;
        
        // Remove existing event listeners
        row.removeEventListener('dragstart', handleDragStart);
        row.removeEventListener('dragover', handleDragOver);
        row.removeEventListener('drop', handleDrop);
        row.removeEventListener('dragenter', handleDragEnter);
        row.removeEventListener('dragleave', handleDragLeave);
        
        // Add new event listeners
        row.addEventListener('dragstart', handleDragStart);
        row.addEventListener('dragover', handleDragOver);
        row.addEventListener('drop', handleDrop);
        row.addEventListener('dragenter', handleDragEnter);
        row.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.target.dataset.rowIndex);
    e.target.classList.add('dragging');
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    const row = e.target.closest('tr');
    if (row && !row.classList.contains('dragging')) {
        row.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    const row = e.target.closest('tr');
    if (row) {
        row.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    const draggedRow = document.querySelector('.dragging');
    const dropRow = e.target.closest('tr');
    
    if (draggedRow && dropRow && draggedRow !== dropRow) {
        const tbody = draggedRow.parentNode;
        const draggedIndex = parseInt(draggedRow.dataset.rowIndex);
        const dropIndex = parseInt(dropRow.dataset.rowIndex);
        
        if (draggedIndex < dropIndex) {
            tbody.insertBefore(draggedRow, dropRow.nextSibling);
        } else {
            tbody.insertBefore(draggedRow, dropRow);
        }
        
        // Update row indices
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.dataset.rowIndex = index;
        });
    }
    
    // Clean up
    document.querySelectorAll('.dragging, .drag-over').forEach(el => {
        el.classList.remove('dragging', 'drag-over');
    });
}

function getDataSourceOptionsHTML(exportType, selectedValue = '') {
    const options = {
        'orders': [
            'order_id', 'order_number', 'order_number_formatted', 'order_date', 'status',
            'shipping_total', 'shipping_tax_total', 'fee_total', 'fee_tax_total', 'tax_total',
            'discount_total', 'order_total', 'refunded_total', 'order_currency', 'payment_method',
            'shipping_method', 'customer_id', 'billing_first_name', 'billing_last_name', 'billing_full_name',
            'billing_company', 'vat_number', 'billing_email', 'billing_phone', 'billing_address_1',
            'billing_address_2', 'billing_postcode', 'billing_city', 'billing_state', 'billing_state_code',
            'billing_country', 'shipping_first_name', 'shipping_last_name', 'shipping_full_name',
            'shipping_address_1', 'shipping_address_2', 'shipping_postcode', 'shipping_city',
            'shipping_state', 'shipping_state_code', 'shipping_country', 'shipping_company',
            'customer_note', 'item_id', 'item_product_id', 'item_name', 'item_sku', 'item_quantity',
            'item_subtotal', 'item_subtotal_tax', 'item_total', 'item_total_tax', 'item_refunded',
            'item_refunded_qty', 'item_meta', 'item_price', 'line_items', 'shipping_items',
            'fee_items', 'tax_items', 'coupon_items', 'refunds', 'order_notes',
            'download_permissions', 'order_meta'
        ],
        'order_items': [
            'order_id', 'order_item_id', 'product_id', 'product_name', 'product_sku',
            'product_variation_id', 'product_variation_sku', 'product_variation_attributes',
            'quantity', 'line_total', 'line_subtotal', 'line_tax', 'line_subtotal_tax', 'product_meta',
            'order_date', 'order_status', 'order_number', 'order_number_formatted', 'customer_id',
            'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
            'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state',
            'billing_postcode', 'billing_country', 'shipping_first_name', 'shipping_last_name',
            'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state',
            'shipping_postcode', 'shipping_country', 'payment_method', 'payment_method_title',
            'order_total', 'order_subtotal', 'order_tax', 'order_shipping', 'order_discount',
            'order_currency', 'customer_note', 'shipping_total', 'shipping_tax_total', 'fee_total',
            'tax_total', 'discount_total', 'refunded_total', 'shipping_method', 'order_meta',
            'product_type', 'product_status', 'product_price', 'product_regular_price',
            'product_sale_price', 'product_description', 'product_short_description',
            'product_categories', 'product_tags', 'product_stock_quantity', 'product_stock_status',
            'product_weight', 'product_dimensions'
        ],
        'customers': [
            'customer_id', 'user_id', 'username', 'email', 'first_name', 'last_name', 'display_name',
            'role', 'date_registered', 'total_spent', 'order_count', 'last_order_date',
            'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
            'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state',
            'billing_postcode', 'billing_country', 'customer_meta'
        ],
        'products': [
            'product_id', 'product_name', 'product_sku', 'product_type', 'product_status',
            'product_price', 'product_regular_price', 'product_sale_price', 'product_description',
            'product_short_description', 'product_categories', 'product_tags', 'product_stock_quantity',
            'product_stock_status', 'product_weight', 'product_dimensions', 'product_meta'
        ],
        'coupons': [
            'coupon_id', 'coupon_code', 'coupon_type', 'coupon_amount', 'coupon_description',
            'coupon_date_expires', 'coupon_usage_count', 'coupon_individual_use', 'coupon_product_ids',
            'coupon_excluded_product_ids', 'coupon_product_categories', 'coupon_excluded_product_categories',
            'coupon_usage_limit', 'coupon_usage_limit_per_user', 'coupon_limit_usage_to_x_items',
            'coupon_free_shipping', 'coupon_meta'
        ]
    };
    
    const optionList = options[exportType] || options['orders'];
    
    // Create a mapping of field keys to proper labels
    const fieldLabels = {
        'order_id': 'Order ID',
        'order_number': 'Order Number',
        'order_number_formatted': 'Order Number (Formatted)',
        'order_date': 'Order Date',
        'status': 'Order Status',
        'shipping_total': 'Shipping Total',
        'shipping_tax_total': 'Shipping Tax Total',
        'fee_total': 'Fee Total',
        'fee_tax_total': 'Fee Tax Total',
        'tax_total': 'Tax Total',
        'discount_total': 'Discount Total',
        'order_total': 'Order Total',
        'refunded_total': 'Refunded Total',
        'order_currency': 'Order Currency',
        'payment_method': 'Payment Method',
        'shipping_method': 'Shipping Method',
        'customer_id': 'Customer ID',
        'billing_first_name': 'Billing First Name',
        'billing_last_name': 'Billing Last Name',
        'billing_full_name': 'Billing Full Name',
        'billing_company': 'Billing Company',
        'vat_number': 'VAT Number',
        'billing_email': 'Billing Email',
        'billing_phone': 'Billing Phone',
        'billing_address_1': 'Billing Address 1',
        'billing_address_2': 'Billing Address 2',
        'billing_postcode': 'Billing Postcode',
        'billing_city': 'Billing City',
        'billing_state': 'Billing State',
        'billing_state_code': 'Billing State Code',
        'billing_country': 'Billing Country',
        'shipping_first_name': 'Shipping First Name',
        'shipping_last_name': 'Shipping Last Name',
        'shipping_full_name': 'Shipping Full Name',
        'shipping_address_1': 'Shipping Address 1',
        'shipping_address_2': 'Shipping Address 2',
        'shipping_postcode': 'Shipping Postcode',
        'shipping_city': 'Shipping City',
        'shipping_state': 'Shipping State',
        'shipping_state_code': 'Shipping State Code',
        'shipping_country': 'Shipping Country',
        'shipping_company': 'Shipping Company',
        'customer_note': 'Customer Note',
        'item_id': 'Item ID',
        'item_product_id': 'Item Product ID',
        'item_name': 'Item Name',
        'item_sku': 'Item SKU',
        'item_quantity': 'Item Quantity',
        'item_subtotal': 'Item Subtotal',
        'item_subtotal_tax': 'Item Subtotal Tax',
        'item_total': 'Item Total',
        'item_total_tax': 'Item Total Tax',
        'item_refunded': 'Item Refunded',
        'item_refunded_qty': 'Item Refunded Quantity',
        'item_meta': 'Item Meta',
        'item_price': 'Item Price',
        'line_items': 'Line Items',
        'shipping_items': 'Shipping Items',
        'fee_items': 'Fee Items',
        'tax_items': 'Tax Items',
        'coupon_items': 'Coupon Items',
        'refunds': 'Refunds',
        'order_notes': 'Order Notes',
        'download_permissions': 'Download Permissions',
        'order_meta': 'Order Meta'
    };
    
    return optionList.map(option => {
        const label = fieldLabels[option] || option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        return `<option value="${option}" ${option === selectedValue ? 'selected' : ''}>${label}</option>`;
    }).join('');
}

function getDefaultFields(exportType) {
    const fields = {
        'orders': [
            { key: 'order_id', label: 'Order ID' },
            { key: 'order_date', label: 'Order Date' },
            { key: 'order_status', label: 'Order Status' },
            { key: 'customer_id', label: 'Customer ID' },
            { key: 'billing_email', label: 'Billing Email' },
            { key: 'order_total', label: 'Order Total' },
            { key: 'payment_method', label: 'Payment Method' },
            { key: 'billing_first_name', label: 'Billing First Name' },
            { key: 'billing_last_name', label: 'Billing Last Name' },
            { key: 'shipping_total', label: 'Shipping Total' }
        ],
        'order_items': [
            { key: 'order_id', label: 'Order ID' },
            { key: 'product_id', label: 'Product ID' },
            { key: 'product_name', label: 'Product Name' },
            { key: 'quantity', label: 'Quantity' },
            { key: 'line_total', label: 'Line Total' }
        ],
        'customers': [
            { key: 'customer_id', label: 'Customer ID' },
            { key: 'email', label: 'Email' },
            { key: 'first_name', label: 'First Name' },
            { key: 'last_name', label: 'Last Name' },
            { key: 'total_spent', label: 'Total Spent' }
        ]
    };
    
    return fields[exportType] || fields['orders'];
}

// Show Notification
function showNotification(type, message) {
    const notifications = document.getElementById('notifications');
    const notification = document.createElement('div');
    notification.className = `wc-s3-notification ${type}`;
    notification.textContent = message;
    
    notifications.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Initialize drag and drop for existing export types and set up event delegation
document.addEventListener('DOMContentLoaded', function() {
    // Export Types Form
    const exportTypesForm = document.getElementById('export-types-form');
    if (exportTypesForm) {
        console.log('Export types form found, attaching event listener');
        
        // Remove any existing event listeners
        const newForm = exportTypesForm.cloneNode(true);
        exportTypesForm.parentNode.replaceChild(newForm, exportTypesForm);
        
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submission started');
            
            // Prevent multiple submissions
            if (this.submitting) {
                console.log('Form already submitting, ignoring');
                return;
            }
            
            this.submitting = true;
            
            const formData = new FormData(this);
            formData.append('action', 'wc_s3_save_export_types_config');
            formData.append('nonce', wcS3ExportPro.nonce);
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.textContent = '💾 Saving...';
            submitButton.disabled = true;
            
            console.log('Sending form data to:', wcS3ExportPro.ajaxUrl);
            
            // Debug: Log the form data being sent
            for (let [key, value] of formData.entries()) {
                console.log('Form data:', key, '=', value);
            }
            
            fetch(wcS3ExportPro.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                showNotification(data.success ? 'success' : 'error', data.message);
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                showNotification('error', 'Save failed: ' + error.message);
            })
            .finally(() => {
                // Restore button state and allow resubmission
                submitButton.textContent = originalText;
                submitButton.disabled = false;
                this.submitting = false;
            });
        });
    } else {
        console.error('Export types form not found');
    }
    
    const exportTypeSections = document.querySelectorAll('.wc-s3-export-type-section');
    exportTypeSections.forEach(section => {
        const index = section.dataset.index;
        if (index !== undefined) {
            initializeDragAndDrop(parseInt(index));
        }
    });
    
    // Handle export type change to show/hide order statuses field
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name*="[type]"]')) {
            const exportTypeSection = e.target.closest('.wc-s3-export-type-section');
            const index = exportTypeSection.dataset.index;
            const orderStatusesContainer = document.getElementById(`order_statuses_${index}_container`);
            
            if (e.target.value === 'orders') {
                orderStatusesContainer.style.display = 'block';
            } else {
                orderStatusesContainer.style.display = 'none';
            }
        }
    });
    
    // Handle "Select All" checkbox for order statuses
    document.addEventListener('change', function(e) {
        if (e.target.matches('.wc-s3-select-all-statuses')) {
            const exportIndex = e.target.getAttribute('data-export-index');
            const statusCheckboxes = document.querySelectorAll(`.wc-s3-status-checkbox[data-export-index="${exportIndex}"]`);
            
            statusCheckboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        }
    });
    
    // Handle individual status checkboxes to update "Select All"
    document.addEventListener('change', function(e) {
        if (e.target.matches('.wc-s3-status-checkbox')) {
            const exportIndex = e.target.getAttribute('data-export-index');
            const selectAllCheckbox = document.querySelector(`.wc-s3-select-all-statuses[data-export-index="${exportIndex}"]`);
            const statusCheckboxes = document.querySelectorAll(`.wc-s3-status-checkbox[data-export-index="${exportIndex}"]`);
            const checkedCheckboxes = document.querySelectorAll(`.wc-s3-status-checkbox[data-export-index="${exportIndex}"]:checked`);
            
            // Update "Select All" checkbox state
            if (checkedCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCheckboxes.length === statusCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
    });
    
    // Set up event delegation for remove field buttons
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-field-btn')) {
            e.preventDefault();
            const exportTypeIndex = parseInt(e.target.getAttribute('data-export-type')) || 0;
            const fieldIndex = parseInt(e.target.getAttribute('data-field-index')) || 0;
            
            if (isNaN(fieldIndex)) {
                console.error('Invalid field index:', e.target.getAttribute('data-field-index'));
                return;
            }
            
            console.log('Remove button clicked - Export Type:', exportTypeIndex, 'Field Index:', fieldIndex);
            removeFieldRow(exportTypeIndex, fieldIndex);
        }
    });

    // Bulk selection handlers for export history
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'history_select_all') {
            const checked = e.target.checked;
            document.querySelectorAll('#export-history-tbody .history-select').forEach(cb => cb.checked = checked);
            updateDeleteSelectedState();
        }
        if (e.target && e.target.classList.contains('history-select')) {
            syncHistorySelectAll();
            updateDeleteSelectedState();
        }
    });
});

function syncHistorySelectAll() {
    const all = document.querySelectorAll('#export-history-tbody .history-select');
    const selected = document.querySelectorAll('#export-history-tbody .history-select:checked');
    const selectAll = document.getElementById('history_select_all');
    if (!selectAll) return;
    if (selected.length === 0) { selectAll.checked = false; selectAll.indeterminate = false; }
    else if (selected.length === all.length) { selectAll.checked = true; selectAll.indeterminate = false; }
    else { selectAll.checked = false; selectAll.indeterminate = true; }
}

function updateDeleteSelectedState() {
    const btn = document.getElementById('delete-selected-btn');
    if (!btn) return;
    const selected = document.querySelectorAll('#export-history-tbody .history-select:checked');
    btn.disabled = selected.length === 0;
}

function deleteSelectedExportRecords() {
    const ids = Array.from(document.querySelectorAll('#export-history-tbody .history-select:checked')).map(cb => cb.value);
    if (ids.length === 0) return;
    if (!confirm(`Delete ${ids.length} selected record(s)? This will also delete the associated files if they exist.`)) return;
    const params = new URLSearchParams();
    params.append('action', 'wc_s3_delete_export_records');
    params.append('nonce', wcS3ExportPro.nonce);
    ids.forEach(id => params.append('record_ids[]', id));
    fetch(wcS3ExportPro.ajaxUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params.toString() })
        .then(r => r.json())
        .then(data => {
            showNotification(data.success ? 'success' : 'error', (data.data && data.data.message) ? data.data.message : (data.success ? 'Deleted' : 'Delete failed'));
            loadExportHistory();
        })
        .catch(err => showNotification('error', 'Delete failed: ' + err.message));
}

// Auto-refresh status every 30 seconds
setInterval(() => {
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=wc_s3_get_export_status&nonce=' + wcS3ExportPro.nonce
    })
    .then(response => response.json())
    .then(data => {
        // Update status cards if needed
        if (data.success && data.data) {
            // Update export status
            const exportCard = document.querySelector('.wc-s3-status-card:last-child');
            if (exportCard) {
                const statusIndicator = exportCard.querySelector('.status-indicator');
                const statusText = data.data.status === 'active' ? 'Active' : 'Inactive';
                const statusClass = data.data.status === 'active' ? 'success' : 'warning';
                
                statusIndicator.className = `status-indicator ${statusClass}`;
                statusIndicator.textContent = statusText;
                
                exportCard.className = `wc-s3-status-card ${statusClass}`;
            }
        }
    })
    .catch(error => {
        console.log('Status refresh failed:', error);
    });
}, 30000);
</script> 