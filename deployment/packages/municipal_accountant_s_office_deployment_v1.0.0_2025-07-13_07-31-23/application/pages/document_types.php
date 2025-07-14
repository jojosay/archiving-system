<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';

// Handle form submission for creating/editing document types
$message = '';
$message_type = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_type') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $message = 'Document type name is required.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $database->getConnection()->prepare("INSERT INTO document_types (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $message = 'Document type created successfully.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error creating document type: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'edit_type') {
        $type_id = intval($_POST['type_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name) || !$type_id) {
            $message = 'Document type name and ID are required.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $database->getConnection()->prepare("UPDATE document_types SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $type_id]);
                $message = 'Document type updated successfully.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error updating document type: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete_type') {
        $type_id = intval($_POST['type_id'] ?? 0);
        
        if (!$type_id) {
            $message = 'Invalid document type ID.';
            $message_type = 'error';
        } else {
            try {
                // Check if there are any documents using this type
                $stmt = $database->getConnection()->prepare("SELECT COUNT(*) FROM documents WHERE document_type_id = ?");
                $stmt->execute([$type_id]);
                $document_count = $stmt->fetchColumn();
                
                if ($document_count > 0) {
                    $message = "Cannot delete document type. It is used by $document_count document(s).";
                    $message_type = 'error';
                } else {
                    $stmt = $database->getConnection()->prepare("DELETE FROM document_types WHERE id = ?");
                    $stmt->execute([$type_id]);
                    $message = 'Document type deleted successfully.';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'Error deleting document type: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get all document types
try {
    $stmt = $database->getConnection()->prepare("SELECT * FROM document_types ORDER BY created_at DESC");
    $stmt->execute();
    $document_types = $stmt->fetchAll();
} catch (Exception $e) {
    $document_types = [];
    $message = 'Error loading document types: ' . $e->getMessage();
    $message_type = 'error';
}

renderPageStart('Document Types', 'document_types');
?>

<div class="page-header">
    <h1>Document Types Management</h1>
    <p>Create and manage custom document categories and their metadata fields</p>
</div>

<style>
    .form-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #2C3E50;
        font-weight: 500;
    }
    
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #BDC3C7;
        border-radius: 4px;
        font-size: 1rem;
        box-sizing: border-box;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .submit-btn {
        background: #F39C12;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    
    .submit-btn:hover {
        background: #E67E22;
    }
    
    .types-table {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #ECF0F1;
    }
    
    th {
        background: #2C3E50;
        color: white;
        font-weight: 500;
    }
    
    tr:hover {
        background: #F8F9FA;
    }
    
    .message {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    
    .message.success {
        background: #D5EDDA;
        color: #155724;
        border: 1px solid #C3E6CB;
    }
    
    .message.error {
        background: #F8D7DA;
        color: #721C24;
        border: 1px solid #F5C6CB;
    }
    
    .action-btn {
        background: #3498DB;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        margin-right: 0.5rem;
        text-decoration: none;
        display: inline-block;
    }
    
    .action-btn:hover {
        background: #2980B9;
    }
    
    .modal {
        display: none;
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
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        border-radius: 8px;
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: black;
    }
</style>

<?php if ($message): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <h3>Create New Document Type</h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="create_type">
        
        <div class="form-group">
            <label for="name">Document Type Name</label>
            <input type="text" id="name" name="name" required placeholder="e.g., Birth Certificate, Marriage License">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Brief description of this document type"></textarea>
        </div>
        
        <button type="submit" class="submit-btn">Create Document Type</button>
    </form>
</div>

<div class="types-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($document_types as $type): ?>
                <tr>
                    <td><?php echo $type['id']; ?></td>
                    <td><?php echo htmlspecialchars($type['name']); ?></td>
                    <td><?php echo htmlspecialchars($type['description'] ?? ''); ?></td>
                    <td><?php echo $type['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><?php echo date('M j, Y', strtotime($type['created_at'])); ?></td>
                    <td>
                        <a href="?page=document_fields&type_id=<?php echo $type['id']; ?>" class="action-btn">
                            Manage Fields
                        </a>
                        <button onclick="editDocumentType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($type['description'] ?? '', ENT_QUOTES); ?>')" class="action-btn" style="background: #28a745;">
                            Edit
                        </button>
                        <button onclick="deleteDocumentType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['name'], ENT_QUOTES); ?>')" class="action-btn" style="background: #dc3545;">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Document Type</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit_type">
            <input type="hidden" id="edit_type_id" name="type_id" value="">
            
            <div class="form-group">
                <label for="edit_name">Document Type Name</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description"></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Update Document Type</button>
            <button type="button" onclick="closeEditModal()" class="submit-btn" style="background: #6c757d; margin-left: 10px;">Cancel</button>
        </form>
    </div>
</div>

<script>
function editDocumentType(id, name, description) {
    document.getElementById('edit_type_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteDocumentType(id, name) {
    if (confirm('Are you sure you want to delete the document type "' + name + '"? This action cannot be undone.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_type';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'type_id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php renderPageEnd(); ?>