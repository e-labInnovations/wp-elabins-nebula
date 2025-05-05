<?php
if (!defined('WPINC')) {
  die;
}
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html($project->name); ?></h1>
  <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->plugin_name . '-new')); ?>"
    class="page-title-action">Add New</a>
  <hr class="wp-header-end">

  <?php
  // Display messages
  if (isset($_GET['message'])) {
    switch ($_GET['message']) {
      case 'deployed':
        echo '<div class="notice notice-success"><p>New version deployed successfully!</p></div>';
        break;
      case 'updated':
        echo '<div class="notice notice-success"><p>Project details updated successfully!</p></div>';
        break;
    }
  }

  if (isset($_GET['error'])) {
    switch ($_GET['error']) {
      case 'project_not_found':
        echo '<div class="notice notice-error"><p>Project not found.</p></div>';
        break;
      case 'no_file':
        echo '<div class="notice notice-error"><p>Please select a file to upload.</p></div>';
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

  <div class="nebula-project-header">
    <div class="nebula-project-meta">
      <p><strong>Slug:</strong> <?php echo esc_html($project->slug); ?></p>
      <p><strong>Created:</strong> <?php echo esc_html(date('F j, Y', strtotime($project->created_at))); ?></p>
      <p><strong>Status:</strong> <span
          class="nebula-status nebula-status-<?php echo esc_attr($project->status); ?>"><?php echo esc_html(ucfirst($project->status)); ?></span>
      </p>
    </div>
    <div class="nebula-project-actions">
      <a href="<?php echo esc_url(home_url('/' . $project->slug)); ?>" class="button" target="_blank">View App</a>
      <button class="button button-primary"
        onclick="document.getElementById('upload-new-version').style.display='block'">Deploy New Version</button>
    </div>
  </div>

  <div class="nebula-project-description">
    <h2>Description</h2>
    <p><?php echo esc_html($project->description); ?></p>
  </div>

  <div class="nebula-project-grid">
    <div class="nebula-grid-item">
      <div class="card">
        <h2>Deployment History</h2>
        <?php if (!empty($project->deployments)) : ?>
          <table class="widefat">
            <thead>
              <tr>
                <th>Version</th>
                <th>Date</th>
                <th>By</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($project->deployments as $deployment) : ?>
                <tr>
                  <td><?php echo esc_html($deployment->version); ?></td>
                  <td><?php echo esc_html(date('Y-m-d H:i', strtotime($deployment->deployed_at))); ?></td>
                  <td><?php echo esc_html($deployment->deployer); ?></td>
                  <td><span
                      class="nebula-status nebula-status-<?php echo esc_attr($deployment->status); ?>"><?php echo esc_html($deployment->status); ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else : ?>
          <p>No deployments yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="nebula-grid-item">
      <div class="card">
        <h2>File System</h2>
        <div class="nebula-file-browser">
          <?php if (!empty($project->files)) : ?>
            <table class="widefat">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Size</th>
                  <th>Modified</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($project->files as $file) : ?>
                  <tr>
                    <td>
                      <?php if ($file['type'] === 'directory') : ?>
                        📁
                      <?php else : ?>
                        📄
                      <?php endif; ?>
                      <?php echo esc_html($file['path']); ?>
                    </td>
                    <td><?php echo esc_html($file['size']); ?></td>
                    <td><?php echo esc_html($file['modified']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else : ?>
            <p>No files found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Upload New Version Modal -->
  <div id="upload-new-version" class="nebula-modal" style="display: none;">
    <div class="nebula-modal-content">
      <h2>Deploy New Version</h2>
      <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('wp_elabins_nebula_upload', 'wp_elabins_nebula_nonce'); ?>
        <input type="hidden" name="project_id" value="<?php echo esc_attr($project->id); ?>">

        <p>
          <label for="version">Version</label><br>
          <input type="text" name="version" id="version" class="regular-text" placeholder="e.g., 1.0.0" required>
        </p>

        <p>
          <label for="react_app">Build Files (ZIP)</label><br>
          <input type="file" name="react_app" id="react_app" accept=".zip" required>
        </p>

        <p>
          <label for="notes">Deployment Notes</label><br>
          <textarea name="notes" id="notes" rows="3" class="large-text"></textarea>
        </p>

        <p class="submit">
          <input type="submit" name="deploy_version" class="button button-primary" value="Deploy">
          <button type="button" class="button"
            onclick="document.getElementById('upload-new-version').style.display='none'">Cancel</button>
        </p>
      </form>
    </div>
  </div>
</div>

<style>
  .nebula-project-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
  }

  .nebula-project-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }

  .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
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

  .nebula-status-success {
    background: #d1e7dd;
    color: #0a3622;
  }

  .nebula-status-failed {
    background: #f8d7da;
    color: #58151c;
  }

  .nebula-file-browser {
    max-height: 400px;
    overflow-y: auto;
  }

  .nebula-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100000;
  }

  .nebula-modal-content {
    position: relative;
    background: #fff;
    margin: 10vh auto;
    padding: 20px;
    width: 90%;
    max-width: 600px;
    border-radius: 4px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
  }
</style>