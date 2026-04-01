<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'password_reset_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

if (!csrf_validate()) {
    $_SESSION['flash_error'] = 'Jeton de sécurité invalide. Merci de réessayer.';
    header('Location: index.php');
    exit();
}

$targetType = $_POST['target_type'] ?? '';
$targetId = (int) ($_POST['target_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!in_array($targetType, ['publication', 'commentaire'], true) || $targetId <= 0) {
    $_SESSION['flash_error'] = 'Signalement invalide.';
    header('Location: index.php');
    exit();
}

// Vérifier que l'utilisateur n'a pas déjà signalé cet élément
$stmt = $pdo->prepare('SELECT id FROM reports WHERE reporter_id = ? AND target_type = ? AND target_id = ?');
$stmt->execute([(int) $_SESSION['user_id'], $targetType, $targetId]);
if ($stmt->fetch()) {
    $_SESSION['flash_error'] = 'Vous avez déjà signalé cet élément.';
    header('Location: index.php');
    exit();
}

// Insérer le signalement
$stmt = $pdo->prepare('INSERT INTO reports (reporter_id, target_type, target_id, reason) VALUES (?, ?, ?, ?)');
$stmt->execute([(int) $_SESSION['user_id'], $targetType, $targetId, $reason]);

// Vérifier le nombre de signalements distincts pour cet élément
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT reporter_id) as report_count FROM reports WHERE target_type = ? AND target_id = ? AND status = ?');
$stmt->execute([$targetType, $targetId, 'pending']);
$reportCount = (int) $stmt->fetchColumn();

$message = 'Signalement envoyé avec succès.';

// Si 5 signalements ou plus, supprimer automatiquement
if ($reportCount >= 5) {
    if ($targetType === 'publication') {
        // Recuperer la publication avant suppression
        $stmt = $pdo->prepare('SELECT id, utilisateur_id, contenu, created_at FROM publications WHERE id = ? LIMIT 1');
        $stmt->execute([$targetId]);
        $publication = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($publication) {
            // Inserer en file de moderation si absent
            $stmt = $pdo->prepare('SELECT id FROM moderation_queue WHERE target_type = ? AND target_id = ? LIMIT 1');
            $stmt->execute(['publication', $targetId]);
            if (!$stmt->fetchColumn()) {
                $stmt = $pdo->prepare(
                    'INSERT INTO moderation_queue (target_type, target_id, publication_id, author_id, target_content, target_created_at) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    'publication',
                    (int) $publication['id'],
                    null,
                    (int) $publication['utilisateur_id'],
                    (string) $publication['contenu'],
                    $publication['created_at'] ?? null,
                ]);
            }

            // Supprimer la publication
            $stmt = $pdo->prepare('DELETE FROM publications WHERE id = ?');
            $stmt->execute([$targetId]);
        }

        // Marquer tous les signalements comme résolus
        $stmt = $pdo->prepare('UPDATE reports SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE target_type = ? AND target_id = ?');
        $stmt->execute(['resolved', (int) $_SESSION['user_id'], $targetType, $targetId]);

        // Log admin
        $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
        $stmt->execute([(int) $_SESSION['user_id'], 'auto_delete_publication', 'publication_id=' . $targetId . ', reports=' . $reportCount, $_SERVER['REMOTE_ADDR'] ?? null]);

        $message = 'Publication supprimee automatiquement suite a ' . $reportCount . ' signalements.';
    } elseif ($targetType === 'commentaire') {
        // Recuperer le commentaire avant suppression
        $stmt = $pdo->prepare('SELECT id, publication_id, utilisateur_id, contenu, created_at FROM commentaires WHERE id = ? LIMIT 1');
        $stmt->execute([$targetId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($comment) {
            // Inserer en file de moderation si absent
            $stmt = $pdo->prepare('SELECT id FROM moderation_queue WHERE target_type = ? AND target_id = ? LIMIT 1');
            $stmt->execute(['commentaire', $targetId]);
            if (!$stmt->fetchColumn()) {
                $stmt = $pdo->prepare(
                    'INSERT INTO moderation_queue (target_type, target_id, publication_id, author_id, target_content, target_created_at) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    'commentaire',
                    (int) $comment['id'],
                    (int) $comment['publication_id'],
                    (int) $comment['utilisateur_id'],
                    (string) $comment['contenu'],
                    $comment['created_at'] ?? null,
                ]);
            }

            // Supprimer le commentaire
            $stmt = $pdo->prepare('DELETE FROM commentaires WHERE id = ?');
            $stmt->execute([$targetId]);
        }

        // Marquer tous les signalements comme résolus
        $stmt = $pdo->prepare('UPDATE reports SET status = ?, resolved_at = NOW(), resolved_by = ? WHERE target_type = ? AND target_id = ?');
        $stmt->execute(['resolved', (int) $_SESSION['user_id'], $targetType, $targetId]);

        // Log admin
        $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
        $stmt->execute([(int) $_SESSION['user_id'], 'auto_delete_comment', 'comment_id=' . $targetId . ', reports=' . $reportCount, $_SERVER['REMOTE_ADDR'] ?? null]);

        $message = 'Commentaire supprime automatiquement suite a ' . $reportCount . ' signalements.';
    }

    // Notifier un admin
    try {
        $stmt = $pdo->prepare('SELECT email, prenom, nom FROM utilisateurs WHERE is_admin = 1 ORDER BY id ASC LIMIT 1');
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $adminName = trim(($admin['prenom'] ?? '') . ' ' . ($admin['nom'] ?? ''));
            $adminUrl = app_url('admin-reports.php');
            send_report_auto_delete_to_admin($admin['email'], $adminName !== '' ? $adminName : 'admin', $targetType, $adminUrl);
        }
    } catch (Throwable $e) {
        // Ne bloque pas si le mail echoue
    }
}

$_SESSION['flash_success'] = $message;
header('Location: index.php');
exit();


