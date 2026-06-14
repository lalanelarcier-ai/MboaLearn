<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('promoteur');

$user = get_user_info();

// Statistiques détaillées
$stats = [];

// Nombre d'utilisateurs par rôle
$stmt = $pdo->query("SELECT role, COUNT(*) as total FROM users GROUP BY role");
$stats['users'] = $stmt->fetchAll();

// Nombre de cours par module
$stmt = $pdo->query("
    SELECT m.nom, COUNT(c.id) as nb_cours 
    FROM modules m 
    LEFT JOIN cours c ON m.id = c.module_id 
    GROUP BY m.id 
    ORDER BY nb_cours DESC
");
$stats['cours_par_module'] = $stmt->fetchAll();

// Moyenne des notes
$stmt = $pdo->query("SELECT AVG(note) as moyenne, MIN(note) as min_note, MAX(note) as max_note FROM resultats");
$stats['notes'] = $stmt->fetch();

// Taux de réussite par module
$stmt = $pdo->query("
    SELECT m.nom, 
           COUNT(DISTINCT cert.etudiant_id) as nb_reussite,
           COUNT(DISTINCT r.etudiant_id) as nb_total
    FROM modules m
    LEFT JOIN cours c ON m.id = c.module_id
    LEFT JOIN lecons l ON c.id = l.cours_id
    LEFT JOIN evaluations e ON l.id = e.lecon_id
    LEFT JOIN resultats r ON e.id = r.evaluation_id
    LEFT JOIN certificats cert ON m.id = cert.module_id
    GROUP BY m.id
");
$stats['taux_reussite'] = $stmt->fetchAll();

// Activité récente (7 derniers jours)
$stmt = $pdo->query("
    SELECT DATE(date_passage) as jour, COUNT(*) as nb_evaluations
    FROM resultats
    WHERE date_passage >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(date_passage)
    ORDER BY jour
");
$stats['activite'] = $stmt->fetchAll();

$page_title = "Statistiques";
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
    <style>
        .stat-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }
        .stat-detail:last-child {
            border-bottom: none;
        }
        .stat-label {
            color: var(--text-muted);
        }
        .stat-value {
            font-weight: 600;
        }
        .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
            height: 150px;
            margin-top: 1rem;
        }
        .bar {
            flex: 1;
            background: var(--primary);
            border-radius: 4px 4px 0 0;
            min-height: 5px;
            position: relative;
            transition: height 0.3s ease;
        }
        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.7rem;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="bi bi-bar-chart"></i> Statistiques détaillées</h1>
                <p>Vue d'ensemble de la plateforme</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <!-- Utilisateurs par rôle -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-people"></i> Utilisateurs</h2>
        </div>
        <div class="grid grid-3">
            <?php foreach ($stats['users'] as $u): ?>
                <div class="stat-detail">
                    <span class="stat-label"><?php echo ucfirst($u['role']); ?></span>
                    <span class="stat-value"><?php echo $u['total']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Notes -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-graph-up"></i> Performance des étudiants</h2>
        </div>
        <div class="grid grid-3">
            <div class="stat-detail">
                <span class="stat-label">Note moyenne</span>
                <span class="stat-value" style="color: var(--primary);">
                    <?php echo $stats['notes']['moyenne'] ? number_format($stats['notes']['moyenne'], 1) . '%' : 'N/A'; ?>
                </span>
            </div>
            <div class="stat-detail">
                <span class="stat-label">Note minimale</span>
                <span class="stat-value" style="color: var(--danger);">
                    <?php echo $stats['notes']['min_note'] ? number_format($stats['notes']['min_note'], 1) . '%' : 'N/A'; ?>
                </span>
            </div>
            <div class="stat-detail">
                <span class="stat-label">Note maximale</span>
                <span class="stat-value" style="color: var(--success);">
                    <?php echo $stats['notes']['max_note'] ? number_format($stats['notes']['max_note'], 1) . '%' : 'N/A'; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Cours par module -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-collection"></i> Cours par module</h2>
        </div>
        <?php if (empty($stats['cours_par_module'])): ?>
            <p class="text-muted text-center" style="padding: 2rem;">Aucune donnée</p>
        <?php else: ?>
            <?php 
            $max_cours = max(array_column($stats['cours_par_module'], 'nb_cours'));
            ?>
            <div class="bar-chart" style="padding-bottom: 30px;">
                <?php foreach ($stats['cours_par_module'] as $cm): ?>
                    <?php $height = $max_cours > 0 ? ($cm['nb_cours'] / $max_cours) * 120 : 0; ?>
                    <div class="bar" style="height: <?php echo max(5, $height); ?>px; background: var(--primary);">
                        <span class="bar-value"><?php echo $cm['nb_cours']; ?></span>
                        <span class="bar-label"><?php echo htmlspecialchars(substr($cm['nom'], 0, 10)); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Activité récente -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-calendar-check"></i> Activité des 7 derniers jours</h2>
        </div>
        <?php if (empty($stats['activite'])): ?>
            <p class="text-muted text-center" style="padding: 2rem;">Aucune activité récente</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Évaluations passées</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['activite'] as $a): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($a['jour'])); ?></td>
                                <td><?php echo $a['nb_evaluations']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
