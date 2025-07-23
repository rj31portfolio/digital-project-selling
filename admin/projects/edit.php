 <?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../../login.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $functions->redirect('list.php');
}

$projectId = intval($_GET['id']);
$project = $functions->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);

if (!$project) {
    $functions->redirect('list.php');
}

$categories = $functions->getCategories();
$screenshots = json_decode($project['screenshots'], true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $functions->sanitize($_POST['title']);
    $description = $functions->sanitize($_POST['description']);
    $categoryId = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $gstPercent = floatval($_POST['gst_percent']);
    $previewLink = $functions->sanitize($_POST['preview_link']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle file uploads
    $newScreenshots = $screenshots;
    $zipFile = $project['zip_file'];
    
    // Upload new screenshots
    if (!empty($_FILES['screenshots']['name'][0])) {
        foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmpName) {
            $file = [
                'name' => $_FILES['screenshots']['name'][$key],
                'type' => $_FILES['screenshots']['type'][$key],
                'tmp_name' => $tmpName,
                'error' => $_FILES['screenshots']['error'][$key],
                'size' => $_FILES['screenshots']['size'][$key]
            ];
            
            $upload = $functions->uploadImage($file, 'project');
            if ($upload['success']) {
                $newScreenshots[] = $upload['filename'];
            }
        }
    }
    
    // Upload new zip file
    if (!empty($_FILES['zip_file']['name'])) {
        $upload = $functions->uploadZip($_FILES['zip_file']);
        if ($upload['success']) {
            // Delete old zip file if exists
            if ($zipFile && file_exists(PROJECT_UPLOAD_PATH . $zipFile)) {
                unlink(PROJECT_UPLOAD_PATH . $zipFile);
            }
            $zipFile = $upload['filename'];
        }
    }
    
    // Delete selected screenshots
    if (!empty($_POST['delete_screenshots'])) {
        foreach ($_POST['delete_screenshots'] as $filename) {
            if (($key = array_search($filename, $newScreenshots)) !== false) {
                unset($newScreenshots[$key]);
                if (file_exists(PROJECT_UPLOAD_PATH . $filename)) {
                    unlink(PROJECT_UPLOAD_PATH . $filename);
                }
            }
        }
        $newScreenshots = array_values($newScreenshots); // Re-index array
    }
    
    // Update project
    $slug = $functions->createSlug($title);
    $projectData = [
        'title' => $title,
        'slug' => $slug,
        'description' => $description,
        'category_id' => $categoryId,
        'price' => $price,
        'gst_percent' => $gstPercent,
        'preview_link' => $previewLink,
        'zip_file' => $zipFile,
        'screenshots' => json_encode($newScreenshots),
        'featured' => $featured
    ];
    
    $updated = $functions->db->update('projects', $projectData, ['id' => $projectId]);
    
    if ($updated) {
        $_SESSION['success_message'] = 'Project updated successfully!';
        $functions->redirect('list.php');
    } else {
        $error = 'Failed to update project. Please try again.';
    }
}

$pageTitle = 'Edit Project';
require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit Project</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Project Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $project['title']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo $project['description']; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $project['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price (₹)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $project['price']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gst_percent" class="form-label">GST Percentage</label>
                            <input type="number" class="form-control" id="gst_percent" name="gst_percent" step="0.01" min="0" max="30" value="<?php echo $project['gst_percent']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="preview_link" class="form-label">Preview Link (URL)</label>
                            <input type="url" class="form-control" id="preview_link" name="preview_link" value="<?php echo $project['preview_link']; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Existing Screenshots</label>
                        <?php if (!empty($screenshots)): ?>
                            <div class="row mb-3">
                                <?php foreach ($screenshots as $screenshot): ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="d-flex align-items-center">
                                            <input type="checkbox" name="delete_screenshots[]" value="<?php echo $screenshot; ?>" id="delete_<?php echo $screenshot; ?>" class="me-2">
                                            <label for="delete_<?php echo $screenshot; ?>" class="mb-0">
                                                <img src="<?php echo BASE_URL . 'assets/uploads/projects/' . $screenshot; ?>" class="img-thumbnail" width="80">
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Check screenshots to delete</small>
                        <?php else: ?>
                            <p class="text-muted">No screenshots uploaded</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="screenshots" class="form-label">Add More Screenshots</label>
                        <input type="file" class="form-control" id="screenshots" name="screenshots[]" multiple accept="image/*">
                        <small class="text-muted">Upload additional screenshots (JPEG, PNG, GIF, max 2MB each)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="zip_file" class="form-label">Project ZIP File</label>
                        <?php if (!empty($project['zip_file'])): ?>
                            <p class="mb-2">Current file: <?php echo $project['zip_file']; ?></p>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="zip_file" name="zip_file" accept=".zip">
                        <small class="text-muted">Upload new ZIP file to replace existing one (max 50MB)</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="featured" name="featured" <?php echo $project['featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="featured">Mark as Featured Project</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Project</button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
