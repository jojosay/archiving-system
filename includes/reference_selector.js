// Reference Field Selector JavaScript Functions

// Global variable to store current selection
let currentSelection = null;

// Open reference selector modal
function openReferenceSelector(fieldId) {
    console.log('openReferenceSelector called for field:', fieldId);
    
    // Create modal if it doesn't exist
    if (!document.getElementById('referenceModal')) {
        createReferenceModal();
    }
    
    // Store current field ID for later use
    window.currentReferenceFieldId = fieldId;
    
    // Show the modal
    const modal = document.getElementById('referenceModal');
    modal.style.display = 'block';
    
    // Load book images
    loadBookImages();
}

// Create the reference selector modal HTML
function createReferenceModal() {
    const modalHtml = `
        <div id="referenceModal" class="reference-modal" onclick="closeReferenceModal()" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div class="reference-modal-content" onclick="event.stopPropagation()" style="background-color: #fefefe; margin: 3% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 900px; border-radius: 8px; max-height: 90vh; overflow-y: auto;">
                <div class="reference-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Select Book Image Reference</h3>
                    <span class="reference-modal-close" onclick="closeReferenceModal()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                </div>
                <div id="referenceModalContent">
                    <p>Loading book images...</p>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    createImagePreviewModal();
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
        const filename = imagePath.split('/').pop() || 'Book Image Preview';
        const imageURL = `${BASE_URL}api/serve_file.php?file=${encodeURIComponent(imagePath)}`;
        window.zoomModal.open(imageURL, filename, 'image');
    } else {
        // Fallback to old modal
        const modal = document.getElementById('imagePreviewModal');
        const modalImg = document.getElementById('previewModalImage');
        modal.style.display = 'block';
        modalImg.src = `${BASE_URL}api/serve_file.php?file=${encodeURIComponent(imagePath)}`;
    }
}

function closeImagePreviewModal() {
    const modal = document.getElementById('imagePreviewModal');
    modal.style.display = 'none';
}

// Close reference modal
function closeReferenceModal() {
    const modal = document.getElementById('referenceModal');
    if (modal) {
        modal.style.display = 'none';
    }
    // Reset selection
    currentSelection = null;
}

// Load book images
function loadBookImages() {
    const content = document.getElementById('referenceModalContent');
    if (content) {
        content.innerHTML = '<p>Loading book images...</p>';
        
        // Fetch book images from the API
        fetch('api/book_images.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.images) {
                    displayBookImages(data.images);
                } else {
                    content.innerHTML = '<p style="color: red;">Error loading book images: ' + (data.message || 'Unknown error') + '</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching book images:', error);
                content.innerHTML = '<p style="color: red;">Error loading book images. Please try again.</p>';
            });
    }
}

// Display book images in a grid
function displayBookImages(images) {
    const content = document.getElementById('referenceModalContent');
    
    if (!images || images.length === 0) {
        content.innerHTML = `
            <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; margin: 1rem 0;">
                <h4 style="color: #6c757d; margin-bottom: 1rem;">No Book Images Found</h4>
                <p style="color: #6c757d; margin-bottom: 1rem;">You need to upload book images before you can select them as references.</p>
                <a href="?page=book_images" target="_blank" style="display: inline-block; padding: 0.75rem 1.5rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px; font-weight: 500;">
                    Upload Book Images
                </a>
            </div>
        `;
        return;
    }
    
    let html = '<div style="margin-bottom: 1rem;">';
    html += '<input type="text" id="imageSearch" placeholder="Search images..." style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" onkeyup="filterImages()">';
    html += '</div>';
    
    html += '<div id="imageGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; max-height: 450px; overflow-y: auto; margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">';
    
    images.forEach(image => {
        const safeTitle = escapeHtml(image.title);
        const safePath = escapeHtml(image.file_path);
        const safeDescription = escapeHtml(image.description || 'No description');
        const pageInfo = image.page_number ? `Page ${image.page_number}` : 'No page specified';
        
        html += `
            <div class="image-item" data-id="${image.id}" data-title="${safeTitle}" data-path="${safePath}" data-page="${image.page_number || ''}" style="
                border: 2px solid #e9ecef; 
                border-radius: 12px; 
                padding: 1rem; 
                cursor: pointer; 
                transition: all 0.3s ease; 
                background: white; 
                position: relative;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            ">
                <img src="${BASE_URL}api/serve_file.php?file=${encodeURIComponent(image.file_path)}" alt="${safeTitle}" style="
                    width: 100%; 
                    height: 150px; 
                    object-fit: cover; 
                    border-radius: 8px; 
                    margin-bottom: 1rem;
                ">
                <div style="
                    font-size: 1rem; 
                    font-weight: 600; 
                    margin-bottom: 0.5rem; 
                    overflow: hidden; 
                    text-overflow: ellipsis; 
                    white-space: nowrap;
                    color: #2c3e50;
                ">${safeTitle}</div>
                <div style="
                    font-size: 0.85rem; 
                    color: #007bff; 
                    margin-bottom: 0.5rem; 
                    font-weight: 600;
                    background: #e3f2fd;
                    padding: 0.25rem 0.5rem;
                    border-radius: 12px;
                    display: inline-block;
                ">${pageInfo}</div>
                <div style="
                    font-size: 0.85rem; 
                    color: #6c757d; 
                    overflow: hidden; 
                    text-overflow: ellipsis; 
                    white-space: nowrap;
                    line-height: 1.4;
                ">${safeDescription}</div>
                <div class="selection-overlay" style="
                    position: absolute; 
                    top: 0; 
                    left: 0; 
                    right: 0; 
                    bottom: 0; 
                    background: rgba(0,123,255,0.15); 
                    border-radius: 12px; 
                    display: none; 
                    align-items: center; 
                    justify-content: center;
                ">
                    <div style="
                        background: #007bff; 
                        color: white; 
                        padding: 0.75rem 1.25rem; 
                        border-radius: 8px; 
                        font-weight: 600;
                        box-shadow: 0 4px 8px rgba(0,123,255,0.3);
                    ">SELECTED</div>
                </div>
                <button class="preview-btn" onclick="openImagePreviewModal('${image.file_path}')" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 4px; padding: 5px; cursor: pointer;">Preview</button>
            </div>
        `;
    });
    
    html += '</div>';
    
    // Add preview area
    html += `
        <div id="previewArea" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; display: none; border: 2px solid #007bff;">
            <h4 style="margin-bottom: 0.5rem; color: #007bff;">Selected Image Preview:</h4>
            <div id="previewContent"></div>
            <div style="margin-top: 1rem; text-align: right;">
                <button onclick="closeReferenceModal()" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem;">Cancel</button>
                <button onclick="confirmSelection()" style="padding: 0.75rem 1.5rem; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">Confirm Selection</button>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
    
    // Add click event listeners to image items and preview buttons
    document.querySelectorAll('.image-item').forEach(item => {
        item.addEventListener('click', function() {
            const imageId = this.dataset.id;
            const imageTitle = this.dataset.title;
            const imagePath = this.dataset.path;
            const imagePage = this.dataset.page;
            
            selectImagePreview(imageId, imageTitle, imagePath, imagePage);
        });
        
        // Add hover effects
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        });
    });
}

// Select an image for preview
function selectImagePreview(imageId, imageTitle, imagePath, imagePage) {
    // Clear previous selections
    document.querySelectorAll('.image-item').forEach(item => {
        item.style.borderColor = '#e9ecef';
        const overlay = item.querySelector('.selection-overlay');
        if (overlay) overlay.style.display = 'none';
    });
    
    // Highlight selected item
    const selectedItem = document.querySelector(`[data-id="${imageId}"]`);
    if (selectedItem) {
        selectedItem.style.borderColor = '#007bff';
        selectedItem.style.boxShadow = '0 4px 8px rgba(0,123,255,0.3)';
        const overlay = selectedItem.querySelector('.selection-overlay');
        if (overlay) overlay.style.display = 'flex';
    }
    
    // Store selection
    currentSelection = {
        id: imageId,
        title: imageTitle,
        path: imagePath,
        page: imagePage
    };
    
    // Show preview
    const previewArea = document.getElementById('previewArea');
    const previewContent = document.getElementById('previewContent');
    
    if (previewArea && previewContent) {
        const pageInfo = imagePage ? `Page ${imagePage}` : 'No page specified';
        
        previewContent.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <img src="${BASE_URL}api/serve_file.php?file=${encodeURIComponent(imagePath)}" alt="${imageTitle}" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 3px solid #007bff;">
                <div>
                    <div style="font-weight: 600; font-size: 1.2rem; margin-bottom: 0.5rem;">${imageTitle}</div>
                    <div style="color: #007bff; font-weight: 600; margin-bottom: 0.5rem; font-size: 1.1rem;">${pageInfo}</div>
                    <div style="color: #6c757d; font-size: 1rem;">Image ID: ${imageId}</div>
                </div>
            </div>
        `;
        
        previewArea.style.display = 'block';
    }
}

// Confirm selection and close modal
function confirmSelection() {
    if (!currentSelection) {
        alert('Please select an image first.');
        return;
    }
    
    selectImage(currentSelection.id, currentSelection.title, currentSelection.path);
}

// Select an image (final selection)
function selectImage(imageId, imageTitle, imagePath) {
    // Store selection and update display
    const fieldId = window.currentReferenceFieldId;
    if (fieldId) {
        // Update hidden input
        const hiddenInput = document.getElementById(fieldId);
        if (hiddenInput) {
            hiddenInput.value = imageId;
        }
        
        // Update display area
        const displayArea = document.getElementById(fieldId + '_display');
        if (displayArea) {
            displayArea.innerHTML = `
                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: #e8f5e8; border-radius: 8px; border: 2px solid #28a745;">
                    <img src="${BASE_URL}api/serve_file.php?file=${encodeURIComponent(imagePath)}" alt="${imageTitle}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                    <div>
                        <div style="font-weight: 500; color: #155724; margin-bottom: 0.25rem;">${imageTitle}</div>
                        <div style="font-size: 0.9rem; color: #6c757d;">Image ID: ${imageId}</div>
                    </div>
                </div>
            `;
        }
    }
    
    // Close modal
    closeReferenceModal();
}

// Filter images based on search
function filterImages() {
    const searchTerm = document.getElementById('imageSearch').value.toLowerCase();
    const imageItems = document.querySelectorAll('.image-item');
    
    imageItems.forEach(item => {
        const title = item.dataset.title.toLowerCase();
        const description = item.querySelector('div:last-child').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Clear reference selection
function clearReferenceSelection(fieldId) {
    console.log('Clearing reference selection for field:', fieldId);
    
    // Handle both with and without metadata_ prefix
    const actualFieldId = fieldId.startsWith('metadata_') ? fieldId : 'metadata_' + fieldId;
    
    // Clear the hidden input value
    const hiddenInput = document.getElementById(actualFieldId);
    if (hiddenInput) {
        hiddenInput.value = '';
    }
    
    // Clear the display area
    const displayArea = document.getElementById(actualFieldId + '_display');
    if (displayArea) {
        displayArea.innerHTML = '<div style="color: #6c757d; text-align: center; padding: 1rem; border: 1px dashed #ddd; border-radius: 4px;">No image selected</div>';
    }
    
    console.log('Reference selection cleared for field:', actualFieldId);
}

// Initialize reference field buttons globally
function initializeReferenceButtons() {
    document.querySelectorAll('.select-reference-btn').forEach(button => {
        if (!button.hasAttribute('data-initialized')) {
            button.setAttribute('data-initialized', 'true');
            button.addEventListener('click', function() {
                const fieldId = this.getAttribute('data-field-id');
                console.log('Reference button clicked for field:', fieldId);
                
                if (typeof openReferenceSelector === 'function') {
                    openReferenceSelector(fieldId);
                } else {
                    console.error('openReferenceSelector function not found');
                    alert('Reference selector not available. Please check if the script is loaded.');
                }
            });
        }
    });
}