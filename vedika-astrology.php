<?php
/**
 * Plugin Name: Vedika Astrology
 * Plugin URI: https://vedika.io/wordpress-plugin
 * Description: Add astrology, tarot, numerology, and horoscope features to your WordPress site. Powered by the Vedika API with 516+ endpoints.
 * Version: 1.0.0
 * Author: Vedika Intelligence
 * Author URI: https://vedika.io
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vedika-astrology
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VEDIKA_ASTROLOGY_VERSION', '1.0.0' );
define( 'VEDIKA_ASTROLOGY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VEDIKA_ASTROLOGY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VEDIKA_ASTROLOGY_BASENAME', plugin_basename( __FILE__ ) );
define( 'VEDIKA_API_BASE_URL', 'https://api.vedika.io' );
define( 'VEDIKA_SANDBOX_BASE', 'https://api.vedika.io/sandbox' );

/**
 * Main plugin class.
 */
final class Vedika_Astrology {

    /**
     * Singleton instance.
     *
     * @var Vedika_Astrology|null
     */
    private static $instance = null;

    /**
     * Return the singleton.
     *
     * @return Vedika_Astrology
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor -- hooks everything.
     */
    private function __construct() {
        $this->load_includes();
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        add_filter( 'plugin_action_links_' . VEDIKA_ASTROLOGY_BASENAME, array( $this, 'add_settings_link' ) );
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton.' );
    }

    /**
     * Load include files.
     */
    private function load_includes() {
        require_once VEDIKA_ASTROLOGY_PLUGIN_DIR . 'includes/class-vedika-api.php';
        require_once VEDIKA_ASTROLOGY_PLUGIN_DIR . 'includes/class-vedika-shortcodes.php';
        require_once VEDIKA_ASTROLOGY_PLUGIN_DIR . 'includes/class-vedika-widgets.php';
        require_once VEDIKA_ASTROLOGY_PLUGIN_DIR . 'includes/class-vedika-admin.php';
    }

    /**
     * Load translation files.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'vedika-astrology',
            false,
            dirname( VEDIKA_ASTROLOGY_BASENAME ) . '/languages/'
        );
    }

    /**
     * Enqueue frontend CSS and JS.
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'vedika-frontend',
            VEDIKA_ASTROLOGY_PLUGIN_URL . 'assets/css/vedika-frontend.css',
            array(),
            VEDIKA_ASTROLOGY_VERSION
        );

        wp_enqueue_script(
            'vedika-frontend',
            VEDIKA_ASTROLOGY_PLUGIN_URL . 'assets/js/vedika-frontend.js',
            array(),
            VEDIKA_ASTROLOGY_VERSION,
            true
        );

        wp_localize_script( 'vedika-frontend', 'vedikaAstrology', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'vedika_astrology_nonce' ),
            'theme'   => get_option( 'vedika_theme', 'light' ),
        ) );
    }

    /**
     * Register Gutenberg blocks.
     */
    public function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        register_block_type( VEDIKA_ASTROLOGY_PLUGIN_DIR . 'blocks/horoscope' );
        register_block_type( VEDIKA_ASTROLOGY_PLUGIN_DIR . 'blocks/tarot' );
    }

    /**
     * Register sidebar widgets.
     */
    public function register_widgets() {
        register_widget( 'Vedika_Horoscope_Widget' );
        register_widget( 'Vedika_Tarot_Widget' );
        register_widget( 'Vedika_Panchang_Widget' );
    }

    /**
     * Add a "Settings" link on the Plugins page.
     *
     * @param array $links Existing action links.
     * @return array
     */
    public function add_settings_link( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'options-general.php?page=vedika-api' ),
            esc_html__( 'Settings', 'vedika-astrology' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }
}

/**
 * Activation hook.
 */
function vedika_astrology_activate() {
    // Set default options.
    add_option( 'vedika_api_key', '' );
    add_option( 'vedika_default_language', 'en' );
    add_option( 'vedika_cache_duration', 3600 );
    add_option( 'vedika_theme', 'light' );

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vedika_astrology_activate' );

/**
 * Deactivation hook.
 */
function vedika_astrology_deactivate() {
    // Clean up transients.
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vedika_%' OR option_name LIKE '_transient_timeout_vedika_%'"
    );

    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'vedika_astrology_deactivate' );

/**
 * AJAX handlers for frontend forms.
 */
add_action( 'wp_ajax_vedika_birth_chart', 'vedika_ajax_birth_chart' );
add_action( 'wp_ajax_nopriv_vedika_birth_chart', 'vedika_ajax_birth_chart' );

function vedika_ajax_birth_chart() {
    check_ajax_referer( 'vedika_astrology_nonce', 'nonce' );

    $datetime = sanitize_text_field( $_POST['datetime'] ?? '' );
    $lat      = floatval( $_POST['lat'] ?? 0 );
    $lng      = floatval( $_POST['lng'] ?? 0 );
    $tz       = floatval( $_POST['tz'] ?? 5.5 );

    if ( empty( $datetime ) || 0 === $lat ) {
        wp_send_json_error( array( 'message' => __( 'Please provide date/time and location.', 'vedika-astrology' ) ) );
    }

    $api    = Vedika_API::instance();
    $result = $api->get_birth_chart( $datetime, $lat, $lng, $tz );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success( $result );
}

add_action( 'wp_ajax_vedika_compatibility', 'vedika_ajax_compatibility' );
add_action( 'wp_ajax_nopriv_vedika_compatibility', 'vedika_ajax_compatibility' );

function vedika_ajax_compatibility() {
    check_ajax_referer( 'vedika_astrology_nonce', 'nonce' );

    $data1 = array(
        'name'      => sanitize_text_field( $_POST['name1'] ?? '' ),
        'datetime'  => sanitize_text_field( $_POST['datetime1'] ?? '' ),
        'latitude'  => floatval( $_POST['lat1'] ?? 0 ),
        'longitude' => floatval( $_POST['lng1'] ?? 0 ),
    );

    $data2 = array(
        'name'      => sanitize_text_field( $_POST['name2'] ?? '' ),
        'datetime'  => sanitize_text_field( $_POST['datetime2'] ?? '' ),
        'latitude'  => floatval( $_POST['lat2'] ?? 0 ),
        'longitude' => floatval( $_POST['lng2'] ?? 0 ),
    );

    if ( empty( $data1['name'] ) || empty( $data2['name'] ) ) {
        wp_send_json_error( array( 'message' => __( 'Please provide both profiles.', 'vedika-astrology' ) ) );
    }

    $api    = Vedika_API::instance();
    $result = $api->get_compatibility( $data1, $data2 );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success( $result );
}

add_action( 'wp_ajax_vedika_numerology', 'vedika_ajax_numerology' );
add_action( 'wp_ajax_nopriv_vedika_numerology', 'vedika_ajax_numerology' );

function vedika_ajax_numerology() {
    check_ajax_referer( 'vedika_astrology_nonce', 'nonce' );

    $name      = sanitize_text_field( $_POST['name'] ?? '' );
    $birthdate = sanitize_text_field( $_POST['birthdate'] ?? '' );

    if ( empty( $name ) || empty( $birthdate ) ) {
        wp_send_json_error( array( 'message' => __( 'Please provide name and birth date.', 'vedika-astrology' ) ) );
    }

    $api    = Vedika_API::instance();
    $result = $api->get_numerology( $name, $birthdate );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success( $result );
}

// Boot the plugin.
Vedika_Astrology::instance();
