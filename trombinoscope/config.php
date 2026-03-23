<?php

$host = 'localhost'; 
$db = 'trombinoscope'; 
$user = 'root'; 
$pass = 'root'; 
$charset = 'utf8mb4'; 
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrf_validate(): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';
    if ($sessionToken === '' || $postedToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $postedToken);
}

?>
