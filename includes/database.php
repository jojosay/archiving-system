<?php
// Database Connection Class

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Only echo error if not during first install detection or output buffering
            if (!$this->isFirstInstallCheck() && ob_get_level() == 0) {
                echo "Connection error: " . $exception->getMessage();
            }
            return null;
        }

        return $this->conn;
    }
    
    // Check if database exists
    public function databaseExists() {
        try {
            // Connect to MySQL server without specifying database
            $conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $conn->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$this->db_name]);
            
            return $stmt->rowCount() > 0;
        } catch(PDOException $exception) {
            // If we can't even connect to MySQL server, return false
            return false;
        }
    }
    
    // Check if this is being called during first install detection
    private function isFirstInstallCheck() {
        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && $trace['class'] === 'FirstInstallManager') {
                return true;
            }
            if (isset($trace['function']) && in_array($trace['function'], ['isFirstInstall', 'databaseExists'])) {
                return true;
            }
            if (isset($trace['file']) && strpos($trace['file'], 'first_install') !== false) {
                return true;
            }
        }
        return false;
    }

    // Test database connection
    public function testConnection() {
        $conn = $this->getConnection();
        if ($conn) {
            return true;
        }
        return false;
    }
}
?>