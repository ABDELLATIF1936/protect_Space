<?php
require_once 'config/database.php';

try {
    // Test de la connexion
    $stmt = $pdo->query("SELECT 1");
    echo "Connexion à la base de données réussie!\n";

    // Test de la table users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "La table 'users' existe!\n";
        
        // Afficher la structure de la table
        $stmt = $pdo->query("DESCRIBE users");
        echo "\nStructure de la table 'users':\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . "\n";
        }
    } else {
        echo "ERREUR: La table 'users' n'existe pas!\n";
    }

} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage();
} 