<?php
/**
 * Class for WooCommerce integration and e-commerce event tracking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Segment_WooCommerce {

    /**
     * Instance of the tracker
     */
    private $tracker;

    /**
     * Instance of settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct($tracker, $settings) {
        $this->tracker = $tracker;
        $this->settings = $settings;

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize WooCommerce hooks
     */
    private function init_hooks() {
        if (!$this->settings->is_woocommerce_enabled()) {
            return;
        }

        // Product hooks
        add_action('woocommerce_single_product_summary', array($this, 'track_product_viewed'), 25);
        add_action('woocommerce_add_to_cart', array($this, 'track_product_added'), 10, 6);
        add_action('woocommerce_cart_item_removed', array($this, 'track_product_removed'), 10, 2);

        // Cart hooks
        add_action('woocommerce_before_cart', array($this, 'track_cart_viewed'));

        // Checkout hooks
        add_action('woocommerce_before_checkout_form', array($this, 'track_checkout_started'));
        add_action('woocommerce_checkout_order_processed', array($this, 'track_order_completed'), 10, 3);

        // Order hooks
        add_action('woocommerce_order_status_changed', array($this, 'track_order_status_changed'), 10, 4);

        // Coupon hooks
        add_action('woocommerce_applied_coupon', array($this, 'track_coupon_applied'));
        add_action('woocommerce_removed_coupon', array($this, 'track_coupon_removed'));

        // Search hooks
        add_action('pre_get_posts', array($this, 'track_product_search'));

        // Product list hooks
        add_action('woocommerce_before_shop_loop', array($this, 'track_product_list_viewed'));

        // AJAX hooks for frontend events
        add_action('wp_ajax_wp_segment_track_event', array($this, 'ajax_track_event'));
        add_action('wp_ajax_nopriv_wp_segment_track_event', array($this, 'ajax_track_event'));

        // Frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_woocommerce_scripts'));
    }

    /**
     * Track product viewed
     */
    public function track_product_viewed() {
        if (!$this->settings->is_event_enabled('track_product_viewed')) {
            return;
        }

        global $product;
        if (!$product) {
            return;
        }

        $product_data = $this->get_product_data($product);

        // Add to frontend script for tracking
        $this->add_frontend_tracking_script('Product Viewed', $product_data);
    }

    /**
     * Track product added to cart
     */
    public function track_product_added($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        if (!$this->settings->is_event_enabled('track_product_added')) {
            return;
        }

        $product = wc_get_product($variation_id ? $variation_id : $product_id);
        if (!$product) {
            return;
        }

        $product_data = $this->get_product_data($product, $quantity);
        $product_data['cart_id'] = WC()->session->get_customer_id();

        $this->tracker->track('Product Added', $product_data);
    }

    /**
     * Track product removed from cart
     */
    public function track_product_removed($cart_item_key, $cart) {
        if (!$this->settings->is_event_enabled('track_product_removed')) {
            return;
        }

        $cart_item = $cart->removed_cart_contents[$cart_item_key];
        $product = $cart_item['data'];

        if (!$product) {
            return;
        }

        $product_data = $this->get_product_data($product, $cart_item['quantity']);
        $product_data['cart_id'] = WC()->session->get_customer_id();

        $this->tracker->track('Product Removed', $product_data);
    }

    /**
     * Track cart viewed
     */
    public function track_cart_viewed() {
        if (!$this->settings->is_event_enabled('track_cart_viewed')) {
            return;
        }

        $cart = WC()->cart;
        if ($cart->is_empty()) {
            return;
        }

        $cart_data = array(
            'cart_id' => WC()->session->get_customer_id(),
            'products' => array(),
            'value' => $cart->get_total('edit'),
            'currency' => get_woocommerce_currency(),
        );

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $cart_data['products'][] = $this->get_product_data($product, $cart_item['quantity']);
        }

        $this->add_frontend_tracking_script('Cart Viewed', $cart_data);
    }

    /**
     * Track checkout started
     */
    public function track_checkout_started() {
        if (!$this->settings->is_event_enabled('track_checkout_started')) {
            return;
        }

        $cart = WC()->cart;
        if ($cart->is_empty()) {
            return;
        }

        $checkout_data = array(
            'order_id' => '', // Will be generated at checkout
            'value' => $cart->get_total('edit'),
            'revenue' => $cart->get_subtotal(),
            'shipping' => $cart->get_shipping_total(),
            'tax' => $cart->get_total_tax(),
            'discount' => $cart->get_discount_total(),
            'currency' => get_woocommerce_currency(),
            'products' => array(),
        );

        // Add coupons
        $coupons = $cart->get_applied_coupons();
        if (!empty($coupons)) {
            $checkout_data['coupon'] = implode(', ', $coupons);
        }

        // Add products
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $checkout_data['products'][] = $this->get_product_data($product, $cart_item['quantity']);
        }

        $this->add_frontend_tracking_script('Checkout Started', $checkout_data);
    }

    /**
     * Track order completed
     */
    public function track_order_completed($order_id, $posted_data, $order) {
        if (!$this->settings->is_event_enabled('track_order_completed')) {
            return;
        }

        if (!$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        $order_data = $this->get_order_data($order);
        $this->tracker->track('Order Completed', $order_data, $order->get_customer_id());
    }

    /**
     * Track order status changed
     */
    public function track_order_status_changed($order_id, $old_status, $new_status, $order) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        $order_data = $this->get_order_data($order);
        $order_data['previous_status'] = $old_status;
        $order_data['new_status'] = $new_status;

        switch ($new_status) {
            case 'cancelled':
                if ($this->settings->is_event_enabled('track_order_cancelled')) {
                    $this->tracker->track('Order Cancelled', $order_data, $order->get_customer_id());
                }
                break;
            case 'refunded':
                if ($this->settings->is_event_enabled('track_order_refunded')) {
                    $this->tracker->track('Order Refunded', $order_data, $order->get_customer_id());
                }
                break;
            default:
                if ($this->settings->is_event_enabled('track_order_updated')) {
                    $this->tracker->track('Order Updated', $order_data, $order->get_customer_id());
                }
                break;
        }
    }

    /**
     * Track coupon applied
     */
    public function track_coupon_applied($coupon_code) {
        if (!$this->settings->is_event_enabled('track_coupon_events')) {
            return;
        }

        $coupon = new WC_Coupon($coupon_code);
        $cart = WC()->cart;

        $coupon_data = array(
            'coupon_id' => $coupon_code,
            'cart_id' => WC()->session->get_customer_id(),
            'name' => $coupon_code,
            'discount' => $cart->get_discount_total(),
            'products' => array(),
        );

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $coupon_data['products'][] = $this->get_product_data($product, $cart_item['quantity']);
        }

        $this->tracker->track('Coupon Applied', $coupon_data);
    }

    /**
     * Track coupon removed
     */
    public function track_coupon_removed($coupon_code) {
        if (!$this->settings->is_event_enabled('track_coupon_events')) {
            return;
        }

        $cart = WC()->cart;

        $coupon_data = array(
            'coupon_id' => $coupon_code,
            'cart_id' => WC()->session->get_customer_id(),
            'name' => $coupon_code,
            'products' => array(),
        );

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $coupon_data['products'][] = $this->get_product_data($product, $cart_item['quantity']);
        }

        $this->tracker->track('Coupon Removed', $coupon_data);
    }

    /**
     * Track product search
     */
    public function track_product_search($query) {
        if (!$this->settings->is_event_enabled('track_product_searches')) {
            return;
        }

        if (!$query->is_main_query() || !$query->is_search() || !is_woocommerce()) {
            return;
        }

        $search_term = get_search_query();
        if (empty($search_term)) {
            return;
        }

        $search_data = array(
            'query' => $search_term,
        );

        $this->add_frontend_tracking_script('Products Searched', $search_data);
    }

    /**
     * Track product list viewed (shop, category, tag pages)
     */
    public function track_product_list_viewed() {
        if (!$this->settings->is_event_enabled('track_product_lists')) {
            return;
        }

        global $wp_query;

        $list_data = array(
            'products' => array(),
        );

        // Determine list type
        if (is_shop()) {
            $list_data['list_id'] = 'shop';
            $list_data['category'] = 'Shop';
        } elseif (is_product_category()) {
            $category = get_queried_object();
            $list_data['list_id'] = 'category_' . $category->term_id;
            $list_data['category'] = $category->name;
        } elseif (is_product_tag()) {
            $tag = get_queried_object();
            $list_data['list_id'] = 'tag_' . $tag->term_id;
            $list_data['category'] = 'Tag: ' . $tag->name;
        }

        // Aggregate products from current query
        if ($wp_query->have_posts()) {
            $position = 1;
            while ($wp_query->have_posts()) {
                $wp_query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $product_data = $this->get_product_data($product);
                    $product_data['position'] = $position;
                    $list_data['products'][] = $product_data;
                    $position++;
                }
            }
            wp_reset_postdata();
        }

        $this->add_frontend_tracking_script('Product List Viewed', $list_data);
    }

    /**
     * Get product data
     */
    private function get_product_data($product, $quantity = 1) {
        $data = array(
            'product_id' => $product->get_id(),
            'sku' => $product->get_sku(),
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'quantity' => $quantity,
            'url' => $product->get_permalink(),
        );

        // Add categories
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($categories)) {
            $data['category'] = $categories[0]->name;
        }

        // Add brand if exists
        $brands = wp_get_post_terms($product->get_id(), 'product_brand');
        if (!empty($brands)) {
            $data['brand'] = $brands[0]->name;
        }

        // Add variant if product is variable
        if ($product->is_type('variation')) {
            $data['variant'] = implode(', ', $product->get_variation_attributes());
        }

        // Add image
        $image_id = $product->get_image_id();
        if ($image_id) {
            $data['image_url'] = wp_get_attachment_image_url($image_id, 'full');
        }

        return apply_filters('wp_segment_product_data', $data, $product);
    }

    /**
     * Get order data
     */
    private function get_order_data($order) {
        $data = array(
            'order_id' => $order->get_id(),
            'affiliation' => get_bloginfo('name'),
            'value' => $order->get_total(),
            'revenue' => $order->get_subtotal(),
            'shipping' => $order->get_shipping_total(),
            'tax' => $order->get_total_tax(),
            'discount' => $order->get_discount_total(),
            'currency' => $order->get_currency(),
            'products' => array(),
        );

        // Add coupons
        $coupons = $order->get_coupon_codes();
        if (!empty($coupons)) {
            $data['coupon'] = implode(', ', $coupons);
        }

        // Add products
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $product_data = $this->get_product_data($product, $item->get_quantity());
                $data['products'][] = $product_data;
            }
        }

        return apply_filters('wp_segment_order_data', $data, $order);
    }

    /**
     * Add tracking script to frontend
     */
    private function add_frontend_tracking_script($event, $data) {
        static $events = array();
        
        $events[] = array(
            'event' => $event,
            'data' => $data,
        );

        add_action('wp_footer', function() use ($events) {
            if (empty($events)) {
                return;
            }

            echo '<script type="text/javascript">';
            echo 'if (typeof analytics !== "undefined") {';
            
            foreach ($events as $event_data) {
                echo 'analytics.track(' . json_encode($event_data['event']) . ', ' . json_encode($event_data['data']) . ');';
            }
            
            echo '}';
            echo '</script>';
        }, 998);
    }

    /**
     * Enqueue WooCommerce scripts
     */
    public function enqueue_woocommerce_scripts() {
        if (!is_woocommerce() && !is_cart() && !is_checkout()) {
            return;
        }

        wp_enqueue_script(
            'wp-segment-woocommerce',
            WP_SEGMENT_PLUGIN_URL . 'public/js/woocommerce-tracking.js',
            array('jquery', 'wp-segment-public'),
            WP_SEGMENT_VERSION,
            true
        );

        wp_localize_script('wp-segment-woocommerce', 'wpSegmentWoo', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_segment_woo_nonce'),
            'events' => array(
                'productClicked' => $this->settings->is_event_enabled('track_product_clicked'),
                'checkoutSteps' => $this->settings->is_event_enabled('track_checkout_steps'),
                'paymentInfo' => $this->settings->is_event_enabled('track_payment_info'),
            ),
        ));
    }

    /**
     * AJAX handler for event tracking
     */
    public function ajax_track_event() {
        check_ajax_referer('wp_segment_woo_nonce', 'nonce');

        $event = sanitize_text_field($_POST['event']);
        $properties = isset($_POST['properties']) ? $_POST['properties'] : array();

        // Sanitizar propiedades
        $properties = $this->sanitize_properties($properties);

        $this->tracker->track($event, $properties);

        wp_send_json_success();
    }

    /**
     * Sanitize event properties
     */
    private function sanitize_properties($properties) {
        $sanitized = array();

        foreach ($properties as $key => $value) {
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_properties($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Get configurations
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Get tracker
     */
    public function get_tracker() {
        return $this->tracker;
    }
}

