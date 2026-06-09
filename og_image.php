<?php
// og_image.php
// Script pour générer dynamiquement une image Open Graph pour un projet
require_once 'config.php';
header('Content-Type: image/png');

$slug = isset($_GET['slug']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['slug']) : '';
if (!$slug) {
    http_response_code(400);
    exit;
}

$cache_dir = __DIR__ . '/og_cache';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

$cache_file = $cache_dir . '/' . $slug . '.png';

// Si l'image existe déjà en cache, on la sert
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 86400 * 7) {
    readfile($cache_file);
    exit;
}

// 1. Lire les données du projet
$data_file = JSON_DATA_PATH;
if (!file_exists($data_file)) {
    http_response_code(500);
    exit;
}

$data = json_decode(file_get_contents($data_file), true);
$projet = null;

foreach ($data['projets'] as $p) {
    if ($p['projetId'] === $slug) {
        $projet = $p;
        break;
    }
}

if (!$projet) {
    http_response_code(404);
    exit;
}

// 2. Générer l'image avec GD
$width = 1200;
$height = 630;
$image = imagecreatetruecolor($width, $height);

// Fond sombre (le theme-color: #0a0a0c)
$bg_color = imagecolorallocate($image, 10, 10, 12);
imagefill($image, 0, 0, $bg_color);

// Charger l'image du projet
$portfolio_public_dir = dirname(JSON_DATA_PATH) . '/';
$source_img_path = $portfolio_public_dir . ltrim($projet['image'], '/');

if (file_exists($source_img_path)) {
    $ext = strtolower(pathinfo($source_img_path, PATHINFO_EXTENSION));
    $source_img = null;
    
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $source_img = @imagecreatefromjpeg($source_img_path);
    } elseif ($ext === 'png') {
        $source_img = @imagecreatefrompng($source_img_path);
    } elseif ($ext === 'webp') {
        $source_img = @imagecreatefromwebp($source_img_path);
    }

    if ($source_img) {
        // Redimensionner et recadrer pour couvrir la moitié droite de l'image (par exemple)
        // Ou au centre avec opacité
        $src_w = imagesx($source_img);
        $src_h = imagesy($source_img);
        
        // Copier l'image au centre en fond avec un fondu
        $dest_w = $width;
        $dest_h = ($src_h / $src_w) * $dest_w;
        if ($dest_h < $height) {
            $dest_h = $height;
            $dest_w = ($src_w / $src_h) * $dest_h;
        }
        
        $dest_x = ($width - $dest_w) / 2;
        $dest_y = ($height - $dest_h) / 2;
        
        // Créer une image temporaire pour la source redimensionnée
        $temp_img = imagecreatetruecolor($width, $height);
        imagecopyresampled($temp_img, $source_img, $dest_x, $dest_y, 0, 0, $dest_w, $dest_h, $src_w, $src_h);
        
        // Appliquer un filtre assombrissant
        imagefilter($temp_img, IMG_FILTER_BRIGHTNESS, -100);
        
        // Fusionner avec le fond
        imagecopymerge($image, $temp_img, 0, 0, 0, 0, $width, $height, 40); // 40% opacité
        imagedestroy($temp_img);
        imagedestroy($source_img);
    }
}

// Ajouter le titre
$text_color = imagecolorallocate($image, 255, 255, 255);
$accent_color = imagecolorallocate($image, 1, 180, 241); // #01b4f1

// Optionnel: chemin vers une police TTF (ici on utilise la police interne si pas de TTF)
// Pour un meilleur rendu, il faudrait utiliser imagettftext() avec un fichier .ttf
// Comme on n'est pas sûr d'avoir une police TTF dispo, on utilise une approche fallback simple
$font_path = $portfolio_public_dir . 'fonts/SpaceGrotesk-Bold.ttf';

if (file_exists($font_path)) {
    // Si la police est dispo, on l'utilise
    imagettftext($image, 50, 0, 80, 250, $text_color, $font_path, $projet['titre']);
    
    // Sous-titre
    $font_light = $portfolio_public_dir . 'fonts/Inter-Regular.ttf';
    if (file_exists($font_light)) {
        // Découper le sous-titre s'il est trop long
        $sous_titre = wordwrap($projet['sousTitre'], 60, "\n");
        imagettftext($image, 24, 0, 80, 330, $text_color, $font_light, $sous_titre);
    } else {
        imagestring($image, 5, 80, 330, substr($projet['sousTitre'], 0, 80) . '...', $text_color);
    }
    
    // Branding en bas
    $branding_text = defined('PORTFOLIO_OWNER') ? PORTFOLIO_OWNER . " — Portfolio" : "Mon Portfolio";
    imagettftext($image, 20, 0, 80, 550, $accent_color, $font_path, $branding_text);
} else {
    // Fallback GD interne
    imagestring($image, 5, 50, 200, $projet['titre'], $text_color);
    imagestring($image, 4, 50, 230, substr($projet['sousTitre'], 0, 60), $text_color);
    $branding_text_fallback = defined('PORTFOLIO_OWNER') ? PORTFOLIO_OWNER . " - Portfolio" : "Mon Portfolio";
    imagestring($image, 3, 50, 500, $branding_text_fallback, $accent_color);
}

// Sauvegarder dans le cache et afficher
imagepng($image, $cache_file);
imagepng($image);
imagedestroy($image);
?>
