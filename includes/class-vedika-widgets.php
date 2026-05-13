<?php
/**
 * Vedika Sidebar Widgets.
 *
 * @package Vedika_Astrology
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Daily Horoscope widget for sidebars.
 */
class Vedika_Horoscope_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vedika_horoscope_widget',
            __( 'Vedika Daily Horoscope', 'vedika-astrology' ),
            array(
                'description' => __( 'Display daily horoscope for a zodiac sign.', 'vedika-astrology' ),
                'classname'   => 'vedika-widget vedika-horoscope-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        $sign = ! empty( $instance['sign'] ) ? $instance['sign'] : 'aries';
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Daily Horoscope', 'vedika-astrology' );
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo do_shortcode( '[vedika_horoscope sign="' . esc_attr( $sign ) . '"]' );

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Daily Horoscope', 'vedika-astrology' );
        $sign  = ! empty( $instance['sign'] )  ? $instance['sign']  : 'aries';
        $signs = array(
            'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo',
            'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces',
        );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'vedika-astrology' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'sign' ) ); ?>"><?php esc_html_e( 'Zodiac Sign:', 'vedika-astrology' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'sign' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'sign' ) ); ?>">
                <?php foreach ( $signs as $s ) : ?>
                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $sign, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['sign']  = sanitize_text_field( $new_instance['sign'] ?? 'aries' );
        return $instance;
    }
}

/**
 * Tarot Card of the Day widget.
 */
class Vedika_Tarot_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vedika_tarot_widget',
            __( 'Vedika Tarot Card of the Day', 'vedika-astrology' ),
            array(
                'description' => __( 'Display the tarot card of the day.', 'vedika-astrology' ),
                'classname'   => 'vedika-widget vedika-tarot-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Tarot Card of the Day', 'vedika-astrology' );
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo do_shortcode( '[vedika_tarot]' );

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Tarot Card of the Day', 'vedika-astrology' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'vedika-astrology' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        return $instance;
    }
}

/**
 * Panchang widget for sidebars.
 */
class Vedika_Panchang_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vedika_panchang_widget',
            __( "Vedika Today's Panchang", 'vedika-astrology' ),
            array(
                'description' => __( "Display today's panchang details.", 'vedika-astrology' ),
                'classname'   => 'vedika-widget vedika-panchang-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( "Today's Panchang", 'vedika-astrology' );
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        $lat = ! empty( $instance['lat'] ) ? $instance['lat'] : '28.6139';
        $lng = ! empty( $instance['lng'] ) ? $instance['lng'] : '77.209';

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo do_shortcode( '[vedika_panchang lat="' . esc_attr( $lat ) . '" lng="' . esc_attr( $lng ) . '"]' );

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( "Today's Panchang", 'vedika-astrology' );
        $lat   = ! empty( $instance['lat'] )   ? $instance['lat']   : '28.6139';
        $lng   = ! empty( $instance['lng'] )   ? $instance['lng']   : '77.209';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'vedika-astrology' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'lat' ) ); ?>"><?php esc_html_e( 'Latitude:', 'vedika-astrology' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'lat' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'lat' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $lat ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'lng' ) ); ?>"><?php esc_html_e( 'Longitude:', 'vedika-astrology' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'lng' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'lng' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $lng ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['lat']   = sanitize_text_field( $new_instance['lat'] ?? '28.6139' );
        $instance['lng']   = sanitize_text_field( $new_instance['lng'] ?? '77.209' );
        return $instance;
    }
}
