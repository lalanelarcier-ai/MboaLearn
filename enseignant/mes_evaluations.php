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
        // Vérifier que l'évaluation appartient à un cours de l'enseignant
        $stmt = $pdo->prepare("
            DELETE e FROM evaluations e
            JOIN lecons l ON e.lecon_id = l.id
            JOIN cours c ON l.cours_id = c.id
            WHERE e.id = ? AND c.enseignant_id = ?
        ");
        $stmt->execute([$id, $enseignant_id]);
        $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Évaluation supprimée.</div>';
    }
}

// Récupérer les évaluations de l'enseignant
$stmt = $pdo->prepare("
    SELECT e.*, l.titre as lecon_titre, c.titre as cours_titre,
           COUNT(DISTINCT q.id) as nb_questions,
           COUNT(DISTINCT r.etudiant_id) as nb_passages
    FROM evaluations e
    JOIN lecons l ON e.lecon_id = l.id
    JOIN cours c ON l.cours_id = c.id
    LEFT JOIN questions q ON e.id = q.evaluation_id
    LEFT JOIN resultats r ON e.id = r.evaluation_id
    WHERE c.enseignant_id = ?
    GROUP BY e.id
    ORDER BY c.titre, l.ordre
");
$stmt->execute([$enseignant_id]);
$evaluations = $stmt->fetchAll();

$page_title = "Gérer les évaluations";
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
        <h1><i class="bi bi-check-square"></i> Gérer les évaluations</h1>
        <p>Ajoutez, visualisez ou supprimez vos évaluations</p>
    </div>

    <?php echo $message; ?>

    <!-- Liste des évaluations -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-list"></i> Toutes les évaluations</h2>
        </div>
        
        <?php if (empty($evaluations)): ?>
            <p class="text-muted text-center" style="padding: 2rem;">Aucune évaluation créée</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Leçon</th>
                            <th>Cours</th>
                            <th>Questions</th>
                            <th>Passages</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $e): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($e['titre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($e['lecon_titre']); ?></td>
                                <td><?php echo htmlspecialchars($e['cours_titre']); ?></td>
                                <td><?php echo $e['nb_questions']; ?></td>
                                <td><?php echo $e['nb_passages']; ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <form method="POST" action="" style="display: inline;" 
                                              onsubmit="return confirm('Supprimer cette évaluation ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $e['id']; ?>">
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
