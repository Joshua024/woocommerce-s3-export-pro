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
                'order_status' => 'Order Status',
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
                'product_meta' => 'Product Meta'
            );
            break;
            
        case 'customers':
            $options = array(
                'customer_id' => 'Customer ID',
                'user_id' => 'User ID',
                'username' => 'Username',
                'email' => 'Email',
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'display_name' => 'Display Name',
                'role' => 'Role',
                'date_registered' => 'Date Registered',
                'total_spent' => 'Total Spent',
                'order_count' => 'Order Count',
                'last_order_date' => 'Last Order Date',
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
                'coupon_id' => 'Coupon ID',
                'coupon_code' => 'Coupon Code',
                'coupon_type' => 'Coupon Type',
                'coupon_amount' => 'Coupon Amount',
                'coupon_description' => 'Coupon Description',
                'coupon_date_expires' => 'Coupon Date Expires',
                'coupon_usage_count' => 'Coupon Usage Count',
                'coupon_individual_use' => 'Coupon Individual Use',
                'coupon_product_ids' => 'Coupon Product IDs',
                'coupon_excluded_product_ids' => 'Coupon Excluded Product IDs',
                'coupon_product_categories' => 'Coupon Product Categories',
                'coupon_excluded_product_categories' => 'Coupon Excluded Product Categories',
                'coupon_usage_limit' => 'Coupon Usage Limit',
                'coupon_usage_limit_per_user' => 'Coupon Usage Limit Per User',
                'coupon_limit_usage_to_x_items' => 'Coupon Limit Usage To X Items',
                'coupon_free_shipping' => 'Coupon Free Shipping',
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
            <?php else: ?>
                <p><strong>Status:</strong> ⚠️ <?php echo $s3_status['message']; ?></p>
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
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="wc-s3-actions">
        <button class="wc-s3-btn primary" onclick="testS3Connection()">
            <span class="wc-s3-loading" id="s3-loading" style="display: none;"></span>
            🔗 Test S3 Connection
        </button>
        
        <button class="wc-s3-btn secondary" onclick="runManualExport()">
            <span class="wc-s3-loading" id="export-loading" style="display: none;"></span>
            📤 Run Manual Export
        </button>
        
        <button class="wc-s3-btn success" onclick="setupAutomation()">
            ⚙️ Setup Automation
        </button>
        
        <button class="wc-s3-btn warning" onclick="viewLogs()">
            📋 View Logs
        </button>
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
                                    <option value="orders" <?php selected($export_type['type'] ?? 'orders', 'orders'); ?>>Orders (Web Sales)</option>
                                    <option value="order_items" <?php selected($export_type['type'] ?? 'orders', 'order_items'); ?>>Order Items (Web Sale Lines)</option>
                                    <option value="customers" <?php selected($export_type['type'] ?? 'orders', 'customers'); ?>>Customers</option>
                                    <option value="products" <?php selected($export_type['type'] ?? 'orders', 'products'); ?>>Products</option>
                                    <option value="coupons" <?php selected($export_type['type'] ?? 'orders', 'coupons'); ?>>Coupons</option>
                                </select>
                                <p class="description">The type of data to export</p>
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
                                        if (empty($current_field_mappings)) {
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
                                            <tr class="wc-s3-field-row" data-field-index="<?php echo $field_index; ?>">
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
                                                    <button type="button" class="wc-s3-btn error small" onclick="removeFieldRow(<?php echo $index; ?>, <?php echo $field_index; ?>)">
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
    
    loading.style.display = 'inline-flex';
    button.disabled = true;
    
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
            location.reload();
        }
    })
    .catch(error => {
        showNotification('error', 'Connection test failed: ' + error.message);
    })
    .finally(() => {
        loading.style.display = 'none';
        button.disabled = false;
    });
}

// Manual Export
function runManualExport() {
    const loading = document.getElementById('export-loading');
    const button = loading.parentElement;
    
    loading.style.display = 'inline-flex';
    button.disabled = true;
    
    fetch(wcS3ExportPro.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=wc_s3_run_manual_export&nonce=' + wcS3ExportPro.nonce
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.success ? 'success' : 'error', data.message);
        if (data.success) {
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
    window.open('<?php echo admin_url('admin.php?page=wc-s3-export-pro&tab=logs'); ?>', '_blank');
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

// Export Types Form
document.getElementById('export-types-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'wc_s3_save_export_types_config');
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
                        <option value="orders">Orders (Web Sales)</option>
                        <option value="order_items">Order Items (Web Sale Lines)</option>
                        <option value="customers">Customers</option>
                        <option value="products">Products</option>
                        <option value="coupons">Coupons</option>
                    </select>
                    <p class="description">The type of data to export</p>
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
                    <!-- Default fields will be loaded here -->
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
    
    // Load default fields for the new export type
    loadDefaultFields(newIndex, 'orders');
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
            <button type="button" class="wc-s3-btn error small" onclick="removeFieldRow(${exportTypeIndex}, ${newFieldIndex})">
                🗑️
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    initializeDragAndDrop(exportTypeIndex);
}

function removeFieldRow(exportTypeIndex, fieldIndex) {
    const tbody = document.getElementById(`field-mapping-tbody-${exportTypeIndex}`);
    const rows = tbody.querySelectorAll('tr');
    
    // Find the row by index (since fieldIndex is the position, not the data attribute)
    const row = rows[fieldIndex];
    
    if (row && confirm('Are you sure you want to remove this field?')) {
        row.remove();
        
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
            // Update the onclick attribute for the remove button
            const removeButton = row.querySelector('button[onclick*="removeFieldRow"]');
            if (removeButton) {
                removeButton.setAttribute('onclick', `removeFieldRow(${exportTypeIndex}, ${index})`);
            }
        });
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
            // Update the onclick attribute for the remove button
            const removeButton = row.querySelector('button[onclick*="removeFieldRow"]');
            if (removeButton) {
                removeButton.setAttribute('onclick', `removeFieldRow(${exportTypeIndex}, ${index})`);
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
                    <button type="button" class="wc-s3-btn error small" onclick="removeFieldRow(${exportTypeIndex}, ${index})">
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
            'order_id', 'order_number', 'order_number_formatted', 'order_date', 'order_status',
            'customer_id', 'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
            'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country',
            'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 'shipping_address_2',
            'shipping_city', 'shipping_state', 'shipping_postcode', 'shipping_country',
            'payment_method', 'payment_method_title', 'order_total', 'order_subtotal', 'order_tax',
            'order_shipping', 'order_discount', 'order_currency', 'customer_note', 'shipping_total',
            'shipping_tax_total', 'fee_total', 'tax_total', 'discount_total', 'refunded_total',
            'shipping_method', 'order_meta'
        ],
        'order_items': [
            'order_id', 'order_item_id', 'product_id', 'product_name', 'product_sku',
            'product_variation_id', 'product_variation_sku', 'product_variation_attributes',
            'quantity', 'line_total', 'line_subtotal', 'line_tax', 'line_subtotal_tax', 'product_meta'
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
    return optionList.map(option => 
        `<option value="${option}" ${option === selectedValue ? 'selected' : ''}>${option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</option>`
    ).join('');
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

// Initialize drag and drop for existing export types
document.addEventListener('DOMContentLoaded', function() {
    const exportTypeSections = document.querySelectorAll('.wc-s3-export-type-section');
    exportTypeSections.forEach(section => {
        const index = section.dataset.index;
        if (index !== undefined) {
            initializeDragAndDrop(parseInt(index));
        }
    });
});

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