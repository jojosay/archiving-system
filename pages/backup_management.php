<?php
require_once 'includes/layout.php';
require_once 'includes/backup_manager.php';
require_once 'includes/backup_progress_modal.php';

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
            
            // If operation has progress tracking, redirect to show progress
            if (isset($result['operation_id'])) {
                $operation_id = $result['operation_id'];
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showProgressModal('{$operation_id}', 'Database Backup Progress');
                    });
                </script>";
            }
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
            
            // If operation has progress tracking, show progress modal
            if (isset($result['operation_id'])) {
                $operation_id = $result['operation_id'];
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showProgressModal('{$operation_id}', 'Complete Backup Progress');
                    });
                </script>";
            }
            break;
            
        case 'restore_database':
            $filename = $_POST['filename'] ?? '';
            if ($filename) {
                $result = $backupManager->restoreDatabase($filename);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                
                // If operation has progress tracking, show progress modal
                if (isset($result['operation_id'])) {
                    $operation_id = $result['operation_id'];
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showProgressModal('{$operation_id}', 'Database Restore Progress');
                        });
                    </script>";
                }
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
                    
                    // If operation has progress tracking, show progress modal
                    if (isset($result['operation_id'])) {
                        $operation_id = $result['operation_id'];
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                showProgressModal('{$operation_id}', 'Guided Restore Progress');
                            });
                        </script>";
                    }
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

/* Enhanced Grid Layout Styles */
.backup-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

/* Create backup cards grid - optimized for backup action cards */
.create-backup-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

/* Restore backup items grid - optimized for backup record cards */
.restore-backup-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .backup-grid,
    .create-backup-grid,
    .restore-backup-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .restore-backup-grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
}

@media (min-width: 1280px) {
    .restore-backup-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1536px) {
    .restore-backup-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.backup-item {
    min-height: 320px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Improved backup card sizing */
.backup-card {
    min-height: 180px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Enhanced Guided Restore Section Styles */
.guided-restore-section {
    margin-top: 3rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
    overflow: hidden;
    position: relative;
}

.guided-restore-header {
    background: linear-gradient(135deg, #4c51bf 0%, #553c9a 100%);
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.guided-restore-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 2;
}

.guided-restore-icon-wrapper {
    position: relative;
    margin-right: 1.5rem;
}

.guided-restore-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    box-shadow: 0 10px 25px rgba(251, 191, 36, 0.4);
    position: relative;
    z-index: 2;
}

.guided-restore-pulse {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100px;
    height: 100px;
    background: rgba(251, 191, 36, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: pulse-ring 2s ease-out infinite;
}

@keyframes pulse-ring {
    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

.guided-restore-title-section {
    flex: 1;
    color: white;
}

.guided-restore-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.guided-restore-subtitle {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1rem;
}

.guided-restore-features {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.feature-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.guided-restore-status {
    display: flex;
    align-items: center;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.15);
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.status-dot {
    width: 12px;
    height: 12px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.status-text {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.guided-restore-content {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    padding: 2.5rem;
}

.guided-restore-info {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 2px solid #bfdbfe;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    gap: 1.5rem;
}

.info-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.info-content {
    flex: 1;
}

.info-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e40af;
    margin-bottom: 0.5rem;
}

.info-description {
    color: #1e40af;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.info-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.step-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.step-number {
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
}

.step-text {
    color: #1e40af;
    font-weight: 600;
    font-size: 0.9rem;
}

.guided-restore-form {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-label {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    font-weight: 700;
    color: #374151;
}

.label-icon {
    margin-right: 0.5rem;
    font-size: 1.2rem;
}

.label-text {
    font-size: 1rem;
}

.form-select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #6b7280;
    font-style: italic;
}

.form-submit {
    text-align: center;
    margin-top: 2rem;
}

.guided-restore-button {
    background: linear-gradient(135deg, #10b981 0%, #059669 25%, #3b82f6 75%, #1d4ed8 100%);
    color: white;
    border: none;
    border-radius: 16px;
    padding: 1.25rem 3rem;
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 1rem;
}

.guided-restore-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4);
}

.guided-restore-button:active {
    transform: translateY(-1px);
}

.button-icon {
    font-size: 1.3rem;
}

.button-text {
    position: relative;
    z-index: 2;
}

.button-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s ease;
}

.guided-restore-button:hover .button-shine {
    left: 100%;
}

.submit-help {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: #6b7280;
    font-style: italic;
}

/* Responsive Design for Guided Restore */
@media (max-width: 768px) {
    .guided-restore-header-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .guided-restore-icon-wrapper {
        margin-right: 0;
    }
    
    .guided-restore-title {
        font-size: 2rem;
    }
    
    .guided-restore-features {
        justify-content: center;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .guided-restore-info {
        flex-direction: column;
        text-align: center;
    }
    
    .info-steps {
        grid-template-columns: 1fr;
    }
}

.backup-item .action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
}

/* Card-style metadata display */
.metadata-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.metadata-card:hover {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-color: #cbd5e1;
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
                
                <div class="create-backup-grid">
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
                    <!-- Backup Grid Layout -->
                    <div class="restore-backup-grid">
                        <?php foreach ($backups as $backup): ?>
                            <div class="backup-item group h-fit">
                                <!-- Backup Header with Type and Status -->
                                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <div class="backup-type-icon <?php echo $backup['type']; ?> flex-shrink-0">
                                            <?php 
                                            echo $backup['type'] === 'database' ? '&#128209;' : 
                                                 ($backup['type'] === 'files' ? '&#128193;' : '&#127760;'); 
                                            ?>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800 capitalize">
                                                <?php echo $backup['type']; ?> backup
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-400 font-medium">
                                        <?php echo number_format($backup['size'] / 1024 / 1024, 1); ?> MB
                                    </div>
                                </div>
                                
                                <!-- Backup Details -->
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 mb-2 leading-tight">
                                        <?php echo htmlspecialchars($backup['filename']); ?>
                                    </h3>
                                    
                                    <!-- Metadata in Card Format -->
                                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-blue-500">&#128197;</span>
                                                <div>
                                                    <div class="font-medium text-gray-700"><?php echo date('M j, Y', strtotime($backup['created'])); ?></div>
                                                    <div class="text-gray-500 text-xs">Creation Date</div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-green-500">&#128336;</span>
                                                <div>
                                                    <div class="font-medium text-gray-700"><?php echo date('g:i A', strtotime($backup['created'])); ?></div>
                                                    <div class="text-gray-500 text-xs">Time Created</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons Grid -->
                                <div class="space-y-3">
                                    <?php if ($backup['type'] === 'database'): ?>
                                        <form method="POST" class="w-full" onsubmit="return confirm('WARNING: Database Restore\\n\\nThis will completely replace your current database with the backup data.\\n\\nAll current data will be permanently lost!\\n\\nAre you sure you want to continue?');">
                                            <input type="hidden" name="action" value="restore_database">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                            <button type="submit" class="action-button restore-db w-full justify-center">
                                                <span class="mr-2">&#128209;</span>
                                                Restore Database
                                            </button>
                                        </form>
                                    <?php elseif ($backup['type'] === 'files'): ?>
                                        <form method="POST" class="w-full" onsubmit="return confirm('WARNING: Files Restore\\n\\nThis will completely replace your current files with the backup files.\\n\\nAll current documents and images will be permanently lost!\\n\\nAre you sure you want to continue?');">
                                            <input type="hidden" name="action" value="restore_files">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                            <button type="submit" class="action-button restore-files w-full justify-center">
                                                <span class="mr-2">&#128193;</span>
                                                Restore Files
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="grid grid-cols-2 gap-2">
                                            <form method="POST" onsubmit="return confirm('WARNING: Database Restore\\n\\nThis will completely replace your current database with the backup data.\\n\\nAll current data will be permanently lost!\\n\\nAre you sure you want to continue?');">
                                                <input type="hidden" name="action" value="restore_database">
                                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                <button type="submit" class="action-button restore-complete w-full justify-center text-sm">
                                                    <span class="mr-1">&#128209;</span>
                                                    Restore DB
                                                </button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('WARNING: Files Restore\\n\\nThis will completely replace your current files with the backup files.\\n\\nAll current documents and images will be permanently lost!\\n\\nAre you sure you want to continue?');">
                                                <input type="hidden" name="action" value="restore_files">
                                                <input type="hidden" name="filename" value="<?php echo str_replace('complete_backup_', 'files_backup_', $backup['filename']); ?>">
                                                <button type="submit" class="action-button restore-complete w-full justify-center text-sm">
                                                    <span class="mr-1">&#128193;</span>
                                                    Restore Files
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Button -->
                                    <form method="POST" class="w-full" onsubmit="return confirm('DELETE BACKUP WARNING\\n\\nThis will permanently delete this backup file.\\n\\nThis action cannot be undone!\\n\\nAre you sure you want to delete this backup?');">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                        <button type="submit" class="action-button delete w-full justify-center">
                                            <span class="mr-2">&#128465;</span>
                                            Delete Backup
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Progress Indicator (Hidden by default, can be shown during operations) -->
                                <div class="hidden mt-4 bg-gray-50 rounded-lg p-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                        <span class="text-sm text-gray-600">Processing restore operation...</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">&#128193;</div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Backup Files Found</h3>
                        <p class="text-gray-500 mb-4">Create a backup first to enable restore functionality</p>
                        <div class="flex justify-center">
                            <a href="#create-backup" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <span class="mr-2">&#128190;</span>
                                Create Your First Backup
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Enhanced Guided Restore Section -->
                <?php if (!empty($backup_pairs)): ?>
                <div class="guided-restore-section">
                    <div class="guided-restore-header">
                        <div class="guided-restore-header-content">
                            <div class="guided-restore-icon-wrapper">
                                <div class="guided-restore-icon">
                                    <span class="guided-restore-emoji">&#128737;</span>
                                </div>
                                <div class="guided-restore-pulse"></div>
                            </div>
                            <div class="guided-restore-title-section">
                                <h3 class="guided-restore-title">Guided Restore</h3>
                                <p class="guided-restore-subtitle">Professional restoration with automated safety checks</p>
                                <div class="guided-restore-features">
                                    <span class="feature-badge">&#10004; Automated</span>
                                    <span class="feature-badge">&#10004; Safe</span>
                                    <span class="feature-badge">&#10004; Reliable</span>
                                </div>
                            </div>
                            <div class="guided-restore-status">
                                <div class="status-indicator">
                                    <div class="status-dot"></div>
                                    <span class="status-text">Ready</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="guided-restore-content">
                        <div class="guided-restore-info">
                            <div class="info-icon">
                                <span>&#8505;</span>
                            </div>
                            <div class="info-content">
                                <h4 class="info-title">How Guided Restore Works</h4>
                                <p class="info-description">Automatically restores both database and files from compatible backups in the correct order to ensure data integrity and system consistency.</p>
                                <div class="info-steps">
                                    <div class="step-item">
                                        <span class="step-number">1</span>
                                        <span class="step-text">Validates backup compatibility</span>
                                    </div>
                                    <div class="step-item">
                                        <span class="step-number">2</span>
                                        <span class="step-text">Restores in optimal order</span>
                                    </div>
                                    <div class="step-item">
                                        <span class="step-number">3</span>
                                        <span class="step-text">Verifies system integrity</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" id="guidedRestoreForm" class="guided-restore-form" onsubmit="return startGuidedRestore(event);">
                            <input type="hidden" name="action" value="guided_restore">
                            
                            <div class="form-grid">
                                <div class="form-field">
                                    <label class="form-label">
                                        <span class="label-icon">&#128209;</span>
                                        <span class="label-text">Select Compatible Backup Pair</span>
                                    </label>
                                    <select name="backup_pair" class="form-select" required>
                                        <option value="">Choose compatible backups...</option>
                                        <?php foreach ($backup_pairs as $index => $pair): ?>
                                            <option value="<?php echo $index; ?>">
                                                &#128197; <?php echo date('M j, Y g:i A', strtotime($pair['database']['created'])); ?>
                                                (DB: <?php echo number_format($pair['database']['size']/1024/1024, 1); ?>MB, 
                                                 Files: <?php echo number_format($pair['files']['size']/1024/1024, 1); ?>MB)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="form-help">Only showing backup pairs created at the same time</p>
                                </div>
                                
                                <div class="form-field">
                                    <label class="form-label">
                                        <span class="label-icon">&#8634;</span>
                                        <span class="label-text">Restoration Order</span>
                                    </label>
                                    <select name="restore_order" class="form-select">
                                        <option value="database_first">&#128209; Database First (Recommended)</option>
                                        <option value="files_first">&#128193; Files First</option>
                                    </select>
                                    <p class="form-help">Database first ensures proper file references</p>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-5 rounded-xl mb-6 border border-blue-200">
                                <h4 class="font-bold text-blue-800 mb-3 flex items-center">
                                    <span class="mr-2">&#9889;</span>
                                    Why Database First?
                                </h4>
                                <div class="grid sm:grid-cols-2 gap-3 text-sm text-blue-700">
                                    <div class="flex items-start space-x-2">
                                        <span class="text-green-600 mt-0.5">&#10004;</span>
                                        <span>Database contains file metadata</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="text-green-600 mt-0.5">&#10004;</span>
                                        <span>Ensures proper file references</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="text-green-600 mt-0.5">&#10004;</span>
                                        <span>Maintains data integrity</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <span class="text-green-600 mt-0.5">&#10004;</span>
                                        <span>System administrator approved</span>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-gradient-to-r from-green-600 via-blue-600 to-purple-600 text-white py-4 px-8 rounded-xl hover:from-green-700 hover:via-blue-700 hover:to-purple-700 transition-all duration-300 font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                <span class="flex items-center justify-center">
                                    <span class="mr-3">&#127919;</span>
                                    Start Guided Restore Process
                                    <span class="ml-3">&#8594;</span>
                                </span>
                            </button>
                            
                            <div class="form-submit">
                                <button type="submit" class="guided-restore-button">
                                    <span class="button-icon">&#9654;</span>
                                    <span class="button-text">Start Guided Restore</span>
                                    <div class="button-shine"></div>
                                </button>
                                <p class="submit-help">This operation will completely replace your current system data</p>
                            </div>
                        </form>
                    </div>
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

<script>
/**
 * Enhanced Backup Operations with Progress Tracking
 */

// Start database backup with progress tracking
function startDatabaseBackup() {
    if (confirm('Start database backup? This may take several minutes for large databases.')) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'create_database_backup');
        
        // Start the backup operation
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Parse response to extract operation ID
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const scripts = doc.querySelectorAll('script');
            
            let operationId = null;
            scripts.forEach(script => {
                const content = script.textContent;
                const match = content.match(/showProgressModal\('([^']+)'/);
                if (match) {
                    operationId = match[1];
                }
            });
            
            if (operationId) {
                showProgressModal(operationId, 'Database Backup Progress');
            } else {
                // Fallback: reload page to show result
                location.reload();
            }
        })
        .catch(error => {
            console.error('Backup operation failed:', error);
            alert('Failed to start backup operation. Please try again.');
        });
    }
}

// Start files backup with progress tracking
function startFilesBackup() {
    if (confirm('Start files backup? This may take several minutes for large file collections.')) {
        const formData = new FormData();
        formData.append('action', 'create_files_backup');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // For now, just reload the page
            // TODO: Add progress tracking for file operations
            location.reload();
        })
        .catch(error => {
            console.error('Files backup failed:', error);
            alert('Failed to start files backup. Please try again.');
        });
    }
}

// Start complete backup with progress tracking
function startCompleteBackup() {
    if (confirm('Start complete backup (database + files)? This may take several minutes.')) {
        const formData = new FormData();
        formData.append('action', 'create_complete_backup');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            console.log('Complete backup response received');
            
            // Parse response to extract operation ID
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const scripts = doc.querySelectorAll('script');
            
            let operationId = null;
            scripts.forEach(script => {
                const content = script.textContent;
                const match = content.match(/showProgressModal\('([^']+)'/);
                if (match) {
                    operationId = match[1];
                    console.log('Found operation ID:', operationId);
                }
            });
            
            if (operationId) {
                console.log('Starting progress modal for complete backup');
                if (typeof showProgressModal === 'function') {
                    showProgressModal(operationId, 'Complete Backup Progress');
                } else {
                    console.error('showProgressModal function not found');
                    alert('Progress tracking not available. Operation started but progress cannot be shown.');
                    location.reload();
                }
            } else {
                console.log('No operation ID found, reloading page');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Complete backup failed:', error);
            alert('Failed to start complete backup. Please try again.');
        });
    }
}

// Start guided restore with progress tracking
function startGuidedRestore(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const backupPair = formData.get('backup_pair');
    const restoreOrder = formData.get('restore_order');
    
    if (!backupPair) {
        alert('Please select a backup pair to restore.');
        return false;
    }
    
    const confirmMessage = `GUIDED RESTORE CONFIRMATION

This will perform a complete system restore:

1. ${restoreOrder === 'database_first' ? 'Database will be restored first' : 'Files will be restored first'}
2. ${restoreOrder === 'database_first' ? 'Files will be restored second' : 'Database will be restored second'}
3. All current data will be replaced

This action cannot be undone!

Are you sure you want to continue?`;
    
    if (confirm(confirmMessage)) {
        // Start the guided restore operation
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            console.log('Guided restore response received');
            
            // Parse response to extract operation ID
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const scripts = doc.querySelectorAll('script');
            
            let operationId = null;
            scripts.forEach(script => {
                const content = script.textContent;
                const match = content.match(/showProgressModal\('([^']+)'/);
                if (match) {
                    operationId = match[1];
                    console.log('Found operation ID:', operationId);
                }
            });
            
            if (operationId) {
                console.log('Starting progress modal for guided restore');
                
                // Check if progress modal functions are available
                if (typeof showProgressModal === 'function') {
                    showProgressModal(operationId, 'Guided Restore Progress');
                } else {
                    console.error('showProgressModal function not found');
                    alert('Progress tracking not available. Operation started but progress cannot be shown.');
                    location.reload();
                }
            } else {
                console.log('No operation ID found, checking for errors in response');
                
                // Check if there's an error message in the response
                const errorElements = doc.querySelectorAll('.alert-error, .error');
                if (errorElements.length > 0) {
                    const errorMessage = errorElements[0].textContent.trim();
                    alert('Guided restore failed: ' + errorMessage);
                } else {
                    console.log('No operation ID or error found, reloading page');
                    location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Guided restore failed:', error);
            alert('Failed to start guided restore. Please try again.');
        });
    }
    
    return false;
}

// Enhanced backup button click handlers
document.addEventListener('DOMContentLoaded', function() {
    // Find and enhance backup buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const actionInput = form.querySelector('input[name="action"]');
        if (actionInput) {
            const action = actionInput.value;
            const button = form.querySelector('button[type="submit"]');
            
            if (action === 'create_database_backup' && button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    startDatabaseBackup();
                });
            } else if (action === 'create_files_backup' && button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    startFilesBackup();
                });
            } else if (action === 'create_complete_backup' && button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    startCompleteBackup();
                });
            }
        }
    });
    
    // Auto-show progress modal if operation ID is present
    const urlParams = new URLSearchParams(window.location.search);
    const operationId = urlParams.get('operation_id');
    if (operationId) {
        showProgressModal(operationId, 'Backup Operation Progress');
    }
});
</script>

<?php renderPageEnd(); ?>