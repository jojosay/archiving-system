<?php
/**
 * Secure PDF Serving API
 * Serves PDF files with proper authentication and security headers
 */

session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Initialize authentication
$database = new Database();
$auth = new Auth($database);

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get file parameter
$file = $_GET['file'] ?? '';
$document_id = $_GET['document_id'] ?? '';

if (empty($file)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File parameter required']);
    exit;
}

try {
    // Sanitize file path to prevent directory traversal
    $file = basename($file);
    
    // Check if it's a template file or document file
    $template_path = __DIR__ . '/../storage/templates/pdf/' . $file;
    $document_path = __DIR__ . '/../storage/documents/' . $file;
    
    if (file_exists($template_path)) {
        $file_path = $template_path;
    } elseif (file_exists($document_path)) {
        $file_path = $document_path;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File not found']);
        exit;
    }
    
    // File existence already checked above
    if (!is_readable($file_path)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File not readable']);
        exit;
    }
    
    // Verify file is a PDF
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    
    if ($mime_type !== 'application/pdf') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
    
    // Optional: Verify user has access to this document
    if (!empty($document_id)) {
        $conn = $database->getConnection();
        $stmt = $conn->prepare("SELECT id FROM documents WHERE id = ? AND (uploaded_by = ? OR ? = 1)");
        $is_admin = $auth->hasRole('admin') ? 1 : 0;
        $stmt->execute([$document_id, $_SESSION['user_id'], $is_admin]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }
    
    // Get file info
    $file_size = filesize($file_path);
    $file_name = pathinfo($file, PATHINFO_FILENAME) . '.pdf';
    
    // Set security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    
    // Set PDF headers
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $file_size);
    header('Content-Disposition: inline; filename="' . $file_name . '"');
    header('Cache-Control: private, max-age=3600');
    header('Pragma: private');
    
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Serve file in chunks for better memory usage
    $handle = fopen($file_path, 'rb');
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error reading file']);
        exit;
    }
    
    while (!feof($handle)) {
        echo fread($handle, 8192); // 8KB chunks
        flush();
    }
    
    fclose($handle);
    
} catch (Exception $e) {
    error_log('PDF serving error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error']);
}
?>