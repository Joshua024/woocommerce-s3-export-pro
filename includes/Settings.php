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
        
        add_settings_section(
            'wc_s3_export_pro_general',
            __('General Settings', 'wc-s3-export-pro'),
            array($this, 'general_settings_section'),
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
            __('Export Frequency', 'wc-s3-export-pro'),
            array($this, 'export_frequency_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_general'
        );
        
        add_settings_field(
            'export_time',
            __('Export Time', 'wc-s3-export-pro'),
            array($this, 'export_time_field'),
            'wc_s3_export_pro_settings',
            'wc_s3_export_pro_general'
        );
        
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
     * Get plugin settings
     */
    public function get_settings() {
        $defaults = array(
            'export_frequency' => 'daily',
            'export_time' => '02:00',
            'enable_notifications' => true,
            'retry_attempts' => 3,
            'retry_delay' => 300,
        );
        
        $settings = get_option(self::SETTINGS_OPTION, array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Get S3 configuration
     */
    public function get_s3_config() {
        $defaults = array(
            'bucket' => '',
            'access_key' => '',
            'secret_key' => '',
            'region' => 'eu-west-2',
        );
        
        $config = get_option(self::S3_CONFIG_OPTION, array());
        return wp_parse_args($config, $defaults);
    }
    
    /**
     * Update S3 configuration
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
        
        if (isset($input['enable_notifications'])) {
            $sanitized['enable_notifications'] = (bool) $input['enable_notifications'];
        }
        
        if (isset($input['retry_attempts'])) {
            $sanitized['retry_attempts'] = absint($input['retry_attempts']);
        }
        
        if (isset($input['retry_delay'])) {
            $sanitized['retry_delay'] = absint($input['retry_delay']);
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
     * General settings section
     */
    public function general_settings_section() {
        echo '<p>' . __('Configure the general export settings.', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * S3 settings section
     */
    public function s3_settings_section() {
        echo '<p>' . __('Configure your AWS S3 credentials and bucket settings.', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * Export frequency field
     */
    public function export_frequency_field() {
        $settings = $this->get_settings();
        $value = $settings['export_frequency'];
        
        echo '<select name="' . self::SETTINGS_OPTION . '[export_frequency]">';
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
        echo '<p class="description">' . __('Time in 24-hour format (HH:MM)', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * S3 bucket field
     */
    public function s3_bucket_field() {
        $config = $this->get_s3_config();
        $value = $config['bucket'];
        
        echo '<input type="text" name="' . self::S3_CONFIG_OPTION . '[bucket]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your S3 bucket name', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * S3 access key field
     */
    public function s3_access_key_field() {
        $config = $this->get_s3_config();
        $value = $config['access_key'];
        
        echo '<input type="text" name="' . self::S3_CONFIG_OPTION . '[access_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your AWS Access Key ID', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * S3 secret key field
     */
    public function s3_secret_key_field() {
        $config = $this->get_s3_config();
        $value = $config['secret_key'];
        
        echo '<input type="password" name="' . self::S3_CONFIG_OPTION . '[secret_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your AWS Secret Access Key', 'wc-s3-export-pro') . '</p>';
    }
    
    /**
     * S3 region field
     */
    public function s3_region_field() {
        $config = $this->get_s3_config();
        $value = $config['region'];
        
        $regions = array(
            'us-east-1' => 'US East (N. Virginia)',
            'us-east-2' => 'US East (Ohio)',
            'us-west-1' => 'US West (N. California)',
            'us-west-2' => 'US West (Oregon)',
            'eu-west-1' => 'Europe (Ireland)',
            'eu-west-2' => 'Europe (London)',
            'eu-central-1' => 'Europe (Frankfurt)',
            'ap-southeast-1' => 'Asia Pacific (Singapore)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)',
            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        );
        
        echo '<select name="' . self::S3_CONFIG_OPTION . '[region]">';
        foreach ($regions as $region => $name) {
            echo '<option value="' . esc_attr($region) . '" ' . selected($value, $region, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }
    
    /**
     * WP-CLI: Setup S3 config
     */
    public function cli_setup_s3_config($args, $assoc_args) {
        \WP_CLI::line("=== S3 CONFIGURATION SETUP ===\n");
        
        $access_key = $args[0] ?? '';
        $secret_key = $args[1] ?? '';
        
        if (empty($access_key) || empty($secret_key)) {
            \WP_CLI::error("Usage: wp wc-s3 setup_s3_config <access_key> <secret_key>");
            \WP_CLI::line("\nExample:");
            \WP_CLI::line("wp wc-s3 setup_s3_config AKIAIOSFODNN7EXAMPLE wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY");
            return;
        }
        
        $config = array(
            'access_key' => $access_key,
            'secret_key' => $secret_key,
            'region' => 'eu-west-2',
            'bucket' => 'directoryofsocialchange'
        );
        
        if ($this->update_s3_config($config)) {
            \WP_CLI::success("✓ S3 credentials saved to database");
            \WP_CLI::line("✓ Access Key ID: " . substr($access_key, 0, 10) . "...");
            \WP_CLI::line("✓ Secret Key: " . substr($secret_key, 0, 10) . "...");
            \WP_CLI::line("✓ Region: eu-west-2");
            \WP_CLI::line("✓ Bucket: directoryofsocialchange");
            \WP_CLI::line("\nRun 'wp wc-s3 check_s3_config' to test the connection");
        } else {
            \WP_CLI::error("❌ Failed to save S3 credentials");
        }
    }
} 