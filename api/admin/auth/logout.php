<?php
require_once __DIR__ . '/../shared/session_bootstrap.php';
adminStartSession();
adminLog('Admin logout', [
    'admin_user_id' => $_SESSION['admin_user_id'] ?? null,
]);
adminClearAuthCookie();
session_destroy();
header('Location: login.php');
exit;
