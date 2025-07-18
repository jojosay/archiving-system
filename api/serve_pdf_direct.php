<?php
/**
 * Direct PDF Serving for Template Editor
 * Serves PDF files directly without authentication for editor use
 */

// Get file parameter
$file = $_GET['file'] ?? '';

if (empty($file)) {
    http_response_code(400);
    echo 'File parameter required';
    exit;
}

try {
    // Sanitize file path to prevent directory traversal
    $file = basename($file);
    
    // Check template directory only
    $template_path = __DIR__ . '/../storage/templates/pdf/' . $file;
    
    if (!file_exists($template_path) || !is_readable($template_path)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
    
    // Verify file is a PDF
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $template_path);
    finfo_close($finfo);
    
    if ($mime_type !== 'application/pdf') {
        http_response_code(400);
        echo 'Invalid file type';
        exit;
    }
    
    // Get file info
    $file_size = filesize($template_path);
    $file_name = pathinfo($file, PATHINFO_FILENAME) . '.pdf';
    
    // Set headers for PDF viewing
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $file_size);
    header('Content-Disposition: inline; filename="' . $file_name . '"');
    header('Cache-Control: public, max-age=3600');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: X-Requested-With');
    
    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Serve file
    readfile($template_path);
    
} catch (Exception $e) {
    error_log('PDF direct serving error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
}
?>