<?php
/**
 * Configuration de la base de données
 * MboaLearn - Mini Learning Management System
 */

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'l2_lms');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Démarrer la session si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction utilitaire pour nettoyer les entrées
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
