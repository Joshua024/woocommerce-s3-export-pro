<?php
namespace WC_S3_Export_Pro;

/**
 * Automation Manager Class
 * 
 * Handles export automation, scheduling, and manual export operations.
 */
class Automation_Manager {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * S3 Uploader instance
     */
    private $s3_uploader;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new Settings();
        $this->s3_uploader = new S3_Uploader();
    }
    
    /**
     * Run export automation
     */
    public function run_export_automation($date_param = null) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Starting export process with enhanced monitoring", $log_file);
        
        // FAILSAFE 1: Check if WooCommerce CSV Export plugin is functional
        if (!function_exists('wc_customer_order_csv_export') || !class_exists('WC_Customer_Order_CSV_Export')) {
            $this->log("[$timestamp] CRITICAL: WooCommerce CSV Export plugin not loaded - scheduling retry", $log_file);
            $this->schedule_export_retry('Plugin not loaded');
            return;
        }
        
        // FAILSAFE 2: Check plugin export handler
        try {
            $export_handler = wc_customer_order_csv_export()->get_export_handler_instance();
            if (!$export_handler) {
                $this->log("[$timestamp] CRITICAL: Export handler not available - scheduling retry", $log_file);
                $this->schedule_export_retry('Export handler unavailable');
                return;
            }
            
            $exports = $export_handler->get_exports();
            if (empty($exports) || count($exports) < 2) {
                $this->log("[$timestamp] CRITICAL: Export configurations missing - attempting recovery", $log_file);
                $this->attempt_export_config_recovery();
                return;
            }
        } catch (\Exception $e) {
            $this->log("[$timestamp] CRITICAL: Export system error - " . $e->getMessage(), $log_file);
            $this->schedule_export_retry('Export system exception: ' . $e->getMessage());
            return;
        }
        
        // FAILSAFE 3: Memory and time limits
        ini_set('memory_limit', '512M');
        set_time_limit(300); // 5 minutes max
        
        $success_count = 0;
        $failed_exports = array();
        
        // Process WebSales with error handling
        try {
            $webSalesFileData = $this->create_csv_export_file('WebSales', $date_param);
            if (!empty($webSalesFileData)) {
                $s3_config = $this->settings->get_s3_config();
                $this->s3_uploader->upload_file(
                    $s3_config['bucket'] ?: 'directoryofsocialchange',
                    $webSalesFileData['file_name'],
                    $webSalesFileData['file_path'],
                    'FundsOnlineWebsiteSales'
                );
                $success_count++;
                $this->log("[$timestamp] WebSales export successful", $log_file);
            } else {
                $failed_exports[] = 'WebSales';
                $this->log("[$timestamp] WebSales export failed - no file created", $log_file);
            }
        } catch (\Exception $e) {
            $failed_exports[] = 'WebSales';
            $this->log("[$timestamp] WebSales export exception: " . $e->getMessage(), $log_file);
        }
        
        // Process WebSaleLines with error handling
        try {
            $webSaleLinesFileData = $this->create_csv_export_file('WebSaleLines', $date_param);
            if (!empty($webSaleLinesFileData)) {
                $s3_config = $this->settings->get_s3_config();
                $this->s3_uploader->upload_file(
                    $s3_config['bucket'] ?: 'directoryofsocialchange',
                    $webSaleLinesFileData['file_name'],
                    $webSaleLinesFileData['file_path'],
                    'FundsOnlineWebsiteSaleLineItems'
                );
                $success_count++;
                $this->log("[$timestamp] WebSaleLines export successful", $log_file);
            } else {
                $failed_exports[] = 'WebSaleLines';
                $this->log("[$timestamp] WebSaleLines export failed - no file created", $log_file);
            }
        } catch (\Exception $e) {
            $failed_exports[] = 'WebSaleLines';
            $this->log("[$timestamp] WebSaleLines export exception: " . $e->getMessage(), $log_file);
        }
        
        // FAILSAFE 4: Handle partial or complete failures
        if ($success_count === 0) {
            $this->log("[$timestamp] CRITICAL: All exports failed - scheduling recovery", $log_file);
            $this->schedule_export_retry('All exports failed');
            $this->send_failure_alert('Complete export failure', $failed_exports);
        } elseif ($success_count < 2) {
            $this->log("[$timestamp] WARNING: Partial export failure - some exports succeeded", $log_file);
            $this->send_failure_alert('Partial export failure', $failed_exports);
        } else {
            $this->log("[$timestamp] SUCCESS: All exports completed successfully", $log_file);
            $this->clear_retry_flags();
        }
        
        return $success_count;
    }
    
    /**
     * Run manual export for specific date
     */
    public function run_manual_export($target_date) {
        // Parse and validate date
        $date_obj = \DateTime::createFromFormat('Y-m-d', $target_date, new \DateTimeZone('Europe/London'));
        if (!$date_obj) {
            $this->log_error("Invalid date format for manual export: $target_date");
            return false;
        }
        
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Manual export started for date: $target_date", $log_file);
        
        // Create exports for both WebSales and WebSaleLines
        $webSalesFileData = $this->create_csv_export_file_for_date('WebSales', $target_date);
        $webSaleLinesFileData = $this->create_csv_export_file_for_date('WebSaleLines', $target_date);
        
        $success_count = 0;
        
        if (!empty($webSalesFileData)) {
            $s3_config = $this->settings->get_s3_config();
            $this->s3_uploader->upload_file(
                $s3_config['bucket'] ?: 'directoryofsocialchange',
                $webSalesFileData['file_name'],
                $webSalesFileData['file_path'],
                'FundsOnlineWebsiteSales'
            );
            $success_count++;
            $this->log("[$timestamp] WebSales export successful for $target_date: " . $webSalesFileData['file_name'], $log_file);
        }

        if (!empty($webSaleLinesFileData)) {
            $s3_config = $this->settings->get_s3_config();
            $this->s3_uploader->upload_file(
                $s3_config['bucket'] ?: 'directoryofsocialchange',
                $webSaleLinesFileData['file_name'],
                $webSaleLinesFileData['file_path'],
                'FundsOnlineWebsiteSaleLineItems'
            );
            $success_count++;
            $this->log("[$timestamp] WebSaleLines export successful for $target_date: " . $webSaleLinesFileData['file_name'], $log_file);
        }
        
        $this->log("[$timestamp] Manual export completed for $target_date - $success_count files created", $log_file);
        return $success_count;
    }
    
    /**
     * Create CSV export file
     */
    private function create_csv_export_file($export_type, $date_param = null) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Creating $export_type export", $log_file);
        
        $exports = wc_customer_order_csv_export()->get_export_handler_instance()->get_exports();

        if ($export_type === 'WebSales') {
            $export_list_index = 0;
        } else {
            $export_list_index = 1;
        }

        $export = wc_customer_order_csv_export_get_export($exports[$export_list_index]->id);
        if (!$export) {
            $this->log("[$timestamp] Failed to get export instance for $export_type", $log_file);
            return false;
        }

        // Build filename
        $date = $date_param ? \DateTime::createFromFormat('Y-m-d', $date_param) : new \DateTime('yesterday', new \DateTimeZone('Europe/London'));
        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');

        $filename = "FO-$export_type-$day-$month-$year.csv";

        // Put CSV into a subdirectory in wp-content/uploads/
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/wc-s3-exports/';
        $sub_dir = ($export_type === 'WebSales') ? 'web-sales' : 'web-sale-lines';
        $filepath = $csv_dir . $sub_dir . '/' . $filename;

        // Create folder if it doesn't exist
        if (!file_exists($csv_dir . $sub_dir)) {
            if (!wp_mkdir_p($csv_dir . $sub_dir)) {
                $this->log("[$timestamp] Failed to create directory: $csv_dir$sub_dir", $log_file);
                return false;
            }
            $this->log("[$timestamp] Created directory: $csv_dir$sub_dir", $log_file);
        }

        // Write the CSV
        $output_resource = fopen($filepath, 'w+');
        if ($output_resource === false) {
            $this->log("[$timestamp] Failed to open file for writing: $filepath", $log_file);
            return false;
        }

        $export->stream_output_to_resource($output_resource);
        fclose($output_resource);

        $this->log("[$timestamp] CSV file generated: $filepath", $log_file);

        if (file_exists($filepath)) {
            return [
                'file_name' => $filename,
                'file_path' => $filepath
            ];
        } else {
            $this->log("[$timestamp] Error: File $filepath was not created successfully", $log_file);
            return false;
        }
    }
    
    /**
     * Create CSV export file for specific date
     */
    private function create_csv_export_file_for_date($export_type, $target_date) {
        return $this->create_csv_export_file($export_type, $target_date);
    }
    
    /**
     * Schedule export retry
     */
    private function schedule_export_retry($reason) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Scheduling export retry: $reason", $log_file);
        
        // Schedule retry in 1 hour
        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event(time() + 3600, 'wc_s3_export_automation');
        }
        
        // Set retry flag
        update_option('wc_s3_export_retry_flag', array(
            'reason' => $reason,
            'timestamp' => current_time('mysql'),
            'attempts' => get_option('wc_s3_export_retry_attempts', 0) + 1
        ));
    }
    
    /**
     * Clear retry flags
     */
    public function clear_retry_flags() {
        delete_option('wc_s3_export_retry_flag');
        delete_option('wc_s3_export_retry_attempts');
    }
    
    /**
     * Reset retry flags (WP-CLI)
     */
    public function reset_retry_flags() {
        $this->clear_retry_flags();
    }
    
    /**
     * Attempt export config recovery
     */
    private function attempt_export_config_recovery() {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Attempting export configuration recovery", $log_file);
        
        // Try to trigger plugin reinitialization
        do_action('wc_customer_order_csv_export_admin_init');
        
        // Schedule retry
        $this->schedule_export_retry('Configuration recovery attempted');
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
     * WP-CLI: Fix export automation
     */
    public function cli_fix_export_automation() {
        \WP_CLI::line("=== COMPREHENSIVE EXPORT AUTOMATION FIX ===\n");
        
        // Check if WooCommerce CSV Export is available
        if (!function_exists('wc_customer_order_csv_export')) {
            \WP_CLI::error("WooCommerce CSV Export plugin is not active!");
            return;
        }
        
        // Get export configurations
        $csv_export = wc_customer_order_csv_export();
        $export_handler = $csv_export->get_export_handler_instance();
        $exports = $export_handler->get_exports();
        
        \WP_CLI::line("Found " . count($exports) . " export configurations\n");
        
        // STEP 1: Clear any existing broken automation settings
        \WP_CLI::line("Step 1: Clearing existing automation settings...");
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%wc_customer_order_csv_export%auto_export%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%wc_customer_order_csv_export%export%auto%'");
        
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('wc_customer_order_csv_export_auto_export');
            as_unschedule_all_actions('wc_customer_order_csv_export_auto_export_orders');
            as_unschedule_all_actions('wc_customer_order_csv_export_auto_export_customers');
        }
        
        \WP_CLI::line("✓ Cleared existing automation settings");
        
        // STEP 2: Create proper automation settings for each export
        \WP_CLI::line("\nStep 2: Creating automation settings...");
        
        foreach ($exports as $export) {
            $export_id = $export->get_id();
            $export_name = $export->get_name();
            $export_type = $export->get_type();
            
            \WP_CLI::line("Setting up automation for: $export_name (ID: $export_id, Type: $export_type)");
            
            // Create the complete automation configuration
            $automation_settings = array(
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_enabled" => 'yes',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_frequency" => 'daily',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_trigger" => 'daily',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_start_time" => '02:00',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_order_statuses" => array('wc-completed', 'wc-processing'),
            );
            
            // Insert automation settings into database
            foreach ($automation_settings as $option_name => $option_value) {
                if (is_array($option_value)) {
                    $option_value = maybe_serialize($option_value);
                }
                
                $wpdb->replace(
                    $wpdb->options,
                    array(
                        'option_name' => $option_name,
                        'option_value' => $option_value,
                        'autoload' => 'yes'
                    )
                );
            }
            
            \WP_CLI::line("  ✓ Database entries created for $export_name");
        }
        
        // STEP 3: Create Action Scheduler jobs
        \WP_CLI::line("\nStep 3: Creating Action Scheduler jobs...");
        
        foreach ($exports as $export) {
            $export_id = $export->get_id();
            $export_name = $export->get_name();
            
            if (function_exists('as_schedule_recurring_action')) {
                $next_run = strtotime('tomorrow 2:00 AM');
                
                try {
                    as_schedule_recurring_action(
                        $next_run,
                        DAY_IN_SECONDS,
                        'wc_customer_order_csv_export_auto_export',
                        array($export_id),
                        'wc-csv-export'
                    );
                    \WP_CLI::line("  ✓ Scheduled daily export for $export_name at 2:00 AM");
                } catch (\Exception $e) {
                    \WP_CLI::warning("  ⚠ Failed to schedule $export_name: " . $e->getMessage());
                }
            }
        }
        
        // STEP 4: Final verification
        \WP_CLI::line("\nStep 4: Final verification...");
        
        $automation_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '%wc_customer_order_csv_export%auto_export_enabled%' 
             AND option_value = 'yes'"
        );
        
        \WP_CLI::line("Database automation entries: $automation_count");
        
        if (function_exists('as_get_scheduled_actions')) {
            $scheduled_actions = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'pending'
            ));
            
            \WP_CLI::line("Scheduled export actions: " . count($scheduled_actions));
        }
        
        // SUCCESS CHECK
        if ($automation_count > 0) {
            \WP_CLI::success("\n🎉 EXPORT AUTOMATION SUCCESSFULLY RESTORED!");
            \WP_CLI::line("✓ $automation_count automation settings created in database");
            \WP_CLI::line("✓ Export jobs scheduled for daily execution at 2:00 AM");
            \WP_CLI::line("\nNEXT STEPS:");
            \WP_CLI::line("1. Monitor the next scheduled export at 2:00 AM");
            \WP_CLI::line("2. Check logs for successful automation");
            \WP_CLI::line("3. Verify S3 uploads are working");
            \WP_CLI::line("4. Run 'wp wc-s3 check_scheduler' to monitor status");
            
        } else {
            \WP_CLI::error("❌ AUTOMATION RESTORATION FAILED");
            \WP_CLI::line("Database entries: $automation_count");
        }
        
        \WP_CLI::line("\n=== AUTOMATION FIX COMPLETED ===");
    }
    
    /**
     * WP-CLI: Simple fix export automation
     */
    public function cli_simple_fix_export_automation() {
        \WP_CLI::line("=== SIMPLE EXPORT AUTOMATION FIX ===\n");
        
        // Check if WooCommerce CSV Export is available
        if (!function_exists('wc_customer_order_csv_export')) {
            \WP_CLI::error("WooCommerce CSV Export plugin is not active!");
            return;
        }
        
        // Get export configurations
        $csv_export = wc_customer_order_csv_export();
        $export_handler = $csv_export->get_export_handler_instance();
        $exports = $export_handler->get_exports();
        
        \WP_CLI::line("Found " . count($exports) . " export configurations\n");
        
        // STEP 1: Clear existing automation settings
        \WP_CLI::line("Step 1: Clearing existing automation settings...");
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%wc_customer_order_csv_export%auto_export%'");
        
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('wc_customer_order_csv_export_auto_export');
        }
        
        \WP_CLI::line("✓ Cleared existing automation settings");
        
        // STEP 2: Create basic automation settings for each export
        \WP_CLI::line("\nStep 2: Creating automation settings...");
        
        foreach ($exports as $export) {
            $export_id = $export->get_id();
            $export_name = $export->get_name();
            
            \WP_CLI::line("Setting up automation for: $export_name (ID: $export_id)");
            
            // Create basic automation settings
            $automation_settings = array(
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_enabled" => 'yes',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_frequency" => 'daily',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_trigger" => 'daily',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_start_time" => '02:00',
                "wc_customer_order_csv_export_export_{$export_id}_auto_export_order_statuses" => array('wc-completed', 'wc-processing'),
            );
            
            // Insert automation settings into database
            foreach ($automation_settings as $option_name => $option_value) {
                if (is_array($option_value)) {
                    $option_value = maybe_serialize($option_value);
                }
                
                $wpdb->replace(
                    $wpdb->options,
                    array(
                        'option_name' => $option_name,
                        'option_value' => $option_value,
                        'autoload' => 'yes'
                    )
                );
            }
            
            \WP_CLI::line("  ✓ Database entries created for $export_name");
        }
        
        // STEP 3: Create Action Scheduler jobs
        \WP_CLI::line("\nStep 3: Creating Action Scheduler jobs...");
        
        foreach ($exports as $export) {
            $export_id = $export->get_id();
            $export_name = $export->get_name();
            
            if (function_exists('as_schedule_recurring_action')) {
                $next_run = strtotime('tomorrow 2:00 AM');
                
                try {
                    as_schedule_recurring_action(
                        $next_run,
                        DAY_IN_SECONDS,
                        'wc_customer_order_csv_export_auto_export',
                        array($export_id),
                        'wc-csv-export'
                    );
                    \WP_CLI::line("  ✓ Scheduled daily export for $export_name at 2:00 AM");
                } catch (\Exception $e) {
                    \WP_CLI::warning("  ⚠ Failed to schedule $export_name: " . $e->getMessage());
                }
            }
        }
        
        // STEP 4: Final verification
        \WP_CLI::line("\nStep 4: Final verification...");
        
        $automation_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '%wc_customer_order_csv_export%auto_export_enabled%' 
             AND option_value = 'yes'"
        );
        
        \WP_CLI::line("Database automation entries: $automation_count");
        
        if (function_exists('as_get_scheduled_actions')) {
            $scheduled_actions = as_get_scheduled_actions(array(
                'hook' => 'wc_customer_order_csv_export_auto_export',
                'status' => 'pending'
            ));
            
            \WP_CLI::line("Scheduled export actions: " . count($scheduled_actions));
        }
        
        // SUCCESS CHECK
        if ($automation_count > 0) {
            \WP_CLI::success("\n🎉 EXPORT AUTOMATION SUCCESSFULLY RESTORED!");
            \WP_CLI::line("✓ $automation_count automation settings created in database");
            \WP_CLI::line("✓ Export jobs scheduled for daily execution at 2:00 AM");
            \WP_CLI::line("\nNOTE: S3 credentials need to be configured for exports to upload to S3");
            \WP_CLI::line("Run 'wp wc-s3 check_s3_config' to verify S3 configuration");
            
        } else {
            \WP_CLI::error("❌ AUTOMATION RESTORATION FAILED");
            \WP_CLI::line("Database entries: $automation_count");
        }
        
        \WP_CLI::line("\n=== AUTOMATION FIX COMPLETED ===");
    }
    
    /**
     * WP-CLI: Emergency recovery
     */
    public function cli_emergency_recovery($args, $assoc_args) {
        $days_back = isset($args[0]) ? intval($args[0]) : 7;
        \WP_CLI::line("=== EMERGENCY EXPORT RECOVERY ===\n");
        \WP_CLI::line("Recovering exports for the last $days_back days...\n");
        
        $success_count = 0;
        $failure_count = 0;
        
        for ($i = 0; $i < $days_back; $i++) {
            $target_date = date('Y-m-d', strtotime("-$i days"));
            \WP_CLI::line("Processing date: $target_date");
            
            try {
                $result = $this->run_manual_export($target_date);
                if ($result > 0) {
                    $success_count++;
                    \WP_CLI::line("  ✓ Success: $result files created");
                } else {
                    $failure_count++;
                    \WP_CLI::line("  ❌ Failed: No files created");
                }
            } catch (\Exception $e) {
                $failure_count++;
                \WP_CLI::line("  ❌ Error: " . $e->getMessage());
            }
        }
        
        \WP_CLI::line("\n=== RECOVERY SUMMARY ===");
        \WP_CLI::line("Successful dates: $success_count");
        \WP_CLI::line("Failed dates: $failure_count");
        
        if ($success_count > 0) {
            \WP_CLI::success("✓ Recovery completed successfully");
        } else {
            \WP_CLI::error("❌ Recovery failed - no exports were created");
        }
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
    
    /**
     * Log error
     */
    private function log_error($message) {
        $this->log("ERROR: " . $message);
    }
    
    /**
     * Setup automation
     */
    public function setup_automation() {
        try {
            // Check if WooCommerce CSV Export plugin is active
            if (!function_exists('wc_customer_order_csv_export')) {
                return array(
                    'success' => false,
                    'message' => 'WooCommerce CSV Export plugin is not active'
                );
            }
            
            // Get current settings
            $settings = $this->settings->get_settings();
            $s3_config = $this->settings->get_s3_config();
            
            // Validate S3 configuration
            if (empty($s3_config['access_key']) || empty($s3_config['secret_key']) || empty($s3_config['bucket'])) {
                return array(
                    'success' => false,
                    'message' => 'S3 configuration is incomplete. Please configure S3 settings first.'
                );
            }
            
            // Test S3 connection
            $s3_test = $this->s3_uploader->test_connection();
            if (!$s3_test['success']) {
                return array(
                    'success' => false,
                    'message' => 'S3 connection failed: ' . $s3_test['message']
                );
            }
            
            // Schedule the automation
            $frequency = $settings['export_frequency'] ?? 'daily';
            $time = $settings['export_time'] ?? '02:00';
            
            // Clear existing schedules
            wp_clear_scheduled_hook('wc_s3_export_automation');
            
            // Schedule new automation
            if ($frequency === 'daily') {
                wp_schedule_event(strtotime($time), 'daily', 'wc_s3_export_automation');
            } elseif ($frequency === 'weekly') {
                wp_schedule_event(strtotime($time), 'weekly', 'wc_s3_export_automation');
            } elseif ($frequency === 'monthly') {
                wp_schedule_event(strtotime($time), 'monthly', 'wc_s3_export_automation');
            }
            
            return array(
                'success' => true,
                'message' => 'Automation setup successfully. Exports will run ' . $frequency . ' at ' . $time
            );
            
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'message' => 'Setup failed: ' . $e->getMessage()
            );
        }
    }
} 