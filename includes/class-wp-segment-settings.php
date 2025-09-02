<?php
/**
 * Class for managing the plugin's settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Segment_Settings {

    /**
     * Name of the option in the database
     */
    const OPTION_NAME = 'wp_segment_settings';

    /**
     * Default settings
     */
    private $default_settings = array(
        'enabled' => true,
        'write_key' => '',
        'debug_mode' => false,
        'track_user_events' => true,
        'track_page_views' => true,
        'track_form_submissions' => false,
        'anonymize_ip' => false,
        'respect_dnt' => true,
        'cookie_consent' => false,
        'custom_events' => array(),
        // WooCommerce settings
        'woocommerce_enabled' => true,
        'track_product_viewed' => true,
        'track_product_added' => true,
        'track_product_removed' => true,
        'track_cart_viewed' => true,
        'track_checkout_started' => true,
        'track_checkout_steps' => true,
        'track_order_completed' => true,
        'track_order_updated' => true,
        'track_order_refunded' => true,
        'track_order_cancelled' => true,
        'track_coupon_events' => true,
        'track_wishlist_events' => false,
        'track_product_searches' => true,
        'track_product_lists' => true,
        'track_promotions' => false,
        'track_reviews' => false,
        'track_sharing' => false,
        // Advanced settings
        'exclude_user_roles' => array(),
        'exclude_pages' => array(),
        'custom_properties' => array(),
        'server_side_tracking' => false,
    );

    /**
     * Current settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
    }

    /**
     * Load settings from the database
     */
    private function load_settings() {
        $saved_settings = get_option(self::OPTION_NAME, array());
        $this->settings = wp_parse_args($saved_settings, $this->default_settings);
    }

    /**
     * Get a specific option
     */
    public function get_option($key, $default = null) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return $default !== null ? $default : (isset($this->default_settings[$key]) ? $this->default_settings[$key] : null);
    }

    /**
     * Set a specific option
     */
    public function set_option($key, $value) {
        $this->settings[$key] = $value;
    }

    /**
     * Get all settings
     */
    public function get_all_settings() {
        return $this->settings;
    }

    /**
     * Save settings to the database
     */
    public function save_settings() {
        return update_option(self::OPTION_NAME, $this->settings);
    }

    /**
     * Update settings
     */
    public function update_settings($new_settings) {
        $this->settings = wp_parse_args($new_settings, $this->default_settings);
        return $this->save_settings();
    }

    /**
     * Check if the plugin is configured
     */
    public function is_configured() {
        $write_key = $this->get_option('write_key');
        return !empty($write_key) && $this->get_option('enabled', true);
    }

    /**
     * Check if WooCommerce is enabled
     */
    public function is_woocommerce_enabled() {
        return class_exists('WooCommerce') && $this->get_option('woocommerce_enabled', true);
    }

    /**
     * Check if a specific event is enabled
     */
    public function is_event_enabled($event_key) {
        return $this->get_option($event_key, true);
    }

    /**
     * Check if the current user should be excluded from tracking
     */
    public function should_exclude_current_user() {
        if (!is_user_logged_in()) {
            return false;
        }

        $current_user = wp_get_current_user();
        $excluded_roles = $this->get_option('exclude_user_roles', array());

        if (empty($excluded_roles)) {
            return false;
        }

        foreach ($current_user->roles as $role) {
            if (in_array($role, $excluded_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current page should be excluded from tracking
     */
    public function should_exclude_current_page() {
        $excluded_pages = $this->get_option('exclude_pages', array());
        
        if (empty($excluded_pages)) {
            return false;
        }

        $current_page_id = get_queried_object_id();
        return in_array($current_page_id, $excluded_pages);
    }

    /**
     * Check if the Do Not Track should be respected
     */
    public function should_respect_dnt() {
        if (!$this->get_option('respect_dnt', true)) {
            return false;
        }

        return isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == '1';
    }

    /**
     * Check if cookie consent is required
     */
    public function requires_cookie_consent() {
        return $this->get_option('cookie_consent', false);
    }

    /**
     * Get custom properties for an event
     */
    public function get_custom_properties($event_type = 'global') {
        $custom_properties = $this->get_option('custom_properties', array());
        
        if (isset($custom_properties[$event_type])) {
            return $custom_properties[$event_type];
        }

        return isset($custom_properties['global']) ? $custom_properties['global'] : array();
    }

    /**
     * Validate settings
     */
    public function validate_settings($settings) {
        $validated = array();

        // Validate write_key
        if (isset($settings['write_key'])) {
            $validated['write_key'] = sanitize_text_field($settings['write_key']);
        }

        // Validate boolean options
        $boolean_options = array(
            'enabled', 'debug_mode', 'track_user_events', 'track_page_views',
            'track_form_submissions', 'anonymize_ip', 'respect_dnt', 'cookie_consent',
            'woocommerce_enabled', 'track_product_viewed', 'track_product_added',
            'track_product_removed', 'track_cart_viewed', 'track_checkout_started',
            'track_checkout_steps', 'track_order_completed', 'track_order_updated',
            'track_order_refunded', 'track_order_cancelled', 'track_coupon_events',
            'track_wishlist_events', 'track_product_searches', 'track_product_lists',
            'track_promotions', 'track_reviews', 'track_sharing', 'server_side_tracking'
        );

        foreach ($boolean_options as $option) {
            if (isset($settings[$option])) {
                $validated[$option] = (bool) $settings[$option];
            }
        }

        // Validate arrays
        $array_options = array('exclude_user_roles', 'exclude_pages', 'custom_events');
        foreach ($array_options as $option) {
            if (isset($settings[$option]) && is_array($settings[$option])) {
                $validated[$option] = array_map('sanitize_text_field', $settings[$option]);
            }
        }

        // Validate custom_properties
        if (isset($settings['custom_properties']) && is_array($settings['custom_properties'])) {
            $validated['custom_properties'] = $this->validate_custom_properties($settings['custom_properties']);
        }

        return $validated;
    }

    /**
     * Validate custom properties
     */
    private function validate_custom_properties($properties) {
        $validated = array();

        foreach ($properties as $event_type => $props) {
            if (is_array($props)) {
                $validated[sanitize_text_field($event_type)] = array();
                foreach ($props as $key => $value) {
                    $validated[sanitize_text_field($event_type)][sanitize_text_field($key)] = sanitize_text_field($value);
                }
            }
        }

        return $validated;
    }

    /**
     * Create default options when activating the plugin
     */
    public function create_default_options() {
        if (!get_option(self::OPTION_NAME)) {
            add_option(self::OPTION_NAME, $this->default_settings);
        }
    }

    /**
     * Delete options when uninstalling the plugin
     */
    public static function delete_options() {
        delete_option(self::OPTION_NAME);
    }

    /**
     * Get default settings
     */
    public function get_default_settings() {
        return $this->default_settings;
    }

    /**
     * Reset settings to default values
     */
    public function reset_to_defaults() {
        $this->settings = $this->default_settings;
        return $this->save_settings();
    }

    /**
     * Export settings
     */
    public function export_settings() {
        return json_encode($this->settings, JSON_PRETTY_PRINT);
    }

    /**
     * Import settings
     */
    public function import_settings($json_settings) {
        $imported_settings = json_decode($json_settings, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON format', 'wp-segment-integration'));
        }

        $validated_settings = $this->validate_settings($imported_settings);
        $this->settings = wp_parse_args($validated_settings, $this->default_settings);
        
        if ($this->save_settings()) {
            return true;
        }

        return new WP_Error('save_failed', __('Failed to save imported settings', 'wp-segment-integration'));
    }
}

