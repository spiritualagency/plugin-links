function initializePlugin() {
    add_action('init', 'registerPluginHooks');
}

/**
 * Register hooks for plugin functionality
 */
function registerPluginHooks() {
    add_action('admin_post_generate_plugin_link', 'handleRequest');
}

/**
 * Handles incoming requests
 *
 * @param array $requestData
 */
function handleRequest() {
    $requestData = $_POST;
    
    if (!validateNonce($requestData['nonce'])) {
        wp_die(__('Invalid request.', 'plugin-links'));
    }

    $pluginIds = isset($requestData['plugin_ids']) ? explode(',', $requestData['plugin_ids']) : [];
    $expiration = isset($requestData['expiration']) ? intval($requestData['expiration']) : PLUGIN_LINK_EXPIRATION;
    
    $shareableLink = generateShareableLink($pluginIds, $expiration);
    
    if ($shareableLink) {
        wp_redirect($shareableLink);
        exit;
    }

    wp_die(__('Failed to generate plugin link.', 'plugin-links'));
}

/**
 * Validates the nonce for security
 *
 * @param string $nonce
 * @return bool
 */
function validateNonce($nonce) {
    return wp_verify_nonce($nonce, PLUGIN_NONCE_ACTION);
}

/**
 * Generates a shareable link for downloading selected plugins
 *
 * @param array $pluginIds
 * @param int $expiration
 * @return string|null
 */
function generateShareableLink($pluginIds, $expiration) {
    if (empty($pluginIds)) {
        return null;
    }

    // Create zip file of selected plugins in uploads directory
    $zip = new ZipArchive();
    $uploadDir = wp_upload_dir();
    $zipPath = $uploadDir['basedir'] . '/plugin-archive-' . time() . '.zip';

    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        return null;
    }

    foreach ($pluginIds as $pluginId) {
        $pluginPath = WP_PLUGIN_DIR . '/' . $pluginId;
        if (is_dir($pluginPath)) {
            $pluginFiles = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pluginPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($pluginFiles as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen(WP_PLUGIN_DIR) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
    }

    $zip->close();

    // Generate the shareable link with expiration
    // Implement cleanup mechanism for zip files
    registerCleanupCron($zipPath, $expiration);
    $downloadLink = generateTemporaryLink($zipPath, $expiration);
    
    return $downloadLink ? $downloadLink : null;
}

/**
 * Registers a cleanup cron job to delete zip after expiration
 *
 * @param string $zipPath
 * @param int $expiration
 */
function registerCleanupCron($zipPath, $expiration) {
    wp_schedule_single_event(time() + $expiration, 'delete_expired_zip', [$zipPath]);
}

add_action('delete_expired_zip', 'deleteZipFile');

/**
 * Deletes the specified zip file
 *
 * @param string $zipPath
 */
function deleteZipFile($zipPath) {
    if (file_exists($zipPath)) {
        unlink($zipPath);
    }
}

// Dummy implementation of generateTemporaryLink
function generateTemporaryLink($zipPath, $expiration) {
    // Ideally, this function should generate a temporary download URL and apply expiration logic
    return 'https://example.com/download.php?file=' . basename($zipPath);
}

// Initialize plugin
initializePlugin();