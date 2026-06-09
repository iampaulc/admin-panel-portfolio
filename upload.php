<?php
require_once 'auth.php';
check_auth();

// Fonction pour générer une miniature à la volée (WebP, 250px)
function generate_thumbnail_in_upload($source_path, $dest_path, $ext, $max_width = 250) {
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
    
    $success = imagewebp($image, $dest_path, 60);
    imagedestroy($image);
    return $success;
}

$messages = [];
if (isset($_FILES['images'])) {
    verify_csrf();
    
    $target_dir = IMAGES_UPLOAD_DIR;
    $total_files = count($_FILES['images']['name']);
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm'];
    $allowed_mimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm'
    ];
    
    // Dossier optionnel
    $subfolder = trim($_POST['subfolder'] ?? '');
    if (!empty($subfolder)) {
        $subfolder = preg_replace("/[^a-zA-Z0-9_-]/", "", $subfolder) . '/';
        if (!is_dir($target_dir . $subfolder)) {
            mkdir($target_dir . $subfolder, 0755, true);
        }
    } else {
        $subfolder = '';
    }

    for ($i = 0; $i < $total_files; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES["images"]["name"][$i]);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Validation extension
            if (!in_array($ext, $allowed_extensions)) {
                $messages[] = ['type' => 'danger', 'text' => "Extension non autorisée pour <strong>{$filename}</strong>"];
                continue;
            }
            
            // Validation MIME réel
            if ($ext !== 'svg') {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $real_mime = $finfo->file($_FILES['images']['tmp_name'][$i]);
                if (!in_array($real_mime, $allowed_mimes)) {
                    $messages[] = ['type' => 'danger', 'text' => "Type invalide pour <strong>{$filename}</strong> ($real_mime)"];
                    continue;
                }
            }
            
            // Nettoyage nom de fichier
            $filename = preg_replace("/[^a-zA-Z0-9.-]/", "_", $filename);
            $target_file = $target_dir . $subfolder . $filename;
            
            if (move_uploaded_file($_FILES["images"]["tmp_name"][$i], $target_file)) {
                $converted = false;
                $compressed_msg = '';
                
                // Récupérer le niveau de compression pour cette image spécifique
                $compression = $_POST['compression'][$i] ?? 'medium';
                
                if ($compression !== 'none') {
                    if ($ext === 'mp4') {
                        // Vérifier si ffmpeg est installé
                        $ffmpeg_check = @shell_exec('ffmpeg -version 2>&1');
                        if ($ffmpeg_check && stripos($ffmpeg_check, 'ffmpeg version') !== false) {
                            $crf = 26;
                            if ($compression === 'extreme') $crf = 36;
                            elseif ($compression === 'high') $crf = 32;
                            elseif ($compression === 'low') $crf = 20;
                            
                            $compressed_path = preg_replace('/\.mp4$/i', '_min.mp4', $target_file);
                            $cmd = "ffmpeg -i " . escapeshellarg($target_file) . " -vcodec libx264 -crf {$crf} -preset fast -acodec aac -y " . escapeshellarg($compressed_path) . " 2>&1";
                            @shell_exec($cmd);
                            
                            if (file_exists($compressed_path) && filesize($compressed_path) > 0) {
                                $old_size = filesize($target_file);
                                $new_size = filesize($compressed_path);
                                if ($new_size < $old_size) {
                                    unlink($target_file);
                                    rename($compressed_path, $target_file);
                                    $saved_percent = round((1 - ($new_size / $old_size)) * 100);
                                    $compressed_msg = ' <span style="color:var(--accent-color);">(Compressée -' . $saved_percent . '%)</span>';
                                } else {
                                    unlink($compressed_path);
                                }
                            }
                        }
                    }
                    elseif (!in_array($ext, ['svg', 'webp', 'webm', 'gif']) && function_exists('imagecreatefromjpeg')) {
                        $image = null;
                        switch ($ext) {
                            case 'jpg': case 'jpeg': $image = @imagecreatefromjpeg($target_file); break;
                            case 'png':
                                $image = @imagecreatefrompng($target_file);
                                if ($image) { imagepalettetotruecolor($image); imagealphablending($image, true); imagesavealpha($image, true); }
                                break;
                        }
                        if ($image) {
                            $quality = 80;
                            $max_width = 1920;
                            
                            if ($compression === 'extreme') { $quality = 40; $max_width = 1024; }
                            elseif ($compression === 'high') { $quality = 60; $max_width = 1280; }
                            elseif ($compression === 'low') { $quality = 95; $max_width = 2560; }
                            
                            // Redimensionnement si nécessaire
                            $orig_width = imagesx($image);
                            $orig_height = imagesy($image);
                            if ($orig_width > $max_width) {
                                $new_width = $max_width;
                                $new_height = floor($orig_height * ($max_width / $orig_width));
                                $resized = imagecreatetruecolor($new_width, $new_height);
                                imagealphablending($resized, false);
                                imagesavealpha($resized, true);
                                imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
                                imagedestroy($image);
                                $image = $resized;
                            }
                            
                            $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $target_file);
                            if (imagewebp($image, $webp_path, $quality)) {
                                imagedestroy($image);
                                if ($webp_path !== $target_file) unlink($target_file);
                                $filename = basename($webp_path);
                                $converted = true;
                                $target_file = $webp_path;
                                $ext = 'webp';
                            } else {
                                imagedestroy($image);
                            }
                        }
                    }
                }
                
                // Pré-génération de la miniature WebP ultra-légère dans images/.thumbs/
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $thumb_dir = IMAGES_UPLOAD_DIR . '/.thumbs/' . $subfolder;
                    if (!is_dir($thumb_dir)) {
                        @mkdir($thumb_dir, 0755, true);
                    }
                    $thumb_filename = preg_replace('/\.[^.]+$/', '.webp', basename($target_file));
                    $thumb_path = $thumb_dir . $thumb_filename;
                    @generate_thumbnail_in_upload($target_file, $thumb_path, $ext);
                }
                
                $conv_tag = $converted ? ' <span style="color:var(--accent-color);">(→ WebP)</span>' : $compressed_msg;
                $messages[] = ['type' => 'success', 'text' => "Image <strong>{$filename}</strong> uploadée avec succès !{$conv_tag}"];
            } else {
                $messages[] = ['type' => 'danger', 'text' => "Erreur lors de l'upload de {$filename}."];
            }
        }
    }
}

// Suppression d'une image
if (isset($_POST['delete_image'])) {
    verify_csrf();
    $path_to_delete = $_POST['delete_image'];
    $full_path = realpath(IMAGES_UPLOAD_DIR . '/' . str_replace('images/', '', $path_to_delete));
    $base_dir = realpath(IMAGES_UPLOAD_DIR);
    
    if ($full_path && strpos($full_path, $base_dir) === 0 && file_exists($full_path)) {
        // Supprimer aussi sa miniature
        $filename = basename($full_path);
        $subfolder = str_replace($base_dir, '', dirname($full_path));
        $subfolder = trim(str_replace('\\', '/', $subfolder), '/');
        $subfolder = empty($subfolder) ? '' : $subfolder . '/';
        
        $thumb_filename = preg_replace('/\.[^.]+$/', '.webp', $filename);
        $thumb_path = $base_dir . '/.thumbs/' . $subfolder . $thumb_filename;
        if (file_exists($thumb_path)) {
            @unlink($thumb_path);
        }

        unlink($full_path);
        $messages[] = ['type' => 'success', 'text' => 'Image supprimée avec succès.'];
    } else {
        $messages[] = ['type' => 'danger', 'text' => 'Impossible de supprimer cette image.'];
    }
}

// Fonction pour récupérer toutes les images existantes (avec miniatures)
function getImages($dir) {
    $dir = realpath($dir);
    $images = [];
    if (!$dir || !is_dir($dir)) return $images;
    
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );
    
    $base = realpath(IMAGES_UPLOAD_DIR);
    $base_thumbs = $base . '/.thumbs';
    if (!is_dir($base_thumbs)) {
        @mkdir($base_thumbs, 0755, true);
    }
    
    foreach ($iter as $file) {
        if ($file->isFile()) {
            $path = $file->getPathname();
            
            // Ignorer le répertoire .thumbs dans le listing général
            if (strpos($path, '.thumbs') !== false) {
                continue;
            }
            
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm'])) {
                $rel = str_ireplace($base, '', $path);
                $rel = trim(str_replace('\\', '/', $rel), '/');
                $copy_path = 'images/' . $rel;
                
                $thumb_url = ASSETS_BASE_URL . $copy_path;
                
                // Pré-générer la miniature si elle n'existe pas encore
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
                        generate_thumbnail_in_upload($path, $thumb_path, $ext);
                    }
                    
                    if (file_exists($thumb_path)) {
                        $thumb_url = ASSETS_BASE_URL . $thumb_rel_path;
                    }
                }
                
                $images[] = [
                    'path' => $path,
                    'copy_path' => $copy_path,
                    'thumb_url' => $thumb_url,
                    'time' => filemtime($path),
                    'size' => filesize($path),
                    'name' => basename($path)
                ];
            }
        }
    }
    usort($images, function($a, $b) {
        return $b['time'] - $a['time'];
    });
    return $images;
}

$all_images = getImages(IMAGES_UPLOAD_DIR);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médiathèque - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(0,0,0,0.2);
            position: relative;
        }
        .upload-area.dragover {
            border-color: var(--accent-color);
            background: rgba(88, 166, 255, 0.05);
        }
        .upload-area input[type="file"] {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .file-list { margin-top: 1rem; text-align: left; }
        .file-list-item { 
            background: rgba(255,255,255,0.05); 
            padding: 0.8rem 1rem; 
            border-radius: 8px; 
            margin-bottom: 0.5rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            font-size: 0.9rem;
            border: 1px solid var(--border-color);
        }
        
        .media-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
            gap: 1.2rem; 
            margin-top: 1.5rem; 
        }
        .media-item { 
            background: rgba(255,255,255,0.03); 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            padding: 0.6rem; 
            text-align: center; 
            transition: all 0.2s; 
            position: relative; 
            content-visibility: auto;
            contain-intrinsic-size: 190px;
        }
        .media-item:hover { border-color: var(--accent-color); transform: translateY(-3px); }
        .media-item img, .media-item video { width: 100%; height: 120px; object-fit: contain; border-radius: 4px; margin-bottom: 0.5rem; background: #111; padding: 4px; }
        .media-item .path-input { width: 100%; padding: 0.4rem; font-size: 0.75rem; text-align: center; cursor: pointer; border: 1px solid var(--border-color); background: rgba(0,0,0,0.5); color: #8b949e; border-radius: 4px; }
        .media-item .path-input:focus { color: #fff; background: var(--bg-color); }
        
        .media-item .delete-overlay { position: absolute; top: 6px; right: 6px; background: var(--danger-color); color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: none; align-items: center; justify-content: center; z-index: 10; }
        .media-item:hover .delete-overlay { display: flex; }
        
        /* Bouton Loupe pour preview grand format */
        .media-item .preview-btn { position: absolute; top: 6px; left: 6px; background: var(--accent-color); color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: none; align-items: center; justify-content: center; z-index: 10; }
        .media-item:hover .preview-btn { display: flex; }
        
        .media-item .size-label { position: absolute; bottom: 45px; right: 10px; background: rgba(0,0,0,0.75); color: var(--text-secondary); font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 500; }
        .search-bar { margin-bottom: 1rem; }
        .search-bar input { width: 100%; padding: 0.8rem 1rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; font-size: 0.95rem; }
        .copy-toast { position: fixed; bottom: 2rem; right: 2rem; background: var(--success-color); color: white; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 500; z-index: 9999; opacity: 0; transform: translateY(20px); transition: all 0.3s; pointer-events: none; }
        .copy-toast.show { opacity: 1; transform: translateY(0); }
        
        /* Styles de la Lightbox de Preview */
        .modal-overlay { 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.85); 
            z-index: 99999; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            backdrop-filter: blur(8px); 
        }
    </style>
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php $active_page = 'upload'; require_once 'sidebar.php'; ?>
        <main class="main-content">
            <header class="header-actions">
                <h1>Médiathèque</h1>
                <span style="color: var(--text-muted);"><?php echo count($all_images); ?> images</span>
            </header>

            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?php echo $msg['type']; ?>"><?php echo $msg['text']; ?></div>
            <?php endforeach; ?>

            <details class="card" style="margin-bottom: 2rem; cursor: pointer;">
                <summary style="font-size: 1.1rem; font-weight: 600; outline: none; list-style: none; display: flex; align-items: center; justify-content: space-between;">
                    <span>📥 Ajouter de nouveaux médias</span>
                    <span style="font-size: 0.8rem; color: var(--accent-color);">Dérouler</span>
                </summary>
                
                <div style="margin-top: 1.5rem; cursor: default; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                    <form action="upload.php" method="post" enctype="multipart/form-data" id="upload-form">
                        <?php echo csrf_field(); ?>
                        <div class="form-group" style="max-width: 300px;">
                            <label>Dossier de destination (Optionnel)</label>
                            <input type="text" name="subfolder" placeholder="Ex: projet1">
                            <small style="color: var(--text-muted); display: block; margin-top: 5px;">Laissez vide pour la racine de <code>images/</code>.</small>
                        </div>

                        <div class="form-group">
                            <label>Sélectionnez une ou plusieurs images</label>
                            <div class="upload-area" id="drop-zone">
                                <span style="font-size: 2rem;">📥</span>
                                <h3 style="margin: 1rem 0 0.5rem;">Glissez-déposez vos fichiers ici</h3>
                                <p style="color: var(--text-muted);">ou cliquez pour parcourir (Images & Vidéos)</p>
                                <input type="file" name="images[]" multiple required id="file-input" accept="image/*,video/mp4,video/webm" aria-label="Sélectionner des images ou vidéos">
                            </div>
                            <div class="file-list" id="file-list"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Uploader tout</button>
                    </form>
                </div>
            </details>
            
            <div class="card">
                <h3>Images sur le Serveur (<?php echo count($all_images); ?>)</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Cliquez sur le champ de texte pour copier le chemin. Survolez pour prévisualiser (🔍) ou supprimer (✕).</p>
                
                <div class="search-bar" style="margin-top: 1rem;">
                    <input type="text" id="media-search" placeholder="🔍 Rechercher par nom de fichier..." oninput="filterMedia(this.value)" aria-label="Rechercher une image">
                </div>

                <div class="media-grid">
                    <!-- Rendu dynamique JS pour optimisation de vitesse -->
                </div>
                
                <div id="load-more-sentinel" style="text-align: center; padding: 2rem; color: var(--text-muted); cursor: pointer; font-weight: bold; border: 1px dashed var(--border-color); border-radius: 8px; margin-top: 1.5rem; background: rgba(255,255,255,0.01);" onclick="renderNextBatch()">
                    📥 Défiler ou cliquer pour charger plus d'images...
                </div>
            </div>
            <?php require_once 'footer.php'; ?>
        </main>
    </div>

    <!-- Lightbox de Prévisualisation Grand Format -->
    <div id="preview-lightbox" class="modal-overlay" style="display:none;" onclick="closePreviewLightbox()">
        <div class="modal-content card glass-panel" style="width: 90%; max-width: 900px; max-height: 90vh; text-align: center; border: 1px solid var(--border-color); padding: 1.5rem; position: relative; display: flex; flex-direction: column;" onclick="event.stopPropagation()">
            <button type="button" onclick="closePreviewLightbox()" class="btn-ctrl btn-remove" style="position: absolute; top: 1rem; right: 1rem; background: none; font-size: 1.5rem; padding: 0.2rem 1rem;" aria-label="Fermer">✕</button>
            <h3 id="lightbox-title" style="margin-top: 0; margin-bottom: 1rem; font-family: var(--font-title); font-size: 1.25rem;">Aperçu</h3>
            
            <div id="lightbox-media-container" style="flex: 1; display: flex; justify-content: center; align-items: center; min-height: 0; background: #070708; border-radius: 8px; border: 1px solid var(--border-color); padding: 1rem;">
                <!-- Rendu de l'image ou de la vidéo -->
            </div>
            
            <div id="lightbox-meta" style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary); display: flex; justify-content: space-between; align-items: center;">
                <!-- Meta infos -->
            </div>
        </div>
    </div>

    <div class="copy-toast" id="copy-toast">✓ Chemin copié !</div>

    <script>
        const allMediaItems = <?php echo json_encode($all_images, JSON_UNESCAPED_UNICODE); ?>;
        let currentIndex = 0;
        const BATCH_SIZE = 40;

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Rendu progressif par lots
        function renderNextBatch() {
            const grid = document.querySelector('.media-grid');
            const batch = allMediaItems.slice(currentIndex, currentIndex + BATCH_SIZE);
            
            if (batch.length === 0) return;
            
            batch.forEach(img => {
                const ext = img.name.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm'].includes(ext);
                const originalUrl = img.thumb_url.replace('/.thumbs/', '/');
                
                const div = document.createElement('div');
                div.className = 'media-item';
                div.dataset.name = img.name;
                div.dataset.path = img.copy_path;
                
                div.innerHTML = `
                    <span class="size-label">${Math.round(img.size / 1024)} KB</span>
                    <button type="button" class="preview-btn" onclick="previewMedia('${originalUrl}', '${escapeHtml(img.name)}', '${ext}', '${Math.round(img.size / 1024)} KB')" title="Prévisualiser grand format">🔍</button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="${document.getElementsByName('csrf_token')[0].value}">
                        <input type="hidden" name="delete_image" value="${escapeHtml(img.copy_path)}">
                        <button type="submit" class="delete-overlay" onclick="return confirm('Supprimer cette image ?')" title="Supprimer" aria-label="Supprimer ${escapeHtml(img.name)}">✕</button>
                    </form>
                    ${isVideo ? 
                        `<video src="${img.thumb_url}" loading="lazy" muted loop autoplay style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem; background: #222;"></video>` : 
                        `<img src="${img.thumb_url}" loading="lazy" alt="${escapeHtml(img.name)}">`
                    }
                    <input class="path-input" type="text" value="${escapeHtml(img.copy_path)}" readonly onclick="copyPath(this)" aria-label="Chemin de l'image">
                `;
                grid.appendChild(div);
            });
            
            currentIndex += BATCH_SIZE;
            
            // Masquer le sentinel si tout est chargé
            if (currentIndex >= allMediaItems.length) {
                document.getElementById('load-more-sentinel').style.display = 'none';
            }
        }

        // Init scroll observer pour le chargement infini
        document.addEventListener('DOMContentLoaded', () => {
            renderNextBatch();
            
            const sentinel = document.getElementById('load-more-sentinel');
            if (sentinel && 'IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting && currentIndex < allMediaItems.length) {
                        renderNextBatch();
                    }
                }, { rootMargin: '250px' });
                observer.observe(sentinel);
            }
        });

        // Drag & drop zone
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const fileList = document.getElementById('file-list');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        fileInput.addEventListener('change', updateFileList);

        function updateFileList() {
            fileList.innerHTML = '';
            const files = fileInput.files;
            if(files.length > 0) {
                Array.from(files).forEach((file, index) => {
                    const div = document.createElement('div');
                    div.className = 'file-list-item';
                    div.innerHTML = `
                        <div style="flex:1; min-width:0; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; text-align:left;">
                            📄 <strong>${escapeHtml(file.name)}</strong> 
                            <span style="color: var(--text-muted); font-size: 0.8rem; margin-left: 8px;">(${(file.size / (1024 * 1024)).toFixed(2)} MB)</span>
                        </div>
                        <div style="flex-shrink:0; display:flex; align-items:center; gap:8px;">
                            <span style="font-size:0.8rem; color:var(--text-muted)">Compression :</span>
                            <select name="compression[]" style="padding:0.3rem 0.6rem; background:rgba(0,0,0,0.5); border:1px solid var(--border-color); border-radius:4px; color:#fff; font-size:0.8rem; cursor:pointer;">
                                <option value="medium" selected>Moyenne (Recommandée)</option>
                                <option value="extreme">Extrême (Compression Max, Fichier ultra-léger)</option>
                                <option value="high">Forte (Grosse compression)</option>
                                <option value="low">Légère (Qualité 4K/Haute)</option>
                                <option value="none">Aucune (Fichier original brut)</option>
                            </select>
                        </div>
                    `;
                    fileList.appendChild(div);
                });
            }
        }

        // Lightbox Preview Actions
        function previewMedia(url, name, ext, sizeLabel) {
            const lightbox = document.getElementById('preview-lightbox');
            const container = document.getElementById('lightbox-media-container');
            const title = document.getElementById('lightbox-title');
            const meta = document.getElementById('lightbox-meta');
            
            title.textContent = name;
            container.innerHTML = '';
            
            if (ext === 'mp4' || ext === 'webm') {
                const video = document.createElement('video');
                video.src = url;
                video.controls = true;
                video.autoplay = true;
                video.style.maxWidth = '100%';
                video.style.maxHeight = '65vh';
                container.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = url;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '65vh';
                img.style.objectFit = 'contain';
                container.appendChild(img);
            }
            
            meta.innerHTML = `
                <span style="font-weight: 500;">Poids Original : ${sizeLabel}</span>
                <a href="${url}" target="_blank" style="color: var(--accent-color); text-decoration: none; font-weight: 600;">Ouvrir l'original dans un nouvel onglet ↗</a>
            `;
            lightbox.style.display = 'flex';
        }

        function closePreviewLightbox() {
            document.getElementById('preview-lightbox').style.display = 'none';
            document.getElementById('lightbox-media-container').innerHTML = '';
        }

        // Copier chemin
        function copyPath(input) {
            const text = input.value;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => showCopyToast());
            } else {
                input.select();
                document.execCommand('copy');
                showCopyToast();
            }
        }

        function showCopyToast() {
            const toast = document.getElementById('copy-toast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

        // Filtrer les images avec debounce
        let searchTimeout;
        function filterMedia(query) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                query = query.toLowerCase().trim();
                const grid = document.querySelector('.media-grid');
                
                if (query === '') {
                    grid.innerHTML = '';
                    currentIndex = 0;
                    document.getElementById('load-more-sentinel').style.display = '';
                    renderNextBatch();
                    return;
                }
                
                grid.innerHTML = '';
                document.getElementById('load-more-sentinel').style.display = 'none';
                
                const matches = allMediaItems.filter(img => 
                    img.name.toLowerCase().includes(query) || img.copy_path.toLowerCase().includes(query)
                );
                
                if (matches.length === 0) {
                    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">Aucun média correspondant.</div>';
                    return;
                }
                
                matches.forEach(img => {
                    const ext = img.name.split('.').pop().toLowerCase();
                    const isVideo = ['mp4', 'webm'].includes(ext);
                    const originalUrl = img.thumb_url.replace('/.thumbs/', '/');
                    
                    const div = document.createElement('div');
                    div.className = 'media-item';
                    div.dataset.name = img.name;
                    div.dataset.path = img.copy_path;
                    
                    div.innerHTML = `
                        <span class="size-label">${Math.round(img.size / 1024)} KB</span>
                        <button type="button" class="preview-btn" onclick="previewMedia('${originalUrl}', '${escapeHtml(img.name)}', '${ext}', '${Math.round(img.size / 1024)} KB')" title="Prévisualiser grand format">🔍</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="${document.getElementsByName('csrf_token')[0].value}">
                            <input type="hidden" name="delete_image" value="${escapeHtml(img.copy_path)}">
                            <button type="submit" class="delete-overlay" onclick="return confirm('Supprimer cette image ?')" title="Supprimer" aria-label="Supprimer ${escapeHtml(img.name)}">✕</button>
                        </form>
                        ${isVideo ? 
                            `<video src="${img.thumb_url}" loading="lazy" muted loop autoplay style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem; background: #222;"></video>` : 
                            `<img src="${img.thumb_url}" loading="lazy" alt="${escapeHtml(img.name)}">`
                        }
                        <input class="path-input" type="text" value="${escapeHtml(img.copy_path)}" readonly onclick="copyPath(this)" aria-label="Chemin de l'image">
                    `;
                    grid.appendChild(div);
                });
            }, 250);
        }
    </script>
</body>
</html>
