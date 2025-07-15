<?php
// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/template_manager.php';
require_once 'includes/template_category_manager.php';

$templateManager = new TemplateManager($database);
$categoryManager = new TemplateCategoryManager($database);

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search_query = $_GET['search'] ?? '';

// Get templates based on filters
if ($search_query) {
    $templates = $templateManager->searchTemplates($search_query, $category_filter ?: null, $type_filter ?: null);
} else {
    $templates = $templateManager->getAllTemplates(true, $category_filter ?: null);
    
    // Filter by type if specified
    if ($type_filter) {
        $templates = array_filter($templates, function($template) use ($type_filter) {
            return $template['file_type'] === $type_filter;
        });
    }
}

// Get categories for filter
$categories = $categoryManager->getCategoriesWithCounts();

// Get template statistics
$stats = $templateManager->getTemplateStats();

renderPageStart('Template Gallery', 'template_gallery');
?>

<div class="page-header">
    <h1>Template Gallery</h1>
    <p>Browse and download document templates</p>
</div>

<style>
/* Modern Gallery Styles */
.gallery-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

.gallery-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 16px;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.gallery-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.gallery-header p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

.gallery-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filters-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
    margin-bottom: 2rem;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.filters-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 2px solid #e2e8f0;
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
    font-size: 1.1rem;
}

.filter-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.filter-tab {
    padding: 0.5rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 20px;
    background: white;
    color: #64748b;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.filter-tab:hover {
    border-color: #667eea;
    color: #667eea;
    text-decoration: none;
}

.filter-tab.active {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.category-filters {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.category-filter {
    padding: 0.5rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    background: #f8fafc;
    color: #475569;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-filter:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    text-decoration: none;
}

.category-filter.active {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.category-count {
    background: rgba(255,255,255,0.2);
    padding: 0.1rem 0.4rem;
    border-radius: 10px;
    font-size: 0.75rem;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.template-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.template-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.template-preview {
    height: 200px;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.template-icon {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.template-type-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #667eea;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.template-content {
    padding: 1.5rem;
}

.template-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.template-description {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.template-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: #64748b;
}

.template-category {
    background: #f1f5f9;
    color: #475569;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-weight: 500;
}

.template-downloads {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.template-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-download {
    flex: 1;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    color: white;
    text-decoration: none;
}

.btn-preview {
    background: #f8fafc;
    color: #475569;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-preview:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    text-decoration: none;
    color: #334155;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
}

.empty-state-icon {
    width: 100px;
    height: 100px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    font-size: 3rem;
}

.empty-state h3 {
    color: #1e293b;
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.empty-state p {
    color: #64748b;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.results-count {
    color: #64748b;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .gallery-header {
        padding: 2rem 1rem;
    }
    
    .gallery-header h2 {
        font-size: 2rem;
    }
    
    .gallery-stats {
        gap: 2rem;
    }
    
    .filters-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        max-width: none;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .template-actions {
        flex-direction: column;
    }
}
</style>

<div class="gallery-container">
    <!-- Gallery Header -->
    <div class="gallery-header">
        <h2>📚 Template Gallery</h2>
        <p>Discover and download professional document templates</p>
        <div class="gallery-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['total_templates'] ?? 0; ?></span>
                <span class="stat-label">Templates</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($categories); ?></span>
                <span class="stat-label">Categories</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $stats['total_downloads'] ?? 0; ?></span>
                <span class="stat-label">Downloads</span>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-header">
            <h3 class="filters-title">Browse Templates</h3>
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" placeholder="Search templates..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" id="searchInput">
            </div>
        </div>

        <!-- File Type Filters -->
        <div class="filter-tabs">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => ''])); ?>" 
               class="filter-tab <?php echo empty($type_filter) ? 'active' : ''; ?>">
                All Types
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'docx'])); ?>" 
               class="filter-tab <?php echo $type_filter === 'docx' ? 'active' : ''; ?>">
                📄 DOCX
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'xlsx'])); ?>" 
               class="filter-tab <?php echo $type_filter === 'xlsx' ? 'active' : ''; ?>">
                📊 Excel
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'pdf'])); ?>" 
               class="filter-tab <?php echo $type_filter === 'pdf' ? 'active' : ''; ?>">
                📋 PDF
            </a>
        </div>

        <!-- Category Filters -->
        <div class="category-filters">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => ''])); ?>" 
               class="category-filter <?php echo empty($category_filter) ? 'active' : ''; ?>">
                All Categories
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $category['name']])); ?>" 
                   class="category-filter <?php echo $category_filter === $category['name'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                    <span class="category-count"><?php echo $category['template_count']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Results Count -->
    <?php if (!empty($templates)): ?>
        <div class="results-count">
            Found <?php echo count($templates); ?> template<?php echo count($templates) !== 1 ? 's' : ''; ?>
            <?php if ($search_query): ?>
                for "<?php echo htmlspecialchars($search_query); ?>"
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Templates Grid -->
    <?php if (empty($templates)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <h3>No Templates Found</h3>
            <p>
                <?php if ($search_query || $category_filter || $type_filter): ?>
                    Try adjusting your search criteria or browse all templates.
                <?php else: ?>
                    No templates are available yet. Check back later!
                <?php endif; ?>
            </p>
            <a href="?page=template_gallery" class="btn-download">Browse All Templates</a>
        </div>
    <?php else: ?>
        <div class="templates-grid">
            <?php foreach ($templates as $template): ?>
                <div class="template-card">
                    <div class="template-preview">
                        <div class="template-icon">
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
                        <div class="template-type-badge"><?php echo strtoupper($template['file_type']); ?></div>
                    </div>
                    
                    <div class="template-content">
                        <h3 class="template-title"><?php echo htmlspecialchars($template['name']); ?></h3>
                        <p class="template-description">
                            <?php echo htmlspecialchars($template['description'] ?: 'Professional template ready for use'); ?>
                        </p>
                        
                        <div class="template-meta">
                            <?php if ($template['category']): ?>
                                <span class="template-category"><?php echo htmlspecialchars($template['category']); ?></span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="template-downloads">
                                ⬇️ <?php echo $template['download_count']; ?>
                            </span>
                        </div>
                        
                        <div class="template-actions">
                            <a href="api/template_download.php?id=<?php echo $template['id']; ?>" 
                               class="btn-download" onclick="trackDownload(<?php echo $template['id']; ?>)">
                                ⬇️ Download
                            </a>
                            <a href="#" class="btn-preview" onclick="previewTemplate(<?php echo $template['id']; ?>)">
                                👁️
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const searchTerm = e.target.value;
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('search', searchTerm);
        window.location.href = currentUrl.toString();
    }
});

// Track download (for analytics)
function trackDownload(templateId) {
    // Optional: Send analytics data
    console.log('Template downloaded:', templateId);
}

// Preview template (placeholder for future implementation)
function previewTemplate(templateId) {
    alert('Template preview feature coming soon!');
}

// Auto-search with debounce
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const searchTerm = e.target.value;
        if (searchTerm.length >= 3 || searchTerm.length === 0) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('search', searchTerm);
            window.location.href = currentUrl.toString();
        }
    }, 1000);
});
</script>

<?php renderPageEnd(); ?>