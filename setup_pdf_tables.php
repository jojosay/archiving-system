<?php
// Setup script for PDF template tables
// Access this via: http://localhost/archiving-system/setup_pdf_tables.php

// Start session and include dependencies
session_start();
require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Simple authentication check (optional - remove if needed)
$database = new Database();
$auth = new Auth($database);

// Uncomment the next 4 lines if you want to require login to run this script
// if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
//     die('Access denied. Please login as admin first.');
// }

echo "<h2>PDF Template Tables Setup</h2>";
echo "<pre>";

try {
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    echo "✓ Database connection successful\n";
    
    // Check and create pdf_templates table
    $checkPdfTemplates = $db->prepare("SHOW TABLES LIKE 'pdf_templates'");
    $checkPdfTemplates->execute();
    
    if ($checkPdfTemplates->rowCount() === 0) {
        echo "Creating pdf_templates table...\n";
        $createPdfTemplates = "
        CREATE TABLE `pdf_templates` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `original_name` varchar(255) NOT NULL,
          `filename` varchar(255) NOT NULL,
          `file_path` varchar(500) NOT NULL,
          `file_size` bigint(20) NOT NULL,
          `pages` int(11) DEFAULT 1,
          `thumbnail_path` varchar(500) DEFAULT NULL,
          `document_type_id` int(11) DEFAULT NULL,
          `is_default` tinyint(1) DEFAULT 0,
          `description` text DEFAULT NULL,
          `metadata` json DEFAULT NULL,
          `template_fields` json DEFAULT NULL,
          `created_by` int(11) NOT NULL,
          `updated_by` int(11) DEFAULT NULL,
          `created_date` datetime NOT NULL,
          `updated_date` datetime DEFAULT NULL,
          `deleted` tinyint(1) DEFAULT 0,
          PRIMARY KEY (`id`),
          KEY `idx_document_type` (`document_type_id`),
          KEY `idx_created_by` (`created_by`),
          KEY `idx_deleted` (`deleted`),
          KEY `idx_is_default` (`is_default`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createPdfTemplates);
        echo "✓ pdf_templates table created\n";
    } else {
        echo "✓ pdf_templates table already exists\n";
    }
    
    // Check and create pdf_template_fields table
    $checkFields = $db->prepare("SHOW TABLES LIKE 'pdf_template_fields'");
    $checkFields->execute();
    
    if ($checkFields->rowCount() === 0) {
        echo "Creating pdf_template_fields table...\n";
        $createFields = "
        CREATE TABLE `pdf_template_fields` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `template_id` int(11) NOT NULL,
          `field_name` varchar(255) NOT NULL,
          `field_type` varchar(50) NOT NULL DEFAULT 'text',
          `x_position` decimal(10,2) NOT NULL,
          `y_position` decimal(10,2) NOT NULL,
          `width` decimal(10,2) NOT NULL,
          `height` decimal(10,2) NOT NULL,
          `page_number` int(11) NOT NULL DEFAULT 1,
          `font_size` int(11) DEFAULT 12,
          `font_family` varchar(100) DEFAULT 'Arial',
          `font_color` varchar(7) DEFAULT '#000000',
          `is_required` tinyint(1) DEFAULT 0,
          `default_value` text DEFAULT NULL,
          `validation_rules` json DEFAULT NULL,
          `created_date` datetime NOT NULL,
          `updated_date` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_template_id` (`template_id`),
          KEY `idx_page_number` (`page_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createFields);
        echo "✓ pdf_template_fields table created\n";
    } else {
        echo "✓ pdf_template_fields table already exists\n";
    }
    
    // Check and create pdf_template_usage table
    $checkUsage = $db->prepare("SHOW TABLES LIKE 'pdf_template_usage'");
    $checkUsage->execute();
    
    if ($checkUsage->rowCount() === 0) {
        echo "Creating pdf_template_usage table...\n";
        $createUsage = "
        CREATE TABLE `pdf_template_usage` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `template_id` int(11) NOT NULL,
          `used_by` int(11) NOT NULL,
          `document_id` int(11) DEFAULT NULL,
          `usage_date` datetime NOT NULL,
          `usage_type` varchar(50) DEFAULT 'generate',
          PRIMARY KEY (`id`),
          KEY `idx_template_id` (`template_id`),
          KEY `idx_used_by` (`used_by`),
          KEY `idx_usage_date` (`usage_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createUsage);
        echo "✓ pdf_template_usage table created\n";
    } else {
        echo "✓ pdf_template_usage table already exists\n";
    }
    
    echo "\n🎉 All PDF template tables are now ready!\n";
    echo "\nYou can now:\n";
    echo "- Access the Template Builder without errors\n";
    echo "- Upload and manage PDF templates\n";
    echo "- Create template field mappings\n";
    echo "\nIt's safe to delete this setup file after running it.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

echo '<p><a href="index.php?page=pdf_template_manager">Go to PDF Template Manager</a></p>';
echo '<p><a href="index.php?page=template_builder">Go to Template Builder</a></p>';
?>