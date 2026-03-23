<?php
// Inclure auth.php pour vérifier que l'utilisateur est connecté
require_once 'auth.php';

// Inclure config.php pour accéder à la base de données
require_once 'config.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer le contenu du formulaire
    $contenu = trim($_POST['contenu'] ?? '');

    // Vérifier que le contenu n'est pas vide
    if (empty($contenu)) {
        $_SESSION['flash_error'] = "La publication ne peut pas être vide.";
        header("Location: profil.php?id=" . $_SESSION['user_id']);
        exit();
    }

    // Récupérer l'identifiant de l'utilisateur connecté
    $utilisateur_id = $_SESSION['user_id'];

    // Insérer la publication en base de données
    $sql = "INSERT INTO publications (utilisateur_id, contenu) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$utilisateur_id, $contenu]);

    // Rediriger vers le profil de l'utilisateur
    header("Location: profil.php?id=" . $utilisateur_id);
    exit();
} else {
    // Si ce n'est pas une requête POST, rediriger vers le profil
    header("Location: profil.php?id=" . $_SESSION['user_id']);
    exit();
}
