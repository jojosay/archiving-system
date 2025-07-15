<?php
// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/template_manager.php';
require_once 'includes/template_storage_manager.php';
require_once 'includes/template_validator.php';
require_once 'includes/template_category_manager.php';

$templateManager = new TemplateManager($database);
$storageManager = new TemplateStorageManager();
$validator = new TemplateValidator();
$categoryManager = new TemplateCategoryManager($database);

$message = '';
$message_type = '';

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'upload_template') {
    $template_name = trim($_POST['template_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $tags = isset($_POST['tags']) ? array_filter(array_map('trim', explode(',', $_POST['tags']))) : [];
    $uploaded_by = $_SESSION['user_id'] ?? null;
    
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
        // Validate file upload
        if (isset($_FILES['template_file'])) {
            $file_validation = $validator->validateFileUpload($_FILES['template_file']);
            
            if (!$file_validation['valid']) {
                $message = implode('<br>', $file_validation['errors']);
                $message_type = 'error';
            } else {
                // Store the file
                $storage_result = $storageManager->storeTemplate($_FILES['template_file'], $template_name);
                
                if (!$storage_result['success']) {
                    $message = implode('<br>', $storage_result['errors']);
                    $message_type = 'error';
                } else {
                    // Create template record
                    $template_data = [
                        'name' => $template_name,
                        'description' => $description,
                        'file_path' => $storage_result['file_path'],
                        'file_name' => $storage_result['file_name'],
                        'file_size' => $storage_result['file_size'],
                        'file_type' => $storage_result['file_type'],
                        'mime_type' => $storage_result['mime_type'],
                        'category' => $category ?: null,
                        'tags' => $tags,
                        'created_by' => $uploaded_by
                    ];
                    
                    $create_result = $templateManager->createTemplate($template_data);
                    
                    if ($create_result['success']) {
                        $message = 'Template uploaded successfully!';
                        $message_type = 'success';
                        
                        // Clear form data
                        $template_name = '';
                        $description = '';
                        $category = '';
                        $tags = [];
                    } else {
                        $message = $create_result['message'];
                        $message_type = 'error';
                        
                        // Clean up uploaded file
                        $storageManager->deleteTemplateFile($storage_result['file_path']);
                    }
                }
            }
        } else {
            $message = 'Please select a file to upload.';
            $message_type = 'error';
        }
    }
}

// Get categories for dropdown
$categories = $categoryManager->getAllCategories();

renderPageStart('Upload Template', 'template_upload');
?>

<div class="page-header">
    <h1>Upload Template</h1>
    <p>Upload DOCX, Excel, or PDF templates to your library</p>
</div>

<style>
/* Modern Upload Interface Styles */
.upload-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1rem;
}

.upload-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}

.upload-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.upload-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.upload-header p {
    margin: 0;
    opacity: 0.9;
}

.upload-form {
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

.file-upload-area {
    border: 3px dashed #d1d5db;
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    transition: all 0.3s ease;
    background: #fafafa;
    cursor: pointer;
    position: relative;
}

.file-upload-area:hover {
    border-color: #667eea;
    background: #f8faff;
}

.file-upload-area.dragover {
    border-color: #667eea;
    background: #f0f4ff;
    transform: scale(1.02);
}

.upload-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: white;
}

.upload-text {
    margin-bottom: 1rem;
}

.upload-text h3 {
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
}

.upload-text p {
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.file-types {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.file-type {
    background: #f3f4f6;
    color: #374151;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-selected {
    background: #f0f9ff;
    border-color: #0ea5e9;
    padding: 1.5rem;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.file-icon {
    width: 48px;
    height: 48px;
    background: #0ea5e9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.file-details h4 {
    margin: 0 0 0.25rem 0;
    color: #374151;
    font-size: 1rem;
}

.file-details p {
    margin: 0;
    color: #6b7280;
    font-size: 0.85rem;
}

.tags-input {
    position: relative;
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

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
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

.progress-bar {
    width: 100%;
    height: 8px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 1rem;
    display: none;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    width: 0%;
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .upload-container {
        padding: 0 0.5rem;
    }
    
    .upload-form {
        padding: 1.5rem;
    }
    
    .file-upload-area {
        padding: 2rem 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>

<div class="upload-container">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="upload-card">
        <div class="upload-header">
            <h2>📄 Upload New Template</h2>
            <p>Add DOCX, Excel, or PDF templates to your library</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
            <input type="hidden" name="action" value="upload_template">

            <!-- File Upload Area -->
            <div class="form-group">
                <label class="form-label">Template File *</label>
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">
                        <h3>Drag & drop your file here</h3>
                        <p>or click to browse files</p>
                        <p class="text-sm">Maximum file size: 50MB</p>
                    </div>
                    <div class="file-types">
                        <span class="file-type">DOCX</span>
                        <span class="file-type">DOC</span>
                        <span class="file-type">XLSX</span>
                        <span class="file-type">XLS</span>
                        <span class="file-type">PDF</span>
                    </div>
                    <input type="file" name="template_file" class="file-input" id="fileInput" accept=".docx,.doc,.xlsx,.xls,.pdf" required>
                </div>
                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </div>

            <!-- Template Name -->
            <div class="form-group">
                <label for="template_name" class="form-label">Template Name *</label>
                <input type="text" id="template_name" name="template_name" class="form-input" 
                       value="<?php echo htmlspecialchars($template_name ?? ''); ?>" 
                       placeholder="Enter a descriptive name for your template" required>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-input form-textarea" 
                          placeholder="Describe what this template is used for..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category" class="form-label">Category</label>
                <select id="category" name="category" class="form-select">
                    <option value="">Select a category (optional)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                <?php echo (isset($category) && $category === $cat['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tags -->
            <div class="form-group">
                <label for="tags" class="form-label">Tags</label>
                <input type="text" id="tags" name="tags" class="form-input" 
                       value="<?php echo htmlspecialchars(implode(', ', $tags ?? [])); ?>" 
                       placeholder="Enter tags separated by commas">
                <div class="tags-help">Add tags to help users find this template (e.g., official, form, letter)</div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="?page=template_management" class="btn btn-secondary">
                    ← Cancel
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    📤 Upload Template
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// File upload handling
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInput = document.getElementById('fileInput');
const templateNameInput = document.getElementById('template_name');
const submitBtn = document.getElementById('submitBtn');
const progressBar = document.getElementById('progressBar');
const progressFill = document.getElementById('progressFill');

// Drag and drop handlers
fileUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileUploadArea.classList.add('dragover');
});

fileUploadArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');
});

fileUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(files[0]);
    }
});

// File input change handler
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
});

// Handle file selection
function handleFileSelect(file) {
    const allowedTypes = ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                         'application/msword',
                         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                         'application/vnd.ms-excel',
                         'application/pdf'];
    
    const allowedExtensions = ['.docx', '.doc', '.xlsx', '.xls', '.pdf'];
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    
    // Validate file type
    if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
        alert('Please select a valid file type (DOCX, DOC, XLSX, XLS, or PDF)');
        fileInput.value = '';
        return;
    }
    
    // Validate file size (50MB)
    if (file.size > 50 * 1024 * 1024) {
        alert('File size must be less than 50MB');
        fileInput.value = '';
        return;
    }
    
    // Update UI to show selected file
    const fileIcon = getFileIcon(fileExtension);
    const fileSize = formatFileSize(file.size);
    
    fileUploadArea.classList.add('file-selected');
    fileUploadArea.innerHTML = `
        <div class="file-info">
            <div class="file-icon">${fileIcon}</div>
            <div class="file-details">
                <h4>${file.name}</h4>
                <p>${fileSize} • ${fileExtension.toUpperCase().substring(1)} file</p>
            </div>
        </div>
    `;
    
    // Auto-fill template name if empty
    if (!templateNameInput.value.trim()) {
        const nameWithoutExtension = file.name.replace(/\.[^/.]+$/, "");
        templateNameInput.value = nameWithoutExtension;
    }
}

// Get file icon based on extension
function getFileIcon(extension) {
    switch (extension) {
        case '.docx':
        case '.doc':
            return 'W';
        case '.xlsx':
        case '.xls':
            return 'X';
        case '.pdf':
            return 'P';
        default:
            return '📄';
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Form submission with progress
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Uploading...';
    progressBar.style.display = 'block';
    
    // Simulate progress (since we can't track actual upload progress easily in PHP)
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        progressFill.style.width = progress + '%';
    }, 200);
    
    // Clear interval after form submission
    setTimeout(() => {
        clearInterval(interval);
        progressFill.style.width = '100%';
    }, 2000);
});
</script>

<?php renderPageEnd(); ?>