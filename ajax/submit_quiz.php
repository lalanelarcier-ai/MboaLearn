<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_role('etudiant')) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$etudiant_id = $_SESSION['user_id'];
$evaluation_id = isset($_POST['evaluation_id']) ? (int)$_POST['evaluation_id'] : 0;

if (!$evaluation_id) {
    echo json_encode(['success' => false, 'message' => 'Évaluation non spécifiée']);
    exit();
}

// Vérifier si on a déjà un résultat
$stmt = $pdo->prepare("SELECT id FROM resultats WHERE etudiant_id = ? AND evaluation_id = ?");
$stmt->execute([$etudiant_id, $evaluation_id]);
$exist = $stmt->fetch();

if ($exist) {
    echo json_encode(['success' => false, 'message' => 'Évaluation déjà passée']);
    exit();
}

// Récupérer les questions et leurs bonnes réponses
$stmt = $pdo->prepare("SELECT * FROM questions WHERE evaluation_id = ?");
$stmt->execute([$evaluation_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    echo json_encode(['success' => false, 'message' => 'Aucune question trouvée']);
    exit();
}

$nombre_bonnes = 0;
$nombre_total = count($questions);

// Enregistrer les réponses
foreach ($questions as $question) {
    $reponse = isset($_POST['question_' . $question['id']]) ? strtoupper($_POST['question_' . $question['id']]) : null;
    
    if ($reponse) {
        $stmt = $pdo->prepare("INSERT INTO reponses (etudiant_id, evaluation_id, question_id, reponse) VALUES (?, ?, ?, ?)");
        $stmt->execute([$etudiant_id, $evaluation_id, $question['id'], $reponse]);
        
        if ($reponse === $question['bonne_reponse']) {
            $nombre_bonnes++;
        }
    }
}

// Calculer la note
$note = ($nombre_bonnes / $nombre_total) * 100;

// Enregistrer le résultat
$stmt = $pdo->prepare("INSERT INTO resultats (etudiant_id, evaluation_id, note, nombre_bonnes_reponses, nombre_total_questions) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$etudiant_id, $evaluation_id, $note, $nombre_bonnes, $nombre_total]);
$resultat_id = $pdo->lastInsertId();

// Mettre à jour la progression
$stmt = $pdo->prepare("
    UPDATE progression p
    JOIN lecons l ON p.lecon_id = l.id
    JOIN evaluations e ON l.id = e.lecon_id
    SET p.evaluation_reussie = ?, p.score_evaluation = ?, p.statut = 'termine', p.date_mise_a_jour = NOW()
    WHERE e.id = ? AND p.etudiant_id = ?
");
$stmt->execute([$note >= 50, $note, $evaluation_id, $etudiant_id]);

// Générer un certificat si score >= 50%
$certificat_id = null;
if ($note >= 50) {
    // Vérifier si un certificat existe déjà pour cette évaluation
    $stmt = $pdo->prepare("SELECT id FROM certificats WHERE etudiant_id = ? AND evaluation_id = ?");
    $stmt->execute([$etudiant_id, $evaluation_id]);
    
    if (!$stmt->fetch()) {
        // Récupérer les infos pour le certificat
        $stmt = $pdo->prepare("
            SELECT e.titre as eval_titre, l.titre as lecon_titre, c.titre as cours_titre, c.module_id
            FROM evaluations e
            JOIN lecons l ON e.lecon_id = l.id
            JOIN cours c ON l.cours_id = c.id
            WHERE e.id = ?
        ");
        $stmt->execute([$evaluation_id]);
        $info = $stmt->fetch();
        
        if ($info) {
            $numero = 'L2-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            $stmt = $pdo->prepare("INSERT INTO certificats (etudiant_id, evaluation_id, module_id, score_moyen, pourcentage_reussite, numero_certificat) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$etudiant_id, $evaluation_id, $info['module_id'], $note, $note, $numero]);
            $certificat_id = $pdo->lastInsertId();
        }
    }
}

echo json_encode([
    'success' => true, 
    'resultat_id' => $resultat_id,
    'note' => $note,
    'bonnes_reponses' => $nombre_bonnes,
    'total' => $nombre_total,
    'certificat_id' => $certificat_id
]);
