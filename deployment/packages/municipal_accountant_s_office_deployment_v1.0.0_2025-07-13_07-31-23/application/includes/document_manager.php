<?php
/**
 * Document Manager Class
 * Handles document database operations and metadata management
 */

require_once 'document_storage_manager.php';

class DocumentManager {
    private $db;
    private $storage_manager;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->storage_manager = new DocumentStorageManager($database);
    }
    
    /**
     * Create a new document with file upload and metadata
     */
    public function createDocument($document_type_id, $title, $files, $metadata = [], $uploaded_by = null) {
        try {
            // Validate required parameters
            if (empty($title) || empty($document_type_id)) {
                return ['success' => false, 'message' => 'Title and document type are required'];
            }

            // Find all file fields in the document type fields
            $docTypeManager = new DocumentTypeManager(new Database());
            $fields = $docTypeManager->getDocumentTypeFields($document_type_id);
            $file_field_names = [];
            foreach ($fields as $field) {
                if ($field['field_type'] === 'file') {
                    $file_field_names[] = $field['field_name'];
                }
            }

            // Handle multiple file uploads
            $file_results = [];
            foreach ($file_field_names as $file_field_name) {
                if (isset($files['metadata']['name'][$file_field_name]) && $files['metadata']['error'][$file_field_name] === UPLOAD_ERR_OK) {
                    $file_to_upload = [
                        'name' => $files['metadata']['name'][$file_field_name],
                        'type' => $files['metadata']['type'][$file_field_name],
                        'tmp_name' => $files['metadata']['tmp_name'][$file_field_name],
                        'error' => $files['metadata']['error'][$file_field_name],
                        'size' => $files['metadata']['size'][$file_field_name],
                    ];
                    
                    $file_result = $this->storage_manager->storeFile($file_to_upload);
                    if ($file_result['success']) {
                        $file_results[$file_field_name] = $file_result;
                        // Store file path in metadata
                        $metadata[$file_field_name] = $file_result['relative_path'];
                    }
                }
            }

            // Keep the first file for main document (backward compatibility)
            $main_file_result = null;
            if (!empty($file_field_names) && isset($files['metadata']['name'][$file_field_names[0]])) {
                $first_field = $file_field_names[0];
                if (isset($file_results[$first_field])) {
                    $main_file_result = $file_results[$first_field];
                }
            }
            
            // Start database transaction
            $this->db->beginTransaction();
            
            // Insert document record
            $stmt = $this->db->prepare("
                INSERT INTO documents (document_type_id, title, file_name, file_path, file_size, mime_type, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $document_type_id,
                $title,
                $main_file_result ? $main_file_result['filename'] : null,
                $main_file_result ? $main_file_result['relative_path'] : null,
                $main_file_result ? $main_file_result['file_size'] : null,
                $main_file_result ? $main_file_result['mime_type'] : null,
                $uploaded_by
            ]);
            
            $document_id = $this->db->lastInsertId();
            
            // Insert metadata
            if (!empty($metadata)) {
                $this->saveDocumentMetadata($document_id, $metadata);
            }
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Document uploaded successfully',
                'document_id' => $document_id
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            
            // Clean up uploaded files if database operation failed
            foreach ($file_results as $file_result) {
                if (isset($file_result['file_path']) && file_exists($file_result['file_path'])) {
                    unlink($file_result['file_path']);
                }
            }
            
            return ['success' => false, 'message' => 'Error creating document: ' . $e->getMessage()];
        }
    }
    
    /**
     * Save document metadata
     */
    private function saveDocumentMetadata($document_id, $metadata) {
        $stmt = $this->db->prepare("INSERT INTO document_metadata (document_id, field_name, field_value) VALUES (?, ?, ?)");
        
        foreach ($metadata as $field_name => $field_value) {
            if (!empty($field_value)) {
                $stmt->execute([$document_id, $field_name, $field_value]);
            }
        }
    }
    
    /**
     * Get document by ID
     */
    public function getDocumentById($document_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT d.*, dt.name as document_type_name, u.username as uploaded_by_username
                FROM documents d
                LEFT JOIN document_types dt ON d.document_type_id = dt.id
                LEFT JOIN users u ON d.uploaded_by = u.id
                WHERE d.id = ?
            ");
            $stmt->execute([$document_id]);
            $document = $stmt->fetch();

            if ($document) {
                $document['metadata'] = $this->getDocumentMetadata($document_id);
            }

            return $document;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get document metadata
     */
    public function getDocumentMetadata($document_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT dm.field_name, dm.field_value, dtf.field_label, dtf.field_type, bi.file_path as book_image_path
                FROM document_metadata dm
                LEFT JOIN documents d ON dm.document_id = d.id
                LEFT JOIN document_type_fields dtf ON d.document_type_id = dtf.document_type_id AND dm.field_name = dtf.field_name
                LEFT JOIN book_images bi ON dtf.field_type = 'reference' AND dm.field_value = bi.id
                WHERE dm.document_id = ?
            ");
            $stmt->execute([$document_id]);
            $metadata = [];
            while ($row = $stmt->fetch()) {
                $label = $row['field_name'];
                $metadata[$label] = [
                    'value' => $row['field_value'],
                    'type' => $row['field_type'],
                    'label' => $row['field_label'],
                    'book_image_path' => $row['book_image_path'] ?? null
                ];
            }
            return $metadata;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Search documents with filters
     */
    public function searchDocuments($search_query = '', $document_type_filter = '', $location_filter = '', $date_from = '', $date_to = '') {
        try {
            $sql = "
                SELECT d.*, dt.name as document_type_name, u.username as uploaded_by_username,
                       GROUP_CONCAT(CONCAT(dm.field_name, ':', dm.field_value) SEPARATOR ';') as metadata
                FROM documents d
                LEFT JOIN document_types dt ON d.document_type_id = dt.id
                LEFT JOIN users u ON d.uploaded_by = u.id
                LEFT JOIN document_metadata dm ON d.id = dm.document_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Search in title and metadata
            if (!empty($search_query)) {
                $sql .= " AND (d.title LIKE ? OR dm.field_value LIKE ?)";
                $search_param = '%' . $search_query . '%';
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            // Filter by document type
            if (!empty($document_type_filter)) {
                $sql .= " AND d.document_type_id = ?";
                $params[] = $document_type_filter;
            }
            
            // Filter by location (search in metadata)
            if (!empty($location_filter)) {
                $sql .= " AND dm.field_value LIKE ?";
                $params[] = '%' . $location_filter . '%';
            }
            
            // Filter by date range
            if (!empty($date_from)) {
                $sql .= " AND DATE(d.created_at) >= ?";
                $params[] = $date_from;
            }
            
            if (!empty($date_to)) {
                $sql .= " AND DATE(d.created_at) <= ?";
                $params[] = $date_to;
            }
            
            $sql .= " GROUP BY d.id ORDER BY d.created_at DESC LIMIT 50";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Error searching documents: " . $e->getMessage());
        }
    }

    /**
     * Delete a document by ID
     */
    public function deleteDocument($document_id) {
        try {
            $this->db->beginTransaction();

            // Get document file path before deleting the record
            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            $file_path = $document['file_path'] ?? null;

            // Delete associated metadata
            $stmt = $this->db->prepare("DELETE FROM document_metadata WHERE document_id = ?");
            $stmt->execute([$document_id]);

            // Delete document record
            $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);

            // Delete physical file if it exists
            if ($file_path && file_exists(STORAGE_PATH . $file_path)) {
                unlink(STORAGE_PATH . $file_path);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Document deleted successfully.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error deleting document: ' . $e->getMessage()];
        }
    }

    /**
     * Get book image by ID
     */
    public function getBookImageById($image_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM book_images WHERE id = ?");
            $stmt->execute([$image_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Update a document with new file upload and metadata
     */
    public function updateDocument($document_id, $title, $files, $metadata = []) {
        try {
            // Validate required parameters
            if (empty($title) || empty($document_id)) {
                return ['success' => false, 'message' => 'Title and document ID are required'];
            }

            // Start database transaction
            $this->db->beginTransaction();

            // Update document record
            $stmt = $this->db->prepare("UPDATE documents SET title = ? WHERE id = ?");
            $stmt->execute([$title, $document_id]);

            // Find the document type ID to get its fields
            $stmt = $this->db->prepare("SELECT document_type_id FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            $document_type_id = $stmt->fetchColumn();

            if (!$document_type_id) {
                throw new Exception("Document type not found for the given document ID.");
            }

            // Find all file fields in the document type fields
            $docTypeManager = new DocumentTypeManager(new Database());
            $fields = $docTypeManager->getDocumentTypeFields($document_type_id);
            $file_field_names = [];
            foreach ($fields as $field) {
                if ($field['field_type'] === 'file') {
                    $file_field_names[] = $field['field_name'];
                }
            }

            // Handle multiple file uploads and clearing
            $file_results = [];
            $main_file_result = null;
            
            foreach ($file_field_names as $file_field_name) {
                // Handle file clearing if requested
                if (isset($metadata[$file_field_name . '_clear_file']) && $metadata[$file_field_name . '_clear_file'] === '1') {
                    // Get existing file path from metadata to delete it
                    $stmt = $this->db->prepare("SELECT field_value FROM document_metadata WHERE document_id = ? AND field_name = ?");
                    $stmt->execute([$document_id, $file_field_name]);
                    $old_file_path = $stmt->fetchColumn();

                    // Delete old physical file
                    if ($old_file_path && file_exists(STORAGE_PATH . $old_file_path)) {
                        unlink(STORAGE_PATH . $old_file_path);
                    }

                    // Remove the file path from metadata (will be handled by updateDocumentMetadata)
                    unset($metadata[$file_field_name]);
                    
                    // Don't clear main document record when clearing file fields
                    // File fields are separate from the main document file
                } else if (isset($files['metadata']['name'][$file_field_name]) && $files['metadata']['error'][$file_field_name] === UPLOAD_ERR_OK) {
                    // Handle new file upload
                    $file_to_upload = [
                        'name' => $files['metadata']['name'][$file_field_name],
                        'type' => $files['metadata']['type'][$file_field_name],
                        'tmp_name' => $files['metadata']['tmp_name'][$file_field_name],
                        'error' => $files['metadata']['error'][$file_field_name],
                        'size' => $files['metadata']['size'][$file_field_name],
                    ];

                    // Get existing file path from metadata to delete it
                    $stmt = $this->db->prepare("SELECT field_value FROM document_metadata WHERE document_id = ? AND field_name = ?");
                    $stmt->execute([$document_id, $file_field_name]);
                    $old_file_path = $stmt->fetchColumn();

                    // Store the new file
                    $file_result = $this->storage_manager->storeFile($file_to_upload);
                    if (!$file_result['success']) {
                        throw new Exception('File upload failed for ' . $file_field_name . ': ' . implode(', ', $file_result['errors']));
                    }

                    $file_results[$file_field_name] = $file_result;
                    
                    // Store file path in metadata
                    $metadata[$file_field_name] = $file_result['relative_path'];

                    // Don't update main document record for file field uploads during edit
                    // This prevents duplication - file fields should only appear in metadata

                    // Delete old physical file
                    if ($old_file_path && file_exists(STORAGE_PATH . $old_file_path)) {
                        unlink(STORAGE_PATH . $old_file_path);
                    }
                }
            }

            // Update metadata
            $this->updateDocumentMetadata($document_id, $metadata);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Document updated successfully',
                'document_id' => $document_id
            ];

        } catch (Exception $e) {
            $this->db->rollBack();

            // Clean up newly uploaded files if database operation failed
            foreach ($file_results as $file_result) {
                if (isset($file_result['file_path']) && file_exists($file_result['file_path'])) {
                    unlink($file_result['file_path']);
                }
            }

            return ['success' => false, 'message' => 'Error updating document: ' . $e->getMessage()];
        }
    }

    /**
     * Update document metadata
     */
    private function updateDocumentMetadata($document_id, $metadata) {
        // Get existing metadata to preserve file fields that weren't updated
        $stmt = $this->db->prepare("SELECT field_name, field_value FROM document_metadata WHERE document_id = ?");
        $stmt->execute([$document_id]);
        $existing_metadata = [];
        while ($row = $stmt->fetch()) {
            $existing_metadata[$row['field_name']] = $row['field_value'];
        }

        // Get document type fields to identify file fields
        $stmt = $this->db->prepare("SELECT document_type_id FROM documents WHERE id = ?");
        $stmt->execute([$document_id]);
        $document_type_id = $stmt->fetchColumn();
        
        $docTypeManager = new DocumentTypeManager(new Database());
        $fields = $docTypeManager->getDocumentTypeFields($document_type_id);
        $file_field_names = [];
        foreach ($fields as $field) {
            if ($field['field_type'] === 'file') {
                $file_field_names[] = $field['field_name'];
            }
        }

        // Preserve existing file fields that weren't updated
        foreach ($file_field_names as $file_field_name) {
            if (!isset($metadata[$file_field_name]) && isset($existing_metadata[$file_field_name])) {
                // File field wasn't updated, preserve existing value
                $metadata[$file_field_name] = $existing_metadata[$file_field_name];
            }
        }

        // Now delete existing metadata and insert updated metadata
        $stmt = $this->db->prepare("DELETE FROM document_metadata WHERE document_id = ?");
        $stmt->execute([$document_id]);

        // Insert all metadata (existing preserved + new/updated)
        $stmt = $this->db->prepare("INSERT INTO document_metadata (document_id, field_name, field_value) VALUES (?, ?, ?)");
        foreach ($metadata as $field_name => $field_value) {
            if (!empty($field_value)) {
                $stmt->execute([$document_id, $field_name, $field_value]);
            }
        }
    }
}
?>