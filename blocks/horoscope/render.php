<?php
/**
 * Server-side render for the Vedika Horoscope block.
 *
 * @package Vedika_Astrology
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sign   = isset( $attributes['sign'] )   ? sanitize_text_field( $attributes['sign'] )   : 'aries';
$period = isset( $attributes['period'] ) ? sanitize_text_field( $attributes['period'] ) : 'daily';
$lang   = isset( $attributes['lang'] )   ? sanitize_text_field( $attributes['lang'] )   : '';

echo do_shortcode(
    sprintf(
        '[vedika_horoscope sign="%s" period="%s" lang="%s"]',
        esc_attr( $sign ),
        esc_attr( $period ),
        esc_attr( $lang )
    )
);
