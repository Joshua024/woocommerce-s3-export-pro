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
                <label for="export_frequency">Export Frequency</label>
                <select id="export_frequency" name="export_frequency">
                    <option value="daily" <?php selected($current_settings['export_frequency'] ?? 'daily', 'daily'); ?>>Daily</option>
                    <option value="weekly" <?php selected($current_settings['export_frequency'] ?? 'daily', 'weekly'); ?>>Weekly</option>
                    <option value="monthly" <?php selected($current_settings['export_frequency'] ?? 'daily', 'monthly'); ?>>Monthly</option>
                </select>
            </div>
            
            <div class="wc-s3-form-group">
                <label for="export_time">Export Time</label>
                <input type="time" id="export_time" name="export_time" 
                       value="<?php echo esc_attr($current_settings['export_time'] ?? '02:00'); ?>">
            </div>
            
            <div class="wc-s3-form-group">
                <label for="export_types">Export Types</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <label style="display: flex; align-items: center; margin: 0;">
                        <input type="checkbox" name="export_types[]" value="orders" 
                               <?php checked(in_array('orders', $current_settings['export_types'] ?? array('orders'))); ?> 
                               style="margin-right: 0.5rem;">
                        Orders
                    </label>
                    <label style="display: flex; align-items: center; margin: 0;">
                        <input type="checkbox" name="export_types[]" value="customers" 
                               <?php checked(in_array('customers', $current_settings['export_types'] ?? array('orders'))); ?> 
                               style="margin-right: 0.5rem;">
                        Customers
                    </label>
                    <label style="display: flex; align-items: center; margin: 0;">
                        <input type="checkbox" name="export_types[]" value="products" 
                               <?php checked(in_array('products', $current_settings['export_types'] ?? array())); ?> 
                               style="margin-right: 0.5rem;">
                        Products
                    </label>
                </div>
            </div>
            
            <button type="submit" class="wc-s3-btn primary">💾 Save Export Settings</button>
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