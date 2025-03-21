<?php
require_once '../config/database.php';

class Document {
    private $conn;
    private $uploadDir = '../uploads/';

    public function __construct() {
        $this->conn = getDBConnection();
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function uploadDocument($projectId, $uploadedBy, $file) {
        try {
            $fileName = time() . '_' . basename($file['name']);
            $targetPath = $this->uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $this->conn->prepare("INSERT INTO documents (project_id, name, file_path, uploaded_by) 
                    VALUES (?, ?, ?, ?)");
                return $stmt->execute([$projectId, $file['name'], $fileName, $uploadedBy]);
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getProjectDocuments($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT d.*, u.full_name as uploader_name 
                FROM documents d 
                JOIN users u ON d.uploaded_by = u.id 
                WHERE d.project_id = ?
                ORDER BY d.uploaded_at DESC");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getDocument($documentId) {
        try {
            $stmt = $this->conn->prepare("SELECT d.*, u.full_name as uploader_name 
                FROM documents d 
                JOIN users u ON d.uploaded_by = u.id 
                WHERE d.id = ?");
            $stmt->execute([$documentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function deleteDocument($documentId) {
        try {
            $document = $this->getDocument($documentId);
            if ($document) {
                $filePath = $this->uploadDir . $document['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                $stmt = $this->conn->prepare("DELETE FROM documents WHERE id = ?");
                return $stmt->execute([$documentId]);
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function searchDocuments($projectId, $searchTerm) {
        try {
            $searchTerm = "%$searchTerm%";
            $stmt = $this->conn->prepare("SELECT d.*, u.full_name as uploader_name 
                FROM documents d 
                JOIN users u ON d.uploaded_by = u.id 
                WHERE d.project_id = ? 
                AND d.name LIKE ?
                ORDER BY d.uploaded_at DESC");
            $stmt->execute([$projectId, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getDocumentPath($documentId) {
        $document = $this->getDocument($documentId);
        if ($document) {
            return $this->uploadDir . $document['file_path'];
        }
        return false;
    }
} 