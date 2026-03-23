<?php
require_once 'auth.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$postId = (int) ($_POST['post_id'] ?? 0);
$contenu = trim($_POST['contenu'] ?? '');

if ($postId <= 0 || $contenu === '') {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare('SELECT utilisateur_id FROM publications WHERE id = ? LIMIT 1');
$stmt->execute([$postId]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare('INSERT INTO commentaires (publication_id, utilisateur_id, contenu) VALUES (?, ?, ?)');
$stmt->execute([$postId, (int) $_SESSION['user_id'], $contenu]);

$auteurId = (int) $publication['utilisateur_id'];
header('Location: profil.php?id=' . $auteurId);
exit();
