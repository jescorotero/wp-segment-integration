<?php
/**
 * Class for managing the plugin's admin panel
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Segment_Admin {

    /**
     * Plugin settings
     */
    private $settings;

    /**
     * Menu hook
     */
    private $hook_suffix;

    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_wp_segment_test_connection', array($this, 'test_segment_connection'));
        add_action('wp_ajax_wp_segment_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_wp_segment_import_settings', array($this, 'import_settings'));
        add_filter('plugin_action_links_' . WP_SEGMENT_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $this->hook_suffix = add_options_page(
            __('WP Segment Integration', 'wp-segment-integration'),
            __('Segment.io', 'wp-segment-integration'),
            'manage_options',
            'wp-segment-integration',
            array($this, 'display_admin_page')
        );
    }

    /**
     * Register configurations
     */
    public function register_settings() {
        register_setting(
            'wp_segment_settings_group',
            WP_Segment_Settings::OPTION_NAME,
            array($this, 'validate_settings')
        );

        // General section
        add_settings_section(
            'wp_segment_general_section',
            __('General Settings', 'wp-segment-integration'),
            array($this, 'general_section_callback'),
            'wp-segment-integration'
        );

        // General configuration fields
        add_settings_field(
            'enabled',
            __('Enable Tracking', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_general_section',
            array('field' => 'enabled', 'description' => __('Enable or disable Segment.io tracking', 'wp-segment-integration'))
        );

        add_settings_field(
            'write_key',
            __('Write Key', 'wp-segment-integration'),
            array($this, 'text_field_callback'),
            'wp-segment-integration',
            'wp_segment_general_section',
            array('field' => 'write_key', 'description' => __('Your Segment.io Write Key', 'wp-segment-integration'), 'required' => true)
        );

        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_general_section',
            array('field' => 'debug_mode', 'description' => __('Enable debug logs for troubleshooting', 'wp-segment-integration'))
        );

        // WordPress Events section
        add_settings_section(
            'wp_segment_wordpress_section',
            __('WordPress Events', 'wp-segment-integration'),
            array($this, 'wordpress_section_callback'),
            'wp-segment-integration'
        );

        add_settings_field(
            'track_user_events',
            __('User Events', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_wordpress_section',
            array('field' => 'track_user_events', 'description' => __('Track user login and registration', 'wp-segment-integration'))
        );

        add_settings_field(
            'track_page_views',
            __('Page Views', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_wordpress_section',
            array('field' => 'track_page_views', 'description' => __('Track page views automatically', 'wp-segment-integration'))
        );

        add_settings_field(
            'track_form_submissions',
            __('Form Submissions', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_wordpress_section',
            array('field' => 'track_form_submissions', 'description' => __('Track form submissions', 'wp-segment-integration'))
        );

        // WooCommerce section (only if active)
        if (class_exists('WooCommerce')) {
            add_settings_section(
                'wp_segment_woocommerce_section',
                __('WooCommerce Events', 'wp-segment-integration'),
                array($this, 'woocommerce_section_callback'),
                'wp-segment-integration'
            );

            $this->add_woocommerce_fields();
        }

        // Privacy and Compliance section
        add_settings_section(
            'wp_segment_privacy_section',
            __('Privacy and Compliance', 'wp-segment-integration'),
            array($this, 'privacy_section_callback'),
            'wp-segment-integration'
        );

        $this->add_privacy_fields();

        // Advanced Settings section
        add_settings_section(
            'wp_segment_advanced_section',
            __('Advanced Settings', 'wp-segment-integration'),
            array($this, 'advanced_section_callback'),
            'wp-segment-integration'
        );

        $this->add_advanced_fields();
    }

    /**
     * Add WooCommerce fields
     */
    private function add_woocommerce_fields() {
        $woocommerce_fields = array(
            'woocommerce_enabled' => __('Enable WooCommerce', 'wp-segment-integration'),
            'track_product_viewed' => __('Product Viewed', 'wp-segment-integration'),
            'track_product_added' => __('Product Added', 'wp-segment-integration'),
            'track_product_removed' => __('Product Removed', 'wp-segment-integration'),
            'track_cart_viewed' => __('Cart Viewed', 'wp-segment-integration'),
            'track_checkout_started' => __('Checkout Started', 'wp-segment-integration'),
            'track_checkout_steps' => __('Checkout Steps', 'wp-segment-integration'),
            'track_order_completed' => __('Order Completed', 'wp-segment-integration'),
            'track_order_updated' => __('Order Updated', 'wp-segment-integration'),
            'track_order_refunded' => __('Order Refunded', 'wp-segment-integration'),
            'track_order_cancelled' => __('Order Cancelled', 'wp-segment-integration'),
            'track_coupon_events' => __('Coupon Events', 'wp-segment-integration'),
            'track_product_searches' => __('Product Searches', 'wp-segment-integration'),
            'track_product_lists' => __('Product Lists', 'wp-segment-integration'),
        );

        foreach ($woocommerce_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'checkbox_field_callback'),
                'wp-segment-integration',
                'wp_segment_woocommerce_section',
                array('field' => $field)
            );
        }
    }

    /**
     * Add privacy fields
     */
    private function add_privacy_fields() {
        add_settings_field(
            'anonymize_ip',
            __('Anonymize IP', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_privacy_section',
            array('field' => 'anonymize_ip', 'description' => __('Do not send IP addresses to Segment.io', 'wp-segment-integration'))
        );

        add_settings_field(
            'respect_dnt',
            __('Respect Do Not Track', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_privacy_section',
            array('field' => 'respect_dnt', 'description' => __('No track users with Do Not Track enabled', 'wp-segment-integration'))
        );

        add_settings_field(
            'cookie_consent',
            __('Require Cookie Consent', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_privacy_section',
            array('field' => 'cookie_consent', 'description' => __('Only track after obtaining cookie consent', 'wp-segment-integration'))
        );

        add_settings_field(
            'exclude_user_roles',
            __('Exclude User Roles', 'wp-segment-integration'),
            array($this, 'multiselect_field_callback'),
            'wp-segment-integration',
            'wp_segment_privacy_section',
            array('field' => 'exclude_user_roles', 'options' => $this->get_user_roles(), 'description' => __('User roles that will not be tracked', 'wp-segment-integration'))
        );
    }

    /**
     * Add advanced fields
     */
    private function add_advanced_fields() {
        add_settings_field(
            'server_side_tracking',
            __('Tracking Server-Side', 'wp-segment-integration'),
            array($this, 'checkbox_field_callback'),
            'wp-segment-integration',
            'wp_segment_advanced_section',
            array('field' => 'server_side_tracking', 'description' => __('Send events also from the server', 'wp-segment-integration'))
        );
    }

    /**
     * Show admin page
     */
    public function display_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-segment-integration'));
        }

        // Process actions
        if (isset($_POST['action'])) {
            $this->process_admin_actions();
        }

        include WP_SEGMENT_PLUGIN_DIR . 'admin/partials/admin-display.php';
    }

    /**
     * Process admin actions
     */
    private function process_admin_actions() {
        if (!wp_verify_nonce($_POST['wp_segment_nonce'], 'wp_segment_admin_action')) {
            wp_die(__('Security check failed.', 'wp-segment-integration'));
        }

        switch ($_POST['action']) {
            case 'test_connection':
                $this->test_segment_connection();
                break;
            case 'reset_settings':
                $this->reset_settings();
                break;
        }
    }

    /**
     * Validate settings
     */
    public function validate_settings($input) {
        return $this->settings->validate_settings($input);
    }

    /**
     * Callback for general section
     */
    public function general_section_callback() {
        echo '<p>' . __('Basic settings for Segment.io integration', 'wp-segment-integration') . '</p>';
    }

    /**
     * Callback for WordPress section
     */
    public function wordpress_section_callback() {
        echo '<p>' . __('Configure which WordPress events to track', 'wp-segment-integration') . '</p>';
    }

    /**
     * Callback for WooCommerce section
     */
    public function woocommerce_section_callback() {
        echo '<p>' . __('Configure which WooCommerce events to track', 'wp-segment-integration') . '</p>';
    }

    /**
     * Callback for privacy section
     */
    public function privacy_section_callback() {
        echo '<p>' . __('Settings related to privacy and legal compliance', 'wp-segment-integration') . '</p>';
    }

    /**
     * Callback for advanced section
     */
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced settings for experienced users', 'wp-segment-integration') . '</p>';
    }

    /**
     * Callback for text fields
     */
    public function text_field_callback($args) {
        $field = $args['field'];
        $value = $this->settings->get_option($field, '');
        $description = isset($args['description']) ? $args['description'] : '';
        $required = isset($args['required']) ? 'required' : '';
        $type = isset($args['type']) ? $args['type'] : 'text';

        echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($field) . '" name="' . WP_Segment_Settings::OPTION_NAME . '[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" class="regular-text" ' . $required . ' />';
        
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Callback for checkbox fields
     */
    public function checkbox_field_callback($args) {
        $field = $args['field'];
        $value = $this->settings->get_option($field, false);
        $description = isset($args['description']) ? $args['description'] : '';

        echo '<label for="' . esc_attr($field) . '">';
        echo '<input type="checkbox" id="' . esc_attr($field) . '" name="' . WP_Segment_Settings::OPTION_NAME . '[' . esc_attr($field) . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html($description);
        echo '</label>';
    }

    /**
     * Callback for multiselect fields
     */
    public function multiselect_field_callback($args) {
        $field = $args['field'];
        $values = $this->settings->get_option($field, array());
        $options = $args['options'];
        $description = isset($args['description']) ? $args['description'] : '';

        echo '<select id="' . esc_attr($field) . '" name="' . WP_Segment_Settings::OPTION_NAME . '[' . esc_attr($field) . '][]" multiple="multiple" class="regular-text">';
        
        foreach ($options as $option_value => $option_label) {
            $selected = in_array($option_value, $values) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        
        echo '</select>';
        
        if ($description) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    /**
     * Get all user roles for multiselect field
     */
    private function get_user_roles() {
        global $wp_roles;
        $roles = array();
        
        foreach ($wp_roles->roles as $role_key => $role) {
            $roles[$role_key] = $role['name'];
        }
        
        return $roles;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== $this->hook_suffix) {
            return;
        }

        wp_enqueue_style(
            'wp-segment-admin',
            WP_SEGMENT_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            WP_SEGMENT_VERSION
        );

        wp_enqueue_script(
            'wp-segment-admin',
            WP_SEGMENT_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery'),
            WP_SEGMENT_VERSION,
            true
        );

        wp_localize_script('wp-segment-admin', 'wpSegmentAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_segment_admin_nonce'),
            'strings' => array(
                'testing' => __('Testing connection...', 'wp-segment-integration'),
                'success' => __('Connection successful', 'wp-segment-integration'),
                'error' => __('Connection error', 'wp-segment-integration'),
                'confirm_reset' => __('Are you sure you want to reset all settings?', 'wp-segment-integration'),
            ),
        ));
    }

    /**
     * Test connection with Segment
     */
    public function test_segment_connection() {
        check_ajax_referer('wp_segment_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-segment-integration'));
        }

        $write_key = $this->settings->get_option('write_key');
        if (empty($write_key)) {
            wp_send_json_error(__('Write Key not configured', 'wp-segment-integration'));
        }

        // Send test event
        $test_data = array(
            'userId' => 'test_user',
            'event' => 'Test Connection',
            'properties' => array(
                'source' => 'wp-segment-integration',
                'timestamp' => date('c'),
            ),
        );

        $response = wp_remote_post('https://api.segment.io/v1/track', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($write_key . ':'),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($test_data),
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            wp_send_json_success(__('Connection successful with Segment.io', 'wp-segment-integration'));
        } else {
            wp_send_json_error(__('Connection error: HTTP ' . $response_code, 'wp-segment-integration'));
        }
    }

    /**
     * Export settings
     */
    public function export_settings() {
        check_ajax_referer('wp_segment_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-segment-integration'));
        }

        $settings_json = $this->settings->export_settings();
        wp_send_json_success(array('settings' => $settings_json));
    }

    /**
     * Import settings
     */
    public function import_settings() {
        check_ajax_referer('wp_segment_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-segment-integration'));
        }

        $settings_json = sanitize_textarea_field($_POST['settings']);
        $result = $this->settings->import_settings($settings_json);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Settings imported successfully', 'wp-segment-integration'));
    }

    /**
     * Reset settings
     */
    private function reset_settings() {
        $this->settings->reset_to_defaults();
        add_settings_error(
            'wp_segment_messages',
            'wp_segment_message',
            __('Settings reset to default values', 'wp-segment-integration'),
            'updated'
        );
    }

    /**
     * Add action links to the plugin
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=wp-segment-integration') . '">' . __('Settings', 'wp-segment-integration') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

