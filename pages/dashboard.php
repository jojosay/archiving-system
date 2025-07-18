<?php
require_once 'includes/layout.php';

// Get dynamic statistics
try {
    // Check if database is available
    if (!$database || !$database->getConnection()) {
        throw new Exception("Database not available");
    }
    
    $conn = $database->getConnection();
    
    // Count document types
    $stmt = $conn->prepare("SELECT COUNT(*) FROM document_types WHERE is_active = 1");
    $stmt->execute();
    $document_types_count = $stmt->fetchColumn();
    
    // Count total documents (will be 0 for now)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM documents");
    $stmt->execute();
    $documents_count = $stmt->fetchColumn();
    
    // Count users
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE is_active = 1");
    $stmt->execute();
    $users_count = $stmt->fetchColumn();
    
    // Count recent documents (last 30 days)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM documents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $recent_documents = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $document_types_count = 0;
    $documents_count = 0;
    $users_count = 0;
    $recent_documents = 0;
}

renderPageStart('Dashboard', 'dashboard');
?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome to the Civil Registry Archiving System. Overview of system statistics and quick access to key functions.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($documents_count); ?></div>
        <div class="stat-label">Total Documents</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($document_types_count); ?></div>
        <div class="stat-label">Document Types</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($users_count); ?></div>
        <div class="stat-label">System Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($recent_documents); ?></div>
        <div class="stat-label">Recent Documents</div>
    </div>
</div>

<div class="quick-actions">
    <h3>Quick Actions</h3>
    <div class="action-grid">
        <a href="?page=document_upload" class="action-card">
            <span class="action-icon">📄</span>
            <div class="action-title">Upload Document</div>
            <div class="action-subtitle">Add new documents</div>
        </a>
        
        <a href="?page=enhanced_search" class="action-card">
            <span class="action-icon">🔍</span>
            <div class="action-title">Search Archive</div>
            <div class="action-subtitle">Find documents quickly</div>
        </a>
        
        <a href="?page=document_archive" class="action-card">
            <span class="action-icon">📚</span>
            <div class="action-title">Browse Archive</div>
            <div class="action-subtitle">View all documents</div>
        </a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="?page=document_types" class="action-card">
                <span class="action-icon">⚙️</span>
                <div class="action-title">Document Types</div>
                <div class="action-subtitle">Manage document categories</div>
            </a>
            
            <a href="?page=user_register" class="action-card">
                <span class="action-icon">👤</span>
                <div class="action-title">Add New User</div>
                <div class="action-subtitle">Create user accounts</div>
            </a>
            
            <a href="?page=user_list" class="action-card">
                <span class="action-icon">👥</span>
                <div class="action-title">Manage Users</div>
                <div class="action-subtitle">View and edit users</div>
            </a>
            
            <a href="?page=backup_management" class="action-card">
                <span class="action-icon">💾</span>
                <div class="action-title">Backup System</div>
                <div class="action-subtitle">Backup and restore data</div>
            </a>
            
            <a href="?page=branding_management" class="action-card">
                <span class="action-icon">🎨</span>
                <div class="action-title">Branding</div>
                <div class="action-subtitle">Customize appearance</div>
            </a>
            
            <a href="?page=pdf_template_manager" class="action-card">
                <span class="action-icon">📋</span>
                <div class="action-title">PDF Template Manager</div>
                <div class="action-subtitle">Manage PDF templates</div>
            </a>
        <?php endif; ?>
        
        <a href="?page=reports" class="action-card">
            <span class="action-icon">📊</span>
            <div class="action-title">Generate Report</div>
            <div class="action-subtitle">View system statistics</div>
        </a>
    </div>
</div>

<?php renderPageEnd(); ?>