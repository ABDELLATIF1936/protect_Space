<?php
header('Content-Type: application/json');
session_start();

require_once 'db_connect.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $pdo = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (!isset($_GET['project_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du projet manquant']);
                exit;
            }

            // Vérifier l'accès au projet
            $stmt = $pdo->prepare("
                SELECT 1 FROM project_members 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$_GET['project_id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès non autorisé']);
                exit;
            }

            // Récupérer les tâches du projet
            $stmt = $pdo->prepare("
                SELECT t.*, 
                       u.full_name as assigned_to_name
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$_GET['project_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['project_id']) || empty($data['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données manquantes']);
                exit;
            }

            // Vérifier l'accès au projet
            $stmt = $pdo->prepare("
                SELECT 1 FROM project_members 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$data['project_id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès non autorisé']);
                exit;
            }

            // Créer la tâche
            $stmt = $pdo->prepare("
                INSERT INTO tasks (project_id, title, description, status, priority, due_date, assigned_to)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['project_id'],
                $data['title'],
                $data['description'] ?? null,
                $data['status'] ?? 'à faire',
                $data['priority'] ?? 'moyenne',
                $data['due_date'] ?? null,
                $data['assigned_to'] ?? null
            ]);

            $taskId = $pdo->lastInsertId();

            // Mettre à jour le score de l'utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO activity_scores (user_id, project_id, action_type, points)
                VALUES (?, ?, 'task_completed', 2)
            ");
            $stmt->execute([$_SESSION['user_id'], $data['project_id']]);

            // Récupérer la tâche créée
            $stmt = $pdo->prepare("
                SELECT t.*, 
                       u.full_name as assigned_to_name
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Tâche créée avec succès',
                'task' => $task
            ]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de la tâche manquant']);
                exit;
            }

            // Vérifier l'accès à la tâche
            $stmt = $pdo->prepare("
                SELECT t.project_id 
                FROM tasks t
                JOIN project_members pm ON t.project_id = pm.project_id
                WHERE t.id = ? AND pm.user_id = ?
            ");
            $stmt->execute([$data['id'], $_SESSION['user_id']]);
            $task = $stmt->fetch();

            if (!$task) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès non autorisé']);
                exit;
            }

            // Mettre à jour la tâche
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET title = ?,
                    description = ?,
                    status = ?,
                    priority = ?,
                    due_date = ?,
                    assigned_to = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['status'] ?? 'à faire',
                $data['priority'] ?? 'moyenne',
                $data['due_date'] ?? null,
                $data['assigned_to'] ?? null,
                $data['id']
            ]);

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de la tâche manquant']);
                exit;
            }

            // Vérifier l'accès à la tâche
            $stmt = $pdo->prepare("
                SELECT t.project_id 
                FROM tasks t
                JOIN project_members pm ON t.project_id = pm.project_id
                WHERE t.id = ? AND pm.user_id = ?
            ");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès non autorisé']);
                exit;
            }

            // Supprimer la tâche
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
} 