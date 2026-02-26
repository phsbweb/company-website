<?php
session_start();
include '../../user/profile_page/includes/db.php';

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_username'])) {
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $username = $_SESSION['reset_username'];

    if (!empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                // 1. Update the admin password
                $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
                if ($stmt->execute([$hashed_password, $username])) {

                    // 2. Clear the OTP from the SEPARATE table
                    $clearStmt = $pdo->prepare("DELETE FROM admin_password_resets WHERE username = ?");
                    $clearStmt->execute([$username]);

                    // Success - Clear session
                    session_destroy();
                    session_start();
                    $_SESSION['success_msg'] = "Password reset successful! You can now log in.";
                    header('Location: login.php');
                    exit;
                } else {
                    $error = "Failed to update password.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --accent-color: #171717;
            --text-color: #171717;
            --text-muted: #737373;
            --bg-color: #fafafa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            border: 1px solid #e5e5e5;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-color);
        }

        .logo span {
            color: var(--text-muted);
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s;
            background: #fafafa;
        }

        .form-group input:focus {
            border-color: var(--accent-color);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--accent-color);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .error-msg {
            background: #fef2f2;
            color: #991b1b;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: center;
            border: 1px solid #fee2e2;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo">Reset <span>Password</span></div>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px;">Enter a new secure password for your account.</p>

        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="6">
            </div>
            <button type="submit" class="btn-login">Reset Password</button>
        </form>
    </div>
</body>

</html>