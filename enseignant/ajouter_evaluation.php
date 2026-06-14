<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_role('enseignant');

$user = get_user_info();
$enseignant_id = $_SESSION['user_id'];
$lecon_id = isset($_GET['lecon']) ? (int)$_GET['lecon'] : 0;
$message = '';
$evaluation_creee = false;

// Vérifier que la leçon appartient à un cours de l'enseignant
$stmt = $pdo->prepare("
    SELECT l.*, c.titre as cours_titre, c.id as cours_id
    FROM lecons l
    JOIN cours c ON l.cours_id = c.id
    WHERE l.id = ? AND c.enseignant_id = ?
");
$stmt->execute([$lecon_id, $enseignant_id]);
$lecon = $stmt->fetch();

if (!$lecon) {
    header('Location: dashboard.php');
    exit();
}

// Vérifier si une évaluation existe déjà pour cette leçon
$stmt = $pdo->prepare("SELECT id FROM evaluations WHERE lecon_id = ?");
$stmt->execute([$lecon_id]);
$evaluation_existante = $stmt->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = clean_input($_POST['titre'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $questions = $_POST['questions'] ?? [];
    
    if (empty($titre)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Le titre est obligatoire.</div>';
    } elseif (empty($questions)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Ajoutez au moins une question.</div>';
    } else {
        // Créer l'évaluation
        $stmt = $pdo->prepare("INSERT INTO evaluations (lecon_id, titre, description) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$lecon_id, $titre, $description])) {
            $evaluation_id = $pdo->lastInsertId();
            
            // Ajouter les questions
            $stmt_q = $pdo->prepare("INSERT INTO questions (evaluation_id, question, option_a, option_b, option_c, option_d, bonne_reponse) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $success = true;
            foreach ($questions as $q) {
                if (!empty($q['question']) && !empty($q['a']) && !empty($q['b']) && !empty($q['c']) && !empty($q['d'])) {
                    if (!$stmt_q->execute([
                        $evaluation_id,
                        clean_input($q['question']),
                        clean_input($q['a']),
                        clean_input($q['b']),
                        clean_input($q['c']),
                        clean_input($q['d']),
                        strtoupper(clean_input($q['correct']))
                    ])) {
                        $success = false;
                        break;
                    }
                }
            }
            
            if ($success) {
                $evaluation_creee = true;
                $message = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Évaluation créée avec succès !</div>';
            } else {
                $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erreur lors de l\'ajout des questions.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Erreur lors de la création.</div>';
        }
    }
}

$page_title = "Créer une évaluation";
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
    <style>
        .question-form {
            background: var(--bg-light);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }
        .question-form .form-group {
            margin-bottom: 1rem;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .question-number {
            font-weight: 600;
            color: var(--primary);
        }
        .btn-remove {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 1.1rem;
        }
        .btn-remove:hover {
            color: #dc2626;
        }
        .correct-answer {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: rgba(16,185,129,0.1);
            border-radius: var(--radius);
            margin-top: 1rem;
        }
        .correct-answer label {
            margin: 0;
            font-weight: 500;
            color: var(--success);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="bi bi-check-square"></i> Créer une évaluation</h1>
                <p>Leçon: <?php echo htmlspecialchars($lecon['titre']); ?></p>
            </div>
            <a href="dashboard.php" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <?php if ($evaluation_existante): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            Une évaluation existe déjà pour cette leçon.
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px;">
        <?php echo $message; ?>
        
        <?php if (!$evaluation_creee && !$evaluation_existante): ?>
        <form method="POST" action="" id="eval-form">
            <div class="form-group">
                <label for="titre">
                    <i class="bi bi-type"></i> Titre de l'évaluation *
                </label>
                <input type="text" id="titre" name="titre" class="form-control" 
                       placeholder="Ex: QCM sur les variables PHP" required
                       value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="bi bi-text-paragraph"></i> Description
                </label>
                <textarea id="description" name="description" class="form-control" 
                          placeholder="Instructions pour les étudiants..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div id="questions-container">
                <h3 style="margin-bottom: 1rem;"><i class="bi bi-list-ol"></i> Questions</h3>
                
                <div class="question-form" id="question-1">
                    <div class="question-header">
                        <span class="question-number">Question 1</span>
                        <button type="button" class="btn-remove" onclick="removeQuestion(1)" style="display: none;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Question *</label>
                        <input type="text" name="questions[1][question]" class="form-control" 
                               placeholder="Entrez votre question" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Option A *</label>
                            <input type="text" name="questions[1][a]" class="form-control" placeholder="Réponse A" required>
                        </div>
                        <div class="form-group">
                            <label>Option B *</label>
                            <input type="text" name="questions[1][b]" class="form-control" placeholder="Réponse B" required>
                        </div>
                        <div class="form-group">
                            <label>Option C *</label>
                            <input type="text" name="questions[1][c]" class="form-control" placeholder="Réponse C" required>
                        </div>
                        <div class="form-group">
                            <label>Option D *</label>
                            <input type="text" name="questions[1][d]" class="form-control" placeholder="Réponse D" required>
                        </div>
                    </div>
                    
                    <div class="correct-answer">
                        <label>Bonne réponse : </label>
                        <select name="questions[1][correct]" class="form-control" style="width: auto;">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <button type="button" onclick="addQuestion()" class="btn-secondary mb-3">
                <i class="bi bi-plus-circle"></i> Ajouter une question
            </button>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-lg"></i> Créer l'évaluation
                </button>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="bi bi-x-lg"></i> Annuler
                </a>
            </div>
        </form>
        <?php else: ?>
        <div style="text-align: center; padding: 2rem 0;">
            <a href="dashboard.php" class="btn-primary">
                <i class="bi bi-house"></i> Retour au dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        let questionCount = 1;
        
        function addQuestion() {
            questionCount++;
            const container = document.getElementById('questions-container');
            const newQuestion = document.getElementById('question-1').cloneNode(true);
            
            newQuestion.id = 'question-' + questionCount;
            newQuestion.querySelector('.question-number').textContent = 'Question ' + questionCount;
            newQuestion.querySelector('.btn-remove').style.display = 'block';
            
            // Mettre à jour les noms
            newQuestion.querySelectorAll('input, select').forEach(el => {
                el.name = el.name.replace(/\[1\]/, '[' + questionCount + ']');
                if (el.type !== 'hidden') el.value = '';
            });
            
            container.appendChild(newQuestion);
        }
        
        function removeQuestion(num) {
            const question = document.getElementById('question-' + num);
            if (question && questionCount > 1) {
                question.remove();
                // Réorganiser les numéros
                const questions = document.querySelectorAll('.question-form');
                questions.forEach((q, index) => {
                    q.querySelector('.question-number').textContent = 'Question ' + (index + 1);
                });
            }
        }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
