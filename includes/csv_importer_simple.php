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
    
    // Progress-enabled import methods
    public function importRegionsWithProgress($csv_file_path, $upload_id) {
        return $this->importWithProgress($csv_file_path, $upload_id, 'regions', 
            "REPLACE INTO regions (id, region_name, region_code) VALUES (?, ?, ?)");
    }
    
    public function importProvincesWithProgress($csv_file_path, $upload_id) {
        return $this->importWithProgress($csv_file_path, $upload_id, 'provinces',
            "REPLACE INTO provinces (id, province_name, region_code) VALUES (?, ?, ?)");
    }
    
    public function importCityMunWithProgress($csv_file_path, $upload_id) {
        return $this->importWithProgress($csv_file_path, $upload_id, 'citymun',
            "REPLACE INTO citymun (id, citymun_name, province_id) VALUES (?, ?, ?)");
    }
    
    public function importBarangaysWithProgress($csv_file_path, $upload_id) {
        // Add specific logging for barangays
        error_log("importBarangaysWithProgress called with file: $csv_file_path, upload_id: $upload_id");
        
        try {
            $result = $this->importWithProgress($csv_file_path, $upload_id, 'barangays',
                "REPLACE INTO barangays (id, barangay_name, citymun_id) VALUES (?, ?, ?)");
            
            error_log("Barangays import completed with result: " . json_encode($result));
            return $result;
        } catch (Exception $e) {
            error_log("Barangays import failed with exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Barangays import error: ' . $e->getMessage()];
        }
    }
    
    private function importWithProgress($csv_file_path, $upload_id, $table, $sql) {
        try {
            // Start session only if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->exec("DELETE FROM $table");
            $this->db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            
            if (!file_exists($csv_file_path)) {
                throw new Exception("CSV file not found");
            }
            
            // Count total rows first (only valid rows)
            $total_rows = 0;
            $csv_total_rows = 0;
            $handle = fopen($csv_file_path, 'r');
            if (!$handle) {
                throw new Exception("Cannot open CSV file");
            }
            
            fgetcsv($handle); // Skip header
            while (($data = fgetcsv($handle)) !== FALSE) {
                $csv_total_rows++; // Count all CSV rows
                // Only count rows with valid data
                if (count($data) >= 3 && !empty(trim($data[0])) && !empty(trim($data[1])) && !empty(trim($data[2]))) {
                    $total_rows++;
                } else {
                    error_log("[$table] Invalid row found at CSV line " . ($csv_total_rows + 1) . ": " . json_encode($data));
                }
            }
            fclose($handle);
            
            error_log("[$table] CSV Analysis: Total CSV rows: $csv_total_rows, Valid rows: $total_rows, Invalid rows: " . ($csv_total_rows - $total_rows));
            
            // Update total rows in session
            $_SESSION['upload_progress'][$upload_id]['total_rows'] = $total_rows;
            
            // Reopen file for processing
            $handle = fopen($csv_file_path, 'r');
            $header = fgetcsv($handle); // Skip header
            $count = 0;
            
            $stmt = $this->db->prepare($sql);
            
            $line_number = 1; // Start at 1 (header is line 1)
            while (($data = fgetcsv($handle)) !== FALSE) {
                $line_number++;
                
                // Skip empty rows or rows with insufficient data
                if (count($data) < 3 || empty(trim($data[0])) || empty(trim($data[1])) || empty(trim($data[2]))) {
                    error_log("[$table] Skipping invalid row at line $line_number: " . json_encode($data));
                    continue;
                }
                
                try {
                    $stmt->execute([trim($data[0]), trim($data[1]), trim($data[2])]);
                    $count++;
                    
                    // Update progress every 100 records
                    if ($count % 100 == 0) {
                        $progress = ($count / $total_rows) * 100;
                        $_SESSION['upload_progress'][$upload_id]['progress'] = min(99, $progress);
                        $_SESSION['upload_progress'][$upload_id]['processed_rows'] = $count;
                        $_SESSION['upload_progress'][$upload_id]['message'] = "Processing... ($count / $total_rows records)";
                    }
                } catch (PDOException $e) {
                    // Log the error but continue processing
                    $line_number = $count + 2; // +1 for 0-based index, +1 for header row
                    error_log("[$table] Error inserting record at line $line_number: " . $e->getMessage());
                    error_log("[$table] Failed data: " . json_encode($data));
                    error_log("[$table] SQL Error Code: " . $e->getCode());
                    
                    // Update session with error info
                    if (!isset($_SESSION['upload_progress'][$upload_id]['errors'])) {
                        $_SESSION['upload_progress'][$upload_id]['errors'] = 0;
                    }
                    $_SESSION['upload_progress'][$upload_id]['errors']++;
                    continue;
                }
            }
            
            fclose($handle);
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Final progress update to ensure 100% completion
            $_SESSION['upload_progress'][$upload_id]['progress'] = 100;
            $_SESSION['upload_progress'][$upload_id]['processed_rows'] = $count;
            $_SESSION['upload_progress'][$upload_id]['message'] = "Import completed successfully";
            
            // Check if all records were processed
            $skipped_records = $total_rows - $count;
            $message = ucfirst($table) . " imported: $count records";
            if ($skipped_records > 0) {
                $message .= " ($skipped_records records skipped due to invalid data or errors)";
                error_log("Import completed for $table: $count/$total_rows records processed, $skipped_records skipped");
            }
            
            return ['success' => true, 'message' => $message];
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