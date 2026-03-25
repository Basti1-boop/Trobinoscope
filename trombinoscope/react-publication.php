<?php
require_once 'auth.php';
require_once 'config.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

if (!csrf_validate()) {
    $_SESSION['flash_error'] = 'Jeton de securite invalide. Merci de reessayer.';
    header('Location: index.php');
    exit();
}

$publicationId = (int) ($_POST['publication_id'] ?? 0);
$reaction = $_POST['reaction'] ?? '';
if ($publicationId <= 0 || !in_array($reaction, ['like', 'dislike'], true)) {
    header('Location: index.php');
    exit();
}

if (!table_exists($pdo, 'publication_reactions')) {
    $_SESSION['flash_error'] = "Les reactions ne sont pas disponibles pour le moment.";
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare('SELECT id, utilisateur_id FROM publications WHERE id = ? LIMIT 1');
$stmt->execute([$publicationId]);
$publication = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publication) {
    header('Location: index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT reaction FROM publication_reactions WHERE publication_id = ? AND utilisateur_id = ? LIMIT 1');
$stmt->execute([$publicationId, $userId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing && $existing['reaction'] === $reaction) {
    $stmt = $pdo->prepare('DELETE FROM publication_reactions WHERE publication_id = ? AND utilisateur_id = ?');
    $stmt->execute([$publicationId, $userId]);
} elseif ($existing) {
    $stmt = $pdo->prepare('UPDATE publication_reactions SET reaction = ? WHERE publication_id = ? AND utilisateur_id = ?');
    $stmt->execute([$reaction, $publicationId, $userId]);
} else {
    $stmt = $pdo->prepare('INSERT INTO publication_reactions (publication_id, utilisateur_id, reaction) VALUES (?, ?, ?)');
    $stmt->execute([$publicationId, $userId, $reaction]);
}

header('Location: profil.php?id=' . (int) $publication['utilisateur_id']);
exit();
