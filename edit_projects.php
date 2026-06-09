<?php
require_once 'auth.php';
check_auth();

$json_data = @file_get_contents(JSON_DATA_PATH);
$data = json_decode($json_data, true);
if (!$data) $data = ['projets' => []];

if (!isset($data['tagsDisponibles'])) {
    $data['tagsDisponibles'] = ["Branding", "Design", "Logo", "2024", "Graphisme", "2025", "Art", "Dessin", "Série", "Web Dev", "Front", "Back", "Motion Design", "Montage", "2023", "Texture"];
}

$editing_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$project_to_edit = null;

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)$_POST['id'];
    
    if (isset($_POST['delete_project'])) {
        foreach ($data['projets'] as $key => $p) {
            if ($p['id'] == $id) {
                array_splice($data['projets'], $key, 1);
                break;
            }
        }
        // Backup + save
        if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
        $fp = fopen(JSON_DATA_PATH, 'c');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        header('Location: edit_projects.php?status=deleted');
        exit;

    } elseif (isset($_POST['reorder_projects'])) {
        // Réorganisation des projets
        $order = json_decode($_POST['project_order'], true);
        if (is_array($order)) {
            $reordered = [];
            foreach ($order as $pid) {
                foreach ($data['projets'] as $p) {
                    if ($p['id'] == $pid) {
                        $reordered[] = $p;
                        break;
                    }
                }
            }
            $data['projets'] = $reordered;
            if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
            $fp = fopen(JSON_DATA_PATH, 'c');
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        header('Location: edit_projects.php?status=saved');
        exit;
    } elseif (isset($_POST['add_global_tag'])) {
        $new_tag = trim($_POST['new_tag']);
        if (!empty($new_tag) && !in_array($new_tag, $data['tagsDisponibles'])) {
            $data['tagsDisponibles'][] = $new_tag;
            sort($data['tagsDisponibles']);
            
            if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
            $fp = fopen(JSON_DATA_PATH, 'c');
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        header('Location: edit_projects.php?status=saved');
        exit;
    } elseif (isset($_POST['delete_global_tag'])) {
        $tag_to_delete = trim($_POST['tag_to_delete']);
        $key = array_search($tag_to_delete, $data['tagsDisponibles']);
        if ($key !== false) {
            array_splice($data['tagsDisponibles'], $key, 1);
            
            if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
            $fp = fopen(JSON_DATA_PATH, 'c');
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        header('Location: edit_projects.php?status=saved');
        exit;
    } else {
        // Création ou Mise à jour
        $is_new = ($id === -1);
        
        $tags = isset($_POST['selected_tags']) ? (array)$_POST['selected_tags'] : [];
        
        // Find existing project to preserve media formats (especially for YouTube videos)
        $old_project = null;
        if (!$is_new) {
            foreach ($data['projets'] as $p) {
                if ($p['id'] == $id) {
                    $old_project = $p;
                    break;
                }
            }
        }

        $galerie = [];
        if (isset($_POST['galerie_path'])) {
            foreach ($_POST['galerie_path'] as $i => $path) {
                if (!empty(trim($path))) {
                    $type = $_POST['galerie_type'][$i] ?? 'image';
                    
                    // Look up if this item already has a format (e.g. portrait) in the loaded DB to preserve it
                    $existing_format = '';
                    if ($old_project && isset($old_project['galerie'])) {
                        foreach ($old_project['galerie'] as $existing_item) {
                            if ($existing_item[0] === trim($path) && isset($existing_item[3])) {
                                $existing_format = $existing_item[3];
                            }
                        }
                    }
                    
                    // If it is a local image, check the actual file dimensions to auto-detect portrait/landscape
                    if ($type === 'image') {
                        $full_img_path = IMAGES_UPLOAD_DIR . preg_replace('/^images\//', '', trim($path));
                        if (file_exists($full_img_path)) {
                            $sizes = @getimagesize($full_img_path);
                            if ($sizes) {
                                if ($sizes[1] > $sizes[0]) {
                                    $existing_format = 'portrait';
                                } else {
                                    $existing_format = ''; // reset to landscape/standard
                                }
                            }
                        }
                    }
                    
                    $item_arr = [trim($path), trim($_POST['galerie_caption'][$i] ?? ''), $type];
                    if (!empty($existing_format)) {
                        $item_arr[] = $existing_format;
                    }
                    
                    $galerie[] = $item_arr;
                }
            }
        }

        $dossier = [];
        if (isset($_POST['dossier_type'])) {
            foreach ($_POST['dossier_type'] as $i => $type) {
                if (!empty(trim($type))) {
                    $dossier[] = [
                        'type' => trim($type),
                        'titre' => trim($_POST['dossier_titre'][$i] ?? ''),
                        'texte' => trim($_POST['dossier_texte'][$i] ?? ''),
                        'image1' => trim($_POST['dossier_image1'][$i] ?? ''),
                        'alt1' => trim($_POST['dossier_alt1'][$i] ?? ''),
                        'legende1' => trim($_POST['dossier_legende1'][$i] ?? ''),
                        'image2' => trim($_POST['dossier_image2'][$i] ?? ''),
                        'alt2' => trim($_POST['dossier_alt2'][$i] ?? ''),
                        'legende2' => trim($_POST['dossier_legende2'][$i] ?? ''),
                        'accent' => trim($_POST['dossier_accent'][$i] ?? '')
                    ];
                }
            }
        }

        // Sanitize projetId (slug)
        $projetId = trim($_POST['projetId']);
        $projetId = preg_replace('/[^a-z0-9-]/', '-', strtolower($projetId));
        $projetId = preg_replace('/-+/', '-', trim($projetId, '-'));

        $project_data = [
            'projetId' => $projetId,
            'image' => trim($_POST['image']),
            'alt' => trim($_POST['alt'] ?? ''),
            'titre' => trim($_POST['titre']),
            'tags' => array_values($tags),
            'categorie' => trim($_POST['categorie'] ?? 'perso'),
            'importance' => isset($_POST['importance']) ? (int)$_POST['importance'] : 5,
            'annee' => trim($_POST['annee'] ?? ''),
            'brief' => trim($_POST['brief'] ?? ''),
            'sousTitre' => trim($_POST['sousTitre']),
            'date' => trim($_POST['date']),
            'role' => trim($_POST['role']),
            'technos' => trim($_POST['technos'] ?? ''),
            'concept' => trim($_POST['concept']),
            'defis' => trim($_POST['defis'] ?? ''),
            'resultats' => trim($_POST['resultats'] ?? ''),
            'seo_title' => trim($_POST['seo_title'] ?? ''),
            'seo_description' => trim($_POST['seo_description'] ?? ''),
            'iframe' => trim($_POST['iframe'] ?? ''),
            'youtubeId' => trim($_POST['youtubeId'] ?? ''),
            'site' => [
                'url' => trim($_POST['site_url'] ?? ''),
                'label' => trim($_POST['site_label'] ?? '')
            ],
            'status' => trim($_POST['status'] ?? 'published'),
            'galerie' => $galerie,
            'dossier' => $dossier
        ];

        // Normaliser : si pas d'URL, garder un objet vide mais cohérent
        if (empty($project_data['site']['url'])) {
            $project_data['site'] = ['url' => '', 'label' => ''];
        }

        if ($is_new) {
            $max_id = 0;
            foreach ($data['projets'] as $p) {
                if ($p['id'] > $max_id) $max_id = $p['id'];
            }
            $project_data['id'] = $max_id + 1;
            $data['projets'][] = $project_data;
            $id = $project_data['id'];
        } else {
            foreach ($data['projets'] as &$p) {
                if ($p['id'] == $id) {
                    $project_data['id'] = $id; 
                    $p = $project_data;
                    break;
                }
            }
        }
        
        if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
        $fp = fopen(JSON_DATA_PATH, 'c');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        header('Location: edit_projects.php?id='.$id.'&status=saved');
        exit;
    }
}

$is_creating = isset($_GET['action']) && $_GET['action'] === 'new';

if ($editing_id !== null && !$is_creating) {
    foreach ($data['projets'] as $p) {
        if ($p['id'] == $editing_id) {
            $project_to_edit = $p;
            break;
        }
    }
} else if ($is_creating) {
    $next_id = 1;
    if (!empty($data['projets'])) {
        foreach ($data['projets'] as $proj) {
            if ($proj['id'] >= $next_id) $next_id = $proj['id'] + 1;
        }
    }
    $project_to_edit = [
        'id' => -1, 'projetId' => '', 'image' => '', 'alt' => '',
        'titre' => '', 'tags' => [], 'categorie' => 'perso', 'importance' => 5, 'annee' => '', 'brief' => '', 'sousTitre' => '',
        'date' => '', 'role' => '', 'technos' => '', 'concept' => '',
        'defis' => '', 'resultats' => '', 'iframe' => '',
        'youtubeId' => '', 'site' => ['url' => '', 'label' => ''], 'status' => 'published', 'galerie' => [], 'dossier' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer Projets - Admin Portfolio</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php $active_page = 'projects'; require_once 'sidebar.php'; ?>

        <main class="main-content">
            <header class="header-actions">
                <h1><?php echo $project_to_edit ? "Modifier : " . htmlspecialchars($project_to_edit['titre'] ?: 'Nouveau Projet') : "Mes Projets"; ?></h1>
                <?php if ($project_to_edit): ?>
                    <div style="display:flex; gap: 1rem;">
                        <?php if ($project_to_edit['id'] !== -1): ?>
                            <a href="export_project.php?id=<?php echo $project_to_edit['id']; ?>&format=json" class="btn btn-secondary">📥 Exporter JSON</a>
                            <a href="export_project.php?id=<?php echo $project_to_edit['id']; ?>&format=zip" class="btn btn-secondary">📦 Exporter ZIP</a>
                        <?php endif; ?>
                        <a href="edit_projects.php" class="btn btn-secondary">← Retour aux Projets</a>
                    </div>
                <?php else: ?>
                    <div style="display:flex; gap: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('import-modal').style.display='flex'">📥 Importer Projet</button>
                        <a href="edit_projects.php?action=new" class="btn btn-primary">+ Nouveau Projet</a>
                    </div>
                <?php endif; ?>
            </header>

            <?php if (!$project_to_edit): ?>
                <!-- Vue liste avec réorganisation -->
                <form method="POST" id="reorder-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="reorder_projects" value="1">
                    <input type="hidden" name="project_order" id="project-order" value="">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Glissez les cartes pour réorganiser l'ordre des projets sur le portfolio.</p>
                        <button type="submit" class="btn btn-primary" id="save-order-btn" style="display:none;">💾 Sauvegarder l'ordre</button>
                    </div>
                </form>

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <button class="btn btn-secondary tab-btn active" onclick="switchTab('published')">Publiés</button>
                    <button class="btn btn-secondary tab-btn" onclick="switchTab('draft')">Brouillons</button>
                    <button class="btn btn-secondary tab-btn" onclick="switchTab('archived')">Archives</button>
                </div>

                <style>
                    .tab-content { display: none; }
                    .tab-content.active { display: block; }
                    .tab-btn.active { background: var(--accent-color); color: white; border-color: var(--accent-color); }
                    .status-badge {
                        position: absolute; top: 10px; right: 10px;
                        padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: bold; color: white;
                    }
                    .status-draft { background: #f59e0b; }
                    .status-archived { background: #6b7280; }
                </style>

                <?php 
                $grouped = ['published' => [], 'draft' => [], 'archived' => []];
                foreach ($data['projets'] as $p) {
                    $status = $p['status'] ?? 'published';
                    if (!isset($grouped[$status])) $status = 'published';
                    $grouped[$status][] = $p;
                }
                ?>

                <?php foreach (['published', 'draft', 'archived'] as $status_key): ?>
                    <div id="tab-<?php echo $status_key; ?>" class="tab-content <?php echo $status_key === 'published' ? 'active' : ''; ?>">
                        <div class="card-grid" id="projects-grid-<?php echo $status_key; ?>">
                            <?php foreach ($grouped[$status_key] as $p): ?>
                                <div class="card drag-item" draggable="true" data-id="<?php echo $p['id']; ?>" style="position: relative; text-decoration: none; color: inherit; display: block; cursor: grab;">
                                    <?php if ($status_key === 'draft'): ?>
                                        <div class="status-badge status-draft">Brouillon</div>
                                    <?php elseif ($status_key === 'archived'): ?>
                                        <div class="status-badge status-archived">Archivé</div>
                                    <?php endif; ?>
                                    
                                    <a href="edit_projects.php?id=<?php echo $p['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <img src="<?php echo ASSETS_BASE_URL . htmlspecialchars($p['image']); ?>" style="width: 100%; height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 1rem;" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'><rect width=\'100%\' height=\'100%\' fill=\'%23333\'/></svg>'" alt="<?php echo htmlspecialchars($p['alt'] ?? ''); ?>">
                                        <h3 style="margin: 0; padding-right: 60px;"><?php echo htmlspecialchars($p['titre']); ?></h3>
                                        <p style="font-size: 0.85rem; margin-top: 0.5rem; opacity: 0.7;"><?php echo htmlspecialchars($p['date']); ?> — <?php echo implode(', ', array_map('htmlspecialchars', $p['tags'] ?? [])); ?></p>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; font-size: 0.8rem; opacity: 0.9; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 0.5rem;">
                                            <span style="background: rgba(255,255,255,0.06); padding: 0.2rem 0.5rem; border-radius: 4px;">
                                                <?php 
                                                    $cat = $p['categorie'] ?? 'perso';
                                                    if ($cat === 'universitaire') echo '🎓 Uni';
                                                    elseif ($cat === 'pro') echo '💼 Pro';
                                                    else echo '🎨 Perso';
                                                ?>
                                            </span>
                                            <span style="color: #eab308; font-weight: bold;">⭐ <?php echo isset($p['importance']) ? (int)$p['importance'] : 5; ?>/10</span>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (empty($grouped[$status_key])): ?>
                            <p style="color: var(--text-muted); padding: 2rem; text-align: center; background: rgba(0,0,0,0.2); border-radius: 8px;">Aucun projet dans cette catégorie.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <script>
                function switchTab(tabId) {
                    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
                    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
                    document.getElementById('tab-' + tabId).classList.add('active');
                    event.target.classList.add('active');
                }
                </script>

                <div class="card" style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">🏷️ Gestion des Tags globaux</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">Ajoutez ou supprimez des tags de la liste globale. Ces tags seront sélectionnables lors de la modification de vos projets.</p>
                    
                    <form method="POST" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; max-width: 500px;">
                        <?php echo csrf_field(); ?>
                        <input type="text" name="new_tag" placeholder="Nouveau tag (ex: 3D, Illustration)" required style="flex: 1;">
                        <button type="submit" name="add_global_tag" class="btn btn-primary">Ajouter</button>
                    </form>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <?php foreach ($data['tagsDisponibles'] as $tag): ?>
                            <div class="tag" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 20px;">
                                <span><?php echo htmlspecialchars($tag); ?></span>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer le tag &quot;<?php echo htmlspecialchars($tag); ?>&quot; de la liste globale ?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="tag_to_delete" value="<?php echo htmlspecialchars($tag); ?>">
                                    <button type="submit" name="delete_global_tag" style="background: none; border: none; color: var(--danger-color); cursor: pointer; font-weight: bold; font-size: 1rem; padding: 0 0.2rem;" title="Supprimer de la liste globale">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <form id="project-form" action="edit_projects.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $project_to_edit['id']; ?>">
                    
                    <div class="card" style="margin-bottom: 2rem;">
                        <h3>Informations Principales</h3>
                        <div class="form-row" style="display:flex; gap:1.5rem; margin-top: 1rem;">
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Titre</label>
                                <input type="text" name="titre" value="<?php echo htmlspecialchars($project_to_edit['titre']); ?>" required>
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Identifiant URL (projetId)</label>
                                <input type="text" name="projetId" value="<?php echo htmlspecialchars($project_to_edit['projetId']); ?>" placeholder="ex: mon-projet" required>
                                <small style="color: var(--text-muted);">Sera auto-slugifié (lettres minuscules, chiffres, tirets)</small>
                            </div>
                        </div>

                        <div class="form-row" style="display:flex; gap:1.5rem; margin-top: 1rem;">
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Catégorie du Projet</label>
                                <select name="categorie" required>
                                    <option value="universitaire" <?php echo (isset($project_to_edit['categorie']) && $project_to_edit['categorie'] === 'universitaire') ? 'selected' : ''; ?>>🎓 Projet Universitaire</option>
                                    <option value="pro" <?php echo (isset($project_to_edit['categorie']) && $project_to_edit['categorie'] === 'pro') ? 'selected' : ''; ?>>💼 Projet Professionnel</option>
                                    <option value="perso" <?php echo (!isset($project_to_edit['categorie']) || $project_to_edit['categorie'] === 'perso') ? 'selected' : ''; ?>>🎨 Projet Personnel</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Statut</label>
                                <select name="status" required>
                                    <option value="published" <?php echo ((!isset($project_to_edit['status'])) || $project_to_edit['status'] === 'published') ? 'selected' : ''; ?>>✅ Publié (Visible)</option>
                                    <option value="draft" <?php echo ((isset($project_to_edit['status'])) && $project_to_edit['status'] === 'draft') ? 'selected' : ''; ?>>📝 Brouillon (Caché)</option>
                                    <option value="archived" <?php echo ((isset($project_to_edit['status'])) && $project_to_edit['status'] === 'archived') ? 'selected' : ''; ?>>🗄️ Archivé (Caché)</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Score d'Importance (Poids)</label>
                                <select name="importance" required>
                                    <?php 
                                    $current_importance = isset($project_to_edit['importance']) ? (int)$project_to_edit['importance'] : 5;
                                    for ($i = 10; $i >= 1; $i--): 
                                        $selected = ($current_importance === $i) ? 'selected' : '';
                                        echo "<option value=\"$i\" $selected>⭐ $i/10 " . ($i >= 8 ? '(Très important)' : ($i >= 5 ? '(Moyen)' : '(Faible)')) . "</option>";
                                    endfor; 
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 1rem;">
                            <label>Sous-titre / En-tête</label>
                            <input type="text" name="sousTitre" value="<?php echo htmlspecialchars($project_to_edit['sousTitre']); ?>" placeholder="Phrase d'accroche">
                        </div>

                        <div class="form-row" style="display:flex; gap:1.5rem;">
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Date</label>
                                <input type="text" name="date" value="<?php echo htmlspecialchars($project_to_edit['date']); ?>" placeholder="ex: Janvier 2026">
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Rôle</label>
                                <input type="text" name="role" value="<?php echo htmlspecialchars($project_to_edit['role']); ?>" placeholder="ex: Développeur Front-end">
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Année (pour les filtres & tris)</label>
                                <input type="text" name="annee" value="<?php echo htmlspecialchars($project_to_edit['annee'] ?? ''); ?>" placeholder="ex: 2026" required>
                            </div>
                        </div>

                        <div class="form-row" style="display:flex; gap:1.5rem; margin-top: 1rem;">
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Technologies & Outils</label>
                                <input type="text" name="technos" id="technos-input" value="<?php echo htmlspecialchars($project_to_edit['technos'] ?? ''); ?>" placeholder="ex: React, Node.js">
                                <div class="software-picker" style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem; background: rgba(0, 0, 0, 0.2); padding: 0.8rem; border-radius: 8px; border: 1px solid var(--border-color); max-height: 180px; overflow-y: auto;">
                                    <?php
                                    $current_technos = array_map('trim', explode(',', $project_to_edit['technos'] ?? ''));
                                    foreach (($data['logos'] ?? []) as $logo):
                                        $logo_title = $logo['titre'];
                                        $logo_path = ASSETS_BASE_URL . $logo['chemin'];
                                        $is_selected = in_array($logo_title, $current_technos);
                                    ?>
                                        <div class="software-badge" 
                                             data-name="<?php echo htmlspecialchars($logo_title); ?>"
                                             style="display: flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.6rem; border-radius: 20px; 
                                                    background: <?php echo $is_selected ? 'rgba(var(--accent-rgb), 0.2)' : 'rgba(255,255,255,0.05)'; ?>; 
                                                    border: 1px solid <?php echo $is_selected ? 'var(--accent-color)' : 'rgba(255,255,255,0.1)'; ?>; 
                                                    color: <?php echo $is_selected ? 'var(--accent-color)' : 'var(--text-color)'; ?>; 
                                                    cursor: pointer; user-select: none; font-size: 0.85rem; transition: all 0.2s;"
                                             onclick="toggleSoftware(this)">
                                            <img src="<?php echo htmlspecialchars($logo_path); ?>" style="width: 16px; height: 16px; object-fit: contain;" alt="">
                                            <span><?php echo htmlspecialchars($logo_title); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Tags du Projet (Sélectionnez les tags applicables)</label>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 0.5rem; background: rgba(0, 0, 0, 0.4); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); max-height: 150px; overflow-y: auto;">
                                    <?php 
                                    $project_tags = $project_to_edit['tags'] ?? [];
                                    foreach ($data['tagsDisponibles'] as $tag): 
                                        $checked = in_array($tag, $project_tags) ? 'checked' : '';
                                    ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-color); font-weight: normal; margin-bottom: 0; font-size: 0.85rem;">
                                            <input type="checkbox" name="selected_tags[]" value="<?php echo htmlspecialchars($tag); ?>" <?php echo $checked; ?> style="width: auto; height: auto; -webkit-appearance: checkbox; appearance: checkbox;">
                                            <?php echo htmlspecialchars($tag); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <small style="color: var(--text-muted); display: block; margin-top: 5px;">Les tags disponibles se gèrent sur la page principale des projets.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 2rem;">
                        <h3>Médias & URL</h3>
                        <div class="form-row" style="display:flex; gap:1.5rem; margin-top: 1rem;">
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Image de couverture (Chemin)</label>
                                <div style="display: flex; gap: 0.5rem; align-items: center; flex: 1;">
                                    <input type="text" name="image" value="<?php echo htmlspecialchars($project_to_edit['image']); ?>" placeholder="ex: images/p1/cover.webp" required style="flex: 1;" id="input-cover">
                                    <button type="button" class="btn btn-secondary" onclick="openMediaModal(document.getElementById('input-cover'), '<?php echo $project_to_edit['id'] !== -1 ? $project_to_edit['id'] : ($next_id ?? ''); ?>')" style="padding: 0.6rem; border-radius: 6px;" title="Choisir/Uploader depuis la médiathèque" aria-label="Ouvrir la médiathèque">🖼️</button>
                                    <?php if ($project_to_edit['image']): ?>
                                        <img src="<?php echo ASSETS_BASE_URL . htmlspecialchars($project_to_edit['image']); ?>" class="preview-img" style="width: 46px; height: 46px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);" alt="Preview">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Texte alternatif (SEO)</label>
                                <input type="text" name="alt" value="<?php echo htmlspecialchars($project_to_edit['alt'] ?? ''); ?>" placeholder="Description de l'image">
                            </div>
                        </div>

                        <div class="form-row" style="display:flex; gap:1.5rem;">
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Lien du site (URL)</label>
                                <?php 
                                    $site_url = is_array($project_to_edit['site']) ? ($project_to_edit['site']['url'] ?? '') : '';
                                    $site_label = is_array($project_to_edit['site']) ? ($project_to_edit['site']['label'] ?? '') : '';
                                ?>
                                <input type="text" name="site_url" value="<?php echo htmlspecialchars($site_url); ?>" placeholder="https://...">
                            </div>
                            <div class="form-group" style="flex:1; margin-bottom:0;">
                                <label>Texte du bouton (url)</label>
                                <input type="text" name="site_label" value="<?php echo htmlspecialchars($site_label); ?>" placeholder="ex: Voir le site">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Vidéo YouTube (ID optionnel)</label>
                            <input type="text" name="youtubeId" value="<?php echo htmlspecialchars($project_to_edit['youtubeId'] ?? ''); ?>" placeholder="ex: dQw4w9WgXcQ">
                        </div>
                        <input type="hidden" name="iframe" value="<?php echo htmlspecialchars($project_to_edit['iframe'] ?? ''); ?>">
                    </div>

                    <!-- Analyse de Perf -->
                    <div style="margin-bottom: 2rem;">
                        <div class="card">
                            <h3>⚡ Analyse de Performance Globale</h3>
                            <div id="perf-metrics" style="margin-top: 1rem;">
                                <div id="perf-results" style="font-size: 0.9rem;">
                                    <p style="color: var(--text-muted); font-style: italic;">En attente d'une image de couverture...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Structure du Dossier Académique (Blocs Composés) -->
                    <div class="card" style="margin-bottom: 2rem; border: 1px solid rgba(88, 166, 255, 0.2);">
                        <h3 style="color: #58a6ff; display: flex; align-items: center; gap: 10px;">📚 Dossier Académique (Blocs Composés)</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                            Créez un dossier complet de projet universitaire en combinant librement textes, consignes de l'enseignant, captures d'écrans et présentations d'images côte à côte.
                        </p>

                        <!-- Sélecteur d'ajout de bloc -->
                        <div style="background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.8rem; align-items: center;">
                                <span style="font-size: 0.85rem; font-weight: bold; color: var(--text-muted);">📝 STRUCTURE :</span>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('text')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Texte Simple</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('consigne')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Consigne (Encadré)</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('brief')" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: rgba(1,180,241,0.5);">📋 Le Brief</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('concept')" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: rgba(245,158,11,0.5);">💡 Concept</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('defis')" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: rgba(16,185,129,0.5);">⚙️ Défis</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('resultats')" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: rgba(139,92,246,0.5);">🏆 Résultats</button>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.8rem; align-items: center;">
                                <span style="font-size: 0.85rem; font-weight: bold; color: var(--text-muted);">🖼️ MÉDIAS :</span>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('text_image')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Texte G. / Image D.</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('image_text')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Image G. / Texte D.</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('full_image')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Image Large</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('two_images')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">2 Images</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('code')" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Code & Explication</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('mood_board')" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: rgba(236, 72, 153, 0.5);">🎨 Mood Board</button>
                                <button type="button" class="btn btn-secondary" onclick="addDossierBlock('before_after')" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: rgba(6, 182, 212, 0.5);">↔️ Avant / Après</button>
                            </div>
                        </div>

                        <!-- Conteneur des blocs -->
                        <div id="dossier-blocks-container" class="dossier-container">
                            <!-- Les blocs dynamiques seront insérés ici en Javascript -->
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 2rem; border: 1px solid rgba(var(--accent-rgb), 0.3);">
                        <h3 style="color: var(--accent-color); margin-bottom: 1.5rem;">🔍 Optimisation SEO</h3>
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <label style="margin-bottom: 0;">Titre SEO (Balise Title)</label>
                                <button type="button" class="btn btn-secondary btn-ai-magic" onclick="magicWand(this, 'seo_title')" style="padding: 4px 10px; font-size: 0.75rem;">🪄 Magic SEO</button>
                            </div>
                            <input type="text" name="seo_title" value="<?php echo htmlspecialchars($project_to_edit['seo_title'] ?? ''); ?>" placeholder="Le titre qui apparaîtra sur Google...">
                        </div>
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <label style="margin-bottom: 0;">Méta Description</label>
                                <button type="button" class="btn btn-secondary btn-ai-magic" onclick="magicWand(this, 'seo_description')" style="padding: 4px 10px; font-size: 0.75rem;">🪄 Magic Meta</button>
                            </div>
                            <textarea name="seo_description" rows="2" placeholder="Le petit résumé sous le titre dans les résultats Google..."><?php echo htmlspecialchars($project_to_edit['seo_description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(29, 161, 242, 0.05), rgba(10, 102, 194, 0.05));">
                        <h3 style="margin-bottom: 1.5rem;">📱 Partage sur les Réseaux</h3>
                        <div class="social-generator-ui" style="background: rgba(255,255,255,0.03); border-radius: 12px; padding: 1.5rem;">
                            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">Générez un post prêt à l'emploi pour vos réseaux preferés :</p>
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
                                <button type="button" class="btn btn-secondary" onclick="generateSocial('LinkedIn')" style="flex: 1;">LinkedIn</button>
                                <button type="button" class="btn btn-secondary" onclick="generateSocial('Twitter')" style="flex: 1;">Twitter / X</button>
                                <button type="button" class="btn btn-secondary" onclick="generateSocial('Instagram')" style="flex: 1;">Instagram</button>
                            </div>

                            <div id="social-result-box" style="display: none;">
                                <label style="font-size: 0.8rem; color: var(--accent-color); font-weight: bold;">Post généré (<span id="social-platform-name"></span>)</label>
                                <textarea id="social-content" rows="6" style="margin-top: 5px; background: rgba(0,0,0,0.2); font-family: inherit; font-size: 0.9rem;"></textarea>
                                <div style="display: flex; gap: 10px; margin-top: 10px;">
                                    <button type="button" class="btn btn-primary" onclick="copyAndOpenSocial()" style="flex: 2;">📋 Copier & Publier</button>
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('social-result-box').style.display='none'" style="flex: 1;">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3>Galerie Multimédia</h3>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" class="btn btn-secondary" onclick="addGalleryItem()">🖼️ + Image</button>
                                <button type="button" class="btn btn-secondary" onclick="addGalleryVideo()">🎬 + Vidéo ID</button>
                            </div>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;">Glissez et déposez les lignes pour modifier l'ordre des images.</p>
                        
                        <div class="gallery-container" id="gallery-container">
                            <?php foreach ($project_to_edit['galerie'] as $i => $img): 
                                $media_type = $img[2] ?? 'image';
                                $is_video = ($media_type === 'youtube');
                            ?>
                                <div class="drag-item gallery-item <?php echo $is_video ? 'type-video' : ''; ?>" draggable="true">
                                    <div style="cursor: grab; color: #8b949e; padding: 0 0.5rem;" aria-label="Déplacer">☰</div>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <button type="button" class="btn-ctrl" onclick="moveUp(this)" style="padding: 2px 6px;" aria-label="Monter">↑</button>
                                        <button type="button" class="btn-ctrl" onclick="moveDown(this)" style="padding: 2px 6px;" aria-label="Descendre">↓</button>
                                    </div>
                                    
                                    <?php if ($is_video): ?>
                                        <div class="preview-img video-placeholder" style="display: flex; align-items: center; justify-content: center; background: #000; color: #ff0000; font-size: 1.5rem; border-radius: 6px;">▶️</div>
                                    <?php else: ?>
                                        <img src="<?php echo ASSETS_BASE_URL . htmlspecialchars($img[0]); ?>" class="preview-img" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'><rect width=\'100%\' height=\'100%\' fill=\'%23333\'/></svg>'" alt="Gallery image">
                                    <?php endif; ?>

                                    <div class="gallery-item-inputs">
                                        <input type="hidden" name="galerie_type[]" value="<?php echo htmlspecialchars($media_type); ?>">
                                        <div style="display: flex; gap: 0.2rem; flex: 1;">
                                            <input type="text" name="galerie_path[]" value="<?php echo htmlspecialchars($img[0]); ?>" placeholder="<?php echo $is_video ? 'ID YouTube (ex: SlMKf02ZsMU)' : 'Chemin (ex: images/p1/01.webp)'; ?>">
                                            <?php if (!$is_video): ?>
                                                <button type="button" class="btn btn-secondary" onclick="openMediaModal(this.previousElementSibling, '<?php echo $project_to_edit['id'] !== -1 ? $project_to_edit['id'] : ($next_id ?? ''); ?>')" style="padding: 0.6rem;" title="Médiathèque" aria-label="Ouvrir la médiathèque">🖼️</button>
                                            <?php endif; ?>
                                        </div>
                                        <input type="text" name="galerie_caption[]" value="<?php echo htmlspecialchars($img[1]); ?>" placeholder="Légende" style="flex: 1;">
                                    </div>
                                    <button type="button" class="btn-remove" onclick="this.closest('.gallery-item').remove(); updatePerformance();" aria-label="Supprimer">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="glass-panel" style="padding: 1.5rem; display: flex; gap: 1rem; justify-content: space-between; position: sticky; bottom: 20px; z-index: 100;">
                        <div>
                            <button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">Enregistrer le projet</button>
                        </div>
                        <?php if ($project_to_edit['id'] !== -1): ?>
                            <button type="submit" name="delete_project" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce projet ? Cette action est irréversible.');">Supprimer ce projet</button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>

            <?php require_once 'footer.php'; ?>
        </main>
    </div>

    <?php require_once 'media_modal.php'; ?>
    
    <!-- Modale d'import -->
    <div id="import-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="width: 90%; max-width: 600px; position: relative;">
            <button onclick="document.getElementById('import-modal').style.display='none'" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: white; cursor: pointer; font-size: 1.5rem;">&times;</button>
            <h3>📥 Importer un projet</h3>
            
            <div style="margin-top: 1.5rem;">
                <h4>Option 1 : Importer un fichier ZIP</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Uploadez un fichier ZIP exporté depuis ce panel pour restaurer le JSON et les images.</p>
                <form action="import_project.php" method="POST" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: center;">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="import_zip" accept=".zip" required style="flex: 1;">
                    <button type="submit" class="btn btn-primary">Importer le ZIP</button>
                </form>
            </div>
            
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 2rem 0;">
            
            <div>
                <h4>Option 2 : Coller le code JSON</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Collez le code JSON brut d'un projet. Les images associées devront être uploadées manuellement si elles n'existent pas.</p>
                <form action="import_project.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <textarea name="import_json" rows="6" style="width: 100%; margin-bottom: 1rem; font-family: monospace; font-size: 0.85rem;" placeholder='{"projetId": "mon-projet", ...}' required></textarea>
                    <button type="submit" class="btn btn-primary">Importer le JSON</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/dragdrop.js"></script>
    <script src="js/admin.js"></script>
    <script>
        // Init drag-and-drop for gallery
        <?php if ($project_to_edit): ?>
        const galleryDD = initDragDrop('gallery-container');
        <?php endif; ?>

        // Init drag-and-drop for project reordering
        <?php if (!$project_to_edit): ?>
        const grids = ['projects-grid-published', 'projects-grid-draft', 'projects-grid-archived'];
        grids.forEach(id => {
            const grid = document.getElementById(id);
            if (grid) {
                initDragDropGrid(id);
                grid.addEventListener('dragend', () => {
                    document.getElementById('save-order-btn').style.display = '';
                    const ids = [];
                    grids.forEach(gid => {
                        const g = document.getElementById(gid);
                        if(g) {
                            [...g.querySelectorAll('.drag-item')].forEach(el => ids.push(el.dataset.id));
                        }
                    });
                    document.getElementById('project-order').value = JSON.stringify(ids);
                });
            }
        });
        <?php endif; ?>

                function addGalleryItem() {
            const container = document.getElementById('gallery-container');
            const div = document.createElement('div');
            div.className = 'drag-item gallery-item';
            div.draggable = true;
            div.innerHTML = `
                <div style="cursor: grab; color: #8b949e; padding: 0 0.5rem;" aria-label="Déplacer">☰</div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" style="padding: 2px 6px;" aria-label="Monter">↑</button>
                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" style="padding: 2px 6px;" aria-label="Descendre">↓</button>
                </div>
                <div style="width: 60px; height: 40px; background: #333; border-radius: 4px;"></div>
                <div class="gallery-item-inputs">
                    <input type="hidden" name="galerie_type[]" value="image">
                    <div style="display: flex; gap: 0.2rem; flex: 1;">
                        <input type="text" name="galerie_path[]" placeholder="images/p/img.webp">
                        <button type="button" class="btn btn-secondary" onclick="openMediaModal(this.previousElementSibling, '<?php echo $project_to_edit ? ($project_to_edit['id'] !== -1 ? $project_to_edit['id'] : ($next_id ?? '')) : ''; ?>')" style="padding: 0.6rem;" title="Médiathèque" aria-label="Ouvrir la médiathèque">🖼️</button>
                    </div>
                    <input type="text" name="galerie_caption[]" placeholder="Légende" style="flex: 1;">
                </div>
                <button type="button" class="btn-remove" onclick="this.closest('.gallery-item').remove(); updatePerformance();" aria-label="Supprimer">×</button>
            `;
            container.appendChild(div);
            if (typeof galleryDD !== 'undefined') galleryDD.bindEvents(div);
        }

        function addGalleryVideo() {
            const container = document.getElementById('gallery-container');
            const div = document.createElement('div');
            div.className = 'drag-item gallery-item type-video';
            div.draggable = true;
            div.innerHTML = `
                <div style="cursor: grab; color: #8b949e; padding: 0 0.5rem;" aria-label="Déplacer">☰</div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" style="padding: 2px 6px;" aria-label="Monter">↑</button>
                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" style="padding: 2px 6px;" aria-label="Descendre">↓</button>
                </div>
                <div class="preview-img video-placeholder" style="display: flex; align-items: center; justify-content: center; background: #000; color: #ff0000; font-size: 1.5rem; border-radius: 6px;">▶️</div>
                <div class="gallery-item-inputs">
                    <input type="hidden" name="galerie_type[]" value="youtube">
                    <div style="display: flex; gap: 0.2rem; flex: 1;">
                        <input type="text" name="galerie_path[]" placeholder="ID YouTube (ex: dQw4w9WgXcQ)">
                    </div>
                    <input type="text" name="galerie_caption[]" placeholder="Titre de la vidéo" style="flex: 1;">
                </div>
                <button type="button" class="btn-remove" onclick="this.closest('.gallery-item').remove(); updatePerformance();" aria-label="Supprimer">×</button>
            `;
            container.appendChild(div);
            if (typeof galleryDD !== 'undefined') galleryDD.bindEvents(div);
        }
        const inputCover = document.getElementById('input-cover');
        const perfResults = document.getElementById('perf-results');

        const updatePerformance = async () => {
            const coverPath = inputCover.value.trim();
            const galleryPaths = Array.from(document.querySelectorAll('input[name="galerie_path[]"]')).map(i => i.value.trim());
            const allPaths = [coverPath, ...galleryPaths].filter(p => p !== '');
            
            if (allPaths.length === 0) {
                perfResults.innerHTML = '<p style="color: var(--text-muted); font-style: italic;">En attente de médias...</p>';
                return;
            }

            perfResults.innerHTML = '<span style="color:var(--text-muted)">Analyse globale...</span>';
            
            try {
                const res = await fetch(`api_media.php`);
                const allMedia = await res.json();
                
                let totalSize = 0;
                let webpCount = 0;
                let missingCount = 0;
                let foundPaths = [];

                allPaths.forEach(path => {
                    const normPath = path.replace(/^\//, '').replace(/\\/g, '/');
                    const file = allMedia.find(m => {
                        const mPath = m.path.replace(/^\//, '').replace(/\\/g, '/');
                        return mPath === normPath || mPath === 'images/' + normPath;
                    });

                    if (file) {
                        totalSize += file.size;
                        if (file.path.endsWith('.webp')) webpCount++;
                        foundPaths.push(file);
                    } else {
                        missingCount++;
                    }
                });

                const totalMb = (totalSize / (1024 * 1024)).toFixed(2);
                const avgSizeKb = foundPaths.length > 0 ? Math.round((totalSize / foundPaths.length) / 1024) : 0;
                
                const statusColor = (totalMb > 5) ? 'var(--danger-color)' : (totalMb > 2 ? 'var(--warning-color)' : 'var(--success-color)');
                const statusLabel = (totalMb > 5) ? '⚠️ TRÈS LOURD' : (totalMb > 2 ? '⚡ MOYEN' : '✅ LÉGER');

                perfResults.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div style="background: rgba(0,0,0,0.1); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.6;">Poids Total</div>
                            <div style="font-size: 1.3rem; font-weight: 800; color: ${statusColor}">${totalMb} MB</div>
                            <div style="font-size: 0.7rem; font-weight: bold; margin-top: 4px;">${statusLabel}</div>
                        </div>
                        <div style="background: rgba(0,0,0,0.1); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.6;">Optimisation</div>
                            <div style="font-size: 1.3rem; font-weight: 800;">${Math.round((webpCount / allPaths.length) * 100) || 0}%</div>
                            <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 4px;">${webpCount}/${allPaths.length} WebP (${allPaths.length} images)</div>
                        </div>
                    </div>
                    
                    <div style="font-size: 0.8rem; line-height: 1.4;">
                        <div style="margin-bottom: 5px;">📍 <strong>Moyenne par image :</strong> ${avgSizeKb} KB</div>
                        ${missingCount > 0 ? `<div style="color: var(--warning-color); margin-bottom: 5px;">❓ <strong>${missingCount} fichier(s)</strong> non trouvés (en attente d'upload ?)</div>` : ''}
                        <div style="background: rgba(var(--accent-rgb), 0.1); padding: 8px; border-radius: 4px; margin-top: 10px; border-left: 2px solid var(--accent-color);">
                            ${totalMb > 3 ? '💡 Conseil : Réduisez le nombre d\'images ou utilisez des WebP plus compressés.' : '✨ Ton projet est parfaitement optimisé pour le web !'}
                        </div>
                    </div>
                `;
            } catch(e) {
                perfResults.innerHTML = 'Erreur lors du calcul global.';
                console.error(e);
            }
        };

        // Ecouter les changements sur la couverture et la galerie
        if (inputCover) {
            inputCover.addEventListener('input', updatePerformance);
        }

        // Délégation d'événements pour les inputs de la galerie (car ils peuvent être ajoutés dynamiquement)
        document.addEventListener('input', (e) => {
            if (e.target && e.target.name === 'galerie_path[]') {
                updatePerformance();
            }
        });

        // Trigger initial
        if (inputCover || document.querySelector('input[name="galerie_path[]"]')) {
            setTimeout(updatePerformance, 600);
        }

        async function magicWand(btn, field) {
            const container = btn.closest('.form-group');
            const target = container.querySelector('textarea, input[type="text"]');
            
            // On récupère TOUT le contexte possible du formulaire pour aider l'IA
            const titre = document.querySelector('input[name="titre"]')?.value || '';
            const sousTitre = document.querySelector('input[name="sousTitre"]')?.value || '';
            const concept = document.querySelector('textarea[name="concept"]')?.value || '';
            const brief = document.querySelector('textarea[name="brief"]')?.value || '';
            const tags = Array.from(document.querySelectorAll('input[name="selected_tags[]"]:checked')).map(el => el.value).join(', ');
            const technos = document.querySelector('input[name="technos"]')?.value || '';
            
            let context = `Projet: ${titre}. Sous-titre: ${sousTitre}. Tags: ${tags}. Technos: ${technos}. 
                           Contenu actuel du champ: ${target.value.trim()}.
                           Consigne/Brief: ${brief}.
                           Concept/Recherche: ${concept}`;

            const originalBtnText = btn.innerHTML;
            btn.innerHTML = '✨ Génération...';
            btn.disabled = true;

            try {
                const response = await fetch(`api_ai.php?action=generate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ field: field, prompt: context })
                });
                const data = await response.json();

                if (data.status === 'success') {
                    let cleanText = data.generated_text.replace(/[\*#_]/g, '');
                    target.value = cleanText;
                    target.style.border = "1px solid var(--success-color)";
                    setTimeout(() => { target.style.border = ""; }, 2000);
                } else {
                    let detailMsg = '';
                    if (data.details && data.details.error && data.details.error.message) {
                        detailMsg = '\n\nDétails : ' + data.details.error.message;
                    }
                    alert("Erreur : " + data.message + detailMsg);
                }
            } catch (e) {
                console.error(e);
                alert("Erreur de connexion avec l'IA.");
            } finally {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            }
        }

        async function magicWandDossier(btn, type, isTitle = false) {
            const container = btn.closest('.form-group');
            const target = container.querySelector('textarea, input[type="text"]');
            if (!target) return;
            
            // On récupère le contexte du projet principal pour aider l'IA
            const titre = document.querySelector('input[name="titre"]')?.value || '';
            const sousTitre = document.querySelector('input[name="sousTitre"]')?.value || '';
            const tags = Array.from(document.querySelectorAll('input[name="selected_tags[]"]:checked')).map(el => el.value).join(', ');
            const technos = document.querySelector('input[name="technos"]')?.value || '';
            
            let context = `Projet: ${titre}. Sous-titre: ${sousTitre}. Tags: ${tags}. Technos: ${technos}.`;
            
            // S'il y a un titre de section dans ce bloc (lorsqu'on génère la description)
            if (!isTitle) {
                const sectionTitre = container.closest('.dossier-item').querySelector('input[name="dossier_titre[]"]')?.value || '';
                if (sectionTitre) {
                    context += ` Section: ${sectionTitre}.`;
                }
            }
            
            if (target.value.trim()) {
                context += ` Base actuelle du champ: ${target.value.trim()}`;
            }

            const originalBtnText = btn.innerHTML;
            btn.innerHTML = '✨...';
            btn.disabled = true;

            // Déterminer la consigne spécifique selon qu'on veut un titre ou un texte
            let aiField = type;
            let finalPrompt = context;
            if (isTitle) {
                // Pour le titre, on demande à l'IA d'être très concise
                aiField = 'seo_title'; // On utilise un champ court
                finalPrompt = `CONSIGNE : Génère UNIQUEMENT un titre de section extrêmement court (max 4-5 mots) en français, pertinent pour le bloc de type "${type}". Context du projet : ${context}`;
            }

            try {
                const response = await fetch(`api_ai.php?action=generate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ field: aiField, prompt: finalPrompt })
                });
                const data = await response.json();

                if (data.status === 'success') {
                    let cleanText = data.generated_text.replace(/[\*#_]/g, '');
                    target.value = cleanText;
                    target.style.border = "1px solid var(--success-color)";
                    setTimeout(() => { target.style.border = ""; }, 2000);
                } else {
                    let detailMsg = '';
                    if (data.details && data.details.error && data.details.error.message) {
                        detailMsg = '\n\nDétails : ' + data.details.error.message;
                    }
                    alert("Erreur : " + data.message + detailMsg);
                }
            } catch (e) {
                console.error(e);
                alert("Erreur de connexion avec l'IA.");
            } finally {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            }
        }

        function toggleSoftware(badge) {
            const name = badge.dataset.name;
            const input = document.getElementById('technos-input');
            if (!input) return;
            let currentVal = input.value.trim();
            
            // Parse current values (split by comma, trim, filter empty)
            let technos = currentVal ? currentVal.split(',').map(s => s.trim()).filter(s => s !== '') : [];
            
            const index = technos.indexOf(name);
            if (index > -1) {
                // Retirer le logiciel
                technos.splice(index, 1);
                badge.style.background = 'rgba(255,255,255,0.05)';
                badge.style.borderColor = 'rgba(255,255,255,0.1)';
                badge.style.color = 'var(--text-color)';
            } else {
                // Ajouter le logiciel
                technos.push(name);
                badge.style.background = 'rgba(var(--accent-rgb), 0.2)';
                badge.style.borderColor = 'var(--accent-color)';
                badge.style.color = 'var(--accent-color)';
            }
            
            input.value = technos.join(', ');
        }

        // Écouter les changements sur le champ technos pour mettre à jour les badges en temps réel
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('technos-input');
            if (input) {
                input.addEventListener('input', (e) => {
                    const currentVal = e.target.value.trim();
                    const technos = currentVal ? currentVal.split(',').map(s => s.trim()) : [];
                    
                    document.querySelectorAll('.software-badge').forEach(badge => {
                        const name = badge.dataset.name;
                        if (technos.includes(name)) {
                            badge.style.background = 'rgba(var(--accent-rgb), 0.2)';
                            badge.style.borderColor = 'var(--accent-color)';
                            badge.style.color = 'var(--accent-color)';
                        } else {
                            badge.style.background = 'rgba(255,255,255,0.05)';
                            badge.style.borderColor = 'rgba(255,255,255,0.1)';
                            badge.style.color = 'var(--text-color)';
                        }
                    });
                });
            }
        });

        let currentSocialPlatform = '';

        async function generateSocial(platform) {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '✨...';
            btn.disabled = true;

            const titre = document.querySelector('input[name="titre"]').value;
            const concept = document.querySelector('textarea[name="concept"]').value;
            const context = `Titre: ${titre}. Concept: ${concept}`;

            try {
                const response = await fetch(`api_ai.php?action=generate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ field: 'social_post', prompt: context, platform: platform })
                });
                const data = await response.json();

                if (data.status === 'success') {
                    document.getElementById('social-result-box').style.display = 'block';
                    document.getElementById('social-platform-name').textContent = platform;
                    document.getElementById('social-content').value = data.generated_text;
                    currentSocialPlatform = platform;
                } else {
                    alert("Erreur : " + data.message);
                }
            } catch (e) {
                alert("Erreur de connexion.");
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function copyAndOpenSocial() {
            const content = document.getElementById('social-content').value;
            navigator.clipboard.writeText(content).then(() => {
                let url = "";
                if (currentSocialPlatform === 'LinkedIn') url = "https://www.linkedin.com/sharing/share-offsite/";
                if (currentSocialPlatform === 'Twitter') url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(content)}`;
                if (currentSocialPlatform === 'Instagram') url = "https://www.instagram.com/";
                
                alert("Texte copié ! Je t'ouvre " + currentSocialPlatform);
                window.open(url, '_blank');
            });
        }

        // --- GESTION DU DOSSIER COMPOSÉ DYNAMIQUE ---
        let dossierBlockCounter = 0;

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

        function getBlockColor(type) {
            switch(type) {
                case 'brief': return '#01b4f1';
                case 'concept': return '#f59e0b';
                case 'defis': return '#10b981';
                case 'resultats': return '#8b5cf6';
                case 'text': return '#58a6ff';
                case 'consigne': return '#ff7b72';
                case 'text_image': return '#f59e0b';
                case 'image_text': return '#10b981';
                case 'full_image': return '#8b5cf6';
                case 'two_images': return '#ec4899';
                case 'code': return '#06b6d4';
                case 'mood_board': return '#ec4899';
                case 'before_after': return '#06b6d4';
                default: return '#8b949e';
            }
        }

        function getBlockTitle(type) {
            switch(type) {
                case 'brief': return 'La Consigne & Le Brief';
                case 'concept': return 'Recherche & Concept';
                case 'defis': return 'Essais & Choix Techniques';
                case 'resultats': return 'Bilan & Compétences';
                case 'text': return 'Bloc Texte Simple';
                case 'consigne': return 'Consigne Académique (Encadré)';
                case 'text_image': return 'Texte à gauche / Image à droite';
                case 'image_text': return 'Image à gauche / Texte à droite';
                case 'full_image': return 'Image Pleine Largeur / Capture';
                case 'two_images': return 'Deux Images côte à côte';
                case 'code': return 'Code & Explication';
                case 'mood_board': return 'Mood Board (Bento Grid)';
                case 'before_after': return 'Slider Avant / Après';
                default: return 'Bloc Composé';
            }
        }

        function getBlockIcon(type) {
            switch(type) {
                case 'brief': return '📋';
                case 'concept': return '💡';
                case 'defis': return '⚙️';
                case 'resultats': return '🏆';
                case 'text': return '📝';
                case 'consigne': return '📋';
                case 'text_image': return '📖';
                case 'image_text': return '📖';
                case 'full_image': return '🖼️';
                case 'two_images': return '👥';
                case 'code': return '💻';
                case 'mood_board': return '🎨';
                case 'before_after': return '↔️';
                default: return '📦';
            }
        }

        function addDossierBlock(type, data = {}) {
            dossierBlockCounter++;
            const currentBlockIndex = dossierBlockCounter;
            const container = document.getElementById('dossier-blocks-container');
            if (!container) return;

            const div = document.createElement('div');
            div.className = 'drag-item dossier-item card';
            div.draggable = true;
            div.style.borderLeft = '5px solid ' + getBlockColor(type);
            div.style.marginBottom = '1.5rem';
            div.style.background = 'rgba(255, 255, 255, 0.02)';
            div.style.position = 'relative';
            div.style.padding = '1.5rem';
            div.style.borderRadius = '12px';
            div.dataset.id = currentBlockIndex;

            const blockTitle = getBlockTitle(type);
            const blockIcon = getBlockIcon(type);
            const projectId = '<?php echo $project_to_edit ? ($project_to_edit['id'] !== -1 ? $project_to_edit['id'] : ($next_id ?? '')) : ''; ?>';

            let fieldsHtml = '';

            // Hidden block type
            fieldsHtml += `<input type="hidden" name="dossier_type[]" value="${type}">`;

            // Block Title & Text
            if (type !== 'full_image' && type !== 'two_images') {
                let defaultTitle = data.titre || '';
                if (!defaultTitle) {
                    if (type === 'brief') defaultTitle = 'La Consigne & Le Brief';
                    if (type === 'concept') defaultTitle = 'Recherche & Concept';
                    if (type === 'defis') defaultTitle = 'Essais & Choix Techniques';
                    if (type === 'resultats') defaultTitle = 'Bilan & Compétences';
                }
                const placeholderTitle = type === 'consigne' ? "Consigne de l'exercice" : "Titre de cette section...";
                fieldsHtml += `
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label style="margin-bottom: 0;">Titre de la section</label>
                            <button type="button" class="btn btn-secondary btn-ai-magic" onclick="magicWandDossier(this, '${type}', true)" style="padding: 4px 10px; font-size: 0.75rem;">🪄 Magic IA</button>
                        </div>
                        <input type="text" name="dossier_titre[]" value="${escapeHtml(defaultTitle)}" placeholder="${placeholderTitle}">
                    </div>
                `;
            } else {
                fieldsHtml += `<input type="hidden" name="dossier_titre[]" value="">`;
            }

            if (type !== 'full_image' && type !== 'two_images') {
                const textVal = data.texte || '';
                let placeholderText = "Votre texte descriptif ici...";
                if (type === 'consigne') placeholderText = "Consignes pédagogiques / directives de l'exercice...";
                if (type === 'code') placeholderText = "Explication du script...";
                if (type === 'brief') placeholderText = "Expliquez la consigne reçue, les objectifs et contraintes...";
                if (type === 'concept') placeholderText = "Expliquez l'idée globale, l'origine, les influences...";
                fieldsHtml += `
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label style="margin-bottom: 0;">Description / Paragraphe</label>
                            <button type="button" class="btn btn-secondary btn-ai-magic" onclick="magicWandDossier(this, '${type}', false)" style="padding: 4px 10px; font-size: 0.75rem;">🪄 Magic IA</button>
                        </div>
                        <textarea name="dossier_texte[]" rows="4" placeholder="${placeholderText}">${escapeHtml(textVal)}</textarea>
                    </div>
                `;
            } else {
                fieldsHtml += `<input type="hidden" name="dossier_texte[]" value="">`;
            }

            // Academic Consigne Style or Layout selection
            if (type === 'consigne') {
                const accentVal = data.accent || 'info';
                fieldsHtml += `
                    <div class="form-group">
                        <label>Style visuel de la Consigne</label>
                        <select name="dossier_accent[]">
                            <option value="info" ${accentVal === 'info' ? 'selected' : ''}>🔵 Bleu Info (Conseil / Note)</option>
                            <option value="success" ${accentVal === 'success' ? 'selected' : ''}>🟢 Vert Succès (Démarche validée)</option>
                            <option value="warning" ${accentVal === 'warning' ? 'selected' : ''}>🟡 Orange Avertissement (Contrainte / Attention)</option>
                            <option value="danger" ${accentVal === 'danger' ? 'selected' : ''}>🔴 Rouge Important (Directive stricte)</option>
                        </select>
                    </div>
                `;
            } else if (type === 'mood_board' || type === 'before_after') {
                const accentVal = data.accent || 'center';
                fieldsHtml += `
                    <div class="form-group">
                        <label>Mise en page / Disposition de ce bloc</label>
                        <select name="dossier_accent[]">
                            <option value="center" ${accentVal === 'center' ? 'selected' : ''}>↕️ Centré au milieu (Composant seul)</option>
                            <option value="split-left" ${accentVal === 'split-left' ? 'selected' : ''}>📖 Texte à gauche / Média à droite</option>
                            <option value="split-right" ${accentVal === 'split-right' ? 'selected' : ''}>📖 Média à gauche / Texte à droite</option>
                        </select>
                    </div>
                `;
            } else {
                fieldsHtml += `<input type="hidden" name="dossier_accent[]" value="">`;
            }

            // Image 1
            if (type === 'text_image' || type === 'image_text' || type === 'full_image' || type === 'two_images' || type === 'code' || type === 'before_after') {
                const img1Val = data.image1 || '';
                const alt1Val = data.alt1 || '';
                const legende1Val = data.legende1 || '';
                const img1Label = type === 'before_after' ? "Image AVANT (Gauche/Chemin)" : "Capture d'écran / Image 1 (Chemin)";
                const leg1Placeholder = type === 'before_after' ? "ex: Avant retouche" : "ex: Fig 1 — Structure de la grille";
                fieldsHtml += `
                    <div class="form-row" style="display:flex; gap:1rem; margin-top:1rem;">
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label>${img1Label}</label>
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <input type="text" name="dossier_image1[]" value="${escapeHtml(img1Val)}" placeholder="ex: images/p1/01.webp" style="flex:1;">
                                <button type="button" class="btn btn-secondary" onclick="openMediaModal(this.previousElementSibling, '${projectId}')" style="padding:0.6rem; border-radius:6px;" title="Médiathèque">🖼️</button>
                            </div>
                        </div>
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label>Texte alternatif 1 (SEO)</label>
                            <input type="text" name="dossier_alt1[]" value="${escapeHtml(alt1Val)}" placeholder="ex: Screen responsive">
                        </div>
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label>${type === 'before_after' ? "Légende AVANT (Optionnel)" : "Légende 1"}</label>
                            <input type="text" name="dossier_legende1[]" value="${escapeHtml(legende1Val)}" placeholder="${leg1Placeholder}">
                        </div>
                    </div>
                `;
            } else if (type === 'mood_board') {
                const img1Val = data.image1 || ''; // comma-separated
                fieldsHtml += `
                    <div class="form-group" style="margin-top: 1rem;">
                        <label style="font-weight: bold; color: var(--accent-color);">🎨 Liste des images du Mood Board (Bento Grid)</label>
                        <div class="moodboard-images-list" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--border-color);" id="moodboard-list-${currentBlockIndex}">
                            <!-- Les miniatures seront chargées dynamiquement -->
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addMoodboardSlot(${currentBlockIndex}, '', '${projectId}')" style="margin-top: 0.8rem; font-size: 0.8rem; padding: 0.4rem 1rem;">+ Ajouter une image</button>
                        <input type="hidden" name="dossier_image1[]" id="moodboard-hidden-${currentBlockIndex}" value="${escapeHtml(img1Val)}">
                    </div>
                `;
                
                // Charger les miniatures
                const imagesArray = img1Val ? img1Val.split(',') : [];
                setTimeout(() => {
                    if (imagesArray.length === 0) {
                        addMoodboardSlot(currentBlockIndex, '', projectId);
                        addMoodboardSlot(currentBlockIndex, '', projectId);
                    } else {
                        imagesArray.forEach(path => {
                            addMoodboardSlot(currentBlockIndex, path, projectId);
                        });
                    }
                }, 10);
                
                fieldsHtml += `
                    <input type="hidden" name="dossier_alt1[]" value="">
                    <input type="hidden" name="dossier_legende1[]" value="">
                `;
            } else {
                fieldsHtml += `
                    <input type="hidden" name="dossier_image1[]" value="">
                    <input type="hidden" name="dossier_alt1[]" value="">
                    <input type="hidden" name="dossier_legende1[]" value="">
                `;
            }

            // Image 2 (for side-by-side or avant/apres)
            if (type === 'two_images' || type === 'before_after') {
                const img2Val = data.image2 || '';
                const alt2Val = data.alt2 || '';
                const legende2Val = data.legende2 || '';
                const img2Label = type === 'before_after' ? "Image APRÈS (Droite/Chemin)" : "Capture d'écran / Image 2 (Chemin)";
                const leg2Placeholder = type === 'before_after' ? "ex: Après retouche" : "ex: Fig 2 — Version responsive mobile";
                fieldsHtml += `
                    <div class="form-row" style="display:flex; gap:1rem; margin-top:1rem;">
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label>${img2Label}</label>
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <input type="text" name="dossier_image2[]" value="${escapeHtml(img2Val)}" placeholder="ex: images/p1/02.webp" style="flex:1;">
                                <button type="button" class="btn btn-secondary" onclick="openMediaModal(this.previousElementSibling, '${projectId}')" style="padding:0.6rem; border-radius:6px;" title="Médiathèque">🖼️</button>
                            </div>
                        </div>
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label>Texte alternatif 2 (SEO)</label>
                            <input type="text" name="dossier_alt2[]" value="${escapeHtml(alt2Val)}" placeholder="ex: Vue mobile">
                        </div>
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label>${type === 'before_after' ? "Légende APRÈS (Optionnel)" : "Légende 2"}</label>
                            <input type="text" name="dossier_legende2[]" value="${escapeHtml(legende2Val)}" placeholder="${leg2Placeholder}">
                        </div>
                    </div>
                `;
            } else {
                fieldsHtml += `
                    <input type="hidden" name="dossier_image2[]" value="">
                    <input type="hidden" name="dossier_alt2[]" value="">
                    <input type="hidden" name="dossier_legende2[]" value="">
                `;
            }

            div.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.8rem; margin-bottom:1rem;">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <span class="drag-handle" style="cursor:grab; color:#8b949e; padding:0 0.5rem; font-size: 1.15rem;" title="Glisser pour réorganiser">☰</span>
                        <span style="font-size:1.3rem;">${blockIcon}</span>
                        <strong style="color:${getBlockColor(type)}; text-transform:uppercase; font-size:0.85rem; letter-spacing:0.5px;">${blockTitle}</strong>
                    </div>
                    <div style="display:flex; gap:0.3rem;">
                        <button type="button" class="btn-ctrl" onclick="moveUp(this)" style="padding:2px 6px;" title="Monter">↑</button>
                        <button type="button" class="btn-ctrl" onclick="moveDown(this)" style="padding:2px 6px;" title="Descendre">↓</button>
                        <button type="button" class="btn-remove" onclick="if(confirm('Supprimer ce bloc ?')){this.closest('.dossier-item').remove(); updatePerformance();}" style="background:none; border:none; color:var(--danger-color); cursor:pointer; font-weight:bold; font-size:1.3rem; padding:0 0.5rem;" title="Supprimer">×</button>
                    </div>
                </div>
                <div class="dossier-item-fields">
                    ${fieldsHtml}
                </div>
            `;

            container.appendChild(div);
            
            // Re-bind performance tracker listeners on new inputs
            div.querySelectorAll('input[name="dossier_image1[]"], input[name="dossier_image2[]"]').forEach(input => {
                input.addEventListener('input', updatePerformance);
            });
            
            if (typeof galleryDD !== 'undefined') {
                galleryDD.bindEvents(div);
            }
        }

        // Charger les blocs existants au démarrage de la page
        document.addEventListener('DOMContentLoaded', () => {
            <?php
            $dossier_blocks = $project_to_edit['dossier'] ?? [];
            
            // Auto-migration: si le dossier est vide mais qu'on a les anciens champs
            if (empty($dossier_blocks) && $project_to_edit) {
                if (!empty(trim($project_to_edit['brief'] ?? ''))) $dossier_blocks[] = ['type' => 'brief', 'texte' => trim($project_to_edit['brief'])];
                if (!empty(trim($project_to_edit['concept'] ?? ''))) $dossier_blocks[] = ['type' => 'concept', 'texte' => trim($project_to_edit['concept'])];
                if (!empty(trim($project_to_edit['defis'] ?? ''))) $dossier_blocks[] = ['type' => 'defis', 'texte' => trim($project_to_edit['defis'])];
                if (!empty(trim($project_to_edit['resultats'] ?? ''))) $dossier_blocks[] = ['type' => 'resultats', 'texte' => trim($project_to_edit['resultats'])];
            }

            if (is_array($dossier_blocks)) {
                foreach ($dossier_blocks as $block) {
                    $json_block = json_encode($block, JSON_UNESCAPED_UNICODE);
                    echo "            addDossierBlock(" . json_encode($block['type']) . ", " . $json_block . ");\n";
                }
            }
            ?>
        });
        // --- GESTION DU MOODBOARD DYNAMIQUE ---
        function addMoodboardSlot(blockIndex, initialPath = '', projectId) {
            const list = document.getElementById(`moodboard-list-${blockIndex}`);
            if (!list) return;
            
            const div = document.createElement('div');
            div.className = 'moodboard-slot';
            div.style.display = 'flex';
            div.style.gap = '0.5rem';
            div.style.alignItems = 'center';
            div.style.marginTop = '0.5rem';
            
            const svgPlaceholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMjIyIi8+PC9zdmc+';
            const assetsBaseUrl = '<?php echo ASSETS_BASE_URL; ?>';
            const previewUrl = initialPath ? assetsBaseUrl + initialPath.replace(/^\//, '') : svgPlaceholder;
            
            div.innerHTML = `
                <img src="${previewUrl}" class="preview-img" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); background: #111;" onerror="this.onerror=null; this.src='${svgPlaceholder}';">
                <input type="text" class="moodboard-slot-input" value="${escapeHtml(initialPath)}" placeholder="images/p1/mood.webp" style="flex: 1; font-size: 0.85rem;" oninput="syncMoodboardPaths(${blockIndex})">
                <button type="button" class="btn btn-secondary" onclick="openMediaModal(this.previousElementSibling, '${projectId}')" style="padding: 0.5rem; border-radius: 4px;" title="Médiathèque">🖼️</button>
                <button type="button" class="btn-remove" onclick="this.parentElement.remove(); syncMoodboardPaths(${blockIndex});" style="font-size: 1.25rem; cursor: pointer; color: var(--danger-color); background: none; border: none; padding: 0 0.5rem;" title="Supprimer">×</button>
            `;
            
            list.appendChild(div);
            
            const input = div.querySelector('.moodboard-slot-input');
            input.addEventListener('input', () => {
                const preview = div.querySelector('.preview-img');
                preview.src = input.value ? assetsBaseUrl + input.value.replace(/^\//, '') : svgPlaceholder;
                syncMoodboardPaths(blockIndex);
            });
        }

        function syncMoodboardPaths(blockIndex) {
            const list = document.getElementById(`moodboard-list-${blockIndex}`);
            const hidden = document.getElementById(`moodboard-hidden-${blockIndex}`);
            if (!list || !hidden) return;
            
            const inputs = list.querySelectorAll('.moodboard-slot-input');
            const paths = Array.from(inputs).map(i => i.value.trim()).filter(p => p !== '');
            hidden.value = paths.join(',');
            
            if (typeof updatePerformance === 'function') {
                updatePerformance();
            }
        }
    </script>
</body>
</html>
