<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!$auth->isLoggedIn()) {
    $functions->redirect('login.php');
}

if (!isset($_GET['payment_id']) || !isset($_GET['order_id']) || !isset($_GET['signature'])) {
    $functions->redirect('index.php');
}

$paymentId = $functions->sanitize($_GET['payment_id']);
$orderId = $functions->sanitize($_GET['order_id']);
$signature = $functions->sanitize($_GET['signature']);

// Verify payment with Razorpay
require_once 'includes/razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

$keyId = $functions->getSetting('razorpay_key_id');
$keySecret = $functions->getSetting('razorpay_key_secret');
$api = new Api($keyId, $keySecret);

try {
    $attributes = [
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    // Update order status in database
    $sql = "UPDATE orders SET razorpay_payment_id = ?, status = 'completed' 
            WHERE id = (SELECT id FROM orders WHERE razorpay_payment_id IS NULL AND status = 'pending' AND user_id = ? LIMIT 1)";
    $stmt = $functions->db->query($sql, [$paymentId, $_SESSION['user_id']]);
    
    if ($stmt->affected_rows > 0) {
        // Get order details
        $order = $functions->db->fetchOne("SELECT * FROM orders WHERE razorpay_payment_id = ?", [$paymentId]);
        
        if ($order) {
            $project = $functions->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$order['project_id']]);
            $pageTitle = 'Payment Successful';
            require_once 'includes/header.php';
            ?>
            
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <div class="card">
                        <div class="card-body py-5">
                            <div class="mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#28a745" class="bi bi-check-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                                </svg>
                            </div>
                            <h2 class="mb-3">Payment Successful!</h2>
                            <p class="lead mb-4">Thank you for your purchase. Your payment has been processed successfully.</p>
                            
                            <div class="card mb-4">
                                <div class="card-body text-start">
                                    <h5 class="card-title">Order Details</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Order ID:</strong> <?php echo $orderId; ?></p>
                                            <p><strong>Payment ID:</strong> <?php echo $paymentId; ?></p>
                                            <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Project:</strong> <?php echo $project['title']; ?></p>
                                            <p><strong>Amount Paid:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                            <p><strong>Status:</strong> <span class="badge bg-success">Completed</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <p class="mb-0">Your project is under review. You will be able to download it once the admin approves your purchase.</p>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-3">
                                <a href="user/orders.php" class="btn btn-outline-primary">
                                    <i class="bi bi-receipt"></i> View Orders
                                </a>
                                <a href="user/dashboard.php" class="btn btn-primary">
                                    <i class="bi bi-house"></i> Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            require_once 'includes/footer.php';
            exit;
        }
    }
} catch (Exception $e) {
    // Payment verification failed
    $functions->redirect('payment-failed.php?error=' . urlencode($e->getMessage()));
}

// If we get here, something went wrong
$functions->redirect('payment-failed.php');
?>