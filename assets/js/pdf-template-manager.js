/**
 * PDF Template Manager JavaScript
 */

class PdfTemplateManager {
    constructor() {
        this.currentTemplateId = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeDropdowns();
    }

    bindEvents() {
        // Upload button
        document.getElementById('uploadPdfBtn').addEventListener('click', () => {
            this.showUploadModal();
        });

        // Modal close events
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.closeModal(e.target.closest('.modal-overlay'));
            });
        });

        // Close modal on outside click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        });

        // File input change
        document.getElementById('pdfFile').addEventListener('change', (e) => {
            this.handleFileSelection(e);
        });
    }

    initializeDropdowns() {
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = toggle.closest('.dropdown');
                const isActive = dropdown.classList.contains('active');
                
                // Close all dropdowns
                document.querySelectorAll('.dropdown.active').forEach(d => {
                    d.classList.remove('active');
                });
                
                // Toggle current dropdown
                if (!isActive) {
                    dropdown.classList.add('active');
                }
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown.active').forEach(d => {
                d.classList.remove('active');
            });
        });
    }

    showUploadModal() {
        document.getElementById('uploadModal').style.display = 'flex';
        document.getElementById('pdfFile').value = '';
        document.getElementById('templateName').value = '';
        document.getElementById('templateDescription').value = '';
    }

    closeUploadModal() {
        this.closeModal(document.getElementById('uploadModal'));
    }

    closeModal(modal) {
        modal.style.display = 'none';
    }

    handleFileSelection(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (file.type !== 'application/pdf') {
            this.showNotification('Please select a PDF file', 'error');
            e.target.value = '';
            return;
        }

        // Validate file size (10MB limit)
        if (file.size > 10 * 1024 * 1024) {
            this.showNotification('File size must be less than 10MB', 'error');
            e.target.value = '';
            return;
        }

        // Auto-fill template name if empty
        const templateNameInput = document.getElementById('templateName');
        if (!templateNameInput.value) {
            templateNameInput.value = file.name.replace('.pdf', '');
        }
    }

    async uploadPdfTemplate() {
        const form = document.getElementById('uploadForm');
        const formData = new FormData(form);
        const file = document.getElementById('pdfFile').files[0];

        if (!file) {
            this.showNotification('Please select a PDF file', 'error');
            return;
        }

        try {
            this.showLoading('Uploading PDF template...');

            const response = await fetch('api/pdf_template_upload_new.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('PDF template uploaded successfully!', 'success');
                this.closeUploadModal();
                
                // Reload the page to show the new template
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.showNotification('Upload failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Network error during upload', 'error');
        } finally {
            this.hideLoading();
        }
    }

    editTemplate(templateId) {
        // Redirect to template builder with edit mode
        window.location.href = `index.php?page=template_builder&edit_template=${templateId}`;
    }

    openStandaloneBuilder(templateId) {
        // Open standalone template builder in new tab (maximized)
        const standaloneUrl = `pages/template_builder_standalone.php?edit_template=${templateId}`;
        const newWindow = window.open(standaloneUrl, '_blank', 'scrollbars=yes,resizable=yes');
        
        // Try to maximize the window
        if (newWindow) {
            newWindow.moveTo(0, 0);
            newWindow.resizeTo(screen.availWidth, screen.availHeight);
        }
    }

    previewTemplate(templateId) {
        // Open PDF viewer in new tab
        window.open(`index.php?page=pdf_viewer&template_id=${templateId}`, '_blank');
    }

    assignDocumentType(templateId) {
        this.currentTemplateId = templateId;
        document.getElementById('assignTemplateId').value = templateId;
        document.getElementById('assignTypeModal').style.display = 'flex';
    }

    closeAssignTypeModal() {
        this.closeModal(document.getElementById('assignTypeModal'));
        this.currentTemplateId = null;
    }

    async saveDocumentTypeAssignment() {
        const form = document.getElementById('assignTypeForm');
        const formData = new FormData(form);

        try {
            this.showLoading('Assigning document type...');

            const response = await fetch('api/pdf_template_assign_new.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Document type assigned successfully!', 'success');
                this.closeAssignTypeModal();
                
                // Update the template card
                this.updateTemplateCard(this.currentTemplateId, result.template);
            } else {
                this.showNotification('Assignment failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Assignment error:', error);
            this.showNotification('Network error during assignment', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async downloadTemplate(templateId) {
        try {
            this.showLoading('Preparing download...');
            
            // Create a temporary link to download the file
            const response = await fetch(`api/serve_pdf_direct.php?template_id=${templateId}`);
            
            if (!response.ok) {
                throw new Error('Download failed');
            }
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `template_${templateId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.showNotification('Template downloaded successfully!', 'success');
        } catch (error) {
            console.error('Download error:', error);
            this.showNotification('Failed to download template', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async duplicateTemplate(templateId) {
        if (!confirm('Are you sure you want to duplicate this template?')) {
            return;
        }

        try {
            this.showLoading('Duplicating template...');

            const response = await fetch('api/template_management_new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'duplicate_template',
                    template_id: templateId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Template duplicated successfully!', 'success');
                // Reload the page to show the new template
                window.location.reload();
            } else {
                this.showNotification('Duplication failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Duplication error:', error);
            this.showNotification('Network error during duplication', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async renameTemplate(templateId) {
        const currentName = document.querySelector(`[data-template-id="${templateId}"] .template-name`).textContent;
        const newName = prompt('Enter new template name:', currentName);
        
        if (!newName || newName.trim() === '' || newName === currentName) {
            return;
        }

        try {
            this.showLoading('Renaming template...');

            const response = await fetch('api/template_management_new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'rename_template',
                    template_id: templateId,
                    new_name: newName.trim()
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Template renamed successfully!', 'success');
                // Update the template name in the UI
                document.querySelector(`[data-template-id="${templateId}"] .template-name`).textContent = newName.trim();
            } else {
                this.showNotification('Rename failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Rename error:', error);
            this.showNotification('Network error during rename', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async deleteTemplate(templateId) {
        const templateName = document.querySelector(`[data-template-id="${templateId}"] .template-name`).textContent;
        
        if (!confirm(`Are you sure you want to delete "${templateName}"?\n\nThis action cannot be undone.`)) {
            return;
        }

        try {
            this.showLoading('Deleting template...');

            const response = await fetch('api/simple_delete_template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    template_id: templateId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Template deleted successfully!', 'success');
                // Remove the template card from the UI
                const templateCard = document.querySelector(`[data-template-id="${templateId}"]`);
                if (templateCard) {
                    templateCard.remove();
                }
                // Also refresh the entire template list to ensure consistency
                setTimeout(() => {
                    this.loadTemplates();
                }, 500);
                
                // Check if no templates remain
                const remainingCards = document.querySelectorAll('.template-card');
                if (remainingCards.length === 0) {
                    window.location.reload();
                }
            } else {
                this.showNotification('Deletion failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Deletion error:', error);
            this.showNotification('Network error during deletion', 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Utility methods
    updateTemplateCard(templateId, templateData) {
        const card = document.querySelector(`[data-template-id="${templateId}"]`);
        if (!card) return;

        // Update document type badge
        const typeContainer = card.querySelector('.assigned-type, .unassigned-type');
        if (templateData.document_type_name) {
            typeContainer.className = 'assigned-type';
            typeContainer.innerHTML = `<span class="type-badge">${templateData.document_type_name}</span>`;
        } else {
            typeContainer.className = 'unassigned-type';
            typeContainer.innerHTML = `<span class="unassigned-badge">Not Assigned</span>`;
        }
    }

    showLoading(message = 'Loading...') {
        // Create or update loading overlay
        let overlay = document.getElementById('loadingOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            document.body.appendChild(overlay);
        }
        
        overlay.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <div style="width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top: 4px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                <p style="margin: 0; color: #374151;">${message}</p>
            </div>
        `;
        overlay.style.display = 'flex';
        
        // Add CSS animation if not already added
        if (!document.getElementById('loadingStyles')) {
            const style = document.createElement('style');
            style.id = 'loadingStyles';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
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
        
        const colors = {
            success: { bg: '#d1fae5', border: '#a7f3d0', text: '#065f46' },
            error: { bg: '#fee2e2', border: '#fecaca', text: '#991b1b' },
            warning: { bg: '#fef3c7', border: '#fde68a', text: '#92400e' },
            info: { bg: '#dbeafe', border: '#bfdbfe', text: '#1e40af' }
        };
        
        const color = colors[type] || colors.info;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${color.bg};
            border: 1px solid ${color.border};
            color: ${color.text};
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10001;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="flex: 1; font-size: 0.875rem;">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: inherit; opacity: 0.7; padding: 0; width: 20px; height: 20px;">×</button>
            </div>
        `;
        
        // Add CSS animation if not already added
        if (!document.getElementById('notificationStyles')) {
            const style = document.createElement('style');
            style.id = 'notificationStyles';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    showUploadModal() {
        // Create upload modal if it doesn't exist
        if (!document.getElementById('uploadModal')) {
            this.createUploadModal();
        }
        document.getElementById('uploadModal').style.display = 'flex';
    }

    createUploadModal() {
        const modal = document.createElement('div');
        modal.id = 'uploadModal';
        modal.className = 'modal-overlay';
        modal.style.display = 'none';
        
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Upload PDF Template</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="pdfFile">Select PDF File</label>
                            <input type="file" id="pdfFile" name="pdf_file" accept=".pdf" required>
                            <small class="form-text">Maximum file size: 10MB</small>
                        </div>
                        <div class="form-group">
                            <label for="templateName">Template Name (Optional)</label>
                            <input type="text" id="templateName" name="template_name" placeholder="Leave blank to use filename">
                        </div>
                        <div class="form-group">
                            <label for="templateDescription">Description (Optional)</label>
                            <textarea id="templateDescription" name="description" rows="3" placeholder="Brief description of this template"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="pdfTemplateManager.closeUploadModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="pdfTemplateManager.uploadTemplate()">Upload Template</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Add event listeners
        modal.querySelector('.modal-close').addEventListener('click', () => {
            this.closeUploadModal();
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeUploadModal();
            }
        });
    }

    closeUploadModal() {
        const modal = document.getElementById('uploadModal');
        if (modal) {
            modal.style.display = 'none';
            // Reset form
            const form = document.getElementById('uploadForm');
            if (form) {
                form.reset();
            }
        }
    }

    async uploadTemplate() {
        const form = document.getElementById('uploadForm');
        const formData = new FormData(form);
        
        // Validate file selection
        const fileInput = document.getElementById('pdfFile');
        if (!fileInput.files.length) {
            this.showNotification('Please select a PDF file', 'error');
            return;
        }
        
        const file = fileInput.files[0];
        if (file.type !== 'application/pdf') {
            this.showNotification('Please select a valid PDF file', 'error');
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            this.showNotification('File size must be less than 10MB', 'error');
            return;
        }

        try {
            this.showLoading('Uploading template...');

            const response = await fetch('api/pdf_template_upload_new.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Template uploaded successfully!', 'success');
                this.closeUploadModal();
                // Reload the page to show the new template
                window.location.reload();
            } else {
                this.showNotification('Upload failed: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Network error during upload', 'error');
        } finally {
            this.hideLoading();
        }
    }



}

// Global functions for onclick handlers
function showUploadModal() {
    window.pdfTemplateManager.showUploadModal();
}

function closeUploadModal() {
    window.pdfTemplateManager.closeUploadModal();
}

function uploadPdfTemplate() {
    window.pdfTemplateManager.uploadPdfTemplate();
}

function editTemplate(templateId) {
    window.pdfTemplateManager.editTemplate(templateId);
}

function openStandaloneBuilder(templateId) {
    window.pdfTemplateManager.openStandaloneBuilder(templateId);
}

function previewTemplate(templateId) {
    window.pdfTemplateManager.previewTemplate(templateId);
}

function assignDocumentType(templateId) {
    window.pdfTemplateManager.assignDocumentType(templateId);
}

function closeAssignTypeModal() {
    window.pdfTemplateManager.closeAssignTypeModal();
}

function saveDocumentTypeAssignment() {
    window.pdfTemplateManager.saveDocumentTypeAssignment();
}

function downloadTemplate(templateId) {
    window.pdfTemplateManager.downloadTemplate(templateId);
}

function duplicateTemplate(templateId) {
    window.pdfTemplateManager.duplicateTemplate(templateId);
}

function renameTemplate(templateId) {
    window.pdfTemplateManager.renameTemplate(templateId);
}

function deleteTemplate(templateId) {
    window.pdfTemplateManager.deleteTemplate(templateId);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.pdfTemplateManager = new PdfTemplateManager();
});