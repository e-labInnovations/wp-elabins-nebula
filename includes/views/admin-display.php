<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}
?>
<div class="wrap">
  <h1 class="wp-heading-inline">React Apps</h1>
  <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->plugin_name . '-new')); ?>"
    class="page-title-action">Add New</a>
  <hr class="wp-header-end">

  <?php
  // Display messages
  if (isset($_GET['message'])) {
    switch ($_GET['message']) {
      case 'created':
        echo '<div class="notice notice-success"><p>React app created successfully!</p></div>';
        break;
      case 'deleted':
        echo '<div class="notice notice-success"><p>React app deleted successfully!</p></div>';
        break;
    }
  }

  if (isset($_GET['error'])) {
    switch ($_GET['error']) {
      case 'invalid_slug':
        echo '<div class="notice notice-error"><p>Invalid slug. The slug conflicts with an existing page, post, or term.</p></div>';
        break;
      case 'upload_error':
        echo '<div class="notice notice-error"><p>File upload failed. Please try again.</p></div>';
        break;
      case 'invalid_type':
        echo '<div class="notice notice-error"><p>Invalid file type. Only ZIP files are allowed.</p></div>';
        break;
      case 'directory_error':
        echo '<div class="notice notice-error"><p>Failed to create app directory. Please check permissions.</p></div>';
        break;
      case 'zip_error':
        echo '<div class="notice notice-error"><p>Failed to process ZIP file. Please ensure it\'s a valid ZIP archive.</p></div>';
        break;
    }
  }
  ?>

  <?php if (!empty($apps)) : ?>
    <div class="nebula-apps-grid">
      <?php foreach ($apps as $app) : ?>
        <div class="nebula-app-card">
          <div class="nebula-app-header">
            <h2>
              <a href="<?php echo esc_url(add_query_arg(array('page' => $this->plugin_name, 'project' => $app->slug))); ?>">
                <?php echo esc_html($app->name); ?>
              </a>
            </h2>
            <span class="nebula-status nebula-status-<?php echo esc_attr($app->status); ?>">
              <?php echo esc_html(ucfirst($app->status)); ?>
            </span>
          </div>

          <div class="nebula-app-meta">
            <p class="nebula-app-description"><?php echo esc_html(wp_trim_words($app->description, 20)); ?></p>

            <div class="nebula-app-details">
              <span title="Slug">
                <span class="dashicons dashicons-tag"></span>
                <?php echo esc_html($app->slug); ?>
              </span>

              <span title="Created">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php echo esc_html(date('M j, Y', strtotime($app->created_at))); ?>
              </span>

              <?php if ($app->deploy_count > 0) : ?>
                <span title="Deployments">
                  <span class="dashicons dashicons-upload"></span>
                  <?php echo esc_html($app->deploy_count); ?> deployments
                </span>
              <?php endif; ?>

              <?php if ($app->last_deploy) : ?>
                <span title="Last Deploy">
                  <span class="dashicons dashicons-clock"></span>
                  <?php echo esc_html(human_time_diff(strtotime($app->last_deploy), current_time('timestamp'))); ?> ago
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="nebula-app-actions">
            <a href="<?php echo esc_url(home_url('/' . $app->slug)); ?>" class="button" target="_blank">View App</a>
            <a href="<?php echo esc_url(add_query_arg(array('page' => $this->plugin_name, 'project' => $app->slug))); ?>"
              class="button button-primary">Manage</a>

            <form method="post" style="display: inline;">
              <?php wp_nonce_field('wp_elabins_nebula_upload', 'wp_elabins_nebula_nonce'); ?>
              <input type="hidden" name="app_slug" value="<?php echo esc_attr($app->slug); ?>">
              <button type="submit" name="delete_app" class="button button-link-delete"
                onclick="return confirm('Are you sure you want to delete this app? This action cannot be undone.');">
                Delete
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <div class="nebula-empty-state">
      <div class="nebula-empty-state-content">
        <span class="dashicons dashicons-welcome-widgets-menus"></span>
        <h2>No React Apps Yet</h2>
        <p>Get started by creating your first React app deployment.</p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->plugin_name . '-new')); ?>"
          class="button button-primary">Add New React App</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<style>
  .nebula-apps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }

  .nebula-app-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .nebula-app-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }

  .nebula-app-header h2 {
    margin: 0;
    font-size: 1.3em;
  }

  .nebula-app-header h2 a {
    text-decoration: none;
    color: #23282d;
  }

  .nebula-app-header h2 a:hover {
    color: #0073aa;
  }

  .nebula-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
  }

  .nebula-status-active {
    background: #d1e7dd;
    color: #0a3622;
  }

  .nebula-status-inactive {
    background: #f8d7da;
    color: #58151c;
  }

  .nebula-app-meta {
    margin-bottom: 15px;
  }

  .nebula-app-description {
    color: #50575e;
    margin: 0 0 15px 0;
  }

  .nebula-app-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 13px;
    color: #646970;
  }

  .nebula-app-details span {
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .nebula-app-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #f0f0f1;
  }

  .nebula-empty-state {
    text-align: center;
    padding: 60px 0;
  }

  .nebula-empty-state-content {
    display: inline-block;
    padding: 40px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .nebula-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #646970;
  }

  .nebula-empty-state h2 {
    margin: 20px 0 10px;
    color: #23282d;
  }

  .nebula-empty-state p {
    margin: 0 0 20px;
    color: #646970;
  }
</style>