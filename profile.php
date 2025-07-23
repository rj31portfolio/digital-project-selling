 <?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $functions->redirect('index.php');
}

$userId = intval($_GET['id']);
$user = $auth->getUser($userId);

if (!$user) {
    $functions->redirect('index.php');
}

$projects = $functions->getUserProjects($userId);

$pageTitle = $user['name'] . "'s Profile";
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?php echo BASE_URL . 'assets/uploads/users/' . ($user['image'] ?: 'default.png'); ?>" class="rounded-circle mb-3" width="150" height="150" alt="Profile Image">
                <h3><?php echo $user['name']; ?></h3>
                <?php if (!empty($user['bio'])): ?>
                    <p class="text-muted"><?php echo $user['bio']; ?></p>
                <?php endif; ?>
                
                <?php if (!empty($user['address'])): ?>
                    <div class="text-start">
                        <h5 class="mt-4">Contact Information</h5>
                        <p><?php echo nl2br($user['address']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Purchased Projects</h4>
            </div>
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <div class="alert alert-info">This user hasn't purchased any projects yet.</div>
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
                                            <a href="project.php?slug=<?php echo $project['slug']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
