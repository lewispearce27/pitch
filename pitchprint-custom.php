<?php
/**
 * Plugin Name: PitchPrint Custom Integration
 * Description: Integrates PitchPrint with WooCommerce products (custom build).
 * Version: 1.0.0
 * Author: Lewis Pearce
 * Text Domain: pitchprint-custom
 */

if (!defined('ABSPATH')) {
    exit;
}

class PitchPrintCustom {

    public function __construct() {
        error_log('PitchPrint Custom Integration Loaded');

        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        // ADD THIS ACTION TO OUTPUT BUTTONS
        add_action('woocommerce_after_add_to_cart_button', [$this, 'output_pitchprint_buttons']);
    }

    /**
     * Add a PitchPrint meta box to products.
     */
    public function add_meta_box() {
        add_meta_box(
            'pitchprint_meta',
            'PitchPrint Settings',
            [$this, 'render_meta_box'],
            'product',
            'side'
        );
    }

    /**
     * Render the meta box content.
     */
    public function render_meta_box($post) {
        $design_id = get_post_meta($post->ID, '_ppcustom_design_id', true);
        wp_nonce_field('ppcustom_meta_box', 'ppcustom_meta_box_nonce');
        ?>
        <p><label for="ppcustom_design_id">PitchPrint Design ID</label></p>
        <input type="text" name="ppcustom_design_id" id="ppcustom_design_id"
               value="<?php echo esc_attr($design_id); ?>" style="width:100%;" />
        <?php
    }

    /**
     * Save the meta box content.
     */
    public function save_meta($post_id) {
        if (!isset($_POST['ppcustom_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['ppcustom_meta_box_nonce'], 'ppcustom_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['ppcustom_design_id'])) {
            update_post_meta(
                $post_id,
                '_ppcustom_design_id',
                sanitize_text_field($_POST['ppcustom_design_id'])
            );
        }
    }

    /**
     * Enqueue front-end scripts and load PitchPrint.
     */
    public function enqueue_frontend_scripts() {
        // Only load on single product pages
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        global $post;
        if (empty($post) || $post->post_type !== 'product') {
            return;
        }

        // Ensure we have a proper WC_Product object
        $product = wc_get_product($post->ID);
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }

        $product_id = $product->get_id();
        $design_id  = get_post_meta($product_id, '_ppcustom_design_id', true);

        // Plugin options (API key etc.)
        $options = get_option('ppcustom_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';

        // Enqueue PitchPrint SDK
        wp_enqueue_script(
            'pitchprint-sdk',
            'https://pitchprint.io/rsc/js/pitchprint.js',
            [],
            null,
            true
        );

        // Enqueue custom JS
        wp_enqueue_script(
            'ppcustom-frontend',
            plugins_url('public/js/custom.js', __FILE__),
            ['jquery', 'pitchprint-sdk'],
            null,
            true
        );

        // Enqueue CSS
        wp_enqueue_style(
            'ppcustom-frontend-css',
            plugins_url('public/css/custom.css', __FILE__),
            [],
            null
        );

        // Pass data to JS
        wp_localize_script('ppcustom-frontend', 'ppcustom', [
            'designId' => $design_id,
            'apiKey'   => $api_key
        ]);
    }

    /**
     * Output PitchPrint buttons on product page (after Add to Cart).
     */
    public function output_pitchprint_buttons() {
        global $post;
        if (!is_product() || empty($post) || $post->post_type !== 'product') {
            return;
        }

        // Fetch meta to decide which buttons to show (future proofing: both for now)
        $button_mode = get_post_meta($post->ID, '_ppcustom_button_mode', true) ?: 'both';

        echo '<div class="ppcustom-buttons">';
        if ($button_mode === 'both' || $button_mode === 'design') {
            echo '<button type="button" class="button ppcustom-design-btn" style="margin-right:8px;">Design Online</button>';
        }
        if ($button_mode === 'both' || $button_mode === 'upload') {
            echo '<button type="button" class="button ppcustom-upload-btn">Upload Artwork</button>';
        }
        echo '</div>';
    }
}

new PitchPrintCustom();
