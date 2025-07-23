 <?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base URL
define('BASE_URL', 'http://localhost/digital-project-selling/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'digital_project_selling');

// Razorpay configuration
define('RAZORPAY_KEY_ID', 'YOUR_TEST_KEY_ID');
define('RAZORPAY_KEY_SECRET', 'YOUR_TEST_KEY_SECRET');

// File upload paths
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');
define('PROJECT_UPLOAD_PATH', UPLOAD_PATH . 'projects/');
define('USER_UPLOAD_PATH', UPLOAD_PATH . 'users/');

// Ensure upload directories exist
if (!file_exists(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
if (!file_exists(PROJECT_UPLOAD_PATH)) mkdir(PROJECT_UPLOAD_PATH, 0755, true);
if (!file_exists(USER_UPLOAD_PATH)) mkdir(USER_UPLOAD_PATH, 0755, true);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Start session
session_start();
?>
