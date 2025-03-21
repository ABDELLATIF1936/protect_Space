<?php
header('Content-Type: application/json');
session_start();

require_once 'db_connect.php';

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Non authentifié'
        ]);
        exit;
    }

    $pdo = getConnection();
    
    // Récupérer tous les utilisateurs sauf l'utilisateur courant
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, role 
        FROM users 
        WHERE id != ? 
        ORDER BY full_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $team_members = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'team_members' => $team_members
    ]);

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