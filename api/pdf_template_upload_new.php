<?php
// PDF Template Upload - Clean Version
// Completely disable any HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);

// Start output buffering to catch any unwanted output
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to send JSON response and exit
function sendJsonResponse($data, $httpCode = 200) {
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($data);
    
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

try {
    // Include required files
    require_once '../config/config.php';
    require_once '../includes/database.php';
    require_once '../includes/auth.php';
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendJsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }
    
    // Check authentication
    $auth = new Auth($database);
    if (!$auth->isLoggedIn()) {
        sendJsonResponse(['success' => false, 'message' => 'Authentication required'], 401);
    }
    
    $user_id = $auth->getCurrentUserId();
    
    
    // Validate file upload
    if (!isset($_FILES['template_file'])) {
        sendJsonResponse(['success' => false, 'message' => 'No PDF file uploaded'], 400);
    }
    
    $file = $_FILES['template_file'];
    
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        $message = $errorMessages[$file['error']] ?? 'Upload error';
        sendJsonResponse(['success' => false, 'message' => $message], 400);
    }
    
    // Validate file type
    $allowedTypes = ['application/pdf'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file['type'], $allowedTypes) || $fileExtension !== 'pdf') {
        sendJsonResponse(['success' => false, 'message' => 'Only PDF files are allowed'], 400);
    }
    
    // Validate file size (10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        sendJsonResponse(['success' => false, 'message' => 'File too large. Maximum 10MB allowed'], 400);
    }
    
    // Verify PDF header
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle) {
        $header = fread($handle, 4);
        fclose($handle);
        
        if ($header !== '%PDF') {
            sendJsonResponse(['success' => false, 'message' => 'Invalid PDF file'], 400);
        }
    }
    
    // Generate unique filename
    $uniqueFilename = 'template_' . uniqid() . '_' . time() . '.pdf';
    
    // Create upload directory
    $uploadDir = '../storage/templates/pdf/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            sendJsonResponse(['success' => false, 'message' => 'Failed to create upload directory'], 500);
        }
    }
    
    $filePath = $uploadDir . $uniqueFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        sendJsonResponse(['success' => false, 'message' => 'Failed to save file'], 500);
    }
    
    // Get template details
    $templateName = !empty($_POST['template_name']) ? trim($_POST['template_name']) : pathinfo($file['name'], PATHINFO_FILENAME);
    $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
    $documentTypeId = !empty($_POST['document_type_id']) ? intval($_POST['document_type_id']) : null;
    $setAsDefault = isset($_POST['set_as_default']) && $_POST['set_as_default'] === 'on';
    
    // Simple page count (default to 1)
    $pages = 1;
    try {
        $content = file_get_contents($filePath);
        $pageMatches = preg_match_all('/\/Type\s*\/Page[^s]/', $content);
        if ($pageMatches > 0) {
            $pages = $pageMatches;
        }
    } catch (Exception $e) {
        // Use default page count
    }
    
    // Insert into database
    try {
        // Disable foreign key checks temporarily
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $sql = "INSERT INTO pdf_templates (
            name, original_name, filename, file_path, file_size, 
            pages, description, created_by, created_date, updated_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $templateName,
            $file['name'],
            $uniqueFilename,
            'storage/templates/pdf/' . $uniqueFilename,
            $file['size'],
            $pages,
            $description,
            $user_id
        ]);
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        if (!$result) {
            throw new Exception('Database insert failed');
        }
        
        $templateId = $db->lastInsertId();
        
        // Handle document type assignment and default setting
        if ($documentTypeId) {
            try {
                // If set as default, first remove default flag from other templates for this document type
                if ($setAsDefault) {
                    $updateStmt = $db->prepare("UPDATE pdf_templates SET is_default = 0 WHERE document_type_id = ?");
                    $updateStmt->execute([$documentTypeId]);
                }
                
                // Update the template with document type and default flag
                $assignStmt = $db->prepare("UPDATE pdf_templates SET document_type_id = ?, is_default = ? WHERE id = ?");
                $assignStmt->execute([
                    $documentTypeId,
                    $setAsDefault ? 1 : 0,
                    $templateId
                ]);
                
            } catch (Exception $e) {
                error_log("Document type assignment error: " . $e->getMessage());
                // Don't fail the upload, just log the error
            }
        }
        
        // Success response
        sendJsonResponse([
            'success' => true,
            'message' => 'PDF template uploaded successfully' . ($documentTypeId ? ' and assigned to document type' : ''),
            'template_id' => $templateId,
            'template' => [
                'id' => $templateId,
                'name' => $templateName,
                'filename' => $uniqueFilename,
                'pages' => $pages,
                'file_size' => $file['size'],
                'document_type_id' => $documentTypeId,
                'is_default' => $setAsDefault
            ]
        ]);
        
    } catch (Exception $e) {
        // Re-enable foreign key checks
        try {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $e2) {
            // Ignore
        }
        
        // Clean up file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        sendJsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("PDF Upload Error: " . $e->getMessage());
    
    // Clean up file if it exists
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    
    sendJsonResponse(['success' => false, 'message' => 'Upload failed'], 500);
}
?>