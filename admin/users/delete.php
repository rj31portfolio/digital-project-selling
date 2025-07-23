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

$userId = intval($_GET['id']);

// Prevent deleting own account
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot delete your own account!';
    $functions->redirect('list.php');
}

$user = $auth->getUser($userId);

if (!$user) {
    $functions->redirect('list.php');
}

// Delete user image if exists
if (!empty($user['image']) && file_exists(USER_UPLOAD_PATH . $user['image'])) {
    unlink(USER_UPLOAD_PATH . $user['image']);
}

// Delete user from database
$functions->db->query("DELETE FROM users WHERE id = ?", [$userId]);

$_SESSION['success_message'] = 'User deleted successfully!';
$functions->redirect('list.php');
?>