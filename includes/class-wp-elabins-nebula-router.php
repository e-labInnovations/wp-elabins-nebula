<?php

class WP_Elabins_Nebula_Router {
  private $plugin_name;
  private $version;

  public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }

  public function add_rewrite_rules() {
    // Add rewrite tag for app slug
    add_rewrite_tag('%nebula_app%', '([^&]+)');
    add_rewrite_tag('%nebula_route%', '(.+)');

    // Add rewrite rule for static assets first (no trailing slash)
    add_rewrite_rule(
      '^([^/]+)/assets/(.+)$',
      'index.php?nebula_app=$matches[1]&nebula_route=assets/$matches[2]',
      'top'
    );

    // Add rewrite rule for the app root
    add_rewrite_rule(
      '^([^/]+)/?$',
      'index.php?nebula_app=$matches[1]',
      'top'
    );

    // Add rewrite rule for nested routes
    add_rewrite_rule(
      '^([^/]+)/(.+)$',
      'index.php?nebula_app=$matches[1]&nebula_route=$matches[2]',
      'top'
    );
  }

  public function handle_template_redirect() {
    global $wp_query;

    // Check if this is a Nebula app request
    $app_slug = get_query_var('nebula_app');
    if (!$app_slug) {
      return;
    }

    // Check if app exists
    $app_dir = WP_ELABINS_NEBULA_REACT_APPS_DIR . '/' . $app_slug;
    if (!is_dir($app_dir)) {
      $wp_query->set_404();
      status_header(404);
      return;
    }

    // Get the requested path
    $request_path = get_query_var('nebula_route', '');
    $request_uri = '/' . $request_path;

    // Try to serve static files first
    if ($this->serve_static_file($app_dir, $request_uri)) {
      return;
    }

    // If no static file found, serve index.html
    $this->serve_index_html($app_dir);
  }

  private function serve_static_file($app_dir, $request_uri) {
    // Start output buffering
    if (ob_get_level()) {
      ob_end_clean();
    }
    ob_start();

    // Remove query string if present
    $request_uri = strtok($request_uri, '?');

    // Clean the path
    $file_path = $app_dir . $request_uri;
    $file_path = realpath($file_path);

    // Security check: ensure the file is within the app directory
    if (!$file_path || strpos($file_path, $app_dir) !== 0) {
      return false;
    }

    // Check if file exists and is readable
    if (!is_file($file_path) || !is_readable($file_path)) {
      return false;
    }

    // Get file extension
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    // Get MIME type using MimeTypes class
    require_once WP_ELABINS_NEBULA_PLUGIN_DIR . 'includes/Mime/MimeTypes.php';
    $mime_types = new MimeTypes();
    $map = MimeTypes::$MAP;
    $mime_type = isset($map[$extension]) ? $map[$extension][0] : 'application/octet-stream';

    // Clear any previous output
    ob_end_clean();

    // Prevent any unwanted output
    while (ob_get_level()) {
      ob_end_clean();
    }

    if (!headers_sent()) {
      // Special handling for JavaScript modules
      if ($extension === 'js') {
        $mime_type = 'application/javascript';
        header('Content-Type: ' . $mime_type . '; charset=utf-8');
      } else {
        header('Content-Type: ' . $mime_type);
      }

      header('Content-Length: ' . filesize($file_path));
      header('Cache-Control: public, max-age=31536000');
      header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_path)) . ' GMT');
    }

    // Serve file
    readfile($file_path);
    exit;
  }

  private function serve_index_html($app_dir) {
    $index_file = $app_dir . '/index.html';

    if (!file_exists($index_file)) {
      status_header(404);
      echo '404 - App not found';
      exit;
    }

    // Set headers
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Clear output buffer
    while (ob_get_level()) {
      ob_end_clean();
    }

    // Serve index.html
    readfile($index_file);
    exit;
  }
}
