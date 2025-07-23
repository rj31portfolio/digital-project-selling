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

$orderId = intval($_GET['id']);
$order = $functions->getOrderDetails($orderId);

if (!$order) {
    $functions->redirect('list.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $functions->sanitize($_POST['status']);
    $functions->db->query("UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId]);
    
    $_SESSION['success_message'] = 'Order status updated successfully!';
    $functions->redirect('view.php?id=' . $orderId);
}

$pageTitle = 'Order Details #' . $orderId;
require_once '../../includes/header.php';

if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">Order Details</h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Project Information</h5>
                        <p><strong>Title:</strong> <?php echo $order['title']; ?></p>
                        <p><strong>Category:</strong> <?php echo $order['category_name']; ?></p>
                        <p><strong>Price:</strong> ₹<?php echo number_format($order['price'], 2); ?></p>
                        <?php if (!empty($order['preview_link'])): ?>
                            <p><strong>Preview:</strong> <a href="<?php echo $order['preview_link']; ?>" target="_blank">View Live Demo</a></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5>User Information</h5>
                        <p><strong>Name:</strong> <?php echo $order['user_name']; ?></p>
                        <p><strong>Email:</strong> <?php echo $order['user_email']; ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5>Payment Information</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo $order['razorpay_payment_id'] ?: 'N/A'; ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
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
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5>Order Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Project Price</td>
                                    <td>₹<?php echo number_format($order['price'], 2); ?></td>
                                </tr>
                                <?php if ($order['coupon_code']): ?>
                                    <tr>
                                        <td>Discount (<?php echo $order['coupon_discount']; ?>% - <?php echo $order['coupon_code']; ?>)</td>
                                        <td class="text-danger">-₹<?php echo number_format($order['price'] * ($order['coupon_discount'] / 100), 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Subtotal after discount</td>
                                        <td>₹<?php echo number_format($order['price'] - ($order['price'] * ($order['coupon_discount'] / 100)), 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>GST (<?php echo $order['gst_percent']; ?>%)</td>
                                    <td>₹<?php echo number_format($order['gst_amount'], 2); ?></td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>Total Amount</td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">Update Status</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="status" class="form-label">Order Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $order['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Actions</h4>
            </div>
            <div class="card-body">
                <a href="list.php" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
                <a href="../../generate-invoice.php?id=<?php echo $orderId; ?>" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-file-earmark-text"></i> Generate Invoice
                </a>
                <?php if ($order['status'] === 'completed' && !empty($order['zip_file'])): ?>
                    <a href="<?php echo BASE_URL . 'assets/uploads/projects/' . $order['zip_file']; ?>" class="btn btn-outline-success w-100" download>
                        <i class="bi bi-download"></i> Download Project
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
