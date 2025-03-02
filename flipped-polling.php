<?php
/*
Plugin Name: Flipped Polling
Description: A feature-rich polling plugin with multiple polls, custom designs, stats, and more.
Version: 3.0
Author: Sethi De Clercq
Author URI: https://sethideclercq.com/
License: GPL-2.0+
Requires PHP: 7.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('FLIPPED_POLLING_VERSION', '3.0');
define('FLIPPED_POLLING_DIR', plugin_dir_path(__FILE__));
define('FLIPPED_POLLING_URL', plugin_dir_url(__FILE__));

// Load includes
require_once FLIPPED_POLLING_DIR . 'includes/admin.php';
require_once FLIPPED_POLLING_DIR . 'includes/templates.php';
require_once FLIPPED_POLLING_DIR . 'includes/shortcode.php';
require_once FLIPPED_POLLING_DIR . 'includes/settings.php';

// Enqueue assets
function flipped_polling_enqueue_assets() {
    wp_enqueue_style('flipped-polling-admin', FLIPPED_POLLING_URL . 'assets/css/admin.css', [], FLIPPED_POLLING_VERSION);
    wp_enqueue_script('flipped-polling-ajax', FLIPPED_POLLING_URL . 'assets/js/ajax.js', ['jquery'], FLIPPED_POLLING_VERSION, true);
    wp_localize_script('flipped-polling-ajax', 'flippedPollingAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('flipped_polling_vote')
    ]);
}

function flipped_polling_load_textdomain() {
    load_plugin_textdomain('flipped-polling', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'flipped_polling_load_textdomain');

add_action('wp_enqueue_scripts', 'flipped_polling_enqueue_assets');
add_action('admin_enqueue_scripts', 'flipped_polling_enqueue_assets');
