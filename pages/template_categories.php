<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/template_category_manager.php';

$categoryManager = new TemplateCategoryManager($database);

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_category':
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'icon' => $_POST['icon'] ?? 'folder',
                'color' => $_POST['color'] ?? '#3498db',
                'sort_order' => intval($_POST['sort_order'] ?? 0)
            ];
            
            $validation = $categoryManager->validateCategoryData($data);
            if (!$validation['valid']) {
                $message = implode('<br>', $validation['errors']);
                $message_type = 'error';
            } else {
                $result = $categoryManager->createCategory($data);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'update_category':
            $id = intval($_POST['category_id'] ?? 0);
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'icon' => $_POST['icon'] ?? 'folder',
                'color' => $_POST['color'] ?? '#3498db',
                'sort_order' => intval($_POST['sort_order'] ?? 0)
            ];
            
            $validation = $categoryManager->validateCategoryData($data);
            if (!$validation['valid']) {
                $message = implode('<br>', $validation['errors']);
                $message_type = 'error';
            } else {
                $result = $categoryManager->updateCategory($id, $data);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'delete_category':
            $id = intval($_POST['category_id'] ?? 0);
            $result = $categoryManager->deleteCategory($id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'reorder_categories':
            $orders = json_decode($_POST['category_orders'] ?? '{}', true);
            if ($orders) {
                $result = $categoryManager->reorderCategories($orders);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
    }
}

// Get categories with counts
$categories = $categoryManager->getCategoriesWithCounts();
$available_icons = $categoryManager->getAvailableIcons();
$available_colors = $categoryManager->getAvailableColors();

renderPageStart('Template Categories', 'template_categories');
?>

<div class="page-header">
    <h1>Template Categories</h1>
    <p>Manage template categories and organization</p>
</div>

<style>
/* Modern Category Management Styles */
.categories-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.categories-header {
    background: white;
    padding: 2rem;
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

.categories-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    display: block;
}

.stat-label {
    font-size: 0.85rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.category-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.1);
}

.category-card.sortable-ghost {
    opacity: 0.4;
}

.category-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin-bottom: 1rem;
    font-weight: bold;
}

.category-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.category-description {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.5;
}

.category-meta {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
}

.template-count {
    background: #667eea;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
}

.download-count {
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.category-actions {
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

.drag-handle {
    position: absolute;
    top: 1rem;
    right: 1rem;
    cursor: move;
    color: #94a3b8;
    font-size: 1.2rem;
}

.drag-handle:hover {
    color: #64748b;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    background: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.close {
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    opacity: 0.8;
}

.close:hover {
    opacity: 1;
}

.modal-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fafafa;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
    min-height: 80px;
    resize: vertical;
}

.form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    background: #fafafa;
    transition: all 0.3s ease;
}

.form-select:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #fafafa;
}

.icon-option {
    padding: 0.75rem;
    border: 2px solid transparent;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.icon-option:hover {
    border-color: #667eea;
    background: #f0f4ff;
}

.icon-option.selected {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
    gap: 0.5rem;
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #fafafa;
}

.color-option {
    width: 40px;
    height: 40px;
    border-radius: 6px;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all 0.3s ease;
}

.color-option:hover {
    transform: scale(1.1);
}

.color-option.selected {
    border-color: #1e293b;
    transform: scale(1.1);
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #f1f5f9;
    margin-top: 1.5rem;
}

.btn-secondary {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
    text-decoration: none;
    color: #334155;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid;
}

.alert-success {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}

.alert-error {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
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

@media (max-width: 768px) {
    .categories-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .categories-stats {
        justify-content: center;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
    }
}
</style>

<div class="categories-container">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Categories Header -->
    <div class="categories-header">
        <div class="categories-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($categories); ?></span>
                <span class="stat-label">Categories</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo array_sum(array_column($categories, 'template_count')); ?></span>
                <span class="stat-label">Templates</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo array_sum(array_column($categories, 'total_downloads')); ?></span>
                <span class="stat-label">Downloads</span>
            </div>
        </div>
        <button onclick="openCreateModal()" class="btn-primary">
            + Add Category
        </button>
    </div>

    <!-- Categories Grid -->
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📁</div>
            <h3>No Categories Yet</h3>
            <p>Create your first template category to organize your templates.</p>
            <button onclick="openCreateModal()" class="btn-primary">Create First Category</button>
        </div>
    <?php else: ?>
        <div class="categories-grid" id="categoriesGrid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card" data-id="<?php echo $category['id']; ?>">
                    <div class="drag-handle">⋮⋮</div>
                    <div class="category-header">
                        <div class="category-icon" style="background-color: <?php echo htmlspecialchars($category['color']); ?>">
                            <?php echo htmlspecialchars($category['icon']); ?>
                        </div>
                        <div class="category-title"><?php echo htmlspecialchars($category['name']); ?></div>
                        <div class="category-description"><?php echo htmlspecialchars($category['description'] ?: 'No description'); ?></div>
                    </div>
                    
                    <div class="category-meta">
                        <span class="template-count"><?php echo $category['template_count']; ?> templates</span>
                        <span class="download-count">
                            ⬇️ <?php echo $category['total_downloads'] ?: 0; ?> downloads
                        </span>
                    </div>
                    
                    <div class="category-actions">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" class="btn-sm btn-edit">
                            ✏️ Edit
                        </button>
                        <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" class="btn-sm btn-delete">
                            🗑️ Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Category</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="categoryForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create_category">
                <input type="hidden" name="category_id" id="categoryId">
                
                <div class="form-group">
                    <label for="categoryName" class="form-label">Category Name *</label>
                    <input type="text" id="categoryName" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="categoryDescription" class="form-label">Description</label>
                    <textarea id="categoryDescription" name="description" class="form-input form-textarea" placeholder="Describe what this category is for..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Icon</label>
                    <div class="icon-grid">
                        <?php foreach ($available_icons as $icon => $label): ?>
                            <div class="icon-option" data-icon="<?php echo $icon; ?>" title="<?php echo $label; ?>">
                                <?php echo $icon; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="icon" id="selectedIcon" value="folder">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <div class="color-grid">
                        <?php foreach ($available_colors as $color => $name): ?>
                            <div class="color-option" data-color="<?php echo $color; ?>" style="background-color: <?php echo $color; ?>" title="<?php echo $name; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="color" id="selectedColor" value="#3498db">
                </div>
                
                <div class="form-group">
                    <label for="sortOrder" class="form-label">Sort Order</label>
                    <input type="number" id="sortOrder" name="sort_order" class="form-input" value="0" min="0">
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-sm btn-secondary">Cancel</button>
                    <button type="submit" class="btn-sm btn-primary" id="submitBtn">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/vendor/sortable.min.js"></script>
<script>
// Initialize sortable for drag and drop reordering
let sortable;
if (document.getElementById('categoriesGrid')) {
    sortable = Sortable.create(document.getElementById('categoriesGrid'), {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function(evt) {
            // Get new order
            const categoryCards = document.querySelectorAll('.category-card');
            const newOrder = {};
            categoryCards.forEach((card, index) => {
                const categoryId = card.dataset.id;
                newOrder[categoryId] = index;
            });
            
            // Send to server
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=reorder_categories&category_orders=' + encodeURIComponent(JSON.stringify(newOrder))
            })
            .then(response => response.text())
            .then(() => {
                // Optionally show success message
                console.log('Categories reordered successfully');
            })
            .catch(error => {
                console.error('Error reordering categories:', error);
                // Revert order on error
                location.reload();
            });
        }
    });
}

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.getElementById('formAction').value = 'create_category';
    document.getElementById('submitBtn').textContent = 'Create Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    
    // Reset selections
    document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
    
    // Set defaults
    document.querySelector('[data-icon="folder"]').classList.add('selected');
    document.querySelector('[data-color="#3498db"]').classList.add('selected');
    document.getElementById('selectedIcon').value = 'folder';
    document.getElementById('selectedColor').value = '#3498db';
    
    document.getElementById('categoryModal').style.display = 'block';
}

function openEditModal(category) {
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('formAction').value = 'update_category';
    document.getElementById('submitBtn').textContent = 'Update Category';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('sortOrder').value = category.sort_order;
    
    // Reset and set icon selection
    document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
    document.querySelector(`[data-icon="${category.icon}"]`)?.classList.add('selected');
    document.getElementById('selectedIcon').value = category.icon;
    
    // Reset and set color selection
    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
    document.querySelector(`[data-color="${category.color}"]`)?.classList.add('selected');
    document.getElementById('selectedColor').value = category.color;
    
    document.getElementById('categoryModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

// Icon selection
document.querySelectorAll('.icon-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('selectedIcon').value = this.dataset.icon;
    });
});

// Color selection
document.querySelectorAll('.color-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('selectedColor').value = this.dataset.color;
    });
});

// Delete category
function deleteCategory(categoryId, categoryName) {
    if (confirm(`Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="category_id" value="${categoryId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('categoryModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php renderPageEnd(); ?>