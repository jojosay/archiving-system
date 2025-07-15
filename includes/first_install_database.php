<?php
/**
 * First Install Database Setup
 * Handles database creation and initial setup during first install
 */

class FirstInstallDatabase {
    private $host;
    private $username;
    private $password;
    private $database_name;
    
    public function __construct($host, $username, $password, $database_name) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database_name = $database_name;
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $dsn = "mysql:host={$this->host};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return ['success' => true, 'message' => 'Connection successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create database if it doesn't exist
     */
    public function createDatabase() {
        try {
            $dsn = "mysql:host={$this->host};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database
            $sql = "CREATE DATABASE IF NOT EXISTS `{$this->database_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);
            
            return ['success' => true, 'message' => 'Database created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Import database schema
     */
    public function importSchema() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Read schema file
            $schema_file = __DIR__ . '/../database/schema.sql';
            error_log("Looking for schema file at: " . $schema_file);
            
            if (!file_exists($schema_file)) {
                error_log("Schema file not found at: " . $schema_file);
                return ['success' => false, 'message' => 'Schema file not found at: ' . $schema_file];
            }
            
            $sql = file_get_contents($schema_file);
            error_log("Schema file size: " . strlen($sql) . " bytes");
            
            // Remove only specific lines we don't want
            $lines = explode("\n", $sql);
            $filtered_lines = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                // Skip empty lines, comments, CREATE DATABASE, and USE statements
                if (empty($line) || 
                    strpos($line, '--') === 0 || 
                    stripos($line, 'CREATE DATABASE') === 0 || 
                    stripos($line, 'USE ') === 0) {
                    continue;
                }
                $filtered_lines[] = $line;
            }
            
            $sql = implode("\n", $filtered_lines);
            error_log("Cleaned SQL preview: " . substr($sql, 0, 200) . "...");
            
            // Split into individual statements
            $statements = explode(';', $sql);
            $statements = array_filter(
                array_map('trim', $statements),
                function($stmt) { 
                    return !empty($stmt) && strlen(trim($stmt)) > 10;
                }
            );
            
            error_log("First 3 statements: " . print_r(array_slice($statements, 0, 3), true));
            
            error_log("Number of SQL statements to execute: " . count($statements));
            
            foreach ($statements as $index => $statement) {
                if (!empty(trim($statement))) {
                    try {
                        error_log("Executing statement " . ($index + 1) . ": " . substr($statement, 0, 100) . "...");
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        error_log("Error executing statement " . ($index + 1) . ": " . $e->getMessage());
                        error_log("Statement was: " . $statement);
                        return ['success' => false, 'message' => 'Error in statement ' . ($index + 1) . ': ' . $e->getMessage()];
                    }
                }
            }
            
            // Verify tables were created
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            error_log("Tables created: " . implode(', ', $tables));
            
            // After successful schema import, set up template system
            $this->setupTemplateSystem($pdo);
            
            return ['success' => true, 'message' => 'Schema imported successfully. Created ' . count($tables) . ' tables.'];
        } catch (PDOException $e) {
            error_log("Schema import PDO error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Set up template system during first install
     */
    private function setupTemplateSystem($pdo) {
        try {
            // Include template database setup
            require_once __DIR__ . '/template_database_setup.php';
            
            // Create a mock database object for TemplateDatabaseSetup
            $mockDatabase = new class($pdo) {
                private $pdo;
                public function __construct($pdo) { $this->pdo = $pdo; }
                public function getConnection() { return $this->pdo; }
            };
            
            $templateSetup = new TemplateDatabaseSetup($mockDatabase);
            $result = $templateSetup->createTables();
            
            if ($result['success']) {
                error_log("Template system setup completed successfully during first install");
            } else {
                error_log("Template system setup failed during first install: " . print_r($result, true));
            }
            
        } catch (Exception $e) {
            error_log("Error setting up template system during first install: " . $e->getMessage());
            // Don't fail the entire installation if template setup fails
        }
    }
    
    /**
     * Create admin user
     */
    public function createAdminUser($username, $password, $email) {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert admin user (using password_hash column as defined in schema)
            $sql = "INSERT INTO users (username, password_hash, email, role, first_name, last_name, created_at) VALUES (?, ?, ?, 'admin', 'Admin', 'User', NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed_password, $email]);
            
            return ['success' => true, 'message' => 'Admin user created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>