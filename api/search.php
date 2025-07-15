<?php
require_once '../config/config.php';
require_once '../includes/document_manager.php';
require_once '../includes/database.php';

$database = new Database();
$documentManager = new DocumentManager($database);

$search_query = $_GET['search'] ?? '';
$document_type_filter = $_GET['document_type'] ?? '';
$location_filter = $_GET['location'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$custom_fields = $_GET['custom_fields'] ?? [];

try {
    $documents = $documentManager->searchDocuments($search_query, $document_type_filter, $location_filter, $date_from, $date_to, $custom_fields);
    echo json_encode(['success' => true, 'documents' => $documents]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
