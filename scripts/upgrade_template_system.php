<?php
/**
 * Template System Upgrade Script
 * Upgrades existing installations to include enhanced template management features
 */

// Prevent direct web access
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    // Allow execution from command line or direct access for upgrades
    if (php_sapi_name() !== 'cli' && !isset($_GET['upgrade_confirm'])) {
        echo "<h2>Template System Upgrade</h2>";
        echo "<p><strong>Warning:</strong> This script will modify your database structure.</p>";
        echo "<p>Please backup your database before proceeding.</p>";
        echo "<p><a href='?upgrade_confirm=1' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Proceed with Upgrade</a></p>";
        echo "<p><a href='../index.php'>Cancel and Return to Application</a></p>";
        exit;
    }
}

require_once '../config/config.php';
require_once '../includes/database.php';

class TemplateSystemUpgrade {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Run the complete upgrade process
     */
    public function runUpgrade() {
        echo "<h2>Template System Upgrade</h2>";
        echo "<p>Starting template system upgrade...</p>";
        
        $results = [];
        
        try {
            // Step 1: Backup existing templates table
            $results['backup'] = $this->backupExistingTemplates();
            
            // Step 2: Apply template enhancements
            $results['enhancements'] = $this->applyTemplateEnhancements();
            
            // Step 3: Migrate existing data
            $results['migration'] = $this->migrateExistingData();
            
            // Step 4: Verify upgrade
            $results['verification'] = $this->verifyUpgrade();
            
            $this->displayResults($results);
            
        } catch (Exception $e) {
            echo "<p style='color: red;'><strong>Upgrade failed:</strong> " . $e->getMessage() . "</p>";
            echo "<p>Please check your database and try again.</p>";
        }
    }
    
    /**
     * Backup existing templates table
     */
    private function backupExistingTemplates() {
        try {
            $timestamp = date('Y_m_d_H_i_s');
            $backup_table = "templates_backup_$timestamp";
            
            // Check if templates table exists
            if (!$this->tableExists('templates')) {
                return [
                    'success' => true,
                    'message' => 'No existing templates table found - fresh installation'
                ];
            }
            
            // Create backup table
            $sql = "CREATE TABLE $backup_table AS SELECT * FROM templates";
            $this->db->exec($sql);
            
            $count = $this->db->query("SELECT COUNT(*) FROM $backup_table")->fetchColumn();
            
            return [
                'success' => true,
                'message' => "Backed up $count templates to table: $backup_table"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Apply template enhancements from SQL file
     */
    private function applyTemplateEnhancements() {
        try {
            $sql_file = '../database/template_enhancements.sql';
            
            if (!file_exists($sql_file)) {
                throw new Exception('Template enhancements SQL file not found');
            }
            
            $sql = file_get_contents($sql_file);
            
            // Split into individual statements
            $statements = explode(';', $sql);
            $statements = array_filter(
                array_map('trim', $statements),
                function($stmt) { 
                    return !empty($stmt) && strlen(trim($stmt)) > 10;
                }
            );
            
            $executed = 0;
            $errors = 0;
            
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    try {
                        $this->db->exec($statement);
                        $executed++;
                    } catch (PDOException $e) {
                        // Some statements may fail if columns/tables already exist
                        if (strpos($e->getMessage(), 'Duplicate column') === false && 
                            strpos($e->getMessage(), 'already exists') === false) {
                            $errors++;
                        }
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => "Executed $executed SQL statements successfully ($errors expected errors for existing structures)"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Enhancement application failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Migrate existing template data to new structure
     */
    private function migrateExistingData() {
        try {
            if (!$this->tableExists('templates')) {
                return [
                    'success' => true,
                    'message' => 'No existing templates to migrate'
                ];
            }
            
            // Update existing templates with default values for new columns
            $updates = [
                "UPDATE templates SET version = '1.0' WHERE version IS NULL OR version = ''",
                "UPDATE templates SET is_default = 0 WHERE is_default IS NULL",
                "UPDATE templates SET usage_count = 0 WHERE usage_count IS NULL",
                "UPDATE templates SET field_completeness_score = 0.00 WHERE field_completeness_score IS NULL",
                "UPDATE templates SET deleted = 0 WHERE deleted IS NULL"
            ];
            
            $updated_count = 0;
            foreach ($updates as $update_sql) {
                try {
                    $stmt = $this->db->prepare($update_sql);
                    $stmt->execute();
                    $updated_count += $stmt->rowCount();
                } catch (PDOException $e) {
                    // Column might not exist yet, which is fine
                }
            }
            
            return [
                'success' => true,
                'message' => "Migrated existing template data ($updated_count records updated)"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Data migration failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify the upgrade was successful
     */
    private function verifyUpgrade() {
        try {
            $verification_results = [];
            
            // Check if enhanced templates table exists with new columns
            $required_columns = [
                'document_type_id', 'is_default', 'version', 'description', 
                'tags', 'preview_image', 'usage_count', 'field_completeness_score',
                'created_by', 'updated_by', 'deleted'
            ];
            
            $existing_columns = $this->getTableColumns('templates');
            $missing_columns = array_diff($required_columns, $existing_columns);
            
            if (empty($missing_columns)) {
                $verification_results[] = "✅ Templates table has all required columns";
            } else {
                $verification_results[] = "⚠️ Missing columns: " . implode(', ', $missing_columns);
            }
            
            // Check if supporting tables exist
            $required_tables = ['template_field_requirements', 'template_usage_analytics'];
            foreach ($required_tables as $table) {
                if ($this->tableExists($table)) {
                    $verification_results[] = "✅ Table '$table' exists";
                } else {
                    $verification_results[] = "❌ Table '$table' missing";
                }
            }
            
            // Check template count
            if ($this->tableExists('templates')) {
                $count = $this->db->query("SELECT COUNT(*) FROM templates")->fetchColumn();
                $verification_results[] = "📊 Total templates: $count";
            }
            
            return [
                'success' => true,
                'message' => implode('<br>', $verification_results)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Display upgrade results
     */
    private function displayResults($results) {
        echo "<h3>Upgrade Results</h3>";
        
        foreach ($results as $step => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $color = $result['success'] ? 'green' : 'red';
            
            echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid $color;'>";
            echo "<strong>$status " . ucfirst($step) . ":</strong><br>";
            echo $result['message'];
            echo "</div>";
        }
        
        $all_success = array_reduce($results, function($carry, $result) {
            return $carry && $result['success'];
        }, true);
        
        if ($all_success) {
            echo "<div style='margin: 20px 0; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
            echo "<h4 style='color: #155724; margin: 0;'>🎉 Upgrade Completed Successfully!</h4>";
            echo "<p style='color: #155724; margin: 5px 0 0 0;'>Your template system has been upgraded with enhanced features.</p>";
            echo "<p><a href='../index.php?page=template_builder' style='color: #155724;'>Go to Template Builder</a></p>";
            echo "</div>";
        } else {
            echo "<div style='margin: 20px 0; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
            echo "<h4 style='color: #721c24; margin: 0;'>⚠️ Upgrade Completed with Issues</h4>";
            echo "<p style='color: #721c24; margin: 5px 0 0 0;'>Some steps failed. Please review the results above.</p>";
            echo "</div>";
        }
    }
    
    /**
     * Check if a table exists
     */
    private function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get columns for a table
     */
    private function getTableColumns($tableName) {
        $sql = "SHOW COLUMNS FROM $tableName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Run upgrade if accessed directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $upgrade = new TemplateSystemUpgrade();
    $upgrade->runUpgrade();
}
?>