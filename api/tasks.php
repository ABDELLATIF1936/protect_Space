<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 401,
        'message' => 'Non authentifié'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        $projectId = $_GET['project_id'] ?? null;
        
        if (!$projectId) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'ID du projet manquant'
            ]);
            exit;
        }

        try {
            // Vérifier que l'utilisateur a accès au projet
            $stmt = $pdo->prepare("
                SELECT 1 FROM project_members 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$projectId, $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode([
                    'status' => 403,
                    'message' => 'Accès non autorisé'
                ]);
                exit;
            }

            // Récupérer les tâches du projet
            $stmt = $pdo->prepare("
                SELECT t.*, u.full_name as assigned_to_name
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?
                ORDER BY t.due_date ASC
            ");
            $stmt->execute([$projectId]);
            $tasks = $stmt->fetchAll();

            echo json_encode([
                'status' => 200,
                'tasks' => $tasks
            ]);
        } catch (Exception $e) {
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la récupération des tâches'
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['project_id']) || empty($data['title'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'Projet ID et titre requis'
            ]);
            exit;
        }

        try {
            // Vérifier que l'utilisateur a accès au projet
            $stmt = $pdo->prepare("
                SELECT 1 FROM project_members 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$data['project_id'], $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode([
                    'status' => 403,
                    'message' => 'Accès non autorisé'
                ]);
                exit;
            }

            // Créer la tâche
            $stmt = $pdo->prepare("
                INSERT INTO tasks (project_id, title, description, assigned_to, due_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['project_id'],
                $data['title'],
                $data['description'] ?? null,
                $data['assigned_to'] ?? null,
                $data['due_date'] ?? null
            ]);

            $taskId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'status' => 201,
                'message' => 'Tâche créée avec succès',
                'task_id' => $taskId
            ]);
        } catch (Exception $e) {
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la création de la tâche'
            ]);
        }
        break;

    case 'PUT':
        $taskId = $_GET['id'] ?? null;
        if (!$taskId) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'ID de la tâche manquant'
            ]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            // Vérifier que l'utilisateur a accès à la tâche
            $stmt = $pdo->prepare("
                SELECT t.* FROM tasks t
                JOIN project_members pm ON t.project_id = pm.project_id
                WHERE t.id = ? AND pm.user_id = ?
            ");
            $stmt->execute([$taskId, $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode([
                    'status' => 403,
                    'message' => 'Accès non autorisé'
                ]);
                exit;
            }

            // Mettre à jour la tâche
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET title = ?, description = ?, assigned_to = ?, due_date = ?, completed = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['assigned_to'],
                $data['due_date'],
                $data['completed'] ? 1 : 0,
                $taskId
            ]);

            echo json_encode([
                'status' => 200,
                'message' => 'Tâche mise à jour avec succès'
            ]);
        } catch (Exception $e) {
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la mise à jour de la tâche'
            ]);
        }
        break;

    case 'DELETE':
        $taskId = $_GET['id'] ?? null;
        if (!$taskId) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'ID de la tâche manquant'
            ]);
            exit;
        }

        try {
            // Vérifier que l'utilisateur a accès à la tâche
            $stmt = $pdo->prepare("
                SELECT t.* FROM tasks t
                JOIN project_members pm ON t.project_id = pm.project_id
                WHERE t.id = ? AND pm.user_id = ? AND pm.role = 'owner'
            ");
            $stmt->execute([$taskId, $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode([
                    'status' => 403,
                    'message' => 'Accès non autorisé'
                ]);
                exit;
            }

            // Supprimer la tâche
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);

            echo json_encode([
                'status' => 200,
                'message' => 'Tâche supprimée avec succès'
            ]);
        } catch (Exception $e) {
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la suppression de la tâche'
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'status' => 405,
            'message' => 'Méthode non autorisée'
        ]);
        break;
} 