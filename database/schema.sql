-- Civil Registry Archiving System Database Schema
-- Version: 1.0.7
-- Updated: 2025-01-15 - Added multi-page PDF support and enhanced template management

CREATE DATABASE IF NOT EXISTS civil_registry_db;
USE civil_registry_db;

-- Users table for authentication and authorization
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Document types table for custom document categories
CREATE TABLE document_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Document type fields for dynamic metadata
CREATE TABLE document_type_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'number', 'date', 'time', 'dropdown', 'textarea', 'cascading_dropdown', 'reference', 'file') NOT NULL,
    field_options TEXT, -- JSON for dropdown options
    is_required BOOLEAN DEFAULT FALSE,
    field_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id) ON DELETE CASCADE
);

-- Documents table for storing document information
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Document metadata for storing dynamic field values
CREATE TABLE document_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

-- Default admin user will be created during installation process

-- Location hierarchy tables for cascading dropdowns
-- Regions table
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(100) NOT NULL,
    region_code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Provinces table
CREATE TABLE provinces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    region_code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (region_code) REFERENCES regions(region_code) ON DELETE CASCADE,
    INDEX idx_region_code (region_code)
);

-- Cities/Municipalities table
CREATE TABLE citymun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citymun_name VARCHAR(100) NOT NULL,
    province_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
    INDEX idx_province_id (province_id)
);

-- Barangays table
CREATE TABLE barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    citymun_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (citymun_id) REFERENCES citymun(id) ON DELETE CASCADE,
    INDEX idx_citymun_id (citymun_id)
);

-- Cascading dropdown field configurations
CREATE TABLE cascading_field_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    hierarchy_levels JSON NOT NULL,  -- ['region', 'province', 'citymun', 'barangay']
    level_labels JSON NOT NULL,      -- ['Region', 'Province', 'City/Municipality', 'Barangay']
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES document_type_fields(id) ON DELETE CASCADE,
    INDEX idx_field_id (field_id)
);

-- Book images table for reference fields
CREATE TABLE book_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    book_title VARCHAR(255),
    page_number INT,
    description TEXT,
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_book_title (book_title),
    INDEX idx_uploaded_by (uploaded_by)
);

-- Reference field configurations
CREATE TABLE reference_field_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    reference_type ENUM('book_image') DEFAULT 'book_image',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES document_type_fields(id) ON DELETE CASCADE,
    INDEX idx_field_id (field_id)
);

-- Document references (many-to-many relationship)
CREATE TABLE document_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    book_image_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (book_image_id) REFERENCES book_images(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reference (document_id, field_name, book_image_id),
    INDEX idx_document_id (document_id),
    INDEX idx_book_image_id (book_image_id)
);

-- Insert default document types
INSERT INTO document_types (name, description) VALUES 
('Birth Certificate', 'Official birth registration documents'),
('Death Certificate', 'Official death registration documents'),
('Marriage Certificate', 'Official marriage registration documents'),
('Divorce Certificate', 'Official divorce registration documents');