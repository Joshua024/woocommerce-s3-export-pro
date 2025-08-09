<?php
namespace WC_S3_Export_Pro;

/**
 * Export History Class
 * 
 * Handles export history tracking, duplicate prevention, and file validation.
 */
class Export_History {
    
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
     * Check if export already exists for given date and type
     */
    public function export_exists($export_type, $date, $export_name = '') {
        $history = $this->get_export_history();
        $date_key = $date;
        
        foreach ($history as $record) {
            if ($record['export_type'] === $export_type && 
                $record['date'] === $date_key && 
                $record['export_name'] === $export_name) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if file exists locally
     */
    public function file_exists_locally($file_path) {
        return file_exists($file_path);
    }
    
    /**
     * Add export record to history
     */
    public function add_export_record($export_type, $date, $file_name, $file_path, $export_name = '', $status = 'completed') {
        $history = $this->get_export_history();
        
        $record = array(
            'id' => uniqid('export_'),
            'export_type' => $export_type,
            'export_name' => $export_name,
            'date' => $date,
            'file_name' => $file_name,
            'file_path' => $file_path,
            'status' => $status,
            'created_at' => current_time('mysql'),
            'file_size' => file_exists($file_path) ? filesize($file_path) : 0,
            'file_exists' => file_exists($file_path)
        );
        
        $history[] = $record;
        
        // Keep only last 1000 records
        if (count($history) > 1000) {
            $history = array_slice($history, -1000);
        }
        
        update_option('wc_s3_export_history', $history);
        
        return $record['id'];
    }
    
    /**
     * Get export history
     */
    public function get_export_history($limit = 100) {
        $history = get_option('wc_s3_export_history', array());
        
        // Sort by created_at descending
        usort($history, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($history, 0, $limit);
    }
    
    /**
     * Get export history for specific date range
     */
    public function get_export_history_for_date_range($start_date, $end_date) {
        $history = $this->get_export_history(1000);
        $filtered = array();
        
        foreach ($history as $record) {
            if ($record['date'] >= $start_date && $record['date'] <= $end_date) {
                $filtered[] = $record;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get export history for specific export type
     */
    public function get_export_history_for_type($export_type) {
        $history = $this->get_export_history(1000);
        $filtered = array();
        
        foreach ($history as $record) {
            if ($record['export_type'] === $export_type) {
                $filtered[] = $record;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Delete export record
     */
    public function delete_export_record($record_id) {
        $history = $this->get_export_history(1000);
        
        foreach ($history as $key => $record) {
            if ($record['id'] === $record_id) {
                // Delete the file if it exists
                if (file_exists($record['file_path'])) {
                    unlink($record['file_path']);
                }
                
                unset($history[$key]);
                update_option('wc_s3_export_history', array_values($history));
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Clear export history
     */
    public function clear_export_history() {
        delete_option('wc_s3_export_history');
    }
    
    /**
     * Get export statistics
     */
    public function get_export_statistics() {
        $history = $this->get_export_history(1000);
        $stats = array(
            'total_exports' => count($history),
            'successful_exports' => 0,
            'failed_exports' => 0,
            'total_file_size' => 0,
            'exports_by_type' => array(),
            'exports_by_date' => array()
        );
        
        foreach ($history as $record) {
            if ($record['status'] === 'completed') {
                $stats['successful_exports']++;
            } else {
                $stats['failed_exports']++;
            }
            
            $stats['total_file_size'] += $record['file_size'];
            
            // Count by type
            if (!isset($stats['exports_by_type'][$record['export_type']])) {
                $stats['exports_by_type'][$record['export_type']] = 0;
            }
            $stats['exports_by_type'][$record['export_type']]++;
            
            // Count by date
            if (!isset($stats['exports_by_date'][$record['date']])) {
                $stats['exports_by_date'][$record['date']] = 0;
            }
            $stats['exports_by_date'][$record['date']]++;
        }
        
        return $stats;
    }
    
    /**
     * Validate file integrity
     */
    public function validate_file_integrity($file_path) {
        if (!file_exists($file_path)) {
            return array('valid' => false, 'error' => 'File does not exist');
        }
        
        $file_size = filesize($file_path);
        if ($file_size === 0) {
            return array('valid' => false, 'error' => 'File is empty');
        }
        
        // Check if it's a valid CSV file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array('valid' => false, 'error' => 'Cannot open file for reading');
        }
        
        $first_line = fgets($handle);
        fclose($handle);
        
        if (empty($first_line) || strpos($first_line, ',') === false) {
            return array('valid' => false, 'error' => 'File does not appear to be a valid CSV');
        }
        
        return array('valid' => true, 'file_size' => $file_size);
    }
}
