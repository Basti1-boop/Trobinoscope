<?php
session_start();
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $prenom = trim($_POST['prenom']);
  $nom = trim($_POST['nom']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $promo = $_POST['promo'];
  $specialite = trim($_POST['specialite']);
  $bio = trim($_POST['bio']);

  // Vérification des champs obligatoires
  if (empty($prenom) || empty($nom) || empty($email) || empty($password) || empty($promo)) {
    $_SESSION['flash_error'] = "Veuillez remplir tous les champs obligatoires.";
    header("Location: register.php");
    exit();
  }

  // Vérification de l'unicité de l'email
  $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetch()) {
    $_SESSION['flash_error'] = "L'adresse email est déjà utilisée. Veuillez en choisir une autre.";
    header("Location: register.php");
    exit();
  }

  // Hachage du mot de passe
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

  $avatar = 'default.svg';
  $maxFileSize = 2097152;
  $allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/avif',
  ];

  // Pendant le développement, décommentez pour inspecter la structure de $_FILES.
  // var_dump($_FILES);

  if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $avatarTmpPath = $_FILES['avatar']['tmp_name'];
    $avatarOriginalName = $_FILES['avatar']['name'];
    $avatarSize = (int) $_FILES['avatar']['size'];
    $avatarMimeType = null;

    if (function_exists('mime_content_type')) {
      $avatarMimeType = mime_content_type($avatarTmpPath);
    } elseif (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      if ($finfo !== false) {
        $avatarMimeType = finfo_file($finfo, $avatarTmpPath);
        finfo_close($finfo);
      }
    }

    // Fallback si les fonctions MIME ne sont pas disponibles sur l'environnement.
    if (!$avatarMimeType) {
      $extension = strtolower(pathinfo($avatarOriginalName, PATHINFO_EXTENSION));
      $mimeByExtension = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
      ];
      $avatarMimeType = $mimeByExtension[$extension] ?? '';
    }

    if ($avatarSize <= $maxFileSize && in_array($avatarMimeType, $allowedMimeTypes, true)) {
      $uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
      if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
      }

      $extension = strtolower(pathinfo($avatarOriginalName, PATHINFO_EXTENSION));
      $uniqueAvatarName = uniqid('avatar_', true) . ($extension ? '.' . $extension : '');
      $destination = $uploadsDir . DIRECTORY_SEPARATOR . $uniqueAvatarName;

      if (move_uploaded_file($avatarTmpPath, $destination)) {
        $avatar = $uniqueAvatarName;
      }
    }
  }

  // Insertion dans la base de données
  $stmt = $pdo->prepare("INSERT INTO utilisateurs (prenom, nom, email, password, promo, specialite, bio, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$prenom, $nom, $email, $hashedPassword, $promo, $specialite, $bio, $avatar]);

  // Redirection vers la page de connexion après inscription réussie
  header("Location: login.php");
  exit();
}

?>


<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trombinoscope — Inscription</title>
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
      <li><a href="login.php" class="btn-nav">Connexion</a></li>
    </ul>
  </nav>

  <div class="container-sm">

    <div class="flash flash-error">
      L'adresse email est déjà utilisée. Veuillez en choisir une autre.
    </div>

    <div class="form-card">
      <div class="form-title">Créer un compte</div>
      <div class="form-subtitle">Rejoignez le trombinoscope de votre promotion.</div>

      <form action="" method="POST" enctype="multipart/form-data">

        <div class="avatar-upload">
          <img src="https://api.dicebear.com/7.x/personas/svg?seed=default&backgroundColor=e2ddd6" alt="Avatar par défaut" id="preview-avatar">
          <div>
            <label for="avatar">Photo de profil</label>
            <input type="file" id="avatar" name="avatar" accept="image/*">
            <p class="form-hint">JPG, PNG, WEBP ou AVIF, 2 Mo maximum.</p>
          </div>
        </div>

        <hr class="divider">

        <div class="form-group">
          <label for="prenom">Prénom</label>
          <input type="text" id="prenom" name="prenom" placeholder="Alice" required>
        </div>

        <div class="form-group">
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" placeholder="Martin" required>
        </div>

        <div class="form-group">
          <label for="email">Adresse email</label>
          <input type="email" id="email" name="email" placeholder="alice@exemple.fr" required>
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" placeholder="8 caractères minimum" required>
          <p class="form-hint">Au moins 8 caractères.</p>
        </div>

        <div class="form-group">
          <label for="promo">Promotion</label>
          <select id="promo" name="promo" required>
            <option value="">Choisissez votre promotion</option>
            <option value="BUT1 2024">BUT1 2024</option>
            <option value="BUT2 2023">BUT2 2023</option>
            <option value="BUT3 2022">BUT3 2022</option>
          </select>
        </div>

        <div class="form-group">
          <label for="specialite">Spécialité</label>
          <input type="text" id="specialite" name="specialite" placeholder="Développeur Web, Designer...">
        </div>

        <div class="form-group">
          <label for="bio">Courte bio</label>
          <textarea id="bio" name="bio" placeholder="Parlez-vous en quelques mots..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Créer mon compte</button>

      </form>

      <div class="form-footer">
        Déjà inscrit ? <a href="login.php">Se connecter</a>
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