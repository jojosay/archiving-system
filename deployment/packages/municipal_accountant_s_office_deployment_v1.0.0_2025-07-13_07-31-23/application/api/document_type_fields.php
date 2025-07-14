<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/document_type_manager.php';

try {
    $database = new Database();
    $docTypeManager = new DocumentTypeManager($database);
    
    // Get type_id from query parameter
    $type_id = $_GET['type_id'] ?? null;
    
    if (!$type_id) {
        throw new Exception('Document type ID is required');
    }
    
    // Get fields for the document type
    $fields = $docTypeManager->getDocumentTypeFields($type_id);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'fields' => $fields,
        'count' => count($fields)
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>