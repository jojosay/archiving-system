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

// Get PDF templates for generation
try {
    $stmt = $conn->prepare("
        SELECT pt.*, dt.name as document_type_name 
        FROM pdf_templates pt 
        LEFT JOIN document_types dt ON pt.document_type_id = dt.id 
        WHERE (pt.deleted = 0 OR pt.deleted IS NULL)
        ORDER BY pt.name
    ");
    $stmt->execute();
    $pdf_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pdf_templates = [];
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
    
    .btn-info {
        background-color: #17a2b8;
        color: white;
    }
    
    .btn-info:hover {
        background-color: #138496;
    }
    
    .document-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }
    
    .document-actions .btn-small {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
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
    
    /* Reference field button styling */
    .reference-field-container {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .reference-field-buttons {
        display: flex;
        gap: 5px;
        align-items: center;
    }
    
    .reference-field-buttons .btn-small {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        border-radius: 3px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .reference-field-buttons .btn-small:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .reference-field-value {
        font-weight: 500;
        color: #495057;
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
                    <button class="btn btn-success btn-small" onclick="openPdfModal(<?php echo $document['id']; ?>)">
                        Generate PDF
                    </button>
                    <button class="btn btn-info btn-small" onclick="previewPdfWithFields(<?php echo $document['id']; ?>)">
                        Preview PDF
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

    if (!modal) {
        alert('Error: Modal element not found!');
        return;
    }

    // Show loading state
    titleEl.textContent = 'Loading...';
    contentEl.innerHTML = '<p>Loading document details...</p>';
    modal.style.display = 'block';

    // Fetch document details from API
    fetch(`api/document_details.php?id=${documentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
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
                
                // Check if we have separate address fields to combine
                const hasAddressFields = data.document.metadata.address_region || 
                                       data.document.metadata.address_province || 
                                       data.document.metadata.address_citymun || 
                                       data.document.metadata.address_barangay;
                
                let addressProcessed = false;
                
                for (const [key, value] of Object.entries(data.document.metadata)) {
                    if (value.type === 'file') {
                        fileFields.push({key, value});
                    } else if (value.type === 'reference' && value.book_image_path) {
                        const fieldLabel = value.label || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        otherMetadata.push({
                            key, 
                            value,
                            html: `<tr>
                                      <td><strong>${fieldLabel}</strong></td>
                                      <td>
                                          <div class="reference-field-container">
                                              <span class="reference-field-value">${value.book_title || value.value}</span>
                                              <div class="reference-field-buttons">
                                                  <button class="btn btn-primary btn-small" onclick="previewReferenceImage('${encodeURIComponent(value.book_image_path)}', '${value.value}')">
                                                      View Reference
                                                  </button>
                                                  <button class="btn btn-secondary btn-small" onclick="downloadReferenceImage('${encodeURIComponent(value.book_image_path)}', '${value.value}')">
                                                      Download
                                                  </button>
                                              </div>
                                          </div>
                                      </td>
                                   </tr>`
                        });
                    } else if (hasAddressFields && (key === 'address_region' || key === 'address_province' || key === 'address_citymun' || key === 'address_barangay')) {
                        // Skip individual address fields if we're combining them
                        if (!addressProcessed) {
                            const regionCode = data.document.metadata.address_region?.value || '';
                            const provinceCode = data.document.metadata.address_province?.value || '';
                            const citymunCode = data.document.metadata.address_citymun?.value || '';
                            const barangayCode = data.document.metadata.address_barangay?.value || '';
                            
                            if (regionCode || provinceCode || citymunCode || barangayCode) {
                                otherMetadata.push({
                                    key: 'address',
                                    value: {label: 'Address'},
                                    html: `<tr>
                                              <td><strong>Address</strong></td>
                                              <td id="address-display-${Date.now()}">Loading...</td>
                                           </tr>`
                                });
                                
                                // Resolve location codes asynchronously
                                const addressDisplayId = `address-display-${Date.now()}`;
                                setTimeout(() => {
                                    resolveLocationCodes(regionCode, provinceCode, citymunCode, barangayCode)
                                        .then(resolvedAddress => {
                                            const addressElement = document.getElementById(addressDisplayId);
                                            if (addressElement) {
                                                addressElement.textContent = resolvedAddress;
                                            }
                                        });
                                }, 100);
                            }
                            addressProcessed = true;
                        }
                    } else if (value.type === 'cascading_dropdown') {
                        // Format cascading dropdown data
                        const fieldLabel = value.label || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        const formattedValue = formatCascadingDropdownValue(value.value);
                        otherMetadata.push({
                            key,
                            value,
                            html: `<tr><td><strong>${fieldLabel}</strong></td><td>${formattedValue}</td></tr>`
                        });
                    } else if (value.value) {
                        const fieldLabel = value.label || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        otherMetadata.push({
                            key,
                            value,
                            html: `<tr><td><strong>${fieldLabel}</strong></td><td>${value.value}</td></tr>`
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

function formatCascadingDropdownValue(value) {
    if (!value) return 'N/A';
    
    try {
        // If it's already a string that looks formatted, return it
        if (typeof value === 'string' && !value.startsWith('{')) {
            return value;
        }
        
        // Parse JSON if it's a string
        const data = typeof value === 'string' ? JSON.parse(value) : value;
        
        // Extract the text values in hierarchical order
        const parts = [];
        if (data.regions && data.regions.text) parts.push(data.regions.text);
        if (data.provinces && data.provinces.text) parts.push(data.provinces.text);
        if (data.citymun && data.citymun.text) parts.push(data.citymun.text);
        if (data.barangays && data.barangays.text) parts.push(data.barangays.text);
        
        return parts.length > 0 ? parts.join(' > ') : 'N/A';
    } catch (e) {
        // If parsing fails, return the original value
        return value.toString();
    }
}

// Function to resolve location codes to readable names
async function resolveLocationCodes(regionCode, provinceCode, citymunCode, barangayCode) {
    try {
        const response = await fetch('api/resolve_locations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                region: regionCode,
                province: provinceCode,
                citymun: citymunCode,
                barangay: barangayCode
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to resolve location codes');
        }
        
        const data = await response.json();
        if (data.success) {
            const parts = [];
            if (data.locations.region) parts.push(data.locations.region);
            if (data.locations.province) parts.push(data.locations.province);
            if (data.locations.citymun) parts.push(data.locations.citymun);
            if (data.locations.barangay) parts.push(data.locations.barangay);
            
            return parts.length > 0 ? parts.join(' > ') : 'N/A';
        }
        
        return 'N/A';
    } catch (error) {
        console.error('Error resolving location codes:', error);
        return `Region: ${regionCode} | Province: ${provinceCode} | City/Municipality: ${citymunCode} | Barangay: ${barangayCode}`;
    }
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

function previewReferenceImage(imagePath, referenceTitle) {
    console.log('Previewing reference image:', imagePath, 'Title:', referenceTitle);
    
    // Construct the full URL for the image
    const imageUrl = `api/serve_file.php?file=${imagePath}`;
    
    // Use zoom modal if available
    if (window.zoomModal) {
        const title = `Reference: ${referenceTitle}`;
        window.zoomModal.open(imageUrl, title, 'image');
    } else {
        // Fallback to opening in new window
        window.open(imageUrl, '_blank');
    }
}

function downloadReferenceImage(imagePath, referenceTitle) {
    console.log('Downloading reference image:', imagePath, 'Title:', referenceTitle);
    
    // Extract filename from path or use reference title
    const filename = imagePath.split('/').pop() || `${referenceTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.jpg`;
    
    // Create download link
    const link = document.createElement('a');
    link.href = `api/serve_file.php?file=${imagePath}`;
    link.download = filename;
    link.style.display = 'none';
    
    // Trigger download
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('Download initiated for:', filename);
}

function closeImagePreviewModal() {
    const modal = document.getElementById('imagePreviewModal');
    modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    createImagePreviewModal();
});

// Include reference selector functionality
if (typeof openReferenceSelector === 'undefined') {
    // Load reference selector script if not already loaded
    const script = document.createElement('script');
    script.src = 'includes/reference_selector.js';
    document.head.appendChild(script);
}

// PDF Generation functionality
// currentDocumentId already declared above

function openPdfModal(documentId) {
    currentDocumentId = documentId;
    
    // Create modal if it doesn't exist
    if (!document.getElementById('pdfGenerationModal')) {
        createPdfModal();
    }
    
    document.getElementById('pdfGenerationModal').style.display = 'block';
    document.getElementById('pdfTemplateSelect').value = '';
    document.getElementById('generatePdfBtn').disabled = true;
    const fieldSection = document.getElementById('fieldMappingSection');
    if (fieldSection) fieldSection.style.display = 'none';
}

function closePdfModal() {
    const modal = document.getElementById('pdfGenerationModal');
    if (modal) modal.style.display = 'none';
    currentDocumentId = null;
}

function createPdfModal() {
    const modalHtml = `
        <div id="pdfGenerationModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Generate PDF</h2>
                    <span class="close" onclick="closePdfModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pdfTemplateSelect">Select PDF Template</label>
                        <select id="pdfTemplateSelect" class="form-control">
                            <option value="">Choose a template...</option>
                            <?php foreach ($pdf_templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>" 
                                        data-document-type="<?php echo $template['document_type_id']; ?>">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                    <?php if ($template['document_type_name']): ?>
                                        (<?php echo htmlspecialchars($template['document_type_name']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="fieldMappingSection" style="display: none;">
                        <h4>Field Mapping</h4>
                        <div id="fieldMappingContainer">
                            <!-- Field mapping will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closePdfModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="generatePdf()" id="generatePdfBtn" disabled>
                        Generate PDF
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Add event listener for template selection
    const pdfTemplateSelect = document.getElementById('pdfTemplateSelect');
    if (pdfTemplateSelect) {
        pdfTemplateSelect.addEventListener('change', function() {
            const templateId = this.value;
            const generateBtn = document.getElementById('generatePdfBtn');
            
            if (templateId) {
                generateBtn.disabled = false;
                loadFieldMapping(templateId);
            } else {
                generateBtn.disabled = true;
                const fieldSection = document.getElementById('fieldMappingSection');
                if (fieldSection) fieldSection.style.display = 'none';
            }
        });
    }
}

async function loadFieldMapping(templateId) {
    try {
        const response = await fetch(`api/pdf_generation.php?action=get_template_fields&template_id=${templateId}`);
        const data = await response.json();
        
        if (data.success && data.fields) {
            displayFieldMapping(data.fields);
        }
    } catch (error) {
        console.error('Error loading field mapping:', error);
    }
}

function displayFieldMapping(fields) {
    const container = document.getElementById('fieldMappingContainer');
    const section = document.getElementById('fieldMappingSection');
    
    if (fields && fields.length > 0) {
        container.innerHTML = fields.map(field => `
            <div class="form-group">
                <label>${escapeHtml(field.name)}</label>
                <input type="text" class="form-control" 
                       data-field="${field.name}" 
                       placeholder="Enter value for ${field.name}">
            </div>
        `).join('');
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

async function generatePdf() {
    if (!currentDocumentId) return;
    
    const templateId = document.getElementById('pdfTemplateSelect').value;
    if (!templateId) return;
    
    try {
        const generateBtn = document.getElementById('generatePdfBtn');
        generateBtn.disabled = true;
        generateBtn.textContent = 'Generating...';
        
        const formData = new FormData();
        formData.append('action', 'generate_pdf');
        formData.append('document_id', currentDocumentId);
        formData.append('template_id', templateId);
        
        // Collect field mapping data
        const fieldInputs = document.querySelectorAll('#fieldMappingContainer input');
        const fieldData = {};
        fieldInputs.forEach(input => {
            if (input.value.trim()) {
                fieldData[input.dataset.field] = input.value.trim();
            }
        });
        formData.append('field_data', JSON.stringify(fieldData));
        
        const response = await fetch('api/pdf_generation.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('PDF generated successfully!');
            if (data.download_url) {
                window.open(data.download_url, '_blank');
            }
            closePdfModal();
        } else {
            alert('Error generating PDF: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error generating PDF. Please try again.');
    } finally {
        const generateBtn = document.getElementById('generatePdfBtn');
        generateBtn.disabled = false;
        generateBtn.textContent = 'Generate PDF';
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}

// Note: editDocument and deleteDocument functions are already defined above

function downloadDocument(documentId) {
    window.open(`api/download_document.php?id=${documentId}`, '_blank');
}

// PDF generation modal variables
if (typeof currentDocumentId === 'undefined') {
    var currentDocumentId = null;
}

// openPdfModal function already defined above

function createPdfModal() {
    const modalHtml = `
        <div id="pdfGenerationModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Generate PDF</h3>
                    <span class="close" onclick="closePdfModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pdfTemplateSelect">Select PDF Template:</label>
                        <select id="pdfTemplateSelect" onchange="onTemplateSelect()">
                            <option value="">-- Select Template --</option>
                        </select>
                    </div>
                    <div id="fieldMappingSection" style="display: none;">
                        <h4>Field Mapping</h4>
                        <div id="fieldMappingList"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="generatePdfBtn" class="btn btn-primary" onclick="generatePdfFromModal()" disabled>
                        Generate PDF
                    </button>
                    <button class="btn btn-secondary" onclick="closePdfModal()">Cancel</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    loadPdfTemplates();
}

function closePdfModal() {
    const modal = document.getElementById('pdfGenerationModal');
    if (modal) modal.style.display = 'none';
}

function loadPdfTemplates() {
    fetch('api/template_management_new.php?action=list')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('pdfTemplateSelect');
            if (data.success && data.templates) {
                data.templates.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading templates:', error));
}

function onTemplateSelect() {
    const templateId = document.getElementById('pdfTemplateSelect').value;
    const generateBtn = document.getElementById('generatePdfBtn');
    
    if (templateId) {
        generateBtn.disabled = false;
        loadTemplateFields(templateId);
    } else {
        generateBtn.disabled = true;
        document.getElementById('fieldMappingSection').style.display = 'none';
    }
}

function loadTemplateFields(templateId) {
    fetch(`api/pdf_generation.php?action=get_template_fields&template_id=${templateId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.fields && data.fields.length > 0) {
                displayFieldMapping(data.fields);
            }
        })
        .catch(error => console.error('Error loading template fields:', error));
}

function displayFieldMapping(fields) {
    const section = document.getElementById('fieldMappingSection');
    const list = document.getElementById('fieldMappingList');
    
    list.innerHTML = '';
    
    fields.forEach(field => {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'field-mapping-item';
        fieldDiv.innerHTML = `
            <label>${field.label || field.name} (${field.type}):</label>
            <span>Will be populated from document data</span>
        `;
        list.appendChild(fieldDiv);
    });
    
    section.style.display = 'block';
}

function generatePdfFromModal() {
    const templateId = document.getElementById('pdfTemplateSelect').value;
    
    if (!currentDocumentId || !templateId) {
        alert('Please select a template');
        return;
    }
    
    // Generate PDF
    const url = `api/pdf_generation.php?action=generate_pdf&document_id=${currentDocumentId}&template_id=${templateId}`;
    window.open(url, '_blank');
    
    closePdfModal();
}

// Add CSS for the modal
const modalStyles = `
<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: none;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.close:hover {
    color: #000;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-group select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.field-mapping-item {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}

.field-mapping-item label {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.field-mapping-item span {
    color: #666;
    font-style: italic;
}
</style>
`;

// Add styles to document head
if (!document.getElementById('pdfModalStyles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'pdfModalStyles';
    styleElement.innerHTML = modalStyles;
    document.head.appendChild(styleElement);
}

// Ensure all PDF modal functions are available globally
window.openPdfModal = openPdfModal;
window.closePdfModal = closePdfModal;
window.createPdfModal = createPdfModal;
window.loadPdfTemplates = loadPdfTemplates;
window.onTemplateSelect = onTemplateSelect;
window.loadTemplateFields = loadTemplateFields;
window.displayFieldMapping = displayFieldMapping;
window.generatePdfFromModal = generatePdfFromModal;

// Preview PDF with embedded fields function
function previewPdfWithFields(documentId) {
    // First, try to get the document to check its type
    fetch(`api/document_details.php?id=${documentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.document) {
                const documentTypeId = data.document.document_type_id;
                const templates = <?php echo json_encode($pdf_templates); ?>;
                
                if (!templates || templates.length === 0) {
                    alert('No PDF templates available. Please create a template first.');
                    return;
                }
                
                // Try to find a template assigned to this document type
                const assignedTemplate = templates.find(t => t.document_type_id == documentTypeId);
                
                if (assignedTemplate) {
                    // Use the assigned template automatically
                    openPdfPreview(documentId, assignedTemplate.id);
                    return;
                }
                
                // If no assigned template, fall back to selection
                if (templates.length === 1) {
                    const templateId = templates[0].id;
                    openPdfPreview(documentId, templateId);
                    return;
                }
                
                // Multiple templates - show selection modal
                showTemplateSelectionModal(documentId, templates);
            } else {
                // Fallback if document details can't be fetched
                const templates = <?php echo json_encode($pdf_templates); ?>;
                if (templates.length === 1) {
                    openPdfPreview(documentId, templates[0].id);
                } else {
                    showTemplateSelectionModal(documentId, templates);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching document details:', error);
            // Fallback to template selection
            const templates = <?php echo json_encode($pdf_templates); ?>;
            if (templates.length === 1) {
                openPdfPreview(documentId, templates[0].id);
            } else {
                showTemplateSelectionModal(documentId, templates);
            }
        });
}

function showTemplateSelectionModal(documentId, templates) {
    // Create template selection modal
    const modalHtml = `
        <div id="templateSelectionModal" class="modal" style="display: block;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3>Select Template for Preview</h3>
                    <span class="close" onclick="closeTemplateSelectionModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="previewTemplateSelect">Choose a template to preview:</label>
                        <select id="previewTemplateSelect" class="form-control">
                            <option value="">-- Select Template --</option>
                            ${templates.map(template => `
                                <option value="${template.id}">
                                    ${escapeHtml(template.name)}
                                    ${template.document_type_name ? `(${escapeHtml(template.document_type_name)})` : ''}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeTemplateSelectionModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="previewWithSelectedTemplate(${documentId})" id="previewBtn" disabled>
                        Preview PDF
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('templateSelectionModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Add event listener for template selection
    const templateSelect = document.getElementById('previewTemplateSelect');
    const previewBtn = document.getElementById('previewBtn');
    
    templateSelect.addEventListener('change', function() {
        previewBtn.disabled = !this.value;
    });
}

function closeTemplateSelectionModal() {
    const modal = document.getElementById('templateSelectionModal');
    if (modal) {
        modal.remove();
    }
}

function previewWithSelectedTemplate(documentId) {
    const templateId = document.getElementById('previewTemplateSelect').value;
    if (!templateId) {
        alert('Please select a template');
        return;
    }
    
    closeTemplateSelectionModal();
    openPdfPreview(documentId, templateId);
}

function openPdfPreview(documentId, templateId) {
    // Show loading indicator
    const loadingToast = showLoadingToast('Generating PDF preview...');
    
    // Open PDF preview with embedded data using the new generate_pdf_with_fields.php
    const previewUrl = `api/generate_pdf_with_fields.php?document_id=${documentId}&template_id=${templateId}`;
    const previewWindow = window.open(previewUrl, '_blank');
    
    // Check if popup was blocked
    if (!previewWindow || previewWindow.closed || typeof previewWindow.closed == 'undefined') {
        hideLoadingToast(loadingToast);
        alert('Popup blocked! Please allow popups for this site and try again.');
        return;
    }
    
    // Hide loading indicator after a short delay
    setTimeout(() => {
        hideLoadingToast(loadingToast);
    }, 2000);
}

function showLoadingToast(message) {
    const toast = document.createElement('div');
    toast.id = 'loadingToast';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #007bff;
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 10000;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    
    toast.innerHTML = `
        <div style="width: 16px; height: 16px; border: 2px solid #ffffff40; border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        ${message}
    `;
    
    // Add spinner animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(toast);
    return toast;
}

function hideLoadingToast(toast) {
    if (toast && toast.parentNode) {
        toast.parentNode.removeChild(toast);
    }
}

// Initialize modal on page load
document.addEventListener('DOMContentLoaded', function() {
    
    // PDF modal functions are loaded from pdf-modal.js
});

</script>

<!-- Include PDF Modal JavaScript -->
<script src="assets/js/pdf-modal.js"></script>
<script>
// Functions are loaded and ready
</script>

<?php renderPageEnd(); ?>