<?php
// AJAX API for location CSV upload with progress tracking

// Start output buffering to catch any unexpected output
ob_start();

// Suppress error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Include required files
    if (!file_exists('../config/config.php')) {
        throw new Exception('Config file not found');
    }
    require_once '../config/config.php';
    
    if (!file_exists('../includes/database.php')) {
        throw new Exception('Database file not found');
    }
    require_once '../includes/database.php';
    
    if (!file_exists('../includes/csv_importer_simple.php')) {
        throw new Exception('CSV importer file not found');
    }
    require_once '../includes/csv_importer_simple.php';
    
    if (!file_exists('../includes/auth.php')) {
        throw new Exception('Auth file not found');
    }
    require_once '../includes/auth.php';

    // Increase execution time and memory limit for large uploads
    set_time_limit(300); // 5 minutes
    ini_set('memory_limit', '512M');

    // Start session only if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Initialize database first
    $database = new Database();
    $auth = new Auth($database);
    
    // Check authentication
    if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
        // Clear any output buffer and send JSON
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Clear any buffered output and set JSON header
    ob_clean();
    header('Content-Type: application/json');

    $importer = new SimpleCSVImporter($database);

    $action = $_POST['action'] ?? '';
    $upload_type = $_POST['upload_type'] ?? '';

    switch ($action) {
        case 'start_upload':
            // Handle file upload and return upload ID for progress tracking
            if (!isset($_FILES['csv_file'])) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                exit;
            }
            
            $file = $_FILES['csv_file'];
            $upload_id = uniqid('upload_', true);
            $upload_path = $importer->getUploadDir() . $upload_id . '_' . $upload_type . '.csv';
            
            // Create upload directory if it doesn't exist
            if (!file_exists($importer->getUploadDir())) {
                mkdir($importer->getUploadDir(), 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Store upload info in session for progress tracking
                $_SESSION['upload_progress'][$upload_id] = [
                    'file_path' => $upload_path,
                    'upload_type' => $upload_type,
                    'status' => 'uploaded',
                    'progress' => 0,
                    'total_rows' => 0,
                    'processed_rows' => 0,
                    'message' => 'File uploaded successfully'
                ];
                
                echo json_encode([
                    'success' => true,
                    'upload_id' => $upload_id,
                    'message' => 'File uploaded successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            }
            break;
            
        case 'process_upload':
            $upload_id = $_POST['upload_id'] ?? '';
            
            if (!isset($_SESSION['upload_progress'][$upload_id])) {
                echo json_encode(['success' => false, 'message' => 'Invalid upload ID']);
                exit;
            }
            
            $upload_info = $_SESSION['upload_progress'][$upload_id];
            $file_path = $upload_info['file_path'];
            $upload_type = $upload_info['upload_type'];
            
            // Update status to processing
            $_SESSION['upload_progress'][$upload_id]['status'] = 'processing';
            $_SESSION['upload_progress'][$upload_id]['message'] = 'Processing file...';
            
            // Process the file based on type
            switch ($upload_type) {
                case 'regions':
                    $result = $importer->importRegionsWithProgress($file_path, $upload_id);
                    break;
                case 'provinces':
                    $result = $importer->importProvincesWithProgress($file_path, $upload_id);
                    break;
                case 'citymun':
                    $result = $importer->importCityMunWithProgress($file_path, $upload_id);
                    break;
                case 'barangays':
                    // Add extra logging for barangays
                    error_log("Processing barangays upload: file_path=$file_path, upload_id=$upload_id");
                    $result = $importer->importBarangaysWithProgress($file_path, $upload_id);
                    error_log("Barangays import result: " . json_encode($result));
                    break;
                default:
                    $result = ['success' => false, 'message' => 'Invalid upload type: ' . $upload_type];
            }
            
            // Update final status
            $_SESSION['upload_progress'][$upload_id]['status'] = $result['success'] ? 'completed' : 'error';
            $_SESSION['upload_progress'][$upload_id]['message'] = $result['message'];
            $_SESSION['upload_progress'][$upload_id]['progress'] = 100;
            
            // Clean up file
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            echo json_encode($result);
            break;
            
        case 'get_progress':
            $upload_id = $_POST['upload_id'] ?? '';
            
            if (!isset($_SESSION['upload_progress'][$upload_id])) {
                echo json_encode(['success' => false, 'message' => 'Invalid upload ID']);
                exit;
            }
            
            $progress = $_SESSION['upload_progress'][$upload_id];
            echo json_encode([
                'success' => true,
                'progress' => $progress['progress'],
                'status' => $progress['status'],
                'message' => $progress['message'],
                'processed_rows' => $progress['processed_rows'],
                'total_rows' => $progress['total_rows']
            ]);
            break;
            
        case 'cleanup':
            $upload_id = $_POST['upload_id'] ?? '';
            
            if (isset($_SESSION['upload_progress'][$upload_id])) {
                unset($_SESSION['upload_progress'][$upload_id]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    // Clear any output buffer and send JSON error
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} catch (Error $e) {
    // Handle fatal errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $e->getMessage()]);
}
?>