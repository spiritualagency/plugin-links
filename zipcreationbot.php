public function __construct($expirationTime = 3600) {
        $this->uploadDir = wp_upload_dir()['path'];
        $this->expirationTime = $expirationTime; // Parameterized expiration time
    }

    public function createZip($pluginIds) {
        $zipFilePath = $this->uploadDir . '/plugins_' . time() . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Could not create ZIP file.');
        }

        foreach ($pluginIds as $pluginId) {
            $this->addPluginToZip($pluginId, $zip);
        }

        $zip->close();
        return $this->setExpirationOnLink($zipFilePath);
    }

    private function addPluginToZip($pluginId, $zip) {
        $pluginDir = WP_PLUGIN_DIR . '/' . sanitize_file_name($pluginId);
        $this->zipDirectory($pluginDir, $pluginId, $zip);
    }

    private function zipDirectory($directory, $baseName, $zip) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $info) {
            if (!$info->isDir()) {
                $filePath = $info->getRealPath();
                $relativePath = $baseName . '/' . substr($filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function setExpirationOnLink($zipFilePath) {
        $downloadUrl = admin_url('admin-ajax.php?action=download_plugin_zip&file=' . basename($zipFilePath));
        
        add_shortcode('download_plugins_link', function() use ($downloadUrl) {
            return '<a href="' . esc_url($downloadUrl) . '" target="_blank">Download Plugins</a>';
        });
        
        if (!wp_next_scheduled('delete_expired_zip', array($zipFilePath))) {
            wp_schedule_single_event(time() + $this->expirationTime, 'delete_expired_zip', array($zipFilePath));
        }
        
        add_action('delete_expired_zip', array($this, 'deleteExpiredZip'));

        return $downloadUrl;
    }
    
    public function deleteExpiredZip($zipFilePath) {
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
    }
}

$zipCreationBot = new ZipCreationBot();

add_action('wp_ajax_download_plugin_zip', function() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $fileName = sanitize_text_field($_GET['file']);
    $fileNamePattern = '/^plugins_\d+\.zip$/';

    if (!preg_match($fileNamePattern, $fileName)) {
        wp_die(__('Invalid file name.'));
    }

    $filePath = wp_upload_dir()['path'] . '/' . $fileName;

    if (file_exists($filePath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        wp_die(__('File not found.'));
    }
});