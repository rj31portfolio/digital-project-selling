 <?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!$auth->isLoggedIn()) {
    $functions->redirect('../../login.php');
}

$userId = $_SESSION['user_id'];
$user = $auth->getUser($userId);
$projects = $functions->getUserProjects($userId);

$pageTitle = 'User Dashboard';
require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?php echo BASE_URL . 'assets/uploads/users/' . ($user['image'] ?: 'default.png'); ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Image">
                <h5><?php echo $user['name']; ?></h5>
                <p class="text-muted"><?php echo $user['email']; ?></p>
                <a href="profile.php" class="btn btn-outline-primary btn-sm">Edit Profile</a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Quick Links</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="bi bi-house"></i> Dashboard
                </a>
                <a href="orders.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-receipt"></i> My Orders
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person"></i> Profile
                </a>
                <a href="change-password.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-lock"></i> Change Password
                </a>
                <a href="../../logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">My Projects</h4>
            </div>
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <div class="alert alert-info">
                        You haven't purchased any projects yet. <a href="../../index.php">Browse our projects</a> to get started.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($projects as $project): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <?php if (!empty($project['screenshots'])): 
                                        $screenshots = json_decode($project['screenshots'], true);
                                        if (is_array($screenshots) && !empty($screenshots)): ?>
                                            <img src="<?php echo BASE_URL . 'assets/uploads/projects/' . $screenshots[0]; ?>" class="card-img-top" alt="<?php echo $project['title']; ?>">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $project['title']; ?></h5>
                                        <p class="card-text"><?php echo substr($project['description'], 0, 100); ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Purchased: <?php echo date('M d, Y', strtotime($project['purchase_date'])); ?></small>
                                            <?php if ($project['order_status'] === 'completed' && !empty($project['zip_file'])): ?>
                                                <a href="<?php echo BASE_URL . 'assets/uploads/projects/' . $project['zip_file']; ?>" class="btn btn-sm btn-primary" download>
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending Approval</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Public Profile</h4>
            </div>
            <div class="card-body">
                <p>Your public profile can be shared with others to showcase the projects you've purchased.</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="profileLink" value="<?php echo BASE_URL . 'profile.php?id=' . $userId; ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="copyProfileLink">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
                <a href="<?php echo BASE_URL . 'profile.php?id=' . $userId; ?>" target="_blank" class="btn btn-primary">
                    <i class="bi bi-eye"></i> View Public Profile
                </a>
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

<?php require_once '../includes/footer.php'; ?>
