<?php
session_start();
require_once 'config.php';
require_once 'password_reset_mailer.php';

$flashError = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $flashError = 'Jeton de securite invalide. Merci de reessayer.';
    }

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($token === '') {
        $flashError = 'Lien invalide.';
    } elseif (strlen($newPassword) < 8) {
        $flashError = 'Le mot de passe doit contenir au moins 8 caracteres.';
    } elseif ($newPassword !== $confirmPassword) {
        $flashError = 'Les deux mots de passe ne correspondent pas.';
    }

    if ($flashError === '') {
        try {
            ensure_password_resets_table($pdo);

            $tokenHash = hash('sha256', $token);
            $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at FROM password_resets WHERE token_hash = ? LIMIT 1');
            $stmt->execute([$tokenHash]);
            $resetRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetRow) {
                $flashError = 'Lien invalide ou deja utilise.';
            } elseif (!empty($resetRow['used_at'])) {
                $flashError = 'Ce lien a deja ete utilise.';
            } elseif (strtotime((string) $resetRow['expires_at']) < time()) {
                $flashError = 'Ce lien a expire. Merci de faire une nouvelle demande.';
            } else {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('UPDATE utilisateurs SET password = ? WHERE id = ?');
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int) $resetRow['user_id']]);

                $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?');
                $stmt->execute([(int) $resetRow['id']]);

                $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
                $stmt->execute([(int) $resetRow['user_id']]);

                $pdo->commit();

                $_SESSION['flash_success'] = 'Mot de passe mis a jour. Vous pouvez maintenant vous connecter.';
                header('Location: login.php');
                exit();
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $flashError = 'Une erreur est survenue pendant la reinitialisation du mot de passe.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trombinoscope - Reinitialiser le mot de passe</title>
    <link rel="stylesheet" href="./assets/css/style.css">
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
        <?php if ($flashError !== ''): ?>
            <div class="flash flash-error"><?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-title">Changer mon mot de passe</div>
            <div class="form-subtitle">Saisissez un nouveau mot de passe.</div>

            <form action="" method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="8 caracteres minimum">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Confirmez votre mot de passe">
                </div>

                <button type="submit" class="btn btn-primary">Mettre a jour le mot de passe</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>Trombinoscope &mdash; Projet PHP &copy; <span class="footer-year"></span></p>
        </div>
    </footer>

</body>

</html>