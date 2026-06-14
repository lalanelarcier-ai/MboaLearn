<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('enseignant');

$user = get_user_info();
$enseignant_id = $_SESSION['user_id'];
$cours_id = isset($_GET['cours']) ? (int)$_GET['cours'] : 0;
$message = '';
$lecon_creee = false;

// Vérifier que le cours appartient à l'enseignant
$stmt = $pdo->prepare("SELECT * FROM cours WHERE id = ? AND enseignant_id = ?");
$stmt->execute([$cours_id, $enseignant_id]);
$cours = $stmt->fetch();

if (!$cours) {
    header('Location: dashboard.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = clean_input($_POST['titre'] ?? '');
    $contenu = clean_input($_POST['contenu'] ?? '');
    $type_contenu = clean_input($_POST['type_contenu'] ?? 'pdf');
    $ordre = (int)($_POST['ordre'] ?? 0);
    
    if (empty($titre)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Le titre est obligatoire.</div>';
    } elseif (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Veuillez sélectionner un fichier.</div>';
    } else {
        // Vérifier le type de fichier
        $allowed_types = $type_contenu === 'video' 
            ? ['video/mp4', 'video/webm', 'video/ogg']
            : ['application/pdf'];
        
        $fichier_type = $_FILES['fichier']['type'];
        if (!in_array($fichier_type, $allowed_types)) {
            $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Type de fichier non autorisé.</div>';
        } else {
            // Upload du fichier
            $upload_dir = __DIR__ . '/../uploads/cours/';
            $fichier_nom = uniqid() . '_' . basename($_FILES['fichier']['name']);
            $fichier_path = $upload_dir . $fichier_nom;
            
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $fichier_path)) {
                $stmt = $pdo->prepare("INSERT INTO lecons (cours_id, titre, contenu, type_contenu, fichier, ordre) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$cours_id, $titre, $contenu, $type_contenu, $fichier_nom, $ordre])) {
                    $lecon_creee = true;
                    $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Leçon ajoutée avec succès !</div>';
                } else {
                    unlink($fichier_path);
                    $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erreur lors de l\'ajout.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erreur lors de l\'upload du fichier.</div>';
            }
        }
    }
}

$page_title = "Ajouter une leçon";
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
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="bi bi-plus-circle"></i> Ajouter une leçon</h1>
                <p>Cours: <?php echo htmlspecialchars($cours['titre']); ?></p>
            </div>
            <a href="dashboard.php" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card" style="max-width: 700px;">
        <?php echo $message; ?>
        
        <?php if (!$lecon_creee): ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="titre">
                    <i class="bi bi-type"></i> Titre de la leçon *
                </label>
                <input type="text" id="titre" name="titre" class="form-control" 
                       placeholder="Ex: Les variables en PHP" required
                       value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="contenu">
                    <i class="bi bi-text-paragraph"></i> Description / Notes
                </label>
                <textarea id="contenu" name="contenu" class="form-control" 
                          placeholder="Décrivez le contenu de cette leçon..."><?php echo htmlspecialchars($_POST['contenu'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="type_contenu">
                    <i class="bi bi-file-earmark"></i> Type de contenu *
                </label>
                <select id="type_contenu" name="type_contenu" class="form-control" required>
                    <option value="pdf" <?php echo ($_POST['type_contenu'] ?? '') === 'pdf' ? 'selected' : ''; ?>>Document PDF</option>
                    <option value="video" <?php echo ($_POST['type_contenu'] ?? '') === 'video' ? 'selected' : ''; ?>>Vidéo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="fichier">
                    <i class="bi bi-upload"></i> Fichier *
                </label>
                <input type="file" id="fichier" name="fichier" class="form-control" required
                       accept=".pdf,video/mp4,video/webm,video/ogg">
                <small class="text-muted" id="file-hint">Formats acceptés: PDF (max 10 Mo)</small>
            </div>
            
            <div class="form-group">
                <label for="ordre">
                    <i class="bi bi-sort-numeric-up"></i> Ordre d'affichage
                </label>
                <input type="number" id="ordre" name="ordre" class="form-control" 
                       min="1" value="<?php echo $_POST['ordre'] ?? 1; ?>">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-lg"></i> Ajouter la leçon
                </button>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="bi bi-x-lg"></i> Annuler
                </a>
            </div>
        </form>
        <?php else: ?>
        <div style="text-align: center; padding: 2rem 0;">
            <a href="mes_lecons.php" class="btn-primary" style="margin-right: 1rem;">
                <i class="bi bi-list-check"></i> Voir les leçons
            </a>
            <a href="ajouter_lecon.php?cours=<?php echo $cours_id; ?>" class="btn-secondary">
                <i class="bi bi-plus-circle"></i> Ajouter une autre leçon
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('type_contenu').addEventListener('change', function() {
            const fileInput = document.getElementById('fichier');
            const hint = document.getElementById('file-hint');
            
            if (this.value === 'video') {
                fileInput.accept = 'video/mp4,video/webm,video/ogg';
                hint.textContent = 'Formats acceptés: MP4, WebM, OGG (max 50 Mo)';
            } else {
                fileInput.accept = '.pdf';
                hint.textContent = 'Formats acceptés: PDF (max 10 Mo)';
            }
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
