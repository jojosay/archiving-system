<?php
/**
 * Backup Manager Class
 * Handles database and file backup/restore operations
 */

require_once __DIR__ . '/backup_progress_tracker.php';

class BackupManager {
    private $db;
    private $backup_dir;
    private $progress_tracker;
    
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
            // Initialize progress tracker
            $this->progress_tracker = new BackupProgressTracker('database_backup');
            $operation_id = $this->progress_tracker->getOperationId();
            
            // Set limits for large database operations
            set_time_limit(0); // No time limit
            ini_set('memory_limit', '2G'); // Increase memory limit
            ini_set('max_execution_time', 0); // No execution time limit
            
            $this->progress_tracker->updateProgress('initializing', 'Starting database backup...', 5);
            
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "database_backup_{$timestamp}.sql";
            $filepath = $this->backup_dir . $filename;
            
            // Estimate database size
            $db_size_mb = $this->progress_tracker->estimateDatabaseSize(new Database());
            $this->progress_tracker->updateProgress('analyzing', "Database size: {$db_size_mb} MB", 10, [
                'database_size_mb' => $db_size_mb,
                'estimated_time' => $db_size_mb > 1000 ? 'Several minutes' : 'Less than a minute'
            ]);
            
            // Get database configuration
            require_once __DIR__ . '/../config/config.php';
            
            $this->progress_tracker->updateProgress('preparing', 'Locating mysqldump executable...', 20);
            
            // Build mysqldump command
            // Try common MySQL paths
            $possible_paths = [
                "C:\\xampp\\mysql\\bin\\mysqldump.exe",
                "C:\\wamp\\bin\\mysql\\mysql8.0.21\\bin\\mysqldump.exe",
                "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe",
                "/usr/bin/mysqldump",
                "/usr/local/bin/mysqldump"
            ];
            
            $mysqldump_exe = "mysqldump"; // Default to system PATH
            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    $mysqldump_exe = $path;
                    break;
                }
            }

            if (!file_exists($mysqldump_exe)) {
                $this->progress_tracker->markFailed('mysqldump.exe not found at ' . $mysqldump_exe);
                return [
                    'success' => false,
                    'message' => 'mysqldump.exe not found at ' . $mysqldump_exe,
                    'operation_id' => $operation_id
                ];
            }
            if (!is_executable($mysqldump_exe)) {
                return [
                    'success' => false,
                    'message' => 'mysqldump.exe is not executable at ' . $mysqldump_exe
                ];
            }

            $password_arg = empty(DB_PASS) ? '' : ' --password=' . escapeshellarg(DB_PASS);
            
            // Enhanced options for large databases
            $large_db_options = [
                '--single-transaction',     // Consistent backup for InnoDB
                '--routines',              // Include stored procedures
                '--triggers',              // Include triggers
                '--lock-tables=false',     // Don't lock tables (for large DBs)
                '--quick',                 // Retrieve rows one at a time
                '--extended-insert=false', // Separate INSERT for each row (safer for large data)
                '--max_allowed_packet=1G', // Handle large packets
                '--default-character-set=utf8mb4' // Proper charset
            ];
            
            $command = sprintf(
                "%s --host=%s --user=%s%s %s %s > %s 2>&1",
                escapeshellarg($mysqldump_exe),
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                $password_arg,
                implode(' ', $large_db_options),
                escapeshellarg(DB_NAME),
                escapeshellarg($filepath)
            );
            
            $this->progress_tracker->updateProgress('executing', 'Running mysqldump command...', 50, [
                'command' => 'mysqldump with enhanced options for large databases'
            ]);
            
            error_log("Executing command: " . $command);

            // Execute mysqldump
            exec($command, $output, $return_code);
            
            $this->progress_tracker->updateProgress('verifying', 'Verifying backup file...', 80);
            
            if ($return_code === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                $file_size_mb = $this->progress_tracker->getFileSizeMB($filepath);
                $this->progress_tracker->markCompleted("Database backup completed successfully ({$file_size_mb} MB)", [
                    'filename' => $filename,
                    'file_size_mb' => $file_size_mb,
                    'database_size_mb' => $db_size_mb
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Database backup created successfully',
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'size' => filesize($filepath),
                    'operation_id' => $operation_id
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
                
                $this->progress_tracker->markFailed($error_message, [
                    'return_code' => $return_code,
                    'output' => $output
                ]);
                
                return [
                    'success' => false,
                    'message' => $error_message,
                    'operation_id' => $operation_id
                ];
            }
            
        } catch (Exception $e) {
            if (isset($this->progress_tracker)) {
                $this->progress_tracker->markFailed('Error creating database backup: ' . $e->getMessage(), [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $operation_id = $this->progress_tracker->getOperationId();
            }
            
            return [
                'success' => false,
                'message' => 'Error creating database backup: ' . $e->getMessage(),
                'operation_id' => $operation_id ?? null
            ];
        }
    }
    
    /**
     * Create ZIP backup of storage directory
     */
    public function exportFiles() {
        try {
            // Set limits for large file operations
            set_time_limit(0); // No time limit
            ini_set('memory_limit', '2G'); // Increase memory limit
            ini_set('max_execution_time', 0); // No execution time limit
            
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
            // Initialize progress tracker for complete backup
            $this->progress_tracker = new BackupProgressTracker('complete_backup');
            $operation_id = $this->progress_tracker->getOperationId();
            
            // Set limits for large operations
            set_time_limit(0);
            ini_set('memory_limit', '2G');
            ini_set('max_execution_time', 0);
            
            $this->progress_tracker->updateProgress('initializing', 'Starting complete backup...', 5);
            
            $results = [];
            
            // Step 1: Backup database
            $this->progress_tracker->updateProgress('step1_start', 'Step 1: Creating database backup...', 10);
            $db_result = $this->exportDatabase();
            $results['database'] = $db_result;
            
            if ($db_result['success']) {
                $this->progress_tracker->updateProgress('step1_complete', 'Database backup completed', 50);
                
                // Step 2: Backup files
                $this->progress_tracker->updateProgress('step2_start', 'Step 2: Creating files backup...', 55);
                $files_result = $this->exportFiles();
                $results['files'] = $files_result;
                
                if ($files_result['success']) {
                    $this->progress_tracker->updateProgress('step2_complete', 'Files backup completed', 90);
                }
            } else {
                // If database backup fails, still try files backup
                $this->progress_tracker->updateProgress('step2_start', 'Database failed, attempting files backup...', 55);
                $files_result = $this->exportFiles();
                $results['files'] = $files_result;
            }
            
            $success = $db_result['success'] && $files_result['success'];
            
            $error_message = '';
            if (!$db_result['success']) {
                $error_message .= 'Database backup failed: ' . $db_result['message'] . '. ';
            }
            if (!$files_result['success']) {
                $error_message .= 'Files backup failed: ' . $files_result['message'];
            }
            
            // Mark operation as completed or failed
            if ($success) {
                $this->progress_tracker->markCompleted('Complete backup created successfully', [
                    'database_file' => $db_result['filename'] ?? 'failed',
                    'files_file' => $files_result['filename'] ?? 'failed',
                    'database_size_mb' => isset($db_result['size']) ? round($db_result['size'] / 1024 / 1024, 2) : 0,
                    'files_size_mb' => isset($files_result['size']) ? round($files_result['size'] / 1024 / 1024, 2) : 0
                ]);
            } else {
                $this->progress_tracker->markFailed('Complete backup failed', [
                    'database_success' => $db_result['success'],
                    'files_success' => $files_result['success'],
                    'error_details' => trim($error_message)
                ]);
            }

            return [
                'success' => $success,
                'message' => $success ? 'Complete backup created successfully' : 'Backup completed with errors: ' . trim($error_message),
                'results' => $results,
                'operation_id' => $operation_id
            ];
            
        } catch (Exception $e) {
            if (isset($this->progress_tracker)) {
                $this->progress_tracker->markFailed('Error creating complete backup: ' . $e->getMessage(), [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $operation_id = $this->progress_tracker->getOperationId();
            }
            
            return [
                'success' => false,
                'message' => 'Error creating complete backup: ' . $e->getMessage(),
                'operation_id' => $operation_id ?? null
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
            // Initialize progress tracker
            $this->progress_tracker = new BackupProgressTracker('database_restore');
            $operation_id = $this->progress_tracker->getOperationId();
            
            // Set limits for large database operations
            set_time_limit(0); // No time limit
            ini_set('memory_limit', '2G'); // Increase memory limit
            ini_set('max_execution_time', 0); // No execution time limit
            
            $this->progress_tracker->updateProgress('initializing', 'Starting database restore...', 5);
            
            $filepath = $this->backup_dir . $filename;
            
            // Check if backup file exists and is not empty
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                $this->progress_tracker->markFailed('Backup file not found or is empty');
                return [
                    'success' => false,
                    'message' => 'Backup file not found or is empty',
                    'operation_id' => $operation_id
                ];
            }
            
            $file_size_mb = $this->progress_tracker->getFileSizeMB($filepath);
            $this->progress_tracker->updateProgress('analyzing', "Backup file size: {$file_size_mb} MB", 15, [
                'backup_file' => $filename,
                'file_size_mb' => $file_size_mb
            ]);
            
            // Get database configuration
            require_once __DIR__ . '/../config/config.php';
            
            $this->progress_tracker->updateProgress('preparing', 'Locating mysql executable...', 25);
            
            // Build mysql command to restore database
            // Try common MySQL paths
            $possible_paths = [
                "C:\\xampp\\mysql\\bin\\mysql.exe",
                "C:\\wamp\\bin\\mysql\\mysql8.0.21\\bin\\mysql.exe",
                "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe",
                "/usr/bin/mysql",
                "/usr/local/bin/mysql"
            ];
            
            $mysql_exe = "mysql"; // Default to system PATH
            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    $mysql_exe = $path;
                    break;
                }
            }
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
            
            $this->progress_tracker->updateProgress('executing', 'Restoring database from backup...', 60);
            
            // Execute mysql restore
            exec($command, $output, $return_code);
            
            $this->progress_tracker->updateProgress('verifying', 'Verifying database restore...', 90);
            
            if ($return_code === 0) {
                $this->progress_tracker->markCompleted("Database restored successfully from {$filename}", [
                    'backup_file' => $filename,
                    'file_size_mb' => $file_size_mb
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Database restored successfully',
                    'operation_id' => $operation_id
                ];
            } else {
                $error_message = 'Failed to restore database. Return code: ' . $return_code . ' Output: ' . implode("\n", $output);
                $this->progress_tracker->markFailed($error_message, [
                    'return_code' => $return_code,
                    'output' => $output
                ]);
                
                return [
                    'success' => false,
                    'message' => $error_message,
                    'operation_id' => $operation_id
                ];
            }
            
        } catch (Exception $e) {
            if (isset($this->progress_tracker)) {
                $this->progress_tracker->markFailed('Error restoring database: ' . $e->getMessage(), [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $operation_id = $this->progress_tracker->getOperationId();
            }
            
            return [
                'success' => false,
                'message' => 'Error restoring database: ' . $e->getMessage(),
                'operation_id' => $operation_id ?? null
            ];
        }
    }
    
    /**
     * Restore files from ZIP backup
     */
    public function restoreFiles($filename) {
        try {
            // Set limits for large file operations
            set_time_limit(0); // No time limit
            ini_set('memory_limit', '2G'); // Increase memory limit
            ini_set('max_execution_time', 0); // No execution time limit
            
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
            // Initialize progress tracker for guided restore
            $this->progress_tracker = new BackupProgressTracker('guided_restore');
            $operation_id = $this->progress_tracker->getOperationId();
            
            // Set limits for large operations
            set_time_limit(0);
            ini_set('memory_limit', '2G');
            ini_set('max_execution_time', 0);
            
            $this->progress_tracker->updateProgress('initializing', 'Starting guided restore process...', 5);
            
            $results = [];
            $overall_success = true;
            $messages = [];
            
            $this->progress_tracker->updateProgress('validating', 'Validating backup files...', 10);
            
            // Validate backup files exist
            if (!file_exists($this->backup_dir . $db_filename)) {
                $this->progress_tracker->markFailed('Database backup file not found: ' . $db_filename);
                return [
                    'success' => false,
                    'message' => 'Database backup file not found: ' . $db_filename,
                    'operation_id' => $operation_id
                ];
            }
            
            if (!file_exists($this->backup_dir . $files_filename)) {
                $this->progress_tracker->markFailed('Files backup file not found: ' . $files_filename);
                return [
                    'success' => false,
                    'message' => 'Files backup file not found: ' . $files_filename,
                    'operation_id' => $operation_id
                ];
            }
            
            // Get file sizes for progress tracking
            $db_size_mb = $this->progress_tracker->getFileSizeMB($this->backup_dir . $db_filename);
            $files_size_mb = $this->progress_tracker->getFileSizeMB($this->backup_dir . $files_filename);
            
            $this->progress_tracker->updateProgress('planning', "Restore plan: {$restore_order}", 15, [
                'database_backup' => $db_filename,
                'files_backup' => $files_filename,
                'db_size_mb' => $db_size_mb,
                'files_size_mb' => $files_size_mb,
                'restore_order' => $restore_order
            ]);
            
            error_log("Starting guided restore with order: " . $restore_order);
            
            if ($restore_order === 'database_first') {
                // Recommended order: Database first, then files
                
                $this->progress_tracker->updateProgress('step1_start', 'Step 1: Starting database restore...', 25);
                
                // Step 1: Restore Database
                error_log("Step 1: Restoring database from " . $db_filename);
                
                // Create a temporary progress tracker for database restore
                $temp_tracker = $this->progress_tracker;
                $db_result = $this->restoreDatabase($db_filename);
                $this->progress_tracker = $temp_tracker; // Restore main tracker
                
                $results['database'] = $db_result;
                
                if ($db_result['success']) {
                    $messages[] = "[SUCCESS] Database restored successfully";
                    $this->progress_tracker->updateProgress('step1_complete', 'Database restore completed successfully', 50);
                    
                    // Step 2: Restore Files
                    $this->progress_tracker->updateProgress('step2_start', 'Step 2: Starting files restore...', 55);
                    error_log("Step 2: Restoring files from " . $files_filename);
                    
                    // Files restore doesn't have progress tracking yet, so we'll simulate it
                    $files_result = $this->restoreFiles($files_filename);
                    $results['files'] = $files_result;
                    
                    if ($files_result['success']) {
                        $messages[] = "[SUCCESS] Files restored successfully";
                        $messages[] = "[COMPLETE] Full restore finished - system is ready";
                        $this->progress_tracker->updateProgress('step2_complete', 'Files restore completed successfully', 90);
                    } else {
                        $overall_success = false;
                        $messages[] = "[ERROR] Files restore failed: " . $files_result['message'];
                        $messages[] = "[WARNING] Database was restored but files failed - system may be inconsistent";
                        $this->progress_tracker->updateProgress('step2_failed', 'Files restore failed', 75, [
                            'error' => $files_result['message']
                        ]);
                    }
                } else {
                    $overall_success = false;
                    $messages[] = "[ERROR] Database restore failed: " . $db_result['message'];
                    $messages[] = "[WARNING] Skipping files restore due to database failure";
                    $this->progress_tracker->updateProgress('step1_failed', 'Database restore failed', 40, [
                        'error' => $db_result['message']
                    ]);
                }
                
            } else {
                // Alternative order: Files first, then database
                
                $this->progress_tracker->updateProgress('step1_start', 'Step 1: Starting files restore...', 25);
                
                // Step 1: Restore Files
                error_log("Step 1: Restoring files from " . $files_filename);
                $files_result = $this->restoreFiles($files_filename);
                $results['files'] = $files_result;
                
                if ($files_result['success']) {
                    $messages[] = "[SUCCESS] Files restored successfully";
                    $this->progress_tracker->updateProgress('step1_complete', 'Files restore completed successfully', 50);
                    
                    // Step 2: Restore Database
                    $this->progress_tracker->updateProgress('step2_start', 'Step 2: Starting database restore...', 55);
                    error_log("Step 2: Restoring database from " . $db_filename);
                    
                    // Create a temporary progress tracker for database restore
                    $temp_tracker = $this->progress_tracker;
                    $db_result = $this->restoreDatabase($db_filename);
                    $this->progress_tracker = $temp_tracker; // Restore main tracker
                    
                    $results['database'] = $db_result;
                    
                    if ($db_result['success']) {
                        $messages[] = "[SUCCESS] Database restored successfully";
                        $messages[] = "[COMPLETE] Full restore finished - system is ready";
                        $this->progress_tracker->updateProgress('step2_complete', 'Database restore completed successfully', 90);
                    } else {
                        $overall_success = false;
                        $messages[] = "[ERROR] Database restore failed: " . $db_result['message'];
                        $messages[] = "[WARNING] Files were restored but database failed - system may be inconsistent";
                        $this->progress_tracker->updateProgress('step2_failed', 'Database restore failed', 75, [
                            'error' => $db_result['message']
                        ]);
                    }
                } else {
                    $overall_success = false;
                    $messages[] = "[ERROR] Files restore failed: " . $files_result['message'];
                    $messages[] = "[WARNING] Skipping database restore due to files failure";
                    $this->progress_tracker->updateProgress('step1_failed', 'Files restore failed', 40, [
                        'error' => $files_result['message']
                    ]);
                }
            }
            
            $final_message = implode("\n", $messages);
            error_log("Guided restore completed. Success: " . ($overall_success ? 'true' : 'false'));
            
            // Mark operation as completed or failed
            if ($overall_success) {
                $this->progress_tracker->markCompleted("Guided restore completed successfully", [
                    'database_backup' => $db_filename,
                    'files_backup' => $files_filename,
                    'restore_order' => $restore_order,
                    'db_size_mb' => $db_size_mb,
                    'files_size_mb' => $files_size_mb,
                    'total_steps' => 2
                ]);
            } else {
                $this->progress_tracker->markFailed("Guided restore failed", [
                    'database_backup' => $db_filename,
                    'files_backup' => $files_filename,
                    'restore_order' => $restore_order,
                    'failure_reason' => $final_message
                ]);
            }
            
            return [
                'success' => $overall_success,
                'message' => $final_message,
                'results' => $results,
                'restore_order' => $restore_order,
                'operation_id' => $operation_id
            ];
            
        } catch (Exception $e) {
            if (isset($this->progress_tracker)) {
                $this->progress_tracker->markFailed('Error during guided restore: ' . $e->getMessage(), [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $operation_id = $this->progress_tracker->getOperationId();
            }
            
            return [
                'success' => false,
                'message' => 'Error during guided restore: ' . $e->getMessage(),
                'operation_id' => $operation_id ?? null
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