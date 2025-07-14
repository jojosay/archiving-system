<?php
// Location Manager Class for handling location hierarchy data

class LocationManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    // Get all regions
    public function getAllRegions() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM regions ORDER BY region_name");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get provinces by region code
    public function getProvincesByRegion($region_code) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM provinces WHERE region_code = ? ORDER BY province_name");
            $stmt->execute([$region_code]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get cities/municipalities by province ID
    public function getCityMunByProvince($province_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM citymun WHERE province_id = ? ORDER BY citymun_name");
            $stmt->execute([$province_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Get barangays by city/municipality ID
    public function getBarangaysByCityMun($citymun_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM barangays WHERE citymun_id = ? ORDER BY barangay_name");
            $stmt->execute([$citymun_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>