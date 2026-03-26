<?php
session_start();
require_once 'config.php';
require_once 'password_reset_mailer.php';

function table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->query('SELECT DATABASE()');
        $dbName = $stmt ? $stmt->fetchColumn() : null;
        if (!$dbName) {
            $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        }
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1'
        );
        $stmt->execute([$dbName, $table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$errors = [];
$email = '';
$remember = false;
$successMessage = '';
$loginSuccess = false;
$flashError = '';
if (isset($_SESSION['flash_error'])) {
    $flashError = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$ipTablesReady = table_exists($pdo, 'ip_blocks') && table_exists($pdo, 'ip_unblock_tokens');
$isIpBlocked = false;
if ($ipAddress !== '' && $ipTablesReady) {
    $stmt = $pdo->prepare('SELECT id FROM ip_blocks WHERE ip = ? AND released_at IS NULL LIMIT 1');
    $stmt->execute([$ipAddress]);
    $isIpBlocked = (bool) $stmt->fetchColumn();
}

function build_unblock_url(string $token): string
{
    return app_url('unblock-ip.php', ['token' => $token]);
}

if (isset($_SESSION['flash_success'])) {
    $successMessage = (string) $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $errors['csrf'] = "Jeton de securite invalide. Merci de reessayer.";
    }

    $action = $_POST['action'] ?? 'login';

    if ($action === 'request_unblock') {
        if (!$ipTablesReady) {
            $_SESSION['flash_error'] = "Le blocage IP n'est pas encore configure.";
            header('Location: login.php');
            exit();
        }
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Adresse email invalide.";
            header('Location: login.php');
            exit();
        }

        if ($ipAddress === '') {
            $_SESSION['flash_error'] = "Impossible de determiner votre adresse IP.";
            header('Location: login.php');
            exit();
        }

        try {
            $stmt = $pdo->prepare('SELECT id, prenom, nom, email FROM utilisateurs WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $stmt = $pdo->prepare('UPDATE ip_unblock_tokens SET used_at = NOW() WHERE ip = ? AND used_at IS NULL');
                $stmt->execute([$ipAddress]);

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $stmt = $pdo->prepare(
                    'INSERT INTO ip_unblock_tokens (ip, user_id, token_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
                );
                $stmt->execute([$ipAddress, (int) $user['id'], $tokenHash]);

                $fullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                $unblockUrl = build_unblock_url($token);
                send_ip_unblock_email($user['email'], $fullName !== '' ? $fullName : 'utilisateur', $unblockUrl);
            }

            $_SESSION['flash_success'] = "Si l adresse existe, un email de debloquage a ete envoye.";
            header('Location: login.php');
            exit();
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Impossible d envoyer l email de debloquage.";
            header('Location: login.php');
            exit();
        }
    }

    if ($isIpBlocked) {
        $_SESSION['flash_error'] = "Adresse IP bloquee. Debloquez l acces par email.";
        header('Location: login.php');
        exit();
    }

    if ($ipAddress !== '' && $ipTablesReady) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM tentatives_connexion WHERE ip = ? AND created_at >= (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmt->execute([$ipAddress]);
        $recentAttempts = (int) $stmt->fetchColumn();
        if ($recentAttempts > 5) {
            $stmt = $pdo->prepare('SELECT id FROM ip_blocks WHERE ip = ? AND released_at IS NULL LIMIT 1');
            $stmt->execute([$ipAddress]);
            $blockedId = $stmt->fetchColumn();
            if (!$blockedId) {
                $stmt = $pdo->prepare('INSERT INTO ip_blocks (ip, reason) VALUES (?, ?)');
                $stmt->execute([$ipAddress, 'trop de tentatives']);
            }
            $_SESSION['flash_error'] = "Adresse IP bloquee. Debloquez l acces par email.";
            header('Location: login.php');
            exit();
        }
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "<span style='color:red;'>Erreur : L'email est incorrect</span>";
    }

    if ($password === '' || strlen($password) < 6) {
        $errors['password'] = "<span style='color:red;'>Erreur : Votre mot de passe est incorrect</span>";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, prenom, nom, email, password FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->rowCount() < 1) {
            $errors['email'] = "<span style='color:red;'>Erreur : Email ou mot de passe incorrect</span>";
            if ($ipAddress !== '') {
                $stmt = $pdo->prepare('INSERT INTO tentatives_connexion (ip, email) VALUES (?, ?)');
                $stmt->execute([$ipAddress, $email]);
            }
        } else {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($password, $user['password'])) {
                $errors['password'] = "<span style='color:red;'>Erreur : Email ou mot de passe incorrect</span>";
                if ($ipAddress !== '') {
                    $stmt = $pdo->prepare('INSERT INTO tentatives_connexion (ip, email) VALUES (?, ?)');
                    $stmt->execute([$ipAddress, $email]);
                }
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_nom'] = $user['nom'];
                $loginSuccess = true;
                $successMessage = "Connexion reussie. Redirection en cours...";
                if ($ipAddress !== '') {
                    $stmt = $pdo->prepare('DELETE FROM tentatives_connexion WHERE ip = ?');
                    $stmt->execute([$ipAddress]);
                }
                if ($ipAddress !== '' && $ipTablesReady) {
                    $stmt = $pdo->prepare('DELETE FROM ip_blocks WHERE ip = ?');
                    $stmt->execute([$ipAddress]);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trombinoscope — Connexion</title>
    <?php if ($loginSuccess): ?>
        <meta http-equiv="refresh" content="2;url=index.php">
    <?php endif; ?>
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
            <li><a href="register.php" class="btn-nav">Inscription</a></li>
        </ul>
    </nav>

    <div class="container-sm">

        <?php if ($flashError !== ''): ?>
            <div class="flash flash-error">
                <?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
            <div class="flash flash-success">
                <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <?php if (!empty($errors['csrf'])): ?>
                <div class="flash flash-error">
                    <?php echo htmlspecialchars($errors['csrf'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <div class="form-title">Se connecter</div>
            <div class="form-subtitle">Bon retour parmi nous.</div>

            <?php if ($isIpBlocked): ?>
                <div class="flash flash-error">
                    Adresse IP bloquee. Demandez un email de debloquage pour recuperer l acces.
                </div>
                <form action="" method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="request_unblock">
                    <div class="form-group">
                        <label for="unblock-email">Adresse email</label>
                        <input type="email" id="unblock-email" name="email" placeholder="alice@exemple.fr" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Recevoir un email de debloquage</button>
                </form>
            <?php else: ?>
            <form action="" method="post">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="alice@exemple.fr"
                        required
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo $errors['email'] ?? ''; ?>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                    <?php echo $errors['password'] ?? ''; ?>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" value="1" <?php echo $remember ? 'checked' : ''; ?>>
                    <label for="remember">Se souvenir de moi</label>
                </div>

                <button type="submit" class="btn btn-primary">Se connecter</button>

            </form>
            <?php endif; ?>

            <div class="form-footer">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
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

