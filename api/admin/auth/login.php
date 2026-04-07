<?php
session_start();
include '../../user/attendance/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];

                // Log login
                require_once '../shared/logger.php';
                logAction($pdo, $user['id'], $user['username'], 'Login', 'Admin', $user['id'], 'Admin logged in successfully');

                echo '<pre>';
                print_r($_SESSION);
                echo '</pre>';
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Priority Horizon</title>
    <!-- Font Awesome -->
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
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

        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-site a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .back-to-site a:hover {
            color: var(--accent-color);
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo">Priority<span>Horizon</span> Admin</div>

        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.85rem; text-align: center; border: 1px solid #d1fae5;">
                <?php
                echo $_SESSION['success_msg'];
                unset($_SESSION['success_msg']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
                <div style="text-align: right; margin-top: 5px;">
                    <a href="forgot-password.php" style="font-size: 0.8rem; color: var(--text-muted); text-decoration: none;">Forgot Password?</a>
                </div>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="back-to-site">
            <a href="../../user/profile_page/index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>

</body>

</html>