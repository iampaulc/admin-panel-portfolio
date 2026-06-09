<?php
require_once 'auth.php';
check_auth();

$json_data = @file_get_contents(JSON_DATA_PATH);
$data = json_decode($json_data, true);
if (!isset($data['logos'])) $data['logos'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $logos = [];
    if (isset($_POST['logo_chemin'])) {
        foreach ($_POST['logo_chemin'] as $i => $chemin) {
            if (!empty(trim($chemin))) {
                $logos[] = [
                    'chemin' => trim($chemin),
                    'titre' => trim($_POST['logo_titre'][$i]),
                    'alt' => trim($_POST['logo_alt'][$i]),
                    'classe' => trim($_POST['logo_classe'][$i])
                ];
            }
        }
    }
    $data['logos'] = $logos;
    if (file_exists(JSON_DATA_PATH)) copy(JSON_DATA_PATH, JSON_DATA_PATH . '.bak');
    $fp = fopen(JSON_DATA_PATH, 'c');
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    header('Location: edit_logos.php?status=saved');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logos & Logiciels - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php $active_page = 'logos'; require_once 'sidebar.php'; ?>

        <main class="main-content">
            <div class="header-actions">
                <h1>Logos & Logiciels</h1>
                <button type="submit" form="logos-form" class="btn btn-primary">💾 Sauvegarder tout</button>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <p>Déplacez les logos par <strong>glisser-déposer</strong> ou utilisez les flèches.</p>
                    <button type="button" class="btn btn-secondary" onclick="addLogo()">+ Ajouter un logo</button>
                </div>
                
                <form id="logos-form" action="edit_logos.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="logo-grid" id="sortable-grid">
                        <?php foreach ($data['logos'] as $logo): ?>
                            <div class="drag-item" draggable="true">
                                <div class="controls">
                                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" title="Précédent" aria-label="Précédent">←</button>
                                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" title="Suivant" aria-label="Suivant">→</button>
                                    <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" title="Supprimer" aria-label="Supprimer">✕</button>
                                </div>
                                <span style="position: absolute; left: 8px; top: 12px; color: var(--text-muted); cursor: grab;" aria-label="Déplacer">☰</span>
                                <img src="<?php echo ASSETS_BASE_URL . htmlspecialchars($logo['chemin']); ?>" class="preview-img" alt="<?php echo htmlspecialchars($logo['alt']); ?>" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'><rect width=\'100%\' height=\'100%\' fill=\'%23333\'/></svg>'">
                                <div style="display: flex; gap: 0.2rem;">
                                    <input type="text" name="logo_chemin[]" value="<?php echo htmlspecialchars($logo['chemin']); ?>" placeholder="Chemin (ex: icons/figma.svg)" style="flex: 1;">
                                    <button type="button" class="btn btn-secondary" onclick="openMediaModal(this.previousElementSibling, 'icons')" style="padding: 0.4rem;" title="Médiathèque" aria-label="Ouvrir la médiathèque">🖼️</button>
                                </div>
                                <input type="text" name="logo_titre[]" value="<?php echo htmlspecialchars($logo['titre']); ?>" placeholder="Titre complet">
                                <input type="text" name="logo_alt[]" value="<?php echo htmlspecialchars($logo['alt']); ?>" placeholder="Texte Alt">
                                <input type="text" name="logo_classe[]" value="<?php echo htmlspecialchars($logo['classe'] ?? ''); ?>" placeholder="Classe CSS spéciale">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <?php require_once 'footer.php'; ?>
        </main>
    </div>

    <?php require_once 'media_modal.php'; ?>

    <script src="js/dragdrop.js"></script>
    <script src="js/admin.js"></script>
    <script>
        const logosDD = initDragDropGrid('sortable-grid');

        function addLogo() {
            const grid = document.getElementById('sortable-grid');
            const div = document.createElement('div');
            div.className = 'drag-item';
            div.draggable = true;
            div.innerHTML = `
                <div class="controls">
                    <button type="button" class="btn-ctrl" onclick="moveUp(this)" title="Précédent" aria-label="Précédent">←</button>
                    <button type="button" class="btn-ctrl" onclick="moveDown(this)" title="Suivant" aria-label="Suivant">→</button>
                    <button type="button" class="btn-ctrl btn-remove" onclick="this.closest('.drag-item').remove()" title="Supprimer" aria-label="Supprimer">✕</button>
                </div>
                <span style="position: absolute; left: 8px; top: 12px; color: var(--text-muted); cursor: grab;">☰</span>
                <img class="preview-img" style="width:50px;height:50px;background:#333;margin:0 auto;border-radius:4px; padding: 4px; border: 1px solid #444;" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg'><rect width='100%' height='100%' fill='%23333'/></svg>" alt="">
                <div style="display: flex; gap: 0.2rem;">
                    <input type="text" name="logo_chemin[]" placeholder="Chemin (ex: icons/file.svg)" style="flex: 1;">
                    <button type="button" class="btn btn-secondary" onclick="openMediaModal(this.previousElementSibling, 'icons')" style="padding: 0.4rem;" title="Médiathèque" aria-label="Ouvrir la médiathèque">🖼️</button>
                </div>
                <input type="text" name="logo_titre[]" placeholder="Titre">
                <input type="text" name="logo_alt[]" placeholder="Texte Alt">
                <input type="text" name="logo_classe[]" placeholder="Classe CSS">
            `;
            grid.appendChild(div);
            logosDD.bindEvents(div);
        }
    </script>
</body>
</html>
