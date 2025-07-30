<?php
/**
 * Plugin Name: PitchPrint Custom Integration
 * Description: Adds PitchPrint integration with Design Online and Artwork Upload buttons to WooCommerce products.
 * Version: 1.0.0
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('PPCUSTOM_URL', plugin_dir_url(__FILE__));
define('PPCUSTOM_PATH', plugin_dir_path(__FILE__));

// Admin Menu - API Keys Page
add_action('admin_menu', function() {
    add_menu_page('PitchPrint Settings', 'PitchPrint', 'manage_options', 'pitchprint-settings', 'ppcustom_admin_page');
});
function ppcustom_admin_page() {
    ?>
    <div class="wrap">
        <h1>PitchPrint API Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ppcustom-settings');
            do_settings_sections('ppcustom-settings');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td><input type="text" name="ppcustom_api_key" value="<?php echo esc_attr(get_option('ppcustom_api_key')); ?>" size="40" /></td>
                </tr>
                <tr>
                    <th scope="row">Secret Key</th>
                    <td><input type="text" name="ppcustom_secret_key" value="<?php echo esc_attr(get_option('ppcustom_secret_key')); ?>" size="40" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
add_action('admin_init', function() {
    register_setting('ppcustom-settings', 'ppcustom_api_key');
    register_setting('ppcustom-settings', 'ppcustom_secret_key');
});

// Add Product Meta Box
add_action('add_meta_boxes', function() {
    add_meta_box(
        'pitchprint-meta-box',
        'PitchPrint',
        'ppcustom_product_meta_box',
        'product',
        'side',
        'default'
    );
});
function ppcustom_product_meta_box($post) {
    $template = get_post_meta($post->ID, '_ppcustom_template', true);
    ?>
    <label for="ppcustom_template">PitchPrint Template ID:</label>
    <input type="text" name="ppcustom_template" id="ppcustom_template" value="<?php echo esc_attr($template); ?>" style="width:100%;">
    <?php
}
add_action('save_post_product', function($post_id) {
    if (isset($_POST['ppcustom_template'])) {
        update_post_meta($post_id, '_ppcustom_template', sanitize_text_field($_POST['ppcustom_template']));
    }
});

// FRONTEND: Enqueue Scripts
add_action('wp_enqueue_scripts', function() {
    if (is_product()) {
        // PitchPrint SDK
        wp_enqueue_script(
            'pitchprint-js',
            'https://pitchprint.io/rsc/js/pitchprint.js',
            array(),
            null,
            true
        );
        // Custom JS (depends on PitchPrint)
        wp_enqueue_script(
            'ppcustom-custom-js',
            PPCUSTOM_URL . 'js/custom.js',
            array('pitchprint-js', 'jquery'),
            time(), // force refresh during development
            true
        );
        // Custom CSS for buttons (optional)
        wp_enqueue_style(
            'ppcustom-css',
            PPCUSTOM_URL . 'css/custom.css',
            array(),
            '1.0'
        );
    }
});

// FRONTEND: Output Buttons on Product Page
add_action('woocommerce_after_add_to_cart_form', function() {
    global $post;
    $template_id = get_post_meta($post->ID, '_ppcustom_template', true);
    if (!$template_id) return;
    ?>
    <div id="ppcustom-buttons" style="margin:20px 0;">
        <button id="ppcustom-design-online" data-template="<?php echo esc_attr($template_id); ?>" class="button" style="margin-right:10px;">
            Design Online
        </button>
        <input type="file" id="ppcustom-upload-artwork" style="display:none;">
        <button id="ppcustom-upload-btn" class="button">
            Upload Artwork
        </button>
        <div id="ppcustom-upload-status" style="margin-top:10px;color:#009900;"></div>
    </div>
    <?php
});
