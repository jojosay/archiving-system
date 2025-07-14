<?php
// Secure file serving endpoint
session_start();

// Check if user is authenticated
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$auth = new Auth($database);
if (!$auth->isLoggedIn()) {
    http_response_code(403);
    exit('Access denied');
}

// Get file path from query parameter or backup filename
$file_path = $_GET['file'] ?? '';
$backup_filename = $_GET['backup'] ?? '';

if (empty($file_path) && empty($backup_filename)) {
    http_response_code(400);
    exit('File path or backup filename required');
}

// Handle backup file requests
if (!empty($backup_filename)) {
    // Serve backup file
    $backup_path = realpath('../backups/' . $backup_filename);
    
    if (!$backup_path || !file_exists($backup_path)) {
        http_response_code(404);
        exit('Backup file not found');
    }
    
    // Security: Ensure backup file is within backups directory
    $backups_dir = realpath('../backups/');
    if (strpos($backup_path, $backups_dir) !== 0) {
        http_response_code(403);
        exit('Access denied');
    }
    
    $real_file_path = $backup_path;
} else {
    // Handle regular file requests
    // Security: Ensure file path is within allowed directories
    $allowed_paths = [
        realpath('../storage/documents/'),
        realpath('../storage/book_images/')
    ];

    $real_file_path = realpath('../' . $file_path);
    
    if (!$real_file_path) {
        http_response_code(404);
        exit('File not found');
    }

    $is_allowed = false;
    foreach ($allowed_paths as $allowed_path) {
        if (strpos($real_file_path, $allowed_path) === 0) {
            $is_allowed = true;
            break;
        }
    }

    if (!$is_allowed) {
        http_response_code(403);
        exit('Access denied');
    }
}

// Check if file exists
if (!file_exists($real_file_path)) {
    http_response_code(404);
    exit('File not found');
}

// Get file info
$file_info = pathinfo($real_file_path);
$mime_type = mime_content_type($real_file_path);

// Set appropriate headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($real_file_path));
header('Content-Disposition: inline; filename="' . basename($real_file_path) . '"');

// Output file
readfile($real_file_path);
exit;
?>