 
<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../../login.php');
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$status = isset($_GET['status']) ? $functions->sanitize($_GET['status']) : '';

$where = '';
$params = [];

if ($status === 'active') {
    $where = "WHERE expiry_date >= CURDATE()";
} elseif ($status === 'expired') {
    $where = "WHERE expiry_date < CURDATE()";
}

$sql = "SELECT * FROM coupons $where ORDER BY created_at DESC LIMIT ?, ?";
$params = array_merge($params, [$offset, $perPage]);

$coupons = $functions->db->fetchAll($sql, $params);

// Count total coupons for pagination
$countSql = "SELECT COUNT(*) as total FROM coupons $where";
$totalCoupons = $functions->db->fetchOne($countSql, array_slice($params, 0, -2))['total'];
$totalPages = ceil($totalCoupons / $perPage);

$pageTitle = 'Manage Coupons';
require_once '../../includes/header.php';

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Coupons</h4>
        <a href="add.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus"></i> Add New
        </a>
    </div>
    <div class="card-body">
        <form method="get" class="mb-4">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <select name="status" class="form-select">
                        <option value="">All Coupons</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No coupons found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><code><?php echo $coupon['code']; ?></code></td>
                                <td><?php echo $coupon['discount_percent']; ?>%</td>
                                <td><?php echo date('M d, Y', strtotime($coupon['expiry_date'])); ?></td>
                                <td>
                                    <?php if (strtotime($coupon['expiry_date']) >= time()): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Expired</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($coupon['created_at'])); ?></td>
                                <td>
                                    <a href="delete.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this coupon?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>