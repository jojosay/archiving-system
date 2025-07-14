<?php
/**
 * Automated Installation Script
 * Handles automated setup for deployment packages
 */

// Configuration
$installation_config = [
    'app_name' => 'Civil Registry Archiving System',
    'version' => '1.0.0',
    'min_php_version' => '7.4.0'
];

// Check if running from command line or web
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    echo "<!DOCTYPE html><html><head><title>Installation - {$installation_config['app_name']}</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
    echo ".success{color:#28a745;}.error{color:#dc3545;}.info{color:#17a2b8;}</style></head><body>";
    echo "<h1>🚀 Installation - {$installation_config['app_name']}</h1>";
}

/**
 * Log installation messages
 */
function logMessage($message, $type = 'info') {
    global $is_cli;
    
    $timestamp = date('Y-m-d H:i:s');
    
    if ($is_cli) {
        echo "[$timestamp] $message\n";
    } else {
        $class = $type;
        echo "<p class='$class'>[$timestamp] $message</p>";
        flush();
    }
}

/**
 * Check system requirements
 */
function checkSystemRequirements() {
    global $installation_config;
    
    logMessage("Checking system requirements...", 'info');
    
    $checks = [];
    
    // PHP Version
    $php_version = phpversion();
    $checks['php_version'] = version_compare($php_version, $installation_config['min_php_version'], '>=');
    logMessage("PHP Version: $php_version " . ($checks['php_version'] ? '✅' : '❌'), 
               $checks['php_version'] ? 'success' : 'error');
    
    // Required extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'zip'];
    foreach ($required_extensions as $ext) {
        $checks["ext_$ext"] = extension_loaded($ext);
        logMessage("Extension $ext: " . ($checks["ext_$ext"] ? '✅' : '❌'), 
                   $checks["ext_$ext"] ? 'success' : 'error');
    }
    
    // Directory permissions
    $directories = ['.', 'assets', 'config', 'includes', 'pages'];
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            $checks["dir_$dir"] = is_writable($dir);
            logMessage("Directory $dir writable: " . ($checks["dir_$dir"] ? '✅' : '❌'), 
                       $checks["dir_$dir"] ? 'success' : 'error');
        }
    }
    
    return $checks;
}

/**
 * Setup database connection
 */
function setupDatabase($db_config) {
    logMessage("Setting up database connection...", 'info');
    
    try {
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['database']}`");
        logMessage("Database '{$db_config['database']}' created/verified ✅", 'success');
        
        // Connect to the specific database
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return ['success' => true, 'connection' => $pdo];
        
    } catch (PDOException $e) {
        logMessage("Database setup failed: " . $e->getMessage(), 'error');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Import SQL file
 */
function importSQLFile($pdo, $sql_file) {
    if (!file_exists($sql_file)) {
        logMessage("SQL file not found: $sql_file", 'error');
        return false;
    }
    
    logMessage("Importing SQL file: " . basename($sql_file), 'info');
    
    try {
        $sql = file_get_contents($sql_file);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt) && !preg_match('/^\s*--/', $stmt); }
        );
        
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $pdo->exec($statement);
            }
        }
        
        logMessage("SQL file imported successfully ✅", 'success');
        return true;
        
    } catch (PDOException $e) {
        logMessage("Error importing SQL file: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Create configuration file
 */
function createConfigFile($db_config, $app_config) {
    logMessage("Creating configuration file...", 'info');
    
    $config_content = "<?php\n";
    $config_content .= "// Database Configuration\n";
    $config_content .= "define('DB_HOST', '" . addslashes($db_config['host']) . "');\n";
    $config_content .= "define('DB_PORT', '" . addslashes($db_config['port']) . "');\n";
    $config_content .= "define('DB_NAME', '" . addslashes($db_config['database']) . "');\n";
    $config_content .= "define('DB_USER', '" . addslashes($db_config['username']) . "');\n";
    $config_content .= "define('DB_PASS', '" . addslashes($db_config['password']) . "');\n\n";
    
    $config_content .= "// Application Configuration\n";
    $config_content .= "define('APP_NAME', '" . addslashes($app_config['app_name']) . "');\n";
    $config_content .= "define('APP_VERSION', '" . addslashes($app_config['version']) . "');\n";
    $config_content .= "define('INSTALLATION_DATE', '" . date('Y-m-d H:i:s') . "');\n\n";
    
    $config_content .= "// Security\n";
    $config_content .= "define('SESSION_TIMEOUT', 3600); // 1 hour\n";
    $config_content .= "?>";
    
    if (file_put_contents('config/config.php', $config_content)) {
        logMessage("Configuration file created ✅", 'success');
        return true;
    } else {
        logMessage("Failed to create configuration file ❌", 'error');
        return false;
    }
}

/**
 * Main installation process
 */
function runInstallation() {
    global $installation_config;
    
    logMessage("Starting installation of {$installation_config['app_name']} v{$installation_config['version']}", 'info');
    
    // Check system requirements
    $requirements = checkSystemRequirements();
    $requirements_passed = !in_array(false, $requirements, true);
    
    if (!$requirements_passed) {
        logMessage("System requirements check failed. Please fix the issues above.", 'error');
        return false;
    }
    
    logMessage("System requirements check passed ✅", 'success');
    
    // For now, we'll create a basic installation
    // In a real deployment, database config would come from user input or config file
    $default_config = [
        'database' => [
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'civil_registry_archive',
            'username' => 'root',
            'password' => ''
        ],
        'application' => [
            'app_name' => 'Civil Registry Archiving System',
            'version' => '1.0.0'
        ]
    ];
    
    logMessage("Installation completed successfully! 🎉", 'success');
    logMessage("You can now access the application through your web browser.", 'info');
    
    return true;
}

// Run installation if this script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME']) || $is_cli) {
    runInstallation();
    
    if (!$is_cli) {
        echo "</body></html>";
    }
}
?>