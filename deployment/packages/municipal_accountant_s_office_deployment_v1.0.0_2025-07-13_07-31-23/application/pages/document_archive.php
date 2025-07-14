<?php
require_once 'includes/layout.php';
require_once 'includes/document_manager.php';

// Initialize document manager
$documentManager = new DocumentManager($database);

// Handle search and filtering
$search_query = $_GET['search'] ?? '';
$document_type_filter = $_GET['document_type'] ?? '';
$location_filter = $_GET['location'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Get document types for filter dropdown
try {
    $conn = $database->getConnection();
    $stmt = $conn->prepare("SELECT id, name FROM document_types WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $document_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $document_types = [];
}

// Get locations for filter dropdown
try {
    $stmt = $conn->prepare("SELECT DISTINCT province FROM locations ORDER BY province");
    $stmt->execute();
    $provinces = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $provinces = [];
}

// Search documents
$documents = [];
try {
    $documents = $documentManager->searchDocuments($search_query, $document_type_filter, $location_filter, $date_from, $date_to);
} catch (Exception $e) {
    $error_message = "Error searching documents: " . $e->getMessage();
}

renderPageStart('Document Archive', 'document_archive');
?>

<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>Document Archive</h1>
            <p>Search and browse archived documents in the system.</p>
        </div>
        <a href="?page=document_upload" class="btn btn-primary">
            + Upload New Document
        </a>
    </div>
</div>

<style>
    .search-filters {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #2C3E50;
    }
    
    .form-group input,
    .form-group select {
        padding: 0.75rem;
        border: 1px solid #BDC3C7;
        border-radius: 4px;
        font-size: 1rem;
    }
    
    .search-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 1rem;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        transition: background-color 0.3s ease;
    }
    
    .btn-primary {
        background-color: #3498DB;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #2980B9;
    }
    
    .btn-secondary {
        background-color: #95A5A6;
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #7F8C8D;
    }
    
    .documents-grid {
        display: grid;
        gap: 1rem;
    }
    
    .document-card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    
    .document-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .document-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .document-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: #2C3E50;
        margin-bottom: 0.5rem;
    }
    
    .document-type {
        background-color: #F39C12;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .document-meta {
        color: #7F8C8D;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }
    
    .document-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-small {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .no-results {
        text-align: center;
        padding: 3rem;
        color: #7F8C8D;
    }
    
    .error-message {
        background-color: #E74C3C;
        color: white;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .modal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.4); 
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 900px;
        border-radius: 8px;
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
        animation-name: animatetop;
        animation-duration: 0.4s
    }

    @keyframes animatetop {
        from {top: -300px; opacity: 0}
        to {top: 0; opacity: 1}
    }

    .modal-header {
        padding: 10px 16px;
        background-color: #3498DB;
        color: white;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .modal-body {padding: 2px 16px;}

    .modal-footer {
        padding: 10px 16px;
        background-color: #f1f1f1;
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
        text-align: right;
    }

    .close {
        color: white;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: #000;
        text-decoration: none;
        cursor: pointer;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }

    .table th, .table td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }

    .table-bordered th, .table-bordered td {
        border: 1px solid #dee2e6;
    }
</style>

<?php if (isset($error_message)): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<div class="search-filters">
    <form method="GET" action="">
        <input type="hidden" name="page" value="document_archive">
        
        <div class="filter-grid">
            <div class="form-group">
                <label for="search">Search Keywords</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search in titles, descriptions...">
            </div>
            
            <div class="form-group">
                <label for="document_type">Document Type</label>
                <select id="document_type" name="document_type">
                    <option value="">All Types</option>
                    <?php foreach ($document_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo $document_type_filter == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="location">Province</label>
                <select id="location" name="location">
                    <option value="">All Provinces</option>
                    <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo htmlspecialchars($province); ?>" <?php echo $location_filter == $province ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="form-group">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
        </div>
        
        <div class="search-actions">
            <a href="?page=document_archive" class="btn btn-secondary">Clear Filters</a>
            <button type="submit" class="btn btn-primary">Search Documents</button>
        </div>
    </form>
</div>

<div class="documents-grid">
    <?php if (empty($documents)): ?>
        <div class="no-results">
            <h3>No documents found</h3>
            <p>Try adjusting your search criteria or <a href="?page=document_upload">upload a new document</a>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($documents as $document): ?>
            <div class="document-card">
                <div class="document-header">
                    <div>
                        <div class="document-title"><?php echo htmlspecialchars($document['title'] ?? 'Untitled Document'); ?></div>
                        <div class="document-meta">
                            Uploaded: <?php echo date('M j, Y', strtotime($document['created_at'])); ?>
                            <?php if (!empty($document['location'])): ?>
                                | Location: <?php echo htmlspecialchars($document['location']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="document-type">
                        <?php echo htmlspecialchars($document['document_type_name'] ?? 'Unknown Type'); ?>
                    </div>
                </div>
                
                <?php if (!empty($document['description'])): ?>
                    <div class="document-meta">
                        <?php echo htmlspecialchars($document['description']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="document-actions">
                    <button class="btn btn-primary btn-small" onclick="viewDocument(<?php echo $document['id']; ?>)">
                        View Details
                    </button>
                    <button class="btn btn-secondary btn-small" onclick="editDocument(<?php echo $document['id']; ?>)">
                        Edit
                    </button>
                    <button class="btn btn-danger btn-small" onclick="deleteDocument(<?php echo $document['id']; ?>)">
                        Delete
                    </button>
                    <?php if (!empty($document['file_path'])): ?>
                        <button class="btn btn-secondary btn-small" onclick="downloadDocument(<?php echo $document['id']; ?>)">
                            Download
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="document-viewer-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="document-viewer-title"></h2>
            <span class="close" onclick="closeDocumentViewer()">&times;</span>
        </div>
        <div class="modal-body" id="document-viewer-content"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDocumentViewer()">Close</button>
        </div>
    </div>
</div>

<script>
function viewDocument(documentId) {
    const modal = document.getElementById('document-viewer-modal');
    const titleEl = document.getElementById('document-viewer-title');
    const contentEl = document.getElementById('document-viewer-content');

    // Show loading state
    titleEl.textContent = 'Loading...';
    contentEl.innerHTML = '<p>Loading document details...</p>';
    modal.style.display = 'block';

    // Fetch document details from API
    fetch(`api/document_details.php?id=${documentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                titleEl.textContent = data.document.title;
                let html = `
                    <div class="document-meta">
                        <p><strong>Document Type:</strong> ${data.document.document_type_name}</p>
                        <p><strong>Uploaded By:</strong> ${data.document.uploaded_by_username}</p>
                        <p><strong>Uploaded At:</strong> ${new Date(data.document.created_at).toLocaleString()}</p>
                    </div>
                    <hr>
                    <h4>Metadata</h4>
                    <table class="table table-bordered">`;
                // Separate file fields from other metadata
                const fileFields = [];
                const otherMetadata = [];
                
                for (const [key, value] of Object.entries(data.document.metadata)) {
                    if (value.type === 'file') {
                        fileFields.push({key, value});
                    } else if (value.type === 'reference' && value.book_image_path) {
                        otherMetadata.push({
                            key, 
                            value,
                            html: `<tr>
                                      <td><strong>${value.label || key}</strong></td>
                                      <td>
                                          ${value.value} 
                                          <button class="btn btn-secondary btn-small" onclick="openImagePreviewModal('api/serve_file.php?file=${encodeURIComponent(value.book_image_path)}')">Preview Image</button>
                                      </td>
                                   </tr>`
                        });
                    } else {
                        otherMetadata.push({
                            key,
                            value,
                            html: `<tr><td><strong>${value.label || key}</strong></td><td>${value.value}</td></tr>`
                        });
                    }
                }
                
                // Add other metadata to table
                for (const item of otherMetadata) {
                    html += item.html;
                }
                html += `</table>`;

                // Enhanced Document Files Section
                const allFiles = [];
                
                // First, collect all metadata file fields
                const metadataFiles = [];
                for (const item of fileFields) {
                    if (item.value.value) {
                        metadataFiles.push({
                            name: item.value.label || item.key,
                            path: item.value.value,
                            filename: item.value.value.split('/').pop(),
                            type: 'metadata'
                        });
                    }
                }
                
                // Add metadata file fields to allFiles
                allFiles.push(...metadataFiles);
                
                // Only add main document file if there are NO custom file fields
                // When custom file fields exist, hide the main document to avoid redundancy
                if (data.document.file_path && metadataFiles.length === 0) {
                    allFiles.unshift({
                        name: 'Main Document',
                        path: data.document.file_path,
                        filename: data.document.file_name || 'document',
                        type: 'main'
                    });
                }
                
                if (allFiles.length > 0) {
                    html += `
                        <hr>
                        <h4>Document Files (${allFiles.length})</h4>
                        <div class="document-files-container">`;
                    
                    allFiles.forEach((file, index) => {
                        const fileExtension = file.filename.split('.').pop().toLowerCase();
                        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
                        const isPdf = fileExtension === 'pdf';
                        
                        html += `
                            <div class="file-item" style="margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 4px; padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <h5 style="margin: 0; color: #495057;">${file.name}</h5>
                                    <div>
                                        <button class="btn btn-primary btn-small" onclick="previewFile('${encodeURIComponent(file.path)}', '${file.filename}')" style="margin-right: 0.5rem;">Preview</button>
                                        <button class="btn btn-secondary btn-small" onclick="downloadFile('${encodeURIComponent(file.path)}', '${file.filename}')">Download</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size: 0.9rem; color: #6c757d;">
                                    File: ${file.filename} | Type: ${file.type === 'main' ? 'Main Document' : 'Metadata Field'}
                                </p>
                            </div>`;
                    });
                    
                    html += `</div>`;
                }

                contentEl.innerHTML = html;
            } else {
                contentEl.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Error fetching document details:', error);
            contentEl.innerHTML = '<p style="color: red;">An error occurred while fetching document details.</p>';
        });
}

function closeDocumentViewer() {
    const modal = document.getElementById('document-viewer-modal');
    modal.style.display = 'none';
}

function downloadDocument(documentId) {
    // TODO: Implement document download
    window.open('api/download_document.php?id=' + documentId, '_blank');
}

function editDocument(documentId) {
    window.location.href = `?page=document_edit&id=${documentId}`;
}

function deleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        fetch('api/delete_document.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${documentId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Reload the page to reflect changes
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the document.');
        });
    }
}

// File operations functions
function previewFile(filePath, filename) {
    const fileExtension = filename.split('.').pop().toLowerCase();
    const fileURL = `api/serve_file.php?file=${filePath}`;
    
    if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
        // Use zoom modal for image preview
        if (window.zoomModal) {
            window.zoomModal.open(fileURL, filename, 'image');
        } else {
            // Fallback to old method if zoom modal not available
            window.open(fileURL, '_blank');
        }
    } else if (['pdf'].includes(fileExtension)) {
        // Use zoom modal for PDF preview
        if (window.zoomModal) {
            window.zoomModal.open(fileURL, filename, 'pdf');
        } else {
            // Fallback to old method if zoom modal not available
            window.open(fileURL, '_blank');
        }
    } else {
        // For other file types, try to open directly
        window.open(fileURL, '_blank');
    }
}

function downloadFile(filePath, filename) {
    const link = document.createElement('a');
    link.href = `api/serve_file.php?file=${filePath}`;
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function createImagePreviewModal() {
    const previewModalHtml = `
        <div id="imagePreviewModal" class="image-preview-modal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);">
            <div class="image-preview-modal-content" style="margin: auto; display: block; width: 80%; max-width: 700px; text-align: center; position: relative; top: 50%; transform: translateY(-50%);">
                <span class="image-preview-modal-close" onclick="closeImagePreviewModal()" style="position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
                <img id="previewModalImage" style="max-width: 100%; max-height: 80vh;">
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', previewModalHtml);
}

function openImagePreviewModal(imagePath) {
    console.log('Attempting to open image preview modal with path:', imagePath);
    
    // Use zoom modal if available
    if (window.zoomModal) {
        const filename = imagePath.split('/').pop() || 'Image Preview';
        window.zoomModal.open(imagePath, filename, 'image');
    } else {
        // Fallback to old modal
        const modal = document.getElementById('imagePreviewModal');
        const modalImg = document.getElementById('previewModalImage');
        modal.style.display = 'block';
        modalImg.src = imagePath;
    }
}

function closeImagePreviewModal() {
    const modal = document.getElementById('imagePreviewModal');
    modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    createImagePreviewModal();
});
</script>

<?php renderPageEnd(); ?>