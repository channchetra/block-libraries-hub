<?php
/**
 * Plugin Name: Guten Cloud Private
 * Plugin URI: https://www.facebook.com/groups/greenshiftwp
 * Description: Private cloud for Gutenberg patterns (Github, Google Drive, Local)
 * Version: 1.0.0
 * Author: Greenshift community, Keith
 * Author URI: https://www.facebook.com/groups/greenshiftwp
 * Text Domain: guten-cloud-private
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GCP_VERSION', '1.0.0');
define('GCP_PATH', plugin_dir_path(__FILE__));
define('GCP_URL', plugin_dir_url(__FILE__));

// Autoload
require_once GCP_PATH . 'includes/class-gcp-settings.php';
require_once GCP_PATH . 'includes/class-gcp-api.php';
require_once GCP_PATH . 'includes/class-gcp-source-local.php';
require_once GCP_PATH . 'includes/class-gcp-source-github.php';
require_once GCP_PATH . 'includes/class-gcp-source-gdrive.php';

class Guten_Cloud_Private {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }

    public function init() {
        new GCP_API();
    }

    public function add_admin_menu() {
        add_menu_page(
            'Guten Cloud',
            'Guten Cloud',
            'manage_options',
            'guten-cloud-private',
            array($this, 'render_admin_page'),
            'dashicons-cloud',
            100
        );
    }

    public function render_admin_page() {
        echo '<div id="gcp-admin-app"></div>';
    }

    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_guten-cloud-private' !== $hook) {
            return;
        }

        // Vue 3 via CDN for Admin
        wp_enqueue_script('vue-cdn', 'https://unpkg.com/vue@3/dist/vue.global.prod.js', array(), '3.0.0', true);

        wp_enqueue_script(
            'gcp-admin',
            GCP_URL . 'assets/js/admin.js',
            array('vue-cdn'),
            GCP_VERSION,
            true
        );

        wp_localize_script('gcp-admin', 'gcpData', array(
            'apiUrl' => esc_url_raw(rest_url('guten-cloud/v2')),
            'nonce' => wp_create_nonce('wp_rest')
        ));

        wp_enqueue_style(
            'gcp-admin-style',
            GCP_URL . 'assets/css/style.css',
            array('dashicons'),
            GCP_VERSION
        );
    }

    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'gcp-modal',
            GCP_URL . 'assets/js/modal.js',
            array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-block-editor', 'wp-blocks'),
            GCP_VERSION,
            true
        );

        wp_localize_script('gcp-modal', 'gcpData', array(
            'apiUrl' => esc_url_raw(rest_url('guten-cloud/v2')),
            'nonce' => wp_create_nonce('wp_rest')
        ));

        wp_enqueue_style(
            'gcp-editor-style',
            GCP_URL . 'assets/css/style.css',
            array(),
            GCP_VERSION
        );
    }
}

function GCP() {
    return Guten_Cloud_Private::get_instance();
}

GCP();
