<?php
namespace WC_S3_Export_Pro;

// Note: AWS SDK classes are used conditionally throughout the code

/**
 * S3 Uploader Class
 * 
 * Handles all S3 upload functionality including connection testing, file uploads, and configuration management.
 */
class S3_Uploader {
    
    /**
     * S3 Client instance
     */
    private $s3_client;
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Whether AWS SDK is available
     */
    private $aws_sdk_available;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new Settings();
        $this->aws_sdk_available = class_exists('Aws\S3\S3Client');
        
        if ($this->aws_sdk_available) {
            $this->init_s3_client();
        } else {
            error_log('WC S3 Export Pro: AWS SDK not available. S3 functionality disabled.');
        }
    }
    
    /**
     * Initialize S3 client
     */
    private function init_s3_client() {
        if (!$this->aws_sdk_available) {
            return false;
        }
        
        $s3_config = $this->settings->get_s3_config();
        
        if (empty($s3_config['access_key']) || empty($s3_config['secret_key'])) {
            return false;
        }
        
        try {
            if (class_exists('Aws\S3\S3Client')) {
                $this->s3_client = new \Aws\S3\S3Client([
                    'version'     => 'latest',
                    'region'      => $s3_config['region'] ?: 'eu-west-2',
                    'credentials' => [
                        'key'    => $s3_config['access_key'],
                        'secret' => $s3_config['secret_key'],
                    ],
                ]);
                
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $this->log_error('Failed to initialize S3 client: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload file to S3
     */
    public function upload_file($bucket, $filename, $file_path, $directory, $folder = '') {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Starting S3 upload for $filename", $log_file);
        
        // Validate input parameters
        if (empty($bucket) || empty($filename) || empty($file_path) || empty($directory)) {
            $this->log("[$timestamp] ERROR: Missing S3 upload parameters", $log_file);
            return false;
        }
        
        // Build S3 key with optional folder
        $s3_key = $directory;
        if (!empty($folder)) {
            $s3_key = $folder . '/' . $directory;
        }
        $s3_key .= '/' . $filename;
        
        // Verify file exists and is readable
        if (!file_exists($file_path)) {
            $this->log("[$timestamp] ERROR: File does not exist: $file_path", $log_file);
            return false;
        }
        
        if (!is_readable($file_path)) {
            $this->log("[$timestamp] ERROR: File is not readable: $file_path", $log_file);
            return false;
        }
        
        $file_size = filesize($file_path);
        if ($file_size === 0) {
            $this->log("[$timestamp] ERROR: File is empty: $file_path", $log_file);
            return false;
        }
        
        $this->log("[$timestamp] File validated ($file_size bytes): $filename", $log_file);
        
        // Check if S3 client is available
        if (!$this->s3_client) {
            $this->log("[$timestamp] ERROR: S3 client not initialized", $log_file);
            return false;
        }
        
        $max_attempts = 3;
        $attempt = 1;
        $success = false;
        
        while ($attempt <= $max_attempts && !$success) {
            try {
                $result = $this->s3_client->putObject([
                    'Bucket' => $bucket,
                    'Key' => $s3_key,
                    'SourceFile' => $file_path,
                ]);
                
                // Verify upload was successful
                if ($result && isset($result['@metadata']['statusCode']) && $result['@metadata']['statusCode'] == 200) {
                    $this->log("[$timestamp] SUCCESS: S3 upload completed for $filename", $log_file);
                    
                    // Log S3 object details
                    if (isset($result['ObjectURL'])) {
                        $this->log("[$timestamp] S3 URL: " . $result['ObjectURL'], $log_file);
                    }
                    
                    $success = true;
                } else {
                    $this->log("[$timestamp] WARNING: S3 upload status unclear for $filename", $log_file);
                    return false;
                }
                
            } catch (\Exception $e) {
                $error_message = $e->getMessage();
                if (class_exists('Aws\Exception\AwsException') && $e instanceof \Aws\Exception\AwsException) {
                    $error_message = $e->getAwsErrorMessage();
                    $this->log("[$timestamp] ERROR: AWS exception during S3 upload (Attempt $attempt/$max_attempts) - " . $error_message, $log_file);
                    $this->log("[$timestamp] AWS Error Code: " . $e->getAwsErrorCode(), $log_file);
                } else {
                    $this->log("[$timestamp] ERROR: General exception during S3 upload (Attempt $attempt/$max_attempts) - " . $error_message, $log_file);
                }
                
                if ($attempt === $max_attempts) {
                    $this->log("[$timestamp] All retry attempts failed for $filename", $log_file);
                    return false;
                }
                
                sleep($attempt * 2); // exponential backoff: 2s, 4s...
            }
            
            $attempt++;
        }
        
        return $success;
    }
    
    /**
     * Test S3 connection
     */
    public function test_connection() {
        $s3_config = $this->settings->get_s3_config();
        
        if (empty($s3_config['access_key']) || empty($s3_config['secret_key'])) {
            return array(
                'success' => false,
                'message' => 'S3 credentials not configured'
            );
        }
        
        if (!$this->s3_client) {
            return array(
                'success' => false,
                'message' => 'Failed to initialize S3 client'
            );
        }
        
        try {
            // Test by listing buckets
            if (!$this->aws_sdk_available || !$this->s3_client) {
                return array(
                    'success' => false,
                    'message' => 'AWS SDK not available or S3 client not initialized'
                );
            }
            
            $result = $this->s3_client->listBuckets();
            
            return array(
                'success' => true,
                'message' => 'S3 connection successful',
                'buckets' => count($result['Buckets'])
            );
            
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            if (class_exists('Aws\Exception\AwsException') && $e instanceof \Aws\Exception\AwsException) {
                $error_message = $e->getAwsErrorMessage();
            }
            
            return array(
                'success' => false,
                'message' => 'S3 connection failed: ' . $error_message
            );
        }
    }
    
    /**
     * WP-CLI: Check S3 config
     */
    public function cli_check_s3_config() {
        if (!defined('WP_CLI') || !\WP_CLI) {
            return;
        }
        
        \WP_CLI::line("=== S3 CONFIGURATION CHECK ===\n");
        
        $s3_config = $this->settings->get_s3_config();
        
        \WP_CLI::line("S3 Access Key ID: " . ($s3_config['access_key'] ? '✓ Set' : '❌ Not set'));
        \WP_CLI::line("S3 Secret Key: " . ($s3_config['secret_key'] ? '✓ Set' : '❌ Not set'));
        \WP_CLI::line("S3 Region: " . ($s3_config['region'] ?: 'eu-west-2 (default)'));
        \WP_CLI::line("S3 Bucket: " . ($s3_config['bucket'] ?: 'Not configured'));
        
        if (empty($s3_config['access_key']) || empty($s3_config['secret_key'])) {
            \WP_CLI::error("S3 credentials are not configured!");
            \WP_CLI::line("\nTo fix this:");
            \WP_CLI::line("1. Go to WooCommerce → Export Manager → Settings");
            \WP_CLI::line("2. Configure your S3 credentials");
            \WP_CLI::line("3. Or run: wp wc-s3 setup_s3_config <access_key> <secret_key>");
            return;
        }
        
        // Test S3 connection
        \WP_CLI::line("\nTesting S3 connection...");
        $result = $this->test_connection();
        
        if ($result['success']) {
            \WP_CLI::success("✓ S3 connection successful");
            if (isset($result['buckets'])) {
                \WP_CLI::line("Found " . $result['buckets'] . " buckets");
            }
        } else {
            \WP_CLI::error("❌ S3 connection error: " . $result['message']);
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
} 