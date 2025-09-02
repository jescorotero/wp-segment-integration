/**
 * WooCommerce Tracking JavaScript for Segment.io
 */

(function($) {
    'use strict';

    // Check if analytics is available
    if (typeof analytics === 'undefined') {
        console.warn('Segment Analytics not loaded');
        return;
    }

    var WPSegmentWoo = {
        
        init: function() {
            this.bindEvents();
            this.trackCheckoutSteps();
        },

        bindEvents: function() {
            // Product Clicked
            if (wpSegmentWoo.events.productClicked) {
                this.trackProductClicks();
            }

            // Payment Info Entered
            if (wpSegmentWoo.events.paymentInfo) {
                this.trackPaymentInfo();
            }

            // Checkout Step Completed
            if (wpSegmentWoo.events.checkoutSteps) {
                this.trackCheckoutStepCompleted();
            }

            // Product List Filtered
            this.trackProductListFiltered();

            // Promotion Clicked
            this.trackPromotionClicked();
        },

        trackProductClicks: function() {
            $(document).on('click', '.woocommerce-loop-product__link, .woocommerce-LoopProduct-link', function(e) {
                var $product = $(this).closest('.product, .type-product');
                var productData = WPSegmentWoo.getProductDataFromElement($product);
                
                if (productData) {
                    analytics.track('Product Clicked', productData);
                }
            });
        },

        trackPaymentInfo: function() {
            var paymentTracked = false;
            
            $(document).on('change', 'input[name="payment_method"]', function() {
                if (!paymentTracked) {
                    var paymentMethod = $(this).val();
                    var shippingMethod = $('input[name^="shipping_method"]:checked').val() || '';
                    
                    analytics.track('Payment Info Entered', {
                        payment_method: paymentMethod,
                        shipping_method: shippingMethod
                    });
                    
                    paymentTracked = true;
                }
            });
        },

        trackCheckoutSteps: function() {
            // Checkout Step Viewed
            if ($('body').hasClass('woocommerce-checkout')) {
                var step = 1;
                var stepData = {
                    step: step
                };

                // Detect shipping method
                var shippingMethod = $('input[name^="shipping_method"]:checked').val();
                if (shippingMethod) {
                    stepData.shipping_method = shippingMethod;
                }

                // Detect payment method if available
                var paymentMethod = $('input[name="payment_method"]:checked').val();
                if (paymentMethod) {
                    stepData.payment_method = paymentMethod;
                }

                analytics.track('Checkout Step Viewed', stepData);
            }
        },

        trackCheckoutStepCompleted: function() {
            // Track when checkout steps are completed
            $(document).on('change', 'input[name^="shipping_method"]', function() {
                var shippingMethod = $(this).val();
                
                analytics.track('Checkout Step Completed', {
                    step: 1,
                    shipping_method: shippingMethod
                });
            });

            // Track when payment method is selected
            $(document).on('change', 'input[name="payment_method"]', function() {
                var paymentMethod = $(this).val();
                var shippingMethod = $('input[name^="shipping_method"]:checked').val() || '';
                
                analytics.track('Checkout Step Completed', {
                    step: 2,
                    payment_method: paymentMethod,
                    shipping_method: shippingMethod
                });
            });
        },

        trackProductListFiltered: function() {
            // Track product filters
            $(document).on('submit', '.woocommerce-widget-layered-nav form', function() {
                var filters = [];
                var sorts = [];

                // Get active filters
                $('.woocommerce-widget-layered-nav .chosen a').each(function() {
                    var filterText = $(this).text();
                    var filterType = $(this).closest('.woocommerce-widget-layered-nav').find('.widget-title').text();
                    
                    filters.push({
                        type: filterType.toLowerCase().replace(/\s+/g, '_'),
                        value: filterText
                    });
                });

                // Get sorting
                var orderby = $('.woocommerce-ordering select').val();
                if (orderby) {
                    sorts.push({
                        type: 'price',
                        value: orderby.includes('desc') ? 'desc' : 'asc'
                    });
                }

                if (filters.length > 0 || sorts.length > 0) {
                    var filterData = {
                        filters: filters,
                        sorts: sorts
                    };

                    // Aggregate category information if available
                    if ($('body').hasClass('tax-product_cat')) {
                        filterData.category = $('.woocommerce-products-header__title').text();
                    }

                    analytics.track('Product List Filtered', filterData);
                }
            });

            // Track sorting changes
            $(document).on('change', '.woocommerce-ordering select', function() {
                var orderby = $(this).val();
                var sorts = [{
                    type: 'price',
                    value: orderby.includes('desc') ? 'desc' : 'asc'
                }];

                analytics.track('Product List Filtered', {
                    sorts: sorts
                });
            });
        },

        trackPromotionClicked: function() {
            // Track promotion clicks
            $(document).on('click', '.promotion-banner, .sale-banner, [class*="promo"]', function() {
                var $promo = $(this);
                var promoData = {
                    promotion_id: $promo.attr('id') || 'unknown',
                    creative: $promo.attr('class') || '',
                    name: $promo.find('h1, h2, h3, .title').first().text() || 'Promotion',
                    position: $promo.attr('data-position') || 'unknown'
                };

                analytics.track('Promotion Clicked', promoData);
            });
        },

        getProductDataFromElement: function($product) {
            var productData = {};

            // Product ID
            var productId = $product.attr('data-product-id') || $product.find('[data-product-id]').attr('data-product-id');
            if (productId) {
                productData.product_id = productId;
            }

            // Product Name
            var productName = $product.find('.woocommerce-loop-product__title, .product-title, h2, h3').first().text().trim();
            if (productName) {
                productData.name = productName;
            }

            // Product Price
            var price = $product.find('.price .amount, .price').first().text().replace(/[^\d.,]/g, '');
            if (price) {
                productData.price = parseFloat(price.replace(',', '.'));
            }

            // Product URL
            var productUrl = $product.find('a').first().attr('href');
            if (productUrl) {
                productData.url = productUrl;
            }

            // Product Category
            var category = $product.find('.product-category').text().trim();
            if (category) {
                productData.category = category;
            }

            // SKU if available
            var sku = $product.attr('data-sku') || $product.find('[data-sku]').attr('data-sku');
            if (sku) {
                productData.sku = sku;
            }

            // Product Image
            var imageUrl = $product.find('img').first().attr('src');
            if (imageUrl) {
                productData.image_url = imageUrl;
            }

            return Object.keys(productData).length > 0 ? productData : null;
        },

        // Helper function to send events via AJAX
        sendEvent: function(event, properties) {
            $.ajax({
                url: wpSegmentWoo.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_segment_track_event',
                    event: event,
                    properties: properties,
                    nonce: wpSegmentWoo.nonce
                },
                success: function(response) {
                    if (wpSegment.debug) {
                        console.log('Event sent successfully:', event, properties);
                    }
                },
                error: function(xhr, status, error) {
                    if (wpSegment.debug) {
                        console.error('Error sending event:', error);
                    }
                }
            });
        }
    };

    // Initialize when the document is ready
    $(document).ready(function() {
        WPSegmentWoo.init();
    });

    // Make available globally for external use
    window.WPSegmentWoo = WPSegmentWoo;

})(jQuery);

