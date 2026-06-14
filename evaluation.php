<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_role('etudiant');

$user = get_user_info();
$etudiant_id = $_SESSION['user_id'];
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$evaluation_id) {
    header('Location: cours.php');
    exit();
}

// Vérifier si l'étudiant a déjà passé cette évaluation
$stmt = $pdo->prepare("SELECT id FROM resultats WHERE etudiant_id = ? AND evaluation_id = ?");
$stmt->execute([$etudiant_id, $evaluation_id]);
$deja_passe = $stmt->fetch();

// Récupérer l'évaluation
$stmt = $pdo->prepare("
    SELECT e.*, l.titre as lecon_titre, l.cours_id, l.id as lecon_id
    FROM evaluations e
    JOIN lecons l ON e.lecon_id = l.id
    WHERE e.id = ?
");
$stmt->execute([$evaluation_id]);
$evaluation = $stmt->fetch();

if (!$evaluation) {
    header('Location: cours.php');
    exit();
}

// Récupérer les questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE evaluation_id = ? ORDER BY id");
$stmt->execute([$evaluation_id]);
$questions = $stmt->fetchAll();

$page_title = $evaluation['titre'];
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
                <h1><i class="bi bi-check-square"></i> <?php echo htmlspecialchars($evaluation['titre']); ?></h1>
                <p>
                    Leçon: 
                    <a href="lecon.php?id=<?php echo $evaluation['lecon_id']; ?>" style="color: var(--primary); text-decoration: none;">
                        <?php echo htmlspecialchars($evaluation['lecon_titre']); ?>
                    </a>
                </p>
            </div>
            <a href="lecon.php?id=<?php echo $evaluation['lecon_id']; ?>" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <?php if ($deja_passe): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            Vous avez déjà passé cette évaluation. 
            <a href="resultats.php?eval=<?php echo $evaluation_id; ?>" style="color: inherit; font-weight: 600;">
                Voir vos résultats
            </a>
        </div>
    <?php endif; ?>

    <div class="quiz-container" id="quiz-container">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
            <p class="text-muted">
                <i class="bi bi-info-circle"></i> 
                <?php echo count($questions); ?> question(s) - 
                Répondez à toutes les questions puis cliquez sur "Soumettre"
            </p>
        </div>

        <form id="quiz-form" onsubmit="submitQuiz(event)">
            <input type="hidden" name="evaluation_id" value="<?php echo $evaluation_id; ?>">
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <div class="question-number">Question <?php echo $index + 1; ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                    
                    <div class="options-list">
                        <label class="option-item" onclick="selectOption(this, <?php echo $question['id']; ?>, 'A')">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="A" required>
                            <span><strong>A.</strong> <?php echo htmlspecialchars($question['option_a']); ?></span>
                        </label>
                        
                        <label class="option-item" onclick="selectOption(this, <?php echo $question['id']; ?>, 'B')">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="B" required>
                            <span><strong>B.</strong> <?php echo htmlspecialchars($question['option_b']); ?></span>
                        </label>
                        
                        <label class="option-item" onclick="selectOption(this, <?php echo $question['id']; ?>, 'C')">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="C" required>
                            <span><strong>C.</strong> <?php echo htmlspecialchars($question['option_c']); ?></span>
                        </label>
                        
                        <label class="option-item" onclick="selectOption(this, <?php echo $question['id']; ?>, 'D')">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="D" required>
                            <span><strong>D.</strong> <?php echo htmlspecialchars($question['option_d']); ?></span>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn-primary" style="padding: 0.8rem 2rem;">
                    <i class="bi bi-send"></i> Soumettre mes réponses
                </button>
            </div>
        </form>
    </div>

    <script>
        let answers = {};
        
        function selectOption(element, questionId, answer) {
            // Retirer la sélection des autres options de cette question
            const questionCard = element.closest('.question-card');
            questionCard.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Ajouter la sélection à l'option cliquée
            element.classList.add('selected');
            answers[questionId] = answer;
        }
        
        function submitQuiz(e) {
            e.preventDefault();
            
            const form = document.getElementById('quiz-form');
            const formData = new FormData(form);
            
            // Vérifier que toutes les réponses sont fournies
            const totalQuestions = <?php echo count($questions); ?>;
            let answered = 0;
            
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('question_')) {
                    answered++;
                }
            }
            
            if (answered < totalQuestions) {
                alert('Veuillez répondre à toutes les questions avant de soumettre.');
                return;
            }
            
            // Envoyer les réponses via AJAX
            fetch('ajax/submit_quiz.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rediriger vers les résultats
                    window.location.href = 'resultats.php?eval=<?php echo $evaluation_id; ?>&id=' + data.resultat_id;
                } else {
                    alert('Erreur lors de la soumission: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la soumission du quiz.');
            });
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
