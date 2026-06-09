<?php
require_once 'config.php';

// Détection du domaine autorisé (CORS)
$allowed_origins = defined('ALLOWED_ORIGINS') ? ALLOWED_ORIGINS : ['http://localhost'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Fallback pour les requêtes sans Origin (même domaine ou localhost)
    header('Access-Control-Allow-Origin: ' . (!empty($allowed_origins) ? $allowed_origins[0] : 'http://localhost'));
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Filtrage des bots
$user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$bots = ['bot', 'crawler', 'spider', 'slurp', 'googlebot', 'bingbot', 'yandex', 'baidu', 'duckduckbot', 'facebookexternalhit', 'curl', 'wget', 'python', 'go-http-client'];
foreach ($bots as $bot) {
    if (strpos($user_agent, $bot) !== false) {
        echo json_encode(['status' => 'ignored', 'reason' => 'bot']);
        exit;
    }
}

$stats_dir = __DIR__ . '/data';
if (!is_dir($stats_dir)) {
    mkdir($stats_dir, 0755, true);
}
$stats_file = $stats_dir . '/stats.json';

// Variables d'entrée
$action = '';
$id = '';
$path = '';
$referrer = '';
$visitorId = '';
$screenWidth = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (is_array($input)) {
        $action = $input['action'] ?? '';
        $id = $input['id'] ?? '';
        $path = $input['path'] ?? '';
        $referrer = $input['referrer'] ?? '';
        $visitorId = $input['visitorId'] ?? '';
        $screenWidth = intval($input['screenWidth'] ?? 0);
    }
} else {
    // Compatibilité descendante si besoin
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';
}

if (empty($action)) {
    echo json_encode(['error' => 'No action']);
    exit;
}

// Identification du visiteur
session_start();
if (empty($visitorId)) {
    if (empty($_SESSION['visitor_id'])) {
        $_SESSION['visitor_id'] = uniqid('sess_', true);
    }
    $visitorId = $_SESSION['visitor_id'];
}

// Prevenir les doublons d'actions exactes par session
$session_key = 'tracked_' . $action . '_' . $id . '_' . $path;
if (isset($_SESSION[$session_key])) {
    echo json_encode(['status' => 'already_tracked']);
    exit;
}
$_SESSION[$session_key] = true;

// Détection de l'appareil
$device = 'Desktop';
if ($screenWidth > 0) {
    if ($screenWidth <= 768) {
        $device = 'Mobile';
    } elseif ($screenWidth <= 1024) {
        $device = 'Tablet';
    }
} else {
    // Fallback User-Agent
    if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false || strpos($user_agent, 'iphone') !== false) {
        $device = 'Mobile';
    } elseif (strpos($user_agent, 'ipad') !== false || strpos($user_agent, 'tablet') !== false) {
        $device = 'Tablet';
    }
}

// Détection de la source
$refSource = 'Direct';
if (!empty($referrer)) {
    $parsed_url = parse_url($referrer);
    $host = strtolower($parsed_url['host'] ?? '');
    
    // Détermination des hôtes internes (qui ne doivent pas être comptés comme référents externes)
    $internal_hosts = ['localhost', '127.0.0.1'];
    if (defined('ALLOWED_ORIGINS')) {
        foreach (ALLOWED_ORIGINS as $allowed_origin) {
            $parsed = parse_url($allowed_origin);
            $h = strtolower($parsed['host'] ?? '');
            if (!empty($h) && !in_array($h, $internal_hosts)) {
                $internal_hosts[] = $h;
                // Ajouter les variantes avec/sans www.
                if (strpos($h, 'www.') === 0) {
                    $internal_hosts[] = substr($h, 4);
                } else {
                    $internal_hosts[] = 'www.' . $h;
                }
            }
        }
    }
    
    if (!empty($host) && !in_array($host, $internal_hosts)) {
        if (strpos($host, 'google.') !== false) $refSource = 'Google';
        elseif (strpos($host, 'instagram.com') !== false) $refSource = 'Instagram';
        elseif (strpos($host, 'linkedin.') !== false) $refSource = 'LinkedIn';
        elseif (strpos($host, 'behance.net') !== false) $refSource = 'Behance';
        elseif (strpos($host, 'bing.com') !== false) $refSource = 'Bing';
        elseif (strpos($host, 'qwant.com') !== false) $refSource = 'Qwant';
        elseif (strpos($host, 'ecosia.org') !== false) $refSource = 'Ecosia';
        else $refSource = $host; // Autre site web
    }
}

// Écriture sécurisée avec verrou (flock)
$fp = fopen($stats_file, 'c+'); // Créer si n'existe pas, ouvre en lecture/écriture
if (!$fp) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot open stats file']);
    exit;
}

if (flock($fp, LOCK_EX)) {
    $filesize = filesize($stats_file);
    if ($filesize > 0) {
        $json_data = fread($fp, $filesize);
        $data = json_decode($json_data, true) ?: [];
    } else {
        $data = [
            'site_views' => 0,
            'unique_visitors' => 0,
            'projects_views' => [],
            'clicks' => [],
            'daily' => [],
            'devices' => ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0],
            'referrers' => ['Direct' => 0],
            'pages' => []
        ];
    }
    
    // Assurer l'existence des clés principales (pour les vieux fichiers stats.json)
    $data['site_views'] = $data['site_views'] ?? 0;
    $data['unique_visitors'] = $data['unique_visitors'] ?? 0;
    $data['devices'] = $data['devices'] ?? ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0];
    $data['referrers'] = $data['referrers'] ?? ['Direct' => 0];
    $data['pages'] = $data['pages'] ?? [];
    
    $today = date('Y-m-d');
    if (!isset($data['daily'][$today])) {
        $data['daily'][$today] = [
            'views' => 0,
            'unique' => 0,
            'projects' => [],
            'clicks' => [],
            'visitors' => [] // Stocke les UUID temporairement pour la journée
        ];
    }
    if (!isset($data['daily'][$today]['visitors'])) {
        $data['daily'][$today]['visitors'] = [];
    }

    // Gestion du Visiteur Unique (On compte uniquement 1 fois par jour par ID)
    $is_new_visitor_today = false;
    if (!in_array($visitorId, $data['daily'][$today]['visitors'])) {
        $data['daily'][$today]['visitors'][] = $visitorId;
        $data['daily'][$today]['unique'] = ($data['daily'][$today]['unique'] ?? 0) + 1;
        $data['unique_visitors']++;
        $is_new_visitor_today = true;
    }

    if ($action === 'pageview') {
        $data['site_views']++;
        $data['daily'][$today]['views'] = ($data['daily'][$today]['views'] ?? 0) + 1;
        
        $pageKey = !empty($path) ? $path : '/';
        $data['pages'][$pageKey] = ($data['pages'][$pageKey] ?? 0) + 1;
        
        if ($is_new_visitor_today) {
            $data['devices'][$device] = ($data['devices'][$device] ?? 0) + 1;
            $data['referrers'][$refSource] = ($data['referrers'][$refSource] ?? 0) + 1;
        }

        // Nettoyage : On supprime les visitors_list d'hier pour ne pas surcharger le JSON indéfiniment
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if (isset($data['daily'][$yesterday]['visitors'])) {
            unset($data['daily'][$yesterday]['visitors']);
        }
    } elseif ($action === 'projectview' && !empty($id)) {
        if (!isset($data['projects_views'])) $data['projects_views'] = [];
        $data['projects_views'][$id] = ($data['projects_views'][$id] ?? 0) + 1;
        $data['daily'][$today]['projects'][$id] = ($data['daily'][$today]['projects'][$id] ?? 0) + 1;
        
        if (!empty($path)) {
            $data['pages'][$path] = ($data['pages'][$path] ?? 0) + 1;
        }
    } elseif ($action === 'click' && !empty($id)) {
        if (!isset($data['clicks'])) $data['clicks'] = [];
        $data['clicks'][$id] = ($data['clicks'][$id] ?? 0) + 1;
        $data['daily'][$today]['clicks'][$id] = ($data['daily'][$today]['clicks'][$id] ?? 0) + 1;
    }

    // Sauvegarde atomique avec ftruncate
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
}
fclose($fp);

echo json_encode(['status' => 'success', 'visitorId' => $visitorId]);
