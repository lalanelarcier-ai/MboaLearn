<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role('etudiant');

$user = get_user_info();
$etudiant_id = $_SESSION['user_id'];

// Récupérer les certificats de l'étudiant
$stmt = $pdo->prepare("
    SELECT cert.*, 
           COALESCE(m.nom, e.titre) as source_nom,
           l.titre as lecon_titre,
           c.titre as cours_titre
    FROM certificats cert
    LEFT JOIN modules m ON cert.module_id = m.id
    LEFT JOIN evaluations e ON cert.evaluation_id = e.id
    LEFT JOIN lecons l ON e.lecon_id = l.id
    LEFT JOIN cours c ON l.cours_id = c.id
    WHERE cert.etudiant_id = ?
    ORDER BY cert.date_obtention DESC
");
$stmt->execute([$etudiant_id]);
$certificats = $stmt->fetchAll();

$page_title = "Mes Certificats";
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
        <h1><i class="bi bi-award"></i> Mes Certificats</h1>
        <p>Vos certificats de validation</p>
    </div>

    <?php if (empty($certificats)): ?>
        <div class="card text-center" style="padding: 3rem;">
            <i class="bi bi-award" style="font-size: 4rem; color: var(--text-muted);"></i>
            <h3 style="margin-top: 1rem;">Aucun certificat</h3>
            <p class="text-muted">Passez les évaluations avec un score ≥ 50% pour obtenir des certificats !</p>
            <a href="cours.php" class="btn-primary mt-2">
                <i class="bi bi-collection-play"></i> Découvrir les cours
            </a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-list"></i> <?php echo count($certificats); ?> certificat(s) obtenu(s)</h2>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><i class="bi bi-book"></i> Cours</th>
                            <th><i class="bi bi-list-check"></i> Leçon</th>
                            <th><i class="bi bi-graph-up"></i> Score</th>
                            <th><i class="bi bi-calendar"></i> Date</th>
                            <th><i class="bi bi-hash"></i> N°</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificats as $cert): ?>
                            <tr style="opacity: 0.9;">
                                <td><strong><?php echo htmlspecialchars($cert['cours_titre'] ?? $cert['source_nom']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cert['lecon_titre'] ?? '-'); ?></td>
                                <td>
                                    <span style="color: <?php echo $cert['score_moyen'] >= 70 ? 'var(--success)' : 'var(--primary)'; ?>; font-weight: 600;">
                                        <?php echo number_format($cert['score_moyen'], 1); ?>%
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($cert['date_obtention'])); ?></td>
                                <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo htmlspecialchars($cert['numero_certificat']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="certificate.php?id=<?php echo $cert['id']; ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
                                        <button onclick="printCertificate(<?php echo $cert['id']; ?>)" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                            <i class="bi bi-download"></i> PDF
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function printCertificate(certId) {
            window.location.href = 'certificate.php?id=' + certId;
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
