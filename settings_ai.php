<?php
require_once 'auth.php';
require_once 'config.php';
check_auth();

// Charger la config existante
$config_file = __DIR__ . '/data/ai_config.json';
$ai_config = ['api_key' => '', 'selected_model' => ''];
if (file_exists($config_file)) {
    $ai_config = json_decode(file_get_contents($config_file), true);
}

$page_title = "Configuration Intelligence Artificielle";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php 
        $active_page = 'ai';
        require_once 'sidebar.php'; 
        ?>

        <main class="main-content">
            <header class="header-actions">
                <div>
                    <h1>🤖 Configuration IA (Gemini)</h1>
                    <p>Gérez votre connexion avec l'IA pour automatiser votre portfolio.</p>
                </div>
            </header>

            <div class="card" style="margin-bottom: 2rem;">
                <h3>🔑 Clé d'API Gemini</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                    Vous pouvez obtenir une clé gratuite sur le <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: var(--accent-color);">Google AI Studio</a>.
                </p>
                
                <div class="form-group">
                    <label for="api-key">Clé d'API Google Gemini</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="password" id="api-key" value="<?php echo htmlspecialchars($ai_config['api_key']); ?>" placeholder="AIzaSy..." style="flex: 1;">
                        <button type="button" class="btn btn-primary" onclick="testAndFetchModels()">Scanner les Modèles</button>
                    </div>
                </div>
            </div>

            <div id="models-section" class="card" style="display: none; margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3>🧠 Modèles Disponibles</h3>
                    <span id="model-count" class="tag">0 modèles</span>
                </div>
                
                <div id="models-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                    <!-- Les modèles seront injectés ici -->
                </div>
            </div>

            <div id="save-section" style="display: none; text-align: right; margin-bottom: 3rem;">
                <p id="save-status" style="display: inline-block; margin-right: 15px; font-weight: bold;"></p>
                <button type="button" class="btn btn-primary" onclick="saveAIConfig()" style="padding: 1rem 2rem; font-size: 1rem;">✅ Valider et Utiliser ce modèle</button>
            </div>
        </main>
    </div>

<style>
    .model-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .model-card:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: var(--accent-color);
        transform: translateY(-2px);
    }
    .model-card.selected {
        background: rgba(var(--accent-rgb), 0.1);
        border-color: var(--accent-color);
        box-shadow: 0 0 15px rgba(var(--accent-rgb), 0.2);
    }
    .model-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .model-name {
        font-weight: 800;
        font-size: 1.1rem;
        color: #fff;
    }
    .model-role {
        font-size: 0.75rem;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 6px;
        background: var(--accent-color);
        color: #000;
    }
    .model-desc {
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.5;
        flex-grow: 1;
    }
    .model-meta {
        font-size: 0.75rem;
        border-top: 1px solid rgba(255,255,255,0.05);
        padding-top: 10px;
        display: flex;
        justify-content: space-between;
        opacity: 0.7;
    }
</style>

<script>
let selectedModelId = "<?php echo $ai_config['selected_model']; ?>";

async function testAndFetchModels() {
    const apiKey = document.getElementById('api-key').value.trim();
    if (!apiKey) {
        alert("Veuillez entrer une clé API.");
        return;
    }

    const btn = event.currentTarget || event.target;
    const originalText = btn.textContent;
    btn.textContent = "Scan en cours...";
    btn.disabled = true;

    try {
        const response = await fetch(`api_ai.php?action=fetch_models&api_key=${encodeURIComponent(apiKey)}`);
        const data = await response.json();

        if (data.status === 'success') {
            displayModels(data.models);
            document.getElementById('models-section').style.display = 'block';
            document.getElementById('save-section').style.display = 'block';
        } else {
            alert("Erreur : " + data.message);
        }
    } catch (e) {
        console.error(e);
        alert("Erreur lors de la communication avec l'API.");
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

function displayModels(models) {
    const container = document.getElementById('models-list');
    const countSpan = document.getElementById('model-count');
    container.innerHTML = '';
    countSpan.textContent = `${models.length} modèles détectés`;

    models.forEach(model => {
        const isSelected = model.id === selectedModelId;
        const div = document.createElement('div');
        div.className = `model-card ${isSelected ? 'selected' : ''}`;
        div.onclick = () => selectModel(model.id, div);
        
        div.innerHTML = `
            <div class="model-header">
                <span class="model-name">${model.displayName}</span>
                <span class="model-role">${model.role}</span>
            </div>
            <p class="model-desc">${model.description}</p>
            <div class="model-meta">
                <span>⚖️ Poids: <strong>${model.weight}</strong></span>
                <span>🎟️ Tokens: <strong>${model.inputTokenLimit}</strong></span>
            </div>
        `;
        container.appendChild(div);
    });
}

function selectModel(id, element) {
    selectedModelId = id;
    document.querySelectorAll('.model-card').forEach(c => c.classList.remove('selected'));
    element.classList.add('selected');
}

async function saveAIConfig() {
    if (!selectedModelId) {
        alert("Veuillez sélectionner un modèle avant de valider.");
        return;
    }

    const apiKey = document.getElementById('api-key').value.trim();
    const status = document.getElementById('save-status');
    status.textContent = "Enregistrement...";
    status.style.color = "var(--accent-color)";

    try {
        const response = await fetch('api_ai.php?action=save_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                api_key: apiKey,
                selected_model: selectedModelId
            })
        });
        const data = await response.json();

        if (data.status === 'success') {
            status.textContent = "✨ Configuration enregistrée avec succès !";
            status.style.color = "var(--success-color)";
            setTimeout(() => { status.textContent = ""; }, 3000);
        } else {
            alert("Erreur lors de l'enregistrement.");
        }
    } catch (e) {
        alert("Erreur serveur.");
    }
}

window.onload = () => {
    if (document.getElementById('api-key').value) {
        testAndFetchModels();
    }
};

async function magicWand(btn, field) {
    const container = btn.closest('.form-group');
    const textarea = container.querySelector('textarea');
    const originalText = textarea.value.trim();

    if (!originalText) {
        alert("Écris d'abord quelques notes brutes pour que l'IA puisse travailler !");
        return;
    }

    const originalBtnText = btn.innerHTML;
    btn.innerHTML = '✨ Génération...';
    btn.disabled = true;

    try {
        const response = await fetch(`api_ai.php?action=generate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ field: field, prompt: originalText })
        });
        const data = await response.json();

        if (data.status === 'success') {
            // Un petit nettoyage final au cas où (retrait des * ou # éventuels)
            let cleanText = data.generated_text.replace(/[\*#_]/g, '');
            textarea.value = cleanText;
            textarea.style.border = "1px solid var(--success-color)";
            setTimeout(() => { textarea.style.border = ""; }, 2000);
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
</script>
</body>
</html>
