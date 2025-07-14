<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/system_reset_manager.php';

$database = new Database();
$resetManager = new SystemResetManager($database);

$message = '';
$message_type = '';
$reset_results = null;

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'perform_reset':
            $confirmation = $_POST['confirmation'] ?? '';
            $preserve_admin = isset($_POST['preserve_admin']) ? true : false;
            
            if ($confirmation !== 'RESET SYSTEM') {
                $message = 'Invalid confirmation text. Please type "RESET SYSTEM" exactly.';
                $message_type = 'error';
            } else {
                $reset_results = $resetManager->performSystemReset($preserve_admin);
                $message = $reset_results['message'];
                $message_type = $reset_results['success'] ? 'success' : 'error';
                
                // If reset actually worked but shows error, check the details
                if (!$reset_results['success'] && !empty($reset_results['details'])) {
                    // Check if most operations succeeded despite the error
                    $successful_ops = 0;
                    $total_ops = count($reset_results['details']);
                    
                    foreach ($reset_results['details'] as $operation => $result) {
                        if (is_array($result) && isset($result['success']) && $result['success']) {
                            $successful_ops++;
                        }
                    }
                    
                    // If most operations succeeded, it might be a false error
                    if ($successful_ops >= ($total_ops * 0.7)) {
                        $message .= " (Note: Most operations completed successfully - check details below)";
                    }
                }
            }
            break;
    }
}

// Get current system statistics
$stats_result = $resetManager->getSystemStats();
$system_stats = $stats_result['success'] ? $stats_result['stats'] : [];

renderPageStart('System Reset', 'system_reset');
?>

<style>
.reset-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.warning-card {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

.info-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0;
}

.stat-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e9ecef;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #495057;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.reset-form {
    background: #fff3cd;
    border: 2px solid #ffeaa7;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 1rem;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 1rem 0;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.message {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
}

.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.results-card {
    background: #e8f5e9;
    border: 1px solid #c3e6cb;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1.5rem;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.result-item {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #d4edda;
}

.icon-warning {
    font-size: 3rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .reset-container {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .results-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="reset-container">
    <div class="text-center mb-4">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">System Reset</h1>
        <p class="text-gray-600 text-lg">Reset the system to a clean slate while preserving admin access</p>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Warning Card -->
    <div class="warning-card">
        <div class="text-center">
            <div class="icon-warning">⚠️</div>
            <h2 class="text-2xl font-bold mb-3">DANGER ZONE</h2>
            <p class="text-lg mb-2">This action will permanently delete ALL system data including:</p>
            <ul class="text-left mt-3 space-y-1">
                <li>• All documents and their metadata</li>
                <li>• All book images and references</li>
                <li>• All custom document types and fields</li>
                <li>• All user accounts (except admin if preserved)</li>
                <li>• All uploaded files in storage</li>
                <li>• All location data</li>
            </ul>
            <p class="mt-3 font-bold">This action CANNOT be undone!</p>
        </div>
    </div>

    <!-- Current System Statistics -->
    <div class="info-card">
        <h3 class="text-xl font-bold mb-3">Current System Statistics</h3>
        <p class="text-gray-600 mb-3">Review what will be deleted:</p>
        
        <?php if (!empty($system_stats)): ?>
            <div class="stats-grid">
                <?php foreach ($system_stats as $label => $value): ?>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $value; ?></div>
                        <div class="stat-label"><?php echo $label; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-red-600">Unable to load system statistics</p>
        <?php endif; ?>
    </div>

    <!-- Reset Form -->
    <div class="reset-form">
        <h3 class="text-xl font-bold mb-3 text-center">Perform System Reset</h3>
        
        <form method="POST" id="resetForm">
            <input type="hidden" name="action" value="perform_reset">
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="preserve_admin" name="preserve_admin" checked>
                    <label for="preserve_admin">Preserve admin user accounts</label>
                </div>
                <small class="text-gray-600">Recommended: Keep admin accounts so you can still access the system</small>
            </div>
            
            <div class="form-group">
                <label for="confirmation">Type "RESET SYSTEM" to confirm:</label>
                <input type="text" id="confirmation" name="confirmation" class="form-control" 
                       placeholder="Type exactly: RESET SYSTEM" required>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="window.history.back()" class="btn btn-secondary mr-3">
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger" id="resetButton" disabled>
                    🗑️ RESET SYSTEM
                </button>
            </div>
        </form>
    </div>

    <!-- Reset Results -->
    <?php if ($reset_results && $reset_results['success']): ?>
        <div class="results-card">
            <h3 class="text-xl font-bold mb-3 text-green-700">✅ Reset Completed Successfully</h3>
            
            <?php if (!empty($reset_results['details'])): ?>
                <div class="results-grid">
                    <?php foreach ($reset_results['details'] as $operation => $result): ?>
                        <div class="result-item">
                            <h4 class="font-semibold capitalize"><?php echo str_replace('_', ' ', $operation); ?></h4>
                            <?php if (is_array($result)): ?>
                                <?php foreach ($result as $key => $value): ?>
                                    <div class="text-sm text-gray-600">
                                        <?php echo ucfirst(str_replace('_', ' ', $key)); ?>: 
                                        <?php echo is_array($value) ? implode(', ', $value) : $value; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-sm text-gray-600"><?php echo $result; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                <p class="text-blue-800">
                    <strong>Next Steps:</strong> The system has been reset to a clean state. 
                    You can now start fresh with document types, users, and data entry.
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmationInput = document.getElementById('confirmation');
    const resetButton = document.getElementById('resetButton');
    const resetForm = document.getElementById('resetForm');
    
    // Enable/disable reset button based on confirmation text
    confirmationInput.addEventListener('input', function() {
        if (this.value === 'RESET SYSTEM') {
            resetButton.disabled = false;
            resetButton.style.opacity = '1';
        } else {
            resetButton.disabled = true;
            resetButton.style.opacity = '0.6';
        }
    });
    
    // Final confirmation before submit
    resetForm.addEventListener('submit', function(e) {
        const confirmed = confirm(
            'FINAL WARNING!\n\n' +
            'This will permanently delete ALL system data.\n' +
            'A backup will be created, but this action cannot be easily undone.\n\n' +
            'Are you absolutely sure you want to proceed?'
        );
        
        if (!confirmed) {
            e.preventDefault();
        }
    });
});
</script>

<?php renderPageEnd(); ?>