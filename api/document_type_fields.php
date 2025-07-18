<?php
/**
 * Document Type Fields API
 * Handles document type field requirements and validation
 */

// Start session first
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Initialize database and auth
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($database);

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_types':
            getDocumentTypes($db);
            break;
            
        case 'get_type_fields':
            getDocumentTypeFields($db);
            break;
            
        case 'save_field_requirements':
            saveFieldRequirements($db);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Get all document types
 */
function getDocumentTypes($db) {
    try {
        // Check if document_types table exists
        $stmt = $db->prepare("SHOW TABLES LIKE 'document_types'");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Document types table does not exist',
                'document_types' => []
            ]);
            return;
        }
        
        // Get active document types
        $sql = "SELECT id, name, description FROM document_types WHERE is_active = 1 ORDER BY name";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $document_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'document_types' => $document_types,
            'count' => count($document_types)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching document types: ' . $e->getMessage(),
            'document_types' => []
        ]);
    }
}

/**
 * Get field requirements for a specific document type
 */
function getDocumentTypeFields($db) {
    $document_type_id = $_GET['document_type_id'] ?? null;
    
    if (!$document_type_id) {
        throw new Exception('Document type ID is required');
    }
    
    // Check if document_type_fields table exists
    $tableExists = checkTableExists($db, 'document_type_fields');
    
    if (!$tableExists) {
        // Return default fields structure if table doesn't exist yet
        echo json_encode([
            'success' => true,
            'fields' => [],
            'message' => 'Document type fields table not yet created'
        ]);
        return;
    }
    
    $sql = "SELECT * FROM document_type_fields 
            WHERE document_type_id = ? 
            ORDER BY field_order, created_at";
    $stmt = $db->prepare($sql);
    $stmt->execute([$document_type_id]);
    
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
}

/**
 * Save field requirements for a document type
 */
function saveFieldRequirements($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $document_type_id = $input['document_type_id'] ?? null;
    $fields = $input['fields'] ?? [];
    
    if (!$document_type_id) {
        throw new Exception('Document type ID is required');
    }
    
    if (!is_array($fields)) {
        throw new Exception('Fields must be an array');
    }
    
    // Check if document_type_fields table exists, create if not
    ensureDocumentTypeFieldsTable($db);
    
    $db->beginTransaction();
    
    try {
        // Delete existing fields for this document type
        $deleteSql = "DELETE FROM document_type_fields WHERE document_type_id = ?";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->execute([$document_type_id]);
        
        // Insert new fields
        $insertSql = "INSERT INTO document_type_fields 
                      (document_type_id, field_name, field_label, field_type, field_options, is_required, field_order) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertSql);
        
        foreach ($fields as $index => $field) {
            $insertStmt->execute([
                $document_type_id,
                $field['field_name'] ?? '',
                $field['field_label'] ?? $field['field_name'] ?? '',
                $field['field_type'] ?? 'text',
                $field['field_options'] ?? null,
                $field['is_required'] ?? 0,
                $index + 1
            ]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Field requirements saved successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Check if a table exists in the database
 */
function checkTableExists($db, $tableName) {
    $sql = "SHOW TABLES LIKE ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$tableName]);
    return $stmt->rowCount() > 0;
}

/**
 * Ensure the document_type_fields table exists
 */
function ensureDocumentTypeFieldsTable($db) {
    if (!checkTableExists($db, 'document_type_fields')) {
        $createSql = "CREATE TABLE document_type_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_type_id INT NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            field_label VARCHAR(100) NOT NULL,
            field_type ENUM('text', 'number', 'date', 'time', 'dropdown', 'textarea', 'cascading_dropdown', 'reference', 'file') NOT NULL,
            field_options TEXT,
            is_required BOOLEAN DEFAULT FALSE,
            field_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE CASCADE
        )";
        
        $db->exec($createSql);
    }
}
?>