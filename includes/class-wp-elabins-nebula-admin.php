<?php

class WP_Elabins_Nebula_Admin {
  private $plugin_name;
  private $version;

  public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }

  public function enqueue_styles() {
    wp_enqueue_style(
      $this->plugin_name,
      plugin_dir_url(__FILE__) . '../admin/css/wp-elabins-nebula-admin.css',
      array(),
      $this->version,
      'all'
    );
  }

  public function enqueue_scripts() {
    wp_enqueue_script(
      $this->plugin_name,
      plugin_dir_url(__FILE__) . '../admin/js/wp-elabins-nebula-admin.js',
      array('jquery'),
      $this->version,
      false
    );
  }

  public function add_plugin_admin_menu() {
    add_menu_page(
      'Nebula: React App Uploader',
      'Nebula',
      'manage_options',
      $this->plugin_name,
      array($this, 'display_plugin_admin_page'),
      'dashicons-admin-generic',
      30
    );
  }

  public function display_plugin_admin_page() {
    // Get list of existing apps
    $apps = $this->get_existing_apps();

    // Display admin page
    include_once 'views/admin-display.php';
  }

  public function handle_form_submission() {
    if (
      !isset($_POST['wp_elabins_nebula_nonce']) ||
      !wp_verify_nonce($_POST['wp_elabins_nebula_nonce'], 'wp_elabins_nebula_upload')
    ) {
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle app deletion
    if (isset($_POST['delete_app']) && isset($_POST['app_slug'])) {
      $this->delete_app(sanitize_text_field($_POST['app_slug']));
      wp_redirect(add_query_arg('message', 'deleted'));
      exit;
    }

    // Handle new app upload
    if (isset($_POST['submit']) && isset($_FILES['react_app'])) {
      $slug = sanitize_title($_POST['app_slug']);

      // Validate slug
      if (!$this->validate_slug($slug)) {
        wp_redirect(add_query_arg('error', 'invalid_slug'));
        exit;
      }

      // Handle file upload
      $result = $this->handle_file_upload($_FILES['react_app'], $slug);

      if (is_wp_error($result)) {
        wp_redirect(add_query_arg('error', $result->get_error_code()));
      } else {
        wp_redirect(add_query_arg('message', 'uploaded'));
      }
      exit;
    }
  }

  private function get_existing_apps() {
    $apps = array();
    if (is_dir(WP_ELABINS_NEBULA_REACT_APPS_DIR)) {
      $dirs = scandir(WP_ELABINS_NEBULA_REACT_APPS_DIR);
      foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir(WP_ELABINS_NEBULA_REACT_APPS_DIR . '/' . $dir)) {
          $apps[] = $dir;
        }
      }
    }
    return $apps;
  }

  private function validate_slug($slug) {
    // Check if slug is empty
    if (empty($slug)) {
      return false;
    }

    // Check if page/post exists with this slug
    if (get_page_by_path($slug)) {
      return false;
    }

    // Check if term exists with this slug
    $terms = get_terms(array(
      'hide_empty' => false,
      'slug' => $slug
    ));
    if (!empty($terms)) {
      return false;
    }

    return true;
  }

  private function handle_file_upload($file, $slug) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
      return new WP_Error('upload_error', 'File upload failed');
    }

    // Check file type
    $file_type = wp_check_filetype($file['name']);
    if ($file_type['ext'] !== 'zip') {
      return new WP_Error('invalid_type', 'Only ZIP files are allowed');
    }

    // Create app directory
    $app_dir = WP_ELABINS_NEBULA_REACT_APPS_DIR . '/' . $slug;
    if (!wp_mkdir_p($app_dir)) {
      return new WP_Error('directory_error', 'Failed to create app directory');
    }

    // Extract ZIP file
    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== TRUE) {
      return new WP_Error('zip_error', 'Failed to open ZIP file');
    }

    // Extract to temp directory first
    $temp_dir = get_temp_dir() . 'nebula-' . uniqid();
    if (!wp_mkdir_p($temp_dir)) {
      return new WP_Error('temp_dir_error', 'Failed to create temporary directory');
    }

    $zip->extractTo($temp_dir);
    $zip->close();

    // Move contents to final directory
    $this->move_directory_contents($temp_dir, $app_dir);

    // Clean up temp directory
    $this->remove_directory($temp_dir);

    return true;
  }

  private function move_directory_contents($source, $destination) {
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
      if ($file != '.' && $file != '..') {
        $src = $source . '/' . $file;
        $dst = $destination . '/' . $file;
        if (is_dir($src)) {
          wp_mkdir_p($dst);
          $this->move_directory_contents($src, $dst);
        } else {
          copy($src, $dst);
        }
      }
    }
    closedir($dir);
  }

  private function remove_directory($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir . "/" . $object)) {
            $this->remove_directory($dir . "/" . $object);
          } else {
            unlink($dir . "/" . $object);
          }
        }
      }
      rmdir($dir);
    }
  }

  private function delete_app($slug) {
    $app_dir = WP_ELABINS_NEBULA_REACT_APPS_DIR . '/' . $slug;
    if (is_dir($app_dir)) {
      $this->remove_directory($app_dir);
    }
  }
}