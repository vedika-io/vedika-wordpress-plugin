<?php
/**
 * Vedika Shortcodes.
 *
 * Registers all [vedika_*] shortcodes for embedding astrology content.
 *
 * @package Vedika_Astrology
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Vedika_Shortcodes {

    /** @var Vedika_API */
    private $api;

    /**
     * Valid zodiac signs.
     *
     * @var string[]
     */
    private static $signs = array(
        'aries', 'taurus', 'gemini', 'cancer',
        'leo', 'virgo', 'libra', 'scorpio',
        'sagittarius', 'capricorn', 'aquarius', 'pisces',
    );

    /**
     * Sign display names and symbols.
     *
     * @var array<string, array{name: string, symbol: string}>
     */
    private static $sign_meta = array(
        'aries'       => array( 'name' => 'Aries',       'symbol' => "\u{2648}" ),
        'taurus'      => array( 'name' => 'Taurus',      'symbol' => "\u{2649}" ),
        'gemini'      => array( 'name' => 'Gemini',      'symbol' => "\u{264A}" ),
        'cancer'      => array( 'name' => 'Cancer',      'symbol' => "\u{264B}" ),
        'leo'         => array( 'name' => 'Leo',         'symbol' => "\u{264C}" ),
        'virgo'       => array( 'name' => 'Virgo',       'symbol' => "\u{264D}" ),
        'libra'       => array( 'name' => 'Libra',       'symbol' => "\u{264E}" ),
        'scorpio'     => array( 'name' => 'Scorpio',     'symbol' => "\u{264F}" ),
        'sagittarius' => array( 'name' => 'Sagittarius', 'symbol' => "\u{2650}" ),
        'capricorn'   => array( 'name' => 'Capricorn',   'symbol' => "\u{2651}" ),
        'aquarius'    => array( 'name' => 'Aquarius',    'symbol' => "\u{2652}" ),
        'pisces'      => array( 'name' => 'Pisces',      'symbol' => "\u{2653}" ),
    );

    public function __construct() {
        $this->api = Vedika_API::instance();

        add_shortcode( 'vedika_horoscope',     array( $this, 'horoscope' ) );
        add_shortcode( 'vedika_horoscope_all', array( $this, 'horoscope_all' ) );
        add_shortcode( 'vedika_tarot',         array( $this, 'tarot' ) );
        add_shortcode( 'vedika_panchang',      array( $this, 'panchang' ) );
        add_shortcode( 'vedika_birth_chart',   array( $this, 'birth_chart' ) );
        add_shortcode( 'vedika_compatibility', array( $this, 'compatibility' ) );
        add_shortcode( 'vedika_numerology',    array( $this, 'numerology' ) );
    }

    // -------------------------------------------------------------------------
    // [vedika_horoscope sign="aries" lang="en" period="daily"]
    // -------------------------------------------------------------------------

    public function horoscope( $atts ) {
        $atts = shortcode_atts( array(
            'sign'   => 'aries',
            'lang'   => '',
            'period' => 'daily',
        ), $atts, 'vedika_horoscope' );

        $sign = strtolower( sanitize_text_field( $atts['sign'] ) );
        if ( ! in_array( $sign, self::$signs, true ) ) {
            return '<p class="vedika-error">' . esc_html__( 'Invalid zodiac sign.', 'vedika-astrology' ) . '</p>';
        }

        $data = $this->api->get_daily_horoscope( $sign, $atts['lang'], $atts['period'] );
        if ( is_wp_error( $data ) ) {
            return $this->render_error( $data );
        }

        $meta       = self::$sign_meta[ $sign ] ?? array( 'name' => ucfirst( $sign ), 'symbol' => '' );
        $prediction = $this->extract_prediction( $data );
        $date_str   = isset( $data['date'] ) ? esc_html( $data['date'] ) : esc_html( gmdate( 'F j, Y' ) );

        ob_start();
        ?>
        <div class="vedika-horoscope vedika-card">
            <div class="vedika-horoscope-header">
                <span class="vedika-sign-symbol"><?php echo esc_html( $meta['symbol'] ); ?></span>
                <h3 class="vedika-sign-name"><?php echo esc_html( $meta['name'] ); ?></h3>
                <span class="vedika-date"><?php echo $date_str; ?></span>
            </div>
            <div class="vedika-horoscope-body">
                <p class="vedika-prediction"><?php echo esc_html( $prediction ); ?></p>
            </div>
            <?php if ( ! empty( $data['luckyNumber'] ) || ! empty( $data['luckyColor'] ) ) : ?>
            <div class="vedika-horoscope-footer">
                <?php if ( ! empty( $data['luckyNumber'] ) ) : ?>
                    <span class="vedika-lucky"><strong><?php esc_html_e( 'Lucky Number:', 'vedika-astrology' ); ?></strong> <?php echo esc_html( $data['luckyNumber'] ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $data['luckyColor'] ) ) : ?>
                    <span class="vedika-lucky"><strong><?php esc_html_e( 'Lucky Color:', 'vedika-astrology' ); ?></strong> <?php echo esc_html( $data['luckyColor'] ); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="vedika-attribution">
                <?php esc_html_e( 'Powered by Vedika AI', 'vedika-astrology' ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [vedika_horoscope_all]
    // -------------------------------------------------------------------------

    public function horoscope_all( $atts ) {
        $atts = shortcode_atts( array(
            'lang'   => '',
            'period' => 'daily',
        ), $atts, 'vedika_horoscope_all' );

        $lang   = $atts['lang'] ?: get_option( 'vedika_default_language', 'en' );
        $period = $atts['period'];

        // Check if ALL 12 signs are cached — if so, render everything server-side.
        $all_cached = true;
        foreach ( self::$signs as $sign ) {
            $cache_key = "vedika_horoscope_{$sign}_{$period}_{$lang}";
            if ( false === get_transient( $cache_key ) ) {
                $all_cached = false;
                break;
            }
        }

        $uid = wp_unique_id( 'vedika-horoscope-all-' );

        ob_start();
        ?>
        <div class="vedika-horoscope-all" id="<?php echo esc_attr( $uid ); ?>"
             data-lang="<?php echo esc_attr( $lang ); ?>"
             data-period="<?php echo esc_attr( $period ); ?>">
            <div class="vedika-tabs" role="tablist">
                <?php foreach ( self::$signs as $index => $sign ) :
                    $meta   = self::$sign_meta[ $sign ];
                    $active = ( 0 === $index ) ? ' vedika-tab-active' : '';
                ?>
                    <button class="vedika-tab<?php echo $active; ?>"
                            role="tab"
                            aria-selected="<?php echo ( 0 === $index ) ? 'true' : 'false'; ?>"
                            data-sign="<?php echo esc_attr( $sign ); ?>">
                        <span class="vedika-tab-symbol"><?php echo esc_html( $meta['symbol'] ); ?></span>
                        <span class="vedika-tab-label"><?php echo esc_html( $meta['name'] ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="vedika-tab-panels">
                <?php if ( $all_cached ) : ?>
                    <?php // All cached — render all 12 server-side. ?>
                    <?php foreach ( self::$signs as $index => $sign ) :
                        $hidden = ( 0 !== $index ) ? ' vedika-hidden' : '';
                    ?>
                        <div class="vedika-tab-panel<?php echo $hidden; ?>"
                             role="tabpanel"
                             data-sign="<?php echo esc_attr( $sign ); ?>"
                             data-loaded="true">
                            <?php echo $this->horoscope( array(
                                'sign'   => $sign,
                                'lang'   => $atts['lang'],
                                'period' => $period,
                            ) ); ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <?php // Cold cache — only fetch the first sign; lazy-load the rest via AJAX. ?>
                    <?php foreach ( self::$signs as $index => $sign ) :
                        $hidden = ( 0 !== $index ) ? ' vedika-hidden' : '';
                    ?>
                        <div class="vedika-tab-panel<?php echo $hidden; ?>"
                             role="tabpanel"
                             data-sign="<?php echo esc_attr( $sign ); ?>"
                             data-loaded="<?php echo ( 0 === $index ) ? 'true' : 'false'; ?>">
                            <?php if ( 0 === $index ) : ?>
                                <?php echo $this->horoscope( array(
                                    'sign'   => $sign,
                                    'lang'   => $atts['lang'],
                                    'period' => $period,
                                ) ); ?>
                            <?php else : ?>
                                <div class="vedika-loading">
                                    <p><?php esc_html_e( 'Loading...', 'vedika-astrology' ); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [vedika_tarot]
    // -------------------------------------------------------------------------

    public function tarot( $atts ) {
        $data = $this->api->get_tarot_card_of_day();
        if ( is_wp_error( $data ) ) {
            return $this->render_error( $data );
        }

        $card = isset( $data['card'] ) ? $data['card'] : $data;
        $name     = isset( $card['name'] )    ? $card['name']    : __( 'The Fool', 'vedika-astrology' );
        $meaning  = isset( $card['meaning'] ) ? $card['meaning'] : '';
        $keywords = isset( $card['keywords'] ) && is_array( $card['keywords'] ) ? $card['keywords'] : array();
        $arcana   = isset( $card['arcana'] )  ? ucfirst( $card['arcana'] ) : '';
        $element  = isset( $card['element'] ) ? $card['element'] : '';
        $upright  = isset( $card['upright'] ) ? $card['upright'] : true;

        ob_start();
        ?>
        <div class="vedika-tarot vedika-card">
            <div class="vedika-tarot-header">
                <h3 class="vedika-card-name"><?php echo esc_html( $name ); ?></h3>
                <?php if ( $arcana ) : ?>
                    <span class="vedika-arcana"><?php echo esc_html( $arcana ); ?> Arcana</span>
                <?php endif; ?>
            </div>
            <div class="vedika-tarot-body">
                <div class="vedika-tarot-orientation">
                    <span class="vedika-orientation-badge <?php echo $upright ? 'vedika-upright' : 'vedika-reversed'; ?>">
                        <?php echo $upright ? esc_html__( 'Upright', 'vedika-astrology' ) : esc_html__( 'Reversed', 'vedika-astrology' ); ?>
                    </span>
                </div>
                <?php if ( $meaning ) : ?>
                    <p class="vedika-tarot-meaning"><?php echo esc_html( $meaning ); ?></p>
                <?php endif; ?>
                <?php if ( $keywords ) : ?>
                    <div class="vedika-tarot-keywords">
                        <?php foreach ( $keywords as $kw ) : ?>
                            <span class="vedika-keyword"><?php echo esc_html( $kw ); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $element ) : ?>
                    <p class="vedika-tarot-element"><strong><?php esc_html_e( 'Element:', 'vedika-astrology' ); ?></strong> <?php echo esc_html( $element ); ?></p>
                <?php endif; ?>
            </div>
            <div class="vedika-attribution">
                <?php esc_html_e( 'Powered by Vedika AI', 'vedika-astrology' ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [vedika_panchang]
    // -------------------------------------------------------------------------

    public function panchang( $atts ) {
        $atts = shortcode_atts( array(
            'date' => '',
            'lat'  => '28.6139',
            'lng'  => '77.209',
        ), $atts, 'vedika_panchang' );

        $data = $this->api->get_panchang( $atts['date'], floatval( $atts['lat'] ), floatval( $atts['lng'] ) );
        if ( is_wp_error( $data ) ) {
            return $this->render_error( $data );
        }

        $tithi     = $this->deep_get( $data, 'tithi.name', '' );
        $nakshatra = $this->deep_get( $data, 'nakshatra.name', '' );
        $yoga      = $this->deep_get( $data, 'yoga.name', '' );
        $karana    = $this->deep_get( $data, 'karana.name', '' );
        $day       = isset( $data['day'] ) ? $data['day'] : gmdate( 'l' );

        ob_start();
        ?>
        <div class="vedika-panchang vedika-card">
            <div class="vedika-panchang-header">
                <h3><?php esc_html_e( "Today's Panchang", 'vedika-astrology' ); ?></h3>
                <span class="vedika-date"><?php echo esc_html( $atts['date'] ?: gmdate( 'F j, Y' ) ); ?></span>
            </div>
            <table class="vedika-panchang-table">
                <tbody>
                    <?php if ( $day ) : ?>
                    <tr><th><?php esc_html_e( 'Day', 'vedika-astrology' ); ?></th><td><?php echo esc_html( $day ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( $tithi ) : ?>
                    <tr><th><?php esc_html_e( 'Tithi', 'vedika-astrology' ); ?></th><td><?php echo esc_html( $tithi ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( $nakshatra ) : ?>
                    <tr><th><?php esc_html_e( 'Nakshatra', 'vedika-astrology' ); ?></th><td><?php echo esc_html( $nakshatra ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( $yoga ) : ?>
                    <tr><th><?php esc_html_e( 'Yoga', 'vedika-astrology' ); ?></th><td><?php echo esc_html( $yoga ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( $karana ) : ?>
                    <tr><th><?php esc_html_e( 'Karana', 'vedika-astrology' ); ?></th><td><?php echo esc_html( $karana ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $data['sunrise'] ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Sunrise', 'vedika-astrology' ); ?></th>
                        <td><?php echo esc_html( is_array( $data['sunrise'] ) ? ( $data['sunrise']['time'] ?? '' ) : $data['sunrise'] ); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ( ! empty( $data['sunset'] ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Sunset', 'vedika-astrology' ); ?></th>
                        <td><?php echo esc_html( is_array( $data['sunset'] ) ? ( $data['sunset']['time'] ?? '' ) : $data['sunset'] ); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="vedika-attribution">
                <?php esc_html_e( 'Powered by Vedika AI', 'vedika-astrology' ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [vedika_birth_chart]
    // -------------------------------------------------------------------------

    public function birth_chart( $atts ) {
        $uid = wp_unique_id( 'vedika-bc-' );

        ob_start();
        ?>
        <div class="vedika-birth-chart vedika-card" data-vedika-instance="<?php echo esc_attr( $uid ); ?>">
            <div class="vedika-form-header">
                <h3><?php esc_html_e( 'Birth Chart Calculator', 'vedika-astrology' ); ?></h3>
            </div>
            <form class="vedika-form vedika-birth-chart-form" id="<?php echo esc_attr( $uid ); ?>-form">
                <div class="vedika-form-group">
                    <label for="<?php echo esc_attr( $uid ); ?>-datetime"><?php esc_html_e( 'Date & Time of Birth', 'vedika-astrology' ); ?></label>
                    <input type="datetime-local" id="<?php echo esc_attr( $uid ); ?>-datetime" name="datetime" required />
                </div>
                <div class="vedika-form-row">
                    <div class="vedika-form-group vedika-half">
                        <label for="<?php echo esc_attr( $uid ); ?>-lat"><?php esc_html_e( 'Latitude', 'vedika-astrology' ); ?></label>
                        <input type="number" id="<?php echo esc_attr( $uid ); ?>-lat" name="lat" step="0.0001" placeholder="28.6139" required />
                    </div>
                    <div class="vedika-form-group vedika-half">
                        <label for="<?php echo esc_attr( $uid ); ?>-lng"><?php esc_html_e( 'Longitude', 'vedika-astrology' ); ?></label>
                        <input type="number" id="<?php echo esc_attr( $uid ); ?>-lng" name="lng" step="0.0001" placeholder="77.209" required />
                    </div>
                </div>
                <div class="vedika-form-group">
                    <label for="<?php echo esc_attr( $uid ); ?>-tz"><?php esc_html_e( 'Timezone Offset', 'vedika-astrology' ); ?></label>
                    <select id="<?php echo esc_attr( $uid ); ?>-tz" name="tz">
                        <option value="-12">UTC-12:00</option>
                        <option value="-11">UTC-11:00</option>
                        <option value="-10">UTC-10:00</option>
                        <option value="-9">UTC-09:00</option>
                        <option value="-8">UTC-08:00</option>
                        <option value="-7">UTC-07:00</option>
                        <option value="-6">UTC-06:00</option>
                        <option value="-5">UTC-05:00</option>
                        <option value="-4">UTC-04:00</option>
                        <option value="-3">UTC-03:00</option>
                        <option value="-2">UTC-02:00</option>
                        <option value="-1">UTC-01:00</option>
                        <option value="0">UTC+00:00</option>
                        <option value="1">UTC+01:00</option>
                        <option value="2">UTC+02:00</option>
                        <option value="3">UTC+03:00</option>
                        <option value="3.5">UTC+03:30</option>
                        <option value="4">UTC+04:00</option>
                        <option value="4.5">UTC+04:30</option>
                        <option value="5">UTC+05:00</option>
                        <option value="5.5" selected>UTC+05:30 (IST)</option>
                        <option value="5.75">UTC+05:45</option>
                        <option value="6">UTC+06:00</option>
                        <option value="6.5">UTC+06:30</option>
                        <option value="7">UTC+07:00</option>
                        <option value="8">UTC+08:00</option>
                        <option value="9">UTC+09:00</option>
                        <option value="9.5">UTC+09:30</option>
                        <option value="10">UTC+10:00</option>
                        <option value="11">UTC+11:00</option>
                        <option value="12">UTC+12:00</option>
                    </select>
                </div>
                <button type="submit" class="vedika-btn"><?php esc_html_e( 'Generate Chart', 'vedika-astrology' ); ?></button>
            </form>
            <div class="vedika-result" id="<?php echo esc_attr( $uid ); ?>-result" style="display:none;"></div>
            <div class="vedika-attribution">
                <?php esc_html_e( 'Powered by Vedika AI', 'vedika-astrology' ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [vedika_compatibility]
    // -------------------------------------------------------------------------

    public function compatibility( $atts ) {
        $uid = wp_unique_id( 'vedika-compat-' );

        ob_start();
        ?>
        <div class="vedika-compatibility vedika-card" data-vedika-instance="<?php echo esc_attr( $uid ); ?>">
            <div class="vedika-form-header">
                <h3><?php esc_html_e( 'Compatibility Checker', 'vedika-astrology' ); ?></h3>
            </div>
            <form class="vedika-form vedika-compatibility-form" id="<?php echo esc_attr( $uid ); ?>-form">
                <fieldset class="vedika-fieldset">
                    <legend><?php esc_html_e( 'Person 1', 'vedika-astrology' ); ?></legend>
                    <div class="vedika-form-group">
                        <label for="<?php echo esc_attr( $uid ); ?>-name1"><?php esc_html_e( 'Name', 'vedika-astrology' ); ?></label>
                        <input type="text" id="<?php echo esc_attr( $uid ); ?>-name1" name="name1" required />
                    </div>
                    <div class="vedika-form-group">
                        <label for="<?php echo esc_attr( $uid ); ?>-dt1"><?php esc_html_e( 'Date & Time of Birth', 'vedika-astrology' ); ?></label>
                        <input type="datetime-local" id="<?php echo esc_attr( $uid ); ?>-dt1" name="datetime1" required />
                    </div>
                    <div class="vedika-form-row">
                        <div class="vedika-form-group vedika-half">
                            <label for="<?php echo esc_attr( $uid ); ?>-lat1"><?php esc_html_e( 'Latitude', 'vedika-astrology' ); ?></label>
                            <input type="number" id="<?php echo esc_attr( $uid ); ?>-lat1" name="lat1" step="0.0001" required />
                        </div>
                        <div class="vedika-form-group vedika-half">
                            <label for="<?php echo esc_attr( $uid ); ?>-lng1"><?php esc_html_e( 'Longitude', 'vedika-astrology' ); ?></label>
                            <input type="number" id="<?php echo esc_attr( $uid ); ?>-lng1" name="lng1" step="0.0001" required />
                        </div>
                    </div>
                </fieldset>
                <fieldset class="vedika-fieldset">
                    <legend><?php esc_html_e( 'Person 2', 'vedika-astrology' ); ?></legend>
                    <div class="vedika-form-group">
                        <label for="<?php echo esc_attr( $uid ); ?>-name2"><?php esc_html_e( 'Name', 'vedika-astrology' ); ?></label>
                        <input type="text" id="<?php echo esc_attr( $uid ); ?>-name2" name="name2" required />
                    </div>
                    <div class="vedika-form-group">
                        <label for="<?php echo esc_attr( $uid ); ?>-dt2"><?php esc_html_e( 'Date & Time of Birth', 'vedika-astrology' ); ?></label>
                        <input type="datetime-local" id="<?php echo esc_attr( $uid ); ?>-dt2" name="datetime2" required />
                    </div>
                    <div class="vedika-form-row">
                        <div class="vedika-form-group vedika-half">
                            <label for="<?php echo esc_attr( $uid ); ?>-lat2"><?php esc_html_e( 'Latitude', 'vedika-astrology' ); ?></label>
                            <input type="number" id="<?php echo esc_attr( $uid ); ?>-lat2" name="lat2" step="0.0001" required />
                        </div>
                        <div class="vedika-form-group vedika-half">
                            <label for="<?php echo esc_attr( $uid ); ?>-lng2"><?php esc_html_e( 'Longitude', 'vedika-astrology' ); ?></label>
                            <input type="number" id="<?php echo esc_attr( $uid ); ?>-lng2" name="lng2" step="0.0001" required />
                        </div>
                    </div>
                </fieldset>
                <button type="submit" class="vedika-btn"><?php esc_html_e( 'Check Compatibility', 'vedika-astrology' ); ?></button>
            </form>
            <div class="vedika-result" id="<?php echo esc_attr( $uid ); ?>-result" style="display:none;"></div>
            <div class="vedika-attribution">
                <?php esc_html_e( 'Powered by Vedika AI', 'vedika-astrology' ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [vedika_numerology]
    // -------------------------------------------------------------------------

    public function numerology( $atts ) {
        $uid = wp_unique_id( 'vedika-num-' );

        ob_start();
        ?>
        <div class="vedika-numerology vedika-card" data-vedika-instance="<?php echo esc_attr( $uid ); ?>">
            <div class="vedika-form-header">
                <h3><?php esc_html_e( 'Numerology Calculator', 'vedika-astrology' ); ?></h3>
            </div>
            <form class="vedika-form vedika-numerology-form" id="<?php echo esc_attr( $uid ); ?>-form">
                <div class="vedika-form-group">
                    <label for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Full Name', 'vedika-astrology' ); ?></label>
                    <input type="text" id="<?php echo esc_attr( $uid ); ?>-name" name="name" required />
                </div>
                <div class="vedika-form-group">
                    <label for="<?php echo esc_attr( $uid ); ?>-dob"><?php esc_html_e( 'Date of Birth', 'vedika-astrology' ); ?></label>
                    <input type="date" id="<?php echo esc_attr( $uid ); ?>-dob" name="birthdate" required />
                </div>
                <button type="submit" class="vedika-btn"><?php esc_html_e( 'Calculate', 'vedika-astrology' ); ?></button>
            </form>
            <div class="vedika-result" id="<?php echo esc_attr( $uid ); ?>-result" style="display:none;"></div>
            <div class="vedika-attribution">
                <?php esc_html_e( 'Powered by Vedika AI', 'vedika-astrology' ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Extract the prediction string from various API response shapes.
     *
     * @param array $data API response data.
     * @return string
     */
    private function extract_prediction( $data ) {
        // v35.3.2 shape: prediction is a string directly.
        if ( isset( $data['prediction'] ) && is_string( $data['prediction'] ) ) {
            return $data['prediction'];
        }
        // Older shape: prediction.text.
        if ( isset( $data['prediction']['text'] ) ) {
            return $data['prediction']['text'];
        }
        // Fallback: horoscope key.
        if ( isset( $data['horoscope'] ) && is_string( $data['horoscope'] ) ) {
            return $data['horoscope'];
        }
        // Last resort: description.
        if ( isset( $data['description'] ) && is_string( $data['description'] ) ) {
            return $data['description'];
        }
        return __( 'Horoscope data is currently unavailable. Please try again later.', 'vedika-astrology' );
    }

    /**
     * Safely traverse nested array keys with dot notation.
     *
     * @param array  $data    Source array.
     * @param string $path    Dot-separated path.
     * @param mixed  $default Fallback value.
     * @return mixed
     */
    private function deep_get( $data, $path, $default = null ) {
        $keys = explode( '.', $path );
        foreach ( $keys as $key ) {
            if ( ! is_array( $data ) || ! array_key_exists( $key, $data ) ) {
                return $default;
            }
            $data = $data[ $key ];
        }
        return $data;
    }

    /**
     * Render an API error as HTML.
     *
     * @param WP_Error $error The error.
     * @return string
     */
    private function render_error( $error ) {
        return '<div class="vedika-error vedika-card"><p>' .
            esc_html__( 'Unable to load content. Please try again later.', 'vedika-astrology' ) .
            '</p></div>';
    }
}

// Initialize shortcodes.
new Vedika_Shortcodes();
