<?php
/**
 * Template Bulk Operations API
 * Handles bulk operations like delete, categorize, activate/deactivate
 */

// Start session and include required files
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/template_manager.php';
require_once '../includes/template_storage_manager.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize components
$database = new Database();
$auth = new Auth($database);
$templateManager = new TemplateManager($database);
$storageManager = new TemplateStorageManager();

// Check if user is authenticated and has admin role
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action']) || !isset($input['template_ids'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action and template IDs are required']);
        exit;
    }
    
    $action = $input['action'];
    $template_ids = $input['template_ids'];
    
    // Validate template IDs
    if (!is_array($template_ids) || empty($template_ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid template IDs']);
        exit;
    }
    
    // Sanitize template IDs
    $template_ids = array_map('intval', $template_ids);
    $template_ids = array_filter($template_ids, function($id) { return $id > 0; });
    
    if (empty($template_ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid template IDs provided']);
        exit;
    }
    
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    switch ($action) {
        case 'delete':
            foreach ($template_ids as $template_id) {
                $result = $templateManager->deleteTemplate($template_id);
                $results[] = [
                    'template_id' => $template_id,
                    'success' => $result['success'],
                    'message' => $result['message'] ?? ''
                ];
                
                if ($result['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            $message = "Deleted {$success_count} template(s)";
            if ($error_count > 0) {
                $message .= ", {$error_count} failed";
            }
            break;
            
        case 'activate':
            foreach ($template_ids as $template_id) {
                $result = $templateManager->updateTemplate($template_id, ['is_active' => 1]);
                $results[] = [
                    'template_id' => $template_id,
                    'success' => $result['success'],
                    'message' => $result['message'] ?? ''
                ];
                
                if ($result['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            $message = "Activated {$success_count} template(s)";
            if ($error_count > 0) {
                $message .= ", {$error_count} failed";
            }
            break;
            
        case 'deactivate':
            foreach ($template_ids as $template_id) {
                $result = $templateManager->updateTemplate($template_id, ['is_active' => 0]);
                $results[] = [
                    'template_id' => $template_id,
                    'success' => $result['success'],
                    'message' => $result['message'] ?? ''
                ];
                
                if ($result['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            $message = "Deactivated {$success_count} template(s)";
            if ($error_count > 0) {
                $message .= ", {$error_count} failed";
            }
            break;
            
        case 'categorize':
            if (!isset($input['category'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category is required for categorize action']);
                exit;
            }
            
            $category = trim($input['category']);
            
            foreach ($template_ids as $template_id) {
                $result = $templateManager->updateTemplate($template_id, ['category' => $category ?: null]);
                $results[] = [
                    'template_id' => $template_id,
                    'success' => $result['success'],
                    'message' => $result['message'] ?? ''
                ];
                
                if ($result['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            $category_text = $category ? "'{$category}'" : 'no category';
            $message = "Updated {$success_count} template(s) to {$category_text}";
            if ($error_count > 0) {
                $message .= ", {$error_count} failed";
            }
            break;
            
        case 'export_info':
            // Export template information as CSV data
            $templates_info = [];
            
            foreach ($template_ids as $template_id) {
                $template = $templateManager->getTemplateById($template_id);
                if ($template) {
                    $templates_info[] = [
                        'id' => $template['id'],
                        'name' => $template['name'],
                        'description' => $template['description'],
                        'category' => $template['category'],
                        'file_type' => $template['file_type'],
                        'file_size' => $template['file_size'],
                        'download_count' => $template['download_count'],
                        'created_at' => $template['created_at'],
                        'created_by' => $template['created_by_username']
                    ];
                    $success_count++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Exported information for {$success_count} template(s)",
                'data' => $templates_info,
                'results' => []
            ]);
            exit;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    // Log the bulk operation
    $user_id = $_SESSION['user_id'] ?? null;
    error_log("Bulk template operation: action={$action}, templates=" . implode(',', $template_ids) . ", user={$user_id}, success={$success_count}, errors={$error_count}");
    
    echo json_encode([
        'success' => $error_count === 0,
        'message' => $message,
        'results' => $results,
        'summary' => [
            'total' => count($template_ids),
            'success' => $success_count,
            'errors' => $error_count
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Template bulk operations error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>