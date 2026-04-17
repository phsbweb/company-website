<?php
ob_start();
require_once __DIR__ . '/../../env_loader.php';
ob_end_clean();

if (!function_exists('attendanceDb')) {
    function attendanceDb(): PDO
    {
        static $attendancePdo = null;

        if ($attendancePdo instanceof PDO) {
            return $attendancePdo;
        }

        date_default_timezone_set('Asia/Kuala_Lumpur');

        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT');
        if (!$port) {
            $port = ($host === 'localhost') ? '3306' : '10624';
        }

        $db = getenv('DB_NAME_ATTENDANCE') ?: 'phsb_erp';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];

        try {
            $attendancePdo = new PDO($dsn, $user, $pass, $options);
            $attendancePdo->exec("SET time_zone = '+08:00'");
        } catch (\PDOException $e) {
            throw new \PDOException("Connection failed to host '$host' on port '$port': " . $e->getMessage(), (int) $e->getCode());
        }

        return $attendancePdo;
    }
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = attendanceDb();
}
