/**
 * WooCommerce S3 Export Pro Admin JavaScript
 * 
 * @package WC_S3_Export_Pro
 */

(function($) {
    'use strict';

    // WooCommerce S3 Export Pro Admin Object
    var WCS3ExportPro = {
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.loadInitialData();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Test S3 Connection
            $('#wc-s3-test-connection').on('click', this.testS3Connection);
            
            // Run Manual Export
            $('#wc-s3-run-export').on('click', this.runManualExport);
            
            // Run Date Export
            $('#wc-s3-run-date-export').on('click', this.runDateExport);
            
            // Refresh Log
            $('#wc-s3-refresh-log').on('click', this.refreshLog);
            
            // Auto-refresh status every 30 seconds
            setInterval(this.loadExportStatus, 30000);
        },

        /**
         * Load initial data
         */
        loadInitialData: function() {
            WCS3ExportPro.loadExportStatus();
            WCS3ExportPro.loadLogContent();
        },

        /**
         * Test S3 Connection
         */
        testS3Connection: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var result = $('#wc-s3-test-result');
            
            // Disable button and show loading state
            button.prop('disabled', true).text(wcS3ExportPro.strings.testing);
            result.removeClass('success error warning').hide();
            
            // Make AJAX request
            $.ajax({
                url: wcS3ExportPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_s3_test_s3_connection',
                    nonce: wcS3ExportPro.nonce
                },
                success: function(response) {
                    if (response.success) {
                        result.addClass('success').html(
                            '<strong>✓ ' + wcS3ExportPro.strings.success + '</strong><br>' + 
                            response.data.message
                        );
                    } else {
                        result.addClass('error').html(
                            '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' + 
                            response.data.message
                        );
                    }
                    result.show();
                },
                error: function(xhr, status, error) {
                    result.addClass('error').html(
                        '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' +
                        'Failed to test connection: ' + error
                    );
                    result.show();
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Connection');
                }
            });
        },

        /**
         * Run Manual Export
         */
        runManualExport: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var result = $('#wc-s3-run-export-result');
            
            // Disable button and show loading state
            button.prop('disabled', true).text(wcS3ExportPro.strings.running);
            result.removeClass('success error warning').hide();
            
            // Make AJAX request
            $.ajax({
                url: wcS3ExportPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_s3_run_manual_export',
                    nonce: wcS3ExportPro.nonce
                },
                success: function(response) {
                    if (response.success) {
                        result.addClass('success').html(
                            '<strong>✓ ' + wcS3ExportPro.strings.success + '</strong><br>' + 
                            response.data.message
                        );
                        // Refresh status after successful export
                        WCS3ExportPro.loadExportStatus();
                    } else {
                        result.addClass('error').html(
                            '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' + 
                            response.data.message
                        );
                    }
                    result.show();
                },
                error: function(xhr, status, error) {
                    result.addClass('error').html(
                        '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' +
                        'Failed to run export: ' + error
                    );
                    result.show();
                },
                complete: function() {
                    button.prop('disabled', false).text('Run Export');
                }
            });
        },

        /**
         * Run Date Export
         */
        runDateExport: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var result = $('#wc-s3-run-date-export-result');
            var date = $('#wc-s3-export-date').val();
            
            // Validate date
            if (!date) {
                result.addClass('error').html(
                    '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' +
                    'Please select a date'
                );
                result.show();
                return;
            }
            
            // Validate date format
            var dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!dateRegex.test(date)) {
                result.addClass('error').html(
                    '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' +
                    'Invalid date format. Use YYYY-MM-DD'
                );
                result.show();
                return;
            }
            
            // Disable button and show loading state
            button.prop('disabled', true).text(wcS3ExportPro.strings.running);
            result.removeClass('success error warning').hide();
            
            // Make AJAX request
            $.ajax({
                url: wcS3ExportPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_s3_run_manual_export',
                    nonce: wcS3ExportPro.nonce,
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        result.addClass('success').html(
                            '<strong>✓ ' + wcS3ExportPro.strings.success + '</strong><br>' + 
                            response.data.message
                        );
                        // Refresh status after successful export
                        WCS3ExportPro.loadExportStatus();
                    } else {
                        result.addClass('error').html(
                            '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' + 
                            response.data.message
                        );
                    }
                    result.show();
                },
                error: function(xhr, status, error) {
                    result.addClass('error').html(
                        '<strong>✗ ' + wcS3ExportPro.strings.error + '</strong><br>' +
                        'Failed to run export: ' + error
                    );
                    result.show();
                },
                complete: function() {
                    button.prop('disabled', false).text('Export for Date');
                }
            });
        },

        /**
         * Refresh Log Content
         */
        refreshLog: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var logContent = $('#wc-s3-log-content');
            
            // Show loading state
            button.prop('disabled', true).text('Refreshing...');
            logContent.text('Loading log entries...');
            
            // Make AJAX request
            $.ajax({
                url: wcS3ExportPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_s3_get_log_content',
                    nonce: wcS3ExportPro.nonce
                },
                success: function(response) {
                    if (response.success) {
                        logContent.text(response.data.content);
                    } else {
                        logContent.text('Failed to load log content: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    logContent.text('Failed to load log content: ' + error);
                },
                complete: function() {
                    button.prop('disabled', false).text('Refresh Log');
                }
            });
        },

        /**
         * Load Export Status
         */
        loadExportStatus: function() {
            $.ajax({
                url: wcS3ExportPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_s3_get_export_status',
                    nonce: wcS3ExportPro.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        // Update status values
                        $('#wc-s3-last-export').text(data.last_export || 'Never');
                        
                        $('#wc-s3-s3-status')
                            .text(data.s3_connected ? '✓ Connected' : '❌ Not connected')
                            .removeClass('success error warning')
                            .addClass(data.s3_connected ? 'success' : 'error');
                        
                        $('#wc-s3-automation-status')
                            .text(data.automation_enabled ? '✓ Enabled' : '❌ Disabled')
                            .removeClass('success error warning')
                            .addClass(data.automation_enabled ? 'success' : 'error');
                        
                        $('#wc-s3-pending-jobs').text(data.pending_jobs);
                        
                        // Add warning class for pending jobs
                        if (data.pending_jobs > 0) {
                            $('#wc-s3-pending-jobs').addClass('warning');
                        } else {
                            $('#wc-s3-pending-jobs').removeClass('warning');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load export status:', error);
                }
            });
        },

        /**
         * Load Log Content
         */
        loadLogContent: function() {
            $.ajax({
                url: wcS3ExportPro.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wc_s3_get_log_content',
                    nonce: wcS3ExportPro.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#wc-s3-log-content').text(response.data.content);
                    } else {
                        $('#wc-s3-log-content').text('Failed to load log content: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#wc-s3-log-content').text('Failed to load log content: ' + error);
                }
            });
        },

        /**
         * Show Notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            var notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Add to page
            $('.wrap h1').after(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Make dismissible
            notification.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
            notification.find('.notice-dismiss').on('click', function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Format Date
         */
        formatDate: function(dateString) {
            if (!dateString) return 'Never';
            
            var date = new Date(dateString);
            return date.toLocaleString();
        },

        /**
         * Format File Size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WCS3ExportPro.init();
    });

    // Make WCS3ExportPro available globally
    window.WCS3ExportPro = WCS3ExportPro;

})(jQuery); 