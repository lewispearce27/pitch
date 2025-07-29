<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_product_options_general_product_data', function() {
    echo '<div class="options_group">';
    // Design ID
    woocommerce_wp_text_input([
        'id' => '_ppcustom_design_id',
        'label' => 'PitchPrint Design ID',
        'desc_tip' => true,
        'description' => 'Enter the PitchPrint template ID for this product.'
    ]);

    // Button Display Mode
    woocommerce_wp_select([
        'id'      => '_ppcustom_button_mode',
        'label'   => 'PitchPrint Buttons',
        'options' => [
            'both'    => 'Show Both Buttons',
            'design'  => 'Design Online Only',
            'upload'  => 'Upload Artwork Only',
        ],
        'desc_tip' => true,
        'description' => 'Select which buttons to show on the product page.'
    ]);
    echo '</div>';
});

add_action('woocommerce_process_product_meta', function($post_id) {
    if (isset($_POST['_ppcustom_design_id'])) {
        update_post_meta($post_id, '_ppcustom_design_id', sanitize_text_field($_POST['_ppcustom_design_id']));
    }
    if (isset($_POST['_ppcustom_button_mode'])) {
        update_post_meta($post_id, '_ppcustom_button_mode', sanitize_text_field($_POST['_ppcustom_button_mode']));
    }
});
