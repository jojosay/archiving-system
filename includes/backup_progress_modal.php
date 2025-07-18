<?php
/**
 * Backup Progress Modal Component
 * Displays real-time progress for backup/restore operations
 */
?>

<!-- Progress Modal -->
<div id="progressModal" class="progress-modal" style="display: none;">
    <div class="progress-modal-content">
        <div class="progress-header">
            <h3 id="progressTitle">Operation in Progress</h3>
            <button type="button" class="progress-close" onclick="closeProgressModal()">&times;</button>
        </div>
        
        <div class="progress-body">
            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div id="progressBar" class="progress-fill" style="width: 0%"></div>
                </div>
                <div id="progressPercentage" class="progress-percentage">0%</div>
            </div>
            
            <!-- Status Information -->
            <div class="progress-info">
                <div class="progress-step">
                    <strong>Current Step:</strong> <span id="progressStep">Initializing...</span>
                </div>
                <div class="progress-message">
                    <span id="progressMessage">Starting operation...</span>
                </div>
            </div>
            
            <!-- Time Information -->
            <div class="progress-timing">
                <div class="timing-item">
                    <strong>Elapsed:</strong> <span id="progressElapsed">0 seconds</span>
                </div>
                <div class="timing-item" id="estimatedContainer" style="display: none;">
                    <strong>Estimated Remaining:</strong> <span id="progressEstimated">Calculating...</span>
                </div>
            </div>
            
            <!-- Resource Information -->
            <div class="progress-resources">
                <div class="resource-item">
                    <strong>Memory Usage:</strong> <span id="progressMemory">-</span>
                </div>
                <div class="resource-item">
                    <strong>Peak Memory:</strong> <span id="progressPeakMemory">-</span>
                </div>
            </div>
            
            <!-- Details Section -->
            <div id="progressDetails" class="progress-details" style="display: none;">
                <h4>Details:</h4>
                <div id="progressDetailsContent"></div>
            </div>
            
            <!-- Result Section (shown on completion) -->
            <div id="progressResult" class="progress-result" style="display: none;">
                <h4>Result:</h4>
                <div id="progressResultContent"></div>
            </div>
            
            <!-- Error Section (shown on failure) -->
            <div id="progressError" class="progress-error" style="display: none;">
                <h4>Error:</h4>
                <div id="progressErrorContent"></div>
            </div>
        </div>
        
        <div class="progress-footer">
            <button id="progressCloseBtn" type="button" class="btn-secondary" onclick="closeProgressModal()" style="display: none;">Close</button>
            <button id="progressCancelBtn" type="button" class="btn-danger" onclick="cancelOperation()" style="display: none;">Cancel</button>
        </div>
    </div>
</div>

<style>
/* Progress Modal Styles */
.progress-modal {
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-modal-content {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px 8px 0 0;
}

.progress-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.progress-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.progress-close:hover {
    background-color: rgba(255,255,255,0.2);
}

.progress-body {
    padding: 1.5rem;
}

.progress-bar-container {
    margin-bottom: 1.5rem;
    position: relative;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    border-radius: 10px;
    transition: width 0.3s ease;
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background-image: linear-gradient(
        -45deg,
        rgba(255, 255, 255, .2) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, .2) 50%,
        rgba(255, 255, 255, .2) 75%,
        transparent 75%,
        transparent
    );
    background-size: 50px 50px;
    animation: move 2s linear infinite;
}

@keyframes move {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 50px 50px;
    }
}

.progress-percentage {
    text-align: center;
    margin-top: 0.5rem;
    font-weight: bold;
    color: #495057;
}

.progress-info {
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 6px;
}

.progress-step {
    margin-bottom: 0.5rem;
    color: #495057;
}

.progress-message {
    color: #6c757d;
    font-style: italic;
}

.progress-timing {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #e3f2fd;
    border-radius: 6px;
}

.timing-item {
    color: #1976d2;
}

.progress-resources {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #fff3e0;
    border-radius: 6px;
}

.resource-item {
    color: #f57c00;
    font-size: 0.9rem;
}

.progress-details {
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #f1f8e9;
    border-radius: 6px;
}

.progress-details h4 {
    margin: 0 0 0.5rem 0;
    color: #388e3c;
}

.progress-result {
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #e8f5e8;
    border: 1px solid #c3e6cb;
    border-radius: 6px;
}

.progress-result h4 {
    margin: 0 0 0.5rem 0;
    color: #155724;
}

.progress-error {
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
}

.progress-error h4 {
    margin: 0 0 0.5rem 0;
    color: #721c24;
}

.progress-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e9ecef;
    text-align: right;
    background-color: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.progress-footer button {
    margin-left: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn-danger:hover {
    background-color: #c82333;
}

/* Responsive Design */
@media (max-width: 768px) {
    .progress-modal-content {
        width: 95%;
        margin: 1rem;
    }
    
    .progress-timing,
    .progress-resources {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let progressInterval;
let currentOperationId = null;
let pollCount = 0;
let maxPollAttempts = 300; // 5 minutes at 1 second intervals

/**
 * Show progress modal and start tracking
 */
function showProgressModal(operationId, title = 'Operation in Progress') {
    currentOperationId = operationId;
    document.getElementById('progressTitle').textContent = title;
    document.getElementById('progressModal').style.display = 'flex';
    document.getElementById('progressCloseBtn').style.display = 'none';
    document.getElementById('progressCancelBtn').style.display = 'inline-block';
    
    // Reset modal state
    resetProgressModal();
    
    // Start polling for progress
    startProgressPolling();
}

/**
 * Close progress modal
 */
function closeProgressModal() {
    document.getElementById('progressModal').style.display = 'none';
    stopProgressPolling();
    currentOperationId = null;
}

/**
 * Reset modal to initial state
 */
function resetProgressModal() {
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressPercentage').textContent = '0%';
    document.getElementById('progressStep').textContent = 'Initializing...';
    document.getElementById('progressMessage').textContent = 'Starting operation...';
    document.getElementById('progressElapsed').textContent = '0 seconds';
    document.getElementById('progressMemory').textContent = '-';
    document.getElementById('progressPeakMemory').textContent = '-';
    document.getElementById('estimatedContainer').style.display = 'none';
    document.getElementById('progressDetails').style.display = 'none';
    document.getElementById('progressResult').style.display = 'none';
    document.getElementById('progressError').style.display = 'none';
}

/**
 * Start polling for progress updates
 */
function startProgressPolling() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    
    pollCount = 0; // Reset poll counter
    
    progressInterval = setInterval(function() {
        if (currentOperationId) {
            pollCount++;
            
            // Stop polling after max attempts to prevent infinite loops
            if (pollCount > maxPollAttempts) {
                stopProgressPolling();
                closeProgressModal();
                alert('Progress tracking timed out. Please check the backup management page for results.');
                return;
            }
            
            fetchProgress(currentOperationId);
        }
    }, 1000); // Poll every second
}

/**
 * Stop polling for progress updates
 */
function stopProgressPolling() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
}

/**
 * Fetch progress from server
 */
function fetchProgress(operationId) {
    fetch(`api/backup_progress.php?operation_id=${operationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProgressDisplay(data);
                
                if (data.is_completed) {
                    stopProgressPolling();
                    showCompletionButtons();
                }
            } else {
                console.error('Progress fetch failed:', data.message);
                // If progress data not found, stop polling and close modal
                if (data.message.includes('Progress data not found')) {
                    stopProgressPolling();
                    closeProgressModal();
                    alert('Operation completed but progress tracking was not available.');
                }
            }
        })
        .catch(error => {
            console.error('Progress fetch error:', error);
            // Stop polling on network errors to prevent infinite loops
            stopProgressPolling();
            closeProgressModal();
            alert('Unable to track progress. Please check the backup management page for results.');
        });
}

/**
 * Update progress display with new data
 */
function updateProgressDisplay(data) {
    // Update progress bar
    if (data.percentage !== null) {
        document.getElementById('progressBar').style.width = data.percentage + '%';
        document.getElementById('progressPercentage').textContent = data.percentage + '%';
    }
    
    // Update step and message
    document.getElementById('progressStep').textContent = data.step || 'Processing...';
    document.getElementById('progressMessage').textContent = data.message || '';
    
    // Update timing
    document.getElementById('progressElapsed').textContent = data.elapsed_time || '0 seconds';
    if (data.estimated_remaining) {
        document.getElementById('progressEstimated').textContent = data.estimated_remaining;
        document.getElementById('estimatedContainer').style.display = 'block';
    }
    
    // Update resources
    document.getElementById('progressMemory').textContent = data.memory_usage || '-';
    document.getElementById('progressPeakMemory').textContent = data.peak_memory || '-';
    
    // Update details
    if (data.details && Object.keys(data.details).length > 0) {
        document.getElementById('progressDetailsContent').innerHTML = formatDetails(data.details);
        document.getElementById('progressDetails').style.display = 'block';
    }
    
    // Handle completion
    if (data.is_completed) {
        if (data.is_failed) {
            showError(data.error || {message: data.message});
        } else {
            showResult(data.result || {message: data.message});
        }
    }
}

/**
 * Format details object for display
 */
function formatDetails(details) {
    let html = '';
    for (const [key, value] of Object.entries(details)) {
        html += `<div><strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${value}</div>`;
    }
    return html;
}

/**
 * Show completion result
 */
function showResult(result) {
    document.getElementById('progressResultContent').innerHTML = formatDetails(result);
    document.getElementById('progressResult').style.display = 'block';
}

/**
 * Show error information
 */
function showError(error) {
    document.getElementById('progressErrorContent').innerHTML = formatDetails(error);
    document.getElementById('progressError').style.display = 'block';
}

/**
 * Show completion buttons
 */
function showCompletionButtons() {
    document.getElementById('progressCloseBtn').style.display = 'inline-block';
    document.getElementById('progressCancelBtn').style.display = 'none';
}

/**
 * Cancel operation (placeholder)
 */
function cancelOperation() {
    // Implement operation cancellation
    if (confirm('Are you sure you want to cancel this operation? This may leave the system in an incomplete state.')) {
        fetch('api/cancel_operation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ operation_id: currentOperationId })
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                hideProgressModal();
                showNotification('Operation cancelled successfully', 'warning');
            } else {
                showNotification('Failed to cancel operation: ' + data.message, 'error');
            }
        }).catch(error => {
            showNotification('Error cancelling operation', 'error');
        });
    }
    alert('Operation cancellation is not yet implemented.');
}
</script>