<?php
// backup.php
require_once 'auth.php';
check_auth();

/**
 * Système de Backup de Sécurité (Un-clic)
 * Compresse les données (JSON) et les médias (Images) dans un seul ZIP.
 */

// Chemin vers les dossiers à sauvegarder
$data_dir = realpath(__DIR__ . '/data');
$images_dir = realpath(IMAGES_UPLOAD_DIR);

if (!$data_dir || !$images_dir) {
    die("Erreur : Dossiers introuvables.");
}

// Dossier de stockage des backups sur le serveur
$backup_storage_dir = __DIR__ . '/backups';
if (!is_dir($backup_storage_dir)) {
    mkdir($backup_storage_dir, 0755, true);
    // Protéger le dossier avec un .htaccess pour empêcher le téléchargement direct via URL
    file_put_contents($backup_storage_dir . '/.htaccess', "Order Deny,Allow\nDeny from all");
}

$zip = new ZipArchive();
$timestamp = date('Y-m-d_His');
$filename = "backup_portfolio_" . $timestamp . ".zip";
$filepath = $backup_storage_dir . '/' . $filename;

if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
    die("Impossible de créer le fichier ZIP.");
}

// Fonction pour ajouter un dossier récursivement
function addDirToZip($dir, $zip, $local_parent = '')
{
    if (!is_dir($dir))
        return;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = $local_parent . substr($filePath, strlen($dir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
}

// Ajouter les données
addDirToZip($data_dir, $zip, 'data/');
// Ajouter les images
addDirToZip($images_dir, $zip, 'images/');
//Ajouter le data.json
$zip->addFile(JSON_DATA_PATH, 'data.json');

$zip->close();

// Téléchargement du fichier
if (file_exists($filepath)) {
    // On garde la copie dans le dossier /backups/
    // Et on l'envoie pour le téléchargement direct
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($filepath);
    // On ne l'efface plus (unlink), on le garde sur le serveur !
    exit;
} else {
    die("Erreur lors de la génération du zip.");
}
