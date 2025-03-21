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
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            if ($action === 'inbox') {
                // Récupérer les messages reçus
                $stmt = $pdo->prepare("
                    SELECT m.*, 
                           u.full_name as sender_name,
                           p.title as project_title
                    FROM messages m
                    LEFT JOIN users u ON m.sender_id = u.id
                    LEFT JOIN projects p ON m.project_id = p.id
                    WHERE m.receiver_id = ?
                    ORDER BY m.created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                echo json_encode($stmt->fetchAll());
            } elseif ($action === 'sent') {
                // Récupérer les messages envoyés
                $stmt = $pdo->prepare("
                    SELECT m.*, 
                           u.full_name as receiver_name,
                           p.title as project_title
                    FROM messages m
                    LEFT JOIN users u ON m.receiver_id = u.id
                    LEFT JOIN projects p ON m.project_id = p.id
                    WHERE m.sender_id = ?
                    ORDER BY m.created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                echo json_encode($stmt->fetchAll());
            } elseif ($action === 'unread-count') {
                // Compter les messages non lus
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM messages
                    WHERE receiver_id = ? AND is_read = 0
                ");
                $stmt->execute([$_SESSION['user_id']]);
                echo json_encode($stmt->fetch());
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['receiver_id']) || empty($data['subject']) || empty($data['content'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données manquantes']);
                exit;
            }

            // Envoyer un nouveau message
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, project_id, subject, content)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $data['receiver_id'],
                $data['project_id'] ?? null,
                $data['subject'],
                $data['content']
            ]);

            // Mettre à jour le score de l'utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO activity_scores (user_id, project_id, action_type, points)
                VALUES (?, ?, 'message_sent', 1)
            ");
            $stmt->execute([$_SESSION['user_id'], $data['project_id'] ?? null]);

            echo json_encode(['success' => true, 'message' => 'Message envoyé']);
            break;

        case 'PUT':
            if ($action === 'mark-read') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['message_id'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID du message manquant']);
                    exit;
                }

                // Marquer le message comme lu
                $stmt = $pdo->prepare("
                    UPDATE messages
                    SET is_read = 1
                    WHERE id = ? AND receiver_id = ?
                ");
                $stmt->execute([$data['message_id'], $_SESSION['user_id']]);
                echo json_encode(['success' => true]);
            }
            break;

        case 'DELETE':
            if (empty($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID du message manquant']);
                exit;
            }

            // Supprimer un message
            $stmt = $pdo->prepare("
                DELETE FROM messages
                WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
            ");
            $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);
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