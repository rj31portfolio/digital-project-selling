<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../../index.php');
}

$id = $_GET['id'] ?? 0;
$category = $functions->db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);

if (!$category) {
    $_SESSION['error_message'] = 'Category not found!';
    $functions->redirect('list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category['name'] = trim($_POST['name'] ?? '');
    $category['slug'] = trim($_POST['slug'] ?? '');
    $category['description'] = trim($_POST['description'] ?? '');
    $category['status'] = $_POST['status'] ?? 'active';

    // Validation
    if (empty($category['name'])) {
        $errors['name'] = 'Name is required';
    }

    if (empty($category['slug'])) {
        $errors['slug'] = 'Slug is required';
    } elseif ($functions->db->fetchOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$category['slug'], $id])) {
        $errors['slug'] = 'Slug already exists';
    }

    if (empty($errors)) {
        try {
            $functions->db->update('categories', $category, ['id' => $id]);
            $_SESSION['success_message'] = 'Category updated successfully!';
            $functions->redirect('list.php');
        } catch (Exception $e) {
            $errors[] = 'Error updating category: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Category';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Category</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                       id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" class="form-control <?php echo isset($errors['slug']) ? 'is-invalid' : ''; ?>" 
                       id="slug" name="slug" value="<?php echo htmlspecialchars($category['slug']); ?>" required>
                <?php if (isset($errors['slug'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['slug']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="status_active" value="active" <?php echo $category['status'] === 'active' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status_active">Active</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" id="status_inactive" value="inactive" <?php echo $category['status'] === 'inactive' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status_inactive">Inactive</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Category</button>
            <a href="list.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>