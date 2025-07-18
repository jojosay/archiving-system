<?php
/**
 * Enhanced PDF Viewer Page using PDF.js
 * Provides full-featured PDF viewing with controls
 */

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/document_storage_manager.php';

// Get parameters
$file = $_GET['file'] ?? '';
$document_id = $_GET['document_id'] ?? '';
$title = $_GET['title'] ?? 'PDF Document';

if (empty($file)) {
    header('Location: index.php?page=dashboard');
    exit;
}

// Validate file access
$storage_manager = new DocumentStorageManager($database);
$pdf_url = $storage_manager->getPDFViewUrl($file, $document_id);

renderPageStart('PDF Viewer - ' . htmlspecialchars($title));
?>

<style>
    body {
        margin: 0;
        padding: 0;
        background: #2c3e50;
        overflow: hidden;
    }
    
    .pdf-viewer-header {
        background: #34495e;
        color: white;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        z-index: 1000;
        position: relative;
    }
    
    .pdf-viewer-title {
        font-size: 1.1rem;
        font-weight: 500;
        margin: 0;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 60%;
    }
    
    .pdf-viewer-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .pdf-action-btn {
        padding: 0.5rem 1rem;
        background: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .pdf-action-btn:hover {
        background: #2980b9;
    }
    
    .pdf-action-btn.secondary {
        background: #95a5a6;
    }
    
    .pdf-action-btn.secondary:hover {
        background: #7f8c8d;
    }
    
    .pdf-viewer-container {
        height: calc(100vh - 80px);
        background: #ecf0f1;
    }
    
    #pdf-viewer {
        width: 100%;
        height: 100%;
    }
    
    .pdf-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(44, 62, 80, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        color: white;
        font-size: 1.1rem;
    }
    
    .loading-content {
        text-align: center;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #34495e;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="pdf-loading-overlay" id="loading-overlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Loading PDF Viewer...</p>
    </div>
</div>

<div class="pdf-viewer-header">
    <h1 class="pdf-viewer-title" title="<?php echo htmlspecialchars($title); ?>">
        📄 <?php echo htmlspecialchars($title); ?>
    </h1>
    <div class="pdf-viewer-actions">
        <a href="<?php echo htmlspecialchars($pdf_url); ?>" class="pdf-action-btn" target="_blank">
            Download
        </a>
        <button onclick="window.close()" class="pdf-action-btn secondary">
            Close
        </button>
    </div>
</div>

<div class="pdf-viewer-container">
    <div id="pdf-viewer"></div>
</div>

<!-- PDF.js Library -->
<script src="<?php echo BASE_URL; ?>/assets/js/vendor/pdfjs/build/pdf.mjs" type="module"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/pdf-viewer.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Import PDF.js as module
    import('<?php echo BASE_URL; ?>/assets/js/vendor/pdfjs/build/pdf.mjs').then(pdfjsLib => {
        // Make pdfjsLib globally available
        window.pdfjsLib = pdfjsLib;
        
        // Initialize PDF viewer
        const container = document.getElementById('pdf-viewer');
        const pdfViewer = new PDFViewer(container, {
            scale: 1.2,
            enableControls: true,
            enableSearch: true,
            enableDownload: true,
            downloadUrl: '<?php echo htmlspecialchars($pdf_url); ?>'
        });
        
        // Load the PDF
        pdfViewer.loadPDF('<?php echo htmlspecialchars($pdf_url); ?>').then(() => {
            // Hide loading overlay
            document.getElementById('loading-overlay').style.display = 'none';
        }).catch(error => {
            console.error('Error loading PDF:', error);
            document.getElementById('loading-overlay').innerHTML = `
                <div class="loading-content">
                    <h3 style="color: #e74c3c;">Error Loading PDF</h3>
                    <p>Unable to load the PDF document.</p>
                    <button onclick="window.close()" class="pdf-action-btn">Close</button>
                </div>
            `;
        });
        
    }).catch(error => {
        console.error('Error loading PDF.js:', error);
        document.getElementById('loading-overlay').innerHTML = `
            <div class="loading-content">
                <h3 style="color: #e74c3c;">PDF.js Not Available</h3>
                <p>The PDF viewer library could not be loaded.</p>
                <a href="<?php echo htmlspecialchars($pdf_url); ?>" class="pdf-action-btn">Download PDF</a>
                <button onclick="window.close()" class="pdf-action-btn secondary">Close</button>
            </div>
        `;
    });
});

// Handle escape key to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.close();
    }
});
</script>

<?php
// Don't render the standard page end for this viewer
echo '</body></html>';
?>