<?php
/*
Plugin Name: PitchPrint Custom Integration
Description: Adds Design Online and Upload Artwork buttons integrated with PitchPrint.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Constants
define('PPCUSTOM_PATH', plugin_dir_path(__FILE__));
define('PPCUSTOM_URL', plugin_dir_url(__FILE__));

/**
 * Include admin meta fields
 */
require_once PPCUSTOM_PATH . 'admin/product-meta.php';

/**
 * Register admin menu for PitchPrint settings
 */
add_action('admin_menu', function() {
    add_menu_page(
        'PitchPrint Settings',
        'PitchPrint',
        'manage_options',
        'ppcustom-settings',
        'ppcustom_render_settings_page',
        'dashicons-art'
    );
});

/**
 * Register settings
 */
add_action('admin_init', function() {
    register_setting('ppcustom_settings_group', 'ppcustom_settings');

    add_settings_section(
        'ppcustom_main_section',
        'PitchPrint API Settings',
        '__return_null',
        'ppcustom-settings'
    );

    add_settings_field(
        'api_key',
        'API Key',
        function() {
            $options = get_option('ppcustom_settings');
            echo '<input type="text" name="ppcustom_settings[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text">';
        },
        'ppcustom-settings',
        'ppcustom_main_section'
    );

    add_settings_field(
        'secret_key',
        'Secret Key',
        function() {
            $options = get_option('ppcustom_settings');
            echo '<input type="text" name="ppcustom_settings[secret_key]" value="' . esc_attr($options['secret_key'] ?? '') . '" class="regular-text">';
        },
        'ppcustom-settings',
        'ppcustom_main_section'
    );
});

/**
 * Render settings page
 */
function ppcustom_render_settings_page() {
    echo '<div class="wrap"><h1>PitchPrint Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('ppcustom_settings_group');
    do_settings_sections('ppcustom-settings');
    submit_button();
    echo '</form></div>';
}

/**
 * Enqueue frontend scripts and PitchPrint SDK
 */
add_action('wp_enqueue_scripts', function() {

    if (is_product()) {

        // Enqueue PitchPrint SDK
        wp_enqueue_script(
            'pitchprint-sdk',
            'https://pitchprint.io/rsc/js/pitchprint.js',
            [],
            null,
            true
        );

        // Enqueue frontend custom script
        wp_enqueue_script(
            'ppcustom-frontend',
            PPCUSTOM_URL . 'public/js/custom.js',
            ['jquery', 'pitchprint-sdk'],
            null,
            true
        );

        // Pass data to JS
        global $product;
        $design_id = get_post_meta($product->get_id(), '_ppcustom_design_id', true);
        $options = get_option('ppcustom_settings');
        $api_key = $options['api_key'] ?? '';

        wp_localize_script('ppcustom-frontend', 'ppcustom', [
            'designId' => $design_id,
            'apiKey'   => $api_key
        ]);
    }
});

/**
 * Show buttons on single product page
 */
add_action('woocommerce_single_product_summary', function() {
    global $product;
    $button_mode = get_post_meta($product->get_id(), '_ppcustom_button_mode', true) ?: 'both';

    echo '<div class="ppcustom-buttons" style="margin-top:20px;">';

    if ($button_mode === 'both' || $button_mode === 'design') {
        echo '<button type="button" class="button pp-design-online" style="margin-right:10px;">Design Online</button>';
    }

    if ($button_mode === 'both' || $button_mode === 'upload') {
        echo '<button type="button" class="button pp-upload-artwork">Upload Artwork</button>';
        // Modal placeholder
        echo '<div id="pp-upload-modal" style="display:none;">';
        echo '<h3>Upload Artwork</h3>';
        echo '<input type="file" id="pp-upload-file">';
        echo '</div>';
    }

    echo '</div>';
}, 35);
