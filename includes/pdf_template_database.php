<?php
/**
 * PDF Template Database Setup
 * Creates the necessary table for storing PDF template information
 */

class PDFTemplateDatabase {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    /**
     * Create the pdf_templates table
     */
    public function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS pdf_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            pages INT DEFAULT NULL,
            metadata TEXT DEFAULT NULL,
            thumbnail_path VARCHAR(500) DEFAULT NULL,
            uploaded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            INDEX idx_uploaded_by (uploaded_by),
            INDEX idx_created_at (created_at),
            INDEX idx_is_active (is_active),
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->db->exec($sql);
            return ['success' => true, 'message' => 'PDF templates table created successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error creating table: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if the table exists
     */
    public function tableExists() {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'pdf_templates'");
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all PDF templates for a user
     */
    public function getUserTemplates($user_id, $include_all = false) {
        try {
            if ($include_all) {
                // Admin can see all templates
                $sql = "SELECT pt.*, u.full_name as uploaded_by_name 
                       FROM pdf_templates pt 
                       LEFT JOIN users u ON pt.uploaded_by = u.id 
                       WHERE pt.is_active = 1 
                       ORDER BY pt.created_at DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            } else {
                // Regular users see only their templates
                $sql = "SELECT pt.*, u.full_name as uploaded_by_name 
                       FROM pdf_templates pt 
                       LEFT JOIN users u ON pt.uploaded_by = u.id 
                       WHERE pt.uploaded_by = ? AND pt.is_active = 1 
                       ORDER BY pt.created_at DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$user_id]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching PDF templates: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a specific template by ID
     */
    public function getTemplate($template_id, $user_id = null) {
        try {
            if ($user_id) {
                // Check ownership
                $sql = "SELECT pt.*, u.full_name as uploaded_by_name 
                       FROM pdf_templates pt 
                       LEFT JOIN users u ON pt.uploaded_by = u.id 
                       WHERE pt.id = ? AND pt.uploaded_by = ? AND pt.is_active = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$template_id, $user_id]);
            } else {
                // Admin access
                $sql = "SELECT pt.*, u.full_name as uploaded_by_name 
                       FROM pdf_templates pt 
                       LEFT JOIN users u ON pt.uploaded_by = u.id 
                       WHERE pt.id = ? AND pt.is_active = 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$template_id]);
            }
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching PDF template: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete a template (soft delete)
     */
    public function deleteTemplate($template_id, $user_id) {
        try {
            $sql = "UPDATE pdf_templates SET is_active = 0 WHERE id = ? AND uploaded_by = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$template_id, $user_id]);
            
            return [
                'success' => $result && $stmt->rowCount() > 0,
                'message' => $result && $stmt->rowCount() > 0 ? 'Template deleted successfully' : 'Template not found or access denied'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error deleting template: ' . $e->getMessage()];
        }
    }
}
?>