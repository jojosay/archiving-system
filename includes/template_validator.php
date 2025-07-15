<?php
/**
 * Template Validator Class
 * Handles template file validation and security checks
 */

class TemplateValidator {
    private $allowed_extensions;
    private $mime_types;
    private $max_file_size;
    
    public function __construct() {
        $this->allowed_extensions = ['docx', 'doc', 'xlsx', 'xls', 'pdf'];
        $this->mime_types = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pdf' => 'application/pdf'
        ];
        $this->max_file_size = 50 * 1024 * 1024; // 50MB
    }
    
    /**
     * Validate template metadata
     */
    public function validateTemplateData($data) {
        $errors = [];
        
        // Validate name
        if (empty($data['name']) || strlen(trim($data['name'])) < 3) {
            $errors[] = 'Template name must be at least 3 characters long';
        }
        
        if (strlen($data['name']) > 255) {
            $errors[] = 'Template name cannot exceed 255 characters';
        }
        
        // Validate description
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors[] = 'Template description cannot exceed 1000 characters';
        }
        
        // Validate category
        if (isset($data['category']) && !empty($data['category'])) {
            if (strlen($data['category']) > 100) {
                $errors[] = 'Category name cannot exceed 100 characters';
            }
            
            if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $data['category'])) {
                $errors[] = 'Category name contains invalid characters';
            }
        }
        
        // Validate tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            if (count($data['tags']) > 10) {
                $errors[] = 'Cannot have more than 10 tags';
            }
            
            foreach ($data['tags'] as $tag) {
                if (strlen($tag) > 50) {
                    $errors[] = 'Tag cannot exceed 50 characters';
                }
                
                if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $tag)) {
                    $errors[] = 'Tag contains invalid characters: ' . $tag;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $errors[] = 'No file was uploaded';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            $errors[] = 'File size exceeds maximum allowed size of ' . ($this->max_file_size / 1024 / 1024) . 'MB';
        }
        
        if ($file['size'] == 0) {
            $errors[] = 'File is empty';
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_extensions)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $this->allowed_extensions);
        }
        
        // Validate MIME type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected_mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (isset($this->mime_types[$file_extension])) {
                $expected_mime = $this->mime_types[$file_extension];
                if ($detected_mime !== $expected_mime) {
                    $errors[] = 'File content does not match file extension';
                }
            }
        }
        
        // Additional security checks
        $security_check = $this->performSecurityChecks($file['tmp_name'], $file_extension);
        if (!$security_check['safe']) {
            $errors = array_merge($errors, $security_check['errors']);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_type' => $file_extension,
            'file_size' => $file['size']
        ];
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Perform security checks on uploaded file
     */
    private function performSecurityChecks($file_path, $file_extension) {
        $errors = [];
        
        // Check file signature (magic bytes)
        if (!$this->validateFileSignature($file_path, $file_extension)) {
            $errors[] = 'File signature does not match file type';
        }
        
        // Check for embedded scripts or macros
        if ($this->containsScripts($file_path, $file_extension)) {
            $errors[] = 'File contains potentially dangerous scripts or macros';
        }
        
        // Check file name for suspicious patterns
        if ($this->hasSuspiciousFileName($file_path)) {
            $errors[] = 'File name contains suspicious patterns';
        }
        
        return [
            'safe' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate file signature (magic bytes)
     */
    private function validateFileSignature($file_path, $file_extension) {
        $file_signatures = [
            'pdf' => ['25504446'], // %PDF
            'docx' => ['504B0304'], // ZIP signature (DOCX is ZIP-based)
            'xlsx' => ['504B0304'], // ZIP signature (XLSX is ZIP-based)
            'doc' => ['D0CF11E0'], // OLE signature
            'xls' => ['D0CF11E0']  // OLE signature
        ];
        
        if (!isset($file_signatures[$file_extension])) {
            return true; // No signature check available
        }
        
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            return false;
        }
        
        $bytes = fread($handle, 4);
        fclose($handle);
        
        $hex = strtoupper(bin2hex($bytes));
        
        foreach ($file_signatures[$file_extension] as $signature) {
            if (strpos($hex, $signature) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for embedded scripts or suspicious content
     */
    private function containsScripts($file_path, $file_extension) {
        // For Office documents, we'll do a basic text search
        if (in_array($file_extension, ['docx', 'xlsx'])) {
            return $this->checkOfficeDocumentForScripts($file_path);
        }
        
        // For PDF files, check for JavaScript
        if ($file_extension === 'pdf') {
            return $this->checkPdfForScripts($file_path);
        }
        
        return false;
    }
    
    /**
     * Check Office documents for scripts
     */
    private function checkOfficeDocumentForScripts($file_path) {
        // Read file content
        $content = file_get_contents($file_path);
        
        // Look for suspicious patterns
        $suspicious_patterns = [
            'javascript:',
            'vbscript:',
            'activex',
            'shell.application',
            'wscript.shell',
            'document.write',
            'eval(',
            'onclick=',
            'onload='
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check PDF for JavaScript
     */
    private function checkPdfForScripts($file_path) {
        $content = file_get_contents($file_path);
        
        // Look for JavaScript in PDF
        $js_patterns = [
            '/JavaScript',
            '/JS',
            'this.print',
            'app.alert',
            'eval('
        ];
        
        foreach ($js_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for suspicious file names
     */
    private function hasSuspiciousFileName($file_path) {
        $filename = basename($file_path);
        
        // Check for double extensions
        if (preg_match('/\.[a-z]{2,4}\.[a-z]{2,4}$/i', $filename)) {
            return true;
        }
        
        // Check for executable extensions hidden in name
        $suspicious_extensions = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com'];
        foreach ($suspicious_extensions as $ext) {
            if (stripos($filename, '.' . $ext) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize template name
     */
    public function sanitizeTemplateName($name) {
        // Remove HTML tags
        $name = strip_tags($name);
        
        // Remove special characters except spaces, hyphens, and underscores
        $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
        
        // Replace multiple spaces with single space
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Trim whitespace
        $name = trim($name);
        
        return $name;
    }
    
    /**
     * Validate category name
     */
    public function validateCategoryName($category) {
        if (empty($category)) {
            return true; // Empty category is allowed
        }
        
        // Check length
        if (strlen($category) > 100) {
            return false;
        }
        
        // Check for valid characters
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $category)) {
            return false;
        }
        
        return true;
    }
}
?>