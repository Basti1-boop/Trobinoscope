<?php
// Ce fichier traite le formulaire de modification. Avant d'exécuter le UPDATE, vérifiez
// que la publication appartient bien à l'utilisateur connecté. Pour cela, exécutez un SELECT de la publication,
// comparez son champ utilisateur_id avec $_SESSION['user_id']. Si les identifiants ne
// correspondent pas, redirigez sans exécuter aucune modification. C'est le contrôle de propriété côté serveur.

require_once 'auth.php';
require_once 'config.php';

// Récupérer l'ID de la publication
$post_id = intval($_POST['post_id'] ?? $_GET['id'] ?? 0);

if ($post_id === 0) {
  header("Location: profil.php");
  exit();
}

// Récupérer la publication
$sql = "SELECT utilisateur_id, contenu FROM publications WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier que la publication existe
if (!$publication) {
  $_SESSION['flash_error'] = "Publication introuvable.";
  header("Location: profil.php");
  exit();
}

// Vérifier que l'utilisateur est propriétaire de la publication
if ($publication['utilisateur_id'] !== $_SESSION['user_id']) {
  $_SESSION['flash_error'] = "Vous n'avez pas le droit de modifier cette publication.";
  header("Location: profil.php?id=" . $_SESSION['user_id']);
  exit();
}

// Si c'est une requête POST, exécuter la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contenu = trim($_POST['contenu'] ?? '');

  if (empty($contenu)) {
    $_SESSION['flash_error'] = "La publication ne peut pas être vide.";
    header("Location: edit-post.php?id=" . $post_id);
    exit();
  }

  // Mettre à jour la publication
  $sql = "UPDATE publications SET contenu = ? WHERE id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$contenu, $post_id]);

  $_SESSION['flash_success'] = "Publication modifiée avec succès.";
  header("Location: profil.php?id=" . $_SESSION['user_id']);
  exit();
}

// Récupérer le contenu actuel pour le formulaire
$contenu_actuel = $publication['contenu'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trombinoscope — Modifier une publication</title>
  <link rel="stylesheet" href="./assets/css/style.css">
  <script src="./assets/js/script.js" defer></script>
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
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>

  <div class="container-sm">

    <div class="form-card">
      <div class="form-title">Modifier la publication</div>
      <div class="form-subtitle">Apportez vos corrections puis enregistrez.</div>

      <form action="" method="POST">

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
