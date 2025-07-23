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

$couponId = intval($_GET['id']);

// Delete coupon from database
$functions->db->query("DELETE FROM coupons WHERE id = ?", [$couponId]);

$_SESSION['success_message'] = 'Coupon deleted successfully!';
$functions->redirect('list.php');
?>