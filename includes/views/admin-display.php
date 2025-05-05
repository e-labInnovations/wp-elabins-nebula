<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}
?>
<div class="wrap">
  <h1>Nebula: React App Uploader</h1>

  <?php
  // Display messages
  if (isset($_GET['message'])) {
    switch ($_GET['message']) {
      case 'uploaded':
        echo '<div class="notice notice-success"><p>React app uploaded successfully!</p></div>';
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

  <div class="card">
    <h2>Upload New React App</h2>
    <form method="post" enctype="multipart/form-data">
      <?php wp_nonce_field('wp_elabins_nebula_upload', 'wp_elabins_nebula_nonce'); ?>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="app_slug">App Slug</label>
          </th>
          <td>
            <input type="text" name="app_slug" id="app_slug" class="regular-text" required>
            <p class="description">Enter a unique slug for your React app (e.g., "my-app"). This will be used in the
              URL: <?php echo home_url('/'); ?><strong>your-slug</strong></p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="react_app">React Build ZIP</label>
          </th>
          <td>
            <input type="file" name="react_app" id="react_app" accept=".zip" required>
            <p class="description">Upload the ZIP file containing your React app build (output of
              <code>npm run build</code>).</p>
          </td>
        </tr>
      </table>

      <?php submit_button('Upload React App'); ?>
    </form>
  </div>

  <?php if (!empty($apps)) : ?>
  <div class="card">
    <h2>Installed React Apps</h2>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>App Slug</th>
          <th>URL</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($apps as $app) : ?>
        <tr>
          <td><?php echo esc_html($app); ?></td>
          <td>
            <a href="<?php echo esc_url(home_url('/' . $app)); ?>" target="_blank">
              <?php echo esc_url(home_url('/' . $app)); ?>
            </a>
          </td>
          <td>
            <form method="post" style="display: inline;">
              <?php wp_nonce_field('wp_elabins_nebula_upload', 'wp_elabins_nebula_nonce'); ?>
              <input type="hidden" name="app_slug" value="<?php echo esc_attr($app); ?>">
              <input type="submit" name="delete_app" class="button button-small" value="Delete"
                onclick="return confirm('Are you sure you want to delete this app?');">
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<style>
.card {
  background: #fff;
  border: 1px solid #ccd0d4;
  border-radius: 4px;
  margin-top: 20px;
  padding: 20px;
  box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
}

.card h2 {
  margin-top: 0;
  padding-bottom: 12px;
  border-bottom: 1px solid #eee;
}

.form-table td {
  padding: 15px 10px;
}

.form-table th {
  padding: 15px 10px;
}
</style>