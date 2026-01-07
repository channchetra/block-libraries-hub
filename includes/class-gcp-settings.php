<?php

class GCP_Settings {
    const OPTION_NAME = 'gcp_settings';

    public static function get_settings() {
        $defaults = array(
            'github_token' => '',
            'github_repo' => '',
            'github_path' => '',
            'gdrive_folder_id' => '',
            'google_api_key' => '',
            'local_root' => '/guten-library'
        );
        $settings = get_option(self::OPTION_NAME, array());
        return wp_parse_args($settings, $defaults);
    }

    public static function update_settings($new_settings) {
        $settings = self::get_settings();
        foreach ($new_settings as $key => $value) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = sanitize_text_field($value);
            }
        }
        return update_option(self::OPTION_NAME, $settings);
    }
}
