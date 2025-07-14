<?php
// User Management Helper Class

class UserManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    // Create new user (admin only)
    public function createUser($username, $email, $password, $first_name, $last_name, $role = 'staff') {
        try {
            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Validate password strength
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $role]);
            
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $this->db->lastInsertId()];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()];
        }
    }
    
    // Reset user password (admin only)
    public function resetPassword($user_id, $new_password) {
        try {
            // Validate password strength
            if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
            }
            
            // Hash new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Password reset successfully'];
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error resetting password: ' . $e->getMessage()];
        }
    }
    
    // Get user by ID
    public function getUserById($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, first_name, last_name, role, is_active, created_at FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Get all users
    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, first_name, last_name, role, is_active, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>