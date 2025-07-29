<?php
if (!defined('ABSPATH')) exit;

/**
 * Add PitchPrint fields to WooCommerce product admin
 */
add_action('woocommerce_product_options_general_product_data', function() {

    error_log('PitchPrint product meta loaded'); // debug log

    echo '<div class="options_group">';

    // Template ID field
    woocommerce_wp_text_input([
        'id'          => '_ppcustom_design_id',
        'label'       => __('PitchPrint Design ID', 'ppcustom'),
        'desc_tip'    => true,
        'description' => __('Enter the PitchPrint template ID for this product.', 'ppcustom'),
    ]);

    // Dropdown for buttons
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
    ]);

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
