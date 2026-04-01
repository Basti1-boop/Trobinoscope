<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = "Vous devez etre connecte pour acceder a cette page.";
    header("Location: login.php");
    exit();
}

if (!function_exists('column_exists')) {
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
}

if (column_exists($pdo, 'utilisateurs', 'banned_at')) {
    $stmt = $pdo->prepare('SELECT banned_at FROM utilisateurs WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['banned_at'])) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = "Votre compte est suspendu.";
        header("Location: login.php");
        exit();
    }
}

// keep admin flag in session
$stmt = $pdo->prepare('SELECT is_admin FROM utilisateurs WHERE id = ? LIMIT 1');
$stmt->execute([(int) $_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['is_admin'] = (bool) ($row['is_admin'] ?? false);
?>
