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
     * CSV Generator instance
     */
    private $csv_generator;
    
    /**
     * Export History instance
     */
    private $export_history;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new Settings();
        $this->s3_uploader = new S3_Uploader();
        $this->csv_generator = new CSV_Generator();
        $this->export_history = new Export_History();
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
        
        // Get export types configuration
        $export_types_config = $this->settings->get_export_types_config();
        $s3_config = $this->settings->get_s3_config();
        
        // Process each configured export type
        foreach ($export_types_config as $export_type) {
            if (!$export_type['enabled']) {
                $this->log("[$timestamp] Export type '{$export_type['name']}' disabled - skipping", $log_file);
                continue;
            }
            
            try {
                $file_data = $this->create_csv_export_file_for_type($export_type, $date_param);
                if (!empty($file_data)) {
                    $s3_folder = $export_type['s3_folder'] ?: sanitize_title($export_type['name']);
                    $file_prefix = $export_type['file_prefix'] ?: $export_type['name'];
                    
                    $this->s3_uploader->upload_file(
                        $s3_config['bucket'] ?: 'fundsonline-exports',
                        $file_data['file_name'],
                        $file_data['file_path'],
                        $s3_folder,
                        ''
                    );
                    $success_count++;
                    $this->log("[$timestamp] Export '{$export_type['name']}' successful", $log_file);
                } else {
                    $failed_exports[] = $export_type['name'];
                    $this->log("[$timestamp] Export '{$export_type['name']}' failed - no file created", $log_file);
                }
            } catch (\Exception $e) {
                $failed_exports[] = $export_type['name'];
                $this->log("[$timestamp] Export '{$export_type['name']}' exception: " . $e->getMessage(), $log_file);
            }
        }
        
        // FAILSAFE 4: Handle partial or complete failures
        $expected_exports = 0;
        foreach ($export_types_config as $export_type) {
            if ($export_type['enabled']) $expected_exports++;
        }
        
        if ($expected_exports === 0) {
            $this->log("[$timestamp] WARNING: No exports are enabled", $log_file);
        } elseif ($success_count === 0 && $expected_exports > 0) {
            $this->log("[$timestamp] CRITICAL: All enabled exports failed - scheduling recovery", $log_file);
            $this->schedule_export_retry('All enabled exports failed');
            $this->send_failure_alert('Complete export failure', $failed_exports);
        } elseif ($success_count < $expected_exports) {
            $this->log("[$timestamp] WARNING: Partial export failure - some exports succeeded", $log_file);
            $this->send_failure_alert('Partial export failure', $failed_exports);
        } else {
            $this->log("[$timestamp] SUCCESS: All enabled exports completed successfully", $log_file);
            $this->clear_retry_flags();
        }
        
        return $success_count;
    }
    
    /**
     * Run manual export for a specific date or date range
     */
    public function run_manual_export($target_date = null, $end_date = null, $export_types = array()) {
        // Make it resilient to long runs
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('memory_limit', '512M');
        
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        // Handle date range
        if ($end_date && $target_date) {
            $start_date = $target_date;
            $end_date = $end_date;
            $this->log("[$timestamp] Manual export started for date range: $start_date to $end_date", $log_file);
        } else {
            $start_date = $target_date ?: date('Y-m-d');
            $end_date = $start_date;
            $this->log("[$timestamp] Manual export started for date: $start_date", $log_file);
        }
        
        // Validate dates
        $start_obj = \DateTime::createFromFormat('Y-m-d', $start_date, new \DateTimeZone('Europe/London'));
        $end_obj = \DateTime::createFromFormat('Y-m-d', $end_date, new \DateTimeZone('Europe/London'));
        
        if (!$start_obj || !$end_obj) {
            $this->log_error("Invalid date format for manual export: $start_date to $end_date");
            return false;
        }
        
        if ($start_obj > $end_obj) {
            $this->log_error("Start date cannot be after end date: $start_date to $end_date");
            return false;
        }
        
        $success_count = 0;
        $total_files = 0;
        
        // Generate date range
        $current_date = clone $start_obj;
        $date_range = array();
        
        while ($current_date <= $end_obj) {
            $date_range[] = $current_date->format('Y-m-d');
            $current_date->add(new \DateInterval('P1D'));
        }
        
        // If no specific export types provided, use all configured types
        if (empty($export_types)) {
            $this->log("[$timestamp] No export types provided, loading all configured types", $log_file);
            $export_types_config = $this->settings->get_export_types_config();
            $this->log("[$timestamp] All configured export types: " . print_r($export_types_config, true), $log_file);
            foreach ($export_types_config as $export_type) {
                if ($export_type['enabled']) {
                    $export_types[] = $export_type['id'];
                    $this->log("[$timestamp] Added enabled export type: " . $export_type['id'], $log_file);
                }
            }
        } else {
            $this->log("[$timestamp] Using provided export types: " . print_r($export_types, true), $log_file);
        }
        
        foreach ($date_range as $date) {
            foreach ($export_types as $export_type_id) {
                $this->log("[$timestamp] Processing export type ID: $export_type_id", $log_file);
                $export_type_config = $this->get_export_type_config($export_type_id);
                if (!$export_type_config) {
                    $this->log("[$timestamp] No config found for export type ID: $export_type_id", $log_file);
                    continue;
                }
                $this->log("[$timestamp] Found config for export type: " . print_r($export_type_config, true), $log_file);
                
                // Check for duplicate export - but allow manual exports to override
                if ($this->export_history->export_exists($export_type_id, $date, $export_type_config['name'])) {
                    $this->log("[$timestamp] Export already exists for $export_type_id on $date - but continuing for manual export", $log_file);
                }
                
                // Create export file
                $this->log("[$timestamp] Creating export for type: {$export_type_config['name']}", $log_file);
                $file_data = $this->create_csv_export_file_for_type($export_type_config, $date);
                
                if (!empty($file_data)) {
                    $this->log("[$timestamp] Successfully created CSV file for {$export_type_config['name']} on $date", $log_file);
                    // Validate file integrity
                    $validation = $this->export_history->validate_file_integrity($file_data['file_path']);
                    if (!$validation['valid']) {
                        $this->log("[$timestamp] File validation failed for $export_type_id on $date: " . $validation['error'], $log_file);
                        continue;
                    }
                    
                    // Upload to S3
                    $s3_config = $this->settings->get_s3_config();
                    $s3_folder = $export_type_config['s3_folder'] ?: sanitize_title($export_type_config['name']);
                    $file_prefix = $export_type_config['file_prefix'] ?: $export_type_config['name'];
                    
                    $upload_result = $this->s3_uploader->upload_file(
                        $s3_config['bucket'] ?: 'fundsonline-exports',
                        $file_data['file_name'],
                        $file_data['file_path'],
                        $s3_folder,
                        ''
                    );
                    
                    if ($upload_result) {
                        // Add to export history
                        $this->export_history->add_export_record(
                            $export_type_id,
                            $date,
                            $file_data['file_name'],
                            $file_data['file_path'],
                            $export_type_config['name'],
                            'completed'
                        );
                        
                        $success_count++;
                        $this->log("[$timestamp] Export successful for $export_type_id on $date: " . $file_data['file_name'], $log_file);
                    } else {
                        // Add failed record to history
                        $this->export_history->add_export_record(
                            $export_type_id,
                            $date,
                            $file_data['file_name'],
                            $file_data['file_path'],
                            $export_type_config['name'],
                            'failed'
                        );
                        
                        $this->log("[$timestamp] S3 upload failed for $export_type_id on $date: " . $file_data['file_name'], $log_file);
                    }
                } else {
                    $this->log("[$timestamp] Failed to create CSV file for {$export_type_config['name']} on $date", $log_file);
                }
                
                $total_files++;
            }
        }
        
        $this->log("[$timestamp] Manual export completed - $success_count/$total_files files created successfully", $log_file);
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
     * Create CSV export file for specific export type
     */
    private function create_csv_export_file_for_type($export_type, $date_param = null) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Creating export for type: {$export_type['name']}", $log_file);
        
        try {
            // Use the new CSV Generator for data extraction and CSV creation
            $this->log("[$timestamp] About to call CSV generator", $log_file);
            $this->log("[$timestamp] CSV generator params - export_type: " . print_r($export_type, true), $log_file);
            $this->log("[$timestamp] CSV generator params - date_param: $date_param", $log_file);
            
            $file_data = $this->csv_generator->generate_csv($export_type, $date_param);
            
            $this->log("[$timestamp] CSV generator returned: " . ($file_data ? 'SUCCESS' : 'FAILED'), $log_file);
            if ($file_data) {
                $this->log("[$timestamp] CSV generator file_data: " . print_r($file_data, true), $log_file);
            }
            
            if ($file_data) {
                $this->log("[$timestamp] CSV file generated successfully for: {$export_type['name']}", $log_file);
            } else {
                $this->log("[$timestamp] Failed to generate CSV file for: {$export_type['name']}", $log_file);
            }
            
            return $file_data;
        } catch (\Exception $e) {
            $this->log("[$timestamp] Exception in CSV generation: " . $e->getMessage(), $log_file);
            return false;
        }
    }
    
    /**
     * Ensure local folder exists
     */
    private function ensure_local_folder_exists($folder_name) {
        $upload_dir = wp_upload_dir();
        $folder_path = $upload_dir['basedir'] . '/wc-s3-exports/' . $folder_name;
        
        if (!file_exists($folder_path)) {
            wp_mkdir_p($folder_path);
        }
        
        return $folder_path;
    }
    
    /**
     * Move file to specified local folder
     */
    private function move_file_to_local_folder($file_data, $folder_name) {
        if (empty($file_data) || empty($file_data['file_path'])) {
            return $file_data;
        }
        
        $upload_dir = wp_upload_dir();
        $folder_path = $upload_dir['basedir'] . '/wc-s3-exports/' . $folder_name;
        
        // Ensure folder exists
        $this->ensure_local_folder_exists($folder_name);
        
        // Generate new file path in the specified folder
        $new_file_path = $folder_path . '/' . $file_data['file_name'];
        
        // Move the file if it's not already in the correct location
        if ($file_data['file_path'] !== $new_file_path && file_exists($file_data['file_path'])) {
            if (rename($file_data['file_path'], $new_file_path)) {
                $file_data['file_path'] = $new_file_path;
                $this->log("File moved to local folder: $folder_name", $this->get_log_file());
            } else {
                $this->log("Failed to move file to local folder: $folder_name", $this->get_log_file());
            }
        }
        
        return $file_data;
    }
    
    /**
     * Map export type to WooCommerce export type
     */
    private function map_export_type_to_wc_type($type) {
        $mapping = array(
            'orders' => 'WebSales',
            'order_items' => 'WebSaleLines',
            'customers' => 'Customers',
            'products' => 'Products',
            'coupons' => 'Coupons'
        );
        
        return isset($mapping[$type]) ? $mapping[$type] : false;
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
     * Setup automation for individual export types
     */
    public function setup_automation() {
        $export_types_config = $this->settings->get_export_types_config();
        
        foreach ($export_types_config as $export_type) {
            if ($export_type['enabled']) {
                $this->setup_export_type_automation($export_type);
            }
        }
    }
    
    /**
     * Setup automation for specific export type
     */
    private function setup_export_type_automation($export_type) {
        $frequency = $export_type['frequency'] ?: 'daily';
        $time = $export_type['time'] ?: '01:00';
        $type_id = $export_type['id'];
        
        // Clear existing schedule for this export type
        wp_clear_scheduled_hook('wc_s3_export_' . $type_id);
        
        // Set up new schedule based on frequency using site timezone
        $tz = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone(wp_timezone_string());
        $now = new \DateTime('now', $tz);
        switch ($frequency) {
            case 'hourly':
                wp_schedule_event(time(), 'hourly', 'wc_s3_export_' . $type_id);
                break;
            case 'daily':
                $dt = new \DateTime('today ' . $time, $tz);
                if ($dt->getTimestamp() <= $now->getTimestamp()) {
                    $dt = new \DateTime('tomorrow ' . $time, $tz);
                }
                wp_schedule_event($dt->getTimestamp(), 'daily', 'wc_s3_export_' . $type_id);
                break;
            case 'weekly':
                $dt = new \DateTime('next monday ' . $time, $tz);
                wp_schedule_event($dt->getTimestamp(), 'weekly', 'wc_s3_export_' . $type_id);
                break;
            case 'monthly':
                $dt = new \DateTime('first day of next month ' . $time, $tz);
                wp_schedule_event($dt->getTimestamp(), 'monthly', 'wc_s3_export_' . $type_id);
                break;
        }
        
        // Add action hook for this export type
        add_action('wc_s3_export_' . $type_id, array($this, 'run_export_type_automation'), 10, 1);
    }
    
    /**
     * Run automation for specific export type
     */
    public function run_export_type_automation($type_id) {
        $export_types_config = $this->settings->get_export_types_config();
        
        // Find the export type by ID
        $export_type = null;
        foreach ($export_types_config as $type) {
            if ($type['id'] === $type_id) {
                $export_type = $type;
                break;
            }
        }
        
        if (!$export_type || !$export_type['enabled']) {
            return;
        }
        
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Starting export automation for '{$export_type['name']}'", $log_file);
        
        try {
            // Use yesterday's date for the export to avoid processing too many orders
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            // Check if there are orders for this date before processing
            $order_count = $this->get_order_count_for_date($yesterday, $export_type['statuses']);
            $this->log("[$timestamp] Found $order_count orders for date $yesterday", $log_file);
            
            if ($order_count > 0) {
                $file_data = $this->create_csv_export_file_for_type($export_type, $yesterday);
            } else {
                $this->log("[$timestamp] No orders found for date $yesterday - skipping export", $log_file);
                return;
            }
            
            if (!empty($file_data)) {
                $s3_config = $this->settings->get_s3_config();
                $s3_folder = $export_type['s3_folder'] ?: sanitize_title($export_type['name']);
                $file_prefix = $export_type['file_prefix'] ?: $export_type['name'];
                
                $this->s3_uploader->upload_file(
                    $s3_config['bucket'] ?: 'fundsonline-exports',
                    $file_data['file_name'],
                    $file_data['file_path'],
                    $s3_folder,
                    ''
                );
                
                $this->log("[$timestamp] Export '{$export_type['name']}' completed successfully", $log_file);
            } else {
                $this->log("[$timestamp] Export '{$export_type['name']}' failed - no file created", $log_file);
            }
        } catch (\Exception $e) {
            $this->log("[$timestamp] Export '{$export_type['name']}' exception: " . $e->getMessage(), $log_file);
        }
    }
    
    /**
     * Setup automation (legacy method for backward compatibility)
     */
    public function setup_legacy_automation() {
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

    /**
     * Get export type configuration by ID
     */
    private function get_export_type_config($export_type_id) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] get_export_type_config called with ID: $export_type_id", $log_file);
        
        $export_types_config = $this->settings->get_export_types_config();
        $this->log("[$timestamp] Retrieved export types config: " . print_r($export_types_config, true), $log_file);
        
        foreach ($export_types_config as $export_type) {
            if ($export_type['id'] === $export_type_id) {
                $this->log("[$timestamp] Found matching export type: " . print_r($export_type, true), $log_file);
                return $export_type;
            }
        }
        
        $this->log("[$timestamp] No matching export type found for ID: $export_type_id", $log_file);
        return false;
    }
    
    /**
     * Get order count for a specific date and statuses
     */
    private function get_order_count_for_date($date, $statuses) {
        $args = array(
            'limit' => -1,
            'status' => $statuses,
            'return' => 'ids'
        );
        
        // Add date filter
        $date_obj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($date_obj) {
            $start_date = $date_obj->format('Y-m-d 00:00:00');
            $end_date = $date_obj->format('Y-m-d 23:59:59');
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        return count($orders);
    }
} 