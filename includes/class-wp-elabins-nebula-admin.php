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
    // Main menu
    add_menu_page(
      'Nebula: React Apps',
      'Nebula',
      'manage_options',
      $this->plugin_name,
      array($this, 'display_plugin_admin_page'),
      'dashicons-admin-generic',
      30
    );

    // Add new app submenu
    add_submenu_page(
      $this->plugin_name,
      'Add New React App',
      'Add New',
      'manage_options',
      $this->plugin_name . '-new',
      array($this, 'display_add_new_page')
    );
  }

  public function display_plugin_admin_page() {
    // Check if viewing a single project
    if (isset($_GET['project'])) {
      $project = $this->get_project(sanitize_text_field($_GET['project']));
      if ($project) {
        include_once 'views/admin-project-details.php';
        return;
      }
    }

    // Get list of existing apps with metadata
    $apps = $this->get_existing_apps();

    // Display admin page
    include_once 'views/admin-display.php';
  }

  public function display_add_new_page() {
    include_once 'views/admin-add-new.php';
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

    // Handle new app creation
    if (isset($_POST['submit'])) {
      $slug = sanitize_title($_POST['app_slug']);
      $name = sanitize_text_field($_POST['app_name']);
      $description = sanitize_textarea_field($_POST['app_description']);

      // Validate slug
      if (!$this->validate_slug($slug)) {
        wp_redirect(add_query_arg('error', 'invalid_slug'));
        exit;
      }

      // Create project in database
      $project_id = $this->create_project($slug, $name, $description);

      if (is_wp_error($project_id)) {
        wp_redirect(add_query_arg('error', 'db_error'));
        exit;
      }

      // Handle file upload if provided
      if (isset($_FILES['react_app']) && !empty($_FILES['react_app']['name'])) {
        $result = $this->handle_file_upload($_FILES['react_app'], $slug, $project_id);

        if (is_wp_error($result)) {
          wp_redirect(add_query_arg('error', $result->get_error_code()));
          exit;
        }
      }

      wp_redirect(add_query_arg(array('page' => $this->plugin_name, 'project' => $slug, 'message' => 'created')));
      exit;
    }
  }

  private function get_project($slug) {
    global $wpdb;
    $project = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}nebula_projects WHERE slug = %s",
        $slug
      )
    );

    if ($project) {
      // Get deployment history
      $project->deployments = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT d.*, u.display_name as deployer 
           FROM {$wpdb->prefix}nebula_deployments d 
           LEFT JOIN {$wpdb->users} u ON d.deployed_by = u.ID 
           WHERE project_id = %d 
           ORDER BY deployed_at DESC",
          $project->id
        )
      );

      // Get file system info
      $project->files = $this->get_project_files($slug);
    }

    return $project;
  }

  private function get_project_files($slug) {
    $app_dir = WP_ELABINS_NEBULA_REACT_APPS_DIR . '/' . $slug;
    $files = array();

    if (is_dir($app_dir)) {
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($app_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
      );

      foreach ($iterator as $file) {
        $path = str_replace($app_dir . '/', '', $file->getPathname());
        $files[] = array(
          'name' => $file->getFilename(),
          'path' => $path,
          'type' => $file->isDir() ? 'directory' : 'file',
          'size' => $file->isFile() ? size_format($file->getSize()) : null,
          'modified' => date('Y-m-d H:i:s', $file->getMTime())
        );
      }
    }

    return $files;
  }

  private function get_existing_apps() {
    global $wpdb;
    return $wpdb->get_results(
      "SELECT p.*, 
        (SELECT COUNT(*) FROM {$wpdb->prefix}nebula_deployments WHERE project_id = p.id) as deploy_count,
        (SELECT deployed_at FROM {$wpdb->prefix}nebula_deployments WHERE project_id = p.id ORDER BY deployed_at DESC LIMIT 1) as last_deploy
       FROM {$wpdb->prefix}nebula_projects p
       ORDER BY created_at DESC"
    );
  }

  private function create_project($slug, $name, $description) {
    global $wpdb;

    $result = $wpdb->insert(
      $wpdb->prefix . 'nebula_projects',
      array(
        'slug' => $slug,
        'name' => $name,
        'description' => $description,
        'status' => 'active'
      ),
      array('%s', '%s', '%s', '%s')
    );

    if ($result === false) {
      return new WP_Error('db_error', 'Failed to create project in database');
    }

    return $wpdb->insert_id;
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

  private function handle_file_upload($file, $slug, $project_id) {
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

    // Record deployment
    global $wpdb;
    $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : date('Y.m.d.H.i');
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

    $wpdb->insert(
      $wpdb->prefix . 'nebula_deployments',
      array(
        'project_id' => $project_id,
        'version' => $version,
        'deployed_by' => get_current_user_id(),
        'status' => 'success',
        'notes' => $notes
      ),
      array('%d', '%s', '%d', '%s', '%s')
    );

    // Update project's last_deploy
    $wpdb->update(
      $wpdb->prefix . 'nebula_projects',
      array('last_deploy' => current_time('mysql')),
      array('id' => $project_id),
      array('%s'),
      array('%d')
    );

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
    global $wpdb;

    // Get project ID
    $project = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}nebula_projects WHERE slug = %s",
        $slug
      )
    );

    if ($project) {
      // Delete deployments
      $wpdb->delete(
        $wpdb->prefix . 'nebula_deployments',
        array('project_id' => $project->id),
        array('%d')
      );

      // Delete project
      $wpdb->delete(
        $wpdb->prefix . 'nebula_projects',
        array('id' => $project->id),
        array('%d')
      );
    }

    // Delete files
    $app_dir = WP_ELABINS_NEBULA_REACT_APPS_DIR . '/' . $slug;
    if (is_dir($app_dir)) {
      $this->remove_directory($app_dir);
    }
  }
}
