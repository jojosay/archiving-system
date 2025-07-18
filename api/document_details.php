<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/document_manager.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    // Check if document ID is provided
    if (!isset($_GET['id'])) {
        $response['message'] = 'Document ID is required.';
        echo json_encode($response);
        exit;
    }

    $documentId = intval($_GET['id']);
    
    // Validate document ID
    if ($documentId <= 0) {
        $response['message'] = 'Invalid document ID.';
        echo json_encode($response);
        exit;
    }

    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        $response['message'] = 'Database connection failed.';
        echo json_encode($response);
        exit;
    }

    // Initialize document manager
    $documentManager = new DocumentManager($database);
    
    // Fetch document details
    $document = $documentManager->getDocumentById($documentId);
    
    if ($document) {
        $response['success'] = true;
        $response['document'] = $document;
        $response['message'] = 'Document details fetched successfully.';
    } else {
        $response['message'] = 'Document not found or access denied.';
    }

} catch (Exception $e) {
    error_log("Document details API error: " . $e->getMessage());
    $response['message'] = 'Error fetching document details: ' . $e->getMessage();
    $response['debug'] = [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

echo json_encode($response);
?>