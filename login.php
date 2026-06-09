<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Admin Portfolio</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-input-wrap {
            position: relative;
        }
        .login-input-wrap .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            opacity: 0.4;
            pointer-events: none;
            transition: opacity 0.2s;
        }
        .login-input-wrap input {
            padding-left: 2.8rem;
        }
        .login-input-wrap input:focus + .input-icon,
        .login-input-wrap input:not(:placeholder-shown) + .input-icon {
            opacity: 0.7;
        }
        .login-btn {
            position: relative;
            width: 100%;
            padding: 0.85rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .login-btn:active {
            transform: scale(0.98);
        }
        .login-features {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(56, 68, 96, 0.3);
        }
        .login-feature {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
        }
        .login-feature-icon {
            font-size: 1.1rem;
            opacity: 0.5;
        }
        .login-feature-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card glass-panel">
        <div style="margin-bottom: 1.5rem;">
            <div style="width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, var(--accent-color), var(--purple)); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 900; color: #fff; margin: 0 auto 1rem;"><?php echo htmlspecialchars(mb_strtoupper(mb_substr(defined('PORTFOLIO_OWNER') ? PORTFOLIO_OWNER : 'Portfolio', 0, 1))); ?></div>
            <h1>Admin Panel</h1>
            <p>Connectez-vous pour gérer votre portfolio.</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" style="text-align: left;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="login-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="login-input-wrap">
                    <input type="password" name="password" id="password" required placeholder="Entrez votre mot de passe" autofocus>
                    <span class="input-icon">🔒</span>
                </div>
            </div>
            <button type="submit" name="submit_login" class="btn btn-primary login-btn" id="login-submit">
                Se connecter
            </button>
        </form>

        <div class="login-features">
            <div class="login-feature">
                <span class="login-feature-icon">🔐</span>
                <span class="login-feature-label">Sécurisé</span>
            </div>
            <div class="login-feature">
                <span class="login-feature-icon">⚡</span>
                <span class="login-feature-label">Rapide</span>
            </div>
            <div class="login-feature">
                <span class="login-feature-icon">🛡️</span>
                <span class="login-feature-label">Protégé</span>
            </div>
        </div>

        <p class="login-footer">© <?php echo date('Y'); ?> Admin Panel — v2.0</p>
    </div>

    <script>
        // Loading animation on submit
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('login-submit');
            btn.classList.add('btn-loading');
            btn.textContent = 'Connexion...';
        });
    </script>
</body>
</html>
