<?php
require_once '../config/database.php';

class Message {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function sendMessage($projectId, $senderId, $content) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO messages (project_id, sender_id, content) VALUES (?, ?, ?)");
            return $stmt->execute([$projectId, $senderId, $content]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getProjectMessages($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT m.*, u.full_name as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE m.project_id = ?
                ORDER BY m.created_at DESC");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getLatestMessages($projectId, $limit = 20) {
        try {
            $stmt = $this->conn->prepare("SELECT m.*, u.full_name as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE m.project_id = ?
                ORDER BY m.created_at DESC 
                LIMIT ?");
            $stmt->execute([$projectId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getMessagesBetweenDates($projectId, $startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("SELECT m.*, u.full_name as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE m.project_id = ? 
                AND m.created_at BETWEEN ? AND ?
                ORDER BY m.created_at DESC");
            $stmt->execute([$projectId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function searchMessages($projectId, $searchTerm) {
        try {
            $searchTerm = "%$searchTerm%";
            $stmt = $this->conn->prepare("SELECT m.*, u.full_name as sender_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE m.project_id = ? 
                AND m.content LIKE ?
                ORDER BY m.created_at DESC");
            $stmt->execute([$projectId, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
} 