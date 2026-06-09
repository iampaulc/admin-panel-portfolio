<!-- media_modal.php -->
<div id="media-modal" class="modal-overlay" style="display:none; z-index: 9999;">
    <div class="modal-content glass-panel" style="width: 95%; max-width: 1100px; height: 90vh; display: flex; flex-direction: column; overflow: hidden; position: relative;">
        <div style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-family: var(--font-title);">Médiathèque (Sélecteur de Médias)</h2>
            <button type="button" onclick="closeMediaModal()" class="btn-ctrl btn-remove" style="background: none; font-size: 1.5rem; padding: 0.2rem 1rem;" aria-label="Fermer">✕</button>
        </div>
        
        <div style="flex-shrink: 0; display: flex; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;" class="upload-zone-modal">
            <div style="flex: 1; border: 2px dashed rgba(88, 166, 255, 0.4); border-radius: 8px; padding: 1rem; text-align: center; position: relative; background: rgba(88, 166, 255, 0.05); transition: 0.2s;" id="modal-drop-zone">
                <p style="margin: 0; font-weight: 500;">📥 Glissez une image ici ou cliquez pour l'uploader</p>
                <input type="file" id="modal-file-upload" accept="image/*" style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;" aria-label="Sélectionner un fichier image">
            </div>
            
            <div style="width: 200px;">
                <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Taux de compression</label>
                <select id="modal-compression-level" style="width: 100%; padding: 0.6rem; background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); border-radius: 4px; color: #fff; cursor: pointer; font-size: 0.85rem;">
                    <option value="medium" selected>Moyenne (Recommandée)</option>
                    <option value="extreme">Extrême (Compression Max, Fichier ultra-léger)</option>
                    <option value="high">Forte (Grosse compression)</option>
                    <option value="low">Légère (Qualité 4K/Haute)</option>
                    <option value="none">Aucune (Fichier original brut)</option>
                </select>
            </div>
            
            <div style="width: 200px;">
                <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Dossier cible (Auto si projet)</label>
                <input type="text" id="modal-folder-name" placeholder="Ex: p1" style="width: 100%; padding: 0.6rem; background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); border-radius: 4px; color: #fff; font-size: 0.85rem;">
            </div>
        </div>

        <!-- Barre de recherche -->
        <div style="flex-shrink: 0; margin-bottom: 1rem;">
            <input type="text" id="modal-search" placeholder="🔍 Rechercher une image..." oninput="filterModalMedia(this.value)" style="width: 100%; padding: 0.6rem 1rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 6px; color: #fff;">
        </div>

        <!-- Grille des médias avec scroll interne -->
        <div style="flex: 1; overflow-y: auto; overflow-x: hidden; min-height: 0; display: flex; flex-direction: column;" id="modal-gallery-wrapper">
            <div id="modal-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1.2rem; padding: 0.5rem 1rem 1rem 0.5rem;">
                <!-- Chargé dynamiquement via JS -->
            </div>
            
            <div id="modal-load-more-sentinel" style="text-align: center; padding: 1.5rem; color: var(--text-muted); cursor: pointer; font-weight: bold; border: 1px dashed var(--border-color); border-radius: 8px; margin: 0 1.2rem 1.5rem; background: rgba(255,255,255,0.01); display: none;" onclick="renderNextModalBatch()">
                📥 Défiler ou cliquer pour charger plus...
            </div>
        </div>
    </div>
</div>

<!-- Modal Lightbox de Preview Grand Format intégrée -->
<div id="modal-preview-lightbox" class="modal-overlay" style="display:none; z-index: 100000;" onclick="closeModalPreviewLightbox()">
    <div class="modal-content card glass-panel" style="width: 85%; max-width: 800px; max-height: 85vh; text-align: center; border: 1px solid var(--border-color); padding: 1.5rem; position: relative; display: flex; flex-direction: column;" onclick="event.stopPropagation()">
        <button type="button" onclick="closeModalPreviewLightbox()" class="btn-ctrl btn-remove" style="position: absolute; top: 1rem; right: 1rem; background: none; font-size: 1.5rem; padding: 0.2rem 1rem;" aria-label="Fermer">✕</button>
        <h3 id="modal-lightbox-title" style="margin-top: 0; margin-bottom: 1rem; font-family: var(--font-title); font-size: 1.25rem;">Aperçu</h3>
        
        <div id="modal-lightbox-media-container" style="flex: 1; display: flex; justify-content: center; align-items: center; min-height: 0; background: #070708; border-radius: 8px; border: 1px solid var(--border-color); padding: 1rem;">
            <!-- Rendu de l'image -->
        </div>
        
        <div id="modal-lightbox-meta" style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary); display: flex; justify-content: space-between; align-items: center;">
            <!-- Méta -->
        </div>
    </div>
</div>

<style>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(8px); }
.modal-gallery-item { 
    cursor: pointer; 
    border: 2px solid transparent; 
    border-radius: 8px; 
    overflow: hidden; 
    position: relative; 
    background: rgba(255,255,255,0.02); 
    transition: all 0.2s; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.3); 
    height: 140px; 
    display: flex; 
    flex-direction: column; 
    content-visibility: auto;
    contain-intrinsic-size: 140px;
}
.modal-gallery-item:hover { border-color: var(--accent-color); transform: translateY(-3px); box-shadow: 0 6px 16px rgba(88, 166, 255, 0.2); }
.modal-gallery-item img, .modal-gallery-item video { width: 100%; height: 110px; min-height: 110px; object-fit: contain; padding: 6px; background: rgba(255,255,255,0.03); flex-shrink: 0; }
.modal-gallery-item p { background: rgba(0,0,0,0.85); width: 100%; font-size: 0.75rem; text-align: center; padding: 6px 4px; margin: 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; border-top: 1px solid rgba(255,255,255,0.1); flex: 1; display: flex; align-items: center; justify-content: center; }

/* Bouton supprimer */
.modal-gallery-item .delete-btn { position: absolute; top: 6px; right: 6px; background: var(--danger-color); color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 0.75rem; display: none; align-items: center; justify-content: center; z-index: 10; transition: background 0.2s; }
.modal-gallery-item:hover .delete-btn { display: flex; }
.modal-gallery-item .delete-btn:hover { background: #ff7b72; }

/* Bouton Loupe pour preview dans la modal */
.modal-gallery-item .modal-preview-btn { position: absolute; top: 6px; left: 6px; background: var(--accent-color); color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 0.75rem; display: none; align-items: center; justify-content: center; z-index: 10; transition: background 0.2s; }
.modal-gallery-item:hover .modal-preview-btn { display: flex; }

#modal-drop-zone:hover { background: rgba(88, 166, 255, 0.12); border-color: var(--accent-color); }

/* Custom scrollbar for the modal */
#modal-gallery-wrapper::-webkit-scrollbar { width: 8px; }
#modal-gallery-wrapper::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 4px; }
#modal-gallery-wrapper::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
#modal-gallery-wrapper::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }
</style>

<script>
let currentMediaInput = null;
let allModalImages = []; // Cache pour la recherche et le progressive loading
let modalCurrentIndex = 0;
const MODAL_BATCH_SIZE = 40;
let modalObserver = null;

function openMediaModal(inputTarget, defaultFolder = '') {
    currentMediaInput = inputTarget;
    document.getElementById('modal-folder-name').value = defaultFolder;
    document.getElementById('modal-search').value = '';
    document.getElementById('modal-compression-level').value = 'medium';
    document.getElementById('media-modal').style.display = 'flex';
    loadModalMedia();
}

function closeMediaModal() {
    document.getElementById('media-modal').style.display = 'none';
    currentMediaInput = null;
    if (modalObserver) {
        modalObserver.disconnect();
        modalObserver = null;
    }
}

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('modal-preview-lightbox').style.display === 'flex') {
            closeModalPreviewLightbox();
            e.stopPropagation();
        } else if (document.getElementById('media-modal').style.display === 'flex') {
            closeMediaModal();
        }
    }
});

// Filtrer les images avec debounce
let modalSearchTimeout;
function filterModalMedia(query) {
    clearTimeout(modalSearchTimeout);
    modalSearchTimeout = setTimeout(() => {
        query = query.toLowerCase().trim();
        const gallery = document.getElementById('modal-gallery');
        const sentinel = document.getElementById('modal-load-more-sentinel');
        
        if (query === '') {
            gallery.innerHTML = '';
            modalCurrentIndex = 0;
            sentinel.style.display = '';
            renderNextModalBatch();
            return;
        }
        
        // Cacher sentinel et filtrer tout
        gallery.innerHTML = '';
        sentinel.style.display = 'none';
        
        const matches = allModalImages.filter(img => 
            img.name.toLowerCase().includes(query) || img.path.toLowerCase().includes(query)
        );
        
        if (matches.length === 0) {
            gallery.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">Aucun média correspondant.</div>';
            return;
        }
        
        matches.forEach(img => {
            renderModalItem(img, gallery);
        });
    }, 250);
}

async function deleteMediaFile(path, itemElement) {
    if (!confirm('Supprimer cette image du serveur ?')) return;
    try {
        const res = await fetch('api_media.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path: path })
        });
        const data = await res.json();
        if (data.success) {
            itemElement.remove();
            // Retirer de la liste en cache
            allModalImages = allModalImages.filter(img => img.path !== path);
        } else {
            alert('Erreur: ' + (data.error || 'Suppression impossible'));
        }
    } catch (e) {
        alert('Erreur réseau lors de la suppression');
    }
}

// Rendu progressif modal
function renderNextModalBatch() {
    const gallery = document.getElementById('modal-gallery');
    const batch = allModalImages.slice(modalCurrentIndex, modalCurrentIndex + MODAL_BATCH_SIZE);
    
    if (batch.length === 0) return;
    
    batch.forEach(img => {
        renderModalItem(img, gallery);
    });
    
    modalCurrentIndex += MODAL_BATCH_SIZE;
    
    const sentinel = document.getElementById('modal-load-more-sentinel');
    if (modalCurrentIndex >= allModalImages.length) {
        sentinel.style.display = 'none';
    } else {
        sentinel.style.display = '';
    }
}

function renderModalItem(img, container) {
    const div = document.createElement('div');
    div.className = 'modal-gallery-item';
    div.dataset.name = img.name;
    div.dataset.path = img.path;
    
    const ext = img.name.split('.').pop().toLowerCase();
    const isVideo = ['mp4', 'webm'].includes(ext);
    const originalUrl = img.thumb_url.replace('/.thumbs/', '/');
    
    // Rendu Image ou Video (Miniature ultra-légère)
    if (isVideo) {
        const videoEl = document.createElement('video');
        videoEl.src = img.thumb_url;
        videoEl.loading = 'lazy';
        videoEl.muted = true;
        videoEl.loop = true;
        videoEl.autoplay = true;
        videoEl.playsInline = true;
        div.appendChild(videoEl);
    } else {
        const imgEl = document.createElement('img');
        imgEl.loading = 'lazy';
        imgEl.src = img.thumb_url;
        imgEl.alt = img.name;
        div.appendChild(imgEl);
    }
    
    // Titre
    const nameEl = document.createElement('p');
    nameEl.textContent = img.name;
    nameEl.title = img.path;
    div.appendChild(nameEl);

    // Bouton de suppression
    const delBtn = document.createElement('button');
    delBtn.className = 'delete-btn';
    delBtn.textContent = '✕';
    delBtn.title = 'Supprimer';
    delBtn.setAttribute('aria-label', 'Supprimer ' + img.name);
    delBtn.onclick = (e) => { e.stopPropagation(); deleteMediaFile(img.path, div); };
    div.appendChild(delBtn);
    
    // Bouton Loupe pour preview
    const previewBtn = document.createElement('button');
    previewBtn.className = 'modal-preview-btn';
    previewBtn.textContent = '🔍';
    previewBtn.title = 'Aperçu grand format';
    previewBtn.onclick = (e) => {
        e.stopPropagation();
        previewModalMedia(originalUrl, img.name, ext, Math.round(img.size / 1024) + ' KB');
    };
    div.appendChild(previewBtn);
    
    // Clic pour sélectionner
    div.onclick = () => {
        if (currentMediaInput) {
            currentMediaInput.value = img.path;
            const parent = currentMediaInput.closest('.form-group') || currentMediaInput.parentElement;
            if (parent) {
                const preview = parent.querySelector('img.preview-img');
                if (preview) preview.src = img.thumb_url; // utilise la miniature pour l'aperçu admin pour accélérer la page !
            }
            currentMediaInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        closeMediaModal();
    };
    
    container.appendChild(div);
}

// Charger les médias modal
async function loadModalMedia() {
    const gallery = document.getElementById('modal-gallery');
    const sentinel = document.getElementById('modal-load-more-sentinel');
    sentinel.style.display = 'none';
    gallery.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">Chargement des images...</div>';
    
    try {
        const res = await fetch('api_media.php');
        const images = await res.json();
        allModalImages = images;
        gallery.innerHTML = '';
        modalCurrentIndex = 0;
        
        if (images.length === 0) {
            gallery.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">La médiathèque est vide.</div>';
            return;
        }
        
        // Lancer la première vague
        renderNextModalBatch();
        
        // Mettre en place l'IntersectionObserver pour la modal
        const wrapper = document.getElementById('modal-gallery-wrapper');
        if (sentinel && wrapper && 'IntersectionObserver' in window) {
            if (modalObserver) modalObserver.disconnect();
            
            modalObserver = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && modalCurrentIndex < allModalImages.length) {
                    renderNextModalBatch();
                }
            }, { root: wrapper, rootMargin: '200px' });
            modalObserver.observe(sentinel);
        }
    } catch (e) {
        gallery.innerHTML = '<div style="color:var(--danger-color); grid-column: 1/-1; text-align: center;">Erreur lors du chargement des images.</div>';
    }
}

// Upload depuis la modal
document.getElementById('modal-file-upload').addEventListener('change', async function() {
    if (!this.files.length) return;
    const file = this.files[0];
    const folder = document.getElementById('modal-folder-name').value;
    const compression = document.getElementById('modal-compression-level').value;
    
    const formData = new FormData();
    formData.append('file', file);
    if (folder) formData.append('subfolder', folder);
    formData.append('compression', compression);

    const gallery = document.getElementById('modal-gallery');
    const sentinel = document.getElementById('modal-load-more-sentinel');
    sentinel.style.display = 'none';
    gallery.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem;">Upload et traitement en cours... ⏳</div>';

    try {
        const res = await fetch('api_media.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (currentMediaInput) {
                currentMediaInput.value = data.path;
                
                const parent = currentMediaInput.closest('.form-group') || currentMediaInput.parentElement;
                if (parent) {
                    const preview = parent.querySelector('img.preview-img');
                    if (preview) preview.src = data.thumb_url; // utilise la miniature légère
                }
                
                currentMediaInput.dispatchEvent(new Event('input', { bubbles: true }));
                closeMediaModal();
            } else {
                loadModalMedia();
            }
        } else {
            alert('Erreur: ' + data.error);
            loadModalMedia();
        }
    } catch (e) {
        alert('Erreur de réseau lors de l\'upload.');
        loadModalMedia();
    }
    this.value = '';
});

// Modal Preview Actions
function previewModalMedia(url, name, ext, sizeLabel) {
    const lightbox = document.getElementById('modal-preview-lightbox');
    const container = document.getElementById('modal-lightbox-media-container');
    const title = document.getElementById('modal-lightbox-title');
    const meta = document.getElementById('modal-lightbox-meta');
    
    title.textContent = name;
    container.innerHTML = '';
    
    if (ext === 'mp4' || ext === 'webm') {
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.autoplay = true;
        video.style.maxWidth = '100%';
        video.style.maxHeight = '60vh';
        container.appendChild(video);
    } else {
        const img = document.createElement('img');
        img.src = url;
        img.style.maxWidth = '100%';
        img.style.maxHeight = '60vh';
        img.style.objectFit = 'contain';
        container.appendChild(img);
    }
    
    meta.innerHTML = `
        <span style="font-weight: 500; font-size: 0.8rem; color: var(--text-secondary);">Poids Original : ${sizeLabel}</span>
        <a href="${url}" target="_blank" style="color: var(--accent-color); text-decoration: none; font-weight: 600; font-size: 0.8rem;">Ouvrir l'original ↗</a>
    `;
    lightbox.style.display = 'flex';
}

function closeModalPreviewLightbox() {
    document.getElementById('modal-preview-lightbox').style.display = 'none';
    document.getElementById('modal-lightbox-media-container').innerHTML = '';
}
</script>
