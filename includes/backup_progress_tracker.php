<?php
/**
 * Backup Progress Tracker
 * Provides real-time progress feedback for long-running backup/restore operations
 */

class BackupProgressTracker {
    private $progress_file;
    private $operation_id;
    private $backup_dir;
    
    public function __construct($operation_type = 'backup') {
        $this->operation_id = uniqid($operation_type . '_');
        $this->backup_dir = __DIR__ . '/../backups/';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
        
        $this->progress_file = $this->backup_dir . 'progress_' . $this->operation_id . '.json';
    }
    
    /**
     * Update progress with current status
     */
    public function updateProgress($step, $message, $percentage = null, $details = []) {
        $progress_data = [
            'operation_id' => $this->operation_id,
            'step' => $step,
            'message' => $message,
            'percentage' => $percentage,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'details' => $details,
            'status' => 'running'
        ];
        
        file_put_contents($this->progress_file, json_encode($progress_data, JSON_PRETTY_PRINT));
        
        // Also log to error log for debugging
        error_log("Backup Progress [{$this->operation_id}]: {$step} - {$message}" . 
                 ($percentage !== null ? " ({$percentage}%)" : ""));
    }
    
    /**
     * Mark operation as completed
     */
    public function markCompleted($final_message, $result_data = []) {
        $progress_data = [
            'operation_id' => $this->operation_id,
            'step' => 'completed',
            'message' => $final_message,
            'percentage' => 100,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'result' => $result_data,
            'status' => 'completed'
        ];
        
        file_put_contents($this->progress_file, json_encode($progress_data, JSON_PRETTY_PRINT));
        error_log("Backup Progress [{$this->operation_id}]: COMPLETED - {$final_message}");
    }
    
    /**
     * Mark operation as failed
     */
    public function markFailed($error_message, $error_details = []) {
        $progress_data = [
            'operation_id' => $this->operation_id,
            'step' => 'failed',
            'message' => $error_message,
            'percentage' => null,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'error' => $error_details,
            'status' => 'failed'
        ];
        
        file_put_contents($this->progress_file, json_encode($progress_data, JSON_PRETTY_PRINT));
        error_log("Backup Progress [{$this->operation_id}]: FAILED - {$error_message}");
    }
    
    /**
     * Get current progress
     */
    public function getProgress() {
        if (file_exists($this->progress_file)) {
            return json_decode(file_get_contents($this->progress_file), true);
        }
        return null;
    }
    
    /**
     * Get progress by operation ID
     */
    public static function getProgressById($operation_id) {
        $backup_dir = __DIR__ . '/../backups/';
        $progress_file = $backup_dir . 'progress_' . $operation_id . '.json';
        
        if (file_exists($progress_file)) {
            return json_decode(file_get_contents($progress_file), true);
        }
        return null;
    }
    
    /**
     * Clean up progress file
     */
    public function cleanup() {
        if (file_exists($this->progress_file)) {
            unlink($this->progress_file);
        }
    }
    
    /**
     * Clean up old progress files (older than 24 hours)
     */
    public static function cleanupOldProgress() {
        $backup_dir = __DIR__ . '/../backups/';
        $files = glob($backup_dir . 'progress_*.json');
        
        foreach ($files as $file) {
            if (filemtime($file) < time() - 86400) { // 24 hours
                unlink($file);
            }
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($size, $precision = 2) {
        if ($size == 0) return '0 B';
        
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Get operation ID for tracking
     */
    public function getOperationId() {
        return $this->operation_id;
    }
    
    /**
     * Estimate database size for progress calculation
     */
    public function estimateDatabaseSize($database) {
        try {
            $db = $database->getConnection();
            $stmt = $db->prepare("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
            ");
            $stmt->execute([DB_NAME]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['size_mb'] ?? 0;
        } catch (Exception $e) {
            error_log("Error estimating database size: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get file size in MB
     */
    public function getFileSizeMB($filepath) {
        if (file_exists($filepath)) {
            return round(filesize($filepath) / 1024 / 1024, 2);
        }
        return 0;
    }
}
?>