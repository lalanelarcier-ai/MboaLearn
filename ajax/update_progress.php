<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in() || !has_role('etudiant')) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$etudiant_id = $_SESSION['user_id'];
$lecon_id = isset($_POST['lecon_id']) ? (int)$_POST['lecon_id'] : 0;
$statut = isset($_POST['statut']) ? clean_input($_POST['statut']) : 'termine';

if (!$lecon_id) {
    echo json_encode(['success' => false, 'message' => 'ID leçon manquant']);
    exit();
}

// Vérifier que la leçon existe
$stmt = $pdo->prepare("SELECT id FROM lecons WHERE id = ?");
$stmt->execute([$lecon_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Leçon non trouvée']);
    exit();
}

// Mettre à jour la progression
$stmt = $pdo->prepare("
    INSERT INTO progression (etudiant_id, lecon_id, statut, date_mise_a_jour) 
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        statut = VALUES(statut),
        date_mise_a_jour = NOW()
");
$stmt->execute([$etudiant_id, $lecon_id, $statut]);

echo json_encode(['success' => true]);
