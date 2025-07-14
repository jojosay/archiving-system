<?php
/**
 * Favicon Manager Class
 * Handles favicon upload, generation, and management
 */

class FaviconManager {
    private $favicon_dir;
    private $supported_sizes;
    
    public function __construct() {
        $this->favicon_dir = __DIR__ . '/../assets/branding/favicons/';
        $this->supported_sizes = [16, 32, 48, 64, 128];
        
        // Ensure favicon directory exists
        if (!is_dir($this->favicon_dir)) {
            mkdir($this->favicon_dir, 0755, true);
        }
    }
    
    /**
     * Upload and process favicon
     */
    public function uploadFavicon($file) {
        try {
            // Validate file
            $allowed_types = ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'];
            if (!in_array($file['type'], $allowed_types)) {
                return [
                    'success' => false,
                    'message' => 'Invalid file type. Only PNG and ICO files are allowed for favicons.'
                ];
            }
            
            // Check file size (max 1MB)
            if ($file['size'] > 1024 * 1024) {
                return [
                    'success' => false,
                    'message' => 'File size too large. Maximum size is 1MB.'
                ];
            }
            
            // Process the uploaded file
            $result = $this->processFaviconFile($file);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Favicon uploaded and processed successfully',
                    'files' => $result['files']
                ];
            } else {
                return $result;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error uploading favicon: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process favicon file and generate multiple sizes
     */
    private function processFaviconFile($file) {
        try {
            $generated_files = [];
            
            // Handle ICO files
            if ($file['type'] === 'image/x-icon' || $file['type'] === 'image/vnd.microsoft.icon') {
                $ico_path = $this->favicon_dir . 'favicon.ico';
                if (move_uploaded_file($file['tmp_name'], $ico_path)) {
                    $generated_files['ico'] = 'assets/branding/favicons/favicon.ico';
                }
            }
            
            // Handle PNG files - generate multiple sizes
            if ($file['type'] === 'image/png') {
                $temp_path = $file['tmp_name'];
                
                // Get image info
                $image_info = getimagesize($temp_path);
                if (!$image_info) {
                    return [
                        'success' => false,
                        'message' => 'Invalid image file'
                    ];
                }
                
                // Create image resource
                $source_image = imagecreatefrompng($temp_path);
                if (!$source_image) {
                    return [
                        'success' => false,
                        'message' => 'Failed to process PNG image'
                    ];
                }
                
                // Generate favicons in different sizes
                foreach ($this->supported_sizes as $size) {
                    $filename = "favicon-{$size}.png";
                    $filepath = $this->favicon_dir . $filename;
                    
                    if ($this->generateFaviconSize($source_image, $filepath, $size)) {
                        $generated_files["png_{$size}"] = "assets/branding/favicons/{$filename}";
                    }
                }
                
                // Also save original as main favicon
                $main_favicon = $this->favicon_dir . 'favicon.png';
                if (copy($temp_path, $main_favicon)) {
                    $generated_files['main'] = 'assets/branding/favicons/favicon.png';
                }
                
                imagedestroy($source_image);
            }
            
            if (!empty($generated_files)) {
                return [
                    'success' => true,
                    'files' => $generated_files
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to generate favicon files'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error processing favicon: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate favicon of specific size
     */
    private function generateFaviconSize($source_image, $output_path, $size) {
        try {
            // Create new image with target size
            $resized_image = imagecreatetruecolor($size, $size);
            
            // Enable transparency
            imagealphablending($resized_image, false);
            imagesavealpha($resized_image, true);
            $transparent = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
            imagefill($resized_image, 0, 0, $transparent);
            
            // Get source dimensions
            $source_width = imagesx($source_image);
            $source_height = imagesy($source_image);
            
            // Resize image
            imagecopyresampled(
                $resized_image, $source_image,
                0, 0, 0, 0,
                $size, $size,
                $source_width, $source_height
            );
            
            // Save as PNG
            $result = imagepng($resized_image, $output_path, 9);
            imagedestroy($resized_image);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error generating favicon size {$size}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available favicon files
     */
    public function getAvailableFavicons() {
        $favicons = [];
        
        // Check for ICO file
        if (file_exists($this->favicon_dir . 'favicon.ico')) {
            $favicons['ico'] = [
                'path' => 'assets/branding/favicons/favicon.ico',
                'size' => 'multi',
                'type' => 'ico'
            ];
        }
        
        // Check for PNG files
        foreach ($this->supported_sizes as $size) {
            $filename = "favicon-{$size}.png";
            if (file_exists($this->favicon_dir . $filename)) {
                $favicons["png_{$size}"] = [
                    'path' => "assets/branding/favicons/{$filename}",
                    'size' => $size,
                    'type' => 'png'
                ];
            }
        }
        
        // Check for main favicon
        if (file_exists($this->favicon_dir . 'favicon.png')) {
            $favicons['main'] = [
                'path' => 'assets/branding/favicons/favicon.png',
                'size' => 'original',
                'type' => 'png'
            ];
        }
        
        return $favicons;
    }
    
    /**
     * Generate favicon HTML tags
     */
    public function generateFaviconHTML() {
        $favicons = $this->getAvailableFavicons();
        $html = '';
        
        // Standard favicon
        if (isset($favicons['ico'])) {
            $html .= '<link rel="icon" type="image/x-icon" href="' . $favicons['ico']['path'] . '">' . "\n";
        } elseif (isset($favicons['main'])) {
            $html .= '<link rel="icon" type="image/png" href="' . $favicons['main']['path'] . '">' . "\n";
        }
        
        // PNG favicons for different sizes
        foreach ($this->supported_sizes as $size) {
            if (isset($favicons["png_{$size}"])) {
                $html .= '<link rel="icon" type="image/png" sizes="' . $size . 'x' . $size . '" href="' . $favicons["png_{$size}"]['path'] . '">' . "\n";
            }
        }
        
        // Apple touch icon (use largest available)
        $largest_favicon = null;
        foreach (array_reverse($this->supported_sizes) as $size) {
            if (isset($favicons["png_{$size}"])) {
                $largest_favicon = $favicons["png_{$size}"];
                break;
            }
        }
        
        if ($largest_favicon) {
            $html .= '<link rel="apple-touch-icon" href="' . $largest_favicon['path'] . '">' . "\n";
        }
        
        return $html;
    }
    
    /**
     * Delete all favicon files
     */
    public function deleteFavicons() {
        try {
            $files = glob($this->favicon_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            return [
                'success' => true,
                'message' => 'All favicon files deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting favicon files: ' . $e->getMessage()
            ];
        }
    }
}
?>