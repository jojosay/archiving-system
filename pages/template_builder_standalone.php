<?php
/**
 * Standalone Template Builder - Opens in new tab
 * Matches the layout of generate_pdf_with_fields.php for exact positioning
 */

session_start();
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Initialize database and auth
$database = new Database();
$auth = new Auth($database);

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    echo '<h1>Authentication required</h1>';
    exit;
}

$db = $database->getConnection();

// Get template ID from URL
$edit_template_id = $_GET['edit_template'] ?? null;
$template_data = null;
$existing_fields = [];

if ($edit_template_id) {
    // Get template data
    try {
        $stmt = $db->prepare("SELECT pt.*, dt.name as document_type_name FROM pdf_templates pt LEFT JOIN document_types dt ON pt.document_type_id = dt.id WHERE pt.id = ?");
        $stmt->execute([$edit_template_id]);
        $template_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template_data) {
            echo '<h1>Template not found</h1>';
            exit;
        }
        
        // Get existing fields
        try {
            $stmt = $db->prepare("SELECT * FROM template_fields WHERE template_id = ? ORDER BY page_number, y_position");
            $stmt->execute([$edit_template_id]);
            $existing_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $existing_fields = [];
        }
        
    } catch (Exception $e) {
        echo '<h1>Error loading template: ' . htmlspecialchars($e->getMessage()) . '</h1>';
        exit;
    }
} else {
    echo '<h1>No template specified</h1>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Template Builder - <?php echo htmlspecialchars($template_data['name']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            padding: 20px; 
            font-family: Arial, sans-serif; 
            background: #f0f0f0;
        }
        .builder-container {
            width: 100vw;
            height: 100vh;
            margin: 0;
            background: white;
            position: relative;
            display: flex;
        }
        .pdf-section {
            flex: 1;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem;
            background: #f1f5f9;
            overflow: auto;
            min-width: 900px; /* Ensure adequate space for PDF rendering */
        }
        .pdf-container {
            position: relative;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: visible;
            margin: 0 auto;
            min-width: 600px;
            min-height: 800px;
        }
        .sidebar {
            width: 350px;
            background: #2c3e50;
            color: white;
            padding: 20px;
            overflow-y: auto;
            max-height: 100vh;
        }
        .field-embedded {
            position: absolute;
            background: rgba(59, 130, 246, 0.8);
            color: white;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 10;
            pointer-events: all;
            cursor: move;
            border: 2px solid #3b82f6;
        }
        .field-embedded:hover {
            background: rgba(29, 78, 216, 0.9);
            border-color: #1d4ed8;
        }
        .field-embedded.selected {
            border-color: #dc2626;
            background: rgba(220, 38, 38, 0.8);
        }
        .header-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        .controls {
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn:hover {
            background: #229954;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .available-fields {
            background: #34495e;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .available-fields h3 {
            margin: 0 0 15px 0;
            color: #ecf0f1;
        }
        .draggable-field {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: #2c3e50;
            border: 1px solid #34495e;
            border-radius: 4px;
            margin-bottom: 8px;
            cursor: grab;
            transition: background 0.2s;
        }
        .draggable-field:hover {
            background: #34495e;
        }
        .draggable-field:active {
            cursor: grabbing;
        }
        .field-icon {
            font-size: 16px;
        }
        .field-name {
            font-size: 14px;
            color: #ecf0f1;
        }
        .field-list {
            background: #34495e;
            padding: 15px;
            border-radius: 6px;
        }
        .field-list h3 {
            margin: 0 0 15px 0;
            color: #ecf0f1;
        }
        .field-item {
            background: #2c3e50;
            margin: 8px 0;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #34495e;
        }
        .field-label {
            font-weight: bold;
            color: #ecf0f1;
            font-size: 12px;
        }
        .field-coordinates {
            color: #3498db;
            font-size: 11px;
            margin-top: 4px;
        }
        .field-item {
            cursor: pointer;
        }
        .field-item:hover {
            background: #34495e;
        }
        .field-item.selected {
            background: #3498db;
        }
        .font-properties {
            background: #34495e;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .font-properties h3 {
            margin: 0 0 15px 0;
            color: #ecf0f1;
        }
        .font-controls .form-group {
            margin-bottom: 15px;
        }
        .font-controls label {
            display: block;
            color: #ecf0f1;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .font-controls input,
        .font-controls select {
            width: 100%;
            padding: 6px;
            border: 1px solid #2c3e50;
            border-radius: 3px;
            background: #2c3e50;
            color: #ecf0f1;
            font-size: 12px;
        }
        .font-controls input[type="number"] {
            width: 60px;
            display: inline-block;
        }
        .font-controls input[type="color"] {
            width: 50px;
            height: 30px;
            padding: 2px;
        }
        .font-controls span {
            color: #ecf0f1;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="builder-container">
        <div class="pdf-section">
            <!-- PDF.js Canvas (same as generate_pdf_with_fields.php) -->
            <div class="pdf-container">
                <canvas id="pdfCanvas" style="display: block; max-width: 100%; height: auto;"></canvas>
                
                <!-- Field overlay for existing fields -->
                <div id="fieldOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 10;">
                    <?php foreach ($existing_fields as $field): ?>
                        <div class="field-embedded" 
                             data-field-id="<?php echo $field['id']; ?>"
                             data-field-name="<?php echo htmlspecialchars($field['field_name']); ?>"
                             data-x="<?php echo $field['x_position']; ?>" 
                             data-y="<?php echo $field['y_position']; ?>"
                             style="left: <?php echo $field['x_position']; ?>px; top: <?php echo $field['y_position']; ?>px; position: absolute; pointer-events: all;">
                            <?php echo htmlspecialchars($field['field_name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="sidebar">
            <div class="header-info">
                <h2>📄 Template Builder</h2>
                <p><strong>Template:</strong> <?php echo htmlspecialchars($template_data['name']); ?></p>
                <p><strong>Type:</strong> <?php echo htmlspecialchars($template_data['document_type_name'] ?? 'No type assigned'); ?></p>
            </div>
            
            <div class="controls">
                <button id="saveFieldsBtn" class="btn">💾 Save Fields</button>
                <button id="previewBtn" class="btn btn-secondary">👁 Preview</button>
                <button onclick="window.close()" class="btn btn-secondary">✕ Close</button>
            </div>
            
            <div class="available-fields">
                <h3>📋 Available Fields</h3>
                <?php
                // Load document type fields
                if ($template_data['document_type_id']) {
                    try {
                        $stmt = $db->prepare("SELECT * FROM document_type_fields WHERE document_type_id = ? ORDER BY field_order, field_label");
                        $stmt->execute([$template_data['document_type_id']]);
                        $doc_type_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($doc_type_fields as $field):
                            if ($field['field_type'] === 'cascading_dropdown') {
                                $location_levels = ['Region', 'Province', 'City', 'Barangay'];
                                foreach ($location_levels as $level) {
                                    $level_field_name = strtolower($field['field_name']) . '_' . strtolower(str_replace(' ', '_', $level));
                                    ?>
                                    <div class="draggable-field" data-field-name="<?php echo htmlspecialchars($level_field_name); ?>" data-field-type="text" data-field-label="<?php echo htmlspecialchars($level); ?>">
                                        <span class="field-icon">📍</span>
                                        <span class="field-name"><?php echo htmlspecialchars($level); ?></span>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <div class="draggable-field" data-field-name="<?php echo htmlspecialchars($field['field_name']); ?>" data-field-type="<?php echo htmlspecialchars($field['field_type']); ?>" data-field-label="<?php echo htmlspecialchars($field['field_label']); ?>">
                                    <span class="field-icon">
                                        <?php 
                                        switch($field['field_type']) {
                                            case 'text': echo '📝'; break;
                                            case 'number': echo '🔢'; break;
                                            case 'date': echo '📅'; break;
                                            case 'dropdown': case 'select': echo '📋'; break;
                                            case 'file': echo '📎'; break;
                                            case 'textarea': echo '📄'; break;
                                            default: echo '📄'; break;
                                        }
                                        ?>
                                    </span>
                                    <span class="field-name"><?php echo htmlspecialchars($field['field_label']); ?></span>
                                </div>
                                <?php
                            }
                        endforeach;
                    } catch (Exception $e) {
                        echo '<p style="color: #e74c3c;">Error loading fields</p>';
                    }
                } else {
                    echo '<p style="color: #e74c3c;">No document type assigned</p>';
                }
                ?>
                
                <!-- System Fields -->
                <h4 style="color: #ecf0f1; margin-top: 20px; margin-bottom: 10px;">System Fields</h4>
                <div class="draggable-field" data-field-name="title" data-field-type="text" data-field-label="Document Title">
                    <span class="field-icon">📝</span>
                    <span class="field-name">Document Title</span>
                </div>
                <div class="draggable-field" data-field-name="created_at" data-field-type="date" data-field-label="Created Date">
                    <span class="field-icon">📅</span>
                    <span class="field-name">Created Date</span>
                </div>
                <div class="draggable-field" data-field-name="document_type_name" data-field-type="text" data-field-label="Document Type">
                    <span class="field-icon">📋</span>
                    <span class="field-name">Document Type</span>
                </div>
            </div>
            
            <div class="field-list">
                <h3>📍 Positioned Fields</h3>
                <div id="fieldsList">
                    <?php foreach ($existing_fields as $field): ?>
                        <div class="field-item" data-field-id="<?php echo $field['id']; ?>" onclick="selectField(<?php echo $field['id']; ?>)">
                            <div class="field-label"><?php echo htmlspecialchars($field['field_name']); ?></div>
                            <div class="field-coordinates">Position: (<?php echo $field['x_position']; ?>, <?php echo $field['y_position']; ?>)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Font Properties Panel -->
            <div class="font-properties" id="fontPropertiesPanel" style="display: block;">
                <h3>🎨 Font Properties</h3>
                <p id="selectedFieldInfo" style="color: #bdc3c7; font-size: 11px; margin: 0 0 15px 0;">Select a field to edit font properties</p>
                <div class="font-controls">
                    <div class="form-group">
                        <label for="fontSizeInput">Font Size:</label>
                        <input type="number" id="fontSizeInput" min="8" max="72" value="12" onchange="updateFieldFont()">
                        <span>px</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="fontFamilySelect">Font Family:</label>
                        <select id="fontFamilySelect" onchange="updateFieldFont()">
                            <option value="Arial">Arial</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Helvetica">Helvetica</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Tahoma">Tahoma</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fontColorInput">Font Color:</label>
                        <input type="color" id="fontColorInput" value="#000000" onchange="updateFieldFont()">
                    </div>
                    
                    <div class="form-group">
                        <label for="fontWeightSelect">Font Weight:</label>
                        <select id="fontWeightSelect" onchange="updateFieldFont()">
                            <option value="normal">Normal</option>
                            <option value="bold">Bold</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fontStyleSelect">Font Style:</label>
                        <select id="fontStyleSelect" onchange="updateFieldFont()">
                            <option value="normal">Normal</option>
                            <option value="italic">Italic</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        // Configure PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
        
        let templateId = <?php echo $edit_template_id; ?>;
        let fields = <?php echo json_encode($existing_fields); ?>;
        let selectedField = null;
        let selectedFieldElement = null;
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };
        
        // Multi-page PDF support - preserves existing single-page functionality
        let currentPDF = null;
        let currentPageNumber = 1;
        let totalPages = 1;
        
        // Load and render PDF (enhanced with multi-page support)
        async function loadPDF() {
            try {
                const pdfUrl = '../api/serve_pdf_direct.php?file=<?php echo urlencode($template_data['filename']); ?>';
                currentPDF = await pdfjsLib.getDocument(pdfUrl).promise;
                totalPages = currentPDF.numPages;
                
                // Update page navigation if multi-page
                updatePageNavigation();
                
                // Load the current page (defaults to page 1 for backward compatibility)
                await renderPage(currentPageNumber);
                
            } catch (error) {
                console.error('Error loading PDF:', error);
            }
        }
        
        // Render specific page
        async function renderPage(pageNum) {
            if (!currentPDF || pageNum < 1 || pageNum > totalPages) return;
            
            const page = await currentPDF.getPage(pageNum);
            const canvas = document.getElementById('pdfCanvas');
            const context = canvas.getContext('2d');
            
            // Use identical scaling to generate_pdf_with_fields.php for perfect positioning alignment
            const viewport = page.getViewport({ scale: 1.0 });
            const container = canvas.parentElement;
            const forcedContainerWidth = 864; // Match preview page container width
            const maxWidth = Math.min(forcedContainerWidth - 40, 800);
            const scale = maxWidth / viewport.width;
            const scaledViewport = page.getViewport({ scale: scale });
            
            canvas.width = scaledViewport.width;
            canvas.height = scaledViewport.height;
            canvas.style.width = scaledViewport.width + 'px';
            canvas.style.height = scaledViewport.height + 'px';
            
            // Update container to match canvas size
            const pdfContainer = canvas.closest('.pdf-container');
            if (pdfContainer) {
                pdfContainer.style.width = scaledViewport.width + 'px';
                pdfContainer.style.height = scaledViewport.height + 'px';
            }
            
            // Render PDF page
            const renderContext = {
                canvasContext: context,
                viewport: scaledViewport
            };
            
            await page.render(renderContext).promise;
            
            // Store scale for coordinate conversion
            window.pdfScale = scale;
            
            // Position existing fields for current page
            positionExistingFields();
        }
        
        // Update page navigation controls
        function updatePageNavigation() {
            let navHtml = '';
            if (totalPages > 1) {
                navHtml = `
                    <div class="page-navigation" style="background: #34495e; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center;">
                        <button onclick="previousPage()" ${currentPageNumber <= 1 ? 'disabled' : ''} class="btn btn-secondary" style="margin-right: 10px;">← Previous</button>
                        <span style="color: #ecf0f1; margin: 0 10px;">Page ${currentPageNumber} of ${totalPages}</span>
                        <button onclick="nextPage()" ${currentPageNumber >= totalPages ? 'disabled' : ''} class="btn btn-secondary" style="margin-left: 10px;">Next →</button>
                    </div>
                `;
            }
            
            // Insert navigation after controls
            const controlsDiv = document.querySelector('.controls');
            let navDiv = document.getElementById('pageNavigation');
            if (!navDiv) {
                navDiv = document.createElement('div');
                navDiv.id = 'pageNavigation';
                controlsDiv.parentNode.insertBefore(navDiv, controlsDiv.nextSibling);
            }
            navDiv.innerHTML = navHtml;
        }
        
        // Page navigation functions
        function previousPage() {
            if (currentPageNumber > 1) {
                currentPageNumber--;
                renderPage(currentPageNumber);
                updatePageNavigation();
                updateFieldsList(); // Refresh field list for current page
            }
        }
        
        function nextPage() {
            if (currentPageNumber < totalPages) {
                currentPageNumber++;
                renderPage(currentPageNumber);
                updatePageNavigation();
                updateFieldsList(); // Refresh field list for current page
            }
        }
        
        // Initialize drag and drop functionality
        function initializeDragDrop() {
            // Make draggable fields draggable
            document.querySelectorAll('.draggable-field').forEach(field => {
                field.addEventListener('dragstart', handleDragStart);
                field.draggable = true;
            });
            
            // Make PDF container a drop zone
            const pdfContainer = document.querySelector('.pdf-container');
            pdfContainer.addEventListener('dragover', handleDragOver);
            pdfContainer.addEventListener('drop', handleDrop);
            
            // Make existing fields draggable
            document.querySelectorAll('.field-embedded').forEach(field => {
                field.addEventListener('mousedown', handleFieldMouseDown);
            });
        }
        
        function handleDragStart(e) {
            e.dataTransfer.setData('text/plain', JSON.stringify({
                fieldName: this.dataset.fieldName,
                fieldType: this.dataset.fieldType,
                fieldLabel: this.dataset.fieldLabel
            }));
        }
        
        function handleDragOver(e) {
            e.preventDefault();
        }
        
        function handleDrop(e) {
            e.preventDefault();
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            const rect = e.currentTarget.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            createField(data.fieldName, data.fieldType, data.fieldLabel, x, y);
        }
        
        function createField(fieldName, fieldType, fieldLabel, x, y) {
            // Generate unique field ID
            const fieldId = 'temp_' + Date.now();
            
            const fieldElement = document.createElement('div');
            fieldElement.className = 'field-embedded';
            fieldElement.dataset.fieldId = fieldId;
            fieldElement.dataset.fieldName = fieldName;
            fieldElement.dataset.fieldType = fieldType;
            fieldElement.dataset.x = x;
            fieldElement.dataset.y = y;
            fieldElement.style.left = x + 'px';
            fieldElement.style.top = y + 'px';
            fieldElement.style.position = 'absolute';
            fieldElement.style.zIndex = '10';
            fieldElement.style.pointerEvents = 'all';
            fieldElement.textContent = fieldLabel || fieldName;
            
            document.getElementById('fieldOverlay').appendChild(fieldElement);
            
            // Add to fields array with exact coordinates and current page number
            const newField = {
                id: fieldId,
                field_name: fieldName,
                field_type: fieldType,
                x_position: x,
                y_position: y,
                width: 100,
                height: 25,
                page_number: currentPageNumber, // Use current page instead of hardcoded 1
                font_size: 12,
                font_family: 'Arial',
                font_color: '#000000',
                font_weight: 'normal',
                font_style: 'normal'
            };
            fields.push(newField);
            
            // Make it draggable and clickable
            fieldElement.addEventListener('mousedown', handleFieldMouseDown);
            fieldElement.addEventListener('click', function(e) {
                e.stopPropagation();
                selectField(fieldId);
            });
            
            updateFieldsList();
            
            // Auto-select the newly created field
            selectField(fieldId);
        }
        
        function handleFieldMouseDown(e) {
            selectedField = this;
            isDragging = true;
            
            const rect = this.getBoundingClientRect();
            const containerRect = this.parentElement.getBoundingClientRect();
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;
            
            document.addEventListener('mousemove', handleFieldMouseMove);
            document.addEventListener('mouseup', handleFieldMouseUp);
            
            e.preventDefault();
        }
        
        function handleFieldMouseMove(e) {
            if (!isDragging || !selectedField) return;
            
            const containerRect = selectedField.parentElement.getBoundingClientRect();
            const x = e.clientX - containerRect.left - dragOffset.x;
            const y = e.clientY - containerRect.top - dragOffset.y;
            
            selectedField.style.left = x + 'px';
            selectedField.style.top = y + 'px';
            selectedField.dataset.x = x;
            selectedField.dataset.y = y;
            
            // Update fields array
            const fieldIndex = fields.findIndex(f => f.field_name === selectedField.dataset.fieldName);
            if (fieldIndex !== -1) {
                fields[fieldIndex].x_position = x;
                fields[fieldIndex].y_position = y;
            }
        }
        
        function handleFieldMouseUp() {
            isDragging = false;
            selectedField = null;
            document.removeEventListener('mousemove', handleFieldMouseMove);
            document.removeEventListener('mouseup', handleFieldMouseUp);
            updateFieldsList();
        }
        
        function updateFieldsList() {
            const fieldsList = document.getElementById('fieldsList');
            fieldsList.innerHTML = '';
            
            // Show fields for current page only
            const currentPageFields = fields.filter(field => field.page_number == currentPageNumber);
            
            if (currentPageFields.length === 0) {
                fieldsList.innerHTML = '<p style="color: #bdc3c7; font-size: 12px; text-align: center; margin: 10px 0;">No fields on this page</p>';
                return;
            }
            
            currentPageFields.forEach(field => {
                const fieldItem = document.createElement('div');
                fieldItem.className = 'field-item';
                fieldItem.dataset.fieldId = field.id;
                fieldItem.innerHTML = `
                    <div class="field-label">${field.field_name}</div>
                    <div class="field-coordinates">Position: (${Math.round(field.x_position)}, ${Math.round(field.y_position)}) | Page: ${field.page_number}</div>
                `;
                
                // Add click handler for field selection
                fieldItem.addEventListener('click', function() {
                    selectField(field.id);
                });
                
                fieldsList.appendChild(fieldItem);
            });
            
            // Add summary for all pages
            if (totalPages > 1) {
                const summaryDiv = document.createElement('div');
                summaryDiv.style.cssText = 'margin-top: 15px; padding: 10px; background: #2c3e50; border-radius: 4px; border: 1px solid #34495e;';
                
                let totalFieldsCount = 0;
                let pageBreakdown = '';
                for (let i = 1; i <= totalPages; i++) {
                    const pageFields = fields.filter(f => f.page_number == i);
                    totalFieldsCount += pageFields.length;
                    pageBreakdown += `Page ${i}: ${pageFields.length} fields<br>`;
                }
                
                summaryDiv.innerHTML = `
                    <div style="color: #ecf0f1; font-size: 11px;">
                        <strong>Total: ${totalFieldsCount} fields</strong><br>
                        ${pageBreakdown}
                    </div>
                `;
                fieldsList.appendChild(summaryDiv);
            }
        }
        
        // Save fields
        document.getElementById('saveFieldsBtn').addEventListener('click', async function() {
            try {
                const response = await fetch('../api/template_management_new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'save_template_fields',
                        template_id: templateId,
                        fields: fields
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Template saved successfully!');
                } else {
                    alert('Failed to save template: ' + (data.message || 'Unknown error'));
                }
                
            } catch (error) {
                console.error('Error saving template:', error);
                alert('Failed to save template: ' + error.message);
            }
        });
        
        // Preview button
        document.getElementById('previewBtn').addEventListener('click', function() {
            const previewUrl = `../api/generate_pdf_with_fields.php?document_id=3&template_id=${templateId}`;
            window.open(previewUrl, '_blank');
        });
        
        // Position existing fields on the PDF and apply font styles (multi-page aware)
        function positionExistingFields() {
            const fieldOverlay = document.getElementById('fieldOverlay');
            
            // Clear all existing field elements
            fieldOverlay.innerHTML = '';
            
            // Only show fields for the current page
            const currentPageFields = fields.filter(field => field.page_number == currentPageNumber);
            
            currentPageFields.forEach(fieldData => {
                // Create field element for current page
                const fieldElement = document.createElement('div');
                fieldElement.className = 'field-embedded';
                fieldElement.dataset.fieldId = fieldData.id;
                fieldElement.dataset.fieldName = fieldData.field_name;
                fieldElement.dataset.x = fieldData.x_position;
                fieldElement.dataset.y = fieldData.y_position;
                fieldElement.textContent = fieldData.field_name;
                
                // Position field
                fieldElement.style.left = fieldData.x_position + 'px';
                fieldElement.style.top = fieldData.y_position + 'px';
                fieldElement.style.position = 'absolute';
                fieldElement.style.zIndex = '10';
                fieldElement.style.pointerEvents = 'all';
                
                // Apply font styles
                fieldElement.style.fontSize = (fieldData.font_size || 12) + 'px';
                fieldElement.style.fontFamily = fieldData.font_family || 'Arial';
                fieldElement.style.color = fieldData.font_color || '#000000';
                fieldElement.style.fontWeight = fieldData.font_weight || 'normal';
                fieldElement.style.fontStyle = fieldData.font_style || 'normal';
                
                // Add event handlers
                fieldElement.addEventListener('mousedown', handleFieldMouseDown);
                fieldElement.addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectField(fieldData.id);
                });
                
                fieldOverlay.appendChild(fieldElement);
            });
        }
        
        // Field selection functionality
        function selectField(fieldId) {
            console.log('Selecting field:', fieldId);
            
            // Find field in array
            selectedField = fields.find(f => f.id == fieldId);
            if (!selectedField) {
                console.log('Field not found in array:', fieldId);
                return;
            }
            
            // Find field element on PDF
            selectedFieldElement = document.querySelector(`[data-field-id="${fieldId}"]`);
            console.log('Found field element:', selectedFieldElement);
            
            // Update field list selection
            document.querySelectorAll('.field-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Select the clicked field item
            const fieldListItem = document.querySelector(`.field-item[data-field-id="${fieldId}"]`);
            if (fieldListItem) {
                fieldListItem.classList.add('selected');
            }
            
            // Show font properties panel and update info
            const fontPanel = document.getElementById('fontPropertiesPanel');
            fontPanel.style.display = 'block';
            
            const selectedFieldInfo = document.getElementById('selectedFieldInfo');
            selectedFieldInfo.textContent = `Editing: ${selectedField.field_name}`;
            console.log('Font panel shown');
            
            // Populate font controls with current values
            document.getElementById('fontSizeInput').value = selectedField.font_size || 12;
            document.getElementById('fontFamilySelect').value = selectedField.font_family || 'Arial';
            document.getElementById('fontColorInput').value = selectedField.font_color || '#000000';
            document.getElementById('fontWeightSelect').value = selectedField.font_weight || 'normal';
            document.getElementById('fontStyleSelect').value = selectedField.font_style || 'normal';
            
            // Highlight selected field on PDF
            document.querySelectorAll('.field-embedded').forEach(field => {
                field.classList.remove('selected');
            });
            if (selectedFieldElement) {
                selectedFieldElement.classList.add('selected');
            }
            
            console.log('Field selected successfully:', selectedField);
        }
        
        // Update field font properties
        function updateFieldFont() {
            if (!selectedField || !selectedFieldElement) return;
            
            // Get values from controls
            const fontSize = document.getElementById('fontSizeInput').value;
            const fontFamily = document.getElementById('fontFamilySelect').value;
            const fontColor = document.getElementById('fontColorInput').value;
            const fontWeight = document.getElementById('fontWeightSelect').value;
            const fontStyle = document.getElementById('fontStyleSelect').value;
            
            // Update field object
            selectedField.font_size = parseInt(fontSize);
            selectedField.font_family = fontFamily;
            selectedField.font_color = fontColor;
            selectedField.font_weight = fontWeight;
            selectedField.font_style = fontStyle;
            
            // Apply styles to field element
            selectedFieldElement.style.fontSize = fontSize + 'px';
            selectedFieldElement.style.fontFamily = fontFamily;
            selectedFieldElement.style.color = fontColor;
            selectedFieldElement.style.fontWeight = fontWeight;
            selectedFieldElement.style.fontStyle = fontStyle;
            
            console.log('Updated field font:', selectedField);
        }
        
        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadPDF();
            initializeDragDrop();
            updateFieldsList();
        });
    </script>
</body>
</html>