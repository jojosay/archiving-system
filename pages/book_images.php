<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/book_image_manager.php';

// Initialize book image manager
$bookManager = new BookImageManager($database);

// Handle form submissions
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'upload_image':
            $book_title = trim($_POST['book_title'] ?? '');
            $page_number = !empty($_POST['page_number']) ? intval($_POST['page_number']) : null;
            $description = trim($_POST['description'] ?? '');
            
            if (empty($book_title)) {
                $message = 'Book title is required.';
                $message_type = 'error';
            } elseif (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                $message = 'Please select a valid image file.';
                $message_type = 'error';
            } else {
                $uploaded_by = $_SESSION['user_id'];
                $result = $bookManager->uploadImage($_FILES['image_file'], $book_title, $uploaded_by, $page_number, $description);
                
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                    // Clear form data on success
                    $_POST = [];
                } else {
                    $message = $result['message'];
                    $message_type = 'error';
                }
            }
            break;
            
        case 'edit_image':
            $image_id = intval($_POST['image_id'] ?? 0);
            $book_title = trim($_POST['edit_book_title'] ?? '');
            $page_number = !empty($_POST['edit_page_number']) ? intval($_POST['edit_page_number']) : null;
            $description = trim($_POST['edit_description'] ?? '');
            
            if (empty($book_title)) {
                $message = 'Book title is required.';
                $message_type = 'error';
            } else {
                $result = $bookManager->updateImage($image_id, $book_title, $page_number, $description);
                
                if ($result['success']) {
                    $message = $result['message'];
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'error';
                }
            }
            break;
            
        case 'delete_image':
            $image_id = intval($_POST['image_id'] ?? 0);
            $result = $bookManager->deleteImage($image_id);
            
            if ($result['success']) {
                $message = $result['message'];
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
            break;
    }
}

// Handle search and pagination
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['p'] ?? 1));
$per_page = 12;

// Get images with pagination
$images = $bookManager->getAllImages($page, $per_page, $search);
$total_images = $bookManager->getTotalCount($search);
$total_pages = ceil($total_images / $per_page);

// Get statistics
$stats = $bookManager->getStatistics();

renderPageStart('Book Image Management');
?>

<style>
    .upload-card, .image-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #2c3e50;
    }
    
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
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
        transition: all 0.2s ease;
    }
    
    .btn-primary { background: #007bff; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    
    .message {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .images-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }
    
    .image-preview {
        width: 100%;
        height: 200px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 3rem;
        border-radius: 8px 8px 0 0;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .image-preview:hover {
        transform: scale(1.02);
    }
    
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .image-preview:hover img {
        transform: scale(1.1);
    }
    
    .image-title {
        font-weight: bold;
        margin-bottom: 0.5rem;
        color: #2c3e50;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }
    
    .image-meta {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
        line-height: 1.4;
        word-wrap: break-word;
        word-break: break-all;
    }
    
    .image-filename {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
        display: inline-block;
    }
    
    .image-description {
        font-size: 0.9rem;
        color: #6c757d;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        line-height: 1.4;
        max-height: 2.8em;
    }
    
    .search-bar {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        align-items: center;
    }
    
    .search-input {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .stats-bar {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    #file-preview {
        border: 1px solid #e9ecef;
    }
    
    .btn-small {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s ease;
    }
    
    .btn-edit {
        background: #17a2b8;
        color: white;
    }
    
    .btn-edit:hover {
        background: #138496;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
    }
    
    .btn-delete:hover {
        background: #c82333;
    }

    /* Image Modal Styles */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
        animation: fadeIn 0.3s ease;
    }
    
    .image-modal-content {
        position: relative;
        margin: auto;
        padding: 20px;
        width: 90%;
        max-width: 800px;
        top: 50%;
        transform: translateY(-50%);
        text-align: center;
    }
    
    .image-modal img {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }
    
    .image-modal-title {
        color: white;
        font-size: 1.2rem;
        margin-top: 1rem;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
    }
    
    .close-modal {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    
    .close-modal:hover {
        color: #bbb;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .search-bar { flex-direction: column; align-items: stretch; }
        .stats-bar { flex-direction: column; gap: 0.5rem; text-align: center; }
        .images-grid { grid-template-columns: 1fr; }
        .image-modal-content { width: 95%; padding: 10px; }
        .close-modal { top: 10px; right: 25px; font-size: 30px; }
    }
</style>

<?php if ($message): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Upload Form -->
<div class="upload-card">
    <h2>Upload Book Image</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_image">
        
        <div class="form-row">
            <div class="form-group">
                <label for="book_title">Book Title *</label>
                <input type="text" id="book_title" name="book_title" 
                       value="<?php echo htmlspecialchars($_POST['book_title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="page_number">Page Number</label>
                <input type="number" id="page_number" name="page_number" min="1"
                       value="<?php echo htmlspecialchars($_POST['page_number'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="image_file">Image File *</label>
            <input type="file" id="image_file" name="image_file" accept="image/*,.pdf" required>
            <div id="file-preview" style="margin-top: 1rem; padding: 1rem; display: none;">
                <img id="preview-image" style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 4px;">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Upload Image</button>
    </form>
</div>

<!-- Search and Stats -->
<div class="search-bar">
    <input type="text" class="search-input" placeholder="Search by book title..." 
           value="<?php echo htmlspecialchars($search); ?>" id="search-input">
    <button onclick="performSearch()" class="btn btn-secondary">Search</button>
    <?php if ($search): ?>
        <a href="?page=book_images" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
</div>

<div class="stats-bar">
    <div>
        <strong>Total Images:</strong> <?php echo number_format($total_images); ?>
        <?php if ($search): ?>
            (filtered from <?php echo number_format($stats['total_images']); ?>)
        <?php endif; ?>
    </div>
    <div>
        <strong>Total Size:</strong> <?php echo number_format($stats['total_size'] / (1024*1024), 1); ?> MB
    </div>
    <div>
        <strong>Unique Books:</strong> <?php echo number_format($stats['unique_books']); ?>
    </div>
</div>

<!-- Images Grid -->
<?php if (!empty($images)): ?>
    <div class="images-grid">
        <?php foreach ($images as $image): ?>
            <div class="image-card">
                <div class="image-preview" onclick="openImageModal('<?php echo htmlspecialchars(BASE_URL . 'api/serve_file.php?file=' . urlencode($image['file_path'])); ?>', '<?php echo htmlspecialchars($image['book_title'] ?: 'Untitled'); ?>')">
                    <?php 
                    $file_extension = strtolower(pathinfo($image['original_name'], PATHINFO_EXTENSION));
                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): 
                    ?>
                        <img src="<?php echo htmlspecialchars(BASE_URL . 'api/serve_file.php?file=' . urlencode($image['file_path'])); ?>" 
                             alt="<?php echo htmlspecialchars($image['book_title']); ?>"
                             onerror="this.style.display='none'; this.parentNode.innerHTML='&#128196;';">
                    <?php else: ?>
                        &#128196;
                    <?php endif; ?>
                </div>
                <div style="padding: 1rem;">
                    <div class="image-title" title="<?php echo htmlspecialchars($image['book_title'] ?: 'Untitled'); ?>">
                        <?php echo htmlspecialchars($image['book_title'] ?: 'Untitled'); ?>
                    </div>
                    <div class="image-meta">
                        <strong>File:</strong> <span class="image-filename" title="<?php echo htmlspecialchars($image['original_name']); ?>"><?php echo htmlspecialchars($image['original_name']); ?></span><br>
                        <strong>Size:</strong> <?php echo number_format($image['file_size'] / 1024, 1); ?> KB<br>
                        <strong>Page:</strong> <?php echo $image['page_number'] ?: 'N/A'; ?><br>
                        <strong>Uploaded:</strong> <?php echo date('M j, Y', strtotime($image['created_at'])); ?>
                    </div>
                    <?php if ($image['description']): ?>
                        <div class="image-description" title="<?php echo htmlspecialchars($image['description']); ?>">
                            <strong>Description:</strong> <?php echo htmlspecialchars($image['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <button class="btn-small btn-secondary preview-btn" data-src="<?php echo htmlspecialchars(BASE_URL . 'api/serve_file.php?file=' . urlencode($image['file_path'])); ?>" data-title="<?php echo htmlspecialchars($image['book_title'] ?: 'Untitled'); ?>">
                            Preview
                        </button>
                        <button onclick="editImage(<?php echo $image['id']; ?>)" 
                                class="btn-small btn-edit">
                            Edit
                        </button>
                        <button onclick="deleteImage(<?php echo $image['id']; ?>)" 
                                class="btn-small btn-delete">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="text-align: center; margin: 2rem 0;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span style="padding: 0.5rem 1rem; background: #007bff; color: white; border-radius: 4px; margin: 0 0.25rem;"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=book_images&p=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       style="padding: 0.5rem 1rem; background: #f8f9fa; color: #007bff; text-decoration: none; border-radius: 4px; margin: 0 0.25rem;"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px;">
        <h3>No Book Images Found</h3>
        <p>
            <?php if ($search): ?>
                No images match your search criteria. <a href="?page=book_images">View all images</a>
            <?php else: ?>
                Upload your first book image using the form above.
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<!-- Image Modal for Full-Size Preview -->
<div id="imageModal" class="image-modal">
    <div class="image-modal-content">
        <span class="close-modal" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" src="" alt="">
        <div id="modalTitle" class="image-modal-title"></div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background: white; margin: 5% auto; padding: 2rem; width: 90%; max-width: 500px; border-radius: 8px;">
        <h3>Edit Book Image</h3>
        <form method="post" id="editForm">
            <input type="hidden" name="action" value="edit_image">
            <input type="hidden" name="image_id" id="edit_image_id">
            
            <div class="form-group">
                <label for="edit_book_title">Book Title *</label>
                <input type="text" id="edit_book_title" name="edit_book_title" required>
            </div>
            
            <div class="form-group">
                <label for="edit_page_number">Page Number</label>
                <input type="number" id="edit_page_number" name="edit_page_number" min="1">
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="edit_description" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // File preview functionality
    const imageFileInput = document.getElementById('image_file');
    if (imageFileInput) {
        imageFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('file-preview');
            const previewImage = document.getElementById('preview-image');
            
            if (file && preview && previewImage) {
                // Check if file is an image
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // For non-image files, show file info
                    preview.innerHTML = '<div style="padding: 1rem; background: #f8f9fa; border-radius: 4px; text-align: center;">' +
                        '<strong>File Selected:</strong> ' + file.name + '<br>' +
                        '<strong>Size:</strong> ' + (file.size / 1024).toFixed(1) + ' KB' +
                        '</div>';
                    preview.style.display = 'block';
                }
            } else {
                if (preview) preview.style.display = 'none';
            }
        });
    }
});

// Search functionality
function performSearch() {
    const searchTerm = document.getElementById('search-input').value;
    window.location.href = '?page=book_images' + (searchTerm ? '&search=' + encodeURIComponent(searchTerm) : '');
}

// Enter key search
document.getElementById('search-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        performSearch();
    }
});

// Image modal functions
function openImageModal(imagePath, title) {
    // Use zoom modal if available
    if (window.zoomModal) {
        window.zoomModal.open(imagePath, title, 'image');
    } else {
        // Fallback to old modal
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        
        modal.style.display = 'block';
        modalImg.src = imagePath;
        modalTitle.textContent = title;
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Edit modal functions
function editImage(imageId) {
    // Find the image data from the current page
    const imageCards = document.querySelectorAll('.image-card');
    let imageData = null;
    
    imageCards.forEach(card => {
        const editBtn = card.querySelector(`button[onclick="editImage(${imageId})"]`);
        if (editBtn) {
            const title = card.querySelector('.image-title').textContent.trim();
            const metaText = card.querySelector('.image-meta').textContent;
            const descElement = card.querySelector('.image-description');
            const description = descElement ? descElement.textContent.replace('Description:', '').trim() : '';
            
            // Extract page number from meta text
            const pageMatch = metaText.match(/Page:\s*(\d+|N\/A)/);
            const pageNumber = pageMatch && pageMatch[1] !== 'N/A' ? pageMatch[1] : '';
            
            imageData = {
                id: imageId,
                title: title,
                pageNumber: pageNumber,
                description: description
            };
        }
    });
    
    if (imageData) {
        document.getElementById('edit_image_id').value = imageData.id;
        document.getElementById('edit_book_title').value = imageData.title;
        document.getElementById('edit_page_number').value = imageData.pageNumber;
        document.getElementById('edit_description').value = imageData.description;
        document.getElementById('editModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Delete function
function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_image">
            <input type="hidden" name="image_id" value="${imageId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
        closeEditModal();
    }
});

// Close image modal when clicking outside the image
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

// Close edit modal when clicking outside
    // Close edit modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // Add event listeners for all preview buttons
    document.querySelectorAll('.preview-btn').forEach(button => {
        button.addEventListener('click', function() {
            const imagePath = this.dataset.src;
            const imageTitle = this.dataset.title;
            
            // Use zoom modal directly for preview buttons
            if (window.zoomModal) {
                window.zoomModal.open(imagePath, imageTitle, 'image');
            } else {
                openImageModal(imagePath, imageTitle);
            }
        });
    });
</script>

<?php renderPageEnd(); ?>