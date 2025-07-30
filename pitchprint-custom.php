<?php
/*
Plugin Name: PitchPrint Custom Integration
Description: PitchPrint and WooCommerce custom integration (admin fields, menu, API, and frontend).
Version: 1.0
Author: Lewis Pearce
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin URL for asset loading
if (!defined('PPCUSTOM_URL')) {
    define('PPCUSTOM_URL', plugin_dir_url(__FILE__));
}

// --- Include all admin and extra files ---
require_once __DIR__ . '/admin/menu.php';
require_once __DIR__ . '/admin/product-meta.php';

// --- Main class definition ---
// (Your original class code here)
class PitchPrintCustom {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        // Enqueue public CSS for the buttons/modal
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_styles']);
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
            PPCUSTOM_URL . 'public/js/custom.js',
            ['jquery', 'pitchprint-sdk'],
            null,
            true
        );

        // Pass data to JS
        wp_localize_script('ppcustom-frontend', 'ppcustom', [
            'designId' => $design_id,
            'apiKey'   => $api_key
        ]);
    }

    /**
     * Enqueue front-end styles.
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style(
            'ppcustom-frontend',
            PPCUSTOM_URL . 'public/css/custom.css',
            [],
            null
        );
    }
}

// Instantiate the main plugin class
new PitchPrintCustom();
