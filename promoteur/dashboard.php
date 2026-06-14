<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('promoteur');

$user = get_user_info();

// Statistiques
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'etudiant'");
$nb_etudiants = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'enseignant'");
$nb_enseignants = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM modules");
$nb_modules = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM cours");
$nb_cours = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM certificats");
$nb_certificats = $stmt->fetch()['total'];

// Derniers inscrits
$stmt = $pdo->query("SELECT nom, email, role, date_inscription FROM users ORDER BY date_inscription DESC LIMIT 5");
$derniers_inscrits = $stmt->fetchAll();

// Derniers résultats
$stmt = $pdo->query("
    SELECT r.*, u.nom as etudiant_nom, e.titre as evaluation_titre
    FROM resultats r
    JOIN users u ON r.etudiant_id = u.id
    JOIN evaluations e ON r.evaluation_id = e.id
    ORDER BY r.date_passage DESC
    LIMIT 5
");
$derniers_resultats = $stmt->fetchAll();

$page_title = "Dashboard Promoteur";
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
        <h1>Espace Promoteur</h1>
        <p>Tableau de bord d'administration</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $nb_etudiants; ?></h3>
                <p>Étudiants</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-easel"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $nb_enseignants; ?></h3>
                <p>Enseignants</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-grid"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $nb_modules; ?></h3>
                <p>Modules</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="bi bi-award"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $nb_certificats; ?></h3>
                <p>Certificats délivrés</p>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-lightning"></i> Actions rapides</h2>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="modules.php" class="btn-primary">
                <i class="bi bi-grid"></i> Gérer les modules
            </a>
            <a href="statistiques.php" class="btn-secondary">
                <i class="bi bi-bar-chart"></i> Statistiques détaillées
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Derniers inscrits -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-person-plus"></i> Derniers inscrits</h2>
            </div>
            
            <?php if (empty($derniers_inscrits)): ?>
                <p class="text-muted text-center" style="padding: 1rem;">Aucun inscrit</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Rôle</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($derniers_inscrits as $inscrit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inscrit['nom']); ?></td>
                                    <td>
                                        <span class="badge"><?php echo ucfirst($inscrit['role']); ?></span>
                                    </td>
                                    <td><?php echo date('d/m', strtotime($inscrit['date_inscription'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Derniers résultats -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-bar-chart"></i> Derniers résultats</h2>
            </div>
            
            <?php if (empty($derniers_resultats)): ?>
                <p class="text-muted text-center" style="padding: 1rem;">Aucun résultat</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($derniers_resultats as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['etudiant_nom']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $r['note'] >= 70 ? 'var(--success)' : ($r['note'] >= 50 ? 'var(--warning)' : 'var(--danger)'); ?>; font-weight: 600;">
                                            <?php echo number_format($r['note'], 1); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
