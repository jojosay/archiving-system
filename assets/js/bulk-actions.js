/**
 * Bulk Actions for PDF Template Manager
 */

// Bulk Actions Functionality
let selectedTemplates = new Set();

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.template-select:checked');
    selectedTemplates.clear();
    
    checkboxes.forEach(checkbox => {
        selectedTemplates.add(parseInt(checkbox.value));
    });
    
    const selectedCount = selectedTemplates.size;
    const toolbar = document.getElementById('bulkActionsToolbar');
    const countElement = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllTemplates');
    
    if (countElement) {
        countElement.textContent = selectedCount;
    }
    
    if (toolbar) {
        toolbar.style.display = selectedCount > 0 ? 'block' : 'none';
    }
    
    // Update select all checkbox state
    if (selectAllCheckbox) {
        const allCheckboxes = document.querySelectorAll('.template-select');
        const checkedCheckboxes = document.querySelectorAll('.template-select:checked');
        
        if (checkedCheckboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedCheckboxes.length === allCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
    }
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllTemplates');
    const templateCheckboxes = document.querySelectorAll('.template-select');
    
    templateCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

function clearSelection() {
    const templateCheckboxes = document.querySelectorAll('.template-select');
    const selectAllCheckbox = document.getElementById('selectAllTemplates');
    
    templateCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
    
    updateBulkActions();
}

function bulkAssignDocumentType() {
    if (selectedTemplates.size === 0) {
        window.pdfTemplateManager.showNotification('Please select templates first', 'warning');
        return;
    }
    
    document.getElementById('bulkAssignCount').textContent = selectedTemplates.size;
    document.getElementById('bulkAssignTypeModal').style.display = 'flex';
}

function closeBulkAssignTypeModal() {
    document.getElementById('bulkAssignTypeModal').style.display = 'none';
    document.getElementById('bulkAssignTypeForm').reset();
}

async function saveBulkDocumentTypeAssignment() {
    const form = document.getElementById('bulkAssignTypeForm');
    const formData = new FormData(form);
    const documentTypeId = formData.get('document_type_id');
    const setAsDefault = formData.get('set_as_default') === 'on';
    
    if (!documentTypeId) {
        window.pdfTemplateManager.showNotification('Please select a document type', 'error');
        return;
    }
    
    try {
        window.pdfTemplateManager.showLoading('Assigning document type to selected templates...');
        
        const response = await fetch('api/bulk_template_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'bulk_assign_document_type',
                template_ids: Array.from(selectedTemplates),
                document_type_id: documentTypeId,
                set_as_default: setAsDefault
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.pdfTemplateManager.showNotification(result.message, 'success');
            closeBulkAssignTypeModal();
            clearSelection();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            window.pdfTemplateManager.showNotification('Assignment failed: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Bulk assignment error:', error);
        window.pdfTemplateManager.showNotification('Network error during assignment', 'error');
    } finally {
        window.pdfTemplateManager.hideLoading();
    }
}

function bulkDelete() {
    if (selectedTemplates.size === 0) {
        window.pdfTemplateManager.showNotification('Please select templates first', 'warning');
        return;
    }
    
    const templateNames = [];
    selectedTemplates.forEach(templateId => {
        const templateCard = document.querySelector('[data-template-id="' + templateId + '"]');
        if (templateCard) {
            const nameElement = templateCard.querySelector('.template-name');
            if (nameElement) {
                templateNames.push(nameElement.textContent.trim());
            }
        }
    });
    
    document.getElementById('bulkDeleteCount').textContent = selectedTemplates.size;
    
    const deleteList = document.getElementById('bulkDeleteList');
    deleteList.innerHTML = templateNames.map(name => 
        '<div class="template-item">• ' + name + '</div>'
    ).join('');
    
    document.getElementById('bulkDeleteModal').style.display = 'flex';
}

function closeBulkDeleteModal() {
    document.getElementById('bulkDeleteModal').style.display = 'none';
}

async function confirmBulkDelete() {
    try {
        window.pdfTemplateManager.showLoading('Deleting selected templates...');
        
        const response = await fetch('api/bulk_template_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'bulk_delete',
                template_ids: Array.from(selectedTemplates)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.pdfTemplateManager.showNotification(result.message, 'success');
            closeBulkDeleteModal();
            
            selectedTemplates.forEach(templateId => {
                const templateCard = document.querySelector('[data-template-id="' + templateId + '"]');
                if (templateCard) {
                    templateCard.remove();
                }
            });
            
            clearSelection();
            
            const remainingCards = document.querySelectorAll('.template-card');
            if (remainingCards.length === 0) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            window.pdfTemplateManager.showNotification('Delete failed: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Bulk delete error:', error);
        window.pdfTemplateManager.showNotification('Network error during delete', 'error');
    } finally {
        window.pdfTemplateManager.hideLoading();
    }
}

async function bulkDownload() {
    if (selectedTemplates.size === 0) {
        window.pdfTemplateManager.showNotification('Please select templates first', 'warning');
        return;
    }
    
    try {
        window.pdfTemplateManager.showLoading('Preparing downloads...');
        
        const response = await fetch('api/bulk_template_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'bulk_download',
                template_ids: Array.from(selectedTemplates)
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.templates) {
            window.pdfTemplateManager.showNotification('Downloading ' + result.download_count + ' template(s)...', 'success');
            
            for (const template of result.templates) {
                try {
                    const downloadResponse = await fetch('api/serve_pdf_direct.php?template_id=' + template.id);
                    
                    if (downloadResponse.ok) {
                        const blob = await downloadResponse.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = template.filename || 'template_' + template.id + '.pdf';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        await new Promise(resolve => setTimeout(resolve, 500));
                    }
                } catch (downloadError) {
                    console.error('Error downloading template ' + template.id + ':', downloadError);
                }
            }
            
            clearSelection();
        } else {
            window.pdfTemplateManager.showNotification('Download failed: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Bulk download error:', error);
        window.pdfTemplateManager.showNotification('Network error during download', 'error');
    } finally {
        window.pdfTemplateManager.hideLoading();
    }
}

function switchView(viewType) {
    const gridContainer = document.querySelector('.templates-grid');
    const viewButtons = document.querySelectorAll('.view-toggle');
    
    viewButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === viewType);
    });
    
    if (gridContainer) {
        gridContainer.className = viewType === 'list' ? 'templates-list' : 'templates-grid';
    }
}

// Initialize bulk actions when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Add event listeners for modal close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-overlay');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modals when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});