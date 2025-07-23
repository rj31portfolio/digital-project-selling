 <?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    $functions->redirect('index.php');
}

$slug = $functions->sanitize($_GET['slug']);
$project = $functions->getProjectBySlug($slug);

if (!$project) {
    $functions->redirect('index.php');
}

$pageTitle = $project['title'];
require_once 'includes/header.php';

// Check if user has purchased this project
$hasPurchased = false;
if ($auth->isLoggedIn()) {
    $sql = "SELECT id FROM orders WHERE user_id = ? AND project_id = ? AND status = 'completed'";
    $result = $functions->db->fetchOne($sql, [$_SESSION['user_id'], $project['id']]);
    $hasPurchased = (bool)$result;
}

// Get screenshots
$screenshots = [];
if (!empty($project['screenshots'])) {
    $screenshots = json_decode($project['screenshots'], true);
    if (!is_array($screenshots)) {
        $screenshots = [];
    }
}
?>

<div class="row">
    <div class="col-md-8">
        <h1><?php echo $project['title']; ?></h1>
        <span class="badge bg-primary"><?php echo $project['category_name']; ?></span>
        
        <div class="mt-4">
            <h4>Description</h4>
            <p><?php echo nl2br($project['description']); ?></p>
        </div>
        
        <?php if (!empty($screenshots)): ?>
            <div class="mt-4">
                <h4>Screenshots</h4>
                <div id="projectScreenshots" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($screenshots as $key => $screenshot): ?>
                            <div class="carousel-item <?php echo $key === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo BASE_URL . 'assets/uploads/projects/' . $screenshot; ?>" class="d-block w-100" alt="Screenshot <?php echo $key + 1; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#projectScreenshots" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#projectScreenshots" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Purchase Details</h5>
                
                <div class="mb-3">
                    <span class="fs-4 text-primary">₹<?php echo number_format($project['price'], 2); ?></span>
                    <?php if ($project['gst_percent'] > 0): ?>
                        <small class="text-muted">+ <?php echo $project['gst_percent']; ?>% GST</small>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($project['preview_link'])): ?>
                    <a href="<?php echo $project['preview_link']; ?>" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-eye"></i> Live Preview
                    </a>
                <?php endif; ?>
                
                <?php if ($hasPurchased): ?>
                    <div class="alert alert-success">
                        You have already purchased this project. You can download it from your dashboard.
                    </div>
                    <a href="<?php echo BASE_URL; ?>user/dashboard.php" class="btn btn-success w-100">
                        <i class="bi bi-download"></i> Go to Downloads
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>checkout.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary w-100">
                        <i class="bi bi-cart"></i> Buy Now
                    </a>
                <?php endif; ?>
                
                <hr>
                
                <div class="project-meta">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Category:</span>
                        <span><?php echo $project['category_name']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Added:</span>
                        <span><?php echo date('M d, Y', strtotime($project['created_at'])); ?></span>
                    </div>
                    <?php if ($project['featured']): ?>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status:</span>
                            <span class="badge bg-warning">Featured</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
