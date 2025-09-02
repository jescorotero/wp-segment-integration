<?php
/**
 * Class for managing event tracking with Segment.io
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Segment_Tracker {

    /**
     * Instance of settings
     */
    private $settings;

    /**
     * URL of the Segment API
     */
    const SEGMENT_API_URL = 'https://api.segment.io/v1/';

    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
    }

    /**
     * Initialize the tracker
     */
    public function init() {
        // Check if tracking is enabled
        if (!$this->should_track()) {
            return;
        }

        // Register hooks for automatic tracking
        $this->register_tracking_hooks();
    }

    /**
     * Check if tracking should be done
     */
    private function should_track() {
        // Check if it is configured
        if (!$this->settings->is_configured()) {
            return false;
        }

        // Check if the user should be excluded
        if ($this->settings->should_exclude_current_user()) {
            return false;
        }

        // Check if the current page should be excluded
        if ($this->settings->should_exclude_current_page()) {
            return false;
        }

        // Check Do Not Track
        if ($this->settings->should_respect_dnt()) {
            return false;
        }

        // Check cookie consent
        if ($this->settings->requires_cookie_consent() && !$this->has_cookie_consent()) {
            return false;
        }

        return true;
    }

    /**
     * Check if cookie consent is given
     */
    private function has_cookie_consent() {
        // Implement logic to check for consent
        // This can integrate with popular cookie plugins
        return apply_filters('wp_segment_has_cookie_consent', true);
    }

    /**
     * Register hooks for automatic tracking
     */
    private function register_tracking_hooks() {
        // Page views
        if ($this->settings->is_event_enabled('track_page_views')) {
            add_action('wp_footer', array($this, 'track_page_view'));
        }

        // Form submissions
        if ($this->settings->is_event_enabled('track_form_submissions')) {
            add_action('wp_footer', array($this, 'add_form_tracking_script'));
        }
    }

    /**
     * Track page view
     */
    public function track_page_view() {
        if (!$this->should_track()) {
            return;
        }

        $page_data = $this->get_page_data();
        $this->enqueue_client_event('page', $page_data);
    }

    /**
     * Get the current page data
     */
    private function get_page_data() {
        global $wp_query;

        $data = array(
            'url' => home_url(add_query_arg(array(), $wp_query->request)),
            'title' => wp_get_document_title(),
            'referrer' => wp_get_referer(),
        );

        // Add more context based on page type
        if (is_single() || is_page()) {
            $post = get_queried_object();
            $data['name'] = $post->post_title;
            $data['category'] = $this->get_post_categories($post);
            $data['author'] = get_the_author_meta('display_name', $post->post_author);
            $data['date'] = $post->post_date;
        } elseif (is_category()) {
            $category = get_queried_object();
            $data['name'] = $category->name;
            $data['category'] = 'Category';
        } elseif (is_tag()) {
            $tag = get_queried_object();
            $data['name'] = $tag->name;
            $data['category'] = 'Tag';
        } elseif (is_search()) {
            $data['name'] = 'Search Results';
            $data['search_term'] = get_search_query();
        } elseif (is_404()) {
            $data['name'] = '404 Not Found';
            $data['category'] = 'Error';
        }

        // Add custom properties
        $custom_properties = $this->settings->get_custom_properties('page');
        if (!empty($custom_properties)) {
            $data = array_merge($data, $custom_properties);
        }

        return apply_filters('wp_segment_page_data', $data);
    }

    /**
     * Get categories for a post
     */
    private function get_post_categories($post) {
        $categories = get_the_category($post->ID);
        if (empty($categories)) {
            return '';
        }

        return implode(', ', wp_list_pluck($categories, 'name'));
    }

    /**
     * Identify user
     */
    public function identify($user_id, $traits = array()) {
        if (!$this->should_track()) {
            return;
        }

        $data = array(
            'userId' => $user_id,
            'traits' => $traits,
        );

        // Add custom properties
        $custom_properties = $this->settings->get_custom_properties('identify');
        if (!empty($custom_properties)) {
            $data['traits'] = array_merge($data['traits'], $custom_properties);
        }

        $this->enqueue_client_event('identify', $data);

        // Also send server-side if enabled
        if ($this->settings->get_option('server_side_tracking', false)) {
            $this->send_server_side_event('identify', $data);
        }
    }

    /**
     * Track event
     */
    public function track($event, $properties = array(), $user_id = null) {
        if (!$this->should_track()) {
            return;
        }

        $data = array(
            'event' => $event,
            'properties' => $properties,
        );

        // Add user ID if available
        if ($user_id) {
            $data['userId'] = $user_id;
        } elseif (is_user_logged_in()) {
            $data['userId'] = get_current_user_id();
        }

        // Add custom properties
        $custom_properties = $this->settings->get_custom_properties('track');
        if (!empty($custom_properties)) {
            $data['properties'] = array_merge($data['properties'], $custom_properties);
        }

        $this->enqueue_client_event('track', $data);

        // Also send server-side if enabled
        if ($this->settings->get_option('server_side_tracking', false)) {
            $this->send_server_side_event('track', $data);
        }

        // Log for debugging
        if ($this->settings->get_option('debug_mode', false)) {
            error_log('WP Segment: Tracking event - ' . $event . ' - ' . json_encode($properties));
        }
    }

    /**
     * Enqueue client event
     */
    private function enqueue_client_event($type, $data) {
        static $events = array();
        
        $events[] = array(
            'type' => $type,
            'data' => $data,
        );

        // Add script to send events
        add_action('wp_footer', function() use ($events) {
            if (empty($events)) {
                return;
            }

            echo '<script type="text/javascript">';
            echo 'if (typeof analytics !== "undefined") {';
            
            foreach ($events as $event) {
                switch ($event['type']) {
                    case 'identify':
                        echo 'analytics.identify(' . json_encode($event['data']['userId']) . ', ' . json_encode($event['data']['traits']) . ');';
                        break;
                    case 'track':
                        $user_id = isset($event['data']['userId']) ? json_encode($event['data']['userId']) : 'null';
                        echo 'analytics.track(' . json_encode($event['data']['event']) . ', ' . json_encode($event['data']['properties']) . ');';
                        break;
                    case 'page':
                        echo 'analytics.page(' . json_encode($event['data']) . ');';
                        break;
                }
            }
            
            echo '}';
            echo '</script>';
        }, 999);
    }

    /**
     * Send server-side event
     */
    private function send_server_side_event($type, $data) {
        $write_key = $this->settings->get_option('write_key');
        if (empty($write_key)) {
            return false;
        }

        $url = self::SEGMENT_API_URL . $type;

        // Add timestamp and messageId
        $data['timestamp'] = date('c');
        $data['messageId'] = $this->generate_message_id();

        // Add context
        $data['context'] = $this->get_context_data();

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($write_key . ':'),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            if ($this->settings->get_option('debug_mode', false)) {
                error_log('WP Segment: Server-side tracking error - ' . $response->get_error_message());
            }
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            if ($this->settings->get_option('debug_mode', false)) {
                error_log('WP Segment: Server-side tracking failed - HTTP ' . $response_code);
            }
            return false;
        }

        return true;
    }

    /**
     * Generate a unique message ID
     */
    private function generate_message_id() {
        return wp_generate_uuid4();
    }

    /**
     * Get context data
     */
    private function get_context_data() {
        $context = array(
            'library' => array(
                'name' => 'wp-segment-integration',
                'version' => WP_SEGMENT_VERSION,
            ),
            'page' => array(
                'url' => home_url($_SERVER['REQUEST_URI']),
                'title' => wp_get_document_title(),
                'referrer' => wp_get_referer(),
            ),
        );

        // Add user information if available
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $context['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        // Add IP if not anonymized
        if (!$this->settings->get_option('anonymize_ip', false)) {
            $context['ip'] = $this->get_client_ip();
        }

        return $context;
    }

    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Add form tracking script
     */
    public function add_form_tracking_script() {
        if (!$this->should_track()) {
            return;
        }

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('form').on('submit', function(e) {
                var form = $(this);
                var formData = {
                    form_id: form.attr('id') || 'unknown',
                    form_class: form.attr('class') || '',
                    form_action: form.attr('action') || window.location.href,
                    form_method: form.attr('method') || 'get'
                };

                if (typeof analytics !== 'undefined') {
                    analytics.track('Form Submitted', formData);
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Track custom event
     */
    public function track_custom_event($event_name, $properties = array()) {
        $custom_events = $this->settings->get_option('custom_events', array());
        
        if (!in_array($event_name, $custom_events)) {
            return false;
        }

        return $this->track($event_name, $properties);
    }

    /**
     * Get tracker settings
     */
    public function get_settings() {
        return $this->settings;
    }
}

