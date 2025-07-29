<?php
if (!defined('ABSPATH')) exit;

/**
 * Get PitchPrint auth token using API + Secret.
 * Token is cached for 15 minutes.
 */
function ppcustom_get_pitchprint_token() {
    $cached = get_transient('ppcustom_pitchprint_token');
    if ($cached) {
        return $cached;
    }

    $options = get_option('ppcustom_settings');
    $api_key = $options['api_key'] ?? '';
    $secret  = $options['secret_key'] ?? '';

    if (!$api_key || !$secret) {
        return false;
    }

    $response = wp_remote_post(
        'https://api.pitchprint.io/runtime/auth',
        [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode([
                'apiKey' => $api_key,
                'secret' => $secret
            ]),
            'timeout' => 15,
        ]
    );

    if (is_wp_error($response)) {
        error_log('PitchPrint auth error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    error_log('PitchPrint Auth Response: ' . $body);
    $data = json_decode($body, true);

    if (isset($data['token'])) {
        set_transient('ppcustom_pitchprint_token', $data['token'], 15 * MINUTE_IN_SECONDS);
        return $data['token'];
    }

    return false;
}

/**
 * Add PitchPrint fields to WooCommerce product admin
 */
add_action('woocommerce_product_options_general_product_data', function() {

    $options = get_option('ppcustom_settings');
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

    // Authenticate and fetch designs
    $token = ppcustom_get_pitchprint_token();
    if (!$token) {
        echo '<em>Please check your PitchPrint API Key and Secret in settings.</em>';
    } else {
        echo '<select id="_ppcustom_design_id" name="_ppcustom_design_id">';
        echo '<option value="">Select a design…</option>';

        // Get designs using the token
        $response = wp_remote_get(
            'https://api.pitchprint.io/runtime/designs',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'timeout' => 15,
            ]
        );

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            error_log('PitchPrint Designs Response: ' . $body);

            $data = json_decode($body, true);

            // Detect structure (sections -> designs)
            if (isset($data['sections']) && is_array($data['sections']) && count($data['sections']) > 0) {
                foreach ($data['sections'] as $section) {
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
            } else {
                echo '<option value="">No designs found.</option>';
            }

        } else {
            error_log('PitchPrint API Error: ' . $response->get_error_message());
            echo '<option value="">Failed to fetch designs.</option>';
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
