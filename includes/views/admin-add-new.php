<?php
if (!defined('WPINC')) {
  die;
}
?>
<div class="wrap">
  <h1>Add New React App</h1>

  <?php
  // Display messages
  if (isset($_GET['error'])) {
    switch ($_GET['error']) {
      case 'invalid_slug':
        echo '<div class="notice notice-error"><p>Invalid slug. The slug conflicts with an existing page, post, or term.</p></div>';
        break;
      case 'db_error':
        echo '<div class="notice notice-error"><p>Database error occurred. Please try again.</p></div>';
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
    <form method="post" enctype="multipart/form-data">
      <?php wp_nonce_field('wp_elabins_nebula_upload', 'wp_elabins_nebula_nonce'); ?>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="app_name">App Name</label>
          </th>
          <td>
            <input type="text" name="app_name" id="app_name" class="regular-text" required>
            <p class="description">Enter a name for your React app (e.g., "My Awesome App")</p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="app_slug">App Slug</label>
          </th>
          <td>
            <input type="text" name="app_slug" id="app_slug" class="regular-text" required>
            <p class="description">Enter a unique slug for your React app. This will be used in the URL:
              <?php echo home_url('/'); ?><strong>your-slug</strong></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="app_description">Description</label>
          </th>
          <td>
            <textarea name="app_description" id="app_description" class="large-text" rows="5"></textarea>
            <p class="description">Optional. Describe your React app and its purpose.</p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="react_app">React Build ZIP</label>
          </th>
          <td>
            <input type="file" name="react_app" id="react_app" accept=".zip">
            <p class="description">Optional. Upload the ZIP file containing your React app build (output of
              <code>npm run build</code>).<br>
              You can also upload the build files later from the project details page.
            </p>
          </td>
        </tr>
      </table>

      <?php submit_button('Create React App'); ?>
    </form>
  </div>

  <div class="card">
    <h3>Getting Started</h3>
    <ol>
      <li>Choose a unique name and slug for your React app</li>
      <li>Build your React app using <code>npm run build</code> or equivalent</li>
      <li>Zip the contents of the build directory (usually <code>build/</code> or <code>dist/</code>)</li>
      <li>Upload the ZIP file here or later from the project details page</li>
    </ol>

    <h3>Important Notes</h3>
    <ul>
      <li>The slug must be unique and not conflict with existing WordPress pages or posts</li>
      <li>Your React app should be configured to handle client-side routing correctly</li>
      <li>The build should be production-ready with optimized assets</li>
      <li>Make sure your app's base URL configuration matches the slug you choose</li>
    </ul>
  </div>
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

  .card h3 {
    margin-top: 0;
  }

  .form-table td {
    padding: 15px 10px;
  }

  .form-table th {
    padding: 15px 10px;
  }

  code {
    background: #f0f0f1;
    padding: 2px 5px;
    border-radius: 3px;
  }
</style>

<script>
  // Auto-generate slug from name
  document.getElementById('app_name').addEventListener('input', function() {
    var slug = this.value
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
    document.getElementById('app_slug').value = slug;
  });
</script>