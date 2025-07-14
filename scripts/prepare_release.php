<?php
/**
 * Release Package Preparation Script
 * Creates a clean ZIP package for GitHub releases
 */

// Configuration
$version = '1.0.1'; // Update this for each release
$package_name = "archiving-system-v{$version}";
$output_dir = __DIR__ . '/../releases/';
$source_dir = __DIR__ . '/../';

// Files and directories to include
$include_files = [
    'api/',
    'assets/',
    'config/',
    'docs/',
    'includes/',
    'pages/',
    'database/',
    'index.php',
    'README.md',
    'CHANGELOG.md',
    '.htaccess'
];

// Files and directories to exclude
$exclude_patterns = [
    '.git',
    '.gitignore',
    'data/',
    'storage/documents/',
    'backups/',
    'releases/',
    'scripts/',
    'tmp_*',
    'php_error_log',
    'error_log.md',
    '*.log'
];

echo "🚀 Preparing release package for Archiving System v{$version}\n\n";

// Create output directory
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0755, true);
    echo "✅ Created releases directory\n";
}

$zip_file = $output_dir . $package_name . '.zip';

// Remove existing package if exists
if (file_exists($zip_file)) {
    unlink($zip_file);
    echo "🗑️  Removed existing package\n";
}

// Create ZIP archive
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
    die("❌ Cannot create ZIP file: {$zip_file}\n");
}

echo "📦 Creating ZIP package...\n";

// Add files to ZIP
$files_added = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    $file_path = $file->getRealPath();
    $relative_path = str_replace($source_dir, '', $file_path);
    $relative_path = str_replace('\\', '/', $relative_path); // Normalize path separators
    
    // Check if file should be excluded
    $should_exclude = false;
    foreach ($exclude_patterns as $pattern) {
        if (fnmatch($pattern, $relative_path) || strpos($relative_path, $pattern) === 0) {
            $should_exclude = true;
            break;
        }
    }
    
    if ($should_exclude) {
        continue;
    }
    
    // Check if file should be included
    $should_include = false;
    foreach ($include_files as $include) {
        if (strpos($relative_path, $include) === 0 || $relative_path === $include) {
            $should_include = true;
            break;
        }
    }
    
    if (!$should_include) {
        continue;
    }
    
    if ($file->isDir()) {
        $zip->addEmptyDir($package_name . '/' . $relative_path);
    } else {
        $zip->addFile($file_path, $package_name . '/' . $relative_path);
        $files_added++;
    }
}

// Add release information file
$release_info = [
    'version' => $version,
    'build_date' => date('Y-m-d H:i:s'),
    'package_name' => $package_name,
    'files_count' => $files_added
];

$zip->addFromString($package_name . '/RELEASE_INFO.json', json_encode($release_info, JSON_PRETTY_PRINT));

$zip->close();

echo "✅ Package created successfully!\n";
echo "📁 Location: {$zip_file}\n";
echo "📊 Files included: {$files_added}\n";
echo "💾 Package size: " . formatBytes(filesize($zip_file)) . "\n\n";

echo "🎯 Next Steps:\n";
echo "1. Upload {$package_name}.zip to your GitHub release\n";
echo "2. Update version in config/config.php to v{$version}\n";
echo "3. Test the update detection system\n";
echo "4. Publish the release on GitHub\n\n";

echo "🔗 GitHub Release URL: https://github.com/YOUR_USERNAME/YOUR_REPO/releases/new\n";

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>