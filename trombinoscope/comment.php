ï»¿<?php
require_once 'auth.php';
require_once 'config.php';

function promo_group($promo) {
    $promo = strtoupper(trim((string) $promo));
    if ($promo === '') {
        return '';
    }
    if (strpos($promo, 'BUT1') === 0 || strpos($promo, 'B1') === 0) {
        return 'B1';
    }
    if (strpos($promo, 'BUT2') === 0 || strpos($promo, 'B2') === 0) {
        return 'B2';
    }
    if (strpos($promo, 'BUT3') === 0 || strpos($promo, 'B3') === 0) {
        return 'B3';
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

if (!csrf_validate()) {
    $_SESSION['flash_error'] = 'Jeton de securite invalide. Merci de reessayer.';
    header('Location: index.php');
    exit();
}

$postId = (int) ($_POST['post_id'] ?? 0);
$contenu = trim($_POST['contenu'] ?? '');

if ($postId <= 0 || $contenu === '') {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare(
    'SELECT p.utilisateur_id, u.promo AS auteur_promo ' .
    'FROM publications p ' .
    'JOIN utilisateurs u ON u.id = p.utilisateur_id ' .
    'WHERE p.id = ? LIMIT 1'
);
$stmt->execute([$postId]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare('SELECT promo FROM utilisateurs WHERE id = ? LIMIT 1');
$stmt->execute([(int) $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
$currentGroup = promo_group($currentUser['promo'] ?? '');
$authorGroup = promo_group($publication['auteur_promo'] ?? '');

if ($currentGroup === '' || $authorGroup === '' || $currentGroup !== $authorGroup) {
    $_SESSION['flash_error'] = "Vous ne pouvez commenter que les profils de votre promo.";
    header('Location: profil.php?id=' . (int) $publication['utilisateur_id']);
    exit();
}

$stmt = $pdo->prepare('INSERT INTO commentaires (publication_id, utilisateur_id, contenu) VALUES (?, ?, ?)');
$stmt->execute([$postId, (int) $_SESSION['user_id'], $contenu]);

$auteurId = (int) $publication['utilisateur_id'];
header('Location: profil.php?id=' . $auteurId);
exit();
