<?php
if (!defined('ABSPATH')) exit;

/**
 * Adds PitchPrint fields to WooCommerce product admin
 */
add_action('woocommerce_product_options_general_product_data', function() {

    $options = get_option('ppcustom_settings');
    $api_key = $options['api_key'] ?? '';
    $selected_design = get_post_meta(get_the_ID(), '_ppcustom_design_id', true);
    $button_mode = get_post_meta(get_the_ID(), '_ppcustom_button_mode', true) ?: 'both';

    echo '<div class="options_group">';

    // Button Mode Selector
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

    // Design Dropdown
    echo '<p class="form-field"><label for="_ppcustom_design_id">PitchPrint Design</label>';

    if (!$api_key) {
        echo '<em>Please set your API Key in the PitchPrint settings page.</em>';
    } else {
        echo '<select id="_ppcustom_design_id" name="_ppcustom_design_id">';
        echo '<option value="">Select a design…</option>';

        $response = wp_remote_get('https://api.pitchprint.io/runtime/designs?apiKey=' . urlencode($api_key));

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['sections']) && is_array($data['sections'])) {

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
