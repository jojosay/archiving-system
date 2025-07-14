<?php
// Authentication Helper Class

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    // Verify user credentials
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, password_hash, role, first_name, last_name, is_active FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Start session and store user data
                $this->startUserSession($user);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Start user session
    private function startUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['login_time'] = time();
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        // Check session timeout
        if ((time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION['login_time'] = time();
        return true;
    }
    
    // Check if user has required role
    public function hasRole($required_role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_role = $_SESSION['role'] ?? '';
        
        // Admin has access to everything
        if ($user_role === 'admin') {
            return true;
        }
        
        // Check specific role
        return $user_role === $required_role;
    }
    
    // Get current user info
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name']
        ];
    }
    
    // Logout user
    public function logout() {
        session_unset();
        session_destroy();
    }
}
?>