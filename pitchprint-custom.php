<?php
/**
 * Plugin Name: PitchPrint Custom Integration
 * Description: Integrates PitchPrint with WooCommerce for artwork upload and online design per product.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

define('PPCUSTOM_PATH', plugin_dir_path(__FILE__));
define('PPCUSTOM_URL', plugin_dir_url(__FILE__));

// Log plugin loaded for debug
error_log('PitchPrint Custom Integration Loaded');

/**
 * Admin menu for settings
 */
add_action('admin_menu', function() {
    add_menu_page(
        'PitchPrint Settings',
        'PitchPrint',
        'manage_options',
        'ppcustom-settings',
        'ppcustom_settings_page',
        'dashicons-art',
        56
    );
});

function ppcustom_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['ppcustom_save'])) {
        update_option('ppcustom_settings', [
            'api_key'    => sanitize_text_field($_POST['api_key']),
            'secret_key' => sanitize_text_field($_POST['secret_key'])
        ]);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $options = get_option('ppcustom_settings');
    ?>
    <div class="wrap">
        <h1>PitchPrint Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>API Key</th>
                    <td><input type="text" name="api_key" value="<?php echo esc_attr($options['api_key'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Secret Key</th>
                    <td><input type="text" name="secret_key" value="<?php echo esc_attr($options['secret_key'] ?? ''); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit"><button type="submit" name="ppcustom_save" class="button-primary">Save Settings</button></p>
        </form>
    </div>
    <?php
}

/**
 * Helper: Generate PitchPrint signature
 */
function ppcustom_pitchprint_signature($api_key, $secret) {
    $timestamp = time();
    $signature = md5($api_key . $secret . $timestamp);
    return [$timestamp, $signature];
}

/**
 * Fetch PitchPrint categories
 */
function ppcustom_fetch_pitchprint_categories() {
    $options = get_option('ppcustom_settings');
    $api_key = $options['api_key'] ?? '';
    $secret  = $options['secret_key'] ?? '';

    if (!$api_key || !$secret) return false;

    [$timestamp, $signature] = ppcustom_pitchprint_signature($api_key, $secret);

    $body = [
        'apiKey'    => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];

    $response = wp_remote_post(
        'https://api.pitchprint.io/runtime/fetch-design-categories',
        [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode($body),
            'timeout' => 20,
        ]
    );

    if (is_wp_error($response)) return false;

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return isset($data['data']) ? $data['data'] : false;
}

/**
 * Fetch designs for a category (AJAX handler)
 */
add_action('wp_ajax_ppcustom_fetch_designs', function() {
    $category_id = sanitize_text_field($_POST['categoryId'] ?? '');
    $options = get_option('ppcustom_settings');
    $api_key = $options['api_key'] ?? '';
    $secret  = $options['secret_key'] ?? '';

    if (!$category_id || !$api_key || !$secret) {
        wp_send_json_error(['message' => 'Missing data']);
    }

    [$timestamp, $signature] = ppcustom_pitchprint_signature($api_key, $secret);

    $body = [
        'apiKey'     => $api_key,
        'timestamp'  => $timestamp,
        'signature'  => $signature,
        'categoryId' => $category_id
    ];

    $response = wp_remote_post(
        'https://api.pitchprint.io/runtime/fetch-designs',
        [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode($body),
            'timeout' => 20,
        ]
    );

    if (is_wp_error($response)) {
        error_log('PitchPrint fetch-designs error: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'Request error']);
    }

    // Log raw response
    $raw = wp_remote_retrieve_body($response);
    error_log('PitchPrint fetch-designs RAW: ' . $raw);

    $data = json_decode($raw, true);
    wp_send_json($data);
});

/**
 * Add PitchPrint panel to WooCommerce product admin
 */
add_action('add_meta_boxes', function() {
    add_meta_box(
        'ppcustom_meta',
        'PitchPrint',
        'ppcustom_render_meta_box',
        'product',
        'normal',
        'default'
    );
});

function ppcustom_render_meta_box($post) {
    $selected_design = get_post_meta($post->ID, '_ppcustom_design_id', true);
    $selected_cat    = get_post_meta($post->ID, '_ppcustom_category_id', true);
    $button_mode     = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';

    wp_nonce_field('ppcustom_meta_box', 'ppcustom_meta_box_nonce');

    wp_enqueue_script(
        'ppcustom-admin-js',
        PPCUSTOM_URL . 'admin/js/ppcustom-admin.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('ppcustom-admin-js', 'ppcustom_admin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'selectedDesign' => $selected_design
    ]);

    echo '<div class="ppcustom-admin-panel">';
    echo '<p><strong>PitchPrint Buttons</strong></p>';
    echo '<select name="_ppcustom_button_mode" id="_ppcustom_button_mode">';
    echo '<option value="both" ' . selected($button_mode, 'both', false) . '>Show Both Buttons</option>';
    echo '<option value="design" ' . selected($button_mode, 'design', false) . '>Design Online Only</option>';
    echo '<option value="upload" ' . selected($button_mode, 'upload', false) . '>Upload Artwork Only</option>';
    echo '</select>';

    echo '<p><strong>PitchPrint Category</strong></p>';
    echo '<select id="_ppcustom_category_id" name="_ppcustom_category_id">';
    echo '<option value="">Select a category…</option>';
    $categories = ppcustom_fetch_pitchprint_categories();
    if ($categories) {
        foreach ($categories as $cat) {
            $cid = esc_attr($cat['id']);
            $title = esc_html($cat['title']);
            $selected = ($selected_cat === $cid) ? 'selected' : '';
            echo "<option value='{$cid}' {$selected}>{$title}</option>";
        }
    }
    echo '</select>';

    // Design dropdown (AJAX populated)
    echo '<p><strong>PitchPrint Design</strong></p>';
    echo '<select id="_ppcustom_design_id" name="_ppcustom_design_id">';
    echo '<option value="">Select a design…</option>';
    if ($selected_design) {
        echo '<option value="' . esc_attr($selected_design) . '" selected>' . esc_html($selected_design) . '</option>';
    }
    echo '</select>';

    echo '</div>';
}

/**
 * Save product meta
 */
add_action('save_post_product', function($post_id) {
    if (!isset($_POST['ppcustom_meta_box_nonce']) ||
        !wp_verify_nonce($_POST['ppcustom_meta_box_nonce'], 'ppcustom_meta_box')) {
        return;
    }

    if (isset($_POST['_ppcustom_design_id'])) {
        update_post_meta($post_id, '_ppcustom_design_id', sanitize_text_field($_POST['_ppcustom_design_id']));
    }
    if (isset($_POST['_ppcustom_category_id'])) {
        update_post_meta($post_id, '_ppcustom_category_id', sanitize_text_field($_POST['_ppcustom_category_id']));
    }
    if (isset($_POST['_ppcustom_button_mode'])) {
        update_post_meta($post_id, '_ppcustom_button_mode', sanitize_text_field($_POST['_ppcustom_button_mode']));
    }
});

/**
 * ENQUEUE ASSETS
 */
add_action('admin_enqueue_scripts', function($hook) {
    // Only enqueue on product edit page
    if ('post.php' == $hook || 'post-new.php' == $hook) {
        wp_enqueue_style('ppcustom-admin', PPCUSTOM_URL . 'admin/css/custom-admin.css');
    }
});
add_action('wp_enqueue_scripts', function() {
    if (!is_product()) return;
    wp_enqueue_style('ppcustom-css', PPCUSTOM_URL . 'public/css/custom.css');
    wp_enqueue_script('pitchprint-sdk', 'https://pitchprint.io/rsc/js/pitchprint.js', [], null, true);
    wp_enqueue_script('ppcustom-frontend', PPCUSTOM_URL . 'public/js/custom.js', ['jquery', 'pitchprint-sdk'], null, true);

    // Localise product meta for JS
    global $post;
    $options = get_option('ppcustom_settings');
    wp_localize_script('ppcustom-frontend', 'ppcustom', [
        'designId' => get_post_meta($post->ID, '_ppcustom_design_id', true),
        'apiKey'   => $options['api_key'] ?? ''
    ]);
});

/**
 * FRONTEND BUTTONS OUTPUT
 */
add_action('woocommerce_single_product_summary', function() {
    global $post;
    if (empty($post) || $post->post_type !== 'product') return;

    $button_mode = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';
    $design_id   = get_post_meta($post->ID, '_ppcustom_design_id', true);

    $output = '';
    if (($button_mode == 'both' || $button_mode == 'design') && $design_id) {
        $output .= '<div class="ppcustom-buttons"><button type="button" class="button ppcustom-design-btn">Design Online</button></div>';
    }
    if ($button_mode == 'both' || $button_mode == 'upload') {
        $output .= '<div class="ppcustom-buttons"><button type="button" class="button ppcustom-upload-btn">Upload Artwork</button></div>';
    }

    echo $output;
}, 25);
