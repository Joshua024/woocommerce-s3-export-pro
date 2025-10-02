<?php
namespace WC_S3_Export_Pro;

/**
 * Main Export Manager Class
 * 
 * Orchestrates all export functionality including S3 uploads, automation, monitoring, and recovery.
 */
class Export_Manager {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * S3 Uploader instance
     */
    private $s3_uploader;
    
    /**
     * Automation Manager instance
     */
    private $automation_manager;
    
    /**
     * Monitoring instance
     */
    private $monitoring;
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Cron hooks
        add_action('wc_s3_export_health_check', array($this, 'run_health_check'));
        add_action('wc_s3_export_automation', array($this, 'run_export_automation'));
        
        // AJAX handlers
        add_action('wp_ajax_wc_s3_test_s3_connection', array($this, 'ajax_test_s3_connection'));
        add_action('wp_ajax_wc_s3_run_manual_export', array($this, 'ajax_run_manual_export'));
        add_action('wp_ajax_wc_s3_test_manual_export', array($this, 'ajax_test_manual_export'));
        add_action('wp_ajax_wc_s3_get_export_status', array($this, 'ajax_get_export_status'));
        add_action('wp_ajax_wc_s3_save_s3_config', array($this, 'ajax_save_s3_config'));
        add_action('wp_ajax_wc_s3_save_export_settings', array($this, 'ajax_save_export_settings'));
        add_action('wp_ajax_wc_s3_save_export_types', array($this, 'ajax_save_export_types'));
        add_action('wp_ajax_wc_s3_save_export_types_config', array($this, 'ajax_save_export_types_config'));
        add_action('wp_ajax_wc_s3_save_source_website_config', array($this, 'ajax_save_source_website_config'));
        add_action('wp_ajax_wc_s3_add_export_type', array($this, 'ajax_add_export_type'));
        add_action('wp_ajax_wc_s3_remove_export_type', array($this, 'ajax_remove_export_type'));
        add_action('wp_ajax_wc_s3_setup_automation', array($this, 'ajax_setup_automation'));
        add_action('wp_ajax_wc_s3_get_log_content', array($this, 'ajax_get_log_content'));
        add_action('wp_ajax_wc_s3_get_export_history', array($this, 'ajax_get_export_history'));
        add_action('wp_ajax_wc_s3_delete_export_record', array($this, 'ajax_delete_export_record'));
        add_action('wp_ajax_wc_s3_delete_export_records', array($this, 'ajax_delete_export_records'));
        add_action('wp_ajax_wc_s3_download_export_file', array($this, 'ajax_download_export_file'));
        add_action('wp_ajax_wc_s3_test_ajax', array($this, 'ajax_test_ajax'));
        add_action('wp_ajax_wc_s3_simple_test', array($this, 'ajax_simple_test'));
        
        // WP-CLI commands
        if (defined('WP_CLI') && WP_CLI) {
            $this->register_cli_commands();
        }
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Load AWS SDK if available (optional dependency)
        if (!class_exists('Aws\S3\S3Client')) {
            $aws_sdk_path = WC_S3_EXPORT_PRO_PLUGIN_DIR . 'vendor/aws/aws-sdk-php/src/functions.php';
            if (file_exists($aws_sdk_path)) {
                require_once $aws_sdk_path;
            } else {
                // Log that AWS SDK is not available but continue
                error_log('WC S3 Export Pro: AWS SDK not found. S3 functionality will be disabled.');
            }
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        $this->settings = new Settings();
        $this->s3_uploader = new S3_Uploader();
        $this->automation_manager = new Automation_Manager();
        $this->monitoring = new Monitoring();
        
        // Set up automation after components are initialized
        $this->setup_automation();
    }
    
    /**
     * Set up automation
     */
    private function setup_automation() {
        // Set up the main automation cron job
        if (!wp_next_scheduled('wc_s3_export_automation')) {
            wp_schedule_event(time(), 'daily', 'wc_s3_export_automation');
        }
        
        // Set up health check cron job
        if (!wp_next_scheduled('wc_s3_export_health_check')) {
            wp_schedule_event(time(), 'daily', 'wc_s3_export_health_check');
        }
        
        // Set up individual export type automation
        $this->automation_manager->setup_automation();
        
        // Log automation setup
        error_log('WC S3 Export Pro: Automation setup completed');
    }
    
    /**
     * Register WP-CLI commands
     */
    private function register_cli_commands() {
        \WP_CLI::add_command('wc-s3 export_orders', array($this, 'cli_export_orders'));
        \WP_CLI::add_command('wc-s3 backfill_export', array($this, 'cli_backfill_export'));
        \WP_CLI::add_command('wc-s3 validate_export_system', array($this, 'cli_validate_export_system'));
        \WP_CLI::add_command('wc-s3 export_status', array($this, 'cli_export_status'));
        \WP_CLI::add_command('wc-s3 reset_export_retries', array($this, 'cli_reset_export_retries'));
        \WP_CLI::add_command('wc-s3 check_scheduler', array($this, 'cli_check_scheduler'));
        \WP_CLI::add_command('wc-s3 fix_export_automation', array($this, 'cli_fix_export_automation'));
        \WP_CLI::add_command('wc-s3 simple_fix_export_automation', array($this, 'cli_simple_fix_export_automation'));
        \WP_CLI::add_command('wc-s3 setup_s3_config', array($this, 'cli_setup_s3_config'));
        \WP_CLI::add_command('wc-s3 check_s3_config', array($this, 'cli_check_s3_config'));
        \WP_CLI::add_command('wc-s3 monitor_exports', array($this, 'cli_monitor_exports'));
        \WP_CLI::add_command('wc-s3 emergency_recovery', array($this, 'cli_emergency_recovery'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'WC S3 Export Pro',
            'S3 Export Pro',
            'manage_woocommerce',
            'wc-s3-export-pro',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_wc-s3-export-pro' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'wc-s3-export-pro-admin',
            WC_S3_EXPORT_PRO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WC_S3_EXPORT_PRO_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wc-s3-export-pro-admin',
            WC_S3_EXPORT_PRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_S3_EXPORT_PRO_VERSION
        );
        
        wp_localize_script('wc-s3-export-pro-admin', 'wcS3ExportPro', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_s3_export_pro_nonce'),
            'strings' => array(
                'testing' => __('Testing S3 connection...', 'wc-s3-export-pro'),
                'success' => __('Success!', 'wc-s3-export-pro'),
                'error' => __('Error!', 'wc-s3-export-pro'),
                'running' => __('Running export...', 'wc-s3-export-pro'),
            )
        ));
    }
    
    /**
     * Get S3 Uploader instance
     */
    public function get_s3_uploader() {
        return $this->s3_uploader;
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        include WC_S3_EXPORT_PRO_PLUGIN_DIR . 'admin/views/admin-page.php';
    }
    
    /**
     * Run health check
     */
    public function run_health_check() {
        $this->monitoring->run_health_check();
    }
    
    /**
     * Run export automation
     */
    public function run_export_automation() {
        $this->automation_manager->run_export_automation();
    }
    
    /**
     * AJAX: Test S3 connection
     */
    public function ajax_test_s3_connection() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $result = $this->s3_uploader->test_connection();
        
        wp_send_json($result);
    }
    
    /**
     * AJAX: Run manual export
     */
    /**
     * AJAX: Test manual export (simple test)
     */
    public function ajax_test_manual_export() {
        try {
            error_log('WC S3 Export Pro: ajax_test_manual_export called!');
            error_log('WC S3 Export Pro: POST data: ' . print_r($_POST, true));
            
            check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
            
            if (!current_user_can('manage_woocommerce')) {
                wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
            }
            
            wp_send_json_success(array('message' => 'Test AJAX call successful!'));
        } catch (\Exception $e) {
            error_log('WC S3 Export Pro: Exception in ajax_test_manual_export: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Test failed with exception: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Run manual export
     */
    public function ajax_run_manual_export() {
        try {
            error_log('WC S3 Export Pro: ajax_run_manual_export called!');
            error_log('WC S3 Export Pro: POST data: ' . print_r($_POST, true));
            
            check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
            
            if (!current_user_can('manage_woocommerce')) {
                wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
            }
            
            $start_date = isset($_POST['export_start_date']) ? sanitize_text_field($_POST['export_start_date']) : date('Y-m-d');
            $end_date = isset($_POST['export_end_date']) ? sanitize_text_field($_POST['export_end_date']) : null;
            $export_types = isset($_POST['export_types']) ? array_map('sanitize_text_field', $_POST['export_types']) : array();
            $force_export = isset($_POST['force_export']) ? (bool)$_POST['force_export'] : false;
            
            error_log('WC S3 Export Pro: Parameters - start_date: ' . $start_date . ', end_date: ' . $end_date . ', export_types: ' . print_r($export_types, true));
            
            // Debug: Check what export types are configured
            $export_types_config = $this->settings->get_export_types_config();
            error_log('WC S3 Export Pro: All configured export types: ' . print_r($export_types_config, true));
            
            // If force export is enabled, we need to modify the export history behavior
            if ($force_export) {
                // This would require modifying the Automation_Manager to skip duplicate checks
                // For now, we'll just proceed with the normal export
            }
            
            error_log('WC S3 Export Pro: About to call automation_manager->run_manual_export');
            $result = $this->automation_manager->run_manual_export($start_date, $end_date, $export_types);
            error_log('WC S3 Export Pro: automation_manager->run_manual_export returned: ' . print_r($result, true));
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => sprintf('Manual export completed successfully. %d files created.', $result),
                    'files_created' => $result
                ));
            } else {
                wp_send_json_error(array('message' => 'Manual export failed. Please check the logs for details.'));
            }
        } catch (\Exception $e) {
            error_log('WC S3 Export Pro: Exception in ajax_run_manual_export: ' . $e->getMessage());
            error_log('WC S3 Export Pro: Exception trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'Manual export failed with exception: ' . $e->getMessage()));
        } catch (\Error $e) {
            error_log('WC S3 Export Pro: Error in ajax_run_manual_export: ' . $e->getMessage());
            error_log('WC S3 Export Pro: Error trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'Manual export failed with error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Get export history
     */
    public function ajax_get_export_history() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : '';
        $trigger_type = isset($_POST['trigger_type']) ? sanitize_text_field($_POST['trigger_type']) : '';
        
        $export_history = new Export_History();
        
        if (!empty($export_type)) {
            $history = $export_history->get_export_history_for_type($export_type);
        } else {
            $history = $export_history->get_export_history_for_date_range($start_date, $end_date);
        }
        
        // Filter by trigger type if specified
        if (!empty($trigger_type)) {
            $history = array_filter($history, function($record) use ($trigger_type) {
                return isset($record['trigger_type']) && $record['trigger_type'] === $trigger_type;
            });
        }
        
        // Format the history data for display
        $formatted_history = array();
        foreach ($history as $record) {
            $formatted_history[] = array(
                'id' => $record['id'],
                'date' => $record['date'],
                'export_type' => $record['export_type'],
                'export_type_name' => $record['export_name'] ?: $record['export_type'],
                'file_name' => $record['file_name'],
                'file_path' => $record['file_path'],
                'status' => $record['status'],
                'trigger_type' => isset($record['trigger_type']) ? $record['trigger_type'] : 'manual',
                'file_size' => $record['file_size'],
                'file_exists' => $record['file_exists'],
                'created_at' => $record['created_at']
            );
        }
        
        wp_send_json_success($formatted_history);
    }
    
    /**
     * AJAX: Delete export record
     */
    public function ajax_delete_export_record() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $record_id = isset($_POST['record_id']) ? sanitize_text_field($_POST['record_id']) : '';
        
        if (empty($record_id)) {
            wp_send_json_error(array('message' => 'Record ID is required.'));
        }
        
        $export_history = new Export_History();
        $result = $export_history->delete_export_record($record_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Export record deleted successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete export record.'));
        }
    }

    /**
     * AJAX: Delete multiple export records
     */
    public function ajax_delete_export_records() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        $record_ids = isset($_POST['record_ids']) ? (array) $_POST['record_ids'] : array();
        if (empty($record_ids)) {
            wp_send_json_error(array('message' => 'No record IDs provided.'));
        }
        $export_history = new Export_History();
        $deleted = 0;
        foreach ($record_ids as $record_id) {
            $record_id = sanitize_text_field($record_id);
            if ($export_history->delete_export_record($record_id)) {
                $deleted++;
            }
        }
        if ($deleted > 0) {
            wp_send_json_success(array('message' => sprintf('%d record(s) deleted.', $deleted)));
        }
        wp_send_json_error(array('message' => 'No records were deleted.'));
    }
    
    /**
     * AJAX: Download export file
     */
    public function ajax_download_export_file() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $file_path = isset($_GET['file_path']) ? sanitize_text_field($_GET['file_path']) : '';
        
        if (empty($file_path) || !file_exists($file_path)) {
            wp_die(__('File not found.', 'wc-s3-export-pro'));
        }
        
        // Security check: ensure the file is within the uploads directory
        $upload_dir = wp_upload_dir();
        $real_file_path = realpath($file_path);
        $real_upload_dir = realpath($upload_dir['basedir']);
        
        if (strpos($real_file_path, $real_upload_dir) !== 0) {
            wp_die(__('Access denied.', 'wc-s3-export-pro'));
        }
        
        // Set headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output file content
        readfile($file_path);
        exit;
    }
    
    /**
     * AJAX: Get export status
     */
    public function ajax_get_export_status() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $status = $this->monitoring->get_export_status();
        
        // Return in wp_send_json_success format so the admin JS can detect success
        wp_send_json_success($status);
    }
    
    /**
     * AJAX: Save S3 configuration
     */
    public function ajax_save_s3_config() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $config = array(
            'access_key' => sanitize_text_field($_POST['s3_access_key'] ?? ''),
            'secret_key' => sanitize_text_field($_POST['s3_secret_key'] ?? ''),
            'region' => sanitize_text_field($_POST['s3_region'] ?? ''),
            'bucket' => sanitize_text_field($_POST['s3_bucket'] ?? '')
        );
        
        $result = $this->settings->update_s3_config($config);
        
        if ($result) {
            wp_send_json_success(array('message' => 'S3 configuration saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save S3 configuration'));
        }
    }
    
    /**
     * AJAX: Save export settings
     */
    public function ajax_save_export_settings() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $settings = array(
            'export_frequency' => sanitize_text_field($_POST['export_frequency'] ?? 'daily'),
            'export_time' => sanitize_text_field($_POST['export_time'] ?? '01:00'),
        );
        
        $result = $this->settings->update_settings($settings);
        
        if ($result) {
            // Recreate schedules based on new settings
            $this->automation_manager->setup_automation();
            wp_send_json_success(array('message' => 'Export settings saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save export settings'));
        }
    }
    
    /**
     * AJAX: Save export types
     */
    public function ajax_save_export_types() {
        error_log('WC S3 Export Pro: ajax_save_export_types called!');
        error_log('WC S3 Export Pro: POST data: ' . print_r($_POST, true));
        
        // Get the raw data
        $export_types = $_POST['export_types'] ?? array();
        
        error_log('WC S3 Export Pro: Export types to save: ' . print_r($export_types, true));
        
        // Sanitize the data before saving
        $sanitized = array();
        if (is_array($export_types)) {
            foreach ($export_types as $index => $type) {
                if (isset($type['name'])) {
                    // Generate ID if not present
                    $id = !empty($type['id']) ? sanitize_text_field($type['id']) : sanitize_title($type['name']) . '_' . time();
                    
                    // Sanitize field mappings
                    $field_mappings = array();
                    if (isset($type['field_mappings']) && is_array($type['field_mappings'])) {
                        foreach ($type['field_mappings'] as $field_data) {
                            if (isset($field_data['enabled']) && $field_data['enabled'] && 
                                isset($field_data['column_name']) && isset($field_data['data_source'])) {
                                $field_mappings[sanitize_key($field_data['data_source'])] = sanitize_text_field($field_data['column_name']);
                            }
                        }
                    }
                    
                    $sanitized[] = array(
                        'id' => $id,
                        'name' => sanitize_text_field($type['name']),
                        'type' => sanitize_text_field($type['type'] ?? 'orders'),
                        'enabled' => (bool) ($type['enabled'] ?? false),
                        'frequency' => sanitize_text_field($type['frequency'] ?? 'daily'),
                        'time' => sanitize_text_field($type['time'] ?? '01:00'),
                        's3_folder' => sanitize_text_field($type['s3_folder'] ?? ''),
                        'local_uploads_folder' => sanitize_text_field($type['local_uploads_folder'] ?? ''),
                        'file_prefix' => sanitize_text_field($type['file_prefix'] ?? ''),
                        'description' => sanitize_textarea_field($type['description'] ?? ''),
                        'statuses' => isset($type['statuses']) && is_array($type['statuses']) ? array_map('sanitize_text_field', $type['statuses']) : array(),
                        'field_mappings' => $field_mappings
                    );
                }
            }
        }
        
        error_log('WC S3 Export Pro: Sanitized data: ' . print_r($sanitized, true));
        
        // Save to database
        $result = update_option('wc_s3_export_pro_export_types', $sanitized);
        
        error_log('WC S3 Export Pro: Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Export types configuration saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save export types configuration'));
        }
    }
    
    /**
     * AJAX: Save export types config
     */
    public function ajax_save_export_types_config() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        error_log('WC S3 Export Pro: ajax_save_export_types_config called!');
        error_log('WC S3 Export Pro: POST data: ' . print_r($_POST, true));
        
        // Get the raw data
        $export_types = $_POST['export_types'] ?? array();
        
        error_log('WC S3 Export Pro: Export types to save: ' . print_r($export_types, true));
        
        // Sanitize the data before saving
        $sanitized = array();
        if (is_array($export_types)) {
            foreach ($export_types as $index => $type) {
                if (isset($type['name'])) {
                    // Generate ID if not present
                    $id = !empty($type['id']) ? sanitize_text_field($type['id']) : sanitize_title($type['name']) . '_' . time();
                    
                    // Sanitize field mappings
                    $field_mappings = array();
                    if (isset($type['field_mappings']) && is_array($type['field_mappings'])) {
                        foreach ($type['field_mappings'] as $field_data) {
                            if (isset($field_data['enabled']) && $field_data['enabled'] && 
                                isset($field_data['column_name']) && isset($field_data['data_source'])) {
                                $field_mappings[sanitize_key($field_data['data_source'])] = sanitize_text_field($field_data['column_name']);
                            }
                        }
                    }
                    
                    $sanitized[] = array(
                        'id' => $id,
                        'name' => sanitize_text_field($type['name']),
                        'type' => sanitize_text_field($type['type'] ?? 'orders'),
                        'enabled' => (bool) ($type['enabled'] ?? false),
                        'frequency' => sanitize_text_field($type['frequency'] ?? 'daily'),
                        'time' => sanitize_text_field($type['time'] ?? '01:00'),
                        's3_folder' => sanitize_text_field($type['s3_folder'] ?? ''),
                        'local_uploads_folder' => sanitize_text_field($type['local_uploads_folder'] ?? ''),
                        'file_prefix' => sanitize_text_field($type['file_prefix'] ?? ''),
                        'description' => sanitize_textarea_field($type['description'] ?? ''),
                        'statuses' => isset($type['statuses']) && is_array($type['statuses']) ? array_map('sanitize_text_field', $type['statuses']) : array(),
                        'field_mappings' => $field_mappings
                    );
                }
            }
        }
        
        error_log('WC S3 Export Pro: Sanitized data: ' . print_r($sanitized, true));
        
        // Save to database
        $result = update_option('wc_s3_export_pro_export_types', $sanitized);
        
        error_log('WC S3 Export Pro: Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            // Recreate schedules when types change
            $this->automation_manager->setup_automation();
            wp_send_json_success(array('message' => 'Export types configuration saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save export types configuration'));
        }
    }
    
    /**
     * AJAX: Save source website configuration
     */
    public function ajax_save_source_website_config() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        error_log('WC S3 Export Pro: ajax_save_source_website_config called!');
        error_log('WC S3 Export Pro: POST data: ' . print_r($_POST, true));
        
        // Get the raw data
        $source_website_config = isset($_POST['source_website_config']) ? $_POST['source_website_config'] : array();
        
        error_log('WC S3 Export Pro: Source website config to save: ' . print_r($source_website_config, true));
        
        // Save using Settings class method which handles sanitization
        $result = $this->settings->update_source_website_config($source_website_config);
        
        error_log('WC S3 Export Pro: Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Source website configuration saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save source website configuration'));
        }
    }
    
    /**
     * AJAX: Add new export type
     */
    public function ajax_add_export_type() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $type_data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? 'orders'),
            'enabled' => (bool) ($_POST['enabled'] ?? true),
            'frequency' => sanitize_text_field($_POST['frequency'] ?? 'daily'),
            'time' => sanitize_text_field($_POST['time'] ?? '01:00'),
            's3_folder' => sanitize_text_field($_POST['s3_folder'] ?? ''),
            'local_uploads_folder' => sanitize_text_field($_POST['local_uploads_folder'] ?? ''),
            'file_prefix' => sanitize_text_field($_POST['file_prefix'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? '')
        );
        
        $result = $this->settings->add_export_type($type_data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Export type added successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add export type'));
        }
    }
    
    /**
     * AJAX: Remove export type
     */
    public function ajax_remove_export_type() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $type_id = sanitize_text_field($_POST['type_id'] ?? '');
        
        if (empty($type_id)) {
            wp_send_json_error(array('message' => 'Export type ID is required'));
        }
        
        $result = $this->settings->remove_export_type($type_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Export type removed successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to remove export type'));
        }
    }
    
    /**
     * AJAX: Setup automation
     */
    public function ajax_setup_automation() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $result = $this->automation_manager->setup_automation();
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * AJAX: Get log content
     */
    public function ajax_get_log_content() {
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        $recent_logs = $this->monitoring->get_recent_logs(50);
        
        if (!empty($recent_logs)) {
            $content = implode("\n", $recent_logs);
            wp_send_json_success(array('content' => $content));
        } else {
            wp_send_json_success(array('content' => 'No log entries found.'));
        }
    }
    
    /**
     * AJAX: Test AJAX functionality
     */
    public function ajax_test_ajax() {
        error_log('WC S3 Export Pro: Test AJAX handler called');
        
        check_ajax_referer('wc_s3_export_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions', 'wc-s3-export-pro'));
        }
        
        error_log('WC S3 Export Pro: Test AJAX handler completed successfully');
        wp_send_json_success(array('message' => 'AJAX test successful!'));
    }

    /**
     * AJAX: Minimal test
     */
    public function ajax_minimal_test() {
        error_log('WC S3 Export Pro: Minimal test called');
        wp_send_json_success(array('message' => 'Minimal test successful!'));
    }
    
    /**
     * AJAX: Simple test
     */
    public function ajax_simple_test() {
        error_log('WC S3 Export Pro: Simple AJAX handler called');
        wp_send_json_success(array('message' => 'Simple AJAX test successful!'));
    }
    
    /**
     * WP-CLI: Export orders
     */
    public function cli_export_orders($args, $assoc_args) {
        $date_param = $args[0] ?? null;
        $this->automation_manager->run_export_automation($date_param);
        \WP_CLI::success("Export finished for date: " . ($date_param ?: date('Y-m-d')));
    }
    
    /**
     * WP-CLI: Backfill export
     */
    public function cli_backfill_export($args, $assoc_args) {
        if (empty($args[0])) {
            \WP_CLI::error("Please provide a date in Y-m-d format (e.g., 2025-07-29)");
            return;
        }
        
        $target_date = $args[0];
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_date)) {
            \WP_CLI::error("Invalid date format. Use Y-m-d format (e.g., 2025-07-29)");
            return;
        }
        
        \WP_CLI::line("Starting manual export for date: $target_date");
        
        $success_count = $this->automation_manager->run_manual_export($target_date);
        
        if ($success_count > 0) {
            \WP_CLI::success("Manual export completed for $target_date - $success_count files created and uploaded to S3");
        } else {
            \WP_CLI::error("Manual export failed for $target_date - no files were created");
        }
    }
    
    /**
     * WP-CLI: Validate export system
     */
    public function cli_validate_export_system($args, $assoc_args) {
        $issues = $this->monitoring->validate_export_system();
        
        if (empty($issues)) {
            \WP_CLI::success("Export system validation passed - all components working correctly");
        } else {
            \WP_CLI::error("Export system issues found:");
            foreach ($issues as $issue) {
                \WP_CLI::line("  - $issue");
            }
        }
    }
    
    /**
     * WP-CLI: Export status
     */
    public function cli_export_status($args, $assoc_args) {
        $status = $this->monitoring->get_export_status();
        
        \WP_CLI::line("=== EXPORT STATUS ===");
        \WP_CLI::line("Last export: " . ($status['last_export'] ?: 'Never'));
        \WP_CLI::line("S3 connection: " . ($status['s3_connected'] ? '✓ Connected' : '❌ Not connected'));
        \WP_CLI::line("Automation status: " . ($status['automation_enabled'] ? '✓ Enabled' : '❌ Disabled'));
        \WP_CLI::line("Pending jobs: " . $status['pending_jobs']);
    }
    
    /**
     * WP-CLI: Reset export retries
     */
    public function cli_reset_export_retries($args, $assoc_args) {
        $this->automation_manager->reset_retry_flags();
        \WP_CLI::success("Export retry flags cleared");
    }
    
    /**
     * WP-CLI: Check scheduler
     */
    public function cli_check_scheduler($args, $assoc_args) {
        $this->monitoring->cli_check_scheduler();
    }
    
    /**
     * WP-CLI: Fix export automation
     */
    public function cli_fix_export_automation($args, $assoc_args) {
        $this->automation_manager->cli_fix_export_automation();
    }
    
    /**
     * WP-CLI: Simple fix export automation
     */
    public function cli_simple_fix_export_automation($args, $assoc_args) {
        $this->automation_manager->cli_simple_fix_export_automation();
    }
    
    /**
     * WP-CLI: Setup S3 config
     */
    public function cli_setup_s3_config($args, $assoc_args) {
        $this->settings->cli_setup_s3_config($args, $assoc_args);
    }
    
    /**
     * WP-CLI: Check S3 config
     */
    public function cli_check_s3_config($args, $assoc_args) {
        $this->s3_uploader->cli_check_s3_config();
    }
    
    /**
     * WP-CLI: Monitor exports
     */
    public function cli_monitor_exports($args, $assoc_args) {
        $this->monitoring->cli_monitor_exports();
    }
    
    /**
     * WP-CLI: Emergency recovery
     */
    public function cli_emergency_recovery($args, $assoc_args) {
        $this->automation_manager->cli_emergency_recovery($args, $assoc_args);
    }
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
} 