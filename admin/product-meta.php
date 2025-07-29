<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_product_options_general_product_data', function() {
    echo '<div class="options_group">';
    woocommerce_wp_text_input([
        'id' => '_ppcustom_design_id',
        'label' => 'PitchPrint Design ID',
        'desc_tip' => true,
        'description' => 'Enter the PitchPrint template ID for this product.'
    ]);
    echo '</div>';
});

add_action('woocommerce_process_product_meta', function($post_id) {
    if (isset($_POST['_ppcustom_design_id'])) {
        update_post_meta($post_id, '_ppcustom_design_id', sanitize_text_field($_POST['_ppcustom_design_id']));
    }
});
