<?php
require_once 'db.php';
require_once 'functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    public function register($name, $email, $password) {
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
        $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $sql = "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->query($sql, [$name, $email, $hashedPassword]);
        $success = $stmt->affected_rows > 0;
        $stmt->close();

        return [
            'success' => $success,
            'message' => $success ? 'Registration successful' : 'Registration failed'
        ];
    }

    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'] ?? '';
        $_SESSION['user_image'] = $user['image'] ?? '';

        return ['success' => true, 'message' => 'Login successful'];
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
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public function updateProfile($userId, $name, $bio, $address, $image = null) {
        if ($image) {
            $sql = "UPDATE users SET name = ?, bio = ?, address = ?, image = ? WHERE id = ?";
            $params = [$name, $bio, $address, $image, $userId];
        } else {
            $sql = "UPDATE users SET name = ?, bio = ?, address = ? WHERE id = ?";
            $params = [$name, $bio, $address, $userId];
        }

        $stmt = $this->db->query($sql, $params);
        $success = $stmt->affected_rows > 0;
        $stmt->close();

        // Update session values
        if ($success && $userId == $_SESSION['user_id']) {
            $_SESSION['user_name'] = $name;
            if ($image) {
                $_SESSION['user_image'] = $image;
            }
        }

        return $success;
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
        $success = $stmt->affected_rows > 0;
        $stmt->close();

        return [
            'success' => $success,
            'message' => $success ? 'Password changed successfully' : 'Failed to change password'
        ];
    }
}

$auth = new Auth();
?>
