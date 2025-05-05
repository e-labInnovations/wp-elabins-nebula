<?php

/**
 * Plugin Name: WP Elabins Nebula
 * Plugin URI: https://github.com/elabins/wp-elabins-nebula
 * Description: A WordPress plugin for deploying React applications with client-side routing support.
 * Version: 1.0.0
 * Author: Elabins
 * Author URI: https://elabins.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-elabins-nebula
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

// Plugin version
define('WP_ELABINS_NEBULA_VERSION', '1.0.0');
define('WP_ELABINS_NEBULA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_ELABINS_NEBULA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_ELABINS_NEBULA_REACT_APPS_DIR', WP_CONTENT_DIR . '/react-apps');

// Include required files
require_once WP_ELABINS_NEBULA_PLUGIN_DIR . 'includes/class-wp-elabins-nebula.php';
require_once WP_ELABINS_NEBULA_PLUGIN_DIR . 'includes/class-wp-elabins-nebula-admin.php';
require_once WP_ELABINS_NEBULA_PLUGIN_DIR . 'includes/class-wp-elabins-nebula-router.php';

// Activation hook
register_activation_hook(__FILE__, array('WP_Elabins_Nebula', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_elabins_nebula_deactivate');
function wp_elabins_nebula_deactivate() {
  flush_rewrite_rules();
}

// Initialize the plugin
function wp_elabins_nebula_init() {
  $plugin = new WP_Elabins_Nebula();
  $plugin->run();

  // Flush rewrite rules on plugin update
  if (get_option('wp_elabins_nebula_version') !== WP_ELABINS_NEBULA_VERSION) {
    flush_rewrite_rules();
    update_option('wp_elabins_nebula_version', WP_ELABINS_NEBULA_VERSION);
  }
}
add_action('plugins_loaded', 'wp_elabins_nebula_init');
