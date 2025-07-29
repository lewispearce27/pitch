<?php
if (!defined('ABSPATH')) exit;

/**
 * Add PitchPrint fields to WooCommerce product admin
 */
add_action('woocommerce_product_options_general_product_data', function() {

    $options = get_option('ppcustom_settings');
    $api_key = $options['api_key'] ?? '';
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

    // Design dropdown
    echo '<p class="form-field"><label for="_ppcustom_design_id">PitchPrint Design</label>';

    if (!$api_key) {
        echo '<em>Please set your API Key in the PitchPrint settings page.</em>';
    } else {
        echo '<select id="_ppcustom_design_id" name="_ppcustom_design_id">';
        echo '<option value="">Fetching designs...</option>';

        // Request designs from PitchPrint API (v3)
        $response = wp_remote_get(
            'https://api.pitchprint.io/api/designs',
            [
                'headers' => [
                    'x-api-key' => $api_key,
                ],
                'timeout' => 15,
            ]
        );

        if (!is_wp_error($response)) {
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            // Log the response for debugging
            error_log('PitchPrint API Status: ' . $status_code);
            error_log('PitchPrint API RAW Response: ' . $body);

            // For now, we just show a message
            echo '<option value="">Check debug.log for raw API response</option>';
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
