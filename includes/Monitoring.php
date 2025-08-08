<?php
namespace WC_S3_Export_Pro;

/**
 * Monitoring Class
 * 
 * Handles system monitoring, health checks, and status reporting.
 */
class Monitoring {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new Settings();
    }
    
    /**
     * Run health check
     */
    public function run_health_check() {
        $issues = $this->validate_export_system();
        
        if (!empty($issues)) {
            $this->send_failure_alert('Daily health check failed', $issues);
        }
        
        return $issues;
    }
    
    /**
     * Validate export system
     */
    public function validate_export_system() {
        $issues = array();
        
        // Check if WooCommerce CSV Export plugin is active
        if (!function_exists('wc_customer_order_csv_export')) {
            $issues[] = 'WooCommerce CSV Export plugin is not active';
        }
        
        // Check if export configurations exist
        if (function_exists('wc_customer_order_csv_export')) {
            try {
                $csv_export = wc_customer_order_csv_export();
                $export_handler = $csv_export->get_export_handler_instance();
                $exports = $export_handler->get_exports();
                
                if (empty($exports) || count($exports) < 2) {
                    $issues[] = 'Export configurations are missing or incomplete';
                }
            } catch (\Exception $e) {
                $issues[] = 'Failed to access export configurations: ' . $e->getMessage();
            }
        }
        
        // Check automation settings
        global $wpdb;
        $automation_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '%wc_customer_order_csv_export%auto_export_enabled%' 
             AND option_value = 'yes'"
        );
        
        if ($automation_count == 0) {
            $issues[] = 'No automation settings found in database';
        }
        
        // Check Action Scheduler
        if (function_exists('as_get_scheduled_actions')) {
            $pending_actions = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'pending'
            ));
            
            if (empty($pending_actions)) {
                $issues[] = 'No pending export actions found in Action Scheduler';
            }
        }
        
        // Check S3 configuration
        $s3_config = $this->settings->get_s3_config();
        if (empty($s3_config['access_key']) || empty($s3_config['secret_key'])) {
            $issues[] = 'S3 credentials are not configured';
        }
        
        // Check log file
        $log_file = $this->get_log_file();
        if (!file_exists($log_file)) {
            $issues[] = 'Log file does not exist';
        }
        
        return $issues;
    }
    
    /**
     * Get system status
     */
    public function get_system_status() {
        $status = array(
            'overall' => 'healthy',
            'woocommerce' => false,
            'csv_export' => false,
            'action_scheduler' => false,
            's3_configured' => false,
            'issues' => array()
        );
        
        // Check WooCommerce
        $status['woocommerce'] = class_exists('WooCommerce');
        if (!$status['woocommerce']) {
            $status['issues'][] = 'WooCommerce is not active';
            $status['overall'] = 'unhealthy';
        }
        
        // Check CSV Export plugin
        $status['csv_export'] = function_exists('wc_customer_order_csv_export');
        if (!$status['csv_export']) {
            $status['issues'][] = 'WooCommerce CSV Export plugin is not active';
            $status['overall'] = 'unhealthy';
        }
        
        // Check Action Scheduler
        $status['action_scheduler'] = function_exists('as_get_scheduled_actions');
        if (!$status['action_scheduler']) {
            $status['issues'][] = 'Action Scheduler is not available';
            $status['overall'] = 'unhealthy';
        }
        
        // Check S3 configuration
        $s3_config = $this->settings->get_s3_config();
        $status['s3_configured'] = !empty($s3_config['access_key']) && !empty($s3_config['secret_key']);
        if (!$status['s3_configured']) {
            $status['issues'][] = 'S3 credentials are not configured';
        }
        
        return $status;
    }
    
    /**
     * Get export status
     */
    public function get_export_status() {
        $status = array(
            'status' => 'inactive',
            'last_export' => null,
            'next_export' => null,
            'total_exports' => 0,
            's3_connected' => false,
            'automation_enabled' => false,
            'pending_jobs' => 0,
        );
        
        // Check last export
        if (function_exists('as_get_scheduled_actions')) {
            $recent_completed = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'complete',
                'per_page' => 1
            ));
            
            if (!empty($recent_completed)) {
                $last_action = reset($recent_completed);
                $status['last_export'] = $last_action->get_schedule()->get_date()->format('Y-m-d H:i:s');
            }
        }
        
        // Check S3 connection
        $s3_config = $this->settings->get_s3_config();
        $status['s3_connected'] = !empty($s3_config['access_key']) && !empty($s3_config['secret_key']);
        
        // Check automation
        global $wpdb;
        $automation_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '%wc_customer_order_csv_export%auto_export_enabled%' 
             AND option_value = 'yes'"
        );
        $status['automation_enabled'] = $automation_count > 0;
        
        // Check pending jobs
        if (function_exists('as_get_scheduled_actions')) {
            $pending_actions = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'pending'
            ));
            $status['pending_jobs'] = count($pending_actions);
            
            // Set status based on automation and pending jobs
            if ($status['automation_enabled']) {
                $status['status'] = 'active';
            }
            
            // Get next export time
            if (!empty($pending_actions)) {
                $next_action = reset($pending_actions);
                $status['next_export'] = $next_action->get_schedule()->get_date()->format('Y-m-d H:i:s');
            }
            
            // Get total exports count
            $completed_actions = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'complete',
                'per_page' => -1
            ));
            $status['total_exports'] = count($completed_actions);
        }
        
        return $status;
    }
    
    /**
     * Send failure alert
     */
    private function send_failure_alert($type, $details) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Sending failure alert: $type", $log_file);
        
        // Log the alert
        $alert_message = "Export failure alert: $type\n";
        $alert_message .= "Details: " . implode(', ', $details) . "\n";
        $alert_message .= "Time: " . $timestamp . "\n";
        $alert_message .= "Site: " . get_site_url() . "\n";
        
        $this->log($alert_message, $log_file);
        
        // Could send email here if configured
        $settings = $this->settings->get_settings();
        if ($settings['enable_notifications']) {
            // Email notification logic would go here
        }
    }
    
    /**
     * WP-CLI: Check scheduler
     */
    public function cli_check_scheduler() {
        \WP_CLI::line("=== ACTION SCHEDULER DIAGNOSTIC ===\n");
        
        if (!function_exists('as_get_scheduled_actions')) {
            \WP_CLI::error("Action Scheduler is not available!");
            return;
        }
        
        // Check for export-related actions
        $export_hooks = array(
            'wc_customer_order_csv_export_auto_export',
            'wc_s3_export_automation',
            'wc_s3_export_health_check'
        );
        
        foreach ($export_hooks as $hook) {
            \WP_CLI::line("Hook: $hook");
            
            $pending = as_get_scheduled_actions(array('hook' => $hook, 'status' => 'pending'));
            $complete = as_get_scheduled_actions(array('hook' => $hook, 'status' => 'complete', 'per_page' => 5));
            $failed = as_get_scheduled_actions(array('hook' => $hook, 'status' => 'failed', 'per_page' => 5));
            
            \WP_CLI::line("  Pending: " . count($pending));
            \WP_CLI::line("  Recent Complete: " . count($complete));
            \WP_CLI::line("  Recent Failed: " . count($failed));
            
            if (!empty($complete)) {
                $last_complete = reset($complete);
                $last_run = $last_complete->get_schedule()->get_date();
                \WP_CLI::line("  Last successful run: " . $last_run->format('Y-m-d H:i:s T'));
            }
            
            if (!empty($failed)) {
                $last_failed = reset($failed);
                $fail_date = $last_failed->get_schedule()->get_date();
                \WP_CLI::line("  Last failure: " . $fail_date->format('Y-m-d H:i:s T'));
            }
            
            \WP_CLI::line("");
        }
        
        // Check WooCommerce CSV Export automations in database
        global $wpdb;
        $automations = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE '%wc_customer_order_csv_export%auto%' 
             OR option_name LIKE '%wc_customer_order_csv_export%export%'"
        );
        
        \WP_CLI::line("=== EXPORT CONFIGURATIONS IN DATABASE ===");
        if (empty($automations)) {
            \WP_CLI::error("No WooCommerce CSV Export configurations found!");
        } else {
            foreach ($automations as $automation) {
                \WP_CLI::line("Config: " . $automation->option_name);
                if (strlen($automation->option_value) > 100) {
                    \WP_CLI::line("  Value: [" . strlen($automation->option_value) . " bytes of data]");
                } else {
                    \WP_CLI::line("  Value: " . $automation->option_value);
                }
            }
        }
    }
    
    /**
     * WP-CLI: Monitor exports
     */
    public function cli_monitor_exports() {
        \WP_CLI::line("=== EXPORT SYSTEM MONITOR ===\n");
        
        // Check export configurations
        if (!function_exists('wc_customer_order_csv_export')) {
            \WP_CLI::error("WooCommerce CSV Export plugin is not active!");
            return;
        }
        
        $csv_export = wc_customer_order_csv_export();
        $export_handler = $csv_export->get_export_handler_instance();
        $exports = $export_handler->get_exports();
        
        \WP_CLI::line("Export Configurations: " . count($exports));
        foreach ($exports as $export) {
            \WP_CLI::line("  - " . $export->get_name() . " (ID: " . $export->get_id() . ", Type: " . $export->get_type() . ")");
        }
        
        // Check automation settings
        global $wpdb;
        $automation_settings = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE '%wc_customer_order_csv_export%auto_export_enabled%' 
             AND option_value = 'yes'"
        );
        
        \WP_CLI::line("\nAutomation Settings: " . count($automation_settings) . " enabled");
        
        // Check Action Scheduler
        if (function_exists('as_get_scheduled_actions')) {
            $pending_actions = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'pending'
            ));
            
            \WP_CLI::line("Pending Export Actions: " . count($pending_actions));
            
            if (!empty($pending_actions)) {
                foreach ($pending_actions as $action) {
                    $next_run = $action->get_schedule()->get_date();
                    $export_id = $action->get_args()[0] ?? 'unknown';
                    \WP_CLI::line("  - Export ID $export_id: " . $next_run->format('Y-m-d H:i:s T'));
                }
            }
            
            // Check recent completed actions
            $recent_completed = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'complete',
                'per_page' => 5
            ));
            
            \WP_CLI::line("\nRecent Completed Exports: " . count($recent_completed));
            foreach ($recent_completed as $action) {
                $completed_date = $action->get_schedule()->get_date();
                $export_id = $action->get_args()[0] ?? 'unknown';
                \WP_CLI::line("  - Export ID $export_id: " . $completed_date->format('Y-m-d H:i:s T'));
            }
            
            // Check failed actions
            $failed_actions = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'failed',
                'per_page' => 5
            ));
            
            \WP_CLI::line("\nRecent Failed Exports: " . count($failed_actions));
            foreach ($failed_actions as $action) {
                $failed_date = $action->get_schedule()->get_date();
                $export_id = $action->get_args()[0] ?? 'unknown';
                \WP_CLI::line("  - Export ID $export_id: " . $failed_date->format('Y-m-d H:i:s T'));
            }
        }
        
        // Check log file
        $log_file = $this->get_log_file();
        if (file_exists($log_file)) {
            $log_size = filesize($log_file);
            $log_lines = count(file($log_file));
            \WP_CLI::line("\nLog File Status:");
            \WP_CLI::line("  - File: " . basename($log_file));
            \WP_CLI::line("  - Size: " . number_format($log_size) . " bytes");
            \WP_CLI::line("  - Lines: " . number_format($log_lines));
            
            // Show last few log entries
            $last_lines = array_slice(file($log_file), -10);
            \WP_CLI::line("\nLast 10 Log Entries:");
            foreach ($last_lines as $line) {
                \WP_CLI::line("  " . trim($line));
            }
        } else {
            \WP_CLI::line("\nLog File: Not found");
        }
        
        // Overall status
        $issues = array();
        if (count($automation_settings) === 0) {
            $issues[] = "No automation settings found";
        }
        if (empty($pending_actions)) {
            $issues[] = "No pending export actions";
        }
        if (count($failed_actions) > 0) {
            $issues[] = "Recent export failures detected";
        }
        
        if (empty($issues)) {
            \WP_CLI::success("\n✓ Export system appears to be working correctly");
        } else {
            \WP_CLI::warning("\n⚠ Issues detected:");
            foreach ($issues as $issue) {
                \WP_CLI::line("  - $issue");
            }
            \WP_CLI::line("\nRun 'wp wc-s3 fix_export_automation' to fix automation issues");
        }
    }
    
    /**
     * Get recent logs
     */
    public function get_recent_logs($limit = 10) {
        $log_file = $this->get_log_file();
        
        if (!file_exists($log_file)) {
            return array();
        }
        
        $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$logs) {
            return array();
        }
        
        // Get the last $limit lines
        $recent_logs = array_slice($logs, -$limit);
        
        return $recent_logs;
    }
    
    /**
     * Get log file path
     */
    private function get_log_file() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/wc-s3-exports/wc-s3-export-pro.log';
    }
    
    /**
     * Log message
     */
    private function log($message, $log_file = null) {
        if (!$log_file) {
            $log_file = $this->get_log_file();
        }
        
        // Ensure log directory exists
        $log_dir = dirname($log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        error_log($message . "\n", 3, $log_file);
    }
} 