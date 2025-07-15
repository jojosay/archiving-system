<?php
// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/document_type_manager.php';
require_once 'includes/document_manager.php';
require_once 'includes/template_manager.php';

$docTypeManager = new DocumentTypeManager($database);
$documentManager = new DocumentManager($database);
$templateManager = new TemplateManager($database);
$document_types = $docTypeManager->getAllDocumentTypes(true);
$templates = $templateManager->getAllTemplates(true);

$message = '';
$message_type = '';

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    $document_type_id = intval($_POST['document_type_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $metadata = $_POST['metadata'] ?? [];
    $uploaded_by = $_SESSION['user_id'] ?? null;
    
    // Find the file field in the document type fields
    $docTypeManager = new DocumentTypeManager($database);
    $fields = $docTypeManager->getDocumentTypeFields($document_type_id);
    $file_field_name = null;
    foreach ($fields as $field) {
        if ($field['field_type'] === 'file') {
            $file_field_name = $field['field_name'];
            break;
        }
    }

    if (empty($title)) {
        $message = 'Document title is required.';
        $message_type = 'error';
    } elseif ($document_type_id <= 0) {
        $message = 'Please select a document type.';
        $message_type = 'error';
    } elseif ($file_field_name && (!isset($_FILES['metadata']['name'][$file_field_name]) || $_FILES['metadata']['error'][$file_field_name] !== UPLOAD_ERR_OK)) {
        $message = 'Please select a valid document file for the field "' . $file_field_name . '"';
        $message_type = 'error';
    } else {
        // Process document upload
        $result = $documentManager->createDocument($document_type_id, $title, $_FILES, $metadata, $uploaded_by);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        
        // Clear form data on success
        if ($result['success']) {
            $_POST = [];
        }
    }
}

renderPageStart('Upload Document');
?>

<div style="max-width: 800px; margin: 0 auto; padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e9ecef;">
        <h1 style="font-size: 2rem; font-weight: 600; color: #2c3e50; margin: 0;">Upload Document</h1>
        <a href="?page=dashboard" style="color: #6c757d; text-decoration: none;">&lt; Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div style="padding: 1rem; border-radius: 4px; margin-bottom: 1rem; <?php echo $message_type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : ($message_type === 'error' ? 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' : 'background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;'); ?>">
            <?php echo htmlspecialchars($message); ?>
            <?php if ($message_type === 'success'): ?>
                <div style="margin-top: 1rem;">
                    <a href="?page=document_archive" style="display: inline-block; padding: 0.5rem 1rem; background: #28a745; color: white; text-decoration: none; border-radius: 4px; font-size: 0.9rem;">Browse Archive</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="action" value="upload_document">
            
            <div style="margin-bottom: 1.5rem;">
                <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Document Title *</label>
                <input type="text" id="title" name="title" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label for="document_type_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Document Type *</label>
                <select id="document_type_id" name="document_type_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" onchange="loadDocumentTypeFields()">
                    <option value="">Select document type</option>
                    <?php foreach ($document_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo (isset($_POST['document_type_id']) && $_POST['document_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Template Selection Section -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Start with Template (Optional)</label>
                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem; background: #f8f9fa;">
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                        <select id="template_filter" style="flex: 1; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;" onchange="filterTemplates()">
                            <option value="">All Categories</option>
                            <?php 
                            $categories = [];
                            foreach ($templates as $template) {
                                if ($template['category'] && !in_array($template['category'], $categories)) {
                                    $categories[] = $template['category'];
                                }
                            }
                            foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="?page=template_gallery" target="_blank" style="padding: 0.5rem 1rem; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Browse All</a>
                    </div>
                    <div id="template_selection" style="max-height: 200px; overflow-y: auto;">
                        <?php if (empty($templates)): ?>
                            <p style="text-align: center; color: #6c757d; margin: 1rem 0;">No templates available</p>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 0.5rem;">
                                <?php foreach ($templates as $template): ?>
                                    <div class="template-option" data-category="<?php echo htmlspecialchars($template['category'] ?? ''); ?>" style="border: 1px solid #dee2e6; border-radius: 4px; padding: 0.75rem; cursor: pointer; transition: all 0.2s;" onclick="selectTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>')">
                                        <div style="font-weight: 500; font-size: 0.9rem; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($template['name']); ?></div>
                                        <div style="font-size: 0.8rem; color: #6c757d;"><?php echo strtoupper($template['file_type']); ?> - <?php echo htmlspecialchars($template['category'] ?? 'Uncategorized'); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div id="selected_template" style="margin-top: 1rem; padding: 0.75rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; display: none;">
                        <strong>Selected Template:</strong> <span id="selected_template_name"></span>
                        <button type="button" onclick="clearTemplate()" style="float: right; background: none; border: none; color: #721c24; cursor: pointer;">X</button>
                        <input type="hidden" id="selected_template_id" name="template_id" value="">
                    </div>
                </div>
            </div>
            
            <!-- Dynamic metadata fields will be loaded here -->
            <div id="metadata-fields" style="margin-bottom: 1.5rem;">
                <p style="color: #6c757d; text-align: center; padding: 1rem;">Select a document type to see additional fields</p>
            </div>
            
            <button type="submit" style="padding: 0.75rem 2rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">Upload Document</button>
        </form>
    </div>
</div>

<script>
// Load document type fields dynamically
function loadDocumentTypeFields() {
    const typeId = document.getElementById('document_type_id').value;
    const metadataContainer = document.getElementById('metadata-fields');
    
    if (!typeId) {
        metadataContainer.innerHTML = '<p style="color: #6c757d; text-align: center; padding: 1rem;">Select a document type to see additional fields</p>';
        return;
    }
    
    metadataContainer.innerHTML = '<p style="color: #6c757d; text-align: center; padding: 1rem;">Loading fields...</p>';
    
    // Fetch fields for the selected document type
    fetch(`api/document_type_fields.php?type_id=${typeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.fields) {
                displayMetadataFields(data.fields);
            } else {
                metadataContainer.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 1rem;">Error loading fields: ' + (data.message || 'Unknown error') + '</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching fields:', error);
            metadataContainer.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 1rem;">Error loading fields. Please try again.</p>';
        });
}

function displayMetadataFields(fields) {
    const container = document.getElementById('metadata-fields');
    
    if (!fields || fields.length === 0) {
        container.innerHTML = '<p style="color: #6c757d; text-align: center; padding: 1rem;">No additional fields required for this document type.</p>';
        return;
    }
    
    let html = '<h4 style="margin-bottom: 1rem; color: #495057;">Additional Information</h4>';
    
    fields.forEach(field => {
        html += generateFieldHTML(field);
    });
    
    container.innerHTML = html;
    
    // Initialize any special field types
    fields.forEach(field => {
        if (field.field_type === 'reference') {
            initializeReferenceField(field.field_name);
        } else if (field.field_type === 'cascading_dropdown') {
            initializeCascadingDropdown(field.field_name);
        }
    });
}

function generateFieldHTML(field) {
    const fieldName = field.field_name;
    const fieldLabel = field.field_label;
    const fieldType = field.field_type;
    const isRequired = field.is_required;
    const fieldOptions = field.field_options;
    
    const requiredAttr = isRequired ? 'required' : '';
    const requiredMark = isRequired ? ' <span style="color: red;">*</span>' : '';
    
    let html = '<div style="margin-bottom: 1rem;">';
    html += `<label for="metadata_${fieldName}" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">${fieldLabel}${requiredMark}</label>`;
    
    switch (fieldType) {
        case 'text':
            html += `<input type="text" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
            break;
            
        case 'number':
            html += `<input type="number" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
            break;
            
        case 'date':
            html += `<input type="date" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
            break;
            
        case 'time':
            html += `<input type="time" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
            break;
            
        case 'textarea':
            html += `<textarea id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; height: 100px; resize: vertical;"></textarea>`;
            break;
            
        case 'dropdown':
            html += `<select id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
            html += '<option value="">Select an option</option>';
            
            if (fieldOptions) {
                try {
                    const options = JSON.parse(fieldOptions);
                    options.forEach(option => {
                        html += `<option value="${option}">${option}</option>`;
                    });
                } catch (e) {
                    console.error('Error parsing field options:', e);
                }
            }
            html += '</select>';
            break;
            
        case 'cascading_dropdown':
            html += generateCascadingDropdownHTML(fieldName, requiredAttr);
            break;
            
        case 'reference':
            html += generateReferenceFieldHTML(fieldName, requiredAttr);
            break;

        case 'file':
            html += `
                <input type="file" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="display: none;" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">
                <div id="metadata_${fieldName}_display" style="border: 1px solid #ced4da; border-radius: 4px; padding: 1rem; margin-bottom: 0.5rem; min-height: 60px; background: #f8f9fa;">
                    <div id="metadata_${fieldName}_placeholder" style="color: #6c757d; text-align: center; padding: 1rem;">No file selected</div>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" onclick="selectDocument('${fieldName}')" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Select Document</button>
                    <button type="button" onclick="previewDocument('${fieldName}')" id="metadata_${fieldName}_preview_btn" style="padding: 0.5rem 1rem; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; display: none;">Preview</button>
                    <button type="button" onclick="clearFileSelection('${fieldName}')" style="padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Clear</button>
                </div>
            `;
            break;
            
        default:
            html += `<input type="text" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
    }
    
    html += '</div>';
    return html;
}

function generateCascadingDropdownHTML(fieldName, requiredAttr) {
    // Default hierarchy levels - this should come from field configuration
    const levels = ['regions', 'provinces', 'citymun', 'barangays'];
    const labels = ['Region', 'Province', 'City/Municipality', 'Barangay'];
    
    let html = '';
    
    // Generate dropdowns for each level
    levels.forEach((level, index) => {
        const label = labels[index];
        const selectId = `metadata_${fieldName}_${level}`;
        
        html += `
            <div style="margin-bottom: 0.5rem;">
                <label for="${selectId}" style="font-size: 0.9rem; color: #6C757D; margin-bottom: 0.25rem; display: block;">${label}</label>
                <select id="${selectId}" class="cascading-dropdown" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; margin-bottom: 0.5rem;">
                    <option value="">Select ${label}</option>
                </select>
            </div>
        `;
    });
    
    // Hidden field to store the final selected values
    html += `<input type="hidden" id="metadata_${fieldName}_data" name="metadata[${fieldName}]" ${requiredAttr}>`;
    
    return html;
}

function generateReferenceFieldHTML(fieldName, requiredAttr) {
    return `
        <input type="hidden" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr}>
        <div id="metadata_${fieldName}_display" style="border: 1px solid #ddd; border-radius: 4px; padding: 1rem; margin-bottom: 0.5rem; min-height: 60px; background: #f8f9fa;">
            <div style="color: #6c757d; text-align: center; padding: 1rem;">No image selected</div>
        </div>
        <button type="button" class="select-reference-btn" data-field-id="metadata_${fieldName}" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem;">Select Image</button>
        <button type="button" onclick="clearReferenceSelection('metadata_${fieldName}')" style="padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Clear</button>
    `;
}

function initializeCascadingDropdown(fieldName) {
    // Initialize cascading dropdown with default levels
    const levels = ['regions', 'provinces', 'citymun', 'barangays'];
    const fullFieldName = `metadata_${fieldName}`;
    
    console.log('Initializing cascading dropdown for field:', fullFieldName);
    
    // Use the global function from cascading_dropdown.js
    setTimeout(() => {
        if (typeof initCascadingDropdown === 'function') {
            initCascadingDropdown(fullFieldName, levels);
        } else {
            console.error('initCascadingDropdown function not found');
        }
    }, 100);
}

function initializeReferenceField(fieldName) {
    const button = document.querySelector(`[data-field-id="metadata_${fieldName}"]`);
    if (button) {
        button.addEventListener('click', function() {
            openReferenceSelector(`metadata_${fieldName}`);
        });
    }
}

// File handling functions
function selectDocument(fieldName) {
    const fileInput = document.getElementById('metadata_' + fieldName);
    fileInput.click();
}

function clearFileSelection(fieldName) {
    const fileInput = document.getElementById('metadata_' + fieldName);
    const display = document.getElementById('metadata_' + fieldName + '_display');
    const previewBtn = document.getElementById('metadata_' + fieldName + '_preview_btn');
    
    // Clear the file input
    fileInput.value = '';
    
    // Update display
    display.innerHTML = '<div id="metadata_' + fieldName + '_placeholder" style="color: #6c757d; text-align: center; padding: 1rem;">No file selected</div>';
    
    // Hide preview button
    if (previewBtn) {
        previewBtn.style.display = 'none';
    }
}

function previewDocument(fieldName, filePath) {
    if (!filePath) {
        const fileInput = document.getElementById('metadata_' + fieldName);
        if (fileInput.files && fileInput.files[0]) {
            const file = fileInput.files[0];
            const fileURL = URL.createObjectURL(file);
            openPreviewWindow(fileURL, file.name);
        } else {
            alert('No file selected for preview');
        }
    } else {
        // Preview existing file through secure endpoint
        const fileURL = 'api/serve_file.php?file=' + encodeURIComponent(filePath);
        openPreviewWindow(fileURL, filePath.split('/').pop());
    }
}

function openPreviewWindow(fileURL, fileName) {
    const fileExtension = fileName.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
        // Image preview with error handling
        const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
        previewWindow.document.write(`
            <html>
                <head><title>Preview: ${fileName}</title></head>
                <body style="margin: 0; padding: 20px; text-align: center; background: #f5f5f5;">
                    <h3>${fileName}</h3>
                    <div id="imageContainer">
                        <img src="${fileURL}" 
                             style="max-width: 100%; max-height: 80vh; border: 1px solid #ddd; border-radius: 4px;"
                             onload="document.getElementById('errorMsg').style.display='none';"
                             onerror="document.getElementById('errorMsg').style.display='block'; this.style.display='none';">
                        <div id="errorMsg" style="display: none; color: #dc3545; padding: 2rem; border: 1px solid #dc3545; border-radius: 4px; background: #f8d7da;">
                            <h4>Unable to preview image</h4>
                            <p>The file may not exist or may not be accessible.</p>
                            <p><strong>File URL:</strong> ${fileURL}</p>
                            <button onclick="window.location.href='${fileURL}'" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Try Direct Download</button>
                        </div>
                    </div>
                </body>
            </html>
        `);
    } else if (['pdf'].includes(fileExtension)) {
        // PDF preview with error handling
        const previewWindow = window.open('', '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
        previewWindow.document.write(`
            <html>
                <head><title>Preview: ${fileName}</title></head>
                <body style="margin: 0; padding: 20px;">
                    <h3>${fileName}</h3>
                    <div id="pdfContainer">
                        <iframe src="${fileURL}" width="100%" height="600px" style="border: 1px solid #ddd;"
                                onload="document.getElementById('pdfErrorMsg').style.display='none';"
                                onerror="document.getElementById('pdfErrorMsg').style.display='block';">
                        </iframe>
                        <div id="pdfErrorMsg" style="display: none; color: #dc3545; padding: 2rem; border: 1px solid #dc3545; border-radius: 4px; background: #f8d7da; margin-top: 1rem;">
                            <h4>Unable to preview PDF</h4>
                            <p>The file may not exist or your browser may not support PDF preview.</p>
                            <p><strong>File URL:</strong> ${fileURL}</p>
                            <button onclick="window.location.href='${fileURL}'" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Try Direct Download</button>
                        </div>
                    </div>
                </body>
            </html>
        `);
    } else {
        // For other file types, try to open directly
        window.open(fileURL, '_blank');
    }
}

// Add event listeners for file inputs when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Function to add file input listeners
    function addFileInputListeners() {
        const fileInputs = document.querySelectorAll('input[type="file"][id^="metadata_"]');
        fileInputs.forEach(function(input) {
            // Remove existing listener to avoid duplicates
            input.removeEventListener('change', handleFileChange);
            // Add new listener
            input.addEventListener('change', handleFileChange);
        });
    }
    
    function handleFileChange(event) {
        const fieldName = event.target.id.replace('metadata_', '');
        const display = document.getElementById('metadata_' + fieldName + '_display');
        const previewBtn = document.getElementById('metadata_' + fieldName + '_preview_btn');
        
        if (event.target.files && event.target.files[0]) {
            const file = event.target.files[0];
            const fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB
            
            // Update display
            display.innerHTML = `
                <div id="metadata_${fieldName}_file_info">
                    <p style="margin: 0; font-weight: 500; color: #495057;">File: ${file.name}</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; color: #6c757d;">Size: ${fileSize} MB | Selected for upload</p>
                </div>
            `;
            
            // Show preview button
            if (previewBtn) {
                previewBtn.style.display = 'inline-block';
            }
        }
    }
    
    // Initial setup
    addFileInputListeners();
    
    // Re-add listeners when new fields are loaded
    const originalDisplayMetadataFields = window.displayMetadataFields;
    window.displayMetadataFields = function(fields) {
        originalDisplayMetadataFields(fields);
        // Add listeners to new file inputs
        setTimeout(addFileInputListeners, 100);
    }
});

// Template selection functions
function filterTemplates() {
    const filter = document.getElementById('template_filter').value;
    const templateOptions = document.querySelectorAll('.template-option');
    
    templateOptions.forEach(option => {
        const category = option.getAttribute('data-category');
        if (!filter || category === filter) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

function selectTemplate(templateId, templateName) {
    // Update hidden input
    document.getElementById('selected_template_id').value = templateId;
    document.getElementById('selected_template_name').textContent = templateName;
    
    // Show selected template section
    document.getElementById('selected_template').style.display = 'block';
    
    // Highlight selected template
    document.querySelectorAll('.template-option').forEach(option => {
        option.style.background = '';
        option.style.borderColor = '#dee2e6';
    });
    
    event.target.closest('.template-option').style.background = '#e7f3ff';
    event.target.closest('.template-option').style.borderColor = '#007bff';
}

function clearTemplate() {
    document.getElementById('selected_template_id').value = '';
    document.getElementById('selected_template').style.display = 'none';
    
    // Remove highlighting
    document.querySelectorAll('.template-option').forEach(option => {
        option.style.background = '';
        option.style.borderColor = '#dee2e6';
    });
}
</script>

<?php renderPageEnd(); ?>