<?php
require_once __DIR__ . '/../../env_loader.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '10624';
$db   = getenv('DB_NAME_ATTENDANCE') ?: 'phsb_erp';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $pdo->exec("SET time_zone = '+08:00'");
} catch (\PDOException $e) {
     throw new \PDOException("Connection failed to host '$host' on port '$port': " . $e->getMessage(), (int)$e->getCode());
}
