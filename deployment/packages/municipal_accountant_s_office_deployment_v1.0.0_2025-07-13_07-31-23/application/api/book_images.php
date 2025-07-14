<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once '../config/config.php';
require_once '../includes/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get all book images
    $stmt = $conn->prepare("SELECT id, book_title as title, description, file_path, created_at FROM book_images ORDER BY created_at DESC");
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images)
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching book images: ' . $e->getMessage()
    ]);
}
?>