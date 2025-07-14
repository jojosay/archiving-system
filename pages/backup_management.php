<?php
require_once 'includes/layout.php';
require_once 'includes/backup_manager.php';

$database = new Database();
$backupManager = new BackupManager($database);

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_database_backup':
            $result = $backupManager->exportDatabase();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'create_files_backup':
            $result = $backupManager->exportFiles();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'create_complete_backup':
            $result = $backupManager->createCompleteBackup();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'restore_database':
            $filename = $_POST['filename'] ?? '';
            if ($filename) {
                $result = $backupManager->restoreDatabase($filename);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'restore_files':
            $filename = $_POST['filename'] ?? '';
            if ($filename) {
                $result = $backupManager->restoreFiles($filename);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'delete_backup':
            $filename = $_POST['filename'] ?? '';
            if ($filename) {
                $result = $backupManager->deleteBackup($filename);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'guided_restore':
            $backup_pair_index = $_POST['backup_pair'] ?? '';
            $restore_order = $_POST['restore_order'] ?? 'database_first';
            
            if ($backup_pair_index !== '') {
                $backup_pairs = $backupManager->getCompatibleBackupPairs();
                if (isset($backup_pairs[$backup_pair_index])) {
                    $pair = $backup_pairs[$backup_pair_index];
                    $result = $backupManager->guidedRestore(
                        $pair['database']['filename'], 
                        $pair['files']['filename'], 
                        $restore_order
                    );
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'error';
                }
            }
            break;
    }
}

// Get available backups
$backups = $backupManager->getAvailableBackups();

// Get compatible backup pairs for guided restore
$backup_pairs = $backupManager->getCompatibleBackupPairs();

// Debug: Check if backups are being detected
if (isset($_GET['debug'])) {
    echo "<pre>Debug Info:\n";
    echo "Backup directory: " . $backupManager->getBackupDirectory() . "\n";
    echo "Backups found: " . count($backups) . "\n";
    foreach ($backups as $backup) {
        echo "- " . $backup['filename'] . " (" . $backup['type'] . ", " . $backup['size'] . " bytes)\n";
    }
    echo "</pre>";
}

renderPageStart('Backup Management', 'backup_management');
?>

<style>
/* Enhanced Backup Management Styles */
.backup-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
}

.backup-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
}

.backup-card.database {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.backup-card.files {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);
}

.backup-card.complete {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
}

.backup-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.backup-card:hover::before {
    left: 100%;
}

.backup-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.backup-card:hover::after {
    opacity: 1;
}

.backup-icon {
    font-size: 3.5rem;
    margin-bottom: 1.5rem;
    display: block;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #4facfe);
    border-radius: 16px 16px 0 0;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.backup-item {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.backup-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

.backup-item:hover {
    transform: translateX(4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #cbd5e1;
}

.backup-item:hover::before {
    width: 8px;
}

.backup-type-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.backup-type-icon.database {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.backup-type-icon.files {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

.backup-type-icon.complete {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
}

.action-button {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.action-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.action-button:hover::before {
    left: 100%;
}

.action-button.restore-db {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.action-button.restore-files {
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.action-button.restore-complete {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.action-button.delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.section-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
    position: relative;
}

.section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.section-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: shimmer 4s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}

.section-content {
    padding: 2rem;
}

.info-box {
    border-radius: 16px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
}

.info-box.tips {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 1px solid #93c5fd;
}

.info-box.warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #f59e0b;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 0.8; }
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
    position: relative;
}

.page-header-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    animation: float 3s ease-in-out infinite;
}

.page-title {
    font-size: 3.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #1f2937 0%, #4b5563 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
}

.page-subtitle {
    font-size: 1.25rem;
    color: #64748b;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .backup-card {
        padding: 1.5rem;
    }
    
    .page-title {
        font-size: 2.5rem;
    }
    
    .action-button {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .backup-item {
        padding: 1rem;
    }
    
    .section-content {
        padding: 1rem;
    }
}
</style>

<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Header -->
            <div class="page-header">
                <div class="page-header-icon">
                    <span>&#128190;</span>
                </div>
                <h1 class="page-title">Backup Management</h1>
                <p class="page-subtitle">Protect your data with comprehensive backup and restore solutions</p>
            </div>

            <!-- Message Display -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
                    <div class="flex items-center">
                        <span class="mr-2"><?php echo $message_type === 'success' ? '✓' : '✗'; ?></span>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <?php if (!empty($backups)): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($backups); ?></div>
                        <div class="stat-label">Total Backups</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($backups, function($b) { return $b['type'] === 'database'; })); ?></div>
                        <div class="stat-label">Database Backups</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($backups, function($b) { return $b['type'] === 'files'; })); ?></div>
                        <div class="stat-label">File Backups</div>
                    </div>
                    <div class="stat-card">
                        <?php 
                        $total_size = array_sum(array_column($backups, 'size'));
                        echo '<div class="stat-number">' . number_format($total_size / 1024 / 1024, 1) . '</div>';
                        ?>
                        <div class="stat-label">Total Size (MB)</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Create Backup Section -->
            <div class="section-card mb-8">
                <div class="section-header">
                    <div class="flex items-center">
                        <span class="text-4xl mr-4">&#128190;</span>
                        <div>
                            <h2 class="text-3xl font-bold">Create Backup</h2>
                            <p class="text-blue-100 mt-2 text-lg">Secure your data with professional backup solutions</p>
                        </div>
                    </div>
                </div>
                <div class="section-content">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <form method="POST" class="w-full">
                        <input type="hidden" name="action" value="create_database_backup">
                        <button type="submit" class="backup-card database w-full">
                            <span class="backup-icon">&#128209;</span>
                            <h3 class="text-xl font-bold mb-2">Database Backup</h3>
                            <p class="text-sm opacity-90">Export all database tables and data</p>
                            <div class="mt-4 text-xs opacity-75">
                                Includes: Users, Documents, Metadata, Settings
                            </div>
                        </button>
                    </form>
                    
                    <form method="POST" class="w-full">
                        <input type="hidden" name="action" value="create_files_backup">
                        <button type="submit" class="backup-card files w-full">
                            <span class="backup-icon">&#128193;</span>
                            <h3 class="text-xl font-bold mb-2">Files Backup</h3>
                            <p class="text-sm opacity-90">Archive all uploaded documents and images</p>
                            <div class="mt-4 text-xs opacity-75">
                                Includes: Documents, Book Images, Attachments
                            </div>
                        </button>
                    </form>
                    
                    <form method="POST" class="w-full">
                        <input type="hidden" name="action" value="create_complete_backup">
                        <button type="submit" class="backup-card complete w-full">
                            <span class="backup-icon">&#127760;</span>
                            <h3 class="text-xl font-bold mb-2">Complete Backup</h3>
                            <p class="text-sm opacity-90">Full system backup with database and files</p>
                            <div class="mt-4 text-xs opacity-75">
                                Recommended: Complete system protection
                            </div>
                        </button>
                    </form>
                </div>
                
                    <div class="mt-8 info-box tips">
                        <div class="flex items-start">
                            <span class="text-blue-600 mr-3 text-2xl">&#8505;</span>
                            <div class="text-blue-800">
                                <h4 class="font-bold text-lg mb-2">💡 Backup Best Practices</h4> 
                                <ul class="space-y-2 text-sm leading-relaxed">
                                    <li class="flex items-start"><span class="mr-2">✓</span>Create regular backups to prevent data loss</li>
                                    <li class="flex items-start"><span class="mr-2">✓</span>Complete backups are recommended for full protection</li>
                                    <li class="flex items-start"><span class="mr-2">✓</span>Store backups in a secure location outside the server</li>
                                    <li class="flex items-start"><span class="mr-2">✓</span>Test restore procedures regularly to ensure backup integrity</li>
                                    <li class="flex items-start"><span class="mr-2">✓</span>Follow the 3-2-1 rule: 3 copies, 2 different media, 1 offsite</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Restore Backups Section -->
            <div class="section-card mb-8">
                <div class="section-header">
                    <div class="flex items-center">
                        <span class="text-4xl mr-4">&#8634;</span>
                        <div>
                            <h2 class="text-3xl font-bold">Restore Backups</h2>
                            <p class="text-blue-100 mt-2 text-lg">Recover your system from previous backup points</p>
                        </div>
                    </div>
                </div>
                <div class="section-content">
                
                <?php if (!empty($backups)): ?>
                    <div class="space-y-4">
                        <?php foreach ($backups as $backup): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <span class="text-2xl">
                                                <?php 
                                                echo $backup['type'] === 'database' ? '&#128209;' : 
                                                     ($backup['type'] === 'files' ? '&#128193;' : '&#127760;'); 
                                                ?>
                                            </span>
                                            <div>
                                                <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($backup['filename']); ?></h3>
                                                <div class="text-sm text-gray-500">
                                                    <span class="capitalize"><?php echo $backup['type']; ?> backup</span> • 
                                                    <?php echo date('M j, Y g:i A', strtotime($backup['created'])); ?> • 
                                                    <?php echo number_format($backup['size'] / 1024 / 1024, 2); ?> MB
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($backup['type'] === 'database'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to restore this database backup? This will overwrite all current data!');">
                                                <input type="hidden" name="action" value="restore_database">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                                                    Restore Database
                                                </button>
                                            </form>
                                        <?php elseif ($backup['type'] === 'files'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to restore this files backup? This will overwrite all current files!');">
                                                <input type="hidden" name="action" value="restore_files">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                                    Restore Files
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to restore this complete backup? This will overwrite all current data and files!');">
                                                <input type="hidden" name="action" value="restore_database">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                                                    Restore DB
                                                </button>
                                            </form>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to restore the files from this complete backup? This will overwrite all current files!');">
                                                <input type="hidden" name="action" value="restore_files">
                                                <input type="hidden" name="filename" value="<?php echo str_replace('complete_backup_', 'files_backup_', $backup['filename']); ?>">
                                                <button type="submit" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                                                    Restore Files
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this backup? This action cannot be undone!');">
                                            <input type="hidden" name="action" value="delete_backup">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <span class="text-4xl block mb-2">&#128193;</span>
                        <p>No backup files found</p>
                        <p class="text-sm">Create a backup first to enable restore functionality</p>
                    </div>
                <?php endif; ?>
                
                <!-- Guided Restore Section -->
                <?php if (!empty($backup_pairs)): ?>
                <div class="mt-8 p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-200">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <span class="mr-2">&#127919;</span> Guided Restore (Recommended)
                    </h3>
                    <p class="text-gray-600 mb-4">Restore both database and files from compatible backups with proper order.</p>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to perform a guided restore? This will overwrite all current data and files!');">
                        <input type="hidden" name="action" value="guided_restore">
                        
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Backup Pair:</label>
                                <select name="backup_pair" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Choose compatible backups...</option>
                                    <?php foreach ($backup_pairs as $index => $pair): ?>
                                        <option value="<?php echo $index; ?>">
                                            <?php echo date('M j, Y g:i A', strtotime($pair['database']['created'])); ?>
                                            (DB: <?php echo number_format($pair['database']['size']/1024/1024, 1); ?>MB, 
                                             Files: <?php echo number_format($pair['files']['size']/1024/1024, 1); ?>MB)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Restore Order:</label>
                                <select name="restore_order" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="database_first">Database First (Recommended)</option>
                                    <option value="files_first">Files First</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Database first ensures data integrity</p>
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-lg mb-4">
                            <h4 class="font-semibold text-blue-800 mb-2">Why Database First?</h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>&#8226; Database contains metadata about files and documents</li>
                                <li>&#8226; Ensures file references are properly restored</li>
                                <li>&#8226; Maintains data integrity and consistency</li>
                                <li>&#8226; Recommended by system administrators</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-3 px-6 rounded-lg hover:from-green-700 hover:to-blue-700 transition-all duration-200 font-medium">
                            Start Guided Restore
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex items-start">
                        <span class="text-yellow-600 mr-2">&#9888;</span>
                        <div class="text-sm text-yellow-700">
                            <strong>Restore Warning:</strong> 
                            <ul class="mt-1 list-disc list-inside space-y-1">
                                <li>Restoring will overwrite current data - create a backup first if needed</li>
                                <li>Database restores will replace all current database content</li>
                                <li>File restores will replace all documents and images</li>
                                <li>Use guided restore for best results and proper order</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php renderPageEnd(); ?>