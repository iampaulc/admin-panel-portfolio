<?php
require_once 'auth.php';
check_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'json';

if (!$id) {
    die("ID du projet manquant.");
}

$json_data = @file_get_contents(JSON_DATA_PATH);
$data = json_decode($json_data, true);
$project = null;

if ($data && isset($data['projets'])) {
    foreach ($data['projets'] as $p) {
        if ($p['id'] == $id) {
            $project = $p;
            break;
        }
    }
}

if (!$project) {
    die("Projet introuvable.");
}

$filename = 'projet_' . ($project['projetId'] ?: $project['id']);

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    echo json_encode($project, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} elseif ($format === 'zip') {
    if (!extension_loaded('zip')) {
        die("L'extension ZIP n'est pas activée sur ce serveur.");
    }

    $zip = new ZipArchive();
    $temp_file = tempnam(sys_get_temp_dir(), 'proj_zip');
    
    if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die("Impossible de créer l'archive ZIP.");
    }

    // Ajouter le JSON
    $zip->addFromString('project.json', json_encode($project, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Récupérer toutes les images du projet
    $images_to_pack = [];
    
    if (!empty($project['image'])) {
        $images_to_pack[] = $project['image'];
    }
    
    if (!empty($project['galerie'])) {
        foreach ($project['galerie'] as $g) {
            if (isset($g[0]) && !empty($g[0]) && (!isset($g[2]) || $g[2] !== 'youtube')) {
                $images_to_pack[] = $g[0];
            }
        }
    }
    
    if (!empty($project['dossier'])) {
        foreach ($project['dossier'] as $d) {
            if (!empty($d['image1'])) {
                // image1 can be comma separated in mood_board
                $imgs = explode(',', $d['image1']);
                foreach ($imgs as $i) {
                    if (trim($i)) $images_to_pack[] = trim($i);
                }
            }
            if (!empty($d['image2'])) {
                $imgs = explode(',', $d['image2']);
                foreach ($imgs as $i) {
                    if (trim($i)) $images_to_pack[] = trim($i);
                }
            }
        }
    }

    // Ajouter les fichiers au ZIP
    $added_files = [];
    foreach ($images_to_pack as $img_path) {
        // Enlever le ASSETS_BASE_URL éventuel si l'utilisateur l'avait entré, mais en général c'est 'images/...'
        $local_path = IMAGES_UPLOAD_DIR . preg_replace('/^images\//', '', $img_path);
        
        if (file_exists($local_path) && !in_array($img_path, $added_files)) {
            $zip->addFile($local_path, $img_path);
            $added_files[] = $img_path;
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
    header('Content-Length: ' . filesize($temp_file));
    
    readfile($temp_file);
    unlink($temp_file);
    exit;
} else {
    die("Format non supporté.");
}
?>
