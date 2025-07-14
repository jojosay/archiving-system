<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/document_manager.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $documentId = intval($_POST['id']);
    $database = new Database();
    $documentManager = new DocumentManager($database);
    
    try {
        $result = $documentManager->deleteDocument($documentId);
        if ($result['success']) {
            $response['success'] = true;
            $response['message'] = 'Document deleted successfully.';
        } else {
            $response['message'] = $result['message'];
        }
    } catch (Exception $e) {
        $response['message'] = 'Error deleting document: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Document ID is required and request method must be POST.';
}

echo json_encode($response);
?>