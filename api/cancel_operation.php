<?php
/**
 * Cancel Operation API
 * Handles operation cancellation requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Initialize database and auth
$database = new Database();
$auth = new Auth($database);

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $operation_id = $input['operation_id'] ?? null;
    
    if (!$operation_id) {
        throw new Exception('Operation ID is required');
    }
    
    // For now, we'll simulate operation cancellation
    // In a real implementation, this would:
    // 1. Check if operation exists and is cancellable
    // 2. Send cancellation signal to the running process
    // 3. Clean up any partial work
    // 4. Update operation status in database
    
    // Simulate cancellation logic
    $success = true; // In real implementation, check actual cancellation result
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Operation cancelled successfully'
        ]);
    } else {
        throw new Exception('Failed to cancel operation');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>