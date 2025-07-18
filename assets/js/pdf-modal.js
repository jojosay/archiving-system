// PDF Generation Modal Functions
// currentDocumentId is declared in the main page

function openPdfModal(documentId) {
    console.log('openPdfModal called with documentId:', documentId);
    currentDocumentId = documentId;
    
    // First, check if document has an assigned template through its document type
    checkDocumentTemplate(documentId);
}

function checkDocumentTemplate(documentId) {
    console.log('Checking assigned template for document:', documentId);
    
    fetch(`api/pdf_generation.php?action=get_document_template&document_id=${documentId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Document template check result:', data);
            
            if (data.success && data.template_id) {
                // Document has an assigned template - show confirmation modal
                showTemplateConfirmationModal(data);
            } else {
                // No assigned template - show template selection modal
                showTemplateSelectionModal();
            }
        })
        .catch(error => {
            console.error('Error checking document template:', error);
            // Fallback to template selection
            showTemplateSelectionModal();
        });
}

function showTemplateConfirmationModal(templateData) {
    // Create confirmation modal if it doesn't exist
    if (!document.getElementById('pdfConfirmationModal')) {
        createConfirmationModal();
    }
    
    // Update modal content with template info
    const modal = document.getElementById('pdfConfirmationModal');
    const templateInfo = document.getElementById('assignedTemplateInfo');
    const fieldsList = document.getElementById('assignedFieldsList');
    
    templateInfo.innerHTML = `
        <h4>Template: ${templateData.template_name}</h4>
        <p><strong>Document Type:</strong> ${templateData.document_type_name}</p>
        <p><strong>Template:</strong> ${templateData.template_name}</p>
        <p>This document will be generated using the template assigned to its document type.</p>
    `;
    
    // Load and show template fields
    if (templateData.fields && templateData.fields.length > 0) {
        fieldsList.innerHTML = '<h5>Fields that will be populated:</h5>';
        templateData.fields.forEach(field => {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'field-info-item';
            fieldDiv.innerHTML = `
                <span class="field-name">${field.label || field.name}</span>
                <span class="field-type">(${field.type})</span>
            `;
            fieldsList.appendChild(fieldDiv);
        });
    } else {
        fieldsList.innerHTML = '<p><em>No custom fields defined for this template.</em></p>';
    }
    
    // Store template ID for generation
    modal.dataset.templateId = templateData.template_id;
    
    // Show the modal
    modal.style.display = 'block';
}

function showTemplateSelectionModal() {
    // Create selection modal if it doesn't exist
    if (!document.getElementById('pdfGenerationModal')) {
        createPdfModal();
    }
    
    // Show the modal
    const modal = document.getElementById('pdfGenerationModal');
    if (modal) {
        modal.style.display = 'block';
        
        // Reset form
        const templateSelect = document.getElementById('pdfTemplateSelect');
        if (templateSelect) templateSelect.value = '';
        
        const generateBtn = document.getElementById('generatePdfBtn');
        if (generateBtn) generateBtn.disabled = true;
        
        const fieldSection = document.getElementById('fieldMappingSection');
        if (fieldSection) fieldSection.style.display = 'none';
    }
}

function createPdfModal() {
    console.log('Creating PDF template selection modal...');
    
    // Add modal styles first
    addModalStyles();
    
    const modalHtml = `
        <div id="pdfGenerationModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>No Template Assigned</h3>
                    <span class="close" onclick="closePdfModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p>This document type doesn't have an assigned PDF template. Please select one:</p>
                    <div class="form-group">
                        <label for="pdfTemplateSelect">Select PDF Template:</label>
                        <select id="pdfTemplateSelect" onchange="onTemplateSelect()">
                            <option value="">-- Select Template --</option>
                        </select>
                    </div>
                    <div id="fieldMappingSection" style="display: none;">
                        <h4>Field Mapping</h4>
                        <div id="fieldMappingList"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="generatePdfBtn" class="btn btn-primary" onclick="generatePdfFromModal()" disabled>
                        Generate PDF
                    </button>
                    <button class="btn btn-secondary" onclick="closePdfModal()">Cancel</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    loadPdfTemplates();
}

function createConfirmationModal() {
    console.log('Creating PDF confirmation modal...');
    
    const modalHtml = `
        <div id="pdfConfirmationModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Generate PDF</h3>
                    <span class="close" onclick="closeConfirmationModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="assignedTemplateInfo">
                        <!-- Template info will be populated here -->
                    </div>
                    <div id="assignedFieldsList">
                        <!-- Fields list will be populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="generatePdfFromAssignedTemplate()">
                        Generate PDF
                    </button>
                    <button class="btn btn-secondary" onclick="closeConfirmationModal()">Cancel</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function addModalStyles() {
    if (document.getElementById('pdfModalStyles')) return;
    
    const styles = `
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 8px 8px;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .field-mapping-item, .field-info-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #007bff;
        }

        .field-mapping-item label, .field-info-item .field-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .field-mapping-item span, .field-info-item .field-type {
            color: #666;
            font-style: italic;
        }
    `;
    
    const styleElement = document.createElement('style');
    styleElement.id = 'pdfModalStyles';
    styleElement.textContent = styles;
    document.head.appendChild(styleElement);
}

function closePdfModal() {
    const modal = document.getElementById('pdfGenerationModal');
    if (modal) modal.style.display = 'none';
}

function closeConfirmationModal() {
    const modal = document.getElementById('pdfConfirmationModal');
    if (modal) modal.style.display = 'none';
}

function generatePdfFromAssignedTemplate() {
    const modal = document.getElementById('pdfConfirmationModal');
    const templateId = modal.dataset.templateId;
    
    if (!currentDocumentId || !templateId) {
        alert('Missing document or template information');
        return;
    }
    
    console.log('Generating PDF for document:', currentDocumentId, 'with assigned template:', templateId);
    
    // Generate PDF using assigned template
    const url = `api/pdf_generation.php?action=generate_pdf&document_id=${currentDocumentId}&template_id=${templateId}`;
    window.open(url, '_blank');
    
    closeConfirmationModal();
}

function loadPdfTemplates() {
    console.log('Loading PDF templates...');
    fetch('api/template_management_new.php?action=list')
        .then(response => response.json())
        .then(data => {
            console.log('Templates loaded:', data);
            const select = document.getElementById('pdfTemplateSelect');
            if (data.success && data.templates) {
                data.templates.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading templates:', error));
}

function onTemplateSelect() {
    const templateId = document.getElementById('pdfTemplateSelect').value;
    const generateBtn = document.getElementById('generatePdfBtn');
    
    if (templateId) {
        generateBtn.disabled = false;
        loadTemplateFields(templateId);
    } else {
        generateBtn.disabled = true;
        document.getElementById('fieldMappingSection').style.display = 'none';
    }
}

function loadTemplateFields(templateId) {
    console.log('Loading template fields for template:', templateId);
    fetch(`api/pdf_generation.php?action=get_template_fields&template_id=${templateId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Template fields loaded:', data);
            if (data.success && data.fields && data.fields.length > 0) {
                displayFieldMapping(data.fields);
            }
        })
        .catch(error => console.error('Error loading template fields:', error));
}

function displayFieldMapping(fields) {
    const section = document.getElementById('fieldMappingSection');
    const list = document.getElementById('fieldMappingList');
    
    list.innerHTML = '';
    
    fields.forEach(field => {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'field-mapping-item';
        fieldDiv.innerHTML = `
            <label>${field.label || field.name} (${field.type}):</label>
            <span>Will be populated from document data</span>
        `;
        list.appendChild(fieldDiv);
    });
    
    section.style.display = 'block';
}

function generatePdfFromModal() {
    const templateId = document.getElementById('pdfTemplateSelect').value;
    
    if (!currentDocumentId || !templateId) {
        alert('Please select a template');
        return;
    }
    
    console.log('Generating PDF for document:', currentDocumentId, 'with template:', templateId);
    
    // Generate PDF
    const url = `api/pdf_generation.php?action=generate_pdf&document_id=${currentDocumentId}&template_id=${templateId}`;
    window.open(url, '_blank');
    
    closePdfModal();
}

// Make functions globally available
window.openPdfModal = openPdfModal;
window.closePdfModal = closePdfModal;
window.closeConfirmationModal = closeConfirmationModal;
window.createPdfModal = createPdfModal;
window.createConfirmationModal = createConfirmationModal;
window.loadPdfTemplates = loadPdfTemplates;
window.onTemplateSelect = onTemplateSelect;
window.loadTemplateFields = loadTemplateFields;
window.displayFieldMapping = displayFieldMapping;
window.generatePdfFromModal = generatePdfFromModal;
window.generatePdfFromAssignedTemplate = generatePdfFromAssignedTemplate;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('PDF modal functions loaded and available globally');
    console.log('openPdfModal function available:', typeof window.openPdfModal === 'function');
});