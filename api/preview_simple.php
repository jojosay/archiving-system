<?php
session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/document_manager.php';

$database = new Database();
$auth = new Auth($database);
$documentManager = new DocumentManager($database);

if (!$auth->isLoggedIn()) {
    die('<h1>Authentication required</h1>');
}

$document_id = $_GET['document_id'] ?? '';
$template_id = $_GET['template_id'] ?? '';

if (empty($document_id) || empty($template_id)) {
    die('<h1>Missing parameters: doc=' . $document_id . ', template=' . $template_id . '</h1>');
}

$db = $database->getConnection();

// Get template
$stmt = $db->prepare("SELECT * FROM pdf_templates WHERE id = ?");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    die('<h1>Template not found</h1>');
}

// Get document
$document = $documentManager->getDocumentById($document_id);
if (!$document) {
    die('<h1>Document not found</h1>');
}

$metadata = $documentManager->getDocumentMetadata($document_id);

// Get template fields
$stmt = $db->prepare("SELECT * FROM pdf_template_fields WHERE template_id = ?");
$stmt->execute([$template_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map data
$mapped_data = [];
foreach ($fields as $field) {
    $name = $field['field_name'];
    $name_lower = strtolower($name);
    
    if (strpos($name_lower, 'title') !== false) {
        $mapped_data[$name] = $document['title'];
    } elseif (strpos($name_lower, 'type') !== false) {
        $mapped_data[$name] = $document['document_type_name'];
    } elseif (strpos($name_lower, 'date') !== false) {
        $mapped_data[$name] = date('Y-m-d', strtotime($document['created_at']));
    } else {
        // Try metadata
        foreach ($metadata as $key => $value) {
            if (strtolower($key) === $name_lower) {
                $mapped_data[$name] = is_array($value) ? $value['value'] : $value;
                break;
            }
        }
        if (!isset($mapped_data[$name])) {
            $mapped_data[$name] = '[' . $name . ']';
        }
    }
}

// Check if PDF file exists and create appropriate display
$pdf_file_path = "../storage/templates/pdf/" . $template['filename'];
$pdf_exists = file_exists($pdf_file_path);

if ($pdf_exists) {
    // Use the secure PDF serving endpoint
    $pdf_url = "serve_pdf_direct.php?file=" . urlencode($template['filename']);
} else {
    // PDF doesn't exist, we'll show a placeholder with field data
    $pdf_url = null;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Preview</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; }
        .container { display: flex; height: 100vh; }
        .pdf-area { flex: 1; position: relative; }
        .sidebar { width: 300px; background: #2c3e50; color: white; padding: 20px; overflow-y: auto; }
        .pdf-frame { width: 100%; height: 100%; border: none; }
        .overlay { position: absolute; background: rgba(0,123,255,0.8); color: white; padding: 2px 6px; font-size: 11px; border-radius: 3px; z-index: 10; }
        .field { background: #34495e; margin: 8px 0; padding: 10px; border-radius: 4px; }
        .field-name { font-weight: bold; color: #ecf0f1; }
        .field-value { color: #3498db; margin-top: 4px; }
        .print-btn { position: fixed; top: 20px; right: 20px; background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; z-index: 1000; }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print</button>
    <div class="container">
        <div class="pdf-area">
            <?php if ($pdf_url): ?>
                <iframe class="pdf-frame" src="<?php echo $pdf_url; ?>"></iframe>
            <?php else: ?>
                <div style="padding: 20px; background: #f8f9fa; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <h2>PDF Template Preview</h2>
                    <p><strong>Template:</strong> <?php echo htmlspecialchars($template['name']); ?></p>
                    <p><strong>File:</strong> <?php echo htmlspecialchars($template['filename']); ?></p>
                    <p style="color: #e74c3c;">PDF file not found, but field data is shown in the sidebar</p>
                    <div style="margin-top: 20px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3>Embedded Field Data Preview</h3>
                        <?php foreach ($mapped_data as $name => $value): ?>
                            <?php if ($value !== '[' . $name . ']'): ?>
                                <div style="margin: 10px 0; padding: 8px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                                    <strong><?php echo htmlspecialchars($name); ?>:</strong> 
                                    <span style="color: #1976d2;"><?php echo htmlspecialchars($value); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach ($fields as $field): ?>
                <?php $value = $mapped_data[$field['field_name']] ?? ''; ?>
                <?php if (!empty($value) && $value !== '[' . $field['field_name'] . ']'): ?>
                    <div class="overlay" style="left: <?php echo ($field['x_position']/595)*100; ?>%; top: <?php echo ($field['y_position']/842)*100; ?>%;">
                        <?php echo htmlspecialchars($value); ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="sidebar">
            <h2>PDF Preview</h2>
            <p><strong>Document:</strong> <?php echo htmlspecialchars($document['title']); ?></p>
            <p><strong>Template:</strong> <?php echo htmlspecialchars($template['name']); ?></p>
            <h3>Field Data</h3>
            <?php foreach ($mapped_data as $name => $value): ?>
                <div class="field">
                    <div class="field-name"><?php echo htmlspecialchars($name); ?></div>
                    <div class="field-value"><?php echo htmlspecialchars($value); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>