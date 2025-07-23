 <?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!$auth->isLoggedIn()) {
    $functions->redirect('../../login.php');
}

$userId = $_SESSION['user_id'];
$orders = $functions->getUserOrders($userId);

$pageTitle = 'My Orders';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">My Orders</h4>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                You haven't placed any orders yet. <a href="../../index.php">Browse our projects</a> to get started.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Project</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['project_title']; ?></td>
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
                                    <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
