<?php
namespace WC_S3_Export_Pro;

/**
 * CSV Generator Class
 * 
 * Handles data extraction from WooCommerce and CSV generation with custom field mappings.
 */
class CSV_Generator {
    
    /**
     * Settings instance
     */
    private $settings;
    
    /**
     * Current export type being processed
     */
    private $current_export_type = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new Settings();
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        $this->log("[$timestamp] CSV Generator instantiated successfully", $log_file);
        
        // Set error handling
        set_error_handler(array($this, 'error_handler'));
    }
    
    /**
     * Custom error handler
     */
    public function error_handler($errno, $errstr, $errfile, $errline) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        // Only log certain error types to avoid spam
        if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $this->log("[$timestamp] CRITICAL ERROR: [$errno] $errstr in $errfile on line $errline", $log_file);
        } elseif (in_array($errno, [E_WARNING, E_USER_WARNING])) {
            $this->log("[$timestamp] WARNING: [$errno] $errstr in $errfile on line $errline", $log_file);
        }
        
        return false; // Let PHP handle the error normally
    }
    
    /**
     * Generate CSV file for export type
     */
    public function generate_csv($export_type, $date_param = null) {
        // Store the current export type for use in other methods
        $this->current_export_type = $export_type;
        
        // Make it resilient to long runs
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('memory_limit', '512M');
        
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] ===== CSV GENERATOR START =====", $log_file);
        $this->log("[$timestamp] Generating CSV for export type: {$export_type['name']}", $log_file);
        $this->log("[$timestamp] Export type details: " . print_r($export_type, true), $log_file);
        $this->log("[$timestamp] Date param: $date_param", $log_file);
        
        // Get field mappings
        $field_mappings = isset($export_type['field_mappings']) ? $export_type['field_mappings'] : array();
        
        $this->log("[$timestamp] Field mappings count: " . count($field_mappings), $log_file);
        $this->log("[$timestamp] Field mappings details: " . print_r($field_mappings, true), $log_file);
        
        if (empty($field_mappings)) {
            $this->log("[$timestamp] No field mappings found for export type: {$export_type['name']}", $log_file);
            return false;
        }
        
        // Extract data based on export type
        $this->log("[$timestamp] About to extract data for type: {$export_type['type']}", $log_file);
        $this->log("[$timestamp] Calling extract_data with type: {$export_type['type']}, date_param: $date_param", $log_file);
        
        $data = $this->extract_data($export_type['type'], $date_param, $export_type);
        
        $this->log("[$timestamp] Data extracted count: " . count($data), $log_file);
        if (!empty($data)) {
            $this->log("[$timestamp] First data row sample: " . print_r(array_slice($data, 0, 1), true), $log_file);
        }
        
        if (empty($data)) {
            $this->log("[$timestamp] No data found for export type: {$export_type['name']}", $log_file);
            return false;
        }
        
        $this->log("[$timestamp] About to create CSV file with " . count($data) . " records", $log_file);
        $this->log("[$timestamp] About to call create_csv_file", $log_file);
        
        // Generate CSV file
        try {
            $file_data = $this->create_csv_file($data, $field_mappings, $export_type, $date_param);
            
            if ($file_data) {
                $this->log("[$timestamp] CSV file generated successfully for: {$export_type['name']}", $log_file);
            } else {
                $this->log("[$timestamp] Failed to generate CSV file for: {$export_type['name']} - create_csv_file returned false", $log_file);
            }
            
            return $file_data;
        } catch (\Exception $e) {
            $this->log("[$timestamp] Exception during CSV generation for {$export_type['name']}: " . $e->getMessage(), $log_file);
            $this->log("[$timestamp] Exception trace: " . $e->getTraceAsString(), $log_file);
            return false;
        }
        
        $this->log("[$timestamp] ===== CSV GENERATOR END =====", $log_file);
    }
    
    /**
     * Extract data from WooCommerce based on export type
     */
    private function extract_data($export_type, $date_param = null, $export_type_config = null) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] extract_data called with type: $export_type", $log_file);
        
        try {
            switch ($export_type) {
                case 'orders':
                    $this->log("[$timestamp] Calling extract_orders_data", $log_file);
                    $result = $this->extract_orders_data($date_param, $export_type_config);
                    $this->log("[$timestamp] extract_orders_data returned " . count($result) . " records", $log_file);
                    return $result;
                case 'customers':
                    return $this->extract_customers_data($date_param);
                case 'products':
                    return $this->extract_products_data($date_param);
                case 'coupons':
                    return $this->extract_coupons_data($date_param);
                default:
                    $this->log("[$timestamp] Unknown export type: $export_type", $log_file);
                    return array();
            }
        } catch (\Exception $e) {
            $this->log("[$timestamp] Exception in extract_data: " . $e->getMessage(), $log_file);
            return array();
        }
    }
    
    /**
     * Extract orders data
     */
    private function extract_orders_data($date_param = null, $export_type = null) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        // Get configured statuses from export type, or use all statuses if none specified
        $statuses = array();
        if ($export_type && isset($export_type['statuses']) && !empty($export_type['statuses'])) {
            $statuses = $export_type['statuses'];
            $this->log("[$timestamp] Using configured statuses: " . implode(', ', $statuses), $log_file);
        } else {
            // Default to all order statuses if none specified (like WooCommerce CSV Export plugin)
            $statuses = array_keys(wc_get_order_statuses());
            $this->log("[$timestamp] No statuses configured, using all order statuses: " . implode(', ', $statuses), $log_file);
        }
        
        $args = array(
            'limit' => -1,
            'status' => $statuses,
            'return' => 'objects'
        );
        
        if ($date_param) {
            // Convert date to proper format for WooCommerce
            $date_obj = \DateTime::createFromFormat('Y-m-d', $date_param);
            if ($date_obj) {
                $start_date = $date_obj->format('Y-m-d 00:00:00');
                $end_date = $date_obj->format('Y-m-d 23:59:59');
                $args['date_created'] = $start_date . '...' . $end_date;
                $this->log("[$timestamp] Filtering orders by date range: $start_date to $end_date", $log_file);
            }
        }
        
        $this->log("[$timestamp] Extracting orders with args: " . print_r($args, true), $log_file);
        
        $orders = wc_get_orders($args);
        
        $this->log("[$timestamp] Found " . count($orders) . " orders", $log_file);
        
        $data = array();
        $order_count = 0;
        
        $this->log("[$timestamp] Starting to process orders...", $log_file);
        
        foreach ($orders as $order) {
            try {
                $order_count++;
                $order_id = method_exists($order, 'get_id') ? $order->get_id() : (isset($order->id) ? $order->id : 'unknown');
                $this->log("[$timestamp] Processing order ID: {$order_id}", $log_file);
                
                // Validate order object
                if (!$order || !is_object($order)) {
                    $this->log("[$timestamp] Invalid order object for order ID: {$order_id}", $log_file);
                    continue;
                }
                
                // Get order-level data with error handling
                try {
                    $order_data = array(
                        'order_id' => $order->get_id(),
                        'order_number' => $order->get_order_number(),
                        'order_number_formatted' => $order->get_order_number(),
                        'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                        'status' => $order->get_status(),
                        'shipping_total' => $order->get_shipping_total(),
                        'shipping_tax_total' => $this->get_shipping_tax_total($order),
                        'fee_total' => $order->get_total_fees(),
                        'fee_tax_total' => $this->get_fee_tax_total($order),
                        'tax_total' => $order->get_total_tax(),
                        'discount_total' => $order->get_total_discount(),
                        'order_total' => $order->get_total(),
                        'refunded_total' => $order->get_total_refunded(),
                        'order_currency' => $order->get_currency(),
                        'payment_method' => $order->get_payment_method(),
                        // Additional payment meta/derived fields
                        'worldpay_transaction_id' => method_exists($order, 'get_transaction_id') ? $order->get_transaction_id() : '',
                        'transaction_type' => method_exists($order, 'get_payment_method_title') ? $order->get_payment_method_title() : $order->get_payment_method(),
                        'shipping_method' => $order->get_shipping_method(),
                        'customer_id' => $order->get_customer_id(),
                        // Custom billing meta
                        'charity_number' => $order->get_meta('_billing_charity_number', true),
                        'billing_title' => $order->get_meta('_billing_title', true),
                        'billing_first_name' => $order->get_billing_first_name(),
                        'billing_last_name' => $order->get_billing_last_name(),
                        'billing_full_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                        'billing_company' => $order->get_billing_company(),
                        'vat_number' => $order->get_meta('_billing_vat'),
                        'billing_email' => $order->get_billing_email(),
                        'billing_phone' => $order->get_billing_phone(),
                        'billing_address_1' => $order->get_billing_address_1(),
                        'billing_address_2' => $order->get_billing_address_2(),
                        'billing_postcode' => $order->get_billing_postcode(),
                        'billing_city' => $order->get_billing_city(),
                        'billing_state' => $order->get_billing_state(),
                        'billing_state_code' => $order->get_billing_state(),
                        'billing_country' => $order->get_billing_country(),
                        'shipping_first_name' => $order->get_shipping_first_name(),
                        'shipping_last_name' => $order->get_shipping_last_name(),
                        'shipping_full_name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                        'shipping_address_1' => $order->get_shipping_address_1(),
                        'shipping_address_2' => $order->get_shipping_address_2(),
                        'shipping_postcode' => $order->get_shipping_postcode(),
                        'shipping_city' => $order->get_shipping_city(),
                        'shipping_state' => $order->get_shipping_state(),
                        'shipping_state_code' => $order->get_shipping_state(),
                        'shipping_country' => $order->get_shipping_country(),
                        'shipping_company' => $order->get_shipping_company(),
                        'customer_note' => $order->get_customer_note(),
                        // Source Website (already requested by stakeholders)
                        'source_website' => $this->get_source_website(),
                        'line_items' => $this->format_line_items($this->get_line_items($order)),
                        'shipping_items' => $this->format_shipping_items($this->get_shipping_items($order)),
                        'fee_items' => $this->format_fee_items($this->get_fee_items($order)),
                        'tax_items' => $this->format_tax_items($this->get_tax_items($order)),
                        'coupon_items' => $this->format_coupon_items($this->get_coupon_items($order)),
                        'refunds' => $this->format_refunds($this->get_refunds($order)),
                        'order_notes' => $this->format_order_notes($this->get_order_notes($order)),
                        'download_permissions' => $this->format_download_permissions($this->get_download_permissions($order)),
                        'order_meta' => $this->get_order_meta($order)
                    );
                    
                    $this->log("[$timestamp] Order data extracted successfully for order ID: {$order_id}", $log_file);
                } catch (\Exception $e) {
                    $this->log("[$timestamp] Error extracting order data for order ID {$order_id}: " . $e->getMessage(), $log_file);
                    continue; // Skip this order
                }
                
                // Check if we have items in this order
                $items = $order->get_items();
                
                if (!empty($items)) {
                    $this->log("[$timestamp]  - order {$order_id} has " . count($items) . " items", $log_file);
                    // Create a row for each item, including order-level data
                    foreach ($items as $item_id => $item) {
                        try {
                            $this->log("[$timestamp]  - processing item {$item_id} for order {$order_id}", $log_file);
                            $item_data = $order_data; // Start with order-level data
                            
                            // Add item-level data
                            $product_id = 0;
                            $variation_id = 0;
                            
                            // Get product ID safely
                            if ($item->get_type() === 'line_item') {
                                $product_id = method_exists($item, 'get_product_id') ? $item->get_product_id() : 0;
                                $variation_id = method_exists($item, 'get_variation_id') ? $item->get_variation_id() : 0;
                            }
                            
                            $product = $product_id ? wc_get_product($product_id) : null;
                            
                            $item_data['item_id'] = $item_id;
                            $item_data['item_product_id'] = $product_id;
                            $item_data['item_name'] = $item->get_name();
                            $item_data['item_sku'] = $product ? $product->get_sku() : '';
                            $item_data['item_quantity'] = $item->get_quantity();
                            
                            // Use proper WooCommerce item data access for product items
                            if ($item->get_type() === 'line_item') {
                                // These methods exist on WC_Order_Item_Product
                                $item_data['item_subtotal'] = method_exists($item, 'get_subtotal') ? $item->get_subtotal() : 0;
                                $item_data['item_subtotal_tax'] = method_exists($item, 'get_subtotal_tax') ? $item->get_subtotal_tax() : 0;
                                $item_data['item_total'] = method_exists($item, 'get_total') ? $item->get_total() : 0;
                                $item_data['item_total_tax'] = method_exists($item, 'get_total_tax') ? $item->get_total_tax() : 0;
                                
                                // Calculate price from total/quantity
                                $quantity = $item->get_quantity();
                                $total = method_exists($item, 'get_total') ? $item->get_total() : 0;
                                $item_data['item_price'] = $quantity > 0 ? wc_format_decimal($total / $quantity, 2) : 0;
                            } else {
                                // Fallback for other item types
                                $item_data['item_subtotal'] = 0;
                                $item_data['item_subtotal_tax'] = 0;
                                $item_data['item_total'] = 0;
                                $item_data['item_total_tax'] = 0;
                                $item_data['item_price'] = 0;
                            }
                            
                            // For refunded amounts, calculate from order refunds
                            $refunded_amount = $order->get_total_refunded_for_item($item_id);
                            $refunded_qty = $order->get_qty_refunded_for_item($item_id);
                            $item_data['item_refunded'] = wc_format_decimal($refunded_amount, 2);
                            $item_data['item_refunded_qty'] = $refunded_qty;
                            
                            $item_data['item_meta'] = $this->get_item_meta($item);
                            
                            $data[] = $item_data;
                        } catch (\Exception $e) {
                            $this->log("[$timestamp] Exception processing item {$item_id} for order {$order_id}: " . $e->getMessage(), $log_file);
                            continue; // Skip this item and continue
                        }
                    }
                } else {
                    // No items, just add order-level data
                    $this->log("[$timestamp]  - order {$order_id} has no items", $log_file);
                    $data[] = $order_data;
                }
                
                $this->log("[$timestamp] Successfully processed order ID: {$order_id}", $log_file);
                
            } catch (\Exception $e) {
                $this->log("[$timestamp] Exception processing order ID {$order_id}: " . $e->getMessage(), $log_file);
                continue; // Skip this order and continue with the next one
            }
        }
        
        $this->log("[$timestamp] Finished processing orders. Total data rows: " . count($data), $log_file);
        
        return $data;
    }
    
    /**
     * Extract customers data
     */
    private function extract_customers_data($date_param = null) {
        $args = array(
            'role' => 'customer',
            'number' => -1
        );
        
        $users = get_users($args);
        $data = array();
        
        foreach ($users as $user) {
            $customer_data = array(
                'customer_id' => $user->ID,
                'first_name' => get_user_meta($user->ID, 'first_name', true),
                'last_name' => get_user_meta($user->ID, 'last_name', true),
                'user_login' => $user->user_login,
                'email' => $user->user_email,
                'user_pass' => $user->user_pass,
                'date_registered' => $user->user_registered,
                'billing_first_name' => get_user_meta($user->ID, 'billing_first_name', true),
                'billing_last_name' => get_user_meta($user->ID, 'billing_last_name', true),
                'billing_full_name' => get_user_meta($user->ID, 'billing_first_name', true) . ' ' . get_user_meta($user->ID, 'billing_last_name', true),
                'billing_company' => get_user_meta($user->ID, 'billing_company', true),
                'billing_email' => get_user_meta($user->ID, 'billing_email', true),
                'billing_phone' => get_user_meta($user->ID, 'billing_phone', true),
                'billing_address_1' => get_user_meta($user->ID, 'billing_address_1', true),
                'billing_address_2' => get_user_meta($user->ID, 'billing_address_2', true),
                'billing_postcode' => get_user_meta($user->ID, 'billing_postcode', true),
                'billing_city' => get_user_meta($user->ID, 'billing_city', true),
                'billing_state' => get_user_meta($user->ID, 'billing_state', true),
                'billing_state_code' => get_user_meta($user->ID, 'billing_state', true),
                'billing_country' => get_user_meta($user->ID, 'billing_country', true),
                'shipping_first_name' => get_user_meta($user->ID, 'shipping_first_name', true),
                'shipping_last_name' => get_user_meta($user->ID, 'shipping_last_name', true),
                'shipping_full_name' => get_user_meta($user->ID, 'shipping_first_name', true) . ' ' . get_user_meta($user->ID, 'shipping_last_name', true),
                'shipping_company' => get_user_meta($user->ID, 'shipping_company', true),
                'shipping_address_1' => get_user_meta($user->ID, 'shipping_address_1', true),
                'shipping_address_2' => get_user_meta($user->ID, 'shipping_address_2', true),
                'shipping_postcode' => get_user_meta($user->ID, 'shipping_postcode', true),
                'shipping_city' => get_user_meta($user->ID, 'shipping_city', true),
                'shipping_state' => get_user_meta($user->ID, 'shipping_state', true),
                'shipping_state_code' => get_user_meta($user->ID, 'shipping_state', true),
                'shipping_country' => get_user_meta($user->ID, 'shipping_country', true),
                'total_spent' => $this->get_customer_total_spent($user->ID),
                'order_count' => $this->get_customer_order_count($user->ID),
                'customer_meta' => $this->get_customer_meta($user->ID)
            );
            
            $data[] = $customer_data;
        }
        
        return $data;
    }
    
    /**
     * Extract products data
     */
    private function extract_products_data($date_param = null) {
        $args = array(
            'limit' => -1,
            'status' => 'publish',
            'return' => 'objects'
        );
        
        $products = wc_get_products($args);
        $data = array();
        
        foreach ($products as $product) {
            $product_data = array(
                'product_id' => $product->get_id(),
                'product_name' => $product->get_name(),
                'product_sku' => $product->get_sku(),
                'product_type' => $product->get_type(),
                'product_status' => $product->get_status(),
                'product_price' => $product->get_price(),
                'product_regular_price' => $product->get_regular_price(),
                'product_sale_price' => $product->get_sale_price(),
                'product_description' => $product->get_description(),
                'product_short_description' => $product->get_short_description(),
                'product_categories' => $this->get_product_categories($product),
                'product_tags' => $this->get_product_tags($product),
                'product_stock_quantity' => $product->get_stock_quantity(),
                'product_stock_status' => $product->get_stock_status(),
                'product_weight' => $product->get_weight(),
                'product_dimensions' => $this->get_product_dimensions($product),
                'product_meta' => $this->get_product_meta($product)
            );
            
            $data[] = $product_data;
        }
        
        return $data;
    }
    
    /**
     * Extract coupons data
     */
    private function extract_coupons_data($date_param = null) {
        $args = array(
            'limit' => -1,
            'status' => 'publish',
            'return' => 'objects'
        );
        
        // Use get_posts for coupons since wc_get_coupons might not exist
        $coupon_posts = get_posts(array(
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $data = array();
        
        foreach ($coupon_posts as $coupon_post) {
            $coupon = new \WC_Coupon($coupon_post->ID);
            $coupon_data = array(
                'coupon_id' => $coupon->get_id(),
                'coupon_code' => $coupon->get_code(),
                'coupon_type' => $coupon->get_discount_type(),
                'coupon_amount' => $coupon->get_amount(),
                'coupon_description' => $coupon->get_description(),
                'coupon_date_expires' => $coupon->get_date_expires() ? $coupon->get_date_expires()->format('Y-m-d H:i:s') : '',
                'coupon_usage_count' => $coupon->get_usage_count(),
                'coupon_individual_use' => $coupon->get_individual_use() ? 'yes' : 'no',
                'coupon_product_ids' => implode(',', $coupon->get_product_ids()),
                'coupon_excluded_product_ids' => implode(',', $coupon->get_excluded_product_ids()),
                'coupon_product_categories' => implode(',', $coupon->get_product_categories()),
                'coupon_excluded_product_categories' => implode(',', $coupon->get_excluded_product_categories()),
                'coupon_usage_limit' => $coupon->get_usage_limit(),
                'coupon_usage_limit_per_user' => $coupon->get_usage_limit_per_user(),
                'coupon_limit_usage_to_x_items' => $coupon->get_limit_usage_to_x_items(),
                'coupon_free_shipping' => $coupon->get_free_shipping() ? 'yes' : 'no',
                'coupon_meta' => $this->get_coupon_meta($coupon)
            );
            
            $data[] = $coupon_data;
        }
        
        return $data;
    }
    
    /**
     * Create CSV file from data
     */
    private function create_csv_file($data, $field_mappings, $export_type, $date_param = null) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] create_csv_file called with " . count($data) . " records", $log_file);
        
        if (empty($data)) {
            $this->log("[$timestamp] No data provided to create_csv_file", $log_file);
            return false;
        }
        
        // Generate filename with date if provided
        if ($date_param) {
            $date_obj = \DateTime::createFromFormat('Y-m-d', $date_param, new \DateTimeZone('Europe/London'));
            if ($date_obj) {
                $day = $date_obj->format('d');
                $month = $date_obj->format('m');
                $year = $date_obj->format('Y');
            } else {
                $day = date('d');
                $month = date('m');
                $year = date('Y');
            }
        } else {
            $day = date('d');
            $month = date('m');
            $year = date('Y');
        }
        
        $filename = "FO-{$export_type['name']}-{$day}-{$month}-{$year}.csv";
        
        // Create uploads directory
        $upload_dir = wp_upload_dir();
        $local_folder = $export_type['local_uploads_folder'] ?: sanitize_title($export_type['name']);
        $folder_path = $upload_dir['basedir'] . '/wc-s3-exports/' . $local_folder;
        
        $this->log("[$timestamp] Creating folder: $folder_path", $log_file);
        
        if (!file_exists($folder_path)) {
            $this->log("[$timestamp] Folder doesn't exist, creating it", $log_file);
            wp_mkdir_p($folder_path);
        } else {
            $this->log("[$timestamp] Folder already exists", $log_file);
        }
        
        $file_path = $folder_path . '/' . $filename;
        
        // Check if file already exists - but allow overwriting for manual exports
        if (file_exists($file_path)) {
            $this->log("[$timestamp] CSV file already exists: {$filename}. Will overwrite.", $log_file);
        }
        
        // Create CSV file
        $file_handle = fopen($file_path, 'w');
        
        if (!$file_handle) {
            $this->log("[$timestamp] Failed to open file for writing: $file_path", $log_file);
            return false;
        }
        
        // Convert field mappings from indexed array format to associative array format
        $converted_field_mappings = array();
        $headers = array();
        
        // Handle both indexed array format (from form) and associative array format (from settings)
        if (is_array($field_mappings)) {
            foreach ($field_mappings as $key => $field_mapping) {
                if (is_array($field_mapping)) {
                    // Indexed array format
                    if (isset($field_mapping['enabled']) && $field_mapping['enabled'] && 
                        isset($field_mapping['data_source']) && isset($field_mapping['column_name'])) {
                        $converted_field_mappings[$field_mapping['data_source']] = $field_mapping['column_name'];
                        $headers[] = $field_mapping['column_name'];
                    }
                } else {
                    // Associative array format (direct mapping) - key is data_source, value is column_name
                    $converted_field_mappings[$key] = $field_mapping;
                    $headers[] = $field_mapping;
                }
            }
        }
        
        $this->log("[$timestamp] Field mappings processed. Converted mappings: " . count($converted_field_mappings) . ", Headers: " . count($headers), $log_file);
        
        if (empty($converted_field_mappings)) {
            $this->log("[$timestamp] No valid field mappings found for CSV generation", $log_file);
            $this->log("[$timestamp] Original field mappings: " . print_r($field_mappings, true), $log_file);
            fclose($file_handle);
            return false;
        }
        
        $this->log("[$timestamp] Converted field mappings: " . print_r($converted_field_mappings, true), $log_file);
        
        // Check if Source Website should be included (from Source Website Configuration)
        $source_website_value = $this->get_source_website();
        $include_source_website = false;
        
        if ($source_website_value !== null) {
            // Check if it's configured for this export type
            if (isset($export_type['name'])) {
                $export_type_name = $export_type['name'];
                $configured_value = $this->settings->get_source_website_for_export_type($export_type_name);
                if ($configured_value !== null) {
                    $include_source_website = true;
                    $this->log("[$timestamp] Source Website enabled for export type: $export_type_name with value: $configured_value", $log_file);
                }
            }
        }
        
        // Add source_website to field mappings if configured but not already present
        if ($include_source_website && !isset($converted_field_mappings['source_website'])) {
            // Check if "Source Website" column name already exists (from incorrect field mapping)
            $source_website_column_exists = in_array('Source Website', $converted_field_mappings);
            
            if (!$source_website_column_exists) {
                // Add to the END of both arrays to keep them aligned
                $converted_field_mappings['source_website'] = 'Source Website';
                $headers[] = 'Source Website';
                $this->log("[$timestamp] Source Website field automatically added to CSV at the end", $log_file);
            } else {
                $this->log("[$timestamp] Source Website column already exists in field mappings, skipping auto-add", $log_file);
                // Replace the incorrect mapping with the correct source_website mapping
                // Find which key maps to "Source Website" and replace it
                foreach ($converted_field_mappings as $key => $value) {
                    if ($value === 'Source Website' && $key !== 'source_website') {
                        $this->log("[$timestamp] Found incorrect mapping: $key => Source Website, will be replaced", $log_file);
                        unset($converted_field_mappings[$key]);
                        // Remove from headers too
                        $header_index = array_search('Source Website', $headers);
                        if ($header_index !== false) {
                            unset($headers[$header_index]);
                            $headers = array_values($headers); // Re-index array
                        }
                        break;
                    }
                }
                // Now add the correct mapping at the END
                $converted_field_mappings['source_website'] = 'Source Website';
                $headers[] = 'Source Website';
                $this->log("[$timestamp] Corrected Source Website field mapping at the end", $log_file);
            }
        }
        
        // Write headers
        fputcsv($file_handle, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            $csv_row = array();
            foreach (array_keys($converted_field_mappings) as $field_key) {
                $csv_row[] = isset($row[$field_key]) ? $row[$field_key] : '';
            }
            fputcsv($file_handle, $csv_row);
        }
        
        fclose($file_handle);
        
        $this->log("[$timestamp] CSV file created successfully: $file_path", $log_file);
        
        return array(
            'file_name' => $filename,
            'file_path' => $file_path
        );
    }
    
    /**
     * Helper methods for data extraction
     */
    private function get_order_meta($order) {
        $meta = array();
        foreach ($order->get_meta_data() as $meta_item) {
            $meta[] = $meta_item->key . ': ' . $meta_item->value;
        }
        return implode('; ', $meta);
    }
    
    private function get_variation_attributes($item) {
        $attributes = array();
        foreach ($item->get_meta_data() as $meta_item) {
            if (strpos($meta_item->key, 'pa_') === 0) {
                $attributes[] = $meta_item->key . ': ' . $meta_item->value;
            }
        }
        return implode('; ', $attributes);
    }
    
    private function get_item_meta($item) {
        $meta = array();
        foreach ($item->get_meta_data() as $meta_item) {
            $meta[] = $meta_item->key . ': ' . $meta_item->value;
        }
        return implode('; ', $meta);
    }
    
    private function get_shipping_tax_total($order) {
        return method_exists($order, 'get_shipping_tax_total') ? $order->get_shipping_tax_total() : 0;
    }

    private function get_fee_tax_total($order) {
        return method_exists($order, 'get_total_fee_tax') ? $order->get_total_fee_tax() : 0;
    }

    private function get_line_items($order) {
        $line_items = array();
        foreach ($order->get_items() as $item_id => $item) {
            $line_item_data = array(
                'item_id' => $item_id,
                'item_product_id' => method_exists($item, 'get_product_id') ? $item->get_product_id() : '',
                'item_name' => $item->get_name(),
                'item_sku' => method_exists($item, 'get_sku') ? $item->get_sku() : '',
                'item_quantity' => $item->get_quantity(),
                'item_subtotal' => method_exists($item, 'get_subtotal') ? $item->get_subtotal() : '',
                'item_subtotal_tax' => method_exists($item, 'get_subtotal_tax') ? $item->get_subtotal_tax() : '',
                'item_total' => method_exists($item, 'get_total') ? $item->get_total() : '',
                'item_total_tax' => method_exists($item, 'get_total_tax') ? $item->get_total_tax() : '',
                'item_meta' => $this->get_item_meta($item),
                'item_price' => method_exists($item, 'get_price') ? $item->get_price() : ''
            );
            $line_items[] = $line_item_data;
        }
        return $line_items;
    }

    private function get_shipping_items($order) {
        $shipping_items = array();
        foreach ($order->get_items('shipping') as $item_id => $item) {
            $shipping_item_data = array(
                'item_id' => $item_id,
                'item_name' => $item->get_name(),
                'item_total' => method_exists($item, 'get_total') ? $item->get_total() : '',
                'item_total_tax' => method_exists($item, 'get_total_tax') ? $item->get_total_tax() : '',
                'item_meta' => $this->get_item_meta($item)
            );
            $shipping_items[] = $shipping_item_data;
        }
        return $shipping_items;
    }

    private function get_fee_items($order) {
        $fee_items = array();
        foreach ($order->get_items('fee') as $item_id => $item) {
            $fee_item_data = array(
                'item_id' => $item_id,
                'item_name' => $item->get_name(),
                'item_total' => method_exists($item, 'get_total') ? $item->get_total() : '',
                'item_total_tax' => method_exists($item, 'get_total_tax') ? $item->get_total_tax() : '',
                'item_meta' => $this->get_item_meta($item)
            );
            $fee_items[] = $fee_item_data;
        }
        return $fee_items;
    }

    private function get_tax_items($order) {
        $tax_items = array();
        foreach ($order->get_items('tax') as $item_id => $item) {
            $tax_item_data = array(
                'item_id' => $item_id,
                'item_name' => $item->get_name(),
                'item_total' => method_exists($item, 'get_total') ? $item->get_total() : '',
                'item_total_tax' => method_exists($item, 'get_total_tax') ? $item->get_total_tax() : '',
                'item_meta' => $this->get_item_meta($item)
            );
            $tax_items[] = $tax_item_data;
        }
        return $tax_items;
    }

    private function get_coupon_items($order) {
        $coupon_items = array();
        foreach ($order->get_items('coupon') as $item_id => $item) {
            $coupon_item_data = array(
                'item_id' => $item_id,
                'item_name' => $item->get_name(),
                'item_total' => method_exists($item, 'get_total') ? $item->get_total() : '',
                'item_total_tax' => method_exists($item, 'get_total_tax') ? $item->get_total_tax() : '',
                'item_meta' => $this->get_item_meta($item)
            );
            $coupon_items[] = $coupon_item_data;
        }
        return $coupon_items;
    }

    private function get_refunds($order) {
        $refunds = array();
        foreach ($order->get_refunds() as $refund) {
            $refund_data = array(
                'refund_id' => $refund->get_id(),
                'refund_reason' => method_exists($refund, 'get_reason') ? $refund->get_reason() : '',
                'refund_amount' => $refund->get_amount(),
                'refund_date' => $refund->get_date_created()->format('Y-m-d H:i:s'),
                // 'refund_meta' => $this->get_order_meta($refund) // Removed to prevent infinite loop
            );
            $refunds[] = $refund_data;
        }
        return $refunds;
    }

    private function get_order_notes($order) {
        $notes = array();
        
        // Try to get customer notes safely
        try {
            if (method_exists($order, 'get_customer_order_notes')) {
                $customer_notes = $order->get_customer_order_notes();
            } else {
                // Fallback method - just return empty for now to avoid hanging
                return $notes;
            }
            
            if (is_array($customer_notes)) {
                foreach ($customer_notes as $note) {
                    $notes[] = array(
                        'note_id' => isset($note->comment_ID) ? $note->comment_ID : '',
                        'note_author' => isset($note->comment_author) ? $note->comment_author : '',
                        'note_date' => isset($note->comment_date) ? $note->comment_date : '',
                        'note_content' => isset($note->comment_content) ? $note->comment_content : ''
                    );
                }
            }
        } catch (\Exception $e) {
            // If there's any error, just return empty notes
            return $notes;
        }
        
        return $notes;
    }

    private function get_download_permissions($order) {
        $permissions = array();
        if (method_exists($order, 'get_download_permissions')) {
            $download_permissions = $order->get_download_permissions();
            if (is_array($download_permissions)) {
                foreach ($download_permissions as $permission) {
                    $permissions[] = array(
                        'permission_id' => isset($permission->id) ? $permission->id : '',
                        'permission_product_id' => isset($permission->product_id) ? $permission->product_id : '',
                        'permission_user_id' => isset($permission->user_id) ? $permission->user_id : '',
                        'permission_downloads_remaining' => isset($permission->downloads_remaining) ? $permission->downloads_remaining : '',
                        'permission_access_expires' => isset($permission->access_expires) && $permission->access_expires ? $permission->access_expires->format('Y-m-d H:i:s') : '',
                        'permission_meta' => $this->get_order_meta($permission)
                    );
                }
            }
        }
        return $permissions;
    }

    private function get_customer_total_spent($user_id) {
        return function_exists('wc_get_customer_total_spent') ? wc_get_customer_total_spent($user_id) : 0;
    }

    private function get_customer_order_count($user_id) {
        return function_exists('wc_get_customer_order_count') ? wc_get_customer_order_count($user_id) : 0;
    }
    
    private function get_customer_last_order_date($user_id) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        return !empty($orders) ? $orders[0]->get_date_created()->format('Y-m-d H:i:s') : '';
    }
    
    private function get_customer_meta($user_id) {
        $meta = array();
        $user_meta = get_user_meta($user_id);
        foreach ($user_meta as $key => $values) {
            if (!in_array($key, array('first_name', 'last_name', 'billing_', 'shipping_'))) {
                $meta[] = $key . ': ' . implode(', ', $values);
            }
        }
        return implode('; ', $meta);
    }
    
    private function get_product_categories($product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if ($categories && !is_wp_error($categories)) {
            return implode(', ', wp_list_pluck($categories, 'name'));
        }
        return '';
    }
    
    private function get_product_tags($product) {
        $tags = get_the_terms($product->get_id(), 'product_tag');
        if ($tags && !is_wp_error($tags)) {
            return implode(', ', wp_list_pluck($tags, 'name'));
        }
        return '';
    }
    
    private function get_product_dimensions($product) {
        $dimensions = $product->get_dimensions();
        if (!empty($dimensions)) {
            return $dimensions['length'] . 'x' . $dimensions['width'] . 'x' . $dimensions['height'];
        }
        return '';
    }
    
    private function get_product_meta($product) {
        $meta = array();
        foreach ($product->get_meta_data() as $meta_item) {
            $meta[] = $meta_item->key . ': ' . $meta_item->value;
        }
        return implode('; ', $meta);
    }
    
    private function get_coupon_meta($coupon) {
        $meta = array();
        foreach ($coupon->get_meta_data() as $meta_item) {
            $meta[] = $meta_item->key . ': ' . $meta_item->value;
        }
        return implode('; ', $meta);
    }
    
    /**
     * Format line items for CSV output
     */
    private function format_line_items($line_items) {
        if (empty($line_items)) {
            return '';
        }
        
        $formatted_items = array();
        foreach ($line_items as $item) {
            $formatted_parts = array();
            
            // Format in the same style as reference CSV
            if (!empty($item['item_id'])) $formatted_parts[] = 'id:' . $item['item_id'];
            if (!empty($item['item_name'])) $formatted_parts[] = 'name:' . $item['item_name'];
            if (!empty($item['item_product_id'])) $formatted_parts[] = 'product_id:' . $item['item_product_id'];
            if (!empty($item['item_sku'])) $formatted_parts[] = 'sku:' . $item['item_sku'];
            if (isset($item['item_quantity'])) $formatted_parts[] = 'quantity:' . $item['item_quantity'];
            if (isset($item['item_subtotal'])) $formatted_parts[] = 'subtotal:' . $item['item_subtotal'];
            if (isset($item['item_subtotal_tax'])) $formatted_parts[] = 'subtotal_tax:' . $item['item_subtotal_tax'];
            if (isset($item['item_total'])) $formatted_parts[] = 'total:' . $item['item_total'];
            if (isset($item['item_total_tax'])) $formatted_parts[] = 'total_tax:' . $item['item_total_tax'];
            if (isset($item['item_refunded'])) $formatted_parts[] = 'refunded:' . $item['item_refunded'];
            if (isset($item['item_refunded_qty'])) $formatted_parts[] = 'refunded_qty:' . $item['item_refunded_qty'];
            if (!empty($item['item_meta'])) $formatted_parts[] = 'meta:' . str_replace(array('|', ','), array(' ', ' '), $item['item_meta']);
            
            if (!empty($formatted_parts)) {
                $formatted_items[] = implode('|', $formatted_parts);
            }
        }
        return implode(',', $formatted_items);
    }
    
    /**
     * Format shipping items for CSV output
     */
    private function format_shipping_items($shipping_items) {
        if (empty($shipping_items)) {
            return '';
        }
        
        $formatted_items = array();
        foreach ($shipping_items as $item) {
            $formatted_item = array();
            foreach ($item as $key => $value) {
                if (!empty($value) || $value === '0' || $value === 0) {
                    $formatted_item[] = $key . ':' . $value;
                }
            }
            if (!empty($formatted_item)) {
                $formatted_items[] = implode('|', $formatted_item);
            }
        }
        return implode(',', $formatted_items);
    }
    
    /**
     * Format fee items for CSV output
     */
    private function format_fee_items($fee_items) {
        if (empty($fee_items)) {
            return '';
        }
        
        $formatted_items = array();
        foreach ($fee_items as $item) {
            $formatted_item = array();
            foreach ($item as $key => $value) {
                if (!empty($value) || $value === '0' || $value === 0) {
                    $formatted_item[] = $key . ':' . $value;
                }
            }
            if (!empty($formatted_item)) {
                $formatted_items[] = implode('|', $formatted_item);
            }
        }
        return implode(',', $formatted_items);
    }
    
    /**
     * Format tax items for CSV output
     */
    private function format_tax_items($tax_items) {
        if (empty($tax_items)) {
            return '';
        }
        
        $formatted_items = array();
        foreach ($tax_items as $item) {
            $formatted_item = array();
            foreach ($item as $key => $value) {
                if (!empty($value) || $value === '0' || $value === 0) {
                    $formatted_item[] = $key . ':' . $value;
                }
            }
            if (!empty($formatted_item)) {
                $formatted_items[] = implode('|', $formatted_item);
            }
        }
        return implode(',', $formatted_items);
    }
    
    /**
     * Format coupon items for CSV output
     */
    private function format_coupon_items($coupon_items) {
        if (empty($coupon_items)) {
            return '';
        }
        
        $formatted_items = array();
        foreach ($coupon_items as $item) {
            $formatted_item = array();
            foreach ($item as $key => $value) {
                if (!empty($value) || $value === '0' || $value === 0) {
                    $formatted_item[] = $key . ':' . $value;
                }
            }
            if (!empty($formatted_item)) {
                $formatted_items[] = implode('|', $formatted_item);
            }
        }
        return implode(',', $formatted_items);
    }
    
    /**
     * Format refunds for CSV output
     */
    private function format_refunds($refunds) {
        if (empty($refunds)) {
            return '';
        }
        
        $formatted_items = array();
        foreach ($refunds as $refund) {
            $formatted_item = array();
            foreach ($refund as $key => $value) {
                if (!empty($value) || $value === '0' || $value === 0) {
                    $formatted_item[] = $key . ':' . $value;
                }
            }
            if (!empty($formatted_item)) {
                $formatted_items[] = implode('|', $formatted_item);
            }
        }
        return implode(',', $formatted_items);
    }
    
    /**
     * Format order notes for CSV output
     */
    private function format_order_notes($order_notes) {
        if (empty($order_notes)) {
            return '';
        }
        
        $formatted_notes = array();
        foreach ($order_notes as $note) {
            $formatted_note = array();
            foreach ($note as $key => $value) {
                if (!empty($value)) {
                    $formatted_note[] = $key . ':' . $value;
                }
            }
            if (!empty($formatted_note)) {
                $formatted_notes[] = implode('|', $formatted_note);
            }
        }
        return implode(',', $formatted_notes);
    }
    
    /**
     * Format download permissions for CSV output
     */
    private function format_download_permissions($download_permissions) {
        if (empty($download_permissions)) {
            return '';
        }
        
        $formatted_permissions = array();
        foreach ($download_permissions as $permission) {
            $formatted_permission = array();
            foreach ($permission as $key => $value) {
                if (!empty($value) || $value === '0' || $value === 0) {
                    $formatted_permission[] = $key . ':' . $value;
                }
            }
            if (!empty($formatted_permission)) {
                $formatted_permissions[] = implode('|', $formatted_permission);
            }
        }
        return implode(',', $formatted_permissions);
    }
    
    /**
     * Get log file path
     */
    private function get_log_file() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/wc-s3-exports/logs/';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        return $log_dir . 'csv-generator-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Get source website URL for Salesforce integration
     */
    private function get_source_website() {
        // First, check if there's a configuration for the current export type
        if ($this->current_export_type && isset($this->current_export_type['name'])) {
            $export_type_name = $this->current_export_type['name'];
            $configured_value = $this->settings->get_source_website_for_export_type($export_type_name);
            
            // If configured and enabled, use the configured value
            if ($configured_value !== null) {
                return $configured_value;
            }
        }
        
        // Fall back to site URL logic
        // Get the site URL from WordPress
        $site_url = get_site_url();
        
        // If site_url is not available, try to get it from wp-config constants
        if (empty($site_url) || $site_url === 'http://' || $site_url === 'https://') {
            if (defined('WP_HOME')) {
                $site_url = WP_HOME;
            } elseif (defined('WP_SITEURL')) {
                $site_url = WP_SITEURL;
            } else {
                // Fallback to current domain
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                $site_url = $protocol . $host;
            }
        }
        
        // Ensure we have a proper URL
        if (empty($site_url) || $site_url === 'http://' || $site_url === 'https://') {
            $site_url = 'https://fundsonline.co.uk'; // Default fallback for DSC Funds Online
        }
        
        return $site_url;
    }
    
    /**
     * Log message
     */
    private function log($message, $log_file = null) {
        if (!$log_file) {
            $log_file = $this->get_log_file();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
