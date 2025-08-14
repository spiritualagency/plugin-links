function loadAdminSettings() {
    // Retrieve settings from the WordPress options table
    $settings = get_option('plugin_links_settings', array());

    // Default settings if none are found
    $defaultSettings = array(
        'zip_enabled' => true,
        'compression_level' => 5,
        'default_role_permissions' => array('administrator')
    );

    // Merge defaults with actual settings
    return wp_parse_args($settings, $defaultSettings);
}

// Function to validate and sanitize settings data
function sanitizeSettingsData($settingsData) {
    $settingsData['zip_enabled'] = isset($settingsData['zip_enabled']) ? boolval($settingsData['zip_enabled']) : false;
    $settingsData['compression_level'] = isset($settingsData['compression_level']) && absint($settingsData['compression_level']) >= 1 && absint($settingsData['compression_level']) <= 9 ? absint($settingsData['compression_level']) : 5;
    return array_map('sanitize_text_field', $settingsData);
}

// Function to save settings
function saveSettings($settingsData) {
    // Validate and sanitize settings data
    $settingsData = sanitizeSettingsData($settingsData);

    // Update settings in the WordPress options table
    update_option('plugin_links_settings', $settingsData);
}

// Function to manage permissions
function managePermissions($userRole) {
    // Load current settings
    $settings = loadAdminSettings();

    // Check if the user role exists in the settings' permissions
    return in_array($userRole, $settings['default_role_permissions']);
}

// Initialize admin settings page
function initAdminPage() {
    add_action('admin_menu', function() {
        add_menu_page(
            'Plugin Links Settings', // Page title
            'Plugin Links',          // Menu title
            'manage_options',        // Capability
            'plugin_links',          // Menu slug
            'renderAdminSettingsPage' // Function to display the page
        );
    });
}

// Render admin settings page
function renderAdminSettingsPage() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    // Load current settings
    $settings = loadAdminSettings();

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php wp_nonce_field('plugin_links_settings_nonce_action', 'plugin_links_settings_nonce'); ?>
            <?php settings_fields('plugin_links_settings_group'); ?>
            <?php do_settings_sections('plugin_links'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Zip Links</th>
                    <td><input type="checkbox" name="plugin_links_settings[zip_enabled]" value="1" <?php checked($settings['zip_enabled'], true); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Compression Level</th>
                    <td><input type="number" name="plugin_links_settings[compression_level]" value="<?php echo esc_attr($settings['compression_level']); ?>" min="1" max="9" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register the admin settings
function registerAdminSettings() {
    register_setting('plugin_links_settings_group', 'plugin_links_settings', 'sanitizeSettingsData');
}

// Hook initialization functions
add_action('admin_init', 'registerAdminSettings');
add_action('admin_menu', 'initAdminPage');