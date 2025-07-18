<?php
// AJAX API endpoints for cascading dropdown location data
header('Content-Type: application/json');

// Include required files
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/location_manager.php';

// Initialize database and location manager
$database = new Database();
$locationManager = new LocationManager($database);

// Get request parameters
$action = $_GET['action'] ?? '';
$parent_id = $_GET['parent_id'] ?? '';
$region_code = $_GET['region_code'] ?? '';

try {
    switch ($action) {
        case 'get_region':
        case 'get_regions':
            $regions = $locationManager->getAllRegions();
            echo json_encode([
                'success' => true,
                'data' => $regions
            ]);
            break;
            
        case 'get_province':
        case 'get_provinces':
            if (empty($region_code)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Region code is required'
                ]);
                break;
            }
            
            $provinces = $locationManager->getProvincesByRegion($region_code);
            echo json_encode([
                'success' => true,
                'data' => $provinces
            ]);
            break;
            
        case 'get_citymun':
            if (empty($parent_id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Province ID is required'
                ]);
                break;
            }
            
            $citymun = $locationManager->getCityMunByProvince($parent_id);
            echo json_encode([
                'success' => true,
                'data' => $citymun
            ]);
            break;
            
        case 'get_barangay':
        case 'get_barangays':
            if (empty($parent_id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'City/Municipality ID is required'
                ]);
                break;
            }
            
            $barangays = $locationManager->getBarangaysByCityMun($parent_id);
            echo json_encode([
                'success' => true,
                'data' => $barangays
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>