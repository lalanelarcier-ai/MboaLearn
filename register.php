<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Rediriger si déjà connecté
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = clean_input($_POST['nom'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = clean_input($_POST['role'] ?? 'etudiant');
    
    if (empty($nom) || empty($email) || empty($password) || empty($password_confirm)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Veuillez remplir tous les champs.</div>';
    } elseif ($password !== $password_confirm) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Les mots de passe ne correspondent pas.</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Le mot de passe doit contenir au moins 6 caractères.</div>';
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Cet email est déjà utilisé.</div>';
        } else {
            // Créer l'utilisateur
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$nom, $email, $hashed_password, $role])) {
                $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Inscription réussie ! Vous pouvez vous connecter.</div>';
            } else {
                $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erreur lors de l\'inscription.</div>';
            }
        }
    }
}

$page_title = "Inscription";
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
                    <h1>Inscription</h1>
                    <p>Créez votre compte</p>
                </div>
                
                <?php echo $message; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom">Nom complet</label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               placeholder="Jean Dupont" required
                               value="<?php echo htmlspecialchars($nom ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="votre@email.com" required
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Je suis</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="etudiant">Étudiant</option>
                            <option value="enseignant">Enseignant</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Minimum 6 caractères" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirmer le mot de passe</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                               placeholder="Retapez votre mot de passe" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 0.8rem;">
                        S'inscrire
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
                    <p style="margin-top: 1rem; font-size: 0.85rem;">
                        <a href="index.html">Retour à l'accueil</a>
                    </p>
                </div>
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
