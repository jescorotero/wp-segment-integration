/**
 * Admin Scripts for WP Segment Integration
 */

(function($) {
    'use strict';

    var WPSegmentAdmin = {
        
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Test Connection
            $('#test-connection').on('click', this.testConnection);
            
            // Export Settings
            $('#export-settings').on('click', this.exportSettings);

            // Import Settings
            $('#import-settings').on('click', this.importSettings);

            // Form Validation
            $('form').on('submit', this.validateForm);
            
            // Show/Hide Dependent Sections
            this.handleDependentFields();
        },

        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#connection-result');
            
            // Disable button and show loading state
            $button.prop('disabled', true).text(wpSegmentAdmin.strings.testing);
            $result.removeClass('success error').hide();
            
            $.ajax({
                url: wpSegmentAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_segment_test_connection',
                    nonce: wpSegmentAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').text(response.data).show();
                    } else {
                        $result.addClass('error').text(response.data).show();
                    }
                },
                error: function(xhr, status, error) {
                    $result.addClass('error').text(wpSegmentAdmin.strings.error + ': ' + error).show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },

        exportSettings: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true);
            
            $.ajax({
                url: wpSegmentAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_segment_export_settings',
                    nonce: wpSegmentAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Crear y descargar archivo
                        var blob = new Blob([response.data.settings], {type: 'application/json'});
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'wp-segment-settings-' + new Date().toISOString().slice(0,10) + '.json';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);

                        WPSegmentAdmin.showNotice('Settings exported successfully', 'success');
                    } else {
                        WPSegmentAdmin.showNotice('Error exporting: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    WPSegmentAdmin.showNotice('Connection error: ' + error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        importSettings: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $textarea = $('#import-settings-data');
            var settingsData = $textarea.val().trim();
            
            if (!settingsData) {
                WPSegmentAdmin.showNotice('Please paste the JSON settings', 'error');
                return;
            }

            // Validate JSON
            try {
                JSON.parse(settingsData);
            } catch (e) {
                WPSegmentAdmin.showNotice('Invalid JSON: ' + e.message, 'error');
                return;
            }

            if (!confirm('Are you sure you want to import these settings? This will overwrite the current settings.')) {
                return;
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: wpSegmentAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_segment_import_settings',
                    settings: settingsData,
                    nonce: wpSegmentAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WPSegmentAdmin.showNotice(response.data, 'success');
                        $textarea.val('');
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        WPSegmentAdmin.showNotice('Error importing: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    WPSegmentAdmin.showNotice('Connection error: ' + error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        validateForm: function(e) {
            var $form = $(this);
            var isValid = true;

            // Validate Write Key if present
            var $writeKey = $form.find('input[name*="write_key"]');
            if ($writeKey.length && $writeKey.val().trim() === '') {
                WPSegmentAdmin.showNotice('The Write Key is required', 'error');
                $writeKey.focus();
                isValid = false;
            }

            // Validate Write Key format
            if ($writeKey.length && $writeKey.val().trim() !== '') {
                var writeKeyPattern = /^[a-zA-Z0-9]{32,}$/;
                if (!writeKeyPattern.test($writeKey.val().trim())) {
                    WPSegmentAdmin.showNotice('The Write Key format is invalid', 'error');
                    $writeKey.focus();
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        },

        handleDependentFields: function() {
            // Show/hide WooCommerce fields
            var $wooEnabled = $('input[name*="woocommerce_enabled"]');
            var $wooFields = $('input[name*="track_product"], input[name*="track_cart"], input[name*="track_checkout"], input[name*="track_order"], input[name*="track_coupon"]').closest('tr');
            
            function toggleWooFields() {
                if ($wooEnabled.is(':checked')) {
                    $wooFields.show();
                } else {
                    $wooFields.hide();
                }
            }
            
            $wooEnabled.on('change', toggleWooFields);
            toggleWooFields(); // Execute on load

            // Show/hide privacy fields
            var $cookieConsent = $('input[name*="cookie_consent"]');
            var $cookieFields = $cookieConsent.closest('tr').next('tr');
            
            function toggleCookieFields() {
                if ($cookieConsent.is(':checked')) {
                    $cookieFields.show();
                } else {
                    $cookieFields.hide();
                }
            }
            
            $cookieConsent.on('change', toggleCookieFields);
            toggleCookieFields(); // Execute on load
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);

            // Scroll to top to show the notice
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        },

        // Function to validate settings in real-time
        validateSettings: function() {
            var errors = [];
            
            // Validate Write Key
            var writeKey = $('input[name*="write_key"]').val();
            if (writeKey && !/^[a-zA-Z0-9]{32,}$/.test(writeKey)) {
                errors.push('Write Key format is invalid');
            }
            
            return errors;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WPSegmentAdmin.init();
    });

    // Make globally available
    window.WPSegmentAdmin = WPSegmentAdmin;

})(jQuery);

