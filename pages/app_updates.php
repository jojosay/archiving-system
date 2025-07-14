<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'config/version.php';

renderPageStart('App Updates', 'app_updates');
?>

<div class="page-header">
    <h1>Application Updates</h1>
    <p>Check for and manage application updates from GitHub</p>
</div>

<style>
    .update-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .version-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .version-item {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 4px;
        border-left: 4px solid #007bff;
    }
    
    .version-label {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .version-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .update-status {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        text-align: center;
    }
    
    .status-up-to-date {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .status-update-available {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .status-checking {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .status-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        text-decoration: none;
        display: inline-block;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .release-notes {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 1rem;
        margin-top: 1rem;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .loading {
        display: none;
        text-align: center;
        padding: 1rem;
    }
    
    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #007bff;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="update-card">
    <h2>Current Version Information</h2>
    <div class="version-info">
        <div class="version-item">
            <div class="version-label">Application Name</div>
            <div class="version-value"><?php echo APP_NAME; ?></div>
        </div>
        <div class="version-item">
            <div class="version-label">Current Version</div>
            <div class="version-value"><?php echo APP_VERSION; ?></div>
        </div>
        <div class="version-item">
            <div class="version-label">Build Number</div>
            <div class="version-value"><?php echo APP_BUILD; ?></div>
        </div>
        <div class="version-item">
            <div class="version-label">Repository</div>
            <div class="version-value"><?php echo GITHUB_REPO_OWNER . '/' . GITHUB_REPO_NAME; ?></div>
        </div>
    </div>
</div>

<div class="update-card">
    <h2>Update Status</h2>
    
    <div id="loading" class="loading">
        <div class="spinner"></div>
        <p>Checking for updates...</p>
    </div>
    
    <div id="update-status" class="update-status status-checking" style="display: none;">
        <p id="status-message">Checking for updates...</p>
    </div>
    
    <div id="update-actions" style="display: none;">
        <button id="check-updates-btn" class="btn btn-primary" onclick="checkForUpdates(true)">
            Check for Updates
        </button>
        <button id="download-update-btn" class="btn btn-success" onclick="downloadUpdate()" style="display: none;">
            Download Update
        </button>
        <button id="view-release-btn" class="btn btn-secondary" onclick="viewReleaseNotes()" style="display: none;">
            View Release Notes
        </button>
    </div>
    
    <div id="release-notes" class="release-notes" style="display: none;">
        <h4>Release Notes</h4>
        <div id="release-content"></div>
    </div>
</div>

<script>
let updateData = null;

// Check for updates when page loads
document.addEventListener('DOMContentLoaded', function() {
    checkForUpdates(false);
});

function checkForUpdates(force = false) {
    const loading = document.getElementById('loading');
    const statusDiv = document.getElementById('update-status');
    const actionsDiv = document.getElementById('update-actions');
    const checkBtn = document.getElementById('check-updates-btn');
    
    // Show loading state
    loading.style.display = 'block';
    statusDiv.style.display = 'none';
    actionsDiv.style.display = 'none';
    checkBtn.disabled = true;
    
    const url = `api/check_updates.php${force ? '?force=true' : ''}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            updateData = data;
            displayUpdateStatus(data);
        })
        .catch(error => {
            console.error('Error checking for updates:', error);
            displayError('Failed to check for updates. Please try again.');
        })
        .finally(() => {
            loading.style.display = 'none';
            statusDiv.style.display = 'block';
            actionsDiv.style.display = 'block';
            checkBtn.disabled = false;
        });
}

function displayUpdateStatus(data) {
    const statusDiv = document.getElementById('update-status');
    const statusMessage = document.getElementById('status-message');
    const downloadBtn = document.getElementById('download-update-btn');
    const viewReleaseBtn = document.getElementById('view-release-btn');
    
    if (!data.success) {
        statusDiv.className = 'update-status status-error';
        statusMessage.textContent = data.message || 'Error checking for updates';
        downloadBtn.style.display = 'none';
        viewReleaseBtn.style.display = 'none';
        return;
    }
    
    if (data.has_update) {
        statusDiv.className = 'update-status status-update-available';
        statusMessage.innerHTML = `
            <strong>Update Available!</strong><br>
            Current Version: ${data.current_version}<br>
            Latest Version: ${data.latest_version}
        `;
        downloadBtn.style.display = 'inline-block';
        viewReleaseBtn.style.display = 'inline-block';
    } else {
        statusDiv.className = 'update-status status-up-to-date';
        statusMessage.innerHTML = `
            <strong>You're up to date!</strong><br>
            Current Version: ${data.current_version}
        `;
        downloadBtn.style.display = 'none';
        viewReleaseBtn.style.display = data.release_info ? 'inline-block' : 'none';
    }
}

function displayError(message) {
    const statusDiv = document.getElementById('update-status');
    const statusMessage = document.getElementById('status-message');
    
    statusDiv.className = 'update-status status-error';
    statusMessage.textContent = message;
}

function downloadUpdate() {
    if (!updateData || !updateData.has_update) {
        alert('No update available to download.');
        return;
    }
    
    // Open GitHub release page
    if (updateData.release_info && updateData.release_info.html_url) {
        window.open(updateData.release_info.html_url, '_blank');
    }
}

function viewReleaseNotes() {
    const releaseNotesDiv = document.getElementById('release-notes');
    const releaseContent = document.getElementById('release-content');
    
    if (!updateData || !updateData.release_info) {
        alert('No release information available.');
        return;
    }
    
    const release = updateData.release_info;
    releaseContent.innerHTML = `
        <h5>${release.name || release.tag_name}</h5>
        <p><strong>Published:</strong> ${new Date(release.published_at).toLocaleDateString()}</p>
        <div>${release.body ? release.body.replace(/\n/g, '<br>') : 'No release notes available.'}</div>
    `;
    
    releaseNotesDiv.style.display = releaseNotesDiv.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php renderPageEnd(); ?>