<?php
// sidebar.php — Navigation commune à toutes les pages
// Usage: set $active_page = 'index' then require_once 'sidebar.php'

$nav_items = [
    ['page' => 'index', 'href' => 'index.php', 'label' => 'Tableau de bord', 'icon' => '📊'],
    ['page' => 'stats', 'href' => 'stats.php', 'label' => 'Statistiques', 'icon' => '📈'],
    ['page' => 'upload', 'href' => 'upload.php', 'label' => 'Médiathèque', 'icon' => '🖼️'],
    ['page' => 'projects', 'href' => 'edit_projects.php', 'label' => 'Gestion Projets', 'icon' => '📁'],
    ['page' => 'logos', 'href' => 'edit_logos.php', 'label' => 'Logos & Logiciels', 'icon' => '💎'],
    ['page' => 'skills', 'href' => 'edit_skills.php', 'label' => 'Compétences', 'icon' => '⚡'],
    ['page' => 'ai', 'href' => 'settings_ai.php', 'label' => 'Intelligence IA', 'icon' => '🤖'],
    ['page' => 'general', 'href' => 'edit_general.php', 'label' => 'Infos Générales', 'icon' => '⚙️'],
    ['page' => 'security', 'href' => 'change_password.php', 'label' => 'Sécurité', 'icon' => '🔒'],
];

// Déterminer l'URL du portfolio pour le lien "Voir le site"
$portfolio_url = defined('PORTFOLIO_URL') ? PORTFOLIO_URL : '../';
?>
<aside class="sidebar" id="admin-sidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; gap: 0.7rem;">
            <div style="width: 34px; height: 34px; border-radius: 10px; background: linear-gradient(135deg, var(--accent-color), var(--purple)); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 800; color: #fff; flex-shrink: 0;"><?php echo htmlspecialchars(mb_strtoupper(mb_substr(defined('PORTFOLIO_OWNER') ? PORTFOLIO_OWNER : 'Portfolio', 0, 1))); ?></div>
            <h2>Admin Panel</h2>
        </div>
        <button class="sidebar-toggle" id="sidebar-close" aria-label="Fermer le menu">✕</button>
    </div>
    <nav aria-label="Navigation principale">
        <a href="<?php echo $portfolio_url; ?>" class="nav-link nav-link-portfolio" target="_blank" rel="noopener" aria-label="Voir le portfolio">
            <span class="nav-icon">🌐</span>
            <span class="nav-label">Voir le portfolio</span>
            <svg style="margin-left: auto; width: 14px; height: 14px; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
        <hr style="margin: 0.6rem 0 0.8rem; border: none; height: 1px; background: linear-gradient(90deg, transparent, var(--border-color), transparent);">
        <?php foreach ($nav_items as $item): ?>
            <a href="<?php echo $item['href']; ?>" class="nav-link <?php echo ($active_page ?? '') === $item['page'] ? 'active' : ''; ?>" aria-label="<?php echo $item['label']; ?>">
                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                <span class="nav-label"><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
        <hr style="margin: 0.8rem 0; border: none; height: 1px; background: linear-gradient(90deg, transparent, var(--border-color), transparent);">
        <a href="auth.php?logout=1" class="nav-link" style="color: var(--danger-color);" aria-label="Se déconnecter">
            <span class="nav-icon">🚪</span>
            <span class="nav-label">Déconnexion</span>
        </a>
    </nav>
    <div class="sidebar-version">Admin Panel v2.0</div>
</aside>
<!-- Hamburger button for mobile -->
<button class="sidebar-overlay-toggle" id="sidebar-open" aria-label="Ouvrir le menu">☰</button>

<!-- Métadonnée globale pour injecter la base d'URL des assets dans le Javascript -->
<meta name="assets-base" content="<?php echo ASSETS_BASE_URL; ?>">
