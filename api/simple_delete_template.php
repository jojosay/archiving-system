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
    
    $input = json_decode(file_get_contents('php://input'), true);
    $template_id = $input['template_id'] ?? null;
    
    
    if (!$template_id) {
        echo json_encode(['success' => false, 'message' => 'Template ID required']);
        exit;
    }
    
    $db = $database->getConnection();
    
    // First check if template exists and get current state
    $checkStmt = $db->prepare("SELECT id, deleted FROM pdf_templates WHERE id = ?");
    $checkStmt->execute([$template_id]);
    $template = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        exit;
    }
    
    // Update template to mark as deleted
    $stmt = $db->prepare("UPDATE pdf_templates SET deleted = 1, updated_date = NOW() WHERE id = ?");
    $result = $stmt->execute([$template_id]);
    $rowCount = $stmt->rowCount();
    
    if ($result && $rowCount > 0) {
        echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete template or template not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>