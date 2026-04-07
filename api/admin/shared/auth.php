<?php
require_once __DIR__ . '/session_bootstrap.php';
adminStartSession();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    adminLog('Admin auth guard redirected to login');
    header('Location: ../auth/login.php');
    exit;
}
