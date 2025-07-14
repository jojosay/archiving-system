<?php
/**
 * Database Export Manager Class
 * Handles database schema export and initial data for deployment packages
 */

class DatabaseExportManager {
    private $database;
    private $export_dir;
    
    public function __construct() {
        $this->database = new Database();
        $this->export_dir = __DIR__ . '/../deployment/packages/';
    }
    
    /**
     * Export database schema
     */
    public function exportSchema($package_path) {
        try {
            $schema_file = $package_path . 'database/schema.sql';
            
            // Create database directory in package
            if (!is_dir($package_path . 'database/')) {
                mkdir($package_path . 'database/', 0755, true);
            }
            
            // Read the original schema file
            $original_schema = __DIR__ . '/../database/schema.sql';
            if (file_exists($original_schema)) {
                $schema_content = file_get_contents($original_schema);
                file_put_contents($schema_file, $schema_content);
                
                return [
                    'success' => true,
                    'file' => $schema_file,
                    'message' => 'Schema exported successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Original schema file not found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error exporting schema: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Export initial configuration data
     */
    public function exportInitialData($package_path, $office_config = []) {
        try {
            $data_file = $package_path . 'database/initial_data.sql';
            
            $sql_content = "-- Initial Data for Deployment\n";
            $sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Export document types
            $sql_content .= $this->exportDocumentTypes();
            
            // Export locations (if any)
            $sql_content .= $this->exportLocations();
            
            // Export initial admin user (optional)
            if (isset($office_config['create_admin']) && $office_config['create_admin']) {
                $sql_content .= $this->generateAdminUser($office_config);
            }
            
            file_put_contents($data_file, $sql_content);
            
            return [
                'success' => true,
                'file' => $data_file,
                'message' => 'Initial data exported successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error exporting initial data: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Export document types
     */
    private function exportDocumentTypes() {
        $sql = "\n-- Document Types\n";
        
        try {
            $stmt = $this->database->getConnection()->query("SELECT * FROM document_types ORDER BY id");
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($types)) {
                foreach ($types as $type) {
                    $sql .= "INSERT INTO document_types (name, description, fields_config) VALUES (";
                    $sql .= "'" . addslashes($type['name']) . "', ";
                    $sql .= "'" . addslashes($type['description']) . "', ";
                    $sql .= "'" . addslashes($type['fields_config']) . "');\n";
                }
            }
            
        } catch (Exception $e) {
            $sql .= "-- Error exporting document types: " . $e->getMessage() . "\n";
        }
        
        return $sql . "\n";
    }
    
    /**
     * Export locations
     */
    private function exportLocations() {
        $sql = "\n-- Locations\n";
        
        try {
            $stmt = $this->database->getConnection()->query("SELECT * FROM locations ORDER BY id");
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($locations)) {
                foreach ($locations as $location) {
                    $sql .= "INSERT INTO locations (province, city, barangay) VALUES (";
                    $sql .= "'" . addslashes($location['province']) . "', ";
                    $sql .= "'" . addslashes($location['city']) . "', ";
                    $sql .= "'" . addslashes($location['barangay']) . "');\n";
                }
            }
            
        } catch (Exception $e) {
            $sql .= "-- Error exporting locations: " . $e->getMessage() . "\n";
        }
        
        return $sql . "\n";
    }
    
    /**
     * Generate admin user creation script
     */
    private function generateAdminUser($office_config) {
        $sql = "\n-- Admin User\n";
        
        $username = $office_config['admin_username'] ?? 'admin';
        $password = $office_config['admin_password'] ?? 'admin123';
        $email = $office_config['admin_email'] ?? 'admin@office.local';
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql .= "INSERT INTO users (username, password, email, role, created_at) VALUES (";
        $sql .= "'" . addslashes($username) . "', ";
        $sql .= "'" . addslashes($hashed_password) . "', ";
        $sql .= "'" . addslashes($email) . "', ";
        $sql .= "'admin', ";
        $sql .= "NOW());\n";
        
        return $sql . "\n";
    }
    
    /**
     * Create office-specific configuration
     */
    public function createOfficeConfig($package_path, $office_data) {
        try {
            $config_file = $package_path . 'database/office_config.sql';
            
            $sql_content = "-- Office-Specific Configuration\n";
            $sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Add office-specific settings if needed
            if (isset($office_data['office_name'])) {
                $sql_content .= "-- Office: " . addslashes($office_data['office_name']) . "\n";
            }
            
            if (isset($office_data['deployment_id'])) {
                $sql_content .= "-- Deployment ID: " . addslashes($office_data['deployment_id']) . "\n";
            }
            
            $sql_content .= "\n-- Additional office-specific data can be added here\n";
            
            file_put_contents($config_file, $sql_content);
            
            return [
                'success' => true,
                'file' => $config_file,
                'message' => 'Office configuration created successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating office configuration: ' . $e->getMessage()
            ];
        }
    }
}
?>