<?php
/*
Plugin Name: PitchPrint Custom Integration
Description: Full integration of PitchPrint with WooCommerce, including admin settings, product options, and front-end buttons.
Version: 1.0.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Define plugin URL/Path for enqueuing
define('PPCUSTOM_URL', plugin_dir_url(__FILE__));
define('PPCUSTOM_PATH', plugin_dir_path(__FILE__));

// Debug log (for dev)
if (!function_exists('ppcustom_debug')) {
    function ppcustom_debug($msg) {
        if (WP_DEBUG === true) error_log($msg);
    }
}
ppcustom_debug('PitchPrint Custom Integration Loaded');

/* ========== ADMIN MENU: Settings Page ========== */
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
            'secret_key' => sanitize_text_field($_POST['secret_key']),
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

/* ========== HELPER: API SIGNATURE ========== */
function ppcustom_pitchprint_signature($api_key, $secret) {
    $timestamp = time();
    $signature = md5($api_key . $secret . $timestamp);
    return [$timestamp, $signature];
}

/* ========== FETCH CATEGORIES (API) ========== */
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

/* ========== FETCH DESIGNS (AJAX) ========== */
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

    $raw = wp_remote_retrieve_body($response);
    error_log('PitchPrint fetch-designs RAW: ' . $raw);

    $data = json_decode($raw, true);
    wp_send_json($data);
});

/* ========== ADMIN: Product Tab with PitchPrint Section ========== */
add_filter('woocommerce_product_data_tabs', function($tabs) {
    $tabs['pitchprint'] = [
        'label'    => __('PitchPrint', 'ppcustom'),
        'target'   => 'ppcustom_pitchprint_panel',
        'class'    => [],
        'priority' => 21,
    ];
    return $tabs;
}, 99);

add_action('woocommerce_product_data_panels', function() {
    global $post;
    $selected_design = get_post_meta($post->ID, '_ppcustom_design_id', true);
    $selected_cat    = get_post_meta($post->ID, '_ppcustom_category_id', true);
    $button_mode     = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';

    // Enqueue admin JS
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

    echo '<div id="ppcustom_pitchprint_panel" class="panel woocommerce_options_panel">';
    echo '<div class="options_group">';
    // Buttons
    woocommerce_wp_select([
        'id'      => '_ppcustom_button_mode',
        'label'   => __('PitchPrint Buttons', 'ppcustom'),
        'options' => [
            'both'   => 'Show Both Buttons',
            'design' => 'Design Online Only',
            'upload' => 'Upload Artwork Only',
        ],
        'value'   => $button_mode
    ]);
    // Category dropdown
    echo '<p class="form-field"><label for="_ppcustom_category_id">PitchPrint Category</label>';
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
    echo '</select></p>';
    // Design dropdown (AJAX populated)
    echo '<p class="form-field"><label for="_ppcustom_design_id">PitchPrint Design</label>';
    echo '<select id="_ppcustom_design_id" name="_ppcustom_design_id">';
    echo '<option value="">Select a design…</option>';
    echo '</select></p>';

    echo '</div></div>';
});

// Save meta fields
add_action('woocommerce_process_product_meta', function($post_id) {
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

/* ========== FRONTEND: Output Buttons on Product Page ========== */
add_action('woocommerce_single_product_summary', function() {
    global $post;
    if (!$post || $post->post_type !== 'product') return;

    $button_mode = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';
    $design_id   = get_post_meta($post->ID, '_ppcustom_design_id', true);

    // Only show if any button enabled and design set
    if (($button_mode == 'both' || $button_mode == 'design') && $design_id) {
        echo '<div class="ppcustom-buttons">';
        echo '<button type="button" class="button ppcustom-design-btn">Design Online</button>';
        echo '</div>';
    }
    if ($button_mode == 'both' || $button_mode == 'upload') {
        echo '<div class="ppcustom-buttons">';
        echo '<button type="button" class="button ppcustom-upload-btn">Upload Artwork</button>';
        echo '</div>';
    }
}, 25);

/* ========== FRONTEND: Enqueue JS and Pass Settings ========== */
add_action('wp_enqueue_scripts', function() {
    if (!is_product()) return;
    global $post;
    if (!$post) return;

    $product_id = $post->ID;
    $design_id  = get_post_meta($product_id, '_ppcustom_design_id', true);
    $options    = get_option('ppcustom_settings');
    $api_key    = $options['api_key'] ?? '';

    wp_enqueue_style('ppcustom-frontend-css', PPCUSTOM_URL . 'public/css/custom.css', [], null);

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

    wp_localize_script('ppcustom-frontend', 'ppcustom', [
        'designId' => $design_id,
        'apiKey'   => $api_key
    ]);
});

/* ========== End of File ========== */
