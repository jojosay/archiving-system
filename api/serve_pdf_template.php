<?php
// PDF Template File Serving - Clean Version
// Serves PDF template files for viewing in the template builder

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Include required files
    require_once '../config/config.php';
    require_once '../includes/database.php';
    require_once '../includes/auth.php';
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        http_response_code(500);
        exit('Database connection failed');
    }
    
    // Check authentication
    $auth = new Auth($database);
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        exit('Authentication required');
    }
    
    // Get template ID
    $template_id = $_GET['template_id'] ?? $_GET['id'] ?? null;
    
    if (!$template_id || !is_numeric($template_id)) {
        http_response_code(400);
        exit('Valid template ID is required');
    }
    
    // Get template file path
    $sql = "SELECT file_path, filename, created_by FROM pdf_templates WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        http_response_code(404);
        exit('Template not found');
    }
    
    // Check permissions
    $user_id = $auth->getCurrentUserId();
    $is_admin = $auth->isAdmin();
    
    if (!$is_admin && $template['created_by'] != $user_id) {
        http_response_code(403);
        exit('Permission denied');
    }
    
    // Build file path
    $file_path = '../' . $template['file_path'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('PDF file not found');
    }
    
    // Get file info
    $file_size = filesize($file_path);
    $filename = $template['filename'];
    
    // Set headers for PDF serving
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $file_size);
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=3600');
    header('Accept-Ranges: bytes');
    
    // Handle range requests for better PDF.js compatibility
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        $ranges = explode('=', $range);
        $offsets = explode('-', $ranges[1]);
        $offset = intval($offsets[0]);
        $length = intval($offsets[1]) - $offset + 1;
        
        if ($length > $file_size - $offset) {
            $length = $file_size - $offset;
        }
        
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $offset . '-' . ($offset + $length - 1) . '/' . $file_size);
        header('Content-Length: ' . $length);
        
        $file = fopen($file_path, 'rb');
        fseek($file, $offset);
        echo fread($file, $length);
        fclose($file);
    } else {
        // Serve entire file
        readfile($file_path);
    }
    
} catch (Exception $e) {
    error_log("PDF Template Serve Error: " . $e->getMessage());
    http_response_code(500);
    exit('Error serving PDF');
}
?>