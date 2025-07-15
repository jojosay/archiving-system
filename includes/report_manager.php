<?php
/**
 * Report Manager Class
 * Handles generation of various reports for the system
 */

class ReportManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
    }
    
    /**
     * Get document statistics by type
     */
    public function getDocumentStatsByType() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    dt.name as document_type,
                    COUNT(d.id) as document_count,
                    dt.id as type_id
                FROM document_types dt 
                LEFT JOIN documents d ON dt.id = d.document_type_id 
                WHERE dt.is_active = 1 
                GROUP BY dt.id, dt.name 
                ORDER BY document_count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting document stats by type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get document statistics by upload date (monthly)
     */
    public function getDocumentStatsByMonth($year = null) {
        try {
            if (!$year) {
                $year = date('Y');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    MONTH(created_at) as month,
                    MONTHNAME(created_at) as month_name,
                    COUNT(*) as document_count
                FROM documents 
                WHERE YEAR(created_at) = ? 
                GROUP BY MONTH(created_at), MONTHNAME(created_at)
                ORDER BY month
            ");
            $stmt->execute([$year]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting document stats by month: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user activity summary
     */
    public function getUserActivityStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.username,
                    u.role,
                    u.created_at as user_since,
                    u.last_login,
                    COUNT(d.id) as documents_uploaded
                FROM users u 
                LEFT JOIN documents d ON u.id = d.uploaded_by 
                WHERE u.is_active = 1 
                GROUP BY u.id, u.username, u.role, u.created_at, u.last_login
                ORDER BY documents_uploaded DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user activity stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get system overview statistics
     */
    public function getSystemOverview() {
        try {
            $stats = [];
            
            // Total documents
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM documents");
            $stmt->execute();
            $stats['total_documents'] = $stmt->fetchColumn();
            
            // Total document types
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM document_types WHERE is_active = 1");
            $stmt->execute();
            $stats['total_document_types'] = $stmt->fetchColumn();
            
            // Total users
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stmt->execute();
            $stats['total_users'] = $stmt->fetchColumn();
            
            // Documents uploaded this month
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM documents 
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute();
            $stats['documents_this_month'] = $stmt->fetchColumn();
            
            // Most active document type
            $stmt = $this->db->prepare("
                SELECT dt.name, COUNT(d.id) as count
                FROM document_types dt 
                LEFT JOIN documents d ON dt.id = d.document_type_id 
                WHERE dt.is_active = 1 
                GROUP BY dt.id, dt.name 
                ORDER BY count DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['most_active_type'] = $result ? $result['name'] : 'None';
            $stats['most_active_type_count'] = $result ? $result['count'] : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting system overview: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export data to CSV format
     */
    public function exportToCSV($data, $filename, $headers = null) {
        try {
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            if (empty($data)) {
                error_log("CSV Export: No data provided for export");
                // Create a default "no data" CSV
                $data = [['message' => 'No data available for export']];
                $headers = ['Message'];
            }
            
            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($output, "\xEF\xBB\xBF");
            
            // Write headers
            if ($headers) {
                fputcsv($output, $headers);
            } else if (!empty($data[0])) {
                // Use array keys as headers
                fputcsv($output, array_keys($data[0]));
            }
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            return true;
        } catch (Exception $e) {
            error_log("Error exporting to CSV: " . $e->getMessage());
            // If we can't export, show an error page
            echo "Error generating CSV export: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Get document statistics by location (province/region)
     */
    public function getDocumentStatsByLocation() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    dm.field_value as location_data,
                    COUNT(d.id) as document_count,
                    dt.name as document_type
                FROM documents d
                JOIN document_metadata dm ON d.id = dm.document_id
                JOIN document_types dt ON d.document_type_id = dt.id
                WHERE (dm.field_name LIKE '%location%' OR dm.field_name LIKE '%province%')
                AND dm.field_value IS NOT NULL AND dm.field_value != ''
                GROUP BY dm.field_value, dt.name
                ORDER BY document_count DESC
                LIMIT 20
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting document stats by location: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get file size analytics
     */
    public function getFileSizeAnalytics() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    dt.name as document_type,
                    COUNT(d.id) as document_count,
                    AVG(d.file_size) as avg_file_size,
                    MIN(d.file_size) as min_file_size,
                    MAX(d.file_size) as max_file_size,
                    SUM(d.file_size) as total_file_size
                FROM documents d
                JOIN document_types dt ON d.document_type_id = dt.id
                WHERE d.file_size IS NOT NULL AND d.file_size > 0
                GROUP BY dt.id, dt.name
                ORDER BY total_file_size DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting file size analytics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get upload trends by day of week
     */
    public function getUploadTrendsByDayOfWeek() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DAYNAME(created_at) as day_name,
                    DAYOFWEEK(created_at) as day_number,
                    COUNT(*) as upload_count
                FROM documents 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
                ORDER BY day_number
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting upload trends by day: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent activity summary (last 30 days)
     */
    public function getRecentActivitySummary() {
        try {
            $stats = [];
            
            // Documents uploaded in last 30 days
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM documents 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['documents_last_30_days'] = $stmt->fetchColumn();
            
            // New users in last 30 days
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['new_users_last_30_days'] = $stmt->fetchColumn();
            
            // Most active user in last 30 days
            $stmt = $this->db->prepare("
                SELECT u.username, COUNT(d.id) as upload_count
                FROM users u
                LEFT JOIN documents d ON u.id = d.uploaded_by 
                    AND d.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE u.is_active = 1
                GROUP BY u.id, u.username
                ORDER BY upload_count DESC
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['most_active_user'] = $result ? $result['username'] : 'None';
            $stats['most_active_user_uploads'] = $result ? $result['upload_count'] : 0;
            
            // Average documents per day (last 30 days)
            $stats['avg_documents_per_day'] = round($stats['documents_last_30_days'] / 30, 1);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting recent activity summary: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get storage usage by document type
     */
    public function getStorageUsageByType() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    dt.name as document_type,
                    COUNT(d.id) as document_count,
                    COALESCE(SUM(d.file_size), 0) as total_size_bytes,
                    ROUND(COALESCE(SUM(d.file_size), 0) / 1024 / 1024, 2) as total_size_mb
                FROM document_types dt
                LEFT JOIN documents d ON dt.id = d.document_type_id
                WHERE dt.is_active = 1
                GROUP BY dt.id, dt.name
                ORDER BY total_size_bytes DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting storage usage by type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export data to JSON format
     */
    public function exportToJSON($data, $filename) {
        try {
            if (empty($data)) {
                return false;
            }
            
            // Set headers for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo json_encode($data, JSON_PRETTY_PRINT);
            return true;
        } catch (Exception $e) {
            error_log("Error exporting to JSON: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate comprehensive document archive report
     */
    public function generateDocumentArchiveReport() {
        try {
            // First check if we have any documents at all
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM documents");
            $countStmt->execute();
            $documentCount = $countStmt->fetchColumn();
            
            error_log("Document Archive Report: Found $documentCount documents");
            
            if ($documentCount == 0) {
                error_log("Document Archive Report: No documents found, returning empty array");
                return [];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    d.id as document_id,
                    d.title,
                    dt.name as document_type,
                    COALESCE(d.file_name, 'Unknown') as file_name,
                    COALESCE(ROUND(d.file_size / 1024, 2), 0) as file_size_kb,
                    u.username as uploaded_by,
                    d.upload_date,
                    COALESCE(
                        (SELECT dm.field_value 
                         FROM document_metadata dm 
                         WHERE dm.document_id = d.id 
                         AND (dm.field_name LIKE '%location%' OR dm.field_name LIKE '%province%')
                         LIMIT 1), 
                        'Not specified'
                    ) as location
                FROM documents d
                JOIN document_types dt ON d.document_type_id = dt.id
                JOIN users u ON d.uploaded_by = u.id
                WHERE dt.is_active = 1
                ORDER BY d.upload_date DESC
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Document Archive Report: Retrieved " . count($result) . " records");
            return $result;
        } catch (Exception $e) {
            error_log("Error generating document archive report: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Get system audit log entries
     */
    public function getAuditLogEntries($limit = 100, $offset = 0) {
        try {
            // Create audit log entries from various system activities
            $auditEntries = [];
            
            // Document uploads
            $stmt = $this->db->prepare("
                SELECT 
                    'Document Upload' as action_type,
                    CONCAT('Document \"', d.title, '\" uploaded') as description,
                    u.username as performed_by,
                    d.upload_date as action_date,
                    d.id as related_id
                FROM documents d
                JOIN users u ON d.uploaded_by = u.id
                ORDER BY d.upload_date DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $documentEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // User registrations
            $stmt = $this->db->prepare("
                SELECT 
                    'User Registration' as action_type,
                    CONCAT('User \"', username, '\" registered with role: ', role) as description,
                    'System' as performed_by,
                    created_at as action_date,
                    id as related_id
                FROM users
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $userEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Document type changes
            $stmt = $this->db->prepare("
                SELECT 
                    'Document Type Created' as action_type,
                    CONCAT('Document type \"', name, '\" created') as description,
                    'Admin' as performed_by,
                    created_at as action_date,
                    id as related_id
                FROM document_types
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $typeEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine all entries
            $auditEntries = array_merge($documentEntries, $userEntries, $typeEntries);
            
            // Sort by date
            usort($auditEntries, function($a, $b) {
                return strtotime($b['action_date']) - strtotime($a['action_date']);
            });
            
            return array_slice($auditEntries, 0, $limit);
        } catch (Exception $e) {
            error_log("Error getting audit log entries: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate custom report based on criteria
     */
    public function generateCustomReport($criteria) {
        try {
            $whereConditions = [];
            $params = [];
            
            // Build dynamic query based on criteria
            $baseQuery = "
                SELECT 
                    d.id,
                    d.title,
                    dt.name as document_type,
                    d.file_name,
                    d.file_size,
                    u.username as uploaded_by,
                    d.upload_date,
                    d.created_at
                FROM documents d
                JOIN document_types dt ON d.document_type_id = dt.id
                JOIN users u ON d.uploaded_by = u.id
                WHERE 1=1
            ";
            
            // Add document type filter
            if (!empty($criteria['document_type'])) {
                $whereConditions[] = "dt.id = ?";
                $params[] = $criteria['document_type'];
            }
            
            // Add date range filter
            if (!empty($criteria['date_from'])) {
                $whereConditions[] = "DATE(d.upload_date) >= ?";
                $params[] = $criteria['date_from'];
            }
            
            if (!empty($criteria['date_to'])) {
                $whereConditions[] = "DATE(d.upload_date) <= ?";
                $params[] = $criteria['date_to'];
            }
            
            // Add user filter
            if (!empty($criteria['uploaded_by'])) {
                $whereConditions[] = "u.id = ?";
                $params[] = $criteria['uploaded_by'];
            }
            
            // Add file size filter
            if (!empty($criteria['min_file_size'])) {
                $whereConditions[] = "d.file_size >= ?";
                $params[] = $criteria['min_file_size'] * 1024; // Convert KB to bytes
            }
            
            if (!empty($criteria['max_file_size'])) {
                $whereConditions[] = "d.file_size <= ?";
                $params[] = $criteria['max_file_size'] * 1024; // Convert KB to bytes
            }
            
            // Combine conditions
            if (!empty($whereConditions)) {
                $baseQuery .= " AND " . implode(" AND ", $whereConditions);
            }
            
            $baseQuery .= " ORDER BY d.upload_date DESC";
            
            // Add limit if specified
            if (!empty($criteria['limit'])) {
                $baseQuery .= " LIMIT " . intval($criteria['limit']);
            }
            
            $stmt = $this->db->prepare($baseQuery);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error generating custom report: " . $e->getMessage());
            return [];
        }
    }
}
?>