<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!$auth->isLoggedIn()) {
    $functions->redirect('../../login.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $functions->redirect('orders.php');
}

$orderId = intval($_GET['id']);
$order = $functions->getOrderDetails($orderId, $_SESSION['user_id']);

if (!$order) {
    $functions->redirect('orders.php');
}

$pageTitle = 'Order Details #' . $orderId;
require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
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
                        <h5>Order Information</h5>
                        <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        <p><strong>Status:</strong> 
                            <?php if ($order['status'] === 'completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php elseif ($order['status'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php endif; ?>
                        </p>
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
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo $order['razorpay_payment_id'] ?: 'N/A'; ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
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
                
                <?php if ($order['status'] === 'completed' && !empty($order['zip_file'])): ?>
                    <div class="alert alert-success">
                        <h5 class="alert-heading">Download Available</h5>
                        <p>Your project is ready for download. Click the button below to download the files.</p>
                        <a href="<?php echo BASE_URL . 'assets/uploads/projects/' . $order['zip_file']; ?>" class="btn btn-success" download>
                            <i class="bi bi-download"></i> Download Project
                        </a>
                    </div>
                <?php elseif ($order['status'] === 'completed'): ?>
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Processing Your Order</h5>
                        <p>Your payment has been processed successfully. The admin will approve your order soon and you'll be able to download the project files.</p>
                    </div>
                <?php endif; ?>
                
                <a href="orders.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
                <a href="../../generate-invoice.php?id=<?php echo $orderId; ?>" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-text"></i> View Invoice
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>