<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../auth/auth.php';
require_once '../models/Project.php';
require_once '../models/Task.php';
require_once '../models/Message.php';
require_once '../models/Document.php';

// Activer CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Récupérer la méthode HTTP et l'endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$endpoint = $request[0] ?? '';

// Initialiser les classes
$auth = new Auth();
$project = new Project();
$task = new Task();
$message = new Message();
$document = new Document();

// Fonction de réponse JSON
function sendResponse($status, $message, $data = null) {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Vérifier l'authentification pour les routes protégées
function checkAuth() {
    global $auth;
    if (!$auth->isLoggedIn()) {
        sendResponse(401, 'Non autorisé');
    }
}

// Router
switch ($endpoint) {
    case 'auth':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'login':
                        if ($auth->login($data['email'], $data['password'])) {
                            sendResponse(200, SUCCESS_MESSAGES['login_success'], $auth->getCurrentUser());
                        }
                        sendResponse(401, ERROR_MESSAGES['invalid_credentials']);
                        break;
                    case 'register':
                        if ($auth->register($data['full_name'], $data['email'], $data['password'], $data['role'])) {
                            sendResponse(201, SUCCESS_MESSAGES['registration_success']);
                        }
                        sendResponse(400, ERROR_MESSAGES['registration_failed']);
                        break;
                }
            }
        }
        break;

    case 'projects':
        checkAuth();
        switch ($method) {
            case 'GET':
                if (isset($request[1])) {
                    $projectData = $project->getProject($request[1]);
                    sendResponse(200, 'Projet récupéré', $projectData);
                } else {
                    $projects = $project->getUserProjects($_SESSION['user_id']);
                    sendResponse(200, 'Projets récupérés', $projects);
                }
                break;
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $projectId = $project->createProject($data['title'], $data['description'], $_SESSION['user_id']);
                if ($projectId) {
                    sendResponse(201, SUCCESS_MESSAGES['project_created'], ['project_id' => $projectId]);
                }
                sendResponse(400, 'Erreur lors de la création du projet');
                break;
        }
        break;

    case 'tasks':
        checkAuth();
        switch ($method) {
            case 'GET':
                if (isset($request[1])) {
                    $taskData = $task->getTaskDetails($request[1]);
                    sendResponse(200, 'Tâche récupérée', $taskData);
                } else {
                    $tasks = $task->getUserTasks($_SESSION['user_id']);
                    sendResponse(200, 'Tâches récupérées', $tasks);
                }
                break;
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                if ($task->createTask($data['project_id'], $data['title'], $data['description'], $data['assigned_to'], $data['due_date'])) {
                    sendResponse(201, SUCCESS_MESSAGES['task_created']);
                }
                sendResponse(400, 'Erreur lors de la création de la tâche');
                break;
        }
        break;

    case 'messages':
        checkAuth();
        switch ($method) {
            case 'GET':
                if (isset($request[1])) {
                    $messages = $message->getProjectMessages($request[1]);
                    sendResponse(200, 'Messages récupérés', $messages);
                }
                sendResponse(400, 'ID du projet requis');
                break;
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                if ($message->sendMessage($data['project_id'], $_SESSION['user_id'], $data['content'])) {
                    sendResponse(201, SUCCESS_MESSAGES['message_sent']);
                }
                sendResponse(400, 'Erreur lors de l\'envoi du message');
                break;
        }
        break;

    case 'documents':
        checkAuth();
        switch ($method) {
            case 'GET':
                if (isset($request[1])) {
                    $documents = $document->getProjectDocuments($request[1]);
                    sendResponse(200, 'Documents récupérés', $documents);
                }
                sendResponse(400, 'ID du projet requis');
                break;
            case 'POST':
                if (isset($_FILES['file'])) {
                    $file = $_FILES['file'];
                    if ($file['size'] > MAX_FILE_SIZE) {
                        sendResponse(400, ERROR_MESSAGES['file_too_large']);
                    }
                    if (!is_allowed_file_type($file)) {
                        sendResponse(400, ERROR_MESSAGES['invalid_file_type']);
                    }
                    if ($document->uploadDocument($_POST['project_id'], $_SESSION['user_id'], $file)) {
                        sendResponse(201, SUCCESS_MESSAGES['document_uploaded']);
                    }
                    sendResponse(400, ERROR_MESSAGES['upload_failed']);
                }
                sendResponse(400, 'Aucun fichier envoyé');
                break;
        }
        break;

    default:
        sendResponse(404, 'Endpoint non trouvé');
} 