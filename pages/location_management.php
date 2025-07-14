<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';

renderPageStart('Location Management', 'location_management');
?>

<div class="page-header">
    <h1>Location Data Management</h1>
    <p>Upload and manage location hierarchy CSV files</p>
</div>

<style>
    .upload-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .upload-section {
        margin-bottom: 2rem;
        padding: 1.5rem;
        border: 2px dashed #BDC3C7;
        border-radius: 8px;
        text-align: center;
        position: relative;
    }
    
    .upload-section h3 {
        color: #2C3E50;
        margin-bottom: 1rem;
    }
    
    .file-input {
        margin: 1rem 0;
    }
    
    .upload-btn {
        background: #27AE60;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s;
    }
    
    .upload-btn:hover:not(:disabled) {
        background: #229954;
    }
    
    .upload-btn:disabled {
        background: #95A5A6;
        cursor: not-allowed;
    }
    
    .progress-container {
        margin: 1rem 0;
        display: none;
    }
    
    .progress-bar {
        width: 100%;
        height: 20px;
        background-color: #ECF0F1;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #27AE60, #2ECC71);
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 10px;
    }
    
    .progress-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 12px;
        font-weight: bold;
        color: #2C3E50;
    }
    
    .progress-details {
        margin-top: 0.5rem;
        font-size: 0.9rem;
        color: #7F8C8D;
    }
    
    .message {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    
    .message.success {
        background: #D5EDDA;
        color: #155724;
        border: 1px solid #C3E6CB;
    }
    
    .message.error {
        background: #F8D7DA;
        color: #721C24;
        border: 1px solid #F5C6CB;
    }
    
    .message.info {
        background: #D1ECF1;
        color: #0C5460;
        border: 1px solid #BEE5EB;
    }
    
    .upload-section.uploading {
        border-color: #3498DB;
        background-color: #F8F9FA;
    }
    
    .upload-section.success {
        border-color: #27AE60;
        background-color: #F8FFF9;
    }
    
    .upload-section.error {
        border-color: #E74C3C;
        background-color: #FFF8F8;
    }
</style>

<div id="global-message" class="message" style="display: none;"></div>

<div class="upload-card">
    <h2>Upload Location CSV Files</h2>
    <p>Upload your location hierarchy CSV files in the correct order: Regions → Provinces → Cities/Municipalities → Barangays</p>
    
    <!-- Regions Upload -->
    <div class="upload-section" id="regions-section">
        <h3>1. Regions CSV</h3>
        <p>Format: id, region_name, region_code</p>
        <form id="regions-form">
            <div class="file-input">
                <input type="file" id="regions-file" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Regions</button>
        </form>
        <div class="progress-container" id="regions-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="regions-progress-fill"></div>
                <div class="progress-text" id="regions-progress-text">0%</div>
            </div>
            <div class="progress-details" id="regions-progress-details"></div>
        </div>
    </div>
    
    <!-- Provinces Upload -->
    <div class="upload-section" id="provinces-section">
        <h3>2. Provinces CSV</h3>
        <p>Format: id, ProvinceName, region_code</p>
        <form id="provinces-form">
            <div class="file-input">
                <input type="file" id="provinces-file" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Provinces</button>
        </form>
        <div class="progress-container" id="provinces-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="provinces-progress-fill"></div>
                <div class="progress-text" id="provinces-progress-text">0%</div>
            </div>
            <div class="progress-details" id="provinces-progress-details"></div>
        </div>
    </div>
    
    <!-- Cities/Municipalities Upload -->
    <div class="upload-section" id="citymun-section">
        <h3>3. Cities/Municipalities CSV</h3>
        <p>Format: id, CityMunName, ProvinceID</p>
        <form id="citymun-form">
            <div class="file-input">
                <input type="file" id="citymun-file" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Cities/Municipalities</button>
        </form>
        <div class="progress-container" id="citymun-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="citymun-progress-fill"></div>
                <div class="progress-text" id="citymun-progress-text">0%</div>
            </div>
            <div class="progress-details" id="citymun-progress-details"></div>
        </div>
    </div>
    
    <!-- Barangays Upload -->
    <div class="upload-section" id="barangays-section">
        <h3>4. Barangays CSV</h3>
        <p>Format: id, BarangayName, CityMunID</p>
        <form id="barangays-form">
            <div class="file-input">
                <input type="file" id="barangays-file" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Barangays</button>
        </form>
        <div class="progress-container" id="barangays-progress">
            <div class="progress-bar">
                <div class="progress-fill" id="barangays-progress-fill"></div>
                <div class="progress-text" id="barangays-progress-text">0%</div>
            </div>
            <div class="progress-details" id="barangays-progress-details"></div>
        </div>
    </div>
</div>

<script>
class LocationUploader {
    constructor() {
        this.initializeEventListeners();
    }
    
    initializeEventListeners() {
        // Add event listeners for all forms
        ['regions', 'provinces', 'citymun', 'barangays'].forEach(type => {
            const form = document.getElementById(`${type}-form`);
            if (form) {
                form.addEventListener('submit', (e) => this.handleUpload(e, type));
            }
        });
    }
    
    async handleUpload(event, uploadType) {
        event.preventDefault();
        
        console.log('handleUpload called for:', uploadType);
        
        const fileInput = document.getElementById(`${uploadType}-file`);
        const file = fileInput.files[0];
        
        if (!file) {
            this.showMessage('Please select a file to upload.', 'error');
            return;
        }
        
        if (!file.name.toLowerCase().endsWith('.csv')) {
            this.showMessage('Please select a CSV file.', 'error');
            return;
        }
        
        console.log('File selected:', file.name, 'Size:', file.size, 'Type:', uploadType);
        
        try {
            await this.uploadWithProgress(uploadType, file);
        } catch (error) {
            console.error('Upload error for', uploadType, ':', error);
            this.showMessage('Upload failed: ' + error.message, 'error');
            this.resetUploadSection(uploadType);
        }
    }
    
    async uploadWithProgress(uploadType, file) {
        const section = document.getElementById(`${uploadType}-section`);
        const progressContainer = document.getElementById(`${uploadType}-progress`);
        const submitButton = section.querySelector('button[type="submit"]');
        
        // Start upload
        this.setUploadState(uploadType, 'uploading');
        submitButton.disabled = true;
        progressContainer.style.display = 'block';
        
        // Upload file
        const uploadId = await this.startUpload(uploadType, file);
        
        // Process file
        await this.processUpload(uploadType, uploadId);
        
        // Monitor progress
        await this.monitorProgress(uploadType, uploadId);
        
        // Cleanup
        await this.cleanup(uploadId);
        submitButton.disabled = false;
    }
    
    async startUpload(uploadType, file) {
        const formData = new FormData();
        formData.append('action', 'start_upload');
        formData.append('upload_type', uploadType);
        formData.append('csv_file', file);
        
        console.log('Starting upload for:', uploadType, 'File:', file.name);
        
        const response = await fetch('api/location_upload_progress.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Get response text first to debug
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text that failed to parse:', responseText);
            throw new Error('Server returned invalid JSON. Check browser console for details.');
        }
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        return result.upload_id;
    }
    
    async processUpload(uploadType, uploadId) {
        const formData = new FormData();
        formData.append('action', 'process_upload');
        formData.append('upload_id', uploadId);
        
        // Start processing (don't wait for completion)
        fetch('api/location_upload_progress.php', {
            method: 'POST',
            body: formData
        });
    }
    
    async monitorProgress(uploadType, uploadId) {
        return new Promise((resolve, reject) => {
            const checkProgress = async () => {
                try {
                    const formData = new FormData();
                    formData.append('action', 'get_progress');
                    formData.append('upload_id', uploadId);
                    
                    const response = await fetch('api/location_upload_progress.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const responseText = await response.text();
                    console.log('Progress check response:', responseText);
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('Progress JSON parse error:', e);
                        reject(new Error('Server returned invalid JSON during progress check'));
                        return;
                    }
                    
                    if (!result.success) {
                        reject(new Error(result.message));
                        return;
                    }
                    
                    this.updateProgress(uploadType, result);
                    
                    if (result.status === 'completed') {
                        this.setUploadState(uploadType, 'success');
                        this.showMessage(result.message, 'success');
                        resolve();
                    } else if (result.status === 'error') {
                        this.setUploadState(uploadType, 'error');
                        reject(new Error(result.message));
                    } else {
                        // Continue monitoring
                        setTimeout(checkProgress, 1000);
                    }
                } catch (error) {
                    console.error('Progress monitoring error:', error);
                    reject(error);
                }
            };
            
            checkProgress();
        });
    }
    
    updateProgress(uploadType, progressData) {
        const progressFill = document.getElementById(`${uploadType}-progress-fill`);
        const progressText = document.getElementById(`${uploadType}-progress-text`);
        const progressDetails = document.getElementById(`${uploadType}-progress-details`);
        
        const progress = Math.round(progressData.progress);
        progressFill.style.width = `${progress}%`;
        progressText.textContent = `${progress}%`;
        
        if (progressData.processed_rows && progressData.total_rows) {
            progressDetails.textContent = `${progressData.processed_rows} / ${progressData.total_rows} records processed`;
        } else {
            progressDetails.textContent = progressData.message || '';
        }
    }
    
    setUploadState(uploadType, state) {
        const section = document.getElementById(`${uploadType}-section`);
        section.className = `upload-section ${state}`;
    }
    
    resetUploadSection(uploadType) {
        const section = document.getElementById(`${uploadType}-section`);
        const progressContainer = document.getElementById(`${uploadType}-progress`);
        const submitButton = section.querySelector('button[type="submit"]');
        
        section.className = 'upload-section';
        progressContainer.style.display = 'none';
        submitButton.disabled = false;
        
        this.updateProgress(uploadType, { progress: 0, message: '' });
    }
    
    async cleanup(uploadId) {
        const formData = new FormData();
        formData.append('action', 'cleanup');
        formData.append('upload_id', uploadId);
        
        await fetch('api/location_upload_progress.php', {
            method: 'POST',
            body: formData
        });
    }
    
    showMessage(message, type) {
        const messageDiv = document.getElementById('global-message');
        messageDiv.textContent = message;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }
}

// Initialize the uploader when the page loads
document.addEventListener('DOMContentLoaded', () => {
    new LocationUploader();
});
</script>

<?php renderPageEnd(); ?>