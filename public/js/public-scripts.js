/**
 * Public Scripts for the WP Segment Integration Plugin
 */

(function($) {
    'use strict';

    var WPSegment = {
        
        init: function() {
            this.bindEvents();
            this.trackPageView();
        },

        bindEvents: function() {            
            // Track form submissions if enabled
            if (wpSegment.trackForms) {
                this.trackFormSubmissions();
            }
            
            // Track external link clicks
            this.trackExternalLinks();

            // Track file downloads
            this.trackFileDownloads();

            // Track time on page
            this.trackTimeOnPage();
        },

        trackPageView: function() {
            
            // PageView is tracked automatically by the Segment snippet            
            // Here we can add additional properties if needed
            // Ex: analytics.page({ title: document.title });
            if (typeof analytics !== 'undefined') {
                var pageData = this.getPageData();
                if (Object.keys(pageData).length > 0) {
                    analytics.page(pageData);
                }
            }
        },

        trackFormSubmissions: function() {
            $(document).on('submit', 'form:not(.woocommerce-form)', function(e) {
                var $form = $(this);
                var formData = {
                    form_id: $form.attr('id') || 'unknown',
                    form_class: $form.attr('class') || '',
                    form_action: $form.attr('action') || window.location.href,
                    form_method: $form.attr('method') || 'get',
                    form_type: WPSegment.getFormType($form)
                };
                
                // Add specific fields based on form type
                if (formData.form_type === 'contact') {
                    var email = $form.find('input[type="email"], input[name*="email"]').val();
                    if (email) {
                        formData.email = email;
                    }
                }

                if (typeof analytics !== 'undefined') {
                    analytics.track('Form Submitted', formData);
                }
            });
        },

        trackExternalLinks: function() {
            $(document).on('click', 'a[href^="http"]:not([href*="' + window.location.hostname + '"])', function(e) {
                var $link = $(this);
                var linkData = {
                    url: $link.attr('href'),
                    text: $link.text().trim(),
                    position: $link.attr('data-position') || 'unknown'
                };

                if (typeof analytics !== 'undefined') {
                    analytics.track('External Link Clicked', linkData);
                }
            });
        },

        trackFileDownloads: function() {
            var downloadExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'mp3', 'mp4', 'avi'];
            var extensionPattern = new RegExp('\\.(' + downloadExtensions.join('|') + ')$', 'i');

            $(document).on('click', 'a[href]', function(e) {
                var $link = $(this);
                var href = $link.attr('href');
                
                if (extensionPattern.test(href)) {
                    var fileName = href.split('/').pop();
                    var fileExtension = fileName.split('.').pop().toLowerCase();
                    
                    var downloadData = {
                        file_name: fileName,
                        file_extension: fileExtension,
                        file_url: href,
                        link_text: $link.text().trim()
                    };

                    if (typeof analytics !== 'undefined') {
                        analytics.track('File Downloaded', downloadData);
                    }
                }
            });
        },

        trackTimeOnPage: function() {
            var startTime = Date.now();
            var tracked = false;

            // Track time after 30 seconds
            setTimeout(function() {
                if (!tracked) {
                    var timeSpent = Math.round((Date.now() - startTime) / 1000);
                    
                    if (typeof analytics !== 'undefined') {
                        analytics.track('Time on Page', {
                            seconds: timeSpent,
                            page_url: window.location.href,
                            page_title: document.title
                        });
                    }
                    tracked = true;
                }
            }, 30000);

            // Track time on page before unload
            $(window).on('beforeunload', function() {
                if (!tracked) {
                    var timeSpent = Math.round((Date.now() - startTime) / 1000);
                    
                    if (typeof analytics !== 'undefined' && timeSpent > 5) {
                        analytics.track('Time on Page', {
                            seconds: timeSpent,
                            page_url: window.location.href,
                            page_title: document.title
                        });
                    }
                }
            });
        },

        getPageData: function() {
            var data = {};

            // Add WP information
            if (typeof wp !== 'undefined') {
                data.cms = 'WordPress';
            }

            // Add page information
            var bodyClasses = $('body').attr('class');
            if (bodyClasses) {
                data.page_type = this.getPageType(bodyClasses);
            }

            // Add author information if available
            var author = $('meta[name="author"]').attr('content');
            if (author) {
                data.author = author;
            }

            // Add category information if available
            var categories = $('meta[name="article:section"]').attr('content');
            if (categories) {
                data.category = categories;
            }

            return data;
        },

        getPageType: function(bodyClasses) {
            if (bodyClasses.includes('home')) return 'home';
            if (bodyClasses.includes('single-post')) return 'blog_post';
            if (bodyClasses.includes('page')) return 'page';
            if (bodyClasses.includes('category')) return 'category';
            if (bodyClasses.includes('tag')) return 'tag';
            if (bodyClasses.includes('search')) return 'search';
            if (bodyClasses.includes('404')) return '404';
            if (bodyClasses.includes('woocommerce')) return 'ecommerce';
            return 'unknown';
        },

        getFormType: function($form) {
            var formClasses = $form.attr('class') || '';
            var formId = $form.attr('id') || '';
            
            if (formClasses.includes('contact') || formId.includes('contact')) return 'contact';
            if (formClasses.includes('newsletter') || formId.includes('newsletter')) return 'newsletter';
            if (formClasses.includes('search') || formId.includes('search')) return 'search';
            if (formClasses.includes('comment') || formId.includes('comment')) return 'comment';
            if (formClasses.includes('login') || formId.includes('login')) return 'login';
            if (formClasses.includes('register') || formId.includes('register')) return 'register';
            
            return 'unknown';
        },

        // Public function to track events
        track: function(event, properties) {
            if (typeof analytics !== 'undefined') {
                analytics.track(event, properties || {});
            }
        },

        // Public function to identify users
        identify: function(userId, traits) {
            if (typeof analytics !== 'undefined') {
                analytics.identify(userId, traits || {});
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WPSegment.init();
    });

    // Make available globally for external use
    window.WPSegment = WPSegment;

})(jQuery);

