<?php
/**
 * Plugin Name: WP Segment Integration
 * Plugin URI: https://github.com/jescorotero/wp-segment-integration
 * Description: Integra Segment.io con WordPress y WooCommerce para tracking avanzado de analytics y eventos de e-commerce.
 * Version: 1.0.1
 * Author: Jesus Correa
 * Author URI: https://github.com/jescorotero/wp-segment-integration
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-segment-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevenir el acceso directo - Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Declarar compatibilidad con WooCommerce HPOS - Declare compatibility with WooCommerce HPOS
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Definir constantes del plugin - Define plugin constants
define('WP_SEGMENT_VERSION', '1.0.1');
define('WP_SEGMENT_PLUGIN_FILE', __FILE__);
define('WP_SEGMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_SEGMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_SEGMENT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin WP Segment Integration - Main plugin class for WP Segment Integration
 */
class WP_Segment_Integration {

    /**
     * Instancia única del plugin - Singleton
     */
    private static $instance = null;

    /**
     * Configuraciones del plugin - Settings
     */
    private $settings;

    /**
     * Tracker de Segment - Tracking
     */
    private $tracker;

    /**
     * Integración con WooCommerce - WooCommerce Integration
     */
    private $woocommerce;

    /**
     * Admin del plugin - Plugin Admin
     */
    private $admin;

    /**
     * Constructor privado para patrón singleton - Private constructor for singleton pattern
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Obtener instancia única del plugin - Get unique plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializar el plugin - Initialize the plugin
     */
    private function init() {
        // Cargar archivos de clases - Load class files
        $this->load_dependencies();

        // Inicializar componentes - Initialize components
        $this->init_components();

        // Registrar hooks - Register hooks
        $this->register_hooks();

        // Hook de activación - Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Hook de desactivación - Deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Cargar dependencias del plugin - Load plugin dependencies
     */
    private function load_dependencies() {
        require_once WP_SEGMENT_PLUGIN_DIR . 'includes/class-wp-segment-settings.php';
        require_once WP_SEGMENT_PLUGIN_DIR . 'includes/class-wp-segment-tracker.php';
        require_once WP_SEGMENT_PLUGIN_DIR . 'includes/class-wp-segment-admin.php';

        // Cargar integración de WooCommerce solo si está activo - Load WooCommerce integration only if active
        if (class_exists('WooCommerce')) {
            require_once WP_SEGMENT_PLUGIN_DIR . 'includes/class-wp-segment-woocommerce.php';
        }
    }

    /**
     * Inicializar componentes del plugin - Initialize plugin components
     */
    private function init_components() {
        $this->settings = new WP_Segment_Settings();
        $this->tracker = new WP_Segment_Tracker($this->settings);
        
        if (is_admin()) {
            $this->admin = new WP_Segment_Admin($this->settings);
        }

        if (class_exists('WooCommerce')) {
            $this->woocommerce = new WP_Segment_WooCommerce($this->tracker, $this->settings);
        }
    }

    /**
     * Registrar hooks de WordPress - Register WordPress hooks
     */
    private function register_hooks() {
        // Hook de inicialización - Initialization hook
        add_action('init', array($this, 'init_plugin'));

        // Hook para cargar textdomain - Hook to load textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Hook para scripts y estilos del frontend - Hook for frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));

        // Hook para insertar el script de Segment en el head - Hook to insert Segment script in head
        add_action('wp_head', array($this, 'insert_segment_script'), 1);

        // Hooks para tracking de eventos básicos de WordPress - Hooks for basic WordPress event tracking
        add_action('wp_login', array($this, 'track_user_login'), 10, 2);
        add_action('user_register', array($this, 'track_user_registration'));
    }

    /**
     * Inicializar plugin después de que WordPress esté cargado - Initialize plugin after WordPress is loaded
     */
    public function init_plugin() {
        // Verificar si Segment está configurado - Check if Segment is configured
        if (!$this->settings->is_configured()) {
            return;
        }

        // Inicializar tracking - Initialize tracking
        $this->tracker->init();
    }

    /**
     * Cargar textdomain para traducciones - Load textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-segment-integration',
            false,
            dirname(WP_SEGMENT_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Encolar assets del frontend - Enqueue frontend assets
     */
    public function enqueue_public_assets() {
        if (!$this->settings->is_configured() || !$this->settings->get_option('enabled', true)) {
            return;
        }

        wp_enqueue_script(
            'wp-segment-public',
            WP_SEGMENT_PLUGIN_URL . 'public/js/public-scripts.js',
            array('jquery'),
            WP_SEGMENT_VERSION,
            true
        );

        // Pasar configuraciones al JavaScript - Pass settings to JavaScript
        wp_localize_script('wp-segment-public', 'wpSegment', array(
            'writeKey' => $this->settings->get_option('write_key'),
            'debug' => $this->settings->get_option('debug_mode', false),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_segment_nonce')
        ));

        wp_enqueue_style(
            'wp-segment-public',
            WP_SEGMENT_PLUGIN_URL . 'public/css/public-styles.css',
            array(),
            WP_SEGMENT_VERSION
        );
    }

    /**
     * Insertar script de Segment.io en el head - Insert Segment.io script in head
     */
    public function insert_segment_script() {
        if (!$this->settings->is_configured() || !$this->settings->get_option('enabled', true)) {
            return;
        }

        $write_key = $this->settings->get_option('write_key');
        if (empty($write_key)) {
            return;
        }

        $debug_mode = $this->settings->get_option('debug_mode', false);
        ?>
        <script type="text/javascript">
        !function(){var analytics=window.analytics=window.analytics||[];if(!analytics.initialize)if(analytics.invoked)window.console&&console.error&&console.error("Segment snippet included twice.");else{analytics.invoked=!0;analytics.methods=["trackSubmit","trackClick","trackLink","trackForm","pageview","identify","reset","group","track","ready","alias","debug","page","once","off","on","addSourceMiddleware","addIntegrationMiddleware","setAnonymousId","addDestinationMiddleware"];analytics.factory=function(e){return function(){var t=Array.prototype.slice.call(arguments);t.unshift(e);analytics.push(t);return analytics}};for(var e=0;e<analytics.methods.length;e++){var key=analytics.methods[e];analytics[key]=analytics.factory(key)}analytics.load=function(key,e){var t=document.createElement("script");t.type="text/javascript";t.async=!0;t.src="https://cdn.segment.com/analytics.js/v1/" + key + "/analytics.min.js";var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(t,n);analytics._loadOptions=e};analytics.SNIPPET_VERSION="4.13.1";
        analytics.load("<?php echo esc_js($write_key); ?>");
        <?php if ($debug_mode): ?>
        analytics.debug(true);
        <?php endif; ?>
        analytics.page();
        }}();
        </script>
        <?php
    }

    /**
     * Trackear login de usuario - Track user login
     */
    public function track_user_login($user_login, $user) {
        if (!$this->settings->get_option('track_user_events', true)) {
            return;
        }

        $this->tracker->identify($user->ID, array(
            'email' => $user->user_email,
            'username' => $user->user_login,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
        ));

        $this->tracker->track('User Logged In', array(
            'userId' => $user->ID,
            'username' => $user->user_login,
        ));
    }

    /**
     * Trackear registro de usuario - Track user registration
     */
    public function track_user_registration($user_id) {
        if (!$this->settings->get_option('track_user_events', true)) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $this->tracker->identify($user->ID, array(
            'email' => $user->user_email,
            'username' => $user->user_login,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
        ));

        $this->tracker->track('User Registered', array(
            'userId' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
        ));
    }

    /**
     * Activar plugin - Activate plugin
     */
    public function activate() {
        // Crear opciones por defecto - Create default options
        $this->settings->create_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desactivar plugin - Deactivate plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Obtener instancia de settings - Get settings instance
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Obtener instancia de tracker - Get tracker instance
     */
    public function get_tracker() {
        return $this->tracker;
    }

    /**
     * Obtener instancia de WooCommerce integration - Get WooCommerce integration instance
     */
    public function get_woocommerce() {
        return $this->woocommerce;
    }
}

/**
 * Función para obtener la instancia principal del plugin - Get main plugin instance
 */
function wp_segment_integration() {
    return WP_Segment_Integration::get_instance();
}

// Inicializar el plugin - Initialize the plugin
wp_segment_integration();

