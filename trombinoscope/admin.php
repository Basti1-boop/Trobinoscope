<?php
require_once 'authAdmin.php';

$action = $_GET['action'] ?? '';
$message = '';
$error = '';

if (!function_exists('table_exists')) {
    function table_exists(PDO $pdo, string $table): bool
    {
        try {
            $stmt = $pdo->query('SELECT DATABASE()');
            $dbName = $stmt ? $stmt->fetchColumn() : null;
            if (!$dbName) {
                return false;
            }
            $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1');
            $stmt->execute([$dbName, $table]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

// ============================================
// OBTENIR LES IPS BLOQUEES
// ============================================
$stmt = $pdo->prepare('SELECT id, ip, blocked_at, released_at, reason FROM ip_blocks ORDER BY blocked_at DESC');
$stmt->execute();
$blockedIps = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// DEMANDES DE DEBAN IP
// ============================================
$pendingRequests = [];
if (table_exists($pdo, 'ip_unblock_requests')) {
    $stmt = $pdo->prepare(
        'SELECT r.id, r.ip, r.requested_at, u.email ' .
        'FROM ip_unblock_requests r ' .
        'JOIN utilisateurs u ON u.id = r.user_id ' .
        'WHERE r.status = ? ' .
        'ORDER BY r.requested_at DESC'
    );
    $stmt->execute(['pending']);
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// TRAITER LES ACTIONS (BAN/DEBAN)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Jeton de securite invalide.';
    } else {
        $postAction = $_POST['action'] ?? '';

        if ($postAction === 'block_ip') {
            $ip = trim($_POST['ip'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
                $error = 'Adresse IP invalide.';
            } else {
                $stmt = $pdo->prepare('SELECT id FROM ip_blocks WHERE ip = ? AND released_at IS NULL');
                $stmt->execute([$ip]);
                if ($stmt->fetch()) {
                    $error = 'Cette IP est deja bloquee.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO ip_blocks (ip, reason) VALUES (?, ?)');
                    $stmt->execute([$ip, $reason]);
                    $message = "IP $ip bloquee avec succes.";
                    header('Location: admin.php?msg=' . urlencode($message));
                    exit();
                }
            }
        } elseif ($postAction === 'unblock_ip') {
            $blockId = (int) ($_POST['block_id'] ?? 0);

            if ($blockId <= 0) {
                $error = 'ID de blocage invalide.';
            } else {
                $stmt = $pdo->prepare('UPDATE ip_blocks SET released_at = NOW() WHERE id = ?');
                $stmt->execute([$blockId]);
                $message = 'IP debloquee avec succes.';
                header('Location: admin.php?msg=' . urlencode($message));
                exit();
            }
        } elseif ($postAction === 'approve_unblock') {
            $requestId = (int) ($_POST['request_id'] ?? 0);
            if ($requestId <= 0) {
                $error = 'Demande invalide.';
            } else {
                $stmt = $pdo->prepare('SELECT ip FROM ip_unblock_requests WHERE id = ? AND status = ? LIMIT 1');
                $stmt->execute([$requestId, 'pending']);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$request) {
                    $error = 'Demande introuvable.';
                } else {
                    $ip = $request['ip'];
                    $stmt = $pdo->prepare('UPDATE ip_blocks SET released_at = NOW() WHERE ip = ? AND released_at IS NULL');
                    $stmt->execute([$ip]);
                    $stmt = $pdo->prepare('UPDATE ip_unblock_requests SET status = ?, handled_at = NOW() WHERE id = ?');
                    $stmt->execute(['approved', $requestId]);
                    $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
                    $stmt->execute([(int) $_SESSION['user_id'], 'approve_unblock', 'request_id=' . $requestId, $_SERVER['REMOTE_ADDR'] ?? null]);
                    $message = 'Demande de deban approuvee.';
                    header('Location: admin.php?msg=' . urlencode($message));
                    exit();
                }
            }
        }
    }
}

// ============================================
// AFFICHER LES MESSAGES
// ============================================
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
}

// ============================================
// OBTENIR LE NOMBRE D'UTILISATEURS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM utilisateurs');
$totalUsers = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM utilisateurs WHERE is_admin = 1');
$totalAdmins = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM ip_blocks WHERE released_at IS NULL');
$totalBlockedIps = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM publications');
$totalPublications = (int) $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trombinoscope - Admin</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/admin.css">
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
            <li><a href="admin.php" style="color: var(--accent); font-weight: 600;">Admin IP</a></li>
            <li><a href="admin-users.php">Admin Utilisateurs</a></li>
            <li><a href="logout.php" class="btn-nav">Deconnexion</a></li>
        </ul>
    </nav>

    <div class="container">

        <a href="index.php" class="back-link">Retour a l'accueil</a>

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

        <div class="admin-header">
            <h1>Panneau d'administration</h1>
            <p>Gerez les utilisateurs, les IPs bloquees et le contenu du site.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalAdmins; ?></div>
                <div class="stat-label">Administrateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalBlockedIps; ?></div>
                <div class="stat-label">IPs bloquees</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalPublications; ?></div>
                <div class="stat-label">Publications</div>
            </div>
        </div>

        <div class="admin-section">
            <h2>Bloquer une adresse IP</h2>
            <form method="POST" class="block-form">
                <?php echo csrf_field(); ?>
                <input
                    type="text"
                    name="ip"
                    placeholder="Ex: 192.168.1.1"
                    required
                    pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$"
                    title="Entrez une adresse IPv4 valide">
                <input
                    type="text"
                    name="reason"
                    placeholder="Raison du blocage (optionnel)">
                <input type="hidden" name="action" value="block_ip">
                <button type="submit" class="btn btn-primary">Bloquer l'IP</button>
            </form>
        </div>

        <div class="admin-section">
            <h2>Demandes de deban</h2>
            <?php if (empty($pendingRequests)): ?>
                <div class="empty-list">
                    <p>Aucune demande en attente.</p>
                </div>
            <?php else: ?>
                <div class="ip-list">
                    <?php foreach ($pendingRequests as $request): ?>
                        <div class="ip-item">
                            <div class="ip-info">
                                <div class="ip-address"><?php echo htmlspecialchars($request['ip'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="ip-details">
                                    Demande de <?php echo htmlspecialchars($request['email'], ENT_QUOTES, 'UTF-8'); ?>
                                    le <?php echo date('d/m/Y a H:i', strtotime($request['requested_at'])); ?>
                                </div>
                            </div>
                            <div class="ip-actions">
                                <form method="POST" style="width: 100%;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="approve_unblock">
                                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                    <button type="submit" class="btn-unblock">Approuver</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-section">
            <h2>Adresses IP bloquees</h2>

            <?php if (empty($blockedIps)): ?>
                <div class="empty-list">
                    <p>Aucune adresse IP n'est actuellement bloquee.</p>
                </div>
            <?php else: ?>
                <div class="ip-list">
                    <?php foreach ($blockedIps as $ipBlock): ?>
                        <?php
                        $id = (int) $ipBlock['id'];
                        $ip = htmlspecialchars($ipBlock['ip'], ENT_QUOTES, 'UTF-8');
                        $blockedAt = $ipBlock['blocked_at'];
                        $releasedAt = $ipBlock['released_at'];
                        $reason = htmlspecialchars($ipBlock['reason'], ENT_QUOTES, 'UTF-8');
                        $isActive = $releasedAt === null;
                        ?>
                        <div class="ip-item">
                            <div class="ip-info">
                                <div class="ip-address"><?php echo $ip; ?></div>
                                <div class="ip-details">
                                    Bloquee le <?php echo date('d/m/Y a H:i', strtotime($blockedAt)); ?>
                                    <?php if ($releasedAt !== null): ?>
                                        <br>Debloquee le <?php echo date('d/m/Y a H:i', strtotime($releasedAt)); ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($reason !== ''): ?>
                                    <div class="ip-reason">Raison : <?php echo $reason; ?></div>
                                <?php endif; ?>
                                <span class="ip-status <?php echo $isActive ? 'active' : 'released'; ?>">
                                    <?php echo $isActive ? 'Actif' : 'Debloquee'; ?>
                                </span>
                            </div>
                            <div class="ip-actions">
                                <?php if ($isActive): ?>
                                    <form method="POST" style="width: 100%;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="unblock_ip">
                                        <input type="hidden" name="block_id" value="<?php echo $id; ?>">
                                        <button type="submit" class="btn-unblock">Debloquer</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-unblock" disabled>Debloquee</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
