<?php

$host = 'localhost';
$db = 'trombinoscope';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', '');
}

if (!defined('MAILTRAP_SMTP_HOST')) {
    define('MAILTRAP_SMTP_HOST', 'sandbox.smtp.mailtrap.io');
}

if (!defined('MAILTRAP_SMTP_PORT')) {
    define('MAILTRAP_SMTP_PORT', 2525);
}

if (!defined('MAILTRAP_SMTP_USERNAME')) {
    define('MAILTRAP_SMTP_USERNAME', '50051836126333');
}

if (!defined('MAILTRAP_SMTP_PASSWORD')) {
    define('MAILTRAP_SMTP_PASSWORD', '62f6fd5c32e033');
}

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

function app_base_url(): string
{
    $configuredBaseUrl = trim((string) APP_BASE_URL);
    if ($configuredBaseUrl !== '') {
        return rtrim($configuredBaseUrl, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $currentDir = realpath(__DIR__);

    if ($documentRoot && $currentDir) {
        $normalizedDocumentRoot = str_replace('\\', '/', $documentRoot);
        $normalizedCurrentDir = str_replace('\\', '/', $currentDir);

        if (strpos($normalizedCurrentDir, $normalizedDocumentRoot) === 0) {
            $relativePath = substr($normalizedCurrentDir, strlen($normalizedDocumentRoot));
            $relativePath = trim((string) $relativePath, '/');

            if ($relativePath !== '') {
                return $scheme . '://' . $host . '/' . $relativePath;
            }
        }
    }

    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/'));
    $scriptDirectory = rtrim($scriptDirectory, '/');

    if ($scriptDirectory === '' || $scriptDirectory === '.') {
        return $scheme . '://' . $host;
    }

    return $scheme . '://' . $host . $scriptDirectory;
}

function app_url(string $path = '', array $query = []): string
{
    $url = app_base_url();
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath !== '') {
        $url .= '/' . $trimmedPath;
    }

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}
