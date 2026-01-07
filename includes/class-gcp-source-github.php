<?php

class GCP_Source_Github {
    public function get_patterns() {
        $settings = GCP_Settings::get_settings();
        $token = $settings['github_token'];
        $repo = $settings['github_repo'];
        $base_path = trim($settings['github_path'], '/');

        if (empty($token) || empty($repo)) return array();

        $api_url = "https://api.github.com/repos/{$repo}/git/trees/main?recursive=1";
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'token ' . $token,
                'User-Agent' => 'WordPress-GCP-Plugin'
            )
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $api_url = "https://api.github.com/repos/{$repo}/git/trees/master?recursive=1";
            $response = wp_remote_get($api_url, array(
                'headers' => array(
                    'Authorization' => 'token ' . $token,
                    'User-Agent' => 'WordPress-GCP-Plugin'
                )
            ));
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return array();
            }
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['tree'])) return array();

        $patterns = array();
        foreach ($data['tree'] as $item) {
            if ($item['type'] === 'blob') {
                $ext = strtolower(pathinfo($item['path'], PATHINFO_EXTENSION));
                if (in_array($ext, array('json', 'html'))) {
                    $item_path = $item['path'];
                    if ($base_path !== '' && strpos($item_path, $base_path . '/') !== 0) {
                        continue;
                    }

                    $rel_path = ($base_path !== '') ? substr($item_path, strlen($base_path) + 1) : $item_path;
                    $parts = explode('/', $rel_path);
                    
                    if (count($parts) >= 3) {
                        $tab = $parts[0];
                        $category = $parts[1];
                    } elseif (count($parts) == 2) {
                        $tab = 'General';
                        $category = $parts[0];
                    } else {
                        $tab = 'General';
                        $category = 'Uncategorized';
                    }

                    // For Github, we still might want to defer content fetching to avoid huge payloads
                    // But for consistency with Local, let's at least have the option or fetch on demand in JS.
                    // Actually, fetching 100 patterns' content from Github API in one loop will hit rate limits or be slow.
                    // Let's keep content empty for Github and let modal handle it.
                    
                    $patterns[] = array(
                        'name' => pathinfo($item['path'], PATHINFO_FILENAME),
                        'path' => $item['path'],
                        'tab' => $tab,
                        'category' => $category,
                        'content' => '', // Defer for Github to avoid slow API
                        'source' => 'github'
                    );
                }
            }
        }

        return $patterns;
    }

    public function get_content($path) {
        $settings = GCP_Settings::get_settings();
        $token = $settings['github_token'];
        $repo = $settings['github_repo'];

        if (empty($token) || empty($repo)) return '';

        $api_url = "https://api.github.com/repos/{$repo}/contents/{$path}";
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'token ' . $token,
                'User-Agent' => 'WordPress-GCP-Plugin',
                'Accept' => 'application/vnd.github.v3.raw'
            )
        ));

        if (is_wp_error($response)) return '';

        $content = wp_remote_retrieve_body($response);
        
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['content'])) {
            return $json['content'];
        }

        return $content;
    }
}
