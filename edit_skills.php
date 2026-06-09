<?php
require_once 'auth.php';
check_auth();

$json_data = @file_get_contents(JSON_DATA_PATH);
$data = json_decode($json_data, true);
if (!$data) $data = ['competencesHumaines' => [], 'competencesTechniques' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $humaines = [];
    if (isset($_POST['h_titre'])) {
        foreach ($_POST['h_titre'] as $i => $t) {
            if (!empty(trim($t))) {
                $humaines[] = [
                    'icone' => trim($_POST['h_icone'][$i]),
                    'titre' => trim($t),
                    'description' => trim($_POST['h_desc'][$i])
                ];
            }
        }
    }
    
    $techniques = [];
    if (isset($_POST['t_titre'])) {
        foreach ($_POST['t_titre'] as $i => $t) {
            if (!empty(trim($t))) {
                $techniques[] = [
                    'icone' => trim($_POST['t_icone'][$i]),
                    'titre' => trim($t),
                    'description' => trim($_POST['t_desc'][$i])
                ];
            }
        }
    }
    
    $data['competencesHumaines'] = $humaines;
    $data['competencesTechniques'] = $techniques;
    
    if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
    $fp = fopen(JSON_DATA_PATH, 'c');
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    header('Location: edit_skills.php?status=saved');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer Compétences - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php $active_page = 'skills'; require_once 'sidebar.php'; ?>
        <main class="main-content">
            <div class="header-actions">
                <h1>Compétences</h1>
                <button type="submit" form="skills-form" class="btn btn-primary">💾 Sauvegarder tout</button>
            </div>
            
            <form id="skills-form" method="POST">
                <?php echo csrf_field(); ?>
                <div class="skills-grid">
                    <!-- HUMAINES -->
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3>Soft Skills (Humaines)</h3>
                            <button type="button" class="btn btn-secondary" onclick="addSkill('h-container', 'h_')">+ Ajouter</button>
                        </div>
                        
                        <div id="h-container">
                            <?php foreach (($data['competencesHumaines'] ?? []) as $s): ?>
                                <div class="drag-item" draggable="true">
                                    <div class="controls">
                                        <button type="button" class="btn-ctrl" onclick="moveUp(this)" title="Monter" aria-label="Monter">↑</button>
                                        <button type="button" class="btn-ctrl" onclick="moveDown(this)" title="Descendre" aria-label="Descendre">↓</button>
                                        <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" title="Supprimer" aria-label="Supprimer">✕</button>
                                    </div>
                                    <div style="margin-right: 100px;">
                                        <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem; align-items: center;">
                                            <span style="color:var(--text-muted); font-size: 1.5rem;" aria-label="Déplacer">☰</span>
                                            <input type="text" name="h_icone[]" value="<?php echo htmlspecialchars($s['icone']); ?>" style="width: 60px; text-align: center; font-size: 1.2rem;" aria-label="Icône">
                                            <input type="text" name="h_titre[]" value="<?php echo htmlspecialchars($s['titre']); ?>" style="flex: 1;" aria-label="Titre">
                                        </div>
                                        <textarea name="h_desc[]" rows="2" aria-label="Description"><?php echo htmlspecialchars($s['description']); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- TECHNIQUES -->
                    <section class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3>Hard Skills (Techniques)</h3>
                            <button type="button" class="btn btn-secondary" onclick="addSkill('t-container', 't_')">+ Ajouter</button>
                        </div>

                        <div id="t-container">
                            <?php foreach (($data['competencesTechniques'] ?? []) as $s): ?>
                                <div class="drag-item" draggable="true">
                                    <div class="controls">
                                        <button type="button" class="btn-ctrl" onclick="moveUp(this)" title="Monter" aria-label="Monter">↑</button>
                                        <button type="button" class="btn-ctrl" onclick="moveDown(this)" title="Descendre" aria-label="Descendre">↓</button>
                                        <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" title="Supprimer" aria-label="Supprimer">✕</button>
                                    </div>
                                    <div style="margin-right: 100px;">
                                        <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem; align-items: center;">
                                            <span style="color:var(--text-muted); font-size: 1.5rem;" aria-label="Déplacer">☰</span>
                                            <input type="text" name="t_icone[]" value="<?php echo htmlspecialchars($s['icone']); ?>" style="width: 60px; text-align: center; font-size: 1.2rem;" aria-label="Icône">
                                            <input type="text" name="t_titre[]" value="<?php echo htmlspecialchars($s['titre']); ?>" style="flex: 1;" aria-label="Titre">
                                        </div>
                                        <textarea name="t_desc[]" rows="2" aria-label="Description"><?php echo htmlspecialchars($s['description']); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </form>
            <?php require_once 'footer.php'; ?>
        </main>
    </div>

    <script src="js/dragdrop.js"></script>
    <script src="js/admin.js"></script>
    <script>
        initDragDrop('h-container');
        initDragDrop('t-container');

        function addSkill(containerId, prefix) {
            const container = document.getElementById(containerId);
            const div = document.createElement('div');
            div.className = 'drag-item';
            div.draggable = true;
            div.innerHTML = `
                <div class="controls">
                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" aria-label="Monter">↑</button>
                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" aria-label="Descendre">↓</button>
                    <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" aria-label="Supprimer">✕</button>
                </div>
                <div style="margin-right: 100px;">
                    <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem; align-items: center;">
                        <span style="color:var(--text-muted); font-size: 1.5rem;">☰</span>
                        <input type="text" name="${prefix}icone[]" placeholder="Emoji" style="width: 60px; text-align: center; font-size: 1.2rem;">
                        <input type="text" name="${prefix}titre[]" placeholder="Titre" style="flex: 1;">
                    </div>
                    <textarea name="${prefix}desc[]" rows="2" placeholder="Description"></textarea>
                </div>
            `;
            container.appendChild(div);
            const dd = (containerId === 'h-container') ? initDragDrop('h-container') : initDragDrop('t-container');
        }
    </script>
</body>
</html>
