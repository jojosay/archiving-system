<?php
// Shared Layout Template
function renderPageStart($title = '', $current_page = '') {
    // Use branded app name if available
    $branded_app_name = defined('BRAND_APP_NAME') ? BRAND_APP_NAME : APP_NAME;
    $app_title = !empty($title) ? $title . ' - ' . $branded_app_name : $branded_app_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ECF0F1;
            line-height: 1.6;
        }
        
        .layout-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2C3E50;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #34495E;
            text-align: center;
        }
        
        .sidebar-header h1 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header .user-info {
            font-size: 0.9rem;
            color: #BDC3C7;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #BDC3C7;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: #34495E;
            color: white;
            border-left-color: #F39C12;
        }
        
        .nav-item.active {
            background: #34495E;
            color: white;
            border-left-color: #F39C12;
        }
        
        .nav-item .icon {
            display: inline-block;
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }
        
        .page-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            color: #2C3E50;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: #7F8C8D;
            margin: 0;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                background: #2C3E50;
                color: white;
                border: none;
                padding: 0.5rem;
                border-radius: 4px;
                cursor: pointer;
            }
        }
        
        .mobile-menu-btn {
            display: none;
        }
    </style>
    <!-- Favicon -->
    <?php
    if (class_exists('FaviconManager')) {
        $faviconManager = new FaviconManager();
        echo $faviconManager->generateFaviconHTML();
    }
    ?>
    
    <!-- Local Assets -->
    <link rel="stylesheet" href="assets/css/custom/app.css">
    <script src="assets/js/app/main.js"></script>
    <script src="assets/js/app/zoom-modal.js"></script>
    
    <!-- Application Scripts -->
    <script src="includes/cascading_dropdown.js"></script>
    <script src="includes/reference_selector.js"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</head>
<body>
    <div class="layout-container">
        <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
        
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <?php if (defined('BRAND_SHOW_LOGO') && BRAND_SHOW_LOGO && defined('BRAND_LOGO_PRIMARY') && !empty(BRAND_LOGO_PRIMARY) && file_exists(BRAND_LOGO_PRIMARY)): ?>
                    <img src="<?php echo BRAND_LOGO_PRIMARY; ?>" alt="Logo" style="max-width: 180px; max-height: 60px; margin-bottom: 0.5rem;">
                <?php endif; ?>
                <h1><?php echo defined('BRAND_APP_NAME') ? BRAND_APP_NAME : APP_NAME; ?></h1>
                <?php if (defined('BRAND_SHOW_TAGLINE') && BRAND_SHOW_TAGLINE && defined('BRAND_APP_TAGLINE') && !empty(BRAND_APP_TAGLINE)): ?>
                    <div style="font-size: 0.8rem; color: #BDC3C7; margin-top: 0.25rem;">
                        <?php echo htmlspecialchars(BRAND_APP_TAGLINE); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['full_name'])): ?>
                    <div class="user-info">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?><br>
                        <small><?php echo ucfirst($_SESSION['role']); ?></small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-nav">
                <a href="?page=dashboard" class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <span class="icon">📊</span> Dashboard
                </a>
                
                <a href="?page=document_archive" class="nav-item <?php echo $current_page === 'document_archive' ? 'active' : ''; ?>">
                    <span class="icon">🔍</span> Browse Archive
                </a>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="?page=document_types" class="nav-item <?php echo $current_page === 'document_types' ? 'active' : ''; ?>">
                        <span class="icon">📋</span> Document Types
                    </a>
                    
                    <a href="?page=book_images" class="nav-item <?php echo $current_page === 'book_images' ? 'active' : ''; ?>">
                        <span class="icon">📚</span> Book Images
                    </a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="?page=user_register" class="nav-item <?php echo $current_page === 'user_register' ? 'active' : ''; ?>">
                        <span class="icon">👤</span> Add User
                    </a>
                    
                    <a href="?page=user_list" class="nav-item <?php echo $current_page === 'user_list' ? 'active' : ''; ?>">
                        <span class="icon">👥</span> Manage Users
                    </a>
                    
                    <a href="?page=location_management" class="nav-item <?php echo $current_page === 'location_management' ? 'active' : ''; ?>">
                        <span class="icon">🗺️</span> Location Data
                    </a>
                    
                    <a href="?page=backup_management" class="nav-item <?php echo $current_page === 'backup_management' ? 'active' : ''; ?>">
                        <span class="icon">💾</span> Backup & Restore
                    </a>
                    
                    <a href="?page=reports" class="nav-item <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                        <span class="icon">📊</span> Reports
                    </a>
                    
                    <a href="?page=branding_management" class="nav-item <?php echo $current_page === 'branding_management' ? 'active' : ''; ?>">
                        <span class="icon">🎨</span> Branding
                    </a>
                    
                    <a href="?page=deployment_center" class="nav-item <?php echo $current_page === 'deployment_center' ? 'active' : ''; ?>">
                        <span class="icon">🚀</span> Deployment Center
                    </a>
                    
                    <a href="?page=system_reset" class="nav-item <?php echo $current_page === 'system_reset' ? 'active' : ''; ?>" style="border-top: 1px solid #34495E; margin-top: 0.5rem; padding-top: 1rem;">
                        <span class="icon" style="color: #e74c3c;">⚠️</span> <span style="color: #e74c3c;">System Reset</span>
                    </a>
                <?php endif; ?>
                
                <a href="test_db.php" class="nav-item" target="_blank">
                    <span class="icon">🔧</span> Test Database
                </a>
                
                <a href="?page=about" class="nav-item <?php echo $current_page === 'about' ? 'active' : ''; ?>">
                    <span class="icon">ℹ️</span> About
                </a>
                
                <a href="?page=logout" class="nav-item" style="margin-top: 2rem; border-top: 1px solid #34495E; padding-top: 1rem;">
                    <span class="icon">🚪</span> Logout
                </a>
            </div>
        </nav>
        
        <main class="main-content">
<?php
}

function renderPageEnd() {
?>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuBtn.contains(event.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
<?php
}
?>