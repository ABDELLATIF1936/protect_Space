<?php
// Configuration de l'application
define('APP_NAME', 'ProjectSpace');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/pro3');

// Configuration des sessions
ini_set('session.cookie_lifetime', 60 * 60 * 24); // 24 heures
ini_set('session.gc_maxlifetime', 60 * 60 * 24); // 24 heures

// Configuration des uploads
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_FILE_TYPES', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
]);

// Messages d'erreur
define('ERROR_MESSAGES', [
    'db_connection' => 'Erreur de connexion à la base de données',
    'invalid_credentials' => 'Email ou mot de passe incorrect',
    'email_exists' => 'Cet email est déjà utilisé',
    'registration_failed' => 'Erreur lors de l\'inscription',
    'upload_failed' => 'Erreur lors du téléchargement du fichier',
    'invalid_file_type' => 'Type de fichier non autorisé',
    'file_too_large' => 'Le fichier est trop volumineux'
]);

// Messages de succès
define('SUCCESS_MESSAGES', [
    'registration_success' => 'Inscription réussie',
    'login_success' => 'Connexion réussie',
    'project_created' => 'Projet créé avec succès',
    'task_created' => 'Tâche créée avec succès',
    'message_sent' => 'Message envoyé avec succès',
    'document_uploaded' => 'Document téléchargé avec succès'
]);

// Fonctions utilitaires globales
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length));
}

function format_date($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function is_allowed_file_type($file) {
    $extension = get_file_extension($file['name']);
    return isset(ALLOWED_FILE_TYPES[$extension]) && 
           ALLOWED_FILE_TYPES[$extension] === $file['type'];
}

function format_file_size($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $power = floor(log($size, 1024));
    return round($size / pow(1024, $power), 2) . ' ' . $units[$power];
} 