<?php
require_once 'authAdmin.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Jeton de securite invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        $reportId = (int) ($_POST['report_id'] ?? 0);
        $queueId = (int) ($_POST['queue_id'] ?? 0);

        if ($action === 'restore_content' || $action === 'keep_deleted') {
            if ($queueId <= 0) {
                $error = 'Element invalide.';
            } else {
                $queueStmt = $pdo->prepare('SELECT * FROM moderation_queue WHERE id = ? AND status = ? LIMIT 1');
                $queueStmt->execute([$queueId, 'pending']);
                $queue = $queueStmt->fetch(PDO::FETCH_ASSOC);

                if (!$queue) {
                    $error = 'Element introuvable dans la file de moderation.';
                } elseif ($action === 'keep_deleted') {
                    $stmt = $pdo->prepare('UPDATE moderation_queue SET status = ?, handled_at = NOW(), handled_by = ? WHERE id = ?');
                    $stmt->execute(['kept_deleted', (int) $_SESSION['user_id'], (int) $queue['id']]);

                    $stmt = $pdo->prepare('UPDATE reports SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE target_type = ? AND target_id = ? AND status = ?');
                    $stmt->execute(['resolved', (int) $_SESSION['user_id'], $queue['target_type'], (int) $queue['target_id'], 'pending']);

                    $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
                    $stmt->execute([(int) $_SESSION['user_id'], 'keep_deleted_content', 'target=' . $queue['target_type'] . ':' . $queue['target_id'], $_SERVER['REMOTE_ADDR'] ?? null]);

                    $message = 'Suppression confirmee.';
                } elseif ($action === 'restore_content') {
                    $targetType = $queue['target_type'] ?? '';
                    $targetId = (int) ($queue['target_id'] ?? 0);
                    $authorId = (int) ($queue['author_id'] ?? 0);
                    $content = (string) ($queue['target_content'] ?? '');
                    $createdAt = $queue['target_created_at'] ?? null;

                    if ($targetType === 'publication') {
                        $existsStmt = $pdo->prepare('SELECT id FROM publications WHERE id = ? LIMIT 1');
                        $existsStmt->execute([$targetId]);
                        if ($existsStmt->fetchColumn()) {
                            $error = 'Impossible de restaurer: une publication avec cet ID existe deja.';
                        } else {
                            $stmt = $pdo->prepare('INSERT INTO publications (id, utilisateur_id, contenu, created_at) VALUES (?, ?, ?, ?)');
                            $stmt->execute([$targetId, $authorId, $content, $createdAt ?? date('Y-m-d H:i:s')]);
                        }
                    } elseif ($targetType === 'commentaire') {
                        $publicationId = (int) ($queue['publication_id'] ?? 0);
                        $existsStmt = $pdo->prepare('SELECT id FROM commentaires WHERE id = ? LIMIT 1');
                        $existsStmt->execute([$targetId]);
                        if ($existsStmt->fetchColumn()) {
                            $error = 'Impossible de restaurer: un commentaire avec cet ID existe deja.';
                        } else {
                            $stmt = $pdo->prepare('INSERT INTO commentaires (id, publication_id, utilisateur_id, contenu, created_at) VALUES (?, ?, ?, ?, ?)');
                            $stmt->execute([$targetId, $publicationId, $authorId, $content, $createdAt ?? date('Y-m-d H:i:s')]);
                        }
                    } else {
                        $error = 'Type de contenu invalide.';
                    }

                    if ($error === '') {
                        $stmt = $pdo->prepare('UPDATE moderation_queue SET status = ?, handled_at = NOW(), handled_by = ? WHERE id = ?');
                        $stmt->execute(['restored', (int) $_SESSION['user_id'], (int) $queue['id']]);

                        $stmt = $pdo->prepare('UPDATE reports SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE target_type = ? AND target_id = ? AND status = ?');
                        $stmt->execute(['dismissed', (int) $_SESSION['user_id'], $targetType, $targetId, 'pending']);

                        $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
                        $stmt->execute([(int) $_SESSION['user_id'], 'restore_reported_content', 'target=' . $targetType . ':' . $targetId, $_SERVER['REMOTE_ADDR'] ?? null]);

                        $message = 'Contenu restaure.';
                    }
                }
            }
        } elseif ($action === 'delete_reported_content' || $action === 'dismiss_report') {
            if ($reportId <= 0) {
                $error = 'Signalement invalide.';
            } else {
                $stmt = $pdo->prepare('SELECT id, target_type, target_id FROM reports WHERE id = ? LIMIT 1');
                $stmt->execute([$reportId]);
                $report = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$report) {
                    $error = 'Signalement introuvable.';
                } elseif ($action === 'dismiss_report') {
                    $stmt = $pdo->prepare('UPDATE reports SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE id = ?');
                    $stmt->execute(['dismissed', (int) $_SESSION['user_id'], $reportId]);

                    $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
                    $stmt->execute([(int) $_SESSION['user_id'], 'dismiss_report', 'report_id=' . $reportId, $_SERVER['REMOTE_ADDR'] ?? null]);

                    $message = 'Signalement ignore avec succes.';
                } elseif ($action === 'delete_reported_content') {
                    $targetType = $report['target_type'] ?? '';
                    $targetId = (int) ($report['target_id'] ?? 0);
                    if ($targetType === 'publication') {
                        $stmt = $pdo->prepare('DELETE FROM publications WHERE id = ?');
                        $stmt->execute([$targetId]);
                    } elseif ($targetType === 'commentaire') {
                        $stmt = $pdo->prepare('DELETE FROM commentaires WHERE id = ?');
                        $stmt->execute([$targetId]);
                    } else {
                        $error = 'Type de contenu invalide.';
                    }

                    if ($error === '') {
                        $stmt = $pdo->prepare('UPDATE reports SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE target_type = ? AND target_id = ? AND status = ?');
                        $stmt->execute(['resolved', (int) $_SESSION['user_id'], $targetType, $targetId, 'pending']);

                        $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
                        $stmt->execute([(int) $_SESSION['user_id'], 'delete_reported_content', 'report_id=' . $reportId . '; target=' . $targetType . ':' . $targetId, $_SERVER['REMOTE_ADDR'] ?? null]);

                        $message = 'Contenu supprime et signalements clotures.';
                    }
                }
            }
        }
    }
}

$pendingReports = [];
$resolvedReports = [];

$pendingStmt = $pdo->prepare('
    SELECT r.*, u.prenom as reporter_prenom, u.nom as reporter_nom,
           CASE
               WHEN r.target_type = "publication" THEN p.contenu
               WHEN r.target_type = "commentaire" THEN c.contenu
           END as target_content,
           CASE
               WHEN r.target_type = "publication" THEN COALESCE(NULLIF(CONCAT_WS(" ", pu.prenom, pu.nom), ""), "Utilisateur supprime")
               WHEN r.target_type = "commentaire" THEN COALESCE(NULLIF(CONCAT_WS(" ", cu.prenom, cu.nom), ""), "Utilisateur supprime")
           END as target_author
    FROM reports r
    JOIN utilisateurs u ON u.id = r.reporter_id
    LEFT JOIN publications p ON r.target_type = "publication" AND r.target_id = p.id
    LEFT JOIN commentaires c ON r.target_type = "commentaire" AND r.target_id = c.id
    LEFT JOIN utilisateurs pu ON r.target_type = "publication" AND p.utilisateur_id = pu.id
    LEFT JOIN utilisateurs cu ON r.target_type = "commentaire" AND c.utilisateur_id = cu.id
    WHERE r.status = ?
    ORDER BY r.created_at DESC
');
$pendingStmt->execute(['pending']);
$pendingReports = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

$resolvedStmt = $pdo->prepare('
    SELECT r.*, u.prenom as reporter_prenom, u.nom as reporter_nom,
           CASE
               WHEN r.target_type = "publication" THEN p.contenu
               WHEN r.target_type = "commentaire" THEN c.contenu
           END as target_content,
           CASE
               WHEN r.target_type = "publication" THEN COALESCE(NULLIF(CONCAT_WS(" ", pu.prenom, pu.nom), ""), "Utilisateur supprime")
               WHEN r.target_type = "commentaire" THEN COALESCE(NULLIF(CONCAT_WS(" ", cu.prenom, cu.nom), ""), "Utilisateur supprime")
           END as target_author
    FROM reports r
    JOIN utilisateurs u ON u.id = r.reporter_id
    LEFT JOIN publications p ON r.target_type = "publication" AND r.target_id = p.id
    LEFT JOIN commentaires c ON r.target_type = "commentaire" AND r.target_id = c.id
    LEFT JOIN utilisateurs pu ON r.target_type = "publication" AND p.utilisateur_id = pu.id
    LEFT JOIN utilisateurs cu ON r.target_type = "commentaire" AND c.utilisateur_id = cu.id
    WHERE r.status IN (?, ?)
    ORDER BY r.created_at DESC
');
$resolvedStmt->execute(['resolved', 'dismissed']);
$resolvedReports = $resolvedStmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query('SELECT COUNT(*) FROM reports WHERE status = "pending"');
$pendingCount = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM reports WHERE status IN ("resolved", "dismissed")');
$resolvedCount = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM reports');
$totalReports = (int) $stmt->fetchColumn();

$queueStmt = $pdo->prepare('SELECT * FROM moderation_queue WHERE status = ? ORDER BY created_at DESC');
$queueStmt->execute(['pending']);
$moderationQueue = $queueStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trombinoscope • Moderation</title>
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
            <li><a href="admin.php">Admin IP</a></li>
            <li><a href="admin-users.php">Admin Utilisateurs</a></li>
            <li><a href="admin-reports.php" style="color: var(--accent); font-weight: 600;">Moderation</a></li>
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
            <h1>Panneau de moderation</h1>
            <p>Gerez les signalements et les contenus problematiques.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $pendingCount; ?></div>
                <div class="stat-label">Signalements en attente</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $resolvedCount; ?></div>
                <div class="stat-label">Signalements traites</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalReports; ?></div>
                <div class="stat-label">Total signalements</div>
            </div>
        </div>

        <?php if (!empty($moderationQueue)): ?>
            <div class="admin-section">
                <h2>Suppressions automatiques a valider</h2>
                <div class="reports-list">
                    <?php foreach ($moderationQueue as $item): ?>
                        <?php
                            $queueContent = (string) ($item['target_content'] ?? '');
                            $queueSnippet = substr($queueContent, 0, 200);
                            $queueSuffix = strlen($queueContent) > 200 ? '...' : '';
                            $queueType = $item['target_type'] === 'commentaire' ? 'commentaire' : 'publication';
                        ?>
                        <div class="report-item">
                            <div class="report-header">
                                <div class="report-meta">
                                    Suppression automatique d'une <?php echo htmlspecialchars($queueType, ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="report-date"><?php echo htmlspecialchars((string) ($item['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                            <div class="report-content">
                                <div class="reported-text">
                                    "<?php echo htmlspecialchars($queueSnippet . $queueSuffix, ENT_QUOTES, 'UTF-8'); ?>"
                                </div>
                            </div>
                            <div class="report-actions">
                                <form method="POST" style="display: inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="restore_content">
                                    <input type="hidden" name="queue_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">Restaurer</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="keep_deleted">
                                    <input type="hidden" name="queue_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Confirmer la suppression</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($pendingReports)): ?>
            <div class="admin-section">
                <h2>Signalements en attente</h2>
                <div class="reports-list">
                    <?php foreach ($pendingReports as $report): ?>
                        <div class="report-item">
                            <div class="report-header">
                                <div class="report-meta">
                                    <strong><?php echo htmlspecialchars($report['reporter_prenom'] . ' ' . $report['reporter_nom'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    a signale <?php echo $report['target_type'] === 'publication' ? 'une publication' : 'un commentaire'; ?>
                                    de <strong><?php echo htmlspecialchars($report['target_author'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="report-date"><?php echo htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                            <div class="report-content">
                                <div class="reported-text">
                                    "<?php
    $targetContent = (string) ($report['target_content'] ?? '');
    $snippet = substr($targetContent, 0, 200);
    $suffix = strlen($targetContent) > 200 ? '...' : '';
?>
<?php echo htmlspecialchars($snippet . $suffix, ENT_QUOTES, 'UTF-8'); ?>"
                                </div>
                                <?php if ($report['reason']): ?>
                                    <div class="report-reason">
                                        <strong>Raison :</strong> <?php echo htmlspecialchars($report['reason'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="report-actions">
                                <form method="POST" style="display: inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_reported_content">
                                    <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Supprimer le contenu</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="dismiss_report">
                                    <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">Ignorer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($resolvedReports)): ?>
            <div class="admin-section">
                <h2>Signalements traites</h2>
                <div class="reports-list">
                    <?php foreach ($resolvedReports as $report): ?>
                        <div class="report-item resolved">
                            <div class="report-header">
                                <div class="report-meta">
                                    <strong><?php echo htmlspecialchars($report['reporter_prenom'] . ' ' . $report['reporter_nom'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    a signale <?php echo $report['target_type'] === 'publication' ? 'une publication' : 'un commentaire'; ?>
                                    de <strong><?php echo htmlspecialchars($report['target_author'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="report-date"><?php echo htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="report-status <?php echo $report['status']; ?>">
                                        <?php echo $report['status'] === 'resolved' ? 'Supprime' : 'Ignore'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="report-content">
                                <div class="reported-text">
                                    "<?php
    $targetContent = (string) ($report['target_content'] ?? '');
    $snippet = substr($targetContent, 0, 200);
    $suffix = strlen($targetContent) > 200 ? '...' : '';
?>
<?php echo htmlspecialchars($snippet . $suffix, ENT_QUOTES, 'UTF-8'); ?>"
                                </div>
                                <?php if ($report['reason']): ?>
                                    <div class="report-reason">
                                        <strong>Raison :</strong> <?php echo htmlspecialchars($report['reason'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($pendingReports) && empty($resolvedReports)): ?>
            <div class="admin-section">
                <div class="empty-list">
                    <p>Aucun signalement pour le moment.</p>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>



