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
        try {
            // Récupérer tous les projets de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT p.* 
                FROM projects p
                JOIN project_members pm ON p.id = pm.project_id
                WHERE pm.user_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$userId]);
            $projects = $stmt->fetchAll();

            echo json_encode([
                'status' => 200,
                'projects' => $projects
            ]);
        } catch (Exception $e) {
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la récupération des projets'
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'Le titre est requis'
            ]);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Créer le projet
            $stmt = $pdo->prepare("
                INSERT INTO projects (title, description, status) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['status'] ?? 'en_cours'
            ]);
            $projectId = $pdo->lastInsertId();

            // Ajouter l'utilisateur comme propriétaire du projet
            $stmt = $pdo->prepare("
                INSERT INTO project_members (project_id, user_id, role)
                VALUES (?, ?, 'owner')
            ");
            $stmt->execute([$projectId, $userId]);

            $pdo->commit();

            http_response_code(201);
            echo json_encode([
                'status' => 201,
                'message' => 'Projet créé avec succès',
                'project_id' => $projectId
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la création du projet'
            ]);
        }
        break;

    case 'PUT':
        $projectId = $_GET['id'] ?? null;
        if (!$projectId) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'ID du projet manquant'
            ]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            // Vérifier que l'utilisateur a les droits sur le projet
            $stmt = $pdo->prepare("
                SELECT role FROM project_members 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$projectId, $userId]);
            $member = $stmt->fetch();

            if (!$member || $member['role'] !== 'owner') {
                http_response_code(403);
                echo json_encode([
                    'status' => 403,
                    'message' => 'Accès non autorisé'
                ]);
                exit;
            }

            // Mettre à jour le projet
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET title = ?, description = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['status'],
                $projectId
            ]);

            echo json_encode([
                'status' => 200,
                'message' => 'Projet mis à jour avec succès'
            ]);
        } catch (Exception $e) {
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la mise à jour du projet'
            ]);
        }
        break;

    case 'DELETE':
        $projectId = $_GET['id'] ?? null;
        if (!$projectId) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'ID du projet manquant'
            ]);
            exit;
        }

        try {
            // Vérifier que l'utilisateur a les droits sur le projet
            $stmt = $pdo->prepare("
                SELECT role FROM project_members 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$projectId, $userId]);
            $member = $stmt->fetch();

            if (!$member || $member['role'] !== 'owner') {
                http_response_code(403);
                echo json_encode([
                    'status' => 403,
                    'message' => 'Accès non autorisé'
                ]);
                exit;
            }

            // Supprimer le projet
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);

            echo json_encode([
                'status' => 200,
                'message' => 'Projet supprimé avec succès'
            ]);
        } catch (Exception $e) {
            error_log('Erreur SQL: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Erreur lors de la suppression du projet'
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