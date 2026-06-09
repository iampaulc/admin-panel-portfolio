<?php
require_once 'auth.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

verify_csrf();

$project_data = null;
$error = null;
$temp_dir = null;

if (!empty($_POST['import_json'])) {
    // Import via text JSON
    $project_data = json_decode($_POST['import_json'], true);
    if (!$project_data) {
        $error = "JSON invalide.";
    }
} elseif (isset($_FILES['import_zip']) && $_FILES['import_zip']['error'] === UPLOAD_ERR_OK) {
    // Import via ZIP
    $zip_file = $_FILES['import_zip']['tmp_name'];
    $zip = new ZipArchive();
    
    if ($zip->open($zip_file) === true) {
        // Look for project.json
        $json_content = $zip->getFromName('project.json');
        if ($json_content === false) {
            $error = "L'archive ne contient pas de fichier project.json.";
        } else {
            $project_data = json_decode($json_content, true);
            if (!$project_data) {
                $error = "Le fichier project.json est invalide.";
            } else {
                // Extract images
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    if ($filename !== 'project.json' && preg_match('/^images\//', $filename)) {
                        // Extract to memory or copy to dest
                        $content = $zip->getFromIndex($i);
                        if ($content !== false) {
                            $dest_path = IMAGES_UPLOAD_DIR . preg_replace('/^images\//', '', $filename);
                            $dest_dir = dirname($dest_path);
                            if (!is_dir($dest_dir)) {
                                mkdir($dest_dir, 0755, true);
                            }
                            // Don't overwrite if not necessary, or perhaps we should? 
                            // Usually a portfolio ZIP will overwrite or use the same names
                            file_put_contents($dest_path, $content);
                        }
                    }
                }
            }
        }
        $zip->close();
    } else {
        $error = "Impossible d'ouvrir le fichier ZIP.";
    }
} else {
    $error = "Aucune donnée fournie pour l'import.";
}

if ($error) {
    die("Erreur d'importation : " . htmlspecialchars($error) . " <br><a href='edit_projects.php'>Retour</a>");
}

if ($project_data) {
    $json_data = @file_get_contents(JSON_DATA_PATH);
    $data = json_decode($json_data, true);
    if (!$data) $data = ['projets' => []];

    // Find a new ID
    $max_id = 0;
    foreach ($data['projets'] as $p) {
        if ($p['id'] > $max_id) $max_id = $p['id'];
    }
    $new_id = $max_id + 1;
    $project_data['id'] = $new_id;
    
    // Set status to draft by default when importing, just to be safe, unless it already has one
    if (!isset($project_data['status'])) {
        $project_data['status'] = 'draft';
    }

    // Append project
    $data['projets'][] = $project_data;

    if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
    $fp = fopen(JSON_DATA_PATH, 'c');
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
    }
    fclose($fp);

    header('Location: edit_projects.php?id=' . $new_id . '&status=imported');
    exit;
} else {
    die("Projet non reconnu. <br><a href='edit_projects.php'>Retour</a>");
}
?>
