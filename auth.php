<?php
require_once 'config.php';

session_start();

function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function check_auth() {
    if (!is_logged_in()) {
        // Si c'est une requête API (AJAX), retourner une erreur JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
}

// --- CSRF Protection ---
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Erreur de sécurité : Token CSRF invalide. <a href="javascript:history.back()">Retour</a>');
    }
}

// Traitement du login
if (isset($_POST['login'])) {
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (password_verify($password, ADMIN_PASSWORD_HASH)) { 
        $_SESSION['admin_logged_in'] = true;
        // Régénérer le session ID pour éviter le session fixation
        session_regenerate_id(true);
        header('Location: index.php');
        exit;
    } else {
        $error = "Mot de passe incorrect.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
