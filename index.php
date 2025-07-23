<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
$featured = isset($_GET['featured']) ? 1 : 0;

$perPage = 9;
$offset = ($page - 1) * $perPage;

if ($categoryId) {
    $projects = $functions->getProjectsByCategory($categoryId, $page, $perPage);
    $totalProjects = $functions->countProjects($categoryId);

    $categoryRow = $functions->db->fetchOne("SELECT name FROM categories WHERE id = ?", [$categoryId]);
    $categoryName = $categoryRow ? $categoryRow['name'] : 'Unknown Category';

} elseif ($featured) {
    $projects = $functions->getFeaturedProjects();
    $totalProjects = count($projects);
} else {
    $projects = $functions->getAllProjects($page, $perPage);
    $totalProjects = $functions->countProjects();
}

$totalPages = ceil($totalProjects / $perPage);
$pageTitle = 'Digital Project Store';
if ($categoryId) $pageTitle .= " - $categoryName";
if ($featured) $pageTitle .= " - Featured Projects";

require_once 'includes/header.php';
?>

<!-- Alerts -->
<?php if ($featured): ?>
    <div class="alert alert-info">
        <h4 class="alert-heading">Featured Projects</h4>
        <p>Check out our hand-picked selection of high-quality projects.</p>
    </div>
<?php elseif ($categoryId): ?>
    <div class="alert alert-info">
        <h4 class="alert-heading">Category: <?php echo htmlspecialchars($categoryName); ?></h4>
        <p><?php echo $totalProjects; ?> projects found in this category</p>
    </div>
<?php else: ?>
    <div class="jumbotron bg-light p-5 rounded mb-4">
        <h1 class="display-4">Welcome to Digital Project Store</h1>
        <p class="lead">Browse and purchase high-quality digital projects, templates, and source code for your next project.</p>
        <hr class="my-4">
        <a class="btn btn-primary btn-lg" href="?featured=1" role="button">View Featured Projects</a>
    </div>
<?php endif; ?>

<!-- Project Cards -->
<div class="row">
    <?php if (empty($projects)): ?>
        <div class="col-12">
            <div class="alert alert-warning">No projects found.</div>
        </div>
    <?php else: ?>
        <?php foreach ($projects as $project): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php
                    if (!empty($project['screenshots'])):
                        $screenshots = json_decode($project['screenshots'], true);
                        if (is_array($screenshots) && !empty($screenshots)):
                    ?>
                        <img src="<?php echo BASE_URL . 'assets/uploads/projects/' . $screenshots[0]; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($project['title']); ?>">
                    <?php
                        endif;
                    endif;
                    ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($project['category_name']); ?></span>
                        <p class="card-text mt-2"><?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-primary fw-bold">₹<?php echo number_format($project['price'], 2); ?></span>
                                <?php if ($project['gst_percent'] > 0): ?>
                                    <small class="text-muted">+ <?php echo $project['gst_percent']; ?>% GST</small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if (!empty($project['preview_link'])): ?>
                                    <a href="<?php echo $project['preview_link']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1">Preview</a>
                                <?php endif; ?>
                                <a href="project.php?slug=<?php echo urlencode($project['slug']); ?>" class="btn btn-sm btn-primary">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if (!$featured && $totalPages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo;</a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&raquo;</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
