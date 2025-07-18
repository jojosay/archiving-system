<?php
/**
 * PDF Manager Class
 * Enhanced PDF handling with validation, optimization, and metadata extraction
 */

class PDFManager {
    private $max_file_size;
    private $storage_path;
    
    public function __construct() {
        $this->max_file_size = 50 * 1024 * 1024; // 50MB for PDFs
        $this->storage_path = __DIR__ . '/../storage/documents/';
    }
    
    /**
     * Enhanced PDF validation
     */
    public function validatePDF($file) {
        $errors = [];
        
        // Basic file validation
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'PDF upload failed';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            $errors[] = 'PDF file size exceeds maximum limit of ' . ($this->max_file_size / 1024 / 1024) . 'MB';
        }
        
        // Check file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            $errors[] = 'File must be a PDF document';
        }
        
        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type !== 'application/pdf') {
            $errors[] = 'Invalid PDF file detected';
        }
        
        // Check if file is actually a PDF by reading header
        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle) {
            $header = fread($handle, 5);
            fclose($handle);
            
            if (substr($header, 0, 4) !== '%PDF') {
                $errors[] = 'File does not appear to be a valid PDF document';
            }
        }
        
        return $errors;
    }
    
    /**
     * Extract PDF metadata
     */
    public function extractPDFMetadata($file_path) {
        $metadata = [
            'pages' => null,
            'title' => null,
            'author' => null,
            'subject' => null,
            'creator' => null,
            'creation_date' => null,
            'modification_date' => null,
            'file_size' => filesize($file_path),
            'file_size_formatted' => $this->formatFileSize(filesize($file_path))
        ];
        
        try {
            // Try to extract basic info using shell command if available
            if (function_exists('shell_exec') && !empty(shell_exec('which pdfinfo'))) {
                $output = shell_exec('pdfinfo "' . escapeshellarg($file_path) . '" 2>/dev/null');
                if ($output) {
                    $lines = explode("\n", $output);
                    foreach ($lines as $line) {
                        if (strpos($line, 'Pages:') === 0) {
                            $metadata['pages'] = (int)trim(substr($line, 6));
                        } elseif (strpos($line, 'Title:') === 0) {
                            $metadata['title'] = trim(substr($line, 6));
                        } elseif (strpos($line, 'Author:') === 0) {
                            $metadata['author'] = trim(substr($line, 7));
                        } elseif (strpos($line, 'Subject:') === 0) {
                            $metadata['subject'] = trim(substr($line, 8));
                        } elseif (strpos($line, 'Creator:') === 0) {
                            $metadata['creator'] = trim(substr($line, 8));
                        } elseif (strpos($line, 'CreationDate:') === 0) {
                            $metadata['creation_date'] = trim(substr($line, 13));
                        } elseif (strpos($line, 'ModDate:') === 0) {
                            $metadata['modification_date'] = trim(substr($line, 8));
                        }
                    }
                }
            }
            
            // Fallback: Try to count pages by reading PDF structure
            if ($metadata['pages'] === null) {
                $metadata['pages'] = $this->countPDFPages($file_path);
            }
            
        } catch (Exception $e) {
            error_log('PDF metadata extraction error: ' . $e->getMessage());
        }
        
        return $metadata;
    }
    
    /**
     * Count PDF pages by parsing the file
     */
    private function countPDFPages($file_path) {
        try {
            $content = file_get_contents($file_path);
            if ($content === false) {
                return null;
            }
            
            // Look for /Count in the pages object
            if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
                return (int)$matches[1];
            }
            
            // Fallback: count /Page objects
            $page_count = preg_match_all('/\/Page\W/', $content);
            return $page_count > 0 ? $page_count : null;
            
        } catch (Exception $e) {
            error_log('PDF page counting error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Format file size in human readable format
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Generate PDF thumbnail (requires ImageMagick)
     */
    public function generateThumbnail($pdf_path, $output_path = null) {
        if (!$output_path) {
            $output_path = str_replace('.pdf', '_thumb.jpg', $pdf_path);
        }
        
        try {
            // Check if ImageMagick is available
            if (function_exists('shell_exec') && !empty(shell_exec('which convert'))) {
                $command = sprintf(
                    'convert "%s[0]" -thumbnail 200x200 -background white -alpha remove "%s" 2>/dev/null',
                    escapeshellarg($pdf_path),
                    escapeshellarg($output_path)
                );
                
                $result = shell_exec($command);
                
                if (file_exists($output_path)) {
                    return $output_path;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('PDF thumbnail generation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate PDF is not corrupted
     */
    public function validatePDFIntegrity($file_path) {
        try {
            // Check if file can be opened and read
            $handle = fopen($file_path, 'rb');
            if (!$handle) {
                return false;
            }
            
            // Check PDF header
            $header = fread($handle, 8);
            if (substr($header, 0, 4) !== '%PDF') {
                fclose($handle);
                return false;
            }
            
            // Check for EOF marker
            fseek($handle, -1024, SEEK_END);
            $tail = fread($handle, 1024);
            fclose($handle);
            
            if (strpos($tail, '%%EOF') === false) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('PDF integrity validation error: ' . $e->getMessage());
            return false;
        }
    }
}
?>