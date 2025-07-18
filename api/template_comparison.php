<?php
/**
 * Template Comparison API
 * Handles template comparison analysis and recommendations
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
        case 'compare_templates':
            compareTemplates($db, $input);
            break;
            
        case 'analyze_similarity':
            analyzeSimilarity($db, $input);
            break;
            
        case 'get_recommendations':
            getRecommendations($db, $input);
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
 * Compare two templates and provide detailed analysis
 */
function compareTemplates($db, $input) {
    $template1_id = $input['template1_id'] ?? null;
    $template2_id = $input['template2_id'] ?? null;
    
    if (!$template1_id || !$template2_id) {
        throw new Exception('Both template IDs are required');
    }
    
    if ($template1_id === $template2_id) {
        throw new Exception('Cannot compare a template with itself');
    }
    
    // Get template data
    $template1 = getTemplateData($db, $template1_id);
    $template2 = getTemplateData($db, $template2_id);
    
    if (!$template1 || !$template2) {
        throw new Exception('One or both templates not found');
    }
    
    // Perform comparison analysis
    $comparison = performDetailedComparison($db, $template1, $template2);
    
    echo json_encode([
        'success' => true,
        'comparison' => $comparison
    ]);
}

/**
 * Get template data with parsed fields
 */
function getTemplateData($db, $template_id) {
    $sql = "SELECT t.*, dt.name as document_type_name, 
                   u.username as created_by_username
            FROM templates t
            LEFT JOIN document_types dt ON t.document_type_id = dt.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$template_id]);
    
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($template) {
        $template['template_data'] = json_decode($template['template_data'], true);
        if (!$template['template_data']) {
            $template['template_data'] = ['fields' => []];
        }
    }
    
    return $template;
}

/**
 * Perform detailed comparison between two templates
 */
function performDetailedComparison($db, $template1, $template2) {
    // Extract field data
    $fields1 = $template1['template_data']['fields'] ?? [];
    $fields2 = $template2['template_data']['fields'] ?? [];
    
    // Analyze field differences
    $fieldAnalysis = analyzeFieldDifferences($fields1, $fields2);
    
    // Analyze completeness
    $completenessAnalysis = analyzeCompleteness($db, $template1, $template2, $fields1, $fields2);
    
    // Generate recommendations
    $recommendations = generateComparisonRecommendations($template1, $template2, $fieldAnalysis, $completenessAnalysis);
    
    // Calculate similarity score
    $similarityScore = calculateSimilarityScore($fieldAnalysis, $completenessAnalysis);
    
    return [
        'template1' => $template1,
        'template2' => $template2,
        'fields' => [
            'template1' => $fields1,
            'template2' => $fields2,
            'common' => $fieldAnalysis['common_fields'],
            'unique_to_template1' => $fieldAnalysis['unique_to_template1'],
            'unique_to_template2' => $fieldAnalysis['unique_to_template2'],
            'analysis' => $fieldAnalysis
        ],
        'completeness' => [
            'template1' => floatval($template1['field_completeness_score'] ?? 0),
            'template2' => floatval($template2['field_completeness_score'] ?? 0),
            'analysis' => $completenessAnalysis
        ],
        'similarity_score' => $similarityScore,
        'recommendations' => $recommendations,
        'metadata' => [
            'comparison_date' => date('Y-m-d H:i:s'),
            'same_document_type' => $template1['document_type_id'] === $template2['document_type_id'],
            'document_types' => [
                'template1' => $template1['document_type_name'],
                'template2' => $template2['document_type_name']
            ]
        ]
    ];
}

/**
 * Analyze field differences between two templates
 */
function analyzeFieldDifferences($fields1, $fields2) {
    // Extract field names and create lookup maps
    $fieldMap1 = [];
    $fieldMap2 = [];
    
    foreach ($fields1 as $field) {
        $name = $field['name'] ?? $field['field_name'] ?? '';
        if ($name) {
            $fieldMap1[$name] = $field;
        }
    }
    
    foreach ($fields2 as $field) {
        $name = $field['name'] ?? $field['field_name'] ?? '';
        if ($name) {
            $fieldMap2[$name] = $field;
        }
    }
    
    $fieldNames1 = array_keys($fieldMap1);
    $fieldNames2 = array_keys($fieldMap2);
    
    // Find common and unique fields
    $commonFields = array_intersect($fieldNames1, $fieldNames2);
    $uniqueToTemplate1 = array_diff($fieldNames1, $fieldNames2);
    $uniqueToTemplate2 = array_diff($fieldNames2, $fieldNames1);
    
    // Analyze field type differences for common fields
    $fieldTypeDifferences = [];
    foreach ($commonFields as $fieldName) {
        $field1 = $fieldMap1[$fieldName];
        $field2 = $fieldMap2[$fieldName];
        
        $type1 = $field1['type'] ?? $field1['field_type'] ?? 'text';
        $type2 = $field2['type'] ?? $field2['field_type'] ?? 'text';
        
        $required1 = $field1['required'] ?? $field1['is_required'] ?? false;
        $required2 = $field2['required'] ?? $field2['is_required'] ?? false;
        
        if ($type1 !== $type2 || $required1 !== $required2) {
            $fieldTypeDifferences[$fieldName] = [
                'template1' => ['type' => $type1, 'required' => $required1],
                'template2' => ['type' => $type2, 'required' => $required2]
            ];
        }
    }
    
    return [
        'total_fields' => [
            'template1' => count($fieldNames1),
            'template2' => count($fieldNames2)
        ],
        'common_fields' => $commonFields,
        'unique_to_template1' => $uniqueToTemplate1,
        'unique_to_template2' => $uniqueToTemplate2,
        'field_type_differences' => $fieldTypeDifferences,
        'field_maps' => [
            'template1' => $fieldMap1,
            'template2' => $fieldMap2
        ]
    ];
}

/**
 * Analyze completeness comparison
 */
function analyzeCompleteness($db, $template1, $template2, $fields1, $fields2) {
    $completeness1 = floatval($template1['field_completeness_score'] ?? 0);
    $completeness2 = floatval($template2['field_completeness_score'] ?? 0);
    
    // Get field requirements for document types if they exist
    $requirements1 = getFieldRequirements($db, $template1['document_type_id']);
    $requirements2 = getFieldRequirements($db, $template2['document_type_id']);
    
    // Calculate detailed completeness metrics
    $metrics1 = calculateDetailedCompleteness($fields1, $requirements1);
    $metrics2 = calculateDetailedCompleteness($fields2, $requirements2);
    
    return [
        'scores' => [
            'template1' => $completeness1,
            'template2' => $completeness2,
            'difference' => abs($completeness1 - $completeness2)
        ],
        'detailed_metrics' => [
            'template1' => $metrics1,
            'template2' => $metrics2
        ],
        'comparison' => [
            'better_template' => $completeness1 > $completeness2 ? 1 : ($completeness2 > $completeness1 ? 2 : 0),
            'significant_difference' => abs($completeness1 - $completeness2) > 0.2
        ]
    ];
}

/**
 * Get field requirements for a document type
 */
function getFieldRequirements($db, $document_type_id) {
    if (!$document_type_id) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM template_field_requirements 
                WHERE document_type_id = ? 
                ORDER BY display_order, field_name";
        $stmt = $db->prepare($sql);
        $stmt->execute([$document_type_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Calculate detailed completeness metrics
 */
function calculateDetailedCompleteness($fields, $requirements) {
    if (empty($requirements)) {
        return [
            'total_fields' => count($fields),
            'required_fields_mapped' => 0,
            'required_fields_total' => 0,
            'optional_fields_mapped' => 0,
            'optional_fields_total' => 0,
            'completeness_percentage' => count($fields) > 0 ? 100 : 0
        ];
    }
    
    $fieldNames = array_map(function($field) {
        return $field['name'] ?? $field['field_name'] ?? '';
    }, $fields);
    
    $requiredRequirements = array_filter($requirements, function($req) {
        return $req['is_required'] == 1;
    });
    
    $optionalRequirements = array_filter($requirements, function($req) {
        return $req['is_required'] == 0;
    });
    
    $requiredMapped = 0;
    foreach ($requiredRequirements as $req) {
        if (in_array($req['field_name'], $fieldNames)) {
            $requiredMapped++;
        }
    }
    
    $optionalMapped = 0;
    foreach ($optionalRequirements as $req) {
        if (in_array($req['field_name'], $fieldNames)) {
            $optionalMapped++;
        }
    }
    
    $totalRequired = count($requiredRequirements);
    $totalOptional = count($optionalRequirements);
    $totalRequirements = $totalRequired + $totalOptional;
    
    $completenessPercentage = 0;
    if ($totalRequirements > 0) {
        $completenessPercentage = (($requiredMapped * 2) + $optionalMapped) / (($totalRequired * 2) + $totalOptional) * 100;
    }
    
    return [
        'total_fields' => count($fields),
        'required_fields_mapped' => $requiredMapped,
        'required_fields_total' => $totalRequired,
        'optional_fields_mapped' => $optionalMapped,
        'optional_fields_total' => $totalOptional,
        'completeness_percentage' => round($completenessPercentage, 2)
    ];
}

/**
 * Generate comparison recommendations
 */
function generateComparisonRecommendations($template1, $template2, $fieldAnalysis, $completenessAnalysis) {
    $recommendations = [];
    
    // Document type compatibility
    if ($template1['document_type_id'] !== $template2['document_type_id']) {
        $recommendations[] = [
            'type' => 'document_type_mismatch',
            'priority' => 'high',
            'title' => 'Different Document Types',
            'description' => "Templates are for different document types ({$template1['document_type_name']} vs {$template2['document_type_name']}). Consider comparing templates of the same type for more meaningful insights."
        ];
    }
    
    // Field coverage recommendations
    $unique1Count = count($fieldAnalysis['unique_to_template1']);
    $unique2Count = count($fieldAnalysis['unique_to_template2']);
    
    if ($unique1Count > 0) {
        $priority = $unique1Count > 5 ? 'high' : ($unique1Count > 2 ? 'medium' : 'low');
        $recommendations[] = [
            'type' => 'field_enhancement',
            'priority' => $priority,
            'title' => 'Enhance Template 2 with Additional Fields',
            'description' => "Template 1 has {$unique1Count} unique fields that could improve Template 2's completeness. Consider adding: " . implode(', ', array_slice($fieldAnalysis['unique_to_template1'], 0, 3)) . ($unique1Count > 3 ? '...' : '')
        ];
    }
    
    if ($unique2Count > 0) {
        $priority = $unique2Count > 5 ? 'high' : ($unique2Count > 2 ? 'medium' : 'low');
        $recommendations[] = [
            'type' => 'field_enhancement',
            'priority' => $priority,
            'title' => 'Enhance Template 1 with Additional Fields',
            'description' => "Template 2 has {$unique2Count} unique fields that could improve Template 1's completeness. Consider adding: " . implode(', ', array_slice($fieldAnalysis['unique_to_template2'], 0, 3)) . ($unique2Count > 3 ? '...' : '')
        ];
    }
    
    // Completeness recommendations
    if ($completenessAnalysis['comparison']['significant_difference']) {
        $betterTemplate = $completenessAnalysis['comparison']['better_template'];
        $betterName = $betterTemplate === 1 ? $template1['name'] : $template2['name'];
        $worseTemplate = $betterTemplate === 1 ? 2 : 1;
        $worseName = $betterTemplate === 1 ? $template2['name'] : $template1['name'];
        
        $recommendations[] = [
            'type' => 'completeness_gap',
            'priority' => 'high',
            'title' => 'Significant Completeness Difference',
            'description' => "{$betterName} has significantly better field completeness than {$worseName}. Consider improving the lower-scoring template by adding missing required fields."
        ];
    }
    
    // Field type consistency
    if (!empty($fieldAnalysis['field_type_differences'])) {
        $diffCount = count($fieldAnalysis['field_type_differences']);
        $recommendations[] = [
            'type' => 'field_consistency',
            'priority' => 'medium',
            'title' => 'Field Type Inconsistencies',
            'description' => "{$diffCount} common fields have different types or requirements between templates. Review these for consistency: " . implode(', ', array_slice(array_keys($fieldAnalysis['field_type_differences']), 0, 3))
        ];
    }
    
    // Consolidation opportunity
    $commonCount = count($fieldAnalysis['common_fields']);
    $total1 = $fieldAnalysis['total_fields']['template1'];
    $total2 = $fieldAnalysis['total_fields']['template2'];
    
    if ($commonCount > 0 && $commonCount / max($total1, $total2) > 0.8) {
        $recommendations[] = [
            'type' => 'consolidation',
            'priority' => 'low',
            'title' => 'Consider Template Consolidation',
            'description' => 'These templates share most of their fields. Consider consolidating them into a single template with optional sections to reduce maintenance overhead.'
        ];
    }
    
    // Usage pattern recommendations
    $usage1 = intval($template1['usage_count'] ?? 0);
    $usage2 = intval($template2['usage_count'] ?? 0);
    
    if ($usage1 === 0 && $usage2 === 0) {
        $recommendations[] = [
            'type' => 'usage_tracking',
            'priority' => 'low',
            'title' => 'No Usage Data Available',
            'description' => 'Neither template has recorded usage. Consider implementing usage tracking to better understand template effectiveness.'
        ];
    } elseif (abs($usage1 - $usage2) > 10) {
        $moreUsed = $usage1 > $usage2 ? $template1['name'] : $template2['name'];
        $lessUsed = $usage1 > $usage2 ? $template2['name'] : $template1['name'];
        
        $recommendations[] = [
            'type' => 'usage_analysis',
            'priority' => 'medium',
            'title' => 'Usage Pattern Difference',
            'description' => "{$moreUsed} is used significantly more than {$lessUsed}. Investigate why and consider improving the less-used template or retiring it if redundant."
        ];
    }
    
    return $recommendations;
}

/**
 * Calculate similarity score between templates
 */
function calculateSimilarityScore($fieldAnalysis, $completenessAnalysis) {
    $commonCount = count($fieldAnalysis['common_fields']);
    $total1 = $fieldAnalysis['total_fields']['template1'];
    $total2 = $fieldAnalysis['total_fields']['template2'];
    $maxFields = max($total1, $total2);
    
    // Field similarity (0-1)
    $fieldSimilarity = $maxFields > 0 ? $commonCount / $maxFields : 0;
    
    // Completeness similarity (0-1)
    $completeness1 = $completenessAnalysis['scores']['template1'];
    $completeness2 = $completenessAnalysis['scores']['template2'];
    $completenessSimilarity = 1 - abs($completeness1 - $completeness2);
    
    // Overall similarity (weighted average)
    $overallSimilarity = ($fieldSimilarity * 0.7) + ($completenessSimilarity * 0.3);
    
    return [
        'field_similarity' => round($fieldSimilarity, 3),
        'completeness_similarity' => round($completenessSimilarity, 3),
        'overall_similarity' => round($overallSimilarity, 3),
        'similarity_percentage' => round($overallSimilarity * 100, 1)
    ];
}

/**
 * Analyze similarity between multiple templates
 */
function analyzeSimilarity($db, $input) {
    $template_ids = $input['template_ids'] ?? [];
    
    if (count($template_ids) < 2) {
        throw new Exception('At least 2 template IDs are required for similarity analysis');
    }
    
    $templates = [];
    foreach ($template_ids as $id) {
        $template = getTemplateData($db, $id);
        if ($template) {
            $templates[] = $template;
        }
    }
    
    if (count($templates) < 2) {
        throw new Exception('Not enough valid templates found');
    }
    
    $similarities = [];
    for ($i = 0; $i < count($templates); $i++) {
        for ($j = $i + 1; $j < count($templates); $j++) {
            $comparison = performDetailedComparison($db, $templates[$i], $templates[$j]);
            $similarities[] = [
                'template1_id' => $templates[$i]['id'],
                'template2_id' => $templates[$j]['id'],
                'similarity_score' => $comparison['similarity_score']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'similarities' => $similarities
    ]);
}

/**
 * Get recommendations for template improvements
 */
function getRecommendations($db, $input) {
    $template_id = $input['template_id'] ?? null;
    
    if (!$template_id) {
        throw new Exception('Template ID is required');
    }
    
    $template = getTemplateData($db, $template_id);
    if (!$template) {
        throw new Exception('Template not found');
    }
    
    // Generate standalone recommendations for a single template
    $recommendations = generateStandaloneRecommendations($db, $template);
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations
    ]);
}

/**
 * Generate recommendations for a single template
 */
function generateStandaloneRecommendations($db, $template) {
    $recommendations = [];
    $fields = $template['template_data']['fields'] ?? [];
    
    // Field count recommendations
    if (count($fields) < 3) {
        $recommendations[] = [
            'type' => 'field_count',
            'priority' => 'medium',
            'title' => 'Consider Adding More Fields',
            'description' => 'This template has very few fields. Consider adding more fields to make it more comprehensive and useful.'
        ];
    }
    
    // Completeness recommendations
    $completeness = floatval($template['field_completeness_score'] ?? 0);
    if ($completeness < 0.5) {
        $recommendations[] = [
            'type' => 'completeness',
            'priority' => 'high',
            'title' => 'Low Field Completeness',
            'description' => 'This template has low completeness score. Consider adding required fields for the document type to improve its effectiveness.'
        ];
    }
    
    // Usage recommendations
    $usage = intval($template['usage_count'] ?? 0);
    if ($usage === 0) {
        $recommendations[] = [
            'type' => 'usage',
            'priority' => 'low',
            'title' => 'Template Not Used',
            'description' => 'This template has never been used. Consider promoting it or reviewing if it meets user needs.'
        ];
    }
    
    return $recommendations;
}
?>