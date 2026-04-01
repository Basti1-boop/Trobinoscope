<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'password_reset_mailer.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$userId = (int) $_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare("SELECT id, prenom, nom, email, promo, specialite, bio, avatar FROM utilisateurs WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header('Location: logout.php');
  exit();
}

$prenom = $user['prenom'] ?? '';
$nom = $user['nom'] ?? '';
$email = $user['email'] ?? '';
$promo = $user['promo'] ?? '';
$specialite = $user['specialite'] ?? '';
$bio = $user['bio'] ?? '';
$avatar = $user['avatar'] ?? 'default.svg';

$avatarBackgrounds = ['b6e3f4', 'ffdfbf', 'd1f4d1', 'ffd5dc', 'e8d5ff', 'fff3b0', 'c0f0f0', 'ffd5b0'];
function default_avatar_url($seed, $backgrounds)
{
  $seed = trim((string) $seed);
  if ($seed === '') {
    $seed = 'Utilisateur';
  }
  $index = abs(crc32($seed)) % max(count($backgrounds), 1);
  $bg = $backgrounds[$index] ?? 'b6e3f4';
  return 'https://api.dicebear.com/7.x/personas/svg?seed=' . urlencode($seed) . '&backgroundColor=' . $bg;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_validate()) {
    $_SESSION['flash_error'] = 'Jeton de securite invalide. Merci de reessayer.';
    header('Location: profil.php?id=' . $userId);
    exit();
  }

  $action = $_POST['action'] ?? 'update_profile';

  if ($action === 'update_profile') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $promo = trim($_POST['promo'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
  }

  if ($action === 'send_password_reset') {
    try {
      ensure_password_resets_table($pdo);

      $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
      $stmt->execute([$userId]);

      $token = bin2hex(random_bytes(32));
      $tokenHash = hash('sha256', $token);

      $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
      $stmt->execute([$userId, $tokenHash]);

      $fullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
      $resetUrl = app_url('reset-password.php', ['token' => $token]);
      send_password_reset_email($user['email'], $fullName !== '' ? $fullName : 'utilisateur', $resetUrl);

      $_SESSION['flash_success'] = 'Un email de changement de mot de passe vient d etre envoye.';
      header('Location: profil.php?id=' . $userId);
      exit();
    } catch (Throwable $e) {
      $errors[] = 'Impossible d envoyer l email de changement de mot de passe. Verifiez la configuration SMTP Mailtrap dans config.php.';
    }
  }

  if ($action === 'update_profile' && ($prenom === '' || $nom === '' || $email === '' || $promo === '')) {
    $errors[] = 'Veuillez remplir tous les champs obligatoires.';
  } elseif ($action === 'update_profile' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Adresse email invalide.';
  }

  if ($action === 'update_profile' && $email !== ($user['email'] ?? '')) {
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id <> ? LIMIT 1");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
      $errors[] = 'Cette adresse email est deja utilisee.';
    }
  }

  $newAvatar = $avatar;
  if ($action === 'update_profile' && isset($_FILES['avatar']) && $_FILES['avatar']['name'] !== '') {
    $uploadError = (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_OK);
    if ($uploadError !== UPLOAD_ERR_OK) {
      $errors[] = "Le televersement de l'image a echoue (code $uploadError).";
    } else {
      $maxFileSize = 2097152;
      $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/webp',
        'image/avif',
      ];
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

      if ($avatarSize > $maxFileSize) {
        $errors[] = "L'image est trop lourde (max 2 Mo).";
      } elseif (!in_array($avatarMimeType, $allowedMimeTypes, true)) {
        $errors[] = "Format d'image non supporte. Utilisez JPG, PNG, WebP ou AVIF.";
      } else {
        $uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($uploadsDir)) {
          mkdir($uploadsDir, 0755, true);
        }

        $extension = strtolower(pathinfo($avatarOriginalName, PATHINFO_EXTENSION));
        $uniqueAvatarName = uniqid('avatar_', true) . ($extension ? '.' . $extension : '');
        $destination = $uploadsDir . DIRECTORY_SEPARATOR . $uniqueAvatarName;

        if (move_uploaded_file($avatarTmpPath, $destination)) {
          $newAvatar = $uniqueAvatarName;
        } else {
          $errors[] = "Impossible d'enregistrer l'image. Verifiez les droits du dossier uploads.";
        }
      }
    }
  }

  if ($action === 'update_profile' && empty($errors)) {
    $fields = [];
    $values = [];

    if ($prenom !== ($user['prenom'] ?? '')) {
      $fields[] = 'prenom = ?';
      $values[] = $prenom;
    }
    if ($nom !== ($user['nom'] ?? '')) {
      $fields[] = 'nom = ?';
      $values[] = $nom;
    }
    if ($email !== ($user['email'] ?? '')) {
      $fields[] = 'email = ?';
      $values[] = $email;
    }
    if ($promo !== ($user['promo'] ?? '')) {
      $fields[] = 'promo = ?';
      $values[] = $promo;
    }
    if ($specialite !== ($user['specialite'] ?? '')) {
      $fields[] = 'specialite = ?';
      $values[] = $specialite;
    }
    if ($bio !== ($user['bio'] ?? '')) {
      $fields[] = 'bio = ?';
      $values[] = $bio;
    }
    if ($newAvatar !== ($user['avatar'] ?? '')) {
      $fields[] = 'avatar = ?';
      $values[] = $newAvatar;
    }
    if (!empty($fields)) {
      $values[] = $userId;
      $sql = "UPDATE utilisateurs SET " . implode(', ', $fields) . " WHERE id = ?";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($values);
    }

    if ($prenom !== ($user['prenom'] ?? '')) {
      $_SESSION['user_prenom'] = $prenom;
    }
    if ($nom !== ($user['nom'] ?? '')) {
      $_SESSION['user_nom'] = $nom;
    }

    $_SESSION['flash_success'] = 'Votre profil a bien ete mis a jour.';
    header('Location: profil.php?id=' . $userId);
    exit();
  }

  $avatar = $newAvatar;
}

$fullName = trim($prenom . ' ' . $nom);
if ($avatar === '' || $avatar === 'default.svg') {
  $avatarPath = default_avatar_url($fullName !== '' ? $fullName : ('user-' . $userId), $avatarBackgrounds);
} else {
  $avatarPath = preg_match('/^https?:\\/\\//', $avatar) ? $avatar : './uploads/' . $avatar;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trombinoscope — Modifier mon profil</title>
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
      <li><a href="logout.php" class="btn-nav">Deconnexion</a></li>
    </ul>
  </nav>

  <div class="container-sm">

    <?php if (!empty($errors)): ?>
      <div class="flash flash-error">
        <?php echo htmlspecialchars(implode(' ', $errors), ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <div class="form-title">Modifier mon profil</div>
      <div class="form-subtitle">Ces informations sont visibles par tous les membres.</div>

      <form action="" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="avatar-upload">
          <img
            src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>"
            alt="Avatar actuel"
            id="preview-avatar">
          <div>
            <label for="avatar">Changer la photo</label>
            <input type="file" id="avatar" name="avatar" accept="image/*">
            <p class="form-hint">Laissez vide pour conserver la photo actuelle.</p>
          </div>
        </div>

        <hr class="divider">

        <div class="form-group">
          <label for="prenom">Prenom</label>
          <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="form-group">
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="form-group">
          <label for="specialite">Specialite</label>
          <input type="text" id="specialite" name="specialite" value="<?php echo htmlspecialchars($specialite, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label for="promo">Promotion</label>
          <select id="promo" name="promo" required>
            <option value="BUT1 2024" <?php echo $promo === 'BUT1 2024' ? 'selected' : ''; ?>>BUT1 2024</option>
            <option value="BUT2 2023" <?php echo $promo === 'BUT2 2023' ? 'selected' : ''; ?>>BUT2 2023</option>
            <option value="BUT3 2022" <?php echo $promo === 'BUT3 2022' ? 'selected' : ''; ?>>BUT3 2022</option>
          </select>
        </div>

        <div class="form-group">
          <label for="bio">Bio</label>
          <textarea id="bio" name="bio"><?php echo htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <hr class="divider">

        <div class="form-group">
          <label for="email">Adresse email</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <input type="hidden" name="action" value="update_profile">

        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>

      </form>

      <hr class="divider">

      <form action="" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="send_password_reset">
        <p class="form-hint">Pour changer le mot de passe, un lien de securite est envoye par email.</p>
        <button type="submit" class="btn btn-secondary">Recevoir un email pour changer mon mot de passe</button>
      </form>

      <div class="form-footer">
        <a href="profil.php?id=<?php echo (int) $_SESSION['user_id']; ?>">Annuler et retourner au profil</a>
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


