<?php
/**
 * Icon Manager Class
 * Handles local SVG icon loading and management
 */

class IconManager {
    private $icons_path;
    private $cache = [];
    
    public function __construct() {
        $this->icons_path = __DIR__ . '/../assets/icons/feather/';
    }
    
    /**
     * Get SVG icon content
     */
    public function getIcon($icon_name, $size = 24, $class = '', $style = '') {
        // Check cache first
        $cache_key = $icon_name . '_' . $size;
        if (isset($this->cache[$cache_key])) {
            $svg = $this->cache[$cache_key];
        } else {
            $svg = $this->loadIconSvg($icon_name);
            if ($svg) {
                $this->cache[$cache_key] = $svg;
            }
        }
        
        if (!$svg) {
            return $this->getFallbackIcon($size, $class, $style);
        }
        
        // Modify SVG attributes
        $svg = $this->modifySvgAttributes($svg, $size, $class, $style);
        
        return $svg;
    }
    
    /**
     * Load SVG content from file
     */
    private function loadIconSvg($icon_name) {
        $file_path = $this->icons_path . $icon_name . '.svg';
        
        if (!file_exists($file_path)) {
            return null;
        }
        
        return file_get_contents($file_path);
    }
    
    /**
     * Modify SVG attributes for size, class, and style
     */
    private function modifySvgAttributes($svg, $size, $class, $style) {
        // Update width and height
        $svg = preg_replace('/width="[^"]*"/', 'width="' . $size . '"', $svg);
        $svg = preg_replace('/height="[^"]*"/', 'height="' . $size . '"', $svg);
        
        // Add class if provided
        if ($class) {
            $svg = str_replace('<svg', '<svg class="' . htmlspecialchars($class) . '"', $svg);
        }
        
        // Add style if provided
        if ($style) {
            $svg = str_replace('<svg', '<svg style="' . htmlspecialchars($style) . '"', $svg);
        }
        
        return $svg;
    }
    
    /**
     * Get fallback icon when requested icon is not found
     */
    private function getFallbackIcon($size, $class, $style) {
        $fallback_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
        
        if ($class) {
            $fallback_svg .= ' class="' . htmlspecialchars($class) . '"';
        }
        
        if ($style) {
            $fallback_svg .= ' style="' . htmlspecialchars($style) . '"';
        }
        
        $fallback_svg .= '><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>';
        
        return $fallback_svg;
    }
    
    /**
     * Check if icon exists
     */
    public function iconExists($icon_name) {
        $file_path = $this->icons_path . $icon_name . '.svg';
        return file_exists($file_path);
    }
    
    /**
     * Get list of available icons
     */
    public function getAvailableIcons() {
        $icons = [];
        $files = glob($this->icons_path . '*.svg');
        
        foreach ($files as $file) {
            $icon_name = basename($file, '.svg');
            $icons[$icon_name] = ucwords(str_replace(['-', '_'], ' ', $icon_name));
        }
        
        return $icons;
    }
    
    /**
     * Get categorized icons for better organization
     */
    public function getCategorizedIcons() {
        return [
            'Business & Office' => [
                'briefcase' => 'Briefcase',
                'building' => 'Building',
                'users' => 'Users',
                'dollar-sign' => 'Finance',
                'trending-up' => 'Growth',
                'bar-chart' => 'Analytics'
            ],
            'Documents & Files' => [
                'folder' => 'Folder',
                'file-text' => 'Document',
                'file' => 'File',
                'book' => 'Book',
                'clipboard' => 'Clipboard',
                'layers' => 'Layers'
            ],
            'Communication' => [
                'mail' => 'Email',
                'phone' => 'Phone',
                'message-square' => 'Message'
            ],
            'Administrative' => [
                'settings' => 'Settings',
                'calendar' => 'Calendar',
                'clock' => 'Time',
                'tag' => 'Tag',
                'grid' => 'Grid',
                'list' => 'List'
            ],
            'Legal & Compliance' => [
                'shield' => 'Security',
                'award' => 'Award',
                'star' => 'Star',
                'check-circle' => 'Approved'
            ],
            'General' => [
                'home' => 'Home',
                'image' => 'Image',
                'map' => 'Location',
                'package' => 'Package',
                'printer' => 'Print',
                'edit' => 'Edit',
                'database' => 'Database'
            ]
        ];
    }
}
?>