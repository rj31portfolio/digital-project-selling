 
<?php
require_once 'db.php';
require_once 'functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    public function register($name, $email, $password) {
        // Validate input
        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->query($sql, [$email]);
        
        if ($stmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->query($sql, [$name, $email, $hashedPassword]);
        
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    public function login($email, $password) {
        // Validate input
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        // Get user by email
        $sql = "SELECT * FROM users WHERE email = ?";
        $user = $this->db->fetchOne($sql, [$email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_image'] = $user['image'];
            
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function getUser($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function updateProfile($userId, $name, $bio, $address, $image = null) {
        $params = [$name, $bio, $address, $userId];
        $imageSql = '';
        
        if ($image) {
            $imageSql = ', image = ?';
            $params[] = $image;
        }
        
        $sql = "UPDATE users SET name = ?, bio = ?, address = ? $imageSql WHERE id = ?";
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt->affected_rows > 0) {
            // Update session if current user
            if ($userId == $_SESSION['user_id']) {
                $_SESSION['user_name'] = $name;
                if ($image) $_SESSION['user_image'] = $image;
            }
            return true;
        }
        return false;
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current password
        $sql = "SELECT password FROM users WHERE id = ?";
        $user = $this->db->fetchOne($sql, [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->db->query($sql, [$hashedPassword, $userId]);
        
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
}

$auth = new Auth();
?>