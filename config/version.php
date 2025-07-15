<?php
// Application Version Configuration
// Only define if not already defined in config.php
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.6');
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Archiving System');
}
define('APP_BUILD', '2025.01.15.006');

// GitHub Repository Configuration
define('GITHUB_REPO_OWNER', 'jojosay'); // Replace with your GitHub username
define('GITHUB_REPO_NAME', 'archiving-system'); // Replace with your repository name
define('GITHUB_API_BASE', 'https://api.github.com');

// Update Configuration
define('UPDATE_CHECK_INTERVAL', 86400); // 24 hours in seconds
define('UPDATE_CACHE_FILE', 'data/update_cache.json');
define('BACKUP_BEFORE_UPDATE', true);

// Version comparison helper
function compareVersions($version1, $version2) {
    return version_compare($version1, $version2);
}

// Get current version info
function getCurrentVersionInfo() {
    return [
        'version' => defined('APP_VERSION') ? APP_VERSION : '1.0.0',
        'build' => defined('APP_BUILD') ? APP_BUILD : '2025.01.14.001',
        'name' => defined('APP_NAME') ? APP_NAME : 'Archiving System',
        'timestamp' => time()
    ];
}
?>