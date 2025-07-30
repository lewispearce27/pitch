<?php
/*
Plugin Name: PitchPrint Custom Integration
Description: Integrates PitchPrint with WooCommerce products, adding Design Online and Upload Artwork buttons, with admin settings.
Version: 1.0.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Define plugin URL constant
if (!defined('PPCUSTOM_URL')) {
    define('PPCUSTOM_URL', plugin_dir_url(__FILE__));
}

// Debug log to confirm plugin load
error_log('PitchPrint Custom Integration Loaded');

// Admin settings (API keys/settings)
require_once plugin_dir_path(__FILE__) . 'admin/menu.php';

// Product admin: meta fields, ajax, etc
require_once plugin_dir_path(__FILE__) . 'admin/product-meta.php';

// FRONTEND: Enqueue scripts/styles and inject buttons
add_action('wp_enqueue_scripts', function() {
    if (!is_product()) return;

    global $post;
    if (empty($post) || $post->post_type !== 'product') return;

    $design_id   = get_post_meta($post->ID, '_ppcustom_design_id', true);
    $button_mode = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';
    $options     = get_option('ppcustom_settings');
    $api_key     = isset($options['api_key']) ? $options['api_key'] : '';

    // Only enqueue if enabled
    if (!$design_id && $button_mode !== 'upload' && $button_mode !== 'both') return;

    wp_enqueue_script(
        'pitchprint-sdk',
        'https://pitchprint.io/rsc/js/pitchprint.js',
        [],
        null,
        true
    );

    wp_enqueue_script(
        'ppcustom-frontend',
        PPCUSTOM_URL . 'public/js/custom.js',
        ['jquery', 'pitchprint-sdk'],
        null,
        true
    );

    wp_enqueue_style(
        'ppcustom-css',
        PPCUSTOM_URL . 'public/css/custom.css'
    );

    wp_localize_script('ppcustom-frontend', 'ppcustom', [
        'designId'    => $design_id,
        'apiKey'      => $api_key,
        'buttonMode'  => $button_mode,
    ]);
});

// Output PitchPrint buttons on the product page
add_action('woocommerce_before_add_to_cart_button', function() {
    global $post;
    $design_id   = get_post_meta($post->ID, '_ppcustom_design_id', true);
    $button_mode = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';

    echo '<div class="ppcustom-buttons">';
    if ($button_mode === 'both' || $button_mode === 'design') {
        echo '<button type="button" class="button ppcustom-design-btn">Design Online</button> ';
    }
    if ($button_mode === 'both' || $button_mode === 'upload') {
        echo '<button type="button" class="button ppcustom-upload-btn">Upload Artwork</button>';
    }
    echo '</div>';

    // Modal for upload (hidden by default)
    echo '<div id="pp-upload-modal" style="display:none;">
        <h4>Upload Artwork</h4>
        <input type="file" id="pp-artwork-file" accept="image/*,.pdf" />
        <button type="button" id="pp-upload-submit" class="button">Upload</button>
        <button type="button" id="pp-upload-close" class="button">Close</button>
    </div>';
});
