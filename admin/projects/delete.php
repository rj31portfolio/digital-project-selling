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

$projectId = intval($_GET['id']);
$project = $functions->db->fetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);

if (!$project) {
    $functions->redirect('list.php');
}

// Delete associated files
if (!empty($project['screenshots'])) {
    $screenshots = json_decode($project['screenshots'], true);
    if (is_array($screenshots)) {
        foreach ($screenshots as $screenshot) {
            if (file_exists(PROJECT_UPLOAD_PATH . $screenshot)) {
                unlink(PROJECT_UPLOAD_PATH . $screenshot);
            }
        }
    }
}

if (!empty($project['zip_file']) && file_exists(PROJECT_UPLOAD_PATH . $project['zip_file'])) {
    unlink(PROJECT_UPLOAD_PATH . $project['zip_file']);
}

// Delete project from database
$functions->db->query("DELETE FROM projects WHERE id = ?", [$projectId]);

$_SESSION['success_message'] = 'Project deleted successfully!';
$functions->redirect('list.php');
?>