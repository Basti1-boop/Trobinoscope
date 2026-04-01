<?php
/**
 * auth-admin.php
 * Vérifier que l'utilisateur est connecté ET admin
 */

require_once 'auth.php';
require_once 'config.php';

// Récupérer le statut admin de l'utilisateur depuis la base de données
$stmt = $pdo->prepare('SELECT is_admin FROM utilisateurs WHERE id = ? LIMIT 1');
$stmt->execute([(int) $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'utilisateur n'est pas admin, le rediriger
if (!$user || !$user['is_admin']) {
    $_SESSION['flash_error'] = "Vous n'avez pas les permissions pour accéder à cette page.";
    header("Location: index.php");
    exit();
}

// Ajouter le statut admin à la session pour utilisation ultérieure
$_SESSION['is_admin'] = true;
?>