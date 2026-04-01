<?php
/**
 * auth-admin.php
 * VÃ©rifier que l'utilisateur est connectÃ© ET admin
 */

require_once 'auth.php';
require_once 'config.php';

// RÃ©cupÃ©rer le statut admin de l'utilisateur depuis la base de donnÃ©es
$stmt = $pdo->prepare('SELECT is_admin FROM utilisateurs WHERE id = ? LIMIT 1');
$stmt->execute([(int) $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'utilisateur n'est pas admin, le rediriger
if (!$user || !$user['is_admin']) {
    $_SESSION['flash_error'] = "Vous n'avez pas les permissions pour accÃ©der Ã  cette page.";
    header("Location: index.php");
    exit();
}

// Ajouter le statut admin Ã  la session pour utilisation ultÃ©rieure
$_SESSION['is_admin'] = true;
?>