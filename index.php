<?php
require_once 'auth.php';
check_auth();

$json_data = @file_get_contents(JSON_DATA_PATH);
$data = json_decode($json_data, true);

if (!$data) {
    $data = [
        'projets' => [],
        'competencesHumaines' => [],
        'competencesTechniques' => [],
        'parcours' => []
    ];
}

// Stats
$stats_file = __DIR__ . '/data/stats.json';
$stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : ['site_views' => 0];

// Nombre d'images dans la médiathèque
$images_count = 0;
$images_dir = realpath(IMAGES_UPLOAD_DIR);
if ($images_dir && is_dir($images_dir)) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($images_dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg','jpeg','png','gif','webp','svg'])) {
            $images_count++;
        }
    }
}

// Nombre de compétences
$skills_count = 0;
if (!empty($data['competencesHumaines'])) $skills_count += count($data['competencesHumaines']);
if (!empty($data['competencesTechniques'])) $skills_count += count($data['competencesTechniques']);

// Salutation dynamique
$hour = (int)date('H');
if ($hour < 12) $greeting = 'Bonjour';
elseif ($hour < 18) $greeting = 'Bon après-midi';
else $greeting = 'Bonsoir';

// Date du jour
$days_fr = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$months_fr = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
$today_str = $days_fr[(int)date('w')] . ' ' . date('j') . ' ' . $months_fr[(int)date('n')] . ' ' . date('Y');

// Projets count
$projects_count = is_array($data['projets']) ? count($data['projets']) : 0;

// Vues aujourd'hui
$daily = $stats['daily'] ?? [];
$today_key = date('Y-m-d');
$today_views = $daily[$today_key]['views'] ?? 0;

// Views per project
$page_views = $stats['pages'] ?? [];
$project_views = [];
foreach ($data['projets'] as $p) {
    $pid = $p['projetId'] ?? '';
    $path = '/projet/' . $pid;
    $project_views[$p['id']] = $page_views[$path] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Portfolio</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2.5rem;
        }
        .project-thumb {
            width: 56px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }
        .project-thumb:hover {
            transform: scale(1.08);
        }
        .project-views-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.5rem;
            background: rgba(96, 165, 250, 0.08);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-bright);
            letter-spacing: -0.01em;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--border-color), transparent);
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Save progress bar -->
    <div class="save-progress" id="save-progress"></div>

    <div class="admin-layout">
        <?php $active_page = 'index'; require_once 'sidebar.php'; ?>

        <main class="main-content">
            <header class="header-actions">
                <div class="dash-welcome">
                    <div>
                        <h1><?php echo $greeting; ?>, <span>Admin</span> 👋</h1>
                        <div class="dash-date">📅 <?php echo $today_str; ?></div>
                    </div>
                </div>
                <div class="connected-badge">Connecté</div>
            </header>

            <!-- KPI Cards -->
            <div class="dashboard-cards">
                <div class="card kpi-card kpi-blue">
                    <div class="kpi-icon">👁️</div>
                    <p class="kpi-label">Vues totales</p>
                    <p class="kpi-value" id="view-count" data-target="<?php echo $stats['site_views'] ?? 0; ?>"><?php echo number_format($stats['site_views'] ?? 0); ?></p>
                    <p class="kpi-sub">Depuis le lancement</p>
                </div>
                <div class="card kpi-card kpi-green">
                    <div class="kpi-icon">📈</div>
                    <p class="kpi-label">Vues aujourd'hui</p>
                    <p class="kpi-value" data-target="<?php echo $today_views; ?>"><?php echo number_format($today_views); ?></p>
                    <p class="kpi-sub"><?php echo $today_str; ?></p>
                </div>
                <div class="card kpi-card kpi-purple">
                    <div class="kpi-icon">📁</div>
                    <p class="kpi-label">Projets publiés</p>
                    <p class="kpi-value" data-target="<?php echo $projects_count; ?>"><?php echo $projects_count; ?></p>
                    <p class="kpi-sub">Projets actifs</p>
                </div>
                <div class="card kpi-card kpi-orange">
                    <div class="kpi-icon">🖼️</div>
                    <p class="kpi-label">Médiathèque</p>
                    <p class="kpi-value" data-target="<?php echo $images_count; ?>"><?php echo number_format($images_count); ?></p>
                    <p class="kpi-sub">Images uploadées</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card" style="margin-bottom: 2.5rem; padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">⚡ Actions rapides</h3>
                <div class="quick-actions">
                    <a href="edit_projects.php?action=new" class="quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(96, 165, 250, 0.1);">✏️</div>
                        <div class="quick-action-text">
                            <strong>Nouveau Projet</strong>
                            <span>Créer un nouveau projet</span>
                        </div>
                    </a>
                    <a href="upload.php" class="quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(167, 139, 250, 0.1);">📤</div>
                        <div class="quick-action-text">
                            <strong>Upload Média</strong>
                            <span>Ajouter des images</span>
                        </div>
                    </a>
                    <a href="backup.php" class="quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(52, 211, 153, 0.1);">📦</div>
                        <div class="quick-action-text">
                            <strong>Backup Complet</strong>
                            <span>Télécharger un ZIP</span>
                        </div>
                    </a>
                    <a href="sitemap_gen.php" class="quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(251, 146, 60, 0.1);">🌐</div>
                        <div class="quick-action-text">
                            <strong>Sitemap XML</strong>
                            <span>Générer pour le SEO</span>
                        </div>
                    </a>
                    <a href="stats.php" class="quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(96, 165, 250, 0.1);">📊</div>
                        <div class="quick-action-text">
                            <strong>Analytics</strong>
                            <span>Voir les statistiques</span>
                        </div>
                    </a>
                    <a href="edit_general.php" class="quick-action-btn">
                        <div class="quick-action-icon" style="background: rgba(167, 139, 250, 0.1);">⚙️</div>
                        <div class="quick-action-text">
                            <strong>Infos Générales</strong>
                            <span>Modifier le profil</span>
                        </div>
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'sitemap_ok'): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">✅ Sitemap.xml généré avec succès à la racine du site !</div>
            <?php endif; ?>

            <!-- Projects Table -->
            <div class="section-title">
                📋 Aperçu des Projets
                <span class="section-title-line"></span>
            </div>
            <div class="card" style="padding: 0; overflow: hidden;">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="padding-left: 1.5rem;">Image</th>
                                <th>Titre</th>
                                <th>Tags</th>
                                <th>Vues</th>
                                <th style="text-align: right; padding-right: 1.5rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data['projets'])): ?>
                                <?php foreach ($data['projets'] as $p): ?>
                                <tr>
                                    <td style="padding-left: 1.5rem;">
                                        <img src="<?php echo ASSETS_BASE_URL . htmlspecialchars($p['image']); ?>" class="project-thumb" alt="<?php echo htmlspecialchars($p['alt'] ?? ''); ?>" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\'><rect width=\'100%\' height=\'100%\' fill=\'%23333\'/></svg>'">
                                    </td>
                                    <td style="font-weight: 600; color: var(--text-bright);"><?php echo htmlspecialchars($p['titre']); ?></td>
                                    <td>
                                        <?php foreach (array_slice($p['tags'], 0, 3) as $t): ?>
                                            <span class="tag"><?php echo htmlspecialchars($t); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($p['tags']) > 3): ?>
                                            <span class="tag" style="opacity: 0.6;">+<?php echo count($p['tags']) - 3; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="project-views-badge">
                                            👁️ <?php echo number_format($project_views[$p['id']] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; padding-right: 1.5rem;">
                                        <a href="edit_projects.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary" style="padding: 0.4rem 0.9rem; font-size: 0.82rem;">Modifier →</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">Aucun projet pour le moment.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php require_once 'footer.php'; ?>
        </main>
    </div>

    <script src="js/admin.js"></script>
    <script>
        // Auto-refresh view count every 30s
        setInterval(async () => {
            try {
                const res = await fetch('api_views.php');
                const data = await res.json();
                const el = document.getElementById('view-count');
                if (el && data.count !== undefined) {
                    el.textContent = Number(data.count).toLocaleString('fr-FR');
                }
            } catch(e) {}
        }, 30000);
    </script>
</body>
</html>
