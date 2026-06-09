<?php
require_once 'auth.php';
check_auth();

// API pour sauver le JSON global
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data) {
        // Backup avant écriture
        if (file_exists(JSON_DATA_PATH)) {
            copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
        }
        
        // Écriture avec file locking
        $fp = fopen(JSON_DATA_PATH, 'c');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            echo json_encode(['status' => 'success']);
        } else {
            fclose($fp);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Impossible de verrouiller le fichier']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Données invalides']);
    }
    exit;
}
?>
