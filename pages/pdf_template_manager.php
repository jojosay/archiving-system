<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/layout.php';

// Initialize database and auth
$database = new Database();
$auth = new Auth($database);

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

$db = $database->getConnection();

// Get document types for filtering and assignment
$document_types = [];
try {
    $stmt = $db->prepare("SELECT id, name FROM document_types WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $document_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $document_types = [];
}

// Handle success/error messages
$success_message = $_GET['success'] ?? null;
$error_message = $_GET['error'] ?? null;

$pageTitle = "PDF Template Manager";
renderPageStart($pageTitle);
?>

<link rel="stylesheet" href="assets/css/pdf-template-manager.css">
<link rel="stylesheet" href="assets/css/pdf-template-manager-enhanced.css">
<link rel="stylesheet" href="assets/css/pdf-template-table.css">
<link rel="stylesheet" href="assets/css/pdf-template-cleanup.css">

<div class="pdf-template-manager">
    <!-- Header -->
    <div class="manager-header">
        <div class="header-content">
            <h1>PDF Template Manager</h1>
            <p>Upload, organize, and manage your PDF templates for document generation</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="window.pdfTemplateManager.refreshTemplates()">
                <i class="icon-refresh"></i>
                Refresh
            </button>
            <button class="btn btn-primary" onclick="window.pdfTemplateManager.showUploadModal()">
                <i class="icon-upload"></i>
                Upload Template
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success" style="margin-bottom: 2rem;">
            <?php 
            switch($success_message) {
                case 'upload_success':
                    echo 'Template uploaded successfully!';
                    break;
                case 'delete_success':
                    echo 'Template deleted successfully!';
                    break;
                case 'assign_success':
                    echo 'Document type assigned successfully!';
                    break;
                default:
                    echo htmlspecialchars($success_message);
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
            <?php 
            switch($error_message) {
                case 'upload_failed':
                    echo 'Failed to upload template. Please try again.';
                    break;
                case 'delete_failed':
                    echo 'Failed to delete template. Please try again.';
                    break;
                case 'template_not_found':
                    echo 'Template not found.';
                    break;
                default:
                    echo htmlspecialchars($error_message);
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-container" style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); padding: 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div>
                <label for="documentTypeFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Filter by Document Type:</label>
                <select id="documentTypeFilter" class="form-control" style="min-width: 200px;" onchange="window.pdfTemplateManager.filterTemplates()">
                    <option value="">All Document Types</option>
                    <?php foreach ($document_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="searchTemplates" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Search Templates:</label>
                <input type="text" id="searchTemplates" class="form-control" placeholder="Search by name..." style="min-width: 200px;" oninput="window.pdfTemplateManager.filterTemplates()">
            </div>
            <div style="margin-top: 1.5rem;">
                <button class="btn btn-outline" onclick="window.pdfTemplateManager.clearFilters()">Clear Filters</button>
            </div>
        </div>
    </div>

    <!-- Templates Container -->
    <div class="templates-container">
        <div class="templates-header">
            <div class="header-left">
                <h2>Your Templates</h2>
                <p>Manage and organize your PDF templates</p>
            </div>
            <div class="header-actions">
                <div class="templates-count">
                    <span>Total: </span>
                    <span id="templatesCount">0</span>
                </div>
            </div>
        </div>


        <!-- Empty State -->
        <div id="templatesEmpty" class="empty-state" style="display: none;">
            <div style="font-size: 3rem; color: #D1D5DB; margin-bottom: 1rem;">📄</div>
            <h3 style="color: #6B7280; margin-bottom: 0.5rem;">No Templates Found</h3>
            <p style="color: #9CA3AF; margin-bottom: 2rem;">Upload your first PDF template to get started</p>
            <button class="btn btn-primary" onclick="window.pdfTemplateManager.showUploadModal()">
                <i class="icon-upload"></i>
                Upload Template
            </button>
        </div>

        <!-- Templates Table -->
        <div id="templatesTable" class="templates-table-container" style="display: none;">
            <div class="table-wrapper">
                <table class="templates-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="name">
                                <span>Template Name</span>
                                <i class="sort-icon"></i>
                            </th>
                            <th class="sortable" data-sort="document_type">
                                <span>Document Type</span>
                                <i class="sort-icon"></i>
                            </th>
                            <th class="sortable" data-sort="pages">
                                <span>Pages</span>
                                <i class="sort-icon"></i>
                            </th>
                            <th class="sortable" data-sort="file_size">
                                <span>File Size</span>
                                <i class="sort-icon"></i>
                            </th>
                            <th class="sortable" data-sort="created_at">
                                <span>Created</span>
                                <i class="sort-icon"></i>
                            </th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="templatesTableBody">
                        <!-- Templates will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Upload PDF Template</h3>
            <button class="modal-close" onclick="window.pdfTemplateManager.hideUploadModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="templateFile">PDF File *</label>
                    <input type="file" id="templateFile" name="template_file" accept=".pdf" required class="form-control">
                    <small class="form-text">Select a PDF file to use as a template</small>
                </div>
                
                <div class="form-group">
                    <label for="templateName">Template Name *</label>
                    <input type="text" id="templateName" name="template_name" required class="form-control" placeholder="Enter template name">
                </div>
                
                <div class="form-group">
                    <label for="templateDescription">Description</label>
                    <textarea id="templateDescription" name="description" class="form-control" rows="3" placeholder="Optional description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="documentType">Document Type</label>
                    <select id="documentType" name="document_type_id" class="form-control">
                        <option value="">Select document type (optional)</option>
                        <?php foreach ($document_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="setAsDefault" name="set_as_default">
                        Set as default template for selected document type
                    </label>
                </div>
            </form>
            
            <div id="uploadProgress" style="display: none; margin-top: 1rem;">
                <div class="progress-bar">
                    <div class="progress-fill" id="uploadProgressBar"></div>
                </div>
                <p id="uploadProgressText">Uploading...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="window.pdfTemplateManager.hideUploadModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="window.pdfTemplateManager.uploadTemplate()">Upload Template</button>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div id="assignmentModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Assign Document Type</h3>
            <button class="modal-close" onclick="window.pdfTemplateManager.hideAssignmentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="assignmentForm">
                <input type="hidden" id="assignTemplateId" name="template_id">
                
                <div class="form-group">
                    <label for="assignDocumentType">Document Type *</label>
                    <select id="assignDocumentType" name="document_type_id" required class="form-control">
                        <option value="">Select document type</option>
                        <?php foreach ($document_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="assignSetAsDefault" name="set_as_default">
                        Set as default template for this document type
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="window.pdfTemplateManager.hideAssignmentModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="window.pdfTemplateManager.assignDocumentType()">Assign</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button class="modal-close" onclick="window.pdfTemplateManager.hideDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this template? This action cannot be undone.</p>
            <p id="deleteTemplateName" style="font-weight: bold; color: #DC2626;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="window.pdfTemplateManager.hideDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="window.pdfTemplateManager.confirmDelete()">Delete Template</button>
        </div>
    </div>
</div>

<script>
// PDF Template Manager JavaScript - Fixed Version
class PdfTemplateManager {
    constructor() {
        this.templates = [];
        this.filteredTemplates = [];
        this.currentView = 'grid';
        this.selectedTemplateId = null;
        this.init();
    }

    init() {
        this.loadTemplates();
        this.bindEvents();
    }

    bindEvents() {
        // File input change for upload
        const templateFile = document.getElementById('templateFile');
        if (templateFile) {
            templateFile.addEventListener('change', (e) => this.handleFileSelection(e));
        }

        // Form submissions
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.uploadTemplate();
            });
        }

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.hideAllModals();
            }
        });

        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideAllModals();
            }
        });
    }

    async loadTemplates() {
        try {
            const response = await fetch('api/get_templates.php');
            const data = await response.json();
            
            if (data.success) {
                this.templates = data.templates || [];
                this.filteredTemplates = [...this.templates];
                this.renderTemplates();
            } else {
                console.error('Failed to load templates:', data.message);
                this.showNotification('Failed to load templates', 'error');
            }
        } catch (error) {
            console.error('Error loading templates:', error);
            this.showNotification('Error loading templates', 'error');
        }
    }

    renderTemplates() {
        const container = document.getElementById('templatesGrid');
        const empty = document.getElementById('templatesEmpty');

        if (this.filteredTemplates.length === 0) {
            if (container) container.style.display = 'none';
            if (empty) empty.style.display = 'block';
            return;
        }

        if (empty) empty.style.display = 'none';
        
        // Update to use table structure
        const tableContainer = document.getElementById('templatesTable');
        const tableBody = document.getElementById('templatesTableBody');
        
        if (tableContainer && tableBody) {
            tableContainer.style.display = 'block';
            tableBody.innerHTML = this.filteredTemplates.map(template => this.renderTemplateRow(template)).join('');
            
            // Update templates count
            this.updateTemplatesCount();
        }
    }

    renderTemplateRow(template) {
        const documentType = template.document_type_name || 'Unassigned';
        const isDefault = template.is_default;
        const statusBadge = isDefault ? 
            '<span class="status-badge status-default">Default</span>' : 
            '<span class="status-badge status-assigned">Active</span>';
        
        const createdDate = template.created_at ? 
            new Date(template.created_at).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            }) : 'Unknown';
        
        return `
            <tr class="template-row" data-template-id="${template.id}">
                <td class="template-name-cell">
                    <div class="template-name-wrapper">
                        <div class="template-name">${this.escapeHtml(template.name)}</div>
                        ${template.description ? `<div class="template-description">${this.escapeHtml(template.description)}</div>` : ''}
                    </div>
                </td>
                <td class="document-type-cell">
                    <span class="document-type-badge ${documentType === 'Unassigned' ? 'unassigned' : 'assigned'}">${documentType}</span>
                </td>
                <td class="pages-cell">
                    <span class="pages-count">${template.pages || 1}</span>
                </td>
                <td class="file-size-cell">
                    <span class="file-size">${this.formatFileSize(template.file_size)}</span>
                </td>
                <td class="created-date-cell">
                    <span class="created-date">${createdDate}</span>
                </td>
                <td class="status-cell">
                    ${statusBadge}
                </td>
                <td class="actions-cell">
                    <div class="action-buttons">
                        <button class="btn-action btn-primary" onclick="window.pdfTemplateManager.openStandaloneBuilder(${template.id})" title="Open Template Builder">
                            <span class="btn-text">Edit</span>
                        </button>
                        <button class="btn-action btn-secondary" onclick="window.pdfTemplateManager.showAssignmentModal(${template.id})" title="Assign to Document Type">
                            <span class="btn-text">Assign</span>
                        </button>
                        <button class="btn-action btn-danger" onclick="window.pdfTemplateManager.showDeleteModal(${template.id}, '${this.escapeHtml(template.name)}')" title="Delete Template">
                            <span class="btn-text">Delete</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    showUploadModal() {
        const modal = document.getElementById('uploadModal');
        if (modal) {
            modal.style.display = 'flex';
            // Reset form
            const form = document.getElementById('uploadForm');
            if (form) form.reset();
        }
    }

    hideUploadModal() {
        const modal = document.getElementById('uploadModal');
        if (modal) modal.style.display = 'none';
    }

    showAssignmentModal(templateId) {
        this.selectedTemplateId = templateId;
        const modal = document.getElementById('assignmentModal');
        if (modal) {
            modal.style.display = 'flex';
            // Set template ID in form
            const templateIdInput = document.getElementById('assignTemplateId');
            if (templateIdInput) templateIdInput.value = templateId;
        }
    }

    hideAssignmentModal() {
        const modal = document.getElementById('assignmentModal');
        if (modal) modal.style.display = 'none';
        this.selectedTemplateId = null;
    }

    showDeleteModal(templateId, templateName) {
        this.selectedTemplateId = templateId;
        const modal = document.getElementById('deleteModal');
        const nameElement = document.getElementById('deleteTemplateName');
        
        if (modal) modal.style.display = 'flex';
        if (nameElement) nameElement.textContent = templateName;
    }

    hideDeleteModal() {
        const modal = document.getElementById('deleteModal');
        if (modal) modal.style.display = 'none';
        this.selectedTemplateId = null;
    }

    hideAllModals() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => modal.style.display = 'none');
        this.selectedTemplateId = null;
    }

    async uploadTemplate() {
        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('templateFile');
        
        if (!form || !fileInput || !fileInput.files[0]) {
            this.showNotification('Please select a PDF file', 'error');
            return;
        }

        const formData = new FormData(form);
        
        try {
            this.showProgress('Uploading template...');
            
            const response = await fetch('api/pdf_template_upload_new.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Template uploaded successfully!', 'success');
                this.hideUploadModal();
                this.loadTemplates(); // Reload templates
            } else {
                this.showNotification(data.message || 'Upload failed', 'error');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Upload failed', 'error');
        } finally {
            this.hideProgress();
        }
    }

    async assignDocumentType() {
        const form = document.getElementById('assignmentForm');
        if (!form) return;

        const formData = new FormData(form);
        
        try {
            const response = await fetch('api/pdf_template_assign_new.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Document type assigned successfully!', 'success');
                this.hideAssignmentModal();
                this.loadTemplates(); // Reload templates
            } else {
                this.showNotification(data.message || 'Assignment failed', 'error');
            }
        } catch (error) {
            console.error('Assignment error:', error);
            this.showNotification('Assignment failed', 'error');
        }
    }

    async confirmDelete() {
        if (!this.selectedTemplateId) return;

        try {
            const response = await fetch('api/simple_delete_template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    template_id: this.selectedTemplateId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Template deleted successfully!', 'success');
                this.hideDeleteModal();
                // Force reload the page to refresh the template list
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.showNotification(data.message || 'Delete failed', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Delete failed', 'error');
        }
    }

    filterTemplates() {
        const searchTerm = document.getElementById('searchTemplates')?.value.toLowerCase() || '';
        const documentTypeFilter = document.getElementById('documentTypeFilter')?.value || '';

        this.filteredTemplates = this.templates.filter(template => {
            const matchesSearch = !searchTerm || 
                template.name.toLowerCase().includes(searchTerm) ||
                (template.description && template.description.toLowerCase().includes(searchTerm));
            
            const matchesType = !documentTypeFilter || 
                template.document_type_id == documentTypeFilter;

            return matchesSearch && matchesType;
        });

        this.renderTemplates();
    }

    clearFilters() {
        const searchInput = document.getElementById('searchTemplates');
        const typeSelect = document.getElementById('documentTypeFilter');
        
        if (searchInput) searchInput.value = '';
        if (typeSelect) typeSelect.value = '';
        
        this.filteredTemplates = [...this.templates];
        this.renderTemplates();
    }

    setViewMode(mode) {
        this.currentView = mode;
        
        // Update button states
        const gridBtn = document.getElementById('gridViewBtn');
        const listBtn = document.getElementById('listViewBtn');
        
        if (gridBtn && listBtn) {
            gridBtn.classList.toggle('active', mode === 'grid');
            listBtn.classList.toggle('active', mode === 'list');
        }
        
        this.renderTemplates();
    }

    refreshTemplates() {
        this.loadTemplates();
        this.showNotification('Templates refreshed', 'info');
    }

    editTemplate(templateId) {
        window.location.href = `index.php?page=template_builder&edit_template=${templateId}`;
    }

    openStandaloneBuilder(templateId) {
        console.log('Opening standalone builder for template:', templateId);
        const standaloneUrl = `pages/template_builder_standalone.php?edit_template=${templateId}`;
        console.log('URL:', standaloneUrl);
        window.open(standaloneUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    }

    handleFileSelection(event) {
        const file = event.target.files[0];
        const nameInput = document.getElementById('templateName');
        
        if (file && nameInput && !nameInput.value) {
            // Auto-fill template name from filename
            const name = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
            nameInput.value = name;
        }
    }

    showProgress(message) {
        const progressDiv = document.getElementById('uploadProgress');
        const progressText = document.getElementById('uploadProgressText');
        
        if (progressDiv) progressDiv.style.display = 'block';
        if (progressText) progressText.textContent = message;
    }

    hideProgress() {
        const progressDiv = document.getElementById('uploadProgress');
        if (progressDiv) progressDiv.style.display = 'none';
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
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

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    updateTemplatesCount() {
        const countElement = document.getElementById('templatesCount');
        if (countElement) {
            countElement.textContent = this.filteredTemplates.length;
        }
    }

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.pdfTemplateManager = new PdfTemplateManager();
});
</script>

<style>
.template-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    background: white;
    transition: all 0.2s ease;
}

.template-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.template-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.template-header h4 {
    margin: 0;
    color: #1f2937;
    font-size: 1.1rem;
}

.template-actions {
    display: flex;
    gap: 0.5rem;
}

.template-info p {
    margin: 0.5rem 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 4px;
}

.badge-primary {
    background: #3b82f6;
    color: white;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 1rem;
    max-width: 400px;
}

.notification-success {
    border-left: 4px solid #10b981;
}

.notification-error {
    border-left: 4px solid #ef4444;
}

.notification-info {
    border-left: 4px solid #3b82f6;
}

.notification button {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #6b7280;
}

#templatesGrid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: #4CAF50;
    width: 0%;
    transition: width 0.3s ease;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-text {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-outline {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}

.btn.active {
    background: #3b82f6;
    color: white;
}
</style>

<?php renderPageEnd(); ?>