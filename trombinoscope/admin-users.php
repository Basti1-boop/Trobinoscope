<?php
require_once 'authAdmin.php';

$message = '';
$error = '';

function column_exists(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->query('SELECT DATABASE()');
        $dbName = $stmt ? $stmt->fetchColumn() : null;
        if (!$dbName) {
            return false;
        }
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1'
        );
        $stmt->execute([$dbName, $table, $column]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function format_ip_for_display(string $ip): string
{
    if ($ip === '::1') {
        return '127.0.0.1';
    }
    if (stripos($ip, '::ffff:') === 0) {
        return substr($ip, 7);
    }
    return $ip;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Jeton de securite invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        $targetId = (int) ($_POST['user_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($targetId <= 0) {
            $error = "Utilisateur invalide.";
        } elseif ($targetId === (int) $_SESSION['user_id']) {
            $error = "Vous ne pouvez pas bannir votre propre compte.";
        } else {
            if ($action === 'ban_user') {
                $stmt = $pdo->prepare('UPDATE utilisateurs SET banned_at = NOW(), ban_reason = ? WHERE id = ?');
                $stmt->execute([$reason, $targetId]);

                $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
                $stmt->execute([(int) $_SESSION['user_id'], 'ban_user', 'user_id=' . $targetId, $_SERVER['REMOTE_ADDR'] ?? null]);

                $message = "Utilisateur banni.";
            } elseif ($action === 'unban_user') {
                $stmt = $pdo->prepare('UPDATE utilisateurs SET banned_at = NULL, ban_reason = NULL WHERE id = ?');
                $stmt->execute([$targetId]);

                $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
                $stmt->execute([(int) $_SESSION['user_id'], 'unban_user', 'user_id=' . $targetId, $_SERVER['REMOTE_ADDR'] ?? null]);

                $message = "Utilisateur debanni.";
            }
        }
    }
}

$hasLastLoginIp = column_exists($pdo, 'utilisateurs', 'last_login_ip');
$selectFields = 'id, prenom, nom, email, promo, is_admin, banned_at, ban_reason';
if ($hasLastLoginIp) {
    $selectFields .= ', last_login_ip';
}

$stmt = $pdo->query('SELECT ' . $selectFields . ' FROM utilisateurs ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trombinoscope • Admin Utilisateurs</title>
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
        <li><a href="admin.php">Admin IP</a></li>
        <li><a href="admin-users.php" style="color: var(--accent); font-weight: 600;">Admin Utilisateurs</a></li>
        <li><a href="logout.php" class="btn-nav">Deconnexion</a></li>
    </ul>
</nav>

<div class="container">
    <a href="admin.php" class="back-link">← Retour admin IP</a>

    <?php if ($message !== ''): ?>
        <div class="flash flash-success">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="flash flash-error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="section-title">Gestion des utilisateurs</div>

    <div class="post-list">
        <?php if (empty($users)): ?>
            <div class="flash">Aucun utilisateur.</div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <?php
                $uid = (int) $user['id'];
                $fullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                $isAdmin = !empty($user['is_admin']);
                $isBanned = !empty($user['banned_at']);
                $lastLoginIp = $user['last_login_ip'] ?? '';
                $displayIp = $lastLoginIp !== '' ? format_ip_for_display($lastLoginIp) : '';
                ?>
                <div class="post-card">
                    <div class="post-meta">
                        <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($isAdmin): ?>
                            <span class="badge-owner">Admin</span>
                        <?php endif; ?>
                        — <?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="post-content">
                        Promo: <?php echo htmlspecialchars($user['promo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($displayIp)): ?>
                            <br>IP: <span class="mono"><?php echo htmlspecialchars($displayIp, ENT_QUOTES, 'UTF-8'); ?></span>
                            <button type="button" class="btn btn-secondary btn-sm copy-ip" data-ip="<?php echo htmlspecialchars($displayIp, ENT_QUOTES, 'UTF-8'); ?>">Copier</button>
                        <?php else: ?>
                            <br>IP: <span class="mono">Non disponible</span>
                        <?php endif; ?>
                        <?php if ($isBanned): ?>
                            <br>Compte suspendu.
                            <?php if (!empty($user['ban_reason'])): ?>
                                Raison: <?php echo htmlspecialchars($user['ban_reason'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <?php if ($uid !== (int) $_SESSION['user_id']): ?>
                            <?php if ($isBanned): ?>
                                <form method="POST" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="unban_user">
                                    <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">Debannir</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="inline-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="ban_user">
                                    <input type="hidden" name="user_id" value="<?php echo $uid; ?>">
                                    <input type="text" name="reason" placeholder="Raison (optionnel)" style="max-width: 220px;">
                                    <button type="submit" class="btn btn-danger btn-sm">Bannir</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="container">
        <p>Trombinoscope &mdash; Projet PHP &copy; <span class="footer-year"></span></p>
    </div>
</footer>

</body>
</html>
