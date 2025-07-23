 <?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!$auth->isLoggedIn()) {
    $functions->redirect('../../login.php');
}

$userId = $_SESSION['user_id'];
$user = $auth->getUser($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $functions->sanitize($_POST['name']);
    $bio = $functions->sanitize($_POST['bio']);
    $address = $functions->sanitize($_POST['address']);
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
            $_SESSION['user_image'] = $image;
        }
    }
    
    // Update user
    $updated = $auth->updateProfile($userId, $name, $bio, $address, $image);
    
    if ($updated) {
        $_SESSION['success_message'] = 'Profile updated successfully!';
        $functions->redirect('profile.php');
    } else {
        $error = 'Failed to update profile. Please try again.';
    }
}

$pageTitle = 'My Profile';
require_once '../../includes/header.php';

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?php echo BASE_URL . 'assets/uploads/users/' . ($user['image'] ?: 'default.png'); ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Image">
                <h5><?php echo $user['name']; ?></h5>
                <p class="text-muted"><?php echo $user['email']; ?></p>
                
                <div class="d-grid gap-2">
                    <a href="change-password.php" class="btn btn-outline-primary">Change Password</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Public Profile</h5>
            </div>
            <div class="card-body">
                <p>Your public profile can be shared with others to showcase the projects you've purchased.</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="profileLink" value="<?php echo BASE_URL . 'profile.php?id=' . $userId; ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="copyProfileLink">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
                <a href="<?php echo BASE_URL . 'profile.php?id=' . $userId; ?>" target="_blank" class="btn btn-primary w-100">
                    <i class="bi bi-eye"></i> View Public Profile
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit Profile</h4>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo $user['bio']; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Profile Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('copyProfileLink').addEventListener('click', function() {
    const profileLink = document.getElementById('profileLink');
    profileLink.select();
    document.execCommand('copy');
    
    const originalText = this.innerHTML;
    this.innerHTML = '<i class="bi bi-check"></i> Copied!';
    
    setTimeout(() => {
        this.innerHTML = originalText;
    }, 2000);
});
</script>

<?php require_once '../../includes/footer.php'; ?>