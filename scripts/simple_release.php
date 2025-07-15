<?php
/**
 * Fixed Release Package Creator
 * Creates a ZIP file with all necessary files, excluding unwanted items
 */

$version = '1.0.6';
$package_name = "archiving-system-v{$version}";
$output_dir = __DIR__ . '/../releases/';
$source_dir = __DIR__ . '/../';

echo "Creating release package for Archiving System v{$version}\n\n";

// Create output directory
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0755, true);
    echo "Created releases directory\n";
}

$zip_file = $output_dir . $package_name . '.zip';

// Remove existing package if exists
if (file_exists($zip_file)) {
    unlink($zip_file);
    echo "Removed existing package\n";
}

// Create ZIP archive
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
    die("Cannot create ZIP file: {$zip_file}\n");
}

echo "Creating ZIP package...\n";

// Files and directories to exclude
$exclude_patterns = [
    '.git',
    'data/',
    'storage/documents/',
    'backups/',
    'releases/',
    'tmp_',
    '.log',
    'php_error_log',
    'acli.exe',
    'node_modules/',
    '.env',
    'composer.lock',
    'package-lock.json',
    '.DS_Store',
    'Thumbs.db'
];

// File extensions to exclude
$exclude_extensions = [
    '.exe',
    '.msi',
    '.dmg',
    '.deb',
    '.rpm'
];

$files_added = 0;

// Function to check if file should be excluded
function shouldExclude($path, $exclude_patterns, $exclude_extensions) {
    // Convert to forward slashes for consistent checking
    $normalized_path = str_replace('\\', '/', $path);
    
    // Check exclude patterns
    foreach ($exclude_patterns as $pattern) {
        if (strpos($normalized_path, $pattern) !== false) {
            return true;
        }
        
        // Check if it's an exact filename match
        if (basename($normalized_path) === $pattern) {
            return true;
        }
    }
    
    // Check file extensions
    foreach ($exclude_extensions as $ext) {
        if (substr($normalized_path, -strlen($ext)) === $ext) {
            return true;
        }
    }
    
    return false;
}

// Add files recursively
function addFilesToZip($zip, $source_dir, $package_name, $exclude_patterns, $exclude_extensions, &$files_added, $current_path = '') {
    $dir = $source_dir . $current_path;
    
    if (!is_dir($dir)) {
        return;
    }
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $file_path = $current_path . $file;
        $full_path = $source_dir . $file_path;
        
        // Check if should exclude
        if (shouldExclude($file_path, $exclude_patterns, $exclude_extensions)) {
            echo "Excluding: {$file_path}\n";
            continue;
        }
        
        if (is_dir($full_path)) {
            // Add directory
            $zip->addEmptyDir($package_name . '/' . $file_path);
            // Recursively add files in directory
            addFilesToZip($zip, $source_dir, $package_name, $exclude_patterns, $exclude_extensions, $files_added, $file_path . '/');
        } else {
            // Add file
            if ($zip->addFile($full_path, $package_name . '/' . $file_path)) {
                $files_added++;
                
                if ($files_added % 50 == 0) {
                    echo "Added {$files_added} files...\n";
                }
            } else {
                echo "Failed to add: {$file_path}\n";
            }
        }
    }
}

// Add all files
addFilesToZip($zip, $source_dir, $package_name, $exclude_patterns, $exclude_extensions, $files_added);

// Add release info
$release_info = [
    'version' => $version,
    'build_date' => date('Y-m-d H:i:s'),
    'package_name' => $package_name,
    'files_count' => $files_added,
    'excluded_patterns' => $exclude_patterns,
    'excluded_extensions' => $exclude_extensions
];

$zip->addFromString($package_name . '/RELEASE_INFO.json', json_encode($release_info, JSON_PRETTY_PRINT));

if ($zip->close()) {
    echo "Package created successfully!\n";
    echo "Location: {$zip_file}\n";
    echo "Files included: {$files_added}\n";
    echo "Package size: " . formatBytes(filesize($zip_file)) . "\n\n";
    
    echo "Next Steps:\n";
    echo "1. Upload {$package_name}.zip to your GitHub release\n";
    echo "2. Create release with tag v{$version}\n";
    echo "3. Test the update detection system\n\n";
} else {
    echo "Failed to create ZIP package!\n";
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

echo "Ready to upload to: https://github.com/jojosay/archiving-system/releases/new\n";
?>