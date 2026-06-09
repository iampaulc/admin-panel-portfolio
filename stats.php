<?php
require_once 'auth.php';
check_auth();

$stats_file = __DIR__ . '/data/stats.json';
$stats = [];
if (file_exists($stats_file)) {
    $stats = json_decode(file_get_contents($stats_file), true) ?: [];
}

// Extraction globale
$site_views = $stats['site_views'] ?? 0;
$unique_visitors = $stats['unique_visitors'] ?? 0;
// Extraction des clés
$devices = $stats['devices'] ?? ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0];
$referrers = $stats['referrers'] ?? [];
arsort($referrers);
$pages = $stats['pages'] ?? [];
arsort($pages);

// Extraction jours
$daily = $stats['daily'] ?? [];
$chart_labels = [];
$chart_views = [];
$chart_uniques = [];

$today_views = 0;
$today_unique = 0;

for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d/m', strtotime("-$i days"));
    $chart_labels[] = $label;
    
    $d = $daily[$date] ?? ['views' => 0, 'unique' => 0];
    $chart_views[] = $d['views'] ?? 0;
    $chart_uniques[] = $d['unique'] ?? 0;
    
    if ($i == 0) { // today
        $today_views = $d['views'] ?? 0;
        $today_unique = $d['unique'] ?? 0;
    }
}

// Projets data for linking
$json_data = @file_get_contents(JSON_DATA_PATH);
$data_site = json_decode($json_data, true);
$projets = $data_site['projets'] ?? [];
$project_titles = [];
foreach ($projets as $p) {
    if(!empty($p['projetId'])) {
        $project_titles['/projet/'.$p['projetId']] = $p['titre'];
    }
}

// Average views per day
$total_days = count(array_filter($chart_views, fn($v) => $v > 0));
$avg_views = $total_days > 0 ? round(array_sum($chart_views) / $total_days) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin Portfolio</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-large {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.03em;
            animation: countUp 0.5s ease-out;
        }
        .chart-container-large { position: relative; width: 100%; height: 300px; padding: 1rem; }
        .chart-container-small { position: relative; width: 100%; height: 250px; padding: 1rem; }
        
        .stat-bar { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.8rem; }
        .stat-bar-label {
            min-width: 140px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-color);
        }
        .stat-bar-fill {
            height: 26px;
            border-radius: 6px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            padding-left: 10px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #fff;
        }
        .stat-bar-wrapper {
            flex: 1;
            background: rgba(255,255,255,0.04);
            border-radius: 6px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        .stat-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; }
        
        @media (max-width: 768px) {
            .stat-grid-2 { grid-template-columns: 1fr; }
        }
        
        .table-pages { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table-pages th, .table-pages td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(56, 68, 96, 0.3); }
        .table-pages th { color: var(--text-muted); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; }
        .table-pages tr:hover { background-color: rgba(96, 165, 250, 0.04); }

        .stats-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php $active_page = 'stats'; require_once 'sidebar.php'; ?>

        <main class="main-content">
            <header class="header-actions">
                <div>
                    <h1>📊 Analytics Avancées</h1>
                    <p>Vue d'ensemble de la performance de votre portfolio.</p>
                </div>
            </header>

            <!-- KPIs -->
            <div class="stats-kpi-grid">
                <div class="card kpi-card kpi-purple">
                    <div class="kpi-icon">👤</div>
                    <p class="kpi-label">Visiteurs Uniques</p>
                    <p class="kpi-value" data-target="<?php echo $unique_visitors; ?>"><?php echo number_format($unique_visitors); ?></p>
                    <p class="kpi-sub">Total</p>
                </div>
                <div class="card kpi-card kpi-blue">
                    <div class="kpi-icon">👁️</div>
                    <p class="kpi-label">Vues Totales</p>
                    <p class="kpi-value" data-target="<?php echo $site_views; ?>"><?php echo number_format($site_views); ?></p>
                    <p class="kpi-sub">Toutes pages</p>
                </div>
                <div class="card kpi-card kpi-green">
                    <div class="kpi-icon">📈</div>
                    <p class="kpi-label">Vues Aujourd'hui</p>
                    <p class="kpi-value" data-target="<?php echo $today_views; ?>"><?php echo number_format($today_views); ?></p>
                    <p class="kpi-sub"><?php echo date('d/m/Y'); ?></p>
                </div>
                <div class="card kpi-card kpi-orange">
                    <div class="kpi-icon">📊</div>
                    <p class="kpi-label">Moy. / jour</p>
                    <p class="kpi-value" data-target="<?php echo $avg_views; ?>"><?php echo number_format($avg_views); ?></p>
                    <p class="kpi-sub">30 derniers jours</p>
                </div>
            </div>

            <!-- Graphe Principal -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 0.5rem;">📉 Trafic sur les 30 derniers jours</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Vues totales et visiteurs uniques par jour</p>
                <div class="chart-container-large">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>

            <div class="stat-grid-2">
                <!-- Appareils -->
                <div class="card">
                    <h3>📱 Appareils</h3>
                    <div class="chart-container-small">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>

                <!-- Sources de trafic -->
                <div class="card">
                    <h3>🌍 Sources de trafic</h3>
                    <div style="margin-top: 1.5rem;">
                        <?php 
                        $max_ref = max(array_values($referrers) ?: [1]);
                        $i = 0;
                        foreach ($referrers as $source => $count): 
                            if ($i++ >= 8) break; // Top 8
                            $pct = max(2, ($count / $max_ref) * 100);
                        ?>
                            <div class="stat-bar" title="<?php echo htmlspecialchars($source); ?>">
                                <span class="stat-bar-label"><?php echo htmlspecialchars($source); ?></span>
                                <div class="stat-bar-wrapper">
                                    <div class="stat-bar-fill" style="width: 0%; background: linear-gradient(90deg, rgba(167,139,250,0.4), var(--purple));" data-width="<?php echo $pct; ?>">
                                        <?php echo $count; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($referrers)): ?>
                            <p style="color: var(--text-muted); text-align: center; margin-top: 2rem;">Aucune source détectée.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Pages -->
            <div class="card" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 0.5rem;">📄 Pages & Projets les plus consultés</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Top 15 des pages les plus visitées</p>
                <div style="overflow-x: auto;">
                    <table class="table-pages">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Page / Projet</th>
                                <th>Chemin</th>
                                <th style="text-align: right;">Vues</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            foreach ($pages as $path => $count): 
                                if ($i++ >= 15) break;
                                
                                $label = $path;
                                if ($path === '/' || $path === '') $label = 'Accueil';
                                if ($path === '/projets') $label = 'Galerie Projets (Toutes)';
                                if (isset($project_titles[$path])) {
                                    $label = 'Projet : ' . $project_titles[$path];
                                }
                            ?>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 700; font-size: 0.85rem;"><?php echo $i; ?></td>
                                <td><strong style="color: var(--text-bright);"><?php echo htmlspecialchars($label); ?></strong></td>
                                <td><code style="color: var(--text-muted); background: rgba(255,255,255,0.04); padding: 3px 8px; border-radius: 4px; font-size: 0.82rem;"><?php echo htmlspecialchars($path); ?></code></td>
                                <td style="text-align: right; font-weight: 700;">
                                    <span style="color: var(--accent-color);"><?php echo number_format($count); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pages)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">Aucune page visitée.</td>
                            </tr>
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
        // Configuration globale de Chart.js pour un thème sombre
        Chart.defaults.color = '#8899b4';
        Chart.defaults.font.family = "'Inter', 'Segoe UI', Roboto, sans-serif";
        Chart.defaults.font.weight = 500;
        
        // Données Graphe Principal
        const ctxMain = document.getElementById('mainChart').getContext('2d');

        // Create gradient fills
        const gradientBlue = ctxMain.createLinearGradient(0, 0, 0, 300);
        gradientBlue.addColorStop(0, 'rgba(96, 165, 250, 0.15)');
        gradientBlue.addColorStop(1, 'rgba(96, 165, 250, 0)');

        const mainChart = new Chart(ctxMain, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Vues Totales',
                        data: <?php echo json_encode($chart_views); ?>,
                        borderColor: '#60a5fa',
                        backgroundColor: gradientBlue,
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#111827',
                        pointBorderColor: '#60a5fa',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#60a5fa'
                    },
                    {
                        label: 'Visiteurs Uniques',
                        data: <?php echo json_encode($chart_uniques); ?>,
                        borderColor: '#a78bfa',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#111827',
                        pointBorderColor: '#a78bfa',
                        pointBorderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#a78bfa'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: { size: 12, weight: 600 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#f8fafc',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(56, 68, 96, 0.5)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        titleFont: { size: 13, weight: 700 },
                        bodyFont: { size: 12 },
                        displayColors: true,
                        boxPadding: 4
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(56, 68, 96, 0.15)', drawBorder: false },
                        ticks: { font: { size: 11, weight: 500 }, padding: 8 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, maxRotation: 0 }
                    }
                }
            }
        });

        // Données Graphe Appareils
        const devices = <?php echo json_encode($devices); ?>;
        const ctxDevice = document.getElementById('deviceChart').getContext('2d');
        const deviceChart = new Chart(ctxDevice, {
            type: 'doughnut',
            data: {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                datasets: [{
                    data: [devices.Desktop || 0, devices.Mobile || 0, devices.Tablet || 0],
                    backgroundColor: [
                        '#60a5fa',
                        '#34d399',
                        '#fb923c'
                    ],
                    borderColor: '#0a0e17',
                    borderWidth: 3,
                    hoverOffset: 8,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            padding: 16,
                            font: { size: 12, weight: 600 }
                        }
                    }
                },
                cutout: '72%'
            }
        });

        // Animate stat bars on load
        setTimeout(() => {
            document.querySelectorAll('.stat-bar-fill[data-width]').forEach(bar => {
                bar.style.width = bar.dataset.width + '%';
            });
        }, 300);
    </script>
</body>
</html>
