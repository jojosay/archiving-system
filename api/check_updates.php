<?php
// API endpoint for checking app updates
header('Content-Type: application/json');

// Include required files
require_once '../config/config.php';
require_once '../config/version.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/update_manager.php';

// Start session and check authentication
session_start();
$database = new Database();
$auth = new Auth($database);

// Check if user is admin
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $updateManager = new UpdateManager();
    $force_check = isset($_GET['force']) && $_GET['force'] === 'true';
    
    $result = $updateManager->checkForUpdates($force_check);
    
    // Add current version info
    $result['current_version_info'] = getCurrentVersionInfo();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking for updates: ' . $e->getMessage()
    ]);
}
?>