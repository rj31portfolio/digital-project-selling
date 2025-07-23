<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate input
$projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
$couponCode = isset($_POST['coupon_code']) ? $functions->sanitize($_POST['coupon_code']) : '';

if ($projectId <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project or amount']);
    exit;
}

// Get project details
$project = $functions->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
    exit;
}

// Check if user already purchased this project
$sql = "SELECT id FROM orders WHERE user_id = ? AND project_id = ? AND status = 'completed'";
$result = $functions->db->fetchOne($sql, [$_SESSION['user_id'], $projectId]);

if ($result) {
    echo json_encode(['success' => false, 'message' => 'You have already purchased this project']);
    exit;
}

// Get coupon details if applied
$couponId = null;
$couponDiscount = 0;
if (!empty($couponCode)) {
    $coupon = $functions->getCouponByCode($couponCode);
    if ($coupon) {
        $couponId = $coupon['id'];
        $couponDiscount = $coupon['discount_percent'];
    }
}

// Calculate amounts
$gstPercent = floatval($functions->getSetting('gst_percent'));
$subtotal = $project['price'];
$discountAmount = $subtotal * ($couponDiscount / 100);
$subtotalAfterDiscount = $subtotal - $discountAmount;
$gstAmount = $subtotalAfterDiscount * ($gstPercent / 100);
$total = $subtotalAfterDiscount + $gstAmount;

// Verify amount matches
if ($amount != $total * 100) {
    echo json_encode(['success' => false, 'message' => 'Amount mismatch']);
    exit;
}

// Create order in database with pending status
$orderData = [
    'user_id' => $_SESSION['user_id'],
    'project_id' => $projectId,
    'coupon_id' => $couponId,
    'gst_amount' => $gstAmount,
    'total_amount' => $total,
    'status' => 'pending'
];

$orderId = $functions->db->insert('orders', $orderData);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Failed to create order']);
    exit;
}

// Initialize Razorpay
require_once 'includes/razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

$keyId = $functions->getSetting('razorpay_key_id');
$keySecret = $functions->getSetting('razorpay_key_secret');
$api = new Api($keyId, $keySecret);

try {
    $order = $api->order->create([
        'receipt' => 'order_' . $orderId,
        'amount' => $amount,
        'currency' => 'INR',
        'payment_capture' => 1
    ]);
    
    echo json_encode([
        'success' => true,
        'key' => $keyId,
        'amount' => $amount,
        'order_id' => $order->id,
        'description' => $project['title'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email']
    ]);
} catch (Exception $e) {
    // Update order status to failed
    $functions->db->query("UPDATE orders SET status = 'failed' WHERE id = ?", [$orderId]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Payment initialization failed: ' . $e->getMessage()
    ]);
}
?>