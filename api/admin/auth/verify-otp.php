<?php
session_start();
require_once __DIR__ . '/../../user/attendance/db_connect.php';

if (!isset($_SESSION['reset_username'])) {
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    $username = $_SESSION['reset_username'];

    if (!empty($otp)) {
        try {
            // Check OTP in the NEW separate table
            $stmt = $pdo->prepare("SELECT * FROM admin_password_resets WHERE username = ? AND otp_code = ?");
            $stmt->execute([$username, $otp]);
            $reset = $stmt->fetch();

            if ($reset) {
                $now = date('Y-m-d H:i:s');
                if ($reset['otp_expires_at'] > $now) {
                    $_SESSION['otp_verified'] = true;
                    header('Location: reset-password.php');
                    exit;
                } else {
                    $error = "OTP has expired. Please request a new one.";
                }
            } else {
                $error = "Invalid OTP code.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter the OTP.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Admin</title>
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
            text-align: center;
            letter-spacing: 5px;
            font-size: 1.2rem;
            font-weight: bold;
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
        <div class="logo">Verify <span>OTP</span></div>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px;">Enter the 6-digit code sent to the company email.</p>

        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="otp">OTP Code</label>
                <input type="text" id="otp" name="otp" placeholder="000000" maxlength="6" required autocomplete="off">
            </div>
            <button type="submit" class="btn-login">Verify OTP</button>
        </form>

        <div class="back-to-site">
            <a href="forgot-password.php"><i class="fas fa-redo"></i> Resend OTP</a>
        </div>
    </div>
</body>

</html>
