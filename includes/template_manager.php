<?php
/**
 * Template Manager Class
 * Handles template database operations and metadata management
 */

class TemplateManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    /**
     * Get all templates with optional filtering
     */
    public function getAllTemplates($active_only = true, $category = null) {
        try {
            $sql = "SELECT t.*, tc.name as category_name, tc.color as category_color, tc.icon as category_icon,
                           u.username as created_by_username
                    FROM document_templates t 
                    LEFT JOIN template_categories tc ON t.category = tc.name
                    LEFT JOIN users u ON t.created_by = u.id";
            
            $conditions = [];
            $params = [];
            
            if ($active_only) {
                $conditions[] = "t.is_active = 1";
            }
            
            if ($category) {
                $conditions[] = "t.category = ?";
                $params[] = $category;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get template by ID
     */
    public function getTemplateById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, tc.name as category_name, tc.color as category_color, tc.icon as category_icon,
                       u.username as created_by_username
                FROM document_templates t 
                LEFT JOIN template_categories tc ON t.category = tc.name
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Create a new template
     */
    public function createTemplate($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO document_templates 
                (name, description, file_path, file_name, file_size, file_type, mime_type, category, tags, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $tags_json = isset($data['tags']) ? json_encode($data['tags']) : null;
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['file_path'],
                $data['file_name'],
                $data['file_size'],
                $data['file_type'],
                $data['mime_type'],
                $data['category'] ?? null,
                $tags_json,
                $data['created_by'] ?? null
            ]);
            
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating template: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update template
     */
    public function updateTemplate($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['name'])) {
                $fields[] = "name = ?";
                $params[] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $fields[] = "description = ?";
                $params[] = $data['description'];
            }
            
            if (isset($data['category'])) {
                $fields[] = "category = ?";
                $params[] = $data['category'];
            }
            
            if (isset($data['tags'])) {
                $fields[] = "tags = ?";
                $params[] = json_encode($data['tags']);
            }
            
            if (isset($data['is_active'])) {
                $fields[] = "is_active = ?";
                $params[] = $data['is_active'];
            }
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
            
            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $id;
            
            $sql = "UPDATE document_templates SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating template: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete template
     */
    public function deleteTemplate($id) {
        try {
            // Soft delete - just mark as inactive
            $stmt = $this->db->prepare("UPDATE document_templates SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting template: ' . $e->getMessage()];
        }
    }
    
    /**
     * Search templates
     */
    public function searchTemplates($query, $category = null, $file_type = null) {
        try {
            $sql = "SELECT t.*, tc.name as category_name, tc.color as category_color, tc.icon as category_icon
                    FROM document_templates t 
                    LEFT JOIN template_categories tc ON t.category = tc.name
                    WHERE t.is_active = 1 AND (t.name LIKE ? OR t.description LIKE ?)";
            
            $params = ["%$query%", "%$query%"];
            
            if ($category) {
                $sql .= " AND t.category = ?";
                $params[] = $category;
            }
            
            if ($file_type) {
                $sql .= " AND t.file_type = ?";
                $params[] = $file_type;
            }
            
            $sql .= " ORDER BY t.download_count DESC, t.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount($template_id, $user_id = null, $ip_address = null, $user_agent = null) {
        try {
            // Update download count
            $stmt = $this->db->prepare("UPDATE document_templates SET download_count = download_count + 1 WHERE id = ?");
            $stmt->execute([$template_id]);
            
            // Log download
            $stmt = $this->db->prepare("
                INSERT INTO template_downloads (template_id, user_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$template_id, $user_id, $ip_address, $user_agent]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error tracking download: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get template statistics
     */
    public function getTemplateStats() {
        try {
            $stats = [];
            
            // Total templates
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM document_templates WHERE is_active = 1");
            $stmt->execute();
            $stats['total_templates'] = $stmt->fetchColumn();
            
            // Templates by type
            $stmt = $this->db->prepare("
                SELECT file_type, COUNT(*) as count 
                FROM document_templates 
                WHERE is_active = 1 
                GROUP BY file_type
            ");
            $stmt->execute();
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Total downloads
            $stmt = $this->db->prepare("SELECT SUM(download_count) FROM document_templates WHERE is_active = 1");
            $stmt->execute();
            $stats['total_downloads'] = $stmt->fetchColumn() ?: 0;
            
            // Most popular templates
            $stmt = $this->db->prepare("
                SELECT name, download_count 
                FROM document_templates 
                WHERE is_active = 1 
                ORDER BY download_count DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $stats['popular_templates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
}
?>