<?php
require_once '../config/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'hierarchy' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $locationIds = $input['location_ids'] ?? [];
    
    if (!empty($locationIds)) {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $hierarchy = [
                'regions' => null,
                'provinces' => null,
                'citymun' => null,
                'barangays' => null
            ];
            
            // Check each ID to determine what type of location it is and build hierarchy
            foreach ($locationIds as $fieldName => $id) {
                if (empty($id)) continue;
                
                switch ($fieldName) {
                    case 'address_region':
                        // Look up region by region_code
                        $stmt = $conn->prepare("SELECT region_code, region_name FROM regions WHERE region_code = ?");
                        $stmt->execute([$id]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) {
                            $hierarchy['regions'] = [
                                'value' => $result['region_code'],
                                'text' => $result['region_name']
                            ];
                        }
                        break;
                        
                    case 'address_province':
                        // Look up province by ID
                        $stmt = $conn->prepare("SELECT id, province_name, region_code FROM provinces WHERE id = ?");
                        $stmt->execute([$id]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) {
                            $hierarchy['provinces'] = [
                                'value' => $result['id'],
                                'text' => $result['province_name']
                            ];
                            // Also get the region info
                            if (!$hierarchy['regions']) {
                                $stmt2 = $conn->prepare("SELECT region_code, region_name FROM regions WHERE region_code = ?");
                                $stmt2->execute([$result['region_code']]);
                                $region = $stmt2->fetch(PDO::FETCH_ASSOC);
                                if ($region) {
                                    $hierarchy['regions'] = [
                                        'value' => $region['region_code'],
                                        'text' => $region['region_name']
                                    ];
                                }
                            }
                        }
                        break;
                        
                    case 'address_citymun':
                        // Look up city/municipality by ID
                        $stmt = $conn->prepare("
                            SELECT c.id, c.citymun_name, c.province_id, p.province_name, p.region_code, r.region_name
                            FROM citymun c
                            LEFT JOIN provinces p ON c.province_id = p.id
                            LEFT JOIN regions r ON p.region_code = r.region_code
                            WHERE c.id = ?
                        ");
                        $stmt->execute([$id]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) {
                            $hierarchy['citymun'] = [
                                'value' => $result['id'],
                                'text' => $result['citymun_name']
                            ];
                            // Also get province and region info
                            if (!$hierarchy['provinces']) {
                                $hierarchy['provinces'] = [
                                    'value' => $result['province_id'],
                                    'text' => $result['province_name']
                                ];
                            }
                            if (!$hierarchy['regions']) {
                                $hierarchy['regions'] = [
                                    'value' => $result['region_code'],
                                    'text' => $result['region_name']
                                ];
                            }
                        }
                        break;
                        
                    case 'address_barangay':
                        // Look up barangay by ID
                        $stmt = $conn->prepare("
                            SELECT b.id, b.barangay_name, b.citymun_id, c.citymun_name, c.province_id, p.province_name, p.region_code, r.region_name
                            FROM barangays b
                            LEFT JOIN citymun c ON b.citymun_id = c.id
                            LEFT JOIN provinces p ON c.province_id = p.id
                            LEFT JOIN regions r ON p.region_code = r.region_code
                            WHERE b.id = ?
                        ");
                        $stmt->execute([$id]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) {
                            $hierarchy['barangays'] = [
                                'value' => $result['id'],
                                'text' => $result['barangay_name']
                            ];
                            // Also get citymun, province and region info
                            if (!$hierarchy['citymun']) {
                                $hierarchy['citymun'] = [
                                    'value' => $result['citymun_id'],
                                    'text' => $result['citymun_name']
                                ];
                            }
                            if (!$hierarchy['provinces']) {
                                $hierarchy['provinces'] = [
                                    'value' => $result['province_id'],
                                    'text' => $result['province_name']
                                ];
                            }
                            if (!$hierarchy['regions']) {
                                $hierarchy['regions'] = [
                                    'value' => $result['region_code'],
                                    'text' => $result['region_name']
                                ];
                            }
                        }
                        break;
                }
            }
            
            $response['success'] = true;
            $response['hierarchy'] = $hierarchy;
            
        } catch (Exception $e) {
            $response['message'] = 'Error resolving location hierarchy: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'No location IDs provided';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>