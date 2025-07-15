<?php
/**
 * Template Category Manager Class
 * Handles template category operations
 */

class TemplateCategoryManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    /**
     * Get all categories
     */
    public function getAllCategories($active_only = true) {
        try {
            $sql = "SELECT * FROM template_categories";
            
            if ($active_only) {
                $sql .= " WHERE is_active = 1";
            }
            
            $sql .= " ORDER BY sort_order ASC, name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get category by name
     */
    public function getCategoryByName($name) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM template_categories WHERE name = ?");
            $stmt->execute([$name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get category by ID
     */
    public function getCategoryById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM template_categories WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Create new category
     */
    public function createCategory($data) {
        try {
            // Validate required fields
            if (empty($data['name'])) {
                return ['success' => false, 'message' => 'Category name is required'];
            }
            
            // Check if category already exists
            if ($this->getCategoryByName($data['name'])) {
                return ['success' => false, 'message' => 'Category already exists'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO template_categories (name, description, icon, color, sort_order) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['icon'] ?? 'folder',
                $data['color'] ?? '#3498db',
                $data['sort_order'] ?? 0
            ]);
            
            return [
                'success' => true,
                'id' => $this->db->lastInsertId(),
                'message' => 'Category created successfully'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating category: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update category
     */
    public function updateCategory($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['name'])) {
                // Check if new name conflicts with existing category
                $existing = $this->getCategoryByName($data['name']);
                if ($existing && $existing['id'] != $id) {
                    return ['success' => false, 'message' => 'Category name already exists'];
                }
                $fields[] = "name = ?";
                $params[] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $fields[] = "description = ?";
                $params[] = $data['description'];
            }
            
            if (isset($data['icon'])) {
                $fields[] = "icon = ?";
                $params[] = $data['icon'];
            }
            
            if (isset($data['color'])) {
                $fields[] = "color = ?";
                $params[] = $data['color'];
            }
            
            if (isset($data['sort_order'])) {
                $fields[] = "sort_order = ?";
                $params[] = $data['sort_order'];
            }
            
            if (isset($data['is_active'])) {
                $fields[] = "is_active = ?";
                $params[] = $data['is_active'];
            }
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
            
            $params[] = $id;
            $sql = "UPDATE template_categories SET " . implode(", ", $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'message' => 'Category updated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating category: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete category
     */
    public function deleteCategory($id) {
        try {
            // Check if category has templates
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM document_templates WHERE category = (SELECT name FROM template_categories WHERE id = ?)");
            $stmt->execute([$id]);
            $template_count = $stmt->fetchColumn();
            
            if ($template_count > 0) {
                return ['success' => false, 'message' => "Cannot delete category. It contains {$template_count} template(s)"];
            }
            
            // Delete category
            $stmt = $this->db->prepare("DELETE FROM template_categories WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Category deleted successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting category: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get categories with template counts
     */
    public function getCategoriesWithCounts() {
        try {
            $sql = "SELECT tc.*, 
                           COUNT(dt.id) as template_count,
                           SUM(dt.download_count) as total_downloads
                    FROM template_categories tc
                    LEFT JOIN document_templates dt ON tc.name = dt.category AND dt.is_active = 1
                    WHERE tc.is_active = 1
                    GROUP BY tc.id
                    ORDER BY tc.sort_order ASC, tc.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Reorder categories
     */
    public function reorderCategories($category_orders) {
        try {
            $this->db->beginTransaction();
            
            foreach ($category_orders as $id => $sort_order) {
                $stmt = $this->db->prepare("UPDATE template_categories SET sort_order = ? WHERE id = ?");
                $stmt->execute([$sort_order, $id]);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Categories reordered successfully'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error reordering categories: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get available icons
     */
    public function getAvailableIcons() {
        return [
            'folder' => 'Folder',
            'file-text' => 'Document',
            'mail' => 'Mail',
            'bar-chart' => 'Chart',
            'award' => 'Award',
            'grid' => 'Grid',
            'briefcase' => 'Briefcase',
            'book' => 'Book',
            'clipboard' => 'Clipboard',
            'database' => 'Database',
            'edit' => 'Edit',
            'file' => 'File',
            'home' => 'Home',
            'image' => 'Image',
            'layers' => 'Layers',
            'list' => 'List',
            'map' => 'Map',
            'package' => 'Package',
            'printer' => 'Printer',
            'settings' => 'Settings',
            'star' => 'Star',
            'tag' => 'Tag',
            'users' => 'Users',
            'calendar' => 'Calendar',
            'clock' => 'Clock'
        ];
    }
    
    /**
     * Get available colors
     */
    public function getAvailableColors() {
        return [
            '#3498db' => 'Blue',
            '#2ecc71' => 'Green',
            '#e74c3c' => 'Red',
            '#f39c12' => 'Orange',
            '#9b59b6' => 'Purple',
            '#34495e' => 'Dark Gray',
            '#1abc9c' => 'Teal',
            '#e67e22' => 'Carrot',
            '#95a5a6' => 'Silver',
            '#16a085' => 'Green Sea',
            '#27ae60' => 'Nephritis',
            '#2980b9' => 'Belize Hole',
            '#8e44ad' => 'Wisteria',
            '#2c3e50' => 'Midnight Blue',
            '#f1c40f' => 'Sun Flower',
            '#d35400' => 'Pumpkin',
            '#c0392b' => 'Pomegranate',
            '#7f8c8d' => 'Concrete'
        ];
    }
    
    /**
     * Validate category data
     */
    public function validateCategoryData($data) {
        $errors = [];
        
        // Validate name
        if (empty($data['name'])) {
            $errors[] = 'Category name is required';
        } elseif (strlen($data['name']) > 100) {
            $errors[] = 'Category name cannot exceed 100 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $data['name'])) {
            $errors[] = 'Category name contains invalid characters';
        }
        
        // Validate description
        if (isset($data['description']) && strlen($data['description']) > 500) {
            $errors[] = 'Description cannot exceed 500 characters';
        }
        
        // Validate icon
        if (isset($data['icon'])) {
            $available_icons = array_keys($this->getAvailableIcons());
            if (!in_array($data['icon'], $available_icons)) {
                $errors[] = 'Invalid icon selected';
            }
        }
        
        // Validate color
        if (isset($data['color'])) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'])) {
                $errors[] = 'Invalid color format';
            }
        }
        
        // Validate sort order
        if (isset($data['sort_order'])) {
            if (!is_numeric($data['sort_order']) || $data['sort_order'] < 0) {
                $errors[] = 'Sort order must be a positive number';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>