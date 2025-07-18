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
    <link rel="stylesheet" href="assets/css/notifications.css">
    <link rel="stylesheet" href="assets/css/performance.css">
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
        
        .nav-category {
            margin-bottom: 0.5rem;
        }
        
        .nav-category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.5rem;
            color: #95A5A6;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-category-header:hover {
            color: #ECF0F1;
            background: rgba(52, 73, 94, 0.5);
        }
        
        .nav-category-header.active {
            color: #F39C12;
            border-left-color: #F39C12;
        }
        
        .nav-category-toggle {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }
        
        .nav-category-toggle.collapsed {
            transform: rotate(-90deg);
        }
        
        .nav-category-items {
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
            max-height: 1000px;
            opacity: 1;
        }
        
        .nav-category-items.collapsed {
            max-height: 0;
            opacity: 0;
        }
        
        .nav-item {
            display: block;
            padding: 0.75rem 1.5rem 0.75rem 2.5rem;
            color: #BDC3C7;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
        }
        
        .nav-item:hover {
            background: #34495E;
            color: white;
            border-left-color: #F39C12;
            transform: translateX(4px);
        }
        
        .nav-item.active {
            background: #34495E;
            color: white;
            border-left-color: #F39C12;
            transform: translateX(4px);
        }
        
        .nav-item .icon {
            display: inline-block;
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        
        .nav-divider {
            height: 1px;
            background: #34495E;
            margin: 1rem 1.5rem;
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
    <link rel="stylesheet" href="assets/css/custom/icons.css">
    <link rel="stylesheet" href="assets/css/custom/modal.css">
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
                <!-- Main Navigation -->
                <div class="nav-category">
                    <div class="nav-category-header" onclick="toggleCategory('main')" id="main-header">
                        <span>Main</span>
                        <span class="nav-category-toggle" id="main-toggle">▼</span>
                    </div>
                    <div class="nav-category-items" id="main-items">
                        <a href="?page=dashboard" class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                            <span class="icon">📊</span> Dashboard
                        </a>
                        <a href="?page=document_archive" class="nav-item <?php echo $current_page === 'document_archive' ? 'active' : ''; ?>">
                            <span class="icon">🔍</span> Browse Archive
                        </a>
                        
                    </div>
                </div>

                <!-- Templates -->
                <div class="nav-category">
                    <div class="nav-category-header" onclick="toggleCategory('templates')" id="templates-header">
                        <span>Templates</span>
                        <span class="nav-category-toggle" id="templates-toggle">▼</span>
                    </div>
                    <div class="nav-category-items" id="templates-items">
                        <a href="?page=pdf_template_manager" class="nav-item <?php echo $current_page === 'pdf_template_manager' ? 'active' : ''; ?>">
                            <span class="icon">📚</span> PDF Template Manager
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <!-- Document Management -->
                <div class="nav-category">
                    <div class="nav-category-header" onclick="toggleCategory('documents')" id="documents-header">
                        <span>Document Management</span>
                        <span class="nav-category-toggle" id="documents-toggle">▼</span>
                    </div>
                    <div class="nav-category-items" id="documents-items">
                        <a href="?page=document_types" class="nav-item <?php echo $current_page === 'document_types' ? 'active' : ''; ?>">
                            <span class="icon">📋</span> Document Types
                        </a>
                        <a href="?page=book_images" class="nav-item <?php echo $current_page === 'book_images' ? 'active' : ''; ?>">
                            <span class="icon">📚</span> Book Images
                        </a>
                    </div>
                </div>

                <!-- User Management -->
                <div class="nav-category">
                    <div class="nav-category-header" onclick="toggleCategory('users')" id="users-header">
                        <span>User Management</span>
                        <span class="nav-category-toggle" id="users-toggle">▼</span>
                    </div>
                    <div class="nav-category-items" id="users-items">
                        <a href="?page=user_register" class="nav-item <?php echo $current_page === 'user_register' ? 'active' : ''; ?>">
                            <span class="icon">👤</span> Add User
                        </a>
                        <a href="?page=user_list" class="nav-item <?php echo $current_page === 'user_list' ? 'active' : ''; ?>">
                            <span class="icon">👥</span> Manage Users
                        </a>
                        <a href="?page=location_management" class="nav-item <?php echo $current_page === 'location_management' ? 'active' : ''; ?>">
                            <span class="icon">🗺️</span> Location Data
                        </a>
                    </div>
                </div>

                <!-- System Tools -->
                <div class="nav-category">
                    <div class="nav-category-header" onclick="toggleCategory('system')" id="system-header">
                        <span>System Tools</span>
                        <span class="nav-category-toggle" id="system-toggle">▼</span>
                    </div>
                    <div class="nav-category-items" id="system-items">
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
                        <a href="?page=app_updates" class="nav-item <?php echo $current_page === 'app_updates' ? 'active' : ''; ?>">
                            <span class="icon">🔄</span> App Updates
                        </a>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="nav-category">
                    <div class="nav-category-header" onclick="toggleCategory('danger')" id="danger-header">
                        <span style="color: #e74c3c;">Danger Zone</span>
                        <span class="nav-category-toggle" id="danger-toggle">▼</span>
                    </div>
                    <div class="nav-category-items collapsed" id="danger-items">
                        <a href="?page=system_reset" class="nav-item <?php echo $current_page === 'system_reset' ? 'active' : ''; ?>">
                            <span class="icon" style="color: #e74c3c;">⚠️</span> <span style="color: #e74c3c;">System Reset</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="nav-divider"></div>

                <!-- Utilities -->
                <div class="nav-category">
                    <div class="nav-category-header" onclick="toggleCategory('utilities')" id="utilities-header">
                        <span>Utilities</span>
                        <span class="nav-category-toggle" id="utilities-toggle">▼</span>
                    </div>
                    <div class="nav-category-items" id="utilities-items">
                        <a href="test_db.php" class="nav-item" target="_blank">
                            <span class="icon">🔧</span> Test Database
                        </a>
                        <a href="?page=about" class="nav-item <?php echo $current_page === 'about' ? 'active' : ''; ?>">
                            <span class="icon">ℹ️</span> About
                        </a>
                    </div>
                </div>

                <div class="nav-divider"></div>

                <!-- Logout -->
                <a href="?page=logout" class="nav-item" style="padding-left: 1.5rem;">
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
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Category collapse/expand functionality
        function toggleCategory(categoryName) {
            const items = document.getElementById(categoryName + '-items');
            const toggle = document.getElementById(categoryName + '-toggle');
            const header = document.getElementById(categoryName + '-header');
            
            if (items.classList.contains('collapsed')) {
                // Expand
                items.classList.remove('collapsed');
                toggle.classList.remove('collapsed');
                header.classList.add('active');
                
                // Save state
                localStorage.setItem('nav-category-' + categoryName, 'expanded');
            } else {
                // Collapse
                items.classList.add('collapsed');
                toggle.classList.add('collapsed');
                header.classList.remove('active');
                
                // Save state
                localStorage.setItem('nav-category-' + categoryName, 'collapsed');
            }
        }
        
        // Initialize category states on page load
        document.addEventListener('DOMContentLoaded', function() {
            const categories = ['main', 'templates', 'documents', 'users', 'system', 'danger', 'utilities'];
            
            categories.forEach(function(categoryName) {
                const savedState = localStorage.getItem('nav-category-' + categoryName);
                const items = document.getElementById(categoryName + '-items');
                const toggle = document.getElementById(categoryName + '-toggle');
                const header = document.getElementById(categoryName + '-header');
                
                if (items && toggle && header) {
                    if (savedState === 'collapsed') {
                        items.classList.add('collapsed');
                        toggle.classList.add('collapsed');
                        header.classList.remove('active');
                    } else {
                        // Default to expanded for main categories, collapsed for danger zone
                        if (categoryName === 'danger') {
                            items.classList.add('collapsed');
                            toggle.classList.add('collapsed');
                            header.classList.remove('active');
                        } else {
                            items.classList.remove('collapsed');
                            toggle.classList.remove('collapsed');
                            header.classList.add('active');
                        }
                    }
                }
            });
            
            // Auto-expand category containing active page
            const activeNavItem = document.querySelector('.nav-item.active');
            if (activeNavItem) {
                const parentCategory = activeNavItem.closest('.nav-category-items');
                if (parentCategory) {
                    const categoryId = parentCategory.id.replace('-items', '');
                    const items = document.getElementById(categoryId + '-items');
                    const toggle = document.getElementById(categoryId + '-toggle');
                    const header = document.getElementById(categoryId + '-header');
                    
                    if (items && toggle && header) {
                        items.classList.remove('collapsed');
                        toggle.classList.remove('collapsed');
                        header.classList.add('active');
                        localStorage.setItem('nav-category-' + categoryId, 'expanded');
                    }
                }
            }
        });
        
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
        
        // Add smooth hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(function(item) {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(4px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('active')) {
                        this.style.transform = 'translateX(0)';
                    }
                });
            });
        });
    </script>
    
    <!-- Performance optimization script -->
    <script src="assets/js/performance.js"></script>
</body>
</html>
<?php
}
?>