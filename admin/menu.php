<?php
if (!defined('ABSPATH')) exit;

// Add PitchPrint Settings page to main admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'PitchPrint Settings',
        'PitchPrint',
        'manage_options',
        'ppcustom-settings',
        'ppcustom_settings_page',
        'dashicons-art',
        56
    );
});

// Render the PitchPrint settings page
function ppcustom_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['ppcustom_save'])) {
        update_option('ppcustom_settings', [
            'api_key'    => sanitize_text_field($_POST['api_key']),
            'secret_key' => sanitize_text_field($_POST['secret_key'])
        ]);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $options = get_option('ppcustom_settings');
    ?>
    <div class="wrap">
        <h1>PitchPrint Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>API Key</th>
                    <td><input type="text" name="api_key" value="<?php echo esc_attr($options['api_key'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Secret Key</th>
                    <td><input type="text" name="secret_key" value="<?php echo esc_attr($options['secret_key'] ?? ''); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit"><button type="submit" name="ppcustom_save" class="button-primary">Save Settings</button></p>
        </form>
    </div>
    <?php
}
