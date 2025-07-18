<?php
// Civil Registry Archiving System
// Main entry point

// Start session
session_start();

// Include configuration and dependencies
require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/first_install_manager.php';

// Basic routing - get page parameter first
$page = $_GET['page'] ?? 'dashboard';

// Check for first install (but not if we're already on the first install page)
$first_install_manager = new FirstInstallManager();
$is_first_install = $first_install_manager->isFirstInstall();

// Don't redirect if we're in the middle of installation process
$in_install_process = isset($_SESSION['install_db_config']) || isset($_SESSION['install_step_completed']);

if ($is_first_install && $page !== 'first_install' && !$in_install_process) {
    // Redirect to first install setup
    header('Location: index.php?page=first_install');
    exit;
} elseif ($in_install_process && $page !== 'first_install') {
    // If in install process but not on first_install page, redirect to first_install
    header('Location: index.php?page=first_install');
    exit;
}

// Initialize authentication only if not first install
if (!$is_first_install) {
    $database = new Database();
    $auth = new Auth($database);
} else {
    $database = null;
    $auth = null;
}

// Handle logout (only if auth is available)
if ($page === 'logout' && $auth) {
    $auth->logout();
    header('Location: index.php?page=login');
    exit;
}

// Check authentication for protected pages (only if not first install)
$protected_pages = ['dashboard', 'user_register', 'user_list', 'document_types', 'document_fields', 'location_management', 'book_images', 'document_upload', 'document_archive', 'document_edit', 'backup_management', 'system_reset', 'reports', 'branding_management', 'deployment_center', 'package_builder', 'app_updates', 'about', 'pdf_template_manager', 'pdf_viewer'];
$public_pages = ['login', 'first_install'];

if (!$is_first_install && in_array($page, $protected_pages) && (!$auth || !$auth->isLoggedIn())) {
    header('Location: index.php?page=login');
    exit;
}

// Simple routing logic
switch($page) {
    case 'first_install':
        include 'pages/first_install.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'user_register':
        include 'pages/user_register.php';
        break;
    case 'user_list':
        include 'pages/user_list.php';
        break;
    case 'document_types':
        include 'pages/document_types.php';
        break;
    case 'document_fields':
        include 'pages/document_fields.php';
        break;
    case 'location_management':
        include 'pages/location_management.php';
        break;
    case 'book_images':
        include 'pages/book_images.php';
        break;
        
    case 'document_upload':
        include 'pages/document_upload.php';
        break;
    case 'document_archive':
        include 'pages/document_archive.php';
        break;
    case 'document_edit':
        include 'pages/document_edit.php';
        break;
    case 'backup_management':
        include 'pages/backup_management.php';
        break;
        
    case 'system_reset':
        include 'pages/system_reset.php';
        break;
    case 'reports':
        include 'pages/reports.php';
        break;
    case 'branding_management':
        include 'pages/branding_management.php';
        break;
    case 'deployment_center':
        include 'pages/deployment_center.php';
        break;
    case 'package_builder':
        include 'pages/package_builder.php';
        break;
    case 'app_updates':
        include 'pages/app_updates.php';
        break;
    case 'about':
        include 'pages/about.php';
        break;
    case 'pdf_template_manager':
        include 'pages/pdf_template_manager.php';
        break;
    case 'pdf_viewer':
        include 'pages/pdf_viewer.php';
        break;
    case 'test_book_images':
        include 'tmp_rovodev_test_book_images_api.php';
        break;
    case 'dashboard':
    default:
        include 'pages/dashboard.php';
        break;
}
?>