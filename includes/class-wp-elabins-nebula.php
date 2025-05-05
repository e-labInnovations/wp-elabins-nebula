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
