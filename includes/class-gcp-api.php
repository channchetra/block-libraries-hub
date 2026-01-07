<?php

class GCP_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('guten-cloud/v2', '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('guten-cloud/v2', '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_settings'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('guten-cloud/v2', '/patterns', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_patterns'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('guten-cloud/v2', '/pattern-content', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_pattern_content'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('guten-cloud/v2', '/check-connection', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_connection'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        register_rest_route('guten-cloud/v2', '/create-directory', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_directory'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }

    public function check_permission() {
        return current_user_can('manage_options');
    }

    public function get_settings() {
        return new WP_REST_Response(GCP_Settings::get_settings(), 200);
    }

    public function update_settings($request) {
        $params = $request->get_json_params();
        GCP_Settings::update_settings($params);
        return new WP_REST_Response(array('success' => true), 200);
    }

    public function get_patterns($request) {
        $source = $request->get_param('source'); // github, gdrive, local
        
        switch ($source) {
            case 'github':
                $adapter = new GCP_Source_Github();
                break;
            case 'gdrive':
                $adapter = new GCP_Source_GDrive();
                break;
            case 'local':
            default:
                $adapter = new GCP_Source_Local();
                break;
        }

        $patterns = $adapter->get_patterns();
        return new WP_REST_Response($patterns, 200);
    }

    public function get_pattern_content($request) {
        $source = $request->get_param('source');
        $path = $request->get_param('path');
        
        switch ($source) {
            case 'github':
                $adapter = new GCP_Source_Github();
                break;
            case 'gdrive':
                $adapter = new GCP_Source_GDrive();
                break;
            case 'local':
            default:
                $adapter = new GCP_Source_Local();
                break;
        }

        $content = $adapter->get_content($path);
        return new WP_REST_Response(array('content' => $content), 200);
    }

    public function check_connection($request) {
        $source = $request->get_param('source');
        $settings = $request->get_json_params();
        
        // Temporarily merge settings for check
        switch ($source) {
            case 'local':
                $root_path = ABSPATH . ltrim($settings['local_root'], '/');
                $success = is_dir($root_path);
                $message = $success ? 'Connection successful (Directory found)' : 'Directory not found';
                break;
            case 'github':
                $token = $settings['github_token'];
                $repo = $settings['github_repo'];
                $api_url = "https://api.github.com/repos/{$repo}";
                $response = wp_remote_get($api_url, array(
                    'headers' => array(
                        'Authorization' => 'token ' . $token,
                        'User-Agent' => 'WordPress-GCP-Plugin'
                    )
                ));
                $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
                $message = $success ? 'Github connection successful' : 'Github connection failed';
                break;
            case 'gdrive':
                $api_key = $settings['google_api_key'];
                $folder_id = $settings['gdrive_folder_id'];
                if (empty($api_key) || empty($folder_id)) {
                    $success = false;
                    $message = 'API Key and Folder ID are required';
                } else {
                    $api_url = "https://www.googleapis.com/drive/v3/files/{$folder_id}?key={$api_key}";
                    $response = wp_remote_get($api_url);
                    $code = wp_remote_retrieve_response_code($response);
                    $success = $code === 200;
                    if ($success) {
                        $message = 'Google Drive connection successful';
                    } else {
                        $body = json_decode(wp_remote_retrieve_body($response), true);
                        $message = isset($body['error']['message']) ? $body['error']['message'] : 'Google Drive connection failed (Code: ' . $code . ')';
                    }
                }
                break;
            default:
                $success = false;
                $message = 'Invalid source';
        }

        return new WP_REST_Response(array('success' => $success, 'message' => $message), 200);
    }

    public function create_directory($request) {
        $settings = $request->get_json_params();
        $local_root = ltrim($settings['local_root'], '/');
        $root_path = ABSPATH . $local_root;

        if (empty($local_root)) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Path cannot be empty'), 200);
        }

        if (is_dir($root_path)) {
            return new WP_REST_Response(array('success' => true, 'message' => 'Directory already exists'), 200);
        }

        if (wp_mkdir_p($root_path)) {
            return new WP_REST_Response(array('success' => true, 'message' => 'Directory created successfully'), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'message' => 'Failed to create directory. Please check permissions.'), 200);
        }
    }
}
