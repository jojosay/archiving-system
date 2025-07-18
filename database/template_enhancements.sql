-- Template Management Enhancement Database Schema
-- This script enhances the templates table and creates supporting tables

-- Create enhanced templates table if it doesn't exist
CREATE TABLE IF NOT EXISTS templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    tags VARCHAR(500),
    version VARCHAR(10) DEFAULT '1.0',
    is_default TINYINT(1) DEFAULT 0,
    template_data LONGTEXT,
    preview_image VARCHAR(255),
    usage_count INT DEFAULT 0,
    field_completeness_score DECIMAL(3,2) DEFAULT 0.00,
    created_by INT,
    updated_by INT,
    deleted TINYINT(1) DEFAULT 0,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document_type (document_type_id),
    INDEX idx_is_default (is_default),
    INDEX idx_created_by (created_by),
    INDEX idx_deleted (deleted)
);

-- Add enhanced columns to existing templates table if they don't exist
-- Note: These ALTER statements will fail silently if columns already exist

-- Add document_type_id column
ALTER TABLE templates ADD COLUMN document_type_id INT;

-- Add is_default column
ALTER TABLE templates ADD COLUMN is_default TINYINT(1) DEFAULT 0;

-- Add version column
ALTER TABLE templates ADD COLUMN version VARCHAR(10) DEFAULT '1.0';

-- Add description column
ALTER TABLE templates ADD COLUMN description TEXT;

-- Add tags column
ALTER TABLE templates ADD COLUMN tags VARCHAR(500);

-- Add preview_image column
ALTER TABLE templates ADD COLUMN preview_image VARCHAR(255);

-- Add usage_count column
ALTER TABLE templates ADD COLUMN usage_count INT DEFAULT 0;

-- Add field_completeness_score column
ALTER TABLE templates ADD COLUMN field_completeness_score DECIMAL(3,2) DEFAULT 0.00;

-- Add created_by column
ALTER TABLE templates ADD COLUMN created_by INT;

-- Add updated_by column
ALTER TABLE templates ADD COLUMN updated_by INT;

-- Add deleted column for soft deletes
ALTER TABLE templates ADD COLUMN deleted TINYINT(1) DEFAULT 0;

-- Create template field requirements table
CREATE TABLE IF NOT EXISTS template_field_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_type VARCHAR(50) NOT NULL DEFAULT 'text',
    is_required TINYINT(1) DEFAULT 0,
    validation_rules TEXT,
    default_value VARCHAR(255),
    display_order INT DEFAULT 0,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE CASCADE,
    INDEX idx_document_type_field (document_type_id),
    INDEX idx_field_name (field_name),
    INDEX idx_is_required (is_required)
);

-- Create template usage analytics table
CREATE TABLE IF NOT EXISTS template_usage_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    used_by INT NOT NULL,
    document_id INT,
    usage_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    success_rate DECIMAL(3,2),
    feedback_score INT,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE,
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_template_usage (template_id),
    INDEX idx_used_by (used_by),
    INDEX idx_usage_date (usage_date)
);

-- Add indexes to templates table if they don't exist
-- Note: These will fail silently if indexes already exist

-- Index for document type lookups
CREATE INDEX idx_templates_document_type ON templates(document_type_id);

-- Index for default template lookups
CREATE INDEX idx_templates_is_default ON templates(is_default);

-- Index for creator lookups
CREATE INDEX idx_templates_created_by ON templates(created_by);

-- Index for soft delete filtering
CREATE INDEX idx_templates_deleted ON templates(deleted);

-- Index for name searches
CREATE INDEX idx_templates_name ON templates(name);

-- Index for tag searches
CREATE INDEX idx_templates_tags ON templates(tags);

-- Insert sample field requirements for common document types
-- This will help with initial setup and testing

-- Birth Certificate field requirements
INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'full_name', 'text', 1, 1 FROM document_types dt WHERE dt.name LIKE '%birth%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'birth_date', 'date', 1, 2 FROM document_types dt WHERE dt.name LIKE '%birth%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'birth_place', 'text', 1, 3 FROM document_types dt WHERE dt.name LIKE '%birth%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'father_name', 'text', 1, 4 FROM document_types dt WHERE dt.name LIKE '%birth%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'mother_name', 'text', 1, 5 FROM document_types dt WHERE dt.name LIKE '%birth%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'registration_number', 'text', 1, 6 FROM document_types dt WHERE dt.name LIKE '%birth%' LIMIT 1;

-- Marriage Certificate field requirements
INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'groom_name', 'text', 1, 1 FROM document_types dt WHERE dt.name LIKE '%marriage%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'bride_name', 'text', 1, 2 FROM document_types dt WHERE dt.name LIKE '%marriage%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'marriage_date', 'date', 1, 3 FROM document_types dt WHERE dt.name LIKE '%marriage%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'marriage_place', 'text', 1, 4 FROM document_types dt WHERE dt.name LIKE '%marriage%' LIMIT 1;

-- Death Certificate field requirements
INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'deceased_name', 'text', 1, 1 FROM document_types dt WHERE dt.name LIKE '%death%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'death_date', 'date', 1, 2 FROM document_types dt WHERE dt.name LIKE '%death%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'death_place', 'text', 1, 3 FROM document_types dt WHERE dt.name LIKE '%death%' LIMIT 1;

INSERT IGNORE INTO template_field_requirements (document_type_id, field_name, field_type, is_required, display_order) 
SELECT dt.id, 'cause_of_death', 'text', 1, 4 FROM document_types dt WHERE dt.name LIKE '%death%' LIMIT 1;

-- Update existing templates to have default values for new columns
UPDATE templates SET 
    version = '1.0' 
WHERE version IS NULL OR version = '';

UPDATE templates SET 
    is_default = 0 
WHERE is_default IS NULL;

UPDATE templates SET 
    usage_count = 0 
WHERE usage_count IS NULL;

UPDATE templates SET 
    field_completeness_score = 0.00 
WHERE field_completeness_score IS NULL;

UPDATE templates SET 
    deleted = 0 
WHERE deleted IS NULL;