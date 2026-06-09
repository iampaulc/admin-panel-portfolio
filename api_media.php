<?php
require_once 'auth.php';
check_auth();

header('Content-Type: application/json');

// Fonction pour générer une miniature légère à la volée (WebP, largeur max 250px)
function generate_thumbnail($source_path, $dest_path, $ext, $max_width = 250) {
    if (!function_exists('imagecreatefromjpeg')) return false;
    
    $image = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg': $image = @imagecreatefromjpeg($source_path); break;
        case 'png': 
            $image = @imagecreatefrompng($source_path); 
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        case 'gif': $image = @imagecreatefromgif($source_path); break;
        case 'webp': $image = @imagecreatefromwebp($source_path); break;
    }
    
    if (!$image) return false;
    
    $orig_width = imagesx($image);
    $orig_height = imagesy($image);
    
    if ($orig_width > $max_width) {
        $new_width = $max_width;
        $new_height = floor($orig_height * ($max_width / $orig_width));
        
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($resized_image, false);
        imagesavealpha($resized_image, true);
        
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        imagedestroy($image);
        $image = $resized_image;
    }
    
    // Enregistre en WebP basse/moyenne qualité (60) pour des miniatures ultra-légères
    $success = imagewebp($image, $dest_path, 60);
    imagedestroy($image);
    return $success;
}

function getMediaFiles($dir) {
    $dir = realpath($dir);
    $images = [];
    if (!$dir || !is_dir($dir)) return $images;
    
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );
    
    $base = realpath($dir);
    
    // Créer le dossier racine des miniatures s'il n'existe pas
    $base_thumbs = $base . '/.thumbs';
    if (!is_dir($base_thumbs)) {
        @mkdir($base_thumbs, 0755, true);
    }
    
    foreach ($iter as $file) {
        if ($file->isFile()) {
            $path = $file->getPathname();
            
            // Exclure les fichiers se trouvant dans le dossier .thumbs du scan
            if (strpos($path, '.thumbs') !== false) {
                continue;
            }
            
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm'])) {
                $rel = str_ireplace($base, '', $path);
                $rel = trim(str_replace('\\', '/', $rel), '/');
                $copy_path = 'images/' . $rel;
                
                $thumb_url = ASSETS_BASE_URL . $copy_path;
                
                // Si c'est une image non-vectorielle, générer ou servir sa miniature WebP
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $subfolder = dirname($rel);
                    $subfolder = ($subfolder === '.' || $subfolder === '/') ? '' : $subfolder . '/';
                    
                    $thumb_dir = $base_thumbs . '/' . $subfolder;
                    if (!is_dir($thumb_dir)) {
                        @mkdir($thumb_dir, 0755, true);
                    }
                    
                    $filename = basename($path);
                    $thumb_filename = preg_replace('/\.[^.]+$/', '.webp', $filename);
                    $thumb_path = $thumb_dir . $thumb_filename;
                    $thumb_rel_path = 'images/.thumbs/' . $subfolder . $thumb_filename;
                    
                    if (!file_exists($thumb_path)) {
                        @generate_thumbnail($path, $thumb_path, $ext);
                    }
                    
                    if (file_exists($thumb_path)) {
                        $thumb_url = ASSETS_BASE_URL . $thumb_rel_path;
                    }
                }
                
                $images[] = [
                    'url' => ASSETS_BASE_URL . $copy_path,
                    'thumb_url' => $thumb_url,
                    'path' => $copy_path,
                    'name' => basename($path),
                    'time' => filemtime($path),
                    'size' => filesize($path),
                    'ext' => $ext
                ];
            }
        }
    }
    usort($images, function($a, $b) { return $b['time'] - $a['time']; });
    return $images;
}

// Validation d'upload
function validate_uploaded_file($file) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm'];
    $allowed_mimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm'
    ];
    $max_size = 50 * 1024 * 1024; // Augmenté à 50 Mo pour soutenir les grosses vidéos non-compressées

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_extensions)) {
        return ['valid' => false, 'error' => "Extension non autorisée : .$ext"];
    }

    if ($file['size'] > $max_size) {
        $size_mb = round($file['size'] / 1024 / 1024, 1);
        return ['valid' => false, 'error' => "Fichier trop volumineux ({$size_mb} Mo). Maximum : 50 Mo"];
    }

    if ($ext !== 'svg') {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($file['tmp_name']);
        if (!in_array($real_mime, $allowed_mimes)) {
            return ['valid' => false, 'error' => "Type de fichier invalide ($real_mime)"];
        }
    }

    return ['valid' => true, 'ext' => $ext];
}

// Redimensionnement et conversion en WebP
function process_image_optimization($source_path, $ext, $quality = 85, $max_width = 1920) {
    if ($ext === 'svg') {
        return $source_path;
    }

    if (!function_exists('imagecreatefromjpeg')) {
        return $source_path; 
    }

    $image = null;
    switch ($ext) {
        case 'jpg':
        case 'jpeg': $image = @imagecreatefromjpeg($source_path); break;
        case 'png': 
            $image = @imagecreatefrompng($source_path); 
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        case 'gif': $image = @imagecreatefromgif($source_path); break;
        case 'webp': $image = @imagecreatefromwebp($source_path); break;
    }

    if (!$image) return $source_path;

    $orig_width = imagesx($image);
    $orig_height = imagesy($image);

    if ($orig_width > $max_width) {
        $new_width = $max_width;
        $new_height = floor($orig_height * ($max_width / $orig_width));
        
        $resized_image = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($resized_image, false);
        imagesavealpha($resized_image, true);
        
        imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        imagedestroy($image);
        $image = $resized_image;
    }

    $webp_path = preg_replace('/\.[^.]+$/', '.webp', $source_path);
    
    if (imagewebp($image, $webp_path, $quality)) {
        imagedestroy($image);
        if (realpath($webp_path) !== realpath($source_path)) {
            @unlink($source_path);
        }
        return $webp_path;
    }

    imagedestroy($image);
    return $source_path; 
}

// GET : Lister les images
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(getMediaFiles(IMAGES_UPLOAD_DIR));
    exit;
}

// POST : Uploader une image (Via Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $validation = validate_uploaded_file($_FILES['file']);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        exit;
    }

    $target_dir = IMAGES_UPLOAD_DIR;
    $subfolder = trim($_POST['subfolder'] ?? '');
    
    if (!empty($subfolder)) {
        $subfolder = preg_replace("/[^a-zA-Z0-9_-]/", "", $subfolder) . '/';
        if (!is_dir($target_dir . $subfolder)) {
            mkdir($target_dir . $subfolder, 0755, true);
        }
    } else {
        $subfolder = '';
    }

    $filename = basename($_FILES["file"]["name"]);
    $filename = preg_replace("/[^a-zA-Z0-9.-]/", "_", $filename);
    $target_file = $target_dir . $subfolder . $filename;
    
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        // Taux de compression depuis la modal
        $compression = $_POST['compression'] ?? 'medium';
        $final_path = $target_file;
        $optimized = false;
        
        $ext = $validation['ext'];
        $is_video = in_array($ext, ['mp4', 'webm']);
        
        if ($compression !== 'none') {
            if ($is_video) {
                // Compression vidéo FFmpeg facultative
                $ffmpeg_check = @shell_exec('ffmpeg -version 2>&1');
                if ($ffmpeg_check && stripos($ffmpeg_check, 'ffmpeg version') !== false && $ext === 'mp4') {
                    $crf = 26;
                    if ($compression === 'extreme') $crf = 36;
                    elseif ($compression === 'high') $crf = 32;
                    elseif ($compression === 'low') $crf = 20;
                    
                    $compressed_path = preg_replace('/\.mp4$/i', '_min.mp4', $target_file);
                    $cmd = "ffmpeg -i " . escapeshellarg($target_file) . " -vcodec libx264 -crf {$crf} -preset fast -acodec aac -y " . escapeshellarg($compressed_path) . " 2>&1";
                    @shell_exec($cmd);
                    
                    if (file_exists($compressed_path) && filesize($compressed_path) > 0) {
                        unlink($target_file);
                        rename($compressed_path, $target_file);
                        $optimized = true;
                    }
                }
            } else {
                // Profils d'optimisation d'image
                $quality = 80;
                $max_width = 1920;
                
                if ($compression === 'extreme') {
                    $quality = 40;
                    $max_width = 1024;
                } elseif ($compression === 'high') {
                    $quality = 60;
                    $max_width = 1280;
                } elseif ($compression === 'low') {
                    $quality = 95;
                    $max_width = 2560;
                }
                
                $final_path = process_image_optimization($target_file, $ext, $quality, $max_width);
                $optimized = true;
            }
        }
        
        $final_filename = basename($final_path);
        $final_ext = strtolower(pathinfo($final_filename, PATHINFO_EXTENSION));
        $rel_path = 'images/' . ltrim($subfolder, '/') . $final_filename;
        
        // Pré-génération de la miniature légère
        $thumb_url = ASSETS_BASE_URL . $rel_path;
        if (in_array($final_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $base = realpath(IMAGES_UPLOAD_DIR);
            $thumb_dir = $base . '/.thumbs/' . $subfolder;
            if (!is_dir($thumb_dir)) {
                @mkdir($thumb_dir, 0755, true);
            }
            $thumb_filename = preg_replace('/\.[^.]+$/', '.webp', $final_filename);
            $thumb_path = $thumb_dir . $thumb_filename;
            @generate_thumbnail($final_path, $thumb_path, $final_ext);
            
            if (file_exists($thumb_path)) {
                $thumb_url = ASSETS_BASE_URL . 'images/.thumbs/' . ltrim($subfolder, '/') . $thumb_filename;
            }
        }
        
        echo json_encode([
            'success' => true, 
            'path' => $rel_path, 
            'url' => ASSETS_BASE_URL . $rel_path,
            'thumb_url' => $thumb_url,
            'optimized' => $optimized
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Échec de l\'upload']);
    }
    exit;
}

// DELETE : Supprimer une image
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $path = $input['path'] ?? '';
    
    if (empty($path)) {
        echo json_encode(['success' => false, 'error' => 'Chemin manquant']);
        exit;
    }

    $full_path = realpath(IMAGES_UPLOAD_DIR . '/' . str_replace('images/', '', $path));
    $base_dir = realpath(IMAGES_UPLOAD_DIR);
    
    if (!$full_path || strpos($full_path, $base_dir) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Chemin non autorisé']);
        exit;
    }

    // Supprimer aussi sa miniature si elle existe
    $filename = basename($full_path);
    $subfolder = str_replace($base_dir, '', dirname($full_path));
    $subfolder = trim(str_replace('\\', '/', $subfolder), '/');
    $subfolder = empty($subfolder) ? '' : $subfolder . '/';
    
    $thumb_filename = preg_replace('/\.[^.]+$/', '.webp', $filename);
    $thumb_path = $base_dir . '/.thumbs/' . $subfolder . $thumb_filename;
    if (file_exists($thumb_path)) {
        @unlink($thumb_path);
    }

    if (file_exists($full_path) && unlink($full_path)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Impossible de supprimer le fichier']);
    }
    exit;
}
?>
