<?php
if (!defined('ABSPATH')) exit;

/**
 * Fetch PitchPrint designs using signature authentication.
 */
function ppcustom_fetch_pitchprint_designs() {

    $options = get_option('ppcustom_settings');
    $api_key = $options['api_key'] ?? '';
    $secret  = $options['secret_key'] ?? '';

    if (!$api_key || !$secret) {
        return false;
    }

    // Create timestamp and signature
    $timestamp = time();
    $signature = md5($api_key . $secret . $timestamp);

    $body = [
        'apiKey'    => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];

    $response = wp_remote_post(
        'https://api.pitchprint.io/runtime/fetch-design-categories',
        [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 20,
        ]
    );

    if (is_wp_error($response)) {
        error_log('PitchPrint design fetch error: ' . $response->get_error_message());
        return false;
    }

    $raw = wp_remote_retrieve_body($response);
    error_log('PitchPrint fetch-design-categories RAW: ' . $raw);
    $data = json_decode($raw, true);

    if (isset($data['sections']) && is_array($data['sections'])) {
        return $data['sections'];
    }

    return false;
}

/**
 * Add PitchPrint fields to WooCommerce product admin
 */
add_action('woocommerce_product_options_general_product_data', function() {

    $selected_design = get_post_meta(get_the_ID(), '_ppcustom_design_id', true);
    $button_mode = get_post_meta(get_the_ID(), '_ppcustom_button_mode', true) ?: 'both';

    echo '<div class="options_group">';

    // Button selector
    woocommerce_wp_select([
        'id'          => '_ppcustom_button_mode',
        'label'       => __('PitchPrint Buttons', 'ppcustom'),
        'options'     => [
            'both'    => 'Show Both Buttons',
            'design'  => 'Design Online Only',
            'upload'  => 'Upload Artwork Only',
        ],
        'desc_tip'    => true,
        'description' => __('Choose which PitchPrint buttons to show on the product page.', 'ppcustom'),
        'value'       => $button_mode
    ]);

    echo '<p class="form-field"><label for="_ppcustom_design_id">PitchPrint Design</label>';

    // Fetch designs using signature auth
    $sections = ppcustom_fetch_pitchprint_designs();
    if ($sections === false) {
        echo '<em>Please check your PitchPrint API Key and Secret in settings.</em>';
    } else {

        echo '<select id="_ppcustom_design_id" name="_ppcustom_design_id">';
        echo '<option value="">Select a design…</option>';

        foreach ($sections as $section) {
            $section_title = esc_html($section['title'] ?? 'Other');
            echo '<optgroup label="' . $section_title . '">';

            if (isset($section['designs']) && is_array($section['designs'])) {
                foreach ($section['designs'] as $design) {
                    $id = esc_attr($design['id']);
                    $title = esc_html($design['title']);
                    $selected = ($selected_design === $id) ? 'selected' : '';
                    echo "<option value='{$id}' {$selected}>{$title}</option>";
                }
            }

            echo '</optgroup>';
        }

        echo '</select>';
    }

    echo '</p>';
    echo '</div>';
});

/**
 * Save PitchPrint fields
 */
add_action('woocommerce_process_product_meta', function($post_id) {
    if (isset($_POST['_ppcustom_design_id'])) {
        update_post_meta(
            $post_id,
            '_ppcustom_design_id',
            sanitize_text_field($_POST['_ppcustom_design_id'])
        );
    }
    if (isset($_POST['_ppcustom_button_mode'])) {
        update_post_meta(
            $post_id,
            '_ppcustom_button_mode',
            sanitize_text_field($_POST['_ppcustom_button_mode'])
        );
    }
});
