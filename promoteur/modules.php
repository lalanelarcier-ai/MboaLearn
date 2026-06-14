<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('promoteur');

$user = get_user_info();
$message = '';
$module_cree = false;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nom = clean_input($_POST['nom'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        
        if (empty($nom)) {
            $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Le nom est obligatoire.</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO modules (nom, description) VALUES (?, ?)");
            if ($stmt->execute([$nom, $description])) {
                $module_cree = true;
                $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Module ajouté avec succès !</div>';
            } else {
                $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erreur lors de l\'ajout.</div>';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Module supprimé.</div>';
    }
}

// Récupérer les modules
$stmt = $pdo->query("
    SELECT m.*, 
           COUNT(DISTINCT c.id) as nb_cours,
           COUNT(DISTINCT cert.id) as nb_certificats
    FROM modules m
    LEFT JOIN cours c ON m.id = c.module_id
    LEFT JOIN certificats cert ON m.id = cert.module_id
    GROUP BY m.id
    ORDER BY m.nom
");
$modules = $stmt->fetchAll();

$page_title = "Gestion des modules";
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
                <h1><i class="bi bi-grid"></i> Gestion des modules</h1>
                <p>Créez et gérez les modules de cours</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <?php echo $message; ?>

    <?php if (!$module_cree): ?>
    <!-- Formulaire d'ajout -->
    <div class="card" style="max-width: 600px; margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-plus-circle"></i> Ajouter un module</h2>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="nom">
                    <i class="bi bi-tag"></i> Nom du module *
                </label>
                <input type="text" id="nom" name="nom" class="form-control" 
                       placeholder="Ex: Développement Web" required>
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="bi bi-text-paragraph"></i> Description
                </label>
                <textarea id="description" name="description" class="form-control" 
                          placeholder="Décrivez le module..."></textarea>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="bi bi-check-lg"></i> Ajouter
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Liste des modules -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-list"></i> Modules existants</h2>
        </div>
        
        <?php if (empty($modules)): ?>
            <p class="text-muted text-center" style="padding: 2rem;">Aucun module créé</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Cours</th>
                            <th>Certificats</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $m): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($m['nom']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($m['description'] ?? '', 0, 80)); ?></td>
                                <td><?php echo $m['nb_cours']; ?></td>
                                <td><?php echo $m['nb_certificats']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($m['date_creation'])); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;" 
                                          onsubmit="return confirm('Supprimer ce module ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" class="btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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
