<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour tester l'API
function testAPI($action, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    $url = "http://localhost/pro3/api/auth.php?action=" . $action;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // Activer le suivi des cookies de session
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo "Erreur CURL: " . curl_error($ch) . "\n";
    }
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Test de création de compte
echo "Test de création de compte: ";
$signupData = [
    'email' => 'test@example.com',
    'password' => 'Test123!',
    'full_name' => 'Test User',
    'role' => 'student'
];

$result = testAPI('signup', 'POST', $signupData);
echo "Code HTTP: " . $result['code'] . " Réponse: ";
print_r($result['response']);
echo "\n\n";

// Test de connexion
echo "Test de connexion: ";
$loginData = [
    'email' => 'test@example.com',
    'password' => 'Test123!'
];

$result = testAPI('login', 'POST', $loginData);
echo "Code HTTP: " . $result['code'] . " Réponse: ";
print_r($result['response']);
echo "\n\n";

// Test de vérification de l'authentification
echo "Test de vérification de l'authentification: ";
$result = testAPI('check', 'GET');
echo "Code HTTP: " . $result['code'] . " Réponse: ";
print_r($result['response']);
echo "\n"; 