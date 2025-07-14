<?php
/**
 * Branding Configuration
 * Customizable branding settings for multi-office deployment
 */

// Load custom branding if exists, otherwise use defaults
$custom_branding_file = __DIR__ . '/branding_custom.php';
if (file_exists($custom_branding_file)) {
    include $custom_branding_file;
} else {
    include __DIR__ . '/branding_default.php';
}

// Branding validation function
function validateBrandingConfig($config) {
    $required_fields = ['app_name', 'app_description', 'office_name'];
    
    foreach ($required_fields as $field) {
        if (empty($config[$field])) {
            return false;
        }
    }
    
    return true;
}

// Get branding asset path
function getBrandingAssetPath($asset_type, $filename = null) {
    $base_path = 'assets/branding/';
    
    switch ($asset_type) {
        case 'logo':
            return $base_path . 'logos/' . ($filename ?: 'logo.png');
        case 'favicon':
            return $base_path . 'favicons/' . ($filename ?: 'favicon.ico');
        case 'theme':
            return $base_path . 'themes/' . ($filename ?: 'custom.css');
        default:
            return $base_path . $asset_type . '/' . $filename;
    }
}

// Check if custom branding asset exists
function brandingAssetExists($asset_type, $filename = null) {
    $path = getBrandingAssetPath($asset_type, $filename);
    return file_exists($path);
}
?>