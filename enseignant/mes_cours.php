<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('enseignant');

$user = get_user_info();
$enseignant_id = $_SESSION['user_id'];
$message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Vérifier que le cours appartient à l'enseignant
        $stmt = $pdo->prepare("DELETE FROM cours WHERE id = ? AND enseignant_id = ?");
        $stmt->execute([$id, $enseignant_id]);
        $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Cours supprimé.</div>';
    }
}

// Récupérer les cours de l'enseignant avec détails
$stmt = $pdo->prepare("
    SELECT c.*, m.nom as module_nom,
           COUNT(DISTINCT l.id) as total_lecons,
           COUNT(DISTINCT e.id) as total_evaluations
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

$page_title = "Mes cours";
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
        <h1><i class="bi bi-collection"></i> Mes cours</h1>
        <p>Gérez vos cours et leurs contenus</p>
    </div>

    <?php echo $message; ?>

    <!-- Liste des cours -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-list"></i> Cours existants</h2>
        </div>
        
        <?php if (empty($cours)): ?>
            <p class="text-muted text-center" style="padding: 2rem;">Aucun cours créé</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Module</th>
                            <th>Leçons</th>
                            <th>Évaluations</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cours as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['titre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['module_nom'] ?? 'N/A'); ?></td>
                                <td><?php echo $c['total_lecons']; ?></td>
                                <td><?php echo $c['total_evaluations']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($c['date_creation'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="ajouter_lecon.php?cours=<?php echo $c['id']; ?>" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                            <i class="bi bi-plus"></i> Leçon
                                        </a>
                                        
                                        <?php if ($c['total_lecons'] > 0): ?>
                                            <?php
                                            $stmt_l = $pdo->prepare("SELECT id FROM lecons WHERE cours_id = ? ORDER BY ordre DESC LIMIT 1");
                                            $stmt_l->execute([$c['id']]);
                                            $derniere_lecon = $stmt_l->fetch();
                                            ?>
                                            <a href="ajouter_evaluation.php?lecon=<?php echo $derniere_lecon['id']; ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                                <i class="bi bi-check-square"></i> Éval
                                            </a>
                                        <?php endif; ?>
                                        
                                        <form method="POST" action="" style="display: inline;" 
                                              onsubmit="return confirm('Supprimer ce cours et toutes ses leçons ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
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
