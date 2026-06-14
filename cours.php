<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role('etudiant');

$user = get_user_info();
$etudiant_id = $_SESSION['user_id'];

// Voir les détails d'un cours
$view_course = isset($_GET['view']) ? (int)$_GET['view'] : null;

// Recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Récupérer les cours avec filtre de recherche
if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT c.*, m.nom as module_nom, u.nom as enseignant_nom,
               COUNT(DISTINCT l.id) as total_lecons
        FROM cours c
        LEFT JOIN modules m ON c.module_id = m.id
        LEFT JOIN users u ON c.enseignant_id = u.id
        LEFT JOIN lecons l ON c.id = l.cours_id
        WHERE c.titre LIKE ? OR c.description LIKE ? OR m.nom LIKE ?
        GROUP BY c.id
        ORDER BY c.date_creation DESC
    ");
    $search_param = "%$search%";
    $stmt->execute([$search_param, $search_param, $search_param]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, m.nom as module_nom, u.nom as enseignant_nom,
               COUNT(DISTINCT l.id) as total_lecons
        FROM cours c
        LEFT JOIN modules m ON c.module_id = m.id
        LEFT JOIN users u ON c.enseignant_id = u.id
        LEFT JOIN lecons l ON c.id = l.cours_id
        GROUP BY c.id
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute();
}
$cours = $stmt->fetchAll();

$page_title = !empty($search) ? "Recherche: $search" : "Mes Cours";
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
        <?php if (!empty($search)): ?>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="bi bi-search"></i> Résultats pour "<?php echo htmlspecialchars($search); ?>"</h1>
                    <p><?php echo count($cours); ?> cours trouvé(s)</p>
                </div>
                <a href="cours.php" class="btn-secondary">
                    <i class="bi bi-x-circle"></i> Effacer la recherche
                </a>
            </div>
        <?php else: ?>
            <h1><i class="bi bi-collection-play"></i> Tous les cours</h1>
            <p>Parcourez les cours disponibles</p>
        <?php endif; ?>
    </div>

    <?php if ($view_course): ?>
        <?php
        // Récupérer le cours sélectionné
        $stmt = $pdo->prepare("
            SELECT c.*, m.nom as module_nom, u.nom as enseignant_nom
            FROM cours c
            LEFT JOIN modules m ON c.module_id = m.id
            LEFT JOIN users u ON c.enseignant_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$view_course]);
        $course = $stmt->fetch();
        
        if ($course) {
            // Récupérer les leçons du cours
            $stmt = $pdo->prepare("
                SELECT l.*, 
                       CASE WHEN p.statut = 'termine' THEN 1 ELSE 0 END as terminee,
                       p.score_evaluation,
                       e.id as evaluation_id
                FROM lecons l
                LEFT JOIN progression p ON l.id = p.lecon_id AND p.etudiant_id = ?
                LEFT JOIN evaluations e ON l.id = e.lecon_id
                WHERE l.cours_id = ?
                ORDER BY l.ordre
            ");
            $stmt->execute([$etudiant_id, $view_course]);
            $lecons = $stmt->fetchAll();
            
            $total_lecons = count($lecons);
            $lecons_terminees = 0;
            foreach ($lecons as $l) {
                if ($l['terminee']) $lecons_terminees++;
            }
            $progression = $total_lecons > 0 ? round(($lecons_terminees / $total_lecons) * 100) : 0;
        ?>
        
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><?php echo htmlspecialchars($course['titre']); ?></h2>
                    <p class="text-muted">
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($course['enseignant_nom'] ?? 'N/A'); ?>
                        &nbsp;|&nbsp;
                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($course['module_nom'] ?? 'Non assigné'); ?>
                    </p>
                </div>
                <a href="cours.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
            
            <div class="card-content" style="margin-bottom: 1.5rem;">
                <?php echo nl2br(htmlspecialchars($course['description'] ?? '')); ?>
            </div>
            
            <div class="progress-text">
                <span>Progression du cours</span>
                <span><?php echo $progression; ?>% (<?php echo $lecons_terminees; ?>/<?php echo $total_lecons; ?> leçons)</span>
            </div>
            <div class="progress-bar" style="margin-bottom: 1.5rem;">
                <div class="progress-fill <?php echo $progression >= 80 ? 'green' : ($progression >= 40 ? 'orange' : 'red'); ?>" 
                     style="width: <?php echo $progression; ?>%"></div>
            </div>
        </div>
        
        <h3 style="margin-bottom: 1rem;"><i class="bi bi-list-check"></i> Leçons</h3>
        
        <ul class="lesson-list">
            <?php foreach ($lecons as $index => $lecon): ?>
                <li class="lesson-item" onclick="window.location.href='lecon.php?id=<?php echo $lecon['id']; ?>'">
                    <div class="lesson-icon <?php echo $lecon['type_contenu']; ?>">
                        <i class="bi bi-<?php echo $lecon['type_contenu'] === 'video' ? 'play-circle' : 'file-pdf'; ?>"></i>
                    </div>
                    <div class="lesson-info">
                        <div class="lesson-title">
                            Leçon <?php echo $index + 1; ?>: <?php echo htmlspecialchars($lecon['titre']); ?>
                        </div>
                        <div class="lesson-meta">
                            <span><i class="bi bi-<?php echo $lecon['type_contenu'] === 'video' ? 'camera-video' : 'file-earmark-pdf'; ?>"></i> 
                                <?php echo strtoupper($lecon['type_contenu']); ?>
                            </span>
                            <?php if ($lecon['evaluation_id']): ?>
                                <span>&nbsp;|&nbsp; <i class="bi bi-check-square"></i> Évaluation disponible</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="lesson-status <?php echo $lecon['terminee'] ? 'completed' : 'pending'; ?>">
                        <i class="bi bi-<?php echo $lecon['terminee'] ? 'check-circle-fill' : 'circle'; ?>"></i>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <?php } else { ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> Cours non trouvé.
            </div>
        <?php } ?>
        
    <?php else: ?>
        
        <?php if (empty($cours)): ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="bi bi-inbox" style="font-size: 4rem; color: var(--text-muted);"></i>
                <h3 style="margin-top: 1rem;">Aucun cours disponible</h3>
                <p class="text-muted">Les cours seront bientôt ajoutés.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($cours as $c): ?>
                    <a href="cours.php?view=<?php echo $c['id']; ?>" class="course-card" style="text-decoration: none; color: inherit;">
                        <div class="course-image">
                            <i class="bi bi-<?php echo $c['module_nom'] ? 'code-slash' : 'book'; ?>"></i>
                        </div>
                        <div class="course-content">
                            <div class="course-meta">
                                <span><i class="bi bi-tag"></i> <?php echo htmlspecialchars($c['module_nom'] ?? 'Non assigné'); ?></span>
                            </div>
                            <h3 class="course-title"><?php echo htmlspecialchars($c['titre']); ?></h3>
                            <p class="card-content" style="margin-bottom: 1rem;">
                                <?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 100)); ?>...
                            </p>
                            <div class="course-meta">
                                <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($c['enseignant_nom'] ?? 'N/A'); ?></span>
                                &nbsp;|&nbsp;
                                <span><i class="bi bi-list-check"></i> <?php echo $c['total_lecons']; ?> leçon(s)</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
