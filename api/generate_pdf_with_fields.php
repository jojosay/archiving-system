<?php
/**
 * Generate PDF with embedded field data
 * Creates an HTML page that shows the PDF with properly positioned field data
 */

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
    echo '<h1>Missing parameters</h1>';
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
    
    // Function to convert location codes to names
    function getLocationName($code, $type) {
        global $db;
        
        try {
            switch ($type) {
                case 'region':
                    $queries_to_try = [
                        "SELECT region_name as name FROM regions WHERE region_code = ?",
                        "SELECT region_name as name FROM regions WHERE id = ?"
                    ];
                    break;
                    
                case 'province':
                    $queries_to_try = [
                        "SELECT province_name as name FROM provinces WHERE id = ?",
                        "SELECT province_name as name FROM provinces WHERE province_code = ?"
                    ];
                    break;
                    
                case 'city':
                case 'citymun':
                    $queries_to_try = [
                        "SELECT citymun_name as name FROM citymun WHERE id = ?",
                        "SELECT citymun_name as name FROM citymun WHERE citymun_code = ?"
                    ];
                    break;
                    
                case 'barangay':
                    $queries_to_try = [
                        "SELECT barangay_name as name FROM barangays WHERE id = ?",
                        "SELECT barangay_name as name FROM barangays WHERE barangay_code = ?"
                    ];
                    break;
                    
                default:
                    return $code;
            }
            
            // Try each query until one returns a result
            foreach ($queries_to_try as $query) {
                try {
                    $stmt = $db->prepare($query);
                    $stmt->execute([$code]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && !empty($result['name'])) {
                        return $result['name'];
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            // If still no result, return formatted fallback
            return ucfirst($type) . " (Code: " . $code . ")";
            
        } catch (Exception $e) {
            return $code;
        }
    }
    
    // Get template fields from the correct table
    $template_fields = [];
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
        // If template_fields doesn't exist, try pdf_template_fields
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
        } catch (Exception $e2) {
            $template_fields = [];
        }
    }
    
    // Map document data to template fields
    $pdf_data = [];
    foreach ($template_fields as $field) {
        $field_name = $field['field_name'];
        $field_name_lower = strtolower($field_name);
        
        // Auto-map common system fields
        if (strpos($field_name_lower, 'title') !== false || $field_name_lower === 'document_title') {
            $pdf_data[$field_name] = $document['title'];
        } elseif (strpos($field_name_lower, 'type') !== false || $field_name_lower === 'document_type') {
            $pdf_data[$field_name] = $document['document_type_name'];
        } elseif (strpos($field_name_lower, 'date') !== false || $field_name_lower === 'created_date') {
            $pdf_data[$field_name] = date('Y-m-d', strtotime($document['created_at']));
        } elseif (strpos($field_name_lower, 'author') !== false || strpos($field_name_lower, 'uploaded') !== false) {
            $pdf_data[$field_name] = $document['uploaded_by_username'];
        } else {
            // Handle cascading dropdown fields (e.g., address_region, address_province)
            $matched = false;
            
            // Check for direct field match first (address_region, address_province, etc.)
            foreach ($metadata as $meta_key => $meta_value) {
                $meta_key_lower = strtolower($meta_key);
                
                // Direct match
                if ($meta_key_lower === $field_name_lower) {
                    $location_code = is_array($meta_value) ? $meta_value['value'] : $meta_value;
                    
                    // Convert location code to readable name
                    if (strpos($field_name_lower, 'region') !== false) {
                        $pdf_data[$field_name] = getLocationName($location_code, 'region');
                    } elseif (strpos($field_name_lower, 'province') !== false) {
                        $pdf_data[$field_name] = getLocationName($location_code, 'province');
                    } elseif (strpos($field_name_lower, 'city') !== false || strpos($field_name_lower, 'citymun') !== false || $field_name_lower === 'address_city') {
                        $pdf_data[$field_name] = getLocationName($location_code, 'city');
                    } elseif (strpos($field_name_lower, 'barangay') !== false) {
                        $pdf_data[$field_name] = getLocationName($location_code, 'barangay');
                    } else {
                        $pdf_data[$field_name] = $location_code;
                    }
                    
                    $matched = true;
                    break;
                }
                
                // Special case: address_city should match address_citymun
                if ($field_name_lower === 'address_city' && $meta_key_lower === 'address_citymun') {
                    $location_code = is_array($meta_value) ? $meta_value['value'] : $meta_value;
                    $pdf_data[$field_name] = getLocationName($location_code, 'city');
                    $matched = true;
                    break;
                }
            }
            
            // If no direct match, try cascading dropdown pattern: [base_field]_[level]
            if (!$matched && preg_match('/^(.+)_(region|province|city|municipality|barangay)$/', $field_name_lower, $matches)) {
                $base_field = $matches[1];
                $level = $matches[2];
                
                // Look for the base field in metadata
                foreach ($metadata as $meta_key => $meta_value) {
                    $meta_key_lower = strtolower($meta_key);
                    if ($meta_key_lower === $base_field || strpos($meta_key_lower, $base_field) !== false) {
                        $location_data = is_array($meta_value) ? $meta_value['value'] : $meta_value;
                        
                        // Parse location data if it's JSON
                        if (is_string($location_data) && (strpos($location_data, '{') === 0 || strpos($location_data, '[') === 0)) {
                            $parsed_location = json_decode($location_data, true);
                            if ($parsed_location && isset($parsed_location[$level])) {
                                $pdf_data[$field_name] = getLocationName($parsed_location[$level], $level);
                                $matched = true;
                                break;
                            }
                        }
                        
                        // If not JSON, try to extract from comma-separated format
                        if (!$matched && is_string($location_data)) {
                            $location_parts = array_map('trim', explode(',', $location_data));
                            $level_index = array_search($level, ['region', 'province', 'city', 'barangay']);
                            if ($level_index !== false && isset($location_parts[$level_index])) {
                                $pdf_data[$field_name] = getLocationName($location_parts[$level_index], $level);
                                $matched = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            // If not a cascading field or no match found, try direct metadata matching
            if (!$matched) {
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
            }
            
            // If still no match, set empty value
            if (!$matched) {
                $pdf_data[$field_name] = '';
            }
        }
    }
    
} catch (Exception $e) {
    echo '<h1>Error: ' . htmlspecialchars($e->getMessage()) . '</h1>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($document['title']); ?> - Filled Form</title>
    <style>
        body { 
            margin: 0; 
            padding: 20px; 
            font-family: Arial, sans-serif; 
            background: #f0f0f0;
        }
        .pdf-container {
            position: relative;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: visible;
            margin: 0 auto;
            width: fit-content;
            max-width: 95vw;
        }
        .pdf-background {
            position: relative;
            background: white;
            overflow: visible;
            width: fit-content;
            height: fit-content;
        }
        .pdf-iframe {
            width: 100%;
            height: 100%;
            border: none;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        .field-embedded {
            position: absolute;
            background: transparent;
            color: black;
            padding: 2px 4px;
            font-size: 12px;
            font-weight: normal;
            font-family: Arial, sans-serif;
            z-index: 10;
            pointer-events: none;
            line-height: 1.2;
            white-space: nowrap;
            overflow: visible;
        }
        .document-info {
            background: #e8f5e8;
            padding: 20px;
            border-bottom: 3px solid #27ae60;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            z-index: 1000;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .print-btn:hover {
            background: #229954;
        }
        .field-list {
            background: #f8f9fa;
            padding: 20px;
            margin-top: 20px;
        }
        .field-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .field-name {
            font-weight: bold;
            color: #495057;
        }
        .field-value {
            color: #007bff;
        }
        @media print {
            .print-btn, .document-info, .field-list, #pageNavigation { display: none !important; }
            body { background: white; }
            .pdf-container { box-shadow: none; }
        }
        
        /* Page navigation styles */
        #pageNavigation button:hover {
            background: #229954 !important;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
        
        #pageNavigation button:disabled {
            background: #6c757d !important;
            cursor: not-allowed !important;
            opacity: 0.6;
        }
        
        #pageNavigation {
            user-select: none;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print PDF</button>
    
    <div class="pdf-container">
        <div class="document-info">
            <h2>📄 <?php echo htmlspecialchars($document['title']); ?></h2>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($document['document_type_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($document['created_at'])); ?></p>
            <p><strong>Template:</strong> <?php echo htmlspecialchars($template['name']); ?></p>
            <p><strong>Fields Embedded:</strong> <?php echo count(array_filter($pdf_data)); ?> of <?php echo count($template_fields); ?></p>
        </div>
        
        <div class="pdf-background">
            <!-- PDF.js Canvas -->
            <canvas id="pdfCanvas" style="position: relative; display: block; z-index: 1;"></canvas>
            
            <!-- Embedded fields overlay (multi-page aware) -->
            <div id="fieldOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 10;">
                <?php foreach ($template_fields as $field): ?>
                    <?php $value = $pdf_data[$field['field_name']] ?? ''; ?>
                    <?php if (!empty($value)): ?>
                        <?php 
                        // Store original coordinates and page number for JavaScript scaling
                        $x = $field['x_position'];
                        $y = $field['y_position'];
                        $page_num = $field['page_number'] ?? 1; // Default to page 1 for backward compatibility
                        ?>
                        <div class="field-embedded" 
                             data-x="<?php echo $x; ?>" 
                             data-y="<?php echo $y; ?>" 
                             data-page-number="<?php echo $page_num; ?>"
                             style="position: absolute; left: <?php echo $x; ?>px; top: <?php echo $y; ?>px; display: none;">
                            <?php echo htmlspecialchars($value); ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="field-list">
            <h3>📋 Field Data Summary</h3>
            <?php foreach ($template_fields as $field): ?>
                <?php $value = $pdf_data[$field['field_name']] ?? ''; ?>
                <div class="field-item">
                    <span class="field-name"><?php echo htmlspecialchars($field['field_name']); ?></span>
                    <span class="field-value"><?php echo htmlspecialchars($value ?: '(empty)'); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Include PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        // Configure PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
        
        console.log('PDF with embedded fields loaded');
        console.log('Document ID: <?php echo $document_id; ?>');
        console.log('Template ID: <?php echo $template_id; ?>');
        console.log('Fields embedded: <?php echo count(array_filter($pdf_data)); ?>');
        
        // Debug: Log all template fields with their page numbers
        console.log('Template fields from database:');
        <?php foreach ($template_fields as $field): ?>
            console.log('Field: <?php echo $field['field_name']; ?>, Page: <?php echo $field['page_number'] ?? 1; ?>, Position: (<?php echo $field['x_position']; ?>, <?php echo $field['y_position']; ?>)');
        <?php endforeach; ?>
        
        // Load and render PDF using PDF.js (same as Template Builder)
        // Multi-page PDF support - preserves existing single-page functionality
        let currentPDF = null;
        let currentPageNumber = 1;
        let totalPages = 1;
        
        async function loadPDF() {
            try {
                const pdfUrl = 'serve_pdf_direct.php?file=<?php echo urlencode($template['filename']); ?>';
                currentPDF = await pdfjsLib.getDocument(pdfUrl).promise;
                totalPages = currentPDF.numPages;
                
                // Add page navigation if multi-page
                if (totalPages > 1) {
                    addPageNavigation();
                }
                
                // Load the first page (preserves existing behavior)
                await renderPage(currentPageNumber);
                
            } catch (error) {
                console.error('Error loading PDF:', error);
                // Fallback to iframe if PDF.js fails
                const canvas = document.getElementById('pdfCanvas');
                canvas.style.display = 'none';
                const iframe = document.createElement('iframe');
                iframe.className = 'pdf-iframe';
                iframe.src = 'serve_pdf_direct.php?file=<?php echo urlencode($template['filename']); ?>';
                canvas.parentElement.appendChild(iframe);
            }
        }
        
        // Render specific page
        async function renderPage(pageNum) {
            if (!currentPDF || pageNum < 1 || pageNum > totalPages) return;
            
            const page = await currentPDF.getPage(pageNum);
            const canvas = document.getElementById('pdfCanvas');
            const context = canvas.getContext('2d');
            
            // Use identical scaling to template_builder_standalone.php for perfect positioning alignment
            const viewport = page.getViewport({ scale: 1.0 });
            const container = canvas.parentElement;
            const forcedContainerWidth = 864; // Match template builder container width
            const maxWidth = Math.min(forcedContainerWidth - 40, 800);
            const scale = maxWidth / viewport.width;
            const scaledViewport = page.getViewport({ scale: scale });
            
            // Set canvas dimensions to match PDF exactly
            canvas.width = scaledViewport.width;
            canvas.height = scaledViewport.height;
            canvas.style.width = scaledViewport.width + 'px';
            canvas.style.height = scaledViewport.height + 'px';
            
            // Update container to match canvas size (same as template builder)
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
            
            // Update field overlay dimensions to match canvas
            const fieldOverlay = document.getElementById('fieldOverlay');
            if (fieldOverlay) {
                fieldOverlay.style.width = scaledViewport.width + 'px';
                fieldOverlay.style.height = scaledViewport.height + 'px';
            }
            
            // Update field positions for current page only
            updateFieldPositionsForPage(pageNum, scale);
            
            console.log(`Rendered page ${pageNum}, showing fields for page ${pageNum}`);
            
            // Update page navigation
            updatePageNavigation();
        }
        
        // Add page navigation controls
        function addPageNavigation() {
            const pdfContainer = document.querySelector('.pdf-container');
            
            // Create navigation with better positioning
            const navHtml = `
                <div id="pageNavigation" style="
                    position: fixed; 
                    top: 80px; 
                    left: 50%; 
                    transform: translateX(-50%); 
                    z-index: 1000; 
                    background: rgba(0,0,0,0.9); 
                    color: white; 
                    padding: 12px 20px; 
                    border-radius: 8px; 
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    font-family: Arial, sans-serif;
                ">
                    <button onclick="previousPage()" id="prevBtn" style="
                        background: #27ae60; 
                        color: white; 
                        border: none; 
                        padding: 10px 16px; 
                        margin-right: 15px; 
                        border-radius: 6px; 
                        cursor: pointer;
                        font-weight: bold;
                    ">&lt; Previous</button>
                    <span id="pageInfo" style="margin: 0 15px; font-weight: bold; font-size: 16px;">Page 1 of ${totalPages}</span>
                    <button onclick="nextPage()" id="nextBtn" style="
                        background: #27ae60; 
                        color: white; 
                        border: none; 
                        padding: 10px 16px; 
                        margin-left: 15px; 
                        border-radius: 6px; 
                        cursor: pointer;
                        font-weight: bold;
                    ">Next &gt;</button>
                </div>
            `;
            
            // Insert navigation at the top of the page
            document.body.insertAdjacentHTML('afterbegin', navHtml);
            
            console.log('Page navigation added for', totalPages, 'pages');
        }
        
        // Update page navigation state
        function updatePageNavigation() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');
            
            if (prevBtn) prevBtn.disabled = currentPageNumber <= 1;
            if (nextBtn) nextBtn.disabled = currentPageNumber >= totalPages;
            if (pageInfo) pageInfo.textContent = `Page ${currentPageNumber} of ${totalPages}`;
        }
        
        // Page navigation functions
        function previousPage() {
            if (currentPageNumber > 1) {
                currentPageNumber--;
                console.log(`Navigating to page ${currentPageNumber}`);
                renderPage(currentPageNumber);
            }
        }
        
        function nextPage() {
            if (currentPageNumber < totalPages) {
                currentPageNumber++;
                console.log(`Navigating to page ${currentPageNumber}`);
                renderPage(currentPageNumber);
            }
        }
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                e.preventDefault();
                previousPage();
            } else if (e.key === 'ArrowRight' || e.key === 'PageDown') {
                e.preventDefault();
                nextPage();
            }
        });
        
        // Function to update field positions for specific page (multi-page aware)
        function updateFieldPositionsForPage(pageNum, scale) {
            const allFields = document.querySelectorAll('.field-embedded');
            console.log(`Total fields found: ${allFields.length}`);
            
            let fieldsShown = 0;
            
            // Hide all fields first
            allFields.forEach(field => field.style.display = 'none');
            
            // Show and position fields for current page only
            allFields.forEach(fieldElement => {
                const fieldPageNum = parseInt(fieldElement.dataset.pageNumber) || 1; // Default to page 1 for backward compatibility
                console.log(`Field page: ${fieldPageNum}, Current page: ${pageNum}, Field text: ${fieldElement.textContent}`);
                
                if (fieldPageNum === pageNum) {
                    const x = parseFloat(fieldElement.dataset.x);
                    const y = parseFloat(fieldElement.dataset.y);
                    
                    // Position fields exactly as template builder does (preserves existing logic)
                    fieldElement.style.left = x + 'px';
                    fieldElement.style.top = y + 'px';
                    fieldElement.style.position = 'absolute';
                    fieldElement.style.zIndex = '10';
                    fieldElement.style.pointerEvents = 'none';
                    fieldElement.style.display = 'block'; // Show field for current page
                    fieldsShown++;
                    console.log(`Showing field: ${fieldElement.textContent} at (${x}, ${y})`);
                }
            });
            
            console.log(`Fields shown on page ${pageNum}: ${fieldsShown}`);
        }
        
        // Legacy function for backward compatibility
        function updateFieldPositions(scale) {
            updateFieldPositionsForPage(currentPageNumber, scale);
        }
        
        // Load PDF when page is ready
        document.addEventListener('DOMContentLoaded', loadPDF);
    </script>
</body>
</html>