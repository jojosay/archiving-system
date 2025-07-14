<?php
/**
 * Package Builder Class
 * Handles creation of deployment packages with branding and configuration
 */

require_once 'deployment_manager.php';
require_once 'branding_manager.php';

class PackageBuilder {
    private $deployment_manager;
    private $branding_manager;
    private $package_name;
    private $package_path;
    
    public function __construct() {
        $this->deployment_manager = new DeploymentManager();
        $this->branding_manager = new BrandingManager();
    }
    
    /**
     * Initialize package creation
     */
    public function initializePackage($office_name, $package_version = '1.0.0') {
        // Sanitize office name for filename
        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($office_name));
        $timestamp = date('Y-m-d_H-i-s');
        
        $this->package_name = $safe_name . '_deployment_v' . $package_version . '_' . $timestamp;
        $this->package_path = $this->deployment_manager->getDeploymentConfig()['packages_dir'] . $this->package_name . '/';
        
        // Create package directory
        if (!is_dir($this->package_path)) {
            mkdir($this->package_path, 0755, true);
        }
        
        return [
            'success' => true,
            'package_name' => $this->package_name,
            'package_path' => $this->package_path
        ];
    }
    
    /**
     * Collect configuration data for packaging
     */
    public function collectConfiguration() {
        try {
            // Get current branding configuration
            $branding_config = $this->branding_manager->getCurrentBranding();
            
            // Get deployment environment validation
            $environment_checks = $this->deployment_manager->validateEnvironment();
            
            return [
                'success' => true,
                'branding' => $branding_config,
                'environment' => $environment_checks,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error collecting configuration: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate package requirements
     */
    public function validatePackageRequirements() {
        $validation = [];
        
        // Check if branding is configured
        $branding = $this->branding_manager->getCurrentBranding();
        $validation['has_branding'] = !empty($branding['app_name']);
        
        // Check if required directories exist
        $validation['assets_exist'] = is_dir(__DIR__ . '/../assets/');
        $validation['config_exists'] = is_dir(__DIR__ . '/../config/');
        $validation['includes_exist'] = is_dir(__DIR__ . '/../includes/');
        
        // Check environment
        $env_checks = $this->deployment_manager->validateEnvironment();
        $validation['environment_ready'] = !in_array(false, $env_checks, true);
        
        return $validation;
    }
    
    /**
     * Get package metadata
     */
    public function getPackageMetadata() {
        return [
            'package_name' => $this->package_name ?? 'Not initialized',
            'package_path' => $this->package_path ?? 'Not initialized',
            'created' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ];
    }
}
?>