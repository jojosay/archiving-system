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
        // Check if install flag file exists
        if (file_exists($this->install_flag_file)) {
            return false;
        }
        
        // If we're in the middle of installation process, check if it's actually complete
        if (isset($_SESSION['install_db_config']) || isset($_SESSION['install_step_completed'])) {
            // Check if installation was actually completed
            try {
                $this->database = new Database();
                $connection = $this->database->getConnection();
                
                if ($connection) {
                    // Check if users table exists and has data
                    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                    if ($stmt && $stmt->execute()) {
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result['count'] > 0) {
                            // Installation is complete, clean up session and mark as installed
                            unset($_SESSION['install_db_config']);
                            unset($_SESSION['install_step_completed']);
                            unset($_SESSION['install_admin_config']);
                            $this->markInstallComplete();
                            return false; // Not first install anymore
                        }
                    }
                }
            } catch (Exception $e) {
                // If there's an error, continue with first install detection
            }
            
            return true; // Still in install process
        }
        
        // Check if database connection is possible
        try {
            // Suppress any output during database connection attempt
            ob_start();
            $this->database = new Database();
            $connection = $this->database->getConnection();
            ob_end_clean();
            
            // If connection is null, it's a first install
            if ($connection === null) {
                return true;
            }
            
            // Check if users table exists and has data
            $stmt = $connection->prepare("SHOW TABLES LIKE 'users'");
            if (!$stmt) {
                return true; // Can't prepare statement = first install
            }
            
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return true; // No users table = first install
            }
            
            // Check if any users exist
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
            if (!$stmt) {
                return true; // Can't prepare statement = first install
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] == 0; // No users = first install
            
        } catch (Exception $e) {
            // Database connection failed = likely first install
            return true;
        } catch (Error $e) {
            // Fatal error (like calling method on null) = first install
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