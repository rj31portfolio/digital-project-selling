 <?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $functions->sanitize($_POST['code']);
    $discountPercent = floatval($_POST['discount_percent']);
    $expiryDate = $functions->sanitize($_POST['expiry_date']);
    
    // Check if coupon code already exists
    $existing = $functions->db->fetchOne("SELECT id FROM coupons WHERE code = ?", [$code]);
    if ($existing) {
        $error = 'Coupon code already exists. Please use a different code.';
    } else {
        $couponData = [
            'code' => $code,
            'discount_percent' => $discountPercent,
            'expiry_date' => $expiryDate
        ];
        
        $couponId = $functions->db->insert('coupons', $couponData);
        
        if ($couponId) {
            $_SESSION['success_message'] = 'Coupon added successfully!';
            $functions->redirect('list.php');
        } else {
            $error = 'Failed to add coupon. Please try again.';
        }
    }
}

$pageTitle = 'Add New Coupon';
require_once '../../includes/header.php';

if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Add New Coupon</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="code" class="form-label">Coupon Code</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discount_percent" class="form-label">Discount Percentage</label>
                        <input type="number" class="form-control" id="discount_percent" name="discount_percent" min="1" max="100" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="expiry_date" class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Coupon</button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
