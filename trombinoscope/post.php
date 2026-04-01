ï»¿<?php
require_once 'auth.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $_SESSION['flash_error'] = "Jeton de securite invalide. Merci de reessayer.";
        header("Location: profil.php?id=" . $_SESSION['user_id']);
        exit();
    }

    $contenu = trim($_POST['contenu'] ?? '');

    if ($contenu === '') {
        $_SESSION['flash_error'] = "La publication ne peut pas etre vide.";
        header("Location: profil.php?id=" . $_SESSION['user_id']);
        exit();
    }

    $utilisateur_id = (int) $_SESSION['user_id'];

    $sql = "INSERT INTO publications (utilisateur_id, contenu) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$utilisateur_id, $contenu]);

    header("Location: profil.php?id=" . $utilisateur_id);
    exit();
}

header("Location: profil.php?id=" . $_SESSION['user_id']);
exit();
