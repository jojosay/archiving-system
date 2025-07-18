<?php
// PDF Template Document Type Assignment - Clean Version
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
    $is_admin = $auth->isAdmin();
    
    // Get form data
    $template_id = $_POST['template_id'] ?? null;
    $document_type_id = $_POST['document_type_id'] ?? null;
    $set_as_default = isset($_POST['set_as_default']) && $_POST['set_as_default'] === 'on';
    
    // Validate input
    if (!$template_id || !is_numeric($template_id)) {
        sendJsonResponse(['success' => false, 'message' => 'Valid template ID is required'], 400);
    }
    
    if (!$document_type_id || !is_numeric($document_type_id)) {
        sendJsonResponse(['success' => false, 'message' => 'Valid document type is required'], 400);
    }
    
    // Check if template exists and user has permission
    try {
        $templateSql = "SELECT id, name, created_by FROM pdf_templates WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
        $templateStmt = $db->prepare($templateSql);
        $templateStmt->execute([$template_id]);
        $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            sendJsonResponse(['success' => false, 'message' => 'Template not found'], 404);
        }
        
        // Check permissions
        if (!$is_admin && $template['created_by'] != $user_id) {
            sendJsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error checking template'], 500);
    }
    
    // Check if document type exists
    try {
        $typeSql = "SELECT id, name FROM document_types WHERE id = ? AND is_active = 1";
        $typeStmt = $db->prepare($typeSql);
        $typeStmt->execute([$document_type_id]);
        $documentType = $typeStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$documentType) {
            sendJsonResponse(['success' => false, 'message' => 'Document type not found'], 404);
        }
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error checking document type'], 500);
    }
    
    // Update template assignment
    try {
        // Start transaction
        $db->beginTransaction();
        
        // If setting as default, clear existing defaults for this document type
        if ($set_as_default) {
            $clearDefaultSql = "UPDATE pdf_templates SET is_default = 0 WHERE document_type_id = ?";
            $clearDefaultStmt = $db->prepare($clearDefaultSql);
            $clearDefaultStmt->execute([$document_type_id]);
        }
        
        // Update the template
        $updateSql = "UPDATE pdf_templates SET 
                      document_type_id = ?, 
                      is_default = ?, 
                      updated_by = ?, 
                      updated_date = NOW() 
                      WHERE id = ?";
        
        $updateStmt = $db->prepare($updateSql);
        $result = $updateStmt->execute([
            $document_type_id,
            $set_as_default ? 1 : 0,
            $user_id,
            $template_id
        ]);
        
        if (!$result) {
            throw new Exception('Failed to update template');
        }
        
        // Commit transaction
        $db->commit();
        
        // Get updated template data
        $updatedSql = "SELECT pt.*, dt.name as document_type_name 
                       FROM pdf_templates pt 
                       LEFT JOIN document_types dt ON pt.document_type_id = dt.id 
                       WHERE pt.id = ?";
        $updatedStmt = $db->prepare($updatedSql);
        $updatedStmt->execute([$template_id]);
        $updatedTemplate = $updatedStmt->fetch(PDO::FETCH_ASSOC);
        
        // Success response
        sendJsonResponse([
            'success' => true,
            'message' => 'Document type assigned successfully',
            'template' => $updatedTemplate
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        sendJsonResponse(['success' => false, 'message' => 'Assignment failed: ' . $e->getMessage()], 500);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("PDF Template Assignment Error: " . $e->getMessage());
    
    sendJsonResponse(['success' => false, 'message' => 'Assignment failed'], 500);
}
?>