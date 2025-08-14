function registerHooks() {
    add_action('admin_menu', 'plugin_links_admin_menu');
    add_action('wp_ajax_generate_zip', 'generateZipFileHandler');
    add_action('wp_ajax_download_zip', 'handleDownloadRequestHandler');
}

function plugin_links_admin_menu() {
    add_menu_page(
        'Plugin Links', 
        'Plugin Links', 
        'manage_options', 
        'plugin-links', 
        'plugin_links_page_content', 
        'dashicons-admin-plugins', 
        80
    );
}

function plugin_links_page_content() {
    ?>
    <div class="wrap">
        <h1>Plugin Links</h1>
        <form id="plugin-links-form">
            <button id="generate-zip-btn">Generate Zip File</button>
            <div id="download-link"></div>
        </form>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#generate-zip-btn').on('click', function(e) {
                e.preventDefault();
                $.post(ajaxurl, { action: 'generate_zip' }, function(response) {
                    if (response.success) {
                        $('#download-link').html('<a href="' + response.data.downloadLink + '">Download Zip</a>');
                    } else {
                        alert('Failed to generate zip file.');
                    }
                });
            });
        });
    </script>
    <?php
}

function generateZipFileHandler() {
    $pluginIds = get_option('active_plugins');
    $downloadLink = generateZipFile($pluginIds);

    if ($downloadLink) {
        wp_send_json_success(['downloadLink' => $downloadLink]);
    } else {
        wp_send_json_error();
    }
}

function generateZipFile($pluginIds) {
    $zip = new ZipArchive();
    $zipFilePath = wp_upload_dir()['path'] . '/plugins.zip';
    
    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
        return false;
    }

    foreach ($pluginIds as $pluginId) {
        $pluginPath = WP_PLUGIN_DIR . '/' . $pluginId;
        if (is_dir($pluginPath)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pluginPath), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $relativePath = substr($file->getRealPath(), strlen(WP_PLUGIN_DIR) + 1);
                    $zip->addFile($file->getRealPath(), $relativePath);
                }
            }
        }
    }

    $zip->close();
    return wp_upload_dir()['url'] . '/plugins.zip';
}

function handleDownloadRequestHandler() {
    // Logic for handling the download request, such as verifying access permissions.
    // For now, it's assumed that the generating the download link is sufficient.
}

registerHooks();