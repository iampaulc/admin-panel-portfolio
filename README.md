# 📊 Panel Admin pour Portfolio Web (Sans Base de Données)

Bienvenue dans ce Panel d'Administration universel et moderne conçu spécifiquement pour les portfolios de créatifs, designers et développeurs. 

Ce panel est entièrement **autonome, léger, ultra-rapide et n'a pas besoin de base de données SQL (MySQL/PostgreSQL)**. Toutes les données du site (projets, compétences, textes, réseaux sociaux, SEO) sont stockées dans un unique fichier `data.json`, ce qui facilite grandement le chargement, la sauvegarde et l'exportation.

---

## 🚀 Fonctionnalités du Panel

### 1. Tableau de Bord (`index.php`)
Un aperçu en temps réel des performances de votre site (Vues totales, visiteurs du jour, nombre de projets publiés, taille de la médiathèque) combiné à des raccourcis d'actions rapides et un tableau récapitulatif de vos projets.

### 2. Gestion des Projets (`edit_projects.php`)
- **CRUD Complet** : Création, modification, brouillon, archivage et suppression de projets.
- **Réorganisation par Drag & Drop** : Réorganisez l'ordre d'affichage de vos projets sur votre site en les glissant-déposant.
- **Dossier Académique (Blocs Composés)** : Permet de construire des pages projets complexes en combinant librement différents blocs :
  - Blocs texte ou de consignes encadrées.
  - Blocs mixtes (Texte + Image / Image + Texte).
  - Images pleine largeur, mood boards, grilles de deux images.
  - Blocs de code formaté et démonstrations interactives.
  - Curseurs dynamiques Avant / Après.
- **Galerie Multimédia** : Gestion d'images ordonnées avec légendes et vidéos (YouTube ou fichiers locaux).
- **Import/Export de Projet** : Exportez un projet spécifique sous forme de fichier JSON ou de dossier compressé ZIP (incluant ses images) pour le réimporter sur une autre instance.

### 3. Compétences & Logos (`edit_skills.php`, `edit_logos.php`)
- **Soft & Hard Skills** : Renseignez vos compétences avec des émojis/icônes et des descriptions courtes.
- **Médiathèque de Logos** : Ajoutez et organisez les logos de vos logiciels et langages maîtrisés (Figma, React, Photoshop, etc.) pour les associer ensuite à vos fiches projets.

### 4. Rédaction assistée par IA Gemini (`settings_ai.php`, `api_ai.php`)
- **Connexion Gemini** : Renseignez votre clé d'API gratuite et scannez en temps réel les modèles disponibles.
- **Génération Contextuelle** : Dans vos fiches projets, utilisez l'assistant IA pour :
  - Rédiger des briefs clients professionnels à partir de simples notes.
  - Développer vos concepts créatifs et raconter vos défis techniques.
  - Générer des titres SEO et des méta-descriptions optimisés pour Google.
  - Rédiger des posts de partage LinkedIn, Twitter/X ou Instagram à partir des détails de vos projets.

### 5. Médiathèque intelligente (`upload.php`, `media_modal.php`)
- **Glisser-Déposer & Tri** : Uploadez plusieurs fichiers (images et vidéos) en drag & drop et recherchez-les par nom de fichier.
- **Compression & Conversion WebP à la volée** : Choisissez votre niveau de compression lors de l'upload (Extrême, Forte, Moyenne, Légère, Aucune). Les images PNG/JPG sont converties automatiquement au format WebP (beaucoup plus léger) et les vidéos MP4 sont optimisées via FFmpeg (si disponible sur le serveur).
- **Miniatures automatiques** : Pré-génération de miniatures ultra-légères dans un dossier masqué `.thumbs/` pour ne pas ralentir le panel lors de l'affichage de centaines d'images.

### 6. Statistiques & Analytics (`stats.php`, `track.php`)
- **Pas de Cookies publicitaires** : Système de statistiques éthique et léger.
- **Indicateurs de Performance** : Suivi des vues globales, des visiteurs uniques quotidiens, du type d'appareil (Desktop, Mobile, Tablette), des sources de trafic (référents externes comme Google, LinkedIn, Behance...) et des clics sur vos boutons externes (liens de projets, CV, etc.).

### 7. Outils SEO & Utilitaires (`sitemap_gen.php`, `backup.php`, `og_image.php`)
- **Générateur de Sitemap** : Crée un fichier `sitemap.xml` propre à la racine de votre site à chaque clic pour indexer vos pages et projets sur Google.
- **Image Open Graph Dynamique (`og_image.php`)** : Génère automatiquement à la volée l'image de partage sur les réseaux sociaux pour chaque projet (affiche le titre, la couverture floutée en fond et votre identité visuelle via la bibliothèque PHP GD).
- **Backup Complet en un clic** : Télécharge instantanément un fichier ZIP contenant l'intégralité de vos données (`data.json`, `stats.json`, configuration IA et toutes vos images).

---

## 🛠️ Installation & Configuration

### 1. Prérequis
- Un hébergement ou serveur local avec **PHP 7.4 ou supérieur**.
- La bibliothèque PHP **GD** activée (généralement incluse par défaut, requise pour les miniatures et images OpenGraph).
- Droits d'écriture sur le dossier d'installation (pour pouvoir écrire `data.json`, générer `sitemap.xml`, et créer le dossier `images/`).

### 2. Déploiement
1. Téléchargez le dossier du panel (nommez-le par exemple `gestion_interne`) et placez-le dans votre projet.
2. Pour des raisons de sécurité, assurez-vous que les fichiers sensibles sont ignorés par Git. Le fichier `.gitignore` fourni à la racine de ce dossier exclut automatiquement vos données personnelles :
   - `credentials.php` (votre mot de passe administrateur)
   - `data/ai_config.json` (votre clé API Gemini)
   - `data/stats.json` (vos statistiques de visites)
   - `images/` et `data.json` locaux de développement

### 3. Configuration du fichier `config.php`
Ouvrez config.php et ajustez les 4 sections simples :
- **`PORTFOLIO_OWNER`** : Votre nom (ex: `"Paul C."`).
- **`PORTFOLIO_URL`** : Lien vers votre site (`"../"` si l'admin est à l'intérieur du dossier de votre site).
- **`ALLOWED_ORIGINS`** : Liste des domaines CORS autorisés à appeler les scripts de tracking (ex: `http://localhost:5173` pour React, `https://monportfolio.com` pour la production).
- **`$dev_path`** : Si vous développez avec un framework comme React/Vite, spécifiez le chemin vers le dossier `public` de vos sources (ex: `../public/` ou `../react-portfolio/public/`) pour que l'admin y écrive directement pendant que vous codez.

### 4. Premier Login
1. Naviguez sur votre navigateur vers `https://votre-site.com/gestion_interne/login.php`.
2. Connectez-vous avec le mot de passe par défaut : **`admin123`**.
3. Allez immédiatement dans l'onglet **Sécurité** (dans le menu latéral) pour modifier votre mot de passe. Cela va créer automatiquement le fichier sécurisé `credentials.php`.

---

## 💻 Guide d'Intégration Frontend

Voici comment connecter votre portfolio (en React ou en PHP/HTML/CSS classique) aux fonctions et données de votre panel d'administration.

### Option A : Intégration dans un site React (Single Page App)

#### 1. Lire et afficher les données du site (`data.json`)
Puisque vos données sont stockées dans un fichier JSON statique à la racine de votre dossier public (ou copié lors du build), vous pouvez le charger via un simple appel `fetch` :

```jsx
import React, { useEffect, useState } from 'react';

function Portfolio() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch('/data.json')
      .then(res => res.json())
      .then(json => {
        // Trier les projets par importance décroissante (importance: 1 à 10)
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
      {/* Affichage du Portrait d'accueil */}
      <section className="hero">
        <h1>{data.portrait.titre}</h1>
        <p>{data.portrait.paragraphe1}</p>
        <img src={`/${data.portrait.image}`} alt="Avatar" />
      </section>

      {/* Liste des Projets */}
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

#### 2. Mettre en place le script de Tracking Analytics (`track.php`)
Pour comptabiliser les visites sans cookies, vous devez envoyer des requêtes POST asynchrones vers le script `track.php` du panel admin.

Créez un hook ou une fonction réutilisable pour suivre les pages vues et les clics :

```javascript
// analytics.js
const ADMIN_URL = '/gestion_interne'; // URL de votre dossier admin panel

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

// Tracker un clic sur un lien externe (bouton CV, URL externe, réseaux)
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

Dans votre composant de routage principal (par exemple avec `react-router-dom`), déclenchez les événements lors des changements d'URL :

```jsx
import { useEffect } from 'react';
import { useLocation, useParams } from 'react-router-dom';
import { trackPageView, trackProjectView } from './analytics';

// Dans votre composant d'application global
function PageTracker() {
  const location = useLocation();

  useEffect(() => {
    // Si nous ne sommes pas sur une page de projet spécifique
    if (!location.pathname.startsWith('/projet/')) {
      trackPageView(location.pathname);
    }
  }, [location]);

  return null;
}

// Dans votre composant de Page Détail Projet
function ProjectDetailPage() {
  const { slug } = useParams(); // ex: mon-super-projet
  
  useEffect(() => {
    if (slug) {
      trackProjectView(slug, `/projet/${slug}`);
    }
  }, [slug]);

  return (
    <div>{/* Rendu du projet */}</div>
  );
}
```

---

### Option B : Intégration dans un site PHP / HTML / CSS Classique

Si votre site est codé en PHP traditionnel, l'intégration est encore plus directe car le fichier JSON peut être lu côté serveur avant l'envoi de la page au client.

#### 1. Lire et afficher les données en PHP
Créez une fonction utilitaire au début de vos pages PHP :

```php
<?php
// Charger et décoder le JSON des données
$data_file_path = __DIR__ . '/data.json'; // ou ajustez le chemin d'accès
$data = [];

if (file_exists($data_file_path)) {
    $json_content = file_get_contents($data_file_path);
    $data = json_decode($json_content, true) ?: [];
}

// Récupérer les projets
$projets = isset($data['projets']) ? $data['projets'] : [];

// Trier les projets par score d'importance (10 à 1)
usort($projets, function($a, $b) {
    $impA = isset($a['importance']) ? (int)$a['importance'] : 5;
    $impB = isset($b['importance']) ? (int)$b['importance'] : 5;
    return $impB - $impA;
});
?>
```

Ensuite, affichez le contenu dans votre structure HTML :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['seo']['titre'] ?? 'Mon Portfolio'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($data['seo']['description'] ?? ''); ?>">
</head>
<body>

    <!-- Section Portrait -->
    <?php if (isset($data['portrait'])): $p = $data['portrait']; ?>
    <section class="hero">
        <h1><?php echo htmlspecialchars($p['titre']); ?></h1>
        <p><?php echo htmlspecialchars($p['paragraphe1']); ?></p>
        <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="Portrait">
    </section>
    <?php endif; ?>

    <!-- Grille des Projets -->
    <section class="projects-grid">
        <?php foreach ($projets as $projet): ?>
            <?php if (($projet['status'] ?? 'published') !== 'published') continue; ?>
            
            <div class="project-card">
                <img src="<?php echo htmlspecialchars($projet['image']); ?>" alt="<?php echo htmlspecialchars($projet['alt'] ?? ''); ?>">
                <h3><?php echo htmlspecialchars($projet['titre']); ?></h3>
                <p><?php echo htmlspecialchars($projet['sousTitre']); ?></p>
                <a href="projet.php?slug=<?php echo $projet['projetId']; ?>">Voir le projet</a>
            </div>
        <?php endforeach; ?>
    </section>

</body>
</html>
```

#### 2. Suivi de visites en Javascript sur site PHP
Pour ne pas ralentir le chargement des pages en PHP, vous pouvez placer le code de tracking dans un simple script Javascript en bas de vos fichiers (juste avant `</body>`) :

```html
<script>
// Configuration
const adminFolder = 'gestion_interne'; // Nom du dossier admin
const trackingUrl = `${window.location.origin}/${adminFolder}/track.php`;

// 1. Envoyer automatiquement la page vue
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

// 2. Si vous êtes sur une page de projet spécifique (détectée en PHP)
<?php if (isset($is_project_page) && $is_project_page && isset($current_project_id)): ?>
fetch(trackingUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'projectview',
        id: '<?php echo htmlspecialchars($current_project_id); ?>',
        path: window.location.pathname,
        screenWidth: window.innerWidth
    })
}).catch(e => console.error(e));
<?php endif; ?>

// 3. Suivre les clics sur les boutons de réseaux sociaux ou liens externes
function trackClick(elementId) {
    fetch(trackingUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'click',
            id: elementId
        })
    }).catch(() => {});
}
</script>
```

Appliquez la fonction `trackClick` sur vos boutons importants :
```html
<a href="https://linkedin.com/in/..." onclick="trackClick('linkedin_link')" target="_blank">LinkedIn</a>
<a href="mon-cv.pdf" onclick="trackClick('cv_download')" download>Télécharger mon CV</a>
```

---

## 🖼️ Affichage des Images Uploadées
Quand vous uploadez des fichiers via l'admin panel, ils sont placés dans le dossier `images/` de votre portfolio (défini par `IMAGES_UPLOAD_DIR`). 

### Bonnes Pratiques :
1. **Chemin relatif** : Les chemins d'images enregistrés dans le fichier `data.json` sont sous la forme `images/mon-image.webp` (ou `images/dossier/mon-image.webp`).
2. **Miniatures (`.thumbs/`)** : Si vous construisez une page d'accueil avec des dizaines de projets et souhaitez optimiser les temps de chargement, servez les miniatures générées par l'admin à la place de la couverture originale. 
   - Chemin de l'image de couverture : `images/couverture.webp`
   - Chemin de sa miniature automatique : `images/.thumbs/couverture.webp`

---

## 🔍 Intégration de l'Image Open Graph Dynamique

L'image Open Graph générée par `og_image.php` vous permet d'avoir un visuel de partage automatique et personnalisé pour chaque projet sur les réseaux sociaux (LinkedIn, Twitter/X, Discord, Slack, etc.).

Dans la balise `<head>` de votre page projet (en PHP ou générée par SSR/React), injectez l'URL du script :

```html
<!-- Exemple pour la page de projet d'un portfolio PHP -->
<meta property="og:title" content="<?php echo htmlspecialchars($projet['titre']); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($projet['seo_description'] ?? $projet['sousTitre']); ?>" />
<meta property="og:type" content="article" />
<meta property="og:url" content="https://monportfolio.com/projet/<?php echo $projet['projetId']; ?>" />

<!-- Lien vers le script de génération d'image dynamique -->
<meta property="og:image" content="https://monportfolio.com/gestion_interne/og_image.php?slug=<?php echo $projet['projetId']; ?>" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:type" content="image/png" />

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:image" content="https://monportfolio.com/gestion_interne/og_image.php?slug=<?php echo $projet['projetId']; ?>" />
```

Le script `og_image.php` s'occupera d'aller chercher la couverture du projet dans `images/`, d'appliquer un filtre sombre/flouté, d'ajouter le titre en grand, la catégorie et le nom de marque configuré, puis renverra le flux PNG brut avec un système de cache pour ne pas surcharger le serveur à chaque partage !

---

## 🛠️ Rendu et Intégration des Composants Spécifiques

Voici les guides pour afficher et intégrer chaque fonctionnalité avancée dans votre code.

### 1. Rendu du Dossier Académique (Blocs Composés de Projets)

Le champ `dossier` de chaque projet contient une liste ordonnée de blocs au format JSON. Chaque bloc possède un `type` décrivant sa disposition graphique.

#### Intégration React
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

#### Intégration PHP
```php
<?php if (!empty($projet['dossier'])): ?>
    <div class="project-dossier">
        <?php foreach ($projet['dossier'] as $bloc): 
            $type = $bloc['type'] ?? '';
            $titre = $bloc['titre'] ?? '';
            $texte = $bloc['texte'] ?? '';
            $image1 = $bloc['image1'] ?? '';
            $alt1 = $bloc['alt1'] ?? '';
            $legende1 = $bloc['legende1'] ?? '';
            $image2 = $bloc['image2'] ?? '';
            $alt2 = $bloc['alt2'] ?? '';
            $legende2 = $bloc['legende2'] ?? '';
            $accent = $bloc['accent'] ?? '';
        ?>
            <?php if (in_array($type, ['text', 'consigne', 'brief', 'concept', 'defis', 'resultats'])): ?>
                <div class="block-text block-<?php echo $type; ?>" style="border-color: <?php echo htmlspecialchars($accent); ?>">
                    <?php if ($titre): ?><h2><?php echo htmlspecialchars($titre); ?></h2><?php endif; ?>
                    <div class="text-content"><?php echo nl2br(htmlspecialchars($texte)); ?></div>
                </div>
            <?php elseif ($type === 'text_image'): ?>
                <div class="block-grid block-text-image">
                    <div class="col-text">
                        <?php if ($titre): ?><h2><?php echo htmlspecialchars($titre); ?></h2><?php endif; ?>
                        <p><?php echo nl2br(htmlspecialchars($texte)); ?></p>
                    </div>
                    <div class="col-media">
                        <img src="<?php echo htmlspecialchars($image1); ?>" alt="<?php echo htmlspecialchars($alt1); ?>">
                        <?php if ($legende1): ?><p class="caption"><?php echo htmlspecialchars($legende1); ?></p><?php endif; ?>
                    </div>
                </div>
            <?php elseif ($type === 'full_image'): ?>
                <div class="block-full-width">
                    <?php if ($titre): ?><h2><?php echo htmlspecialchars($titre); ?></h2><?php endif; ?>
                    <img src="<?php echo htmlspecialchars($image1); ?>" alt="<?php echo htmlspecialchars($alt1); ?>">
                    <?php if ($legende1): ?><p class="caption"><?php echo htmlspecialchars($legende1); ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

---

### 2. Rendu de la Galerie Multimédia (avec Vidéos YouTube)

La galerie est stockée sous la forme d'un tableau d'éléments structurés ainsi : `[chemin_du_media, legende, type_media]`. Le type peut être `image` ou `video`. Si le média provient d'une URL de type YouTube ou similaire, il est géré dynamiquement.

#### Rendu React
```jsx
function ProjetGalerie({ galerie, youtubeId }) {
  return (
    <div className="project-gallery">
      {/* 1. Rendu de la vidéo YouTube principale si renseignée */}
      {youtubeId && (
        <div className="video-container">
          <iframe
            src={`https://www.youtube.com/embed/${youtubeId}`}
            title="Présentation vidéo"
            allowFullScreen
          ></iframe>
        </div>
      )}

      {/* 2. Rendu des images de la galerie */}
      <div className="gallery-grid">
        {galerie.map((item, index) => {
          const [path, caption, type] = item;
          return (
            <div key={index} className={`gallery-item ${type}`}>
              {type === 'video' ? (
                <video src={`/${path}`} controls />
              ) : (
                <img src={`/${path}`} alt={caption || 'Visuel galerie'} />
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

---

### 3. Rendu du Parcours (Timeline Chronologique)

Le parcours est stocké dans l'objet global `data.parcours`. Vous pouvez en faire un rendu vertical stylisé en CSS.

#### Rendu PHP
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

---

### 4. Rendu des Compétences & Outils (Logos)

Les soft skills, hard skills et outils (logos) sont également stockés dans des tableaux séparés pour un affichage sur votre page À propos.

#### Affichage des Outils/Logos en React
```jsx
function SkillsList({ skillsHumaines, skillsTechniques, logos }) {
  return (
    <div className="skills-section">
      {/* Compétences Humaines */}
      <h2>Soft Skills</h2>
      <ul className="soft-skills">
        {skillsHumaines.map((s, i) => (
          <li key={i}>
            <span>{s.icone}</span> <strong>{s.titre}</strong>: {s.description}
          </li>
        ))}
      </ul>

      {/* Outils & Logiciels (avec logos) */}
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

---

## ✍️ Crédits & Créateur

Ce panel d'administration pour portfolio sans base de données a été imaginé, conçu et entièrement développé par **Paul Chéhère Le Lann**.

- **Portfolio** : [paul-c.fr](https://paul-c.fr)
- **GitHub** : [@iampaulc](https://github.com/iampaulc)
- **Instagram** : [@iampaulc_](https://instagram.com/iampaulc_)

*N'hésitez pas à mentionner ce crédit ou à laisser une étoile ⭐ sur le dépôt GitHub si ce panel d'administration vous a aidé dans la gestion de votre portfolio !*
