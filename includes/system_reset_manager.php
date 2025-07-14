<?php
/**
 * System Reset Manager
 * Handles complete system reset while preserving admin credentials
 */

class SystemResetManager {
    private $db;
    private $backup_dir;
    private $storage_dir;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->backup_dir = __DIR__ . '/../backups/';
        $this->storage_dir = __DIR__ . '/../storage/';
    }
    
    /**
     * Perform complete system reset
     */
    public function performSystemReset($preserve_admin = true) {
        $transaction_started = false;
        
        try {
            $results = [];
            
            // 1. Create backup before reset (outside transaction)
            $backup_result = $this->createPreResetBackup();
            $results['backup'] = $backup_result;
            
            // Start transaction for database operations
            $this->db->beginTransaction();
            $transaction_started = true;
            
            // 2. Get admin user info before deletion
            $admin_users = [];
            if ($preserve_admin) {
                $admin_users = $this->getAdminUsers();
                $results['admin_users_preserved'] = count($admin_users);
            }
            
            // 3. Clear all data tables
            $clear_result = $this->clearAllData();
            $results['data_cleared'] = $clear_result;
            
            // 4. Reset auto-increment counters
            $reset_result = $this->resetAutoIncrements();
            $results['auto_increments_reset'] = $reset_result;
            
            // 5. Restore admin users
            if ($preserve_admin && !empty($admin_users)) {
                $restore_result = $this->restoreAdminUsers($admin_users);
                $results['admin_users_restored'] = $restore_result;
            }
            
            // 6. Reset document type defaults
            $defaults_result = $this->resetDocumentTypeDefaults();
            $results['defaults_restored'] = $defaults_result;
            
            // Commit database changes
            if ($transaction_started) {
                // Check if transaction is still active before committing
                if ($this->db->inTransaction()) {
                    $this->db->commit();
                    $transaction_started = false;
                } else {
                    error_log("Warning: Transaction was expected to be active but isn't. Skipping commit.");
                    $transaction_started = false;
                }
            }
            
            // 7. Clear storage files (after successful database reset)
            $storage_result = $this->clearStorageFiles();
            $results['storage_cleared'] = $storage_result;
            
            return [
                'success' => true,
                'message' => 'System reset completed successfully',
                'details' => $results
            ];
            
        } catch (Exception $e) {
            // Only rollback if transaction was started and is still active
            if ($transaction_started && $this->db->inTransaction()) {
                try {
                    $this->db->rollBack();
                } catch (Exception $rollback_error) {
                    // Ignore rollback errors if transaction wasn't active
                    error_log("Rollback error (ignored): " . $rollback_error->getMessage());
                }
            }
            
            // Log the actual error for debugging
            error_log("System reset error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
            
            return [
                'success' => false,
                'message' => 'System reset failed: ' . $e->getMessage() . ' (Location: ' . basename($e->getFile()) . ':' . $e->getLine() . ')',
                'details' => $results ?? []
            ];
        }
    }
    
    /**
     * Create backup before reset
     */
    private function createPreResetBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backup_filename = "pre_reset_backup_{$timestamp}.sql";
            $backup_path = $this->backup_dir . $backup_filename;
            
            // Get database configuration
            require_once __DIR__ . '/../config/config.php';
            
            // Create mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_NAME),
                escapeshellarg($backup_path)
            );
            
            exec($command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($backup_path)) {
                return [
                    'success' => true,
                    'filename' => $backup_filename,
                    'size' => filesize($backup_path)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create pre-reset backup'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Backup error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get admin users before deletion
     */
    private function getAdminUsers() {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role = 'admin'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clear all data from tables
     */
    private function clearAllData() {
        try {
            $potential_tables = [
                'document_metadata',
                'document_references', 
                'documents',
                'book_images',
                'document_type_fields',
                'document_types',
                'locations',
                'users'
            ];
            
            $cleared_tables = [];
            $skipped_tables = [];
            
            // Get list of existing tables
            $existing_tables = $this->getExistingTables();
            
            // Filter to only include tables that exist
            $tables_to_clear = array_intersect($potential_tables, $existing_tables);
            
            // Disable foreign key checks temporarily
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            foreach ($tables_to_clear as $table) {
                try {
                    $stmt = $this->db->prepare("DELETE FROM {$table}");
                    if ($stmt->execute()) {
                        $cleared_tables[] = $table;
                    }
                } catch (Exception $e) {
                    $skipped_tables[] = $table . ' (Error: ' . $e->getMessage() . ')';
                    error_log("Error clearing table {$table}: " . $e->getMessage());
                }
            }
            
            // Track tables that don't exist
            $missing_tables = array_diff($potential_tables, $existing_tables);
            foreach ($missing_tables as $table) {
                $skipped_tables[] = $table . ' (Table does not exist)';
            }
            
            // Re-enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return [
                'success' => true,
                'tables_cleared' => $cleared_tables,
                'tables_skipped' => $skipped_tables,
                'count' => count($cleared_tables)
            ];
            
        } catch (Exception $e) {
            // Ensure foreign key checks are re-enabled even on error
            try {
                $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $fk_error) {
                error_log("Error re-enabling foreign key checks: " . $fk_error->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Reset auto-increment counters
     */
    private function resetAutoIncrements() {
        try {
            $potential_tables = [
                'users',
                'document_types', 
                'document_type_fields',
                'documents',
                'document_metadata',
                'document_references',
                'book_images',
                'locations'
            ];
            
            $reset_tables = [];
            $skipped_tables = [];
            
            // Get list of existing tables
            $existing_tables = $this->getExistingTables();
            
            // Filter to only include tables that exist
            $tables_to_reset = array_intersect($potential_tables, $existing_tables);
            
            foreach ($tables_to_reset as $table) {
                try {
                    $stmt = $this->db->prepare("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                    if ($stmt->execute()) {
                        $reset_tables[] = $table;
                    }
                } catch (Exception $e) {
                    $skipped_tables[] = $table . ' (Error: ' . $e->getMessage() . ')';
                }
            }
            
            return [
                'success' => true,
                'tables_reset' => $reset_tables,
                'tables_skipped' => $skipped_tables,
                'count' => count($reset_tables)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Auto-increment reset error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore admin users
     */
    private function restoreAdminUsers($admin_users) {
        try {
            $restored_count = 0;
            
            foreach ($admin_users as $admin) {
                $stmt = $this->db->prepare("
                    INSERT INTO users (username, email, password_hash, role, created_at) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([
                    $admin['username'],
                    $admin['email'], 
                    $admin['password_hash'],
                    $admin['role'],
                    $admin['created_at']
                ])) {
                    $restored_count++;
                }
            }
            
            return [
                'success' => true,
                'restored_count' => $restored_count,
                'total_admins' => count($admin_users)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Admin restore error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear storage files
     */
    private function clearStorageFiles() {
        try {
            $cleared_files = 0;
            $cleared_dirs = [];
            
            // Directories to clear
            $dirs_to_clear = [
                $this->storage_dir . 'documents/',
                $this->storage_dir . 'book_images/'
            ];
            
            foreach ($dirs_to_clear as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '*');
                    foreach ($files as $file) {
                        if (is_file($file) && basename($file) !== '.htaccess') {
                            if (unlink($file)) {
                                $cleared_files++;
                            }
                        }
                    }
                    $cleared_dirs[] = basename($dir);
                }
            }
            
            // Also clear any files in storage root (except .htaccess)
            $root_files = glob($this->storage_dir . '*');
            foreach ($root_files as $file) {
                if (is_file($file) && basename($file) !== '.htaccess') {
                    if (unlink($file)) {
                        $cleared_files++;
                    }
                }
            }
            
            return [
                'success' => true,
                'files_cleared' => $cleared_files,
                'directories_cleared' => $cleared_dirs
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Storage clear error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reset document type defaults
     */
    private function resetDocumentTypeDefaults() {
        try {
            // Insert default document types
            $default_types = [
                ['Birth Certificate', 'Official birth registration documents'],
                ['Death Certificate', 'Official death registration documents'], 
                ['Marriage Certificate', 'Official marriage registration documents'],
                ['Divorce Certificate', 'Official divorce registration documents']
            ];
            
            $inserted_count = 0;
            
            foreach ($default_types as $type) {
                $stmt = $this->db->prepare("
                    INSERT INTO document_types (name, description, created_at) 
                    VALUES (?, ?, NOW())
                ");
                
                if ($stmt->execute($type)) {
                    $inserted_count++;
                }
            }
            
            return [
                'success' => true,
                'default_types_created' => $inserted_count,
                'types' => array_column($default_types, 0)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Defaults restore error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get list of existing tables in the database
     */
    private function getExistingTables() {
        try {
            $stmt = $this->db->prepare("SHOW TABLES");
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $tables;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get system statistics before reset
     */
    public function getSystemStats() {
        try {
            $stats = [];
            
            // Count records in each table
            $potential_tables = [
                'users' => 'Total Users',
                'document_types' => 'Document Types',
                'document_type_fields' => 'Custom Fields',
                'documents' => 'Documents',
                'book_images' => 'Book Images',
                'locations' => 'Locations'
            ];
            
            $existing_tables = $this->getExistingTables();
            
            foreach ($potential_tables as $table => $label) {
                if (in_array($table, $existing_tables)) {
                    try {
                        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table}");
                        $stmt->execute();
                        $stats[$label] = $stmt->fetchColumn();
                    } catch (Exception $e) {
                        $stats[$label] = 'Error';
                    }
                } else {
                    $stats[$label] = 'N/A (Table missing)';
                }
            }
            
            // Storage file counts
            $storage_files = 0;
            $storage_size = 0;
            
            $dirs = [
                $this->storage_dir . 'documents/',
                $this->storage_dir . 'book_images/',
                $this->storage_dir
            ];
            
            foreach ($dirs as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '*');
                    foreach ($files as $file) {
                        if (is_file($file) && basename($file) !== '.htaccess') {
                            $storage_files++;
                            $storage_size += filesize($file);
                        }
                    }
                }
            }
            
            $stats['Storage Files'] = $storage_files;
            $stats['Storage Size (MB)'] = round($storage_size / 1024 / 1024, 2);
            
            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting stats: ' . $e->getMessage()
            ];
        }
    }
}
?>