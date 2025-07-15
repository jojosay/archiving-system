<?php
// First Install Setup Page
// No session or auth checks - this runs before system is configured

require_once __DIR__ . '/../includes/first_install_manager.php';
require_once __DIR__ . '/../includes/first_install_database.php';

// Set flag to indicate we're in installation process
$_SESSION['install_in_progress'] = true;

// Debug: Log what we receive
error_log("First Install Debug - GET params: " . print_r($_GET, true));
error_log("First Install Debug - POST params: " . print_r($_POST, true));
error_log("First Install Debug - Session: " . print_r($_SESSION, true));

$first_install_manager = new FirstInstallManager();

// If not first install, redirect to login (but only if we're not already in a redirect loop)
if (!$first_install_manager->isFirstInstall() && !isset($_GET['force_install'])) {
    header('Location: index.php?page=login');
    exit();
}

// Handle form submission
$message = '';
$message_type = '';
$step = $_GET['step'] ?? '1';

// Check session for completed steps to prevent skipping
if (!isset($_SESSION['install_step_completed'])) {
    $_SESSION['install_step_completed'] = [];
}

// Validate step progression - only check what's actually needed
if ($step == '3' && !isset($_SESSION['install_db_config'])) {
    $step = '1'; // Need database config to proceed to admin setup
} elseif ($step == '4' && (!isset($_SESSION['install_db_config']) || !isset($_SESSION['install_admin_config']))) {
    if (!isset($_SESSION['install_db_config'])) {
        $step = '1'; // Need database config
    } else {
        $step = '3'; // Need admin config
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'test_database':
                // Test database connection
                $db_host = $_POST['db_host'] ?? 'localhost';
                $db_name = $_POST['db_name'] ?? '';
                $db_user = $_POST['db_user'] ?? '';
                $db_pass = $_POST['db_pass'] ?? '';
                
                $db_setup = new FirstInstallDatabase($db_host, $db_user, $db_pass, $db_name);
                $test_result = $db_setup->testConnection();
                
                if ($test_result['success']) {
                    // Also create the database immediately
                    $create_result = $db_setup->createDatabase();
                    
                    if ($create_result['success']) {
                        // Store database config in session for next steps
                        $_SESSION['install_db_config'] = [
                            'host' => $db_host,
                            'name' => $db_name,
                            'user' => $db_user,
                            'pass' => $db_pass
                        ];
                        
                        // Mark step 1 as completed (avoid duplicates)
                        if (!in_array('1', $_SESSION['install_step_completed'])) {
                            $_SESSION['install_step_completed'][] = '1';
                        }
                        
                        $message = 'Database connection successful and database created!';
                        $message_type = 'success';
                        $step = '2';
                    } else {
                        $message = 'Database connection successful but failed to create database: ' . $create_result['message'];
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Database connection failed: ' . $test_result['message'];
                    $message_type = 'error';
                }
                break;
                
            case 'create_admin':
                // Create admin user
                $admin_username = $_POST['admin_username'] ?? '';
                $admin_password = $_POST['admin_password'] ?? '';
                $admin_email = $_POST['admin_email'] ?? '';
                
                if (empty($admin_username) || empty($admin_password)) {
                    $message = 'Username and password are required.';
                    $message_type = 'error';
                } else {
                    $_SESSION['install_admin_config'] = [
                        'username' => $admin_username,
                        'password' => $admin_password,
                        'email' => $admin_email
                    ];
                    
                    // Mark step 3 as completed (avoid duplicates)
                    if (!in_array('3', $_SESSION['install_step_completed'])) {
                        $_SESSION['install_step_completed'][] = '3';
                    }
                    
                    $message = 'Admin account configured successfully!';
                    $message_type = 'success';
                    $step = '4';
                }
                break;
                
            case 'complete_install':
                // Complete the installation
                if (isset($_SESSION['install_db_config']) && isset($_SESSION['install_admin_config'])) {
                    $db_config = $_SESSION['install_db_config'];
                    $admin_config = $_SESSION['install_admin_config'];
                    
                    $db_setup = new FirstInstallDatabase(
                        $db_config['host'],
                        $db_config['user'],
                        $db_config['pass'],
                        $db_config['name']
                    );
                    
                    // Database already created in step 1, now import schema first
                    $schema_result = $db_setup->importSchema();
                    if (!$schema_result['success']) {
                        $message = 'Schema import failed: ' . $schema_result['message'];
                        $message_type = 'error';
                        error_log("Schema import error: " . $schema_result['message']);
                        break;
                    } else {
                        error_log("Schema import successful: " . $schema_result['message']);
                    }
                    
                    // Now create admin user (after tables exist)
                    $admin_result = $db_setup->createAdminUser(
                        $admin_config['username'],
                        $admin_config['password'],
                        $admin_config['email']
                    );
                    if (!$admin_result['success']) {
                        $message = 'Admin user creation failed: ' . $admin_result['message'];
                        $message_type = 'error';
                        break;
                    }
                    
                    // Update config file with database settings
                    $config_updated = updateConfigFile($db_config);
                    if (!$config_updated) {
                        $message = 'Warning: Could not update config file. Please update manually.';
                        $message_type = 'error';
                    }
                    
                    // Mark installation as complete
                    $install_marked = $first_install_manager->markInstallComplete();
                    
                    // Clear session data
                    unset($_SESSION['install_db_config']);
                    unset($_SESSION['install_admin_config']);
                    unset($_SESSION['install_step_completed']);
                    unset($_SESSION['install_in_progress']);
                    
                    if ($install_marked) {
                        $message = 'Installation completed successfully! You can now log in to the system.';
                        $message_type = 'success';
                        $step = 'complete';
                    } else {
                        $message = 'Installation completed but could not create install flag file. Please check file permissions.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Installation data missing. Please start over.';
                    $message_type = 'error';
                    $step = '1';
                }
                break;
        }
    }
}

// Function to update config file
function updateConfigFile($db_config) {
    $config_file = __DIR__ . '/../config/config.php';
    $config_content = file_get_contents($config_file);
    
    if ($config_content === false) {
        return false;
    }
    
    // Replace database configuration
    $config_content = preg_replace(
        "/define\('DB_HOST', '[^']*'\);/",
        "define('DB_HOST', '" . addslashes($db_config['host']) . "');",
        $config_content
    );
    
    $config_content = preg_replace(
        "/define\('DB_NAME', '[^']*'\);/",
        "define('DB_NAME', '" . addslashes($db_config['name']) . "');",
        $config_content
    );
    
    $config_content = preg_replace(
        "/define\('DB_USER', '[^']*'\);/",
        "define('DB_USER', '" . addslashes($db_config['user']) . "');",
        $config_content
    );
    
    $config_content = preg_replace(
        "/define\('DB_PASS', '[^']*'\);/",
        "define('DB_PASS', '" . addslashes($db_config['pass']) . "');",
        $config_content
    );
    
    return file_put_contents($config_file, $config_content) !== false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Install Setup - Civil Registry Archiving System</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .install-header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .install-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .install-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .install-content {
            padding: 30px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #6c757d;
        }
        
        .step.active {
            background: #007bff;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .text-center {
            text-align: center;
        }
        
        .requirement-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .requirement-pass {
            color: #28a745;
        }
        
        .requirement-fail {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>First Install Setup</h1>
            <p>Welcome! Let's get your Civil Registry Archiving System configured.</p>
        </div>
        
        <div class="install-content">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">4</div>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Debug Info (temporary) -->
            <?php if (true): // Set to false to hide debug info ?>
                <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px; border-radius: 4px;">
                    <strong>Debug Info:</strong><br>
                    Requested Step: <?php echo $_GET['step'] ?? 'none'; ?><br>
                    Current Step: <?php echo $step; ?><br>
                    DB Config Set: <?php echo isset($_SESSION['install_db_config']) ? 'Yes' : 'No'; ?><br>
                    Admin Config Set: <?php echo isset($_SESSION['install_admin_config']) ? 'Yes' : 'No'; ?><br>
                    Completed Steps: <?php echo implode(', ', $_SESSION['install_step_completed'] ?? []); ?><br>
                    Step 3 Validation: <?php echo ($step == '3' && !isset($_SESSION['install_db_config'])) ? 'BLOCKED' : 'ALLOWED'; ?><br>
                </div>
            <?php endif; ?>
            
            <!-- Step Content -->
            <?php if ($step == '1'): ?>
                <h3>Step 1: Database Configuration</h3>
                <p>Please provide your database connection details:</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="test_database">
                    
                    <div class="form-group">
                        <label for="db_host">Database Host:</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name:</label>
                        <input type="text" id="db_name" name="db_name" value="civil_registry_db" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database Username:</label>
                        <input type="text" id="db_user" name="db_user" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password:</label>
                        <input type="password" id="db_pass" name="db_pass">
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Test Database Connection</button>
                    </div>
                </form>
                
            <?php elseif ($step == '2'): ?>
                <h3>Step 2: System Requirements Check</h3>
                <p>Checking your system requirements...</p>
                
                <?php
                // Check system requirements
                $requirements = [
                    'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                    'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
                    'JSON Extension' => extension_loaded('json'),
                    'ZIP Extension' => extension_loaded('zip'),
                    'GD Extension' => extension_loaded('gd'),
                    'cURL Extension' => extension_loaded('curl'),
                    'Write Permission (config/)' => is_writable(__DIR__ . '/../config/'),
                    'Write Permission (assets/)' => is_writable(__DIR__ . '/../assets/'),
                ];
                
                $all_passed = !in_array(false, $requirements, true);
                ?>
                
                <div style="margin: 20px 0;">
                    <?php foreach ($requirements as $requirement => $passed): ?>
                        <div class="requirement-item">
                            <span><?php echo $requirement; ?></span>
                            <span class="<?php echo $passed ? 'requirement-pass' : 'requirement-fail'; ?>">
                                <?php echo $passed ? 'Pass' : 'Fail'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($all_passed): ?>
                    <?php
                    // Mark step 2 as completed if requirements are met
                    if (!in_array('2', $_SESSION['install_step_completed'])) {
                        $_SESSION['install_step_completed'][] = '2';
                    }
                    ?>
                    <div class="alert alert-success">
                        All system requirements are met! You can proceed with the installation.
                    </div>
                    <div class="text-center">
                        <form method="GET" style="display: inline;">
                            <input type="hidden" name="page" value="first_install">
                            <input type="hidden" name="step" value="3">
                            <button type="submit" class="btn">Continue to Admin Setup</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">
                        Some system requirements are not met. Please fix the issues above before continuing.
                    </div>
                    <div class="text-center">
                        <a href="?step=2" class="btn">Recheck Requirements</a>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($step == '3'): ?>
                <h3>Step 3: Admin Account Setup</h3>
                <p>Create your administrator account:</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_admin">
                    
                    <div class="form-group">
                        <label for="admin_username">Admin Username:</label>
                        <input type="text" id="admin_username" name="admin_username" value="admin" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password:</label>
                        <input type="password" id="admin_password" name="admin_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Admin Email:</label>
                        <input type="email" id="admin_email" name="admin_email" required>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Create Admin Account</button>
                    </div>
                </form>
                
            <?php elseif ($step == '4'): ?>
                <h3>Step 4: Complete Installation</h3>
                <p>Ready to complete the installation!</p>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">
                    <h4>Installation Summary:</h4>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Database connection tested successfully</li>
                        <li>System requirements verified</li>
                        <li>Admin account configured</li>
                        <li>Ready to create database and import schema</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="complete_install">
                    <div class="text-center">
                        <button type="submit" class="btn btn-success">Complete Installation</button>
                    </div>
                </form>
                
            <?php elseif ($step == 'complete'): ?>
                <h3>Installation Complete!</h3>
                <div class="alert alert-success">
                    <strong>Congratulations!</strong> Your Civil Registry Archiving System has been successfully installed.
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;">
                    <h4>What's Next:</h4>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Database has been created and configured</li>
                        <li>Template management system initialized</li>
                        <li>Admin user account has been set up</li>
                        <li>System is ready for use</li>
                        <li>You can now log in and start using the system</li>
                    </ul>
                </div>
                
                <div style="background: #e3f2fd; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #2196f3;">
                    <h4>New Feature: Template Management</h4>
                    <p style="margin: 10px 0;">Your system now includes a powerful template management feature:</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Template Gallery</strong> - Browse and download DOCX, Excel, and PDF templates</li>
                        <li><strong>Categories</strong> - Organize templates by type (Forms, Letters, Reports, etc.)</li>
                        <li><strong>Search & Filter</strong> - Find templates quickly with advanced filtering</li>
                        <li><strong>Analytics</strong> - Track template usage and downloads</li>
                        <li><strong>Admin Tools</strong> - Upload and manage templates (Admin only)</li>
                    </ul>
                    <p style="margin: 10px 0; font-style: italic;">Access these features from the main navigation menu after logging in.</p>
                </div>
                
                <div class="text-center">
                    <a href="index.php?page=login" class="btn btn-success">Go to Login Page</a>
                </div>
                
                <script>
                // Auto-redirect after 3 seconds to ensure clean state
                setTimeout(function() {
                    window.location.href = 'index.php?page=login';
                }, 3000);
                </script>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>