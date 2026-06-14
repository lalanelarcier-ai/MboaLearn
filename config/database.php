<?php
/**
 * Configuration de la base de donnees
 * MboaLearn - Mini Learning Management System
 */

// Parametres de connexion (supporte Docker/Render via env vars)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'l2_lms');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

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
    die("Erreur de connexion a la base de donnees : " . $e->getMessage());
}

// Demarrer la session si pas deja active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction utilitaire pour nettoyer les entrees
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
