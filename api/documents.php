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

            // Récupérer les documents du projet
            $stmt = $pdo->prepare("
                SELECT d.*, u.full_name as uploaded_by
                FROM documents d
                JOIN users u ON d.user_id = u.id
                WHERE d.project_id = ?
                ORDER BY d.created_at DESC
            ");
            $stmt->execute([$_GET['project_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'POST':
            if (!isset($_POST['project_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du projet manquant']);
                exit;
            }

            // Vérifier l'accès au projet
            $stmt = $pdo->prepare("
                SELECT 1 FROM project_members 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$_POST['project_id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès non autorisé']);
                exit;
            }

            // Vérifier si un fichier a été uploadé
            if (!isset($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucun fichier uploadé']);
                exit;
            }

            $file = $_FILES['file'];
            $fileName = basename($file['name']);
            $targetDir = '../uploads/';
            $targetFile = $targetDir . time() . '_' . $fileName;

            // Créer le dossier uploads s'il n'existe pas
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // Déplacer le fichier uploadé
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                // Enregistrer le document dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO documents (project_id, user_id, title, file_path, file_type, file_size, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['project_id'],
                    $_SESSION['user_id'],
                    $_POST['title'] ?? $fileName,
                    $targetFile,
                    $file['type'],
                    $file['size'],
                    $_POST['description'] ?? null
                ]);

                // Mettre à jour le score de l'utilisateur
                $stmt = $pdo->prepare("
                    INSERT INTO activity_scores (user_id, project_id, action_type, points)
                    VALUES (?, ?, 'document_added', 2)
                ");
                $stmt->execute([$_SESSION['user_id'], $_POST['project_id']]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Document uploadé avec succès'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de l\'upload du fichier']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du document manquant']);
                exit;
            }

            // Vérifier les droits de suppression
            $stmt = $pdo->prepare("
                SELECT d.file_path, d.project_id, pm.role
                FROM documents d
                JOIN project_members pm ON d.project_id = pm.project_id
                WHERE d.id = ? AND (d.user_id = ? OR pm.role = 'owner')
            ");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            $document = $stmt->fetch();

            if (!$document) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorisé à supprimer ce document']);
                exit;
            }

            // Supprimer le fichier physique
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }

            // Supprimer l'entrée de la base de données
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
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