<?php

class GCP_Source_GDrive {
    public function get_patterns() {
        $settings = GCP_Settings::get_settings();
        $folder_id = $settings['gdrive_folder_id'];
        $api_key = $settings['google_api_key'];

        if (empty($folder_id) || empty($api_key)) return array();

        $patterns = array();
        $this->fetch_recursive($folder_id, 'General', 'Uncategorized', $patterns, $api_key);
        
        return $patterns;
    }

    private function fetch_recursive($parent_id, $tab, $category, &$patterns, $api_key) {
        $query = urlencode("'{$parent_id}' in parents and trashed = false");
        $api_url = "https://www.googleapis.com/drive/v3/files?q={$query}&key={$api_key}&fields=files(id,name,mimeType)";

        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) return;

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['files'])) return;

        foreach ($data['files'] as $file) {
            if ($file['mimeType'] === 'application/vnd.google-apps.folder') {
                $new_tab = $tab;
                $new_cat = $category;

                if ($tab === 'General') {
                    $new_tab = $file['name'];
                } else {
                    $new_cat = $file['name'];
                }

                $this->fetch_recursive($file['id'], $new_tab, $new_cat, $patterns, $api_key);
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, array('json', 'html'))) {
                    $patterns[] = array(
                        'name' => pathinfo($file['name'], PATHINFO_FILENAME),
                        'path' => $file['id'],
                        'tab' => $tab,
                        'category' => $category,
                        'content' => '', // Defer for GDrive
                        'source' => 'gdrive'
                    );
                }
            }
        }
    }

    public function get_content($file_id) {
        $settings = GCP_Settings::get_settings();
        $api_key = $settings['google_api_key'];

        if (empty($file_id) || empty($api_key)) return '';

        $api_url = "https://www.googleapis.com/drive/v3/files/{$file_id}?alt=media&key={$api_key}";
        $response = wp_remote_get($api_url);

        if (is_wp_error($response)) return '';

        $content = wp_remote_retrieve_body($response);
        
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['content'])) {
            return $decoded['content'];
        }

        return $content;
    }
}
