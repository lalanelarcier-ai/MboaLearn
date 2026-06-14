<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_login();

$user = get_user_info();
$base_url = '/MboaLearn';

// Rediriger selon le rôle
if (has_role('enseignant')) {
    header('Location: ' . $base_url . '/enseignant/dashboard.php');
    exit();
} elseif (has_role('promoteur')) {
    header('Location: ' . $base_url . '/promoteur/dashboard.php');
    exit();
}

// Dashboard Étudiant
$etudiant_id = $_SESSION['user_id'];

// Récupérer les cours suivis avec progression
$stmt = $pdo->prepare("
    SELECT c.id, c.titre, c.description, m.nom as module_nom,
           COUNT(DISTINCT l.id) as total_lecons,
           COUNT(DISTINCT CASE WHEN p.statut = 'termine' THEN l.id END) as lecons_terminees,
           AVG(CASE WHEN r.note IS NOT NULL THEN r.note ELSE NULL END) as moyenne
    FROM cours c
    LEFT JOIN modules m ON c.module_id = m.id
    LEFT JOIN lecons l ON c.id = l.cours_id
    LEFT JOIN progression p ON l.id = p.lecon_id AND p.etudiant_id = ?
    LEFT JOIN evaluations e ON l.id = e.lecon_id
    LEFT JOIN resultats r ON e.id = r.evaluation_id AND r.etudiant_id = ?
    GROUP BY c.id, c.titre, c.description, m.nom
    ORDER BY c.date_creation DESC
");
$stmt->execute([$etudiant_id, $etudiant_id]);
$cours = $stmt->fetchAll();

// Récupérer les certificats
$stmt = $pdo->prepare("
    SELECT cert.*, m.nom as module_nom
    FROM certificats cert
    JOIN modules m ON cert.module_id = m.id
    WHERE cert.etudiant_id = ?
    ORDER BY cert.date_obtention DESC
");
$stmt->execute([$etudiant_id]);
$certificats = $stmt->fetchAll();

$page_title = "Mon espace";
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-header">
        <h1><i class="bi bi-person-circle"></i> Bonjour, <?php echo htmlspecialchars($user['nom']); ?> !</h1>
        <p>Voici votre espace d'apprentissage</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-collection"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($cours); ?></h3>
                <p>Cours disponibles</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php 
                    $total_lecons_terminees = 0;
                    foreach ($cours as $c) {
                        $total_lecons_terminees += $c['lecons_terminees'];
                    }
                    echo $total_lecons_terminees;
                ?></h3>
                <p>Leçons terminées</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-star"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($certificats); ?></h3>
                <p>Certificats obtenus</p>
            </div>
        </div>
    </div>

    <!-- Cours -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-collection-play"></i> Mes Cours</h2>
            <a href="cours.php" class="btn-primary">
                <i class="bi bi-eye"></i> Voir tout
            </a>
        </div>
        
        <?php if (empty($cours)): ?>
            <div class="card-content text-center" style="padding: 2rem;">
                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                <p style="margin-top: 1rem;">Aucun cours pour le moment</p>
                <a href="cours.php" class="btn-primary mt-2">Découvrir les cours</a>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach (array_slice($cours, 0, 4) as $c): ?>
                    <?php
                    $progression = $c['total_lecons'] > 0 ? 
                        round(($c['lecons_terminees'] / $c['total_lecons']) * 100) : 0;
                    $progress_class = $progression >= 80 ? 'green' : ($progression >= 40 ? 'orange' : 'red');
                    ?>
                    <div class="course-card">
                        <div class="course-image">
                            <i class="bi <?php echo $c['module_nom'] ? 'bi-code-slash' : 'bi-book'; ?>"></i>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span><i class="bi bi-tag"></i> <?php echo htmlspecialchars($c['module_nom'] ?? 'Non assigné'); ?></span>
                            </div>
                            <h3 class="course-title"><?php echo htmlspecialchars($c['titre']); ?></h3>
                            <p class="card-content" style="margin-bottom: 1rem;">
                                <?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 80)); ?>...
                            </p>
                            
                            <div class="progress-text">
                                <span>Progression</span>
                                <span><?php echo $progression; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $progress_class; ?>" 
                                     style="width: <?php echo $progression; ?>%"></div>
                            </div>
                            
                            <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                <span class="text-muted" style="font-size: 0.85rem;">
                                    <i class="bi bi-list-check"></i> 
                                    <?php echo $c['lecons_terminees']; ?>/<?php echo $c['total_lecons']; ?> leçons
                                </span>
                                <a href="cours.php?view=<?php echo $c['id']; ?>" class="btn-secondary" style="padding: 0.4rem 0.8rem;">
                                    Continuer
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Certificats récents -->
    <?php if (!empty($certificats)): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-award"></i> Mes Certificats</h2>
            <a href="certificats.php" class="btn-secondary">
                <i class="bi bi-eye"></i> Voir tout
            </a>
        </div>
        
        <div class="grid grid-3">
            <?php foreach (array_slice($certificats, 0, 3) as $cert): ?>
                <div class="card" style="border-left: 4px solid var(--success); margin-bottom: 0;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; background: rgba(16,185,129,0.1); color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0;"><?php echo htmlspecialchars($cert['module_nom']); ?></h4>
                            <p style="color: var(--text-muted); margin: 0; font-size: 0.85rem;">
                                Obtenu le <?php echo date('d/m/Y', strtotime($cert['date_obtention'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
