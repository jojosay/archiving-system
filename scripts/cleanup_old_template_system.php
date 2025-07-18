<?php
/**
 * Cleanup Old Template System
 * Removes old template tables and files from the previous implementation
 */

require_once '../config/config.php';
require_once '../includes/database.php';

class OldTemplateSystemCleanup {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Remove old template database tables
     */
    public function removeOldTables() {
        $results = [];
        
        try {
            // Drop tables in correct order (foreign key dependencies)
            $tables = [
                'template_downloads',
                'document_templates', 
                'template_categories'
            ];
            
            foreach ($tables as $table) {
                $sql = "DROP TABLE IF EXISTS `{$table}`";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute();
                
                $results[$table] = [
                    'success' => $result,
                    'message' => $result ? "Table {$table} dropped successfully" : "Failed to drop table {$table}"
                ];
            }
            
            return [
                'success' => true,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'results' => $results
            ];
        }
    }
    
    /**
     * List old template files to be removed
     */
    public function listOldTemplateFiles() {
        $files_to_remove = [
            // Old template system files
            'includes/template_manager.php',
            'includes/template_storage_manager.php', 
            'includes/template_validator.php',
            'includes/template_category_manager.php',
            'includes/template_database_setup.php',
            'includes/template_merge_manager.php',
            
            // Old template pages
            'pages/template_management.php',
            'pages/template_upload.php',
            'pages/template_gallery.php', 
            'pages/template_categories.php',
            'pages/template_edit.php',
            'pages/template_placeholders.php'
        ];
        
        return $files_to_remove;
    }
}

// Run cleanup if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    echo "<h2>Old Template System Cleanup</h2>";
    
    $cleanup = new OldTemplateSystemCleanup();
    
    // Show files that will be removed
    echo "<h3>Files to be removed:</h3>";
    $files = $cleanup->listOldTemplateFiles();
    echo "<ul>";
    foreach ($files as $file) {
        $exists = file_exists("../{$file}") ? " (EXISTS)" : " (NOT FOUND)";
        echo "<li>{$file}{$exists}</li>";
    }
    echo "</ul>";
    
    // Remove database tables
    echo "<h3>Removing database tables:</h3>";
    $result = $cleanup->removeOldTables();
    
    if ($result['success']) {
        echo "<p style='color: green;'>Database cleanup completed successfully!</p>";
        foreach ($result['results'] as $table => $info) {
            $color = $info['success'] ? 'green' : 'red';
            echo "<p style='color: {$color};'>{$info['message']}</p>";
        }
    } else {
        echo "<p style='color: red;'>Database cleanup failed: {$result['error']}</p>";
    }
    
    echo "<p><strong>Note:</strong> File removal must be done manually for safety.</p>";
}
?>