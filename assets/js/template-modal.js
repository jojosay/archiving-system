// Enhanced Save Template Modal JavaScript
class TemplateSaveModal {
    constructor() {
        this.modal = null;
        this.documentTypes = [];
        this.currentTemplate = null;
        this.init();
    }

    init() {
        this.createModal();
        this.loadDocumentTypes();
        this.bindEvents();
    }

    createModal() {
        const modalHTML = `
            <div id="templateSaveModal" class="template-save-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="icon">💾</span>
                        <h3>Save Template</h3>
                        <button class="modal-close" type="button">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="templateSaveForm">
                            <div class="form-group">
                                <label for="documentType">Document Type *</label>
                                <select id="documentType" name="document_type_id" class="form-control select" required>
                                    <option value="">Select document type...</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="templateName">Template Name *</label>
                                <input type="text" id="templateName" name="template_name" class="form-control" 
                                       placeholder="Enter template name..." required>
                                <small class="form-hint">Auto-generated based on document type</small>
                            </div>

                            <div class="form-group">
                                <label for="templateDescription">Description</label>
                                <textarea id="templateDescription" name="description" class="form-control textarea" 
                                          placeholder="Optional template description..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="templateTags">Tags</label>
                                <input type="text" id="templateTags" name="tags" class="form-control" 
                                       placeholder="official, standard, government...">
                                <small class="form-hint">Comma-separated tags for easy searching</small>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="setAsDefault" name="is_default">
                                <label for="setAsDefault">Set as default template for this document type</label>
                            </div>

                            <div class="checkbox-group">
                                <input type="checkbox" id="generatePreview" name="generate_preview" checked>
                                <label for="generatePreview">Generate preview image</label>
                            </div>

                            <div class="field-status">
                                <h4>Field Mapping Status</h4>
                                <div id="fieldStatusContent">
                                    <div class="status-item">
                                        <span class="status-icon">⏳</span>
                                        <span>Analyzing template fields...</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelSave">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveTemplate" form="templateSaveForm">
                            <span class="btn-icon">💾</span>
                            Save Template
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('templateSaveModal');
    }

    bindEvents() {
        // Modal close events
        const closeBtn = this.modal.querySelector('.modal-close');
        const cancelBtn = this.modal.querySelector('#cancelSave');
        
        closeBtn.addEventListener('click', () => this.hide());
        cancelBtn.addEventListener('click', () => this.hide());
        
        // Click outside to close
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.hide();
            }
        });

        // Document type change event
        const documentTypeSelect = this.modal.querySelector('#documentType');
        documentTypeSelect.addEventListener('change', (e) => {
            this.onDocumentTypeChange(e.target.value);
        });

        // Template name auto-generation
        const templateNameInput = this.modal.querySelector('#templateName');
        templateNameInput.addEventListener('focus', () => {
            if (!templateNameInput.value) {
                this.generateTemplateName();
            }
        });

        // Form submission
        const form = this.modal.querySelector('#templateSaveForm');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveTemplate();
        });

        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('show')) {
                this.hide();
            }
        });
    }

    async loadDocumentTypes() {
        try {
            console.log('Loading document types...');
            const response = await fetch('api/document_type_fields.php?action=get_types', {
                credentials: 'same-origin'
            });
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Document types data:', data);
            
            if (data.success && data.document_types) {
                this.documentTypes = data.document_types;
                this.populateDocumentTypeDropdown();
                console.log(`Loaded ${data.document_types.length} document types`);
            } else {
                console.error('API returned error:', data.message || 'Unknown error');
                this.showError('Failed to load document types: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading document types:', error);
            this.showError('Failed to load document types: ' + error.message);
        }
    }

    populateDocumentTypeDropdown() {
        const select = this.modal.querySelector('#documentType');
        
        if (!select) {
            console.error('Document type select element not found');
            return;
        }
        
        // Clear existing options and add default
        select.innerHTML = '<option value="">Select document type...</option>';
        
        // Add document types
        if (this.documentTypes && this.documentTypes.length > 0) {
            this.documentTypes.forEach(type => {
                const option = document.createElement('option');
                option.value = type.id;
                option.textContent = type.name;
                select.appendChild(option);
            });
            console.log(`Populated dropdown with ${this.documentTypes.length} document types`);
        } else {
            select.innerHTML += '<option value="" disabled>No document types available</option>';
            console.warn('No document types to populate');
        }
    }

    onDocumentTypeChange(documentTypeId) {
        if (documentTypeId) {
            this.generateTemplateName();
            this.analyzeFieldMapping(documentTypeId);
        } else {
            this.clearFieldStatus();
        }
    }

    generateTemplateName() {
        const documentTypeSelect = this.modal.querySelector('#documentType');
        const templateNameInput = this.modal.querySelector('#templateName');
        
        if (documentTypeSelect.value) {
            const selectedType = this.documentTypes.find(type => type.id == documentTypeSelect.value);
            if (selectedType) {
                const timestamp = new Date().toISOString().slice(0, 10);
                const suggestedName = `${selectedType.name} Template v1.0 (${timestamp})`;
                templateNameInput.value = suggestedName;
            }
        }
    }

    async analyzeFieldMapping(documentTypeId) {
        const statusContent = this.modal.querySelector('#fieldStatusContent');
        statusContent.innerHTML = '<div class="status-item"><span class="status-icon">⏳</span><span>Analyzing field mapping...</span></div>';

        try {
            // Get current template fields (this would come from the template builder)
            const currentFields = this.getCurrentTemplateFields();
            
            const response = await fetch('api/template_validation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'validate_fields',
                    document_type_id: documentTypeId,
                    template_fields: currentFields
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayFieldStatus(data.validation);
            } else {
                this.displayFieldStatus({
                    required_mapped: 0,
                    required_total: 0,
                    optional_mapped: 0,
                    optional_total: 0,
                    completeness_score: 0
                });
            }
        } catch (error) {
            console.error('Error analyzing field mapping:', error);
            statusContent.innerHTML = '<div class="status-item"><span class="status-icon">❌</span><span>Error analyzing fields</span></div>';
        }
    }

    displayFieldStatus(validation) {
        const statusContent = this.modal.querySelector('#fieldStatusContent');
        const requiredIcon = validation.required_mapped === validation.required_total ? '✅' : '⚠️';
        const optionalIcon = validation.optional_mapped > 0 ? '✅' : '⚠️';

        statusContent.innerHTML = `
            <div class="status-item">
                <span class="status-icon ${validation.required_mapped === validation.required_total ? 'success' : 'warning'}">${requiredIcon}</span>
                <span>Required fields: ${validation.required_mapped}/${validation.required_total} mapped</span>
            </div>
            <div class="status-item">
                <span class="status-icon ${validation.optional_mapped > 0 ? 'success' : 'warning'}">${optionalIcon}</span>
                <span>Optional fields: ${validation.optional_mapped}/${validation.optional_total} mapped</span>
            </div>
        `;
    }

    clearFieldStatus() {
        const statusContent = this.modal.querySelector('#fieldStatusContent');
        statusContent.innerHTML = '<div class="status-item"><span class="status-icon">ℹ️</span><span>Select a document type to analyze field mapping</span></div>';
    }

    getCurrentTemplateFields() {
        // This method should integrate with the template builder to get current fields
        // For now, return empty array - will be implemented when integrating with template builder
        return [];
    }

    async saveTemplate() {
        const form = this.modal.querySelector('#templateSaveForm');
        const formData = new FormData(form);
        const saveBtn = this.modal.querySelector('#saveTemplate');
        
        // Disable save button
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="btn-icon">⏳</span>Saving...';

        try {
            // Get current template data (fields, positions, etc.)
            const templateData = this.getCurrentTemplateData();
            
            // Add template data to form
            formData.append('template_data', JSON.stringify(templateData));
            formData.append('action', 'save_template');

            const response = await fetch('api/template_management_new.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Template saved successfully!');
                this.hide();
                // Trigger template saved event
                document.dispatchEvent(new CustomEvent('templateSaved', { detail: data.template }));
            } else {
                this.showError(data.message || 'Error saving template');
            }
        } catch (error) {
            console.error('Error saving template:', error);
            this.showError('Network error while saving template');
        } finally {
            // Re-enable save button
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span class="btn-icon">💾</span>Save Template';
        }
    }

    getCurrentTemplateData() {
        // Get template data from the template builder if available
        if (window.templateBuilder && window.templateBuilder.placedFields) {
            return {
                fields: window.templateBuilder.placedFields.map(field => ({
                    field_name: field.fieldName,
                    field_type: field.fieldType,
                    x: field.x,
                    y: field.y,
                    width: field.width,
                    height: field.height,
                    page: field.page || 1,
                    properties: field.properties || {}
                })),
                metadata: {
                    scale: window.templateBuilder.scale || 1,
                    total_pages: window.templateBuilder.totalPages || 1,
                    version: '1.0'
                },
                settings: {}
            };
        }
        
        // Fallback for when template builder is not available
        return {
            fields: [],
            metadata: { version: '1.0' },
            settings: {}
        };
    }

    show(templateData = null) {
        this.currentTemplate = templateData;
        this.modal.classList.add('show');
        
        // Reset form
        const form = this.modal.querySelector('#templateSaveForm');
        form.reset();
        
        // Clear field status
        this.clearFieldStatus();
        
        // Focus on document type dropdown
        setTimeout(() => {
            this.modal.querySelector('#documentType').focus();
        }, 300);
    }

    hide() {
        this.modal.classList.remove('show');
        this.currentTemplate = null;
    }

    showSuccess(message) {
        // Simple success notification - can be enhanced with a proper notification system
        alert('✅ ' + message);
    }

    showError(message) {
        // Simple error notification - can be enhanced with a proper notification system
        alert('❌ ' + message);
    }
}

// Initialize the modal when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.templateSaveModal = new TemplateSaveModal();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TemplateSaveModal;
}