<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role('etudiant');

$user = get_user_info();
$etudiant_id = $_SESSION['user_id'];
$evaluation_id = isset($_GET['eval']) ? (int)$_GET['eval'] : 0;
$resultat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$evaluation_id) {
    header('Location: cours.php');
    exit();
}

// Récupérer le résultat
if ($resultat_id) {
    $stmt = $pdo->prepare("SELECT * FROM resultats WHERE id = ? AND etudiant_id = ?");
    $stmt->execute([$resultat_id, $etudiant_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM resultats WHERE evaluation_id = ? AND etudiant_id = ? ORDER BY date_passage DESC LIMIT 1");
    $stmt->execute([$evaluation_id, $etudiant_id]);
}
$resultat = $stmt->fetch();

if (!$resultat) {
    header('Location: evaluation.php?id=' . $evaluation_id);
    exit();
}

// Récupérer les détails de l'évaluation
$stmt = $pdo->prepare("
    SELECT e.*, l.titre as lecon_titre, l.cours_id, l.id as lecon_id
    FROM evaluations e
    JOIN lecons l ON e.lecon_id = l.id
    WHERE e.id = ?
");
$stmt->execute([$evaluation_id]);
$evaluation = $stmt->fetch();

// Récupérer les réponses de l'étudiant avec les bonnes réponses
$stmt = $pdo->prepare("
    SELECT r.reponse, q.*
    FROM reponses r
    JOIN questions q ON r.question_id = q.id
    WHERE r.etudiant_id = ? AND r.evaluation_id = ?
");
$stmt->execute([$etudiant_id, $evaluation_id]);
$reponses = $stmt->fetchAll();

// Vérifier si un certificat existe pour cette évaluation
$stmt = $pdo->prepare("SELECT id FROM certificats WHERE etudiant_id = ? AND evaluation_id = ?");
$stmt->execute([$etudiant_id, $evaluation_id]);
$certificat = $stmt->fetch();

$page_title = "Résultats";
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
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="bi bi-bar-chart"></i> Résultats</h1>
                <p><?php echo htmlspecialchars($evaluation['titre']); ?></p>
            </div>
            <a href="lecon.php?id=<?php echo $evaluation['lecon_id']; ?>" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la leçon
            </a>
        </div>
    </div>

    <!-- Résumé -->
    <div class="card" style="text-align: center; margin-bottom: 2rem;">
        <div style="margin-bottom: 1rem;">
            <?php 
            $note = $resultat['note'];
            $icon = $note >= 70 ? 'check-circle-fill' : ($note >= 50 ? 'exclamation-circle' : 'x-circle');
            $color = $note >= 70 ? 'var(--success)' : ($note >= 50 ? 'var(--warning)' : 'var(--danger)');
            ?>
            <i class="bi bi-<?php echo $icon; ?>" style="font-size: 4rem; color: <?php echo $color; ?>;"></i>
        </div>
        
        <h2 style="font-size: 3rem; margin-bottom: 0.5rem; color: <?php echo $color; ?>;">
            <?php echo number_format($note, 1); ?>%
        </h2>
        
        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1rem;">
            <?php echo $resultat['nombre_bonnes_reponses']; ?> bonne(s) réponse(s) sur <?php echo $resultat['nombre_total_questions']; ?>
        </p>
        
        <?php if ($note >= 70): ?>
            <div class="alert alert-success" style="max-width: 400px; margin: 0 auto;">
                <i class="bi bi-trophy"></i> Félicitations ! Vous avez réussi l'évaluation.
            </div>
        <?php elseif ($note >= 50): ?>
            <div class="alert alert-success" style="max-width: 400px; margin: 0 auto;">
                <i class="bi bi-check-circle"></i> Évaluation réussie ! Vous avez obtenu votre certificat.
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="max-width: 400px; margin: 0 auto;">
                <i class="bi bi-arrow-repeat"></i> Vous pouvez réessayer cette évaluation.
            </div>
        <?php endif; ?>
        
        <?php if ($certificat && $note >= 50): ?>
            <div style="margin-top: 1.5rem;">
                <a href="certificate.php?id=<?php echo $certificat['id']; ?>" class="btn-success">
                    <i class="bi bi-award"></i> Voir mon certificat
                </a>
            </div>
        <?php endif; ?>
        
        <p class="text-muted" style="margin-top: 1rem; font-size: 0.9rem;">
            Passé le <?php echo date('d/m/Y à H:i', strtotime($resultat['date_passage'])); ?>
        </p>
    </div>

    <!-- Détail des réponses -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-list-check"></i> Détail des réponses</h2>
        </div>
        
        <?php foreach ($reponses as $index => $rep): ?>
            <?php 
            $est_correcte = $rep['reponse'] === $rep['bonne_reponse'];
            ?>
            <div class="question-card" style="border-left-color: <?php echo $est_correcte ? 'var(--success)' : 'var(--danger)'; ?>;">
                <div class="question-number" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Question <?php echo $index + 1; ?></span>
                    <span style="font-size: 1.25rem;">
                        <i class="bi bi-<?php echo $est_correcte ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                    </span>
                </div>
                <div class="question-text"><?php echo htmlspecialchars($rep['question']); ?></div>
                
                <div class="options-list">
                    <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                        <?php 
                        $is_selected = $rep['reponse'] === $opt;
                        $is_correct = $rep['bonne_reponse'] === $opt;
                        $class = '';
                        if ($is_correct) $class = 'correct';
                        elseif ($is_selected && !$is_correct) $class = 'incorrect';
                        ?>
                        <div class="option-item <?php echo $class; ?>">
                            <span><strong><?php echo $opt; ?>.</strong> <?php echo htmlspecialchars($rep["option_$opt"]); ?></span>
                            <?php if ($is_selected): ?>
                                <span style="margin-left: auto;">
                                    <i class="bi bi-<?php echo $is_correct ? 'check-lg text-success' : 'x-lg text-danger'; ?>"></i>
                                    <?php if ($is_selected) echo ' (votre réponse)'; ?>
                                </span>
                            <?php elseif ($is_correct): ?>
                                <span style="margin-left: auto; color: var(--success); font-size: 0.85rem;">
                                    (bonne réponse)
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="lecon.php?id=<?php echo $evaluation['lecon_id']; ?>" class="btn-secondary" style="margin-right: 1rem;">
            <i class="bi bi-arrow-left"></i> Retour à la leçon
        </a>
        <?php if ($note < 50): ?>
            <a href="evaluation.php?id=<?php echo $evaluation_id; ?>" class="btn-primary">
                <i class="bi bi-arrow-repeat"></i> Réessayer
            </a>
        <?php endif; ?>
        <?php if ($certificat && $note >= 50): ?>
            <a href="certificate.php?id=<?php echo $certificat['id']; ?>" class="btn-success" style="margin-left: 1rem;">
                <i class="bi bi-award"></i> Télécharger le certificat
            </a>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
