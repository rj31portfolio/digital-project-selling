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
$search = isset($_GET['search']) ? $functions->sanitize($_GET['search']) : '';

$where = '';
$params = [];

if (!empty($status)) {
    $where = "WHERE o.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where = $where ? "$where AND (p.title LIKE ? OR u.name LIKE ? OR u.email LIKE ?)" : "WHERE p.title LIKE ? OR u.name LIKE ? OR u.email LIKE ?";
    $params = array_merge($params, array_fill(0, $where ? 3 : 3, "%$search%"));
}

$sql = "SELECT o.*, p.title as project_title, u.name as user_name, u.email as user_email 
        FROM orders o 
        JOIN projects p ON o.project_id = p.id 
        JOIN users u ON o.user_id = u.id 
        $where 
        ORDER BY o.created_at DESC 
        LIMIT ?, ?";
$params = array_merge($params, [$offset, $perPage]);

$orders = $functions->db->fetchAll($sql, $params);

// Count total orders for pagination
$countSql = "SELECT COUNT(*) as total 
             FROM orders o 
             JOIN projects p ON o.project_id = p.id 
             JOIN users u ON o.user_id = u.id 
             $where";
$totalOrders = $functions->db->fetchOne($countSql, array_slice($params, 0, -2))['total'];
$totalPages = ceil($totalOrders / $perPage);

$pageTitle = 'Manage Orders';
require_once '../../includes/header.php';

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="card mb-4">
    <div class="card-header">
        <h4 class="mb-0">Orders</h4>
    </div>
    <div class="card-body">
        <form method="get" class="mb-4">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Search orders..." value="<?php echo $search; ?>">
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
                        <th>Order ID</th>
                        <th>Project</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No orders found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['project_title']; ?></td>
                                <td>
                                    <?php echo $order['user_name']; ?>
                                    <small class="d-block text-muted"><?php echo $order['user_email']; ?></small>
                                </td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <?php if ($order['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($order['status'] === 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
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
