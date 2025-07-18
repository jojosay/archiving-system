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

renderPageStart('PDF Document Generator', 'pdf_document_generator');
?>

<div class="page-header">
    <div>
        <h1>PDF Document Generator</h1>
        <p>Search archived records and generate PDF documents using templates with embedded field data.</p>
    </div>
</div>

<div class="generator-container">
    <!-- Step 1: Select Template -->
    <div class="step-card" id="step1">
        <div class="step-header">
            <div class="step-number">1</div>
            <h3>Select PDF Template</h3>
        </div>
        <div class="step-content">
            <?php if (empty($templates)): ?>
                <div class="empty-state">
                    <p>No PDF templates available. <a href="index.php?page=pdf_template_manager">Upload a template first</a>.</p>
                </div>
            <?php else: ?>
                <div class="templates-grid">
                    <?php foreach ($templates as $template): ?>
                        <div class="template-option" data-template-id="<?php echo $template['id']; ?>">
                            <div class="template-info">
                                <h4><?php echo htmlspecialchars($template['name']); ?></h4>
                                <p class="template-meta">
                                    <?php if ($template['document_type_name']): ?>
                                        Type: <?php echo htmlspecialchars($template['document_type_name']); ?> |
                                    <?php endif; ?>
                                    <?php echo $template['pages']; ?> page(s)
                                </p>
                                <?php if ($template['description']): ?>
                                    <p class="template-description"><?php echo htmlspecialchars($template['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="template-actions">
                                <button class="btn btn-primary btn-small" onclick="selectTemplate(<?php echo $template['id']; ?>)">
                                    Select Template
                                </button>
                                <button class="btn btn-secondary btn-small" onclick="previewTemplate(<?php echo $template['id']; ?>)">
                                    Preview
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 2: Search and Select Document -->
    <div class="step-card" id="step2" style="display: none;">
        <div class="step-header">
            <div class="step-number">2</div>
            <h3>Search Archived Records</h3>
        </div>
        <div class="step-content">
            <!-- Search Filters -->
            <div class="search-section">
                <div class="search-filters">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="searchKeywords">Search Keywords</label>
                            <input type="text" id="searchKeywords" placeholder="Search in titles, descriptions, field values...">
                        </div>
                        <div class="form-group">
                            <label for="documentTypeFilter">Document Type</label>
                            <select id="documentTypeFilter">
                                <option value="">All Types</option>
                                <?php foreach ($document_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dateFrom">Date From</label>
                            <input type="date" id="dateFrom">
                        </div>
                        <div class="form-group">
                            <label for="dateTo">Date To</label>
                            <input type="date" id="dateTo">
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary" onclick="searchDocuments()">Search</button>
                            <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Results -->
            <div class="search-results" id="searchResults">
                <div class="search-placeholder">
                    <p>Use the search filters above to find archived records.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Field Mapping and Generation -->
    <div class="step-card" id="step3" style="display: none;">
        <div class="step-header">
            <div class="step-number">3</div>
            <h3>Field Mapping & PDF Generation</h3>
        </div>
        <div class="step-content">
            <div class="mapping-section">
                <div class="selected-info">
                    <div class="info-card">
                        <h4>Selected Template</h4>
                        <div id="selectedTemplateInfo"></div>
                    </div>
                    <div class="info-card">
                        <h4>Selected Document</h4>
                        <div id="selectedDocumentInfo"></div>
                    </div>
                </div>

                <div class="field-mapping">
                    <h4>Field Mapping</h4>
                    <p>Map template fields to document data fields:</p>
                    <div id="fieldMappingTable">
                        <!-- Field mapping will be populated here -->
                    </div>
                </div>

                <div class="generation-actions">
                    <button class="btn btn-success btn-large" onclick="generatePDF()">
                        <span class="icon">📄</span>
                        Generate PDF Document
                    </button>
                    <button class="btn btn-secondary" onclick="previewMapping()">
                        Preview Mapping
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p id="loadingMessage">Processing...</p>
        </div>
    </div>
</div>

<style>
.generator-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.step-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    overflow: hidden;
}

.step-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.step-number {
    background: rgba(255,255,255,0.2);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.step-content {
    padding: 2rem;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.template-option {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.template-option:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.template-option.selected {
    border-color: #10b981;
    background: #f0fdf4;
}

.template-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
}

.template-meta {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0.5rem 0;
}

.template-description {
    color: #4b5563;
    font-size: 0.875rem;
    margin: 0.5rem 0 1rem 0;
}

.template-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.search-filters {
    background: #f9fafb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-group input,
.form-group select {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
}

.search-results {
    min-height: 200px;
}

.search-placeholder {
    text-align: center;
    color: #6b7280;
    padding: 3rem;
}

.document-result {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.document-result:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.document-result.selected {
    border-color: #10b981;
    background: #f0fdf4;
}

.selected-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.info-card {
    background: #f9fafb;
    border-radius: 8px;
    padding: 1.5rem;
}

.info-card h4 {
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.field-mapping {
    margin-bottom: 2rem;
}

.mapping-table {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.mapping-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    border-bottom: 1px solid #e5e7eb;
}

.mapping-row:last-child {
    border-bottom: none;
}

.mapping-cell {
    padding: 1rem;
    border-right: 1px solid #e5e7eb;
}

.mapping-cell:last-child {
    border-right: none;
}

.mapping-header {
    background: #f9fafb;
    font-weight: 600;
}

.generation-actions {
    text-align: center;
    padding: 2rem 0;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.loading-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;
    min-width: 300px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .selected-info {
        grid-template-columns: 1fr;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="assets/js/pdf-document-generator.js"></script>

<?php renderPageEnd(); ?>