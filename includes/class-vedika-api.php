<?php
/**
 * Vedika API client.
 *
 * Handles all HTTP communication with the Vedika Intelligence API.
 * Falls back to sandbox endpoints when no API key is configured.
 *
 * @package Vedika_Astrology
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vedika_API {

    /** @var Vedika_API|null */
    private static $instance = null;

    /** @var string */
    private $api_key;

    /** @var string */
    private $base_url;

    /** @var string */
    private $default_language;

    /** @var int */
    private $cache_duration;

    /**
     * Return singleton.
     *
     * @return Vedika_API
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api_key          = get_option( 'vedika_api_key', '' );
        $this->default_language = get_option( 'vedika_default_language', 'en' );
        $this->cache_duration   = (int) get_option( 'vedika_cache_duration', 3600 );
        $this->base_url         = $this->has_api_key() ? VEDIKA_API_BASE_URL : VEDIKA_SANDBOX_BASE;
    }

    /**
     * Whether a production API key is configured.
     *
     * @return bool
     */
    public function has_api_key() {
        return ! empty( $this->api_key ) && 0 === strpos( $this->api_key, 'vk_' );
    }

    /**
     * Check if the API key is valid by hitting the health endpoint.
     *
     * @param string $key API key to test.
     * @return bool|WP_Error
     */
    public function validate_api_key( $key ) {
        $response = wp_remote_get( VEDIKA_API_BASE_URL . '/ping', array(
            'headers' => array(
                'x-api-key' => $key,
                'Accept'    => 'application/json',
            ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        return ( $code >= 200 && $code < 300 );
    }

    // -------------------------------------------------------------------------
    // Public API methods
    // -------------------------------------------------------------------------

    /**
     * Get daily/weekly/monthly horoscope for a zodiac sign.
     *
     * @param string $sign   Zodiac sign (aries, taurus, ...).
     * @param string $lang   Language code.
     * @param string $period One of daily, weekly, monthly.
     * @return array|WP_Error
     */
    public function get_daily_horoscope( $sign, $lang = '', $period = 'daily' ) {
        $sign = strtolower( sanitize_text_field( $sign ) );
        $lang = $lang ?: $this->default_language;

        $cache_key = "vedika_horoscope_{$sign}_{$period}_{$lang}";
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $suffix = ( 'daily' === $period ) ? '' : "/{$period}";

        if ( $this->has_api_key() ) {
            $url = VEDIKA_API_BASE_URL . "/v2/astrology/horoscope/{$sign}{$suffix}";
        } else {
            $url = VEDIKA_SANDBOX_BASE . "/horoscope/{$sign}{$suffix}";
        }

        $url = add_query_arg( 'lang', $lang, $url );

        $result = $this->get( $url );
        if ( ! is_wp_error( $result ) ) {
            set_transient( $cache_key, $result, $this->cache_duration );
        }
        return $result;
    }

    /**
     * Get tarot card of the day.
     *
     * @return array|WP_Error
     */
    public function get_tarot_card_of_day() {
        $cache_key = 'vedika_tarot_cotd_' . gmdate( 'Y-m-d' );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        if ( $this->has_api_key() ) {
            $url = VEDIKA_API_BASE_URL . '/v2/astrology/tarot/card-of-the-day';
        } else {
            $url = VEDIKA_SANDBOX_BASE . '/tarot/card-of-the-day';
        }

        $result = $this->get( $url );
        if ( ! is_wp_error( $result ) ) {
            set_transient( $cache_key, $result, DAY_IN_SECONDS );
        }
        return $result;
    }

    /**
     * Get panchang for a date and location.
     *
     * @param string $date Date in Y-m-d format. Empty = today.
     * @param float  $lat  Latitude.
     * @param float  $lng  Longitude.
     * @return array|WP_Error
     */
    public function get_panchang( $date = '', $lat = 28.6139, $lng = 77.209 ) {
        $date = $date ?: gmdate( 'Y-m-d' );
        $cache_key = "vedika_panchang_{$date}_{$lat}_{$lng}";
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        if ( $this->has_api_key() ) {
            $url = VEDIKA_API_BASE_URL . '/v2/astrology/panchang/today';
        } else {
            $url = VEDIKA_SANDBOX_BASE . '/panchang/today';
        }

        $url = add_query_arg( array(
            'date'      => $date,
            'latitude'  => $lat,
            'longitude' => $lng,
            'include'   => 'sunrise,muhurta,festivals',
        ), $url );

        $result = $this->get( $url );
        if ( ! is_wp_error( $result ) ) {
            set_transient( $cache_key, $result, $this->cache_duration );
        }
        return $result;
    }

    /**
     * Get a birth chart.
     *
     * @param string $datetime ISO datetime string.
     * @param float  $lat      Latitude.
     * @param float  $lng      Longitude.
     * @param float  $tz       Timezone offset (e.g. 5.5 for IST).
     * @return array|WP_Error
     */
    public function get_birth_chart( $datetime, $lat, $lng, $tz = 5.5 ) {
        $cache_key = 'vedika_birthchart_' . md5( "{$datetime}_{$lat}_{$lng}_{$tz}" );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        if ( $this->has_api_key() ) {
            $url = VEDIKA_API_BASE_URL . '/v2/astrology/birth-chart';
        } else {
            $url = VEDIKA_SANDBOX_BASE . '/kundali/birth-chart';
        }

        $result = $this->post( $url, array(
            'datetime'  => $datetime,
            'latitude'  => $lat,
            'longitude' => $lng,
            'timezone'  => $tz,
        ) );

        if ( ! is_wp_error( $result ) ) {
            set_transient( $cache_key, $result, DAY_IN_SECONDS );
        }
        return $result;
    }

    /**
     * Get compatibility / kundli match.
     *
     * @param array $data1 First person data (name, datetime, latitude, longitude).
     * @param array $data2 Second person data.
     * @return array|WP_Error
     */
    public function get_compatibility( $data1, $data2 ) {
        $cache_key = 'vedika_compat_' . md5( wp_json_encode( array( $data1, $data2 ) ) );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        if ( $this->has_api_key() ) {
            $url = VEDIKA_API_BASE_URL . '/v2/astrology/kundli-match';
        } else {
            $url = VEDIKA_SANDBOX_BASE . '/matching/ashtakoot';
        }

        $result = $this->post( $url, array(
            'person1' => $data1,
            'person2' => $data2,
        ) );

        if ( ! is_wp_error( $result ) ) {
            set_transient( $cache_key, $result, DAY_IN_SECONDS );
        }
        return $result;
    }

    /**
     * Get numerology analysis.
     *
     * @param string $name      Full name.
     * @param string $birthdate Date of birth (Y-m-d).
     * @return array|WP_Error
     */
    public function get_numerology( $name, $birthdate ) {
        $cache_key = 'vedika_numerology_' . md5( "{$name}_{$birthdate}" );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        if ( $this->has_api_key() ) {
            $url = VEDIKA_API_BASE_URL . '/v2/astrology/numerology/complete';
        } else {
            $url = VEDIKA_SANDBOX_BASE . '/numerology/complete';
        }

        $result = $this->post( $url, array(
            'name'      => $name,
            'birthDate' => $birthdate,
        ) );

        if ( ! is_wp_error( $result ) ) {
            set_transient( $cache_key, $result, DAY_IN_SECONDS );
        }
        return $result;
    }

    /**
     * Get API usage statistics (requires API key).
     *
     * @return array|WP_Error
     */
    public function get_usage_stats() {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'no_api_key', __( 'API key required for usage stats.', 'vedika-astrology' ) );
        }

        return $this->get( VEDIKA_API_BASE_URL . '/api/billing/usage' );
    }

    // -------------------------------------------------------------------------
    // Internal HTTP helpers
    // -------------------------------------------------------------------------

    /**
     * Make a GET request.
     *
     * @param string $url Full URL.
     * @return array|WP_Error Decoded JSON body or error.
     */
    private function get( $url ) {
        $args = array(
            'headers' => $this->build_headers(),
            'timeout' => 15,
        );

        $response = wp_remote_get( $url, $args );
        return $this->handle_response( $response );
    }

    /**
     * Make a POST request.
     *
     * @param string $url  Full URL.
     * @param array  $body Request body.
     * @return array|WP_Error Decoded JSON body or error.
     */
    private function post( $url, $body ) {
        $args = array(
            'headers' => $this->build_headers( true ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        );

        $response = wp_remote_post( $url, $args );
        return $this->handle_response( $response );
    }

    /**
     * Build HTTP headers.
     *
     * @param bool $json Whether to set Content-Type to JSON.
     * @return array
     */
    private function build_headers( $json = false ) {
        $headers = array(
            'Accept'     => 'application/json',
            'User-Agent' => 'VedikaWordPress/' . VEDIKA_ASTROLOGY_VERSION,
        );

        if ( $this->has_api_key() ) {
            $headers['x-api-key'] = $this->api_key;
        }

        if ( $json ) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    /**
     * Parse an HTTP response.
     *
     * @param array|WP_Error $response wp_remote_* result.
     * @return array|WP_Error
     */
    private function handle_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code >= 400 ) {
            $message = isset( $data['error'] ) ? $data['error'] : "API returned HTTP {$code}";
            return new WP_Error( 'vedika_api_error', $message, array( 'status' => $code ) );
        }

        if ( null === $data ) {
            return new WP_Error( 'vedika_parse_error', __( 'Failed to parse API response.', 'vedika-astrology' ) );
        }

        // Unwrap the data key if present (sandbox wraps in { data: ... }).
        if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
            return $data['data'];
        }

        return $data;
    }
}
