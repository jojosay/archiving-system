<?php
/**
 * Branding Manager Class
 * Handles branding configuration, file uploads, and customization
 */

class BrandingManager {
    private $branding_dir;
    private $config_file;
    
    public function __construct() {
        $this->branding_dir = __DIR__ . '/../assets/branding/';
        $this->config_file = __DIR__ . '/../config/branding_custom.php';
        
        // Ensure branding directories exist
        $this->createBrandingDirectories();
    }
    
    /**
     * Create branding directory structure
     */
    private function createBrandingDirectories() {
        $directories = [
            $this->branding_dir,
            $this->branding_dir . 'logos/',
            $this->branding_dir . 'favicons/',
            $this->branding_dir . 'themes/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Get current branding configuration
     */
    public function getCurrentBranding() {
        return [
            'app_name' => defined('BRAND_APP_NAME') ? BRAND_APP_NAME : APP_NAME,
            'app_description' => defined('BRAND_APP_DESCRIPTION') ? BRAND_APP_DESCRIPTION : '',
            'app_tagline' => defined('BRAND_APP_TAGLINE') ? BRAND_APP_TAGLINE : '',
            'office_name' => defined('BRAND_OFFICE_NAME') ? BRAND_OFFICE_NAME : '',
            'office_department' => defined('BRAND_OFFICE_DEPARTMENT') ? BRAND_OFFICE_DEPARTMENT : '',
            'office_address' => defined('BRAND_OFFICE_ADDRESS') ? BRAND_OFFICE_ADDRESS : '',
            'office_phone' => defined('BRAND_OFFICE_PHONE') ? BRAND_OFFICE_PHONE : '',
            'office_email' => defined('BRAND_OFFICE_EMAIL') ? BRAND_OFFICE_EMAIL : '',
            'office_website' => defined('BRAND_OFFICE_WEBSITE') ? BRAND_OFFICE_WEBSITE : '',
            'primary_color' => defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#2C3E50',
            'secondary_color' => defined('BRAND_SECONDARY_COLOR') ? BRAND_SECONDARY_COLOR : '#F39C12',
            'accent_color' => defined('BRAND_ACCENT_COLOR') ? BRAND_ACCENT_COLOR : '#3498DB',
            'background_color' => defined('BRAND_BACKGROUND_COLOR') ? BRAND_BACKGROUND_COLOR : '#ECF0F1',
            'logo_primary' => defined('BRAND_LOGO_PRIMARY') ? BRAND_LOGO_PRIMARY : '',
            'favicon' => defined('BRAND_FAVICON') ? BRAND_FAVICON : '',
            'footer_text' => defined('BRAND_FOOTER_TEXT') ? BRAND_FOOTER_TEXT : '',
            'copyright_text' => defined('BRAND_COPYRIGHT_TEXT') ? BRAND_COPYRIGHT_TEXT : '',
            'show_logo' => defined('BRAND_SHOW_LOGO') ? BRAND_SHOW_LOGO : true,
            'show_tagline' => defined('BRAND_SHOW_TAGLINE') ? BRAND_SHOW_TAGLINE : true,
            'show_office_info' => defined('BRAND_SHOW_OFFICE_INFO') ? BRAND_SHOW_OFFICE_INFO : true
        ];
    }
    
    /**
     * Update branding configuration
     */
    public function updateBranding($branding_data) {
        try {
            // Validate required fields
            $required_fields = ['app_name', 'office_name'];
            foreach ($required_fields as $field) {
                if (empty($branding_data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ];
                }
            }
            
            // Generate custom branding configuration file
            $config_content = $this->generateBrandingConfig($branding_data);
            
            // Write configuration file
            if (file_put_contents($this->config_file, $config_content)) {
                return [
                    'success' => true,
                    'message' => 'Branding configuration updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to write branding configuration file'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating branding: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate branding configuration PHP file content
     */
    private function generateBrandingConfig($data) {
        $config = "<?php\n";
        $config .= "/**\n";
        $config .= " * Custom Branding Configuration\n";
        $config .= " * Generated on: " . date('Y-m-d H:i:s') . "\n";
        $config .= " */\n\n";
        
        // Application Information
        $config .= "// Application Information\n";
        $config .= "define('BRAND_APP_NAME', " . var_export($data['app_name'], true) . ");\n";
        $config .= "define('BRAND_APP_DESCRIPTION', " . var_export($data['app_description'] ?? '', true) . ");\n";
        $config .= "define('BRAND_APP_TAGLINE', " . var_export($data['app_tagline'] ?? '', true) . ");\n\n";
        
        // Office Information
        $config .= "// Office Information\n";
        $config .= "define('BRAND_OFFICE_NAME', " . var_export($data['office_name'], true) . ");\n";
        $config .= "define('BRAND_OFFICE_DEPARTMENT', " . var_export($data['office_department'] ?? '', true) . ");\n";
        $config .= "define('BRAND_OFFICE_ADDRESS', " . var_export($data['office_address'] ?? '', true) . ");\n";
        $config .= "define('BRAND_OFFICE_PHONE', " . var_export($data['office_phone'] ?? '', true) . ");\n";
        $config .= "define('BRAND_OFFICE_EMAIL', " . var_export($data['office_email'] ?? '', true) . ");\n";
        $config .= "define('BRAND_OFFICE_WEBSITE', " . var_export($data['office_website'] ?? '', true) . ");\n\n";
        
        // Visual Branding
        $config .= "// Visual Branding\n";
        $config .= "define('BRAND_PRIMARY_COLOR', " . var_export($data['primary_color'] ?? '#2C3E50', true) . ");\n";
        $config .= "define('BRAND_SECONDARY_COLOR', " . var_export($data['secondary_color'] ?? '#F39C12', true) . ");\n";
        $config .= "define('BRAND_ACCENT_COLOR', " . var_export($data['accent_color'] ?? '#3498DB', true) . ");\n";
        $config .= "define('BRAND_BACKGROUND_COLOR', " . var_export($data['background_color'] ?? '#ECF0F1', true) . ");\n\n";
        
        // Logo Settings
        $config .= "// Logo Settings\n";
        $config .= "define('BRAND_LOGO_PRIMARY', " . var_export($data['logo_primary'] ?? '', true) . ");\n";
        $config .= "define('BRAND_FAVICON', " . var_export($data['favicon'] ?? '', true) . ");\n\n";
        
        // Footer Information
        $config .= "// Footer Information\n";
        $config .= "define('BRAND_FOOTER_TEXT', " . var_export($data['footer_text'] ?? '', true) . ");\n";
        $config .= "define('BRAND_COPYRIGHT_TEXT', " . var_export($data['copyright_text'] ?? '', true) . ");\n\n";
        
        // Feature Flags
        $config .= "// Feature Flags\n";
        $config .= "define('BRAND_SHOW_LOGO', " . var_export(!empty($data['show_logo']), true) . ");\n";
        $config .= "define('BRAND_SHOW_TAGLINE', " . var_export(!empty($data['show_tagline']), true) . ");\n";
        $config .= "define('BRAND_SHOW_OFFICE_INFO', " . var_export(!empty($data['show_office_info']), true) . ");\n\n";
        
        // Deployment Information
        $config .= "// Deployment Information\n";
        $config .= "define('BRAND_DEPLOYMENT_ID', " . var_export($data['deployment_id'] ?? uniqid('deploy_'), true) . ");\n";
        $config .= "define('BRAND_DEPLOYMENT_DATE', " . var_export(date('Y-m-d'), true) . ");\n";
        $config .= "define('BRAND_DEPLOYMENT_VERSION', " . var_export($data['deployment_version'] ?? '1.0.0', true) . ");\n";
        
        $config .= "?>";
        
        return $config;
    }
    
    /**
     * Upload logo file
     */
    public function uploadLogo($file, $type = 'primary') {
        try {
            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
            if (!in_array($file['type'], $allowed_types)) {
                return [
                    'success' => false,
                    'message' => 'Invalid file type. Only JPG, PNG, GIF, and SVG files are allowed.'
                ];
            }
            
            // Check file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                return [
                    'success' => false,
                    'message' => 'File size too large. Maximum size is 2MB.'
                ];
            }
            
            // Generate filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $type . '_logo_' . time() . '.' . $extension;
            $upload_path = $this->branding_dir . 'logos/' . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                return [
                    'success' => true,
                    'message' => 'Logo uploaded successfully',
                    'filename' => $filename,
                    'path' => 'assets/branding/logos/' . $filename
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to upload logo file'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error uploading logo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Export branding configuration for deployment
     */
    public function exportBrandingConfig() {
        try {
            $branding = $this->getCurrentBranding();
            $export_data = [
                'branding' => $branding,
                'export_date' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ];
            
            return [
                'success' => true,
                'data' => $export_data,
                'filename' => 'branding_config_' . date('Y-m-d_H-i-s') . '.json'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error exporting branding configuration: ' . $e->getMessage()
            ];
        }
    }
}
?>