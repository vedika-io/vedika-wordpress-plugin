<?php
/**
 * Server-side render for the Vedika Tarot block.
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

echo do_shortcode( '[vedika_tarot]' );
