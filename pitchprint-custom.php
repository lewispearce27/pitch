<?php
/**
 * Plugin Name: PitchPrint Custom Integration
 * Description: Custom integration of PitchPrint with WooCommerce. Adds Design Online and Upload Artwork buttons.
 * Version: 1.1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Define constants
define('PPCUSTOM_PATH', plugin_dir_path(__FILE__));
define('PPCUSTOM_URL', plugin_dir_url(__FILE__));

// Include admin functionality
if (is_admin()) {
    require_once PPCUSTOM_PATH . 'admin/menu.php';
    require_once PPCUSTOM_PATH . 'admin/product-meta.php';
}

/**
 * Enqueue scripts and pass settings to JS
 */
add_action('wp_enqueue_scripts', function() {
    if (!is_product()) return;

    wp_enqueue_style(
        'ppcustom-style',
        PPCUSTOM_URL . 'public/css/custom.css'
    );

    wp_enqueue_script(
        'pp-sdk',
        'https://pitchprint.io/rsc/js/client.js',
        [],
        null,
        true
    );

    wp_enqueue_script(
        'ppcustom-js',
        PPCUSTOM_URL . 'public/js/custom.js',
        ['jquery','pp-sdk'],
        null,
        true
    );

    global $post;
    $design_id = get_post_meta($post->ID, '_ppcustom_design_id', true);
    $options   = get_option('ppcustom_settings');

    wp_localize_script('ppcustom-js', 'ppcustom', [
        'designId' => $design_id,
        'apiKey'   => $options['api_key'] ?? '',
        'ajaxUrl'  => admin_url('admin-ajax.php')
    ]);
});

/**
 * Add custom PitchPrint buttons on the product page
 */
add_action('woocommerce_after_add_to_cart_button', function() {
    global $post;

    $mode = get_post_meta($post->ID, '_ppcustom_button_mode', true);
    if (!$mode) {
        $mode = 'both'; // default
    }

    echo '<div class="ppcustom-buttons">';

    // Design button
    if ($mode === 'both' || $mode === 'design') {
        echo '<button type="button" class="button pp-design-online">Design Online</button>';
    }

    // Upload button
    if ($mode === 'both' || $mode === 'upload') {
        echo '<button type="button" class="button pp-upload-artwork">Upload Artwork</button>';
    }

    echo '</div>';

    // Modal only if upload option is active
    if ($mode === 'both' || $mode === 'upload') {
        echo '<div id="pp-upload-modal" style="display:none;">
                <input type="file" id="pp-file-input" />
                <button id="pp-upload-submit" class="button">Start</button>
              </div>';
    }
});

/**
 * Handle upload via AJAX
 */
add_action('wp_ajax_ppcustom_upload', 'ppcustom_upload');
add_action('wp_ajax_nopriv_ppcustom_upload', 'ppcustom_upload');

function ppcustom_upload() {
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $uploaded = wp_handle_upload($_FILES['file'], ['test_form' => false]);

    if (isset($uploaded['url'])) {
        wp_send_json(['url' => $uploaded['url']]);
    } else {
        wp_send_json_error(['message' => 'Upload failed']);
    }
}
