<?php
// Document Type Management Helper Class

class DocumentTypeManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    // Get all document types
    public function getAllDocumentTypes($active_only = false) {
        try {
            $sql = "SELECT * FROM document_types";
            if ($active_only) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get document type by ID
    public function getDocumentTypeById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM document_types WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Get fields for a document type
    public function getDocumentTypeFields($type_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM document_type_fields WHERE document_type_id = ? ORDER BY field_order, created_at");
            $stmt->execute([$type_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get fields for a document type with cascading config
    public function getDocumentTypeFieldsWithConfig($type_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT dtf.*, cfc.hierarchy_levels, cfc.level_labels 
                FROM document_type_fields dtf 
                LEFT JOIN cascading_field_config cfc ON dtf.id = cfc.field_id 
                WHERE dtf.document_type_id = ? 
                ORDER BY dtf.field_order, dtf.created_at
            ");
            $stmt->execute([$type_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Create new document type
    public function createDocumentType($name, $description = '') {
        try {
            $stmt = $this->db->prepare("INSERT INTO document_types (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            return ['success' => true, 'message' => 'Document type created successfully', 'id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating document type: ' . $e->getMessage()];
        }
    }
    
    // Create new field for document type
    public function createDocumentTypeField($type_id, $field_name, $field_label, $field_type, $is_required = false, $field_options = null, $reference_type = null) {
        try {
            $this->db->beginTransaction();
            
            // Create the field
            $stmt = $this->db->prepare("INSERT INTO document_type_fields (document_type_id, field_name, field_label, field_type, field_options, is_required) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$type_id, $field_name, $field_label, $field_type, $field_options, $is_required]);
            $field_id = $this->db->lastInsertId();
            
            // Create reference configuration if it's a reference field
            if ($field_type === 'reference' && $reference_type) {
                $stmt = $this->db->prepare("INSERT INTO reference_field_config (field_id, reference_type) VALUES (?, ?)");
                $stmt->execute([$field_id, $reference_type]);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Field created successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error creating field: ' . $e->getMessage()];
        }
    }
    
    // Update existing field
    public function updateDocumentTypeField($field_id, $field_name, $field_label, $field_type, $is_required = false, $field_options = null, $reference_type = null) {
        try {
            $this->db->beginTransaction();
            
            // Update the field
            $stmt = $this->db->prepare("UPDATE document_type_fields SET field_name = ?, field_label = ?, field_type = ?, field_options = ?, is_required = ? WHERE id = ?");
            $stmt->execute([$field_name, $field_label, $field_type, $field_options, $is_required, $field_id]);
            
            // Handle reference configuration
            if ($field_type === 'reference' && $reference_type) {
                // Check if reference config exists
                $stmt = $this->db->prepare("SELECT id FROM reference_field_config WHERE field_id = ?");
                $stmt->execute([$field_id]);
                $config_exists = $stmt->fetch();
                
                if ($config_exists) {
                    // Update existing config
                    $stmt = $this->db->prepare("UPDATE reference_field_config SET reference_type = ? WHERE field_id = ?");
                    $stmt->execute([$reference_type, $field_id]);
                } else {
                    // Create new config
                    $stmt = $this->db->prepare("INSERT INTO reference_field_config (field_id, reference_type) VALUES (?, ?)");
                    $stmt->execute([$field_id, $reference_type]);
                }
            } else {
                // Remove reference config if field type changed from reference
                $stmt = $this->db->prepare("DELETE FROM reference_field_config WHERE field_id = ?");
                $stmt->execute([$field_id]);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Field updated successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error updating field: ' . $e->getMessage()];
        }
    }
    
    // Delete field
    public function deleteDocumentTypeField($field_id) {
        try {
            // Check if field is being used in any documents
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM document_metadata WHERE field_name = (SELECT field_name FROM document_type_fields WHERE id = ?)");
            $stmt->execute([$field_id]);
            $usage = $stmt->fetch();
            
            if ($usage['count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete field: it is being used in ' . $usage['count'] . ' document(s)'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM document_type_fields WHERE id = ?");
            $stmt->execute([$field_id]);
            return ['success' => true, 'message' => 'Field deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting field: ' . $e->getMessage()];
        }
    }
    
    // Get field by ID
    public function getFieldById($field_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM document_type_fields WHERE id = ?");
            $stmt->execute([$field_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Update field order
    public function updateFieldOrder($field_orders) {
        try {
            $this->db->beginTransaction();
            
            foreach ($field_orders as $field_id => $order) {
                $stmt = $this->db->prepare("UPDATE document_type_fields SET field_order = ? WHERE id = ?");
                $stmt->execute([$order, $field_id]);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Field order updated successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error updating field order: ' . $e->getMessage()];
        }
    }
    
    // Generate dynamic form HTML for a document type
    public function generateFormFields($type_id, $values = []) {
        $fields = $this->getDocumentTypeFieldsWithConfig($type_id);
        $html = '';
        
        foreach ($fields as $field) {
            $field_name = $field['field_name'];
            $field_label = $field['field_label'];
            $field_type = $field['field_type'];
            $is_required = $field['is_required'];
            $field_options = $field['field_options'];
            $value = $values[$field_name] ?? '';
            
            $required_attr = $is_required ? 'required' : '';
            
            $html .= '<div class="form-group">';
            $html .= '<label for="' . htmlspecialchars($field_name) . '">' . htmlspecialchars($field_label);
            if ($is_required) $html .= ' <span style="color: red;">*</span>';
            $html .= '</label>';
            
            switch ($field_type) {
                case 'text':
                    $html .= '<input type="text" id="' . htmlspecialchars($field_name) . '" name="metadata[' . htmlspecialchars($field_name) . ']" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
                    break;
                    
                case 'number':
                    $html .= '<input type="number" id="' . htmlspecialchars($field_name) . '" name="metadata[' . htmlspecialchars($field_name) . ']" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
                    break;
                    
                case 'date':
                    $html .= '<input type="date" id="' . htmlspecialchars($field_name) . '" name="metadata[' . htmlspecialchars($field_name) . ']" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
                    break;
                    
                case 'time':
                    $html .= '<input type="time" id="' . htmlspecialchars($field_name) . '" name="metadata[' . htmlspecialchars($field_name) . ']" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
                    break;
                    
                case 'textarea':
                    $html .= '<textarea id="' . htmlspecialchars($field_name) . '" name="metadata[' . htmlspecialchars($field_name) . ']" ' . $required_attr . '>' . htmlspecialchars($value) . '</textarea>';
                    break;
                    
                case 'dropdown':
                    $html .= '<select id="' . htmlspecialchars($field_name) . '" name="metadata[' . htmlspecialchars($field_name) . ']" ' . $required_attr . '>';
                    $html .= '<option value="">Select an option</option>';
                    
                    if ($field_options) {
                        $options = json_decode($field_options, true);
                        if ($options) {
                            foreach ($options as $option) {
                                $selected = ($value === $option) ? 'selected' : '';
                                $html .= '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                            }
                        }
                    }
                    $html .= '</select>';
                    break;
                    
                case 'cascading_dropdown':
                    $html .= $this->generateCascadingDropdown($field);
                    break;
                    
                case 'reference':
                    $html .= $this->generateReferenceField($field, $value);
                    break;
                    
                case 'file':
                    $html .= '<input type="file" id="' . htmlspecialchars($field_name) . '" name="metadata[' . htmlspecialchars($field_name) . ']" ' . $required_attr . ' style="width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px;">';
                    if ($value) {
                        $html .= '<p style="margin-top: 0.5rem; font-size: 0.9rem; color: #6c757d;">Current file: ' . htmlspecialchars(basename($value)) . '</p>';
                    }
                    break;
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    // Generate cascading dropdown HTML
    private function generateCascadingDropdown($field) {
        $field_name = $field['field_name'];
        $hierarchy_levels = json_decode($field['hierarchy_levels'], true);
        $level_labels = json_decode($field['level_labels'], true);
        
        if (!$hierarchy_levels || !$level_labels) {
            return '<p style="color: red;">Cascading dropdown configuration error</p>';
        }
        
        $html = '';
        
        // Generate dropdowns for each level
        foreach ($hierarchy_levels as $index => $level) {
            $label = $level_labels[$index] ?? ucfirst($level);
            $select_id = $field_name . '_' . $level;
            
            $html .= '<div class="cascading-level" style="margin-bottom: 0.5rem;">';
            $html .= '<label for="' . htmlspecialchars($select_id) . '" style="font-size: 0.9rem; color: #6C757D;">' . htmlspecialchars($label) . '</label>';
            $html .= '<select id="' . htmlspecialchars($select_id) . '" class="cascading-dropdown" style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem;">';
            $html .= '<option value="">Select ' . htmlspecialchars($label) . '</option>';
            $html .= '</select>';
            $html .= '</div>';
        }
        
        // Hidden field to store the final selected values
        $html .= '<input type="hidden" id="' . htmlspecialchars($field_name) . '_data" name="metadata[' . htmlspecialchars($field_name) . ']" value="">';
        
        // JavaScript initialization
        $levels_json = json_encode($hierarchy_levels);
        $html .= '<script>';
        $html .= 'document.addEventListener("DOMContentLoaded", function() {';
        $html .= '    initCascadingDropdown("' . htmlspecialchars($field_name) . '", ' . $levels_json . ');';
        $html .= '});';
        $html .= '</script>';
        
        return $html;
    }
    
    // Generate reference field HTML
    private function generateReferenceField($field, $value = '') {
        $field_name = $field['field_name'];
        $field_id = htmlspecialchars($field_name);
        $is_required = $field['is_required'];
        $required_attr = $is_required ? 'required' : '';
        
        $html = '';
        
        // Hidden input to store selected image ID
        $html .= '<input type="hidden" id="' . $field_id . '" name="metadata[' . $field_id . ']" value="' . htmlspecialchars($value) . '" ' . $required_attr . '>';
        
        // Display area for selected image
        $html .= '<div id="' . $field_id . '_display" class="reference-display" style="border: 1px solid #ddd; border-radius: 4px; padding: 1rem; margin-bottom: 0.5rem; min-height: 60px; background: #f8f9fa;">';
        
        if ($value) {
            // If there's a value, we'll load the image info via JavaScript
            $html .= '<div id="' . $field_id . '_selected" style="display: none;">Loading selected image...</div>';
        } else {
            $html .= '<div id="' . $field_id . '_placeholder" style="color: #6c757d; text-align: center; padding: 1rem;">No image selected</div>';
        }
        
        $html .= '</div>';
        
        // Button to open selection modal
        $html .= '<button type="button" onclick="openReferenceSelector(\'' . $field_id . '\')" class="btn btn-secondary" style="margin-right: 0.5rem;">Select Image</button>';
        
        // Clear button
        $html .= '<button type="button" onclick="clearReferenceSelection(\'' . $field_id . '\')" class="btn btn-outline-secondary">Clear</button>';
        
        return $html;
    }
}
?>