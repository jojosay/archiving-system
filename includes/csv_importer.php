<?php
// CSV Importer Class for location hierarchy data

class CSVImporter {
    private $db;
    private $upload_dir;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->upload_dir = 'data/location_csv/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
        
        // Increase execution time and memory limit for large CSV files
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '256M');
    }
    
    // Import regions CSV
    public function importRegions($csv_file_path) {
        try {
            // Disable foreign key checks temporarily
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $this->db->beginTransaction();
            
            // Clear existing regions data and reset auto-increment
            $this->db->exec("DELETE FROM regions");
            $this->db->exec("ALTER TABLE regions AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found: " . $csv_file_path);
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file: " . $csv_file_path);
            }
            
            $header = fgetcsv($handle); // Skip header row
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO regions (id, region_name, region_code) VALUES (?, ?, ?)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Skip empty rows
                if (empty($data[0]) || empty($data[1]) || empty($data[2])) {
                    continue;
                }
                $stmt->execute([$data[0], $data[1], $data[2]]);
                $count++;
            }
            
            fclose($handle);
            $this->db->commit();
            
            // Re-enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Regions imported successfully ($count records)"];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            // Re-enable foreign key checks even on error
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error importing regions: ' . $e->getMessage()];
        }
    }
    
    // Import provinces CSV
    public function importProvinces($csv_file_path) {
        try {
            // Disable foreign key checks temporarily
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $this->db->beginTransaction();
            
            // Clear existing provinces data and reset auto-increment
            $this->db->exec("DELETE FROM provinces");
            $this->db->exec("ALTER TABLE provinces AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found: " . $csv_file_path);
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file: " . $csv_file_path);
            }
            
            $header = fgetcsv($handle); // Skip header row
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO provinces (id, province_name, region_code) VALUES (?, ?, ?)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Skip empty rows
                if (empty($data[0]) || empty($data[1]) || empty($data[2])) {
                    continue;
                }
                $stmt->execute([$data[0], $data[1], $data[2]]);
                $count++;
            }
            
            fclose($handle);
            $this->db->commit();
            
            // Re-enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Provinces imported successfully ($count records)"];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            // Re-enable foreign key checks even on error
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error importing provinces: ' . $e->getMessage()];
        }
    }
    
    // Import cities/municipalities CSV
    public function importCityMun($csv_file_path) {
        try {
            // Disable foreign key checks temporarily
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $this->db->beginTransaction();
            
            // Clear existing citymun data and reset auto-increment
            $this->db->exec("DELETE FROM citymun");
            $this->db->exec("ALTER TABLE citymun AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found: " . $csv_file_path);
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file: " . $csv_file_path);
            }
            
            $header = fgetcsv($handle); // Skip header row
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO citymun (id, citymun_name, province_id) VALUES (?, ?, ?)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Skip empty rows
                if (empty($data[0]) || empty($data[1]) || empty($data[2])) {
                    continue;
                }
                $stmt->execute([$data[0], $data[1], $data[2]]);
                $count++;
            }
            
            fclose($handle);
            $this->db->commit();
            
            // Re-enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Cities/Municipalities imported successfully ($count records)"];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            // Re-enable foreign key checks even on error
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error importing cities/municipalities: ' . $e->getMessage()];
        }
    }
    
    // Import barangays CSV
    public function importBarangays($csv_file_path) {
        try {
            // Disable foreign key checks temporarily
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $this->db->beginTransaction();
            
            // Clear existing barangays data and reset auto-increment
            $this->db->exec("DELETE FROM barangays");
            $this->db->exec("ALTER TABLE barangays AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found: " . $csv_file_path);
            }
            
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file: " . $csv_file_path);
            }
            
            $header = fgetcsv($handle); // Skip header row
            $count = 0;
            
            $stmt = $this->db->prepare("REPLACE INTO barangays (id, barangay_name, citymun_id) VALUES (?, ?, ?)");
            
            $batch_size = 1000; // Process in batches
            $batch_count = 0;
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                // Skip empty rows
                if (empty($data[0]) || empty($data[1]) || empty($data[2])) {
                    continue;
                }
                
                $stmt->execute([$data[0], $data[1], $data[2]]);
                $count++;
                $batch_count++;
                
                // Commit every batch_size records to prevent timeout
                if ($batch_count >= $batch_size) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                    $batch_count = 0;
                }
            }
            
            fclose($handle);
            $this->db->commit();
            
            // Re-enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return ['success' => true, 'message' => "Barangays imported successfully ($count records)"];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            // Re-enable foreign key checks even on error
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'message' => 'Error importing barangays: ' . $e->getMessage()];
        }
    }
    
    // Get upload directory
    public function getUploadDir() {
        return $this->upload_dir;
    }
}
?>