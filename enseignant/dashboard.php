<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('enseignant');

$user = get_user_info();
$enseignant_id = $_SESSION['user_id'];

// Récupérer les cours de l'enseignant
$stmt = $pdo->prepare("
    SELECT c.*, m.nom as module_nom,
           COUNT(DISTINCT l.id) as total_lecons,
           COUNT(DISTINCT e.id) as total_evaluations,
           (SELECT COUNT(DISTINCT r.etudiant_id) FROM resultats r 
            JOIN evaluations ev ON r.evaluation_id = ev.id 
            JOIN lecons le ON ev.lecon_id = le.id 
            WHERE le.cours_id = c.id) as etudiants_inscrits
    FROM cours c
    LEFT JOIN modules m ON c.module_id = m.id
    LEFT JOIN lecons l ON c.id = l.cours_id
    LEFT JOIN evaluations e ON l.id = e.lecon_id
    WHERE c.enseignant_id = ?
    GROUP BY c.id
    ORDER BY c.date_creation DESC
");
$stmt->execute([$enseignant_id]);
$cours = $stmt->fetchAll();

// Récupérer les résultats récents
$stmt = $pdo->prepare("
    SELECT r.*, u.nom as etudiant_nom, e.titre as evaluation_titre, c.titre as cours_titre
    FROM resultats r
    JOIN users u ON r.etudiant_id = u.id
    JOIN evaluations e ON r.evaluation_id = e.id
    JOIN lecons l ON e.lecon_id = l.id
    JOIN cours c ON l.cours_id = c.id
    WHERE c.enseignant_id = ?
    ORDER BY r.date_passage DESC
    LIMIT 10
");
$stmt->execute([$enseignant_id]);
$resultats = $stmt->fetchAll();

$page_title = "Dashboard Enseignant";
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-header">
        <h1>Espace Enseignant</h1>
        <p>Bienvenue, <?php echo htmlspecialchars($user['nom']); ?> !</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-collection"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($cours); ?></h3>
                <p>Mes cours</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-list-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php 
                    $total = 0;
                    foreach ($cours as $c) $total += $c['total_lecons'];
                    echo $total;
                ?></h3>
                <p>Leçons créées</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3><?php 
                    $etudiants = 0;
                    foreach ($cours as $c) $etudiants += $c['etudiants_inscrits'];
                    echo $etudiants;
                ?></h3>
                <p>Étudiants</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="bi bi-check-square"></i>
            </div>
            <div class="stat-info">
                <h3><?php 
                    $evals = 0;
                    foreach ($cours as $c) $evals += $c['total_evaluations'];
                    echo $evals;
                ?></h3>
                <p>Évaluations</p>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-lightning"></i> Actions rapides</h2>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="ajouter_cours.php" class="btn-primary">
                <i class="bi bi-plus-circle"></i> Nouveau cours
            </a>
            <a href="mes_cours.php" class="btn-secondary">
                <i class="bi bi-collection"></i> Gérer mes cours
            </a>
        </div>
    </div>

    <!-- Mes cours -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-collection-play"></i> Mes Cours</h2>
            <a href="mes_cours.php" class="btn-secondary">
                <i class="bi bi-eye"></i> Voir tout
            </a>
        </div>
        
        <?php if (empty($cours)): ?>
            <div class="card-content text-center" style="padding: 2rem;">
                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                <p style="margin-top: 1rem;">Aucun cours créé</p>
                <a href="ajouter_cours.php" class="btn-primary mt-2">Créer un cours</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Module</th>
                            <th>Leçons</th>
                            <th>Évaluations</th>
                            <th>Étudiants</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($cours, 0, 5) as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['titre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['module_nom'] ?? 'N/A'); ?></td>
                                <td><?php echo $c['total_lecons']; ?></td>
                                <td><?php echo $c['total_evaluations']; ?></td>
                                <td><?php echo $c['etudiants_inscrits']; ?></td>
                                <td>
                                    <a href="ajouter_lecon.php?cours=<?php echo $c['id']; ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                        <i class="bi bi-plus"></i> Leçon
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Résultats récents -->
    <?php if (!empty($resultats)): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-bar-chart"></i> Résultats récents</h2>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Évaluation</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['etudiant_nom']); ?></td>
                            <td><?php echo htmlspecialchars($r['evaluation_titre']); ?></td>
                            <td>
                                <span style="color: <?php echo $r['note'] >= 70 ? 'var(--success)' : ($r['note'] >= 50 ? 'var(--warning)' : 'var(--danger)'); ?>; font-weight: 600;">
                                    <?php echo number_format($r['note'], 1); ?>%
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($r['date_passage'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
