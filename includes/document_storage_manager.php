<?php
/**
 * Document Storage Manager Class
 * Handles secure file storage and document management
 */

class DocumentStorageManager {
    private $db;
    private $storage_path;
    private $allowed_types;
    private $max_file_size;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->storage_path = __DIR__ . '/../storage/documents/';
        $this->allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $this->max_file_size = 10 * 1024 * 1024; // 10MB
        
        // Create storage directory if it doesn't exist
        if (!is_dir($this->storage_path)) {
            mkdir($this->storage_path, 0755, true);
        }
    }
    
    /**
     * Validate uploaded file
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            $errors[] = 'File size exceeds maximum limit of ' . ($this->max_file_size / 1024 / 1024) . 'MB';
        }
        
        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_types)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $this->allowed_types);
        }
        
        // Check MIME type for additional security
        $allowed_mimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_mimes)) {
            $errors[] = 'Invalid file type detected';
        }
        
        return $errors;
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($original_name) {
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $unique_id = uniqid() . '_' . time();
        return $unique_id . '.' . $extension;
    }
    
    /**
     * Store uploaded file securely
     */
    public function storeFile($file) {
        // Validate file first
        $validation_errors = $this->validateFile($file);
        if (!empty($validation_errors)) {
            return ['success' => false, 'errors' => $validation_errors];
        }
        
        try {
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file['name']);
            $file_path = $this->storage_path . $filename;
            
            // Move uploaded file to storage directory
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'file_path' => $file_path,
                    'relative_path' => 'storage/documents/' . $filename,
                    'original_name' => $file['name'],
                    'file_size' => $file['size'],
                    'mime_type' => mime_content_type($file_path)
                ];
            } else {
                return ['success' => false, 'errors' => ['Failed to move uploaded file']];
            }
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['Storage error: ' . $e->getMessage()]];
        }
    }
}
?>