<?php
if (!defined('ABSPATH')) exit;

// Helper: PitchPrint Signature
function ppcustom_pitchprint_signature($api_key, $secret) {
    $timestamp = time();
    $signature = md5($api_key . $secret . $timestamp);
    return [$timestamp, $signature];
}

// Fetch PitchPrint Categories (API)
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

// Fetch designs for a category (AJAX)
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

// Add fields to WooCommerce product admin (NEW: wrap in tabbed section "PitchPrint")
add_action('woocommerce_product_data_panels', function() {
    global $post;
    $selected_design = get_post_meta($post->ID, '_ppcustom_design_id', true);
    $selected_cat    = get_post_meta($post->ID, '_ppcustom_category_id', true);
    $button_mode     = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';

    echo '<div id="ppcustom_tab" class="panel woocommerce_options_panel">';
    echo '<h2>PitchPrint</h2>';

    // Button mode dropdown
    woocommerce_wp_select([
        'id'          => '_ppcustom_button_mode',
        'label'       => __('PitchPrint Buttons', 'ppcustom'),
        'options'     => [
            'both'    => 'Show Both Buttons',
            'design'  => 'Design Online Only',
            'upload'  => 'Upload Artwork Only',
        ],
        'value'       => $button_mode
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

    // Design dropdown (AJAX loaded)
    echo '<p class="form-field"><label for="_ppcustom_design_id">PitchPrint Design</label>';
    echo '<select id="_ppcustom_design_id" name="_ppcustom_design_id">';
    echo '<option value="">Select a design…</option>';
    echo '</select></p>';

    echo '</div>';
});

// Add a new tab to the product data tabs for PitchPrint
add_filter('woocommerce_product_data_tabs', function($tabs) {
    $tabs['ppcustom_tab'] = [
        'label'    => __('PitchPrint', 'ppcustom'),
        'target'   => 'ppcustom_tab',
        'priority' => 50,
        'class'    => [],
    ];
    return $tabs;
});

// Enqueue admin JS for AJAX loading of designs
add_action('admin_enqueue_scripts', function($hook) {
    global $post;
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_script(
            'ppcustom-admin-js',
            PPCUSTOM_URL . 'admin/js/ppcustom-admin.js',
            ['jquery'],
            null,
            true
        );
        wp_localize_script('ppcustom-admin-js', 'ppcustom_admin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'selectedDesign' => get_post_meta($post->ID ?? 0, '_ppcustom_design_id', true)
        ]);
    }
});

// Save product-level PitchPrint fields
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
