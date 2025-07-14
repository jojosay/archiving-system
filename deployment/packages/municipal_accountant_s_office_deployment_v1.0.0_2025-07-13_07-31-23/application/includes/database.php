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
            // Only echo error if not during first install detection
            if (!$this->isFirstInstallCheck()) {
                echo "Connection error: " . $exception->getMessage();
            }
        }

        return $this->conn;
    }
    
    // Check if this is being called during first install detection
    private function isFirstInstallCheck() {
        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && $trace['class'] === 'FirstInstallManager') {
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