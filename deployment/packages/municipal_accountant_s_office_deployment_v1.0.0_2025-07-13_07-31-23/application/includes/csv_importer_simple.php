<?php
// Simplified CSV Importer for large files
class SimpleCSVImporter {
    private $db;
    private $upload_dir;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->upload_dir = 'data/location_csv/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
        
        // Increase limits for large files
        set_time_limit(0); // No time limit
        ini_set('memory_limit', '512M');
    }
    
    // Simple import without transactions for large files
    public function importRegions($csv_file_path) {
        try {
            // Disable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Clear existing data
            $this->db->exec("DELETE FROM regions");
            $this->db->exec("ALTER TABLE regions AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found");
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file");
            }
            
            $header = fgetcsv($handle); // Skip header
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO regions (id, region_name, region_code) VALUES (?, ?, ?)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[0]) && !empty($data[1]) && !empty($data[2])) {
                    $stmt->execute([$data[0], $data[1], $data[2]]);
                    $count++;
                }
            }
            
            fclose($handle);
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Regions imported: $count records"];
        } catch (Exception $e) {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function importProvinces($csv_file_path) {
        try {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->exec("DELETE FROM provinces");
            $this->db->exec("ALTER TABLE provinces AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found");
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file");
            }
            
            $header = fgetcsv($handle);
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO provinces (id, province_name, region_code) VALUES (?, ?, ?)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[0]) && !empty($data[1]) && !empty($data[2])) {
                    $stmt->execute([$data[0], $data[1], $data[2]]);
                    $count++;
                }
            }
            
            fclose($handle);
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Provinces imported: $count records"];
        } catch (Exception $e) {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function importCityMun($csv_file_path) {
        try {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->exec("DELETE FROM citymun");
            $this->db->exec("ALTER TABLE citymun AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found");
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file");
            }
            
            $header = fgetcsv($handle);
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO citymun (id, citymun_name, province_id) VALUES (?, ?, ?)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[0]) && !empty($data[1]) && !empty($data[2])) {
                    $stmt->execute([$data[0], $data[1], $data[2]]);
                    $count++;
                }
            }
            
            fclose($handle);
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Cities/Municipalities imported: $count records"];
        } catch (Exception $e) {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function importBarangays($csv_file_path) {
        try {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->exec("DELETE FROM barangays");
            $this->db->exec("ALTER TABLE barangays AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found");
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file");
            }
            
            $header = fgetcsv($handle);
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO barangays (id, barangay_name, citymun_id) VALUES (?, ?, ?)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[0]) && !empty($data[1]) && !empty($data[2])) {
                    $stmt->execute([$data[0], $data[1], $data[2]]);
                    $count++;
                    
                    // Show progress for large files
                    if ($count % 5000 == 0) {
                        echo "Processed $count records...\n";
                        flush();
                    }
                }
            }
            
            fclose($handle);
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Barangays imported: $count records"];
        } catch (Exception $e) {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function getUploadDir() {
        return $this->upload_dir;
    }
}
?>