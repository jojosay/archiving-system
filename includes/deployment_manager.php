<?php
/**
 * Deployment Manager Class
 * Core deployment functionality for packaging and distributing the application
 */

class DeploymentManager {
    private $deployment_dir;
    private $packages_dir;
    private $templates_dir;
    private $scripts_dir;
    
    public function __construct() {
        $this->deployment_dir = __DIR__ . '/../deployment/';
        $this->packages_dir = $this->deployment_dir . 'packages/';
        $this->templates_dir = $this->deployment_dir . 'templates/';
        $this->scripts_dir = $this->deployment_dir . 'scripts/';
        
        // Ensure deployment directories exist
        $this->createDeploymentDirectories();
    }
    
    /**
     * Create deployment directory structure
     */
    private function createDeploymentDirectories() {
        $directories = [
            $this->deployment_dir,
            $this->packages_dir,
            $this->templates_dir,
            $this->scripts_dir,
            $this->deployment_dir . 'documentation/',
            $this->deployment_dir . 'office_registry/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Get deployment configuration
     */
    public function getDeploymentConfig() {
        return [
            'deployment_dir' => $this->deployment_dir,
            'packages_dir' => $this->packages_dir,
            'templates_dir' => $this->templates_dir,
            'scripts_dir' => $this->scripts_dir,
            'version' => '1.0.0',
            'created' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Validate deployment environment
     */
    public function validateEnvironment() {
        $checks = [];
        
        // Check if directories are writable
        $checks['deployment_writable'] = is_writable($this->deployment_dir);
        $checks['packages_writable'] = is_writable($this->packages_dir);
        
        // Check PHP extensions
        $checks['zip_extension'] = extension_loaded('zip');
        $checks['json_extension'] = extension_loaded('json');
        
        // Check available disk space (minimum 100MB)
        $checks['disk_space'] = disk_free_space($this->deployment_dir) > (100 * 1024 * 1024);
        
        return $checks;
    }
}
?>