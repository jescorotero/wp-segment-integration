<?php
/**
 * Admin Display
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('wp_segment_messages'); ?>

    <nav class="nav-tab-wrapper">
        <a href="?page=wp-segment-integration&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'wp-segment-integration'); ?>
        </a>
        <a href="?page=wp-segment-integration&tab=wordpress" class="nav-tab <?php echo $active_tab == 'wordpress' ? 'nav-tab-active' : ''; ?>">
            <?php _e('WordPress', 'wp-segment-integration'); ?>
        </a>
        <?php if (class_exists('WooCommerce')): ?>
        <a href="?page=wp-segment-integration&tab=woocommerce" class="nav-tab <?php echo $active_tab == 'woocommerce' ? 'nav-tab-active' : ''; ?>">
            <?php _e('WooCommerce', 'wp-segment-integration'); ?>
        </a>
        <?php endif; ?>
        <a href="?page=wp-segment-integration&tab=privacy" class="nav-tab <?php echo $active_tab == 'privacy' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Privacy', 'wp-segment-integration'); ?>
        </a>
        <a href="?page=wp-segment-integration&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Advanced', 'wp-segment-integration'); ?>
        </a>
        <a href="?page=wp-segment-integration&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Tools', 'wp-segment-integration'); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php if ($active_tab == 'general'): ?>
            <div class="tab-pane active">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    do_settings_sections('wp-segment-integration');
                    ?>
                    
                    <div class="wp-segment-section">
                        <h2><?php _e('Connection Status', 'wp-segment-integration'); ?></h2>
                        <div class="wp-segment-connection-status">
                            <?php if ($this->settings->is_configured()): ?>
                                <span class="status-indicator status-connected"></span>
                                <span><?php _e('Configured', 'wp-segment-integration'); ?></span>
                                <button type="button" id="test-connection" class="button button-secondary">
                                    <?php _e('Test Connection', 'wp-segment-integration'); ?>
                                </button>
                            <?php else: ?>
                                <span class="status-indicator status-disconnected"></span>
                                <span><?php _e('Not Configured', 'wp-segment-integration'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div id="connection-result"></div>
                    </div>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'wordpress'): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    do_settings_sections('wp-segment-integration');
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('User Events', 'wp-segment-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_user_events]" value="1" <?php checked(1, $this->settings->get_option('track_user_events', true)); ?> />
                                        <?php _e('Track user login and registration', 'wp-segment-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Page Views', 'wp-segment-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_page_views]" value="1" <?php checked(1, $this->settings->get_option('track_page_views', true)); ?> />
                                        <?php _e('Automatically track page views', 'wp-segment-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Form Submissions', 'wp-segment-integration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_form_submissions]" value="1" <?php checked(1, $this->settings->get_option('track_form_submissions', false)); ?> />
                                        <?php _e('Track form submissions', 'wp-segment-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'woocommerce' && class_exists('WooCommerce')): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    ?>

                    <h2><?php _e('E-commerce Events', 'wp-segment-integration'); ?></h2>
                    <p><?php _e('Configure which WooCommerce events to track according to the Segment.io specification', 'wp-segment-integration'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable WooCommerce', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[woocommerce_enabled]" value="1" <?php checked(1, $this->settings->get_option('woocommerce_enabled', true)); ?> />
                                    <?php _e('Enable WooCommerce event tracking', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Product Events', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Product Viewed', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_viewed]" value="1" <?php checked(1, $this->settings->get_option('track_product_viewed', true)); ?> />
                                    <?php _e('Track when a user views a product', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Product Added', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_added]" value="1" <?php checked(1, $this->settings->get_option('track_product_added', true)); ?> />
                                    <?php _e('Track when a product is added to the cart', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Product Removed', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_removed]" value="1" <?php checked(1, $this->settings->get_option('track_product_removed', true)); ?> />
                                    <?php _e('Track when a product is removed from the cart', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Cart and Checkout Events', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Cart Viewed', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_cart_viewed]" value="1" <?php checked(1, $this->settings->get_option('track_cart_viewed', true)); ?> />
                                    <?php _e('Track when a user views their cart', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Checkout Started', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_checkout_started]" value="1" <?php checked(1, $this->settings->get_option('track_checkout_started', true)); ?> />
                                    <?php _e('Track when a user starts the checkout', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Checkout Steps', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_checkout_steps]" value="1" <?php checked(1, $this->settings->get_option('track_checkout_steps', true)); ?> />
                                    <?php _e('Track individual checkout steps', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Order Events', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Order Completed', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_completed]" value="1" <?php checked(1, $this->settings->get_option('track_order_completed', true)); ?> />
                                    <?php _e('Track when an order is completed', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Order Updated', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_updated]" value="1" <?php checked(1, $this->settings->get_option('track_order_updated', true)); ?> />
                                    <?php _e('Track when an order is updated', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Order Refunded', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_refunded]" value="1" <?php checked(1, $this->settings->get_option('track_order_refunded', true)); ?> />
                                    <?php _e('Track when an order is refunded', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Order Cancelled', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_order_cancelled]" value="1" <?php checked(1, $this->settings->get_option('track_order_cancelled', true)); ?> />
                                    <?php _e('Track when an order is cancelled', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Other Events', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Coupon Events', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_coupon_events]" value="1" <?php checked(1, $this->settings->get_option('track_coupon_events', true)); ?> />
                                    <?php _e('Track coupon application and removal', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Product Searches', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_searches]" value="1" <?php checked(1, $this->settings->get_option('track_product_searches', true)); ?> />
                                    <?php _e('Track product searches', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Product Lists', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[track_product_lists]" value="1" <?php checked(1, $this->settings->get_option('track_product_lists', true)); ?> />
                                    <?php _e('Track product and category list views', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'privacy'): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    ?>
                    
                    <h2><?php _e('Privacy Settings', 'wp-segment-integration'); ?></h2>
                    <p><?php _e('Settings to comply with privacy regulations such as GDPR', 'wp-segment-integration'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Anonymize IP', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[anonymize_ip]" value="1" <?php checked(1, $this->settings->get_option('anonymize_ip', false)); ?> />
                                    <?php _e('Do not send IP addresses to Segment.io', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Respect Do Not Track', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[respect_dnt]" value="1" <?php checked(1, $this->settings->get_option('respect_dnt', true)); ?> />
                                    <?php _e('Do not track users with Do Not Track enabled', 'wp-segment-integration'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Consentimiento de Cookies', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[cookie_consent]" value="1" <?php checked(1, $this->settings->get_option('cookie_consent', false)); ?> />
                                    <?php _e('Only track after obtaining cookie consent', 'wp-segment-integration'); ?>
                                </label>
                                <p class="description"><?php _e('Requires integration with a cookie consent plugin', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Exclusions', 'wp-segment-integration'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Exclude User Roles', 'wp-segment-integration'); ?></th>
                            <td>
                                <?php
                                $excluded_roles = $this->settings->get_option('exclude_user_roles', array());
                                $roles = wp_roles()->roles;
                                foreach ($roles as $role_key => $role) {
                                    $checked = in_array($role_key, $excluded_roles) ? 'checked' : '';
                                    echo '<label><input type="checkbox" name="' . WP_Segment_Settings::OPTION_NAME . '[exclude_user_roles][]" value="' . esc_attr($role_key) . '" ' . $checked . ' /> ' . esc_html($role['name']) . '</label><br>';
                                }
                                ?>
                                <p class="description"><?php _e('Users with these roles will not be tracked', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'advanced'): ?>
            <div class="tab-pane">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wp_segment_settings_group');
                    ?>

                    <h2><?php _e('Advanced Settings', 'wp-segment-integration'); ?></h2>
                    <p><?php _e('Settings for advanced users', 'wp-segment-integration'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Debug Mode', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[debug_mode]" value="1" <?php checked(1, $this->settings->get_option('debug_mode', false)); ?> />
                                    <?php _e('Enable debug logs for troubleshooting', 'wp-segment-integration'); ?>
                                </label>
                                <p class="description"><?php _e('Logs will be saved to the WordPress error log', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Tracking Server-Side', 'wp-segment-integration'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo WP_Segment_Settings::OPTION_NAME; ?>[server_side_tracking]" value="1" <?php checked(1, $this->settings->get_option('server_side_tracking', false)); ?> />
                                    <?php _e('Send events also from the server', 'wp-segment-integration'); ?>
                                </label>
                                <p class="description"><?php _e('Useful for critical events that should not be missed due to ad blockers', 'wp-segment-integration'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>
                </form>
            </div>

        <?php elseif ($active_tab == 'tools'): ?>
            <div class="tab-pane">
                <h2><?php _e('Tools', 'wp-segment-integration'); ?></h2>
                
                <div class="wp-segment-tools">
                    <div class="tool-section">
                        <h3><?php _e('Test Connection', 'wp-segment-integration'); ?></h3>
                        <p><?php _e('Send a test event to Segment.io to verify that the configuration is correct', 'wp-segment-integration'); ?></p>
                        <button type="button" id="test-connection" class="button button-secondary">
                            <?php _e('Test Connection', 'wp-segment-integration'); ?>
                        </button>
                        <div id="connection-result"></div>
                    </div>

                    <div class="tool-section">
                        <h3><?php _e('Export/Import Settings', 'wp-segment-integration'); ?></h3>
                        <p><?php _e('Export or import plugin settings', 'wp-segment-integration'); ?></p>

                        <button type="button" id="export-settings" class="button button-secondary">
                            <?php _e('Export Settings', 'wp-segment-integration'); ?>
                        </button>
                        
                        <div class="import-section" style="margin-top: 20px;">
                            <h4><?php _e('Import Settings', 'wp-segment-integration'); ?></h4>
                            <textarea id="import-settings-data" rows="10" cols="50" placeholder="<?php _e('Paste your JSON settings here...', 'wp-segment-integration'); ?>"></textarea><br>
                            <button type="button" id="import-settings" class="button button-secondary">
                                <?php _e('Import Settings', 'wp-segment-integration'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="tool-section">
                        <h3><?php _e('Reset Settings', 'wp-segment-integration'); ?></h3>
                        <p><?php _e('Reset all settings to their default values', 'wp-segment-integration'); ?></p>
                        <form method="post">
                            <?php wp_nonce_field('wp_segment_admin_action', 'wp_segment_nonce'); ?>
                            <input type="hidden" name="action" value="reset_settings">
                            <button type="submit" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to reset all settings?', 'wp-segment-integration'); ?>')">
                                <?php _e('Reset Settings', 'wp-segment-integration'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.wp-segment-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.wp-segment-connection-status {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.status-connected {
    background-color: #46b450;
}

.status-disconnected {
    background-color: #dc3232;
}

.wp-segment-tools .tool-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.tab-content {
    margin-top: 20px;
}

#connection-result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

#connection-result.success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

#connection-result.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.import-section textarea {
    width: 100%;
    max-width: 600px;
}
</style>

