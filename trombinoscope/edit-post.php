<?php
require_once 'auth.php';
require_once 'config.php';

$post_id = (int) ($_POST['post_id'] ?? $_GET['id'] ?? 0);

if ($post_id === 0) {
  header("Location: profil.php?id=" . $_SESSION['user_id']);
  exit();
}

$sql = "SELECT utilisateur_id, contenu FROM publications WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
  $_SESSION['flash_error'] = "Publication introuvable.";
  header("Location: profil.php?id=" . $_SESSION['user_id']);
  exit();
}

if ((int) $publication['utilisateur_id'] !== (int) $_SESSION['user_id']) {
  $_SESSION['flash_error'] = "Vous n'avez pas le droit de modifier cette publication.";
  header("Location: profil.php?id=" . $_SESSION['user_id']);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_validate()) {
    $_SESSION['flash_error'] = "Jeton de securite invalide. Merci de reessayer.";
    header("Location: edit-post.php?id=" . $post_id);
    exit();
  }

  $contenu = trim($_POST['contenu'] ?? '');

  if ($contenu === '') {
    $_SESSION['flash_error'] = "La publication ne peut pas etre vide.";
    header("Location: edit-post.php?id=" . $post_id);
    exit();
  }

  $sql = "UPDATE publications SET contenu = ? WHERE id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$contenu, $post_id]);

  $_SESSION['flash_success'] = "Publication modifiee avec succes.";
  header("Location: profil.php?id=" . $_SESSION['user_id']);
  exit();
}

$contenu_actuel = $publication['contenu'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trombinoscope — Modifier une publication</title>
  <link rel="stylesheet" href="./assets/css/style.css">
  <script src="./assets/js/script.js?v=20260326" defer></script>
</head>

<body>

  <nav>
    <a href="index.php" class="nav-logo">trombi<span>.</span></a>
    <button class="nav-toggle" aria-label="Ouvrir le menu">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <ul class="nav-links">
      <li><a href="index.php">Accueil</a></li>
      <li><a href="profil.php?id=<?php echo (int) $_SESSION['user_id']; ?>">Mon profil</a></li>
      <li><a href="logout.php">Deconnexion</a></li>
    </ul>
  </nav>

  <div class="container-sm">

    <div class="form-card">
      <div class="form-title">Modifier la publication</div>
      <div class="form-subtitle">Apportez vos corrections puis enregistrez.</div>

      <form action="" method="POST">

        <?php echo csrf_field(); ?>

        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

        <div class="form-group">
          <label for="contenu">Contenu</label>
          <textarea id="contenu" name="contenu" rows="5"><?php echo htmlspecialchars($contenu_actuel, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>

      </form>

      <div class="form-footer">
        <a href="profil.php?id=<?php echo $_SESSION['user_id']; ?>">Annuler</a>
      </div>
    </div>
  </div>

  <footer>
    <div class="container">
      <p>Trombinoscope &mdash; Projet PHP &copy; <span class="footer-year"></span></p>
    </div>
  </footer>

</body>

</html>

