<?php
// Session is already started by index.php
// Authentication is already checked by index.php
require_once 'includes/deployment_manager.php';
require_once 'includes/package_builder.php';

// Check if user is admin (only admins can access deployment center)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php?page=dashboard');
    exit();
}

$deployment_manager = new DeploymentManager();
$package_builder = new PackageBuilder();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'validate_environment':
                $validation = $deployment_manager->validateEnvironment();
                $message = 'Environment validation completed.';
                $message_type = 'success';
                break;
                
            case 'initialize_package':
                $office_name = $_POST['office_name'] ?? '';
                $package_version = $_POST['package_version'] ?? '1.0.0';
                
                if (!empty($office_name)) {
                    $result = $package_builder->initializePackage($office_name, $package_version);
                    if ($result['success']) {
                        $message = 'Package initialized successfully: ' . $result['package_name'];
                        $message_type = 'success';
                    } else {
                        $message = 'Error initializing package.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Office name is required.';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get current status
$deployment_config = $deployment_manager->getDeploymentConfig();
$environment_status = $deployment_manager->validateEnvironment();
$package_validation = $package_builder->validatePackageRequirements();

require_once 'includes/layout.php';

renderPageStart('Deployment Center', 'deployment_center');
?>

    <div class="deployment-center-container">
        <!-- Hero Section -->
        <div class="deployment-hero">
            <div class="hero-content">
                <div class="hero-icon">🚀</div>
                <h1>Deployment Center</h1>
                <p>Create and manage professional deployment packages for multi-office distribution</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($environment_status); ?></span>
                        <span class="stat-label">System Checks</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count(array_filter($environment_status)); ?></span>
                        <span class="stat-label">Passed</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count(array_filter($package_validation)); ?></span>
                        <span class="stat-label">Requirements Met</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="enhanced-alert alert-<?php echo $message_type; ?>">
                <div class="alert-icon">
                    <?php echo $message_type === 'success' ? '✅' : '⚠️'; ?>
                </div>
                <div class="alert-content">
                    <div class="alert-title"><?php echo $message_type === 'success' ? 'Success!' : 'Attention Required'; ?></div>
                    <div class="alert-message"><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">×</button>
            </div>
        <?php endif; ?>

        <!-- Main Content Grid -->
        <div class="deployment-grid">
            <!-- Environment Status Card -->
            <div class="deployment-card status-card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="card-icon">📊</span>
                        <h3>System Environment</h3>
                    </div>
                    <div class="card-status">
                        <?php 
                        $passed = count(array_filter($environment_status));
                        $total = count($environment_status);
                        $percentage = $total > 0 ? round(($passed / $total) * 100) : 0;
                        ?>
                        <span class="status-badge <?php echo $percentage === 100 ? 'success' : 'warning'; ?>">
                            <?php echo $passed; ?>/<?php echo $total; ?> Checks
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="status-grid">
                        <?php foreach ($environment_status as $check => $status): ?>
                            <div class="status-item <?php echo $status ? 'status-success' : 'status-error'; ?>">
                                <span class="status-icon">
                                    <?php echo $status ? '✅' : '❌'; ?>
                                </span>
                                <span class="status-label"><?php echo ucwords(str_replace('_', ' ', $check)); ?></span>
                                <?php if (!$status): ?>
                                    <span class="status-help">⚠️</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="card-actions">
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="validate_environment">
                            <button type="submit" class="btn btn-outline">
                                <span class="btn-icon">🔄</span>
                                Refresh Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Package Requirements Card -->
            <div class="deployment-card requirements-card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="card-icon">📋</span>
                        <h3>Package Requirements</h3>
                    </div>
                    <div class="card-status">
                        <?php 
                        $req_passed = count(array_filter($package_validation));
                        $req_total = count($package_validation);
                        $req_percentage = $req_total > 0 ? round(($req_passed / $req_total) * 100) : 0;
                        ?>
                        <span class="status-badge <?php echo $req_percentage === 100 ? 'success' : 'warning'; ?>">
                            <?php echo $req_passed; ?>/<?php echo $req_total; ?> Ready
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $req_percentage; ?>%"></div>
                    </div>
                    <div class="requirements-list">
                        <?php foreach ($package_validation as $check => $status): ?>
                            <div class="requirement-item <?php echo $status ? 'requirement-success' : 'requirement-error'; ?>">
                                <span class="requirement-icon">
                                    <?php echo $status ? '✅' : '❌'; ?>
                                </span>
                                <div class="requirement-content">
                                    <span class="requirement-label"><?php echo ucwords(str_replace('_', ' ', $check)); ?></span>
                                    <span class="requirement-status"><?php echo $status ? 'Ready' : 'Missing'; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="quick-actions-section">
            <div class="section-header">
                <h2>🚀 Quick Actions</h2>
                <p>Choose your deployment approach</p>
            </div>
            
            <div class="actions-grid">
                <!-- Quick Package Creation -->
                <div class="action-card quick-package">
                    <div class="action-header">
                        <span class="action-icon">⚡</span>
                        <h3>Quick Package</h3>
                        <span class="action-badge">Recommended</span>
                    </div>
                    <div class="action-body">
                        <p>Create a basic deployment package with current settings</p>
                        <form method="POST" class="quick-form">
                            <input type="hidden" name="action" value="initialize_package">
                            
                            <div class="form-group">
                                <label for="office_name">Office Name</label>
                                <input type="text" id="office_name" name="office_name" required 
                                       placeholder="e.g., City Hall Civil Registry"
                                       class="enhanced-input">
                                <small class="form-hint">Used for package naming and identification</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="package_version">Version</label>
                                <input type="text" id="package_version" name="package_version" 
                                       value="1.0.0" pattern="[0-9]+\.[0-9]+\.[0-9]+"
                                       class="enhanced-input">
                                <small class="form-hint">Semantic version (e.g., 1.0.0)</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-large">
                                <span class="btn-icon">🚀</span>
                                Create Package
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Advanced Package Builder -->
                <div class="action-card advanced-package">
                    <div class="action-header">
                        <span class="action-icon">🔧</span>
                        <h3>Advanced Builder</h3>
                        <span class="action-badge advanced">Pro</span>
                    </div>
                    <div class="action-body">
                        <p>Full-featured package builder with custom options</p>
                        <ul class="feature-list">
                            <li>✅ Custom admin user creation</li>
                            <li>✅ Advanced branding options</li>
                            <li>✅ Database customization</li>
                            <li>✅ Asset management</li>
                        </ul>
                        <a href="?page=package_builder" class="btn btn-success btn-large">
                            <span class="btn-icon">📦</span>
                            Open Advanced Builder
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information Section -->
        <div class="system-info-section">
            <div class="section-header">
                <h2>⚙️ System Information</h2>
                <p>Current deployment configuration and system details</p>
            </div>
            
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">📁</div>
                    <div class="info-content">
                        <h4>Deployment Directory</h4>
                        <p><?php echo htmlspecialchars($deployment_config['deployment_dir']); ?></p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📦</div>
                    <div class="info-content">
                        <h4>Packages Directory</h4>
                        <p><?php echo htmlspecialchars($deployment_config['packages_dir']); ?></p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">🏷️</div>
                    <div class="info-content">
                        <h4>System Version</h4>
                        <p><?php echo htmlspecialchars($deployment_config['version']); ?></p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">🕒</div>
                    <div class="info-content">
                        <h4>Last Updated</h4>
                        <p><?php echo htmlspecialchars($deployment_config['created']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="help-section">
            <div class="help-card">
                <div class="help-header">
                    <span class="help-icon">💡</span>
                    <h3>Need Help?</h3>
                </div>
                <div class="help-content">
                    <p>Learn more about creating and managing deployment packages:</p>
                    <ul class="help-list">
                        <li><strong>Quick Package:</strong> Perfect for standard deployments with default settings</li>
                        <li><strong>Advanced Builder:</strong> Full control over branding, users, and configuration</li>
                        <li><strong>System Checks:</strong> Ensure your environment meets all requirements</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Deployment Center Enhanced Styles */
    .deployment-center-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    /* Hero Section */
    .deployment-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

    .deployment-hero h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .deployment-hero p {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }

    .hero-stats {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .stat-item {
        text-align: center;
        background: rgba(255,255,255,0.1);
        padding: 1rem 1.5rem;
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }

    .stat-number {
        display: block;
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
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

    /* Main Grid Layout */
    .deployment-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 3rem;
    }

    /* Enhanced Cards */
    .deployment-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .deployment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    }

    .deployment-card .card-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .card-icon {
        font-size: 1.5rem;
    }

    .card-title h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-badge.success {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.warning {
        background: #fff3cd;
        color: #856404;
    }

    .deployment-card .card-body {
        padding: 1.5rem;
    }

    /* Progress Bars */
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        transition: width 0.3s ease;
    }

    /* Status Grid */
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .status-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        border-radius: 8px;
        transition: background-color 0.2s;
    }

    .status-item.status-success {
        background: #f8fff9;
        border: 1px solid #d4edda;
    }

    .status-item.status-error {
        background: #fff8f8;
        border: 1px solid #f8d7da;
    }

    .status-icon {
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .status-label {
        font-weight: 500;
        flex: 1;
    }

    .status-help {
        opacity: 0.6;
        cursor: help;
    }

    /* Requirements List */
    .requirements-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .requirement-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        border-radius: 8px;
        transition: background-color 0.2s;
    }

    .requirement-item.requirement-success {
        background: #f8fff9;
        border: 1px solid #d4edda;
    }

    .requirement-item.requirement-error {
        background: #fff8f8;
        border: 1px solid #f8d7da;
    }

    .requirement-icon {
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .requirement-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .requirement-label {
        font-weight: 500;
    }

    .requirement-status {
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 500;
    }

    .requirement-success .requirement-status {
        background: #d4edda;
        color: #155724;
    }

    .requirement-error .requirement-status {
        background: #f8d7da;
        color: #721c24;
    }

    /* Card Actions */
    .card-actions {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #e9ecef;
    }

    .inline-form {
        display: inline;
    }

    /* Section Headers */
    .section-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .section-header h2 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }

    .section-header p {
        color: #6c757d;
        font-size: 1.1rem;
    }

    /* Quick Actions Section */
    .quick-actions-section {
        margin-bottom: 3rem;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .action-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }

    .action-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        text-align: center;
        position: relative;
    }

    .action-icon {
        font-size: 2.5rem;
        display: block;
        margin-bottom: 0.5rem;
    }

    .action-header h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
    }

    .action-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
    }

    .action-badge.advanced {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #333;
    }

    .action-body {
        padding: 2rem;
    }

    .action-body p {
        margin-bottom: 1.5rem;
        color: #6c757d;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin-bottom: 2rem;
    }

    .feature-list li {
        padding: 0.5rem 0;
        color: #495057;
    }

    /* Enhanced Forms */
    .quick-form .form-group {
        margin-bottom: 1.5rem;
    }

    .quick-form label {
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
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-hint {
        display: block;
        margin-top: 0.25rem;
        color: #6c757d;
        font-size: 0.85rem;
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
        width: 100%;
        justify-content: center;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .btn-outline {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
    }

    .btn-outline:hover {
        background: #667eea;
        color: white;
    }

    .btn-icon {
        font-size: 1.1rem;
    }

    /* System Information */
    .system-info-section {
        margin-bottom: 3rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .info-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s;
    }

    .info-card:hover {
        transform: translateY(-2px);
    }

    .info-icon {
        font-size: 2rem;
        flex-shrink: 0;
    }

    .info-content h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: #495057;
    }

    .info-content p {
        margin: 0;
        color: #6c757d;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        word-break: break-all;
    }

    /* Help Section */
    .help-section {
        margin-bottom: 2rem;
    }

    .help-card {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
    }

    .help-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .help-icon {
        font-size: 2rem;
    }

    .help-header h3 {
        margin: 0;
        color: #495057;
    }

    .help-content p {
        color: #6c757d;
        margin-bottom: 1rem;
    }

    .help-list {
        list-style: none;
        padding: 0;
        text-align: left;
        max-width: 600px;
        margin: 0 auto;
    }

    .help-list li {
        padding: 0.5rem 0;
        color: #495057;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .deployment-grid,
        .actions-grid {
            grid-template-columns: 1fr;
        }

        .hero-stats {
            gap: 1rem;
        }

        .deployment-hero h1 {
            font-size: 2rem;
        }

        .deployment-hero p {
            font-size: 1rem;
        }

        .status-grid {
            grid-template-columns: 1fr;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

<?php renderPageEnd(); ?>