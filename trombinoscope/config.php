<?php
/*Avant toute chose, créez un zchier config.php qui établit la connexion PDO à votre base de données. Ce
zchier sera inclus dans toutes les pages qui en ont besoin avec require_once .
Utilisez PDO avec le mode d'erreur PDO::ERRMODE_EXCEPTION . Cherchez dans la documentation PHP
comment instancier une connexion PDO et déznir son mode d'erreur. */
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


?>