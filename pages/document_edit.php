<?php
require_once 'includes/layout.php';
require_once 'includes/document_manager.php';
require_once 'includes/document_type_manager.php';


$database = new Database();
$documentManager = new DocumentManager($database);
$docTypeManager = new DocumentTypeManager($database);

$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$document = null;
$message = '';
$message_type = '';

if ($document_id > 0) {
    $document = $documentManager->getDocumentById($document_id);
    if (!$document) {
        $message = 'Document not found.';
        $message_type = 'error';
    }
} else {
    $message = 'Invalid document ID.';
    $message_type = 'error';
}

// Handle form submission for updating document
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_document' && $document) {
    $title = trim($_POST['title'] ?? '');
    $metadata = $_POST['metadata'] ?? [];

    if (empty($title)) {
        $message = 'Document title is required.';
        $message_type = 'error';
    } else {
        $result = $documentManager->updateDocument($document_id, $title, $_FILES, $metadata);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        
        // Reload document data after update
        $document = $documentManager->getDocumentById($document_id);
    }
}

renderPageStart('Edit Document');
?>

<div style="max-width: 800px; margin: 0 auto; padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e9ecef;">
        <h1 style="font-size: 2rem; font-weight: 600; color: #2c3e50; margin: 0;">Edit Document</h1>
        <a href="?page=document_archive" style="color: #6c757d; text-decoration: none;">&lt; Back to Archive</a>
    </div>

    <?php if ($message): ?>
        <div style="padding: 1rem; border-radius: 4px; margin-bottom: 1rem; <?php echo $message_type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : ($message_type === 'error' ? 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' : 'background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;'); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($document): ?>
        <div style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <form method="POST" enctype="multipart/form-data" id="editDocumentForm">
                <input type="hidden" name="action" value="update_document">
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Document Title *</label>
                    <input type="text" id="title" name="title" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" value="<?php echo htmlspecialchars($document['title'] ?? ''); ?>">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="document_type_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Document Type</label>
                    <input type="text" id="document_type_name" value="<?php echo htmlspecialchars($document['document_type_name'] ?? 'Unknown'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; background-color: #e9ecef;" readonly>
                </div>
                
                <!-- Dynamic metadata fields will be loaded here -->
                <div id="metadata-fields" style="margin-bottom: 1.5rem;">
                    <?php
                    // Pre-populate metadata fields
                    $document_type_fields = $docTypeManager->getDocumentTypeFields($document['document_type_id']);
                    foreach ($document_type_fields as $field) {
                        $field_name = $field['field_name'];
                        $field_label = $field['field_label'];
                        $field_type = $field['field_type'];
                        $is_required = $field['is_required'];
                        $field_options = $field['field_options'];
                        $current_value = $document['metadata'][$field_name]['value'] ?? '';

                        $requiredAttr = $is_required ? 'required' : '';
                        $requiredMark = $is_required ? ' <span style="color: red;">*</span>' : '';

                        echo '<div style="margin-bottom: 1rem;">';
                        echo '<label for="metadata_' . $field_name . '" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">' . $field_label . $requiredMark . '</label>';
                        
                        switch ($field_type) {
                            case 'text':
                            case 'number':
                            case 'date':
                            case 'time':
                                echo '<input type="' . $field_type . '" id="metadata_' . $field_name . '" name="metadata[' . $field_name . ']" ' . $requiredAttr . ' style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" value="' . htmlspecialchars($current_value) . '">';
                                break;
                                
                            case 'textarea':
                                echo '<textarea id="metadata_' . $field_name . '" name="metadata[' . $field_name . ']" ' . $requiredAttr . ' style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; height: 100px; resize: vertical;">' . htmlspecialchars($current_value) . '</textarea>';
                                break;
                                
                            case 'dropdown':
                                echo '<select id="metadata_' . $field_name . '" name="metadata[' . $field_name . ']" ' . $requiredAttr . ' style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">';
                                echo '<option value="">Select an option</option>';
                                if ($field_options) {
                                    try {
                                        $options = json_decode($field_options, true);
                                        foreach ($options as $option) {
                                            $selected = ($current_value == $option) ? 'selected' : '';
                                            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                                        }
                                    } catch (Exception $e) {
                                        // Error parsing options
                                    }
                                }
                                echo '</select>';
                                break;
                                
                            case 'cascading_dropdown':
                                // Render the cascading dropdown HTML
                                echo generateCascadingDropdownHTML($field_name, $requiredAttr, $current_value);
                                break;
                                
                            case 'reference':
                                echo '<input type="hidden" id="metadata_' . $field_name . '" name="metadata[' . $field_name . ']" value="' . htmlspecialchars($current_value) . '" ' . $requiredAttr . '>';
                                echo '<div id="metadata_' . $field_name . '_display" style="border: 1px solid #ddd; border-radius: 4px; padding: 1rem; margin-bottom: 0.5rem; min-height: 60px; background: #f8f9fa;">';
                                if (!empty($current_value)) {
                                    // Fetch book image details for display
                                    $book_image = $documentManager->getBookImageById($current_value);
                                    if ($book_image) {
                                        echo '
                                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: #e8f5e8; border-radius: 8px; border: 2px solid #28a745;">
                                                <img src="' . BASE_URL . 'api/serve_file.php?file=' . urlencode($book_image['file_path']) . '" alt="' . ($book_image['title'] ?? '') . '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                <div>
                                                <div style="font-weight: 500; color: #155724; margin-bottom: 0.25rem;">' . ($book_image['title'] ?? '') . '</div>

                                                    <div style="font-size: 0.9rem; color: #6c757d;">Image ID: ' . $book_image['id'] . '</div>
                                                </div>
                                            </div>
                                        ';
                                    } else {
                                        echo '<div style="color: #6c757d; text-align: center; padding: 1rem; border: 1px dashed #ddd; border-radius: 4px;">No image selected</div>';
                                    }
                                } else {
                                    echo '<div style="color: #6c757d; text-align: center; padding: 1rem; border: 1px dashed #ddd; border-radius: 4px;">No image selected</div>';
                                }
                                echo '</div>';
                                echo '<button type="button" class="select-reference-btn" data-field-id="metadata_' . $field_name . '" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem;">Select Image</button>';
                                                                echo "<button type=\"button\" onclick=\"clearReferenceSelection('" . addslashes($field_name) . "')\" style=\"padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;\">Clear</button>";
                                break;

                            case 'file':
                                // Hidden file input
                                echo '<input type="file" id="metadata_' . $field_name . '" name="metadata[' . $field_name . ']" ' . $requiredAttr . ' style="display: none;" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">';
                                echo '<input type="hidden" id="metadata_' . $field_name . '_clear_file" name="metadata[' . $field_name . '_clear_file]" value="0">';
                                
                                // File display area
                                echo '<div id="metadata_' . $field_name . '_display" style="border: 1px solid #ced4da; border-radius: 4px; padding: 1rem; margin-bottom: 0.5rem; min-height: 60px; background: #f8f9fa;">';
                                if (!empty($current_value)) {
                                    $file_name = basename($current_value);
                                    echo '<div id="metadata_' . $field_name . '_file_info">';
                                    echo '<p style="margin: 0; font-weight: 500; color: #495057;">File: ' . htmlspecialchars($file_name) . '</p>';
                                    echo '<p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; color: #6c757d;">Current file</p>';
                                    echo '</div>';
                                } else {
                                    echo '<div id="metadata_' . $field_name . '_placeholder" style="color: #6c757d; text-align: center; padding: 1rem;">No file selected</div>';
                                }
                                echo '</div>';
                                
                                // Action buttons
                                echo '<div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">';
                                echo '<button type="button" onclick="selectDocument(\'' . $field_name . '\')" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Select Document</button>';
                                if (!empty($current_value)) {
                                    echo '<button type="button" onclick="previewDocument(\'' . $field_name . '\', \'' . htmlspecialchars($current_value) . '\')" style="padding: 0.5rem 1rem; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Preview</button>';
                                }
                                echo '<button type="button" onclick="clearFileSelection(\'' . $field_name . '\')" style="padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Clear</button>';
                                echo '</div>';
                                break;
                                
                            default:
                                echo '<input type="text" id="metadata_' . $field_name . '" name="metadata[' . $field_name . ']" ' . $requiredAttr . ' style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" value="' . htmlspecialchars($current_value) . '">';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <button type="submit" style="padding: 0.75rem 2rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">Update Document</button>
            </form>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #dc3545;">Document not found or invalid ID.</p>
    <?php endif; ?>
</div>

<script>
    // Pass document metadata to JavaScript
    const documentMetadata = <?php echo json_encode($document['metadata'] ?? []); ?>;
    console.log('Document metadata:', documentMetadata);

    <?php
function generateCascadingDropdownHTML($fieldName, $requiredAttr, $currentValue = '') {
    $levels = ['regions', 'provinces', 'citymun', 'barangays'];
    $labels = ['Region', 'Province', 'City/Municipality', 'Barangay'];
    
    $html = '';
    foreach ($levels as $index => $level) {
        $label = $labels[$index];
        $selectId = "metadata_{$fieldName}_{$level}";
        $html .= "
            <div style=\"margin-bottom: 0.5rem;\">
                <label for=\"$selectId\" style=\"font-size: 0.9rem; color: #6C757D; margin-bottom: 0.25rem; display: block;\">{$label}</label>
                <select id=\"$selectId\" class=\"cascading-dropdown\" style=\"width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; margin-bottom: 0.5rem;\">
                    <option value=\"\">Select {$label}</option>
                </select>
            </div>
        ";
    }
    $html .= "<input type=\"hidden\" id=\"metadata_{$fieldName}_data\" name=\"metadata[{$fieldName}]\" value=\"" . htmlspecialchars($currentValue) . "\" {$requiredAttr}>";
    return $html;
}
?>

    // Function to initialize cascading dropdown (copied from document_upload.php)
    function initializeCascadingDropdown(fieldName, initialValue) {
        const levels = ['regions', 'provinces', 'citymun', 'barangays'];
        const fullFieldName = `metadata_${fieldName}`;
        
        console.log(`Initializing cascading dropdown: ${fieldName} with value:`, initialValue);
        
        setTimeout(() => {
            if (typeof initCascadingDropdown === 'function') {
                // Set the hidden input value first
                const hiddenInput = document.getElementById(`${fullFieldName}_data`);
                if (hiddenInput && initialValue) {
                    hiddenInput.value = initialValue;
                }
                
                initCascadingDropdown(fullFieldName, levels, initialValue);
            } else {
                console.error('initCascadingDropdown function not found');
            }
        }, 500); // Increased timeout to ensure DOM is ready
    }

    // Function to initialize reference field (copied from document_upload.php)
    function initializeReferenceField(fieldName) {
        const button = document.querySelector(`[data-field-id="metadata_${fieldName}"]`);
        if (button) {
            button.addEventListener('click', function() {
                openReferenceSelector(`metadata_${fieldName}`);
            });
        }
    }

    // Function to clear reference selection (copied from reference_selector.js)
    function clearReferenceSelection(fieldName) {
        console.log('Clearing reference selection for field:', fieldName);
        const hiddenInput = document.getElementById(`metadata_${fieldName}`);
        if (hiddenInput) {
            hiddenInput.value = '';
        }
        const displayArea = document.getElementById(`metadata_${fieldName}_display`);
        if (displayArea) {
            displayArea.innerHTML = '<div style="color: #6c757d; text-align: center; padding: 1rem; border: 1px dashed #ddd; border-radius: 4px;">No image selected</div>';
        }
        console.log('Reference selection cleared for field:', fieldName);
    }

    function clearFileSelection(fieldName) {
        console.log('Clearing file selection for field:', fieldName);
        const fileInput = document.getElementById(`metadata_${fieldName}`);
        if (fileInput) {
            fileInput.value = ''; // Clear the file input
        }
        const clearFileHiddenInput = document.getElementById(`metadata_${fieldName}_clear_file`);
        if (clearFileHiddenInput) {
            clearFileHiddenInput.value = '1'; // Signal to backend to clear the file
        }
        const currentFileDisplay = document.getElementById(`metadata_${fieldName}_current_file`);
        if (currentFileDisplay) {
            currentFileDisplay.innerHTML = 'No file selected';
        }
        console.log('File selection cleared for field:', fieldName);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dynamic fields based on document's current metadata
        const documentMetadata = <?php echo json_encode($document['metadata'] ?? []); ?>;
        const documentTypeId = <?php echo $document['document_type_id'] ?? 0; ?>;

        if (documentTypeId > 0) {
            fetch(`api/document_type_fields.php?type_id=${documentTypeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.fields) {
                        data.fields.forEach(field => {
                            const fieldName = field.field_name;
                            const fieldType = field.field_type;
                            const fieldLabel = field.field_label;
                            
                            // Get current value from document metadata
                            let currentValue = '';
                            if (documentMetadata[fieldName]) {
                                currentValue = documentMetadata[fieldName].value || '';
                            }
                            
                            console.log(`Initializing field: ${fieldName}, type: ${fieldType}, value:`, currentValue);

                            if (fieldType === 'cascading_dropdown') {
                                initializeCascadingDropdown(fieldName, currentValue);
                            } else if (fieldType === 'reference') {
                                initializeReferenceField(fieldName);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching document type fields:', error);
                });
        }
    });

    // File handling functions
    window.selectDocument = function(fieldName) {
        const fileInput = document.getElementById('metadata_' + fieldName);
        fileInput.click();
    };

    window.clearFileSelection = function(fieldName) {
        const fileInput = document.getElementById('metadata_' + fieldName);
        const display = document.getElementById('metadata_' + fieldName + '_display');
        const clearFlag = document.getElementById('metadata_' + fieldName + '_clear_file');
        
        // Clear the file input
        fileInput.value = '';
        
        // Set clear flag for existing files
        if (clearFlag) {
            clearFlag.value = '1';
        }
        
        // Update display
        display.innerHTML = '<div id="metadata_' + fieldName + '_placeholder" style="color: #6c757d; text-align: center; padding: 1rem;">No file selected</div>';
        
        // Update buttons - hide preview button
        const previewBtn = document.querySelector(`button[onclick*="previewDocument('${fieldName}'"]`);
        if (previewBtn) {
            previewBtn.style.display = 'none';
        }
    };

    window.previewDocument = function(fieldName, filePath) {
        if (!filePath) {
            const fileInput = document.getElementById('metadata_' + fieldName);
            if (fileInput.files && fileInput.files[0]) {
                // Preview selected file
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
    };

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

    // Add event listeners for file inputs
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const fieldName = this.id.replace('metadata_', '');
            const display = document.getElementById('metadata_' + fieldName + '_display');
            const clearFlag = document.getElementById('metadata_' + fieldName + '_clear_file');
            
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB
                
                // Reset clear flag
                if (clearFlag) {
                    clearFlag.value = '0';
                }
                
                // Update display
                display.innerHTML = `
                    <div id="metadata_${fieldName}_file_info">
                        <p style="margin: 0; font-weight: 500; color: #495057;">File: ${file.name}</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; color: #6c757d;">Size: ${fileSize} MB | Selected for upload</p>
                    </div>
                `;
                
                // Show preview button
                const previewBtn = document.querySelector(`button[onclick*="previewDocument('${fieldName}'"]`);
                if (previewBtn) {
                    previewBtn.style.display = 'inline-block';
                    previewBtn.setAttribute('onclick', `previewDocument('${fieldName}')`);
                }
            }
        });
    });
</script>

<?php renderPageEnd(); ?>