<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $functions->redirect('../../index.php');
}

$id = $_GET['id'] ?? 0;

// Check if category exists
$category = $functions->db->fetchOne("SELECT id FROM categories WHERE id = ?", [$id]);

if ($category) {
    try {
        // Check if any projects are using this category
        $projectsCount = $functions->db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE category_id = ?", [$id])['count'];
        
        if ($projectsCount > 0) {
            $_SESSION['error_message'] = 'Cannot delete category because it is being used by '.$projectsCount.' project(s).';
        } else {
            $functions->db->delete('categories', ['id' => $id]);
            $_SESSION['success_message'] = 'Category deleted successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error deleting category: ' . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = 'Category not found!';
}

$functions->redirect('list.php');
?>