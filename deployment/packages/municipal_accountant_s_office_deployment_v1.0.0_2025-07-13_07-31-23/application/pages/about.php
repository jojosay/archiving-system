<?php
// Session is already started by index.php
// Authentication is already checked by index.php

require_once 'includes/layout.php';
require_once 'includes/branding_manager.php';

// Get branding information
$branding_manager = new BrandingManager();
$branding = $branding_manager->getCurrentBranding();

// Get application information with branding integration
$app_info = [
    'name' => $branding['app_name'],
    'description' => !empty($branding['app_description']) ? $branding['app_description'] : 'A comprehensive offline archiving system for civil registry documents with modern deployment capabilities.',
    'tagline' => $branding['app_tagline'],
    'version' => '1.0.0',
    'build_date' => !empty($branding['deployment_date']) ? $branding['deployment_date'] : '2025-01-13',
    'deployment_version' => !empty($branding['deployment_version']) ? $branding['deployment_version'] : '1.0.0',
    'deployment_id' => !empty($branding['deployment_id']) ? $branding['deployment_id'] : 'N/A',
    'php_version' => phpversion(),
    'developer' => !empty($branding['developer_name']) ? $branding['developer_name'] : 'Jose John S. Saycon'
];

// Get office information from branding
$office_info = [
    'name' => $branding['office_name'],
    'department' => $branding['office_department'],
    'address' => $branding['office_address'],
    'phone' => $branding['office_phone'],
    'email' => $branding['office_email'],
    'website' => $branding['office_website']
];

// Get visual branding
$visual_branding = [
    'primary_color' => $branding['primary_color'],
    'secondary_color' => $branding['secondary_color'],
    'accent_color' => $branding['accent_color'],
    'background_color' => $branding['background_color'],
    'logo_primary' => $branding['logo_primary'],
    'show_logo' => $branding['show_logo'],
    'show_tagline' => $branding['show_tagline'],
    'show_office_info' => $branding['show_office_info'],
    'footer_text' => $branding['footer_text'],
    'copyright_text' => $branding['copyright_text']
];

// Get system information
$system_info = [
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_version' => phpversion(),
    'mysql_version' => 'MySQL/MariaDB',
    'operating_system' => php_uname('s') . ' ' . php_uname('r'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . ' seconds',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Get database info if available
try {
    require_once 'includes/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    $stmt = $pdo->query("SELECT VERSION() as version");
    $db_version = $stmt->fetch(PDO::FETCH_ASSOC);
    $system_info['mysql_version'] = $db_version['version'] ?? 'MySQL/MariaDB';
} catch (Exception $e) {
    $system_info['mysql_version'] = 'Not connected';
}

renderPageStart('About', 'about');
?>

    <div class="about-container">
        <!-- Hero Section -->
        <div class="about-hero" style="background: linear-gradient(135deg, <?php echo $visual_branding['primary_color']; ?> 0%, <?php echo $visual_branding['accent_color']; ?> 100%);">
            <div class="hero-content">
                <div class="app-logo">
                    <?php if ($visual_branding['show_logo'] && !empty($visual_branding['logo_primary']) && file_exists($visual_branding['logo_primary'])): ?>
                        <img src="<?php echo htmlspecialchars($visual_branding['logo_primary']); ?>" alt="Application Logo" class="hero-logo">
                    <?php else: ?>
                        <div class="hero-icon">📋</div>
                    <?php endif; ?>
                </div>
                <h1><?php echo htmlspecialchars($app_info['name']); ?></h1>
                <p class="app-description"><?php echo htmlspecialchars($app_info['description']); ?></p>
                <?php if ($visual_branding['show_tagline'] && !empty($app_info['tagline'])): ?>
                    <p class="app-tagline"><?php echo htmlspecialchars($app_info['tagline']); ?></p>
                <?php endif; ?>
                <div class="version-badge">
                    <span class="version-label">Version</span>
                    <span class="version-number"><?php echo htmlspecialchars($app_info['deployment_version']); ?></span>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="about-grid">
            
            <?php if ($visual_branding['show_office_info'] && !empty($office_info['name'])): ?>
            <!-- Office Information -->
            <div class="about-card office-card">
                <div class="card-header">
                    <div class="card-icon">🏢</div>
                    <h2>Office Information</h2>
                </div>
                <div class="card-body">
                    <div class="office-info">
                        <div class="office-header">
                            <h3><?php echo htmlspecialchars($office_info['name']); ?></h3>
                            <?php if (!empty($office_info['department'])): ?>
                                <p class="office-department"><?php echo htmlspecialchars($office_info['department']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="office-details">
                            <?php if (!empty($office_info['address'])): ?>
                                <div class="office-detail">
                                    <span class="detail-icon">📍</span>
                                    <span class="detail-text"><?php echo htmlspecialchars($office_info['address']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($office_info['phone'])): ?>
                                <div class="office-detail">
                                    <span class="detail-icon">📞</span>
                                    <span class="detail-text"><?php echo htmlspecialchars($office_info['phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($office_info['email'])): ?>
                                <div class="office-detail">
                                    <span class="detail-icon">📧</span>
                                    <span class="detail-text"><?php echo htmlspecialchars($office_info['email']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($office_info['website'])): ?>
                                <div class="office-detail">
                                    <span class="detail-icon">🌐</span>
                                    <span class="detail-text"><?php echo htmlspecialchars($office_info['website']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!-- Developer Information -->
            <div class="about-card developer-card">
                <div class="card-header">
                    <div class="card-icon">👨‍💻</div>
                    <h2>Developer</h2>
                </div>
                <div class="card-body">
                    <div class="developer-info">
                        <div class="developer-avatar">
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($app_info['developer'], 0, 2)); ?>
                            </div>
                        </div>
                        <div class="developer-details">
                            <h3><?php echo htmlspecialchars($app_info['developer']); ?></h3>
                            <p class="developer-title">Full Stack Developer</p>
                            <p class="developer-description">
                                Specialized in creating robust, offline-capable applications for government and enterprise use. 
                                Passionate about building secure, user-friendly systems that work reliably in any environment.
                            </p>
                        </div>
                    </div>
                    
                    <div class="developer-skills">
                        <h4>Technical Expertise</h4>
                        <div class="skills-grid">
                            <span class="skill-tag">PHP</span>
                            <span class="skill-tag">MySQL</span>
                            <span class="skill-tag">JavaScript</span>
                            <span class="skill-tag">HTML5/CSS3</span>
                            <span class="skill-tag">Responsive Design</span>
                            <span class="skill-tag">Database Design</span>
                            <span class="skill-tag">System Architecture</span>
                            <span class="skill-tag">UI/UX Design</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Features -->
            <div class="about-card features-card">
                <div class="card-header">
                    <div class="card-icon">⭐</div>
                    <h2>Key Features</h2>
                </div>
                <div class="card-body">
                    <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon">📄</div>
                            <div class="feature-content">
                                <h4>Document Management</h4>
                                <p>Comprehensive archiving and retrieval system for civil registry documents</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🔍</div>
                            <div class="feature-content">
                                <h4>Advanced Search</h4>
                                <p>Powerful search capabilities with multiple filters and criteria</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🎨</div>
                            <div class="feature-content">
                                <h4>Custom Branding</h4>
                                <p>Full branding customization for different offices and organizations</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🚀</div>
                            <div class="feature-content">
                                <h4>Deployment Tools</h4>
                                <p>Professional deployment packages for multi-office distribution</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🔒</div>
                            <div class="feature-content">
                                <h4>Security & Privacy</h4>
                                <p>Role-based access control and secure data handling</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">📱</div>
                            <div class="feature-content">
                                <h4>Responsive Design</h4>
                                <p>Modern, mobile-friendly interface that works on all devices</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">🌐</div>
                            <div class="feature-content">
                                <h4>Offline Capable</h4>
                                <p>100% offline functionality - no internet connection required</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">📊</div>
                            <div class="feature-content">
                                <h4>Reports & Analytics</h4>
                                <p>Comprehensive reporting system with data visualization</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="about-card system-card">
                <div class="card-header">
                    <div class="card-icon">⚙️</div>
                    <h2>System Information</h2>
                </div>
                <div class="card-body">
                    <div class="system-info">
                        <?php foreach ($system_info as $label => $value): ?>
                            <div class="info-row">
                                <span class="info-label"><?php echo ucwords(str_replace('_', ' ', $label)); ?>:</span>
                                <span class="info-value"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Application Details -->
            <div class="about-card details-card">
                <div class="card-header">
                    <div class="card-icon">📋</div>
                    <h2>Application Details</h2>
                </div>
                <div class="card-body">
                    <div class="details-info">
                        <div class="info-row">
                            <span class="info-label">Application Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($app_info['name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Application Version:</span>
                            <span class="info-value"><?php echo htmlspecialchars($app_info['version']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Deployment Version:</span>
                            <span class="info-value"><?php echo htmlspecialchars($app_info['deployment_version']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Deployment ID:</span>
                            <span class="info-value"><?php echo htmlspecialchars($app_info['deployment_id']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Build Date:</span>
                            <span class="info-value"><?php echo htmlspecialchars($app_info['build_date']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Developer:</span>
                            <span class="info-value"><?php echo htmlspecialchars($app_info['developer']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">License:</span>
                            <span class="info-value">Proprietary</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Platform:</span>
                            <span class="info-value">Web Application (PHP/MySQL)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="about-footer">
            <div class="footer-content">
                <p class="copyright">
                    <?php if (!empty($visual_branding['copyright_text'])): ?>
                        <?php echo htmlspecialchars($visual_branding['copyright_text']); ?>
                    <?php else: ?>
                        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($app_info['developer']); ?>. All rights reserved.
                    <?php endif; ?>
                </p>
                <p class="footer-description">
                    <?php if (!empty($visual_branding['footer_text'])): ?>
                        <?php echo htmlspecialchars($visual_branding['footer_text']); ?>
                    <?php else: ?>
                        Built with ❤️ for efficient document management.
                    <?php endif; ?>
                </p>
                <?php if (!empty($office_info['name'])): ?>
                    <p class="office-credit">
                        Deployed for <?php echo htmlspecialchars($office_info['name']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
    /* About Page Styles */
    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    /* Hero Section */
    .about-hero {
        /* Background set dynamically via inline style */
        color: white;
        padding: 4rem 2rem;
        border-radius: 16px;
        margin-bottom: 3rem;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .hero-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .app-logo {
        margin-bottom: 2rem;
    }

    .hero-logo {
        max-height: 80px;
        max-width: 200px;
        border-radius: 8px;
    }

    .hero-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        display: block;
    }

    .about-hero h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .app-description {
        font-size: 1.2rem;
        margin-bottom: 1rem;
        opacity: 0.9;
        line-height: 1.6;
    }

    .app-tagline {
        font-size: 1rem;
        margin-bottom: 2rem;
        opacity: 0.8;
        font-style: italic;
        line-height: 1.4;
    }

    .version-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255,255,255,0.2);
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        backdrop-filter: blur(10px);
    }

    .version-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .version-number {
        font-size: 1.1rem;
        font-weight: bold;
    }

    /* Main Grid */
    .about-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    /* Cards */
    .about-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .about-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    }

    .card-header {
        padding: 2rem 2rem 1rem 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .card-icon {
        font-size: 2rem;
        flex-shrink: 0;
    }

    .card-header h2 {
        margin: 0;
        font-size: 1.5rem;
        color: #2c3e50;
    }

    .card-body {
        padding: 2rem;
    }

    /* Office Information Card */
    .office-info {
        text-align: center;
    }

    .office-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e9ecef;
    }

    .office-header h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
        color: #2c3e50;
    }

    .office-department {
        margin: 0;
        color: #6c757d;
        font-weight: 500;
    }

    .office-details {
        display: grid;
        gap: 1rem;
    }

    .office-detail {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        text-align: left;
    }

    .office-detail .detail-icon {
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .office-detail .detail-text {
        color: #495057;
        font-weight: 500;
    }

    /* Developer Card */
    .developer-info {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .developer-avatar {
        flex-shrink: 0;
    }

    .avatar-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #a29bfe);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .developer-details h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
        color: #2c3e50;
    }

    .developer-title {
        color: #6c5ce7;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .developer-description {
        color: #6c757d;
        line-height: 1.6;
        margin: 0;
    }

    .developer-skills h4 {
        margin-bottom: 1rem;
        color: #2c3e50;
    }

    .skills-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .skill-tag {
        background: #f8f9fa;
        color: #495057;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid #e9ecef;
    }

    /* Features List */
    .features-list {
        display: grid;
        gap: 1.5rem;
    }

    .feature-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .feature-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
        margin-top: 0.25rem;
    }

    .feature-content h4 {
        margin: 0 0 0.5rem 0;
        color: #2c3e50;
    }

    .feature-content p {
        margin: 0;
        color: #6c757d;
        line-height: 1.5;
    }

    /* System Info */
    .system-info,
    .details-info {
        display: grid;
        gap: 1rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
    }

    .info-value {
        color: #6c757d;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
    }

    /* Footer */
    .about-footer {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .footer-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .copyright {
        margin: 0 0 0.5rem 0;
        font-weight: 600;
        color: #495057;
    }

    .footer-description {
        margin: 0 0 0.5rem 0;
        color: #6c757d;
    }

    .office-credit {
        margin: 0;
        color: #495057;
        font-weight: 500;
        font-size: 0.9rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .about-grid {
            grid-template-columns: 1fr;
        }

        .about-hero h1 {
            font-size: 2rem;
        }

        .app-description {
            font-size: 1rem;
        }

        .developer-info {
            flex-direction: column;
            text-align: center;
        }

        .info-row {
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
        }

        .card-header {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }
    }
    </style>

<?php renderPageEnd(); ?>