<?php
require_once 'auth.php';
check_auth();

$json_data = @file_get_contents(JSON_DATA_PATH);
$data = json_decode($json_data, true);

// Initialiser les sections manquantes
if (!isset($data['portrait'])) $data['portrait'] = ['titre'=>'', 'motCle'=>'', 'paragraphe1'=>'', 'paragraphe2'=>'', 'image'=>''];
if (!isset($data['parcours'])) $data['parcours'] = [];
if (!isset($data['influences'])) $data['influences'] = [];
if (!isset($data['processusCreatif'])) $data['processusCreatif'] = [];
if (!isset($data['socials'])) $data['socials'] = ['instagram'=>'', 'behance'=>'', 'linkedin'=>'', 'github'=>'', 'email'=>''];
if (!isset($data['seo'])) $data['seo'] = ['titre'=>'', 'description'=>'', 'ogImage'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_portrait') {
        $data['portrait'] = [
            'titre' => trim($_POST['portrait_titre']),
            'motCle' => trim($_POST['portrait_motCle']),
            'paragraphe1' => trim($_POST['portrait_p1']),
            'paragraphe2' => trim($_POST['portrait_p2']),
            'image' => trim($_POST['portrait_image'] ?? $data['portrait']['image'] ?? '')
        ];
    } elseif ($action === 'save_parcours') {
        $parcours = [];
        if (isset($_POST['annee'])) {
            foreach ($_POST['annee'] as $i => $annee) {
                if (!empty(trim($annee))) {
                    $parcours[] = [
                        'annee' => trim($annee),
                        'titre' => trim($_POST['titre'][$i]),
                        'description' => trim($_POST['description'][$i])
                    ];
                }
            }
        }
        $data['parcours'] = $parcours;
    } elseif ($action === 'save_influences') {
        $influences = [];
        if (isset($_POST['inf_titre'])) {
            foreach ($_POST['inf_titre'] as $i => $titre) {
                if (!empty(trim($titre))) {
                    $influences[] = [
                        'titre' => trim($titre),
                        'description' => trim($_POST['inf_desc'][$i])
                    ];
                }
            }
        }
        $data['influences'] = $influences;
    } elseif ($action === 'save_socials') {
        $data['socials'] = [
            'instagram' => trim($_POST['social_instagram'] ?? ''),
            'behance' => trim($_POST['social_behance'] ?? ''),
            'linkedin' => trim($_POST['social_linkedin'] ?? ''),
            'github' => trim($_POST['social_github'] ?? ''),
            'email' => trim($_POST['social_email'] ?? '')
        ];
    } elseif ($action === 'save_seo') {
        $data['seo'] = [
            'titre' => trim($_POST['seo_titre'] ?? ''),
            'description' => trim($_POST['seo_description'] ?? ''),
            'ogImage' => trim($_POST['seo_ogImage'] ?? '')
        ];
    } elseif ($action === 'save_processus') {
        $processus = [];
        if (isset($_POST['proc_titre'])) {
            foreach ($_POST['proc_titre'] as $i => $titre) {
                if (!empty(trim($titre))) {
                    $processus[] = [
                        'etape' => $i + 1,
                        'icone' => trim($_POST['proc_icone'][$i]),
                        'titre' => trim($titre),
                        'description' => trim($_POST['proc_desc'][$i])
                    ];
                }
            }
        }
        $data['processusCreatif'] = $processus;
    }
    
    if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
    $fp = fopen(JSON_DATA_PATH, 'c');
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    header('Location: edit_general.php?status=saved');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infos Générales - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php $active_page = 'general'; require_once 'sidebar.php'; ?>
        <main class="main-content">
            <header class="header-actions">
                <h1>Infos Générales</h1>
            </header>

            <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">

                <!-- PORTRAIT -->
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_portrait">
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3>🖼️ Portrait (Accueil)</h3>
                            <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                        </div>
                        <div style="margin-top: 1.5rem; background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                            <div class="form-group">
                                <label>Image du Portrait</label>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="text" name="portrait_image" value="<?php echo htmlspecialchars($data['portrait']['image'] ?? ''); ?>" placeholder="images/portrait.webp" style="flex: 1;" id="input-portrait">
                                    <button type="button" class="btn btn-secondary" onclick="openMediaModal(document.getElementById('input-portrait'), '')" style="padding: 0.6rem;" aria-label="Ouvrir la médiathèque">🖼️</button>
                                    <?php if (!empty($data['portrait']['image'] ?? '')): ?>
                                        <img src="<?php echo ASSETS_BASE_URL . htmlspecialchars($data['portrait']['image']); ?>" class="preview-img" style="width: 46px; height: 46px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);" alt="Portrait preview">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display:flex; gap:1rem;">
                                <div class="form-group" style="flex:1;">
                                    <label>Titre Principal</label>
                                    <input type="text" name="portrait_titre" value="<?php echo htmlspecialchars($data['portrait']['titre']); ?>">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Mot Clé (Animé)</label>
                                    <input type="text" name="portrait_motCle" value="<?php echo htmlspecialchars($data['portrait']['motCle']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Paragraphe 1 (Mettre **Texte** pour gras)</label>
                                <textarea name="portrait_p1" rows="4"><?php echo htmlspecialchars($data['portrait']['paragraphe1']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Paragraphe 2 (Sous-texte)</label>
                                <textarea name="portrait_p2" rows="2"><?php echo htmlspecialchars($data['portrait']['paragraphe2']); ?></textarea>
                            </div>
                        </div>
                    </section>
                </form>

                <!-- LIENS SOCIAUX -->
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_socials">
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3>🔗 Liens Sociaux & Contact</h3>
                            <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                        </div>
                        <div style="margin-top: 1.5rem; background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                                <div class="form-group" style="flex:1; min-width: 250px;">
                                    <label>📷 Instagram (URL complète)</label>
                                    <input type="text" name="social_instagram" value="<?php echo htmlspecialchars($data['socials']['instagram'] ?? ''); ?>" placeholder="https://instagram.com/...">
                                </div>
                                <div class="form-group" style="flex:1; min-width: 250px;">
                                    <label>🎨 Behance (URL complète)</label>
                                    <input type="text" name="social_behance" value="<?php echo htmlspecialchars($data['socials']['behance'] ?? ''); ?>" placeholder="https://behance.net/...">
                                </div>
                            </div>
                            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                                <div class="form-group" style="flex:1; min-width: 250px;">
                                    <label>💼 LinkedIn (URL complète)</label>
                                    <input type="text" name="social_linkedin" value="<?php echo htmlspecialchars($data['socials']['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/...">
                                </div>
                                <div class="form-group" style="flex:1; min-width: 250px;">
                                    <label>💻 GitHub (URL complète)</label>
                                    <input type="text" name="social_github" value="<?php echo htmlspecialchars($data['socials']['github'] ?? ''); ?>" placeholder="https://github.com/...">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>📧 Email de contact</label>
                                <input type="text" name="social_email" value="<?php echo htmlspecialchars($data['socials']['email'] ?? ''); ?>" placeholder="contact@example.com">
                            </div>
                        </div>
                    </section>
                </form>

                <!-- META SEO -->
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_seo">
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3>🔍 Meta SEO</h3>
                            <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                        </div>
                        <div style="margin-top: 1.5rem; background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color);">
                            <div class="form-group">
                                <label>Titre du site (balise &lt;title&gt;)</label>
                                <input type="text" name="seo_titre" value="<?php echo htmlspecialchars($data['seo']['titre'] ?? ''); ?>" placeholder="Mon Portfolio — Designer & Développeur">
                            </div>
                            <div class="form-group">
                                <label>Meta description</label>
                                <textarea name="seo_description" rows="2" placeholder="Courte description pour Google..."><?php echo htmlspecialchars($data['seo']['description'] ?? ''); ?></textarea>
                                <small style="color: var(--text-muted);">Recommandé : 150-160 caractères.</small>
                            </div>
                            <div class="form-group">
                                <label>Image Open Graph (partage réseaux)</label>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="text" name="seo_ogImage" value="<?php echo htmlspecialchars($data['seo']['ogImage'] ?? ''); ?>" style="flex:1;" id="input-og" placeholder="images/og-preview.webp">
                                    <button type="button" class="btn btn-secondary" onclick="openMediaModal(document.getElementById('input-og'), '')" style="padding: 0.6rem;" aria-label="Ouvrir la médiathèque">🖼️</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>

                <!-- PARCOURS -->
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_parcours">
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3>📅 Mon Parcours (Timeline)</h3>
                            <div style="display: flex; gap: 1rem;">
                                <button type="button" class="btn btn-secondary" onclick="addParcours()">+ Étape</button>
                                <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                            </div>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: -10px; margin-bottom: 15px;">Glissez ou utilisez les flèches pour réorganiser.</p>
                        
                        <div id="parcours-container">
                            <?php foreach ($data['parcours'] as $p): ?>
                                <div class="drag-item" draggable="true">
                                    <div class="controls">
                                        <button type="button" class="btn-ctrl" onclick="moveUp(this)" aria-label="Monter">↑</button>
                                        <button type="button" class="btn-ctrl" onclick="moveDown(this)" aria-label="Descendre">↓</button>
                                        <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" aria-label="Supprimer">✕</button>
                                    </div>
                                    <div style="margin-right: 120px; padding-left: 2rem;">
                                        <span style="position: absolute; left: 1rem; top: 1.5rem; color: var(--text-muted); font-size: 1.5rem;" aria-label="Déplacer">☰</span>
                                        <div style="display:flex; gap:1rem;">
                                            <div class="form-group" style="width: 150px;">
                                                <label>Année</label>
                                                <input type="text" name="annee[]" value="<?php echo htmlspecialchars($p['annee'] ?? ''); ?>" placeholder="Année">
                                            </div>
                                            <div class="form-group" style="flex:1;">
                                                <label>Titre</label>
                                                <input type="text" name="titre[]" value="<?php echo htmlspecialchars($p['titre'] ?? ''); ?>" placeholder="Titre">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description[]" rows="2"><?php echo htmlspecialchars($p['description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </form>

                <!-- INFLUENCES -->
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_influences">
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3>✨ Influences</h3>
                            <div style="display: flex; gap: 1rem;">
                                <button type="button" class="btn btn-secondary" onclick="addInfluence()">+ Influence</button>
                                <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                            </div>
                        </div>
                        <div id="influences-container">
                            <?php foreach ($data['influences'] as $inf): ?>
                                <div class="drag-item" draggable="true">
                                    <div class="controls">
                                        <button type="button" class="btn-ctrl" onclick="moveUp(this)" aria-label="Monter">↑</button>
                                        <button type="button" class="btn-ctrl" onclick="moveDown(this)" aria-label="Descendre">↓</button>
                                        <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" aria-label="Supprimer">✕</button>
                                    </div>
                                    <div style="margin-right: 120px; padding-left: 2rem;">
                                        <span style="position: absolute; left: 1rem; top: 1.5rem; color: var(--text-muted); font-size: 1.5rem;">☰</span>
                                        <div class="form-group">
                                            <label>Influenceur / Artiste (Titre)</label>
                                            <input type="text" name="inf_titre[]" value="<?php echo htmlspecialchars($inf['titre'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="inf_desc[]" rows="2"><?php echo htmlspecialchars($inf['description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </form>

                <!-- PROCESSUS CREATIF -->
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_processus">
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3>🎯 Processus Créatif</h3>
                            <div style="display: flex; gap: 1rem;">
                                <button type="button" class="btn btn-secondary" onclick="addProcessus()">+ Étape</button>
                                <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                            </div>
                        </div>
                        <div id="processus-container">
                            <?php foreach ($data['processusCreatif'] as $proc): ?>
                                <div class="drag-item" draggable="true">
                                    <div class="controls">
                                        <button type="button" class="btn-ctrl" onclick="moveUp(this)" aria-label="Monter">↑</button>
                                        <button type="button" class="btn-ctrl" onclick="moveDown(this)" aria-label="Descendre">↓</button>
                                        <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" aria-label="Supprimer">✕</button>
                                    </div>
                                    <div style="margin-right: 120px; padding-left: 2rem;">
                                        <span style="position: absolute; left: 1rem; top: 1.5rem; color: var(--text-muted); font-size: 1.5rem;">☰</span>
                                        <div style="display:flex; gap:1rem; align-items:center;">
                                            <div class="form-group" style="width: 80px;">
                                                <label>Icône</label>
                                                <input type="text" name="proc_icone[]" value="<?php echo htmlspecialchars($proc['icone'] ?? ''); ?>" style="text-align:center; font-size:1.2rem;" placeholder="🎯">
                                            </div>
                                            <div class="form-group" style="flex:1;">
                                                <label>Titre de l'étape</label>
                                                <input type="text" name="proc_titre[]" value="<?php echo htmlspecialchars($proc['titre'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="proc_desc[]" rows="2"><?php echo htmlspecialchars($proc['description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </form>

            </div>
            <?php require_once 'footer.php'; ?>
        </main>
    </div>

    <?php require_once 'media_modal.php'; ?>

    <script src="js/dragdrop.js"></script>
    <script src="js/admin.js"></script>
    <script>
        initDragDrop('parcours-container');
        initDragDrop('influences-container');
        initDragDrop('processus-container');

        function createDragItem(containerId, innerHTML) {
            const container = document.getElementById(containerId);
            const div = document.createElement('div');
            div.className = 'drag-item';
            div.draggable = true;
            div.innerHTML = innerHTML;
            container.appendChild(div);
            initDragDrop(containerId);
        }

        function addParcours() {
            createDragItem('parcours-container', `
                <div class="controls">
                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" aria-label="Monter">↑</button>
                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" aria-label="Descendre">↓</button>
                    <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" aria-label="Supprimer">✕</button>
                </div>
                <div style="margin-right: 120px; padding-left: 2rem;">
                    <span style="position: absolute; left: 1rem; top: 1.5rem; color: var(--text-muted); font-size: 1.5rem;">☰</span>
                    <div style="display:flex; gap:1rem;">
                        <div class="form-group" style="width: 150px;">
                            <label>Année</label>
                            <input type="text" name="annee[]" placeholder="Année">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Titre</label>
                            <input type="text" name="titre[]" placeholder="Titre">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description[]" rows="2"></textarea>
                    </div>
                </div>
            `);
        }

        function addInfluence() {
            createDragItem('influences-container', `
                <div class="controls">
                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" aria-label="Monter">↑</button>
                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" aria-label="Descendre">↓</button>
                    <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" aria-label="Supprimer">✕</button>
                </div>
                <div style="margin-right: 120px; padding-left: 2rem;">
                    <span style="position: absolute; left: 1rem; top: 1.5rem; color: var(--text-muted); font-size: 1.5rem;">☰</span>
                    <div class="form-group">
                        <label>Influenceur / Artiste (Titre)</label>
                        <input type="text" name="inf_titre[]" placeholder="Titre Influence">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="inf_desc[]" rows="2"></textarea>
                    </div>
                </div>
            `);
        }

        function addProcessus() {
            createDragItem('processus-container', `
                <div class="controls">
                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" aria-label="Monter">↑</button>
                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" aria-label="Descendre">↓</button>
                    <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" aria-label="Supprimer">✕</button>
                </div>
                <div style="margin-right: 120px; padding-left: 2rem;">
                    <span style="position: absolute; left: 1rem; top: 1.5rem; color: var(--text-muted); font-size: 1.5rem;">☰</span>
                    <div style="display:flex; gap:1rem; align-items:center;">
                        <div class="form-group" style="width: 80px;">
                            <label>Icône</label>
                            <input type="text" name="proc_icone[]" style="text-align:center; font-size:1.2rem;" placeholder="🎯">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Titre de l'étape</label>
                            <input type="text" name="proc_titre[]" placeholder="Titre">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="proc_desc[]" rows="2"></textarea>
                    </div>
                </div>
            `);
        }
    </script>
</body>
</html>
