<?php
/**
 * Template Database Setup
 * Creates the necessary database tables for the template system
 */

class TemplateDatabaseSetup {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    /**
     * Create all template-related tables
     */
    public function createTables() {
        $results = [];
        
        try {
            // Create document_templates table
            $results['document_templates'] = $this->createDocumentTemplatesTable();
            
            // Create template_categories table
            $results['template_categories'] = $this->createTemplateCategoriesTable();
            
            // Create template_downloads table
            $results['template_downloads'] = $this->createTemplateDownloadsTable();
            
            // Insert default categories
            $results['default_categories'] = $this->insertDefaultCategories();
            
            return [
                'success' => true,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'results' => $results
            ];
        }
    }
    
    /**
     * Create document_templates table
     */
    private function createDocumentTemplatesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS document_templates (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            file_path VARCHAR(500) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            file_type ENUM('docx', 'doc', 'xlsx', 'xls', 'pdf') NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            category VARCHAR(100),
            tags JSON,
            is_active BOOLEAN DEFAULT 1,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            download_count INT DEFAULT 0,
            INDEX idx_template_category (category),
            INDEX idx_template_type (file_type),
            INDEX idx_template_active (is_active),
            INDEX idx_template_created (created_at),
            INDEX idx_template_downloads (download_count),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        return [
            'success' => $result,
            'message' => $result ? 'Document templates table created successfully' : 'Failed to create document templates table'
        ];
    }
    
    /**
     * Create template_categories table
     */
    private function createTemplateCategoriesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS template_categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50) DEFAULT 'folder',
            color VARCHAR(7) DEFAULT '#3498db',
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_category_name (name),
            INDEX idx_category_active (is_active),
            INDEX idx_category_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        return [
            'success' => $result,
            'message' => $result ? 'Template categories table created successfully' : 'Failed to create template categories table'
        ];
    }
    
    /**
     * Create template_downloads table
     */
    private function createTemplateDownloadsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS template_downloads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            template_id INT NOT NULL,
            user_id INT,
            downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            INDEX idx_download_template (template_id),
            INDEX idx_download_user (user_id),
            INDEX idx_download_date (downloaded_at),
            FOREIGN KEY (template_id) REFERENCES document_templates(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute();
        
        return [
            'success' => $result,
            'message' => $result ? 'Template downloads table created successfully' : 'Failed to create template downloads table'
        ];
    }
    
    /**
     * Insert default template categories
     */
    private function insertDefaultCategories() {
        $default_categories = [
            [
                'name' => 'Forms',
                'description' => 'Official forms and applications',
                'icon' => 'file-text',
                'color' => '#3498db',
                'sort_order' => 1
            ],
            [
                'name' => 'Letters',
                'description' => 'Letter templates and correspondence',
                'icon' => 'mail',
                'color' => '#2ecc71',
                'sort_order' => 2
            ],
            [
                'name' => 'Reports',
                'description' => 'Report templates and formats',
                'icon' => 'bar-chart',
                'color' => '#e74c3c',
                'sort_order' => 3
            ],
            [
                'name' => 'Certificates',
                'description' => 'Certificate templates',
                'icon' => 'award',
                'color' => '#f39c12',
                'sort_order' => 4
            ],
            [
                'name' => 'Spreadsheets',
                'description' => 'Excel templates and calculators',
                'icon' => 'grid',
                'color' => '#9b59b6',
                'sort_order' => 5
            ],
            [
                'name' => 'Legal',
                'description' => 'Legal documents and contracts',
                'icon' => 'briefcase',
                'color' => '#34495e',
                'sort_order' => 6
            ]
        ];
        
        $inserted = 0;
        $errors = [];
        
        foreach ($default_categories as $category) {
            try {
                // Check if category already exists
                $check_stmt = $this->db->prepare("SELECT id FROM template_categories WHERE name = ?");
                $check_stmt->execute([$category['name']]);
                
                if (!$check_stmt->fetch()) {
                    // Insert new category
                    $insert_stmt = $this->db->prepare("
                        INSERT INTO template_categories (name, description, icon, color, sort_order) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $insert_stmt->execute([
                        $category['name'],
                        $category['description'],
                        $category['icon'],
                        $category['color'],
                        $category['sort_order']
                    ]);
                    
                    $inserted++;
                }
            } catch (Exception $e) {
                $errors[] = "Failed to insert category '{$category['name']}': " . $e->getMessage();
            }
        }
        
        return [
            'success' => empty($errors),
            'inserted' => $inserted,
            'errors' => $errors,
            'message' => "Inserted {$inserted} default categories"
        ];
    }
    
    /**
     * Check if tables exist
     */
    public function tablesExist() {
        $tables = ['document_templates', 'template_categories', 'template_downloads'];
        $existing_tables = [];
        
        foreach ($tables as $table) {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->fetch()) {
                $existing_tables[] = $table;
            }
        }
        
        return [
            'all_exist' => count($existing_tables) === count($tables),
            'existing' => $existing_tables,
            'missing' => array_diff($tables, $existing_tables)
        ];
    }
    
    /**
     * Drop template tables (for cleanup/reset)
     */
    public function dropTables() {
        $tables = ['template_downloads', 'document_templates', 'template_categories'];
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->db->prepare("DROP TABLE IF EXISTS {$table}");
                $result = $stmt->execute();
                $results[$table] = [
                    'success' => $result,
                    'message' => $result ? "Table {$table} dropped successfully" : "Failed to drop table {$table}"
                ];
            } catch (Exception $e) {
                $results[$table] = [
                    'success' => false,
                    'message' => "Error dropping table {$table}: " . $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get template system status
     */
    public function getSystemStatus() {
        $status = [];
        
        // Check tables
        $tables_status = $this->tablesExist();
        $status['tables'] = $tables_status;
        
        // Check storage directories
        $storage_base = __DIR__ . '/../storage/templates/';
        $required_dirs = ['docx/', 'excel/', 'pdf/', 'temp/'];
        
        $dirs_status = [];
        foreach ($required_dirs as $dir) {
            $full_path = $storage_base . $dir;
            $dirs_status[$dir] = [
                'exists' => is_dir($full_path),
                'writable' => is_writable($full_path),
                'path' => $full_path
            ];
        }
        $status['directories'] = $dirs_status;
        
        // Check template counts
        if ($tables_status['all_exist']) {
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM document_templates WHERE is_active = 1");
                $stmt->execute();
                $status['template_count'] = $stmt->fetchColumn();
                
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM template_categories WHERE is_active = 1");
                $stmt->execute();
                $status['category_count'] = $stmt->fetchColumn();
                
                $stmt = $this->db->prepare("SELECT SUM(download_count) FROM document_templates WHERE is_active = 1");
                $stmt->execute();
                $status['total_downloads'] = $stmt->fetchColumn() ?: 0;
            } catch (Exception $e) {
                $status['error'] = $e->getMessage();
            }
        }
        
        return $status;
    }
}
?>