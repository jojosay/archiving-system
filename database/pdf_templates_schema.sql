-- PDF Templates table for the new workflow
CREATE TABLE IF NOT EXISTS `pdf_templates` (
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
  KEY `idx_is_default` (`is_default`),
  FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_pdf_templates_filename` ON `pdf_templates` (`filename`);
CREATE INDEX IF NOT EXISTS `idx_pdf_templates_name` ON `pdf_templates` (`name`);
CREATE INDEX IF NOT EXISTS `idx_pdf_templates_created_date` ON `pdf_templates` (`created_date`);

-- Template field mappings (for advanced template building)
CREATE TABLE IF NOT EXISTS `pdf_template_fields` (
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
  KEY `idx_page_number` (`page_number`),
  FOREIGN KEY (`template_id`) REFERENCES `pdf_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template usage tracking (optional)
CREATE TABLE IF NOT EXISTS `pdf_template_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `used_by` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `usage_date` datetime NOT NULL,
  `usage_type` varchar(50) DEFAULT 'generate', -- 'generate', 'preview', 'download'
  PRIMARY KEY (`id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_used_by` (`used_by`),
  KEY `idx_usage_date` (`usage_date`),
  FOREIGN KEY (`template_id`) REFERENCES `pdf_templates` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data (optional)
-- INSERT INTO `pdf_templates` (`name`, `original_name`, `filename`, `file_path`, `file_size`, `pages`, `created_by`, `created_date`) 
-- VALUES ('Sample Letter Template', 'letter_template.pdf', 'template_sample_001.pdf', 'storage/templates/pdf/template_sample_001.pdf', 245760, 1, 1, NOW());