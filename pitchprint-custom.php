<?php
/**
 * Plugin Name: PitchPrint Custom Integration
 * Description: Full custom PitchPrint integration for WooCommerce products.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// DEBUG LOGGING
error_log('PitchPrint Custom Integration Loaded');

// === ADMIN INCLUDES ===
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/menu.php';
    require_once plugin_dir_path(__FILE__) . 'admin/product-meta.php';
}

// === FRONTEND SCRIPTS AND INTEGRATION ===

class PitchPrintCustom {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
    }

    public function enqueue_frontend_scripts() {
        // Only on single product pages
        if (!function_exists('is_product') || !is_product()) return;
        global $post;
        if (empty($post) || $post->post_type !== 'product') return;
        $product = wc_get_product($post->ID);
        if (!$product || !is_a($product, 'WC_Product')) return;

        $product_id = $product->get_id();
        $design_id = get_post_meta($product_id, '_ppcustom_design_id', true);
        $options = get_option('ppcustom_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';

        wp_enqueue_script(
            'pitchprint-sdk',
            'https://pitchprint.io/rsc/js/pitchprint.js',
            [],
            null,
            true
        );
        wp_enqueue_script(
            'ppcustom-frontend',
            plugin_dir_url(__FILE__) . 'public/js/custom.js',
            ['jquery', 'pitchprint-sdk'],
            null,
            true
        );
        wp_localize_script('ppcustom-frontend', 'ppcustom', [
            'designId' => $design_id,
            'apiKey' => $api_key
        ]);
        wp_enqueue_style(
            'ppcustom-style',
            plugin_dir_url(__FILE__) . 'public/css/custom.css'
        );
    }
}
new PitchPrintCustom();
