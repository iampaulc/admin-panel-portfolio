<?php
/**
 * Configuration du Panel Admin
 */

// 1. CONFIGURATION GÉNÉRALE DU PORTFOLIO
define('PORTFOLIO_OWNER', 'Mon Portfolio'); // Nom affiché sur le Panel et le footer (ex: Paul Chéhère Le Lann)
define('PORTFOLIO_URL', '../');            // Chemin relatif ou URL absolue vers la racine publique du portfolio

// 2. SÉCURITÉ CORS (Tracking et API)
// Ajoutez ici les URL de votre site (local et production) autorisées à envoyer des statistiques.
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1'
]);

// 3. SÉCURITÉ DU MOT DE PASSE (Credentials)
// Le mot de passe par défaut est 'admin123'. 
// Après modification dans le panel (Sécurité), il sera stocké de manière sécurisée dans 'credentials.php'.
if (file_exists(__DIR__ . '/credentials.php')) {
    require_once __DIR__ . '/credentials.php';
} else {
    // Hash par défaut correspondant à 'admin123' (Généré via password_hash)
    define('ADMIN_PASSWORD_HASH', '$2y$10$KsLLpYDCEmW1ClSLSdgG/.FEcboPSdEJlYlwCx5bNmPHK45lFWfg2'); 
}

// 4. CHEMINS D'ACCÈS AUX DONNÉES & MÉDIAS (Dev vs Production)
// Vous pouvez adapter ces chemins selon la structure de votre projet.
// Par défaut, le script regarde si un dossier de développement existe (ex: dossier public de React/Vite).
$dev_path = __DIR__ . '/../public/'; // Adaptez si votre dossier s'appelle 'react-portfolio/public/' par exemple

if (is_dir($dev_path)) {
    // Mode Développement (dossier source)
    define('JSON_DATA_PATH', $dev_path . 'data.json');
    define('IMAGES_UPLOAD_DIR', $dev_path . 'images/');
    define('ASSETS_BASE_URL', '../public/'); // Préfixe pour le chargement des images dans l'admin
} else {
    // Mode Production (racine du site)
    // Structure serveur attendue :
    //   public_html/ (racine)
    //     ├── data.json
    //     ├── images/
    //     └── gestion_interne/ (dossier contenant ce panel)
    define('JSON_DATA_PATH', __DIR__ . '/../data.json');
    define('IMAGES_UPLOAD_DIR', __DIR__ . '/../images/');
    define('ASSETS_BASE_URL', '../');
}

// Création automatique du dossier images/ s'il n'existe pas
if (!is_dir(IMAGES_UPLOAD_DIR)) {
    mkdir(IMAGES_UPLOAD_DIR, 0755, true);
}
?>
