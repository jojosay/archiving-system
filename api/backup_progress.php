<?php
/**
 * Backup Progress API
 * Provides real-time progress updates for backup/restore operations
 */

// Start session and include required files
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/backup_progress_tracker.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize components
$database = new Database();
$auth = new Auth($database);

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get operation ID from request
$operation_id = $_GET['operation_id'] ?? '';

if (empty($operation_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Operation ID is required']);
    exit;
}

try {
    // Get progress data
    $progress = BackupProgressTracker::getProgressById($operation_id);
    
    if ($progress === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Progress data not found',
            'operation_id' => $operation_id
        ]);
        exit;
    }
    
    // Calculate time elapsed
    $start_time = strtotime($progress['timestamp']);
    $current_time = time();
    $elapsed_seconds = $current_time - $start_time;
    
    // Estimate remaining time if percentage is available
    $estimated_remaining = null;
    if ($progress['percentage'] && $progress['percentage'] > 0 && $progress['status'] === 'running') {
        $total_estimated = ($elapsed_seconds / $progress['percentage']) * 100;
        $estimated_remaining = max(0, $total_estimated - $elapsed_seconds);
    }
    
    // Format response
    $response = [
        'success' => true,
        'operation_id' => $operation_id,
        'step' => $progress['step'],
        'message' => $progress['message'],
        'percentage' => $progress['percentage'],
        'status' => $progress['status'],
        'timestamp' => $progress['timestamp'],
        'memory_usage' => $progress['memory_usage'],
        'peak_memory' => $progress['peak_memory'],
        'elapsed_time' => formatDuration($elapsed_seconds),
        'estimated_remaining' => $estimated_remaining ? formatDuration($estimated_remaining) : null,
        'details' => $progress['details'] ?? [],
        'is_completed' => in_array($progress['status'], ['completed', 'failed']),
        'is_failed' => $progress['status'] === 'failed'
    ];
    
    // Add result data if completed
    if (isset($progress['result'])) {
        $response['result'] = $progress['result'];
    }
    
    // Add error data if failed
    if (isset($progress['error'])) {
        $response['error'] = $progress['error'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Backup progress API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'operation_id' => $operation_id
    ]);
}

/**
 * Format duration in seconds to human readable format
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return round($seconds) . ' seconds';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $minutes . ' min ' . round($secs) . ' sec';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . ' hr ' . $minutes . ' min';
    }
}
?>