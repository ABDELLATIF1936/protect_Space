<?php
header('Content-Type: application/json');
session_start();

require_once 'db_connect.php';

try {
    $pdo = getConnection();
    
    $action = $_GET['action'] ?? '';
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'login':
            if (empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Email et mot de passe requis'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if ($user && password_verify($data['password'], $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                unset($user['password']); // Ne pas renvoyer le mot de passe
                
                echo json_encode([
                    'success' => true,
                    'user' => $user
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Email ou mot de passe incorrect'
                ]);
            }
            break;

        case 'signup':
            if (empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Données manquantes'
                ]);
                exit;
            }

            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Cet email est déjà utilisé'
                ]);
                exit;
            }

            // Créer le nouvel utilisateur
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password, full_name, role) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['email'],
                $hashedPassword,
                $data['full_name'],
                $data['role'] ?? 'user'
            ]);

            $userId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;

            // Récupérer l'utilisateur créé
            $stmt = $pdo->prepare("SELECT id, email, full_name, role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
            break;

        case 'check':
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("SELECT id, email, full_name, role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                if ($user) {
                    echo json_encode([
                        'success' => true,
                        'authenticated' => true,
                        'user' => $user
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'authenticated' => false,
                        'error' => 'Utilisateur non trouvé'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'authenticated' => false,
                    'error' => 'Non authentifié'
                ]);
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode([
                'success' => true
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Action non valide'
            ]);
            break;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données'
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur'
    ]);
} 