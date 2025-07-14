<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/document_type_manager.php';

// Get document type ID from URL
$type_id = $_GET['type_id'] ?? null;
if (!$type_id) {
    header('Location: index.php?page=document_types');
    exit;
}

$docTypeManager = new DocumentTypeManager($database);
$document_type = $docTypeManager->getDocumentTypeById($type_id);

if (!$document_type) {
    header('Location: index.php?page=document_types');
    exit;
}

$message = '';
$message_type = '';

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_field') {
        $field_name = trim($_POST['field_name'] ?? '');
        $field_label = trim($_POST['field_label'] ?? '');
        $field_type = $_POST['field_type'] ?? '';
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $field_options = $_POST['field_options'] ?? null;
        $reference_type = $_POST['reference_type'] ?? null;
        
        if (empty($field_name) || empty($field_label) || empty($field_type)) {
            $message = 'Field name, label, and type are required.';
            $message_type = 'error';
        } else {
            // Convert dropdown options to JSON if provided
            if ($field_type === 'dropdown' && !empty($field_options)) {
                $options_array = array_map('trim', explode("\n", $field_options));
                $options_array = array_filter($options_array); // Remove empty lines
                $field_options = json_encode($options_array);
            } else {
                $field_options = null;
            }
            
            $result = $docTypeManager->createDocumentTypeField($type_id, $field_name, $field_label, $field_type, $is_required, $field_options, $reference_type);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    elseif ($action === 'edit_field') {
        $field_id = intval($_POST['field_id'] ?? 0);
        $field_name = trim($_POST['edit_field_name'] ?? '');
        $field_label = trim($_POST['edit_field_label'] ?? '');
        $field_type = $_POST['edit_field_type'] ?? '';
        $is_required = isset($_POST['edit_is_required']) ? 1 : 0;
        $field_options = $_POST['edit_field_options'] ?? null;
        $reference_type = $_POST['edit_reference_type'] ?? null;
        
        if (empty($field_name) || empty($field_label) || empty($field_type)) {
            $message = 'Field name, label, and type are required.';
            $message_type = 'error';
        } else {
            // Convert dropdown options to JSON if provided
            if ($field_type === 'dropdown' && !empty($field_options)) {
                $options_array = array_map('trim', explode("\n", $field_options));
                $options_array = array_filter($options_array); // Remove empty lines
                $field_options = json_encode($options_array);
            } else {
                $field_options = null;
            }
            
            $result = $docTypeManager->updateDocumentTypeField($field_id, $field_name, $field_label, $field_type, $is_required, $field_options, $reference_type);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    elseif ($action === 'delete_field') {
        $field_id = intval($_POST['field_id'] ?? 0);
        $result = $docTypeManager->deleteDocumentTypeField($field_id);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
    
    elseif ($action === 'reorder_fields') {
        $field_orders = json_decode($_POST['field_orders'] ?? '{}', true);
        if (!empty($field_orders)) {
            $result = $docTypeManager->updateFieldOrder($field_orders);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Get existing fields
$fields = $docTypeManager->getDocumentTypeFields($type_id);

renderPageStart('Manage Fields - ' . htmlspecialchars($document_type['name']));
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e9ecef;">
        <h1 style="font-size: 2rem; font-weight: 600; color: #2c3e50; margin: 0;">Manage Fields</h1>
        <a href="?page=document_types" style="color: #6c757d; text-decoration: none;">&lt; Back to Document Types</a>
    </div>
    
    <p style="color: #6c757d; margin-bottom: 2rem;">
        Document Type: <strong><?php echo htmlspecialchars($document_type['name']); ?></strong>
    </p>

    <?php if ($message): ?>
        <div style="padding: 1rem; border-radius: 4px; margin-bottom: 1rem; <?php echo $message_type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Create Field Form -->
    <div style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
        <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: #495057;">Add New Field</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="create_field">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label for="field_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Field Name (Internal)</label>
                    <input type="text" id="field_name" name="field_name" required placeholder="e.g., birth_date" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                
                <div>
                    <label for="field_label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Field Label (Display)</label>
                    <input type="text" id="field_label" name="field_label" required placeholder="e.g., Birth Date" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label for="field_type" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Field Type</label>
                    <select id="field_type" name="field_type" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" onchange="toggleFieldOptions()">
                        <option value="">Select field type</option>
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="time">Time</option>
                        <option value="textarea">Textarea</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="cascading_dropdown">Cascading Dropdown</option>
                        <option value="reference">Reference</option>
                        <option value="file">File</option>
                    </select>
                </div>
                
                <div style="display: flex; align-items: center; padding-top: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="is_required" name="is_required">
                        Required Field
                    </label>
                </div>
            </div>
            
            <div id="field_options_group" style="display: none; margin-bottom: 1rem;">
                <label for="field_options" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Dropdown Options (one per line)</label>
                <textarea id="field_options" name="field_options" placeholder="Option 1&#10;Option 2&#10;Option 3" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; height: 100px; resize: vertical;"></textarea>
            </div>
            
            <div id="reference_config_group" style="display: none; margin-bottom: 1rem;">
                <label for="reference_type" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reference Type</label>
                <select id="reference_type" name="reference_type" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">
                    <option value="book_image">Book Image</option>
                </select>
                <small style="color: #6c757d;">Select what type of content this reference field should link to</small>
            </div>
            
            <button type="submit" style="padding: 0.75rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Add Field</button>
        </form>
    </div>

    <!-- Existing Fields -->
    <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <?php if (!empty($fields)): ?>
            <div style="padding: 1rem; background: #f8f9fa; border-bottom: 1px solid #e9ecef; font-size: 0.9rem; color: #6c757d;">
                Tip: Drag and drop rows to reorder fields
            </div>
        <?php endif; ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef; width: 40px;">Order</th>
                    <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef;">Field Name</th>
                    <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef;">Field Label</th>
                    <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef;">Type</th>
                    <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef;">Required</th>
                    <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef;">Created</th>
                    <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #e9ecef;">Actions</th>
                </tr>
            </thead>
            <tbody id="sortable-fields">
                <?php if (empty($fields)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #6c757d; padding: 2rem;">
                            No fields defined yet. Add your first field above.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fields as $field): ?>
                        <tr data-field-id="<?php echo $field['id']; ?>" style="cursor: move;">
                            <td style="padding: 1rem; border-bottom: 1px solid #e9ecef; text-align: center;">
                                <span style="font-size: 1.2rem; color: #6c757d;">&#8942;&#8942;</span>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;"><code><?php echo htmlspecialchars($field['field_name']); ?></code></td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;"><?php echo htmlspecialchars($field['field_label']); ?></td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                                <?php
                                $display_field_type = [
                                    'text' => 'Text',
                                    'number' => 'Number',
                                    'date' => 'Date',
                                    'time' => 'Time',
                                    'textarea' => 'Textarea',
                                    'dropdown' => 'Dropdown',
                                    'cascading_dropdown' => 'Cascading Dropdown',
                                    'reference' => 'Reference',
                                    'file' => 'File',
                                ];
                                echo htmlspecialchars($display_field_type[$field['field_type']] ?? $field['field_type']);
                                ?>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;"><?php echo $field['is_required'] ? 'Yes' : 'No'; ?></td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;"><?php echo date('M j, Y', strtotime($field['created_at'])); ?></td>
                            <td style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                                <button onclick="editField(<?php echo $field['id']; ?>)" style="padding: 0.25rem 0.5rem; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; margin-right: 0.5rem; font-size: 0.8rem;">Edit</button>
                                <button onclick="deleteField(<?php echo $field['id']; ?>)" style="padding: 0.25rem 0.5rem; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 0.8rem;">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Field Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <h3 style="margin-top: 0; margin-bottom: 1.5rem;">Edit Field</h3>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit_field">
            <input type="hidden" name="field_id" id="edit_field_id">
            
            <div style="margin-bottom: 1rem;">
                <label for="edit_field_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Field Name (Internal)</label>
                <input type="text" id="edit_field_name" name="edit_field_name" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="edit_field_label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Field Label (Display)</label>
                <input type="text" id="edit_field_label" name="edit_field_label" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="edit_field_type" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Field Type</label>
                <select id="edit_field_type" name="edit_field_type" required style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;" onchange="toggleEditFieldOptions()">
                    <option value="">Select field type</option>
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="time">Time</option>
                    <option value="textarea">Textarea</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="cascading_dropdown">Cascading Dropdown</option>
                    <option value="reference">Reference</option>
                    <option value="file">File</option>
                </select>
            </div>
            
            <div id="edit_field_options_group" style="display: none; margin-bottom: 1rem;">
                <label for="edit_field_options" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Dropdown Options (one per line)</label>
                <textarea id="edit_field_options" name="edit_field_options" placeholder="Option 1&#10;Option 2&#10;Option 3" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; height: 100px; resize: vertical;"></textarea>
            </div>
            
            <div id="edit_reference_config_group" style="display: none; margin-bottom: 1rem;">
                <label for="edit_reference_type" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reference Type</label>
                <select id="edit_reference_type" name="edit_reference_type" style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">
                    <option value="book_image">Book Image</option>
                </select>
                <small style="color: #6c757d;">Select what type of content this reference field should link to</small>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="edit_is_required" name="edit_is_required">
                    Required Field
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" style="padding: 0.75rem 1.5rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.75rem 1.5rem; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Update Field</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/vendor/sortable.min.js"></script>
<script>
// Field data for editing
const fieldsData = <?php echo json_encode($fields); ?>;

// Initialize sortable
let sortable = null;
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('sortable-fields');
    if (tbody && fieldsData.length > 0) {
        sortable = Sortable.create(tbody, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                updateFieldOrder();
            }
        });
    }
});

function updateFieldOrder() {
    const rows = document.querySelectorAll('#sortable-fields tr[data-field-id]');
    const fieldOrders = {};
    
    rows.forEach((row, index) => {
        const fieldId = row.getAttribute('data-field-id');
        fieldOrders[fieldId] = index + 1;
    });
    
    // Send AJAX request to update order
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="reorder_fields">
        <input type="hidden" name="field_orders" value='${JSON.stringify(fieldOrders)}'>
    `;
    document.body.appendChild(form);
    form.submit();
}

function toggleFieldOptions() {
    const fieldType = document.getElementById('field_type').value;
    const optionsGroup = document.getElementById('field_options_group');
    const referenceGroup = document.getElementById('reference_config_group');
    
    // Hide all configuration groups first
    optionsGroup.style.display = 'none';
    referenceGroup.style.display = 'none';
    
    // Show relevant configuration group
    if (fieldType === 'dropdown') {
        optionsGroup.style.display = 'block';
    } else if (fieldType === 'reference') {
        referenceGroup.style.display = 'block';
    }
}

function toggleEditFieldOptions() {
    const fieldType = document.getElementById('edit_field_type').value;
    const optionsGroup = document.getElementById('edit_field_options_group');
    const referenceGroup = document.getElementById('edit_reference_config_group');
    
    // Hide all configuration groups first
    optionsGroup.style.display = 'none';
    referenceGroup.style.display = 'none';
    
    // Show relevant configuration group
    if (fieldType === 'dropdown') {
        optionsGroup.style.display = 'block';
    } else if (fieldType === 'reference') {
        referenceGroup.style.display = 'block';
    }
}

function editField(fieldId) {
    const field = fieldsData.find(f => f.id == fieldId);
    if (!field) return;
    
    document.getElementById('edit_field_id').value = field.id;
    document.getElementById('edit_field_name').value = field.field_name;
    document.getElementById('edit_field_label').value = field.field_label;
    document.getElementById('edit_field_type').value = field.field_type;
    document.getElementById('edit_is_required').checked = field.is_required == 1;
    
    // Handle field options for dropdown fields
    const optionsTextarea = document.getElementById('edit_field_options');
    if (field.field_type === 'dropdown' && field.field_options) {
        try {
            const options = JSON.parse(field.field_options);
            optionsTextarea.value = options.join('\n');
        } catch (e) {
            optionsTextarea.value = '';
        }
    } else {
        optionsTextarea.value = '';
    }
    
    // Show/hide options group based on field type
    toggleEditFieldOptions();
    
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteField(fieldId) {
    if (confirm('Are you sure you want to delete this field? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_field">
            <input type="hidden" name="field_id" value="${fieldId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
}
#sortable-fields tr:hover {
    background-color: #f8f9fa;
}
</style>

<?php renderPageEnd(); ?>