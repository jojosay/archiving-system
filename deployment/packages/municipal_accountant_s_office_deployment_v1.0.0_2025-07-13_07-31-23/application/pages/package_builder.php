<?php
// Session is already started by index.php
// Authentication is already checked by index.php
require_once 'includes/deployment_manager.php';
require_once 'includes/package_builder.php';
require_once 'includes/database_export_manager.php';
require_once 'includes/asset_bundler.php';

// Check if user is admin (only admins can access package builder)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php?page=dashboard');
    exit();
}

$deployment_manager = new DeploymentManager();
$package_builder = new PackageBuilder();
$database_export_manager = new DatabaseExportManager();
$asset_bundler = new AssetBundler();

// Handle form submissions
$message = '';
$message_type = '';
$package_created = false;
$package_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_package') {
        
        $office_name = trim($_POST['office_name'] ?? '');
        $package_version = trim($_POST['package_version'] ?? '1.0.0');
        $admin_username = trim($_POST['admin_username'] ?? 'admin');
        $admin_password = trim($_POST['admin_password'] ?? '');
        $admin_email = trim($_POST['admin_email'] ?? '');
        $create_admin = isset($_POST['create_admin']);
        
        if (empty($office_name)) {
            $message = 'Office name is required.';
            $message_type = 'error';
        } else {
            try {
                // Initialize package
                $init_result = $package_builder->initializePackage($office_name, $package_version);
                
                if ($init_result['success']) {
                    $package_path = $init_result['package_path'];
                    $package_name = $init_result['package_name'];
                    
                    $results = [];
                    
                    // Create application directory structure
                    $app_dirs = [
                        'application/',
                        'application/config/',
                        'application/includes/',
                        'application/pages/',
                        'application/api/',
                        'installation/'
                    ];
                    
                    foreach ($app_dirs as $dir) {
                        if (!is_dir($package_path . $dir)) {
                            mkdir($package_path . $dir, 0755, true);
                        }
                    }
                    
                    // Export database schema and data
                    $results['schema'] = $database_export_manager->exportSchema($package_path);
                    
                    $office_config = [
                        'office_name' => $office_name,
                        'deployment_id' => uniqid('deploy_'),
                        'create_admin' => $create_admin,
                        'admin_username' => $admin_username,
                        'admin_password' => $admin_password,
                        'admin_email' => $admin_email
                    ];
                    
                    $results['initial_data'] = $database_export_manager->exportInitialData($package_path, $office_config);
                    $results['office_config'] = $database_export_manager->createOfficeConfig($package_path, $office_config);
                    
                    // Bundle branding assets
                    $results['branding_assets'] = $asset_bundler->bundleBrandingAssets($package_path);
                    
                    // Bundle application assets
                    $results['app_assets'] = $asset_bundler->bundleApplicationAssets($package_path);
                    
                    // Copy core application files
                    $results['core_files'] = copyApplicationFiles($package_path);
                    
                    // Copy installation script
                    if (file_exists('deployment/scripts/install.php')) {
                        copy('deployment/scripts/install.php', $package_path . 'installation/install.php');
                    }
                    
                    // Create package info file
                    $package_info = [
                        'package_name' => $package_name,
                        'office_name' => $office_name,
                        'version' => $package_version,
                        'created' => date('Y-m-d H:i:s'),
                        'created_by' => $_SESSION['username'] ?? 'Unknown',
                        'deployment_id' => $office_config['deployment_id']
                    ];
                    
                    file_put_contents($package_path . 'package_info.json', json_encode($package_info, JSON_PRETTY_PRINT));
                    
                    $package_created = true;
                    $message = "Package '$package_name' created successfully!";
                    $message_type = 'success';
                    
                } else {
                    $message = 'Failed to initialize package.';
                    $message_type = 'error';
                }
                
            } catch (Exception $e) {
                $message = 'Error creating package: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Function to copy application files
function copyApplicationFiles($package_path) {
    $app_path = $package_path . 'application/';
    $source_dirs = [
        'config/' => 'config/',
        'includes/' => 'includes/',
        'pages/' => 'pages/',
        'api/' => 'api/'
    ];
    
    $copied_files = 0;
    
    foreach ($source_dirs as $source => $dest) {
        if (is_dir($source)) {
            $dest_path = $app_path . $dest;
            if (!is_dir($dest_path)) {
                mkdir($dest_path, 0755, true);
            }
            
            $files = glob($source . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    copy($file, $dest_path . basename($file));
                    $copied_files++;
                }
            }
        }
    }
    
    // Copy index.php
    if (file_exists('index.php')) {
        copy('index.php', $app_path . 'index.php');
        $copied_files++;
    }
    
    return [
        'success' => true,
        'files_copied' => $copied_files,
        'message' => "$copied_files application files copied"
    ];
}

require_once 'includes/layout.php';

renderPageStart('Package Builder', 'package_builder');
?>

    <div class="package-builder-container">
        <!-- Hero Section -->
        <div class="builder-hero">
            <div class="hero-content">
                <div class="hero-icon">📦</div>
                <h1>Advanced Package Builder</h1>
                <p>Create professional deployment packages with full customization options</p>
                <div class="hero-features">
                    <div class="feature-item">
                        <span class="feature-icon">🎨</span>
                        <span>Custom Branding</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">👤</span>
                        <span>Admin Setup</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🗄️</span>
                        <span>Database Export</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📁</span>
                        <span>Asset Bundling</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="enhanced-alert alert-<?php echo $message_type; ?>">
                <div class="alert-icon">
                    <?php echo $message_type === 'success' ? '🎉' : '⚠️'; ?>
                </div>
                <div class="alert-content">
                    <div class="alert-title"><?php echo $message_type === 'success' ? 'Package Created Successfully!' : 'Attention Required'; ?></div>
                    <div class="alert-message"><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
        <?php endif; ?>

        <?php if ($package_created && !empty($package_info)): ?>
            <!-- Success State -->
            <div class="success-section">
                <div class="success-card">
                    <div class="success-header">
                        <div class="success-icon">🎉</div>
                        <h2>Package Created Successfully!</h2>
                        <p>Your deployment package is ready for distribution</p>
                    </div>
                    
                    <div class="package-details">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-icon">📦</span>
                                <div class="detail-content">
                                    <h4>Package Name</h4>
                                    <p><?php echo htmlspecialchars($package_info['package_name']); ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-icon">🏢</span>
                                <div class="detail-content">
                                    <h4>Office</h4>
                                    <p><?php echo htmlspecialchars($package_info['office_name']); ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-icon">🏷️</span>
                                <div class="detail-content">
                                    <h4>Version</h4>
                                    <p><?php echo htmlspecialchars($package_info['version']); ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-icon">🕒</span>
                                <div class="detail-content">
                                    <h4>Created</h4>
                                    <p><?php echo htmlspecialchars($package_info['created']); ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-icon">🆔</span>
                                <div class="detail-content">
                                    <h4>Deployment ID</h4>
                                    <p><?php echo htmlspecialchars($package_info['deployment_id']); ?></p>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-icon">👤</span>
                                <div class="detail-content">
                                    <h4>Created By</h4>
                                    <p><?php echo htmlspecialchars($package_info['created_by']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="success-actions">
                        <a href="?page=deployment_center" class="btn btn-primary btn-large">
                            <span class="btn-icon">🏠</span>
                            Back to Deployment Center
                        </a>
                        <button onclick="createAnotherPackage()" class="btn btn-outline btn-large">
                            <span class="btn-icon">📦</span>
                            Create Another Package
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Package Creation Form -->
            <div class="builder-form-section">
                <div class="form-progress">
                    <div class="progress-step active">
                        <span class="step-number">1</span>
                        <span class="step-label">Office Info</span>
                    </div>
                    <div class="progress-step">
                        <span class="step-number">2</span>
                        <span class="step-label">Admin Setup</span>
                    </div>
                    <div class="progress-step">
                        <span class="step-number">3</span>
                        <span class="step-label">Create Package</span>
                    </div>
                </div>

                <form method="POST" id="packageForm" class="builder-form">
                    <input type="hidden" name="action" value="create_package">
                    
                    <!-- Office Information Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <h3>🏢 Office Information</h3>
                            <p>Basic information about the target office</p>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="office_name">Office Name *</label>
                                <input type="text" id="office_name" name="office_name" required 
                                       placeholder="e.g., City Hall Civil Registry Department"
                                       class="enhanced-input">
                                <small class="form-hint">This will be used to name the deployment package and identify the office</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="package_version">Package Version</label>
                                <input type="text" id="package_version" name="package_version" 
                                       value="1.0.0" pattern="[0-9]+\.[0-9]+\.[0-9]+"
                                       class="enhanced-input">
                                <small class="form-hint">Semantic version number (e.g., 1.0.0)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Admin User Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <h3>👤 Administrator Setup</h3>
                            <p>Configure the initial admin user for the deployed system</p>
                        </div>
                        
                        <div class="admin-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" id="create_admin" name="create_admin" onchange="toggleAdminFields()">
                                <span class="toggle-slider"></span>
                                <span class="toggle-label">Create Admin User</span>
                            </label>
                            <small class="form-hint">Enable this to create an initial administrator account</small>
                        </div>
                        
                        <div id="admin_fields" class="admin-fields" style="display: none;">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="admin_username">Admin Username</label>
                                    <input type="text" id="admin_username" name="admin_username" 
                                           value="admin" class="enhanced-input">
                                    <small class="form-hint">Username for the admin account</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="admin_password">Admin Password</label>
                                    <input type="password" id="admin_password" name="admin_password" 
                                           placeholder="Enter secure password" class="enhanced-input">
                                    <small class="form-hint">Leave empty to use default password 'admin123'</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="admin_email">Admin Email</label>
                                    <input type="email" id="admin_email" name="admin_email" 
                                           placeholder="admin@office.local" class="enhanced-input">
                                    <small class="form-hint">Email address for the admin account</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Package Options Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <h3>📦 Package Options</h3>
                            <p>Additional configuration for your deployment package</p>
                        </div>
                        
                        <div class="options-grid">
                            <div class="option-card">
                                <div class="option-icon">🎨</div>
                                <div class="option-content">
                                    <h4>Branding Assets</h4>
                                    <p>Include current branding configuration</p>
                                    <span class="option-status enabled">✅ Included</span>
                                </div>
                            </div>
                            
                            <div class="option-card">
                                <div class="option-icon">🗄️</div>
                                <div class="option-content">
                                    <h4>Database Schema</h4>
                                    <p>Export current database structure</p>
                                    <span class="option-status enabled">✅ Included</span>
                                </div>
                            </div>
                            
                            <div class="option-card">
                                <div class="option-icon">📁</div>
                                <div class="option-content">
                                    <h4>Application Files</h4>
                                    <p>Bundle all application components</p>
                                    <span class="option-status enabled">✅ Included</span>
                                </div>
                            </div>
                            
                            <div class="option-card">
                                <div class="option-icon">⚙️</div>
                                <div class="option-content">
                                    <h4>Installation Scripts</h4>
                                    <p>Automated setup and configuration</p>
                                    <span class="option-status enabled">✅ Included</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <span class="btn-icon">🚀</span>
                            Create Deployment Package
                        </button>
                        <a href="?page=deployment_center" class="btn btn-outline btn-large">
                            <span class="btn-icon">←</span>
                            Back to Deployment Center
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function toggleAdminFields() {
            const checkbox = document.getElementById('create_admin');
            const fields = document.getElementById('admin_fields');
            const progressSteps = document.querySelectorAll('.progress-step');
            
            if (checkbox.checked) {
                fields.style.display = 'block';
                fields.style.animation = 'slideDown 0.3s ease-out';
                progressSteps[1].classList.add('active');
            } else {
                fields.style.display = 'none';
                progressSteps[1].classList.remove('active');
            }
        }
        
        function createAnotherPackage() {
            window.location.href = '?page=package_builder';
        }
        
        // Enhanced form validation with visual feedback
        document.getElementById('packageForm')?.addEventListener('submit', function(e) {
            const officeName = document.getElementById('office_name').value.trim();
            const progressSteps = document.querySelectorAll('.progress-step');
            
            if (!officeName) {
                e.preventDefault();
                showFieldError('office_name', 'Office name is required.');
                return false;
            }
            
            // Activate final step
            progressSteps[2].classList.add('active');
            
            const createAdmin = document.getElementById('create_admin').checked;
            if (createAdmin) {
                const adminPassword = document.getElementById('admin_password').value;
                if (!adminPassword) {
                    if (!confirm('No admin password specified. Default password "admin123" will be used. Continue?')) {
                        e.preventDefault();
                        progressSteps[2].classList.remove('active');
                        return false;
                    }
                }
            }
            
            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="btn-icon">⏳</span>Creating Package...';
            submitBtn.disabled = true;
        });
        
        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            field.style.borderColor = '#dc3545';
            field.focus();
            
            // Remove existing error message
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
            
            // Remove error styling on input
            field.addEventListener('input', function() {
                field.style.borderColor = '';
                const errorMsg = field.parentNode.querySelector('.field-error');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }, { once: true });
        }
        
        // Add smooth animations for form interactions
        document.addEventListener('DOMContentLoaded', function() {
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.5s ease-out';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>

    <style>
    /* Package Builder Enhanced Styles */
    .package-builder-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    /* Hero Section */
    .builder-hero {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .hero-content {
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
    }

    .hero-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        display: block;
    }

    .builder-hero h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .builder-hero p {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }

    .hero-features {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255,255,255,0.1);
        padding: 0.75rem 1rem;
        border-radius: 12px;
        backdrop-filter: blur(10px);
        font-weight: 500;
    }

    .feature-icon {
        font-size: 1.2rem;
    }

    /* Enhanced Alerts */
    .enhanced-alert {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        position: relative;
    }

    .enhanced-alert.alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border-left: 4px solid #28a745;
    }

    .enhanced-alert.alert-error {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        border-left: 4px solid #dc3545;
    }

    .alert-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .alert-content {
        flex: 1;
    }

    .alert-title {
        font-weight: bold;
        margin-bottom: 0.25rem;
    }

    .alert-message {
        color: #666;
    }

    .alert-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .alert-close:hover {
        opacity: 1;
    }

    /* Success Section */
    .success-section {
        margin-bottom: 2rem;
    }

    .success-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .success-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .success-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .success-header h2 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
    }

    .success-header p {
        margin: 0;
        opacity: 0.9;
    }

    .package-details {
        padding: 2rem;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }

    .detail-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .detail-content h4 {
        margin: 0 0 0.25rem 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-content p {
        margin: 0;
        font-weight: 500;
        color: #495057;
        font-family: 'Courier New', monospace;
    }

    .success-actions {
        padding: 2rem;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* Form Progress */
    .form-progress {
        display: flex;
        justify-content: center;
        margin-bottom: 3rem;
        position: relative;
    }

    .form-progress::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: #e9ecef;
        z-index: 1;
    }

    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        background: white;
        padding: 0 1rem;
        z-index: 2;
        position: relative;
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .progress-step.active .step-number {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        transform: scale(1.1);
    }

    .step-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: #6c757d;
        transition: color 0.3s ease;
    }

    .progress-step.active .step-label {
        color: #28a745;
        font-weight: 600;
    }

    /* Form Sections */
    .builder-form-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .builder-form {
        padding: 2rem;
    }

    .form-section {
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #e9ecef;
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .section-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .section-header h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }

    .section-header p {
        color: #6c757d;
        margin: 0;
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
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

    .enhanced-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .enhanced-input:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }

    .form-hint {
        display: block;
        margin-top: 0.25rem;
        color: #6c757d;
        font-size: 0.85rem;
    }

    .field-error {
        color: #dc3545;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        font-weight: 500;
    }

    /* Toggle Switch */
    .admin-toggle {
        margin-bottom: 2rem;
    }

    .toggle-switch {
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
    }

    .toggle-switch input[type="checkbox"] {
        display: none;
    }

    .toggle-slider {
        width: 50px;
        height: 26px;
        background: #e9ecef;
        border-radius: 13px;
        position: relative;
        transition: background 0.3s ease;
    }

    .toggle-slider::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 22px;
        height: 22px;
        background: white;
        border-radius: 50%;
        transition: transform 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .toggle-switch input:checked + .toggle-slider {
        background: #28a745;
    }

    .toggle-switch input:checked + .toggle-slider::before {
        transform: translateX(24px);
    }

    .toggle-label {
        font-weight: 600;
        color: #495057;
    }

    /* Admin Fields */
    .admin-fields {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 2rem;
        margin-top: 1rem;
    }

    /* Options Grid */
    .options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .option-card {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.2s ease;
    }

    .option-card:hover {
        border-color: #28a745;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
    }

    .option-icon {
        font-size: 2rem;
        margin-bottom: 1rem;
        display: block;
    }

    .option-content h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
        color: #495057;
    }

    .option-content p {
        margin: 0 0 1rem 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .option-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .option-status.enabled {
        background: #d4edda;
        color: #155724;
    }

    /* Enhanced Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-large {
        padding: 1rem 2rem;
        font-size: 1.1rem;
        flex: 1;
        justify-content: center;
    }

    .btn-primary {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-outline {
        background: white;
        color: #28a745;
        border: 2px solid #28a745;
    }

    .btn-outline:hover {
        background: #28a745;
        color: white;
    }

    .btn-icon {
        font-size: 1.1rem;
    }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    /* Animations */
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .form-grid,
        .options-grid,
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .hero-features {
            gap: 1rem;
        }

        .builder-hero h1 {
            font-size: 2rem;
        }

        .builder-hero p {
            font-size: 1rem;
        }

        .form-actions {
            flex-direction: column;
        }

        .success-actions {
            flex-direction: column;
        }

        .form-progress {
            flex-wrap: wrap;
            gap: 1rem;
        }

        .form-progress::before {
            display: none;
        }
    }
    </style>

<?php renderPageEnd(); ?>