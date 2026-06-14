<?php
/**
 * Authentification et vérification des rôles
 */
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Vérifier le rôle
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Protéger une page selon le rôle
function require_role($role) {
    if (!is_logged_in()) {
        header('Location: /MboaLearn/login.php');
        exit();
    }
    if (!has_role($role)) {
        header('Location: /MboaLearn/dashboard.php');
        exit();
    }
}

// Protéger une page (connexion requise)
function require_login() {
    if (!is_logged_in()) {
        header('Location: /MboaLearn/login.php');
        exit();
    }
}

// Récupérer les informations de l'utilisateur
function get_user_info() {
    global $pdo;
    if (!is_logged_in()) return null;
    
    $stmt = $pdo->prepare("SELECT id, nom, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
