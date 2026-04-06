<?php
ob_start();
require_once __DIR__ . '/../../env_loader.php';
ob_end_clean();

function log_debug($pdo, $context, $message, $user_id = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO debug_logs (context, message, user_id, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$context, $message, $user_id, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (Exception $e) {
        // Silent fail if log table doesn't exist yet
    }
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT');
if (!$port) {
     $port = ($host === 'localhost') ? '3306' : '10624';
}
$db   = getenv('DB_NAME_ATTENDANCE') ?: 'phsb_erp';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES   => false,
     PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $pdo->exec("SET time_zone = '+08:00'");
} catch (\PDOException $e) {
     throw new \PDOException("Connection failed to host '$host' on port '$port': " . $e->getMessage(), (int)$e->getCode());
}
