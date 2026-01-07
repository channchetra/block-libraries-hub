<?php

class GCP_Source_Local {
    public function get_patterns() {
        $settings = GCP_Settings::get_settings();
        $root_path = wp_normalize_path(ABSPATH . ltrim($settings['local_root'], '/'));
        
        if (!is_dir($root_path)) {
            return array();
        }

        $patterns = array();
        try {
            $pattern_files = $this->get_files_recursive($root_path, array('json', 'html'));
            
            foreach ($pattern_files as $full_path) {
                $full_path = wp_normalize_path($full_path);
                $relative_path = ltrim(str_replace($root_path, '', $full_path), '/');
                
                $parts = explode('/', $relative_path);
                
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

                $content = $this->get_content($relative_path);

                $patterns[] = array(
                    'name' => pathinfo($full_path, PATHINFO_FILENAME),
                    'path' => $relative_path,
                    'tab' => $tab,
                    'category' => $category,
                    'content' => $content, // Include content for live preview
                    'source' => 'local'
                );
            }
        } catch (Exception $e) {
            error_log('GCP Local Source Error: ' . $e->getMessage());
        }
        
        return $patterns;
    }

    private function get_files_recursive($dir, $extensions) {
        $results = array();
        if (!is_dir($dir)) return $results;
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $results = array_merge($results, $this->get_files_recursive($path, $extensions));
            } else {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, $extensions)) {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    public function get_content($path) {
        $settings = GCP_Settings::get_settings();
        $root_path = wp_normalize_path(ABSPATH . ltrim($settings['local_root'], '/'));
        $file_path = wp_normalize_path($root_path . '/' . ltrim($path, '/'));
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            if (pathinfo($file_path, PATHINFO_EXTENSION) === 'json') {
                $json = json_decode($content, true);
                if (isset($json['content'])) {
                    return $json['content'];
                }
            }
            return $content;
        }
        
        return '';
    }
}
