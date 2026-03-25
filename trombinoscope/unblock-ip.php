<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['flash_success'])) {
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    unset($_SESSION['flash_error']);
}

$token = $_GET['token'] ?? '';
if ($token === '') {
    $_SESSION['flash_error'] = 'Lien invalide.';
    header('Location: login.php');
    exit();
}

$tokenHash = hash('sha256', $token);
$stmt = $pdo->prepare(
    'SELECT ip, used_at, expires_at FROM ip_unblock_tokens WHERE token_hash = ? LIMIT 1'
);
$stmt->execute([$tokenHash]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $_SESSION['flash_error'] = 'Lien invalide ou expire.';
    header('Location: login.php');
    exit();
}

if (!empty($row['used_at'])) {
    $_SESSION['flash_error'] = 'Ce lien a deja ete utilise.';
    header('Location: login.php');
    exit();
}

if (!empty($row['expires_at']) && strtotime($row['expires_at']) < time()) {
    $_SESSION['flash_error'] = 'Ce lien a expire.';
    header('Location: login.php');
    exit();
}

$ip = $row['ip'] ?? '';
if ($ip !== '') {
    $stmt = $pdo->prepare('UPDATE ip_blocks SET released_at = NOW() WHERE ip = ? AND released_at IS NULL');
    $stmt->execute([$ip]);
}

$stmt = $pdo->prepare('UPDATE ip_unblock_tokens SET used_at = NOW() WHERE token_hash = ?');
$stmt->execute([$tokenHash]);

$_SESSION['flash_success'] = 'Acces debloque. Vous pouvez vous connecter.';
header('Location: login.php');
exit();
