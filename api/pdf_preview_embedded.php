<?php
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/document_manager.php';

// Check authentication
$database = new Database();
$auth = new Auth($database);
$documentManager = new DocumentManager($database);

if (!$auth->isLoggedIn()) {
    echo '<h1>Authentication required</h1>';
    exit;
}

$document_id = $_GET['document_id'] ?? '';
$template_id = $_GET['template_id'] ?? '';

if (empty($document_id) || empty($template_id)) {
    echo '<h1>Missing Parameters</h1>';
    echo '<p>Document ID: ' . htmlspecialchars($document_id) . '</p>';
    echo '<p>Template ID: ' . htmlspecialchars($template_id) . '</p>';
    exit;
}

try {
    $db = $database->getConnection();
    
    // Get template
    $stmt = $db->prepare("SELECT * FROM pdf_templates WHERE id = ? AND (deleted = 0 OR deleted IS NULL)");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo '<h1>Template not found</h1>';
        exit;
    }
    
    // Get document
    $document = $documentManager->getDocumentById($document_id);
    if (!$document) {
        echo '<h1>Document not found</h1>';
        exit;
    }
    
    $metadata = $documentManager->getDocumentMetadata($document_id);
    
    // Get template fields - check both possible table names
    $template_fields = [];
    
    // First try pdf_template_fields table
    try {
        $stmt = $db->prepare("
            SELECT field_name, x_position, y_position, width, height, 
                   font_size, font_family, font_color, page_number
            FROM pdf_template_fields 
            WHERE template_id = ? 
            ORDER BY page_number, y_position
        ");
        $stmt->execute([$template_id]);
        $template_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Table might not exist, try template_fields table
        try {
            $stmt = $db->prepare("
                SELECT field_name, x_position, y_position, width, height, 
                       font_size, font_family, font_color, page_number
                FROM template_fields 
                WHERE template_id = ? 
                ORDER BY page_number, y_position
            ");
            $stmt->execute([$template_id]);
            $template_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            // Neither table exists, use empty array
            $template_fields = [];
        }
    }
    
    // If no fields found in pdf_template_fields, try template_fields
    if (empty($template_fields)) {
        try {
            $stmt = $db->prepare("
                SELECT field_name, x_position, y_position, width, height, 
                       font_size, font_family, font_color, page_number
                FROM template_fields 
                WHERE template_id = ? 
                ORDER BY page_number, y_position
            ");
            $stmt->execute([$template_id]);
            $template_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If template_fields table also doesn't exist, keep empty array
            $template_fields = [];
        }
    }
    
    // Map document data to template fields
    $pdf_data = [];
    foreach ($template_fields as $field) {
        $field_name = $field['field_name'];
        $field_name_lower = strtolower($field_name);
        
        // Auto-map common fields
        if (strpos($field_name_lower, 'title') !== false || $field_name_lower === 'document_title') {
            $pdf_data[$field_name] = $document['title'];
        } elseif (strpos($field_name_lower, 'type') !== false || $field_name_lower === 'document_type') {
            $pdf_data[$field_name] = $document['document_type_name'];
        } elseif (strpos($field_name_lower, 'date') !== false || $field_name_lower === 'created_date') {
            $pdf_data[$field_name] = date('Y-m-d', strtotime($document['created_at']));
        } elseif (strpos($field_name_lower, 'author') !== false || strpos($field_name_lower, 'uploaded') !== false) {
            $pdf_data[$field_name] = $document['uploaded_by_username'];
        } else {
            // Try to match with metadata fields
            $matched = false;
            foreach ($metadata as $meta_key => $meta_value) {
                $meta_key_lower = strtolower($meta_key);
                if ($meta_key_lower === $field_name_lower || 
                    strpos($field_name_lower, $meta_key_lower) !== false ||
                    strpos($meta_key_lower, $field_name_lower) !== false) {
                    $pdf_data[$field_name] = is_array($meta_value) ? $meta_value['value'] : $meta_value;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                // For debugging, let's show the field name even if empty
                $pdf_data[$field_name] = '[' . $field_name . ']';
            }
        }
    }
    
    $template_url = "serve_pdf_direct.php?file=" . urlencode($template['filename']);
    
} catch (Exception $e) {
    echo '<h1>Error: ' . htmlspecialchars($e->getMessage()) . '</h1>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Preview - <?php echo htmlspecialchars($document['title']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            padding: 0; 
            font-family: Arial, sans-serif; 
            background: #f0f0f0;
        }
        .container { 
            display: flex;
            height: 100vh;
        }
        .pdf-section {
            flex: 1;
            background: white;
            position: relative;
            overflow: hidden;
        }
        .sidebar {
            width: 350px;
            background: #2c3e50;
            color: white;
            padding: 20px;
            overflow-y: auto;
        }
        .pdf-frame { 
            width: 100%; 
            height: 100%; 
            border: none; 
        }
        .field-overlay { 
            position: absolute; 
            background: rgba(0, 123, 255, 0.9); 
            border: 2px solid #007bff; 
            color: white; 
            font-weight: bold; 
            padding: 4px 8px; 
            font-size: 11px; 
            z-index: 10; 
            border-radius: 3px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            max-width: 200px;
            word-wrap: break-word;
            pointer-events: none;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #34495e;
        }
        .header h2 {
            margin: 0 0 10px 0;
            color: #ecf0f1;
        }
        .header p {
            margin: 5px 0;
            color: #bdc3c7;
            font-size: 14px;
        }
        .field-list {
            margin-top: 20px;
        }
        .field-item {
            background: #34495e;
            margin: 8px 0;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        .field-label {
            font-weight: bold;
            color: #ecf0f1;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .field-value {
            color: #3498db;
            font-size: 14px;
            word-wrap: break-word;
        }
        .empty-value {
            color: #95a5a6;
            font-style: italic;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: background 0.3s;
        }
        .print-btn:hover {
            background: #229954;
        }
        .stats {
            background: #34495e;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .stats h3 {
            margin: 0 0 10px 0;
            color: #ecf0f1;
            font-size: 16px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            color: #bdc3c7;
            font-size: 13px;
        }
        .stat-value {
            color: #3498db;
            font-weight: bold;
        }
        @media print {
            .sidebar, .print-btn { display: none !important; }
            .container { display: block; }
            .pdf-section { width: 100% !important; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print PDF</button>
    
    <div class="container">
        <div class="pdf-section">
            <?php if (count($template_fields) > 0): ?>
                <!-- Show options for viewing PDF with embedded fields -->
                <div style="text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px; margin: 10px; border-left: 4px solid #27ae60;">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 20px; flex-wrap: wrap;">
                        <div>
                            <strong>📄 PDF Preview Options:</strong>
                        </div>
                        <a href="generate_pdf_with_fields.php?document_id=<?php echo $document_id; ?>&template_id=<?php echo $template_id; ?>" 
                           target="_blank" 
                           style="display: inline-block; padding: 8px 16px; background: #27ae60; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;">
                            🔗 View with Embedded Data
                        </a>
                        <span style="color: #666; font-size: 14px;">
                            (<?php echo count(array_filter($pdf_data)); ?> fields will be embedded)
                        </span>
                    </div>
                </div>
            <?php endif; ?>
            
            <iframe class="pdf-frame" src="<?php echo $template_url; ?>"></iframe>
        </div>
        
        <div class="sidebar">
            <div class="header">
                <h2>📄 PDF Preview</h2>
                <p><strong>Document:</strong> <?php echo htmlspecialchars($document['title']); ?></p>
                <p><strong>Template:</strong> <?php echo htmlspecialchars($template['name']); ?></p>
                <p><strong>Type:</strong> <?php echo htmlspecialchars($document['document_type_name']); ?></p>
            </div>
            
            <div class="stats">
                <h3>📊 Field Statistics</h3>
                <div class="stat-item">
                    <span>Total Fields:</span>
                    <span class="stat-value"><?php echo count($template_fields); ?></span>
                </div>
                <div class="stat-item">
                    <span>Populated:</span>
                    <span class="stat-value"><?php echo count(array_filter($pdf_data)); ?></span>
                </div>
                <div class="stat-item">
                    <span>Empty:</span>
                    <span class="stat-value"><?php echo count($template_fields) - count(array_filter($pdf_data)); ?></span>
                </div>
            </div>
            
            <div class="field-list">
                <h3>🔗 Embedded Field Data</h3>
                <?php if (empty($template_fields)): ?>
                    <div class="field-item">
                        <div class="field-label" style="color: #e74c3c;">No fields found for this template</div>
                        <div class="field-value">Template ID: <?php echo $template_id; ?></div>
                    </div>
                <?php else: ?>
                    <?php foreach ($template_fields as $field): ?>
                        <?php $value = $pdf_data[$field['field_name']] ?? ''; ?>
                        <div class="field-item">
                            <div class="field-label"><?php echo htmlspecialchars($field['field_name']); ?></div>
                            <div class="field-value <?php echo empty($value) ? 'empty-value' : ''; ?>">
                                <?php echo !empty($value) ? htmlspecialchars($value) : '(no data)'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        console.log('PDF Preview with Embedded Data loaded');
        console.log('Document ID: <?php echo $document_id; ?>');
        console.log('Template ID: <?php echo $template_id; ?>');
        console.log('Template found: <?php echo $template ? "YES" : "NO"; ?>');
        console.log('Template filename: <?php echo $template['filename'] ?? "N/A"; ?>');
        console.log('Total fields in template: <?php echo count($template_fields); ?>');
        console.log('Fields mapped: <?php echo count(array_filter($pdf_data)); ?>');
        console.log('Template URL: <?php echo $template_url; ?>');
        console.log('Template fields:', <?php echo json_encode($template_fields); ?>);
        console.log('PDF data:', <?php echo json_encode($pdf_data); ?>);
        
        // Debug field positions
        <?php foreach ($template_fields as $field): ?>
        console.log('Field "<?php echo $field['field_name']; ?>" - Original: (<?php echo $field['x_position']; ?>, <?php echo $field['y_position']; ?>) -> Converted: (<?php echo ($field['x_position'] / 800) * 100 * 0.85; ?>%, <?php echo ($field['y_position'] / 1000) * 100 * 0.85; ?>%)');
        <?php endforeach; ?>
    </script>
</body>
</html>