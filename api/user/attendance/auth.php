<?php
require_once 'session_bootstrap.php';
attendanceStartSession();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        

        $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            attendanceLog('Login credentials accepted', [
                'user_id' => $user['id'],
                'username' => $user['username'],
            ]);
            
            // Punch Buddy Protection check
            $stmt = $pdo->prepare("SELECT id, user_agent FROM device_tokens WHERE employee_id = ?");
            $stmt->execute([$user['id']]);
            $existing_token = $stmt->fetch();
            
            if ($existing_token) {
                if ($existing_token['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                    $_SESSION['error'] = "already_signed_in";
                    attendanceLog('Blocked login because a different device is active', [
                        'user_id' => $user['id'],
                    ]);
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

            $cookie_set = attendanceSetDeviceTokenCookie($token);
            attendanceLog('Issued login session and device token', [
                'user_id' => $user['id'],
                'cookie_set' => $cookie_set,
                'https' => attendanceIsHttps(),
            ]);

            session_write_close();
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid username or password.";
            attendanceLog('Login failed', [
                'username' => $username,
            ]);
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
            attendanceClearDeviceTokenCookie();
        }

        attendanceLog('User logged out', [
            'user_id' => $_SESSION['user_id'],
        ]);
        session_destroy();
        header("Location: index.php?trace=logout_success");
        exit;
    }
}
