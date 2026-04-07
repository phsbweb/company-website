<?php
session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        

        $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            // Punch Buddy Protection check
            $stmt = $pdo->prepare("SELECT id, user_agent FROM device_tokens WHERE employee_id = ?");
            $stmt->execute([$user['id']]);
            $existing_token = $stmt->fetch();
            
            if ($existing_token) {
                if ($existing_token['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                    $_SESSION['error'] = "already_signed_in";
                    session_write_close();
                    header("Location: index.php");
                    exit;
                }
                // Same device? Clear the old one to issue a fresh one below
                $pdo->prepare("DELETE FROM device_tokens WHERE id = ?")->execute([$existing_token['id']]);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];

            // Device token logic: create a unique token for the device
            $token = bin2hex(random_bytes(32));
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            $stmt = $pdo->prepare("INSERT INTO device_tokens (employee_id, token, user_agent) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $user_agent]);

            // Set cookie for 30 days - Fixed: secure = true for Vercel/HTTPS
            $cookie_set = setcookie('device_token', $token, time() + (86400 * 30), "/", "", true, true);

            session_write_close();
            echo '<pre>';
            print_r($_SESSION);
            echo '</pre>';
            exit;
        } else {
            $_SESSION['error'] = "Invalid username or password.";
            session_write_close();
            header("Location: index.php?trace=login_failed");
            exit;
        }
    }

    if ($_POST['action'] === 'logout') {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?trace=no_session");
            exit;
        }

        // Clear device token from database and cookie
        if (isset($_COOKIE['device_token'])) {
            $stmt = $pdo->prepare("DELETE FROM device_tokens WHERE token = ?");
            $stmt->execute([$_COOKIE['device_token']]);
            setcookie('device_token', '', time() - 3600, '/');
        }

        session_destroy();
        header("Location: index.php?trace=logout_success");
        exit;
    }
}
