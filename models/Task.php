<?php
require_once '../config/database.php';

class Task {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function createTask($projectId, $title, $description, $assignedTo, $dueDate) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO tasks (project_id, title, description, assigned_to, due_date) 
                VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$projectId, $title, $description, $assignedTo, $dueDate]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getProjectTasks($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT t.*, u.full_name as assigned_to_name 
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id 
                WHERE t.project_id = ?
                ORDER BY t.due_date ASC");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getUserTasks($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT t.*, p.title as project_title 
                FROM tasks t 
                JOIN projects p ON t.project_id = p.id 
                WHERE t.assigned_to = ?
                ORDER BY t.due_date ASC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function updateTaskStatus($taskId, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $taskId]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function reassignTask($taskId, $newAssigneeId) {
        try {
            $stmt = $this->conn->prepare("UPDATE tasks SET assigned_to = ? WHERE id = ?");
            return $stmt->execute([$newAssigneeId, $taskId]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function updateDueDate($taskId, $newDueDate) {
        try {
            $stmt = $this->conn->prepare("UPDATE tasks SET due_date = ? WHERE id = ?");
            return $stmt->execute([$newDueDate, $taskId]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getTaskDetails($taskId) {
        try {
            $stmt = $this->conn->prepare("SELECT t.*, 
                u.full_name as assigned_to_name,
                p.title as project_title
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id 
                JOIN projects p ON t.project_id = p.id
                WHERE t.id = ?");
            $stmt->execute([$taskId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
} 