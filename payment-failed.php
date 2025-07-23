 
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!$auth->isLoggedIn()) {
    $functions->redirect('login.php');
}

$error = isset($_GET['error']) ? $functions->sanitize($_GET['error']) : 'Your payment could not be processed. Please try again.';

$pageTitle = 'Payment Failed';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 text-center">
        <div class="card">
            <div class="card-body py-5">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#dc3545" class="bi bi-x-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <h2 class="mb-3">Payment Failed!</h2>
                <p class="lead mb-4"><?php echo $error; ?></p>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house"></i> Return Home
                    </a>
                    <a href="user/orders.php" class="btn btn-primary">
                        <i class="bi bi-receipt"></i> View Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>