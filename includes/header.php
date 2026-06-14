<?php
if (!isset($page_title)) $page_title = 'MboaLearn';
$base_url = '/MboaLearn';
$user = get_user_info();
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
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo $base_url; ?>/index.html" class="nav-brand">
                <img src="<?php echo $base_url; ?>/assets/images/mboalearn.png" alt="MboaLearn" class="nav-logo">
            </a>
            
            <?php if (is_logged_in()): ?>
            <div class="nav-menu">
                <a href="<?php echo $base_url; ?>/dashboard.php" class="nav-link">
                    Accueil
                </a>
                
                <?php if (has_role('etudiant')): ?>
                    <a href="<?php echo $base_url; ?>/cours.php" class="nav-link">
                        Cours
                    </a>
                    <a href="<?php echo $base_url; ?>/certificats.php" class="nav-link">
                        Certificats
                    </a>
                    <form action="<?php echo $base_url; ?>/cours.php" method="GET" class="nav-search">
                        <input type="text" name="search" placeholder="Rechercher un cours..." 
                               class="nav-search-input" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button type="submit" class="nav-search-btn">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (has_role('enseignant')): ?>
                    <a href="<?php echo $base_url; ?>/enseignant/mes_cours.php" class="nav-link">
                        Cours
                    </a>
                    <a href="<?php echo $base_url; ?>/enseignant/mes_lecons.php" class="nav-link">
                        Leçons
                    </a>
                    <a href="<?php echo $base_url; ?>/enseignant/mes_evaluations.php" class="nav-link">
                        Évaluations
                    </a>
                    <a href="<?php echo $base_url; ?>/enseignant/ajouter_cours.php" class="nav-link">
                        Nouveau
                    </a>
                <?php endif; ?>
                
                <?php if (has_role('promoteur')): ?>
                    <a href="<?php echo $base_url; ?>/promoteur/modules.php" class="nav-link">
                        Modules
                    </a>
                    <a href="<?php echo $base_url; ?>/promoteur/statistiques.php" class="nav-link">
                        Stats
                    </a>
                <?php endif; ?>
                
                <div class="nav-user">
                    <span class="user-info">
                        <?php echo $user['nom']; ?>
                        <span class="badge"><?php echo ucfirst($user['role']); ?></span>
                    </span>
                    <a href="<?php echo $base_url; ?>/logout.php" class="btn-logout">
                        Déconnexion
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="nav-menu">
                <a href="<?php echo $base_url; ?>/login.php" class="nav-link">
                    Connexion
                </a>
                <a href="<?php echo $base_url; ?>/register.php" class="btn-primary">
                    Inscription
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="container">
