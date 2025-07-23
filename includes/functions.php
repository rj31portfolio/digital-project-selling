<?php
require_once 'config.php';
require_once 'db.php'; // Ensure Database class is loaded

class Functions {
    public $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public function redirect($url) {
        header("Location: $url");
        exit();
    }

    public function getSetting($key) {
        $sql = "SELECT value FROM settings WHERE `key` = ?";
        $result = $this->db->fetchOne($sql, [$key]);
        return $result ? $result['value'] : null;
    }

    public function getCategories() {
        $sql = "SELECT * FROM categories ORDER BY name";
        return $this->db->fetchAll($sql);
    }

    public function getFeaturedProjects($limit = 6) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM projects p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.featured = 1 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function getProjectBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM projects p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.slug = ?";
        return $this->db->fetchOne($sql, [$slug]);
    }

    public function getProjectsByCategory($categoryId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name as category_name 
                FROM projects p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT ?, ?";
        return $this->db->fetchAll($sql, [$categoryId, $offset, $perPage]);
    }

    public function getAllProjects($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name as category_name 
                FROM projects p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created_at DESC 
                LIMIT ?, ?";
        return $this->db->fetchAll($sql, [$offset, $perPage]);
    }

    public function countProjects($categoryId = null) {
        if ($categoryId) {
            $sql = "SELECT COUNT(*) as total FROM projects WHERE category_id = ?";
            $result = $this->db->fetchOne($sql, [$categoryId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM projects";
            $result = $this->db->fetchOne($sql);
        }
        return $result['total'];
    }

    public function getUserProjects($userId) {
        $sql = "SELECT p.*, o.id as order_id, o.status as order_status, o.created_at as purchase_date 
                FROM orders o 
                JOIN projects p ON o.project_id = p.id 
                WHERE o.user_id = ? AND o.status = 'completed' 
                ORDER BY o.created_at DESC";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getUserOrders($userId) {
        $sql = "SELECT o.*, p.title as project_title, p.price as project_price, 
                       c.code as coupon_code, c.discount_percent as coupon_discount 
                FROM orders o 
                JOIN projects p ON o.project_id = p.id 
                LEFT JOIN coupons c ON o.coupon_id = c.id 
                WHERE o.user_id = ? 
                ORDER BY o.created_at DESC";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getOrderDetails($orderId, $userId = null) {
        $params = [$orderId];
        $userCondition = '';

        if ($userId) {
            $userCondition = ' AND o.user_id = ?';
            $params[] = $userId;
        }

        $sql = "SELECT o.*, p.*, c.code as coupon_code, c.discount_percent as coupon_discount, 
                       u.name as user_name, u.email as user_email, cat.name as category_name 
                FROM orders o 
                JOIN projects p ON o.project_id = p.id 
                LEFT JOIN coupons c ON o.coupon_id = c.id 
                JOIN users u ON o.user_id = u.id 
                LEFT JOIN categories cat ON p.category_id = cat.id 
                WHERE o.id = ? $userCondition";
        return $this->db->fetchOne($sql, $params);
    }

    public function getCouponByCode($code) {
        $sql = "SELECT * FROM coupons WHERE code = ? AND expiry_date >= CURDATE()";
        return $this->db->fetchOne($sql, [$code]);
    }

    public function validateImageUpload($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Image size must be less than 2MB'];
        }

        return ['success' => true];
    }

    public function uploadImage($file, $type = 'user') {
        $validation = $this->validateImageUpload($file);
        if (!$validation['success']) {
            return $validation;
        }

        $uploadDir = $type === 'user' ? USER_UPLOAD_PATH : PROJECT_UPLOAD_PATH;
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Failed to upload image'];
        }
    }

    public function validateZipUpload($file) {
        $allowedTypes = ['application/zip', 'application/x-zip-compressed'];
        $maxSize = 50 * 1024 * 1024; // 50MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Only ZIP files are allowed'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'ZIP file size must be less than 50MB'];
        }

        return ['success' => true];
    }

    public function uploadZip($file) {
        $validation = $this->validateZipUpload($file);
        if (!$validation['success']) {
            return $validation;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = PROJECT_UPLOAD_PATH . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Failed to upload ZIP file'];
        }
    }

    public function createSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM projects WHERE slug = ?";
            $result = $this->db->fetchOne($sql, [$slug]);

            if (!$result) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function generateInvoiceNumber() {
        $prefix = 'INV-' . date('Ymd') . '-';
        $sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $count = $result['count'] + 1;
        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function protectPreviewPage() {
        echo '<script>
            document.addEventListener("contextmenu", function(e) {
                e.preventDefault();
            }, false);
            
            document.addEventListener("keydown", function(e) {
                if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83 || e.keyCode === 117 || e.keyCode === 115)) {
                    e.preventDefault();
                }
                if (e.keyCode === 123) {
                    e.preventDefault();
                }
            }, false);
        </script>';
    }
}

$functions = new Functions();
?>
