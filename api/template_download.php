<?php
/**
 * Template Download API
 * Handles secure template file downloads with tracking
 */

// Start session and include required files
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/template_manager.php';
require_once '../includes/template_storage_manager.php';

// Initialize components
$database = new Database();
$auth = new Auth($database);
$templateManager = new TemplateManager($database);
$storageManager = new TemplateStorageManager();

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get template ID
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$template_id) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Template ID is required']);
    exit;
}

try {
    // Get template information
    $template = $templateManager->getTemplateById($template_id);
    
    if (!$template) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        exit;
    }
    
    // Check if template is active
    if (!$template['is_active']) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Template is not available']);
        exit;
    }
    
    // Get file information
    $file_info = $storageManager->getFileForDownload($template['file_path']);
    
    if (!$file_info) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Template file not found']);
        exit;
    }
    
    // Track download
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $templateManager->incrementDownloadCount($template_id, $user_id, $ip_address, $user_agent);
    
    // Prepare file for download
    $file_path = $file_info['path'];
    $file_size = $file_info['size'];
    $mime_type = $file_info['mime_type'];
    
    // Generate safe filename for download
    $safe_filename = generateSafeFilename($template['name'], $template['file_type']);
    
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for file download
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Prevent script timeout for large files
    set_time_limit(0);
    
    // Read and output file in chunks
    $handle = fopen($file_path, 'rb');
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error reading file']);
        exit;
    }
    
    while (!feof($handle)) {
        $chunk = fread($handle, 8192); // 8KB chunks
        echo $chunk;
        flush();
    }
    
    fclose($handle);
    exit;
    
} catch (Exception $e) {
    error_log('Template download error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit;
}

/**
 * Generate safe filename for download
 */
function generateSafeFilename($template_name, $file_type) {
    // Remove or replace unsafe characters
    $safe_name = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $template_name);
    
    // Replace multiple spaces with single space
    $safe_name = preg_replace('/\s+/', ' ', $safe_name);
    
    // Trim and replace spaces with underscores
    $safe_name = str_replace(' ', '_', trim($safe_name));
    
    // Ensure we have a name
    if (empty($safe_name)) {
        $safe_name = 'template';
    }
    
    // Add file extension
    return $safe_name . '.' . $file_type;
}
?>