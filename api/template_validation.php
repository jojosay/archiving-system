<?php
/**
 * Template Validation API
 * Handles template field validation and completeness checking
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'validate_fields':
            validateTemplateFields($db, $input);
            break;
            
        case 'analyze_template':
            analyzeTemplate($db, $input);
            break;
            
        case 'suggest_document_type':
            suggestDocumentType($db, $input);
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
 * Validate template fields against document type requirements
 */
function validateTemplateFields($db, $input) {
    $document_type_id = $input['document_type_id'] ?? null;
    $template_fields = $input['template_fields'] ?? [];
    
    if (!$document_type_id) {
        throw new Exception('Document type ID is required');
    }
    
    if (!is_array($template_fields)) {
        throw new Exception('Template fields must be an array');
    }
    
    // Get field requirements for this document type
    $requirements = getFieldRequirements($db, $document_type_id);
    
    // Analyze field mapping
    $validation = analyzeFieldMapping($requirements, $template_fields);
    
    echo json_encode([
        'success' => true,
        'validation' => $validation
    ]);
}

/**
 * Get field requirements for a document type
 */
function getFieldRequirements($db, $document_type_id) {
    // Check if template_field_requirements table exists
    $tableExists = checkTableExists($db, 'template_field_requirements');
    
    if (!$tableExists) {
        // Return default requirements based on common document types
        return getDefaultFieldRequirements($document_type_id);
    }
    
    $sql = "SELECT * FROM template_field_requirements 
            WHERE document_type_id = ? 
            ORDER BY display_order, field_name";
    $stmt = $db->prepare($sql);
    $stmt->execute([$document_type_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get default field requirements when table doesn't exist
 */
function getDefaultFieldRequirements($document_type_id) {
    // Get document type name to determine default fields
    global $db;
    $sql = "SELECT name FROM document_types WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$document_type_id]);
    $docType = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$docType) {
        return [];
    }
    
    $typeName = strtolower($docType['name']);
    
    // Define common field patterns for different document types
    $defaultFields = [];
    
    if (strpos($typeName, 'birth') !== false) {
        $defaultFields = [
            ['field_name' => 'full_name', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'birth_date', 'field_type' => 'date', 'is_required' => 1],
            ['field_name' => 'birth_place', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'father_name', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'mother_name', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'registration_number', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'gender', 'field_type' => 'select', 'is_required' => 0],
            ['field_name' => 'nationality', 'field_type' => 'text', 'is_required' => 0]
        ];
    } elseif (strpos($typeName, 'marriage') !== false) {
        $defaultFields = [
            ['field_name' => 'groom_name', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'bride_name', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'marriage_date', 'field_type' => 'date', 'is_required' => 1],
            ['field_name' => 'marriage_place', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'registration_number', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'witness1_name', 'field_type' => 'text', 'is_required' => 0],
            ['field_name' => 'witness2_name', 'field_type' => 'text', 'is_required' => 0]
        ];
    } elseif (strpos($typeName, 'death') !== false) {
        $defaultFields = [
            ['field_name' => 'deceased_name', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'death_date', 'field_type' => 'date', 'is_required' => 1],
            ['field_name' => 'death_place', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'cause_of_death', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'registration_number', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'age_at_death', 'field_type' => 'number', 'is_required' => 0],
            ['field_name' => 'informant_name', 'field_type' => 'text', 'is_required' => 0]
        ];
    } else {
        // Generic document fields
        $defaultFields = [
            ['field_name' => 'document_number', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'issue_date', 'field_type' => 'date', 'is_required' => 1],
            ['field_name' => 'applicant_name', 'field_type' => 'text', 'is_required' => 1],
            ['field_name' => 'description', 'field_type' => 'textarea', 'is_required' => 0]
        ];
    }
    
    return $defaultFields;
}

/**
 * Analyze field mapping between requirements and template fields
 */
function analyzeFieldMapping($requirements, $template_fields) {
    $required_fields = array_filter($requirements, function($field) {
        return $field['is_required'] == 1;
    });
    
    $optional_fields = array_filter($requirements, function($field) {
        return $field['is_required'] == 0;
    });
    
    // Extract field names from template fields
    $template_field_names = array_map(function($field) {
        return $field['name'] ?? $field['field_name'] ?? '';
    }, $template_fields);
    
    // Count mapped fields
    $required_mapped = 0;
    $optional_mapped = 0;
    
    foreach ($required_fields as $field) {
        if (in_array($field['field_name'], $template_field_names)) {
            $required_mapped++;
        }
    }
    
    foreach ($optional_fields as $field) {
        if (in_array($field['field_name'], $template_field_names)) {
            $optional_mapped++;
        }
    }
    
    // Calculate completeness score
    $total_required = count($required_fields);
    $total_optional = count($optional_fields);
    $total_fields = $total_required + $total_optional;
    
    if ($total_fields > 0) {
        $completeness_score = (($required_mapped * 2) + $optional_mapped) / (($total_required * 2) + $total_optional);
    } else {
        $completeness_score = 1.0; // No requirements = 100% complete
    }
    
    return [
        'required_mapped' => $required_mapped,
        'required_total' => $total_required,
        'optional_mapped' => $optional_mapped,
        'optional_total' => $total_optional,
        'completeness_score' => round($completeness_score, 2),
        'missing_required' => array_filter($required_fields, function($field) use ($template_field_names) {
            return !in_array($field['field_name'], $template_field_names);
        }),
        'missing_optional' => array_filter($optional_fields, function($field) use ($template_field_names) {
            return !in_array($field['field_name'], $template_field_names);
        })
    ];
}

/**
 * Analyze template and provide insights
 */
function analyzeTemplate($db, $input) {
    $template_fields = $input['template_fields'] ?? [];
    
    if (!is_array($template_fields)) {
        throw new Exception('Template fields must be an array');
    }
    
    $analysis = [
        'field_count' => count($template_fields),
        'field_types' => [],
        'suggestions' => [],
        'potential_issues' => []
    ];
    
    // Analyze field types
    foreach ($template_fields as $field) {
        $type = $field['type'] ?? 'unknown';
        $analysis['field_types'][$type] = ($analysis['field_types'][$type] ?? 0) + 1;
    }
    
    // Generate suggestions
    if ($analysis['field_count'] < 3) {
        $analysis['suggestions'][] = 'Consider adding more fields to make the template more useful';
    }
    
    if (!isset($analysis['field_types']['date'])) {
        $analysis['suggestions'][] = 'Most documents benefit from having at least one date field';
    }
    
    if (!isset($analysis['field_types']['text'])) {
        $analysis['suggestions'][] = 'Text fields are essential for names and descriptions';
    }
    
    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);
}

/**
 * Suggest document type based on template fields
 */
function suggestDocumentType($db, $input) {
    $template_fields = $input['template_fields'] ?? [];
    
    if (!is_array($template_fields)) {
        throw new Exception('Template fields must be an array');
    }
    
    // Extract field names
    $field_names = array_map(function($field) {
        return strtolower($field['name'] ?? $field['field_name'] ?? '');
    }, $template_fields);
    
    // Define patterns for different document types
    $patterns = [
        'birth_certificate' => ['birth', 'father', 'mother', 'born'],
        'marriage_certificate' => ['marriage', 'groom', 'bride', 'married', 'wedding'],
        'death_certificate' => ['death', 'deceased', 'died', 'cause'],
        'identity_document' => ['identity', 'id', 'citizen', 'nationality'],
        'residence_certificate' => ['residence', 'address', 'resident', 'living']
    ];
    
    $scores = [];
    
    foreach ($patterns as $doc_type => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            foreach ($field_names as $field_name) {
                if (strpos($field_name, $keyword) !== false) {
                    $score++;
                    break; // Only count each keyword once
                }
            }
        }
        $scores[$doc_type] = $score;
    }
    
    // Sort by score
    arsort($scores);
    
    // Get actual document types from database
    $sql = "SELECT id, name FROM document_types ORDER BY name";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $document_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $suggestions = [];
    foreach ($scores as $pattern => $score) {
        if ($score > 0) {
            // Try to match pattern to actual document type
            foreach ($document_types as $doc_type) {
                $type_name = strtolower($doc_type['name']);
                if (strpos($type_name, str_replace('_', ' ', $pattern)) !== false ||
                    strpos(str_replace('_', ' ', $pattern), $type_name) !== false) {
                    $suggestions[] = [
                        'document_type_id' => $doc_type['id'],
                        'document_type_name' => $doc_type['name'],
                        'confidence_score' => $score,
                        'pattern_matched' => $pattern
                    ];
                    break;
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'field_analysis' => $field_names
    ]);
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
?>