<?php
require_once 'includes/layout.php';
require_once 'includes/report_manager.php';

$database = new Database();
$reportManager = new ReportManager($database);

$message = '';
$message_type = '';

// Handle special report actions
if ($_GET['action'] ?? false) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'document_archive_report':
            // Add debug parameter to see what's happening
            if (isset($_GET['debug'])) {
                echo "<h2>Debug: Document Archive Report</h2>";
                
                try {
                    $archiveData = $reportManager->generateDocumentArchiveReport();
                    echo "<p>Data count: " . count($archiveData) . "</p>";
                    
                    if (!empty($archiveData)) {
                        echo "<h3>Sample data (first 3 records):</h3>";
                        echo "<pre>" . print_r(array_slice($archiveData, 0, 3), true) . "</pre>";
                    } else {
                        echo "<p>No data returned from generateDocumentArchiveReport()</p>";
                        
                        // Check if we have documents at all
                        $conn = $database->getConnection();
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM documents");
                        $stmt->execute();
                        $count = $stmt->fetchColumn();
                        echo "<p>Total documents in database: $count</p>";
                        
                        if ($count > 0) {
                            $stmt = $conn->prepare("SELECT d.id, d.title, dt.name as type, u.username FROM documents d JOIN document_types dt ON d.document_type_id = dt.id JOIN users u ON d.uploaded_by = u.id LIMIT 3");
                            $stmt->execute();
                            $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            echo "<h3>Sample documents:</h3>";
                            echo "<pre>" . print_r($sample, true) . "</pre>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<p>Error: " . $e->getMessage() . "</p>";
                    echo "<pre>" . $e->getTraceAsString() . "</pre>";
                }
                
                echo '<p><a href="?page=reports&action=document_archive_report">Try Export</a> | <a href="?page=reports">Back to Reports</a></p>';
                exit;
            }
            
            // Generate comprehensive document archive report
            $archiveData = $reportManager->generateDocumentArchiveReport();
            
            // Debug: Check if data exists
            if (empty($archiveData)) {
                // If no data, create a fallback with message
                $archiveData = [
                    [
                        'document_id' => 'No Data',
                        'title' => 'No documents found in the system',
                        'document_type' => 'N/A',
                        'file_name' => 'N/A',
                        'file_size_kb' => '0',
                        'uploaded_by' => 'N/A',
                        'upload_date' => date('Y-m-d'),
                        'location' => 'N/A'
                    ]
                ];
            }
            
            $filename = 'document_archive_report_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = ['Document ID', 'Title', 'Type', 'File Name', 'File Size (KB)', 'Uploaded By', 'Upload Date', 'Location'];
            $reportManager->exportToCSV($archiveData, $filename, $headers);
            exit;
            
        case 'audit_log':
            // Redirect to audit log view
            header('Location: ?page=reports&view=audit_log');
            exit;
            
        case 'custom_report_builder':
            // Redirect to custom report builder
            header('Location: ?page=reports&view=custom_builder');
            exit;
    }
}

// Handle export requests
if ($_GET['export'] ?? false) {
    $export_type = $_GET['export'];
    $format = $_GET['format'] ?? 'csv';
    
    switch ($export_type) {
        case 'document_stats':
            $data = $reportManager->getDocumentStatsByType();
            $filename = 'document_statistics_' . date('Y-m-d') . '.' . $format;
            $headers = ['Document Type', 'Document Count', 'Type ID'];
            if ($format === 'json') {
                $reportManager->exportToJSON($data, $filename);
            } else {
                $reportManager->exportToCSV($data, $filename, $headers);
            }
            exit;
            
        case 'user_activity':
            $data = $reportManager->getUserActivityStats();
            $filename = 'user_activity_' . date('Y-m-d') . '.' . $format;
            $headers = ['Username', 'Role', 'User Since', 'Last Login', 'Documents Uploaded'];
            if ($format === 'json') {
                $reportManager->exportToJSON($data, $filename);
            } else {
                $reportManager->exportToCSV($data, $filename, $headers);
            }
            exit;
            
        case 'monthly_stats':
            $year = $_GET['year'] ?? date('Y');
            $data = $reportManager->getDocumentStatsByMonth($year);
            $filename = 'monthly_statistics_' . $year . '.' . $format;
            $headers = ['Month Number', 'Month Name', 'Document Count'];
            if ($format === 'json') {
                $reportManager->exportToJSON($data, $filename);
            } else {
                $reportManager->exportToCSV($data, $filename, $headers);
            }
            exit;
            
        case 'location_stats':
            $data = $reportManager->getDocumentStatsByLocation();
            $filename = 'location_statistics_' . date('Y-m-d') . '.' . $format;
            $headers = ['Location Data', 'Document Count', 'Document Type'];
            if ($format === 'json') {
                $reportManager->exportToJSON($data, $filename);
            } else {
                $reportManager->exportToCSV($data, $filename, $headers);
            }
            exit;
            
        case 'file_size_analytics':
            $data = $reportManager->getFileSizeAnalytics();
            $filename = 'file_size_analytics_' . date('Y-m-d') . '.' . $format;
            $headers = ['Document Type', 'Document Count', 'Avg File Size', 'Min File Size', 'Max File Size', 'Total File Size'];
            if ($format === 'json') {
                $reportManager->exportToJSON($data, $filename);
            } else {
                $reportManager->exportToCSV($data, $filename, $headers);
            }
            exit;
            
        case 'storage_usage':
            $data = $reportManager->getStorageUsageByType();
            $filename = 'storage_usage_' . date('Y-m-d') . '.' . $format;
            $headers = ['Document Type', 'Document Count', 'Total Size (Bytes)', 'Total Size (MB)'];
            if ($format === 'json') {
                $reportManager->exportToJSON($data, $filename);
            } else {
                $reportManager->exportToCSV($data, $filename, $headers);
            }
            exit;
            
        case 'day_trends':
            $data = $reportManager->getUploadTrendsByDayOfWeek();
            $filename = 'day_trends_' . date('Y-m-d') . '.' . $format;
            $headers = ['Day Name', 'Day Number', 'Upload Count'];
            if ($format === 'json') {
                $reportManager->exportToJSON($data, $filename);
            } else {
                $reportManager->exportToCSV($data, $filename, $headers);
            }
            exit;
    }
}

// Get report data
$documentStats = $reportManager->getDocumentStatsByType();
$monthlyStats = $reportManager->getDocumentStatsByMonth();
$userActivity = $reportManager->getUserActivityStats();
$systemOverview = $reportManager->getSystemOverview();

// Get enhanced report data
$locationStats = $reportManager->getDocumentStatsByLocation();
$fileSizeAnalytics = $reportManager->getFileSizeAnalytics();
$dayTrends = $reportManager->getUploadTrendsByDayOfWeek();
$recentActivity = $reportManager->getRecentActivitySummary();
$storageUsage = $reportManager->getStorageUsageByType();

// Handle special views
$currentView = $_GET['view'] ?? 'default';
$auditLogEntries = [];
$customReportData = [];

if ($currentView === 'audit_log') {
    $auditLogEntries = $reportManager->getAuditLogEntries(50, 0);
} elseif ($currentView === 'custom_builder') {
    // Handle custom report generation
    if ($_POST['generate_custom_report'] ?? false) {
        $criteria = [
            'document_type' => $_POST['document_type'] ?? '',
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'uploaded_by' => $_POST['uploaded_by'] ?? '',
            'min_file_size' => $_POST['min_file_size'] ?? '',
            'max_file_size' => $_POST['max_file_size'] ?? '',
            'limit' => $_POST['limit'] ?? 100
        ];
        $customReportData = $reportManager->generateCustomReport($criteria);
        
        // If export is requested
        if ($_POST['export_custom'] ?? false) {
            $format = $_POST['export_format'] ?? 'csv';
            $filename = 'custom_report_' . date('Y-m-d_H-i-s') . '.' . $format;
            $headers = ['ID', 'Title', 'Document Type', 'File Name', 'File Size', 'Uploaded By', 'Upload Date', 'Created At'];
            
            if ($format === 'json') {
                $reportManager->exportToJSON($customReportData, $filename);
            } else {
                $reportManager->exportToCSV($customReportData, $filename, $headers);
            }
            exit;
        }
    }
}

renderPageStart('Reports', 'reports');
?>

<style>
.report-section {
    background: white;
    margin-bottom: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.report-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.report-content {
    padding: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.report-table th,
.report-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.report-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #4a5568;
}

.export-btn {
    background: #48bb78;
    color: white;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.2s;
    margin-left: 0.5rem;
}

.export-btn:hover {
    background: #38a169;
}

.export-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.export-buttons .export-btn {
    margin-left: 0;
}

.export-buttons .export-btn:first-child {
    background: #48bb78;
}

.export-buttons .export-btn:last-child {
    background: #ed8936;
}

.export-buttons .export-btn:last-child:hover {
    background: #dd6b20;
}

.chart-container {
    height: 300px;
    margin: 1rem 0;
}
</style>

<div class="page-header">
    <h1>Reports & Analytics</h1>
    <p>Generate insights and reports on system usage and document data</p>
</div>

<!-- System Overview -->
<div class="report-section">
    <div class="report-header">
        <h2>System Overview</h2>
        <span><?php echo date('F j, Y'); ?></span>
    </div>
    <div class="report-content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $systemOverview['total_documents'] ?? 0; ?></div>
                <div class="stat-label">Total Documents</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $systemOverview['total_document_types'] ?? 0; ?></div>
                <div class="stat-label">Document Types</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $systemOverview['total_users'] ?? 0; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $systemOverview['documents_this_month'] ?? 0; ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        
        <?php if ($systemOverview['most_active_type'] ?? false): ?>
        <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-semibold text-blue-800">Most Active Document Type</h4>
            <p class="text-blue-700">
                <strong><?php echo htmlspecialchars($systemOverview['most_active_type']); ?></strong> 
                with <?php echo $systemOverview['most_active_type_count']; ?> documents
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Document Statistics by Type -->
<div class="report-section">
    <div class="report-header">
        <h2>Document Statistics by Type</h2>
        <a href="?page=reports&export=document_stats&format=csv" class="export-btn">Export CSV</a>
    </div>
    <div class="report-content">
        <?php if (!empty($documentStats)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Document Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = array_sum(array_column($documentStats, 'document_count'));
                    foreach ($documentStats as $stat): 
                        $percentage = $total > 0 ? round(($stat['document_count'] / $total) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['document_type']); ?></td>
                            <td><?php echo $stat['document_count']; ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No document statistics available yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly Document Statistics -->
<div class="report-section">
    <div class="report-header">
        <h2>Monthly Document Statistics (<?php echo date('Y'); ?>)</h2>
        <a href="?page=reports&export=monthly_stats&format=csv&year=<?php echo date('Y'); ?>" class="export-btn">Export CSV</a>
    </div>
    <div class="report-content">
        <?php if (!empty($monthlyStats)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Documents Uploaded</th>
                        <th>Growth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $previousCount = 0;
                    foreach ($monthlyStats as $stat): 
                        $growth = $previousCount > 0 ? round((($stat['document_count'] - $previousCount) / $previousCount) * 100, 1) : 0;
                        $growthClass = $growth > 0 ? 'text-green-600' : ($growth < 0 ? 'text-red-600' : 'text-gray-600');
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['month_name']); ?></td>
                            <td><?php echo $stat['document_count']; ?></td>
                            <td class="<?php echo $growthClass; ?>">
                                <?php echo $growth > 0 ? '+' : ''; ?><?php echo $growth; ?>%
                            </td>
                        </tr>
                    <?php 
                        $previousCount = $stat['document_count'];
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No monthly statistics available for <?php echo date('Y'); ?>.</p>
        <?php endif; ?>
    </div>
</div>

<!-- User Activity Report -->
<div class="report-section">
    <div class="report-header">
        <h2>User Activity Report</h2>
        <a href="?page=reports&export=user_activity&format=csv" class="export-btn">Export CSV</a>
    </div>
    <div class="report-content">
        <?php if (!empty($userActivity)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>User Since</th>
                        <th>Last Login</th>
                        <th>Documents Uploaded</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userActivity as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['user_since'])); ?></td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                                <?php else: ?>
                                    <span class="text-gray-500">Never</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['documents_uploaded']; ?></td>
                            <td>
                                <?php 
                                $lastLoginDays = $user['last_login'] ? 
                                    floor((time() - strtotime($user['last_login'])) / (60 * 60 * 24)) : 999;
                                if ($lastLoginDays <= 7): 
                                ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                <?php elseif ($lastLoginDays <= 30): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Inactive</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Dormant</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No user activity data available.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Activity Summary -->
<div class="report-section">
    <div class="report-header">
        <h2>Recent Activity Summary (Last 30 Days)</h2>
    </div>
    <div class="report-content">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $recentActivity['documents_last_30_days'] ?? 0; ?></div>
                <div class="stat-label">Documents Uploaded</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $recentActivity['new_users_last_30_days'] ?? 0; ?></div>
                <div class="stat-label">New Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $recentActivity['avg_documents_per_day'] ?? 0; ?></div>
                <div class="stat-label">Avg. Docs/Day</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $recentActivity['most_active_user_uploads'] ?? 0; ?></div>
                <div class="stat-label">Top User Uploads</div>
            </div>
        </div>
        
        <?php if ($recentActivity['most_active_user'] ?? false): ?>
        <div class="bg-green-50 p-4 rounded-lg mt-4">
            <h4 class="font-semibold text-green-800">Most Active User (Last 30 Days)</h4>
            <p class="text-green-700">
                <strong><?php echo htmlspecialchars($recentActivity['most_active_user']); ?></strong> 
                with <?php echo $recentActivity['most_active_user_uploads']; ?> uploads
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Storage Usage Analytics -->
<div class="report-section">
    <div class="report-header">
        <h2>Storage Usage by Document Type</h2>
        <div class="export-buttons">
            <a href="?page=reports&export=storage_usage&format=csv" class="export-btn">Export CSV</a>
            <a href="?page=reports&export=storage_usage&format=json" class="export-btn">Export JSON</a>
        </div>
    </div>
    <div class="report-content">
        <?php if (!empty($storageUsage)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Document Count</th>
                        <th>Total Size (MB)</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalSize = array_sum(array_column($storageUsage, 'total_size_mb'));
                    foreach ($storageUsage as $usage): 
                        $percentage = $totalSize > 0 ? round(($usage['total_size_mb'] / $totalSize) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usage['document_type']); ?></td>
                            <td><?php echo $usage['document_count']; ?></td>
                            <td><?php echo number_format($usage['total_size_mb'], 2); ?> MB</td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No storage usage data available.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Trends by Day of Week -->
<div class="report-section">
    <div class="report-header">
        <h2>Upload Trends by Day of Week (Last 3 Months)</h2>
        <div class="export-buttons">
            <a href="?page=reports&export=day_trends&format=csv" class="export-btn">Export CSV</a>
            <a href="?page=reports&export=day_trends&format=json" class="export-btn">Export JSON</a>
        </div>
    </div>
    <div class="report-content">
        <?php if (!empty($dayTrends)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Day of Week</th>
                        <th>Upload Count</th>
                        <th>Activity Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $maxUploads = max(array_column($dayTrends, 'upload_count'));
                    foreach ($dayTrends as $trend): 
                        $activityLevel = $maxUploads > 0 ? round(($trend['upload_count'] / $maxUploads) * 100) : 0;
                        $levelClass = $activityLevel >= 75 ? 'bg-green-100 text-green-800' : 
                                     ($activityLevel >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trend['day_name']); ?></td>
                            <td><?php echo $trend['upload_count']; ?></td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $levelClass; ?>">
                                    <?php echo $activityLevel; ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No upload trend data available for the last 3 months.</p>
        <?php endif; ?>
    </div>
</div>

<!-- File Size Analytics -->
<div class="report-section">
    <div class="report-header">
        <h2>File Size Analytics</h2>
        <div class="export-buttons">
            <a href="?page=reports&export=file_size_analytics&format=csv" class="export-btn">Export CSV</a>
            <a href="?page=reports&export=file_size_analytics&format=json" class="export-btn">Export JSON</a>
        </div>
    </div>
    <div class="report-content">
        <?php if (!empty($fileSizeAnalytics)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Document Count</th>
                        <th>Avg Size (KB)</th>
                        <th>Min Size (KB)</th>
                        <th>Max Size (KB)</th>
                        <th>Total Size (MB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fileSizeAnalytics as $analytics): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($analytics['document_type']); ?></td>
                            <td><?php echo $analytics['document_count']; ?></td>
                            <td><?php echo number_format($analytics['avg_file_size'] / 1024, 1); ?></td>
                            <td><?php echo number_format($analytics['min_file_size'] / 1024, 1); ?></td>
                            <td><?php echo number_format($analytics['max_file_size'] / 1024, 1); ?></td>
                            <td><?php echo number_format($analytics['total_file_size'] / 1024 / 1024, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No file size analytics available.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Document Statistics by Location -->
<?php if (!empty($locationStats)): ?>
<div class="report-section">
    <div class="report-header">
        <h2>Document Statistics by Location</h2>
        <div class="export-buttons">
            <a href="?page=reports&export=location_stats&format=csv" class="export-btn">Export CSV</a>
            <a href="?page=reports&export=location_stats&format=json" class="export-btn">Export JSON</a>
        </div>
    </div>
    <div class="report-content">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Document Type</th>
                    <th>Document Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locationStats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['location_data']); ?></td>
                        <td><?php echo htmlspecialchars($stat['document_type']); ?></td>
                        <td><?php echo $stat['document_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Quick Report Actions -->
<div class="report-section">
    <div class="report-header">
        <h2>Quick Report Actions</h2>
    </div>
    <div class="report-content">
        <div class="grid md:grid-cols-3 gap-4">
            <div class="p-4 border border-gray-200 rounded-lg text-center">
                <h4 class="font-semibold mb-2">Document Archive Report</h4>
                <p class="text-sm text-gray-600 mb-3">Complete overview of all archived documents</p>
                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    <a href="?page=reports&action=document_archive_report" class="export-btn">Generate Report</a>
                    <a href="?page=reports&action=document_archive_report&debug=1" class="export-btn" style="background: #ed8936;">Debug</a>
                </div>
            </div>
            
            <div class="p-4 border border-gray-200 rounded-lg text-center">
                <h4 class="font-semibold mb-2">System Audit Log</h4>
                <p class="text-sm text-gray-600 mb-3">Track all system activities and changes</p>
                <a href="?page=reports&view=audit_log" class="export-btn">View Audit Log</a>
            </div>
            
            <div class="p-4 border border-gray-200 rounded-lg text-center">
                <h4 class="font-semibold mb-2">Custom Report Builder</h4>
                <p class="text-sm text-gray-600 mb-3">Create custom reports with specific criteria</p>
                <a href="?page=reports&view=custom_builder" class="export-btn">Build Report</a>
            </div>
        </div>
    </div>
</div>

<?php if ($currentView === 'audit_log'): ?>
<!-- System Audit Log View -->
<div class="report-section">
    <div class="report-header">
        <h2>System Audit Log</h2>
        <a href="?page=reports" class="export-btn">Back to Reports</a>
    </div>
    <div class="report-content">
        <?php if (!empty($auditLogEntries)): ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Action Type</th>
                        <th>Description</th>
                        <th>Performed By</th>
                        <th>Date & Time</th>
                        <th>Related ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLogEntries as $entry): ?>
                        <tr>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $entry['action_type'] === 'Document Upload' ? 'bg-blue-100 text-blue-800' : 
                                              ($entry['action_type'] === 'User Registration' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'); ?>">
                                    <?php echo htmlspecialchars($entry['action_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                            <td><?php echo htmlspecialchars($entry['performed_by']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($entry['action_date'])); ?></td>
                            <td><?php echo $entry['related_id']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">No audit log entries available.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($currentView === 'custom_builder'): ?>
<!-- Custom Report Builder View -->
<div class="report-section">
    <div class="report-header">
        <h2>Custom Report Builder</h2>
        <a href="?page=reports" class="export-btn">Back to Reports</a>
    </div>
    <div class="report-content">
        <form method="POST" action="?page=reports&view=custom_builder">
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label for="document_type">Document Type</label>
                    <select id="document_type" name="document_type" class="form-control">
                        <option value="">All Types</option>
                        <?php
                        try {
                            $conn = $database->getConnection();
                            $stmt = $conn->prepare("SELECT id, name FROM document_types WHERE is_active = 1 ORDER BY name");
                            $stmt->execute();
                            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" 
                                    <?php echo ($_POST['document_type'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach;
                        } catch (Exception $e) {
                            // Handle error silently
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="uploaded_by">Uploaded By</label>
                    <select id="uploaded_by" name="uploaded_by" class="form-control">
                        <option value="">All Users</option>
                        <?php
                        try {
                            $stmt = $conn->prepare("SELECT id, username FROM users WHERE is_active = 1 ORDER BY username");
                            $stmt->execute();
                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"
                                    <?php echo ($_POST['uploaded_by'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach;
                        } catch (Exception $e) {
                            // Handle error silently
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['date_from'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['date_to'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="min_file_size">Min File Size (KB)</label>
                    <input type="number" id="min_file_size" name="min_file_size" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['min_file_size'] ?? ''); ?>" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="max_file_size">Max File Size (KB)</label>
                    <input type="number" id="max_file_size" name="max_file_size" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['max_file_size'] ?? ''); ?>" placeholder="No limit">
                </div>
                
                <div class="form-group">
                    <label for="limit">Result Limit</label>
                    <select id="limit" name="limit" class="form-control">
                        <option value="50" <?php echo ($_POST['limit'] ?? '100') == '50' ? 'selected' : ''; ?>>50 results</option>
                        <option value="100" <?php echo ($_POST['limit'] ?? '100') == '100' ? 'selected' : ''; ?>>100 results</option>
                        <option value="250" <?php echo ($_POST['limit'] ?? '100') == '250' ? 'selected' : ''; ?>>250 results</option>
                        <option value="500" <?php echo ($_POST['limit'] ?? '100') == '500' ? 'selected' : ''; ?>>500 results</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="export_format">Export Format</label>
                    <select id="export_format" name="export_format" class="form-control">
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" name="generate_custom_report" value="1" class="export-btn">Generate Report</button>
                <button type="submit" name="export_custom" value="1" class="export-btn" style="background: #ed8936;">Export Report</button>
            </div>
        </form>
        
        <?php if (!empty($customReportData)): ?>
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Custom Report Results (<?php echo count($customReportData); ?> records)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Document Type</th>
                        <th>File Name</th>
                        <th>File Size (KB)</th>
                        <th>Uploaded By</th>
                        <th>Upload Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customReportData as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                            <td><?php echo number_format($row['file_size'] / 1024, 2); ?></td>
                            <td><?php echo htmlspecialchars($row['uploaded_by']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['upload_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.flex {
    display: flex;
}

.gap-2 {
    gap: 0.5rem;
}
</style>

<?php renderPageEnd(); ?>