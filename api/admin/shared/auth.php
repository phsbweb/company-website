<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Dynamic redirect based on whether we are in the admin root or a subfolder
    $redirect_url = (file_exists('auth/login.php')) ? 'auth/login.php' : '../auth/login.php';
    header('Location: ' . $redirect_url);
    exit;
}
