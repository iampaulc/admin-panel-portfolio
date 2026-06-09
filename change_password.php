<?php
require_once 'auth.php';
check_auth();

$success = '';
$error = '';

if (isset($_POST['update_password'])) {
    verify_csrf();
    
    $current = trim($_POST['current_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    
    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!password_verify($current, ADMIN_PASSWORD_HASH)) {
        $error = "Le mot de passe actuel est incorrect.";
    } elseif ($new !== $confirm) {
        $error = "Le nouveau mot de passe et sa confirmation ne correspondent pas.";
    } elseif (strlen($new) < 6) {
        $error = "Le nouveau mot de passe doit faire au moins 6 caractères.";
    } else {
        $new_hash = password_hash($new, PASSWORD_BCRYPT);
        $content = "<?php\n// credentials.php — Ne pas modifier manuellement\ndefine('ADMIN_PASSWORD_HASH', '" . $new_hash . "');\n?>";
        
        if (file_put_contents(__DIR__ . '/credentials.php', $content)) {
            $success = "Le mot de passe a été mis à jour avec succès.";
        } else {
            $error = "Erreur lors de l'écriture du fichier credentials.php. Vérifiez les permissions.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sécurité - Admin Portfolio</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-card {
            max-width: 500px;
            margin: 2rem auto;
        }
        .form-feedback {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeInUp 0.3s ease-out;
        }
        .feedback-success {
            background: rgba(52, 211, 153, 0.08);
            color: var(--success-color);
            border: 1px solid rgba(52, 211, 153, 0.25);
        }
        .feedback-error {
            background: rgba(248, 113, 113, 0.08);
            color: var(--danger-color);
            border: 1px solid rgba(248, 113, 113, 0.25);
        }
    </style>
</head>
<body>
    <div class="save-progress" id="save-progress"></div>
    <div class="admin-layout">
        <?php $active_page = 'security'; require_once 'sidebar.php'; ?>

        <main class="main-content">
            <header class="header-actions">
                <div>
                    <h1>Sécurité</h1>
                    <p>Gérez vos identifiants d'accès au panel admin.</p>
                </div>
            </header>

            <div class="card settings-card">
                <h3>Changer le mot de passe</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
                    Sécurisez votre accès en modifiant régulièrement votre mot de passe.
                </p>

                <?php if ($success): ?>
                    <div class="form-feedback feedback-success">✅ <?php echo $success; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="form-feedback feedback-error">❌ <?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Entrez votre mot de passe actuel">
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Min. 6 caractères" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Répétez le nouveau mot de passe">
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" name="update_password" class="btn btn-primary" style="width: 100%;">
                            Mettre à jour le mot de passe
                        </button>
                    </div>
                </form>
            </div>

            <?php require_once 'footer.php'; ?>
        </main>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>
