# 📊 Panel Admin pour Portfolio Web (Sans SQL)

<p align="center">
  <img src="https://img.shields.io/badge/PHP-%3E%3D%207.4-8892BF.svg?style=for-the-badge&logo=php" alt="Version PHP" />
  <img src="https://img.shields.io/badge/Version-2.0-blue.svg?style=for-the-badge" alt="Version du Panel" />
  <img src="https://img.shields.io/badge/Base%20de%20donn%C3%A9es-JSON%20(Sans%20SQL)-orange.svg?style=for-the-badge&logo=json" alt="Base de données JSON" />
  <img src="https://img.shields.io/badge/Cr%C3%A9ateur-Paul%20C.-pink.svg?style=for-the-badge" alt="Créateur" />
</p>

---

Un panel d'administration moderne, sécurisé, fluide et **sans base de données relationnelle (NoSQL / JSON)** conçu spécifiquement pour les portfolios de créatifs, designers et développeurs. Toutes les données (projets, compétences, timeline, socials, SEO) sont centralisées dans un unique fichier `data.json`.

---

## 🌟 Fonctionnalités principales

*   **Tableau de bord complet** : Indicateurs clés (vues, visiteurs uniques, projets, médias) et raccourcis d'actions.
*   **Gestion de projets avancée** : Système CRUD avec réorganisation de l'ordre d'affichage par glisser-déposer.
*   **Dossier académique flexible** : Composez des fiches de projets universitaires ou pro sur-mesure à l'aide de blocs prédéfinis (images côte à côte, moodboards, blocs de code, curseurs de comparaison avant/après).
*   **Assistant IA Gemini intégré** : Auto-génération et optimisation de textes (briefs, concepts, titres SEO, méta-descriptions et posts pour les réseaux sociaux) directement depuis les pages de création.
*   **Médiathèque intelligente** : Drag & drop de fichiers (images et vidéos), compression modulable, conversion automatique en format WebP léger, et génération automatique de miniatures.
*   **Statistiques sans cookies** : Tracking éthique du trafic (pages vues, clics de boutons externes, terminaux de connexion, sources référentes).
*   **SEO & Partage** : Générateur automatique de `sitemap.xml` et script d'image OpenGraph (`og_image.php`) générée à la volée.
*   **Système de sauvegarde** : Exportation de projet en ZIP/JSON et sauvegarde globale du site en un clic.

---

## ⚙️ Installation & Configuration rapide

<details>
<summary><b>📖 Cliquez pour dérouler les étapes d'installation</b></summary>

### 1. Prérequis
*   Un hébergeur ou serveur local avec **PHP 7.4 ou supérieur**.
*   L'extension PHP **GD** activée (requis pour les miniatures d'images et les cartes OpenGraph).
*   Droits d'écriture sur le dossier d'installation (pour l'écriture du fichier `data.json` et la création du dossier `images/`).

### 2. Déploiement
1. Clonez ou téléchargez ce dossier (nommez-le par exemple `gestion_interne` ou `admin`) et placez-le dans le dossier racine de votre site.
2. Pour des raisons de sécurité, assurez-vous que les fichiers sensibles sont ignorés par Git. Le fichier `.gitignore` fourni à la racine de ce dossier exclut automatiquement :
    *   `credentials.php` (votre mot de passe hashé)
    *   `data/ai_config.json` (votre clé API Gemini)
    *   `data/stats.json` (vos statistiques de visites locales)
    *   Vos fichiers `data.json` et dossier `images/` de travail local.

### 3. Paramétrage (`config.php`)
Ouvrez `config.php` et ajustez les variables :
*   **`PORTFOLIO_OWNER`** : Votre nom (ex: `"Paul C."`).
*   **`PORTFOLIO_URL`** : Lien relatif ou absolu vers votre portfolio (ex: `"../"`).
*   **`ALLOWED_ORIGINS`** : Liste des domaines CORS autorisés à envoyer des statistiques (ex: `http://localhost:5173` pour React, `https://monportfolio.com` pour la production).
*   **`$dev_path`** : Si vous développez avec React, spécifiez le dossier public de vos sources (ex: `../public/`) pour que le panel y écrive en direct.

### 4. Premier Login
1. Accédez à `https://votre-site.com/gestion_interne/login.php`.
2. Connectez-vous avec le mot de passe par défaut : **`admin123`**.
3. Allez dans l'onglet **Sécurité** pour modifier immédiatement votre mot de passe administrateur (ceci va générer le fichier sécurisé `credentials.php`).
</details>

---

## 💻 Guides d'intégration Frontend

Déroulez l'option de votre choix selon la technologie utilisée pour votre portfolio :

<details>
<summary><b>⚛️ Option A : Intégration dans un site React (Single Page App)</b></summary>

### 1. Lire et afficher les données du site (`data.json`)
Puisque vos données sont stockées dans un fichier JSON statique à la racine de votre dossier public, chargez-les simplement via `fetch` :

```jsx
import React, { useEffect, useState } from 'react';

function Portfolio() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch('/data.json')
      .then(res => res.json())
      .then(json => {
        // Optionnel : Trier les projets par score d'importance (10 à 1)
        if (json.projets) {
          json.projets.sort((a, b) => (b.importance || 5) - (a.importance || 5));
        }
        setData(json);
        setLoading(false);
      })
      .catch(err => {
        console.error("Erreur lors du chargement des données", err);
        setLoading(false);
      });
  }, []);

  if (loading) return <div>Chargement...</div>;
  if (!data) return <div>Aucune donnée trouvée.</div>;

  return (
    <div>
      {/* Affichage du Portrait */}
      <section className="hero">
        <h1>{data.portrait.titre}</h1>
        <p>{data.portrait.paragraphe1}</p>
        <img src={`/${data.portrait.image}`} alt="Avatar" />
      </section>

      {/* Grille des Projets */}
      <section className="projects-grid">
        {data.projets
          .filter(p => p.status === 'published')
          .map(projet => (
            <div key={projet.id} className="project-card">
              <img src={`/${projet.image}`} alt={projet.alt} />
              <h3>{projet.titre}</h3>
              <p>{projet.sousTitre}</p>
              <div className="tags">
                {projet.tags.map(t => <span key={t}>{t}</span>)}
              </div>
            </div>
          ))}
      </section>
    </div>
  );
}
```

### 2. Suivi de visites / Analytics (`track.php`)
Créez un module d'analytics Javascript pour appeler le script de tracking à chaque changement de page ou événement de clic :

```javascript
// analytics.js
const ADMIN_URL = '/gestion_interne'; // Chemin vers le dossier admin

// Tracker une page vue
export function trackPageView(path) {
  fetch(`${ADMIN_URL}/track.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'pageview',
      path: path,
      referrer: document.referrer,
      screenWidth: window.innerWidth
    })
  }).catch(() => {});
}

// Tracker une vue projet spécifique
export function trackProjectView(projectId, path) {
  fetch(`${ADMIN_URL}/track.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'projectview',
      id: projectId,
      path: path,
      screenWidth: window.innerWidth
    })
  }).catch(() => {});
}

// Tracker un clic sur un lien externe (CV, réseaux sociaux, etc.)
export function trackExternalClick(linkId) {
  fetch(`${ADMIN_URL}/track.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'click',
      id: linkId
    })
  }).catch(() => {});
}
```

Déclenchez ensuite ces fonctions lors du routage de votre SPA (par exemple avec `useEffect` de React Router) :

```jsx
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { trackPageView } from './analytics';

function RouteTracker() {
  const location = useLocation();

  useEffect(() => {
    if (!location.pathname.startsWith('/projet/')) {
      trackPageView(location.pathname);
    }
  }, [location]);

  return null;
}
```
</details>

<details>
<summary><b>🐘 Option B : Intégration dans un site PHP / HTML / CSS Classique</b></summary>

### 1. Lire et afficher les données en PHP
Le fichier JSON peut être lu côté serveur avant l'envoi du HTML :

```php
<?php
$data_file_path = __DIR__ . '/data.json';
$data = [];

if (file_exists($data_file_path)) {
    $json_content = file_get_contents($data_file_path);
    $data = json_decode($json_content, true) ?: [];
}

$projets = isset($data['projets']) ? $data['projets'] : [];

// Trier par importance
usort($projets, function($a, $b) {
    $impA = isset($a['importance']) ? (int)$a['importance'] : 5;
    $impB = isset($b['importance']) ? (int)$b['importance'] : 5;
    return $impB - $impA;
});
?>
```

Affichez les variables directement dans vos pages :

```html
<section class="projects-grid">
    <?php foreach ($projets as $projet): ?>
        <?php if (($projet['status'] ?? 'published') !== 'published') continue; ?>
        <div class="project-card">
            <img src="<?php echo htmlspecialchars($projet['image']); ?>" alt="">
            <h3><?php echo htmlspecialchars($projet['titre']); ?></h3>
            <p><?php echo htmlspecialchars($projet['sousTitre']); ?></p>
            <a href="projet.php?slug=<?php echo $projet['projetId']; ?>">Découvrir</a>
        </div>
    <?php endforeach; ?>
</section>
```

### 2. Suivi de visites en JavaScript
Ajoutez ce script asynchrone avant la fermeture de la balise `</body>` de vos pages :

```html
<script>
const adminFolder = 'gestion_interne';
const trackingUrl = `${window.location.origin}/${adminFolder}/track.php`;

// 1. Envoi page vue
fetch(trackingUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'pageview',
        path: window.location.pathname,
        referrer: document.referrer,
        screenWidth: window.innerWidth
    })
}).catch(e => console.error(e));

// 2. Événement clic externe
function trackClick(elementId) {
    fetch(trackingUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'click', id: elementId })
    }).catch(() => {});
}
</script>
```
</details>

---

## 🛠️ Rendu et affichage des composants complexes

Déroulez pour accéder aux structures de rendu de chaque type de contenu spécifique :

<details>
<summary><b>📝 1. Dossier Académique (Blocs Composés de Projets)</b></summary>

Le dossier composé est un tableau de blocs ayant chacun un format de grille spécifique (`type`).

#### Rendu React dynamique
```jsx
function ProjetDossier({ dossier }) {
  if (!dossier || dossier.length === 0) return null;

  return (
    <div className="project-dossier">
      {dossier.map((bloc, index) => {
        const { type, titre, texte, image1, alt1, legende1, image2, alt2, legende2, accent } = bloc;
        
        switch (type) {
          case 'text':
          case 'consigne':
          case 'brief':
          case 'concept':
          case 'defis':
          case 'resultats':
            return (
              <div key={index} className={`block-text block-${type}`} style={{ borderColor: accent }}>
                {titre && <h2>{titre}</h2>}
                <div className="text-content">{texte}</div>
              </div>
            );
            
          case 'text_image':
            return (
              <div key={index} className="block-grid block-text-image">
                <div className="col-text">
                  {titre && <h2>{titre}</h2>}
                  <p>{texte}</p>
                </div>
                <div className="col-media">
                  <img src={`/${image1}`} alt={alt1} />
                  {legende1 && <p className="caption">{legende1}</p>}
                </div>
              </div>
            );

          case 'image_text':
            return (
              <div key={index} className="block-grid block-image-text">
                <div className="col-media">
                  <img src={`/${image1}`} alt={alt1} />
                  {legende1 && <p className="caption">{legende1}</p>}
                </div>
                <div className="col-text">
                  {titre && <h2>{titre}</h2>}
                  <p>{texte}</p>
                </div>
              </div>
            );

          case 'full_image':
            return (
              <div key={index} className="block-full-width">
                {titre && <h2>{titre}</h2>}
                <img src={`/${image1}`} alt={alt1} />
                {legende1 && <p className="caption">{legende1}</p>}
              </div>
            );

          case 'two_images':
            return (
              <div key={index} className="block-grid block-two-images">
                <div className="col-media">
                  <img src={`/${image1}`} alt={alt1} />
                  {legende1 && <p className="caption">{legende1}</p>}
                </div>
                <div className="col-media">
                  <img src={`/${image2}`} alt={alt2} />
                  {legende2 && <p className="caption">{legende2}</p>}
                </div>
              </div>
            );

          case 'code':
            return (
              <div key={index} className="block-code" style={{ accentColor: accent }}>
                {titre && <h2>{titre}</h2>}
                <pre><code>{texte}</code></pre>
                {legende1 && <p className="caption">{legende1}</p>}
              </div>
            );

          case 'before_after':
            return (
              <div key={index} className="block-before-after">
                {titre && <h2>{titre}</h2>}
                <div className="slider-wrapper">
                  <img src={`/${image1}`} className="img-before" alt={alt1} />
                  <img src={`/${image2}`} className="img-after" alt={alt2} />
                </div>
              </div>
            );

          default:
            return null;
        }
      })}
    </div>
  );
}
```
</details>

<details>
<summary><b>🖼️ 2. Galerie Multimédia & Vidéos</b></summary>

Chaque projet possède un tableau `galerie` formaté comme suit : `[chemin, legende, type]`. Le `type` peut être `image` ou `video`. Si le projet possède un `youtubeId`, vous pouvez directement intégrer un player iframe.

#### Composant React
```jsx
function ProjetGalerie({ galerie, youtubeId }) {
  return (
    <div className="project-gallery">
      {/* Vidéo YouTube */}
      {youtubeId && (
        <div className="video-container">
          <iframe
            src={`https://www.youtube.com/embed/${youtubeId}`}
            title="Présentation vidéo"
            allowFullScreen
          ></iframe>
        </div>
      )}

      {/* Galerie d'images et vidéos locales */}
      <div className="gallery-grid">
        {galerie.map((item, index) => {
          const [path, caption, type] = item;
          return (
            <div key={index} className={`gallery-item ${type}`}>
              {type === 'video' ? (
                <video src={`/${path}`} controls />
              ) : (
                <img src={`/${path}`} alt={caption || 'Visuel'} />
              )}
              {caption && <p className="caption">{caption}</p>}
            </div>
          );
        })}
      </div>
    </div>
  );
}
```
</details>

<details>
<summary><b>📅 3. Parcours Chronologique (Timeline)</b></summary>

Affichage vertical des étapes de formation ou d'expérience stockées dans `data.parcours`.

#### Rendu PHP / HTML
```php
<?php if (!empty($data['parcours'])): ?>
    <div class="timeline">
        <?php foreach ($data['parcours'] as $etape): ?>
            <div class="timeline-step">
                <div class="year"><?php echo htmlspecialchars($etape['annee']); ?></div>
                <div class="details">
                    <h3><?php echo htmlspecialchars($etape['titre']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($etape['description'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```
</details>

<details>
<summary><b>⚡ 4. Compétences & Outils (Logos)</b></summary>

Rendu des soft/hard skills et affichage des outils maîtrisés sous forme de grille de logos.

#### Composant React
```jsx
function SkillsList({ skillsHumaines, skillsTechniques, logos }) {
  return (
    <div className="skills-section">
      <h2>Soft Skills</h2>
      <ul className="soft-skills">
        {skillsHumaines.map((s, i) => (
          <li key={i}>
            <span>{s.icone}</span> <strong>{s.titre}</strong>: {s.description}
          </li>
        ))}
      </ul>

      <h2>Outils & Logiciels</h2>
      <div className="logos-grid">
        {logos.map((logo, i) => (
          <div key={i} className={`logo-card ${logo.classe}`} title={logo.titre}>
            <img src={`/${logo.chemin}`} alt={logo.alt} />
            <span>{logo.titre}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
```
</details>

<details>
<summary><b>🔍 5. Image de Partage Dynamique (Open Graph)</b></summary>

Associez l'image Open Graph dynamique en injectant ce bloc dans la balise `<head>` de vos pages de détails de projets :

```html
<meta property="og:title" content="<?php echo htmlspecialchars($projet['titre']); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($projet['seo_description'] ?? $projet['sousTitre']); ?>" />
<meta property="og:image" content="https://monportfolio.com/gestion_interne/og_image.php?slug=<?php echo $projet['projetId']; ?>" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:image" content="https://monportfolio.com/gestion_interne/og_image.php?slug=<?php echo $projet['projetId']; ?>" />
```
</details>

---

## 🖼️ Gestion des médias & Miniature optimisée

1.  **Miniature automatique** : Lors de l'upload d'une image, le panel génère une version ultra-légère dans un sous-dossier caché `.thumbs/`. Utilisez-la pour charger rapidement votre grille de projets (ex: `images/.thumbs/couverture.webp` au lieu de `images/couverture.webp`).
2.  **Conversion WebP** : Le module d'upload convertit automatiquement les fichiers PNG et JPG en WebP (avec compression configurable) pour améliorer les performances de votre site.

---

## ✍️ Crédits & Créateur

Ce panel d'administration pour portfolio sans base de données a été imaginé, conçu et entièrement développé par **Paul Chéhère Le Lann**.

*   **Portfolio** : [paul-c.fr](https://paul-c.fr)
*   **GitHub** : [@iampaulc](https://github.com/iampaulc)
*   **Instagram** : [@iampaulc_](https://instagram.com/iampaulc_)

*N'hésitez pas à mentionner ce crédit ou à laisser une étoile ⭐ sur le dépôt GitHub si ce projet vous a aidé dans la gestion de votre portfolio !*
