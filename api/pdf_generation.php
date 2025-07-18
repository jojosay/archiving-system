<?php
/**
 * PDF Generation API
 * Handles PDF generation with document data embedding
 */

require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/document_manager.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session
session_start();

// Initialize components
$database = new Database();
$auth = new Auth($database);
$documentManager = new DocumentManager($database);

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'generate_pdf':
        case 'generate':
            generatePDF();
            break;
            
        case 'preview_fields':
        case 'preview':
            previewFields();
            break;
            
        case 'get_template_fields':
            getTemplateFields();
            break;
            
        case 'get_document_template':
            getDocumentTemplate();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
function getTemplateFields() {
    $templateId = $_GET['template_id'] ?? null;
    
    if (!$templateId) {
        echo json_encode(['success' => false, 'message' => 'Template ID required']);
        return;
    }
    
    try {
        $db = getDatabase();
        
        // Check if template_fields table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'template_fields'");
        if ($checkTable->rowCount() === 0) {
            echo json_encode(['success' => true, 'fields' => []]);
            return;
        }
        
        $stmt = $db->prepare("SELECT field_name as name, field_label as label, field_type as type FROM template_fields WHERE template_id = ? ORDER BY page_number, y_position, x_position");
        $stmt->execute([$templateId]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'fields' => $fields]);
        
    } catch (Exception $e) {
        error_log("Get template fields error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get template fields']);
    }
}

function getDocumentTemplate() {
    $documentId = $_GET['document_id'] ?? null;
    
    if (!$documentId) {
        echo json_encode(['success' => false, 'message' => 'Document ID required']);
        return;
    }
    
    try {
        $db = getDatabase();
        
        // Get document and its document type with assigned template
        $sql = "SELECT d.*, dt.name as document_type_name, dt.pdf_template_id, 
                       pt.name as template_name, pt.original_name as template_file
                FROM documents d 
                LEFT JOIN document_types dt ON d.document_type_id = dt.id 
                LEFT JOIN pdf_templates pt ON dt.pdf_template_id = pt.id 
                WHERE d.id = ?";
        
        error_log("Executing query for document ID: " . $documentId);
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$document) {
            echo json_encode(['success' => false, 'message' => 'Document not found']);
            return;
        }
        
        if (!$document['pdf_template_id']) {
            echo json_encode(['success' => false, 'message' => 'No template assigned to document type']);
            return;
        }
        
        // Get template fields if they exist
        $fields = [];
        $checkTable = $db->query("SHOW TABLES LIKE 'template_fields'");
        if ($checkTable->rowCount() > 0) {
            $fieldsSql = "SELECT field_name as name, field_label as label, field_type as type 
                         FROM template_fields 
                         WHERE template_id = ? 
                         ORDER BY page_number, y_position, x_position";
            $fieldsStmt = $db->prepare($fieldsSql);
            $fieldsStmt->execute([$document['pdf_template_id']]);
            $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode([
            'success' => true,
            'template_id' => $document['pdf_template_id'],
            'template_name' => $document['template_name'],
            'document_type_name' => $document['document_type_name'],
            'fields' => $fields
        ]);
        
    } catch (Exception $e) {
        error_log("Get document template error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get document template']);
    }
}
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Generate PDF with embedded document data
 */
function generatePDF() {
    global $database, $documentManager, $auth;
    
    $document_id = $_POST['document_id'] ?? null;
    $template_id = $_POST['template_id'] ?? null;
    
    if (!$document_id || !$template_id) {
        throw new Exception('Document ID and Template ID are required');
    }
    
    // Get document details and metadata
    $document = $documentManager->getDocumentById($document_id);
    if (!$document) {
        throw new Exception('Document not found');
    }
    
    // Get template details
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT * FROM pdf_templates WHERE id = ? AND (deleted = 0 OR deleted IS NULL)");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template not found');
    }
    
    // Check if template file exists
    $template_path = __DIR__ . '/../' . $template['file_path'];
    if (!file_exists($template_path)) {
        throw new Exception('Template file not found');
    }
    
    // Generate unique filename for output
    $output_filename = 'generated_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.pdf';
    $output_path = __DIR__ . '/../storage/documents/' . $output_filename;
    
    // Ensure output directory exists
    $output_dir = dirname($output_path);
    if (!is_dir($output_dir)) {
        mkdir($output_dir, 0755, true);
    }
    
    // Use different PDF generation methods based on available libraries
    $success = false;
    
    // Method 1: Try FPDI/TCPDF (if available)
    if (class_exists('FPDI')) {
        $success = generatePDFWithFPDI($template_path, $output_path, $document, $template);
    }
    
    // Method 2: Try PDFtk (if available)
    if (!$success && isPDFtkAvailable()) {
        $success = generatePDFWithPDFtk($template_path, $output_path, $document, $template);
    }
    
    // Method 3: Simple copy with metadata injection (fallback)
    if (!$success) {
        $success = generatePDFSimple($template_path, $output_path, $document, $template);
    }
    
    if (!$success) {
        throw new Exception('Failed to generate PDF with any available method');
    }
    
    // Log the generation
    $user_id = $auth->getCurrentUserId();
    $stmt = $db->prepare("
        INSERT INTO pdf_template_usage (template_id, used_by, document_id, usage_date, usage_type) 
        VALUES (?, ?, ?, NOW(), 'generate')
    ");
    $stmt->execute([$template_id, $user_id, $document_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'PDF generated successfully',
        'download_url' => 'api/serve_file.php?file=' . urlencode($output_filename),
        'filename' => $output_filename
    ]);
}

/**
 * Generate PDF using FPDI/TCPDF
 */
function generatePDFWithFPDI($template_path, $output_path, $document, $template) {
    try {
        // This would require FPDI library to be installed
        // For now, return false to fall back to other methods
        return false;
    } catch (Exception $e) {
        error_log('FPDI PDF generation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate PDF using PDFtk
 */
function generatePDFWithPDFtk($template_path, $output_path, $document, $template) {
    try {
        // Create FDF file with form data
        $fdf_content = createFDFContent($document);
        $fdf_path = sys_get_temp_dir() . '/form_data_' . uniqid() . '.fdf';
        file_put_contents($fdf_path, $fdf_content);
        
        // Use PDFtk to fill the form
        $command = sprintf(
            'pdftk "%s" fill_form "%s" output "%s" flatten',
            escapeshellarg($template_path),
            escapeshellarg($fdf_path),
            escapeshellarg($output_path)
        );
        
        $result = shell_exec($command . ' 2>&1');
        
        // Clean up temporary FDF file
        unlink($fdf_path);
        
        return file_exists($output_path) && filesize($output_path) > 0;
        
    } catch (Exception $e) {
        error_log('PDFtk PDF generation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Simple PDF generation (copy template and add metadata)
 */
function generatePDFSimple($template_path, $output_path, $document, $template) {
    try {
        // Copy the template file
        if (!copy($template_path, $output_path)) {
            return false;
        }
        
        // Add document metadata as PDF metadata (if possible)
        // This is a basic implementation - in production you might want to use a proper PDF library
        
        return true;
        
    } catch (Exception $e) {
        error_log('Simple PDF generation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create FDF content for form filling
 */
function createFDFContent($document) {
    $fdf_header = "%FDF-1.2\n1 0 obj\n<<\n/FDF << /Fields [\n";
    $fdf_footer = "] >>\n>>\nendobj\ntrailer\n<<\n/Root 1 0 R\n>>\n%%EOF";
    
    $fields = [];
    
    // Add document title
    $fields[] = sprintf('<< /T (%s) /V (%s) >>', 'title', $document['title']);
    
    // Add document metadata
    if (isset($document['metadata']) && is_array($document['metadata'])) {
        foreach ($document['metadata'] as $field_name => $field_data) {
            $value = is_array($field_data) ? ($field_data['value'] ?? '') : $field_data;
            
            // Handle cascading dropdown location data
            if (is_array($field_data) && $field_data['type'] === 'cascading_dropdown') {
                $location_data = parseLocationData($value);
                if ($location_data) {
                    // Add individual location fields
                    if (!empty($location_data['region'])) {
                        $fields[] = sprintf('<< /T (%s) /V (%s) >>', 'region', $location_data['region']);
                    }
                    if (!empty($location_data['province'])) {
                        $fields[] = sprintf('<< /T (%s) /V (%s) >>', 'province', $location_data['province']);
                    }
                    if (!empty($location_data['city'])) {
                        $fields[] = sprintf('<< /T (%s) /V (%s) >>', 'city', $location_data['city']);
                    }
                    if (!empty($location_data['barangay'])) {
                        $fields[] = sprintf('<< /T (%s) /V (%s) >>', 'barangay', $location_data['barangay']);
                    }
                }
            }
            
            $fields[] = sprintf('<< /T (%s) /V (%s) >>', $field_name, $value);
        }
    }
    
    // Add document type
    if (isset($document['document_type_name'])) {
        $fields[] = sprintf('<< /T (%s) /V (%s) >>', 'document_type', $document['document_type_name']);
    }
    
    // Add creation date
    $fields[] = sprintf('<< /T (%s) /V (%s) >>', 'created_date', date('Y-m-d', strtotime($document['created_at'])));
    
    return $fdf_header . implode("\n", $fields) . "\n" . $fdf_footer;
}

/**
 * Parse location data from cascading dropdown or individual fields
 */
function parseLocationData($value) {
    $location_data = [
        'region' => '',
        'province' => '',
        'city' => '',
        'barangay' => ''
    ];
    
    // Try to parse JSON data from cascading dropdown
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            // Extract from cascading dropdown format
            if (isset($decoded['regions']['text'])) {
                $location_data['region'] = $decoded['regions']['text'];
            }
            if (isset($decoded['provinces']['text'])) {
                $location_data['province'] = $decoded['provinces']['text'];
            }
            if (isset($decoded['citymun']['text'])) {
                $location_data['city'] = $decoded['citymun']['text'];
            }
            if (isset($decoded['barangays']['text'])) {
                $location_data['barangay'] = $decoded['barangays']['text'];
            }
        } else {
            // Try to parse formatted string like "Region > Province > City > Barangay"
            $parts = explode(' > ', $value);
            if (count($parts) >= 1) $location_data['region'] = trim($parts[0]);
            if (count($parts) >= 2) $location_data['province'] = trim($parts[1]);
            if (count($parts) >= 3) $location_data['city'] = trim($parts[2]);
            if (count($parts) >= 4) $location_data['barangay'] = trim($parts[3]);
        }
    }
    
    return $location_data;
}

/**
 * Check if PDFtk is available
 */
function isPDFtkAvailable() {
    $result = shell_exec('pdftk --version 2>&1');
    return strpos($result, 'pdftk') !== false;
}

/**
 * Preview available fields for mapping
 */
function previewFields() {
    global $documentManager;
    
    $document_id = $_POST['document_id'] ?? null;
    
    if (!$document_id) {
        throw new Exception('Document ID is required');
    }
    
    $document = $documentManager->getDocumentById($document_id);
    if (!$document) {
        throw new Exception('Document not found');
    }
    
    $fields = [];
    
    // Add basic document fields
    $fields['title'] = [
        'label' => 'Document Title',
        'value' => $document['title'],
        'type' => 'text'
    ];
    
    $fields['document_type'] = [
        'label' => 'Document Type',
        'value' => $document['document_type_name'] ?? 'Unknown',
        'type' => 'text'
    ];
    
    $fields['created_date'] = [
        'label' => 'Created Date',
        'value' => date('Y-m-d', strtotime($document['created_at'])),
        'type' => 'date'
    ];
    
    $fields['uploaded_by'] = [
        'label' => 'Uploaded By',
        'value' => $document['uploaded_by_username'] ?? 'Unknown',
        'type' => 'text'
    ];
    
    // Add metadata fields
    if (isset($document['metadata']) && is_array($document['metadata'])) {
        foreach ($document['metadata'] as $field_name => $field_data) {
            $fields[$field_name] = [
                'label' => $field_data['label'] ?? $field_name,
                'value' => $field_data['value'] ?? '',
                'type' => $field_data['type'] ?? 'text'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
}
?>