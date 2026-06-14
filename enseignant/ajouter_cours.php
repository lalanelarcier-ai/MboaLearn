<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('enseignant');

$user = get_user_info();
$enseignant_id = $_SESSION['user_id'];
$message = '';
$cours_cree = false;

// Récupérer les modules disponibles
$stmt = $pdo->query("SELECT id, nom FROM modules ORDER BY nom");
$modules = $stmt->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = clean_input($_POST['titre'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $module_id = !empty($_POST['module_id']) ? (int)$_POST['module_id'] : null;
    
    if (empty($titre)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Le titre est obligatoire.</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO cours (titre, description, module_id, enseignant_id) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$titre, $description, $module_id, $enseignant_id])) {
            $cours_id = $pdo->lastInsertId();
            $cours_cree = true;
            $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Cours créé avec succès ! <a href="ajouter_lecon.php?cours=' . $cours_id . '">Ajouter une leçon</a></div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erreur lors de la création.</div>';
        }
    }
}

$page_title = "Nouveau cours";
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
                <h1><i class="bi bi-plus-circle"></i> Nouveau cours</h1>
                <p>Créez un nouveau cours</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card" style="max-width: 700px;">
        <?php echo $message; ?>
        
        <?php if (!$cours_cree): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="titre">
                    <i class="bi bi-type"></i> Titre du cours *
                </label>
                <input type="text" id="titre" name="titre" class="form-control" 
                       placeholder="Ex: Introduction à PHP" required
                       value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="bi bi-text-paragraph"></i> Description
                </label>
                <textarea id="description" name="description" class="form-control" 
                          placeholder="Décrivez le contenu de ce cours..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="module_id">
                    <i class="bi bi-tag"></i> Module
                </label>
                <select id="module_id" name="module_id" class="form-control">
                    <option value="">-- Sélectionner un module (optionnel) --</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo (isset($_POST['module_id']) && $_POST['module_id'] == $m['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-lg"></i> Créer le cours
                </button>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="bi bi-x-lg"></i> Annuler
                </a>
            </div>
        </form>
        <?php else: ?>
        <div style="text-align: center; padding: 2rem 0;">
            <a href="ajouter_lecon.php?cours=<?php echo $cours_id; ?>" class="btn-primary" style="margin-right: 1rem;">
                <i class="bi bi-plus-circle"></i> Ajouter une leçon
            </a>
            <a href="dashboard.php" class="btn-secondary">
                <i class="bi bi-house"></i> Retour au dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
