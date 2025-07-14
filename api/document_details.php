<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/document_manager.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if (isset($_GET['id'])) {
    $documentId = intval($_GET['id']);
    $database = new Database();
    $documentManager = new DocumentManager($database);
    
    try {
        $document = $documentManager->getDocumentById($documentId);
        if ($document) {
            $response['success'] = true;
            $response['document'] = $document;
            $response['message'] = 'Document details fetched successfully.';
        } else {
            $response['message'] = 'Document not found.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error fetching document details: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Document ID is required.';
}

echo json_encode($response);
?>