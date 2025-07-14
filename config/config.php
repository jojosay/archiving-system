<?php
// Civil Registry Archiving System - Configuration File

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'civil_registry_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Archiving System');
define('APP_VERSION', '1.0.1');
define('BASE_URL', 'http://localhost/archiving-system/'); // Update this for your server

// Include Branding Configuration
require_once __DIR__ . '/branding.php';

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
define('STORAGE_PATH', __DIR__ . '/../storage/documents/');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');
?>