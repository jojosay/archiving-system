<?php
/**
 * First Install Manager Class
 * Handles detection and management of first-time installation
 */

class FirstInstallManager {
    private $database;
    private $install_flag_file;
    
    public function __construct() {
        $this->install_flag_file = __DIR__ . '/../config/.installed';
    }
    
    /**
     * Check if this is a first install
     */
    public function isFirstInstall() {
        // Check if install flag file exists - this is the primary indicator
        if (file_exists($this->install_flag_file)) {
            return false;
        }
        
        // If we're currently in the installation process, allow it to continue
        if (isset($_SESSION['install_in_progress'])) {
            return true;
        }
        
        // Simple database check - if database doesn't exist or has no users, it's first install
        try {
            // Suppress any output during database checks
            ob_start();
            $this->database = new Database();
            
            // Check if database exists first
            if (!$this->database->databaseExists()) {
                ob_end_clean();
                return true; // Database doesn't exist = first install
            }
            
            // Try to connect and check for users
            $connection = $this->database->getConnection();
            ob_end_clean();
            
            if (!$connection) {
                return true; // Can't connect = first install
            }
            
            // Check if users table exists
            $stmt = $connection->prepare("SHOW TABLES LIKE 'users'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return true; // No users table = first install
            }
            
            // Check if any users exist
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] == 0; // No users = first install
            
        } catch (Exception $e) {
            // Any error means first install
            return true;
        }
    }
    
    /**
     * Mark installation as complete
     */
    public function markInstallComplete() {
        $install_data = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => APP_VERSION ?? '1.0.0',
            'php_version' => PHP_VERSION,
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
        
        return file_put_contents($this->install_flag_file, json_encode($install_data, JSON_PRETTY_PRINT));
    }
}
?>