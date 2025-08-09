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
            
            <input type="hidden" name="export_types[${newIndex}][id]" 
                   value="">
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', newExportType);
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