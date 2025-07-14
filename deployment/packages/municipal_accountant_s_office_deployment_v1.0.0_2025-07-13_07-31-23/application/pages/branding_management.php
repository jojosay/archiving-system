<?php
require_once 'includes/layout.php';
require_once 'includes/branding_manager.php';
require_once 'includes/favicon_manager.php';
require_once 'includes/theme_engine.php';

$database = new Database();
$brandingManager = new BrandingManager();
$faviconManager = new FaviconManager();
$themeEngine = new ThemeEngine();

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_branding':
            $branding_data = [
                'app_name' => $_POST['app_name'] ?? '',
                'app_description' => $_POST['app_description'] ?? '',
                'app_tagline' => $_POST['app_tagline'] ?? '',
                'office_name' => $_POST['office_name'] ?? '',
                'office_department' => $_POST['office_department'] ?? '',
                'office_address' => $_POST['office_address'] ?? '',
                'office_phone' => $_POST['office_phone'] ?? '',
                'office_email' => $_POST['office_email'] ?? '',
                'office_website' => $_POST['office_website'] ?? '',
                'primary_color' => $_POST['primary_color'] ?? '#2C3E50',
                'secondary_color' => $_POST['secondary_color'] ?? '#F39C12',
                'accent_color' => $_POST['accent_color'] ?? '#3498DB',
                'background_color' => $_POST['background_color'] ?? '#ECF0F1',
                'footer_text' => $_POST['footer_text'] ?? '',
                'copyright_text' => $_POST['copyright_text'] ?? '',
                'show_logo' => isset($_POST['show_logo']),
                'show_tagline' => isset($_POST['show_tagline']),
                'show_office_info' => isset($_POST['show_office_info']),
                'deployment_id' => $_POST['deployment_id'] ?? uniqid('deploy_'),
                'deployment_version' => $_POST['deployment_version'] ?? '1.0.0'
            ];
            
            $result = $brandingManager->updateBranding($branding_data);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'upload_logo':
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $result = $brandingManager->uploadLogo($_FILES['logo_file'], 'primary');
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                
                if ($result['success']) {
                    // Update branding with new logo path
                    $current_branding = $brandingManager->getCurrentBranding();
                    $current_branding['logo_primary'] = $result['path'];
                    $brandingManager->updateBranding($current_branding);
                }
            } else {
                $message = 'Please select a logo file to upload';
                $message_type = 'error';
            }
            break;
            
        case 'upload_favicon':
            if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
                $result = $faviconManager->uploadFavicon($_FILES['favicon_file']);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            } else {
                $message = 'Please select a favicon file to upload';
                $message_type = 'error';
            }
            break;
            
        case 'apply_theme_template':
            $template_name = $_POST['template_name'] ?? '';
            if ($template_name) {
                $result = $themeEngine->applyThemeTemplate($template_name);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
            }
            break;
            
        case 'generate_custom_theme':
            $theme_config = [
                'theme_id' => 'custom_' . time(),
                'primary_color' => $_POST['theme_primary_color'] ?? '#2C3E50',
                'secondary_color' => $_POST['theme_secondary_color'] ?? '#F39C12',
                'accent_color' => $_POST['theme_accent_color'] ?? '#3498DB',
                'background_color' => $_POST['theme_background_color'] ?? '#ECF0F1',
                'text_color' => $_POST['theme_text_color'] ?? '#2C3E50',
                'sidebar_color' => $_POST['theme_sidebar_color'] ?? '#2C3E50',
                'success_color' => $_POST['theme_success_color'] ?? '#27AE60',
                'warning_color' => $_POST['theme_warning_color'] ?? '#F39C12',
                'error_color' => $_POST['theme_error_color'] ?? '#E74C3C',
                'font_family' => $_POST['theme_font_family'] ?? "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                'font_size_base' => $_POST['theme_font_size_base'] ?? '1rem',
                'sidebar_width' => $_POST['theme_sidebar_width'] ?? '250px',
                'border_radius' => $_POST['theme_border_radius'] ?? '8px',
                'custom_css' => $_POST['custom_css'] ?? ''
            ];
            
            $result = $themeEngine->generateThemeCSS($theme_config);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'export_config':
            $result = $brandingManager->exportBrandingConfig();
            if ($result['success']) {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                echo json_encode($result['data'], JSON_PRETTY_PRINT);
                exit;
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
            break;
    }
}

// Get current branding configuration
$current_branding = $brandingManager->getCurrentBranding();

// Get available favicons
$available_favicons = $faviconManager->getAvailableFavicons();

// Get theme templates
$theme_templates = $themeEngine->getThemeTemplates();

// Get custom themes
$custom_themes = $themeEngine->getCustomThemes();

renderPageStart('Branding Management', 'branding_management');
?>

<style>
.branding-section {
    background: white;
    margin-bottom: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.branding-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.branding-content {
    padding: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.color-input {
    height: 50px;
    padding: 0.25rem;
    cursor: pointer;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5a67d8;
}

.btn-success {
    background: #48bb78;
    color: white;
}

.btn-success:hover {
    background: #38a169;
}

.btn-warning {
    background: #ed8936;
    color: white;
}

.btn-warning:hover {
    background: #dd6b20;
}

.preview-section {
    background: #f8fafc;
    padding: 2rem;
    border-radius: 8px;
    margin-top: 2rem;
}

.logo-preview {
    max-width: 200px;
    max-height: 100px;
    border: 2px dashed #d1d5db;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.logo-preview img {
    max-width: 100%;
    max-height: 100%;
}

.color-preview {
    width: 50px;
    height: 30px;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    display: inline-block;
    margin-left: 0.5rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #f0fff4;
    border: 1px solid #9ae6b4;
    color: #22543d;
}

.alert-error {
    background: #fed7d7;
    border: 1px solid #feb2b2;
    color: #742a2a;
}
</style>

<div class="page-header">
    <h1>Branding Management</h1>
    <p>Customize the application name, logo, colors, and office information for deployment</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Application Information -->
<div class="branding-section">
    <div class="branding-header">
        <h2>Application Information</h2>
    </div>
    <div class="branding-content">
        <form method="POST">
            <input type="hidden" name="action" value="update_branding">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="app_name">Application Name *</label>
                    <input type="text" id="app_name" name="app_name" value="<?php echo htmlspecialchars($current_branding['app_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="app_description">Application Description</label>
                    <input type="text" id="app_description" name="app_description" value="<?php echo htmlspecialchars($current_branding['app_description']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="app_tagline">Application Tagline</label>
                    <input type="text" id="app_tagline" name="app_tagline" value="<?php echo htmlspecialchars($current_branding['app_tagline']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="deployment_id">Deployment ID</label>
                    <input type="text" id="deployment_id" name="deployment_id" value="<?php echo htmlspecialchars($current_branding['deployment_id'] ?? uniqid('deploy_')); ?>">
                </div>
            </div>
            
            <h3>Office Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="office_name">Office Name *</label>
                    <input type="text" id="office_name" name="office_name" value="<?php echo htmlspecialchars($current_branding['office_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="office_department">Department</label>
                    <input type="text" id="office_department" name="office_department" value="<?php echo htmlspecialchars($current_branding['office_department']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="office_address">Address</label>
                    <textarea id="office_address" name="office_address" rows="3"><?php echo htmlspecialchars($current_branding['office_address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="office_phone">Phone Number</label>
                    <input type="text" id="office_phone" name="office_phone" value="<?php echo htmlspecialchars($current_branding['office_phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="office_email">Email Address</label>
                    <input type="email" id="office_email" name="office_email" value="<?php echo htmlspecialchars($current_branding['office_email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="office_website">Website</label>
                    <input type="url" id="office_website" name="office_website" value="<?php echo htmlspecialchars($current_branding['office_website']); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Application Information</button>
        </form>
    </div>
</div>

<!-- Favicon Management -->
<div class="branding-section">
    <div class="branding-header">
        <h2>Favicon Management</h2>
    </div>
    <div class="branding-content">
        <div class="form-grid">
            <div>
                <h4>Upload Custom Favicon</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_favicon">
                    <div class="form-group">
                        <label for="favicon_file">Favicon File (ICO or PNG)</label>
                        <input type="file" id="favicon_file" name="favicon_file" accept=".ico,.png" required>
                        <small>Recommended: 32x32 PNG or ICO file, max 1MB</small>
                    </div>
                    <button type="submit" class="btn btn-success">Upload Favicon</button>
                </form>
            </div>
            
            <div>
                <h4>Current Favicons</h4>
                <?php if (!empty($available_favicons)): ?>
                    <div class="favicon-list">
                        <?php foreach ($available_favicons as $favicon): ?>
                            <div class="favicon-item" style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <img src="<?php echo $favicon['path']; ?>" alt="Favicon" style="width: 16px; height: 16px; margin-right: 0.5rem;">
                                <span><?php echo $favicon['size']; ?>px - <?php echo strtoupper($favicon['type']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No custom favicons uploaded</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Theme Templates -->
<div class="branding-section">
    <div class="branding-header">
        <h2>Theme Templates</h2>
    </div>
    <div class="branding-content">
        <p>Choose from pre-designed themes optimized for different types of organizations:</p>
        
        <div class="theme-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
            <?php foreach ($theme_templates as $template_id => $template): ?>
                <div class="theme-card" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; background: white;">
                    <h4><?php echo htmlspecialchars($template['name']); ?></h4>
                    <p style="color: #6b7280; margin: 0.5rem 0;"><?php echo htmlspecialchars($template['description']); ?></p>
                    
                    <div class="color-preview-row" style="display: flex; gap: 0.5rem; margin: 1rem 0;">
                        <div class="color-preview" style="background-color: <?php echo $template['primary_color']; ?>; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #d1d5db;"></div>
                        <div class="color-preview" style="background-color: <?php echo $template['secondary_color']; ?>; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #d1d5db;"></div>
                        <div class="color-preview" style="background-color: <?php echo $template['accent_color']; ?>; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #d1d5db;"></div>
                    </div>
                    
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="apply_theme_template">
                        <input type="hidden" name="template_name" value="<?php echo $template_id; ?>">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Theme</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Advanced Theme Customizer -->
<div class="branding-section">
    <div class="branding-header">
        <h2>Advanced Theme Customizer</h2>
    </div>
    <div class="branding-content">
        <form method="POST">
            <input type="hidden" name="action" value="generate_custom_theme">
            
            <h4>Color Scheme</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label for="theme_primary_color">Primary Color</label>
                    <input type="color" id="theme_primary_color" name="theme_primary_color" value="#2C3E50" class="color-input">
                </div>
                
                <div class="form-group">
                    <label for="theme_secondary_color">Secondary Color</label>
                    <input type="color" id="theme_secondary_color" name="theme_secondary_color" value="#F39C12" class="color-input">
                </div>
                
                <div class="form-group">
                    <label for="theme_accent_color">Accent Color</label>
                    <input type="color" id="theme_accent_color" name="theme_accent_color" value="#3498DB" class="color-input">
                </div>
                
                <div class="form-group">
                    <label for="theme_background_color">Background Color</label>
                    <input type="color" id="theme_background_color" name="theme_background_color" value="#ECF0F1" class="color-input">
                </div>
                
                <div class="form-group">
                    <label for="theme_sidebar_color">Sidebar Color</label>
                    <input type="color" id="theme_sidebar_color" name="theme_sidebar_color" value="#2C3E50" class="color-input">
                </div>
                
                <div class="form-group">
                    <label for="theme_text_color">Text Color</label>
                    <input type="color" id="theme_text_color" name="theme_text_color" value="#2C3E50" class="color-input">
                </div>
            </div>
            
            <h4>Status Colors</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label for="theme_success_color">Success Color</label>
                    <input type="color" id="theme_success_color" name="theme_success_color" value="#27AE60" class="color-input">
                </div>
                
                <div class="form-group">
                    <label for="theme_warning_color">Warning Color</label>
                    <input type="color" id="theme_warning_color" name="theme_warning_color" value="#F39C12" class="color-input">
                </div>
                
                <div class="form-group">
                    <label for="theme_error_color">Error Color</label>
                    <input type="color" id="theme_error_color" name="theme_error_color" value="#E74C3C" class="color-input">
                </div>
            </div>
            
            <h4>Typography & Layout</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label for="theme_font_family">Font Family</label>
                    <select id="theme_font_family" name="theme_font_family">
                        <option value="'Segoe UI', Tahoma, Geneva, Verdana, sans-serif">Segoe UI (Default)</option>
                        <option value="'Arial', sans-serif">Arial</option>
                        <option value="'Roboto', sans-serif">Roboto</option>
                        <option value="'Open Sans', sans-serif">Open Sans</option>
                        <option value="'Inter', sans-serif">Inter</option>
                        <option value="'Poppins', sans-serif">Poppins</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="theme_font_size_base">Base Font Size</label>
                    <select id="theme_font_size_base" name="theme_font_size_base">
                        <option value="0.875rem">Small (14px)</option>
                        <option value="1rem" selected>Medium (16px)</option>
                        <option value="1.125rem">Large (18px)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="theme_sidebar_width">Sidebar Width</label>
                    <select id="theme_sidebar_width" name="theme_sidebar_width">
                        <option value="200px">Narrow (200px)</option>
                        <option value="250px" selected>Default (250px)</option>
                        <option value="300px">Wide (300px)</option>
                        <option value="350px">Extra Wide (350px)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="theme_border_radius">Border Radius</label>
                    <select id="theme_border_radius" name="theme_border_radius">
                        <option value="0px">None</option>
                        <option value="4px">Small</option>
                        <option value="8px" selected>Medium</option>
                        <option value="12px">Large</option>
                        <option value="20px">Extra Large</option>
                    </select>
                </div>
            </div>
            
            <h4>Custom CSS</h4>
            <div class="form-group">
                <label for="custom_css">Additional Custom CSS</label>
                <textarea id="custom_css" name="custom_css" rows="8" placeholder="/* Add your custom CSS here */"></textarea>
                <small>Advanced users can add custom CSS rules here</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Generate Custom Theme</button>
        </form>
    </div>
</div>

<!-- Export & Deployment -->
<div class="branding-section">
    <div class="branding-header">
        <h2>Export & Deployment</h2>
    </div>
    <div class="branding-content">
        <div class="form-grid">
            <div>
                <h4>Export Configuration</h4>
                <p>Export your branding configuration for deployment to other offices.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="export_config">
                    <button type="submit" class="btn btn-warning">Export Branding Config</button>
                </form>
            </div>
            
            <div>
                <h4>Custom Themes</h4>
                <?php if (!empty($custom_themes)): ?>
                    <div class="theme-list">
                        <?php foreach ($custom_themes as $theme): ?>
                            <div class="theme-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; margin-bottom: 0.5rem;">
                                <div>
                                    <strong>Theme <?php echo htmlspecialchars($theme['id']); ?></strong><br>
                                    <small>Created: <?php echo $theme['created']; ?></small>
                                </div>
                                <div>
                                    <a href="<?php echo $theme['path']; ?>" target="_blank" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View CSS</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No custom themes generated yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php renderPageEnd(); ?>