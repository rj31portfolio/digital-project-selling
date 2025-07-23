 <?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!$auth->isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    $functions->redirect('login.php');
}

if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    $functions->redirect('index.php');
}

$projectId = intval($_GET['project_id']);
$project = $functions->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);

if (!$project) {
    $functions->redirect('index.php');
}

// Check if user already purchased this project
$sql = "SELECT id FROM orders WHERE user_id = ? AND project_id = ? AND status = 'completed'";
$result = $functions->db->fetchOne($sql, [$_SESSION['user_id'], $projectId]);

if ($result) {
    $functions->redirect('user/dashboard.php');
}

$gstPercent = floatval($functions->getSetting('gst_percent'));
$subtotal = $project['price'];
$gstAmount = $subtotal * ($gstPercent / 100);
$total = $subtotal + $gstAmount;

// Handle coupon application
$couponApplied = false;
$couponError = '';
$couponCode = '';
$couponDiscount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = $functions->sanitize($_POST['coupon_code']);
    $coupon = $functions->getCouponByCode($couponCode);
    
    if ($coupon) {
        $couponApplied = true;
        $couponDiscount = $coupon['discount_percent'];
        $discountAmount = $subtotal * ($couponDiscount / 100);
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $gstAmount = $subtotalAfterDiscount * ($gstPercent / 100);
        $total = $subtotalAfterDiscount + $gstAmount;
    } else {
        $couponError = 'Invalid or expired coupon code';
    }
}

$pageTitle = 'Checkout - ' . $project['title'];
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h4>Order Summary</h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <?php if (!empty($project['screenshots'])): 
                            $screenshots = json_decode($project['screenshots'], true);
                            if (is_array($screenshots) && !empty($screenshots)): ?>
                                <img src="<?php echo BASE_URL . 'assets/uploads/projects/' . $screenshots[0]; ?>" class="img-fluid rounded" alt="<?php echo $project['title']; ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h5><?php echo $project['title']; ?></h5>
                        <p><?php echo substr($project['description'], 0, 200); ?>...</p>
                    </div>
                </div>
                
                <form method="post" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="coupon_code" class="form-control" placeholder="Coupon Code" value="<?php echo $couponCode; ?>">
                        <button type="submit" name="apply_coupon" class="btn btn-outline-primary">Apply Coupon</button>
                    </div>
                    <?php if ($couponError): ?>
                        <div class="text-danger small mt-2"><?php echo $couponError; ?></div>
                    <?php endif; ?>
                    <?php if ($couponApplied): ?>
                        <div class="text-success small mt-2">Coupon applied successfully! <?php echo $couponDiscount; ?>% discount.</div>
                    <?php endif; ?>
                </form>
                
                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($couponApplied): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount (<?php echo $couponDiscount; ?>%):</span>
                            <span class="text-danger">-₹<?php echo number_format($subtotal * ($couponDiscount / 100), 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal after discount:</span>
                            <span>₹<?php echo number_format($subtotal - ($subtotal * ($couponDiscount / 100)), 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>GST (<?php echo $gstPercent; ?>%):</span>
                        <span>₹<?php echo number_format($gstAmount, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total:</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4>Payment Method</h4>
            </div>
            <div class="card-body">
                <form id="paymentForm" action="<?php echo BASE_URL; ?>payment-process.php" method="post">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <input type="hidden" name="amount" value="<?php echo $total * 100; ?>"> <!-- Razorpay expects amount in paise -->
                    <input type="hidden" name="coupon_code" value="<?php echo $couponCode; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $_SESSION['user_name']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['user_email']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="payButton">
                        <i class="bi bi-lock"></i> Pay ₹<?php echo number_format($total, 2); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const payButton = document.getElementById('payButton');
    payButton.disabled = true;
    payButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    
    fetch('payment-process.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const options = {
                key: data.key,
                amount: data.amount,
                currency: 'INR',
                name: 'Digital Project Store',
                description: 'Purchase: ' + data.description,
                image: '<?php echo BASE_URL; ?>assets/images/logo.png',
                order_id: data.order_id,
                handler: function(response) {
                    window.location.href = 'payment-success.php?payment_id=' + response.razorpay_payment_id + 
                                          '&order_id=' + data.order_id + 
                                          '&signature=' + response.razorpay_signature;
                },
                prefill: {
                    name: document.getElementById('name').value,
                    email: document.getElementById('email').value,
                    contact: document.getElementById('phone').value
                },
                notes: {
                    project_id: '<?php echo $project['id']; ?>',
                    user_id: '<?php echo $_SESSION['user_id']; ?>'
                },
                theme: {
                    color: '#007bff'
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        } else {
            alert('Error: ' + data.message);
            payButton.disabled = false;
            payButton.innerHTML = '<i class="bi bi-lock"></i> Pay ₹<?php echo number_format($total, 2); ?>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        payButton.disabled = false;
        payButton.innerHTML = '<i class="bi bi-lock"></i> Pay ₹<?php echo number_format($total, 2); ?>';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
