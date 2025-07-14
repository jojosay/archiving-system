<?php
require_once 'includes/layout.php';

// Get dynamic statistics
try {
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
    
} catch (Exception $e) {
    $document_types_count = 0;
    $documents_count = 0;
    $users_count = 0;
}

renderPageStart('Dashboard', 'dashboard');
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome to the Civil Registry Archiving System. Overview of system statistics and quick access to key functions.</p>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #F39C12;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #7F8C8D;
        font-size: 1rem;
        font-weight: 500;
    }
    
    .quick-actions {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .quick-actions h3 {
        color: #2C3E50;
        margin-bottom: 1rem;
    }
    
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .action-card {
        padding: 1.5rem;
        border: 2px solid #ECF0F1;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        color: #2C3E50;
        transition: all 0.3s ease;
    }
    
    .action-card:hover {
        border-color: #F39C12;
        background: #FEF9E7;
        transform: translateY(-2px);
    }
    
    .action-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
        color: #F39C12;
    }
</style>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?php echo $documents_count; ?></div>
        <div class="stat-label">Total Documents</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $document_types_count; ?></div>
        <div class="stat-label">Document Types</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $users_count; ?></div>
        <div class="stat-label">System Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">0</div>
        <div class="stat-label">Pending Requests</div>
    </div>
</div>

<div class="quick-actions">
    <h3>Quick Actions</h3>
    <div class="action-grid">
        <a href="?page=document_upload" class="action-card">
            <span class="action-icon">+</span>
            <div>Upload Document</div>
        </a>
        
        <a href="#" class="action-card" onclick="alert('Coming soon!')">
            <span class="action-icon">?</span>
            <div>Search Archive</div>
        </a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="?page=document_types" class="action-card">
                <span class="action-icon">@</span>
                <div>Document Types</div>
            </a>
            
            <a href="?page=user_register" class="action-card">
                <span class="action-icon">+</span>
                <div>Add New User</div>
            </a>
            
            <a href="?page=user_list" class="action-card">
                <span class="action-icon">#</span>
                <div>Manage Users</div>
            </a>
        <?php endif; ?>
        
        <a href="?page=reports" class="action-card">
            <span class="action-icon">%</span>
            <div>Generate Report</div>
        </a>
        
        <a href="#" class="action-card" onclick="alert('Coming soon!')">
            <span class="action-icon">*</span>
            <div>Backup System</div>
        </a>
    </div>
</div>

<?php renderPageEnd(); ?>