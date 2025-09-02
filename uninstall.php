<?php
/**
 * Archivo de desinstalación para WP Segment Integration - Uninstall file for WP Segment Integration
 *
 * Este archivo se ejecuta cuando el plugin es desinstalado desde WordPress - This file runs when the plugin is uninstalled from WordPress
 */

// Prevenir acceso directo - Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar que realmente se está desinstalando - Check if really uninstalling
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Limpiar datos del plugin al desinstalar - Clean up plugin data on uninstall
 */
function wp_segment_uninstall_cleanup() {
    // Eliminar opciones del plugin - Remove plugin options
    delete_option('wp_segment_settings');

    // Eliminar opciones de red si es multisite - Remove network options if multisite
    if (is_multisite()) {
        delete_site_option('wp_segment_settings');
    }

    // Eliminar transients relacionados - Remove related transients
    delete_transient('wp_segment_connection_test');
    delete_transient('wp_segment_cache');

    // Eliminar user meta relacionada - Remove related user meta
    delete_metadata('user', 0, 'wp_segment_user_id', '', true);
    delete_metadata('user', 0, 'wp_segment_last_seen', '', true);

    // Eliminar post meta relacionada (si existe) - Remove related post meta (if exists)
    delete_metadata('post', 0, 'wp_segment_tracked', '', true);

    // Limpiar caché de WordPress - Clear WordPress cache
    wp_cache_flush();

    // Eliminar archivos de log si existen - Remove log files if they exist
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/wp-segment-debug.log';
    if (file_exists($log_file)) {
        unlink($log_file);
    }

    // Eliminar scheduled events - Remove scheduled events
    wp_clear_scheduled_hook('wp_segment_cleanup');
    wp_clear_scheduled_hook('wp_segment_batch_send');

    // Log de desinstalación (opcional) - Uninstall log (optional)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('WP Segment Integration: Plugin uninstalled and data cleaned up');
    }
}

// Ejecutar limpieza - Run cleanup
wp_segment_uninstall_cleanup();

