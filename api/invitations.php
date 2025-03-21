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
            if ($action === 'received') {
                // Récupérer les invitations reçues
                $stmt = $pdo->prepare("
                    SELECT i.*,
                           u.full_name as sender_name,
                           p.title as project_title
                    FROM invitations i
                    JOIN users u ON i.sender_id = u.id
                    JOIN projects p ON i.project_id = p.id
                    WHERE i.receiver_id = ? AND i.status = 'pending'
                    ORDER BY i.created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                echo json_encode($stmt->fetchAll());
            } elseif ($action === 'sent') {
                // Récupérer les invitations envoyées
                $stmt = $pdo->prepare("
                    SELECT i.*,
                           u.full_name as receiver_name,
                           p.title as project_title
                    FROM invitations i
                    JOIN users u ON i.receiver_id = u.id
                    JOIN projects p ON i.project_id = p.id
                    WHERE i.sender_id = ?
                    ORDER BY i.created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['project_id']) || empty($data['receiver_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données manquantes']);
                exit;
            }

            // Vérifier que l'utilisateur est propriétaire du projet
            $stmt = $pdo->prepare("
                SELECT 1 FROM project_members
                WHERE project_id = ? AND user_id = ? AND role = 'owner'
            ");
            $stmt->execute([$data['project_id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Non autorisé à inviter des membres']);
                exit;
            }

            // Vérifier si l'invitation existe déjà
            $stmt = $pdo->prepare("
                SELECT 1 FROM invitations
                WHERE project_id = ? AND receiver_id = ? AND status = 'pending'
            ");
            $stmt->execute([$data['project_id'], $data['receiver_id']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Invitation déjà envoyée']);
                exit;
            }

            // Créer l'invitation
            $stmt = $pdo->prepare("
                INSERT INTO invitations (project_id, sender_id, receiver_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $data['project_id'],
                $_SESSION['user_id'],
                $data['receiver_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Invitation envoyée']);
            break;

        case 'PUT':
            if ($action === 'respond') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['invitation_id']) || empty($data['status'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Données manquantes']);
                    exit;
                }

                // Vérifier que l'invitation existe et est destinée à l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT project_id FROM invitations
                    WHERE id = ? AND receiver_id = ? AND status = 'pending'
                ");
                $stmt->execute([$data['invitation_id'], $_SESSION['user_id']]);
                $invitation = $stmt->fetch();

                if (!$invitation) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Invitation non trouvée']);
                    exit;
                }

                // Mettre à jour le statut de l'invitation
                $stmt = $pdo->prepare("
                    UPDATE invitations
                    SET status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$data['status'], $data['invitation_id']]);

                // Si acceptée, ajouter l'utilisateur comme membre du projet
                if ($data['status'] === 'accepted') {
                    $stmt = $pdo->prepare("
                        INSERT INTO project_members (project_id, user_id, role)
                        VALUES (?, ?, 'member')
                    ");
                    $stmt->execute([$invitation['project_id'], $_SESSION['user_id']]);
                }

                echo json_encode(['success' => true]);
            }
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