<?php
header('Content-Type: application/json');
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

// Connexion à la base de données
require_once 'db_connect.php';

// Fonction pour formater la date
function formatDate($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}

try {
    $pdo = getConnection();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Récupération des événements
            if (isset($_GET['upcoming'])) {
                // Événements à venir
                $stmt = $pdo->prepare("
                    SELECT e.*, u.full_name as creator_name 
                    FROM events e 
                    JOIN users u ON e.creator_id = u.id 
                    WHERE e.date >= CURDATE() 
                    ORDER BY e.date ASC 
                    LIMIT 10
                ");
            } else {
                // Tous les événements du mois
                $start_date = isset($_GET['start']) ? formatDate($_GET['start']) : date('Y-m-01');
                $end_date = isset($_GET['end']) ? formatDate($_GET['end']) : date('Y-m-t');
                
                $stmt = $pdo->prepare("
                    SELECT e.*, u.full_name as creator_name 
                    FROM events e 
                    JOIN users u ON e.creator_id = u.id 
                    WHERE e.date BETWEEN ? AND ?
                    ORDER BY e.date ASC
                ");
                $stmt->execute([$start_date, $end_date]);
            }
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($events);
            break;

        case 'POST':
            // Création d'un nouvel événement
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['title']) || !isset($data['date']) || !isset($data['type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données manquantes']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO events (title, description, date, type, creator_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                formatDate($data['date']),
                $data['type'],
                $_SESSION['user_id']
            ]);

            $eventId = $pdo->lastInsertId();
            
            // Récupération de l'événement créé
            $stmt = $pdo->prepare("
                SELECT e.*, u.full_name as creator_name 
                FROM events e 
                JOIN users u ON e.creator_id = u.id 
                WHERE e.id = ?
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($event);
            break;

        case 'PUT':
            // Mise à jour d'un événement
            $data = json_decode(file_get_contents('php://input'), true);
            $eventId = $_GET['id'] ?? null;

            if (!$eventId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de l\'événement manquant']);
                exit;
            }

            $updates = [];
            $params = [];

            if (isset($data['title'])) {
                $updates[] = 'title = ?';
                $params[] = $data['title'];
            }
            if (isset($data['description'])) {
                $updates[] = 'description = ?';
                $params[] = $data['description'];
            }
            if (isset($data['date'])) {
                $updates[] = 'date = ?';
                $params[] = formatDate($data['date']);
            }
            if (isset($data['type'])) {
                $updates[] = 'type = ?';
                $params[] = $data['type'];
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucune donnée à mettre à jour']);
                exit;
            }

            $params[] = $eventId;
            $params[] = $_SESSION['user_id'];

            $stmt = $pdo->prepare("
                UPDATE events 
                SET " . implode(', ', $updates) . "
                WHERE id = ? AND creator_id = ?
            ");
            
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Événement non trouvé ou non autorisé']);
                exit;
            }

            // Récupération de l'événement mis à jour
            $stmt = $pdo->prepare("
                SELECT e.*, u.full_name as creator_name 
                FROM events e 
                JOIN users u ON e.creator_id = u.id 
                WHERE e.id = ?
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($event);
            break;

        case 'DELETE':
            // Suppression d'un événement
            $eventId = $_GET['id'] ?? null;

            if (!$eventId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de l\'événement manquant']);
                exit;
            }

            $stmt = $pdo->prepare("
                DELETE FROM events 
                WHERE id = ? AND creator_id = ?
            ");
            
            $stmt->execute([$eventId, $_SESSION['user_id']]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Événement non trouvé ou non autorisé']);
                exit;
            }

            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
    error_log($e->getMessage());
} 