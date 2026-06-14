<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role('etudiant');

$user = get_user_info();
$etudiant_id = $_SESSION['user_id'];
$lecon_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$lecon_id) {
    header('Location: cours.php');
    exit();
}

// Récupérer la leçon
$stmt = $pdo->prepare("
    SELECT l.*, c.titre as cours_titre, c.id as cours_id,
           e.id as evaluation_id, e.titre as evaluation_titre
    FROM lecons l
    JOIN cours c ON l.cours_id = c.id
    LEFT JOIN evaluations e ON l.id = e.lecon_id
    WHERE l.id = ?
");
$stmt->execute([$lecon_id]);
$lecon = $stmt->fetch();

if (!$lecon) {
    header('Location: cours.php');
    exit();
}

// Marquer la leçon comme "en cours" si pas encore fait
$stmt = $pdo->prepare("
    INSERT INTO progression (etudiant_id, lecon_id, statut) 
    VALUES (?, ?, 'en_cours')
    ON DUPLICATE KEY UPDATE statut = IF(statut = 'non_commence', 'en_cours', statut), date_mise_a_jour = NOW()
");
$stmt->execute([$etudiant_id, $lecon_id]);

$page_title = $lecon['titre'];
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
                <h1><i class="bi bi-<?php echo $lecon['type_contenu'] === 'video' ? 'camera-video' : 'file-pdf'; ?>"></i> <?php echo htmlspecialchars($lecon['titre']); ?></h1>
                <p>
                    <a href="cours.php?view=<?php echo $lecon['cours_id']; ?>" style="color: var(--primary); text-decoration: none;">
                        <?php echo htmlspecialchars($lecon['cours_titre']); ?>
                    </a>
                </p>
            </div>
            <a href="cours.php?view=<?php echo $lecon['cours_id']; ?>" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour au cours
            </a>
        </div>
    </div>

    <!-- Contenu de la leçon -->
    <div class="card">
        <?php if ($lecon['contenu']): ?>
            <div class="card-content" style="margin-bottom: 1.5rem;">
                <?php echo nl2br(htmlspecialchars($lecon['contenu'])); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($lecon['type_contenu'] === 'video'): ?>
            <!-- Lecteur vidéo -->
            <div class="video-container">
                <video controls id="video-player">
                    <source src="uploads/cours/<?php echo htmlspecialchars($lecon['fichier']); ?>" type="video/mp4">
                    Votre navigateur ne supporte pas la vidéo.
                </video>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                Regardez la vidéo jusqu'à la fin pour pouvoir accéder à l'évaluation.
            </div>
        <?php else: ?>
            <!-- Viewer PDF -->
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <iframe src="uploads/cours/<?php echo htmlspecialchars($lecon['fichier']); ?>" 
                        class="pdf-viewer" 
                        frameborder="0"></iframe>
            </div>
            
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <a href="uploads/cours/<?php echo htmlspecialchars($lecon['fichier']); ?>" 
                   class="btn-primary" download>
                    <i class="bi bi-download"></i> Télécharger le PDF
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bouton pour marquer comme terminé et accéder à l'évaluation -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-check-circle"></i> Terminer cette leçon</h2>
        </div>
        
        <div class="card-content">
            <p>Une fois que vous avez terminé cette leçon, vous pouvez passer l'évaluation.</p>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button onclick="markAsCompleted()" class="btn-success" id="btn-complete">
                    <i class="bi bi-check-lg"></i> Marquer comme terminé
                </button>
                
                <?php if ($lecon['evaluation_id']): ?>
                    <a href="evaluation.php?id=<?php echo $lecon['evaluation_id']; ?>" 
                       class="btn-primary" id="btn-eval" style="opacity: 0.5; pointer-events: none;">
                        <i class="bi bi-check-square"></i> Passer l'évaluation
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function markAsCompleted() {
            fetch('ajax/update_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'lecon_id=<?php echo $lecon_id; ?>&statut=termine'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('btn-complete').innerHTML = '<i class="bi bi-check-circle-fill"></i> Terminé';
                    document.getElementById('btn-complete').disabled = true;
                    document.getElementById('btn-complete').style.opacity = '0.7';
                    
                    <?php if ($lecon['evaluation_id']): ?>
                    const btnEval = document.getElementById('btn-eval');
                    btnEval.style.opacity = '1';
                    btnEval.style.pointerEvents = 'auto';
                    <?php endif; ?>
                }
            });
        }
        
        // Vidéo terminée
        document.getElementById('video-player')?.addEventListener('ended', function() {
            markAsCompleted();
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
