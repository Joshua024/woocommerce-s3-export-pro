<?php
namespace WC_S3_Export_Pro;

/**
 * Settings Class
 * 
 * Handles plugin settings, S3 configuration, and settings page functionality.
 */
class Settings {
    
    /**
     * Settings option name
     */
    const SETTINGS_OPTION = 'wc_s3_export_pro_settings';
    
    /**
     * S3 config option name
     */
    const S3_CONFIG_OPTION = 'wc_s3_export_pro_s3_config';
    
    /**
     * Export types configuration option name
     */
    const EXPORT_TYPES_OPTION = 'wc_s3_export_pro_export_types';
    
    /**
     * Available export type templates
     */
    const EXPORT_TYPE_TEMPLATES = [
        'orders' => 'Orders (Web Sales)',
        'order_items' => 'Order Items (Web Sale Lines)',
        'customers' => 'Customers',
        'products' => 'Products',
        'coupons' => 'Coupons',
        'custom' => 'Custom Export'
    ];
    
    /**
     * Default field mappings for each export type
     */
    const DEFAULT_FIELD_MAPPINGS = [
        'orders' => [
            'order_id' => 'Order ID',
            'order_date' => 'Order Date',
            'status' => 'Order Status',
            'order_total' => 'Order Total',
            'billing_email' => 'Billing Email'
        ],
        'order_items' => [
            'order_id' => 'Order ID',
            'product_id' => 'Product ID',
            'product_name' => 'Product Name',
            'quantity' => 'Quantity',
            'line_total' => 'Line Total'
        ],
        'customers' => [
            'customer_id' => 'Customer ID',
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name'
        ],
        'products' => [
            'product_id' => 'Product ID',
            'product_name' => 'Product Name',
            'product_sku' => 'Product SKU',
            'product_price' => 'Product Price'
        ],
        'coupons' => [
            'coupon_id' => 'Coupon ID',
            'coupon_code' => 'Coupon Code',
            'discount_type' => 'Discount Type',
            'amount' => 'Amount'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'wc_s3_export_pro_settings',
            self::SETTINGS_OPTION,
            array($this, 'sanitize_settings')
        );
        
        register_setting(
            'wc_s3_export_pro_settings',
            self::S3_CONFIG_OPTION,
            array($this, 'sanitize_s3_config')
        );
        
        // Removed sanitization callback to prevent double processing in AJAX handlers
        // register_setting(
        //     'wc_s3_export_pro_settings',
        //     self::EXPORT_TYPES_OPTION,
        //     array($this, 'sanitize_export_types_config')
        // );
        
        add_settings_section(
            'wc_s3_export_pro_general',
            __('General Settings', 'wc-s3-export-pro'),
            array($this, 'general_settings_section'),
            'wc_s3_export_pro_settings'
        );
        
        add_settings_section(
            'wc_s3_export_pro_export_types',
            __('Export Types Configuration', 'wc-s3-export-pro'),
            array($this, 'export_types_settings_section'),
            'wc_s3_export_pro_settings'
        );
        
        add_settings_section(
            'wc_s3_export_pro_s3',
            __('S3 Configuration', 'wc-s3-export-pro'),
            array($this, 's3_settings_section'),
            'wc_s3_export_pro_settings'
        );
        
        // General settings fields
        add_settings_field(
            'export_frequency',
            __('Default Export Frequency', 'wc-s3-export-pro'),
            array($this, 'export_frequency_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_general'
        );
        
        add_settings_field(
            'export_time',
            __('Default Export Time', 'wc-s3-export-pro'),
            array($this, 'export_time_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_general'
        );
        
        // S3 Configuration fields
        add_settings_field(
            's3_bucket',
            __('S3 Bucket', 'wc-s3-export-pro'),
            array($this, 's3_bucket_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_s3'
        );
        
        add_settings_field(
            's3_access_key',
            __('S3 Access Key ID', 'wc-s3-export-pro'),
            array($this, 's3_access_key_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_s3'
        );
        
        add_settings_field(
            's3_secret_key',
            __('S3 Secret Access Key', 'wc-s3-export-pro'),
            array($this, 's3_secret_key_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_s3'
        );
        
        add_settings_field(
            's3_region',
            __('S3 Region', 'wc-s3-export-pro'),
            array($this, 's3_region_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_s3'
        );
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        $defaults = array(
            'export_frequency' => 'daily',
            'export_time' => '01:00',
        );
        
        $settings = get_option(self::SETTINGS_OPTION, array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Get S3 config
     */
    public function get_s3_config() {
        $defaults = array(
            'bucket' => '',
            'access_key' => '',
            'secret_key' => '',
            'region' => 'us-east-1',
        );
        
        $config = get_option(self::S3_CONFIG_OPTION, array());
        return wp_parse_args($config, $defaults);
    }
    
    /**
     * Get export types configuration
     */
    public function get_export_types_config() {
        $config = get_option(self::EXPORT_TYPES_OPTION, array());
        
        // If no configuration exists, create default ones
        if (empty($config)) {
            $config = array(
                array(
                    'id' => 'orders',
                    'name' => 'Orders',
                    'type' => 'orders',
                    'enabled' => true,
                    'frequency' => 'daily',
                    'time' => '01:00',
                    's3_folder' => 'orders',
                    'local_uploads_folder' => 'orders',
                    'file_prefix' => 'FundsOnlineOrders',
                    'description' => 'Export order data including order items, customer info, and all related data',
                    'field_mappings' => self::DEFAULT_FIELD_MAPPINGS['orders']
                )
            );
        }
        
        // Convert field mappings format for form display
        foreach ($config as &$export_type) {
            if (isset($export_type['field_mappings']) && is_array($export_type['field_mappings'])) {
                $converted_mappings = array();
                $field_index = 0;
                
                foreach ($export_type['field_mappings'] as $data_source => $column_name) {
                    $converted_mappings[] = array(
                        'enabled' => true,
                        'column_name' => $column_name,
                        'data_source' => $data_source
                    );
                    $field_index++;
                }
                
                $export_type['field_mappings'] = $converted_mappings;
            }
        }
        
        return $config;
    }
    
    /**
     * Update S3 config
     */
    public function update_s3_config($config) {
        return update_option(self::S3_CONFIG_OPTION, $config);
    }
    
    /**
     * Update settings
     */
    public function update_settings($settings) {
        return update_option(self::SETTINGS_OPTION, $settings);
    }
    
    /**
     * Update export types config
     */
    public function update_export_types_config($input) {
        // Debug: Log the config being saved
        error_log('WC S3 Export Pro: update_export_types_config called with input: ' . print_r($input, true));
        
        try {
            $sanitized = array();
            
            if (is_array($input)) {
                error_log('WC S3 Export Pro: Input is array, processing...');
                foreach ($input as $index => $type) {
                    error_log('WC S3 Export Pro: Processing type ' . $index . ': ' . print_r($type, true));
                    if (isset($type['name'])) {
                        // Generate ID if not present
                        $id = !empty($type['id']) ? sanitize_text_field($type['id']) : sanitize_title($type['name']) . '_' . time();
                        error_log('WC S3 Export Pro: Generated ID: ' . $id);
                        
                        error_log('WC S3 Export Pro: Sanitizing field mappings...');
                        $field_mappings = $this->sanitize_field_mappings($type['field_mappings'] ?? [], $type['type'] ?? 'orders');
                        error_log('WC S3 Export Pro: Field mappings sanitized: ' . print_r($field_mappings, true));
                        
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
                            'field_mappings' => $field_mappings
                        );
                        error_log('WC S3 Export Pro: Type sanitized successfully');
                    } else {
                        error_log('WC S3 Export Pro: Type missing name, skipping');
                    }
                }
            } else {
                error_log('WC S3 Export Pro: Input is not an array: ' . gettype($input));
            }
            
            error_log('WC S3 Export Pro: Final sanitized data: ' . print_r($sanitized, true));
            
            $result = update_option(self::EXPORT_TYPES_OPTION, $sanitized);
            
            // Debug: Log the result
            error_log('WC S3 Export Pro: update_option result: ' . ($result ? 'true' : 'false'));
            
            return $result;
        } catch (\Exception $e) {
            error_log('WC S3 Export Pro: Exception in update_export_types_config: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add new export type
     */
    public function add_export_type($type_data) {
        $config = $this->get_export_types_config();
        
        // Generate unique ID
        $id = sanitize_title($type_data['name']) . '_' . time();
        
        $new_type = array(
            'id' => $id,
            'name' => sanitize_text_field($type_data['name']),
            'type' => sanitize_text_field($type_data['type']),
            'enabled' => (bool) ($type_data['enabled'] ?? true),
            'frequency' => sanitize_text_field($type_data['frequency'] ?? 'daily'),
            'time' => sanitize_text_field($type_data['time'] ?? '01:00'),
            's3_folder' => sanitize_text_field($type_data['s3_folder'] ?? ''),
            'local_uploads_folder' => sanitize_text_field($type_data['local_uploads_folder'] ?? ''),
            'file_prefix' => sanitize_text_field($type_data['file_prefix'] ?? ''),
            'description' => sanitize_textarea_field($type_data['description'] ?? ''),
            'field_mappings' => $this->sanitize_field_mappings($type_data['field_mappings'] ?? [], $type_data['type'])
        );
        
        $config[] = $new_type;
        
        return $this->update_export_types_config($config);
    }
    
    /**
     * Remove export type
     */
    public function remove_export_type($type_id) {
        $config = $this->get_export_types_config();
        
        foreach ($config as $key => $type) {
            if ($type['id'] === $type_id) {
                unset($config[$key]);
                break;
            }
        }
        
        // Re-index array
        $config = array_values($config);
        
        return $this->update_export_types_config($config);
    }
    
    /**
     * Update export type
     */
    public function update_export_type($type_id, $type_data) {
        $config = $this->get_export_types_config();
        
        foreach ($config as $key => $type) {
            if ($type['id'] === $type_id) {
                $config[$key] = array_merge($type, array(
                    'name' => sanitize_text_field($type_data['name']),
                    'type' => sanitize_text_field($type_data['type']),
                    'enabled' => (bool) ($type_data['enabled'] ?? true),
                    'frequency' => sanitize_text_field($type_data['frequency'] ?? 'daily'),
                    'time' => sanitize_text_field($type_data['time'] ?? '01:00'),
                    's3_folder' => sanitize_text_field($type_data['s3_folder'] ?? ''),
                    'local_uploads_folder' => sanitize_text_field($type_data['local_uploads_folder'] ?? ''),
                    'file_prefix' => sanitize_text_field($type_data['file_prefix'] ?? ''),
                    'description' => sanitize_textarea_field($type_data['description'] ?? '')
                ));
                break;
            }
        }
        
        return $this->update_export_types_config($config);
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['export_frequency'])) {
            $sanitized['export_frequency'] = sanitize_text_field($input['export_frequency']);
        }
        
        if (isset($input['export_time'])) {
            $sanitized['export_time'] = sanitize_text_field($input['export_time']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize S3 config
     */
    public function sanitize_s3_config($input) {
        $sanitized = array();
        
        if (isset($input['bucket'])) {
            $sanitized['bucket'] = sanitize_text_field($input['bucket']);
        }
        
        if (isset($input['access_key'])) {
            $sanitized['access_key'] = sanitize_text_field($input['access_key']);
        }
        
        if (isset($input['secret_key'])) {
            $sanitized['secret_key'] = sanitize_text_field($input['secret_key']);
        }
        
        if (isset($input['region'])) {
            $sanitized['region'] = sanitize_text_field($input['region']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize export types config
     */
    public function sanitize_export_types_config($input) {
        $sanitized = array();
        
        if (is_array($input)) {
            foreach ($input as $type) {
                if (isset($type['id']) && isset($type['name'])) {
                    $sanitized[] = array(
                        'id' => sanitize_text_field($type['id']),
                        'name' => sanitize_text_field($type['name']),
                        'type' => sanitize_text_field($type['type'] ?? 'orders'),
                        'enabled' => (bool) ($type['enabled'] ?? false),
                        'frequency' => sanitize_text_field($type['frequency'] ?? 'daily'),
                        'time' => sanitize_text_field($type['time'] ?? '01:00'),
                        's3_folder' => sanitize_text_field($type['s3_folder'] ?? ''),
                        'local_uploads_folder' => sanitize_text_field($type['local_uploads_folder'] ?? ''),
                        'file_prefix' => sanitize_text_field($type['file_prefix'] ?? ''),
                        'description' => sanitize_textarea_field($type['description'] ?? ''),
                        'field_mappings' => $this->sanitize_field_mappings($type['field_mappings'] ?? [], $type['type'] ?? 'orders')
                    );
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize field mappings
     */
    public function sanitize_field_mappings($field_mappings, $export_type) {
        error_log('WC S3 Export Pro: sanitize_field_mappings called with field_mappings: ' . print_r($field_mappings, true) . ' and export_type: ' . $export_type);
        
        $sanitized = array();
        
        // If no field mappings provided, return empty array
        if (empty($field_mappings)) {
            error_log('WC S3 Export Pro: No field mappings provided, returning empty array');
            return array();
        }
        
        // Handle the new table-based field mapping structure from the form
        if (is_array($field_mappings)) {
            error_log('WC S3 Export Pro: Field mappings is array, processing...');
            foreach ($field_mappings as $field_index => $field_data) {
                error_log('WC S3 Export Pro: Processing field ' . $field_index . ': ' . print_r($field_data, true));
                // Check if the field is enabled (checkbox is checked)
                $enabled = isset($field_data['enabled']) && $field_data['enabled'];
                
                if ($enabled) {
                    $column_name = isset($field_data['column_name']) ? sanitize_text_field($field_data['column_name']) : '';
                    $data_source = isset($field_data['data_source']) ? sanitize_text_field($field_data['data_source']) : '';
                    
                    if (!empty($column_name) && !empty($data_source)) {
                        $sanitized[sanitize_key($data_source)] = $column_name;
                        error_log('WC S3 Export Pro: Added field mapping: ' . sanitize_key($data_source) . ' => ' . $column_name);
                    }
                }
            }
        }
        
        error_log('WC S3 Export Pro: Final sanitized field mappings: ' . print_r($sanitized, true));
        return $sanitized;
    }
    
    /**
     * Get available fields for export type
     */
    public function get_available_fields($export_type) {
        return self::DEFAULT_FIELD_MAPPINGS[$export_type] ?? array();
    }
    
    /**
     * General settings section
     */
    public function general_settings_section() {
        echo '<p>' . __('Configure default export settings that will be used for all export types unless overridden below.', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * Export types settings section
     */
    public function export_types_settings_section() {
        echo '<p>' . __('Configure individual settings for each export type. You can add, remove, and configure as many export types as you need.', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * S3 settings section
     */
    public function s3_settings_section() {
        echo '<p>' . __('Configure your Amazon S3 credentials and bucket settings.', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * Export frequency field
     */
    public function export_frequency_field() {
        $settings = $this->get_settings();
        $value = $settings['export_frequency'];
        
        echo '<select name="' . self::SETTINGS_OPTION . '[export_frequency]">';
        echo '<option value="hourly" ' . selected($value, 'hourly', false) . '>' . __('Hourly', 'wc-s3-export-pro') . '</option>';
        echo '<option value="daily" ' . selected($value, 'daily', false) . '>' . __('Daily', 'wc-s3-export-pro') . '</option>';
        echo '<option value="weekly" ' . selected($value, 'weekly', false) . '>' . __('Weekly', 'wc-s3-export-pro') . '</option>';
        echo '<option value="monthly" ' . selected($value, 'monthly', false) . '>' . __('Monthly', 'wc-s3-export-pro') . '</option>';
        echo '</select>';
    }
    
    /**
     * Export time field
     */
    public function export_time_field() {
        $settings = $this->get_settings();
        $value = $settings['export_time'];
        
        echo '<input type="time" name="' . self::SETTINGS_OPTION . '[export_time]" value="' . esc_attr($value) . '" />';
    }
    
    /**
     * S3 bucket field
     */
    public function s3_bucket_field() {
        $config = $this->get_s3_config();
        $value = $config['bucket'];
        
        echo '<input type="text" name="' . self::S3_CONFIG_OPTION . '[bucket]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * S3 access key field
     */
    public function s3_access_key_field() {
        $config = $this->get_s3_config();
        $value = $config['access_key'];
        
        echo '<input type="text" name="' . self::S3_CONFIG_OPTION . '[access_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * S3 secret key field
     */
    public function s3_secret_key_field() {
        $config = $this->get_s3_config();
        $value = $config['secret_key'];
        
        echo '<input type="password" name="' . self::S3_CONFIG_OPTION . '[secret_key]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * S3 region field
     */
    public function s3_region_field() {
        $config = $this->get_s3_config();
        $value = $config['region'];
        
        echo '<select name="' . self::S3_CONFIG_OPTION . '[region]">';
        echo '<option value="us-east-1" ' . selected($value, 'us-east-1', false) . '>US East (N. Virginia)</option>';
        echo '<option value="us-west-2" ' . selected($value, 'us-west-2', false) . '>US West (Oregon)</option>';
        echo '<option value="eu-west-1" ' . selected($value, 'eu-west-1', false) . '>Europe (Ireland)</option>';
        echo '<option value="eu-west-2" ' . selected($value, 'eu-west-2', false) . '>Europe (London)</option>';
        echo '<option value="ap-southeast-1" ' . selected($value, 'ap-southeast-1', false) . '>Asia Pacific (Singapore)</option>';
        echo '</select>';
    }
    
    /**
     * CLI setup S3 config
     */
    public function cli_setup_s3_config($args, $assoc_args) {
        $bucket = $assoc_args['bucket'] ?? '';
        $access_key = $assoc_args['access-key'] ?? '';
        $secret_key = $assoc_args['secret-key'] ?? '';
        $region = $assoc_args['region'] ?? 'us-east-1';
        
        if (empty($bucket) || empty($access_key) || empty($secret_key)) {
            \WP_CLI::error('Bucket, access-key, and secret-key are required.');
        }
        
        $config = array(
            'bucket' => $bucket,
            'access_key' => $access_key,
            'secret_key' => $secret_key,
            'region' => $region,
        );
        
        $result = $this->update_s3_config($config);
        
        if ($result) {
            \WP_CLI::success('S3 configuration saved successfully.');
        } else {
            \WP_CLI::error('Failed to save S3 configuration.');
        }
    }
} 


