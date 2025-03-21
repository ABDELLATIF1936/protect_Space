<?php
require_once '../config/database.php';

class Project {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function createProject($title, $description, $createdBy) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO projects (title, description, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$title, $description, $createdBy]);
            $projectId = $this->conn->lastInsertId();

            // Ajouter le créateur comme propriétaire du projet
            $this->addMember($projectId, $createdBy, 'owner');
            return $projectId;
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getProject($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT p.*, u.full_name as creator_name 
                FROM projects p 
                JOIN users u ON p.created_by = u.id 
                WHERE p.id = ?");
            $stmt->execute([$projectId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getUserProjects($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT p.*, pm.role as member_role 
                FROM projects p 
                JOIN project_members pm ON p.id = pm.project_id 
                WHERE pm.user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function addMember($projectId, $userId, $role = 'member') {
        try {
            $stmt = $this->conn->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
            return $stmt->execute([$projectId, $userId, $role]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getProjectMembers($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT u.*, pm.role as project_role 
                FROM users u 
                JOIN project_members pm ON u.id = pm.user_id 
                WHERE pm.project_id = ?");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function updateProjectStatus($projectId, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $projectId]);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function calculateProjectProgress($projectId) {
        try {
            $stmt = $this->conn->prepare("SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'termine' THEN 1 ELSE 0 END) as completed_tasks
                FROM tasks
                WHERE project_id = ?");
            $stmt->execute([$projectId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total_tasks'] > 0) {
                return ($result['completed_tasks'] / $result['total_tasks']) * 100;
            }
            return 0;
        } catch(PDOException $e) {
            return 0;
        }
    }
} 