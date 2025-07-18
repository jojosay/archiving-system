<?php
require_once '../config/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'locations' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle both old format (codes array) and new format (individual codes)
    $regionCode = $input['region'] ?? '';
    $provinceCode = $input['province'] ?? '';
    $citymunCode = $input['citymun'] ?? '';
    $barangayCode = $input['barangay'] ?? '';
    $locationCodes = $input['codes'] ?? [];
    
    if (!empty($regionCode) || !empty($provinceCode) || !empty($citymunCode) || !empty($barangayCode) || !empty($locationCodes)) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $resolvedLocations = [];
            
            // Handle new format with specific location types
            if (!empty($regionCode) || !empty($provinceCode) || !empty($citymunCode) || !empty($barangayCode)) {
                // Resolve region
                if (!empty($regionCode)) {
                    $stmt = $conn->prepare("SELECT region_name FROM regions WHERE region_code = ?");
                    $stmt->execute([$regionCode]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $resolvedLocations['region'] = $result ? $result['region_name'] : $regionCode;
                }
                
                // Resolve province
                if (!empty($provinceCode)) {
                    $stmt = $conn->prepare("SELECT province_name FROM provinces WHERE id = ?");
                    $stmt->execute([$provinceCode]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $resolvedLocations['province'] = $result ? $result['province_name'] : $provinceCode;
                }
                
                // Resolve city/municipality
                if (!empty($citymunCode)) {
                    $stmt = $conn->prepare("SELECT citymun_name FROM citymun WHERE id = ?");
                    $stmt->execute([$citymunCode]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $resolvedLocations['citymun'] = $result ? $result['citymun_name'] : $citymunCode;
                }
                
                // Resolve barangay
                if (!empty($barangayCode)) {
                    $stmt = $conn->prepare("SELECT barangay_name FROM barangays WHERE id = ?");
                    $stmt->execute([$barangayCode]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $resolvedLocations['barangay'] = $result ? $result['barangay_name'] : $barangayCode;
                }
            }
            
            // Handle old format for backward compatibility
            foreach ($locationCodes as $code) {
                $resolvedName = null;
                
                // Try regions first
                $stmt = $conn->prepare("SELECT region_name FROM regions WHERE region_code = ?");
                $stmt->execute([$code]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $resolvedName = $result['region_name'];
                } else {
                    // Try provinces
                    $stmt = $conn->prepare("SELECT province_name FROM provinces WHERE id = ?");
                    $stmt->execute([$code]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $resolvedName = $result['province_name'];
                    } else {
                        // Try cities/municipalities
                        $stmt = $conn->prepare("SELECT citymun_name FROM citymun WHERE id = ?");
                        $stmt->execute([$code]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) {
                            $resolvedName = $result['citymun_name'];
                        } else {
                            // Try barangays
                            $stmt = $conn->prepare("SELECT barangay_name FROM barangays WHERE id = ?");
                            $stmt->execute([$code]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($result) {
                                $resolvedName = $result['barangay_name'];
                            }
                        }
                    }
                }
                
                $resolvedLocations[$code] = $resolvedName;
            }
            
            $response['success'] = true;
            $response['locations'] = $resolvedLocations;
            
        } catch (Exception $e) {
            $response['message'] = 'Error resolving locations: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'No location codes provided';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>