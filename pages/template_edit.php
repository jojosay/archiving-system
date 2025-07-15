<?php
// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/template_manager.php';
require_once 'includes/template_category_manager.php';
require_once 'includes/template_validator.php';

$templateManager = new TemplateManager($database);
$categoryManager = new TemplateCategoryManager($database);
$validator = new TemplateValidator();

$message = '';
$message_type = '';
$template_id = intval($_GET['id'] ?? 0);

// Get template data
$template = $templateManager->getTemplateById($template_id);

if (!$template) {
    header('Location: index.php?page=template_management');
    exit;
}

// Check permissions - admin can edit any template, users can edit their own
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = $auth->hasRole('admin');
$is_owner = $template['created_by'] == $user_id;

if (!$is_admin && !$is_owner) {
    header('Location: index.php?page=template_gallery');
    exit;
}

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_template') {
    $template_name = trim($_POST['template_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $tags = isset($_POST['tags']) ? array_filter(array_map('trim', explode(',', $_POST['tags']))) : [];
    
    // Validate template data
    $data_validation = $validator->validateTemplateData([
        'name' => $template_name,
        'description' => $description,
        'category' => $category,
        'tags' => $tags
    ]);
    
    if (!$data_validation['valid']) {
        $message = implode('<br>', $data_validation['errors']);
        $message_type = 'error';
    } else {
        // Update template record
        $update_data = [
            'name' => $template_name,
            'description' => $description,
            'category' => $category ?: null,
            'tags' => $tags
        ];
        
        $update_result = $templateManager->updateTemplate($template_id, $update_data);
        
        if ($update_result['success']) {
            $message = 'Template updated successfully!';
            $message_type = 'success';
            
            // Refresh template data
            $template = $templateManager->getTemplateById($template_id);
        } else {
            $message = $update_result['message'];
            $message_type = 'error';
        }
    }
}

// Get categories for dropdown
$categories = $categoryManager->getAllCategories();

// Parse existing tags
$existing_tags = [];
if ($template['tags']) {
    $existing_tags = json_decode($template['tags'], true) ?: [];
}

renderPageStart('Edit Template', 'template_edit');
?>

<div class="page-header">
    <h1>Edit Template</h1>
    <p>Update template information and metadata</p>
</div>

<style>
/* Modern Edit Interface Styles */
.edit-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1rem;
}

.edit-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    align-items: start;
}

.edit-form-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}

.edit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.edit-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.edit-header p {
    margin: 0;
    opacity: 0.9;
}

.edit-form {
    padding: 2rem;
}

.template-preview-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #f1f5f9;
    overflow: hidden;
    position: sticky;
    top: 2rem;
}

.preview-header {
    background: #f8fafc;
    padding: 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}

.preview-header h3 {
    margin: 0;
    color: #1e293b;
    font-size: 1.1rem;
}

.preview-content {
    padding: 1.5rem;
}

.file-preview {
    text-align: center;
    padding: 2rem 1rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.file-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    font-weight: bold;
    color: white;
}

.file-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
    word-break: break-word;
}

.file-details {
    color: #64748b;
    font-size: 0.9rem;
}

.preview-meta {
    space-y: 1rem;
}

.meta-item {
    margin-bottom: 1rem;
}

.meta-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.meta-value {
    color: #64748b;
    font-size: 0.9rem;
}

.download-stats {
    background: #f0f9ff;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    margin-top: 1rem;
}

.download-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0ea5e9;
    display: block;
}

.download-label {
    font-size: 0.8rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
    min-height: 100px;
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

.tags-input-container {
    position: relative;
}

.tags-display {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    min-height: 2rem;
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #fafafa;
}

.tag-item {
    background: #667eea;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tag-remove {
    cursor: pointer;
    opacity: 0.8;
    font-weight: bold;
}

.tag-remove:hover {
    opacity: 1;
}

.tags-help {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #f1f5f9;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
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

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
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

.breadcrumb {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: #667eea;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    color: #94a3b8;
    margin: 0 0.5rem;
}

@media (max-width: 768px) {
    .edit-container {
        padding: 0 0.5rem;
    }
    
    .edit-layout {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .template-preview-card {
        position: static;
    }
    
    .edit-form {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>

<div class="edit-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="?page=template_management">Template Management</a>
        <span class="breadcrumb-separator">›</span>
        <span>Edit Template</span>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="edit-layout">
        <!-- Edit Form -->
        <div class="edit-form-card">
            <div class="edit-header">
                <h2>✏️ Edit Template</h2>
                <p>Update template information and metadata</p>
            </div>

            <form method="POST" class="edit-form" id="editForm">
                <input type="hidden" name="action" value="update_template">

                <!-- Template Name -->
                <div class="form-group">
                    <label for="template_name" class="form-label">Template Name *</label>
                    <input type="text" id="template_name" name="template_name" class="form-input" 
                           value="<?php echo htmlspecialchars($template['name']); ?>" 
                           placeholder="Enter a descriptive name for your template" required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-input form-textarea" 
                              placeholder="Describe what this template is used for..."><?php echo htmlspecialchars($template['description'] ?? ''); ?></textarea>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="category" class="form-label">Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">Select a category (optional)</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                    <?php echo ($template['category'] === $cat['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tags -->
                <div class="form-group">
                    <label for="tags" class="form-label">Tags</label>
                    <div class="tags-input-container">
                        <div class="tags-display" id="tagsDisplay">
                            <?php foreach ($existing_tags as $tag): ?>
                                <span class="tag-item">
                                    <?php echo htmlspecialchars($tag); ?>
                                    <span class="tag-remove" onclick="removeTag(this)">×</span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" id="tagInput" class="form-input" 
                               placeholder="Type a tag and press Enter">
                        <input type="hidden" id="tags" name="tags" 
                               value="<?php echo htmlspecialchars(implode(', ', $existing_tags)); ?>">
                        <div class="tags-help">Press Enter to add tags, click × to remove</div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="?page=template_management" class="btn btn-secondary">
                        ← Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        💾 Update Template
                    </button>
                </div>
            </form>
        </div>

        <!-- Template Preview -->
        <div class="template-preview-card">
            <div class="preview-header">
                <h3>Template Preview</h3>
            </div>
            <div class="preview-content">
                <div class="file-preview">
                    <div class="file-icon">
                        <?php
                        switch ($template['file_type']) {
                            case 'docx':
                            case 'doc':
                                echo 'W';
                                break;
                            case 'xlsx':
                            case 'xls':
                                echo 'X';
                                break;
                            case 'pdf':
                                echo 'P';
                                break;
                            default:
                                echo '📄';
                        }
                        ?>
                    </div>
                    <div class="file-name"><?php echo htmlspecialchars($template['file_name']); ?></div>
                    <div class="file-details">
                        <?php echo strtoupper($template['file_type']); ?> • 
                        <?php echo number_format($template['file_size'] / 1024, 1); ?> KB
                    </div>
                </div>

                <div class="preview-meta">
                    <div class="meta-item">
                        <div class="meta-label">Created</div>
                        <div class="meta-value"><?php echo date('M j, Y', strtotime($template['created_at'])); ?></div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-label">Created By</div>
                        <div class="meta-value"><?php echo htmlspecialchars($template['created_by_username'] ?? 'Unknown'); ?></div>
                    </div>
                    
                    <?php if ($template['category']): ?>
                    <div class="meta-item">
                        <div class="meta-label">Category</div>
                        <div class="meta-value"><?php echo htmlspecialchars($template['category']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($template['updated_at'] !== $template['created_at']): ?>
                    <div class="meta-item">
                        <div class="meta-label">Last Updated</div>
                        <div class="meta-value"><?php echo date('M j, Y', strtotime($template['updated_at'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="download-stats">
                    <span class="download-count"><?php echo $template['download_count']; ?></span>
                    <span class="download-label">Downloads</span>
                </div>

                <!-- Quick Actions -->
                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                    <a href="api/template_download.php?id=<?php echo $template['id']; ?>" 
                       class="btn btn-secondary" style="flex: 1; justify-content: center; font-size: 0.85rem;">
                        ⬇️ Download
                    </a>
                    <?php if ($is_admin): ?>
                    <button onclick="deleteTemplate()" class="btn btn-danger" style="font-size: 0.85rem;">
                        🗑️
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tags management
const tagInput = document.getElementById('tagInput');
const tagsDisplay = document.getElementById('tagsDisplay');
const tagsHidden = document.getElementById('tags');

let currentTags = <?php echo json_encode($existing_tags); ?>;

tagInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addTag(this.value.trim());
        this.value = '';
    }
});

function addTag(tagText) {
    if (!tagText || currentTags.includes(tagText)) return;
    
    currentTags.push(tagText);
    updateTagsDisplay();
    updateTagsInput();
}

function removeTag(element) {
    const tagText = element.parentElement.textContent.replace('×', '').trim();
    currentTags = currentTags.filter(tag => tag !== tagText);
    updateTagsDisplay();
    updateTagsInput();
}

function updateTagsDisplay() {
    tagsDisplay.innerHTML = currentTags.map(tag => 
        `<span class="tag-item">
            ${escapeHtml(tag)}
            <span class="tag-remove" onclick="removeTag(this)">×</span>
        </span>`
    ).join('');
}

function updateTagsInput() {
    tagsHidden.value = currentTags.join(', ');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Delete template function
function deleteTemplate() {
    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
        fetch('api/template_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: <?php echo $template_id; ?> })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '?page=template_management';
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

// Form submission enhancement
document.getElementById('editForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Updating...';
    
    // Re-enable button after a delay in case of errors
    setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '💾 Update Template';
    }, 5000);
});
</script>

<?php renderPageEnd(); ?>