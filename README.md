# WP Segment Integration #
Contributors: Jesus Correa

Tags: analytics, segment, tracking, ecommerce, woocommerce, wordpress

Requires at least: 5.0

Tested up to: 6.3

Requires PHP: 7.4

Stable tag: 1.0.1

License: GPLv2 or later

License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates Segment.io with WordPress and WooCommerce for advanced analytics tracking and e-commerce events.

## Description ##

WP Segment Integration is a comprehensive plugin that integrates Segment.io with your WordPress site and WooCommerce store. It allows you to automatically track e-commerce events, user behavior, and custom metrics following Segment.io best practices.

### Key Features ###

*   **Complete Segment.io Integration**: Easy setup with your Write Key
*   **E-commerce Tracking**: Comprehensive WooCommerce events according to Segment's V2 specification
*   **WordPress Events**: Login, registration, page views, and more
*   **Privacy Compliance**: Support for GDPR, Do Not Track, and cookie consent
*   **Granular Configuration**: Detailed control over which events to track
*   **Debug Mode**: Debugging tools for development and troubleshooting
*   **Server-Side Tracking**: Option to send events from the server
*   **Export/Import**: Portable settings between sites

### Supported WooCommerce Events ###

**Navigation & Search:**
*   Products Searched
*   Product List Viewed
*   Product List Filtered

**Products:**
*   Product Clicked
*   Product Viewed
*   Product Added (to cart)
*   Product Removed (from cart)

**Cart & Checkout:**
*   Cart Viewed
*   Checkout Started
*   Checkout Step Viewed
*   Checkout Step Completed
*   Payment Info Entered

**Orders:**
*   Order Completed
*   Order Updated
*   Order Refunded
*   Order Cancelled

**Coupons:**
*   Coupon Entered
*   Coupon Applied
*   Coupon Denied
*   Coupon Removed

**Other:**
*   Promotion Viewed
*   Promotion Clicked
*   Product Added to Wishlist
*   Product Removed from Wishlist
*   Product Shared
*   Cart Shared
*   Product Reviewed

### WordPress Events ###

*   User Logged In
*   User Registered
*   Page View
*   Form Submitted
*   External Link Clicked
*   File Downloaded
*   Time on Page

### Requirements ###

*   WordPress 5.0 or higher
*   PHP 7.4 or higher
*   WooCommerce 4.0 or higher (optional, only for e-commerce events)
*   Active Segment.io account

## Installation ##

1.  Upload the plugin files to the `/wp-content/plugins/wp-segment-integration/` directory
2.  Activate the plugin through the 'Plugins' menu in WordPress
3.  Go to Settings > Segment.io to configure the plugin
4.  Enter your Segment.io Write Key
5.  Configure which events you want to track
6.  Done! Tracking will start automatically

### Quick Setup ###

1.  **Get your Write Key**: Go to your Segment.io project and copy the Write Key
2.  **Configure the Plugin**: Paste the Write Key into the plugin settings
3.  **Test Connection**: Use the test tool to verify everything is working
4.  **Customize Events**: Enable or disable events as needed

## Frequently Asked Questions ##

### Do I need a Segment.io account? ###

Yes, you need an active Segment.io account and a valid Write Key to use this plugin.

### Is it compatible with WooCommerce? ###

Yes, the plugin includes full integration with WooCommerce and supports all major e-commerce events according to Segment.io's specification.

### Is it GDPR compliant? ###

Yes, the plugin includes options for GDPR compliance such as IP anonymization, Do Not Track respect, and cookie consent support.

### Can I track custom events? ###

Yes, you can add custom events using the plugin's JavaScript functions or WordPress hooks.

### Does it affect site performance? ###

No, the plugin is optimized for minimal performance impact. Scripts are loaded asynchronously and settings are cached.

### Can I use the plugin without WooCommerce? ###

Yes, the plugin works perfectly without WooCommerce to track general WordPress events.

## Screenshots ##

1.  General configuration panel
2.  WordPress events configuration
3.  WooCommerce events configuration
4.  Privacy and compliance settings
5.  Testing and export tools
6.  Real-time event view (debug mode)

## Changelog ##

### 1.0.1 ###
*   Added compatibility with WooCommerce HPOS (High-Performance Order Storage)
*   Declared compatibility with Custom Order Tables (COT)
*   Improved compatibility with WooCommerce 8.2+
*   Fixed incompatibility warnings

### 1.0.0 ###
*   Initial release
*   Complete Segment.io integration
*   Support for all major WooCommerce events
*   WordPress event tracking
*   Full administration panel
*   Debugging and testing tools
*   GDPR and privacy compliance
*   Complete documentation

## Upgrade Notice ##

### 1.0.1 ###
Important update: Adds compatibility with WooCommerce HPOS. Recommended for all WooCommerce 8.2+ users.

### 1.0.0 ###
Initial plugin release. Install to start tracking events with Segment.io.

## Advanced Configuration ##

### Hooks for Developers ###

The plugin provides several hooks for customization:

**Filters:**
*   `wp_segment_page_data` - Modify page data
*   `wp_segment_product_data` - Modify product data
*   `wp_segment_order_data` - Modify order data
*   `wp_segment_has_cookie_consent` - Check cookie consent

**Actions:**
*   `wp_segment_before_track` - Before sending event
*   `wp_segment_after_track` - After sending event

### Custom Tracking ###

You can add custom tracking using JavaScript:

```javascript
// Track custom event
WPSegment.track(\'Custom Event\', {
    property1: \'value1\',
    property2: \'value2\'
});

// Identify user
WPSegment.identify(userId, {
    email: \'user@example.com\',
    name: \'John Doe\'
});
```

### Programmatic Configuration ###

You can also configure the plugin programmatically:

```php
// Get settings
$settings = wp_segment_integration()->get_settings();

// Track event from PHP
$tracker = wp_segment_integration()->get_tracker();
$tracker->track(\'Server Event\', array(
    \'property\' => \'value\'
));
```

## Support ##

For technical support, bug reports, or feature requests:

*   GitHub: https://github.com/jescorotero/wp-segment-integration
*   Documentation: https://github.com/jescorotero/wp-segment-integration/DOCUMENTATION.md

## Contribute ##

Contributions are welcome! Please:

1.  Fork the repository on GitHub
2.  Create a branch for your feature
3.  Commit your changes
4.  Submit a pull request

## License ##

This plugin is licensed under GPL v2 or later.

## Credits ##

Developed with ❤️ for the WordPress community.

Segment.io is a registered trademark of Segment.io, Inc.

