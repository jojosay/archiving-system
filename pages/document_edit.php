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
                        
                        // Get current value from document metadata
                        $current_value = '';
                        if (isset($document['metadata'][$field_name])) {
                            $current_value = $document['metadata'][$field_name]['value'] ?? '';
                        }
                        
                        $requiredAttr = $is_required ? 'required' : '';
                        $requiredMark = $is_required ? ' *' : '';
                        
                        echo '<div style="margin-bottom: 1.5rem;">';
                        echo '<label for="metadata_' . $field_name . '" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">' . htmlspecialchars($field_label) . $requiredMark . '</label>';
                        
                        switch ($field_type) {
                            case 'text':
                                echo '<input type="text" id="metadata_' . $field_name . '" name="metadata[' . $field_name . ']" ' . $requiredAttr . ' style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" value="' . htmlspecialchars($current_value) . '">';
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
                                // Show current value display - check for separate address fields
                                $display_location = '';
                                if (!empty($current_value)) {
                                    $display_location = formatCascadingDropdownValue($current_value);
                                } else if (strpos($field_name, 'address') !== false) {
                                    // Try to get from separate address fields
                                    $region_value = $document['metadata']['address_region']['value'] ?? '';
                                    $province_value = $document['metadata']['address_province']['value'] ?? '';
                                    $citymun_value = $document['metadata']['address_citymun']['value'] ?? '';
                                    $barangay_value = $document['metadata']['address_barangay']['value'] ?? '';
                                    
                                    if (!empty($region_value) || !empty($province_value) || !empty($citymun_value) || !empty($barangay_value)) {
                                        $display_location = "Region: $region_value | Province: $province_value | City/Municipality: $citymun_value | Barangay: $barangay_value";
                                    }
                                }
                                
                                if (!empty($display_location)) {
                                    echo '<div style="background: #e8f5e8; border: 2px solid #28a745; border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem;">';
                                    echo '<div style="font-weight: 500; color: #155724; margin-bottom: 0.25rem;">Current Location:</div>';
                                    echo '<div style="color: #155724;">' . htmlspecialchars($display_location) . '</div>';
                                    echo '<div style="font-size: 0.8rem; color: #6c757d; margin-top: 0.5rem;">Note: These are location codes. Use the dropdowns below to change the selection.</div>';
                                    echo '</div>';
                                }
                                
                                // Always render the cascading dropdown HTML
                                echo generateCascadingDropdownHTML($field_name, $requiredAttr, $current_value);
                                // Mark location fields for special initialization
                                if (strpos($field_name, 'address') !== false && !empty($current_value) && $current_value[0] !== '{') {
                                    echo '<script>window.locationFieldsToResolve = window.locationFieldsToResolve || []; window.locationFieldsToResolve.push({field: "' . $field_name . '", value: "' . htmlspecialchars($current_value) . '"});</script>';
                                    echo '<script>console.log("Marked location field for resolution:", "' . $field_name . '", "' . htmlspecialchars($current_value) . '");</script>';
                                }
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

    <?php
function generateCascadingDropdownHTML($fieldName, $requiredAttr, $currentValue = '') {
    $levels = ['region', 'province', 'citymun', 'barangay'];
    $labels = ['Region', 'Province', 'City/Municipality', 'Barangay'];
    
    $html = '';
    foreach ($levels as $index => $level) {
        $label = $labels[$index];
        $selectId = "metadata_{$fieldName}_{$level}";
        $disabled = $index > 0 ? 'disabled' : '';
        $html .= "
            <div style=\"margin-bottom: 0.5rem;\">
                <label for=\"$selectId\" style=\"font-size: 0.9rem; color: #6C757D; margin-bottom: 0.25rem; display: block;\">{$label}</label>
                <select id=\"$selectId\" class=\"cascading-dropdown\" style=\"width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px; margin-bottom: 0.5rem;\" {$disabled}>
                    <option value=\"\">Select {$label}</option>
                </select>
            </div>
        ";
    }
    $html .= "<input type=\"hidden\" id=\"metadata_{$fieldName}\" name=\"metadata[{$fieldName}]\" value=\"" . htmlspecialchars($currentValue) . "\" {$requiredAttr}>";
    return $html;
}

function formatCascadingDropdownValue($value) {
    if (!$value) return 'N/A';
    
    try {
        // If it's already a string that looks formatted, return it
        if (is_string($value) && substr($value, 0, 1) !== '{') {
            return $value;
        }
        
        // Parse JSON if it's a string
        $data = is_string($value) ? json_decode($value, true) : $value;
        
        if (!$data) return $value; // Return original if parsing fails
        
        // Extract the text values in hierarchical order
        $parts = [];
        if (isset($data['regions']['text'])) $parts[] = $data['regions']['text'];
        if (isset($data['provinces']['text'])) $parts[] = $data['provinces']['text'];
        if (isset($data['citymun']['text'])) $parts[] = $data['citymun']['text'];
        if (isset($data['barangays']['text'])) $parts[] = $data['barangays']['text'];
        
        // Also check for singular forms
        if (isset($data['region']['text'])) $parts[] = $data['region']['text'];
        if (isset($data['province']['text'])) $parts[] = $data['province']['text'];
        if (isset($data['barangay']['text'])) $parts[] = $data['barangay']['text'];
        
        return count($parts) > 0 ? implode(' > ', $parts) : $value;
    } catch (Exception $e) {
        // If parsing fails, return the original value
        return $value;
    }
}
?>

    // File handling functions
    function selectDocument(fieldName) {
        document.getElementById('metadata_' + fieldName).click();
    }

    function clearFileSelection(fieldName) {
        const fileInput = document.getElementById(`metadata_${fieldName}`);
        if (fileInput) {
            fileInput.value = '';
        }
        const clearFileHiddenInput = document.getElementById(`metadata_${fieldName}_clear_file`);
        if (clearFileHiddenInput) {
            clearFileHiddenInput.value = '1';
        }
        const display = document.getElementById(`metadata_${fieldName}_display`);
        if (display) {
            display.innerHTML = '<div id="metadata_' + fieldName + '_placeholder" style="color: #6c757d; text-align: center; padding: 1rem;">No file selected</div>';
        }
    }

    // Fixed previewDocument function
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
            const fileName = filePath.split('/').pop();
            openPreviewWindow(fileURL, fileName, filePath);
        }
    };

    // Fixed openPreviewWindow function
    function openPreviewWindow(fileURL, fileName, fullFilePath = null) {
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
            // Use enhanced PDF.js viewer with the full file path - THIS IS THE KEY FIX
            const fileParam = fullFilePath || fileName;
            const pdfViewerUrl = `${BASE_URL}index.php?page=pdf_viewer&file=${encodeURIComponent(fileParam)}&title=${encodeURIComponent(fileName)}`;
            window.open(pdfViewerUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            return;
        } else {
            // For other file types, try to open directly
            window.open(fileURL, '_blank');
        }
    }

    // Function to clear reference selection
    function clearReferenceSelection(fieldName) {
        const hiddenInput = document.getElementById(`metadata_${fieldName}`);
        if (hiddenInput) {
            hiddenInput.value = '';
        }
        const displayArea = document.getElementById(`metadata_${fieldName}_display`);
        if (displayArea) {
            displayArea.innerHTML = '<div style="color: #6c757d; text-align: center; padding: 1rem; border: 1px dashed #ddd; border-radius: 4px;">No image selected</div>';
        }
    }

    // Function to initialize cascading dropdown
    function initializeCascadingDropdown(fieldName, initialValue) {
        const levels = ['region', 'province', 'citymun', 'barangay'];
        const fullFieldName = `metadata_${fieldName}`;
        
        setTimeout(() => {
            if (typeof initCascadingDropdown === 'function') {
                const hiddenInput = document.getElementById(fullFieldName);
                if (hiddenInput && initialValue) {
                    hiddenInput.value = initialValue;
                }
                
                let parsedInitialValue = null;
                if (initialValue) {
                    try {
                        if (typeof initialValue === 'string') {
                            parsedInitialValue = JSON.parse(initialValue);
                        } else {
                            parsedInitialValue = initialValue;
                        }
                    } catch (e) {
                        parsedInitialValue = null;
                    }
                }
                
                initCascadingDropdown(fullFieldName, levels, parsedInitialValue);
            }
        }, 500);
    }

    // Function to initialize reference field
    function initializeReferenceField(fieldName) {
        const button = document.querySelector(`[data-field-id="metadata_${fieldName}"]`);
        if (button) {
            button.addEventListener('click', function() {
                openReferenceSelector(`metadata_${fieldName}`);
            });
        }
    }

    // Function to initialize cascading dropdown with separate address values
    function initializeCascadingDropdownWithSeparateValues(fieldName, values) {
        const levels = ['region', 'province', 'citymun', 'barangay'];
        const fullFieldName = `metadata_${fieldName}`;
        
        setTimeout(() => {
            if (typeof initCascadingDropdown === 'function') {
                const dropdown = initCascadingDropdown(fullFieldName, levels, null);
                
                setTimeout(async () => {
                    try {
                        if (values.region) {
                            await dropdown.setValueByCode('region', values.region);
                        }
                        if (values.province) {
                            await dropdown.setValueByCode('province', values.province);
                        }
                        if (values.citymun) {
                            await dropdown.setValueByCode('citymun', values.citymun);
                        }
                        if (values.barangay) {
                            await dropdown.setValueByCode('barangay', values.barangay);
                        }
                        
                        dropdown.updateHiddenField();
                    } catch (error) {
                        // Silent error handling
                    }
                }, 1500);
            }
        }, 500);
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

    // Initialize fields on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dynamic fields based on document's current metadata
        const documentMetadata = <?php echo json_encode($document['metadata'] ?? []); ?>;
        const documentTypeId = <?php echo $document['document_type_id'] ?? 0; ?>;

        // Initialize cascading dropdown fields
        <?php
        foreach ($document_type_fields as $field) {
            if ($field['field_type'] === 'cascading_dropdown') {
                $current_value = '';
                if (isset($document['metadata'][$field['field_name']])) {
                    $current_value = $document['metadata'][$field['field_name']]['value'] ?? '';
                }
                
                // Check if this is an address field and we have separate address components
                if (strpos($field['field_name'], 'address') !== false && empty($current_value)) {
                    $region_value = $document['metadata']['address_region']['value'] ?? '';
                    $province_value = $document['metadata']['address_province']['value'] ?? '';
                    $citymun_value = $document['metadata']['address_citymun']['value'] ?? '';
                    $barangay_value = $document['metadata']['address_barangay']['value'] ?? '';
                    
                    if (!empty($region_value) || !empty($province_value) || !empty($citymun_value) || !empty($barangay_value)) {
                        echo "initializeCascadingDropdownWithSeparateValues('" . $field['field_name'] . "', {\n";
                        echo "    region: '" . addslashes($region_value) . "',\n";
                        echo "    province: '" . addslashes($province_value) . "',\n";
                        echo "    citymun: '" . addslashes($citymun_value) . "',\n";
                        echo "    barangay: '" . addslashes($barangay_value) . "'\n";
                        echo "});\n";
                    } else {
                        echo "initializeCascadingDropdown('" . $field['field_name'] . "', '');\n";
                    }
                } else {
                    echo "initializeCascadingDropdown('" . $field['field_name'] . "', '" . addslashes($current_value) . "');\n";
                }
            }
            if ($field['field_type'] === 'reference') {
                echo "initializeReferenceField('" . $field['field_name'] . "');\n";
            }
        }
        ?>
    });
</script>

<!-- JavaScript files are already included in layout.php -->

<?php renderPageEnd(); ?>