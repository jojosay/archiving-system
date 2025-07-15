<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/template_manager.php';
require_once 'includes/template_category_manager.php';
require_once 'includes/template_database_setup.php';

// Initialize managers
$templateManager = new TemplateManager($database);
$categoryManager = new TemplateCategoryManager($database);
$dbSetup = new TemplateDatabaseSetup($database);

// Check if template system is set up
$system_status = $dbSetup->getSystemStatus();
$setup_required = !$system_status['tables']['all_exist'];

// Handle setup request
if (isset($_POST['action']) && $_POST['action'] === 'setup_template_system') {
    $setup_result = $dbSetup->createTables();
    $setup_required = !$setup_result['success'];
}

// Get templates and categories
$templates = $setup_required ? [] : $templateManager->getAllTemplates();
$categories = $setup_required ? [] : $categoryManager->getCategoriesWithCounts();
$stats = $setup_required ? [] : $templateManager->getTemplateStats();

renderPageStart('Template Management', 'template_management');
?>

<div class="page-header">
    <h1>Template Management</h1>
    <p>Manage document templates for DOCX, Excel, and PDF files</p>
</div>

<style>
/* Modern SaaS Design Styles */
.template-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.setup-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem;
    border-radius: 16px;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.setup-card h2 {
    margin-bottom: 1rem;
    font-size: 2rem;
    font-weight: 600;
}

.setup-card p {
    margin-bottom: 2rem;
    font-size: 1.1rem;
    opacity: 0.9;
}

.btn-setup {
    background: white;
    color: #667eea;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-setup:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    color: white;
    font-size: 1.5rem;
}

.action-bar {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #f8fafc;
    color: #475569;
    padding: 0.75rem 1.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    text-decoration: none;
    color: #334155;
}

.search-bar {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.template-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
    overflow: hidden;
    transition: all 0.3s ease;
}

.template-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.1);
}

.template-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}

.template-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.template-description {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.5;
}

.template-meta {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
}

.template-type {
    background: #667eea;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.75rem;
}

.template-downloads {
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.template-actions {
    padding: 1rem 1.5rem;
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.btn-download {
    background: #10b981;
    color: white;
}

.btn-download:hover {
    background: #059669;
    color: white;
    text-decoration: none;
}

.btn-edit {
    background: #f59e0b;
    color: white;
}

.btn-edit:hover {
    background: #d97706;
    color: white;
    text-decoration: none;
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
    color: white;
    text-decoration: none;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: #94a3b8;
}

.empty-state h3 {
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
}

.empty-state p {
    color: #64748b;
    margin-bottom: 2rem;
}

.bulk-actions-bar {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.bulk-actions-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.bulk-selection {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.bulk-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

.bulk-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #667eea;
}

.selected-count {
    color: #64748b;
    font-size: 0.9rem;
}

.bulk-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.bulk-select {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
}

.btn-bulk {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.template-checkbox {
    position: absolute;
    top: 1rem;
    left: 1rem;
    z-index: 10;
}

.template-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #667eea;
}

.template-card {
    position: relative;
}

.bulk-mode .template-checkbox {
    display: block !important;
}

.bulk-mode .template-card {
    padding-left: 3rem;
}

@media (max-width: 768px) {
    .action-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-bar {
        max-width: none;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .bulk-actions-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .bulk-actions {
        justify-content: center;
    }
}
</style>

<div class="template-container">
    <?php if ($setup_required): ?>
        <!-- Setup Required -->
        <div class="setup-card">
            <h2>🚀 Template System Setup</h2>
            <p>The template management system needs to be set up. This will create the necessary database tables and default categories.</p>
            <form method="POST">
                <input type="hidden" name="action" value="setup_template_system">
                <button type="submit" class="btn-setup">Setup Template System</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-number"><?php echo $stats['total_templates'] ?? 0; ?></div>
                <div class="stat-label">Total Templates</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-number"><?php echo count($categories); ?></div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⬇️</div>
                <div class="stat-number"><?php echo $stats['total_downloads'] ?? 0; ?></div>
                <div class="stat-label">Total Downloads</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo count($stats['by_type'] ?? []); ?></div>
                <div class="stat-label">File Types</div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <div class="search-bar">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" placeholder="Search templates..." id="templateSearch">
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="?page=template_upload" class="btn-primary">
                    ➕ Upload Template
                </a>
                <a href="?page=template_categories" class="btn-secondary">
                    🏷️ Manage Categories
                </a>
                <a href="?page=template_gallery" class="btn-secondary">
                    👁️ View Gallery
                </a>
                <button onclick="toggleBulkMode()" class="btn-secondary" id="bulkModeBtn">
                    ☑️ Bulk Actions
                </button>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
            <div class="bulk-actions-content">
                <div class="bulk-selection">
                    <label class="bulk-checkbox">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        <span class="checkmark"></span>
                        Select All
                    </label>
                    <span class="selected-count" id="selectedCount">0 selected</span>
                </div>
                <div class="bulk-actions">
                    <select id="bulkCategory" class="bulk-select">
                        <option value="">Change Category...</option>
                        <option value="">No Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="bulkCategorize()" class="btn-bulk btn-secondary">Apply Category</button>
                    <button onclick="bulkActivate()" class="btn-bulk btn-success">Activate</button>
                    <button onclick="bulkDeactivate()" class="btn-bulk btn-warning">Deactivate</button>
                    <button onclick="bulkDelete()" class="btn-bulk btn-danger">Delete</button>
                </div>
            </div>
        </div>

        <!-- Templates Grid -->
        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📄</div>
                <h3>No Templates Yet</h3>
                <p>Start building your template library by uploading your first document template.</p>
                <a href="?page=template_upload" class="btn-primary">Upload Your First Template</a>
            </div>
        <?php else: ?>
            <div class="templates-grid" id="templatesGrid">
                <?php foreach ($templates as $template): ?>
                    <div class="template-card" data-id="<?php echo $template['id']; ?>" data-name="<?php echo htmlspecialchars(strtolower($template['name'])); ?>" data-type="<?php echo $template['file_type']; ?>" data-category="<?php echo htmlspecialchars($template['category'] ?? ''); ?>">
                        <div class="template-checkbox" style="display: none;">
                            <input type="checkbox" class="template-select" value="<?php echo $template['id']; ?>" onchange="updateSelectedCount()">
                        </div>
                        <div class="template-header">
                            <div class="template-title"><?php echo htmlspecialchars($template['name']); ?></div>
                            <div class="template-description"><?php echo htmlspecialchars($template['description'] ?: 'No description available'); ?></div>
                        </div>
                        
                        <div class="template-meta">
                            <span class="template-type template-type-<?php echo $template['file_type']; ?>"><?php echo strtoupper($template['file_type']); ?></span>
                            <span class="template-downloads">
                                ⬇️ <?php echo $template['download_count']; ?> downloads
                            </span>
                        </div>
                        
                        <div class="template-actions">
                            <a href="api/template_download.php?id=<?php echo $template['id']; ?>" class="btn-sm btn-download">
                                ⬇️ Download
                            </a>
                            <a href="?page=template_edit&id=<?php echo $template['id']; ?>" class="btn-sm btn-edit">
                                ✏️ Edit
                            </a>
                            <button onclick="deleteTemplate(<?php echo $template['id']; ?>)" class="btn-sm btn-delete">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Bulk operations state
let bulkMode = false;

// Search functionality
document.getElementById('templateSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const templateCards = document.querySelectorAll('.template-card');
    
    templateCards.forEach(card => {
        const name = card.dataset.name;
        const type = card.dataset.type;
        const category = card.dataset.category;
        
        const matches = name.includes(searchTerm) || 
                       type.includes(searchTerm) || 
                       category.includes(searchTerm);
        
        card.style.display = matches ? 'block' : 'none';
    });
});

// Toggle bulk mode
function toggleBulkMode() {
    bulkMode = !bulkMode;
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const bulkModeBtn = document.getElementById('bulkModeBtn');
    const templatesGrid = document.getElementById('templatesGrid');
    
    if (bulkMode) {
        bulkActionsBar.style.display = 'block';
        bulkModeBtn.textContent = '✕ Exit Bulk Mode';
        bulkModeBtn.classList.add('btn-danger');
        bulkModeBtn.classList.remove('btn-secondary');
        templatesGrid.classList.add('bulk-mode');
    } else {
        bulkActionsBar.style.display = 'none';
        bulkModeBtn.textContent = '☑️ Bulk Actions';
        bulkModeBtn.classList.remove('btn-danger');
        bulkModeBtn.classList.add('btn-secondary');
        templatesGrid.classList.remove('bulk-mode');
        
        // Clear all selections
        document.querySelectorAll('.template-select').forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateSelectedCount();
    }
}

// Toggle select all
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const templateSelects = document.querySelectorAll('.template-select');
    
    templateSelects.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateSelectedCount();
}

// Update selected count
function updateSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('.template-select:checked');
    const selectedCount = document.getElementById('selectedCount');
    const count = selectedCheckboxes.length;
    
    selectedCount.textContent = `${count} selected`;
    
    // Update select all checkbox state
    const totalCheckboxes = document.querySelectorAll('.template-select');
    const selectAll = document.getElementById('selectAll');
    
    if (count === 0) {
        selectAll.indeterminate = false;
        selectAll.checked = false;
    } else if (count === totalCheckboxes.length) {
        selectAll.indeterminate = false;
        selectAll.checked = true;
    } else {
        selectAll.indeterminate = true;
    }
}

// Get selected template IDs
function getSelectedTemplateIds() {
    const selectedCheckboxes = document.querySelectorAll('.template-select:checked');
    return Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
}

// Bulk categorize
function bulkCategorize() {
    const selectedIds = getSelectedTemplateIds();
    const category = document.getElementById('bulkCategory').value;
    
    if (selectedIds.length === 0) {
        alert('Please select templates to categorize');
        return;
    }
    
    const categoryText = category || 'no category';
    if (!confirm(`Apply "${categoryText}" to ${selectedIds.length} template(s)?`)) {
        return;
    }
    
    performBulkOperation('categorize', selectedIds, { category: category });
}

// Bulk activate
function bulkActivate() {
    const selectedIds = getSelectedTemplateIds();
    
    if (selectedIds.length === 0) {
        alert('Please select templates to activate');
        return;
    }
    
    if (!confirm(`Activate ${selectedIds.length} template(s)?`)) {
        return;
    }
    
    performBulkOperation('activate', selectedIds);
}

// Bulk deactivate
function bulkDeactivate() {
    const selectedIds = getSelectedTemplateIds();
    
    if (selectedIds.length === 0) {
        alert('Please select templates to deactivate');
        return;
    }
    
    if (!confirm(`Deactivate ${selectedIds.length} template(s)?`)) {
        return;
    }
    
    performBulkOperation('deactivate', selectedIds);
}

// Bulk delete
function bulkDelete() {
    const selectedIds = getSelectedTemplateIds();
    
    if (selectedIds.length === 0) {
        alert('Please select templates to delete');
        return;
    }
    
    if (!confirm(`Delete ${selectedIds.length} template(s)? This action cannot be undone.`)) {
        return;
    }
    
    performBulkOperation('delete', selectedIds);
}

// Perform bulk operation
function performBulkOperation(action, templateIds, extraData = {}) {
    const payload = {
        action: action,
        template_ids: templateIds,
        ...extraData
    };
    
    // Show loading state
    const bulkActions = document.querySelectorAll('.btn-bulk');
    bulkActions.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });
    
    fetch('api/template_bulk_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error performing bulk operation');
    })
    .finally(() => {
        // Restore button states
        bulkActions.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    });
}

// Delete template function (single)
function deleteTemplate(templateId) {
    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
        fetch('api/template_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: templateId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting template: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting template');
        });
    }
}
</script>

<?php renderPageEnd(); ?>