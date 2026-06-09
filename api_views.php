<?php
require_once 'config.php';

// Détection du domaine autorisé (CORS)
$allowed_origins = defined('ALLOWED_ORIGINS') ? ALLOWED_ORIGINS : ['http://localhost'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: ' . (!empty($allowed_origins) ? $allowed_origins[0] : 'http://localhost'));
}
header('Content-Type: application/json');

$stats_file = __DIR__ . '/data/stats.json';
$count = 0;
if (file_exists($stats_file)) {
    $data = json_decode(file_get_contents($stats_file), true);
    $count = $data['site_views'] ?? 0;
}
echo json_encode(['count' => $count]);
