/**
 * Enhanced Template Builder JavaScript
 * Provides advanced PDF template field mapping functionality
 */

class TemplateBuilderNew {
    constructor() {
        this.templateId = this.getTemplateIdFromUrl();
        this.pdfDoc = null;
        this.currentPage = 1;
        this.totalPages = 0;
        this.scale = 1.0;
        this.canvas = null;
        this.context = null;
        this.fieldOverlay = null;
        this.fields = [];
        this.selectedField = null;
        this.isDragging = false;
        this.isCreatingField = false;
        this.activeFieldType = null;
        this.dragStartPos = { x: 0, y: 0 };
        
        // Field repositioning state
        this.isRepositioning = false;
        this.repositioningField = null;
        this.repositionStartPos = null;
        
        // Drop prevention state
        this.isDropping = false;
        
        this.init();
    }

    init() {
        this.setupElements();
        this.bindEvents();
        
        if (this.templateId) {
            this.loadTemplate();
        }
    }

    setupElements() {
        this.canvas = document.getElementById('pdfCanvas');
        this.context = this.canvas?.getContext('2d');
        this.fieldOverlay = document.getElementById('fieldOverlay');
        
        // Page controls
        this.currentPageSpan = document.getElementById('currentPage');
        this.totalPagesSpan = document.getElementById('totalPages');
        this.zoomLevelSpan = document.getElementById('zoomLevel');
        
        // Buttons
        this.prevPageBtn = document.getElementById('prevPageBtn');
        this.nextPageBtn = document.getElementById('nextPageBtn');
        this.zoomInBtn = document.getElementById('zoomInBtn');
        this.zoomOutBtn = document.getElementById('zoomOutBtn');
        this.fitWidthBtn = document.getElementById('fitWidthBtn');
        this.fitPageBtn = document.getElementById('fitPageBtn');
        this.saveBtn = document.getElementById('saveTemplateFieldsBtn');
        this.previewBtn = document.getElementById('previewTemplateBtn');
        this.backBtn = document.getElementById('backToManagerBtn');
        
        // Field type buttons
        this.fieldTypeButtons = document.querySelectorAll('.field-type-btn');
        
        // Properties form
        this.propertiesForm = document.getElementById('fieldPropertiesForm');
        this.fieldsList = document.getElementById('fieldsList');
    }

    bindEvents() {
        // Navigation buttons
        this.prevPageBtn?.addEventListener('click', () => this.previousPage());
        this.nextPageBtn?.addEventListener('click', () => this.nextPage());
        
        // Zoom controls
        this.zoomInBtn?.addEventListener('click', () => this.zoomIn());
        this.zoomOutBtn?.addEventListener('click', () => this.zoomOut());
        this.fitWidthBtn?.addEventListener('click', () => this.fitToWidth());
        this.fitPageBtn?.addEventListener('click', () => this.fitToPage());
        
        // Action buttons
        this.saveBtn?.addEventListener('click', () => this.saveTemplate());
        this.previewBtn?.addEventListener('click', () => this.previewTemplate());
        this.backBtn?.addEventListener('click', () => this.goBackToManager());
        
        // Field type selection
        this.fieldTypeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.selectFieldType(e.target.closest('.field-type-btn').dataset.type);
            });
        });
        
        // Canvas events for field creation and selection
        if (this.canvas) {
            this.canvas.addEventListener('mousedown', (e) => this.onCanvasMouseDown(e));
            this.canvas.addEventListener('mousemove', (e) => this.onCanvasMouseMove(e));
            this.canvas.addEventListener('mouseup', (e) => this.onCanvasMouseUp(e));
        }
        
        // Field overlay events - REMOVED to prevent event blocking
        // if (this.fieldOverlay) {
        //     this.fieldOverlay.addEventListener('click', (e) => this.onFieldOverlayClick(e));
        // }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
        
        // Window resize (commented out to prevent errors)
        // window.addEventListener('resize', () => this.handleResize());
        
        // Initialize drag and drop functionality
        this.initializeDragAndDrop();
        
        // Load sidebar states
        setTimeout(() => {
            this.loadSidebarStates();
        }, 100);
        
        // Re-initialize drag and drop when DOM changes
        setTimeout(() => {
            this.initializeDragAndDrop();
        }, 1000);
    }

    getTemplateIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('edit_template');
    }

    async loadTemplate() {
        try {
            this.showLoading('Loading template...');
            
            // Load template data and PDF
            const response = await fetch(`api/template_management_new.php?action=get_template&id=${this.templateId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load template');
            }
            
            this.templateData = data.template;
            
            // Load PDF using the correct web path
            const pdfPath = data.template.web_file_path || data.template.file_path;
            console.log('Loading PDF from path:', pdfPath);
            await this.loadPdf(pdfPath);
            
            // Load existing fields
            await this.loadExistingFields();
            
            this.hideLoading();
            
        } catch (error) {
            console.error('Error loading template:', error);
            this.showNotification('Failed to load template: ' + error.message, 'error');
            this.hideLoading();
        }
    }

    async loadPdf(pdfPath) {
        try {
            console.log('Attempting to load PDF from:', pdfPath);
            
            // Try loading the PDF
            const loadingTask = pdfjsLib.getDocument({
                url: pdfPath,
                cMapUrl: 'assets/js/vendor/pdfjs/web/cmaps/',
                cMapPacked: true
            });
            
            this.pdfDoc = await loadingTask.promise;
            this.totalPages = this.pdfDoc.numPages;
            
            console.log('PDF loaded successfully. Pages:', this.totalPages);
            
            this.updatePageInfo();
            await this.renderPage();
            
        } catch (error) {
            console.error('Error loading PDF from', pdfPath, ':', error);
            
            // Try fallback paths if available
            if (this.templateData.direct_file_path && pdfPath !== this.templateData.direct_file_path) {
                console.log('Trying fallback path:', this.templateData.direct_file_path);
                try {
                    await this.loadPdf(this.templateData.direct_file_path);
                    return;
                } catch (fallbackError) {
                    console.error('Fallback path also failed:', fallbackError);
                }
            }
            
            // Show user-friendly error
            this.showNotification('Failed to load PDF file. Please check if the file exists and is accessible.', 'error');
            throw new Error('Failed to load PDF file: ' + error.message);
        }
    }

    async loadExistingFields() {
        try {
            const response = await fetch(`api/template_management_new.php?action=get_template_fields&template_id=${this.templateId}`);
            const data = await response.json();
            
            if (data.success) {
                this.fields = data.fields || [];
                this.renderFields();
                this.updateFieldsList();
            }
            
        } catch (error) {
            console.error('Error loading existing fields:', error);
        }
    }

    async renderPage() {
        if (!this.pdfDoc || !this.canvas) return;
        
        try {
            const page = await this.pdfDoc.getPage(this.currentPage);
            const viewport = page.getViewport({ scale: this.scale });
            
            // Set canvas dimensions
            this.canvas.width = viewport.width;
            this.canvas.height = viewport.height;
            this.canvas.style.width = viewport.width + 'px';
            this.canvas.style.height = viewport.height + 'px';
            
            // Update field overlay dimensions
            if (this.fieldOverlay) {
                this.fieldOverlay.style.width = viewport.width + 'px';
                this.fieldOverlay.style.height = viewport.height + 'px';
            }
            
            // Render PDF page
            const renderContext = {
                canvasContext: this.context,
                viewport: viewport
            };
            
            await page.render(renderContext).promise;
            
            // Re-render fields for current page
            this.renderFields();
            
        } catch (error) {
            console.error('Error rendering page:', error);
            this.showNotification('Failed to render PDF page', 'error');
        }
    }

    renderFields() {
        if (!this.fieldOverlay) return;
        
        // Clear existing field elements
        this.fieldOverlay.innerHTML = '';
        
        // Render fields for current page
        const pageFields = this.fields.filter(field => field.page_number === this.currentPage);
        
        console.log('Rendering fields:', pageFields.length, 'fields found for page', this.currentPage);
        
        pageFields.forEach(field => {
            console.log('Rendering field:', field.field_name, 'at position:', field.x_position, field.y_position);
            const fieldElement = this.createFieldElement(field);
            this.fieldOverlay.appendChild(fieldElement);
            
            // Verify the element was added and has events
            console.log('Field element added:', fieldElement.dataset.fieldId, 'with cursor:', fieldElement.style.cursor);
        });
    }

    createFieldElement(field) {
        const element = document.createElement('div');
        element.className = `field-element field-${field.field_type}`;
        element.dataset.fieldId = field.id || field.temp_id;
        
        // Position and size - scale appropriately
        const scaledX = field.x_position * (this.scale || 1);
        const scaledY = field.y_position * (this.scale || 1);
        const scaledWidth = field.width * (this.scale || 1);
        const scaledHeight = field.height * (this.scale || 1);
        
        element.style.left = scaledX + 'px';
        element.style.top = scaledY + 'px';
        element.style.width = scaledWidth + 'px';
        element.style.height = scaledHeight + 'px';
        element.style.cursor = 'grab';
        element.style.position = 'absolute';
        
        // Content based on field type
        switch (field.field_type) {
            case 'text':
            case 'number':
                element.innerHTML = `<input type="${field.field_type}" placeholder="${field.field_name}" readonly>`;
                break;
            case 'date':
                element.innerHTML = `<input type="date" readonly>`;
                break;
            case 'checkbox':
                element.innerHTML = `<input type="checkbox" disabled> <span>${field.field_name}</span>`;
                break;
            case 'signature':
                element.innerHTML = `<div class="signature-placeholder">Signature: ${field.field_name}</div>`;
                break;
            case 'region':
                element.innerHTML = `<select disabled><option>Region: ${field.field_name}</option></select>`;
                break;
            case 'province':
                element.innerHTML = `<select disabled><option>Province: ${field.field_name}</option></select>`;
                break;
            case 'city':
                element.innerHTML = `<select disabled><option>City: ${field.field_name}</option></select>`;
                break;
            case 'barangay':
                element.innerHTML = `<select disabled><option>Barangay: ${field.field_name}</option></select>`;
                break;
            default:
                element.innerHTML = `<div class="field-placeholder">${field.field_name}</div>`;
        }
        
        // Add selection and drag handlers
        element.addEventListener('click', (e) => {
            e.stopPropagation();
            console.log('Field clicked:', field.field_name);
            this.selectField(field);
        });
        
        element.addEventListener('mousedown', (e) => {
            if (e.button === 0) { // Left mouse button only
                e.preventDefault();
                e.stopPropagation();
                console.log('MOUSEDOWN on field:', field.field_name, 'at', e.clientX, e.clientY);
                console.log('Starting repositioning...');
                this.startFieldRepositioning(e, field);
            }
        });
        
        // Add debug logging
        console.log('Events bound to field:', field.field_name, 'element:', element);
        
        // Add resize handles
        this.addResizeHandles(element);
        
        return element;
    }

    addResizeHandles(element) {
        const handles = ['nw', 'ne', 'sw', 'se'];
        
        handles.forEach(handle => {
            const resizeHandle = document.createElement('div');
            resizeHandle.className = `resize-handle resize-${handle}`;
            resizeHandle.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                this.startFieldResize(element, handle, e);
            });
            element.appendChild(resizeHandle);
        });
    }

    selectFieldType(type) {
        // Update active field type
        this.activeFieldType = type;
        
        // Update UI
        this.fieldTypeButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
        
        // Change cursor
        if (this.canvas) {
            this.canvas.style.cursor = 'crosshair';
        }
        
        this.showNotification(`Selected ${type} field tool. Click and drag on the PDF to create a field.`, 'info');
    }

    onCanvasMouseDown(e) {
        if (!this.activeFieldType) return;
        
        const rect = this.canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        this.isCreatingField = true;
        this.dragStartPos = { x, y };
        
        // Create temporary field preview
        this.createFieldPreview(x, y);
    }

    onCanvasMouseMove(e) {
        if (!this.isCreatingField) return;
        
        const rect = this.canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        this.updateFieldPreview(x, y);
    }

    onCanvasMouseUp(e) {
        if (!this.isCreatingField) return;
        
        const rect = this.canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        this.finishFieldCreation(x, y);
        this.isCreatingField = false;
    }

    createFieldPreview(x, y) {
        // Remove existing preview
        const existingPreview = document.querySelector('.field-preview');
        if (existingPreview) {
            existingPreview.remove();
        }
        
        // Create new preview
        const preview = document.createElement('div');
        preview.className = 'field-preview';
        preview.style.left = x + 'px';
        preview.style.top = y + 'px';
        preview.style.width = '0px';
        preview.style.height = '0px';
        
        this.fieldOverlay.appendChild(preview);
    }

    updateFieldPreview(x, y) {
        const preview = document.querySelector('.field-preview');
        if (!preview) return;
        
        const width = Math.abs(x - this.dragStartPos.x);
        const height = Math.abs(y - this.dragStartPos.y);
        const left = Math.min(x, this.dragStartPos.x);
        const top = Math.min(y, this.dragStartPos.y);
        
        preview.style.left = left + 'px';
        preview.style.top = top + 'px';
        preview.style.width = width + 'px';
        preview.style.height = height + 'px';
    }

    finishFieldCreation(x, y) {
        // Remove preview
        const preview = document.querySelector('.field-preview');
        if (preview) {
            preview.remove();
        }
        
        // Calculate field dimensions
        const width = Math.abs(x - this.dragStartPos.x);
        const height = Math.abs(y - this.dragStartPos.y);
        const left = Math.min(x, this.dragStartPos.x);
        const top = Math.min(y, this.dragStartPos.y);
        
        // Minimum size check
        if (width < 20 || height < 15) {
            this.showNotification('Field too small. Please draw a larger area.', 'warning');
            return;
        }
        
        // Create new field
        const field = {
            temp_id: 'temp_' + Date.now(),
            field_type: this.activeFieldType,
            field_name: `${this.activeFieldType}_field_${this.fields.length + 1}`,
            x_position: left,
            y_position: top,
            width: width,
            height: height,
            page_number: this.currentPage,
            required: false,
            default_value: ''
        };
        
        this.fields.push(field);
        this.renderFields();
        this.updateFieldsList();
        this.selectField(field);
        
        // Clear active tool
        this.clearActiveFieldType();
    }

    clearActiveFieldType() {
        this.activeFieldType = null;
        this.fieldTypeButtons.forEach(btn => btn.classList.remove('active'));
        if (this.canvas) {
            this.canvas.style.cursor = 'default';
        }
    }

    selectField(field) {
        this.selectedField = field;
        
        // Update visual selection
        document.querySelectorAll('.field-element').forEach(el => {
            el.classList.remove('selected');
        });
        
        const fieldElement = document.querySelector(`[data-field-id="${field.id || field.temp_id}"]`);
        if (fieldElement) {
            fieldElement.classList.add('selected');
        }
        
        // Update properties form
        this.updatePropertiesForm(field);
        
        // Update fields list selection
        this.updateFieldsListSelection(field);
    }

    updatePropertiesForm(field) {
        if (!this.propertiesForm) return;
        
        this.propertiesForm.innerHTML = `
            <div class="form-group">
                <label for="fieldName">Field Name:</label>
                <input type="text" id="fieldName" value="${field.field_name}" onchange="templateBuilder.updateFieldProperty('field_name', this.value)">
            </div>
            <div class="form-group">
                <label for="fieldType">Field Type:</label>
                <select id="fieldType" onchange="templateBuilder.updateFieldProperty('field_type', this.value)">
                    <option value="text" ${field.field_type === 'text' ? 'selected' : ''}>Text</option>
                    <option value="number" ${field.field_type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="date" ${field.field_type === 'date' ? 'selected' : ''}>Date</option>
                    <option value="checkbox" ${field.field_type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                    <option value="signature" ${field.field_type === 'signature' ? 'selected' : ''}>Signature</option>
                </select>
            </div>
            <div class="form-group">
                <label for="fieldRequired">Required:</label>
                <input type="checkbox" id="fieldRequired" ${field.required ? 'checked' : ''} onchange="templateBuilder.updateFieldProperty('required', this.checked)">
            </div>
            <div class="form-group">
                <label for="fieldDefault">Default Value:</label>
                <input type="text" id="fieldDefault" value="${field.default_value || ''}" onchange="templateBuilder.updateFieldProperty('default_value', this.value)">
            </div>
            <div class="form-group">
                <label>Position & Size:</label>
                <div class="position-controls">
                    <input type="number" placeholder="X" value="${Math.round(field.x_position)}" onchange="templateBuilder.updateFieldProperty('x_position', parseFloat(this.value))">
                    <input type="number" placeholder="Y" value="${Math.round(field.y_position)}" onchange="templateBuilder.updateFieldProperty('y_position', parseFloat(this.value))">
                    <input type="number" placeholder="Width" value="${Math.round(field.width)}" onchange="templateBuilder.updateFieldProperty('width', parseFloat(this.value))">
                    <input type="number" placeholder="Height" value="${Math.round(field.height)}" onchange="templateBuilder.updateFieldProperty('height', parseFloat(this.value))">
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-danger btn-small" onclick="templateBuilder.deleteField()">Delete Field</button>
                <button class="btn btn-secondary btn-small" onclick="templateBuilder.duplicateField()">Duplicate</button>
            </div>
        `;
    }

    updateFieldProperty(property, value) {
        if (!this.selectedField) return;
        
        this.selectedField[property] = value;
        
        // Re-render if visual property changed
        if (['x_position', 'y_position', 'width', 'height', 'field_type', 'field_name'].includes(property)) {
            this.renderFields();
        }
        
        // Update fields list
        this.updateFieldsList();
    }

    updateFieldsList() {
        if (!this.fieldsList) return;
        
        const pageFields = this.fields.filter(field => field.page_number === this.currentPage);
        
        this.fieldsList.innerHTML = pageFields.map(field => `
            <div class="field-list-item ${this.selectedField?.temp_id === field.temp_id || this.selectedField?.id === field.id ? 'selected' : ''}" 
                 onclick="templateBuilder.selectFieldFromList('${field.id || field.temp_id}')">
                <div class="field-info">
                    <span class="field-name">${field.field_name}</span>
                    <span class="field-type">${field.field_type}</span>
                </div>
                <div class="field-actions">
                    <button class="btn-icon" onclick="event.stopPropagation(); templateBuilder.editFieldFromList('${field.id || field.temp_id}')" title="Edit">✏️</button>
                    <button class="btn-icon" onclick="event.stopPropagation(); templateBuilder.deleteFieldFromList('${field.id || field.temp_id}')" title="Delete">🗑️</button>
                </div>
            </div>
        `).join('');
    }

    selectFieldFromList(fieldId) {
        const field = this.fields.find(f => (f.id || f.temp_id) === fieldId);
        if (field) {
            this.selectField(field);
        }
    }

    deleteField() {
        if (!this.selectedField) return;
        
        if (confirm('Are you sure you want to delete this field?')) {
            this.fields = this.fields.filter(f => (f.id || f.temp_id) !== (this.selectedField.id || this.selectedField.temp_id));
            this.selectedField = null;
            this.renderFields();
            this.updateFieldsList();
            this.propertiesForm.innerHTML = '<p class="no-field-selected">No field selected</p>';
        }
    }

    duplicateField() {
        if (!this.selectedField) return;
        
        const newField = {
            ...this.selectedField,
            temp_id: 'temp_' + Date.now(),
            id: null,
            field_name: this.selectedField.field_name + '_copy',
            x_position: this.selectedField.x_position + 20,
            y_position: this.selectedField.y_position + 20
        };
        
        this.fields.push(newField);
        this.renderFields();
        this.updateFieldsList();
        this.selectField(newField);
    }

    // Navigation methods
    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.updatePageInfo();
            this.renderPage();
        }
    }

    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.updatePageInfo();
            this.renderPage();
        }
    }

    updatePageInfo() {
        if (this.currentPageSpan) {
            this.currentPageSpan.textContent = this.currentPage;
        }
        if (this.totalPagesSpan) {
            this.totalPagesSpan.textContent = this.totalPages;
        }
        
        // Update navigation buttons
        if (this.prevPageBtn) {
            this.prevPageBtn.disabled = this.currentPage <= 1;
        }
        if (this.nextPageBtn) {
            this.nextPageBtn.disabled = this.currentPage >= this.totalPages;
        }
    }

    // Zoom methods
    zoomIn() {
        this.scale = Math.min(this.scale * 1.25, 3.0);
        this.updateZoomLevel();
        this.renderPage();
    }

    zoomOut() {
        this.scale = Math.max(this.scale / 1.25, 0.25);
        this.updateZoomLevel();
        this.renderPage();
    }

    fitToWidth() {
        if (!this.canvas || !this.pdfDoc) return;
        
        const container = this.canvas.parentElement;
        const containerWidth = container.clientWidth - 40; // Account for padding
        
        this.pdfDoc.getPage(this.currentPage).then(page => {
            const viewport = page.getViewport({ scale: 1.0 });
            this.scale = containerWidth / viewport.width;
            this.updateZoomLevel();
            this.renderPage();
        });
    }

    fitToPage() {
        if (!this.canvas || !this.pdfDoc) return;
        
        const container = this.canvas.parentElement;
        const containerWidth = container.clientWidth - 40;
        const containerHeight = container.clientHeight - 40;
        
        this.pdfDoc.getPage(this.currentPage).then(page => {
            const viewport = page.getViewport({ scale: 1.0 });
            const scaleX = containerWidth / viewport.width;
            const scaleY = containerHeight / viewport.height;
            this.scale = Math.min(scaleX, scaleY);
            this.updateZoomLevel();
            this.renderPage();
        });
    }

    updateZoomLevel() {
        if (this.zoomLevelSpan) {
            this.zoomLevelSpan.textContent = Math.round(this.scale * 100) + '%';
        }
    }

    // Save template
    async saveTemplate() {
        try {
            this.showLoading('Saving template...');
            
            const response = await fetch('api/template_management_new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save_template_fields',
                    template_id: this.templateId,
                    fields: this.fields
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Template saved successfully!', 'success');
                // Reload fields to get proper IDs
                await this.loadExistingFields();
            } else {
                throw new Error(data.message || 'Failed to save template');
            }
            
        } catch (error) {
            console.error('Error saving template:', error);
            this.showNotification('Failed to save template: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Preview template
    previewTemplate() {
        if (!this.templateId) return;
        
        const previewUrl = `index.php?page=pdf_viewer&template_id=${this.templateId}`;
        window.open(previewUrl, '_blank');
    }

    // Navigation
    goBackToManager() {
        window.location.href = 'index.php?page=pdf_template_manager';
    }

    // Keyboard shortcuts
    handleKeyboardShortcuts(e) {
        if (e.ctrlKey || e.metaKey) {
            switch (e.key) {
                case 's':
                    e.preventDefault();
                    this.saveTemplate();
                    break;
                case 'z':
                    e.preventDefault();
                    // TODO: Implement undo
                    break;
                case 'd':
                    e.preventDefault();
                    if (this.selectedField) {
                        this.duplicateField();
                    }
                    break;
            }
        }
        
        // Delete key
        if (e.key === 'Delete' && this.selectedField) {
            this.deleteField();
        }
        
        // Escape key
        if (e.key === 'Escape') {
            this.clearActiveFieldType();
            this.selectedField = null;
            this.renderFields();
            this.propertiesForm.innerHTML = '<p class="no-field-selected">No field selected</p>';
        }
    }

    // Window resize handler
    handleResize() {
        // Simple resize handler - just re-render page
        console.log('Window resized, re-rendering page');
        if (this.pdfDoc) {
            this.renderPage();
        }
    }

    // Drag and Drop functionality
    initializeDragAndDrop() {
        // Make all draggable fields draggable
        this.setupDraggableFields();
        
        // Setup drop zone on PDF canvas
        this.setupDropZone();
    }
    
    setupDraggableFields() {
        const draggableFields = document.querySelectorAll('.draggable-field');
        
        draggableFields.forEach(field => {
            field.draggable = true;
            field.addEventListener('dragstart', (e) => this.handleDragStart(e));
            field.addEventListener('dragend', (e) => this.handleDragEnd(e));
        });
    }
    
    setupDropZone() {
        const pdfContainer = document.getElementById('pdfContainer');
        if (!pdfContainer) return;
        
        pdfContainer.addEventListener('dragover', (e) => this.handleDragOver(e));
        pdfContainer.addEventListener('drop', (e) => this.handleDrop(e));
        pdfContainer.addEventListener('dragenter', (e) => this.handleDragEnter(e));
        pdfContainer.addEventListener('dragleave', (e) => this.handleDragLeave(e));
    }
    
    handleDragStart(e) {
        const field = e.target.closest('.draggable-field');
        if (!field) return;
        
        // Store field data for drop
        const fieldData = {
            name: field.dataset.fieldName,
            type: field.dataset.fieldType,
            label: field.dataset.fieldLabel || field.dataset.fieldName
        };
        
        e.dataTransfer.setData('application/json', JSON.stringify(fieldData));
        e.dataTransfer.effectAllowed = 'copy';
        
        // Add dragging class for visual feedback
        field.classList.add('dragging');
        
        console.log('Drag started for field:', fieldData);
    }
    
    handleDragEnd(e) {
        const field = e.target.closest('.draggable-field');
        if (field) {
            field.classList.remove('dragging');
        }
    }
    
    handleDragEnter(e) {
        e.preventDefault();
        this.showDropZone();
    }
    
    handleDragLeave(e) {
        // Only hide if we're leaving the container entirely
        const pdfContainer = document.getElementById('pdfContainer');
        if (!pdfContainer.contains(e.relatedTarget)) {
            this.hideDropZone();
        }
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }
    
    handleDrop(e) {
        e.preventDefault();
        this.hideDropZone();
        
        // Prevent duplicate drops
        if (this.isDropping) {
            console.log('Drop already in progress, ignoring duplicate');
            return;
        }
        this.isDropping = true;
        
        try {
            const fieldData = JSON.parse(e.dataTransfer.getData('application/json'));
            if (!fieldData) {
                this.isDropping = false;
                return;
            }
            
            // Get drop position relative to PDF canvas
            const rect = this.canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Convert to PDF coordinates
            const pdfX = x / this.scale;
            const pdfY = y / this.scale;
            
            console.log('Dropped field at:', { x: pdfX, y: pdfY }, 'Field data:', fieldData);
            
            // Create field at drop position
            this.createFieldAtPosition(fieldData, pdfX, pdfY);
            
        } catch (error) {
            console.error('Error handling drop:', error);
            this.showNotification('Error creating field', 'error');
        } finally {
            // Reset drop flag after a short delay
            setTimeout(() => {
                this.isDropping = false;
            }, 100);
        }
    }
    
    showDropZone() {
        let overlay = document.getElementById('dropZoneOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'dropZoneOverlay';
            overlay.className = 'drop-zone-overlay';
            overlay.innerHTML = `
                <div class="drop-zone-message">
                    <span class="icon">+</span>
                    <p>Drop field here to position it</p>
                </div>
            `;
            document.getElementById('pdfContainer').appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }
    
    hideDropZone() {
        const overlay = document.getElementById('dropZoneOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
    
    createFieldAtPosition(fieldData, x, y) {
        // Check if a field with similar position already exists (prevent duplicates)
        const existingField = this.fields.find(field => 
            Math.abs(field.x_position - x) < 5 && 
            Math.abs(field.y_position - y) < 5 && 
            field.field_name === fieldData.name
        );
        
        if (existingField) {
            console.log('Field already exists at this position, skipping duplicate');
            this.selectField(existingField);
            return;
        }
        
        // Generate unique ID for the field
        const fieldId = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Create field object
        const field = {
            id: fieldId,
            temp_id: fieldId,
            field_name: fieldData.name,
            field_type: fieldData.type,
            field_label: fieldData.label,
            x_position: x,
            y_position: y,
            width: 120, // Default width
            height: 25, // Default height
            page_number: this.currentPage,
            font_size: 12,
            font_family: 'Arial',
            font_color: '#000000',
            is_required: false,
            default_value: '',
            created_date: new Date().toISOString()
        };
        
        // Add to fields array
        this.fields.push(field);
        
        // Render the field on the overlay
        this.renderFieldOnOverlay(field);
        
        // Update fields list
        this.updateFieldsList();
        
        // Select the new field
        this.selectField(field);
        
        console.log('Created field:', field);
        this.showNotification('Field "' + fieldData.label + '" added successfully', 'success');
    }
    
    renderFieldOnOverlay(field) {
        if (!this.fieldOverlay) return;
        
        // Create field element using the same method as createFieldElement for consistency
        const fieldElement = this.createFieldElement(field);
        
        // Override the class to use positioned-field for dropped fields
        fieldElement.className = 'positioned-field';
        
        // Update the content to show field label
        fieldElement.innerHTML = `
            <div class="field-label" style="pointer-events: none;">${field.field_label || field.field_name}</div>
        `;
        
        this.fieldOverlay.appendChild(fieldElement);
    }
    
    ensureFieldEvents(fieldElement, field) {
        // Remove any existing event listeners to prevent duplicates
        const newElement = fieldElement.cloneNode(true);
        fieldElement.parentNode.replaceChild(newElement, fieldElement);
        
        // Add fresh event listeners
        newElement.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!this.isRepositioning) {
                console.log('Field clicked:', field.field_name);
                this.selectField(field);
            }
        });
        
        newElement.addEventListener('mousedown', (e) => {
            if (e.button === 0) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Field mousedown:', field.field_name);
                this.startFieldRepositioning(e, field);
            }
        });
        
        console.log('Events bound to field:', field.field_name);
    }
    
    selectField(field) {
        // Remove selection from other fields
        document.querySelectorAll('.positioned-field.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Select current field
        const fieldElement = document.querySelector('[data-field-id="' + (field.id || field.temp_id) + '"]');
        if (fieldElement) {
            fieldElement.classList.add('selected');
        }
        
        this.selectedField = field;
        this.showFieldProperties(field);
    }
    
    showFieldProperties(field) {
        const propertiesForm = document.getElementById('fieldPropertiesForm');
        if (!propertiesForm) return;
        
        propertiesForm.innerHTML = `
            <div class="form-group">
                <label for="fieldName">Field Name</label>
                <input type="text" id="fieldName" value="${field.field_name}" readonly>
            </div>
            <div class="form-group">
                <label for="fieldLabel">Display Label</label>
                <input type="text" id="fieldLabel" value="${field.field_label || field.field_name}">
            </div>
            <div class="form-group">
                <label for="fieldType">Field Type</label>
                <select id="fieldType">
                    <option value="text" ${field.field_type === 'text' ? 'selected' : ''}>Text</option>
                    <option value="number" ${field.field_type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="date" ${field.field_type === 'date' ? 'selected' : ''}>Date</option>
                    <option value="checkbox" ${field.field_type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="fieldX">X Position</label>
                    <input type="number" id="fieldX" value="${Math.round(field.x_position)}" step="1">
                </div>
                <div class="form-group">
                    <label for="fieldY">Y Position</label>
                    <input type="number" id="fieldY" value="${Math.round(field.y_position)}" step="1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="fieldWidth">Width</label>
                    <input type="number" id="fieldWidth" value="${field.width}" step="1">
                </div>
                <div class="form-group">
                    <label for="fieldHeight">Height</label>
                    <input type="number" id="fieldHeight" value="${field.height}" step="1">
                </div>
            </div>
            <div class="form-group">
                <label for="fontSize">Font Size</label>
                <input type="number" id="fontSize" value="${field.font_size}" min="8" max="72">
            </div>
            <div class="form-group">
                <label for="fontColor">Font Color</label>
                <input type="color" id="fontColor" value="${field.font_color}">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="templateBuilder.updateSelectedField()">Update Field</button>
                <button type="button" class="btn btn-danger" onclick="templateBuilder.deleteSelectedField()">Delete Field</button>
            </div>
        `;
    }
    
    updateSelectedField() {
        if (!this.selectedField) return;
        
        // Get updated values from form
        const fieldLabel = document.getElementById('fieldLabel')?.value;
        const fieldType = document.getElementById('fieldType')?.value;
        const fieldX = parseFloat(document.getElementById('fieldX')?.value);
        const fieldY = parseFloat(document.getElementById('fieldY')?.value);
        const fieldWidth = parseFloat(document.getElementById('fieldWidth')?.value);
        const fieldHeight = parseFloat(document.getElementById('fieldHeight')?.value);
        const fontSize = parseInt(document.getElementById('fontSize')?.value);
        const fontColor = document.getElementById('fontColor')?.value;
        
        // Update field object
        this.selectedField.field_label = fieldLabel;
        this.selectedField.field_type = fieldType;
        this.selectedField.x_position = fieldX;
        this.selectedField.y_position = fieldY;
        this.selectedField.width = fieldWidth;
        this.selectedField.height = fieldHeight;
        this.selectedField.font_size = fontSize;
        this.selectedField.font_color = fontColor;
        
        // Re-render field
        this.rerenderField(this.selectedField);
        this.updateFieldsList();
        
        this.showNotification('Field updated successfully', 'success');
    }
    
    deleteSelectedField() {
        if (!this.selectedField) return;
        
        if (confirm('Are you sure you want to delete this field?')) {
            // Remove from fields array
            this.fields = this.fields.filter(f => (f.id || f.temp_id) !== (this.selectedField.id || this.selectedField.temp_id));
            
            // Remove from overlay
            const fieldElement = document.querySelector('[data-field-id="' + (this.selectedField.id || this.selectedField.temp_id) + '"]');
            if (fieldElement) {
                fieldElement.remove();
            }
            
            // Clear selection
            this.selectedField = null;
            document.getElementById('fieldPropertiesForm').innerHTML = '<p class="no-field-selected">Click on a field or create a new one to edit properties</p>';
            
            this.updateFieldsList();
            this.showNotification('Field deleted successfully', 'success');
        }
    }
    
    rerenderField(field) {
        // Remove existing element
        const existingElement = document.querySelector('[data-field-id="' + (field.id || field.temp_id) + '"]');
        if (existingElement) {
            existingElement.remove();
        }
        
        // Re-render
        this.renderFieldOnOverlay(field);
        
        // Re-select if it was selected
        if (this.selectedField && (this.selectedField.id || this.selectedField.temp_id) === (field.id || field.temp_id)) {
            this.selectField(field);
        }
    }
    
    updateFieldsList() {
        const fieldsList = document.getElementById('fieldsList');
        if (!fieldsList) return;
        
        if (this.fields.length === 0) {
            fieldsList.innerHTML = '<p class="no-fields">No fields added yet. Drag fields from the sidebar to get started.</p>';
            return;
        }
        
        fieldsList.innerHTML = this.fields.map(field => `
            <div class="field-list-item ${this.selectedField && (this.selectedField.id || this.selectedField.temp_id) === (field.id || field.temp_id) ? 'selected' : ''}" 
                 onclick="templateBuilder.selectField(${JSON.stringify(field).replace(/"/g, '&quot;')})">
                <div class="field-info">
                    <span class="field-name">${field.field_label || field.field_name}</span>
                    <span class="field-type">${field.field_type}</span>
                </div>
                <div class="field-position">
                    ${Math.round(field.x_position)}, ${Math.round(field.y_position)}
                </div>
            </div>
        `).join('');
    }
    
    // Fix layout issues
    handleResize() {
        // Simple resize handler - just re-render fields
        console.log('Window resized, re-rendering fields');
        this.rerenderAllFields();
    }
    
    rerenderAllFields() {
        // Clear existing field elements
        if (this.fieldOverlay) {
            this.fieldOverlay.innerHTML = '';
        }
        
        // Re-render all fields
        this.fields.forEach(field => {
            if (field.page_number === this.currentPage) {
                this.renderFieldOnOverlay(field);
            }
        });
        
        // Re-select current field if any
        if (this.selectedField) {
            this.selectField(this.selectedField);
        }
    }
    
    // Field repositioning functionality
    startFieldRepositioning(e, field) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Starting field repositioning for:', field.field_name);
        
        // Select the field first
        this.selectField(field);
        
        // Set up repositioning state
        this.isRepositioning = true;
        this.repositioningField = field;
        
        // Get initial mouse position
        const rect = this.canvas.getBoundingClientRect();
        this.repositionStartPos = {
            mouseX: e.clientX,
            mouseY: e.clientY,
            fieldX: field.x_position,
            fieldY: field.y_position
        };
        
        // Add event listeners for mouse move and up
        this.boundRepositionMove = this.handleFieldRepositionMove.bind(this);
        this.boundRepositionEnd = this.handleFieldRepositionEnd.bind(this);
        document.addEventListener('mousemove', this.boundRepositionMove);
        document.addEventListener('mouseup', this.boundRepositionEnd);
        
        // Add visual feedback
        const fieldElement = document.querySelector(`[data-field-id="${field.id || field.temp_id}"]`);
        if (fieldElement) {
            fieldElement.classList.add('repositioning');
            fieldElement.style.cursor = 'grabbing';
            fieldElement.style.opacity = '0.8';
            fieldElement.style.zIndex = '1000';
        }
        
        console.log('Started repositioning field:', field.field_name);
    }
    
    handleFieldRepositionMove(e) {
        if (!this.isRepositioning || !this.repositioningField) {
            console.log('Not repositioning or no field:', this.isRepositioning, this.repositioningField);
            return;
        }
        
        e.preventDefault();
        console.log('Moving field:', this.repositioningField.field_name);
        
        // Calculate movement delta
        const deltaX = e.clientX - this.repositionStartPos.mouseX;
        const deltaY = e.clientY - this.repositionStartPos.mouseY;
        
        // Convert to PDF coordinates
        const pdfDeltaX = deltaX / this.scale;
        const pdfDeltaY = deltaY / this.scale;
        
        // Calculate new position
        const newX = this.repositionStartPos.fieldX + pdfDeltaX;
        const newY = this.repositionStartPos.fieldY + pdfDeltaY;
        
        // Constrain to canvas bounds
        const canvasWidth = this.canvas ? (this.canvas.width / this.scale) : 800;
        const canvasHeight = this.canvas ? (this.canvas.height / this.scale) : 600;
        
        const constrainedX = Math.max(0, Math.min(newX, canvasWidth - this.repositioningField.width));
        const constrainedY = Math.max(0, Math.min(newY, canvasHeight - this.repositioningField.height));
        
        // Update field position
        this.repositioningField.x_position = constrainedX;
        this.repositioningField.y_position = constrainedY;
        
        // Update visual position
        const fieldElement = document.querySelector(`[data-field-id="${this.repositioningField.id || this.repositioningField.temp_id}"]`);
        if (fieldElement) {
            fieldElement.style.left = (constrainedX * this.scale) + 'px';
            fieldElement.style.top = (constrainedY * this.scale) + 'px';
        }
        
        // Update properties panel if this field is selected
        if (this.selectedField && (this.selectedField.id || this.selectedField.temp_id) === (this.repositioningField.id || this.repositioningField.temp_id)) {
            const fieldXInput = document.getElementById('fieldX');
            const fieldYInput = document.getElementById('fieldY');
            if (fieldXInput) fieldXInput.value = Math.round(constrainedX);
            if (fieldYInput) fieldYInput.value = Math.round(constrainedY);
        }
    }
    
    handleFieldRepositionEnd(e) {
        if (!this.isRepositioning || !this.repositioningField) return;
        
        e.preventDefault();
        
        // Remove event listeners
        document.removeEventListener('mousemove', this.boundRepositionMove);
        document.removeEventListener('mouseup', this.boundRepositionEnd);
        
        // Reset visual feedback
        const fieldElement = document.querySelector(`[data-field-id="${this.repositioningField.id || this.repositioningField.temp_id}"]`);
        if (fieldElement) {
            fieldElement.classList.remove('repositioning');
            fieldElement.style.cursor = 'grab';
            fieldElement.style.opacity = '1';
            fieldElement.style.zIndex = '10';
        }
        
        // Update fields list
        this.updateFieldsList();
        
        // Show notification
        this.showNotification(`Field "${this.repositioningField.field_label || this.repositioningField.field_name}" repositioned`, 'success');
        
        console.log('Finished repositioning field to:', {
            x: this.repositioningField.x_position,
            y: this.repositioningField.y_position
        });
        
        // Reset repositioning state
        this.isRepositioning = false;
        this.repositioningField = null;
        this.repositionStartPos = null;
    }
    
    // Enhanced field selection to prevent repositioning conflicts
    selectField(field) {
        // Don't change selection while repositioning
        if (this.isRepositioning) return;
        
        // Remove selection from other fields
        document.querySelectorAll('.positioned-field.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Select current field
        const fieldElement = document.querySelector(`[data-field-id="${field.id || field.temp_id}"]`);
        if (fieldElement) {
            fieldElement.classList.add('selected');
        }
        
        this.selectedField = field;
        
        // Auto-expand properties section when field is selected
        this.autoExpandSection('properties');
        
        this.showFieldProperties(field);
    }
    
    // Sidebar collapse/expand functionality with accordion behavior
    toggleSidebar(sectionName) {
        const content = document.getElementById(sectionName + 'Content');
        const toggleButton = content?.parentElement.querySelector('.sidebar-toggle');
        const toggleIcon = toggleButton?.querySelector('.toggle-icon');
        
        if (!content || !toggleButton || !toggleIcon) {
            console.warn('Sidebar elements not found for:', sectionName);
            return;
        }
        
        const isCollapsed = content.classList.contains('collapsed');
        
        if (isCollapsed) {
            // Collapse all other sections first (accordion behavior)
            this.collapseAllSections();
            
            // Expand this section
            content.classList.remove('collapsed');
            toggleIcon.textContent = '-';
            toggleButton.setAttribute('title', 'Collapse ' + this.getSectionTitle(sectionName) + ' Panel');
            
            // Save state to localStorage
            this.saveSidebarState(sectionName, true);
        } else {
            // Collapse this section
            content.classList.add('collapsed');
            toggleIcon.textContent = '+';
            toggleButton.setAttribute('title', 'Expand ' + this.getSectionTitle(sectionName) + ' Panel');
            
            // Save state to localStorage
            this.saveSidebarState(sectionName, false);
        }
    }
    
    collapseAllSections() {
        const sections = ['fields', 'tools', 'properties', 'list'];
        
        sections.forEach(sectionName => {
            const content = document.getElementById(sectionName + 'Content');
            const toggleButton = content?.parentElement.querySelector('.sidebar-toggle');
            const toggleIcon = toggleButton?.querySelector('.toggle-icon');
            
            if (content && toggleButton && toggleIcon) {
                content.classList.add('collapsed');
                toggleIcon.textContent = '+';
                toggleButton.setAttribute('title', 'Expand ' + this.getSectionTitle(sectionName) + ' Panel');
                this.saveSidebarState(sectionName, false);
            }
        });
    }
    
    getSectionTitle(sectionName) {
        const titles = {
            'fields': 'Fields',
            'tools': 'Tools',
            'properties': 'Properties',
            'list': 'Fields List'
        };
        return titles[sectionName] || sectionName;
    }
    
    saveSidebarState(sectionName, isExpanded) {
        const sidebarStates = JSON.parse(localStorage.getItem('templateBuilderSidebar') || '{}');
        sidebarStates[sectionName] = isExpanded;
        localStorage.setItem('templateBuilderSidebar', JSON.stringify(sidebarStates));
    }
    
    loadSidebarStates() {
        const sidebarStates = JSON.parse(localStorage.getItem('templateBuilderSidebar') || '{}');
        const sections = ['fields', 'tools', 'properties', 'list'];
        
        // Find which section should be expanded (accordion behavior - only one at a time)
        let expandedSection = null;
        for (const sectionName of sections) {
            if (sidebarStates[sectionName] === true) {
                expandedSection = sectionName;
                break;
            }
        }
        
        // If no section is marked as expanded, default to 'fields'
        if (!expandedSection) {
            expandedSection = 'fields';
        }
        
        sections.forEach(sectionName => {
            const content = document.getElementById(sectionName + 'Content');
            const toggleButton = content?.parentElement.querySelector('.sidebar-toggle');
            const toggleIcon = toggleButton?.querySelector('.toggle-icon');
            
            if (!content || !toggleButton || !toggleIcon) return;
            
            const isExpanded = sectionName === expandedSection;
            
            if (!isExpanded) {
                content.classList.add('collapsed');
                toggleIcon.textContent = '+';
                toggleButton.setAttribute('title', 'Expand ' + this.getSectionTitle(sectionName) + ' Panel');
            } else {
                content.classList.remove('collapsed');
                toggleIcon.textContent = '-';
                toggleButton.setAttribute('title', 'Collapse ' + this.getSectionTitle(sectionName) + ' Panel');
            }
        });
    }
    
    autoExpandSection(sectionName) {
        const content = document.getElementById(sectionName + 'Content');
        if (!content || content.classList.contains('collapsed')) {
            this.toggleSidebar(sectionName);
        }
    }

    // Debug function to test repositioning
    testRepositioning() {
        console.log('Testing repositioning functionality...');
        console.log('Fields available:', this.fields);
        console.log('Canvas element:', this.canvas);
        console.log('Field overlay:', this.fieldOverlay);
        
        if (this.fields.length > 0) {
            const testField = this.fields[0];
            console.log('Test field:', testField);
            
            // Try to find the field element
            const fieldElement = document.querySelector(`[data-field-id="${testField.id || testField.temp_id}"]`);
            console.log('Field element found:', fieldElement);
            
            if (fieldElement) {
                console.log('Field element styles:', {
                    left: fieldElement.style.left,
                    top: fieldElement.style.top,
                    position: fieldElement.style.position
                });
            }
        }
        
        return {
            fields: this.fields.length,
            canvas: !!this.canvas,
            overlay: !!this.fieldOverlay,
            repositioning: this.isRepositioning
        };
    }

    // Utility methods
    showLoading(message = 'Loading...') {
        // Create or update loading overlay
        let overlay = document.getElementById('loadingOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            document.body.appendChild(overlay);
        }
        
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p>${message}</p>
            </div>
        `;
        overlay.style.display = 'flex';
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.templateBuilder = new TemplateBuilderNew();
});