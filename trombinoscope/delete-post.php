<?php
// Ce fichier traite la suppression d'une publication.
// Récupérez l'identifiant de la publication depuis $_GET['id'],
// vérifiez la propriété, puis exécutez le DELETE. Redirigez vers le profil après suppression.

require_once 'auth.php';
require_once 'config.php';

// Récupérer l'ID de la publication
$post_id = (int) ($_GET['id'] ?? 0);

if ($post_id === 0) {
    $_SESSION['flash_error'] = "Publication introuvable.";
    header("Location: profil.php?id=" . $_SESSION['user_id']);
    exit();
}

// Récupérer la publication
$sql = "SELECT utilisateur_id FROM publications WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier que la publication existe
if (!$publication) {
    $_SESSION['flash_error'] = "Publication introuvable.";
    header("Location: profil.php?id=" . $_SESSION['user_id']);
    exit();
}

// Vérifier que l'utilisateur est propriétaire de la publication
if ((int) $publication['utilisateur_id'] !== (int) $_SESSION['user_id']) {
    $_SESSION['flash_error'] = "Vous n'avez pas le droit de supprimer cette publication.";
    header("Location: profil.php?id=" . $_SESSION['user_id']);
    exit();
}

// Exécuter la suppression
$sql = "DELETE FROM publications WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);

$_SESSION['flash_success'] = "Publication supprimée avec succès.";
header("Location: profil.php?id=" . $_SESSION['user_id']);
exit();
