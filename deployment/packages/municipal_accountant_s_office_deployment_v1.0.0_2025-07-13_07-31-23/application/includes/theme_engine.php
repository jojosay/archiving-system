<?php
/**
 * Theme Engine Class
 * Handles advanced theming, CSS generation, and theme management
 */

class ThemeEngine {
    private $themes_dir;
    private $generated_dir;
    private $templates_dir;
    
    public function __construct() {
        $this->themes_dir = __DIR__ . '/../assets/branding/themes/';
        $this->generated_dir = $this->themes_dir . 'generated/';
        $this->templates_dir = $this->themes_dir . 'templates/';
        
        // Ensure directories exist
        $this->createThemeDirectories();
    }
    
    /**
     * Create theme directory structure
     */
    private function createThemeDirectories() {
        $directories = [
            $this->themes_dir,
            $this->generated_dir,
            $this->templates_dir,
            $this->themes_dir . 'custom/',
            $this->themes_dir . 'backgrounds/',
            $this->themes_dir . 'patterns/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Generate custom CSS from theme configuration
     */
    public function generateThemeCSS($theme_config) {
        try {
            $css = $this->buildCustomCSS($theme_config);
            
            // Generate filename
            $theme_id = $theme_config['theme_id'] ?? 'custom_' . time();
            $filename = "theme_{$theme_id}.css";
            $filepath = $this->generated_dir . $filename;
            
            // Write CSS file
            if (file_put_contents($filepath, $css)) {
                return [
                    'success' => true,
                    'message' => 'Theme CSS generated successfully',
                    'filename' => $filename,
                    'path' => "assets/branding/themes/generated/{$filename}"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to write theme CSS file'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error generating theme CSS: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Build custom CSS from configuration
     */
    private function buildCustomCSS($config) {
        $css = "/* Custom Theme CSS - Generated on " . date('Y-m-d H:i:s') . " */\n\n";
        
        // CSS Variables
        $css .= ":root {\n";
        $css .= $this->generateCSSVariables($config);
        $css .= "}\n\n";
        
        // Base styles
        $css .= $this->generateBaseStyles($config);
        
        // Layout styles
        $css .= $this->generateLayoutStyles($config);
        
        // Component styles
        $css .= $this->generateComponentStyles($config);
        
        // Typography styles
        $css .= $this->generateTypographyStyles($config);
        
        // Color scheme styles
        $css .= $this->generateColorStyles($config);
        
        // Custom CSS
        if (!empty($config['custom_css'])) {
            $css .= "\n/* Custom CSS */\n";
            $css .= $config['custom_css'] . "\n";
        }
        
        return $css;
    }
    
    /**
     * Generate CSS variables
     */
    private function generateCSSVariables($config) {
        $variables = '';
        
        // Colors
        $variables .= "  /* Color Variables */\n";
        $variables .= "  --primary-color: " . ($config['primary_color'] ?? '#2C3E50') . ";\n";
        $variables .= "  --secondary-color: " . ($config['secondary_color'] ?? '#F39C12') . ";\n";
        $variables .= "  --accent-color: " . ($config['accent_color'] ?? '#3498DB') . ";\n";
        $variables .= "  --background-color: " . ($config['background_color'] ?? '#ECF0F1') . ";\n";
        $variables .= "  --text-color: " . ($config['text_color'] ?? '#2C3E50') . ";\n";
        $variables .= "  --sidebar-color: " . ($config['sidebar_color'] ?? '#2C3E50') . ";\n";
        
        // Success, Warning, Error colors
        $variables .= "  --success-color: " . ($config['success_color'] ?? '#27AE60') . ";\n";
        $variables .= "  --warning-color: " . ($config['warning_color'] ?? '#F39C12') . ";\n";
        $variables .= "  --error-color: " . ($config['error_color'] ?? '#E74C3C') . ";\n";
        
        // Typography
        $variables .= "\n  /* Typography Variables */\n";
        $variables .= "  --font-family: " . ($config['font_family'] ?? "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif") . ";\n";
        $variables .= "  --font-size-base: " . ($config['font_size_base'] ?? '1rem') . ";\n";
        $variables .= "  --font-size-small: " . ($config['font_size_small'] ?? '0.875rem') . ";\n";
        $variables .= "  --font-size-large: " . ($config['font_size_large'] ?? '1.125rem') . ";\n";
        $variables .= "  --line-height: " . ($config['line_height'] ?? '1.6') . ";\n";
        
        // Layout
        $variables .= "\n  /* Layout Variables */\n";
        $variables .= "  --sidebar-width: " . ($config['sidebar_width'] ?? '250px') . ";\n";
        $variables .= "  --header-height: " . ($config['header_height'] ?? 'auto') . ";\n";
        $variables .= "  --content-padding: " . ($config['content_padding'] ?? '2rem') . ";\n";
        $variables .= "  --border-radius: " . ($config['border_radius'] ?? '8px') . ";\n";
        $variables .= "  --box-shadow: " . ($config['box_shadow'] ?? '0 2px 4px rgba(0,0,0,0.1)') . ";\n";
        
        return $variables;
    }
    
    /**
     * Generate base styles
     */
    private function generateBaseStyles($config) {
        $css = "/* Base Styles */\n";
        $css .= "body {\n";
        $css .= "  font-family: var(--font-family);\n";
        $css .= "  font-size: var(--font-size-base);\n";
        $css .= "  line-height: var(--line-height);\n";
        $css .= "  color: var(--text-color);\n";
        $css .= "  background-color: var(--background-color);\n";
        $css .= "}\n\n";
        
        return $css;
    }
    
    /**
     * Generate layout styles
     */
    private function generateLayoutStyles($config) {
        $css = "/* Layout Styles */\n";
        $css .= ".sidebar {\n";
        $css .= "  width: var(--sidebar-width);\n";
        $css .= "  background: var(--sidebar-color);\n";
        $css .= "}\n\n";
        
        $css .= ".main-content {\n";
        $css .= "  margin-left: var(--sidebar-width);\n";
        $css .= "  padding: var(--content-padding);\n";
        $css .= "}\n\n";
        
        return $css;
    }
    
    /**
     * Generate component styles
     */
    private function generateComponentStyles($config) {
        $css = "/* Component Styles */\n";
        $css .= ".page-header {\n";
        $css .= "  background: white;\n";
        $css .= "  border-radius: var(--border-radius);\n";
        $css .= "  box-shadow: var(--box-shadow);\n";
        $css .= "}\n\n";
        
        $css .= ".nav-item:hover {\n";
        $css .= "  border-left-color: var(--secondary-color);\n";
        $css .= "}\n\n";
        
        $css .= ".nav-item.active {\n";
        $css .= "  border-left-color: var(--secondary-color);\n";
        $css .= "}\n\n";
        
        return $css;
    }
    
    /**
     * Generate typography styles
     */
    private function generateTypographyStyles($config) {
        $css = "/* Typography Styles */\n";
        $css .= "h1, h2, h3, h4, h5, h6 {\n";
        $css .= "  color: var(--primary-color);\n";
        $css .= "}\n\n";
        
        $css .= ".text-primary { color: var(--primary-color); }\n";
        $css .= ".text-secondary { color: var(--secondary-color); }\n";
        $css .= ".text-accent { color: var(--accent-color); }\n\n";
        
        return $css;
    }
    
    /**
     * Generate color styles
     */
    private function generateColorStyles($config) {
        $css = "/* Color Styles */\n";
        $css .= ".bg-primary { background-color: var(--primary-color); }\n";
        $css .= ".bg-secondary { background-color: var(--secondary-color); }\n";
        $css .= ".bg-accent { background-color: var(--accent-color); }\n";
        $css .= ".bg-success { background-color: var(--success-color); }\n";
        $css .= ".bg-warning { background-color: var(--warning-color); }\n";
        $css .= ".bg-error { background-color: var(--error-color); }\n\n";
        
        return $css;
    }
    
    /**
     * Get predefined theme templates
     */
    public function getThemeTemplates() {
        return [
            'government' => [
                'name' => 'Government Theme',
                'description' => 'Professional theme for government offices',
                'primary_color' => '#1e3a8a',
                'secondary_color' => '#f59e0b',
                'accent_color' => '#059669',
                'background_color' => '#f8fafc',
                'sidebar_color' => '#1e3a8a',
                'font_family' => "'Arial', sans-serif"
            ],
            'corporate' => [
                'name' => 'Corporate Theme',
                'description' => 'Modern theme for business environments',
                'primary_color' => '#1f2937',
                'secondary_color' => '#3b82f6',
                'accent_color' => '#10b981',
                'background_color' => '#f9fafb',
                'sidebar_color' => '#1f2937',
                'font_family' => "'Inter', sans-serif"
            ],
            'medical' => [
                'name' => 'Medical Theme',
                'description' => 'Clean theme for healthcare facilities',
                'primary_color' => '#065f46',
                'secondary_color' => '#06b6d4',
                'accent_color' => '#84cc16',
                'background_color' => '#f0fdf4',
                'sidebar_color' => '#065f46',
                'font_family' => "'Roboto', sans-serif"
            ],
            'educational' => [
                'name' => 'Educational Theme',
                'description' => 'Friendly theme for educational institutions',
                'primary_color' => '#7c2d12',
                'secondary_color' => '#ea580c',
                'accent_color' => '#facc15',
                'background_color' => '#fefce8',
                'sidebar_color' => '#7c2d12',
                'font_family' => "'Open Sans', sans-serif"
            ],
            'modern' => [
                'name' => 'Modern Theme',
                'description' => 'Sleek and minimal design',
                'primary_color' => '#0f172a',
                'secondary_color' => '#8b5cf6',
                'accent_color' => '#06b6d4',
                'background_color' => '#ffffff',
                'sidebar_color' => '#0f172a',
                'font_family' => "'Poppins', sans-serif"
            ]
        ];
    }
    
    /**
     * Apply theme template
     */
    public function applyThemeTemplate($template_name) {
        $templates = $this->getThemeTemplates();
        
        if (!isset($templates[$template_name])) {
            return [
                'success' => false,
                'message' => 'Theme template not found'
            ];
        }
        
        $template = $templates[$template_name];
        $template['theme_id'] = $template_name . '_' . time();
        
        return $this->generateThemeCSS($template);
    }
    
    /**
     * Get available custom themes
     */
    public function getCustomThemes() {
        $themes = [];
        $files = glob($this->generated_dir . 'theme_*.css');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $theme_id = str_replace(['theme_', '.css'], '', $filename);
            
            $themes[] = [
                'id' => $theme_id,
                'filename' => $filename,
                'path' => "assets/branding/themes/generated/{$filename}",
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'size' => filesize($file)
            ];
        }
        
        return $themes;
    }
    
    /**
     * Delete custom theme
     */
    public function deleteTheme($theme_id) {
        try {
            $filename = "theme_{$theme_id}.css";
            $filepath = $this->generated_dir . $filename;
            
            if (file_exists($filepath)) {
                if (unlink($filepath)) {
                    return [
                        'success' => true,
                        'message' => 'Theme deleted successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to delete theme file'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Theme file not found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting theme: ' . $e->getMessage()
            ];
        }
    }
}
?>