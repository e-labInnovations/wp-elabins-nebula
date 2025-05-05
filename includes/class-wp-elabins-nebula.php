<?php

class WP_Elabins_Nebula {
  protected $loader;
  protected $plugin_name;
  protected $version;
  protected $admin;
  protected $router;

  public function __construct() {
    if (headers_sent()) {
      return;
    }

    $this->plugin_name = 'wp-elabins-nebula';
    $this->version = WP_ELABINS_NEBULA_VERSION;
    $this->load_dependencies();
    $this->define_admin_hooks();
    $this->define_public_hooks();
  }

  public static function activate() {
    global $wpdb;

    // Create projects table
    $table_name = $wpdb->prefix . 'nebula_projects';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      slug varchar(100) NOT NULL,
      name varchar(255) NOT NULL,
      description text,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      last_deploy datetime DEFAULT NULL,
      status varchar(20) DEFAULT 'active',
      PRIMARY KEY  (id),
      UNIQUE KEY slug (slug)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Create deployments table
    $table_name = $wpdb->prefix . 'nebula_deployments';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      project_id bigint(20) NOT NULL,
      version varchar(50),
      deployed_at datetime DEFAULT CURRENT_TIMESTAMP,
      deployed_by bigint(20),
      status varchar(20) DEFAULT 'success',
      notes text,
      PRIMARY KEY  (id),
      KEY project_id (project_id),
      FOREIGN KEY (project_id) REFERENCES {$wpdb->prefix}nebula_projects(id) ON DELETE CASCADE
    ) $charset_collate;";

    dbDelta($sql);

    // Create react-apps directory if it doesn't exist
    if (!file_exists(WP_ELABINS_NEBULA_REACT_APPS_DIR)) {
      wp_mkdir_p(WP_ELABINS_NEBULA_REACT_APPS_DIR);
    }

    // Add .htaccess to protect the directory
    $htaccess_content = "Options -Indexes\nDeny from all";
    file_put_contents(WP_ELABINS_NEBULA_REACT_APPS_DIR . '/.htaccess', $htaccess_content);

    // Flush rewrite rules
    flush_rewrite_rules();
  }

  private function load_dependencies() {
    // Load admin class
    $this->admin = new WP_Elabins_Nebula_Admin($this->get_plugin_name(), $this->get_version());

    // Load router class
    $this->router = new WP_Elabins_Nebula_Router($this->get_plugin_name(), $this->get_version());
  }

  private function define_admin_hooks() {
    // Add admin menu
    add_action('admin_menu', array($this->admin, 'add_plugin_admin_menu'));

    // Add admin scripts and styles
    add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
    add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));

    // Handle form submissions
    add_action('admin_init', array($this->admin, 'handle_form_submission'));
  }

  private function define_public_hooks() {
    // Add rewrite rules
    add_action('init', array($this->router, 'add_rewrite_rules'));

    // Handle template redirect
    add_action('template_redirect', array($this->router, 'handle_template_redirect'));
  }

  public function run() {
    // Plugin is now running
  }

  public function get_plugin_name() {
    return $this->plugin_name;
  }

  public function get_version() {
    return $this->version;
  }
}
