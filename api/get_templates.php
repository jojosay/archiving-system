<?php
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $auth = new Auth($database);
    
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $db = $database->getConnection();
    
    // Get templates with document type information, excluding deleted ones
    $sql = "SELECT pt.*, dt.name as document_type_name, u.username as created_by_username
            FROM pdf_templates pt 
            LEFT JOIN document_types dt ON pt.document_type_id = dt.id 
            LEFT JOIN users u ON pt.created_by = u.id
            WHERE (pt.deleted = 0 OR pt.deleted IS NULL)
            ORDER BY pt.created_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'count' => count($templates)
    ]);
    
} catch (Exception $e) {
    error_log('Get templates error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>