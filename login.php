<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Rediriger si déjà connecté
if (is_logged_in()) {
    header('Location: /MboaLearn/dashboard.php');
    exit();
}

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Veuillez remplir tous les champs.</div>';
    } else {
        // Vérifier l'utilisateur
        $stmt = $pdo->prepare("SELECT id, nom, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: /MboaLearn/dashboard.php');
            exit();
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Email ou mot de passe incorrect.</div>';
        }
    }
}

$page_title = "Connexion";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MboaLearn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/MboaLearn/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.html" class="nav-brand">
                <img src="/MboaLearn/assets/images/mboalearn.png" alt="MboaLearn" class="nav-logo">
            </a>
            <div class="nav-menu">
                <a href="login.php" class="nav-link">
                    Connexion
                </a>
                <a href="register.php" class="btn-primary">
                    Inscription
                </a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Connexion</h1>
                    <p>Connectez-vous à votre compte</p>
                </div>
                
                <?php echo $message; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="votre@email.com" required
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="••••••••" required>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 0.8rem;">
                        Se connecter
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
                    <p style="margin-top: 1rem; font-size: 0.85rem;">
                        <a href="index.html">Retour à l'accueil</a>
                    </p>
                </div>
            </div>
            
            <div class="card mt-3" style="background: rgba(5,150,105,0.05); border: 1px solid rgba(5,150,105,0.2);">
                <p style="font-size: 0.85rem; color: var(--text-muted); text-align: center;">
                    <strong>Comptes de test :</strong><br>
                    Admin: admin@lms.com<br>
                    Enseignant: dupont@lms.com<br>
                    Étudiant: jean@lms.com<br>
                    Mot de passe: password
                </p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 MboaLearn — TP INF222 — Yvana Emilia Lalane Larcier 24G2439</p>
        </div>
    </footer>
</body>
</html>
