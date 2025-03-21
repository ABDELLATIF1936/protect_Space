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

// Récupération de la connexion à la base de données
try {
    $pdo = getConnection();
} catch (PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

// Gestion des requêtes selon la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Récupération de tous les projets de l'utilisateur
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, 
                    (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_count,
                    (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) as members_count
                FROM projects p
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($projects);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des projets : " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des projets']);
        }
        break;

    case 'POST':
        // Création d'un nouveau projet
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || empty($data['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Le titre est requis']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (title, description, status, user_id, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['status'] ?? 'en cours',
                $_SESSION['user_id']
            ]);
            
            $projectId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Projet créé avec succès',
                'id' => $projectId
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du projet : " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la création du projet']);
        }
        break;

    case 'DELETE':
        // Suppression d'un projet
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID du projet non spécifié']);
            exit;
        }

        try {
            // Vérifier que l'utilisateur est propriétaire du projet
            $stmt = $pdo->prepare("SELECT user_id FROM projects WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $project = $stmt->fetch();

            if (!$project || $project['user_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorisé']);
                exit;
            }

            // Supprimer le projet
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Projet supprimé avec succès'
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du projet : " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression du projet']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
} 