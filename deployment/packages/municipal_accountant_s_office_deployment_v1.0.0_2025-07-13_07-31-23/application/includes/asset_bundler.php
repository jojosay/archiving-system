<?php
/**
 * Asset Bundler Class
 * Handles collection and bundling of branding assets for deployment packages
 */

require_once 'branding_manager.php';

class AssetBundler {
    private $branding_manager;
    private $assets_dir;
    
    public function __construct() {
        $this->branding_manager = new BrandingManager();
        $this->assets_dir = __DIR__ . '/../assets/';
    }
    
    /**
     * Bundle all branding assets for deployment
     */
    public function bundleBrandingAssets($package_path) {
        try {
            $branding_path = $package_path . 'branding/';
            
            // Create branding directories in package
            $this->createBrandingDirectories($branding_path);
            
            $results = [];
            
            // Bundle logos
            $results['logos'] = $this->bundleLogos($branding_path);
            
            // Bundle favicons
            $results['favicons'] = $this->bundleFavicons($branding_path);
            
            // Bundle themes
            $results['themes'] = $this->bundleThemes($branding_path);
            
            // Bundle custom CSS
            $results['custom_css'] = $this->bundleCustomCSS($branding_path);
            
            // Create branding configuration file
            $results['config'] = $this->createBrandingConfig($branding_path);
            
            return [
                'success' => true,
                'results' => $results,
                'message' => 'Branding assets bundled successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error bundling branding assets: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create branding directory structure in package
     */
    private function createBrandingDirectories($branding_path) {
        $directories = [
            $branding_path,
            $branding_path . 'logos/',
            $branding_path . 'favicons/',
            $branding_path . 'themes/',
            $branding_path . 'backgrounds/',
            $branding_path . 'custom/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Bundle logo files
     */
    private function bundleLogos($branding_path) {
        $logos_source = $this->assets_dir . 'branding/logos/';
        $logos_dest = $branding_path . 'logos/';
        
        return $this->copyAssetFiles($logos_source, $logos_dest, 'logos');
    }
    
    /**
     * Bundle favicon files
     */
    private function bundleFavicons($branding_path) {
        $favicons_source = $this->assets_dir . 'branding/favicons/';
        $favicons_dest = $branding_path . 'favicons/';
        
        return $this->copyAssetFiles($favicons_source, $favicons_dest, 'favicons');
    }
    
    /**
     * Bundle theme files
     */
    private function bundleThemes($branding_path) {
        $themes_source = $this->assets_dir . 'branding/themes/';
        $themes_dest = $branding_path . 'themes/';
        
        return $this->copyAssetFiles($themes_source, $themes_dest, 'themes');
    }
    
    /**
     * Bundle custom CSS files
     */
    private function bundleCustomCSS($branding_path) {
        $css_source = $this->assets_dir . 'css/custom/';
        $css_dest = $branding_path . 'custom/';
        
        return $this->copyAssetFiles($css_source, $css_dest, 'custom CSS');
    }
    
    /**
     * Copy asset files from source to destination
     */
    private function copyAssetFiles($source_dir, $dest_dir, $asset_type) {
        $copied_files = [];
        $errors = [];
        
        if (!is_dir($source_dir)) {
            return [
                'success' => true,
                'files' => [],
                'message' => "No $asset_type directory found (this is normal for new installations)"
            ];
        }
        
        $files = glob($source_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $dest_file = $dest_dir . $filename;
                
                if (copy($file, $dest_file)) {
                    $copied_files[] = $filename;
                } else {
                    $errors[] = "Failed to copy $filename";
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'files' => $copied_files,
            'errors' => $errors,
            'message' => count($copied_files) . " $asset_type files copied"
        ];
    }
    
    /**
     * Create branding configuration file
     */
    private function createBrandingConfig($branding_path) {
        try {
            $branding_data = $this->branding_manager->getCurrentBranding();
            
            $config = [
                'branding' => $branding_data,
                'export_date' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'assets' => [
                    'logos_path' => 'branding/logos/',
                    'favicons_path' => 'branding/favicons/',
                    'themes_path' => 'branding/themes/',
                    'custom_css_path' => 'branding/custom/'
                ]
            ];
            
            $config_file = $branding_path . 'branding_config.json';
            file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
            
            return [
                'success' => true,
                'file' => $config_file,
                'message' => 'Branding configuration created'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating branding configuration: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Bundle application assets (CSS, JS, etc.)
     */
    public function bundleApplicationAssets($package_path) {
        try {
            $app_assets_path = $package_path . 'application/assets/';
            
            // Create assets directories
            if (!is_dir($app_assets_path)) {
                mkdir($app_assets_path, 0755, true);
            }
            
            $results = [];
            
            // Copy CSS files
            $results['css'] = $this->copyDirectory(
                $this->assets_dir . 'css/',
                $app_assets_path . 'css/'
            );
            
            // Copy JS files
            $results['js'] = $this->copyDirectory(
                $this->assets_dir . 'js/',
                $app_assets_path . 'js/'
            );
            
            // Copy fonts
            $results['fonts'] = $this->copyDirectory(
                $this->assets_dir . 'fonts/',
                $app_assets_path . 'fonts/'
            );
            
            return [
                'success' => true,
                'results' => $results,
                'message' => 'Application assets bundled successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error bundling application assets: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Copy entire directory recursively
     */
    private function copyDirectory($source, $destination) {
        if (!is_dir($source)) {
            return [
                'success' => true,
                'message' => 'Source directory does not exist: ' . basename($source)
            ];
        }
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $copied_files = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $dest_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($dest_path)) {
                    mkdir($dest_path, 0755, true);
                }
            } else {
                copy($item, $dest_path);
                $copied_files++;
            }
        }
        
        return [
            'success' => true,
            'files_copied' => $copied_files,
            'message' => "$copied_files files copied from " . basename($source)
        ];
    }
}
?>