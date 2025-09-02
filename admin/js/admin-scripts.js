/**
 * Scripts de administración para WP Segment Integration
 */

(function($) {
    'use strict';

    var WPSegmentAdmin = {
        
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Probar conexión
            $('#test-connection').on('click', this.testConnection);
            
            // Exportar configuraciones
            $('#export-settings').on('click', this.exportSettings);
            
            // Importar configuraciones
            $('#import-settings').on('click', this.importSettings);
            
            // Validación de formularios
            $('form').on('submit', this.validateForm);
            
            // Mostrar/ocultar secciones dependientes
            this.handleDependentFields();
        },

        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#connection-result');
            
            // Deshabilitar botón y mostrar estado de carga
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
                    $button.prop('disabled', false).text('Probar Conexión');
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
                        
                        WPSegmentAdmin.showNotice('Configuraciones exportadas exitosamente', 'success');
                    } else {
                        WPSegmentAdmin.showNotice('Error al exportar: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    WPSegmentAdmin.showNotice('Error de conexión: ' + error, 'error');
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
                WPSegmentAdmin.showNotice('Por favor, pega las configuraciones JSON', 'error');
                return;
            }
            
            // Validar JSON
            try {
                JSON.parse(settingsData);
            } catch (e) {
                WPSegmentAdmin.showNotice('JSON inválido: ' + e.message, 'error');
                return;
            }
            
            if (!confirm('¿Estás seguro de que quieres importar estas configuraciones? Esto sobrescribirá las configuraciones actuales.')) {
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
                        // Recargar página después de 2 segundos
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        WPSegmentAdmin.showNotice('Error al importar: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    WPSegmentAdmin.showNotice('Error de conexión: ' + error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },

        validateForm: function(e) {
            var $form = $(this);
            var isValid = true;
            
            // Validar Write Key si está presente
            var $writeKey = $form.find('input[name*="write_key"]');
            if ($writeKey.length && $writeKey.val().trim() === '') {
                WPSegmentAdmin.showNotice('El Write Key es requerido', 'error');
                $writeKey.focus();
                isValid = false;
            }
            
            // Validar formato de Write Key
            if ($writeKey.length && $writeKey.val().trim() !== '') {
                var writeKeyPattern = /^[a-zA-Z0-9]{32,}$/;
                if (!writeKeyPattern.test($writeKey.val().trim())) {
                    WPSegmentAdmin.showNotice('El formato del Write Key no es válido', 'error');
                    $writeKey.focus();
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        },

        handleDependentFields: function() {
            // Mostrar/ocultar campos de WooCommerce
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
            toggleWooFields(); // Ejecutar al cargar
            
            // Mostrar/ocultar campos de privacidad
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
            toggleCookieFields(); // Ejecutar al cargar
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto-dismiss después de 5 segundos
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll hacia arriba para mostrar el notice
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        },

        // Función para validar configuraciones en tiempo real
        validateSettings: function() {
            var errors = [];
            
            // Validar Write Key
            var writeKey = $('input[name*="write_key"]').val();
            if (writeKey && !/^[a-zA-Z0-9]{32,}$/.test(writeKey)) {
                errors.push('Write Key format is invalid');
            }
            
            return errors;
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        WPSegmentAdmin.init();
    });

    // Hacer disponible globalmente
    window.WPSegmentAdmin = WPSegmentAdmin;

})(jQuery);

