<?php
// sitemap_gen.php
require_once 'auth.php';
check_auth();

/**
 * Générateur de Sitemap (XML)
 * Scanne les projets et crée un fichier sitemap.xml à la racine du site.
 */

$json_data = @file_get_contents(JSON_DATA_PATH);
$data = json_decode($json_data, true);

if (!$data || !isset($data['projets'])) {
    die("Erreur : Impossible de lire les données des projets.");
}

// URL de base du site (à ajuster selon ton domaine)
// On essaye de la deviner ou on utilise une constante
$base_url = "https://" . $_SERVER['HTTP_HOST'] . "/"; 

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// 1. Accueil
$xml .= '  <url>' . PHP_EOL;
$xml .= '    <loc>' . htmlspecialchars($base_url) . '</loc>' . PHP_EOL;
$xml .= '    <priority>1.0</priority>' . PHP_EOL;
$xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
$xml .= '  </url>' . PHP_EOL;

// 2. Projets (Listing)
$xml .= '  <url>' . PHP_EOL;
$xml .= '    <loc>' . htmlspecialchars($base_url . 'projets') . '</loc>' . PHP_EOL;
$xml .= '    <priority>0.8</priority>' . PHP_EOL;
$xml .= '  </url>' . PHP_EOL;

// 3. Chaque projet individuel
foreach ($data['projets'] as $p) {
    if (!empty($p['projetId'])) {
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . htmlspecialchars($base_url . 'projet/' . $p['projetId']) . '</loc>' . PHP_EOL;
        $xml .= '    <priority>0.7</priority>' . PHP_EOL;
        $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
}

$xml .= '</urlset>';

// Sauvegarde à la racine du portfolio (qui est le parent de gestion_interne ou selon votre structure)
// Si votre dossier public est le dossier parent ou un sous-dossier public/ de développement.
// Ici on va essayer de le mettre à la racine du projet.
$sitemap_path = realpath(__DIR__ . '/../') . '/sitemap.xml';

if (file_put_contents($sitemap_path, $xml)) {
    // Rediriger vers le dashboard avec un succès
    header('Location: index.php?status=sitemap_ok');
} else {
    die("Erreur : Impossible d'écrire le fichier sitemap.xml à : " . $sitemap_path);
}
