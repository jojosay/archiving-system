<?php
/**
 * Backup Manager Class
 * Handles database and file backup/restore operations
 */

class BackupManager {
    private $db;
    private $backup_dir;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->backup_dir = __DIR__ . '/../backups/';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    /**
     * Get backup directory path
     */
    public function getBackupDirectory() {
        return $this->backup_dir;
    }
    
    /**
     * Export database to SQL file
     */
    public function exportDatabase() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "database_backup_{$timestamp}.sql";
            $filepath = $this->backup_dir . $filename;
            
            // Get database configuration
            require_once __DIR__ . '/../config/config.php';
            
            // Build mysqldump command
            $mysqldump_exe = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

            if (!file_exists($mysqldump_exe)) {
                return [
                    'success' => false,
                    'message' => 'mysqldump.exe not found at ' . $mysqldump_exe
                ];
            }
            if (!is_executable($mysqldump_exe)) {
                return [
                    'success' => false,
                    'message' => 'mysqldump.exe is not executable at ' . $mysqldump_exe
                ];
            }

            $password_arg = empty(DB_PASS) ? '' : ' --password=' . escapeshellarg(DB_PASS);
            $command = sprintf(
                "%s --host=%s --user=%s%s %s > %s 2>&1",
                escapeshellarg($mysqldump_exe),
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                $password_arg,
                escapeshellarg(DB_NAME),
                escapeshellarg($filepath)
            );
            
            error_log("Executing command: " . $command);

            // Execute mysqldump
            exec($command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                return [
                    'success' => true,
                    'message' => 'Database backup created successfully',
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath)
                ];
            } else {
                $error_message = 'Failed to create database backup.';
                if ($return_code !== 0) {
                    $error_message .= ' Return code: ' . $return_code;
                }
                if (file_exists($filepath)) {
                    $error_message .= ' File content: ' . file_get_contents($filepath);
                    unlink($filepath); // Delete empty/error file
                } else {
                    $error_message .= ' Output: ' . implode("\n", $output);
                }
                return [
                    'success' => false,
                    'message' => $error_message
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating database backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create ZIP backup of storage directory
     */
    public function exportFiles() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "files_backup_{$timestamp}.zip";
            $filepath = $this->backup_dir . $filename;
            $storage_path = realpath(__DIR__ . '/../storage');
            
            // Check if storage directory exists
            if (!$storage_path || !is_dir($storage_path)) {
                return [
                    'success' => false,
                    'message' => 'Storage directory not found'
                ];
            }
            
            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                error_log("Failed to open ZIP file: " . $filepath);
                return [
                    'success' => false,
                    'message' => 'Cannot create ZIP file'
                ];
            }
            
            error_log("Starting file backup from: " . $storage_path);
            
            // Add the entire storage directory with its full structure
            $this->addDirectoryToZipWithFullPath($zip, $storage_path, '');
            
            $zip->close();
            error_log("ZIP file created: " . $filepath . " with " . $zip->numFiles . " files.");
            
            if (file_exists($filepath)) {
                return [
                    'success' => true,
                    'message' => 'Files backup created successfully',
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create files backup'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating files backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Recursively add directory contents to ZIP
     */
    private function addDirectoryToZip($zip, $dir, $zipPath = '') {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . substr($filePath, strlen($dir));
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
                error_log("Adding directory to ZIP: " . $relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
                error_log("Adding file to ZIP: " . $filePath . " as " . $relativePath);
            }
        }
    }
    
    /**
     * Add directory to ZIP preserving full path structure
     */
    private function addDirectoryToZipWithFullPath($zip, $source_dir, $prefix = '') {
        $source_dir = rtrim($source_dir, '/\\');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            // Create relative path from the parent of storage directory
            $relativePath = str_replace(dirname($source_dir) . DIRECTORY_SEPARATOR, '', $filePath);
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize path separators
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath . '/');
                error_log("Adding directory to ZIP: " . $relativePath . '/');
            } else {
                $zip->addFile($filePath, $relativePath);
                error_log("Adding file to ZIP: " . $filePath . " as " . $relativePath);
            }
        }
    }
    
    /**
     * Create complete backup (database + files)
     */
    public function createCompleteBackup() {
        try {
            $results = [];
            
            // Backup database
            $db_result = $this->exportDatabase();
            $results['database'] = $db_result;
            
            // Backup files
            $files_result = $this->exportFiles();
            $results['files'] = $files_result;
            
            $success = $db_result['success'] && $files_result['success'];
            
            $error_message = '';
            if (!$db_result['success']) {
                $error_message .= 'Database backup failed: ' . $db_result['message'] . '. ';
            }
            if (!$files_result['success']) {
                $error_message .= 'Files backup failed: ' . $files_result['message'];
            }

            return [
                'success' => $success,
                'message' => $success ? 'Complete backup created successfully' : 'Backup completed with errors: ' . trim($error_message),
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating complete backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get list of available backups
     */
    public function getAvailableBackups() {
        try {
            $backups = [];
            $files = scandir($this->backup_dir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $filepath = $this->backup_dir . $file;
                if (is_file($filepath)) {
                    $backups[] = [
                        'filename' => $file,
                        'size' => filesize($filepath),
                        'created' => date('Y-m-d H:i:s', filemtime($filepath)),
                        'type' => $this->getBackupType($file)
                    ];
                }
            }
            
            // Sort by creation time (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created']) - strtotime($a['created']);
            });
            
            return $backups;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Determine backup type from filename
     */
    private function getBackupType($filename) {
        if (strpos($filename, 'database_backup_') === 0) {
            return 'database';
        } elseif (strpos($filename, 'files_backup_') === 0) {
            return 'files';
        } elseif (strpos($filename, 'complete_backup_') === 0) {
            return 'complete';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Restore database from SQL backup file
     */
    public function restoreDatabase($filename) {
        try {
            $filepath = $this->backup_dir . $filename;
            
            // Check if backup file exists and is not empty
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found or is empty'
                ];
            }
            
            // Get database configuration
            require_once __DIR__ . '/../config/config.php';
            
            // Build mysql command to restore database
            $mysql_exe = "C:\\xampp\\mysql\\bin\\mysql.exe";
            $password_arg = empty(DB_PASS) ? '' : ' --password=' . escapeshellarg(DB_PASS);
            $command = sprintf(
                "%s --host=%s --user=%s%s %s < %s",
                escapeshellarg($mysql_exe),
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                $password_arg,
                escapeshellarg(DB_NAME),
                escapeshellarg($filepath)
            );
            
            // Execute mysql restore
            exec($command, $output, $return_code);
            
            if ($return_code === 0) {
                return [
                    'success' => true,
                    'message' => 'Database restored successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to restore database. Return code: ' . $return_code . ' Output: ' . implode("\n", $output)
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error restoring database: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore files from ZIP backup
     */
    public function restoreFiles($filename) {
        try {
            $filepath = $this->backup_dir . $filename;
            $project_root = realpath(__DIR__ . '/..');
            
            // Check if backup file exists and is not empty
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found or is empty'
                ];
            }
            
            // Create backup of current storage before restore
            $storage_path = $project_root . '/storage';
            $backup_current = $storage_path . '_backup_' . date('Y-m-d_H-i-s');
            if (is_dir($storage_path)) {
                rename($storage_path, $backup_current);
                error_log("Backed up current storage to: " . $backup_current);
            }
            
            // Extract ZIP file to project root (this will restore the storage directory structure)
            $zip = new ZipArchive();
            if ($zip->open($filepath) === TRUE) {
                error_log("Attempting to extract ZIP to project root: " . $project_root);
                
                // Extract all files to project root, which will recreate the storage directory structure
                $zip->extractTo($project_root);
                $zip->close();
                
                error_log("ZIP extracted to: " . $project_root);
                
                // Verify that storage directory was restored
                if (!is_dir($storage_path)) {
                    // If storage directory wasn't created, restore from backup
                    if (is_dir($backup_current)) {
                        rename($backup_current, $storage_path);
                        error_log("Storage directory not found after extraction, restored from backup");
                    }
                    return [
                        'success' => false,
                        'message' => 'Storage directory was not properly restored from backup'
                    ];
                }
                
                // Set proper permissions recursively
                $this->setDirectoryPermissions($storage_path, 0755);
                
                // Count restored files for verification
                $total_files = $this->countFilesRecursively($storage_path);
                
                error_log("Files restored successfully. Total files: " . $total_files);
                
                return [
                    'success' => true,
                    'message' => "Files restored successfully to original locations. Total files restored: " . $total_files,
                    'backup_location' => $backup_current,
                    'files_count' => $total_files
                ];
            } else {
                // Restore original storage if extraction failed
                if (is_dir($backup_current)) {
                    rename($backup_current, $storage_path);
                    error_log("Restored original storage from: " . $backup_current);
                }
                
                return [
                    'success' => false,
                    'message' => 'Failed to extract backup file. ZipArchive error code: ' . $zip->status
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error restoring files: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Set permissions recursively for a directory
     */
    private function setDirectoryPermissions($dir, $permissions) {
        if (!is_dir($dir)) return;
        
        chmod($dir, $permissions);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                chmod($file->getRealPath(), $permissions);
            } else {
                chmod($file->getRealPath(), 0644);
            }
        }
    }
    
    /**
     * Count files recursively in a directory
     */
    private function countFilesRecursively($dir) {
        if (!is_dir($dir)) return 0;
        
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Guided restore with proper order and validation
     */
    public function guidedRestore($db_filename, $files_filename, $restore_order = 'database_first') {
        try {
            $results = [];
            $overall_success = true;
            $messages = [];
            
            // Validate backup files exist
            if (!file_exists($this->backup_dir . $db_filename)) {
                return [
                    'success' => false,
                    'message' => 'Database backup file not found: ' . $db_filename
                ];
            }
            
            if (!file_exists($this->backup_dir . $files_filename)) {
                return [
                    'success' => false,
                    'message' => 'Files backup file not found: ' . $files_filename
                ];
            }
            
            error_log("Starting guided restore with order: " . $restore_order);
            
            if ($restore_order === 'database_first') {
                // Recommended order: Database first, then files
                
                // Step 1: Restore Database
                error_log("Step 1: Restoring database from " . $db_filename);
                $db_result = $this->restoreDatabase($db_filename);
                $results['database'] = $db_result;
                
                if ($db_result['success']) {
                    $messages[] = "[SUCCESS] Database restored successfully";
                    
                    // Step 2: Restore Files
                    error_log("Step 2: Restoring files from " . $files_filename);
                    $files_result = $this->restoreFiles($files_filename);
                    $results['files'] = $files_result;
                    
                    if ($files_result['success']) {
                        $messages[] = "[SUCCESS] Files restored successfully";
                        $messages[] = "[COMPLETE] Full restore finished - system is ready";
                    } else {
                        $overall_success = false;
                        $messages[] = "[ERROR] Files restore failed: " . $files_result['message'];
                        $messages[] = "[WARNING] Database was restored but files failed - system may be inconsistent";
                    }
                } else {
                    $overall_success = false;
                    $messages[] = "[ERROR] Database restore failed: " . $db_result['message'];
                    $messages[] = "[WARNING] Skipping files restore due to database failure";
                }
                
            } else {
                // Alternative order: Files first, then database
                
                // Step 1: Restore Files
                error_log("Step 1: Restoring files from " . $files_filename);
                $files_result = $this->restoreFiles($files_filename);
                $results['files'] = $files_result;
                
                if ($files_result['success']) {
                    $messages[] = "[SUCCESS] Files restored successfully";
                    
                    // Step 2: Restore Database
                    error_log("Step 2: Restoring database from " . $db_filename);
                    $db_result = $this->restoreDatabase($db_filename);
                    $results['database'] = $db_result;
                    
                    if ($db_result['success']) {
                        $messages[] = "[SUCCESS] Database restored successfully";
                        $messages[] = "[COMPLETE] Full restore finished - system is ready";
                    } else {
                        $overall_success = false;
                        $messages[] = "[ERROR] Database restore failed: " . $db_result['message'];
                        $messages[] = "[WARNING] Files were restored but database failed - system may be inconsistent";
                    }
                } else {
                    $overall_success = false;
                    $messages[] = "[ERROR] Files restore failed: " . $files_result['message'];
                    $messages[] = "[WARNING] Skipping database restore due to files failure";
                }
            }
            
            $final_message = implode("\n", $messages);
            error_log("Guided restore completed. Success: " . ($overall_success ? 'true' : 'false'));
            
            return [
                'success' => $overall_success,
                'message' => $final_message,
                'results' => $results,
                'restore_order' => $restore_order
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error during guided restore: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get compatible backup pairs for guided restore
     */
    public function getCompatibleBackupPairs() {
        $backups = $this->getAvailableBackups();
        $db_backups = array_filter($backups, function($b) { return $b['type'] === 'database'; });
        $file_backups = array_filter($backups, function($b) { return $b['type'] === 'files'; });
        
        $pairs = [];
        
        // Try to match backups by timestamp
        foreach ($db_backups as $db_backup) {
            // Extract timestamp from filename
            if (preg_match('/database_backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql/', $db_backup['filename'], $db_matches)) {
                $db_timestamp = $db_matches[1];
                
                // Look for matching files backup
                foreach ($file_backups as $file_backup) {
                    if (preg_match('/files_backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.zip/', $file_backup['filename'], $file_matches)) {
                        $file_timestamp = $file_matches[1];
                        
                        // Consider backups compatible if they're within 5 minutes of each other
                        $db_time = strtotime(str_replace('_', ' ', str_replace('-', ':', $db_timestamp)));
                        $file_time = strtotime(str_replace('_', ' ', str_replace('-', ':', $file_timestamp)));
                        
                        if (abs($db_time - $file_time) <= 300) { // 5 minutes
                            $pairs[] = [
                                'database' => $db_backup,
                                'files' => $file_backup,
                                'compatibility' => 'exact_match',
                                'time_diff' => abs($db_time - $file_time)
                            ];
                        }
                    }
                }
            }
        }
        
        // Sort by time difference (closest matches first)
        usort($pairs, function($a, $b) {
            return $a['time_diff'] - $b['time_diff'];
        });
        
        return $pairs;
    }
    
    /**
     * Delete backup file
     */
    public function deleteBackup($filename) {
        try {
            $filepath = $this->backup_dir . $filename;
            
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found'
                ];
            }
            
            if (unlink($filepath)) {
                return [
                    'success' => true,
                    'message' => 'Backup deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete backup file'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting backup: ' . $e->getMessage()
            ];
        }
    }
}
?>