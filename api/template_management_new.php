<?php
/**
 * Template Management API - Clean Version
 * Handles template CRUD operations
 */

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
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        sendJsonResponse(['success' => true]);
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
    
    // Get action from various sources
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
    
    if (empty($action)) {
        sendJsonResponse(['success' => false, 'message' => 'Action is required'], 400);
    }
    
    // Route to appropriate function
    switch ($action) {
        case 'get_template':
            getTemplate($db, $auth);
            break;
            
        case 'get_templates':
            getTemplates($db, $auth);
            break;
            
        case 'save_template':
            saveTemplate($db, $auth);
            break;
            
        case 'update_template':
            updateTemplate($db, $auth);
            break;
            
        case 'delete_template':
            deleteTemplate($db, $auth);
            break;
            
        case 'duplicate_template':
            duplicateTemplate($db, $auth);
            break;
            
        case 'rename_template':
            renameTemplate($db, $auth);
            break;
            
        case 'list':
            listAllTemplates($db);
            break;
            
        case 'get_template_fields':
            getTemplateFields($db);
            break;
            
        case 'save_template_fields':
            saveTemplateFields($db, $auth);
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Template Management API Error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'An error occurred'], 500);
}

/**
 * List all templates for dropdown selection
 */
function listAllTemplates($db) {
    try {
        $sql = "SELECT t.*, dt.name as document_type_name 
                FROM pdf_templates t 
                LEFT JOIN document_types dt ON t.document_type_id = dt.id 
                ORDER BY t.created_date DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse([
            'success' => true,
            'templates' => $templates
        ]);
        
    } catch (Exception $e) {
        error_log("List templates error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Failed to list templates'], 500);
    }
}

/**
 * Get template fields
 */
function getTemplateFields($db) {
    try {
        $templateId = $_GET['template_id'] ?? null;
        
        if (!$templateId) {
            sendJsonResponse(['success' => false, 'message' => 'Template ID required'], 400);
            return;
        }
        
        // Check if template_fields table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'template_fields'");
        if ($checkTable->rowCount() === 0) {
            sendJsonResponse(['success' => true, 'fields' => []]);
            return;
        }
        
        $sql = "SELECT * FROM template_fields WHERE template_id = ? ORDER BY page_number, y_position, x_position";
        $stmt = $db->prepare($sql);
        $stmt->execute([$templateId]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse(['success' => true, 'fields' => $fields]);
        
    } catch (Exception $e) {
        error_log("Get template fields error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Failed to get template fields'], 500);
    }
}

/**
 * Save template fields
 */
function saveTemplateFields($db, $auth) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $templateId = $input['template_id'] ?? null;
        $fields = $input['fields'] ?? [];
        
        if (!$templateId) {
            sendJsonResponse(['success' => false, 'message' => 'Template ID required'], 400);
            return;
        }
        
        // Create template_fields table if it doesn't exist
        $createTable = "
            CREATE TABLE IF NOT EXISTS template_fields (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_id INT NOT NULL,
                field_name VARCHAR(255) NOT NULL,
                field_type VARCHAR(50) NOT NULL,
                field_label VARCHAR(255),
                x_position DECIMAL(10,2) NOT NULL,
                y_position DECIMAL(10,2) NOT NULL,
                width DECIMAL(10,2) NOT NULL,
                height DECIMAL(10,2) NOT NULL,
                page_number INT NOT NULL DEFAULT 1,
                font_size INT DEFAULT 12,
                font_family VARCHAR(100) DEFAULT 'Arial',
                font_color VARCHAR(7) DEFAULT '#000000',
                is_required BOOLEAN DEFAULT FALSE,
                default_value TEXT,
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_template_id (template_id),
                INDEX idx_page_number (page_number)
            )
        ";
        $db->exec($createTable);
        
        // Start transaction
        $db->beginTransaction();
        
        // Delete existing fields for this template
        $deleteStmt = $db->prepare("DELETE FROM template_fields WHERE template_id = ?");
        $deleteStmt->execute([$templateId]);
        
        // Insert new fields
        if (!empty($fields)) {
            $insertSql = "INSERT INTO template_fields 
                         (template_id, field_name, field_type, field_label, x_position, y_position, 
                          width, height, page_number, font_size, font_family, font_color, is_required, default_value) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertSql);
            
            foreach ($fields as $field) {
                $insertStmt->execute([
                    $templateId,
                    $field['field_name'] ?? '',
                    $field['field_type'] ?? 'text',
                    $field['field_label'] ?? $field['field_name'] ?? '',
                    $field['x_position'] ?? 0,
                    $field['y_position'] ?? 0,
                    $field['width'] ?? 100,
                    $field['height'] ?? 25,
                    $field['page_number'] ?? 1,
                    $field['font_size'] ?? 12,
                    $field['font_family'] ?? 'Arial',
                    $field['font_color'] ?? '#000000',
                    $field['is_required'] ?? false,
                    $field['default_value'] ?? ''
                ]);
            }
        }
        
        $db->commit();
        
        sendJsonResponse(['success' => true, 'message' => 'Template fields saved successfully']);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Save template fields error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Failed to save template fields'], 500);
    }
}

/**
 * Get a single template by ID
 */
function getTemplate($db, $auth) {
    // Get template ID from various sources
    $input = json_decode(file_get_contents('php://input'), true);
    $template_id = $input['id'] ?? $_POST['id'] ?? $_GET['id'] ?? null;
    
    if (!$template_id || !is_numeric($template_id)) {
        sendJsonResponse(['success' => false, 'message' => 'Valid template ID is required'], 400);
    }
    
    try {
        // Get template with document type information
        $sql = "SELECT pt.*, dt.name as document_type_name 
                FROM pdf_templates pt 
                LEFT JOIN document_types dt ON pt.document_type_id = dt.id 
                WHERE pt.id = ? AND (pt.deleted = 0 OR pt.deleted IS NULL)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            sendJsonResponse(['success' => false, 'message' => 'Template not found'], 404);
        }
        
        // Check permissions (users can only view their own templates unless admin)
        $user_id = $auth->getCurrentUserId();
        $is_admin = $auth->isAdmin();
        
        if (!$is_admin && $template['created_by'] != $user_id) {
            sendJsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        // Add correct file path for PDF loading in browser
        if (!empty($template['file_path'])) {
            // Use the dedicated PDF serving endpoint for better compatibility
            $template['web_file_path'] = "api/serve_pdf_template.php?template_id=" . $template_id;
            
            // Also provide the direct path as fallback
            $clean_path = str_replace('../', '', $template['file_path']);
            $template['direct_file_path'] = $clean_path;
            
            // For debugging - log the paths
            error_log("Template ID: " . $template_id);
            error_log("Original file_path: " . $template['file_path']);
            error_log("Web file_path: " . $template['web_file_path']);
            error_log("Direct file_path: " . $template['direct_file_path']);
        }
        
        sendJsonResponse([
            'success' => true,
            'template' => $template
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error loading template: ' . $e->getMessage()], 500);
    }
}

/**
 * Get all templates for the current user
 */
function getTemplates($db, $auth) {
    try {
        $user_id = $auth->getCurrentUserId();
        $is_admin = $auth->isAdmin();
        
        // Build query based on user permissions
        if ($is_admin) {
            $sql = "SELECT pt.*, dt.name as document_type_name, u.username as created_by_username
                    FROM pdf_templates pt 
                    LEFT JOIN document_types dt ON pt.document_type_id = dt.id 
                    LEFT JOIN users u ON pt.created_by = u.id
                    WHERE (pt.deleted = 0 OR pt.deleted IS NULL)
                    ORDER BY pt.created_date DESC";
            $params = [];
        } else {
            $sql = "SELECT pt.*, dt.name as document_type_name, u.username as created_by_username
                    FROM pdf_templates pt 
                    LEFT JOIN document_types dt ON pt.document_type_id = dt.id 
                    LEFT JOIN users u ON pt.created_by = u.id
                    WHERE pt.created_by = ? AND (pt.deleted = 0 OR pt.deleted IS NULL)
                    ORDER BY pt.created_date DESC";
            $params = [$user_id];
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse([
            'success' => true,
            'templates' => $templates
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error loading templates: ' . $e->getMessage()], 500);
    }
}

/**
 * Duplicate a template
 */
function duplicateTemplate($db, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    $template_id = $input['template_id'] ?? $_POST['template_id'] ?? null;
    
    if (!$template_id || !is_numeric($template_id)) {
        sendJsonResponse(['success' => false, 'message' => 'Valid template ID is required'], 400);
    }
    
    try {
        // Get original template
        $sql = "SELECT * FROM pdf_templates WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            sendJsonResponse(['success' => false, 'message' => 'Template not found'], 404);
        }
        
        // Check permissions
        $user_id = $auth->getCurrentUserId();
        $is_admin = $auth->isAdmin();
        
        if (!$is_admin && $template['created_by'] != $user_id) {
            sendJsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        // Copy the file
        $original_file = '../' . $template['file_path'];
        if (!file_exists($original_file)) {
            sendJsonResponse(['success' => false, 'message' => 'Original template file not found'], 404);
        }
        
        // Generate new filename
        $new_filename = 'template_' . uniqid() . '_' . time() . '.pdf';
        $new_file_path = '../storage/templates/pdf/' . $new_filename;
        
        if (!copy($original_file, $new_file_path)) {
            sendJsonResponse(['success' => false, 'message' => 'Failed to copy template file'], 500);
        }
        
        // Insert new template record
        $sql = "INSERT INTO pdf_templates (
            name, original_name, filename, file_path, file_size, 
            pages, description, created_by, created_date, updated_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $template['name'] . ' (Copy)',
            $template['original_name'],
            $new_filename,
            'storage/templates/pdf/' . $new_filename,
            $template['file_size'],
            $template['pages'],
            $template['description'],
            $user_id
        ]);
        
        if (!$result) {
            // Clean up file if database insert failed
            unlink($new_file_path);
            sendJsonResponse(['success' => false, 'message' => 'Failed to create template record'], 500);
        }
        
        $new_template_id = $db->lastInsertId();
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Template duplicated successfully',
            'template_id' => $new_template_id
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error duplicating template: ' . $e->getMessage()], 500);
    }
}

/**
 * Rename a template
 */
function renameTemplate($db, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    $template_id = $input['template_id'] ?? $_POST['template_id'] ?? null;
    $new_name = $input['new_name'] ?? $_POST['new_name'] ?? null;
    
    if (!$template_id || !is_numeric($template_id)) {
        sendJsonResponse(['success' => false, 'message' => 'Valid template ID is required'], 400);
    }
    
    if (empty($new_name)) {
        sendJsonResponse(['success' => false, 'message' => 'New name is required'], 400);
    }
    
    try {
        // Check if template exists and user has permission
        $sql = "SELECT * FROM pdf_templates WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            sendJsonResponse(['success' => false, 'message' => 'Template not found'], 404);
        }
        
        // Check permissions
        $user_id = $auth->getCurrentUserId();
        $is_admin = $auth->isAdmin();
        
        if (!$is_admin && $template['created_by'] != $user_id) {
            sendJsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        // Update template name
        $sql = "UPDATE pdf_templates SET name = ?, updated_by = ?, updated_date = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([trim($new_name), $user_id, $template_id]);
        
        if (!$result) {
            sendJsonResponse(['success' => false, 'message' => 'Failed to update template name'], 500);
        }
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Template renamed successfully'
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error renaming template: ' . $e->getMessage()], 500);
    }
}

/**
 * Delete a template
 */
function deleteTemplate($db, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    $template_id = $input['template_id'] ?? $_POST['template_id'] ?? null;
    
    if (!$template_id || !is_numeric($template_id)) {
        sendJsonResponse(['success' => false, 'message' => 'Valid template ID is required'], 400);
    }
    
    try {
        // Check if template exists
        $sql = "SELECT * FROM pdf_templates WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            sendJsonResponse(['success' => false, 'message' => 'Template not found'], 404);
        }
        
        // Check permissions
        $user_id = $auth->getCurrentUserId();
        $is_admin = $auth->isAdmin();
        
        if (!$is_admin && $template['created_by'] != $user_id) {
            sendJsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        // Soft delete
        $sql = "UPDATE pdf_templates SET deleted = 1, updated_by = ?, updated_date = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$user_id, $template_id]);
        
        if (!$result) {
            sendJsonResponse(['success' => false, 'message' => 'Failed to delete template'], 500);
        }
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error deleting template: ' . $e->getMessage()], 500);
    }
}

// Placeholder functions for other operations
function saveTemplate($db, $auth) {
    sendJsonResponse(['success' => false, 'message' => 'Save template not implemented yet'], 501);
}

function updateTemplate($db, $auth) {
    sendJsonResponse(['success' => false, 'message' => 'Update template not implemented yet'], 501);
}
?>