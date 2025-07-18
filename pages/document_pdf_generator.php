<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';
require_once 'includes/layout.php';
require_once 'includes/document_manager.php';

$database = new Database();
$auth = new Auth($database);

if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

$db = $database->getConnection();
$documentManager = new DocumentManager($database);

// Get available PDF templates
$templates = [];
try {
    $sql = "SELECT pt.*, dt.name as document_type_name 
            FROM pdf_templates pt 
            LEFT JOIN document_types dt ON pt.document_type_id = dt.id 
            WHERE (pt.deleted = 0 OR pt.deleted IS NULL)
            ORDER BY pt.name";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Error loading templates: " . $e->getMessage();
}

// Get document types for filtering
$document_types = [];
try {
    $stmt = $db->prepare("SELECT id, name FROM document_types WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $document_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $document_types = [];
}

renderPageStart('Document PDF Generator', 'document_pdf_generator');
?>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>Document PDF Generator</h1>
            <p>Search archived documents and generate PDFs using templates with embedded field data.</p>
        </div>
    </div>
</div>

<style>
.generator-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.search-panel, .template-panel {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.documents-list {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-top: 1rem;
}

.document-item {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    cursor: pointer;
    transition: background-color 0.2s;
}

.document-item:hover {
    background-color: #f9fafb;
}

.document-item.selected {
    background-color: #eff6ff;
    border-left: 4px solid #3b82f6;
}

.document-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.document-meta {
    font-size: 0.875rem;
    color: #6b7280;
}

.template-item {
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.template-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.template-item.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.template-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.template-meta {
    font-size: 0.875rem;
    color: #6b7280;
}

.generation-controls {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.field-mapping {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1rem;
}

.field-mapping h4 {
    margin-bottom: 1rem;
    color: #374151;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.field-label {
    font-weight: 500;
    color: #4b5563;
}

.field-value {
    color: #1f2937;
    background: white;
    padding: 0.5rem;
    border-radius: 4px;
    border: 1px solid #d1d5db;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.loading-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;
    max-width: 400px;
}

.spinner {
    border: 4px solid #f3f4f6;
    border-top: 4px solid #3b82f6;
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

.no-results {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.success-message {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #16a34a;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}
</style>

<div class="generator-container">
    <!-- Document Search Panel -->
    <div class="search-panel">
        <h3>1. Search & Select Document</h3>
        <div class="search-filters">
            <div class="form-group">
                <label for="search-query">Search Documents</label>
                <input type="text" id="search-query" class="form-control" placeholder="Enter keywords...">
            </div>
            <div class="form-group">
                <label for="document-type-filter">Document Type</label>
                <select id="document-type-filter" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($document_types as $type): ?>
                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date-from">Date From</label>
                <input type="date" id="date-from" class="form-control">
            </div>
            <div class="form-group">
                <label for="date-to">Date To</label>
                <input type="date" id="date-to" class="form-control">
            </div>
        </div>
        <button type="button" id="search-documents" class="btn btn-primary">
            🔍 Search Documents
        </button>
        
        <div id="documents-list" class="documents-list" style="display: none;">
            <!-- Documents will be loaded here -->
        </div>
    </div>

    <!-- Template Selection Panel -->
    <div class="template-panel">
        <h3>2. Select PDF Template</h3>
        <div id="templates-list">
            <?php if (empty($templates)): ?>
                <div class="no-results">
                    <p>No PDF templates available.</p>
                    <a href="index.php?page=pdf_template_manager" class="btn btn-primary">Upload Templates</a>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $template): ?>
                    <div class="template-item" data-template-id="<?= $template['id'] ?>">
                        <div class="template-name"><?= htmlspecialchars($template['name']) ?></div>
                        <div class="template-meta">
                            <?= $template['document_type_name'] ? 'Type: ' . htmlspecialchars($template['document_type_name']) : 'No specific type' ?>
                            • <?= $template['pages'] ?> page(s)
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Generation Controls -->
<div class="generation-controls">
    <h3>3. Generate PDF</h3>
    <p>Select a document and template above to generate a PDF with embedded field data.</p>
    
    <div id="field-mapping" class="field-mapping" style="display: none;">
        <h4>Field Mapping Preview</h4>
        <div id="field-mapping-content">
            <!-- Field mappings will be shown here -->
        </div>
    </div>
    
    <div style="margin-top: 1.5rem;">
        <button type="button" id="generate-pdf" class="btn btn-primary" disabled>
            📄 Generate PDF
        </button>
        <button type="button" id="preview-mapping" class="btn btn-secondary" disabled>
            👁️ Preview Field Mapping
        </button>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <h3>Generating PDF...</h3>
        <p>Please wait while we process your document.</p>
    </div>
</div>

<script>
let selectedDocument = null;
let selectedTemplate = null;
let documentMetadata = {};

// Search documents
document.getElementById('search-documents').addEventListener('click', function() {
    const searchQuery = document.getElementById('search-query').value;
    const documentType = document.getElementById('document-type-filter').value;
    const dateFrom = document.getElementById('date-from').value;
    const dateTo = document.getElementById('date-to').value;
    
    const params = new URLSearchParams({
        search: searchQuery,
        document_type: documentType,
        date_from: dateFrom,
        date_to: dateTo
    });
    
    fetch(`api/search.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDocuments(data.documents);
            } else {
                showError('Error searching documents: ' + data.message);
            }
        })
        .catch(error => {
            showError('Network error: ' + error.message);
        });
});

// Display search results
function displayDocuments(documents) {
    const container = document.getElementById('documents-list');
    
    if (documents.length === 0) {
        container.innerHTML = '<div class="no-results"><p>No documents found. Try adjusting your search criteria.</p></div>';
    } else {
        container.innerHTML = documents.map(doc => `
            <div class="document-item" data-document-id="${doc.id}">
                <div class="document-title">${escapeHtml(doc.title)}</div>
                <div class="document-meta">
                    Type: ${escapeHtml(doc.document_type_name || 'Unknown')} • 
                    Created: ${new Date(doc.created_at).toLocaleDateString()}
                </div>
            </div>
        `).join('');
        
        // Add click handlers
        container.querySelectorAll('.document-item').forEach(item => {
            item.addEventListener('click', function() {
                selectDocument(this.dataset.documentId, this);
            });
        });
    }
    
    container.style.display = 'block';
}

// Select document
function selectDocument(documentId, element) {
    // Remove previous selection
    document.querySelectorAll('.document-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Add selection to clicked item
    element.classList.add('selected');
    selectedDocument = documentId;
    
    // Fetch document metadata
    fetch(`api/document_details.php?id=${documentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                documentMetadata = data.document.metadata || {};
                updateGenerationControls();
            }
        })
        .catch(error => {
            console.error('Error fetching document details:', error);
        });
}

// Select template
document.querySelectorAll('.template-item').forEach(item => {
    item.addEventListener('click', function() {
        // Remove previous selection
        document.querySelectorAll('.template-item').forEach(template => {
            template.classList.remove('selected');
        });
        
        // Add selection to clicked item
        this.classList.add('selected');
        selectedTemplate = this.dataset.templateId;
        updateGenerationControls();
    });
});

// Update generation controls
function updateGenerationControls() {
    const generateBtn = document.getElementById('generate-pdf');
    const previewBtn = document.getElementById('preview-mapping');
    
    if (selectedDocument && selectedTemplate) {
        generateBtn.disabled = false;
        previewBtn.disabled = false;
    } else {
        generateBtn.disabled = true;
        previewBtn.disabled = true;
    }
}

// Preview field mapping
document.getElementById('preview-mapping').addEventListener('click', function() {
    if (!selectedDocument || !selectedTemplate) return;
    
    const mappingContainer = document.getElementById('field-mapping');
    const mappingContent = document.getElementById('field-mapping-content');
    
    // Display available fields from document metadata
    let html = '';
    if (Object.keys(documentMetadata).length > 0) {
        html = '<div class="field-row"><strong>Document Field</strong><strong>Value</strong></div>';
        for (const [fieldName, fieldData] of Object.entries(documentMetadata)) {
            const value = fieldData.value || 'No value';
            html += `
                <div class="field-row">
                    <div class="field-label">${escapeHtml(fieldData.label || fieldName)}</div>
                    <div class="field-value">${escapeHtml(value)}</div>
                </div>
            `;
        }
    } else {
        html = '<p>No metadata fields found for this document.</p>';
    }
    
    mappingContent.innerHTML = html;
    mappingContainer.style.display = 'block';
});

// Generate PDF
document.getElementById('generate-pdf').addEventListener('click', function() {
    if (!selectedDocument || !selectedTemplate) return;
    
    const loadingOverlay = document.getElementById('loading-overlay');
    loadingOverlay.style.display = 'flex';
    
    const formData = new FormData();
    formData.append('action', 'generate_pdf');
    formData.append('document_id', selectedDocument);
    formData.append('template_id', selectedTemplate);
    
    fetch('api/pdf_generation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loadingOverlay.style.display = 'none';
        
        if (data.success) {
            showSuccess('PDF generated successfully!');
            // Download the generated PDF
            window.open(data.download_url, '_blank');
        } else {
            showError('Error generating PDF: ' + data.message);
        }
    })
    .catch(error => {
        loadingOverlay.style.display = 'none';
        showError('Network error: ' + error.message);
    });
});

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    const container = document.querySelector('.page-header');
    container.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    
    const container = document.querySelector('.page-header');
    container.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.remove();
    }, 5000);
}

// Auto-search on Enter key
document.getElementById('search-query').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('search-documents').click();
    }
});
</script>

<?php renderPageEnd(); ?>