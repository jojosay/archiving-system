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
    private $pdf_manager;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->storage_path = __DIR__ . '/../storage/documents/';
        $this->allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $this->max_file_size = 10 * 1024 * 1024; // 10MB (50MB for PDFs)
        
        // Initialize PDF manager
        require_once 'pdf_manager.php';
        $this->pdf_manager = new PDFManager();
        
        // Create storage directory if it doesn't exist
        if (!is_dir($this->storage_path)) {
            mkdir($this->storage_path, 0755, true);
        }
    }
    
    /**
     * Validate uploaded file with enhanced PDF support
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        // Get file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check file type
        if (!in_array($file_extension, $this->allowed_types)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $this->allowed_types);
        }
        
        // Enhanced PDF validation
        if ($file_extension === 'pdf') {
            $pdf_errors = $this->pdf_manager->validatePDF($file);
            $errors = array_merge($errors, $pdf_errors);
        } else {
            // Standard file size check for non-PDFs
            if ($file['size'] > $this->max_file_size) {
                $errors[] = 'File size exceeds maximum limit of ' . ($this->max_file_size / 1024 / 1024) . 'MB';
            }
            
            // Check MIME type for additional security
            $allowed_mimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (isset($allowed_mimes[$file_extension]) && $mime_type !== $allowed_mimes[$file_extension]) {
                $errors[] = 'Invalid file type detected';
            }
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
     * Store uploaded file securely with enhanced PDF support
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
                $result = [
                    'success' => true,
                    'filename' => $filename,
                    'file_path' => $file_path,
                    'relative_path' => 'storage/documents/' . $filename,
                    'original_name' => $file['name'],
                    'file_size' => $file['size'],
                    'mime_type' => mime_content_type($file_path)
                ];
                
                // Extract PDF metadata if it's a PDF file
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($file_extension === 'pdf') {
                    $pdf_metadata = $this->pdf_manager->extractPDFMetadata($file_path);
                    $result['pdf_metadata'] = $pdf_metadata;
                    
                    // Validate PDF integrity
                    $result['pdf_valid'] = $this->pdf_manager->validatePDFIntegrity($file_path);
                    
                    // Try to generate thumbnail
                    $thumbnail_path = $this->pdf_manager->generateThumbnail($file_path);
                    if ($thumbnail_path) {
                        $result['thumbnail_path'] = $thumbnail_path;
                    }
                }
                
                return $result;
            } else {
                return ['success' => false, 'errors' => ['Failed to move uploaded file']];
            }
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['Storage error: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get PDF metadata for an existing file
     */
    public function getPDFMetadata($filename) {
        $file_path = $this->storage_path . $filename;
        
        if (!file_exists($file_path)) {
            return null;
        }
        
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            return null;
        }
        
        return $this->pdf_manager->extractPDFMetadata($file_path);
    }
    
    /**
     * Generate secure URL for PDF viewing
     */
    public function getPDFViewUrl($filename, $document_id = null) {
        $base_url = rtrim(BASE_URL, '/');
        $url = $base_url . '/api/serve_pdf.php?file=' . urlencode($filename);
        
        if ($document_id) {
            $url .= '&document_id=' . intval($document_id);
        }
        
        return $url;
    }
}
?>