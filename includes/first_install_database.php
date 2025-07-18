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
            
            // After successful schema import, set up template system with v1.0.7 features
            $this->setupTemplateSystem($pdo);
            $this->ensureV107Features($pdo);
            
            return ['success' => true, 'message' => 'Schema imported successfully with v1.0.7 features. Created ' . count($tables) . ' tables.'];
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
            error_log("Setting up enhanced template system during first install");
            
            // Read and execute template enhancements SQL
            $template_sql_file = __DIR__ . '/../database/template_enhancements.sql';
            
            if (file_exists($template_sql_file)) {
                $sql = file_get_contents($template_sql_file);
                
                // Split into individual statements
                $statements = explode(';', $sql);
                $statements = array_filter(
                    array_map('trim', $statements),
                    function($stmt) { 
                        return !empty($stmt) && strlen(trim($stmt)) > 10;
                    }
                );
                
                foreach ($statements as $index => $statement) {
                    if (!empty(trim($statement))) {
                        try {
                            error_log("Executing template enhancement statement " . ($index + 1));
                            $pdo->exec($statement);
                        } catch (PDOException $e) {
                            // Log error but continue - some statements may fail if tables already exist
                            error_log("Template enhancement statement " . ($index + 1) . " failed (this may be normal): " . $e->getMessage());
                        }
                    }
                }
                
                error_log("Template system enhancements setup completed successfully during first install");
            } else {
                error_log("Template enhancements SQL file not found at: " . $template_sql_file);
            }
            
        } catch (Exception $e) {
            error_log("Error setting up template system during first install: " . $e->getMessage());
            // Don't fail the entire installation if template setup fails
        }
    }
    
    /**
     * Ensure v1.0.7 features are properly configured during first install
     */
    private function ensureV107Features($pdo) {
        try {
            error_log("Configuring v1.0.7 features during first install");
            
            // Ensure multi-page support columns exist with proper defaults
            $pdo->exec("
                ALTER TABLE pdf_template_fields 
                MODIFY COLUMN page_number int(11) NOT NULL DEFAULT 1
            ");
            
            // Add performance indexes for multi-page queries
            $pdo->exec("
                CREATE INDEX IF NOT EXISTS idx_template_page ON pdf_template_fields (template_id, page_number)
            ");
            $pdo->exec("
                CREATE INDEX IF NOT EXISTS idx_template_position ON pdf_template_fields (template_id, page_number, y_position)
            ");
            
            // Ensure font styling columns exist for enhanced UI
            $pdo->exec("
                ALTER TABLE pdf_template_fields 
                ADD COLUMN IF NOT EXISTS font_weight varchar(20) DEFAULT 'normal'
            ");
            $pdo->exec("
                ALTER TABLE pdf_template_fields 
                ADD COLUMN IF NOT EXISTS font_style varchar(20) DEFAULT 'normal'
            ");
            
            // Ensure template metadata columns exist for better management
            $pdo->exec("
                ALTER TABLE pdf_templates 
                ADD COLUMN IF NOT EXISTS pages int(11) DEFAULT 1
            ");
            $pdo->exec("
                ALTER TABLE pdf_templates 
                ADD COLUMN IF NOT EXISTS file_size bigint DEFAULT 0
            ");
            $pdo->exec("
                ALTER TABLE pdf_templates 
                ADD COLUMN IF NOT EXISTS last_modified timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ");
            
            // Insert version tracking record
            $pdo->exec("
                INSERT INTO system_settings (setting_key, setting_value, description) 
                VALUES ('database_schema_version', '1.0.7', 'Database schema version for feature compatibility')
                ON DUPLICATE KEY UPDATE 
                setting_value = '1.0.7'
            ");
            
            // Insert installation feature flags
            $features = [
                'pdf_template_manager' => 'Modern PDF Template Manager with table interface',
                'multi_page_support' => 'Multi-page PDF template support with page navigation',
                'modern_ui' => 'Professional UI/UX with gradients and animations',
                'enhanced_actions' => 'Enhanced action buttons and user interactions'
            ];
            
            foreach ($features as $feature => $description) {
                $pdo->exec("
                    INSERT INTO system_settings (setting_key, setting_value, description) 
                    VALUES ('feature_$feature', '1', '$description')
                    ON DUPLICATE KEY UPDATE 
                    setting_value = '1'
                ");
            }
            
            error_log("v1.0.7 features configured successfully during first install");
            
        } catch (Exception $e) {
            error_log("Error configuring v1.0.7 features during first install: " . $e->getMessage());
            // Don't fail the entire installation if feature configuration fails
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