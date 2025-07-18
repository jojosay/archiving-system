<?php
// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/document_type_manager.php';
require_once 'includes/document_manager.php';

$docTypeManager = new DocumentTypeManager($database);
$documentManager = new DocumentManager($database);
$document_types = $docTypeManager->getAllDocumentTypes(true);

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

    <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_document">
            
            <div style="margin-bottom: 1.5rem;">
                <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Document Title *</label>
                <input type="text" id="title" name="title" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label for="document_type_id" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Document Type *</label>
                <select id="document_type_id" name="document_type_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" onchange="loadDocumentTypeFields()">
                    <option value="">Select Document Type</option>
                    <?php foreach ($document_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo (isset($_POST['document_type_id']) && $_POST['document_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Enhanced PDF Upload Notice -->
            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #0c5460;">Enhanced PDF Support</h4>
                <p style="margin: 0; font-size: 0.9rem; color: #0c5460;">
                    PDF files now include: metadata extraction, enhanced validation, secure viewing, and support for files up to 50MB.
                </p>
            </div>
            
            <!-- Dynamic metadata fields will be loaded here -->
            <div id="metadata-fields" style="margin-bottom: 1.5rem;">
                <p style="color: #6c757d; text-align: center; padding: 1rem;">Select a document type to see additional fields</p>
            </div>
            
            <button type="submit" style="padding: 0.75rem 2rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">Upload Document</button>
        </form>
    </div>
</div>

<!-- Include reference selector and cascading dropdown scripts -->
<!-- Scripts are already loaded in layout.php -->

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
    fetch(`api/document_type_fields.php?action=get_type_fields&document_type_id=${typeId}`)
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
    
    if (fields.length === 0) {
        container.innerHTML = '<p style="color: #6c757d; text-align: center; padding: 1rem;">No additional fields required for this document type</p>';
        return;
    }
    
    let html = '<h4 style="margin-bottom: 1rem; color: #2c3e50;">Additional Information</h4>';
    
    fields.forEach(field => {
        html += generateFieldHTML(field);
    });
    
    container.innerHTML = html;
    
    // Initialize special field types after DOM update
    setTimeout(() => {
        fields.forEach(field => {
            if (field.field_type === 'cascading_dropdown') {
                console.log('Initializing cascading dropdown for:', field.field_name);
                if (typeof initCascadingDropdown === 'function') {
                    const levels = ['region', 'province', 'citymun', 'barangay'];
                    initCascadingDropdown(field.field_name, levels);
                }
            } else if (field.field_type === 'reference') {
                console.log('Initializing reference field for:', field.field_name);
                initializeReferenceButtons();
            }
        });
    }, 100);
}

function generateFieldHTML(field) {
    const fieldName = field.field_name;
    const fieldLabel = field.field_label || fieldName;
    const isRequired = field.is_required;
    const fieldType = field.field_type;
    const requiredAttr = isRequired ? 'required' : '';
    const requiredMark = isRequired ? ' *' : '';
    
    let html = `<div style="margin-bottom: 1.5rem;">
        <label for="metadata_${fieldName}" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">${fieldLabel}${requiredMark}</label>`;
    
    switch (fieldType) {
        case 'text':
            html += `<input type="text" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
            break;
            
        case 'textarea':
            html += `<textarea id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; resize: vertical;"></textarea>`;
            break;
            
        case 'number':
            html += `<input type="number" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
            break;
            
        case 'date':
            html += `<input type="date" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
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
            
        case 'cascading_dropdown':
            html += `
                <div class="cascading-dropdown-container" id="${fieldName}_container">
                    <div class="dropdown-level" style="margin-bottom: 0.5rem;">
                        <label style="display: block; margin-bottom: 0.25rem; font-size: 0.9rem; color: #495057;">Region:</label>
                        <select id="${fieldName}_region" name="metadata[${fieldName}_region]" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;">
                            <option value="">Select Region</option>
                        </select>
                    </div>
                    <div class="dropdown-level" style="margin-bottom: 0.5rem;">
                        <label style="display: block; margin-bottom: 0.25rem; font-size: 0.9rem; color: #495057;">Province:</label>
                        <select id="${fieldName}_province" name="metadata[${fieldName}_province]" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;" disabled>
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div class="dropdown-level" style="margin-bottom: 0.5rem;">
                        <label style="display: block; margin-bottom: 0.25rem; font-size: 0.9rem; color: #495057;">City/Municipality:</label>
                        <select id="${fieldName}_citymun" name="metadata[${fieldName}_citymun]" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;" disabled>
                            <option value="">Select City/Municipality</option>
                        </select>
                    </div>
                    <div class="dropdown-level">
                        <label style="display: block; margin-bottom: 0.25rem; font-size: 0.9rem; color: #495057;">Barangay:</label>
                        <select id="${fieldName}_barangay" name="metadata[${fieldName}_barangay]" style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;" disabled>
                            <option value="">Select Barangay</option>
                        </select>
                    </div>
                    <input type="hidden" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr}>
                </div>
            `;
            break;
            
        case 'reference':
            html += `
                <div style="margin-bottom: 0.5rem;">
                    <button type="button" class="select-reference-btn" data-field-id="metadata_${fieldName}" style="padding: 0.75rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 0.5rem;">
                        [REF] Select Reference Document
                    </button>
                    <input type="hidden" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr}>
                    <div id="metadata_${fieldName}_display" style="border: 1px solid #ced4da; border-radius: 4px; padding: 1rem; background: #f8f9fa; min-height: 60px;">
                        <span style="color: #6c757d;">No reference document selected</span>
                    </div>
                </div>
            `;
            break;
            
        default:
            html += `<input type="text" id="metadata_${fieldName}" name="metadata[${fieldName}]" ${requiredAttr} style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">`;
    }
    
    html += '</div>';
    return html;
}

// File handling functions
function selectDocument(fieldName) {
    document.getElementById('metadata_' + fieldName).click();
}

function clearFileSelection(fieldName) {
    const fileInput = document.getElementById('metadata_' + fieldName);
    const display = document.getElementById('metadata_' + fieldName + '_display');
    const previewBtn = document.getElementById('metadata_' + fieldName + '_preview_btn');
    
    fileInput.value = '';
    display.innerHTML = '<div style="color: #6c757d; text-align: center; padding: 1rem;">No file selected</div>';
    if (previewBtn) {
        previewBtn.style.display = 'none';
    }
}

function previewDocument(fieldName, filePath) {
    const fileInput = document.getElementById('metadata_' + fieldName);
    
    if (fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        const fileURL = URL.createObjectURL(file);
        openPreviewWindow(fileURL, file.name);
    } else if (filePath) {
        openPreviewWindow(filePath, 'Document');
    } else {
        alert('No file selected for preview');
    }
}

function openPreviewWindow(fileURL, fileName) {
    const fileExtension = fileName.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
        // Image preview
        const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
        previewWindow.document.write(`
            <html>
                <head><title>Preview: ${fileName}</title></head>
                <body style="margin: 0; padding: 20px; text-align: center; background: #f5f5f5;">
                    <h3>${fileName}</h3>
                    <img src="${fileURL}" style="max-width: 100%; max-height: 80vh; border: 1px solid #ddd; border-radius: 4px;">
                </body>
            </html>
        `);
    } else if (['pdf'].includes(fileExtension)) {
        // Use enhanced PDF.js viewer for better experience
        const pdfViewerUrl = `${BASE_URL}/index.php?page=pdf_viewer&file=${encodeURIComponent(fileName)}&title=${encodeURIComponent(fileName)}`;
        window.open(pdfViewerUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    } else {
        // For other file types, try to open directly
        window.open(fileURL, '_blank');
    }
}

// Enhanced file change handler with PDF support
document.addEventListener('DOMContentLoaded', function() {
    function addFileInputListeners() {
        const fileInputs = document.querySelectorAll('input[type="file"][id^="metadata_"]');
        fileInputs.forEach(function(input) {
            input.removeEventListener('change', handleFileChange);
            input.addEventListener('change', handleFileChange);
        });
    }
    
    function handleFileChange(event) {
        const fieldName = event.target.id.replace('metadata_', '');
        const display = document.getElementById('metadata_' + fieldName + '_display');
        const previewBtn = document.getElementById('metadata_' + fieldName + '_preview_btn');
        
        if (event.target.files && event.target.files[0]) {
            const file = event.target.files[0];
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            // Enhanced display for PDF files
            if (fileExtension === 'pdf') {
                display.innerHTML = `
                    <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; padding: 0.75rem;">
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <span style="color: #dc3545; font-size: 1.2rem; margin-right: 0.5rem;">[PDF]</span>
                            <div>
                                <p style="margin: 0; font-weight: 500; color: #495057;">PDF Document: ${file.name}</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; color: #6c757d;">Size: ${fileSize} MB | Ready for upload</p>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; color: #0c5460; background: #d1ecf1; padding: 0.5rem; border-radius: 3px;">
                            <strong>PDF Features:</strong> Secure viewing, metadata extraction, and enhanced validation
                        </div>
                    </div>
                `;
            } else {
                display.innerHTML = `
                    <div>
                        <p style="margin: 0; font-weight: 500; color: #495057;">File: ${file.name}</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; color: #6c757d;">Size: ${fileSize} MB | Selected for upload</p>
                    </div>
                `;
            }
            
            if (previewBtn) {
                previewBtn.style.display = 'inline-block';
            }
        }
    }
    
    addFileInputListeners();
    
    const originalDisplayMetadataFields = window.displayMetadataFields;
    window.displayMetadataFields = function(fields) {
        originalDisplayMetadataFields(fields);
        setTimeout(addFileInputListeners, 100);
        
        // Initialize special field types after DOM update
        setTimeout(() => {
            fields.forEach(field => {
                console.log('Processing field:', field.field_name, 'type:', field.field_type);
                if (field.field_type === 'cascading_dropdown') {
                    console.log('Initializing cascading dropdown for:', field.field_name);
                    if (typeof initCascadingDropdown === 'function') {
                        const levels = ['region', 'province', 'citymun', 'barangay'];
                        initCascadingDropdown(field.field_name, levels);
                    }
                } else if (field.field_type === 'reference') {
                    console.log('Initializing reference field for:', field.field_name);
                    initializeReferenceButtons();
                }
            });
        }, 200);
    }
});

// Initialize cascading dropdown field
function initializeCascadingDropdownField(fieldName) {
    if (typeof initCascadingDropdown === 'function') {
        const levels = ['region', 'province', 'citymun', 'barangay'];
        initCascadingDropdown(fieldName, levels);
    } else {
        console.warn('initCascadingDropdown function not found');
    }
}

// Initialize reference field buttons
function initializeReferenceButtons() {
    document.querySelectorAll('.select-reference-btn').forEach(button => {
        if (!button.hasAttribute('data-initialized')) {
            button.setAttribute('data-initialized', 'true');
            button.addEventListener('click', function() {
                const fieldId = this.getAttribute('data-field-id');
                console.log('Reference button clicked for field:', fieldId);
                
                if (typeof openReferenceSelector === 'function') {
                    openReferenceSelector(fieldId);
                } else {
                    console.error('openReferenceSelector function not found');
                    alert('Reference selector not available. Please check if the script is loaded.');
                }
            });
        }
    });
}
</script>

<?php renderPageEnd(); ?>