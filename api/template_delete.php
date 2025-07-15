<?php
/**
 * Template Delete API
 * Handles template deletion with proper authorization
 */

// Start session and include required files
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/template_manager.php';
require_once '../includes/template_storage_manager.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize components
$database = new Database();
$auth = new Auth($database);
$templateManager = new TemplateManager($database);
$storageManager = new TemplateStorageManager();

// Check if user is authenticated and has admin role
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Template ID is required']);
        exit;
    }
    
    $template_id = intval($input['id']);
    
    if (!$template_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
        exit;
    }
    
    // Get template information before deletion
    $template = $templateManager->getTemplateById($template_id);
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        exit;
    }
    
    // Check if user has permission to delete this template
    // Admin can delete any template, or user can delete their own templates
    $user_id = $_SESSION['user_id'] ?? null;
    $is_admin = $auth->hasRole('admin');
    $is_owner = $template['created_by'] == $user_id;
    
    if (!$is_admin && !$is_owner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this template']);
        exit;
    }
    
    // Delete template from database (soft delete)
    $delete_result = $templateManager->deleteTemplate($template_id);
    
    if (!$delete_result['success']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $delete_result['message']]);
        exit;
    }
    
    // Optionally delete the physical file (uncomment if you want hard delete)
    /*
    $file_delete_result = $storageManager->deleteTemplateFile($template['file_path']);
    if (!$file_delete_result) {
        error_log("Failed to delete template file: " . $template['file_path']);
        // Don't fail the request if file deletion fails, as DB record is already marked deleted
    }
    */
    
    // Log the deletion
    error_log("Template deleted: ID={$template_id}, Name='{$template['name']}', DeletedBy={$user_id}");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Template deleted successfully',
        'template_id' => $template_id
    ]);
    
} catch (Exception $e) {
    error_log('Template deletion error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>