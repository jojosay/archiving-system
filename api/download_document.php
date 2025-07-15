<?php
require_once '../includes/document_manager.php';
require_once '../includes/database.php';

$database = new Database();
$documentManager = new DocumentManager($database);

if (isset($_GET['id'])) {
    $document = $documentManager->getDocumentById($_GET['id']);
    if ($document && !empty($document['file_path'])) {
        $file_path = STORAGE_PATH . $document['file_path'];
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $document['mime_type']);
            header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}

http_response_code(404);
echo 'File not found.';
