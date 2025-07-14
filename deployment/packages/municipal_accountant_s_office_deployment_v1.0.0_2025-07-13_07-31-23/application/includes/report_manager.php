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
            if (empty($data)) {
                return false;
            }
            
            // Set headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // Write headers
            if ($headers) {
                fputcsv($output, $headers);
            } else {
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
            return false;
        }
    }
}
?>