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
        // Vérifier que la leçon appartient à un cours de l'enseignant
        $stmt = $pdo->prepare("
            DELETE l FROM lecons l
            JOIN cours c ON l.cours_id = c.id
            WHERE l.id = ? AND c.enseignant_id = ?
        ");
        $stmt->execute([$id, $enseignant_id]);
        $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Leçon supprimée.</div>';
    }
}

// Récupérer les leçons de l'enseignant
$stmt = $pdo->prepare("
    SELECT l.*, c.titre as cours_titre,
           CASE WHEN e.id IS NOT NULL THEN 1 ELSE 0 END as a_evaluation
    FROM lecons l
    JOIN cours c ON l.cours_id = c.id
    LEFT JOIN evaluations e ON l.id = e.lecon_id
    WHERE c.enseignant_id = ?
    ORDER BY c.titre, l.ordre
");
$stmt->execute([$enseignant_id]);
$lecons = $stmt->fetchAll();

$page_title = "Gérer les leçons";
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
        <h1><i class="bi bi-list-check"></i> Gérer les leçons</h1>
        <p>Ajoutez, visualisez ou supprimez vos leçons</p>
    </div>

    <?php echo $message; ?>

    <!-- Liste des leçons -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-list"></i> Toutes les leçons</h2>
        </div>
        
        <?php if (empty($lecons)): ?>
            <p class="text-muted text-center" style="padding: 2rem;">Aucune leçon créée</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ordre</th>
                            <th>Titre</th>
                            <th>Cours</th>
                            <th>Type</th>
                            <th>Évaluation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lecons as $l): ?>
                            <tr>
                                <td><?php echo $l['ordre']; ?></td>
                                <td><strong><?php echo htmlspecialchars($l['titre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($l['cours_titre']); ?></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $l['type_contenu'] === 'video' ? '#8b5cf6' : '#ef4444'; ?>; color: white;">
                                        <?php echo strtoupper($l['type_contenu']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($l['a_evaluation']): ?>
                                        <span class="badge" style="background: var(--success); color: white;"><i class="bi bi-check"></i> Oui</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: var(--text-muted); color: white;">Non</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if (!$l['a_evaluation']): ?>
                                            <a href="ajouter_evaluation.php?lecon=<?php echo $l['id']; ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                                <i class="bi bi-check-square"></i> Éval
                                            </a>
                                        <?php endif; ?>
                                        
                                        <form method="POST" action="" style="display: inline;" 
                                              onsubmit="return confirm('Supprimer cette leçon ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
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
