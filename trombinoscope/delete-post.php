<?php
require_once 'auth.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: profil.php?id=' . $_SESSION['user_id']);
  exit();
}

if (!csrf_validate()) {
  $_SESSION['flash_error'] = 'Jeton de securite invalide. Merci de reessayer.';
  header('Location: profil.php?id=' . $_SESSION['user_id']);
  exit();
}

$postId = (int) ($_POST['id'] ?? 0);
$_SESSION['debug'] = "Tentative de suppression de la publication $postId par l'utilisateur " . ($_SESSION['user_id'] ?? 'inconnu');
if ($postId <= 0) {
  $_SESSION['flash_error'] = 'Publication introuvable.';
  header('Location: profil.php?id=' . $_SESSION['user_id'] . '&t=' . time());
  exit();
}

$stmt = $pdo->prepare('SELECT utilisateur_id FROM publications WHERE id = ?');
$stmt->execute([$postId]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
  $_SESSION['flash_error'] = 'Publication introuvable.';
  header('Location: profil.php?id=' . $_SESSION['user_id'] . '&t=' . time());
  exit();
}

if ((int) $publication['utilisateur_id'] !== (int) $_SESSION['user_id']) {
  $_SESSION['flash_error'] = "Vous n'avez pas le droit de supprimer cette publication.";
  header('Location: profil.php?id=' . $_SESSION['user_id'] . '&t=' . time());
  exit();
}

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare('DELETE FROM commentaires WHERE publication_id = ?');
  $stmt->execute([$postId]);

  $stmt = $pdo->prepare('DELETE FROM publications WHERE id = ?');
  $stmt->execute([$postId]);

  if ($stmt->rowCount() === 0) {
    throw new RuntimeException('Suppression impossible.');
  }

  $pdo->commit();
  $_SESSION['flash_success'] = 'Publication supprimee avec succes.';
  $_SESSION['debug'] = "Publication $postId supprimee.";
} catch (Exception $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  $_SESSION['flash_error'] = 'Erreur lors de la suppression.';
  $_SESSION['debug'] = "Erreur lors de la suppression de $postId: " . $e->getMessage();
}

header('Location: profil.php?id=' . $_SESSION['user_id'] . '&t=' . time());
exit();
