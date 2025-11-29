<?php
require_once 'config.php';

// User authentication functions
class Auth {
    public static function register($username, $email, $password, $first_name, $last_name, $phone = '', $role = 'patient') {
        global $conn;
        
        // Validate input
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Check if user already exists
        $check = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check);
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssss', $username, $email, $hashed_password, $first_name, $last_name, $phone, $role);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
        }
    }
    
    public static function login($username, $password) {
        global $conn;
        
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }
        
        // Check by username OR email
        $sql = "SELECT id, username, email, password, first_name, last_name, role, status FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] === 'inactive') {
                return ['success' => false, 'message' => 'Your account has been deactivated'];
            }
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                
                return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    public static function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'first_name' => $_SESSION['first_name'],
                'last_name' => $_SESSION['last_name'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . SITE_URL . 'login.php');
            exit();
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        $role = self::getRole();
        if ($role !== ADMIN_ROLE) {
            header('Location: ' . SITE_URL . 'index.php');
            exit();
        }
    }
    
    public static function requirePatient() {
        self::requireLogin();
        if (self::getRole() !== PATIENT_ROLE) {
            header('Location: ' . SITE_URL . 'admin/dashboard.php');
            exit();
        }
    }
}
?>
