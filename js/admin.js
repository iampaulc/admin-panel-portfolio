/**
 * admin.js — Premium Admin Panel Utilities v2.0
 */

// --- Toast Notifications ---
function showToast(message, type = 'success') {
    const existing = document.querySelector('.admin-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `admin-toast admin-toast-${type}`;
    
    const icon = type === 'success' ? '✓' : '⚠';
    toast.innerHTML = `<span style="font-size: 1.1rem;">${icon}</span> ${message}`;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

// --- Unsaved Changes Warning ---
let formDirty = false;
let dirtyBadge = null;

function trackFormChanges() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('input', () => {
            if (!formDirty) {
                formDirty = true;
                showDirtyBadge();
            }
        });
        form.addEventListener('change', () => {
            if (!formDirty) {
                formDirty = true;
                showDirtyBadge();
            }
        });
        form.addEventListener('submit', () => {
            formDirty = false;
            removeDirtyBadge();
            showSaveProgress();
        });
    });

    window.addEventListener('beforeunload', (e) => {
        if (formDirty) {
            e.preventDefault();
            e.returnValue = 'Des modifications non sauvegardées seront perdues.';
            return e.returnValue;
        }
    });
}

function showDirtyBadge() {
    if (dirtyBadge) return;
    const header = document.querySelector('.header-actions');
    if (!header) return;
    
    dirtyBadge = document.createElement('div');
    dirtyBadge.className = 'dirty-badge';
    dirtyBadge.textContent = 'Non sauvegardé';
    header.appendChild(dirtyBadge);
}

function removeDirtyBadge() {
    if (dirtyBadge) {
        dirtyBadge.remove();
        dirtyBadge = null;
    }
}

// --- Save Progress Bar ---
function showSaveProgress() {
    const bar = document.getElementById('save-progress');
    if (bar) {
        bar.classList.add('active');
        setTimeout(() => bar.classList.remove('active'), 2000);
    }
}

// --- Image Preview Update ---
function setupImagePreviewListeners() {
    document.querySelectorAll('input[name="image"], input[name^="galerie_path"]').forEach(input => {
        input.addEventListener('input', function() {
            const parent = this.closest('.form-group') || this.closest('.gallery-item') || this.parentElement;
            if (parent) {
                const preview = parent.querySelector('img');
                if (preview && this.value) {
                    const baseUrl = document.querySelector('meta[name="assets-base"]')?.content || '../public/';
                    preview.src = baseUrl + this.value;
                }
            }
        });
    });
}

// --- Mobile Sidebar Toggle ---
function setupSidebarToggle() {
    const sidebar = document.getElementById('admin-sidebar');
    const openBtn = document.getElementById('sidebar-open');
    const closeBtn = document.getElementById('sidebar-close');

    if (openBtn && sidebar) {
        openBtn.addEventListener('click', () => {
            sidebar.classList.add('sidebar-open');
            document.body.classList.add('sidebar-overlay-active');
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('sidebar-open');
            document.body.classList.remove('sidebar-overlay-active');
        });
    }

    // Close on overlay click
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('sidebar-overlay-active') || 
            (document.body.classList.contains('sidebar-overlay-active') && 
             !sidebar.contains(e.target) && e.target !== openBtn)) {
            sidebar.classList.remove('sidebar-open');
            document.body.classList.remove('sidebar-overlay-active');
        }
    });
}

// --- Keyboard Shortcuts ---
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Ctrl+S or Cmd+S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                formDirty = false;
                removeDirtyBadge();
                showSaveProgress();
                form.submit();
            }
        }
    });
}

// --- Count-up Animation ---
function animateCountUp() {
    const values = document.querySelectorAll('.kpi-value[data-target]');
    values.forEach(el => {
        const target = parseInt(el.dataset.target) || 0;
        if (target === 0) return;
        
        const duration = 1200;
        const start = performance.now();
        const startVal = 0;
        
        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            // Ease-out curve
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(startVal + (target - startVal) * eased);
            el.textContent = current.toLocaleString('fr-FR');
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    });
}

// --- Staggered card animations ---
function animateCardsOnLoad() {
    const cards = document.querySelectorAll('.card, .kpi-card, .quick-action-btn');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(12px)';
        card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 60 + i * 50);
    });
}

// --- Auto-show toast for URL status param ---
function checkStatusParam() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (status === 'saved') {
        showToast('Modifications enregistrées avec succès !');
        const url = new URL(window.location);
        url.searchParams.delete('status');
        window.history.replaceState({}, '', url);
    } else if (status === 'deleted') {
        showToast('Élément supprimé avec succès.');
        const url = new URL(window.location);
        url.searchParams.delete('status');
        window.history.replaceState({}, '', url);
    }
}

// --- Init on DOM Ready ---
document.addEventListener('DOMContentLoaded', () => {
    trackFormChanges();
    setupImagePreviewListeners();
    setupSidebarToggle();
    setupKeyboardShortcuts();
    checkStatusParam();
    animateCountUp();
    animateCardsOnLoad();
});
