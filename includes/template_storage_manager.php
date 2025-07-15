<?php
/**
 * Template Storage Manager Class
 * Handles secure file storage operations for templates
 */

class TemplateStorageManager {
    private $storage_base_path;
    private $allowed_types;
    private $max_file_size;
    
    public function __construct() {
        $this->storage_base_path = __DIR__ . '/../storage/templates/';
        $this->allowed_types = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'pdf' => 'application/pdf'
        ];
        $this->max_file_size = 50 * 1024 * 1024; // 50MB
        
        $this->createStorageDirectories();
    }
    
    /**
     * Create storage directory structure
     */
    private function createStorageDirectories() {
        $directories = [
            $this->storage_base_path,
            $this->storage_base_path . 'docx/',
            $this->storage_base_path . 'excel/',
            $this->storage_base_path . 'pdf/',
            $this->storage_base_path . 'temp/'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
                
                // Add .htaccess for security
                $htaccess_content = "Order Deny,Allow\nDeny from all\n";
                file_put_contents($dir . '.htaccess', $htaccess_content);
            }
        }
    }
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file was uploaded or upload failed';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            $errors[] = 'File size exceeds maximum allowed size of ' . ($this->max_file_size / 1024 / 1024) . 'MB';
        }
        
        // Get file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check allowed file types
        if (!array_key_exists($file_extension, $this->allowed_types)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', array_keys($this->allowed_types));
        }
        
        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $expected_mime = $this->allowed_types[$file_extension];
        if ($detected_mime !== $expected_mime) {
            $errors[] = 'File content does not match file extension';
        }
        
        // Check for malicious content (basic check)
        if ($this->containsMaliciousContent($file['tmp_name'])) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_type' => $file_extension,
            'mime_type' => $detected_mime,
            'file_size' => $file['size']
        ];
    }
    
    /**
     * Store uploaded template file
     */
    public function storeTemplate($file, $template_name) {
        $validation = $this->validateFile($file);
        
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        try {
            $file_extension = $validation['file_type'];
            $file_type_dir = $this->getFileTypeDirectory($file_extension);
            
            // Generate unique filename
            $safe_name = $this->sanitizeFileName($template_name);
            $unique_id = uniqid();
            $new_filename = $safe_name . '_' . $unique_id . '.' . $file_extension;
            $destination_path = $this->storage_base_path . $file_type_dir . '/' . $new_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $destination_path)) {
                return ['success' => false, 'errors' => ['Failed to move uploaded file']];
            }
            
            // Set proper permissions
            chmod($destination_path, 0644);
            
            return [
                'success' => true,
                'file_path' => $file_type_dir . '/' . $new_filename,
                'file_name' => $new_filename,
                'original_name' => $file['name'],
                'file_size' => $validation['file_size'],
                'file_type' => $validation['file_type'],
                'mime_type' => $validation['mime_type']
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['Storage error: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get file type directory
     */
    private function getFileTypeDirectory($file_extension) {
        switch ($file_extension) {
            case 'docx':
            case 'doc':
                return 'docx';
            case 'xlsx':
            case 'xls':
                return 'excel';
            case 'pdf':
                return 'pdf';
            default:
                return 'other';
        }
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFileName($filename) {
        // Remove file extension if present
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        
        // Replace spaces and special characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $filename);
        
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Trim underscores from ends
        $filename = trim($filename, '_');
        
        // Limit length
        if (strlen($filename) > 50) {
            $filename = substr($filename, 0, 50);
        }
        
        return $filename ?: 'template';
    }
    
    /**
     * Basic malicious content detection
     */
    private function containsMaliciousContent($file_path) {
        // Read first 1KB of file for basic checks
        $content = file_get_contents($file_path, false, null, 0, 1024);
        
        // Check for common malicious patterns
        $malicious_patterns = [
            '<?php',
            '<%',
            '<script',
            'javascript:',
            'vbscript:',
            'onload=',
            'onerror='
        ];
        
        foreach ($malicious_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get template file path
     */
    public function getTemplateFilePath($relative_path) {
        return $this->storage_base_path . $relative_path;
    }
    
    /**
     * Check if template file exists
     */
    public function templateFileExists($relative_path) {
        $full_path = $this->getTemplateFilePath($relative_path);
        return file_exists($full_path) && is_readable($full_path);
    }
    
    /**
     * Delete template file
     */
    public function deleteTemplateFile($relative_path) {
        try {
            $full_path = $this->getTemplateFilePath($relative_path);
            
            if (file_exists($full_path)) {
                return unlink($full_path);
            }
            
            return true; // File doesn't exist, consider it deleted
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get file size
     */
    public function getFileSize($relative_path) {
        $full_path = $this->getTemplateFilePath($relative_path);
        
        if (file_exists($full_path)) {
            return filesize($full_path);
        }
        
        return 0;
    }
    
    /**
     * Get file info for download
     */
    public function getFileForDownload($relative_path) {
        $full_path = $this->getTemplateFilePath($relative_path);
        
        if (!file_exists($full_path) || !is_readable($full_path)) {
            return null;
        }
        
        return [
            'path' => $full_path,
            'size' => filesize($full_path),
            'mime_type' => mime_content_type($full_path)
        ];
    }
    
    /**
     * Clean up temporary files
     */
    public function cleanupTempFiles($older_than_hours = 24) {
        $temp_dir = $this->storage_base_path . 'temp/';
        $cutoff_time = time() - ($older_than_hours * 3600);
        
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
}
?>