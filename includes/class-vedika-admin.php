<?php
/**
 * Vedika Admin Settings page.
 *
 * Adds a settings page at Settings > Vedika API for configuring
 * the API key, language, cache duration, and theme.
 *
 * @package Vedika_Astrology
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vedika_Admin {

    /** @var Vedika_Admin|null */
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Register the settings page under Settings.
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Vedika API Settings', 'vedika-astrology' ),
            __( 'Vedika API', 'vedika-astrology' ),
            'manage_options',
            'vedika-api',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin CSS on the settings page only.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_vedika-api' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'vedika-admin',
            VEDIKA_ASTROLOGY_PLUGIN_URL . 'assets/css/vedika-frontend.css',
            array(),
            VEDIKA_ASTROLOGY_VERSION
        );
    }

    /**
     * Register settings, sections, and fields.
     */
    public function register_settings() {
        // API Key.
        register_setting( 'vedika_settings', 'vedika_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_api_key' ),
        ) );

        // Default Language.
        register_setting( 'vedika_settings', 'vedika_default_language', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'en',
        ) );

        // Cache Duration.
        register_setting( 'vedika_settings', 'vedika_cache_duration', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 3600,
        ) );

        // Theme.
        register_setting( 'vedika_settings', 'vedika_theme', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'light',
        ) );

        // ---- Section: API Configuration ----
        add_settings_section(
            'vedika_api_section',
            __( 'API Configuration', 'vedika-astrology' ),
            array( $this, 'render_api_section' ),
            'vedika-api'
        );

        add_settings_field( 'vedika_api_key', __( 'API Key', 'vedika-astrology' ), array( $this, 'render_api_key_field' ), 'vedika-api', 'vedika_api_section' );
        add_settings_field( 'vedika_default_language', __( 'Default Language', 'vedika-astrology' ), array( $this, 'render_language_field' ), 'vedika-api', 'vedika_api_section' );

        // ---- Section: Display Settings ----
        add_settings_section(
            'vedika_display_section',
            __( 'Display Settings', 'vedika-astrology' ),
            null,
            'vedika-api'
        );

        add_settings_field( 'vedika_cache_duration', __( 'Cache Duration', 'vedika-astrology' ), array( $this, 'render_cache_field' ), 'vedika-api', 'vedika_display_section' );
        add_settings_field( 'vedika_theme', __( 'Theme', 'vedika-astrology' ), array( $this, 'render_theme_field' ), 'vedika-api', 'vedika_display_section' );
    }

    /**
     * Sanitize and validate the API key on save.
     *
     * @param string $value Submitted API key.
     * @return string
     */
    public function sanitize_api_key( $value ) {
        $value = sanitize_text_field( $value );

        if ( empty( $value ) ) {
            return '';
        }

        // Must start with vk_.
        if ( 0 !== strpos( $value, 'vk_' ) ) {
            add_settings_error(
                'vedika_api_key',
                'invalid_format',
                __( 'Invalid API key format. Keys start with vk_live_ or vk_ent_.', 'vedika-astrology' ),
                'error'
            );
            return get_option( 'vedika_api_key', '' );
        }

        // Validate against the API.
        $api   = Vedika_API::instance();
        $valid = $api->validate_api_key( $value );

        if ( is_wp_error( $valid ) ) {
            add_settings_error(
                'vedika_api_key',
                'connection_error',
                sprintf(
                    /* translators: %s: error message */
                    __( 'Could not validate API key: %s', 'vedika-astrology' ),
                    $valid->get_error_message()
                ),
                'error'
            );
            return get_option( 'vedika_api_key', '' );
        }

        if ( ! $valid ) {
            add_settings_error(
                'vedika_api_key',
                'invalid_key',
                __( 'API key validation failed. Please check that your key is active.', 'vedika-astrology' ),
                'error'
            );
            return get_option( 'vedika_api_key', '' );
        }

        add_settings_error(
            'vedika_api_key',
            'key_valid',
            __( 'API key validated successfully.', 'vedika-astrology' ),
            'success'
        );

        return $value;
    }

    // -------------------------------------------------------------------------
    // Renderers
    // -------------------------------------------------------------------------

    public function render_api_section() {
        $has_key = ! empty( get_option( 'vedika_api_key', '' ) );
        echo '<p>';
        if ( $has_key ) {
            echo '<span style="color:#46b450;">&#10003;</span> ';
            esc_html_e( 'Connected to Vedika API (production endpoints).', 'vedika-astrology' );
        } else {
            esc_html_e( 'No API key configured. The plugin will use free sandbox endpoints with sample data.', 'vedika-astrology' );
            echo ' <a href="https://vedika.io/pricing" target="_blank" rel="noopener">';
            esc_html_e( 'Get an API key', 'vedika-astrology' );
            echo '</a>';
        }
        echo '</p>';
    }

    public function render_api_key_field() {
        $value = get_option( 'vedika_api_key', '' );
        $masked = $value ? substr( $value, 0, 8 ) . str_repeat( '*', max( 0, strlen( $value ) - 12 ) ) . substr( $value, -4 ) : '';
        ?>
        <input type="password"
               id="vedika_api_key"
               name="vedika_api_key"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text"
               placeholder="vk_live_xxxxxxxxxxxx"
               autocomplete="off" />
        <?php if ( $masked ) : ?>
            <p class="description"><?php printf( esc_html__( 'Current key: %s', 'vedika-astrology' ), '<code>' . esc_html( $masked ) . '</code>' ); ?></p>
        <?php endif; ?>
        <p class="description">
            <?php esc_html_e( 'Enter your Vedika API key. Leave blank to use sandbox (free, sample data).', 'vedika-astrology' ); ?>
        </p>
        <?php
    }

    public function render_language_field() {
        $value = get_option( 'vedika_default_language', 'en' );
        $languages = array(
            'en' => 'English',
            'hi' => 'Hindi',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'kn' => 'Kannada',
            'ml' => 'Malayalam',
            'mr' => 'Marathi',
            'gu' => 'Gujarati',
            'bn' => 'Bengali',
            'pa' => 'Punjabi',
            'or' => 'Odia',
            'as' => 'Assamese',
            'ur' => 'Urdu',
            'ne' => 'Nepali',
            'sa' => 'Sanskrit',
        );
        ?>
        <select id="vedika_default_language" name="vedika_default_language">
            <?php foreach ( $languages as $code => $label ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $value, $code ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Default language for API responses. Individual shortcodes can override this.', 'vedika-astrology' ); ?></p>
        <?php
    }

    public function render_cache_field() {
        $value = get_option( 'vedika_cache_duration', 3600 );
        $options = array(
            900   => __( '15 minutes', 'vedika-astrology' ),
            1800  => __( '30 minutes', 'vedika-astrology' ),
            3600  => __( '1 hour', 'vedika-astrology' ),
            7200  => __( '2 hours', 'vedika-astrology' ),
            21600 => __( '6 hours', 'vedika-astrology' ),
            43200 => __( '12 hours', 'vedika-astrology' ),
            86400 => __( '24 hours', 'vedika-astrology' ),
        );
        ?>
        <select id="vedika_cache_duration" name="vedika_cache_duration">
            <?php foreach ( $options as $seconds => $label ) : ?>
                <option value="<?php echo esc_attr( $seconds ); ?>" <?php selected( (int) $value, $seconds ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'How long to cache API responses. Longer durations reduce API calls and improve performance.', 'vedika-astrology' ); ?></p>
        <?php
    }

    public function render_theme_field() {
        $value = get_option( 'vedika_theme', 'light' );
        ?>
        <fieldset>
            <label>
                <input type="radio" name="vedika_theme" value="light" <?php checked( $value, 'light' ); ?> />
                <?php esc_html_e( 'Light', 'vedika-astrology' ); ?>
            </label>
            <br>
            <label>
                <input type="radio" name="vedika_theme" value="dark" <?php checked( $value, 'dark' ); ?> />
                <?php esc_html_e( 'Dark', 'vedika-astrology' ); ?>
            </label>
        </fieldset>
        <p class="description"><?php esc_html_e( 'Choose the visual theme for Vedika widgets and shortcodes.', 'vedika-astrology' ); ?></p>
        <?php
    }

    /**
     * Render the full settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'vedika_settings' );
                do_settings_sections( 'vedika-api' );
                submit_button();
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Quick Reference', 'vedika-astrology' ); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Shortcode', 'vedika-astrology' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'vedika-astrology' ); ?></th>
                        <th><?php esc_html_e( 'Parameters', 'vedika-astrology' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[vedika_horoscope]</code></td>
                        <td><?php esc_html_e( 'Daily horoscope for a sign', 'vedika-astrology' ); ?></td>
                        <td><code>sign</code>, <code>lang</code>, <code>period</code> (daily/weekly/monthly)</td>
                    </tr>
                    <tr>
                        <td><code>[vedika_horoscope_all]</code></td>
                        <td><?php esc_html_e( 'All 12 signs with tabs', 'vedika-astrology' ); ?></td>
                        <td><code>lang</code>, <code>period</code></td>
                    </tr>
                    <tr>
                        <td><code>[vedika_tarot]</code></td>
                        <td><?php esc_html_e( 'Tarot card of the day', 'vedika-astrology' ); ?></td>
                        <td><?php esc_html_e( 'None', 'vedika-astrology' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[vedika_panchang]</code></td>
                        <td><?php esc_html_e( "Today's panchang", 'vedika-astrology' ); ?></td>
                        <td><code>date</code>, <code>lat</code>, <code>lng</code></td>
                    </tr>
                    <tr>
                        <td><code>[vedika_birth_chart]</code></td>
                        <td><?php esc_html_e( 'Birth chart form and display', 'vedika-astrology' ); ?></td>
                        <td><?php esc_html_e( 'None (interactive form)', 'vedika-astrology' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[vedika_compatibility]</code></td>
                        <td><?php esc_html_e( 'Compatibility checker', 'vedika-astrology' ); ?></td>
                        <td><?php esc_html_e( 'None (interactive form)', 'vedika-astrology' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[vedika_numerology]</code></td>
                        <td><?php esc_html_e( 'Numerology calculator', 'vedika-astrology' ); ?></td>
                        <td><?php esc_html_e( 'None (interactive form)', 'vedika-astrology' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Gutenberg Blocks', 'vedika-astrology' ); ?></h2>
            <p><?php esc_html_e( 'Search for "Vedika" in the block editor to find the Horoscope and Tarot blocks.', 'vedika-astrology' ); ?></p>

            <h2><?php esc_html_e( 'Widgets', 'vedika-astrology' ); ?></h2>
            <p>
                <?php
                printf(
                    /* translators: %s: link to Widgets page */
                    esc_html__( 'Add Vedika widgets from %s. Available: Daily Horoscope, Tarot Card of the Day, Panchang.', 'vedika-astrology' ),
                    '<a href="' . esc_url( admin_url( 'widgets.php' ) ) . '">' . esc_html__( 'Appearance > Widgets', 'vedika-astrology' ) . '</a>'
                );
                ?>
            </p>

            <?php $this->render_usage_stats(); ?>
        </div>
        <?php
    }

    /**
     * Render usage statistics section (only when API key is set).
     */
    private function render_usage_stats() {
        $api_key = get_option( 'vedika_api_key', '' );
        if ( empty( $api_key ) ) {
            return;
        }

        $api   = Vedika_API::instance();
        $stats = $api->get_usage_stats();
        if ( is_wp_error( $stats ) ) {
            return;
        }
        ?>
        <hr />
        <h2><?php esc_html_e( 'API Usage', 'vedika-astrology' ); ?></h2>
        <table class="widefat striped">
            <tbody>
                <?php if ( isset( $stats['totalQueries'] ) ) : ?>
                <tr><th><?php esc_html_e( 'Total Queries', 'vedika-astrology' ); ?></th><td><?php echo esc_html( number_format( $stats['totalQueries'] ) ); ?></td></tr>
                <?php endif; ?>
                <?php if ( isset( $stats['queriesThisMonth'] ) ) : ?>
                <tr><th><?php esc_html_e( 'This Month', 'vedika-astrology' ); ?></th><td><?php echo esc_html( number_format( $stats['queriesThisMonth'] ) ); ?></td></tr>
                <?php endif; ?>
                <?php if ( isset( $stats['walletBalance'] ) ) : ?>
                <tr><th><?php esc_html_e( 'Wallet Balance', 'vedika-astrology' ); ?></th><td>$<?php echo esc_html( number_format( $stats['walletBalance'] / 100, 2 ) ); ?></td></tr>
                <?php endif; ?>
                <?php if ( isset( $stats['plan'] ) ) : ?>
                <tr><th><?php esc_html_e( 'Plan', 'vedika-astrology' ); ?></th><td><?php echo esc_html( ucfirst( $stats['plan'] ) ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}

// Initialize the admin.
Vedika_Admin::instance();
