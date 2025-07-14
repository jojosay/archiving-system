<?php
require_once 'includes/layout.php';
require_once 'includes/report_manager.php';

$database = new Database();
$reportManager = new ReportManager($database);

$message = '';
$message_type = '';

// Handle export requests
if ($_GET['export'] ?? false) {
    $export_type = $_GET['export'];
    $format = $_GET['format'] ?? 'csv';
    
    switch ($export_type) {
        case 'document_stats':
            $data = $reportManager->getDocumentStatsByType();
            $filename = 'document_statistics_' . date('Y-m-d') . '.csv';
            $headers = ['Document Type', 'Document Count', 'Type ID'];
            $reportManager->exportToCSV($data, $filename, $headers);
            exit;
            
        case 'user_activity':
            $data = $reportManager->getUserActivityStats();
            $filename = 'user_activity_' . date('Y-m-d') . '.csv';
            $headers = ['Username', 'Role', 'User Since', 'Last Login', 'Documents Uploaded'];
            $reportManager->exportToCSV($data, $filename, $headers);
            exit;
            
        case 'monthly_stats':
            $year = $_GET['year'] ?? date('Y');
            $data = $reportManager->getDocumentStatsByMonth($year);
            $filename = 'monthly_statistics_' . $year . '.csv';
            $headers = ['Month Number', 'Month Name', 'Document Count'];
            $reportManager->exportToCSV($data, $filename, $headers);
            exit;
    }
}

// Get report data
$documentStats = $reportManager->getDocumentStatsByType();
$monthlyStats = $reportManager->getDocumentStatsByMonth();
$userActivity = $reportManager->getUserActivityStats();
$systemOverview = $reportManager->getSystemOverview();

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
}

.export-btn:hover {
    background: #38a169;
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
                <a href="#" onclick="alert('Feature coming soon!')" class="export-btn">Generate Report</a>
            </div>
            
            <div class="p-4 border border-gray-200 rounded-lg text-center">
                <h4 class="font-semibold mb-2">System Audit Log</h4>
                <p class="text-sm text-gray-600 mb-3">Track all system activities and changes</p>
                <a href="#" onclick="alert('Feature coming soon!')" class="export-btn">View Audit Log</a>
            </div>
            
            <div class="p-4 border border-gray-200 rounded-lg text-center">
                <h4 class="font-semibold mb-2">Custom Report Builder</h4>
                <p class="text-sm text-gray-600 mb-3">Create custom reports with specific criteria</p>
                <a href="#" onclick="alert('Feature coming soon!')" class="export-btn">Build Report</a>
            </div>
        </div>
    </div>
</div>

<?php renderPageEnd(); ?>