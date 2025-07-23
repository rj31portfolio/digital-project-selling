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

$userId = intval($_GET['id']);
$user = $auth->getUser($userId);

if (!$user) {
    $functions->redirect('list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $functions->sanitize($_POST['name']);
    $email = $functions->sanitize($_POST['email']);
    $bio = $functions->sanitize($_POST['bio']);
    $address = $functions->sanitize($_POST['address']);
    $role = $functions->sanitize($_POST['role']);
    $image = $user['image'];
    
    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $upload = $functions->uploadImage($_FILES['image'], 'user');
        if ($upload['success']) {
            // Delete old image if exists
            if ($image && file_exists(USER_UPLOAD_PATH . $image)) {
                unlink(USER_UPLOAD_PATH . $image);
            }
            $image = $upload['filename'];
        }
    }
    
    // Update user
    $userData = [
        'name' => $name,
        'email' => $email,
        'bio' => $bio,
        'address' => $address,
        'image' => $image,
        'role' => $role
    ];
    
    $updated = $functions->db->update('users', $userData, ['id' => $userId]);
    
    if ($updated) {
        $_SESSION['success_message'] = 'User updated successfully!';
        $functions->redirect('list.php');
    } else {
        $error = 'Failed to update user. Please try again.';
    }
}

$pageTitle = 'Edit User';
require_once '../../includes/header.php';

if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit User</h4>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo $user['bio']; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    
                    <?php if ($user['image']): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div>
                                <img src="<?php echo BASE_URL . 'assets/uploads/users/' . $user['image']; ?>" class="img-thumbnail" width="150">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
