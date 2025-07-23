 <?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../index.php');
}

// Get stats for dashboard
$totalProjects = $functions->db->fetchOne("SELECT COUNT(*) as total FROM projects")['total'];
$totalUsers = $functions->db->fetchOne("SELECT COUNT(*) as total FROM users")['total'];
$totalOrders = $functions->db->fetchOne("SELECT COUNT(*) as total FROM orders")['total'];
$totalRevenue = $functions->db->fetchOne("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'")['total'] ?? 0;

// Get recent orders
$recentOrders = $functions->db->fetchAll("
    SELECT o.*, p.title as project_title, u.name as user_name 
    FROM orders o 
    JOIN projects p ON o.project_id = p.id 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Projects</h6>
                        <h2 class="mb-0"><?php echo $totalProjects; ?></h2>
                    </div>
                    <i class="bi bi-collection" style="font-size: 2rem;"></i>
                </div>
            </div>
            <div class="card-footer bg-primary-dark">
                <a href="projects/list.php" class="text-white">View All <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Users</h6>
                        <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                    </div>
                    <i class="bi bi-people" style="font-size: 2rem;"></i>
                </div>
            </div>
            <div class="card-footer bg-success-dark">
                <a href="users/list.php" class="text-white">View All <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Orders</h6>
                        <h2 class="mb-0"><?php echo $totalOrders; ?></h2>
                    </div>
                    <i class="bi bi-cart-check" style="font-size: 2rem;"></i>
                </div>
            </div>
            <div class="card-footer bg-info-dark">
                <a href="orders/list.php" class="text-white">View All <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="mb-0">₹<?php echo number_format($totalRevenue, 2); ?></h2>
                    </div>
                    <i class="bi bi-currency-rupee" style="font-size: 2rem;"></i>
                </div>
            </div>
            <div class="card-footer bg-warning-dark">
                <a href="reports.php" class="text-dark">View Reports <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Orders</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentOrders)): ?>
                    <div class="alert alert-info">No recent orders found.</div>
                <?php else: ?>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><a href="orders/view.php?id=<?php echo $order['id']; ?>">#<?php echo $order['id']; ?></a></td>
                                        <td><?php echo $order['project_title']; ?></td>
                                        <td><?php echo $order['user_name']; ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="projects/add.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-plus-circle"></i> Add New Project
                </a>
                <a href="coupons/add.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-tag"></i> Create Coupon
                </a>
                <a href="settings.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-gear"></i> System Settings
                </a>
                <a href="reports.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-graph-up"></i> Sales Reports
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Info</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                </div>
                <div class="mb-3">
                    <strong>MySQL Version:</strong> 
                    <?php 
                    $version = $functions->db->getConnection()->server_version;
                    echo floor($version / 10000) . '.' . floor(($version % 10000) / 100) . '.' . ($version % 100);
                    ?>
                </div>
                <div class="mb-3">
                    <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </div>
                <div>
                    <strong>Razorpay Mode:</strong> 
                    <span class="badge bg-<?php echo $functions->getSetting('razorpay_mode') === 'test' ? 'warning' : 'success'; ?>">
                        <?php echo strtoupper($functions->getSetting('razorpay_mode')); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
