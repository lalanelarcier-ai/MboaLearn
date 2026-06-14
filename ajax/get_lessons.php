<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$cours_id = isset($_GET['cours_id']) ? (int)$_GET['cours_id'] : 0;

if (!$cours_id) {
    echo json_encode(['error' => 'ID cours manquant']);
    exit();
}

$stmt = $pdo->prepare("
    SELECT l.*, 
           CASE WHEN p.statut = 'termine' THEN 1 ELSE 0 END as terminee,
           p.score_evaluation,
           e.id as evaluation_id
    FROM lecons l
    LEFT JOIN progression p ON l.id = p.lecon_id AND p.etudiant_id = ?
    LEFT JOIN evaluations e ON l.id = e.lecon_id
    WHERE l.cours_id = ?
    ORDER BY l.ordre
");
$stmt->execute([$_SESSION['user_id'], $cours_id]);
$lecons = $stmt->fetchAll();

echo json_encode(['lecons' => $lecons]);
