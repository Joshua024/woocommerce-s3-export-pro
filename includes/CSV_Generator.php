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
     * Constructor
     */
    public function __construct() {
        $this->settings = new Settings();
    }
    
    /**
     * Generate CSV file for export type
     */
    public function generate_csv($export_type, $date_param = null) {
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
        $this->log("[$timestamp] Generating CSV for export type: {$export_type['name']}", $log_file);
        
        // Get field mappings
        $field_mappings = $export_type['field_mappings'] ?? [];
        
        if (empty($field_mappings)) {
            $this->log("[$timestamp] No field mappings found for export type: {$export_type['name']}", $log_file);
            return false;
        }
        
        // Extract data based on export type
        $data = $this->extract_data($export_type['type'], $date_param);
        
        if (empty($data)) {
            $this->log("[$timestamp] No data found for export type: {$export_type['name']}", $log_file);
            return false;
        }
        
        // Generate CSV file
        $file_data = $this->create_csv_file($data, $field_mappings, $export_type, $date_param);
        
        if ($file_data) {
            $this->log("[$timestamp] CSV file generated successfully for: {$export_type['name']}", $log_file);
        } else {
            $this->log("[$timestamp] Failed to generate CSV file for: {$export_type['name']}", $log_file);
        }
        
        return $file_data;
    }
    
    /**
     * Extract data from WooCommerce based on export type
     */
    private function extract_data($export_type, $date_param = null) {
        switch ($export_type) {
            case 'orders':
                return $this->extract_orders_data($date_param);
            case 'customers':
                return $this->extract_customers_data($date_param);
            case 'products':
                return $this->extract_products_data($date_param);
            case 'coupons':
                return $this->extract_coupons_data($date_param);
            default:
                return array();
        }
    }
    
    /**
     * Extract orders data
     */
    private function extract_orders_data($date_param = null) {
        $args = array(
            'limit' => -1,
            'status' => array('wc-completed', 'wc-processing', 'wc-on-hold'),
            'return' => 'objects'
        );
        
        if ($date_param) {
            $args['date_created'] = $date_param;
        }
        
        $orders = wc_get_orders($args);
        $data = array();
        
        foreach ($orders as $order) {
            // Get order-level data
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
                'shipping_method' => $order->get_shipping_method(),
                'customer_id' => $order->get_customer_id(),
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
                'line_items' => $this->get_line_items($order),
                'shipping_items' => $this->get_shipping_items($order),
                'fee_items' => $this->get_fee_items($order),
                'tax_items' => $this->get_tax_items($order),
                'coupon_items' => $this->get_coupon_items($order),
                'refunds' => $this->get_refunds($order),
                'order_notes' => $this->get_order_notes($order),
                'download_permissions' => $this->get_download_permissions($order),
                'order_meta' => $this->get_order_meta($order)
            );
            
            // Check if we have items in this order
            $items = $order->get_items();
            
            if (!empty($items)) {
                // Create a row for each item, including order-level data
                foreach ($items as $item_id => $item) {
                    $item_data = $order_data; // Start with order-level data
                    
                    // Add item-level data
                    $product_id = 0;
                    $variation_id = 0;
                    
                    if (method_exists($item, 'get_product_id')) {
                        $product_id = $item->get_product_id();
                    }
                    
                    if (method_exists($item, 'get_variation_id')) {
                        $variation_id = $item->get_variation_id();
                    }
                    
                    $product = $product_id ? wc_get_product($product_id) : null;
                    
                    $item_data['item_id'] = $item_id;
                    $item_data['item_product_id'] = $product_id;
                    $item_data['item_name'] = $item->get_name();
                    $item_data['item_sku'] = $product ? $product->get_sku() : '';
                    $item_data['item_quantity'] = $item->get_quantity();
                    $item_data['item_subtotal'] = method_exists($item, 'get_subtotal') ? $item->get_subtotal() : 0;
                    $item_data['item_subtotal_tax'] = method_exists($item, 'get_subtotal_tax') ? $item->get_subtotal_tax() : 0;
                    $item_data['item_total'] = method_exists($item, 'get_total') ? $item->get_total() : 0;
                    $item_data['item_total_tax'] = method_exists($item, 'get_total_tax') ? $item->get_total_tax() : 0;
                    $item_data['item_refunded'] = method_exists($item, 'get_total_refunded') ? $item->get_total_refunded() : 0;
                    $item_data['item_refunded_qty'] = method_exists($item, 'get_qty_refunded') ? $item->get_qty_refunded() : 0;
                    $item_data['item_meta'] = $this->get_item_meta($item);
                    $item_data['item_price'] = method_exists($item, 'get_price') ? $item->get_price() : 0;
                    
                    $data[] = $item_data;
                }
            } else {
                // No items, just add order-level data
                $data[] = $order_data;
            }
        }
        
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
        if (empty($data)) {
            return false;
        }
        
        $log_file = $this->get_log_file();
        $timestamp = date('Y-m-d H:i:s');
        
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
        
        if (!file_exists($folder_path)) {
            wp_mkdir_p($folder_path);
        }
        
        $file_path = $folder_path . '/' . $filename;
        
        // Check if file already exists
        if (file_exists($file_path)) {
            $this->log("[$timestamp] CSV file already exists: {$filename}. Skipping generation.", $log_file);
            return false;
        }
        
        // Create CSV file
        $file_handle = fopen($file_path, 'w');
        
        if (!$file_handle) {
            $this->log("[$timestamp] Failed to open file for writing: $file_path", $log_file);
            return false;
        }
        
        // Write headers
        $headers = array_values($field_mappings);
        fputcsv($file_handle, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            $csv_row = array();
            foreach (array_keys($field_mappings) as $field_key) {
                $csv_row[] = $row[$field_key] ?? '';
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
                'item_refunded' => method_exists($item, 'get_total_refunded') ? $item->get_total_refunded() : '',
                'item_refunded_qty' => method_exists($item, 'get_qty_refunded') ? $item->get_qty_refunded() : '',
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
                'refund_meta' => $this->get_order_meta($refund)
            );
            $refunds[] = $refund_data;
        }
        return $refunds;
    }

    private function get_order_notes($order) {
        $notes = array();
        $customer_notes = $order->get_customer_notes();
        if (is_array($customer_notes)) {
            foreach ($customer_notes as $note) {
                $notes[] = array(
                    'note_id' => isset($note->id) ? $note->id : '',
                    'note_author' => isset($note->author) ? $note->author : '',
                    'note_date' => isset($note->date_created) ? $note->date_created->format('Y-m-d H:i:s') : '',
                    'note_content' => isset($note->note) ? $note->note : ''
                );
            }
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
